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

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
