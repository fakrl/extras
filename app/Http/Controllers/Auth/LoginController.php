<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        // SENGAJA bukan redirect()->intended() — RF-03 mensyaratkan tiap role
        // selalu diarahkan ke dashboard masing-masing setelah login, terlepas
        // dari URL apa yang sempat dicoba diakses sebelum login (mis. Extras
        // yang tadinya nyasar ke /admin/dashboard dan kena redirect ke /login,
        // begitu berhasil login jangan dibalikin lagi ke /admin/dashboard).
        return redirect($this->dashboardRouteFor(Auth::user()->role));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * RF-03: tiap role diarahkan ke dashboard masing-masing setelah login,
     * bukan satu dashboard generic yang sama untuk semua orang.
     */
    private function dashboardRouteFor(string $role): string
    {
        return match ($role) {
            'super_admin' => '/super-admin/dashboard',
            'admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed' => '/admin/dashboard',
            'casting_director' => '/cd/dashboard',
            'extras' => '/extras/dashboard',
            default => '/',
        };
    }
}
