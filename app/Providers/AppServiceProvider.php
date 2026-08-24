<?php

namespace App\Providers;

use App\Models\Report;
use App\Models\User;
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
        View::composer('layouts.dashboard', function ($view) {
            $user = auth()->user();

            if ($user && $user->isDshn()) {
                $view->with('sidebarPendingFederations', User::where('role', 'federation')->where('status', 'pending')->count());
                $view->with('sidebarSoumisReports', Report::where('status', 'soumis')->count());
            }
        });
    }
}
