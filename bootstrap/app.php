<?php

use App\Http\Middleware\CekAkunAktif;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ViewAs;
use App\Http\Middleware\WajibGantiPassword;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        $middleware->append(SecurityHeaders::class);
        $middleware->appendToGroup('web', CekAkunAktif::class);
        $middleware->appendToGroup('web', WajibGantiPassword::class);
        // BD.6: setelah WajibGantiPassword (cek pakai SA asli), sebelum CheckRole route (role dicek pakai akun target).
        $middleware->appendToGroup('web', ViewAs::class);

        // Trusted proxy diatur di config/trustedproxy.php (bukan di sini -
        // env() di bootstrap tidak kebaca kalau config:cache dipakai).
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
