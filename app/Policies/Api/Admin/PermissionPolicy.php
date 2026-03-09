<?php

namespace App\Policies\Api\Admin;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    /**
     * Determine whether the user can view any permissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view permissions');
    }
}
