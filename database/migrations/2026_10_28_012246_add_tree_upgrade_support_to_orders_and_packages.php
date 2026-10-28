<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('target_tree_id')->nullable()->after('package_id')->constrained('family_trees')->nullOnDelete();
            $table->string('order_type', 30)->default('quota')->after('target_tree_id');
        });

        // Insert Default Upgrade Package (Rp 5.000)
        $existing = DB::table('pricing_packages')->where('slug', 'upgrade-pohon')->first();
        if (! $existing) {
            DB::table('pricing_packages')->insert([
                'name' => 'Upgrade Pohon ke Premium',
                'slug' => 'upgrade-pohon',
                'tree_quota' => 0,
                'price' => 5000,
                'original_price' => 10000,
                'badge' => 'UPGRADE',
                'description' => 'Tingkatkan pohon standar menjadi Pohon Premium: anggota unlimited & bebas undang editor kolaborasi',
                'is_one_time_per_user' => false,
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pricing_packages')->where('slug', 'upgrade-pohon')->delete();

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['target_tree_id']);
            $table->dropColumn(['target_tree_id', 'order_type']);
        });
    }
};
