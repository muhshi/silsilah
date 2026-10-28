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
    public const FREE_MEMBER_LIMIT = 100;

    /** @use HasFactory<FamilyTreeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_public',
        'is_premium',
        'views_count',
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
            'views_count' => 'integer',
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

    public function isPremium(): bool
    {
        return (bool) $this->is_premium;
    }

    public function maxMembersLimit(): ?int
    {
        return $this->isPremium() ? null : self::FREE_MEMBER_LIMIT;
    }

    public function hasReachedMemberLimit(): bool
    {
        if ($this->isPremium()) {
            return false;
        }

        return $this->members()->count() >= self::FREE_MEMBER_LIMIT;
    }

    public function canAddMembers(int $count = 1): bool
    {
        if ($this->isPremium()) {
            return true;
        }

        return ($this->members()->count() + $count) <= self::FREE_MEMBER_LIMIT;
    }

    public function remainingMemberSlots(): ?int
    {
        if ($this->isPremium()) {
            return null;
        }

        return max(0, self::FREE_MEMBER_LIMIT - $this->members()->count());
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
