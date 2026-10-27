<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;
use Throwable;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'silsilah:backup {--notify : Kirim notifikasi bot Telegram jika terkonfigurasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cadangkan database aplikasi ke file .sql lokal dan bersihkan cadangan usang';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService, TelegramNotificationService $telegramService): int
    {
        $this->info('Memulai pencadangan database Silsilah...');

        try {
            $result = $backupService->dumpToFile();
            $this->info("Cadangan tersimpan: {$result['filename']} ({$result['size']})");

            $cleaned = $backupService->cleanupOldBackups(7);
            if ($cleaned > 0) {
                $this->info("Membersihkan {$cleaned} file cadangan lama (>7 hari).");
            }

            if ($this->option('notify') && $telegramService->isConfigured()) {
                $telegramService->sendBackupNotification($result['filename'], $result['size']);
                $this->info('Notifikasi bot Telegram terkirim ke administrator.');
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Gagal membuat cadangan database: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
