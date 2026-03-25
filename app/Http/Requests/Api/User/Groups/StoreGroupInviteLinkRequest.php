<?php

namespace App\Http\Requests\Api\User\Groups;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupInviteLinkRequest extends FormRequest
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
            'max_uses' => 'nullable|integer|min:1|max:100000',
            'expires_at' => 'nullable|date|after:now',
            'role' => 'sometimes|in:member,admin',
        ];
    }
}
