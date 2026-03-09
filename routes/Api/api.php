<?php

use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });

    Route::prefix('profile')->middleware('throttle:60,1')->group(function () {
        Route::get('/', [ProfileController::class, 'index']);
        Route::post('/update', [ProfileController::class, 'update']);
    });
});
