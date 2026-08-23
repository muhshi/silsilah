<?php

namespace App\Models;

use Database\Factories\MarriageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Marriage extends Model
{
    /** @use HasFactory<MarriageFactory> */
    use HasFactory;

    protected $fillable = [
        'husband_id',
        'wife_id',
        'marriage_date',
        'is_current',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'marriage_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Member, Marriage>
     */
    public function husband(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'husband_id');
    }

    /**
     * @return BelongsTo<Member, Marriage>
     */
    public function wife(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'wife_id');
    }
}
