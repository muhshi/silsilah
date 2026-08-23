<?php

namespace App\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    protected $fillable = [
        'family_tree_id',
        'first_name',
        'last_name',
        'gender',
        'is_living',
        'birth_date',
        'death_date',
        'birth_place',
        'death_place',
        'father_id',
        'mother_id',
        'photo',
        'avatar_id',
        'facebook',
        'instagram',
        'whatsapp',
        'address',
        'phone_home',
        'profession',
        'company',
        'interests',
        'bio',
        'order',
        'external_family_tree_link',
        'member_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_living' => 'boolean',
            'birth_date' => 'date',
            'death_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<FamilyTree, Member>
     */
    public function familyTree(): BelongsTo
    {
        return $this->belongsTo(FamilyTree::class);
    }

    /**
     * @return BelongsTo<Member, Member>
     */
    public function father(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'father_id');
    }

    /**
     * @return BelongsTo<Member, Member>
     */
    public function mother(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'mother_id');
    }

    /**
     * @return HasMany<Member>
     */
    public function childrenAsFather(): HasMany
    {
        return $this->hasMany(Member::class, 'father_id');
    }

    /**
     * @return HasMany<Member>
     */
    public function childrenAsMother(): HasMany
    {
        return $this->hasMany(Member::class, 'mother_id');
    }

    /**
     * Current Member as Husband
     *
     * @return HasMany<Marriage>
     */
    public function marriagesAsHusband(): HasMany
    {
        return $this->hasMany(Marriage::class, 'husband_id');
    }

    /**
     * Current Member as Wife
     *
     * @return HasMany<Marriage>
     */
    public function marriagesAsWife(): HasMany
    {
        return $this->hasMany(Marriage::class, 'wife_id');
    }
}
