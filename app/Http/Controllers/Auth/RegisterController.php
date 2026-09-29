<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * RF-01: registrasi mandiri Extras, publik, tanpa kode undangan.
     *
     * SPEC.md Bagian B5: kalau datang dari tombol "Daftar" di link event
     * publik (?event=token), simpan token itu ke session di sini, dibaca
     * lagi nanti oleh ProfileController::update() setelah lengkapi-profil
     * selesai (RF-06 tetap wajib jalan dulu, bukan di-skip).
     */
    public function showExtras(Request $request)
    {
        if ($request->query('event')) {
            $request->session()->put('intended_event_token', $request->query('event'));
        }

        return view('auth.register-extras');
    }

    public function registerExtras(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'setuju_privasi' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'extras',
            'status' => 'aktif',
        ]);

        // RF-06: profil dibuat kosong dulu, extras lengkapi rate card/foto/dst
        // di langkah berikutnya, bukan wajib penuh saat registrasi awal.
        ExtrasProfile::create([
            'user_id' => $user->id,
        ]);

        Auth::login($user);

        return redirect('/extras/profil/lengkapi');
    }
}
