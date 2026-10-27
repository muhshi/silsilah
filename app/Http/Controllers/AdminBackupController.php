<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminBackupController extends Controller
{
    /**
     * Download instant database backup as .sql file
     */
    public function download(Request $request, DatabaseBackupService $backupService): StreamedResponse
    {
        $admin = $request->user();

        AdminActivityLog::log(
            'download_database_backup',
            "Admin {$admin->name} mengunduh file cadangan database (.sql)",
            null,
            null,
            ['ip' => $request->ip()]
        );

        $filename = 'silsilah-backup-'.now()->format('Y-m-d_H-i-s').'.sql';

        return response()->streamDownload(function () use ($backupService) {
            echo $backupService->generateDump();
        }, $filename, [
            'Content-Type' => 'application/sql',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
