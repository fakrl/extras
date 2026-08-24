@extends('layouts.app')

@section('title', 'Negosiasi Fee')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Proyek: {{ $application->castingProject->nama_produksi }}
</p>

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Riwayat Tawar-Menawar</div>
    <table>
        <thead><tr><th>Ronde</th><th>Diajukan Oleh</th><th>Nominal</th><th>Aksi</th></tr></thead>
        <tbody>
            @foreach ($application->feeNegotiations as $nego)
                <tr>
                    <td>{{ $nego->round }}</td>
                    <td>{{ $nego->diajukan_oleh === 'admin' ? 'Admin' : 'Kamu' }}</td>
                    <td>Rp {{ number_format($nego->nominal, 0, ',', '.') }}</td>
                    <td style="text-transform: capitalize;">{{ $nego->aksi }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if ($application->status_partisipasi === 'deal')
    <div class="alert-success">Fee sudah Deal di Rp {{ number_format($application->fee_final, 0, ',', '.') }}. Tunggu kabar selanjutnya dari Admin.</div>
@elseif ($application->status_partisipasi === 'ditolak')
    <div class="alert-info">Negosiasi untuk pendaftaran ini sudah dihentikan.</div>
@elseif ($application->feeNegotiations->isNotEmpty())
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <form method="POST" action="{{ route('extras.negotiations.terima', $application) }}">
            @csrf
            <button class="btn btn-brand">Terima Penawaran Terakhir</button>
        </form>
        <form method="POST" action="{{ route('extras.negotiations.counter', $application) }}" style="display: flex; gap: 8px;">
            @csrf
            <input type="number" name="nominal" class="input-inline" placeholder="Nominal counter" required>
            <button class="btn">Ajukan Counter</button>
        </form>
    </div>
@else
    <p style="color: var(--text-muted);">Menunggu penawaran fee dari Admin.</p>
@endif
@endsection
