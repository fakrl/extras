@extends('layouts.app')

@section('title', 'Kontrak Digital')

@php
    $role = auth()->user()->role === 'extras' ? 'extras' : 'admin';
    $sudahTtd = $role === 'extras'
        ? $application->contract->ttd_extras_signature_path
        : $application->contract->ttd_admin_signature_path;
@endphp

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Proyek: {{ $application->castingProject->nama_produksi }} · Fee: Rp {{ number_format($application->fee_final, 0, ',', '.') }}
</p>

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status tanda tangan</div>
    <ul style="margin: 0; padding-left: 18px; font-size: 13.5px;">
        <li>Admin: {{ $application->contract->ttd_admin_signature_path ? 'Sudah TTD' : 'Belum' }}</li>
        <li>Extras: {{ $application->contract->ttd_extras_signature_path ? 'Sudah TTD' : 'Belum' }}</li>
    </ul>
</div>

@if (! $sudahTtd)
    <form method="POST" action="{{ route('contracts.sign', $application) }}" id="sign-form">
        @csrf
        <x-signature-pad name="signature" />
        <button type="submit" class="btn btn-brand" style="margin-top: 10px;">Simpan Tanda Tangan</button>
    </form>
@else
    <div class="alert-success">Kamu sudah menandatangani kontrak ini.</div>
@endif

@if ($application->contract->isFullySigned())
    <div class="alert-info" style="margin-top: 12px;">Kontrak sudah ditandatangani lengkap kedua pihak. Lanjut ke proses pembayaran.</div>
@endif
@endsection
