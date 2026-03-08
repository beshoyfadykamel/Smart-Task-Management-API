<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\ProfileRequest;
use App\Http\Resources\User\ProfileResource;
use App\Traits\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        // If password is being updated, logout from all other devices
        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);

            // Keep current token, delete all others
            $user->tokens()
                ->where('id', '!=', $request->user()->currentAccessToken()->id)
                ->delete();
        }

        $user->update($validatedData);

        return $this->success(new ProfileResource($user), 'Profile updated successfully', 200);
    }
}
