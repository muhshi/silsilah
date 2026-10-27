<?php

use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\File;

test('database backup service generates valid sql dump', function () {
    $service = new DatabaseBackupService;
    $dump = $service->generateDump();

    expect($dump)->toBeString()
        ->and($dump)->toContain('SILSILAH KELUARGA DATABASE BACKUP')
        ->and(strtoupper($dump))->toContain('TABLE')
        ->and($dump)->toContain('users');
});

test('artisan silsilah:backup command executes successfully', function () {
    $this->artisan('silsilah:backup')
        ->expectsOutputToContain('Memulai pencadangan database Silsilah...')
        ->expectsOutputToContain('Cadangan tersimpan:')
        ->assertSuccessful();

    // Verify file created in storage/app/backups
    $backupDir = storage_path('app/backups');
    $files = File::files($backupDir);
    expect(count($files))->toBeGreaterThan(0);

    // Clean up test file
    File::deleteDirectory($backupDir);
});

test('superadmin can download database backup .sql file', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.backup.download'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/sql');
    $response->assertDownload();

    $this->assertDatabaseHas('admin_activity_logs', [
        'admin_id' => $admin->id,
        'action' => 'download_database_backup',
    ]);
});

test('regular user cannot download database backup', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('admin.backup.download'));

    $response->assertForbidden();
});
