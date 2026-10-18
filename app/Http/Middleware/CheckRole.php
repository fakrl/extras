<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC gate untuk 5 role sistem. Dipakai di route lewat alias 'role',
 * misal: ->middleware('role:admin,super_admin')
 *
 * Pola ini mengikuti CheckRole di Nobel Akademi (bukan middleware terpisah
 * per role), satu middleware, role diberikan sebagai parameter route.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // CM.3: godmode SA dicabut untuk aksi tulis. GET/HEAD tetap lolos (lihat-saja);
        // non-GET hanya lolos kalau sedang mode Monitoring Admin/Korlap (session sa_mode).
        if ($user->role === 'super_admin' && (in_array('admin', $roles, true) || in_array('korlap', $roles, true))) {
            if (in_array($request->method(), ['GET', 'HEAD'], true)) {
                return $next($request);
            }

            $mode = $request->hasSession() ? $request->session()->get('sa_mode') : null;
            if (in_array($mode, $roles, true)) {
                return $next($request);
            }

            abort(403, 'Proyek ini bukan tanggung jawab Anda. Masuk mode Monitoring dulu untuk bertindak sebagai Admin/Korlap.');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
