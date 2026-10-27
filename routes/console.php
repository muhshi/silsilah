<?php

use App\Models\Order;
use App\Models\TreeInvitation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('silsilah:cleanup', function () {
    $deletedInvites = TreeInvitation::where('created_at', '<', now()->subDays(30))->delete();
    $expiredOrders = Order::where('status', 'pending')
        ->where('created_at', '<', now()->subHours(24))
        ->update(['status' => 'expired']);

    $this->info("Pembersihan selesai: {$deletedInvites} undangan lama dihapus, {$expiredOrders} order kedaluwarsa diperbarui.");
})->purpose('Bersihkan data sementara, token undangan usang, dan order pending kedaluwarsa');

Schedule::command('silsilah:cleanup')->daily();
