<?php

namespace App\Notifications\Api\Auth;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPasswordQueued extends BaseResetPassword implements ShouldQueue
{
    use Queueable;
}
