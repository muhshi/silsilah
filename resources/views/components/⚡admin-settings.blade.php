<?php

use Livewire\Component;
use App\Models\Setting;
use App\Models\AdminActivityLog;
use App\Services\TelegramNotificationService;
use Flux\Flux;

new class extends Component
{
    public string $telegram_bot_token = '';
    public string $telegram_admin_chat_id = '';
    public bool $telegram_enabled = true;
    public bool $telegram_notify_order = true;
    public bool $telegram_notify_backup = true;

    public ?string $feedbackMessage = null;
    public string $feedbackType = 'success';

    public function mount(): void
    {
        $this->telegram_bot_token = (string) Setting::get('telegram_bot_token', config('services.telegram.bot_token', ''));
        $this->telegram_admin_chat_id = (string) Setting::get('telegram_admin_chat_id', config('services.telegram.admin_chat_id', ''));
        $this->telegram_enabled = Setting::get('telegram_enabled', '1') !== '0';
        $this->telegram_notify_order = Setting::get('telegram_notify_order', '1') !== '0';
        $this->telegram_notify_backup = Setting::get('telegram_notify_backup', '1') !== '0';
    }

    public function autoDetectChatId(): void
    {
        $token = trim($this->telegram_bot_token);
        if (empty($token)) {
            $this->feedbackMessage = 'Masukkan Bot Token terlebih dahulu sebelum mendeteksi Chat ID.';
            $this->feedbackType = 'error';
            return;
        }

        $service = app(TelegramNotificationService::class);
        $result = $service->detectChatId($token);

        if ($result['ok']) {
            $this->telegram_admin_chat_id = $result['chat_id'];
            $this->saveSettings();
            $this->feedbackMessage = $result['message'];
            $this->feedbackType = 'success';
            Flux::toast(text: $result['message'], variant: 'success');
        } else {
            $this->feedbackMessage = $result['error'] ?? 'Gagal mendeteksi Chat ID.';
            $this->feedbackType = 'error';
            Flux::toast(text: $this->feedbackMessage, variant: 'danger');
        }
    }

    public function sendTestPing(): void
    {
        $token = trim($this->telegram_bot_token);
        $chatId = trim($this->telegram_admin_chat_id);

        if (empty($token) || empty($chatId)) {
            $this->feedbackMessage = 'Bot Token dan Chat ID wajib diisi sebelum mengirim pesan tes.';
            $this->feedbackType = 'error';
            return;
        }

        $service = app(TelegramNotificationService::class);
        $result = $service->testConnection($token, $chatId);

        if ($result['ok']) {
            $this->feedbackMessage = '🚀 ' . $result['message'];
            $this->feedbackType = 'success';
            Flux::toast(text: 'Pesan uji coba berhasil dikirim ke Telegram Anda!', variant: 'success');
        } else {
            $this->feedbackMessage = '❌ ' . $result['message'];
            $this->feedbackType = 'error';
            Flux::toast(text: $this->feedbackMessage, variant: 'danger');
        }
    }

    public function saveSettings(bool $withToast = true): void
    {
        $this->validate([
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_admin_chat_id' => 'nullable|string|max:100',
            'telegram_enabled' => 'boolean',
            'telegram_notify_order' => 'boolean',
            'telegram_notify_backup' => 'boolean',
        ]);

        Setting::set('telegram_bot_token', trim($this->telegram_bot_token), 'telegram');
        Setting::set('telegram_admin_chat_id', trim($this->telegram_admin_chat_id), 'telegram');
        Setting::set('telegram_enabled', $this->telegram_enabled ? '1' : '0', 'telegram');
        Setting::set('telegram_notify_order', $this->telegram_notify_order ? '1' : '0', 'telegram');
        Setting::set('telegram_notify_backup', $this->telegram_notify_backup ? '1' : '0', 'telegram');

        AdminActivityLog::log(
            'update_settings',
            "Admin " . auth()->user()->name . " memperbarui pengaturan integrasi bot Telegram",
            null,
            null,
            ['chat_id' => $this->telegram_admin_chat_id, 'enabled' => $this->telegram_enabled]
        );

        if ($withToast) {
            $this->feedbackMessage = 'Pengaturan berhasil disimpan ke sistem!';
            $this->feedbackType = 'success';
            Flux::toast(text: 'Pengaturan berhasil disimpan!', variant: 'success');
        }
    }

    public function saveAndTest(): void
    {
        $this->saveSettings(withToast: false);
        $this->sendTestPing();
    }

    public function with(): array
    {
        $isConfigured = ! empty(trim($this->telegram_bot_token)) && ! empty(trim($this->telegram_admin_chat_id));

        return [
            'isConfigured' => $isConfigured,
            'duitkuConfig' => [
                'merchant_code' => config('services.duitku.merchant_code', '-'),
                'is_production' => (bool) config('services.duitku.is_production', false),
                'callback_url' => route('duitku.callback'),
            ],
        ];
    }
};
?>

