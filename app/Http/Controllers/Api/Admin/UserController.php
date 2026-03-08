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


    public function show(User $user)
    {
        return $this->success(new UsersResource($user), 'User retrieved successfully', 200);
    }


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


    public function destroy(User $user)
    {
        $user->delete();
        return $this->success(null, 'User deleted successfully', 200);
    }



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

    public function restore(User $user)
    {
        if (!$user->trashed()) {
            return $this->error('User is not deleted', 400, 400);
        }
        $user->restore();
        return $this->success(new UsersResource($user), 'User restored successfully', 200);
    }

    public function forceDelete(User $user)
    {
        if (!$user->trashed()) {
            return $this->error('User is not deleted', 400, 400);
        }
        $user->forceDelete();
        return $this->success(null, 'User permanently deleted successfully', 200);
    }
}
