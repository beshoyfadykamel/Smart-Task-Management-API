<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\User;
use App\Traits\Api\ApiResponse;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    use ApiResponse;
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
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
