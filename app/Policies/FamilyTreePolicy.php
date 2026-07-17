<?php

namespace App\Policies;

use App\Models\FamilyTree;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FamilyTreePolicy
{
    /**
     * Helper to get user role in the tree
     */
    private function getRole(User $user, FamilyTree $familyTree): ?string
    {
        $pivot = $familyTree->users()->where('user_id', $user->id)->first();
        return $pivot ? $pivot->pivot->role : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FamilyTree $familyTree): bool
    {
        return $this->getRole($user, $familyTree) !== null || $familyTree->is_public;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model (Name, settings, etc).
     */
    public function update(User $user, FamilyTree $familyTree): bool
    {
        return $this->getRole($user, $familyTree) === 'owner';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FamilyTree $familyTree): bool
    {
        return $this->getRole($user, $familyTree) === 'owner';
    }

    /**
     * Determine whether the user can edit family members in this tree.
     */
    public function editMembers(User $user, FamilyTree $familyTree): bool
    {
        return in_array($this->getRole($user, $familyTree), ['owner', 'editor']);
    }

    /**
     * Determine whether the user can manage collaborators.
     */
    public function manageCollaborators(User $user, FamilyTree $familyTree): bool
    {
        return $this->getRole($user, $familyTree) === 'owner';
    }
}
