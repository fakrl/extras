<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UbahPasswordController extends Controller
{
    public function edit()
    {
        return view('auth.ubah-password');
    }

    public function validateCurrentPassword(Request $request)
    {
        return response()->json([
            'valid' => Hash::check($request->input('password', ''), $request->user()->password),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($request->current_password, $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.'])->withInput();
        }

        $user = $request->user();
        $dipaksa = $user->wajib_ganti_password;
        $user->update(['password' => Hash::make($request->new_password), 'wajib_ganti_password' => false]);

        return ($dipaksa ? redirect($user->dashboardUrl()) : back())->with('status', 'Kata sandi berhasil diubah.');
    }
}
