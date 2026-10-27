<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotificationService
{
    protected ?string $botToken;

    protected ?string $adminChatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->adminChatId = config('services.telegram.admin_chat_id');
    }

    /**
     * Check if Telegram notifications are configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->botToken) && ! empty($this->adminChatId);
    }

    /**
     * Send notification when an order payment is successfully completed
     */
    public function sendOrderPaidNotification(Order $order): bool
    {
        if (! $this->isConfigured()) {
            Log::debug('TelegramNotificationService: bot_token or admin_chat_id not configured, skipping notification.');

            return false;
        }

        try {
            $user = $order->user;
            $userName = $user ? $user->name : 'Pelanggan';
            $userEmail = $user ? $user->email : '-';
            $packageName = $order->package ? $order->package->name : 'Kuota Tambahan';
            $amountFormatted = number_format($order->amount, 0, ',', '.');
            $paidAt = $order->paid_at ? $order->paid_at->format('d M Y H:i:s') : now()->format('d M Y H:i:s');
            $paymentMethod = $order->payment_method ?? 'QRIS';

            $message = "🔔 <b>PEMBAYARAN DITERIMA ({$paymentMethod})</b>\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "🧾 <b>Order ID:</b> <code>#{$order->order_id}</code>\n";
            $message .= "👤 <b>Pelanggan:</b> {$userName}\n";
            $message .= "📧 <b>Email:</b> <code>{$userEmail}</code>\n";
            $message .= "📦 <b>Paket:</b> {$packageName}\n";
            $message .= "🌳 <b>Kuota Masuk:</b> +{$order->quota_count} Pohon\n";
            $message .= "💰 <b>Nominal:</b> <b>Rp {$amountFormatted}</b>\n";
            $message .= "🕒 <b>Waktu:</b> {$paidAt} WIB\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= '🚀 <i>Sistem Silsilah Keluarga Digital</i>';

            return $this->sendMessage($message);
        } catch (Throwable $e) {
            Log::warning('TelegramNotificationService failed: '.$e->getMessage(), [
                'order_id' => $order->order_id,
            ]);

            return false;
        }
    }

    /**
     * Send notification for database backup status
     */
    public function sendBackupNotification(string $filename, string $fileSize): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $now = now()->format('d M Y H:i:s');
            $message = "💾 <b>CADANGAN DATABASE BERHASIL</b>\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "📁 <b>File:</b> <code>{$filename}</code>\n";
            $message .= "📊 <b>Ukuran:</b> {$fileSize}\n";
            $message .= "🕒 <b>Waktu:</b> {$now} WIB\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= '🛡️ <i>Sistem Otomasi Silsilah</i>';

            return $this->sendMessage($message);
        } catch (Throwable $e) {
            Log::warning('TelegramNotificationService backup notification failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send raw message via Telegram Bot API
     */
    protected function sendMessage(string $htmlText): bool
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $response = Http::timeout(5)->post($url, [
            'chat_id' => $this->adminChatId,
            'text' => $htmlText,
            'parse_mode' => 'HTML',
        ]);

        if (! $response->successful()) {
            Log::warning('Telegram API responded with error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }
}
