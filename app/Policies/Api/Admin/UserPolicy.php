<?php

namespace App\Policies\Api\Admin;

use App\Models\User;

class UserPolicy
{
    // NOTE: super_admin bypasses all methods via Gate::before in AppServiceProvider.
    // NOTE: Self-protection (blocking actions on own account) is handled in UserController::denyIfSelf().

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view users');
    }

    public function viewTrashed(User $user): bool
    {
        return $user->hasPermissionTo('view trashed users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('view users');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('update users');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('delete users');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasPermissionTo('restore users');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('force delete users');
    }

    public function changeRole(User $user, User $model): bool
    {
        return $user->hasPermissionTo('change roles');
    }

    public function assignSuperAdmin(User $user, User $model): bool
    {
        // Only super_admin can assign super_admin role.
        // Gate::before already returns true for super_admin,
        // so this method is only reached by non-super_admin users → always false.
        return false;
    }

    public function givePermission(User $user, User $model): bool
    {
        return $user->hasPermissionTo('give permissions');
    }

    public function revokePermission(User $user, User $model): bool
    {
        return $user->hasPermissionTo('revoke permissions');
    }
}
