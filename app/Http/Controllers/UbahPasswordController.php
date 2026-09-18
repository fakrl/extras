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

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($request->current_password, $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.'])->withInput();
        }

        $request->user()->update(['password' => Hash::make($request->new_password)]);

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }
}
