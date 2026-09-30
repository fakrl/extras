@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
@php $tampilAdmin = auth()->user()->bisaSebagaiAdmin() && auth()->user()->modeSa() !== 'korlap'; @endphp
@if ($tampilAdmin)
    @php $tindakan = array_filter($ringkasan, fn ($r) => $r['jumlah'] > 0); $jumlahTindakan = $urgentProjects->count() + ($pembayaranSengketa ? 1 : 0) + count($tindakan); @endphp
    <div class="dash-tiga">
        <div class="card">
            <div class="card-title">Jadwal Shooting Bulan Ini</div>
            <x-jadwal-calendar :events="$jadwalBulanIni" :compact="true" />
        </div>

        <div class="card dash-perlu {{ $jumlahTindakan ? '' : 'is-aman' }}">
            <div class="card-title">
                <i class="ti ti-clipboard-list"></i> Perlu Tindakan
                @if ($jumlahTindakan)
                    <span class="badge badge-pending" style="margin-left: 8px;">{{ $jumlahTindakan }}</span>
                @endif
            </div>

            @if ($urgentProjects->isNotEmpty())
                <p class="dash-sub" style="margin: 0 0 4px;">
                    <strong style="color: var(--danger);"><i class="ti ti-alert-triangle"></i> Ada {{ $urgentProjects->count() }} Proyek Berstatus Urgent / H-3!</strong>
                    Tanggal shooting sudah sangat dekat namun kuota kandidat belum terpenuhi. Segera bagikan link pendaftaran ke grup WA atau review lineup.
                </p>
                @foreach ($urgentProjects as $p)
                    <a href="{{ route('admin.projects.show', $p) }}" class="dash-row">
                        <div>
                            <span class="badge badge-tolak">Urgent</span>
                            <strong>{{ $p->nama_produksi }}</strong>
                            <div class="dash-sub">{{ $p->client_ph ?? '-' }} &bull; {{ $p->rentangShooting() }}</div>
                        </div>
                        <span class="dash-sub">Buka &rarr;</span>
                    </a>
                @endforeach
                <a href="{{ route('admin.projects.index', ['urgent' => 1]) }}" class="dash-row" style="color: var(--accent); font-weight: 600;">Lihat Proyek Urgent &rarr;</a>
            @endif

            @if ($pembayaranSengketa > 0)
                <a href="{{ route('admin.projects.index') }}" class="dash-row">
                    <div>
                        <span class="badge badge-pending">Pembayaran Bermasalah</span>
                        <strong>{{ $pembayaranSengketa }} kasus</strong>
                        <div class="dash-sub">Extras menyengketakan pembayaran</div>
                    </div>
                    <span class="dash-sub">Tinjau Pembayaran &rarr;</span>
                </a>
            @endif

            @foreach ($tindakan as $r)
                <a href="{{ $r['url'] }}" class="dash-row">
                    <div><strong>{{ $r['jumlah'] }}</strong> {{ $r['label'] }}</div>
                    <span class="dash-sub">Buka &rarr;</span>
                </a>
            @endforeach

            @unless ($jumlahTindakan)
                <div class="dash-aman" role="status"><i class="ti ti-circle-check"></i> Semua aman &mdash; tidak ada yang perlu ditindak.</div>
            @endunless
        </div>

        <div class="card">
            <div class="card-title">Ringkasan</div>
            <div class="dash-metrik" style="margin-bottom: 14px;">
                <a href="{{ route('admin.projects.index', ['status' => 'dibuka']) }}" class="metric-card">
                    <div class="metric-label">Proyek Aktif</div>
                    <div class="metric-value">{{ $proyekAktif }}</div>
                </a>
                <a href="{{ route('admin.projects.index') }}" class="metric-card">
                    <div class="metric-label">Total Pendaftar</div>
                    <div class="metric-value">{{ $totalPendaftar }}</div>
                </a>
                <a href="{{ route('admin.projects.index', ['peserta' => 'nego_fee']) }}" class="metric-card" style="grid-column: span 2;">
                    <div class="metric-label">Perlu Dinego</div>
                    <div class="metric-value">{{ $perluDinego }}</div>
                </a>
            </div>
            <a href="{{ route('admin.projects.index') }}" class="dash-row"><span><i class="ti ti-folder"></i> Kelola Proyek</span><span class="dash-sub">&rarr;</span></a>
            <a href="{{ route('admin.users.index') }}" class="dash-row"><span><i class="ti ti-users"></i> Kelola Akun Client & Extras</span><span class="dash-sub">&rarr;</span></a>
            <a href="{{ route('admin.attendance.index') }}" class="dash-row"><span><i class="ti ti-camera"></i> Absensi Lapangan</span><span class="dash-sub">&rarr;</span></a>
            <a href="{{ route('admin.recap.index') }}" class="dash-row"><span><i class="ti ti-report"></i> Rekap Extras</span><span class="dash-sub">&rarr;</span></a>
            <a href="{{ route('admin.work-history') }}" class="dash-row"><span><i class="ti ti-wallet"></i> Riwayat Kerja & Status Gaji Saya</span><span class="dash-sub">&rarr;</span></a>
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
@elseif (auth()->user()->bisaSebagaiKorlap())
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

@if (auth()->user()->bisaSebagaiAdmin() && auth()->user()->modeSa() !== 'korlap')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
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
