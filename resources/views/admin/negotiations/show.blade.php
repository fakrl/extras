@extends('layouts.app')

@section('title', 'Negosiasi Fee')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Proyek: {{ $application->castingProject->nama_produksi }} ·
    Rate card awal: Rp {{ number_format($application->extras->rate_card ?? 0, 0, ',', '.') }} ·
    Status: <span class="badge badge-pending">{{ $application->status_partisipasi }}</span>
</p>

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Riwayat Tawar-Menawar</div>
    <table>
        <thead><tr><th>Ronde</th><th>Diajukan Oleh</th><th>Nominal</th><th>Aksi</th><th>Catatan / Alasan</th><th>Waktu</th></tr></thead>
        <tbody>
            @forelse ($application->feeNegotiations as $nego)
                <tr>
                    <td>{{ $nego->round }}</td>
                    <td style="text-transform: capitalize; font-weight: 500;">{{ $nego->diajukan_oleh }}</td>
                    <td style="font-weight: 600; color: var(--accent-strong);">Rp {{ number_format($nego->nominal, 0, ',', '.') }}</td>
                    <td style="text-transform: capitalize;"><span class="badge {{ $nego->aksi === 'terima' ? 'badge-aktif' : ($nego->aksi === 'tolak' ? 'badge-tolak' : 'badge-pending') }}">{{ $nego->aksi }}</span></td>
                    <td style="color: var(--text-secondary); font-size: 12.5px;">{{ $nego->catatan ?: '-' }}</td>
                    <td>{{ $nego->created_at->format('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 20px 0;">Belum ada penawaran.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($application->status_partisipasi === 'deal')
    <div class="alert-success">
        Fee sudah Deal di Rp {{ number_format($application->fee_final, 0, ',', '.') }}.
    </div>
    <form method="POST" action="{{ route('admin.negotiations.ajukan-ke-cd', $application) }}">
        @csrf
        <button class="btn btn-brand">Ajukan ke Casting Director</button>
    </form>
@elseif ($application->status_partisipasi === 'ditolak')
    <div class="alert-info">Negosiasi untuk pendaftar ini sudah dihentikan.</div>
@elseif ($application->feeNegotiations->isEmpty())
    <form method="POST" action="{{ route('admin.negotiations.ajukan', $application) }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
        @csrf
        <input type="number" name="nominal" class="input-inline" placeholder="Nominal penawaran awal" required
               value="{{ $application->extras->rate_card }}">
        <input type="text" name="catatan" class="input-inline" placeholder="Catatan/alasan (opsional)" style="min-width: 200px;">
        <button class="btn btn-brand">Ajukan Fee Awal</button>
    </form>
@else
    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
        <form method="POST" action="{{ route('admin.negotiations.terima', $application) }}" style="display: flex; gap: 8px;">
            @csrf
            <input type="hidden" name="nominal" value="{{ $application->feeNegotiations->last()->nominal }}">
            <button class="btn btn-brand">Terima (Rp {{ number_format($application->feeNegotiations->last()->nominal, 0, ',', '.') }})</button>
        </form>
        <form method="POST" action="{{ route('admin.negotiations.counter', $application) }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
            @csrf
            <input type="number" name="nominal" class="input-inline" placeholder="Nominal counter" required style="width: 140px;">
            <input type="text" name="catatan" class="input-inline" placeholder="Catatan/alasan counter (opsional)" style="min-width: 180px;">
            <button class="btn">Counter</button>
        </form>
        <form method="POST" action="{{ route('admin.negotiations.tolak', $application) }}">
            @csrf
            <button class="btn btn-danger-outline">Hentikan Negosiasi</button>
        </form>
    </div>
@endif
@endsection
