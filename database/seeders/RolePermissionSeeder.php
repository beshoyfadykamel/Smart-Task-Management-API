<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ================================================================
        // PERMISSIONS — Admin: User Management (UserPolicy)
        // ================================================================
        Permission::firstOrCreate(['name' => 'view users',         'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'view trashed users', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'create users',       'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'update users',       'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'delete users',       'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'restore users',      'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'force delete users', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'change roles',       'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'give permissions',   'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'revoke permissions', 'guard_name' => 'sanctum']);

        // ================================================================
        // PERMISSIONS — Admin: Role Management (RolePolicy)
        // ================================================================
        Permission::firstOrCreate(['name' => 'view roles',   'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'create roles', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'update roles', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'delete roles', 'guard_name' => 'sanctum']);

        // ================================================================
        // PERMISSIONS — Admin: Permission Management (PermissionPolicy)
        // ================================================================
        Permission::firstOrCreate(['name' => 'view permissions', 'guard_name' => 'sanctum']);

        // ================================================================
        // ROLES
        // ================================================================
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $admin      = Role::firstOrCreate(['name' => 'admin',       'guard_name' => 'sanctum']);
        $user       = Role::firstOrCreate(['name' => 'user',        'guard_name' => 'sanctum']);

        // ================================================================
        // ROLE → PERMISSION ASSIGNMENTS
        // ================================================================

        // super_admin: all permissions
        $superAdmin->syncPermissions([
            'view users',
            'view trashed users',
            'create users',
            'update users',
            'delete users',
            'restore users',
            'force delete users',
            'change roles',
            'give permissions',
            'revoke permissions',
            'view roles',
            'create roles',
            'update roles',
            'delete roles',
            'view permissions',
        ]);

        // admin: read-only on users, roles, and permissions
        $admin->syncPermissions([
            'view users',
            'view trashed users',
            'view roles',
            'view permissions',
        ]);

        // user: no admin permissions (accesses own profile only)
        $user->syncPermissions([]);
    }
}
