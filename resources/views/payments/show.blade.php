@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Proyek: {{ $application->castingProject->nama_produksi }} ·
    Fee: Rp {{ number_format($application->fee_final, 0, ',', '.') }}
</p>

<div class="card" style="margin-bottom: 16px;">
    <p style="margin: 0 0 8px;">Status: <x-status-badge :model="$application->payment" /></p>

    @if ($application->payment->addons->isNotEmpty())
        <p style="margin: 0 0 4px; font-size: 12.5px; color: var(--text-muted);">Komponen tambahan:</p>
        <ul style="margin: 0; padding-left: 18px; font-size: 13.5px;">
            @foreach ($application->payment->addons as $addon)
                <li>{{ $addon->label }}: Rp {{ number_format($addon->nominal, 0, ',', '.') }}</li>
            @endforeach
        </ul>
    @endif
</div>

@if (auth()->user()->isAdmin() && $application->payment->status === 'belum_dibayar')
    <div class="card" style="margin-bottom: 14px;">
        <form method="POST" action="{{ route('payments.transfer', $application) }}" enctype="multipart/form-data">
            @csrf
            <label>Unggah Bukti Transfer</label>
            <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" required style="margin-bottom: 10px;">
            <button type="submit" class="btn btn-brand">Tandai Sudah Ditransfer</button>
        </form>
    </div>
@endif

@if ((auth()->user()->isAdmin() || auth()->user()->isExtras()) && $application->payment->status !== 'dikonfirmasi_diterima')
    <form method="POST" action="{{ route('payments.addon', $application) }}" style="display: flex; gap: 8px; margin-bottom: 14px;">
        @csrf
        <input type="text" name="label" class="input-inline" placeholder="Label (misal: Reimburse transport)" required>
        <input type="number" name="nominal" class="input-inline" placeholder="Nominal" required>
        <button class="btn">+ Tambah Komponen</button>
    </form>
@endif

@if (auth()->user()->role === 'extras' && $application->payment->status === 'ditransfer')
    <x-confirm-form :action="route('payments.confirm', $application)" message="Konfirmasi kamu sudah menerima pembayaran ini?">
        <button type="submit" class="btn btn-brand">Konfirmasi Sudah Terima</button>
    </x-confirm-form>
    <div style="margin-top: 12px;">
        <button onclick="var f=document.getElementById('form-sengketa');f.style.display=f.style.display==='none'?'block':'none'" class="btn btn-sm" style="background: var(--warning, #eab308); color: #000;">
            Laporkan Masalah
        </button>
        <div id="form-sengketa" style="display:none; margin-top: 10px;">
            <form method="POST" action="{{ route('payments.sengketa', $application) }}">
                @csrf
                <textarea name="alasan" rows="3" placeholder="Jelaskan masalahnya (maks 500 karakter)" maxlength="500" required style="width: 100%; margin-bottom: 8px; padding: 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-primary); resize: vertical;"></textarea>
                <button type="submit" class="btn btn-sm" style="background: var(--danger, #ef4444); color: #fff;">Kirim Laporan</button>
            </form>
        </div>
    </div>
@endif

@if ($application->payment->status === 'disengketakan')
    <div style="background: rgba(234, 179, 8, 0.1); border: 1px solid var(--warning, #eab308); border-radius: 8px; padding: 12px 16px; margin-top: 12px;">
        <div style="font-weight: 600; color: var(--warning, #eab308); margin-bottom: 4px;"><i class="ti ti-alert-circle"></i> Pembayaran Sedang Disengketakan</div>
        <div style="font-size: 13px; color: var(--text-secondary);">{{ $application->payment->alasan_sengketa }}</div>
    </div>
@endif

@if ($application->payment->status === 'dikonfirmasi_diterima')
    <div class="alert-success">Pembayaran sudah dikonfirmasi diterima. Proyek ini selesai.</div>
@endif
@endsection
