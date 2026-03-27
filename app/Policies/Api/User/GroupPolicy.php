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
     * Determine whether the user can alter a member's status (update role or remove).
     */
    public function alterMember(User $authUser, Group $group, User $targetUser): bool
    {
        // User must be admin of the group
        if (!$group->isAdmin($authUser->id)) {
            return false;
        }

        // Cannot alter owner
        if ($group->owner_id === $targetUser->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can leave the group.
     */
    public function leave(User $authUser, Group $group): bool
    {
        // Owner cannot leave the group
        if ($group->owner_id === $authUser->id) {
            return false;
        }

        return true;
    }
}
