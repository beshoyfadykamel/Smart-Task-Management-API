<?php

use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

Route::prefix('auth')->group(function () {

    Route::middleware('guest:sanctum', 'throttle:5,1')->group(function () {
        Route::post('/register', [RegisterController::class, 'register'])->name('register');
        Route::post('/login', [LoginController::class, 'login'])->name('login');
    });


    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [LogoutController::class, 'logout'])->middleware('auth:sanctum')->name('logout');

        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
            ->middleware(['throttle:6,1'])->name('verification.send');
    });
});
