<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * SPEC BD.6: mode Monitoring Super Admin. Admin/Korlap = cuma ganti menu.
 * Client/Extras = read-only: GET dirender sebagai akun target, non-GET ditolak.
 * Session tetap milik Super Admin; hak SA dicek ulang dari DB tiap request.
 */
class ViewAs
{
    public const MODES = [User::ROLE_ADMIN, User::ROLE_KORLAP, User::ROLE_CLIENT, User::ROLE_EXTRAS];

    public const MODES_LIHAT = [User::ROLE_CLIENT, User::ROLE_EXTRAS];

    public function handle(Request $request, Closure $next): Response
    {
        View::share('saMonitoring', null);

        if (! $request->hasSession() || ! $request->session()->has('sa_mode')) {
            return $next($request);
        }

        $sa = $request->user();
        $mode = $request->session()->get('sa_mode');

        if (! $sa?->isSuperAdmin() || $sa->status === 'nonaktif' || ! in_array($mode, self::MODES, true)) {
            return $this->reset($request, $next);
        }

        $target = null;
        if (in_array($mode, self::MODES_LIHAT, true)) {
            $target = User::find($request->session()->get('sa_view_user_id'));
            if (! self::bolehDilihat($target, $mode)) {
                return $this->reset($request, $next);
            }

            if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
                if (! $request->routeIs('super-admin.mode.keluar', 'logout')) {
                    return back()->with('error', 'Mode lihat saja: Super Admin tidak bisa melakukan aksi atas nama akun ini.');
                }
            } elseif (! $request->is('super-admin', 'super-admin/*')) {
                Auth::setUser($target);
            }
        }

        View::share('saMonitoring', ['mode' => $mode, 'target' => $target, 'sa' => $sa]);

        return $next($request);
    }

    public static function bolehDilihat(?User $user, string $mode): bool
    {
        return $user
            && in_array($mode, self::MODES_LIHAT, true)
            && $user->role === $mode
            && $user->status === 'aktif'
            && ! $user->is_protected;
    }

    private function reset(Request $request, Closure $next): Response
    {
        $request->session()->forget(['sa_mode', 'sa_view_user_id']);

        return $next($request);
    }
}
