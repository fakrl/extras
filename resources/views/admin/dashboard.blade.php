@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
@if (auth()->user()->isAdmin())
    @if ($urgentProjects->isNotEmpty())
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger, #ef4444); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div>
                    <div style="font-weight: 700; color: var(--danger, #ef4444); font-size: 14.5px;">
                        <i class="ti ti-alert-triangle"></i> Perhatian: Ada {{ $urgentProjects->count() }} Proyek Berstatus Urgent / H-3!
                    </div>
                    <div style="font-size: 13px; color: var(--text-secondary); margin-top: 2px;">
                        Tanggal shooting sudah sangat dekat namun kuota kandidat belum terpenuhi. Segera bagikan link pendaftaran ke grup WA atau review lineup.
                    </div>
                </div>
                <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-brand">Lihat Proyek Urgent &rarr;</a>
            </div>
        </div>
    @endif

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

    <div class="card" style="margin-bottom: 20px;">
        <div class="card-title">Jadwal Shooting Bulan Ini</div>
        <x-jadwal-calendar :events="$jadwalBulanIni" :compact="true" />
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
        <a href="{{ route('admin.attendance.index') }}" class="btn">Kelola Absensi Lapangan</a>
        <a href="{{ route('admin.recap.index') }}" class="btn">Rekap Extras</a>
        <a href="{{ route('admin.work-history') }}" class="btn">Riwayat Kerja & Status Gaji Saya</a>
    </div>
@elseif (auth()->user()->isKorlap())
    <div class="alert-info" style="margin-bottom: 16px;">
        Sebagai Koordinator Lapangan (Korlap), tugas utama kamu adalah memvalidasi kehadiran Extras di lokasi syuting dan mencatat evaluasi lapangan.
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-brand"><i class="ti ti-camera"></i> Absensi Lapangan</a>
        <a href="{{ route('admin.work-history') }}" class="btn">Riwayat Kerja & Status Gaji Saya</a>
    </div>
@else
    <div class="alert-info">
        Akses kamu sebagai {{ auth()->user()->role }} terbatas ke pencatatan penugasan & riwayat kerja.
    </div>
    <a href="{{ route('admin.work-history') }}" class="btn">Riwayat Kerja & Status Gaji Saya</a>
@endif
@endsection

@if (auth()->user()->isAdmin())
@push('scripts')
<script>
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    var textColor = isDark ? '#9db3a2' : '#435449';

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
