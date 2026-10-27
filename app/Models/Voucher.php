<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_amount',
        'max_discount',
        'usage_limit',
        'used_count',
        'user_limit',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'min_amount' => 'integer',
            'max_discount' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'user_limit' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope to active and valid vouchers
     *
     * @param  Builder<Voucher>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            })
            ->where(function (Builder $q) {
                $q->whereNull('usage_limit')->orWhereRaw('used_count < usage_limit');
            });
    }

    /**
     * Calculate discount amount in Rupiah
     */
    public function calculateDiscount(int $amount): int
    {
        if ($this->type === 'percentage') {
            $discount = (int) round(($amount * $this->value) / 100);
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }

            return min($amount, $discount);
        }

        return min($amount, $this->value);
    }

    /**
     * Validate voucher eligibility for a specific amount and user
     *
     * @return array{valid: bool, discount: int, message: ?string}
     */
    public function validateFor(int $amount, ?User $user = null): array
    {
        if (! $this->is_active) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Kode voucher ini sudah tidak aktif.'];
        }

        $now = now();
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Kode voucher ini belum mulai berlaku.'];
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Masa berlaku kode voucher ini telah kedaluwarsa.'];
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Kuota pemakaian kode voucher ini telah habis.'];
        }

        if ($amount < $this->min_amount) {
            return [
                'valid' => false,
                'discount' => 0,
                'message' => 'Minimal pembelian untuk voucher ini adalah Rp '.number_format($this->min_amount, 0, ',', '.').'.',
            ];
        }

        if ($user && $this->user_limit > 0) {
            $userUsedCount = Order::where('user_id', $user->id)
                ->where('voucher_code', $this->code)
                ->where('status', 'paid')
                ->count();

            if ($userUsedCount >= $this->user_limit) {
                return ['valid' => false, 'discount' => 0, 'message' => 'Anda telah mencapai batas maksimal penggunaan voucher ini.'];
            }
        }

        $discount = $this->calculateDiscount($amount);

        return [
            'valid' => true,
            'discount' => $discount,
            'message' => null,
        ];
    }
}
