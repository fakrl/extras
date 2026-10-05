@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Proyek: {{ $application->castingProject->nama_produksi }} ·
    Fee: Rp {{ number_format($application->fee_final, 0, ',', '.') }}
</p>

@if (! $application->payment)
    <div class="card">
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 6px;">Data pembayaran belum tersedia</div>
        <p style="margin: 0; font-size: 13.5px; color: var(--text-secondary);">Data pembayaran dibuat otomatis saat Extras dinyatakan Lolos. Hubungi Admin kalau belum muncul.</p>
    </div>
@else
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

@php $totalHonor = $application->fee_final + $application->payment->addons->sum('nominal'); @endphp

@if (auth()->user()->bisaSebagaiAdmin())
    <div class="card" style="margin-bottom: 14px;">
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">Rekening Tujuan Transfer</div>
        <p style="margin: 0 0 4px;">{{ $application->extras->rekening ?: 'Extras belum mengisi nomor rekening.' }}</p>
        <p style="margin: 0; font-weight: 600;">Total dibayar: Rp {{ number_format($totalHonor, 0, ',', '.') }}</p>
    </div>
@endif

@if (auth()->user()->bisaSebagaiAdmin() && $application->payment->status === 'belum_dibayar' && ! $application->payment->menungguKontrak())
    <div class="card" style="margin-bottom: 14px;">
        <x-confirm-form action="{{ route('payments.transfer', $application) }}" enctype="multipart/form-data" message="Tandai Rp {{ number_format($totalHonor, 0, ',', '.') }} sudah ditransfer ke {{ $application->extras->user->name }}? Aksi ini cuma bisa sekali.">
            <label>Unggah Bukti Transfer <span class="wajib" aria-hidden="true">*</span></label>
            <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" required style="margin-bottom: 10px;">
            <button type="submit" class="btn btn-brand">Tandai Sudah Ditransfer (Rp {{ number_format($totalHonor, 0, ',', '.') }})</button>
        </x-confirm-form>
    </div>
@endif

@if (auth()->user()->role === 'extras' && in_array($application->payment->status, ['ditransfer', 'disengketakan', 'dikonfirmasi_diterima'], true) && $application->payment->bukti_transfer_path)
    <div class="card" style="margin-bottom: 14px;">
        <p style="margin: 0 0 4px;"><a href="{{ route('payments.bukti', $application) }}" target="_blank">Lihat Bukti Transfer</a></p>
        <p style="margin: 0; color: var(--text-secondary); font-size: var(--fs-sm);">Ditransfer pada: {{ $application->payment->ditransfer_at?->format('d M Y H:i') }}</p>
    </div>
@endif

@if ((auth()->user()->bisaSebagaiAdmin() || auth()->user()->isExtras()) && $application->payment->status !== 'dikonfirmasi_diterima')
    <form method="POST" action="{{ route('payments.addon', $application) }}" style="display: flex; gap: 8px; margin-bottom: 14px;">
        @csrf
        <input type="text" name="label" class="input-inline" placeholder="Label (misal: Reimburse transport) *" required>
        <input type="number" name="nominal" class="input-inline" placeholder="Nominal *" required>
        <button class="btn">+ Tambah Komponen</button>
    </form>
@endif

@if (auth()->user()->role === 'extras' && $application->payment->status === 'ditransfer')
    <x-confirm-form :action="route('payments.confirm', $application)" message="Konfirmasi kamu sudah menerima Rp {{ number_format($totalHonor, 0, ',', '.') }}? Proyek ini akan ditandai selesai setelah ini.">
        <button type="submit" class="btn btn-brand">Konfirmasi Sudah Terima (Rp {{ number_format($totalHonor, 0, ',', '.') }})</button>
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
    @if (auth()->user()->bisaSebagaiAdmin())
        <div class="card" style="margin-top: 12px;">
            <x-confirm-form action="{{ route('payments.selesaikan-sengketa', $application) }}" enctype="multipart/form-data" message="Selesaikan sengketa dan kembalikan pembayaran ke {{ $application->extras->user->name }}?">
                <label>Tanggapan untuk Extras <span class="wajib" aria-hidden="true">*</span></label>
                <textarea name="catatan" rows="3" maxlength="500" required style="width: 100%; margin-bottom: 8px; padding: 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-primary); resize: vertical;"></textarea>
                <label>Bukti transfer baru, bila transfer ulang atau koreksi</label>
                <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" style="margin-bottom: 10px;">
                <button type="submit" class="btn btn-brand">Selesaikan dan kembalikan ke Extras</button>
            </x-confirm-form>
        </div>
    @endif
@endif

@if ($application->payment->catatan_penyelesaian)
    <div class="card" style="margin-top: 12px;">
        <div style="font-weight: 600; margin-bottom: 4px;"><i class="ti ti-message-circle"></i> Tanggapan Admin</div>
        <div style="font-size: 13.5px;">{{ $application->payment->catatan_penyelesaian }}</div>
    </div>
@endif

@if ($application->payment->status === 'dikonfirmasi_diterima')
    <div class="alert-success">Pembayaran sudah dikonfirmasi diterima. Proyek ini selesai.</div>
@endif
@endif
@endsection
