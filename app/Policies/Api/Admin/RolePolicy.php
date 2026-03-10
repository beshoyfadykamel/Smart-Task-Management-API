<?php

namespace App\Policies\Api\Admin;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('view roles');
    }

    /**
     * Determine whether the user can view a specific role.
     */
    public function view(User $authUser, Role $role): bool
    {
        return $authUser->hasPermissionTo('view roles');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('create roles');
    }

    /**
     * Determine whether the user can update a role.
     * System roles (user, admin, super_admin) are immutable.
     */
    public function update(User $authUser, Role $role): bool
    {
        return $authUser->hasPermissionTo('update roles');
    }

    public function delete(User $authUser, Role $role): bool
    {
        return $authUser->hasPermissionTo('delete roles');
    }
}
