<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreeSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_tree_id',
        'target_member_id',
        'type',
        'data',
        'contributor_name',
        'contributor_contact',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FamilyTree, TreeSuggestion>
     */
    public function familyTree(): BelongsTo
    {
        return $this->belongsTo(FamilyTree::class);
    }

    /**
     * @return BelongsTo<Member, TreeSuggestion>
     */
    public function targetMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'target_member_id');
    }

    /**
     * @return BelongsTo<User, TreeSuggestion>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