<div class="space-y-6 max-w-5xl">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-200/80 dark:border-zinc-800/80">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-sky-50 text-sky-800 dark:bg-sky-950/80 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60 mb-2">
                <span class="material-symbols-outlined text-xs">tune</span>
                Pusat Pengaturan Sistem
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Pengaturan & Integrasi Bot
            </h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola kredensial API Telegram, notifikasi instan transaksi QRIS, dan parameter gateway pembayaran.
            </p>
        </div>
    </div>

    <!-- Feedback Banner if any -->
    @if($feedbackMessage)
        <div class="p-4 rounded-2xl border text-xs flex items-center justify-between gap-3 shadow-sm transition-all
            {{ $feedbackType === 'success'
                ? 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300'
                : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/40 dark:border-red-800 dark:text-red-300' }}">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-base shrink-0">
                    {{ $feedbackType === 'success' ? 'check_circle' : 'error' }}
                </span>
                <span class="font-bold">{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form: Telegram Integration -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
                <!-- Status Top Header -->
                <div class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold text-xl shadow-inner shrink-0">
                            ✈️
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">Bot Notifikasi Telegram</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Pemberitahuan real-time pembayaran & cadangan database</p>
                        </div>
                    </div>

                    <!-- Status Pill -->
                    <div>
                        @if($isConfigured && $telegram_enabled)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/40 shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                Terhubung (Aktif)
                            </span>
                        @elseif($isConfigured && !$telegram_enabled)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300/40">
                                Dinonaktifkan
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-700">
                                Belum Terhubung
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Input Fields -->
                <div class="space-y-4">
                    <!-- Bot Token -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                Telegram Bot Token
                            </label>
                            <a href="https://t.me/BotFather" target="_blank" class="text-[11px] font-bold text-sky-600 hover:text-sky-700 dark:text-sky-400 flex items-center gap-1">
                                <span>Buka @BotFather</span>
                                <span class="material-symbols-outlined text-[12px]">open_in_new</span>
                            </a>
                        </div>
                        <flux:input wire:model="telegram_bot_token" placeholder="Contoh: 123456789:AAF9SDUsE9m7xgem..." icon="key" />
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">
                            Token API yang diperoleh dari @BotFather saat membuat bot Telegram.
                        </p>
                    </div>

                    <!-- Chat ID with Magic Auto-Detect Button -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                Telegram Admin Chat ID
                            </label>
                            <span class="text-[11px] text-zinc-500">ID Pengguna / Grup Telegram</span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <flux:input wire:model="telegram_admin_chat_id" placeholder="Contoh: 1277320023 atau -100xxxxxxxxxx" icon="user" />
                            </div>

                            <button type="button"
                                    wire:click="autoDetectChatId"
                                    wire:loading.attr="disabled"
                                    title="Deteksi Chat ID otomatis dari pesan terakhir yang masuk ke bot"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-sky-50 hover:bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:hover:bg-sky-900 dark:text-sky-300 border border-sky-200 dark:border-sky-800 font-bold text-xs shadow-xs hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer shrink-0 disabled:opacity-50">
                                <span wire:loading.remove wire:target="autoDetectChatId" class="material-symbols-outlined text-sm">auto_fix_high</span>
                                <span wire:loading wire:target="autoDetectChatId" class="material-symbols-outlined text-sm animate-spin">refresh</span>
                                <span wire:loading.remove wire:target="autoDetectChatId">Deteksi Otomatis</span>
                                <span wire:loading wire:target="autoDetectChatId">Mendeteksi...</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">
                            Klik <strong>Deteksi Otomatis</strong> setelah Anda menekan tombol <em>Start</em> di bot Telegram Anda.
                        </p>
                    </div>

                    <!-- Notification Toggles -->
                    <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 space-y-3">
                        <label class="block text-xs font-bold text-zinc-800 dark:text-zinc-200 mb-2">
                            Preferensi Jenis Peringatan:
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-3 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 cursor-pointer transition-colors">
                                <flux:checkbox wire:model="telegram_enabled" />
                                <div>
                                    <div class="text-xs font-bold text-zinc-900 dark:text-white">Master Notifikasi</div>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Aktifkan atau jeda seluruh notifikasi bot Telegram</p>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 cursor-pointer transition-colors">
                                <flux:checkbox wire:model="telegram_notify_order" />
                                <div>
                                    <div class="text-xs font-bold text-zinc-900 dark:text-white">Transaksi QRIS Sukses</div>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Kirim tiket invoice dan data pelanggan saat bayar</p>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 cursor-pointer transition-colors sm:col-span-2">
                                <flux:checkbox wire:model="telegram_notify_backup" />
                                <div>
                                    <div class="text-xs font-bold text-zinc-900 dark:text-white">Cadangan Database Harian</div>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Laporan status file snapshot database setiap pukul 02:00 WIB</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons: Save & Test Ping -->
                <div class="flex flex-col sm:flex-row items-center justify-end gap-2.5 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <button type="button"
                            wire:click="saveSettings"
                            wire:loading.attr="disabled"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-xs font-bold transition-all cursor-pointer disabled:opacity-50">
                        <span class="material-symbols-outlined text-sm">save</span>
                        <span>Simpan Pengaturan</span>
                    </button>

                    <button type="button"
                            wire:click="saveAndTest"
                            wire:loading.attr="disabled"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-blue-700 hover:from-sky-700 hover:to-blue-800 text-white text-xs font-bold shadow-md shadow-sky-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer disabled:opacity-50">
                        <span wire:loading.remove wire:target="saveAndTest" class="material-symbols-outlined text-sm">send</span>
                        <span wire:loading wire:target="saveAndTest" class="material-symbols-outlined text-sm animate-spin">refresh</span>
                        <span wire:loading.remove wire:target="saveAndTest">Simpan & Test Kirim Pesan 🚀</span>
                        <span wire:loading wire:target="saveAndTest">Mengirim Pesan...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Info: Cara Menghubungkan & Info Gateway -->
        <div class="space-y-6">
            <!-- Quick Guide Card -->
            <div class="bg-gradient-to-br from-sky-50/70 to-indigo-50/40 dark:from-sky-950/20 dark:to-indigo-950/20 rounded-3xl border border-sky-100 dark:border-sky-900/40 p-5 space-y-3.5 shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-lg">💡</span>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-sky-900 dark:text-sky-300">
                        Cara Bikin & Hubungkan Bot (1 Menit)
                    </h3>
                </div>

                <ol class="space-y-2.5 text-xs text-zinc-600 dark:text-zinc-400 pl-4 list-decimal leading-relaxed">
                    <li>
                        Buka Telegram dan cari <strong>@BotFather</strong>.
                    </li>
                    <li>
                        Ketik <code>/newbot</code>, masukkan nama bot dan username (misal: <code>silsilahApp_bot</code>).
                    </li>
                    <li>
                        Salin <strong>HTTP API Token</strong> yang diberikan oleh BotFather dan tempel di kolom <em>Bot Token</em> di samping.
                    </li>
                    <li>
                        Buka bot yang baru Anda buat di Telegram, lalu klik tombol <strong>Start</strong> atau kirim pesan <code>/start</code>.
                    </li>
                    <li>
                        Kembali ke halaman ini dan klik tombol <strong>🪄 Deteksi Otomatis</strong>, lalu klik <strong>Simpan & Test Kirim Pesan</strong>.
                    </li>
                </ol>
            </div>

            <!-- Gateway Duitku Info Card -->
            <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 p-5 space-y-3 shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-base text-zinc-500">credit_card</span>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                        Gateway Duitku QRIS
                    </h3>
                </div>

                <div class="space-y-2 text-xs divide-y divide-zinc-100 dark:divide-zinc-800">
                    <div class="flex items-center justify-between pt-1">
                        <span class="text-zinc-500 dark:text-zinc-400">Mode Server:</span>
                        <span class="font-bold px-2 py-0.5 rounded text-[10px] {{ $duitkuConfig['is_production'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                            {{ $duitkuConfig['is_production'] ? 'PRODUCTION' : 'SANDBOX' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="text-zinc-500 dark:text-zinc-400">Merchant Code:</span>
                        <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200">{{ $duitkuConfig['merchant_code'] }}</span>
                    </div>

                    <div class="pt-2">
                        <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] mb-1">Webhook Callback URL:</span>
                        <span class="font-mono text-[10px] bg-zinc-100 dark:bg-zinc-800 p-1.5 rounded block truncate select-all text-zinc-700 dark:text-zinc-300" title="{{ $duitkuConfig['callback_url'] }}">
                            {{ $duitkuConfig['callback_url'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
