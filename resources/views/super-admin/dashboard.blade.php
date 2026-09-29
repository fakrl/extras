@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $jumlahTindakan = $pendingRequests->count() + $sengketa->count() + ($honorStaf->jumlah ? 1 : 0) + $invoiceBelumLunas->count();
    $namaRole = ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'korlap' => 'Korlap', 'client' => 'Client', 'extras' => 'Extras'];
@endphp

@push('styles')
<style>
.sa-filter { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.sa-filter .btn { min-height: 32px; padding: 0 12px; font-size: 12px; border-radius: 20px; }
.sa-filter input[type=date] { min-height: 32px; font-size: 12px; padding: 0 6px; width: auto; }
.sa-stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
.sa-stat-grid.is-5 .metric-card:last-child { grid-column: span 2; }
@media (min-width: 861px) {
    .sa-stat-grid { grid-template-columns: repeat(4, 1fr); }
    .sa-stat-grid.is-5 { grid-template-columns: repeat(5, 1fr); }
    .sa-stat-grid.is-5 .metric-card:last-child { grid-column: auto; }
}
a.metric-card { display: block; text-decoration: none; color: inherit; border: 1px solid var(--border-color); }
a.metric-card:hover { border-color: var(--accent); }
.sa-row { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; padding: 10px 0; border-bottom: 1px solid var(--border-color); font-size: 13.5px; }
.sa-row:last-child { border-bottom: none; }
a.sa-row { text-decoration: none; color: inherit; }
a.sa-row:hover { color: var(--accent); }
.sa-sub { font-size: 12px; color: var(--text-muted); }
@media (min-width: 861px) { .sa-cal-grid { grid-template-columns: minmax(0, 400px) 1fr; } }
.sa-role-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 8px; }
</style>
@endpush

@section('content')
{{-- BD.3.1 Filter periode --}}
<div class="card" style="margin-bottom: 16px;">
    <div class="sa-filter">
        @foreach (\App\Http\Controllers\SuperAdmin\DashboardController::PRESET as $val => $label)
            <a href="{{ route('super-admin.dashboard', ['periode' => $val]) }}" class="btn {{ $preset === $val ? 'btn-brand' : '' }}">{{ $label }}</a>
        @endforeach
        <form method="GET" action="{{ route('super-admin.dashboard') }}" class="sa-filter">
            <input type="date" name="dari" value="{{ $dari->format('Y-m-d') }}" aria-label="Dari tanggal" required>
            <span class="sa-sub">&ndash;</span>
            <input type="date" name="sampai" value="{{ $sampai->format('Y-m-d') }}" aria-label="Sampai tanggal" required>
            <button type="submit" class="btn {{ $preset ? '' : 'btn-brand' }}">Terapkan</button>
        </form>
        <span class="sa-sub"><strong>{{ $jumlahHari }} hari</strong> &middot; {{ $dari->translatedFormat('d M Y') }} &ndash; {{ $sampai->translatedFormat('d M Y') }}</span>
    </div>
    <p class="sa-sub" style="margin: 8px 0 0;">Proyek difilter pakai tanggal shooting, uang pakai tanggal transaksi (invoice lunas, transfer honor, biaya lain-lain).</p>
</div>

<div class="dashboard-grid-2col sa-cal-grid">
{{-- BD.3.5 Kalender --}}
<div class="card">
    <div class="card-title">Jadwal Shooting</div>
    <x-jadwal-calendar :events="$jadwal" :bulan="$bulan->format('Y-m')" :detail="true" />
    <p class="sa-sub" style="margin: 8px 0 0;">Klik tanggal bertanda untuk lihat kegiatan hari itu.</p>
</div>

