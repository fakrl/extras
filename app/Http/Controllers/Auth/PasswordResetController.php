<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Form "lupa password" — user masukkan email, Laravel kirim link
     * bertoken (masa berlaku default 60 menit, lihat config/auth.php).
     */
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        // Pesan sukses ditampilkan terlepas email ditemukan atau tidak,
        // supaya orang lain tidak bisa "menebak" email mana yang terdaftar
        // di sistem cuma dari respons form ini (praktik umum, bukan berlebihan).
        return back()->with('status', 'Kalau email tersebut terdaftar, link reset password sudah dikirim. Cek inbox (atau folder spam) kamu.');
    }

    /**
     * Form reset — token dari link email, dicek validitasnya oleh
     * Password::reset() saat submit (bukan di sini), supaya token yang sudah
     * kedaluwarsa tetap dikasih pesan error yang jelas, bukan 404 mentah.
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)]);
        }

        return redirect()->route('login')->with('status', 'Password berhasil diganti. Silakan masuk dengan password baru kamu.');
    }
}
