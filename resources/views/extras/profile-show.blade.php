@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="card" style="max-width: 560px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
        <div style="font-size: 17px; font-weight: 600;">Profil Saya</div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button type="button" id="btn-share" class="btn btn-sm btn-outline" style="font-size: 12px;">
                <i class="ti ti-share"></i> Share
            </button>
            <a href="{{ route('extras.profile.edit') }}" class="btn btn-sm btn-brand">Edit Profil</a>
        </div>
    </div>
    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    {{-- Header foto dominan --}}
    <div class="profile-section" style="text-align: center; margin-bottom: 20px;">
        <div style="width: 180px; aspect-ratio: 3/4; margin: 0 auto 10px; border-radius: 14px; overflow: hidden; background: var(--bg-nav-active); display: flex; align-items: center; justify-content: center;">
            @if ($profile->foto_profil_path)
                <img src="{{ route('extras.media.foto', $profile) }}" alt="Foto profil"
                     style="width: 100%; height: 100%; object-fit: cover; display: block;">
            @else
                <i class="ti ti-photo-off" style="font-size: 36px; color: var(--text-muted);"></i>
            @endif
        </div>
        <div style="font-size: 18px; font-weight: 700;">{{ $profile->user->username ?? '— belum diisi —' }}</div>
    </div>

    {{-- Video --}}
    <div class="profile-section">
        <div class="profile-section-title">Video Profil</div>
        @if ($profile->video_profil_path)
            <video src="{{ route('extras.media.video', $profile) }}" controls
                   style="width: 100%; border-radius: 12px; background: #000; aspect-ratio: 16/9;"></video>
        @else
            <div style="width: 100%; aspect-ratio: 16/9; border-radius: 12px; background: var(--bg-nav-active); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                <i class="ti ti-video-off" style="font-size: 28px;"></i>
            </div>
            <p class="field-hint" style="margin-top: 6px;">Belum ada video</p>
        @endif
    </div>

    {{-- Foto tambahan via lightbox --}}
    <div class="profile-section">
        <div class="profile-section-title">Foto Tambahan</div>
        @php
            $fotosArr = collect($fotoTambahan)->filter()->map(fn($foto, $slot) => [
                'url' => route('extras.media.foto-tambahan', [$profile, $slot]),
                'alt' => 'Foto ' . $slot,
            ])->values()->all();
        @endphp
        @include('partials.foto-lightbox', ['fotos' => $fotosArr, 'lightboxId' => 'profil-lb'])
    </div>

    {{-- Data Diri & Ciri Fisik — grid 2-kolom --}}
    <div class="profile-section">
        <div class="profile-section-title">Data Diri &amp; Ciri Fisik</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; font-size: 13px;">
            <div style="color: var(--text-secondary);">Usia</div>
            <div>{{ $profile->usia ? $profile->usia . ' tahun' : '—' }}</div>

            <div style="color: var(--text-secondary);">Jenis Kelamin</div>
            <div>{{ $profile->gender === 'pria' ? 'Laki-laki' : ($profile->gender === 'wanita' ? 'Perempuan' : '—') }}</div>

            <div style="color: var(--text-secondary);">Tinggi Badan</div>
            <div>{{ $profile->tinggi_badan ? $profile->tinggi_badan . ' cm' : '—' }}</div>

            <div style="color: var(--text-secondary);">Ukuran Baju</div>
            <div>{{ $profile->ukuran_baju ?: '—' }}</div>

            <div style="color: var(--text-secondary);">Warna Kulit</div>
            <div>{{ $profile->warna_kulit ?: '—' }}</div>
        </div>
    </div>

    <div class="profile-section">
        <div class="profile-section-title">Pengalaman &amp; Kemampuan</div>
        <div class="profile-view-row" style="flex-direction: column; align-items: flex-start; gap: 4px;">
            <span class="profile-view-label">Pengalaman Main / Kerja</span>
            <span class="profile-view-value" style="text-align: left;">{{ $profile->pengalaman ?: '— belum diisi —' }}</span>
        </div>
        <div class="profile-view-row">
            <span class="profile-view-label">Bahasa</span>
            <span class="profile-view-value">{{ $profile->bahasa ?: '—' }}</span>
        </div>
    </div>

    <div class="profile-section">
        <div class="profile-section-title">Tautan Tambahan</div>
        @forelse ($profile->tautan_tambahan ?? [] as $tautan)
            <div class="profile-view-row">
                <span class="profile-view-label">{{ $tautan['label'] }}</span>
                <span class="profile-view-value"><a href="{{ $tautan['url'] }}" target="_blank">{{ $tautan['url'] }}</a></span>
            </div>
        @empty
            <span class="profile-view-value" style="color: var(--text-muted); font-weight: 400;">—</span>
        @endforelse
    </div>

    <div class="profile-section">
        <div class="profile-section-title">Tarif</div>
        <div class="profile-view-row">
            <span class="profile-view-label">Tarif Harapan</span>
            <span class="profile-view-value">{{ $profile->rate_card ? 'Rp ' . number_format($profile->rate_card, 0, ',', '.') : '—' }}</span>
        </div>
    </div>

    <a href="{{ route('extras.profile.edit') }}" class="btn btn-brand" style="width: 100%; margin-top: 8px; display: flex;">Edit Profil</a>
</div>

<dialog id="modal-share" style="border:1px solid var(--border-color); border-radius:16px; padding:0; max-width:360px; width:95%;">
    <div style="padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <span style="font-size:15px; font-weight:600;">Bagikan Profil Kamu</span>
            <button type="button" onclick="document.getElementById('modal-share').close()"
                style="background:none; border:none; cursor:pointer; font-size:20px; color:var(--text-muted); line-height:1;">×</button>
        </div>

        <div style="display:flex; gap:8px; margin-bottom:16px;">
            <input id="share-url-modal" readonly type="text" value="{{ route('public.extras.profile', $profile->user->username) }}"
                style="flex:1; font-size:12px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card-hover); color:var(--text-primary); min-width:0;">
            <button type="button" id="btn-copy-link" title="Salin link"
                style="flex-shrink:0; padding:8px 10px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); cursor:pointer; color:var(--text-primary);">
                <i class="ti ti-copy" style="font-size:16px;"></i>
            </button>
        </div>
        <p id="share-copied-modal" style="display:none; color:var(--accent); font-size:12px; margin:-10px 0 12px;">Link disalin!</p>

        <a href="https://wa.me/?text={{ urlencode(route('public.extras.profile', $profile->user->username)) }}" target="_blank" rel="noopener"
            style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:10px; border-radius:10px; background:#25d366; color:#fff; text-decoration:none; font-size:14px; font-weight:600; box-sizing:border-box;">
            <i class="ti ti-brand-whatsapp" style="font-size:18px;"></i> WhatsApp
        </a>
    </div>
</dialog>

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('modal-share');
    var copied = document.getElementById('share-copied-modal');

    modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });

    document.getElementById('btn-share').addEventListener('click', function () {
        copied.style.display = 'none';
        modal.showModal();
    });

    document.getElementById('btn-copy-link').addEventListener('click', function () {
        var url = document.getElementById('share-url-modal').value;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                copied.style.display = 'block';
                setTimeout(function () { copied.style.display = 'none'; }, 2000);
            });
        } else {
            document.getElementById('share-url-modal').select();
            document.execCommand('copy');
            copied.style.display = 'block';
            setTimeout(function () { copied.style.display = 'none'; }, 2000);
        }
    });
})();
</script>
@endpush
@endsection
