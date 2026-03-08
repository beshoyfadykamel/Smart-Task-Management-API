<?php

use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::prefix('users')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/trashed', [UserController::class, 'trashed']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        Route::post('/{user}/restore', [UserController::class, 'restore'])->withTrashed();
        Route::delete('/{user}/force-delete', [UserController::class, 'forceDelete'])->withTrashed();
    });
});
