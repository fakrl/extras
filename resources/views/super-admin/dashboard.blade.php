@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $jumlahTindakan = $pendingRequests->count() + $sengketa->count() + ($honorStaf->jumlah ? 1 : 0) + ($tanpaClient ? 1 : 0) + $invoiceBelumLunas->count();
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
.sa-role-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 8px; }
.sa-akun { display: grid; gap: 10px; align-items: center; }
@media (min-width: 861px) { .sa-akun { grid-template-columns: 2fr 1fr; gap: 16px; } }
.sa-tab { width: 100%; text-align: left; font: inherit; color: inherit; cursor: pointer; border: 1px solid var(--border-color); }
.sa-tab:hover { border-color: var(--accent); }
.sa-tab[aria-selected=true] { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
.sa-tab:focus-visible { outline: 2px solid var(--accent-strong); outline-offset: 2px; }
.sa-lihat-semua { color: var(--accent); font-weight: 600; }
.sa-filterbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 16px; }
.sa-filterbar .btn { min-height: 34px; padding: 0 12px; font-size: 12.5px; border-radius: 20px; }
.sa-filterbar-range { display: flex; gap: 6px; align-items: center; margin: 0; }
.sa-filterbar-range input[type=date] { min-height: 34px; font-size: 12.5px; padding: 0 6px; width: auto; margin: 0; }
.sa-filterbar-info { margin-left: auto; cursor: help; white-space: nowrap; }
@media (max-width: 860px) { .sa-filterbar-info { margin-left: 0; white-space: normal; } }
.dash-tiga .sa-stat-grid { grid-template-columns: repeat(2, 1fr); }
.sa-akun-mini { margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); }
.sa-mini-title { font-size: var(--fs-sm, 13px); font-weight: 600; margin-bottom: 8px; }
.sa-akun-mini .sa-akun { grid-template-columns: 1fr !important; gap: 8px; }
.sa-akun-mini .sa-role-grid { display: flex; flex-wrap: wrap; gap: 6px; }
</style>
@endpush

@section('content')
{{-- BD.3.1 Filter periode: 1 baris --}}
<div class="sa-filterbar">
    @foreach (\App\Http\Controllers\SuperAdmin\DashboardController::PRESET as $val => $label)
        <a href="{{ route('super-admin.dashboard', ['periode' => $val]) }}" class="btn {{ $preset === $val ? 'btn-brand' : '' }}">{{ $label }}</a>
    @endforeach
    <form method="GET" action="{{ route('super-admin.dashboard') }}" class="sa-filterbar-range">
        <input type="date" name="dari" value="{{ $dari->format('Y-m-d') }}" aria-label="Dari tanggal" required>
        <span class="dash-sub">&ndash;</span>
        <input type="date" name="sampai" value="{{ $sampai->format('Y-m-d') }}" aria-label="Sampai tanggal" required>
        <button type="submit" class="btn {{ $preset ? '' : 'btn-brand' }}">Terapkan</button>
    </form>
    <span class="dash-sub sa-filterbar-info" title="Proyek difilter pakai tanggal shooting, uang pakai tanggal transaksi (invoice lunas, transfer honor, biaya lain-lain).">
        <strong>{{ $jumlahHari }} hari</strong> &middot; {{ $dari->translatedFormat('d M Y') }} &ndash; {{ $sampai->translatedFormat('d M Y') }} &#9432;
    </span>
</div>

{{-- 3 kotak sejajar: Jadwal · Perlu tindakan · Status proyek (+ Akun) --}}
<div class="dash-tiga">
<div class="card">
    <div class="card-title">Jadwal Shooting</div>
    <x-jadwal-calendar :events="$jadwal" :bulan="$bulan->format('Y-m')" :detail="true" />
    <p class="dash-sub" style="margin: 8px 0 0;">Klik tanggal bertanda untuk lihat kegiatan hari itu.</p>
</div>

