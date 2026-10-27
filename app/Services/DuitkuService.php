<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DuitkuService
{
    protected string $merchantCode;

    protected string $apiKey;

    protected bool $isProduction;

    protected string $apiUrl;

    public function __construct()
    {
        $this->merchantCode = (string) config('services.duitku.merchant_code', '');
        $this->apiKey = (string) config('services.duitku.api_key', '');
        $this->isProduction = (bool) config('services.duitku.is_production', false);
        $this->apiUrl = $this->isProduction
            ? (string) config('services.duitku.production_url')
            : (string) config('services.duitku.sandbox_url');
    }

    /**
     * Create Invoice to Duitku
     *
     * @throws Exception
     */
    public function createInvoice(
        User $user,
        int $quotaCount = 1,
        int $amount = 10000,
        ?int $packageId = null,
        ?int $originalAmount = null,
        int $discountAmount = 0,
        ?string $voucherCode = null,
        ?string $referralCode = null,
        ?int $referrerId = null,
        bool $isCustomQuota = false,
    ): Order {
        if (empty($this->merchantCode) || empty($this->apiKey)) {
            throw new Exception('Kredensial Duitku (Merchant Code / API Key) belum dikonfigurasi di server.');
        }

        $orderId = 'SILSILAH-'.date('YmdHis').'-'.strtoupper(Str::random(4));

        $order = Order::create([
            'user_id' => $user->id,
            'package_id' => $packageId,
            'order_id' => $orderId,
            'amount' => $amount,
            'original_amount' => $originalAmount ?? $amount,
            'discount_amount' => $discountAmount,
            'voucher_code' => $voucherCode,
            'referral_code' => $referralCode,
            'referrer_id' => $referrerId,
            'is_custom_quota' => $isCustomQuota,
            'quota_count' => $quotaCount,
            'status' => 'pending',
            'payment_method' => 'QRIS',
        ]);

        $timestamp = (string) round(microtime(true) * 1000);
        $stringToSign = $this->merchantCode.$timestamp;
        $signature = hash_hmac('sha256', $stringToSign, $this->apiKey);

        $callbackUrl = route('duitku.callback');
        $returnUrl = route('dashboard', ['order_id' => $orderId]);

        $productDetails = $packageId && $order->package
            ? $order->package->name
            : "Kuota {$quotaCount} Pohon Silsilah";

        $payload = [
            'paymentAmount' => $amount,
            'merchantOrderId' => $orderId,
            'productDetails' => $productDetails,
            'email' => $user->email,
            'additionalParam' => (string) $user->id,
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'expiryPeriod' => 60,
        ];

        $response = Http::timeout(15)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-duitku-merchantcode' => $this->merchantCode,
                'x-duitku-timestamp' => $timestamp,
                'x-duitku-signature' => $signature,
            ])
            ->post($this->apiUrl, $payload);

        if (! $response->successful()) {
            Log::error('Duitku API HTTP Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Gagal menghubungi gateway Duitku: '.$response->body());
        }

        $data = $response->json();

        if (isset($data['statusCode']) && $data['statusCode'] !== '00') {
            Log::error('Duitku Create Invoice Rejected', $data);
            $message = $data['statusMessage'] ?? 'Transaksi ditolak oleh Duitku.';
            throw new Exception("Duitku: {$message}");
        }

        $paymentUrl = $data['paymentUrl'] ?? null;
        $reference = $data['reference'] ?? null;

        $order->update([
            'payment_url' => $paymentUrl,
            'reference' => $reference,
            'payload' => $data,
        ]);

        return $order;
    }

    /**
     * Validate callback signature from Duitku (Supports HMAC-SHA256 and legacy MD5)
     */
    public function validateCallbackSignature(array $data): bool
    {
        $merchantCode = (string) ($data['merchantCode'] ?? '');
        $amount = (string) ($data['amount'] ?? '');
        $merchantOrderId = (string) ($data['merchantOrderId'] ?? '');
        $signature = (string) ($data['signature'] ?? '');

        if (empty($merchantCode) || empty($amount) || empty($merchantOrderId) || empty($signature)) {
            return false;
        }

        // 1. Check v2 HMAC-SHA256 signature
        $stringToSignHmac = $merchantCode.$amount.$merchantOrderId;
        $expectedHmac = hash_hmac('sha256', $stringToSignHmac, $this->apiKey);
        if (hash_equals(strtolower($expectedHmac), strtolower($signature))) {
            return true;
        }

        // 2. Fallback check legacy MD5 signature
        $stringToSignMd5 = $merchantCode.$merchantOrderId.$amount.$this->apiKey;
        $expectedMd5 = md5($stringToSignMd5);
        if (hash_equals(strtolower($expectedMd5), strtolower($signature))) {
            return true;
        }

        return false;
    }

    /**
     * Process successful payment callback from Duitku
     *
     * @throws Exception
     */
    public function processSuccessfulPayment(string $merchantOrderId, array $callbackData): Order
    {
        $order = Order::where('order_id', $merchantOrderId)->firstOrFail();

        if ($order->status === 'pending') {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $callbackData['paymentCode'] ?? $callbackData['paymentMethod'] ?? 'QRIS',
                'reference' => $callbackData['reference'] ?? $order->reference,
                'payload' => array_merge($order->payload ?? [], ['callback' => $callbackData]),
            ]);

            // 1. Add tree quota to purchasing user
            $order->user->increment('tree_quota', $order->quota_count);

            // 2. Handle Referral Reward for Referrer (+1 tree quota if this is referee's first paid order)
            if ($order->referrer_id) {
                $previousPaidCount = Order::where('user_id', $order->user_id)
                    ->where('status', 'paid')
                    ->where('id', '!=', $order->id)
                    ->count();

                if ($previousPaidCount === 0) {
                    $referrer = User::find($order->referrer_id);
                    if ($referrer) {
                        $referrer->increment('tree_quota', 1);

                        AdminActivityLog::log(
                            action: 'referral_bonus_credited',
                            description: "Referral Sukses: {$referrer->name} memperoleh bonus +1 kuota pohon karena pembeli perdana {$order->user->name} ({$order->order_id})",
                            targetType: 'User',
                            targetId: $referrer->id,
                            properties: [
                                'referee_id' => $order->user_id,
                                'order_id' => $order->order_id,
                                'bonus_quota' => 1,
                            ]
                        );

                        Log::info("Referral Bonus: Referrer #{$referrer->id} awarded +1 tree quota for order #{$order->order_id}");
                    }
                }
            }

            // 3. Update voucher used count if voucher was applied
            if ($order->voucher_code) {
                Voucher::where('code', $order->voucher_code)->increment('used_count');
            }

            Log::info("Duitku Payment Success: Order #{$order->order_id} credited {$order->quota_count} quota to User #{$order->user_id}");
        }

        return $order;
    }
}