{{-- BD.3.2 Perlu tindakan --}}
<div class="card" style="border: 2px solid var(--accent-strong); margin-bottom: 16px;">
    <div class="card-title" style="color: var(--accent-strong);">
        <i class="ti ti-clipboard-list"></i> Perlu Tindakan
        @if ($jumlahTindakan)
            <span class="badge badge-pending" style="margin-left: 8px;">{{ $jumlahTindakan }}</span>
        @endif
    </div>

    @foreach ($pendingRequests as $req)
        <div class="sa-row">
            <div>
                <span class="badge badge-pending">Menunggu ACC</span>
                <strong>{{ $req->nama_produksi }}</strong>
                <div class="sa-sub">{{ $req->client_ph }} &bull; {{ $req->diajukanOlehClient?->name }}</div>
                <details style="margin-top: 6px; font-size: 13px;">
                    <summary style="cursor: pointer; color: var(--accent);">Lihat brief, kuota, deadline</summary>
                    <div style="margin-top: 6px;">Kuota: <strong>{{ $req->kuota }} orang</strong> &bull; Deadline: <strong>{{ $req->deadline?->format('d M Y') ?? '-' }}</strong></div>
                    <div style="margin-top: 4px; white-space: pre-line;">{{ $req->brief_catatan ?: '-' }}</div>
                </details>
            </div>
            <div style="display: flex; gap: 12px; flex-shrink: 0;">
                <form method="POST" action="{{ route('super-admin.projects.acc', $req) }}" style="display: inline-block;">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-brand" onclick="return confirm('Setujui permintaan proyek ini?')">ACC</button>
                </form>
                <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('tolak-req-{{ $req->id }}').showModal()">Tolak</button>
            </div>
        </div>
        <dialog id="tolak-req-{{ $req->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
            <form method="POST" action="{{ route('super-admin.projects.reject', $req) }}" style="padding: 18px;">
                @csrf @method('PATCH')
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Tolak pengajuan {{ $req->nama_produksi }}?</div>
                <label for="alasan-req-{{ $req->id }}">Alasan penolakan (dikirim ke Client)</label>
                <textarea name="alasan_tolak" id="alasan-req-{{ $req->id }}" rows="3" required maxlength="500" style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Tolak Pengajuan</button>
                </div>
            </form>
        </dialog>
    @endforeach

    @foreach ($sengketa as $p)
        <a href="{{ route('payments.show', $p->project_application_id) }}" class="sa-row">
            <div>
                <span class="badge badge-tolak">Pembayaran disengketakan</span>
                <strong>{{ $p->projectApplication?->extras?->user?->name ?? 'Extras' }}</strong>
                <div class="sa-sub">{{ $p->projectApplication?->castingProject?->nama_produksi }} &bull; {{ \Illuminate\Support\Str::limit($p->alasan_sengketa, 80) }}</div>
            </div>
            <span class="sa-sub">Buka &rarr;</span>
        </a>
    @endforeach

    @if ($honorStaf->jumlah)
        <a href="{{ route('admin.projects.index', ['bayar' => 'staf']) }}" class="sa-row">
            <div>
                <span class="badge badge-pending">Honor staf belum dibayar</span>
                <strong>{{ $honorStaf->jumlah }} honor</strong>
                <div class="sa-sub">Total {{ $rp($honorStaf->total) }}</div>
            </div>
            <span class="sa-sub">Buka &rarr;</span>
        </a>
    @endif

    @foreach ($invoiceBelumLunas as $inv)
        <a href="{{ route('admin.projects.show', [$inv->casting_project_id, 'tab' => 'cashflow']) }}" class="sa-row">
            <div>
                <span class="badge badge-info">Invoice belum lunas</span>
                <strong>{{ $inv->castingProject?->nama_produksi }}</strong>
                <div class="sa-sub">{{ $rp($inv->nilai) }}</div>
            </div>
            <span class="sa-sub">Buka &rarr;</span>
        </a>
    @endforeach

    @if (! $jumlahTindakan)
        <p class="sa-sub" style="margin: 0;">Tidak ada yang perlu ditindak saat ini.</p>
    @endif
</div>
</div>

{{-- BD.3.3 Status proyek --}}
<div class="card-title">Status Proyek <span class="sa-sub" style="font-weight: 400;">(shooting dalam periode; Menunggu ACC semua)</span></div>
<div class="sa-stat-grid">
    @foreach (\App\Models\CastingProject::TAHAP as $tahap => $label)
        <a href="{{ route('admin.projects.index', ['tahap' => $tahap] + ($tahap === 'menunggu_acc' ? [] : ['dari' => $dari->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d')])) }}" class="metric-card">
            <div class="metric-label">{{ $label }}</div>
            <div class="metric-value">{{ $statusProyek[$tahap] }}</div>
        </a>
    @endforeach
</div>

