<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DatabaseBackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
    }

    /**
     * Generate SQL dump string for the entire database
     *
     * @throws Exception
     */
    public function generateDump(): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $databaseName = $connection->getDatabaseName();
        $date = now()->format('Y-m-d H:i:s');

        $out = "-- ========================================================\n";
        $out .= "-- SILSILAH KELUARGA DATABASE BACKUP\n";
        $out .= "-- Generated At : {$date} WIB\n";
        $out .= "-- Driver       : {$driver}\n";
        $out .= "-- Database     : {$databaseName}\n";
        $out .= "-- ========================================================\n\n";

        if ($driver === 'mysql') {
            $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
            $out .= "SET NAMES utf8mb4;\n\n";

            $tableResults = DB::select("SELECT TABLE_NAME as name FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'");
            $tables = array_map(fn ($row) => (string) $row->name, $tableResults);
        } elseif ($driver === 'sqlite') {
            $out .= "PRAGMA foreign_keys = OFF;\n\n";

            $tableResults = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = array_map(fn ($row) => (string) $row->name, $tableResults);
        } else {
            $tables = Schema::getTableListing();
        }

        // Tables to skip from data dumping if desired (e.g. cache locks)
        $skipDataTables = ['sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'job_batches'];

        foreach ($tables as $table) {
            // SQLite internal tables
            if ($driver === 'sqlite' && str_starts_with($table, 'sqlite_')) {
                continue;
            }

            $out .= "-- --------------------------------------------------------\n";
            $out .= "-- Table structure & data for: `{$table}`\n";
            $out .= "-- --------------------------------------------------------\n";

            // Schema dump
            if ($driver === 'mysql') {
                $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
                if (! empty($createResult)) {
                    $row = (array) $createResult[0];
                    $createSql = $row['Create Table'] ?? '';
                    $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
                    $out .= $createSql.";\n\n";
                }
            } elseif ($driver === 'sqlite') {
                $schemaResult = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
                if (! empty($schemaResult) && ! empty($schemaResult[0]->sql)) {
                    $out .= "DROP TABLE IF EXISTS \"{$table}\";\n";
                    $out .= $schemaResult[0]->sql.";\n\n";
                }
            }

            // If table data is in skip list, skip rows
            if (in_array($table, $skipDataTables)) {
                $out .= "-- (Skipped cache/session data for {$table})\n\n";

                continue;
            }

            // Data dump in chunks
            $query = DB::table($table);
            $count = $query->count();

            if ($count === 0) {
                $out .= "-- Table `{$table}` is empty.\n\n";

                continue;
            }

            $out .= "-- Dumping {$count} records for `{$table}`\n";

            $columns = Schema::getColumnListing($table);
            $firstCol = $columns[0] ?? null;
            if ($firstCol) {
                $query->orderBy($firstCol);
            }

            $query->chunk(200, function ($rows) use (&$out, $table, $driver) {
                foreach ($rows as $row) {
                    $rowData = (array) $row;
                    $columns = array_keys($rowData);

                    $quotedColumns = array_map(function ($col) use ($driver) {
                        return $driver === 'mysql' ? "`{$col}`" : "\"{$col}\"";
                    }, $columns);

                    $values = array_map(function ($val) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        if (is_numeric($val) && ! is_string($val)) {
                            return $val;
                        }

                        return "'".addslashes((string) $val)."'";
                    }, array_values($rowData));

                    $tableQuote = $driver === 'mysql' ? "`{$table}`" : "\"{$table}\"";
                    $out .= "INSERT INTO {$tableQuote} (".implode(', ', $quotedColumns).') VALUES ('.implode(', ', $values).");\n";
                }
            });

            $out .= "\n";
        }

        if ($driver === 'mysql') {
            $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        } elseif ($driver === 'sqlite') {
            $out .= "PRAGMA foreign_keys = ON;\n";
        }

        return $out;
    }

    /**
     * Dump database to a backup file on disk
     *
     * @return array{filename: string, path: string, size: string, bytes: int}
     *
     * @throws Exception
     */
    public function dumpToFile(?string $customFilename = null): array
    {
        if (! File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }

        $filename = $customFilename ?? 'silsilah-backup-'.now()->format('Y-m-d_H-i-s').'.sql';
        $fullPath = $this->backupDir.DIRECTORY_SEPARATOR.$filename;

        $content = $this->generateDump();
        File::put($fullPath, $content);

        $bytes = (int) File::size($fullPath);
        $sizeFormatted = $this->formatBytes($bytes);

        return [
            'filename' => $filename,
            'path' => $fullPath,
            'size' => $sizeFormatted,
            'bytes' => $bytes,
        ];
    }

    /**
     * Clean up backups older than X days
     */
    public function cleanupOldBackups(int $keepDays = 7): int
    {
        if (! File::isDirectory($this->backupDir)) {
            return 0;
        }

        $deletedCount = 0;
        $files = File::files($this->backupDir);
        $threshold = now()->subDays($keepDays)->timestamp;

        foreach ($files as $file) {
            try {
                if ($file->getExtension() === 'sql' && $file->getMTime() < $threshold) {
                    File::delete($file->getPathname());
                    $deletedCount++;
                }
            } catch (Throwable) {
                // ignore deletion error
            }
        }

        return $deletedCount;
    }

    /**
     * Get list of recent backups on disk
     *
     * @return array<int, array{filename: string, size: string, created_at: string, path: string}>
     */
    public function listRecentBackups(int $limit = 10): array
    {
        if (! File::isDirectory($this->backupDir)) {
            return [];
        }

        $files = File::files($this->backupDir);
        usort($files, fn ($a, $b) => $b->getMTime() <=> $a->getMTime());

        $list = [];
        foreach (array_slice($files, 0, $limit) as $file) {
            if ($file->getExtension() === 'sql') {
                $list[] = [
                    'filename' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'created_at' => date('d M Y H:i:s', $file->getMTime()),
                    'path' => $file->getPathname(),
                ];
            }
        }

        return $list;
    }

    /**
     * Helper to format bytes into readable KB/MB
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