<div class="card dash-perlu {{ $jumlahTindakan ? '' : 'is-aman' }}">
    <div class="card-title">
        <i class="ti ti-clipboard-list"></i> Perlu Tindakan
        @if ($jumlahTindakan)
            <span class="badge badge-pending" style="margin-left: 8px;">{{ $jumlahTindakan }}</span>
        @endif
    </div>

    @foreach ($pendingRequests as $req)
        <div class="dash-row">
            <div>
                <span class="badge badge-pending">Menunggu ACC</span>
                <strong>{{ $req->nama_produksi }}</strong>
                <div class="dash-sub">{{ $req->namaClient() }} &bull; {{ $req->client?->name }}</div>
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
                <label for="alasan-req-{{ $req->id }}">Alasan penolakan (dikirim ke Client) <span class="wajib" aria-hidden="true">*</span></label>
                <textarea name="alasan_tolak" id="alasan-req-{{ $req->id }}" rows="3" required maxlength="500" style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Tolak Pengajuan</button>
                </div>
            </form>
        </dialog>
    @endforeach

    @foreach ($sengketa as $p)
        <a href="{{ route('payments.show', $p->project_application_id) }}" class="dash-row">
            <div>
                <span class="badge badge-tolak">Pembayaran disengketakan</span>
                <strong>{{ $p->projectApplication?->extras?->user?->name ?? 'Extras' }}</strong>
                <div class="dash-sub">{{ $p->projectApplication?->castingProject?->nama_produksi }} &bull; {{ \Illuminate\Support\Str::limit($p->alasan_sengketa, 80) }}</div>
            </div>
            <span class="dash-sub">Buka &rarr;</span>
        </a>
    @endforeach

    @if ($honorStaf->jumlah)
        <a href="{{ route('admin.projects.index', ['bayar' => 'staf']) }}" class="dash-row">
            <div>
                <span class="badge badge-pending">Honor staf belum dibayar</span>
                <strong>{{ $honorStaf->jumlah }} honor</strong>
                <div class="dash-sub">Total {{ $rp($honorStaf->total) }}</div>
            </div>
            <span class="dash-sub">Buka &rarr;</span>
        </a>
    @endif

    @if ($tanpaClient)
        <a href="{{ route('admin.projects.index', ['tanpa_client' => 1]) }}" class="dash-row">
            <div>
                <span class="badge badge-pending">Client belum diisi</span>
                <strong>{{ $tanpaClient }} proyek</strong>
                <div class="dash-sub">Pilih akun Client di form edit proyek</div>
            </div>
            <span class="dash-sub">Buka &rarr;</span>
        </a>
    @endif

    @foreach ($invoiceBelumLunas as $inv)
        <a href="{{ route('admin.projects.show', [$inv->casting_project_id, 'tab' => 'cashflow']) }}" class="dash-row">
            <div>
                <span class="badge badge-info">Invoice belum lunas</span>
                <strong>{{ $inv->castingProject?->nama_produksi }}</strong>
                <div class="dash-sub">{{ $rp($inv->nilai) }}</div>
            </div>
            <span class="dash-sub">Buka &rarr;</span>
        </a>
    @endforeach

    @unless ($jumlahTindakan)
        <div class="dash-aman" role="status"><i class="ti ti-circle-check"></i> Semua aman &mdash; tidak ada yang perlu ditindak.</div>
    @endunless
</div>

