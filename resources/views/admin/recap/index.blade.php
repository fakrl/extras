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
    <form method="GET" action="{{ route('admin.recap.index') }}" style="margin-bottom:12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <label style="font-size:12.5px; color:var(--text-secondary); margin:0;">Filter Kategori:</label>
        <select name="kategori_id" style="width:auto; min-height:unset; padding:4px 8px; font-size:12.5px; margin-bottom:0;">
            <option value="">Semua</option>
            @foreach ($allCategories as $kat)
                <option value="{{ $kat->id }}" {{ request('kategori_id') == $kat->id ? 'selected' : '' }}>{{ $kat->nama }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm">Filter</button>
    </form>
    <table>
        <thead><tr><th>Alias</th><th>Jumlah Terpilih</th><th>Status</th><th>Kategori</th></tr></thead>
        <tbody>
            @foreach ($extrasPalingSering as $ex)
                <tr>
                    <td>{{ $ex->user->username ?? '-' }}</td>
                    <td>{{ $ex->applications_count }}</td>
                    <td>
                        <span class="badge {{ $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $ex->status }}
                        </span>
                    </td>
                    <td>
                        @foreach ($ex->categories as $kat)
                            <span class="badge badge-pending" style="font-size:11px;">{{ $kat->nama }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card" style="margin-top: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Extras Paling Sering Membatalkan Mendadak</div>
    @if ($extrasSeringBatal->isEmpty())
        <p style="color: var(--text-muted); font-size: 13px; margin: 0;">Belum ada pembatalan tercatat.</p>
    @else
        <table>
            <thead><tr><th>Alias</th><th>Jumlah Batal</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($extrasSeringBatal as $ex)
                    <tr>
                        <td>{{ $ex->user->username ?? '-' }}</td>
                        <td>{{ $ex->cancel_count }}</td>
                        <td>
                            <span class="badge {{ $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">
                                {{ $ex->status }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
