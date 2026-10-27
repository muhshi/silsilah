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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name')->nullable();
            $table->string('type', 20)->default('percentage'); // 'percentage' or 'fixed'
            $table->unsignedInteger('value'); // 15 (15%) or 5000 (Rp 5.000)
            $table->unsignedInteger('min_amount')->default(0);
            $table->unsignedInteger('max_discount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('user_limit')->default(1);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert Starter Voucher
        DB::table('vouchers')->insert([
            'code' => 'SILSILAH15',
            'name' => 'Diskon Sambut Ramadan 15%',
            'type' => 'percentage',
            'value' => 15,
            'min_amount' => 10000,
            'max_discount' => 15000,
            'usage_limit' => 500,
            'used_count' => 0,
            'user_limit' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
