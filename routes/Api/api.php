<?php

use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\GroupController;
use App\Http\Controllers\Api\User\GroupInviteLinkController;
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
        Route::get('/{task}', 'show');
        Route::put('/{task}', 'update');
        Route::delete('/{task}', 'destroy');
    });

    Route::prefix('groups')->middleware('throttle:60,1')->controller(GroupController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{group}', 'show');
        Route::put('/{group}', 'update');
        Route::delete('/{group}', 'destroy');
    });

    Route::prefix('groups/{group}/invite-links')->middleware('throttle:60,1')->controller(GroupInviteLinkController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::delete('/{inviteLink}', 'destroy');
    });

    Route::prefix('invite-links')->middleware('throttle:60,1')->controller(GroupInviteLinkController::class)->group(function () {
        Route::post('/{inviteLink}/accept', 'accept');
    });
});
