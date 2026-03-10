<?php

namespace App\Http\Requests\Api\Admin\Roles_Permissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->route('role')->id), Rule::notIn(['user', 'admin', 'super_admin'])],
            'permissions' => 'required|array',
            'permissions.*' => [
                Rule::exists('permissions', 'name')->where('guard_name', 'sanctum'),
            ],
        ];
    }
}
