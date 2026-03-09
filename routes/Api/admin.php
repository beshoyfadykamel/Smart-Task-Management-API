<?php

use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);

        Route::get('/trashed', [UserController::class, 'trashed']);

        Route::post('/', [UserController::class, 'store']);

        Route::get('/{user}', [UserController::class, 'show']);

        Route::put('/{user}', [UserController::class, 'update']);

        Route::put('/{user}/role', [UserController::class, 'changeRole']);

        Route::post('/{user}/permissions/give', [UserController::class, 'givePermissions']);

        Route::post('/{user}/permissions/revoke', [UserController::class, 'revokePermissions']);

        Route::delete('/{user}', [UserController::class, 'destroy']);

        Route::post('/{user}/restore', [UserController::class, 'restore'])->withTrashed();

        Route::delete('/{user}/force-delete', [UserController::class, 'forceDelete'])->withTrashed();
    });


    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{role}', [RoleController::class, 'show']);
        Route::put('/{role}', [RoleController::class, 'update']);
        Route::delete('/{role}', [RoleController::class, 'destroy']);
    });

    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
    });
});
