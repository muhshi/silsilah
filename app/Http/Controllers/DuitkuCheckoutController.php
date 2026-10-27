<?php

namespace App\Http\Controllers;

use App\Models\PricingPackage;
use App\Services\DuitkuService;
use App\Services\PricingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DuitkuCheckoutController extends Controller
{
    public function __construct(
        protected DuitkuService $duitkuService
    ) {}

    /**
     * Initiate Duitku checkout for tree quota
     */
    public function checkout(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'package_id' => ['nullable', 'exists:pricing_packages,id'],
            'custom_quota' => ['nullable', 'integer', 'min:1', 'max:500'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();

        $package = null;
        $isCustomQuota = false;
        $quotaCount = 1;
        $amount = 10000;
        $packageId = null;

        if ($request->filled('custom_quota')) {
            $quotaCount = (int) $request->input('custom_quota');
            $amount = PricingService::calculateCustomQuotaPrice($quotaCount);
            $isCustomQuota = true;
        } else {
            if ($request->filled('package_id')) {
                $package = PricingPackage::find($request->input('package_id'));
            } else {
                $package = PricingPackage::where('slug', 'satuan')->first() ?? PricingPackage::active()->first();
            }

            if ($package) {
                if (! $package->is_active) {
                    if ($request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Paket ini sedang tidak aktif.',
                        ], 422);
                    }

                    return back()->with('error', 'Paket ini sedang tidak aktif.');
                }

                if (! $package->isAvailableNow()) {
                    $msg = 'Paket ini berada di luar periode promo yang ditentukan.';
                    if ($request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $msg,
                        ], 422);
                    }

                    return back()->with('error', $msg);
                }

                if (! $package->canUserPurchase($user)) {
                    $msg = 'Anda sudah pernah menggunakan promo ini sebelumnya.';
                    if ($request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $msg,
                        ], 422);
                    }

                    return back()->with('error', $msg);
                }

                $quotaCount = $package->tree_quota;
                $amount = $package->price;
                $packageId = $package->id;
            }
        }

        $originalAmount = $amount;
        $discountAmount = 0;
        $voucherCode = null;
        $referralCode = null;
        $referrerId = null;

        if ($request->filled('code')) {
            $codeEvaluation = PricingService::evaluateCode($request->input('code'), $originalAmount, $user, $package);
            if (! $codeEvaluation['valid']) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $codeEvaluation['message'],
                    ], 422);
                }

                return back()->with('error', $codeEvaluation['message']);
            }

            $discountAmount = $codeEvaluation['discount'];
            $amount = max(1000, $originalAmount - $discountAmount);

            if ($codeEvaluation['type'] === 'referral') {
                $referralCode = $codeEvaluation['code'];
                $referrerId = $codeEvaluation['referrer_id'];
            } elseif ($codeEvaluation['type'] === 'voucher') {
                $voucherCode = $codeEvaluation['code'];
            }
        }

        try {
            $order = $this->duitkuService->createInvoice(
                user: $user,
                quotaCount: $quotaCount,
                amount: $amount,
                packageId: $packageId,
                originalAmount: $originalAmount,
                discountAmount: $discountAmount,
                voucherCode: $voucherCode,
                referralCode: $referralCode,
                referrerId: $referrerId,
                isCustomQuota: $isCustomQuota,
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'order_id' => $order->order_id,
                    'payment_url' => $order->payment_url,
                ]);
            }

            if ($order->payment_url) {
                return redirect()->away($order->payment_url);
            }

            return back()->with('error', 'Gagal mendapatkan URL pembayaran dari Duitku.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Real-time validation endpoint for voucher or referral code
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'package_id' => ['nullable', 'exists:pricing_packages,id'],
            'custom_quota' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $user = $request->user();
        $package = null;
        $amount = 10000;

        if ($request->filled('custom_quota')) {
            $amount = PricingService::calculateCustomQuotaPrice((int) $request->input('custom_quota'));
        } elseif ($request->filled('package_id')) {
            $package = PricingPackage::find($request->input('package_id'));
            if ($package) {
                $amount = $package->price;
            }
        }

        $evaluation = PricingService::evaluateCode($request->input('code'), $amount, $user, $package);

        $finalAmount = max(1000, $amount - $evaluation['discount']);

        return response()->json([
            'valid' => $evaluation['valid'],
            'type' => $evaluation['type'],
            'code' => $evaluation['code'],
            'discount' => $evaluation['discount'],
            'original_amount' => $amount,
            'final_amount' => $finalAmount,
            'message' => $evaluation['message'],
        ]);
    }
}
