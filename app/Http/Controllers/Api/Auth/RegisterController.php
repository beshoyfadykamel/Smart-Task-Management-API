<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Traits\Api\ApiResponse;

class RegisterController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user
     * Sends email verification notification
     * Token expires after 30 days
     * 
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $token = $user->createToken($user->name);
        $token->accessToken->update([
            'expires_at' => now()->addDays(30),
        ]);

        $user->sendEmailVerificationNotification();

        $data = [
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
        ];

        return $this->success($data, "User registered successfully", 201);
    }
}
