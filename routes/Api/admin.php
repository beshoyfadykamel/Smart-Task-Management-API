<?php

use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:sanctum', 'verified', 'active', 'throttle:60,1'])->group(function () {

    // ── Users ─────────────────────────────────────────────────────────────────
    Route::prefix('users')->controller(UserController::class)->group(function () {

        // Static routes first — must come before /{user} to avoid route conflicts
        Route::get('/trashed', 'trashed');
        Route::post('/', 'store');

        // Dynamic routes
        Route::get('/', 'index');
        Route::get('/{user}', 'show');
        Route::put('/{user}', 'update');
        Route::delete('/{user}', 'destroy');
        Route::post('/{user}/restore', 'restore')->withTrashed();
        Route::delete('/{user}/force-delete', 'forceDelete')->withTrashed();
        Route::put('/{user}/role', 'changeRole');
        Route::post('/{user}/permissions/give', 'givePermissions');
        Route::post('/{user}/permissions/revoke', 'revokePermissions');
    });

    // ── Roles ──────────────────────────────────────────────────────────────────
    Route::prefix('roles')->controller(RoleController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{role}', 'show');
        Route::put('/{role}', 'update');
        Route::delete('/{role}', 'destroy');
    });

    // ── Permissions ────────────────────────────────────────────────────────────
    Route::get('/permissions', [PermissionController::class, 'index']);
});
