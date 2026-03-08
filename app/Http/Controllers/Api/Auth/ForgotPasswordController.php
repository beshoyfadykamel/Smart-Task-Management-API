<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Traits\Api\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use ApiResponse;

    /**
     * Send password reset link to user's email.
     * 
     * @param ForgotPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success([], 'Password reset link sent to your email', 200);
        }

        return $this->error('Unable to send password reset link', 422, 422);
    }

    /**
     * Reset user's password.
     * 
     * @param ResetPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {

            $user = User::where('email', $request->email)->first();
            $user->tokens()->delete();

            return $this->success([], 'Password reset successfully', 200);
        }

        return $this->error('Unable to reset password', 422, 422);
    }
}
