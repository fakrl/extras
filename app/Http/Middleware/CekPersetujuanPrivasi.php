<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** DA.2: Extras yang belum setuju Kebijakan Privasi & S&K diarahkan ke halaman persetujuan. Jalan setelah WajibGantiPassword (ganti password dulu, baru consent). */
class CekPersetujuanPrivasi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isExtras()
            && $user->privasi_disetujui_at === null
            && in_array('auth', $request->route()?->gatherMiddleware() ?? [], true)
            && ! $request->routeIs('privasi.consent', 'privasi.consent.store', 'logout')) {
            return redirect()->route('privasi.consent');
        }

        return $next($request);
    }
}
