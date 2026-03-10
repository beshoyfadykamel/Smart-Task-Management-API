<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Roles_Permissions\StoreRoleRequest;
use App\Http\Requests\Api\Admin\Roles_Permissions\UpdateRoleRequest;
use App\Http\Resources\Admin\RoleResource;
use App\Traits\Api\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Role::class);
        $roles = Role::with('permissions')->get();
        return $this->success(['roles' => RoleResource::collection($roles)], 'Roles retrieved successfully', 200);
    }

    public function show(Role $role)
    {
        $this->authorize('view', $role);
        $role->load('permissions');
        return $this->success(['role' => new RoleResource($role)], 'Role retrieved successfully', 200);
    }

    public function store(StoreRoleRequest $request)
    {
        $this->authorize('create', Role::class);
        $validated = $request->validated();

        $role = DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name'       => $validated['name'],
                'guard_name' => 'sanctum',
            ]);
            $role->syncPermissions($validated['permissions']);
            return $role;
        });

        $role->load('permissions');
        return $this->success(['role' => new RoleResource($role)], 'Role created successfully', 201);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->denyIfSystemRole($role);
        $this->authorize('update', $role);
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $role) {
            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions']);
        });

        $role->load('permissions');
        return $this->success(['role' => new RoleResource($role)], 'Role updated successfully', 200);
    }

    public function destroy(Role $role)
    {
        $this->denyIfSystemRole($role);
        $this->authorize('delete', $role);
        $role->delete();
        return $this->success(null, 'Role deleted successfully', 200);
    }

    private const SYSTEM_ROLES = ['user', 'admin', 'super_admin'];

    private function denyIfSystemRole(Role $role): void
    {
        if (in_array($role->name, self::SYSTEM_ROLES)) {
            abort(403, 'System roles cannot be modified or deleted.');
        }
    }
}
