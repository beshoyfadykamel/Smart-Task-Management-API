<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\Api\Admin\PermissionPolicy;
use App\Policies\Api\Admin\RolePolicy;
use App\Policies\Api\Admin\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── super_admin bypasses ALL policies ──────────────────────
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });

        // ── Admin Policies ─────────────────────────────────────────
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
    }
}
