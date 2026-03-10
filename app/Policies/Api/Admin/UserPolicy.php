<?php

namespace App\Policies\Api\Admin;

use App\Models\User;

class UserPolicy
{
    // NOTE: super_admin bypasses all methods via Gate::before in AppServiceProvider.
    // NOTE: Self-protection (blocking actions on own account) is enforced via the notSelf policy method.

    public function notSelf(User $authUser, User $targetUser): bool
    {
        return $authUser->id !== $targetUser->id;
    }

    public function notSuperAdmin(User $authUser, User $targetUser): bool
    {
        // Users may always act on their own account (e.g. super_admin updating themselves).
        // For any other target, deny if the target holds the super_admin role.
        if ($authUser->id === $targetUser->id) {
            return true;
        }

        return !$targetUser->hasRole('super_admin');
    }

    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('view users');
    }

    public function viewTrashed(User $authUser): bool
    {
        return $authUser->hasPermissionTo('view trashed users');
    }

    public function view(User $authUser, User $targetUser): bool
    {
        return $authUser->hasPermissionTo('view users');
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('create users');
    }

    public function update(User $authUser, User $targetUser): bool
    {
        return $authUser->hasPermissionTo('update users');
    }

    public function delete(User $authUser, User $targetUser): bool
    {
        return $authUser->hasPermissionTo('delete users');
    }

    public function restore(User $authUser, User $targetUser): bool
    {
        return $authUser->hasPermissionTo('restore users');
    }

    public function forceDelete(User $authUser, User $targetUser): bool
    {
        return $authUser->hasPermissionTo('force delete users');
    }

    public function changeRole(User $authUser, User $targetUser): bool
    {
        return $authUser->hasPermissionTo('change roles');
    }

    public function assignSuperAdmin(User $authUser, User $targetUser): bool
    {
        return $authUser->hasRole('super_admin');
    }

    public function givePermission(User $authUser, User $targetUser): bool
    {
        return $authUser->hasPermissionTo('give permissions');
    }

    public function revokePermission(User $authUser, User $targetUser): bool
    {
        return $authUser->hasPermissionTo('revoke permissions');
    }
}
