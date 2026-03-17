<?php

use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'active'])->group(function () {

    Route::prefix('profile')->middleware('throttle:60,1')->group(function () {
        Route::get('/', [ProfileController::class, 'index']);
        Route::post('/update', [ProfileController::class, 'update']);
    });

    Route::prefix('tasks')->middleware('throttle:60,1')->controller(TaskController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });
});
