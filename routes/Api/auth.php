<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {

    Route::middleware('guest:sanctum' , 'throttle:5,1')->group(function () {
        Route::post('/register', [RegisterController::class, 'register']);
        Route::post('/login', [LoginController::class, 'login']);

    });


    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('/logout', [LogoutController::class, 'logout'])->middleware('auth:sanctum');

    });

});


