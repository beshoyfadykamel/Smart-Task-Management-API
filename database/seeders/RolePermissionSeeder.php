<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Permission::firstOrCreate(['name' => 'create users', 'guard_name' => 'sanctum']);
        // Permission::firstOrCreate(['name' => 'edit users', 'guard_name' => 'sanctum']);
        // Permission::firstOrCreate(['name' => 'delete users', 'guard_name' => 'sanctum']);
        // Permission::firstOrCreate(['name' => 'view users', 'guard_name' => 'sanctum']);

        // $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        // $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);


        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);


        // $superAdmin->givePermissionTo([
        //     'create users',
        //     'edit users',
        //     'delete users',
        //     'view users'
        // ]);

        // $admin->givePermissionTo('view users');
    }
}
