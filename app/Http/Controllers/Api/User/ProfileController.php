<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\ProfileRequest;
use App\Http\Resources\User\ProfileResource;
use App\Traits\Api\ApiResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * Get authenticated user's profile
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $data = $request->user();
        return $this->success(new ProfileResource($data), 'Profile retrieved successfully', 200);
    }

    /**
     * Update authenticated user's profile
     * If password is updated, logout from all other devices
     * 
     * @param ProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ProfileRequest $request)
    {
        $user = $request->user();

        $validatedData = $request->validated();

        // If email changed, reset verification
        if (!empty($validatedData['email']) && $validatedData['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        // If password is being updated, logout from all other devices
        if (!empty($validatedData['password'])) {
            // Keep current token, delete all others
            $user->tokens()
                ->where('id', '!=', $request->user()->currentAccessToken()->id)
                ->delete();
        } else {
            unset($validatedData['password']);
        }

        $user->update($validatedData);

        return $this->success(new ProfileResource($user->fresh()), 'Profile updated successfully', 200);
    }
}
