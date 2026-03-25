<?php

namespace App\Providers;

use App\Models\Group;
use App\Models\Task;
use App\Models\User;
use App\Policies\Api\Admin\PermissionPolicy;
use App\Policies\Api\Admin\RolePolicy;
use App\Policies\Api\Admin\UserPolicy;
use App\Policies\Api\User\GroupPolicy;
use App\Policies\Api\User\TaskPolicy;
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
        // Guard abilities are intentionally excluded from this bypass — they
        // must always be evaluated regardless of role.
        Gate::before(function (User $user, string $ability) {
            $guardAbilities = ['notSelf', 'notSuperAdmin', 'assignSuperAdmin'];
            if ($user->hasRole('super_admin') && !in_array($ability, $guardAbilities)) {
                return true;
            }
        });

        // ── Admin Policies ─────────────────────────────────────────
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
    }
}
