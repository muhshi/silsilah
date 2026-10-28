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

    /**
     * @return HasMany<TreeSuggestion>
     */
    public function suggestions(): HasMany
    {
        return $this->hasMany(TreeSuggestion::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(FamilyEvent::class);
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

    /**
     * @return array{
     *     total_members: int,
     *     male_count: int,
     *     female_count: int,
     *     alive_count: int,
     *     deceased_count: int,
     *     views_count: int,
     *     quota_limit: ?int,
     *     remaining_slots: ?int,
     *     is_premium: bool,
     * }
     */
    public function getStatistics(): array
    {
        $members = $this->members()->get(['id', 'gender', 'is_living']);

        $total = $members->count();
        $male = $members->where('gender', 'male')->count();
        $female = $members->where('gender', 'female')->count();
        $alive = $members->where('is_living', true)->count();
        $deceased = $members->where('is_living', false)->count();

        return [
            'total_members' => $total,
            'male_count' => $male,
            'female_count' => $female,
            'alive_count' => $alive,
            'deceased_count' => $deceased,
            'views_count' => (int) ($this->views_count ?? 0),
            'quota_limit' => $this->maxMembersLimit(),
            'remaining_slots' => $this->isPremium() ? null : max(0, self::FREE_MEMBER_LIMIT - $total),
            'is_premium' => $this->isPremium(),
        ];
    }
}
