@extends('layouts.app')

@section('title', 'Kontrak Digital')

@php
    $role = auth()->user()->role === 'extras' ? 'extras' : 'admin';
    $sudahTtd = $role === 'extras'
        ? $application->contract?->ttd_extras_signature_path
        : $application->contract?->ttd_admin_signature_path;
    [$alasanBelum, $linkLengkapi] = match (true) {
        ! in_array($application->status_partisipasi, \App\Models\ProjectApplication::STATUS_LOLOS_KE_ATAS, true) => ['Kontrak dibuat otomatis setelah Extras dinyatakan Lolos oleh Client.', null],
        ! $application->extras->nama_asli => ['Kontrak dibuat otomatis setelah Extras melengkapi Nama Asli (sesuai KTP) di profil.', route('extras.profile.edit')],
        ! $application->extras->nik => ['Kontrak dibuat otomatis setelah Extras melengkapi NIK.', $application->status_partisipasi === 'lolos' ? route('extras.kontrak.lengkapi-ktp', $application) : null],
        default => ['Kontrak sedang disiapkan. Hubungi Admin kalau belum muncul.', null],
    };
@endphp

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    @if ($role === 'admin')
        Extras: <strong>{{ $application->extras->user->name }}</strong> ·
    @endif
    Proyek: {{ $application->castingProject->nama_produksi }} · Fee: Rp {{ number_format($application->fee_final, 0, ',', '.') }}
    @if ($application->castingProject->link_grup && in_array($application->status_partisipasi, ['kontrak_ditandatangani', 'selesai_produksi'], true))
        · <a href="{{ $application->castingProject->link_grup }}" target="_blank" style="font-weight: 500;">Link Grup Koordinasi</a>
    @endif
</p>

@if (! $application->contract)
    <div class="card">
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 6px;">Kontrak belum tersedia</div>
        <p style="margin: 0; font-size: 13.5px; color: var(--text-secondary);">{{ $alasanBelum }}</p>
        @if ($role === 'extras' && $linkLengkapi)
            <a href="{{ $linkLengkapi }}" class="btn btn-brand" style="margin-top: 12px;">Lengkapi Sekarang</a>
        @endif
    </div>
@else
@if ($application->contract->isVoided())
    <div class="alert-danger" style="font-weight: bold; margin-bottom: 16px;">TIDAK BERLAKU - Pendaftaran Dibatalkan pada {{ $application->contract->voided_at->translatedFormat('d M Y H:i') }}</div>
@endif

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status tanda tangan</div>
    <ul style="margin: 0; padding-left: 18px; font-size: 13.5px;">
        <li>Admin: {{ $application->contract->ttd_admin_signature_path ? 'Sudah TTD' : 'Belum' }}</li>
        <li>Extras: {{ $application->contract->ttd_extras_signature_path ? 'Sudah TTD' : 'Belum' }}</li>
    </ul>
</div>

@if (! $application->contract->isVoided())
    <div style="margin-bottom: 12px;">
        <a href="{{ route('contracts.download-pdf', $application) }}" class="btn">Lihat Kontrak (PDF)</a>
    </div>
    @if (! $sudahTtd)
        <div class="card" style="margin-bottom: 12px;">
            <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">Ringkasan isi kontrak</div>
            <ul style="margin: 0; padding-left: 18px; font-size: 13.5px;">
                <li>Proyek: {{ $application->castingProject->nama_produksi }}</li>
                <li>Peran: {{ $application->karakter ?: ($application->castingProjectClass?->nama_kelas ?? '-') }}</li>
                <li>Honor yang disepakati: Rp {{ number_format($application->fee_final, 0, ',', '.') }}</li>
                <li>Tanggal syuting: {{ $application->castingProject->shootingDates->sortBy('tanggal')->map(fn ($d) => $d->tanggal->translatedFormat('d M Y'))->implode(', ') ?: '-' }}</li>
            </ul>
        </div>
        <x-confirm-form action="{{ route('contracts.sign', $application) }}" message="Simpan tanda tangan ini? Kontrak akan mengikat begitu kedua pihak sudah TTD dan tidak bisa diubah lagi.">
            <label style="display: flex; gap: 8px; align-items: center; margin-bottom: 10px; font-weight: 500;">
                <input type="checkbox" style="width: auto; min-height: auto; margin: 0;" onchange="this.closest('form').querySelector('[type=submit]').disabled = !this.checked">
                Saya sudah membaca kontrak (PDF) di atas
            </label>
            <x-signature-pad name="signature" />
            <button type="submit" class="btn btn-brand" style="margin-top: 10px;" disabled>Simpan Tanda Tangan</button>
        </x-confirm-form>
    @else
        <div class="alert-success">{{ $role === 'extras' ? 'Kamu' : 'Anda' }} sudah menandatangani kontrak ini.</div>
        @unless ($application->contract->isFullySigned())
            <div class="alert-info" style="margin-top: 12px;">Berikutnya: menunggu tanda tangan {{ $role === 'extras' ? 'Admin' : 'Extras' }}.</div>
        @endunless
    @endif

    @if ($application->contract->isFullySigned())
        <div class="alert-info" style="margin-top: 12px;">Kontrak sudah ditandatangani lengkap kedua pihak.</div>
        <a href="{{ route('payments.show', $application) }}" class="btn btn-brand" style="margin-top: 10px;">Lihat Pembayaran</a>
    @endif
@endif
@endif
@endsection
