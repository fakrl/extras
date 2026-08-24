@extends('layouts.app')

@section('title', 'Pembayaran')

@php
    $statusBadge = [
        'belum_dibayar' => 'badge-tolak',
        'ditransfer' => 'badge-pending',
        'dikonfirmasi_diterima' => 'badge-aktif',
    ];
@endphp

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Proyek: {{ $application->castingProject->nama_produksi }} ·
    Fee: Rp {{ number_format($application->fee_final, 0, ',', '.') }}
</p>

<div class="card" style="margin-bottom: 16px;">
    <p style="margin: 0 0 8px;">Status: <span class="badge {{ $statusBadge[$application->payment->status] ?? 'badge-pending' }}">{{ $application->payment->status }}</span></p>

    @if ($application->payment->addons->isNotEmpty())
        <p style="margin: 0 0 4px; font-size: 12.5px; color: var(--text-muted);">Komponen tambahan:</p>
        <ul style="margin: 0; padding-left: 18px; font-size: 13.5px;">
            @foreach ($application->payment->addons as $addon)
                <li>{{ $addon->label }}: Rp {{ number_format($addon->nominal, 0, ',', '.') }}</li>
            @endforeach
        </ul>
    @endif
</div>

@if (auth()->user()->role === 'admin_default')
    @if ($application->payment->status === 'belum_dibayar')
        <div class="card" style="margin-bottom: 14px;">
            <form method="POST" action="{{ route('payments.transfer', $application) }}" enctype="multipart/form-data">
                @csrf
                <label>Unggah Bukti Transfer</label>
                <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" required style="margin-bottom: 10px;">
                <button type="submit" class="btn btn-brand">Tandai Sudah Ditransfer</button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ route('payments.addon', $application) }}" style="display: flex; gap: 8px; margin-bottom: 14px;">
        @csrf
        <input type="text" name="label" class="input-inline" placeholder="Label (misal: Reimburse transport)" required>
        <input type="number" name="nominal" class="input-inline" placeholder="Nominal" required>
        <button class="btn">+ Tambah Komponen</button>
    </form>
@endif

@if (auth()->user()->role === 'extras' && $application->payment->status === 'ditransfer')
    <form method="POST" action="{{ route('payments.confirm', $application) }}">
        @csrf
        <button class="btn btn-brand">Konfirmasi Sudah Terima</button>
    </form>
@endif

@if ($application->payment->status === 'dikonfirmasi_diterima')
    <div class="alert-success">Pembayaran sudah dikonfirmasi diterima. Proyek ini selesai.</div>
@endif
@endsection
