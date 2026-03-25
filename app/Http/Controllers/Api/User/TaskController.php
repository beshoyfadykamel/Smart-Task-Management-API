<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\Tasks\TasksFilterRequest;
use App\Http\Requests\Api\User\Tasks\StoreTaskRequest;
use App\Http\Requests\Api\User\Tasks\UpdateTaskRequest;
use App\Http\Resources\User\TasksResource;
use App\Models\Group;
use App\Models\Task;
use App\Traits\Api\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
            ->filter($request)
            ->with('group')
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
            new TasksResource($task->load('group')),
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
        $task->load('group');

        return $this->success(new TasksResource($task), 'Task retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validated();

        if (array_key_exists('group_id', $validated) && !empty($validated['group_id'])) {
            $group = Group::findOrFail($validated['group_id']);
            $this->authorize('manageTasks', $group);
        }

        $task->update($validated);

        return $this->success(new TasksResource($task->load('group')), 'Task updated successfully');
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
}
