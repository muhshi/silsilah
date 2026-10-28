<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_tree_id',
        'created_by',
        'title',
        'event_type',
        'event_date',
        'start_time',
        'end_time',
        'location_name',
        'location_maps_url',
        'description',
        'contact_person',
        'is_recurring_yearly',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_recurring_yearly' => 'boolean',
        ];
    }

    public function familyTree(): BelongsTo
    {
        return $this->belongsTo(FamilyTree::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->event_type) {
            'halal_bi_halal' => 'Halal bi Halal',
            'haul_akbar' => 'Haul Akbar / Jam\'i',
            'arisan' => 'Arisan Trah',
            'pertemuan' => 'Pertemuan Keluarga',
            'ziarah' => 'Ziarah Kubur Bersama',
            default => 'Agenda Keluarga',
        };
    }

    public function getTypeEmojiAttribute(): string
    {
        return match ($this->event_type) {
            'halal_bi_halal' => '🤝',
            'haul_akbar' => '🕌',
            'arisan' => '☕',
            'pertemuan' => '👥',
            'ziarah' => '🤲',
            default => '📌',
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->event_type) {
            'halal_bi_halal' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            'haul_akbar' => 'bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-800',
            'arisan' => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'pertemuan' => 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            'ziarah' => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
            default => 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700',
        };
    }
}
