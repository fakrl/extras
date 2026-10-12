<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Rules\NomorWa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('client.profil', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $emailBerubah = ($request->input('email') ?: null) !== $user->email;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_perusahaan' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'nomor_wa' => ['nullable', 'string', 'max:20', new NomorWa],
            'current_password' => [Rule::requiredIf($emailBerubah && (bool) $user->password), 'nullable', 'current_password'],
        ], ['current_password.required' => 'Masukkan kata sandi saat ini untuk mengganti email.']);

        $user->update(Arr::except($data, 'current_password'));

        return back()->with('status', 'Profil diperbarui.');
    }
}
