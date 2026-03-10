<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\Api\Admin\Users\ChangeRoleRequest;
use App\Http\Requests\Api\Admin\Users\GivePermissionsRequest;
use App\Http\Requests\Api\Admin\Users\RevokePermissionsRequest;
use App\Http\Requests\Api\Admin\Users\StoreUserRequest;
use App\Http\Requests\Api\Admin\Users\UsersFilterRequest;
use App\Http\Requests\Api\Admin\Users\UsersUpdateRequest;
use App\Http\Resources\Admin\UsersResource;
use App\Models\User;
use App\Traits\Api\ApiResponse;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    /**
     * List users with filters, sorting, and pagination.
     */
    public function index(UsersFilterRequest $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->filter($request)
            ->with(['roles:id,name'])
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return $this->successPaginated(
            $users,
            UsersResource::collection($users),
            'users',
            'Users retrieved successfully',
        );
    }

    /**
     * Show a single user with roles and permissions.
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);
        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'User retrieved successfully');
    }

    /**
     * Create a new user with role assignment.
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $roleName = $request->validated('role', 'user');

        if ($roleName === 'super_admin') {
            $this->authorize('assignSuperAdmin', new User());
        }

        $user = DB::transaction(function () use ($request, $roleName) {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            $user->syncRoles($roleName);
            return $user;
        });

        $user->sendEmailVerificationNotification();

        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'User created successfully', 201);
    }

    /**
     * Update user data. Resets email verification if email changes.
     */
    public function update(UsersUpdateRequest $request, User $user)
    {
        $this->authorize('notSuperAdmin', $user);
        $this->authorize('update', $user);
        $validated = $request->validated();

        $emailChanged = isset($validated['email']) && $validated['email'] !== $user->email;

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->update($validated);

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'User updated successfully');
    }

    /**
     * Replace the user's role (single role only).
     */
    public function changeRole(ChangeRoleRequest $request, User $user)
    {
        $this->authorize('notSelf', $user);
        $this->authorize('notSuperAdmin', $user);
        $this->authorize('changeRole', $user);
        if ($request->validated('role') === 'super_admin') {
            $this->authorize('assignSuperAdmin', $user);
        }

        $user->syncRoles($request->validated('role'));

        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'User role updated successfully');
    }

    /**
     * Give multiple direct permissions to a user.
     */
    public function givePermissions(GivePermissionsRequest $request, User $user)
    {
        $this->authorize('notSelf', $user);
        $this->authorize('notSuperAdmin', $user);
        $this->authorize('givePermission', $user);
        $user->givePermissionTo($request->validated('permissions'));

        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'Permissions granted successfully');
    }

    /**
     * Revoke multiple direct permissions from a user.
     */
    public function revokePermissions(RevokePermissionsRequest $request, User $user)
    {
        $this->authorize('notSelf', $user);
        $this->authorize('notSuperAdmin', $user);
        $this->authorize('revokePermission', $user);
        $user->revokePermissionTo($request->validated('permissions'));

        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'Permissions revoked successfully');
    }

    /**
     * Soft delete a user.
     */
    public function destroy(User $user)
    {
        $this->authorize('notSelf', $user);
        $this->authorize('notSuperAdmin', $user);
        $this->authorize('delete', $user);
        $user->delete();

        return $this->success(null, 'User deleted successfully');
    }

    /**
     * List only soft-deleted users with filters and pagination.
     */
    public function trashed(UsersFilterRequest $request)
    {
        $this->authorize('viewTrashed', User::class);
        $users = User::onlyTrashed()
            ->filter($request)
            ->with(['roles:id,name'])
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return $this->successPaginated(
            $users,
            UsersResource::collection($users),
            'users',
            'Trashed users retrieved successfully',
        );
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(User $user)
    {
        $this->authorize('notSelf', $user);
        $this->authorize('notSuperAdmin', $user);
        $this->authorize('restore', $user);
        if (!$user->trashed()) {
            return $this->error('User is not deleted', null, 400);
        }

        $user->restore();

        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'User restored successfully');
    }

    /**
     * Permanently delete a soft-deleted user.
     */
    public function forceDelete(User $user)
    {
        $this->authorize('notSelf', $user);
        $this->authorize('notSuperAdmin', $user);
        $this->authorize('forceDelete', $user);
        if (!$user->trashed()) {
            return $this->error('User is not deleted', null, 400);
        }

        $user->forceDelete();

        return $this->success(null, 'User permanently deleted successfully');
    }
}
