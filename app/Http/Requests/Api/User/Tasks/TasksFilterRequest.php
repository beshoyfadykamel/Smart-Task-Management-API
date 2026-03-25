<?php

namespace App\Http\Requests\Api\User\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class TasksFilterRequest extends FormRequest
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
            'status' => 'nullable|boolean',
            'created_from' => 'nullable|date',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'created_from.date' => 'Invalid date format. Use Y-m-d (2026-03-08) or Y-m-d H:i:s (2026-03-08 14:30:00)',
            'per_page.integer' => 'The per page must be an integer.',
            'per_page.min' => 'The per page must be at least 1.',
            'per_page.max' => 'The per page may not exceed 100.',
        ];
    }
}
