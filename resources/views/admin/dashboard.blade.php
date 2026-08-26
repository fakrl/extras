@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
@if (auth()->user()->role === 'admin_default')
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
        <div class="metric-card">
            <div class="metric-label">Proyek Aktif</div>
            <div class="metric-value">{{ $proyekAktif }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Total Pendaftar</div>
            <div class="metric-value">{{ $totalPendaftar }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Perlu Dinego</div>
            <div class="metric-value">{{ $perluDinego }}</div>
        </div>
    </div>

    <div class="dashboard-grid-2col is-wide-narrow">
        <div class="card">
            <div class="card-title">Tahapan Partisipasi Kandidat</div>
            @php $maxPartisipasi = max($chartStatusPartisipasi['data']) ?: 1; @endphp
            <div class="funnel-steps">
                @foreach ($chartStatusPartisipasi['labels'] as $i => $label)
                    @php $val = $chartStatusPartisipasi['data'][$i]; @endphp
                    <div class="funnel-step">
                        <div class="funnel-step-label">{{ $label }}</div>
                        <div class="funnel-step-track">
                            <div class="funnel-step-fill" style="width: {{ round($val / $maxPartisipasi * 100) }}%;"></div>
                        </div>
                        <div class="funnel-step-value">{{ $val }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card">
            <div class="card-title">Status Pembayaran Extras</div>
            <div class="chart-box"><canvas id="chartPembayaran"></canvas></div>
        </div>
    </div>

    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('admin.users.index') }}" class="btn">Kelola Akun CD & Extras</a>
        <a href="{{ route('admin.projects.index') }}" class="btn btn-brand">Manajemen Proyek Casting</a>
        <a href="{{ route('admin.recap.index') }}" class="btn">Rekap Extras</a>
        <a href="{{ route('admin.work-history') }}" class="btn">Riwayat Kerja & Status Gaji Saya</a>
    </div>
@elseif (auth()->user()->role === 'admin_korlap')
    <div class="alert-info">
        Sebagai Korlap, kamu bisa mencatat absensi/sanksi Extras di lapangan lewat halaman proyek terkait.
    </div>
    <a href="{{ route('admin.work-history') }}" class="btn">Riwayat Kerja & Status Gaji Saya</a>
@else
    <div class="alert-info">
        Akses kamu sebagai {{ auth()->user()->role }} terbatas ke pencatatan penugasan & riwayat kerja.
    </div>
    <a href="{{ route('admin.work-history') }}" class="btn">Riwayat Kerja & Status Gaji Saya</a>
@endif
@endsection

@if (auth()->user()->role === 'admin_default')
@push('scripts')
<script>
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    var textColor = isDark ? '#a8c2ae' : '#4b5f52';

    new Chart(document.getElementById('chartPembayaran'), {
        type: 'doughnut',
        data: {
            labels: @json($chartStatusPembayaran['labels']),
            datasets: [{
                data: @json($chartStatusPembayaran['data']),
                backgroundColor: ['#374151', '#eab308', '#22c55e'],
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
@endif
