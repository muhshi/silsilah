<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotificationService
{
    protected ?string $botToken;

    protected ?string $adminChatId;

    public function __construct(?string $token = null, ?string $chatId = null)
    {
        $this->botToken = $token ?? Setting::get('telegram_bot_token', config('services.telegram.bot_token'));
        $this->adminChatId = $chatId ?? Setting::get('telegram_admin_chat_id', config('services.telegram.admin_chat_id'));
    }

    /**
     * Check if Telegram notifications are configured and enabled
     */
    public function isConfigured(): bool
    {
        return ! empty($this->botToken) && ! empty($this->adminChatId);
    }

    public function isEnabled(): bool
    {
        return $this->isConfigured() && Setting::get('telegram_enabled', '1') !== '0';
    }

    public function getBotToken(): ?string
    {
        return $this->botToken;
    }

    public function getAdminChatId(): ?string
    {
        return $this->adminChatId;
    }

    /**
     * Get basic info about the configured bot from Telegram getMe
     *
     * @return array{ok: bool, username?: string, first_name?: string, error?: string}
     */
    public function getBotInfo(?string $token = null): array
    {
        $token = $token ?: $this->botToken;

        if (empty($token)) {
            return ['ok' => false, 'error' => 'Bot token belum diisi.'];
        }

        try {
            $response = Http::timeout(5)->get("https://api.telegram.org/bot{$token}/getMe");

            if (! $response->successful()) {
                return ['ok' => false, 'error' => 'Token bot tidak valid atau ditolak Telegram.'];
            }

            $data = $response->json();
            $result = $data['result'] ?? [];

            return [
                'ok' => true,
                'username' => $result['username'] ?? '',
                'first_name' => $result['first_name'] ?? '',
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Koneksi ke Telegram gagal: '.$e->getMessage()];
        }
    }

    /**
     * Automatically detect the latest chat ID from users who sent /start to the bot
     *
     * @return array{ok: bool, chat_id?: string, sender?: string, message?: string, error?: string}
     */
    public function detectChatId(?string $token = null): array
    {
        $token = $token ?: $this->botToken;

        if (empty($token)) {
            return ['ok' => false, 'error' => 'Masukkan Bot Token terlebih dahulu sebelum mendeteksi Chat ID.'];
        }

        try {
            $response = Http::timeout(5)->get("https://api.telegram.org/bot{$token}/getUpdates");

            if (! $response->successful()) {
                return ['ok' => false, 'error' => 'Gagal mengambil data dari Telegram. Pastikan Bot Token benar.'];
            }

            $data = $response->json();
            $updates = $data['result'] ?? [];

            if (empty($updates)) {
                return [
                    'ok' => false,
                    'error' => "Belum ada pesan yang diterima bot. Buka Telegram, cari bot Anda, lalu klik tombol 'Start' atau kirim pesan apa saja.",
                ];
            }

            // Get latest message with chat ID
            $latest = end($updates);
            $msg = $latest['message'] ?? $latest['my_chat_member'] ?? $latest['channel_post'] ?? null;

            if (! $msg || empty($msg['chat']['id'])) {
                return ['ok' => false, 'error' => 'Pesan diterima tetapi tidak memiliki format Chat ID yang valid.'];
            }

            $chatId = (string) $msg['chat']['id'];
            $from = $msg['from'] ?? $msg['chat'] ?? [];
            $senderName = trim(($from['first_name'] ?? '').' '.($from['last_name'] ?? ''));
            $username = $from['username'] ?? '';
            $senderDisplay = $senderName.($username ? " (@{$username})" : '');

            return [
                'ok' => true,
                'chat_id' => $chatId,
                'sender' => $senderDisplay ?: "ID {$chatId}",
                'message' => "Berhasil mendeteksi Chat ID: {$chatId} dari {$senderDisplay}!",
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Gagal mendeteksi Chat ID: '.$e->getMessage()];
        }
    }

    /**
     * Send test message to verify Telegram credentials
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(?string $token = null, ?string $chatId = null): array
    {
        $token = $token ?: $this->botToken;
        $chatId = $chatId ?: $this->adminChatId;

        if (empty($token) || empty($chatId)) {
            return ['ok' => false, 'message' => 'Bot Token dan Chat ID wajib diisi untuk melakukan tes kirim pesan.'];
        }

        $now = now()->format('d M Y H:i:s');
        $text = "🌿 <b>TES KONEKSI TELEGRAM BERHASIL</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "Halo! Bot Telegram berhasil terhubung dengan sistem <b>Silsilah Digital</b>.\n";
        $text .= "🕒 <b>Waktu Tes:</b> {$now} WIB\n";
        $text .= '🛡️ <i>Semua notifikasi transaksi QRIS dan backup database akan dikirim ke sini.</i>';

        try {
            $response = Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'message' => 'Pesan uji coba berhasil dikirim! Silakan periksa aplikasi Telegram Anda.',
                ];
            }

            $data = $response->json();
            $desc = $data['description'] ?? 'Tolak koneksi oleh server Telegram.';

            return ['ok' => false, 'message' => "Telegram API Error: {$desc}"];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Gagal menghubungi server Telegram: '.$e->getMessage()];
        }
    }

    /**
     * Send notification when an order payment is successfully completed
     */
    public function sendOrderPaidNotification(Order $order): bool
    {
        if (! $this->isEnabled()) {
            Log::debug('TelegramNotificationService: not configured or disabled, skipping notification.');

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
        if (! $this->isEnabled()) {
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
