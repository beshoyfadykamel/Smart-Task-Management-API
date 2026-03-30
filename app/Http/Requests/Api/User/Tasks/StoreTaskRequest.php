<?php

namespace App\Http\Requests\Api\User\Tasks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'group_slug' => [
                'nullable',
                'string',
                Rule::exists('groups', 'slug')->where('active', true),
            ],
            'image_path' => 'nullable|string|max:2048',
            'active' => 'sometimes|boolean',
            'due_date' => 'nullable|date',
        ];
    }
}
