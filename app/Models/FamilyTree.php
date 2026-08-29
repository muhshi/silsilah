<?php

namespace App\Models;

use Database\Factories\FamilyTreeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FamilyTree extends Model
{
    /** @use HasFactory<FamilyTreeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_public',
        'is_premium',
        'view_password',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_premium' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FamilyTree $tree) {
            if (empty($tree->slug)) {
                $base = Str::slug($tree->name);
                $slug = $base;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$counter++;
                }
                $tree->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /**
     * @return HasMany<Member>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
