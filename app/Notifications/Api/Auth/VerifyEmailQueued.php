<?php

namespace App\Notifications\Api\Auth;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;

class VerifyEmailQueued extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;
}
