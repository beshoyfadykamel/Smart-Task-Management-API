<?php

namespace App\Policies\Api\User;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->status;
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $authUser, Task $task): bool
    {
        if (!$task->group) {
            return $task->created_by === $authUser->id;
        }

        return $task->group->isMember($authUser->id) || $task->created_by === $authUser->id;
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function create(User $authUser): bool
    {
        return $authUser->status;
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $authUser, Task $task): bool
    {
        if (!$task->group) {
            return $task->created_by === $authUser->id;
        }

        if ($task->group->isAdmin($authUser->id)) {
            return true;
        }

        return $task->created_by === $authUser->id && $task->group->isMember($authUser->id);
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $authUser, Task $task): bool
    {
        if (!$task->group) {
            return $task->created_by === $authUser->id;
        }

        if ($task->group->isAdmin($authUser->id)) {
            return true;
        }

        return $task->created_by === $authUser->id && $task->group->isMember($authUser->id);
    }
}
