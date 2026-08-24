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
        <thead><tr><th>Ronde</th><th>Diajukan Oleh</th><th>Nominal</th><th>Aksi</th><th>Waktu</th></tr></thead>
        <tbody>
            @forelse ($application->feeNegotiations as $nego)
                <tr>
                    <td>{{ $nego->round }}</td>
                    <td style="text-transform: capitalize;">{{ $nego->diajukan_oleh }}</td>
                    <td>Rp {{ number_format($nego->nominal, 0, ',', '.') }}</td>
                    <td style="text-transform: capitalize;">{{ $nego->aksi }}</td>
                    <td>{{ $nego->created_at->format('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color: var(--text-muted); padding: 20px 0;">Belum ada penawaran.</td></tr>
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
    <form method="POST" action="{{ route('admin.negotiations.ajukan', $application) }}" style="display: flex; gap: 8px;">
        @csrf
        <input type="number" name="nominal" class="input-inline" placeholder="Nominal penawaran awal" required
               value="{{ $application->extras->rate_card }}">
        <button class="btn btn-brand">Ajukan Fee Awal</button>
    </form>
@else
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <form method="POST" action="{{ route('admin.negotiations.terima', $application) }}" style="display: flex; gap: 8px;">
            @csrf
            <input type="number" name="nominal" class="input-inline" placeholder="Nominal" required
                   value="{{ $application->feeNegotiations->last()->nominal }}">
            <button class="btn btn-brand">Terima</button>
        </form>
        <form method="POST" action="{{ route('admin.negotiations.counter', $application) }}" style="display: flex; gap: 8px;">
            @csrf
            <input type="number" name="nominal" class="input-inline" placeholder="Nominal counter" required>
            <button class="btn">Counter</button>
        </form>
        <form method="POST" action="{{ route('admin.negotiations.tolak', $application) }}">
            @csrf
            <button class="btn btn-danger-outline">Hentikan Negosiasi</button>
        </form>
    </div>
@endif
@endsection
