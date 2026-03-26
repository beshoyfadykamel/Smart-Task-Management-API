<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\Groups\AcceptGroupInviteLinkRequest;
use App\Http\Requests\Api\User\Groups\StoreGroupInviteLinkRequest;
use App\Http\Resources\User\GroupInviteLinkResource;
use App\Http\Resources\User\GroupsResource;
use App\Models\Group;
use App\Models\GroupInviteLink;
use App\Traits\Api\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupInviteLinkController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function index(Request $request, Group $group)
    {
        $this->authorize('manageInvites', $group);

        $links = $group->inviteLinks()
            ->latest()
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return $this->successPaginated(
            $links,
            GroupInviteLinkResource::collection($links),
            'invite_links',
            'Invite links retrieved successfully',
        );
    }

    public function store(StoreGroupInviteLinkRequest $request, Group $group)
    {
        $this->authorize('manageInvites', $group);

        $validated = $request->validated();
        $role = $validated['role'] ?? 'member';

        if ($role === 'admin' && !$group->isOwner($request->user()->id)) {
            return $this->error('Only the group owner can create admin invite links.', null, 403);
        }

        $inviteLink = $group->inviteLinks()->create([
            'created_by' => $request->user()->id,
            'token' => Str::random(48),
            'role' => $role,
            'max_uses' => $validated['max_uses'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'current_uses' => 0,
            'active' => true,
        ]);

        return $this->success(new GroupInviteLinkResource($inviteLink), 'Invite link created successfully', 201);
    }

    public function destroy(Group $group, GroupInviteLink $inviteLink)
    {
        $this->authorize('manageInvites', $group);

        if ($inviteLink->group_id !== $group->id) {
            return $this->error('Invite link does not belong to this group.', null, 404);
        }

        $inviteLink->update(['active' => false]);

        return $this->success(null, 'Invite link revoked successfully');
    }

    public function accept(AcceptGroupInviteLinkRequest $request, GroupInviteLink $inviteLink)
    {
        $user = $request->user();

        $result = DB::transaction(function () use ($inviteLink, $user) {
            /** @var GroupInviteLink $lockedInvite */
            $lockedInvite = GroupInviteLink::query()
                ->with('group')
                ->lockForUpdate()
                ->findOrFail($inviteLink->id);

            $group = $lockedInvite->group;

            if (!$lockedInvite->isUsable()) {
                return ['error' => 'Invite link is expired, inactive, or out of uses.', 'code' => 422];
            }

            if ($group->isMember($user->id)) {
                $group->setAttribute('current_user_role', $group->currentUserRole($user->id));
                return ['group' => $group, 'message' => 'You are already a member of this group.'];
            }

            if (!$group->hasCapacity()) {
                return ['error' => 'Group has reached maximum members limit.', 'code' => 422];
            }

            $group->users()->syncWithoutDetaching([
                $user->id => ['role' => $lockedInvite->role],
            ]);
            $lockedInvite->increment('current_uses', 1, []);

            $group->setAttribute('current_user_role', $lockedInvite->role);

            return [
                'group' => $group->fresh()->load('owner:id,name')->loadCount(['tasks', 'users']),
                'message' => 'Joined group successfully using invite link.',
            ];
        });

        if (isset($result['error'])) {
            return $this->error($result['error'], null, $result['code']);
        }

        $group = $result['group'];
        $group->loadMissing('owner:id,name')->loadCount(['tasks', 'users']);
        $group->setAttribute('current_user_role', $group->currentUserRole($user->id));

        return $this->success(new GroupsResource($group), $result['message']);
    }
}
