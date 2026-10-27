<?php

namespace App\Services;

use App\Models\PricingPackage;
use App\Models\User;
use App\Models\Voucher;

class PricingService
{
    /**
     * Calculate price for custom tree quota count
     */
    public static function calculateCustomQuotaPrice(int $count): int
    {
        if ($count <= 0) {
            return 10000;
        }

        if ($count <= 2) {
            return $count * 10000;
        }

        if ($count <= 4) {
            return $count * 8500;
        }

        return $count * 8000;
    }

    /**
     * Validate and apply voucher or referral code
     *
     * @return array{
     *     valid: bool,
     *     type: ?string,
     *     code: ?string,
     *     discount: int,
     *     referrer_id: ?int,
     *     voucher_id: ?int,
     *     message: ?string
     * }
     */
    public static function evaluateCode(?string $code, int $amount, ?User $user = null, ?PricingPackage $package = null): array
    {
        if (empty($code)) {
            return [
                'valid' => false,
                'type' => null,
                'code' => null,
                'discount' => 0,
                'referrer_id' => null,
                'voucher_id' => null,
                'message' => null,
            ];
        }

        $code = strtoupper(trim($code));

        // 1. Check if it's a User Referral Code
        $referrer = User::where('referral_code', $code)->first();
        if ($referrer) {
            // Cannot use self referral code
            if ($user && $referrer->id === $user->id) {
                return [
                    'valid' => false,
                    'type' => null,
                    'code' => $code,
                    'discount' => 0,
                    'referrer_id' => null,
                    'voucher_id' => null,
                    'message' => 'Anda tidak dapat menggunakan kode referral milik sendiri.',
                ];
            }

            // Cannot stack with BOGO (Beli 1 Gratis 1) package
            if ($package && $package->is_one_time_per_user) {
                return [
                    'valid' => false,
                    'type' => null,
                    'code' => $code,
                    'discount' => 0,
                    'referrer_id' => null,
                    'voucher_id' => null,
                    'message' => 'Diskon referral tidak dapat digabungkan dengan promo Beli 1 Gratis 1.',
                ];
            }

            // 15% discount for referral
            $discount = (int) round(($amount * 15) / 100);

            return [
                'valid' => true,
                'type' => 'referral',
                'code' => $code,
                'discount' => min($amount, $discount),
                'referrer_id' => $referrer->id,
                'voucher_id' => null,
                'message' => 'Kode referral aktif! Anda mendapatkan diskon 15% dan pengajak akan menerima bonus kuota.',
            ];
        }

        // 2. Check if it's a System Voucher Code
        $voucher = Voucher::where('code', $code)->first();
        if ($voucher) {
            $validation = $voucher->validateFor($amount, $user);
            if (! $validation['valid']) {
                return [
                    'valid' => false,
                    'type' => null,
                    'code' => $code,
                    'discount' => 0,
                    'referrer_id' => null,
                    'voucher_id' => null,
                    'message' => $validation['message'],
                ];
            }

            return [
                'valid' => true,
                'type' => 'voucher',
                'code' => $code,
                'discount' => $validation['discount'],
                'referrer_id' => null,
                'voucher_id' => $voucher->id,
                'message' => "Kode voucher '{$voucher->name}' berhasil diterapkan!",
            ];
        }

        return [
            'valid' => false,
            'type' => null,
            'code' => $code,
            'discount' => 0,
            'referrer_id' => null,
            'voucher_id' => null,
            'message' => 'Kode voucher atau referral tidak ditemukan.',
        ];
    }
}
