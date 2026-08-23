<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreeInvitation extends Model
{
    protected $fillable = [
        'family_tree_id',
        'email',
        'role',
        'token',
        'status',
    ];

    /**
     * @return BelongsTo<FamilyTree, TreeInvitation>
     */
    public function familyTree(): BelongsTo
    {
        return $this->belongsTo(FamilyTree::class);
    }
}
