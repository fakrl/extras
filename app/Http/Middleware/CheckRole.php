<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC gate untuk 7 role sistem. Dipakai di route lewat alias 'role',
 * misal: ->middleware('role:admin_default,super_admin')
 *
 * Pola ini mengikuti CheckRole di Nobel Akademi (bukan middleware terpisah
 * per role) — satu middleware, role diberikan sebagai parameter route.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Expand aliases to ensure smooth transition between 7 roles and 5 roles
        $expandedRoles = [];
        foreach ($roles as $r) {
            $expandedRoles[] = $r;
            if ($r === 'admin' || $r === 'admin_default') {
                $expandedRoles[] = 'admin';
                $expandedRoles[] = 'admin_default';
            }
            if ($r === 'korlap' || $r === 'admin_korlap') {
                $expandedRoles[] = 'korlap';
                $expandedRoles[] = 'admin_korlap';
            }
            if ($r === 'client' || $r === 'casting_director') {
                $expandedRoles[] = 'client';
                $expandedRoles[] = 'casting_director';
            }
        }
        $expandedRoles = array_unique($expandedRoles);

        // Super Admin Godmode: otomatis lolos untuk semua rute internal admin dan korlap
        if ($user->role === 'super_admin' && (in_array('admin', $expandedRoles, true) || in_array('korlap', $expandedRoles, true))) {
            return $next($request);
        }

        if (! in_array($user->role, $expandedRoles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
