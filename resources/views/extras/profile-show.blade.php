@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="card" style="max-width: 560px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
        <div style="font-size: 17px; font-weight: 600;">Profil Saya</div>
        <a href="{{ route('extras.profile.edit') }}" class="btn btn-sm btn-brand">Edit Profil</a>
    </div>
    <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 20px; line-height: 1.5;">
        Ini persis tampilan profil kamu yang dilihat Admin & Casting Director saat cross-check kandidat.
    </p>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    {{-- ===== Foto & Video — bagian paling penting, ditaruh atas ===== --}}
    <div class="profile-section">
        <div class="profile-section-title">Foto & Video</div>
        <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-start;">
            <div style="width: 130px; flex-shrink: 0;">
                @if ($profile->foto_profil_path)
                    <img src="{{ route('extras.media.foto', $profile) }}" alt="Foto profil"
                         style="width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 12px; display: block;">
                @else
                    <div style="width: 100%; aspect-ratio: 3/4; border-radius: 12px; background: var(--bg-nav-active); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                        <i class="ti ti-photo-off" style="font-size: 28px;"></i>
                    </div>
                    <p class="field-hint" style="margin-top: 6px;">Belum ada foto</p>
                @endif
            </div>
            <div style="flex: 1; min-width: 160px;">
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
        </div>

        <p class="field-hint" style="margin: 14px 0 8px;">Foto tambahan:</p>
        <div class="photo-slot-grid" style="max-width: 100%; grid-template-columns: repeat(4, 1fr);">
            @foreach ($fotoTambahan as $slot => $foto)
                <div>
                    @if ($foto)
                        <img src="{{ route('extras.media.foto-tambahan', [$profile, $slot]) }}" alt="Foto tambahan {{ $slot }}"
                             style="width: 100%; aspect-ratio: 1/1; object-fit: cover; border-radius: 10px; display: block;">
                    @else
                        <div style="width: 100%; aspect-ratio: 1/1; border-radius: 10px; background: var(--bg-nav-active); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 11px;">
                            Kosong
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== Nama & data dasar ===== --}}
    <div class="profile-section">
        <div class="profile-section-title">Nama Panggilan</div>
        <div class="profile-view-row">
            <span class="profile-view-label">Nama Panggung / Alias</span>
            <span class="profile-view-value">{{ $profile->alias ?: '— belum diisi —' }}</span>
        </div>
        <p class="field-hint">Nama ini yang dilihat Casting Director — bukan nama asli kamu di KTP.</p>
    </div>

    <div class="profile-section">
        <div class="profile-section-title">Data Diri</div>
        <div class="profile-view-row">
            <span class="profile-view-label">Usia</span>
            <span class="profile-view-value">{{ $profile->usia ? $profile->usia . ' tahun' : '—' }}</span>
        </div>
        <div class="profile-view-row">
            <span class="profile-view-label">Jenis Kelamin</span>
            <span class="profile-view-value">{{ $profile->gender === 'pria' ? 'Laki-laki' : ($profile->gender === 'wanita' ? 'Perempuan' : '—') }}</span>
        </div>
        <div class="profile-view-row">
            <span class="profile-view-label">Tinggi Badan</span>
            <span class="profile-view-value">{{ $profile->tinggi_badan ? $profile->tinggi_badan . ' cm' : '—' }}</span>
        </div>
    </div>

    <div class="profile-section">
        <div class="profile-section-title">Ciri-ciri Fisik</div>
        <div class="profile-view-row">
            <span class="profile-view-label">Ukuran Baju</span>
            <span class="profile-view-value">{{ $profile->ukuran_baju ?: '—' }}</span>
        </div>
        <div class="profile-view-row">
            <span class="profile-view-label">Warna Kulit</span>
            <span class="profile-view-value">{{ $profile->warna_kulit ?: '—' }}</span>
        </div>
    </div>

    <div class="profile-section">
        <div class="profile-section-title">Pengalaman & Kemampuan</div>
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
@endsection
