<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'tree_quota',
        'price',
        'original_price',
        'badge',
        'description',
        'is_one_time_per_user',
        'is_active',
        'starts_at',
        'ends_at',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_one_time_per_user' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'tree_quota' => 'integer',
            'price' => 'integer',
            'original_price' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Check if package is available at current moment based on schedule
     */
    public function isAvailableNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Scope to only active and currently scheduled packages sorted by sort_order
     *
     * @param  Builder<PricingPackage>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort_order');
    }

    /**
     * Check if a specific user is eligible to buy this package
     */
    public function canUserPurchase(?User $user): bool
    {
        if (! $this->isAvailableNow()) {
            return false;
        }

        if (! $this->is_one_time_per_user || ! $user) {
            return true;
        }

        return ! Order::where('user_id', $user->id)
            ->where('status', 'paid')
            ->where('package_id', $this->id)
            ->exists();
    }

    /**
     * @return HasMany<Order, PricingPackage>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'package_id');
    }
}
