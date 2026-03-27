<?php

namespace App\Policies\Api\User;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Determine whether the user can view any groups.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->status;
    }

    /**
     * Determine whether the user can view the group.
     */
    public function view(User $authUser, Group $group): bool
    {
        return $group->isMember($authUser->id);
    }

    /**
     * Determine whether the user can create groups.
     */
    public function create(User $authUser): bool
    {
        return $authUser->status;
    }

    /**
     * Determine whether the user can update the group.
     */
    public function update(User $authUser, Group $group): bool
    {
        return $group->isAdmin($authUser->id);
    }

    /**
     * Determine whether the user can delete the group.
     */
    public function delete(User $authUser, Group $group): bool
    {
        return $group->isOwner($authUser->id);
    }

    /**
     * Determine whether the user can manage group tasks.
     */
    public function manageTasks(User $authUser, Group $group): bool
    {
        return $group->isAdmin($authUser->id);
    }

    /**
     * Determine whether the user can manage invite links.
     */
    public function manageInvites(User $authUser, Group $group): bool
    {
        return $group->isAdmin($authUser->id);
    }

    /**
     * Determine whether the user can manage members.
     */
    public function manageMembers(User $authUser, Group $group): bool
    {
        return $group->isAdmin($authUser->id);
    }

    /**
     * Determine whether the user can update a specific member's role.
     */
    public function updateMemberRole(User $authUser, Group $group, User $targetUser): bool
    {
        // User must be admin of the group
        if (!$group->isAdmin($authUser->id)) {
            return false;
        }

        // Cannot change owner's role
        if ($group->owner_id === $targetUser->id) {
            return false;
        }

        // User must actually be a member of the group
        if (!$group->users()->where('users.id', $targetUser->id)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can remove a specific member from the group.
     */
    public function removeMember(User $authUser, Group $group, User $targetUser): bool
    {
        // User must be admin of the group
        if (!$group->isAdmin($authUser->id)) {
            return false;
        }

        // Cannot remove owner from the group
        if ($group->owner_id === $targetUser->id) {
            return false;
        }

        // User must actually be a member of the group
        $targetMember = $group->users()->where('users.id', $targetUser->id)->first();
        if (!$targetMember) {
            return false;
        }

        // Only group owner can remove admin members
        if ($targetMember->pivot->role === 'admin' && !$group->isOwner($authUser->id)) {
            return false;
        }

        return true;
    }
}
