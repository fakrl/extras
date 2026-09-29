{{-- BI.1: profil editorial di dalam aplikasi (pemilik|admin|client), media lewat route ber-auth. Dipakai halaman penuh & isi popup. --}}
@include('partials.profil-extras-editorial', [
    'mode' => $mode,
    'fotoUrl' => $profile->foto_profil_path ? route('extras.media.foto', $profile) : null,
    'videoUrl' => $profile->video_profil_path ? route('extras.media.video', $profile) : null,
    'fotos' => $profile->photos->whereBetween('urutan', [1, 4])->sortBy('urutan')->map(fn ($foto) => [
        'url' => route('extras.media.foto-tambahan', [$profile, $foto->urutan]),
        'alt' => 'Foto '.$foto->urutan,
    ])->values()->all(),
])
