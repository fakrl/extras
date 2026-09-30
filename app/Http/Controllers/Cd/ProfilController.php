<?php

namespace App\Http\Controllers\Cd;

use App\Http\Controllers\Controller;
use App\Rules\NomorWa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('cd.profil', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_perusahaan' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'nomor_wa' => ['nullable', 'string', 'max:20', new NomorWa],
        ]));

        return back()->with('status', 'Profil diperbarui.');
    }
}
