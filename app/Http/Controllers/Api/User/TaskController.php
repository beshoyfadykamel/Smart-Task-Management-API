<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\Tasks\StoreTaskAssigneesRequest;
use App\Http\Requests\Api\User\Tasks\TasksFilterRequest;
use App\Http\Requests\Api\User\Tasks\UpdateTaskAssigneeStatusRequest;
use App\Http\Requests\Api\User\Tasks\StoreTaskRequest;
use App\Http\Requests\Api\User\Tasks\UpdateTaskRequest;
use App\Http\Resources\User\TaskAssigneeResource;
use App\Http\Resources\User\TasksResource;
use App\Models\Group;
use App\Models\Task;
use App\Models\User;
use App\Traits\Api\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    use ApiResponse, AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(TasksFilterRequest $request)
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->visibleTo($request->user()->id)
            ->filter($request, $request->user()->id)
            ->with(['group:id,slug,name', 'creator:id,name'])
            ->withCount('users')
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return $this->successPaginated(
            $tasks,
            TasksResource::collection($tasks),
            'tasks',
            'Tasks retrieved successfully',
            200,
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $this->authorize('create', Task::class);

        $validated = $request->validated();

        if (!empty($validated['group_id'])) {
            $group = Group::findOrFail($validated['group_id']);
            $this->authorize('manageTasks', $group);
        }

        $task = DB::transaction(function () use ($request, $validated) {
            return Task::create([
                ...$validated,
                'slug' => Str::uuid()->toString(),
                'created_by' => $request->user()->id,
            ]);
        });

        return $this->success(
            new TasksResource($task->load(['group:id,slug,name', 'creator:id,name'])->loadCount('users')),
            'Task created successfully',
            201,
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $this->authorize('view', $task);
        $task->load(['group:id,slug,name', 'creator:id,name'])->loadCount('users');

        return $this->success(new TasksResource($task), 'Task retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validated();

        if (array_key_exists('group_id', $validated)) {
            $currentGroup = $task->group;
            $newGroupId = $validated['group_id'];

            if (is_null($newGroupId)) {
                if ($currentGroup) {
                    $this->authorize('manageTasks', $currentGroup);
                }
            } else {
                $newGroup = Group::findOrFail($newGroupId);

                if ($currentGroup && $currentGroup->id !== $newGroup->id) {
                    $this->authorize('manageTasks', $currentGroup);
                }

                $this->authorize('manageTasks', $newGroup);
            }
        }

        $task->update($validated);

        return $this->success(
            new TasksResource($task->load(['group:id,slug,name', 'creator:id,name'])->loadCount('users')),
            'Task updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete([]);

        return $this->success(null, 'Task deleted successfully');
    }

    /**
     * Display task assignees.
     */
    public function assignees(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        $assignees = $task->users()
            ->select('users.id', 'users.name', 'users.email')
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return $this->successPaginated(
            $assignees,
            TaskAssigneeResource::collection($assignees),
            'assignees',
            'Task assignees retrieved successfully',
        );
    }

    /**
     * Assign users to a task.
     */
    public function assignAssignees(StoreTaskAssigneesRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        if ($task->group) {
            $this->authorize('manageTasks', $task->group);
        } elseif ($task->created_by !== $request->user()->id) {
            return $this->error('Only task creator can assign users to personal tasks.', null, 403);
        }

        $validated = $request->validated();
        $userIds = $validated['user_ids'];

        $users = User::query()->whereIn('id', $userIds)->get(['id', 'status']);

        $inactiveUserExists = $users->contains(fn(User $user) => !$user->status);
        if ($inactiveUserExists) {
            return $this->error('Cannot assign inactive users to task.', null, 422);
        }

        if ($task->group) {
            $memberIds = $task->group->users()
                ->whereIn('users.id', $userIds)
                ->pluck('users.id')
                ->map(fn($id) => (int) $id)
                ->all();

            $nonMembers = array_values(array_diff($userIds, $memberIds));

            if (!empty($nonMembers)) {
                return $this->error('All assignees must be members of the task group.', [
                    'user_ids' => $nonMembers,
                ], 422);
            }
        } else {
            if (count($userIds) !== 1 || (int) $userIds[0] !== (int) $request->user()->id) {
                return $this->error('Personal tasks can only be assigned to the task creator.', null, 422);
            }
        }

        $syncPayload = [];
        foreach ($userIds as $userId) {
            $syncPayload[$userId] = [
                'status' => $validated['status'] ?? 'pending',
            ];
        }

        $task->users()->syncWithoutDetaching($syncPayload);

        $task->load(['group:id,slug,name', 'creator:id,name'])->loadCount('users');

        return $this->success(new TasksResource($task), 'Task assignees added successfully');
    }

    /**
     * Update task assignee status.
     */
    public function updateAssigneeStatus(UpdateTaskAssigneeStatusRequest $request, Task $task, User $user)
    {
        $this->authorize('view', $task);

        $isSelfUpdate = $request->user()->id === $user->id;
        if (!$isSelfUpdate) {
            $this->authorize('update', $task);

            if ($task->group) {
                $this->authorize('manageTasks', $task->group);
            } elseif ($task->created_by !== $request->user()->id) {
                return $this->error('Only task creator can update assignee status on personal tasks.', null, 403);
            }
        }

        $isAssigned = $task->users()->where('users.id', $user->id)->exists();
        if (!$isAssigned) {
            return $this->error('User is not assigned to this task.', null, 404);
        }

        $task->users()->updateExistingPivot($user->id, [
            'status' => $request->validated('status'),
        ]);

        $task->load(['group:id,slug,name', 'creator:id,name'])->loadCount('users');

        return $this->success(new TasksResource($task), 'Assignee status updated successfully');
    }

    /**
     * Remove user assignment from a task.
     */
    public function unassignAssignee(Request $request, Task $task, User $user)
    {
        $this->authorize('update', $task);

        if ($task->group) {
            $this->authorize('manageTasks', $task->group);
        } elseif ($task->created_by !== $request->user()->id) {
            return $this->error('Only task creator can unassign users from personal tasks.', null, 403);
        }

        $isAssigned = $task->users()->where('users.id', $user->id)->exists();
        if (!$isAssigned) {
            return $this->error('User is not assigned to this task.', null, 404);
        }

        $task->users()->detach($user->id);

        $task->load(['group:id,slug,name', 'creator:id,name'])->loadCount('users');

        return $this->success(new TasksResource($task), 'Assignee removed successfully');
    }
}
