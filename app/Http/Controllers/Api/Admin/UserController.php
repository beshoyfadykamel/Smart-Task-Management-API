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
use Illuminate\Http\JsonResponse;

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
            ->status($request->input('status'))
            ->createdFrom($request->input('created_from'))
            ->emailVerified($request->input('email_verified'))
            ->search($request->input('search'))
            ->sortByCreated($request->input('sort'))
            ->with('roles:id,name,guard_name')
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
        $user = User::create($request->safe()->only(['name', 'email', 'password']));

        $user->syncRoles($request->validated('role'));

        $user->sendEmailVerificationNotification();

        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'User created successfully', 201);
    }

    /**
     * Update user data. Resets email verification if email changes.
     */
    public function update(UsersUpdateRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $validated = $request->validated();

        if (!empty($validated['email']) && $validated['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        $user->update($validated);

        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'User updated successfully');
    }

    /**
     * Replace the user's role (single role only).
     */
    public function changeRole(ChangeRoleRequest $request, User $user)
    {
        if ($response = $this->denyIfSelf($user, 'You cannot change your own role.')) {
            return $response;
        }

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
        if ($response = $this->denyIfSelf($user, 'You cannot give permissions to your own account.')) {
            return $response;
        }

        $this->authorize('givePermission', $user);
        $user->givePermissionTo($request->validated('permissions'));

        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'Permissions granted successfully');
    }

    /**
     * Revoke multiple direct permissions from a user.
     */
    public function revokePermissions(RevokePermissionsRequest $request, User $user)
    {
        if ($response = $this->denyIfSelf($user, 'You cannot revoke permissions from your own account.')) {
            return $response;
        }

        $this->authorize('revokePermission', $user);
        foreach ($request->validated('permissions') as $permission) {
            $user->revokePermissionTo($permission);
        }

        return $this->success(new UsersResource($user->loadRolesAndPermissions()), 'Permissions revoked successfully');
    }

    /**
     * Soft delete a user.
     */
    public function destroy(User $user)
    {
        if ($response = $this->denyIfSelf($user, 'You cannot delete your own account.')) {
            return $response;
        }

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
            ->status($request->input('status'))
            ->createdFrom($request->input('created_from'))
            ->emailVerified($request->input('email_verified'))
            ->search($request->input('search'))
            ->sortByCreated($request->input('sort'))
            ->with('roles:id,name,guard_name')
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
        if ($response = $this->denyIfSelf($user, 'You cannot restore your own account.')) {
            return $response;
        }

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
        if ($response = $this->denyIfSelf($user, 'You cannot permanently delete your own account.')) {
            return $response;
        }

        $this->authorize('forceDelete', $user);
        if (!$user->trashed()) {
            return $this->error('User is not deleted', null, 400);
        }

        $user->forceDelete();

        return $this->success(null, 'User permanently deleted successfully');
    }




    // Private Methods
    // ───────────────────────────────────────────────────────────────────────────────


    /**
     * Deny action if the target user is the authenticated user (self-protection).
     */
    private function denyIfSelf(User $user, string $message): ?JsonResponse
    {
        if ($user->id === request()->user()->id) {
            return $this->error($message, null, 403);
        }
        return null;
    }
}
