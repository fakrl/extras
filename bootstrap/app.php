<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\SecurityHeaders;
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

        // Percaya semua proxy di depan aplikasi (ngrok, dsb) supaya Laravel
        // baca header X-Forwarded-Proto dengan benar dan tahu request aslinya
        // HTTPS — tanpa ini, request lewat ngrok bisa memicu redirect loop
        // karena Laravel salah kira koneksinya HTTP biasa.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
