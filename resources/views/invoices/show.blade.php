@extends('layouts.app')

@section('title', 'Invoice')

@php
    $role = auth()->user()->role === 'casting_director' ? 'cd' : 'admin';
    $sudahTtd = $role === 'cd' ? $invoice->ttd_cd_signature_path : $invoice->ttd_admin_signature_path;
@endphp

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    {{ $castingProject->nama_produksi }}
</p>

<div class="card" style="margin-bottom: 16px;">
    <ul style="margin: 0; padding-left: 18px; font-size: 13.5px;">
        <li>Admin: {{ $invoice->ttd_admin_signature_path ? 'Sudah TTD' : 'Belum' }}</li>
        <li>Casting Director: {{ $invoice->ttd_cd_signature_path ? 'Sudah TTD' : 'Belum' }}</li>
    </ul>
</div>

@if (! $sudahTtd)
    <form method="POST" action="{{ route('invoices.sign', $castingProject) }}">
        @csrf
        <x-signature-pad name="signature" />
        <button type="submit" class="btn btn-brand" style="margin-top: 10px;">Simpan Tanda Tangan</button>
    </form>
@else
    <div class="alert-success">Kamu sudah menandatangani invoice ini.</div>
@endif
@endsection
