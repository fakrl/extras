<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
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
        // View pagination default Laravel butuh Tailwind (nggak dipakai di sini) -> ikon panah
        // SVG jadi raksasa. Pakai markup bootstrap-4 (teks ‹ ›, tanpa SVG), gayanya di layouts/app.
        Paginator::defaultView('pagination::bootstrap-4');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-4');

        // Arahkan user yang sudah login ke dashboard role-nya, bukan /home.
        RedirectIfAuthenticated::redirectUsing(fn () => Auth::user()?->dashboardUrl() ?? '/login');

        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
            // SECURITY-CHECKLIST.md #9: sebelumnya cuma andalkan SESSION_SECURE_COOKIE
            // di .env diisi manual saat deploy, dipaksa di sini biar tidak
            // bergantung Fakrul ingat set env var itu sendiri.
            Config::set('session.secure', true);
        }
    }
}
