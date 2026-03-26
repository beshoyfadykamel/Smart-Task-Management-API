<?php

namespace App\Http\Requests\Api\User\Groups;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Group|null $group */
        $group = $this->route('group');

        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'active' => 'sometimes|boolean',
            'max_members' => [
                'sometimes',
                'integer',
                'min:1',
                'max:10000',
                function (string $attribute, mixed $value, \Closure $fail) use ($group) {
                    if (!$group) {
                        return;
                    }

                    $currentMembersCount = $group->users()->count();

                    if ((int) $value < $currentMembersCount) {
                        $fail("The {$attribute} must be at least {$currentMembersCount} to fit current members.");
                    }
                },
            ],
        ];
    }
}
