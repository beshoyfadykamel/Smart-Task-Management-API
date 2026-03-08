<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Traits\Api\ApiResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    use ApiResponse;

    /**
     * Logout from current device
     * Deletes only the current access token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(
            null,
            'Logged out successfully.',
            200
        );
    }

    /**
     * Logout from all devices
     * Deletes all access tokens for the user
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();
        return $this->success(
            null,
            'Logged out from all devices successfully.',
            200
        );
    }
}
