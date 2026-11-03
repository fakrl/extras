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

        // Percaya proxy dari TRUSTED_PROXIES (nginx/load balancer asli) supaya
        // Laravel baca X-Forwarded-Proto/For dengan benar. Kosong = tidak
        // percaya siapa pun - $request->ip() pakai koneksi asli, bukan header
        // yang bisa dipalsukan siapa saja (penting buat throttle login/register).
        $middleware->trustProxies(at: array_filter(explode(',', (string) env('TRUSTED_PROXIES', ''))) ?: null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
