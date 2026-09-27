@extends('layouts.app')

@section('title', 'Negosiasi Fee')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Proyek: {{ $application->castingProject->nama_produksi }} ·
    Rate card awal: Rp {{ number_format($application->extras->rate_card ?? 0, 0, ',', '.') }} ·
    Status: <span class="badge badge-pending">{{ $application->status_partisipasi }}</span>
</p>

<div style="margin-bottom: 80px;">
    <div style="display: flex; flex-direction: column; gap: 10px; padding: 4px 0;">
        @forelse ($application->feeNegotiations as $nego)
            @php $isAdmin = $nego->diajukan_oleh === 'admin'; @endphp
            <div style="display: flex; justify-content: {{ $isAdmin ? 'flex-end' : 'flex-start' }};">
                <div style="max-width: 70%; background: {{ $isAdmin ? 'color-mix(in srgb, var(--accent-strong) 12%, var(--bg-card))' : 'var(--bg-secondary)' }}; border-radius: 12px; padding: 10px 14px; border: 1px solid var(--border-color);">
                    <div style="font-size: 15px; font-weight: 600; color: var(--accent-strong);">Rp {{ number_format($nego->nominal, 0, ',', '.') }}</div>
                    <div style="margin: 4px 0;">
                        <span class="badge {{ $nego->aksi === 'terima' ? 'badge-aktif' : ($nego->aksi === 'tolak' ? 'badge-tolak' : 'badge-pending') }}" style="text-transform: capitalize;">{{ $nego->aksi }}</span>
                    </div>
                    @if ($nego->catatan)
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">{{ $nego->catatan }}</div>
                    @endif
                    <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-top: 6px; text-align: {{ $isAdmin ? 'right' : 'left' }};">
                        {{ ucfirst($nego->diajukan_oleh) }} · {{ $nego->created_at->format('d M Y H:i') }}
                    </div>
                </div>
            </div>
        @empty
            <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">Belum ada penawaran.</p>
        @endforelse
    </div>
</div>

<div style="position: sticky; bottom: 0; background: var(--bg-card); padding: 12px; border-top: 1px solid var(--border-color); z-index: 10;">
    @if ($application->status_partisipasi === 'deal')
        <div class="alert-success" style="margin-bottom: 8px;">Fee sudah Deal di Rp {{ number_format($application->fee_final, 0, ',', '.') }}.</div>
        <form method="POST" action="{{ route('admin.negotiations.ajukan-ke-cd', $application) }}">
            @csrf
            <button class="btn btn-brand">Ajukan ke Client</button>
        </form>
    @elseif ($application->status_partisipasi === 'ditolak')
        <div class="alert-info">Negosiasi untuk pendaftar ini sudah dihentikan.</div>
    @elseif ($application->feeNegotiations->isEmpty())
        <form method="POST" action="{{ route('admin.negotiations.ajukan', $application) }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
            @csrf
            <input type="number" name="nominal" class="input-inline" placeholder="Nominal penawaran awal" required value="{{ $application->extras->rate_card }}">
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
</div>
@endsection
