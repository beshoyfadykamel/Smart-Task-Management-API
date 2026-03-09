<?php

namespace App\Policies\Api\Admin;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view roles');
    }

    /**
     * Determine whether the user can view a specific role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('view roles');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create roles');
    }

    /**
     * Determine whether the user can update a role.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('update roles');
    }

    /**
     * Determine whether the user can delete a role.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('delete roles');
    }
}
