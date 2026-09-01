@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Monitoring & analitik seluruh sistem — read-only. Operasional harian (seleksi, nego fee, kelola akun) tetap wewenang Admin Default.
</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="metric-card">
        <div class="metric-label">Proyek Berjalan</div>
        <div class="metric-value">{{ $proyekBerjalan }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Extras Aktif</div>
        <div class="metric-value">{{ $extrasAktif }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Total Akun Sistem</div>
        <div class="metric-value">{{ $totalAkun }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Honor Belum Diproses</div>
        <div class="metric-value">{{ $honorBelumDiproses }}</div>
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
        <div class="card-title">Status Keaktifan Extras</div>
        <div class="chart-box"><canvas id="chartStatusExtras"></canvas></div>
    </div>
</div>

<div class="dashboard-grid-2col is-even">
    <div class="card">
        <div class="card-title">Jumlah Akun per Role</div>
        <div class="chart-box"><canvas id="chartAkunRole"></canvas></div>
    </div>
    <div class="card">
        <div class="card-title">Penugasan Admin Selesai</div>
        <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
            <span>{{ $assignmentSelesai }} dari {{ $assignmentTotal }} penugasan</span>
            <span>{{ $assignmentTotal > 0 ? round($assignmentSelesai / $assignmentTotal * 100) : 0 }}%</span>
        </div>
        <div style="background: var(--bg-nav-active); border-radius: 20px; height: 10px; overflow: hidden;">
            <div style="background: var(--accent); height: 100%; width: {{ $assignmentTotal > 0 ? round($assignmentSelesai / $assignmentTotal * 100) : 0 }}%;"></div>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('super-admin.monitoring') }}" class="btn btn-brand">Monitoring Semua Akun</a>
            <a href="{{ route('super-admin.admins.index') }}" class="btn">Kelola Admin & Staf</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-title">Rekap Honor Seluruh Admin</div>
    <table>
        <thead>
            <tr><th>Nama Admin</th><th>Role</th><th>Total Honor</th><th>Proyek Selesai</th><th>Proyek Berjalan</th></tr>
        </thead>
        <tbody>
            @forelse ($rekapHonorAdmin as $admin)
                <tr>
                    <td>{{ $admin->nama }}</td>
                    <td>{{ $admin->role }}</td>
                    <td>Rp {{ number_format($admin->total_honor, 0, ',', '.') }}</td>
                    <td>{{ $admin->proyek_selesai }}</td>
                    <td>{{ $admin->proyek_berjalan }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color: var(--text-muted);">Belum ada Admin.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    var textColor = isDark ? '#9db3a2' : '#435449';
    var palette = ['#22c55e', '#4ade80', '#86efac', '#15803d', '#065f46', '#a3e635', '#eab308', '#f97316', '#ef4444', '#94a3b8', '#64748b'];

    new Chart(document.getElementById('chartStatusExtras'), {
        type: 'doughnut',
        data: {
            labels: @json($chartStatusExtras['labels']),
            datasets: [{
                data: @json($chartStatusExtras['data']),
                backgroundColor: ['#22c55e', '#374151'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: textColor } } }
        }
    });

    new Chart(document.getElementById('chartAkunRole'), {
        type: 'bar',
        data: {
            labels: @json($chartAkunPerRole['labels']),
            datasets: [{
                data: @json($chartAkunPerRole['data']),
                backgroundColor: palette,
                borderRadius: 6,
                maxBarThickness: 32,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: textColor, maxRotation: 30, minRotation: 30 }, grid: { display: false } },
                y: { ticks: { color: textColor, precision: 0 }, grid: { color: gridColor } }
            }
        }
    });
</script>
@endpush
