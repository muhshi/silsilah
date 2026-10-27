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
        Schema::create('pricing_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('tree_quota');
            $table->unsignedInteger('price');
            $table->unsignedInteger('original_price')->nullable();
            $table->string('badge')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_one_time_per_user')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Insert Default Starter Packages
        DB::table('pricing_packages')->insert([
            [
                'name' => 'Promo Idul Fitri (Beli 1 Gratis 1)',
                'slug' => 'promo-idul-fitri',
                'tree_quota' => 2,
                'price' => 10000,
                'original_price' => 20000,
                'badge' => 'BELI 1 GRATIS 1',
                'description' => 'Promo Spesial Mudik: Dapatkan 2 kuota pohon seharga 1 pohon',
                'is_one_time_per_user' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Paket Satuan (1 Pohon)',
                'slug' => 'satuan',
                'tree_quota' => 1,
                'price' => 10000,
                'original_price' => null,
                'badge' => null,
                'description' => 'Tambah 1 pohon keluarga baru kapan saja',
                'is_one_time_per_user' => false,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Paket Keluarga Lengkap (3 Pohon)',
                'slug' => 'keluarga-lengkap',
                'tree_quota' => 3,
                'price' => 25000,
                'original_price' => 30000,
                'badge' => 'POPULER',
                'description' => 'Ideal untuk Pohon Ayah, Ibu, dan Pasangan/Mertua (Hemat Rp 5.000)',
                'is_one_time_per_user' => false,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Paket Trah & Paguyuban (6 Pohon)',
                'slug' => 'trah',
                'tree_quota' => 6,
                'price' => 45000,
                'original_price' => 60000,
                'badge' => 'BELI 5 GRATIS 1',
                'description' => 'Untuk reuni, sesepuh trah, dan paguyuban keluarga besar (Hemat Rp 15.000)',
                'is_one_time_per_user' => false,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_packages');
    }
};
