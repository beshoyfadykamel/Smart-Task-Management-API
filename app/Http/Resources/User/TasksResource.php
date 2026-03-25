<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TasksResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'active' => $this->active,
            'group_id' => $this->whenNotNull($this->group_id),
            'group' => $this->whenLoaded('group', function () {
                return [
                    'slug' => $this->group->slug,
                    'name' => $this->group->name,
                ];
            }),
            'image_path' => $this->image_path,
            'due_date' => $this->due_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
