<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Roles_Permissions\StoreRoleRequest;
use App\Http\Requests\Api\Admin\Roles_Permissions\UpdateRoleRequest;
use App\Traits\Api\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ApiResponse;

    /**
     * List all roles.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function index()
    {
        $roles = Role::with(['permissions:id,name,guard_name'])->get(['id', 'name', 'guard_name']);
        return $this->success(['roles' => $roles], 'Roles retrieved successfully', 200);
    }

    /**
     * Show a single role details.
     *
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Role $role)
    {
        $role->load('permissions');
        return $this->success(['role' => $role], 'Role retrieved successfully', 200);
    }

    /**
     * Create a new role.
     *
     * @param StoreRoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'sanctum',
        ]);
        $role->syncPermissions($validated['permissions']);
        $role->load('permissions');
        return $this->success(['role' => $role], 'Role created successfully', 201);
    }

    /**
     * Update a role.
     *
     * @param UpdateRoleRequest $request
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validated = $request->validated();

        $role->update([
            'name' => $validated['name'],
        ]);
        $role->syncPermissions($validated['permissions']);
        $role->load('permissions');
        return $this->success(['role' => $role], 'Role updated successfully', 200);
    }

    /**
     * Delete a role.
     *
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return $this->success(null, 'Role deleted successfully', 200);
    }
}
