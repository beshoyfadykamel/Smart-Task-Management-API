<?php

use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:sanctum', 'verified', 'throttle:60,1'])->group(function () {

    // ── Users ─────────────────────────────────────────────────────────────────
    Route::prefix('users')->controller(UserController::class)->group(function () {

        // Static routes first — must come before /{user} to avoid route conflicts
        Route::get('/trashed', 'trashed')->middleware('can:view trashed users');
        Route::post('/', 'store')->middleware('can:create users');

        // Dynamic routes
        Route::get('/', 'index')->middleware('can:view users');
        Route::get('/{user}', 'show')->middleware('can:view users');
        Route::put('/{user}', 'update')->middleware('can:update users');
        Route::delete('/{user}', 'destroy')->middleware('can:delete users');
        Route::post('/{user}/restore', 'restore')->middleware('can:restore users')->withTrashed();
        Route::delete('/{user}/force-delete', 'forceDelete')->middleware('can:force delete users')->withTrashed();
        Route::put('/{user}/role', 'changeRole')->middleware('can:change roles');
        Route::post('/{user}/permissions/give', 'givePermissions')->middleware('can:give permissions');
        Route::post('/{user}/permissions/revoke', 'revokePermissions')->middleware('can:revoke permissions');
    });

    // ── Roles ──────────────────────────────────────────────────────────────────
    Route::prefix('roles')->controller(RoleController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:view roles');
        Route::post('/', 'store')->middleware('can:create roles');
        Route::get('/{role}', 'show')->middleware('can:view roles');
        Route::put('/{role}', 'update')->middleware('can:update roles');
        Route::delete('/{role}', 'destroy')->middleware('can:delete roles');
    });

    // ── Permissions ────────────────────────────────────────────────────────────
    Route::get('/permissions', [PermissionController::class, 'index'])
        ->middleware('can:view permissions');
});
