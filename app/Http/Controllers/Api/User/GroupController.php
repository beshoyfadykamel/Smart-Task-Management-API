<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\Groups\GroupsFilterRequest;
use App\Http\Requests\Api\User\Groups\GroupMembersFilterRequest;
use App\Http\Requests\Api\User\Groups\StoreGroupMemberRequest;
use App\Http\Requests\Api\User\Groups\StoreGroupRequest;
use App\Http\Requests\Api\User\Groups\UpdateGroupMemberRoleRequest;
use App\Http\Requests\Api\User\Groups\UpdateGroupRequest;
use App\Http\Resources\User\GroupMemberResource;
use App\Http\Resources\User\GroupsResource;
use App\Models\Group;
use App\Models\User;
use App\Traits\Api\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    /**
     * Display a listing of the user's groups.
     */
    public function index(GroupsFilterRequest $request)
    {
        $this->authorize('viewAny', Group::class);

        $userId = $request->user()->id;

        $groups = Group::query()
            ->forUser($userId)
            ->filter($request, $userId)
            ->leftJoin('group_user', 'group_user.group_id', '=', 'groups.id')
            ->where('group_user.user_id', '=', $userId)
            ->select('groups.*')
            ->selectRaw("
                CASE
                    WHEN groups.owner_id = ? THEN 'owner'
                    ELSE group_user.role
                END as current_user_role
                    ", [$userId])
            ->with('owner:id,name')
            ->withCount(['tasks', 'users'])
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

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
                'owner_id' => $request->user()->id,
            ]);

            // Owner is automatically a group admin member.
            $group->users()->syncWithoutDetaching([
                $request->user()->id => ['role' => 'admin'],
            ]);

            return $group;
        });

        return $this->success(
            new GroupsResource($group->load('owner:id,name')->loadCount(['tasks', 'users'])),
            'Group created successfully',
            201
        );
    }

    /**
     * Display the specified group.
     */
    public function show(Request $request, Group $group)
    {
        $this->authorize('view', $group);
        $group->load('owner:id,name')->loadCount(['tasks', 'users']);
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

        return $this->success(
            new GroupsResource($group->load('owner:id,name')->loadCount(['tasks', 'users'])),
            'Group updated successfully'
        );
    }

    /**
     * Remove the specified group.
     */
    public function destroy(Group $group)
    {
        $this->authorize('delete', $group);

        DB::transaction(function () use ($group) {
            $group->delete();
        });
        return $this->success(null, 'Group deleted successfully');
    }

    /**
     * Display group members.
     */
    public function members(GroupMembersFilterRequest $request, Group $group)
    {
        $this->authorize('view', $group);

        $members = $group->users()
            ->select('users.id', 'users.name', 'users.email')
            ->withGroupMemberMeta($group->owner_id)
            ->filterGroupMembers($request, $group->owner_id)
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return $this->successPaginated(
            $members,
            GroupMemberResource::collection($members),
            'members',
            'Group members retrieved successfully',
        );
    }

    /**
     * Add a member to group.
     */
    public function addMember(StoreGroupMemberRequest $request, Group $group)
    {
        $this->authorize('manageMembers', $group);

        $validated = $request->validated();
        $user = User::query()->findOrFail($validated['user_id']);
        $role = $validated['role'] ?? 'member';

        if ($group->owner_id === $user->id) {
            return $this->error('Group owner is already a member.', null, 422);
        }

        if (!$user->status) {
            return $this->error('Cannot add inactive users to group.', null, 422);
        }

        if ($group->isMember($user->id)) {
            return $this->error('User is already a member of this group.', null, 422);
        }

        if (!$group->hasCapacity()) {
            return $this->error('Group has reached maximum members limit.', null, 422);
        }

        if ($role === 'admin' && !$group->isOwner($request->user()->id)) {
            return $this->error('Only group owner can add members as admin.', null, 403);
        }

        DB::transaction(function () use ($group, $user, $role) {
            $group->users()->syncWithoutDetaching([
                $user->id => ['role' => $role],
            ]);
        });

        $member = $group->users()
            ->where('users.id', $user->id)
            ->select('users.id', 'users.name', 'users.email')
            ->first();

        return $this->success(
            new GroupMemberResource($member),
            'Member added successfully',
            201
        );
    }

    /**
     * Update member role in group.
     */
    public function updateMemberRole(UpdateGroupMemberRoleRequest $request, Group $group, User $user)
    {
        if ($group->isOwner($user->id)) {
            return $this->error('Group owner role cannot be changed.', null, 422);
        }

        $this->authorize('alterMember', [$group, $user]);

        $member = $group->users()
            ->where('users.id', $user->id)
            ->select('users.id', 'users.name', 'users.email')
            ->first();

        if (!$member) {
            return $this->error('User is not a member of this group.', null, 404);
        }

        $currentRole = $member->pivot->role;
        $newRole = $request->validated('role');

        if (($currentRole === 'admin' || $newRole === 'admin') && !$group->isOwner($request->user()->id)) {
            return $this->error('Only group owner can manage admin roles.', null, 403);
        }

        DB::transaction(function () use ($group, $user, $newRole, $member) {
            $group->users()->updateExistingPivot($user->id, ['role' => $newRole]);
            $member->pivot->role = $newRole;
        });

        return $this->success(new GroupMemberResource($member), 'Member role updated successfully');
    }

    /**
     * Remove member from group.
     */
    public function removeMember(Request $request, Group $group, User $user)
    {
        if ($group->isOwner($request->user()->id) && $group->isOwner($user->id)) {
            return $this->error('Group owner cannot remove themselves from the group.', null, 422);
        }

        $this->authorize('alterMember', [$group, $user]);

        $member = $group->users()->where('users.id', $user->id)->first();

        if (!$member) {
            return $this->error('User is not a member of this group.', null, 404);
        }

        if ($member->pivot->role === 'admin' && !$group->isOwner($request->user()->id)) {
            return $this->error('Only group owner can remove admin members.', null, 403);
        }

        DB::transaction(function () use ($group, $user) {
            $group->users()->detach($user->id);
        });

        return $this->success(null, 'Member removed successfully');
    }

    /**
     * Current user leaves group.
     */
    public function leave(Request $request, Group $group)
    {
        $userId = $request->user()->id;

        if ($group->isOwner($userId)) {
            return $this->error('Group owner cannot leave the group. Transfer ownership first.', null, 422);
        }

        $this->authorize('leave', $group);

        if (!$group->users()->where('users.id', $userId)->exists()) {
            return $this->error('You are not a member of this group.', null, 404);
        }

        DB::transaction(function () use ($group, $userId) {
            $group->users()->detach($userId);
        });

        return $this->success(null, 'You left the group successfully');
    }
}
