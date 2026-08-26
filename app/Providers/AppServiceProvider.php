<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Auth;
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
        // Tanpa override ini, middleware 'guest' bawaan Laravel fallback ke
        // route 'dashboard' atau 'home' kalau ada (lihat
        // RedirectIfAuthenticated::defaultRedirectUri()) — dua-duanya sekarang
        // terdaftar ('/' = home, '/dashboard' = dashboard), jadi tanpa override
        // ini pun sudah aman dari infinite loop. Override tetap dipasang supaya
        // UX-nya lebih tepat: user yang sudah login yang coba buka /login
        // langsung diarahkan ke dashboard role-nya (RF-03), bukan ke landing
        // page publik.
        RedirectIfAuthenticated::redirectUsing(fn () => Auth::user()?->dashboardUrl() ?? '/login');
    }
}
