<?php

namespace App\Http\Controllers;

use App\Models\ExtrasProfile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicExtrasProfileController extends Controller
{
    public function show(string $token)
    {
        $profile = ExtrasProfile::where('share_token', $token)->firstOrFail();
        $profile->load(['categories', 'user']);
        $this->abortUnlessAktif($profile);

        $fotosArr = $profile->fotoTambahan()->keys()->map(fn ($slot) => [
            'url' => route('public.extras.foto-tambahan', [$token, $slot]),
            'alt' => 'Foto '.$slot,
        ])->all();

        return view('public.extras-profile', compact('profile', 'token', 'fotosArr'));
    }

    public function foto(string $token): StreamedResponse
    {
        $profile = ExtrasProfile::where('share_token', $token)->firstOrFail();
        $this->abortUnlessAktif($profile);
        abort_unless($profile->foto_profil_path, 404);

        return Storage::disk('local')->response($profile->foto_profil_path);
    }

    public function fotoTambahan(string $token, int $slot): StreamedResponse
    {
        $profile = ExtrasProfile::where('share_token', $token)->firstOrFail();
        $this->abortUnlessAktif($profile);
        $path = $profile->fotoTambahan()->get($slot);
        abort_unless($path, 404);

        return Storage::disk('local')->response($path);
    }

    /**
     * Akun nonaktif/melanggar/soft-deleted TIDAK boleh tampil publik lewat token share.
     * User::user() soft-deleted otomatis null (global scope SoftDeletes), jadi ikut ter-404 di sini.
     */
    private function abortUnlessAktif(ExtrasProfile $profile): void
    {
        abort_unless($profile->user?->status === 'aktif', 404);
    }
}
