<?php

namespace App\Http\Middleware;

use App\Traits\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class CheckUserStatus
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user() && !$request->user()->status) {
            return $this->error('Your account has been suspended.', null, 403);
        }

        return $next($request);
    }
}