<div class="card">
    <div class="card-title">Status Proyek <span class="dash-sub" style="font-weight: 400; cursor: help;" title="Mendatang/Berjalan/Selesai: shooting dalam periode. Menunggu ACC: semua.">&#9432;</span></div>
    <div class="sa-stat-grid" role="tablist" aria-label="Tahap proyek">
        @foreach (\App\Models\CastingProject::TAHAP as $tahap => $label)
            <button type="button" class="metric-card sa-tab" role="tab" id="tab-{{ $tahap }}" aria-controls="panel-{{ $tahap }}" aria-selected="{{ $tahap === $tabAwal ? 'true' : 'false' }}" tabindex="{{ $tahap === $tabAwal ? 0 : -1 }}">
                <div class="metric-label">{{ $label }}</div>
                <div class="metric-value">{{ $statusProyek[$tahap] }}</div>
            </button>
        @endforeach
    </div>
    @foreach ($proyekPerTahap as $tahap => $daftar)
        @php $label = \App\Models\CastingProject::TAHAP[$tahap]; @endphp
        <div role="tabpanel" id="panel-{{ $tahap }}" aria-labelledby="tab-{{ $tahap }}" @if ($tahap !== $tabAwal) hidden @endif>
            @forelse ($daftar as $p)
                <a href="{{ route('admin.projects.show', $p) }}" class="dash-row">
                    <div>
                        <strong>{{ $p->nama_produksi }}</strong>
                        <div class="dash-sub">{{ $p->client?->name ?? '-' }} &bull; {{ $p->rentangShooting() }}</div>
                    </div>
                    <span class="dash-sub">{{ $p->tanggal_acuan ? \Carbon\Carbon::parse($p->tanggal_acuan)->translatedFormat('d M') : '' }}</span>
                </a>
            @empty
                <p class="dash-sub" style="margin: 0;">Tidak ada proyek {{ strtolower($label) }}{{ $tahap === 'menunggu_acc' ? '' : ' dalam periode ini' }}.</p>
            @endforelse
            <div style="margin-top: 10px; display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; font-size: 12.5px;">
                <a href="{{ route('admin.projects.index', ['tahap' => $tahap] + ($tahap === 'menunggu_acc' ? [] : ['dari' => $dari->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d')])) }}" class="sa-lihat-semua">Lihat semua {{ $label }} &rarr;</a>
                <a href="{{ route('admin.projects.index') }}" style="color: var(--accent);">Proyek &amp; Keuangan &rarr;</a>
            </div>
        </div>
    @endforeach
    {{-- Akun digabung ke kartu Status --}}
    <div class="sa-akun-mini">
        <div class="sa-mini-title">Akun</div>
    <div class="sa-akun">
        <div class="sa-role-grid">
            @foreach ($namaRole as $role => $label)
                <a href="{{ route('super-admin.akun.index', ['role' => $role]) }}" class="dash-pill">{{ $label }} <strong>{{ $akunPerRole[$role] ?? 0 }}</strong></a>
            @endforeach
        </div>
        @if ($clientBelumGantiPassword)
            <a href="{{ route('super-admin.admins.index', ['role' => 'client']) }}" class="dash-row">
                <div><span class="badge badge-pending">Perlu tindakan</span> {{ $clientBelumGantiPassword }} akun Client baru belum ganti password</div>
                <span class="dash-sub">Buka &rarr;</span>
            </a>
        @else
            <p class="dash-sub" style="margin: 0;">Semua akun Client sudah ganti password.</p>
        @endif
    </div>
    </div>
</div>
</div>

@include('partials.keputusan-client', ['kecil' => true])

<div class="card">
    <div class="card-title">Uang Periode Ini</div>
    <div class="sa-stat-grid is-5">
        @foreach ([
            ['Masuk', $uang->total_masuk, 'invoice lunas'],
            ['Piutang', $uang->piutang, 'invoice belum lunas'],
            ['Keluar (sudah dibayar)', $uang->total_keluar, 'honor & biaya dibayar di periode ini'],
            ['Saldo', $uang->saldo, 'masuk − keluar'],
            ['Proyeksi', $uang->proyeksi, 'masuk + piutang − keluar'],
        ] as [$label, $nilai, $ket])
            <div class="metric-card" style="border: 1px solid var(--border-color);">
                <div class="metric-label">{{ $label }}@if (str_starts_with($label, 'Keluar')) <span title="Dashboard cuma menghitung yang sudah dibayar dalam periode; di proyek dihitung semua kewajiban." style="cursor: help;">&#9432;</span>@endif</div>
                <div class="metric-value" style="font-size: var(--fs-lg, 18px);{{ in_array($label, ['Saldo', 'Proyeksi']) ? ' color: '.($nilai < 0 ? 'var(--danger)' : 'var(--accent-strong)').';' : '' }}">{{ $rp($nilai) }}</div>
                <div class="dash-sub">{{ $ket }}</div>
            </div>
        @endforeach
    </div>
    <p class="dash-sub" style="margin: 0 0 12px;">Saldo minus wajar kalau invoice belum dibayar — lihat Proyeksi. Piutang = invoice belum lunas dari proyek yang shooting-nya dalam periode.</p>
    <div class="chart-box"><canvas id="chartUangBulanan"></canvas></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var tabs = [].slice.call(document.querySelectorAll('.sa-tab'));
    var pilih = function (t) {
        tabs.forEach(function (x) {
            var aktif = x === t;
            x.setAttribute('aria-selected', aktif);
            x.tabIndex = aktif ? 0 : -1;
            document.getElementById(x.getAttribute('aria-controls')).hidden = !aktif;
        });
    };
    tabs.forEach(function (t, i) {
        t.addEventListener('click', function () { pilih(t); });
        t.addEventListener('keydown', function (e) {
            var n = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[e.key];
            if (n === undefined) return;
            e.preventDefault();
            var t2 = tabs[(n + tabs.length) % tabs.length];
            pilih(t2);
            t2.focus();
        });
    });
}());
</script>
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
            scales: { y: { beginAtZero: true, suggestedMax: 1000000, ticks: { precision: 0, callback: rp } } }
        }
    });
}());
</script>
@endpush
