<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\FamilyTree, \App\Models\Member>
     */
    public function familyTree(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FamilyTree::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Member, \App\Models\Member>
     */
    public function father(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class, 'father_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Member, \App\Models\Member>
     */
    public function mother(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class, 'mother_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Member>
     */
    public function childrenAsFather(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Member::class, 'father_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Member>
     */
    public function childrenAsMother(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Member::class, 'mother_id');
    }

    /**
     * Current Member as Husband
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Marriage>
     */
    public function marriagesAsHusband(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Marriage::class, 'husband_id');
    }

    /**
     * Current Member as Wife
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Marriage>
     */
    public function marriagesAsWife(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Marriage::class, 'wife_id');
    }
}
