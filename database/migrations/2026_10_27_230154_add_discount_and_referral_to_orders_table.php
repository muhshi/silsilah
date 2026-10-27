<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('original_amount')->nullable()->after('amount');
            $table->unsignedInteger('discount_amount')->default(0)->after('original_amount');
            $table->string('voucher_code', 50)->nullable()->after('discount_amount');
            $table->string('referral_code', 50)->nullable()->after('voucher_code');
            $table->foreignId('referrer_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            $table->boolean('is_custom_quota')->default(false)->after('quota_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropColumn([
                'original_amount',
                'discount_amount',
                'voucher_code',
                'referral_code',
                'referrer_id',
                'is_custom_quota',
            ]);
        });
    }
};
