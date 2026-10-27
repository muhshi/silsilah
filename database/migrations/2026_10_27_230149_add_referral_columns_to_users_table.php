<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 30)->nullable()->unique()->after('remember_token');
            $table->foreignId('referred_by_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
        });

        // Generate referral codes for existing users
        $users = DB::table('users')->whereNull('referral_code')->get(['id']);
        foreach ($users as $user) {
            $code = 'SIL-'.strtoupper(Str::random(6));
            while (DB::table('users')->where('referral_code', $code)->exists()) {
                $code = 'SIL-'.strtoupper(Str::random(6));
            }
            DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropColumn(['referred_by_id', 'referral_code']);
        });
    }
};
