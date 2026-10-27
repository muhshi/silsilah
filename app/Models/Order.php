<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'order_id',
        'amount',
        'quota_count',
        'status',
        'payment_method',
        'reference',
        'payment_url',
        'paid_at',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'package_id' => 'integer',
            'amount' => 'integer',
            'quota_count' => 'integer',
            'paid_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, Order>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PricingPackage, Order>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(PricingPackage::class, 'package_id');
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'success']);
    }
}
