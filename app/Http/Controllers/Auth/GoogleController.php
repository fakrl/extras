<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ViewAs;
use App\Models\ActivityLog;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as GoogleUser;
use Laravel\Socialite\Facades\Socialite;

/** BO.2: login/daftar Extras via Google + hubungkan/putus untuk semua role. */
class GoogleController extends Controller
{
    public const PESAN_BELUM_TERHUBUNG = 'Akun ini belum dihubungkan ke Google. Login pakai username & password dulu, lalu hubungkan di menu Profil.';

    public const PESAN_NONAKTIF = 'Akun Anda dinonaktifkan. Hubungi admin untuk info lebih lanjut.';

    public static function aktif(): bool
    {
        return filled(config('services.google.client_id'));
    }

    public function redirect(Request $request)
    {
        abort_unless(self::aktif(), 404);

        $hubungkan = $request->query('mode') === 'hubungkan';
        if ($hubungkan) {
            if (! $request->user()) {
                return redirect()->route('login');
            }
            if ($this->modeLihat($request)) {
                return back()->with('error', 'Mode lihat saja: tidak bisa menghubungkan Google atas nama akun lain.');
            }
            $request->session()->put('google_kembali', url()->previous());
        } elseif ($request->user()) {
            return redirect($request->user()->dashboardUrl());
        }

        $request->session()->put('google_mode', $hubungkan ? 'hubungkan' : 'login');

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(self::aktif(), 404);

        $hubungkan = $request->session()->pull('google_mode') === 'hubungkan' && $request->user();

        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return $this->gagal($request, $hubungkan, 'Masuk dengan Google gagal atau dibatalkan. Coba lagi.');
        }

        return $hubungkan ? $this->hubungkan($request, $google) : $this->login($request, $google);
    }

    public function lanjut(Request $request)
    {
        abort_unless(self::aktif(), 404);

        return $request->session()->has('google_baru')
            ? view('auth.google-lanjut', ['google' => $request->session()->get('google_baru')])
            : redirect()->route('login');
    }

    public function daftar(Request $request): RedirectResponse
    {
        abort_unless(self::aktif(), 404);

        $g = $request->session()->get('google_baru');
        if (! $g) {
            return redirect()->route('login');
        }

        $request->validate(['setuju_privasi' => ['accepted']], ['setuju_privasi.accepted' => 'Centang persetujuan Kebijakan Privasi dulu.']);

        if (User::withTrashed()->where('email', $g['email'])->orWhere('google_id', $g['id'])->exists()) {
            $request->session()->forget('google_baru');

            return $this->gagal($request, false, 'Email ini sudah terdaftar. Silakan masuk.');
        }

        $user = User::create([
            'name' => $g['name'],
            'email' => $g['email'],
            'username' => self::usernameDari($g['email']),
            'password' => null,
            'role' => User::ROLE_EXTRAS,
            'status' => 'aktif',
        ]);
        $user->forceFill(['google_id' => $g['id'], 'email_verified_at' => now()])->save();
        ExtrasProfile::create(['user_id' => $user->id]);

        $request->session()->forget('google_baru');
        Auth::login($user);
        $request->session()->regenerate();

        // intended_event_token tetap di session, dibaca ProfileController::update() (sama seperti registrasi biasa).
        return redirect('/extras/profil/lengkapi');
    }

    public function putus(Request $request): RedirectResponse
    {
        abort_unless(self::aktif(), 404);

        $user = $request->user();
        if (! $user->password) {
            return back()->with('error', 'Buat kata sandi dulu sebelum memutus Google, supaya akun tidak terkunci.');
        }

        $user->forceFill(['google_id' => null])->save();
        ActivityLog::record('PUTUS_GOOGLE', "{$user->name} memutus akun Google.", $user);

        return back()->with('status', 'Akun Google diputus.');
    }

    private function login(Request $request, GoogleUser $google): RedirectResponse
    {
        $user = User::withTrashed()->where('google_id', $google->getId())->first();

        if (! $user && $google->getEmail()) {
            $user = User::withTrashed()->where('email', $google->getEmail())->first();
            if ($user && ! $user->isExtras()) {
                return $this->gagal($request, false, self::PESAN_BELUM_TERHUBUNG);
            }
            if ($user?->google_id) {
                return $this->gagal($request, false, 'Email ini sudah terhubung ke akun Google lain.');
            }
        }

        if (! $user) {
            if (! $google->getEmail()) {
                return $this->gagal($request, false, 'Akun Google ini tidak punya email.');
            }
            $request->session()->put('google_baru', [
                'id' => $google->getId(),
                'name' => $google->getName() ?: Str::before($google->getEmail(), '@'),
                'email' => $google->getEmail(),
            ]);

            return redirect()->route('google.lanjut');
        }

        if ($user->trashed() || $user->diblokir()) {
            return $this->gagal($request, false, self::PESAN_NONAKTIF);
        }

        if (! $user->google_id) {
            $user->forceFill(['google_id' => $google->getId()])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return LoginController::arahkanSetelahLogin($request);
    }

    private function hubungkan(Request $request, GoogleUser $google): RedirectResponse
    {
        if ($this->modeLihat($request)) {
            return $this->gagal($request, true, 'Mode lihat saja: tidak bisa menghubungkan Google atas nama akun lain.');
        }

        $user = $request->user();
        if (User::withTrashed()->where('google_id', $google->getId())->whereKeyNot($user->id)->exists()) {
            return $this->gagal($request, true, 'Akun Google ini sudah terhubung ke akun lain.');
        }

        $user->forceFill(['google_id' => $google->getId()]);
        if (! $user->email && $google->getEmail() && ! User::withTrashed()->where('email', $google->getEmail())->exists()) {
            $user->email = $google->getEmail();
        }
        $user->save();

        ActivityLog::record('HUBUNGKAN_GOOGLE', "{$user->name} menghubungkan akun Google.", $user);

        return redirect($request->session()->pull('google_kembali') ?: route('ubah-password'))
            ->with('status', 'Akun Google berhasil dihubungkan.');
    }

    private function gagal(Request $request, bool $hubungkan, string $pesan): RedirectResponse
    {
        return $hubungkan
            ? redirect($request->session()->pull('google_kembali') ?: route('ubah-password'))->with('error', $pesan)
            : redirect()->route('login')->withErrors(['email' => $pesan]);
    }

    private function modeLihat(Request $request): bool
    {
        return in_array($request->session()->get('sa_mode'), ViewAs::MODES_LIHAT, true);
    }

    private static function usernameDari(string $email): string
    {
        $dasar = Str::of(Str::before($email, '@'))->lower()->replace('.', '_')
            ->replaceMatches('/[^a-z0-9_-]/', '')->limit(40, '')->value() ?: 'extras';

        $username = $dasar;
        for ($i = 2; User::withTrashed()->where('username', $username)->exists(); $i++) {
            $username = $dasar.$i;
        }

        return $username;
    }
}
