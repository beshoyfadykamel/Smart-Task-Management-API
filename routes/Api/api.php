<?php

use App\Http\Controllers\Api\User\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'active'])->group(function () {

    Route::prefix('profile')->middleware('throttle:60,1')->group(function () {
        Route::get('/', [ProfileController::class, 'index']);
        Route::post('/update', [ProfileController::class, 'update']);
    });
});
