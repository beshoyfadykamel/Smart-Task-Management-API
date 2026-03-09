<?php

namespace App\Http\Requests\Api\Admin\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RevokePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => 'required|array|min:1',
            'permissions.*' => [
                'required',
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'sanctum'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'At least one permission is required.',
            'permissions.*.exists' => 'The permission :input does not exist.',
        ];
    }
}
