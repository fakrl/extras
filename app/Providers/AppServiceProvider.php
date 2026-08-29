<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
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
        // Arahkan user yang sudah login ke dashboard role-nya, bukan /home.
        RedirectIfAuthenticated::redirectUsing(fn () => Auth::user()?->dashboardUrl() ?? '/login');

        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
