<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Traits\Api\ApiResponse;
use Illuminate\Support\Facades\DB;
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
        // Always send the same response regardless of whether the email exists,
        // to prevent user enumeration attacks.
        Password::sendResetLink($request->only('email'));

        return $this->success([], 'If this email is registered, a password reset link has been sent.', 200);
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
                DB::transaction(function () use ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->save();

                    $user->tokens()->delete();
                });
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success([], 'Password reset successfully', 200);
        }

        return $this->error('Unable to reset password', null, 422);
    }
}
