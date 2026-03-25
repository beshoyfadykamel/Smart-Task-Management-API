<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'current_user_role' => $this->when(isset($this->current_user_role), $this->current_user_role),
            'active' => $this->active,
            'max_members' => $this->max_members,
            'tasks_count' => $this->whenCounted('tasks'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
