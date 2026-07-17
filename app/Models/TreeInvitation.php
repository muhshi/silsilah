<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\FamilyTree, \App\Models\TreeInvitation>
     */
    public function familyTree(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FamilyTree::class);
    }
}
