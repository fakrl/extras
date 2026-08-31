@extends('layouts.app')

@section('title', 'Rekap Extras')

@section('content')
<div class="card-header-row">
    <div style="font-size: 16px; font-weight: 600;">Rekap Extras</div>
    <a href="{{ route('admin.recap.export') }}" class="btn">Ekspor ke Excel</a>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    @foreach ($rekapStatus as $status => $total)
        <div class="metric-card" style="text-align: center;">
            <div class="metric-value">{{ $total }}</div>
            <div class="metric-label" style="text-transform: capitalize;">{{ $status }}</div>
        </div>
    @endforeach
</div>

<div class="card">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Extras Paling Sering Terpilih</div>
    <table>
        <thead><tr><th>Alias</th><th>Jumlah Terpilih</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($extrasPalingSering as $ex)
                <tr>
                    <td>{{ $ex->alias_tampil ?? '-' }}</td>
                    <td>{{ $ex->applications_count }}</td>
                    <td>
                        <span class="badge {{ $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $ex->status }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
