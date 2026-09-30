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
    <div class="alert-danger" style="font-weight: bold; margin-bottom: 16px;">TIDAK BERLAKU - Pendaftaran Dibatalkan pada {{ $application->contract->voided_at->format('d M Y H:i') }}</div>
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
        <x-confirm-form action="{{ route('contracts.sign', $application) }}" message="Simpan tanda tangan ini? Kontrak akan mengikat begitu kedua pihak sudah TTD dan tidak bisa diubah lagi.">
            <x-signature-pad name="signature" />
            <button type="submit" class="btn btn-brand" style="margin-top: 10px;">Simpan Tanda Tangan</button>
        </x-confirm-form>
    @else
        <div class="alert-success">Kamu sudah menandatangani kontrak ini.</div>
    @endif

    @if ($application->contract->isFullySigned())
        <div class="alert-info" style="margin-top: 12px;">Kontrak sudah ditandatangani lengkap kedua pihak. Lanjut ke proses pembayaran.</div>
    @endif
@endif
@endif
@endsection
