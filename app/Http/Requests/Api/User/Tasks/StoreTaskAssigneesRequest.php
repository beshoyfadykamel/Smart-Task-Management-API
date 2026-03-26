<?php

namespace App\Http\Requests\Api\User\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskAssigneesRequest extends FormRequest
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
            'user_ids' => 'required|array|min:1|max:100',
            'user_ids.*' => 'required|integer|distinct|exists:users,id',
            'status' => 'sometimes|in:pending,in_progress,completed',
        ];
    }
}
