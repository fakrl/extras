{{-- BI.1: profil editorial di dalam aplikasi (pemilik|admin|client), media lewat route ber-auth. Dipakai halaman penuh & isi popup. --}}
@include('partials.profil-extras-editorial', [
    'mode' => $mode,
    'fotoUrl' => $profile->foto_profil_path ? route('extras.media.foto', $profile) : null,
    'videoUrl' => $profile->video_profil_path ? route('extras.media.video', $profile) : null,
    'fotos' => $profile->fotoTambahan()->keys()->map(fn ($slot) => [
        'url' => route('extras.media.foto-tambahan', [$profile, $slot]),
        'alt' => 'Foto '.$slot,
    ])->all(),
])
