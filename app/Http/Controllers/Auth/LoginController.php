<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * SPEC.md Bagian B5: sama seperti RegisterController::showExtras(),
     * simpan token event dari ?event=token (link publik), dibaca lagi
     * setelah login sukses di bawah.
     */
    public function show(Request $request)
    {
        if ($request->query('event')) {
            $request->session()->put('intended_event_token', $request->query('event'));
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Satu input untuk dua identifier. Nama field tetap 'email'
            // (form lama, link lupa-password, dan pesan error ikut key ini).
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $identifier = str_contains($data['email'], '@') ? 'email' : 'username';

        if (! Auth::attempt([$identifier => $data['email'], 'password' => $data['password']], $request->boolean('remember'))) {
            // BQ.3: akun Google tanpa password (BO.2) dikasih arahan, bukan pesan kredensial generik.
            $google = User::where($identifier, $data['email'])->whereNull('password')->exists();

            return back()->withErrors([
                'email' => $google
                    ? 'Akun ini login pakai Google. Klik "Lanjut dengan Google", atau pakai "Lupa password" untuk membuat password.'
                    : 'Email/username atau password salah.',
            ])->onlyInput('email');
        }

        if (Auth::user()->diblokir()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Hubungi admin untuk info lebih lanjut.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return self::arahkanSetelahLogin($request);
    }

    // Dipakai juga GoogleController (BO.2).
    public static function arahkanSetelahLogin(Request $request): RedirectResponse
    {
        // SPEC.md Bagian B5: mekanisme TERPISAH dari redirect()->intended()
        // (sengaja tidak dipakai, lihat komentar di bawah), session key
        // eksplisit yang cuma terisi kalau login ini datang dari link event
        // publik. Kosong = behavior tidak berubah sama sekali dari sebelumnya.
        $eventToken = $request->session()->pull('intended_event_token');
        if ($eventToken) {
            $project = CastingProject::where('share_token', $eventToken)->first();

            if ($project && $project->menerimaPendaftaran() && Auth::user()->isExtras()) {
                return redirect()->route('extras.projects.show', $project);
            }
        }

        // SENGAJA bukan redirect()->intended(), RF-03 mensyaratkan tiap role
        // selalu diarahkan ke dashboard masing-masing setelah login, terlepas
        // dari URL apa yang sempat dicoba diakses sebelum login (mis. Extras
        // yang tadinya nyasar ke /admin/dashboard dan kena redirect ke /login,
        // begitu berhasil login jangan dibalikin lagi ke /admin/dashboard).
        return redirect(Auth::user()->dashboardUrl());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
