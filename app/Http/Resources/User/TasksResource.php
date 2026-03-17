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
            'id' => $this->id,
            'title' => $this->title,
            'active' => $this->active,
            'group_id' => $this->whenNotNull($this->group_id),
            'group' => $this->whenLoaded('group', function () {
                return [
                    'id' => $this->group->id,
                    'name' => $this->group->name,
                    'slug' => $this->group->slug,
                ];
            }),
            'image_path' => $this->image_path,
            'created_at' => $this->created_at,
        ];
    }
}
