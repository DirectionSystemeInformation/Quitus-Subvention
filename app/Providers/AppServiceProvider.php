<?php

namespace App\Providers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        // Portail « Suivi des talents » : le super administrateur passe toutes les permissions talents.
        Gate::before(fn (User $user) => $user->hasRole('Super administrateur') ? true : null);

        View::composer('layouts.dashboard', function ($view) {
            $user = auth()->user();

            if ($user && $user->isDshn()) {
                $view->with('sidebarPendingFederations', User::where('role', 'federation')->where('status', 'pending')->count());
                $view->with('sidebarSoumisReports', Report::where('status', 'soumis')->count());
            }
        });
    }
}
