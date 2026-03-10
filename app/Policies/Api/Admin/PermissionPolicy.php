<?php

namespace App\Policies\Api\Admin;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    /**
     * Determine whether the user can view any permissions.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('view permissions');
    }
}
