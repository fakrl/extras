<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** SA-04: akun nonaktif/diblokir yang masih punya sesi dikeluarkan. Jalan sebelum ViewAs (cek akun asli, bukan akun yang dilihat SA). */
class CekAkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->status !== null && $user->diblokir() && ! $request->routeIs('logout')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Hubungi admin untuk info lebih lanjut.',
            ]);
        }

        return $next($request);
    }
}
