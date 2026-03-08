<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Traits\Api\ApiResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use ApiResponse;

    /**
     * Login user and generate access token
     * Token expires after 30 days
     * 
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {


        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->error('Invalid credentials', 401, 401);
        }

        $token = $user->createToken($user->name);
        $token->accessToken->update([
            'expires_at' => now()->addDays(30),
        ]);


        $data = [
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
        ];

        return $this->success($data, 'User logged in successfully', 200);
    }
}