{{-- BD.3.4 Uang periode --}}
<div class="card" style="margin-bottom: 16px;">
    <div class="card-title">Uang Periode Ini</div>
    <div class="sa-stat-grid is-5">
        @foreach ([
            ['Masuk', $uang->total_masuk, 'invoice lunas'],
            ['Piutang', $uang->piutang, 'invoice belum lunas'],
            ['Keluar', $uang->total_keluar, 'honor & biaya'],
            ['Saldo', $uang->saldo, 'masuk − keluar'],
            ['Proyeksi', $uang->proyeksi, 'masuk + piutang − keluar'],
        ] as [$label, $nilai, $ket])
            <div class="metric-card" style="border: 1px solid var(--border-color);">
                <div class="metric-label">{{ $label }}</div>
                <div class="metric-value" style="font-size: var(--fs-lg, 18px);{{ in_array($label, ['Saldo', 'Proyeksi']) ? ' color: '.($nilai < 0 ? 'var(--danger)' : 'var(--accent-strong)').';' : '' }}">{{ $rp($nilai) }}</div>
                <div class="sa-sub">{{ $ket }}</div>
            </div>
        @endforeach
    </div>
    <p class="sa-sub" style="margin: 0 0 12px;">Saldo minus wajar kalau invoice belum dibayar — lihat Proyeksi. Piutang = invoice belum lunas dari proyek yang shooting-nya dalam periode.</p>
    <div class="chart-box"><canvas id="chartUangBulanan"></canvas></div>
</div>

<div class="dashboard-grid-2col is-even">
    {{-- BD.3.6 Akun --}}
    <div class="card">
        <div class="card-title">Akun</div>
        <div class="sa-role-grid">
            @foreach ($namaRole as $role => $label)
                <a href="{{ route('super-admin.admins.index', ['role' => $role]) }}" class="metric-card">
                    <div class="metric-label">{{ $label }}</div>
                    <div class="metric-value">{{ $akunPerRole[$role] ?? 0 }}</div>
                </a>
            @endforeach
        </div>
        @if ($clientBelumGantiPassword)
            <a href="{{ route('super-admin.admins.index', ['role' => 'client']) }}" class="sa-row" style="margin-top: 8px;">
                <div><span class="badge badge-pending">Perlu tindakan</span> {{ $clientBelumGantiPassword }} akun Client baru belum ganti password</div>
                <span class="sa-sub">Buka &rarr;</span>
            </a>
        @else
            <p class="sa-sub" style="margin: 10px 0 0;">Semua akun Client sudah ganti password.</p>
        @endif
    </div>

    {{-- BD.3.7 5 proyek berjalan teratas --}}
    <div class="card">
        <div class="card-title">5 Proyek Berjalan Teratas</div>
        @forelse ($proyekBerjalan as $p)
            <a href="{{ route('admin.projects.show', $p) }}" class="sa-row">
                <div>
                    <strong>{{ $p->nama_produksi }}</strong>
                    <div class="sa-sub">{{ $p->client?->name ?? $p->client_ph }} &bull; {{ $p->rentangShooting() }}</div>
                </div>
                <span class="sa-sub">{{ $p->shooting_terdekat ? \Carbon\Carbon::parse($p->shooting_terdekat)->translatedFormat('d M') : '' }}</span>
            </a>
        @empty
            <p class="sa-sub" style="margin: 0;">Tidak ada proyek berjalan.</p>
        @endforelse
        <div style="margin-top: 10px; display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; font-size: 12.5px;">
            <a href="{{ route('admin.projects.index', ['tahap' => 'berjalan']) }}" style="color: var(--accent);">Semua proyek berjalan &rarr;</a>
            <a href="{{ route('admin.projects.index') }}" style="color: var(--accent);">Proyek &amp; Keuangan &rarr;</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var css = getComputedStyle(document.documentElement);
    var warna = function (v, d) { return css.getPropertyValue(v).trim() || d; };
    var rp = function (v) { return 'Rp ' + Number(v).toLocaleString('id-ID'); };
    new Chart(document.getElementById('chartUangBulanan'), {
        type: 'bar',
        data: {
            labels: @json($uang->per_bulan->pluck('label')),
            datasets: [
                { label: 'Masuk', data: @json($uang->per_bulan->pluck('masuk')), backgroundColor: warna('--accent-strong', '#15803D') },
                { label: 'Keluar', data: @json($uang->per_bulan->pluck('keluar')), backgroundColor: warna('--danger', '#DC2626') }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + rp(c.raw); } } } },
            scales: { y: { beginAtZero: true, ticks: { callback: rp } } }
        }
    });
}());
</script>
@endpush
