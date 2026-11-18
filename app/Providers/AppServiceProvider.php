<?php

namespace App\Providers;

use App\Models\AccountRequest;
use App\Models\Activity;
use App\Models\Direction;
use App\Models\User;
use App\Policies\AccountRequestPolicy;
use App\Policies\ActivityPolicy;
use App\Policies\DirectionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(AccountRequest::class, AccountRequestPolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Direction::class, DirectionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('admin', static fn (User $user): bool => $user->isAdmin());
        Gate::define('sg', static fn (User $user): bool => $user->isSg());
        Gate::define('director', static fn (User $user): bool => $user->isDirector());

        Gate::define('view-direction', static fn (User $user, mixed $direction): bool => $user->canAccessDirection($direction));
        Gate::define('manage-direction', static fn (User $user, mixed $direction): bool => $user->canManageDirection($direction));
        Gate::define('access-direction', static fn (User $user, mixed $direction): bool => $user->canAccessDirection($direction));
    }
}
