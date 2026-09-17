@extends('layouts.app')

@section('title', 'Dashboard Casting Director')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Halo, {{ auth()->user()->name }}.
</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="metric-card">
        <div class="metric-label">Perlu Direview</div>
        <div class="metric-value">{{ $perluDireview }}</div>
    </div>
</div>

<div class="dashboard-grid-2col is-even">
    <div class="card">
        <div class="card-title">Keputusan Greenlight Saya</div>
        <div class="chart-box"><canvas id="chartKeputusan"></canvas></div>
    </div>
    <div class="card" style="display: flex; align-items: center; justify-content: center;">
        <a href="{{ route('cd.reviews.index') }}" class="btn btn-brand">Buka Greenlight</a>
    </div>
</div>

<div class="card" style="margin-top: 16px;">
    <div class="card-title">Proyek Berjalan</div>
    @forelse ($proyekBerjalan as $p)
        <div style="padding: 6px 0; border-bottom: 1px solid var(--border-color);">
            {{ $p->nama_produksi }}
            <span style="color: var(--text-secondary); font-size: 12px; float: right;">Deadline: {{ \Carbon\Carbon::parse($p->deadline)->format('d M Y') }}</span>
        </div>
    @empty
        <p style="color: var(--text-muted); font-size: 13px;">Tidak ada proyek yang sedang berjalan.</p>
    @endforelse
</div>

<div class="card" style="margin-top: 16px;">
    <div class="card-title">Pembayaran Pending</div>
    @forelse ($pelunasanPending as $produksi => $jumlah)
        <div style="padding: 6px 0; border-bottom: 1px solid var(--border-color);">
            {{ $produksi }}
            <span class="badge badge-pending" style="float: right;">{{ $jumlah }} pending</span>
        </div>
    @empty
        <p style="color: var(--text-muted); font-size: 13px;">Tidak ada pembayaran pending.</p>
    @endforelse
</div>

<div class="card" style="margin-top: 16px;">
    <div class="card-title">Karakter &amp; Pendaftar</div>
    <table>
        <thead>
            <tr><th>Proyek</th><th>Karakter</th><th>Pendaftar</th></tr>
        </thead>
        <tbody>
            @forelse ($karakterPendaftar as $item)
                <tr>
                    <td>{{ $item['proyek'] }}</td>
                    <td>{{ $item['karakter'] }}</td>
                    <td>{{ $item['jumlah'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 16px 0;">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var textColor = isDark ? '#9db3a2' : '#435449';

    new Chart(document.getElementById('chartKeputusan'), {
        type: 'doughnut',
        data: {
            labels: @json($chartKeputusan['labels']),
            datasets: [{
                data: @json($chartKeputusan['data']),
                backgroundColor: ['#22c55e', '#ef4444'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: textColor } } }
        }
    });
</script>
@endpush
