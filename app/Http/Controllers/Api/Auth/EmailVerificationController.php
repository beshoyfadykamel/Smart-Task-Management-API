<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Traits\Api\ApiResponse;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use ApiResponse;

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return $this->success([], 'Email verified successfully' , 200);
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->error('Email already verified', 400, 400);
        }
        $request->user()->sendEmailVerificationNotification();
        return $this->success([], 'Verification email resent' , 200);
    }
}