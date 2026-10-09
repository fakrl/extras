{{-- BI.1: profil editorial di dalam aplikasi (pemilik|admin|client), media lewat route ber-auth. Dipakai halaman penuh & isi popup. --}}
@if ($mode === 'admin' && auth()->user()?->bisaSebagaiAdmin())
    <form method="POST" action="{{ route('admin.extras.favorit', $profile->user_id) }}" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; padding: 12px 16px; border-bottom: 1px solid var(--border-color);">
        @csrf @method('PATCH')
        @if ($profile->apresiasi)
            <button type="submit" class="btn btn-sm" aria-pressed="true" title="Hapus dari Favorit">⭐ Favorit</button>
            @if ($profile->apresiasi_catatan)
                <span style="font-size: var(--fs-sm); color: var(--text-secondary);">Kenapa favorit? {{ $profile->apresiasi_catatan }}</span>
            @endif
        @else
            <span style="flex: 1 1 220px;">
                <label for="fav-catatan-{{ $profile->id }}" style="font-size: var(--fs-xs);">Kenapa favorit?</label>
                <input type="text" id="fav-catatan-{{ $profile->id }}" name="apresiasi_catatan" maxlength="1000" style="margin: 0;">
            </span>
            <button type="submit" class="btn btn-sm" aria-pressed="false"><i class="ti ti-star" aria-hidden="true"></i> Jadikan Favorit</button>
        @endif
    </form>
    <div style="display: flex; flex-wrap: wrap; gap: 8px; padding: 10px 16px; border-bottom: 1px solid var(--border-color);">
        @include('partials.tombol-wa', ['user' => $profile->user, 'pesanWa' => null])
        @include('partials.tombol-undang', ['user' => $profile->user])
    </div>
@endif
@include('partials.profil-extras-editorial', [
    'mode' => $mode,
    'fotoUrl' => $profile->foto_profil_path ? route('extras.media.foto', $profile) : null,
    'videoUrl' => $profile->video_profil_path ? route('extras.media.video', $profile) : null,
    'fotos' => $profile->fotoTambahan()->keys()->map(fn ($slot) => [
        'url' => route('extras.media.foto-tambahan', [$profile, $slot]),
        'alt' => 'Foto '.$slot,
    ])->all(),
])
