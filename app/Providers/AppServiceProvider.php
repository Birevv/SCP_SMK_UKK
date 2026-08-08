<?php

namespace App\Providers;

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
        //
    }
}
