<?php

namespace App\Providers;

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
     *
     * Observers diregistrasi via #[ObservedBy] attribute pada model masing-masing:
     * - Journal -> JournalObserver
     * - LeaveRequest -> LeaveRequestObserver
     */
    public function boot(): void
    {
        // Super Admin memiliki akses penuh ke seluruh gate & abilities
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
