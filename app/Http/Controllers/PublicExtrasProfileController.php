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
        $profile->load(['photos']);

        $fotosArr = $profile->photos->map(fn ($foto) => [
            'url' => route('public.extras.foto-tambahan', [$token, $foto->urutan]),
            'alt' => 'Foto '.$foto->urutan,
        ])->values()->all();

        return view('public.extras-profile', compact('profile', 'token', 'fotosArr'));
    }

    public function foto(string $token): StreamedResponse
    {
        $profile = ExtrasProfile::where('share_token', $token)->firstOrFail();
        abort_unless($profile->foto_profil_path, 404);

        return Storage::disk('local')->response($profile->foto_profil_path);
    }

    public function video(string $token): StreamedResponse
    {
        $profile = ExtrasProfile::where('share_token', $token)->firstOrFail();
        abort_unless($profile->video_profil_path, 404);

        return Storage::disk('local')->response($profile->video_profil_path);
    }

    public function fotoTambahan(string $token, int $slot): StreamedResponse
    {
        $profile = ExtrasProfile::where('share_token', $token)->firstOrFail();
        $foto = $profile->photos()->where('urutan', $slot)->firstOrFail();

        return Storage::disk('local')->response($foto->path);
    }
}
