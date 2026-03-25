<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\Api\ApiResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use ApiResponse;

    /**
     * Return a verification notice for unverified authenticated users.
     */
    public function notice(Request $request)
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return $this->success([], 'Email is already verified.', 200);
        }

        return $this->error('Email verification is required.', null, 403);
    }

    /**
     * Verify user's email address.
     * Route is protected by 'signed' middleware only — no auth required.
     * The hash is compared against sha1 of the user's email for integrity.
     */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return $this->error('Invalid verification link.', null, 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success([], 'Email already verified.', 200);
        }

        $user->markEmailAsVerified();

        return $this->success([], 'Email verified successfully.', 200);
    }

    /**
     * Resend email verification notification.
     * Requires auth:sanctum.
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->error('Email already verified.', null, 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->success([], 'Verification email resent.', 200);
    }
}
