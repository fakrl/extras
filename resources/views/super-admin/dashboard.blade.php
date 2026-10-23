@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $namaRole = ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'korlap' => 'Korlap', 'client' => 'Client', 'extras' => 'Extras'];
@endphp

@push('styles')
<style>
.sa-stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
@media (min-width: 861px) {
    .sa-stat-grid { grid-template-columns: repeat(4, 1fr); }
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
.sa-filterbar-info { margin-left: auto; cursor: help; white-space: nowrap; }
@media (max-width: 860px) { .sa-filterbar-info { margin-left: 0; white-space: normal; } }
.dash-tiga .sa-stat-grid { grid-template-columns: repeat(2, 1fr); }
.sa-akun-mini { margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); }
.sa-mini-title { font-size: var(--fs-sm, 13px); font-weight: 600; margin-bottom: 8px; }
.sa-akun-mini .sa-akun { grid-template-columns: 1fr !important; gap: 8px; }
.sa-akun-mini .sa-role-grid { display: flex; flex-wrap: wrap; gap: 6px; }
.sa-pintasan { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
.sa-pintasan .btn { display: inline-flex; align-items: center; gap: 8px; }
.pt-grup { margin-bottom: 14px; }
.pt-grup:last-child { margin-bottom: 0; }
.pt-grup-head { font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
</style>
@endpush

@section('content')
@php
    $badgeKelas = fn ($mendesak) => $mendesak ? 'badge-urgent' : 'badge-netral';
@endphp
<div class="sa-pintasan">
    <a href="#perlu-tindakan" class="btn"><span class="badge {{ $badgeKelas($pengajuanMendesak || $pantauMendesak) }}">{{ $jumlahTindakan }}</span> Perlu Tindakan</a>
    <a href="#grup-pengajuan" class="btn"><span class="badge {{ $badgeKelas($pengajuanMendesak) }}">{{ $pengajuanBaru->count() + $pengajuanRevisi->count() }}</span> Persetujuan</a>
    <a href="#pantau-admin" class="btn"><span class="badge {{ $badgeKelas($pantauMendesak) }}">{{ $pantauTotal }}</span> Pantau Admin</a>
</div>

{{-- CQ.4.1 Filter periode: satu dropdown untuk semua (Status Proyek, agenda, statistik) --}}
<div class="sa-filterbar">
    <x-filter-periode :dari="$dari" :sampai="$sampai" :preset="$preset" :action="route('super-admin.dashboard')" />
    <span class="dash-sub sa-filterbar-info">
        <strong>{{ $jumlahHari }} hari</strong> &middot; {{ $dari->translatedFormat('d M Y') }} &ndash; {{ $sampai->translatedFormat('d M Y') }}
    </span>
</div>

{{-- 3 kotak sejajar: Jadwal · Perlu tindakan · Status proyek (+ Akun) --}}
<div class="dash-tiga">
<div class="card">
    <div class="card-title">Jadwal Shooting</div>
    <x-agenda :events="$agendaEvents" />
</div>

<div class="card dash-perlu {{ $jumlahTindakan ? '' : 'is-aman' }}" id="perlu-tindakan">
    <div class="card-title">
        <i class="ti ti-clipboard-list"></i> Perlu Tindakan
        @if ($jumlahTindakan)
            <span class="badge badge-netral" style="margin-left: 8px;">{{ $jumlahTindakan }}</span>
        @endif
        <span class="dash-sub" style="font-weight: 400;">(semua waktu, tidak ikut filter)</span>
    </div>

    <div id="grup-pengajuan">
        <div class="pt-grup">
            <div class="pt-grup-head">Pengajuan Client menunggu keputusan <span class="badge badge-netral">{{ $pengajuanBaru->count() }}</span></div>
            @foreach ($pengajuanBaru as $req)
                @include('super-admin.dashboard._pengajuan', ['req' => $req, 'label' => 'Menunggu ACC'])
            @endforeach
            @if ($pengajuanBaru->isEmpty())
                <p class="dash-sub" style="margin: 0;">Tidak ada pengajuan baru.</p>
            @endif
        </div>

        <div class="pt-grup">
            <div class="pt-grup-head">Pengajuan sudah direvisi Client <span class="badge badge-netral">{{ $pengajuanRevisi->count() }}</span></div>
            @foreach ($pengajuanRevisi as $req)
                @include('super-admin.dashboard._pengajuan', ['req' => $req, 'label' => 'Diajukan ulang'])
            @endforeach
            @if ($pengajuanRevisi->isEmpty())
                <p class="dash-sub" style="margin: 0;">Tidak ada pengajuan hasil revisi.</p>
            @endif
        </div>
    </div>

    <div class="pt-grup">
        <div class="pt-grup-head">Proyek disetujui belum punya PIC <span class="badge badge-netral">{{ $tanpaPic->count() }}</span></div>
        @if ($tanpaPic->isNotEmpty())
            <a href="{{ route('admin.projects.index', ['tanpa_client' => 1]) }}" class="dash-row">
                <div>
                    <span class="badge badge-pending">PIC/Client belum diisi</span>
                    <strong>{{ $tanpaPic->count() }} proyek</strong>
                    <div class="dash-sub">Pilih Admin PIC dan akun Client di form edit proyek</div>
                </div>
                <span class="dash-sub">Buka &rarr;</span>
            </a>
        @else
            <p class="dash-sub" style="margin: 0;">Semua proyek disetujui sudah punya PIC.</p>
        @endif
    </div>

    <div class="pt-grup">
        <div class="pt-grup-head">Akun perlu perhatian <span class="badge badge-netral">{{ $akunPerhatian }}</span></div>
        @if ($akunPerhatian)
            <a href="{{ route('super-admin.akun.index', ['role' => 'client', 'ganti_password' => 1]) }}" class="dash-row">
                <div><strong>{{ $akunPerhatian }} akun Client</strong><div class="dash-sub">Belum ganti password lebih dari 7 hari</div></div>
                <span class="dash-sub">Buka &rarr;</span>
            </a>
        @else
            <p class="dash-sub" style="margin: 0;">Tidak ada akun yang perlu perhatian.</p>
        @endif
    </div>

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
                @if ($pertumbuhan[$tahap] === null)
                    <div class="dash-sub" style="font-size: 11px;">Semua waktu</div>
                @else
                    <div class="dash-sub" style="font-size: 11px;" title="Dibanding periode sebelumnya, panjang sama">{{ $pertumbuhan[$tahap] >= 0 ? '▲' : '▼' }} {{ abs($pertumbuhan[$tahap]) }}%</div>
                @endif
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
                <a href="{{ route('admin.projects.index') }}" style="color: var(--accent);">Semua proyek &rarr;</a>
            </div>
        </div>
    @endforeach
    {{-- Akun digabung ke kartu Status --}}
    <div class="sa-akun-mini">
        <div class="sa-mini-title">Akun</div>
    <div class="sa-akun">
        <div class="sa-role-grid">
            @foreach ($namaRole as $role => $label)
                <a href="{{ route('super-admin.akun.index', ['role' => $role, 'status' => 'aktif']) }}" class="dash-pill" @if ($role === 'super_admin') title="Termasuk akun Anda sendiri, yang tidak tampil di daftar" @endif>{{ $label }} <strong>{{ $akunPerRole[$role] ?? 0 }}</strong>@if ($role === 'super_admin') <span class="dash-sub">(termasuk Anda)</span>@endif</a>
            @endforeach
        </div>
    </div>
    </div>
</div>
</div>

<div class="card" id="pantau-admin">
    <div class="card-title">
        <i class="ti ti-users"></i> Pantau Admin
        @if ($pantauTotal)
            <span class="badge badge-netral" style="margin-left: 8px;">{{ $pantauTotal }}</span>
        @endif
        <span class="dash-sub" style="font-weight: 400;">(semua waktu, tidak ikut filter)</span>
    </div>
    <p class="dash-sub" style="margin: 0 0 4px;">Tugas tertunda tiap Admin aktif di proyeknya. Klik Ingatkan untuk kirim pengingat in-app ke Admin PIC.</p>
    @forelse ($pantauAdmin as $p)
        @include('super-admin.dashboard._pantau-admin', ['p' => $p])
    @empty
        <p class="dash-sub" style="margin: 0;">Tidak ada tugas tertunda di Admin mana pun saat ini.</p>
    @endforelse
</div>

<div class="card">
    <div class="card-title">Total saat ini <span class="dash-sub" style="font-weight: 400;">(semua periode, tidak ikut filter)</span></div>
    <div class="sa-stat-grid">
        <div class="metric-card" style="border: 1px solid var(--border-color);">
            <div class="metric-label">Honor staf belum dibayar</div>
            <div class="metric-value" style="font-size: var(--fs-lg, 18px);">{{ $rp($honorStaf->total) }}</div>
        </div>
        <div class="metric-card" style="border: 1px solid var(--border-color);">
            <div class="metric-label">Invoice belum lunas ({{ $invoiceBelumLunas->count() }})</div>
            <div class="dash-sub">Rincian ada di halaman keuangan proyek masing-masing.</div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
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
@endpush
