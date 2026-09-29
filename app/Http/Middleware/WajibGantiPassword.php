<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WajibGantiPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        // BE.5: cuma route ber-auth; halaman publik (home, /event, /p, privacy) tetap bisa dibuka.
        if ($request->user()?->wajib_ganti_password
            && in_array('auth', $request->route()?->gatherMiddleware() ?? [], true)
            && ! $request->routeIs('ubah-password', 'ubah-password.update', 'ubah-password.validate', 'logout')) {
            return redirect()->route('ubah-password')
                ->with('error', 'Demi keamanan, ganti password sementara kamu dulu sebelum lanjut.');
        }

        return $next($request);
    }
}
