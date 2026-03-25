<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\Groups\GroupsFilterRequest;
use App\Http\Requests\Api\User\Groups\StoreGroupRequest;
use App\Http\Requests\Api\User\Groups\UpdateGroupRequest;
use App\Http\Resources\User\GroupsResource;
use App\Models\Group;
use App\Traits\Api\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    /**
     * Display a listing of the user's groups.
     */
    public function index(GroupsFilterRequest $request)
    {
        $this->authorize('viewAny', Group::class);

        $groups = Group::query()
            ->forUser($request->user()->id)
            ->filter($request)
            ->withCount('tasks')
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        $groups->getCollection()->transform(function (Group $group) use ($request) {
            $group->setAttribute('current_user_role', $group->currentUserRole($request->user()->id));
            return $group;
        });

        return $this->successPaginated(
            $groups,
            GroupsResource::collection($groups),
            'groups',
            'Groups retrieved successfully',
        );
    }

    /**
     * Store a newly created group.
     */
    public function store(StoreGroupRequest $request)
    {
        $this->authorize('create', Group::class);

        $group = DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $group = Group::create([
                ...$validated,
                'slug' => Str::uuid()->toString(),
                'owner_id' => $request->user()->id,
            ]);

            // Owner is automatically a group admin member.
            $group->users()->syncWithoutDetaching([
                $request->user()->id => ['role' => 'admin'],
            ]);

            return $group;
        });

        return $this->success(new GroupsResource($group), 'Group created successfully', 201);
    }

    /**
     * Display the specified group.
     */
    public function show(Request $request, Group $group)
    {
        $this->authorize('view', $group);
        $group->loadCount('tasks');
        $group->setAttribute('current_user_role', $group->currentUserRole($request->user()->id));

        return $this->success(new GroupsResource($group), 'Group retrieved successfully');
    }

    /**
     * Update the specified group.
     */
    public function update(UpdateGroupRequest $request, Group $group)
    {
        $this->authorize('update', $group);

        $group->update($request->validated());
        $group->setAttribute('current_user_role', $group->currentUserRole($request->user()->id));

        return $this->success(new GroupsResource($group->loadCount('tasks')), 'Group updated successfully');
    }

    /**
     * Remove the specified group.
     */
    public function destroy(Group $group)
    {
        $this->authorize('delete', $group);

        $group->delete([]);

        return $this->success(null, 'Group deleted successfully');
    }
}
