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
            'name' => 'required|unique:roles,name,' . $this->route('role')->id,
            'permissions' => 'required|array',
            'permissions.*' => [
                Rule::exists('permissions', 'name')->where('guard_name', 'sanctum'),
            ],
        ];
    }
}
