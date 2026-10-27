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
            'tree_quota' => 'integer',
            'price' => 'integer',
            'original_price' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Scope to only active packages sorted by sort_order
     *
     * @param  Builder<PricingPackage>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Check if a specific user is eligible to buy this package
     */
    public function canUserPurchase(?User $user): bool
    {
        if (! $this->is_active) {
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
