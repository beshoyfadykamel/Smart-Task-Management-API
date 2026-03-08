<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Users\UsersFilterRequest;
use App\Http\Requests\Api\Admin\Users\UsersUpdateRequest;
use App\Http\Resources\Admin\UsersResource;
use App\Models\User;
use App\Traits\Api\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * List users with filters, sorting, and pagination.
     *
     * @param UsersFilterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(UsersFilterRequest $request)
    {
        $users = User::query()
            ->status($request->input('status'))
            ->createdFrom($request->input('created_from'))
            ->emailVerified($request->input('email_verified'))
            ->search($request->input('search'))
            ->sortByCreated($request->input('sort'))
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return $this->successPaginated(
            $users,
            UsersResource::collection($users),
            'users',
            'Users retrieved successfully',
            200
        );
    }

    /**
     * Show a single user details.
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        return $this->success(new UsersResource($user), 'User retrieved successfully', 200);
    }

    /**
     * Update a user and reset verification when email changes.
     *
     * @param UsersUpdateRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UsersUpdateRequest $request, User $user)
    {
        $validatedData = $request->validated();

        // If email changed, reset verification
        if (!empty($validatedData['email']) && $validatedData['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        $user->update($validatedData);

        return $this->success(new UsersResource($user), 'User updated successfully', 200);
    }

    /**
     * Soft delete a user.
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        $user->delete();
        return $this->success(null, 'User deleted successfully', 200);
    }

    /**
     * List only soft-deleted users with filters and pagination.
     *
     * @param UsersFilterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trashed(UsersFilterRequest $request)
    {
        $users = User::onlyTrashed()
            ->status($request->input('status'))
            ->createdFrom($request->input('created_from'))
            ->emailVerified($request->input('email_verified'))
            ->search($request->input('search'))
            ->sortByCreated($request->input('sort'))
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return $this->successPaginated(
            $users,
            UsersResource::collection($users),
            'users',
            'Trashed users retrieved successfully',
            200
        );
    }

    /**
     * Restore a soft-deleted user.
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(User $user)
    {
        if (!$user->trashed()) {
            return $this->error('User is not deleted', 400, 400);
        }
        $user->restore();
        return $this->success(new UsersResource($user), 'User restored successfully', 200);
    }

    /**
     * Permanently delete a soft-deleted user.
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete(User $user)
    {
        if (!$user->trashed()) {
            return $this->error('User is not deleted', 400, 400);
        }
        $user->forceDelete();
        return $this->success(null, 'User permanently deleted successfully', 200);
    }
}
