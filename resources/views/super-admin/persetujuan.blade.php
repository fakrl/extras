@extends('layouts.app')

@section('title', 'Persetujuan')

@push('styles')
<style>
.sa-head-judul { font-size: 18px; font-weight: 700; margin: 0 0 16px; }
.pu-tab { width: 100%; text-align: left; font: inherit; color: inherit; cursor: pointer; border: 1px solid var(--border-color); min-height: 44px; }
.pu-tab:hover { border-color: var(--accent); }
.pu-tab[aria-selected=true] { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
.pu-tab:focus-visible { outline: 2px solid var(--accent-strong); outline-offset: 2px; }
.pu-tabs { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
@media (min-width: 861px) { .pu-tabs { grid-template-columns: repeat(4, 1fr); } }
</style>
@endpush

@section('content')
<h1 class="sa-head-judul">Persetujuan</h1>

<div class="pu-tabs" role="tablist" aria-label="Grup persetujuan">
    @foreach (['pengajuan-baru' => ['Pengajuan baru', $pengajuanBaru->count()], 'pengajuan-revisi' => ['Diajukan ulang', $pengajuanRevisi->count()], 'tanpa-pic' => ['Tanpa PIC', $tanpaPic->count()], 'akun' => ['Akun perlu perhatian', $akunPerhatianList->count()]] as $key => [$label, $jumlah])
        <button type="button" class="metric-card pu-tab" role="tab" id="pu-tab-{{ $key }}" aria-controls="pu-panel-{{ $key }}" aria-selected="{{ $key === $tabAwal ? 'true' : 'false' }}" tabindex="{{ $key === $tabAwal ? 0 : -1 }}">
            <div class="metric-label">{{ $label }}</div>
            <div class="metric-value">{{ $jumlah }}</div>
        </button>
    @endforeach
</div>

<div class="card" role="tabpanel" id="pu-panel-pengajuan-baru" aria-labelledby="pu-tab-pengajuan-baru" @if ($tabAwal !== 'pengajuan-baru') hidden @endif>
    <div class="card-title">Pengajuan Client menunggu keputusan</div>
    @forelse ($pengajuanBaru as $req)
        @include('super-admin.dashboard._pengajuan', ['req' => $req, 'label' => 'Menunggu ACC'])
    @empty
        <x-kosong teks="Tidak ada pengajuan baru." />
    @endforelse
</div>

<div class="card" role="tabpanel" id="pu-panel-pengajuan-revisi" aria-labelledby="pu-tab-pengajuan-revisi" @if ($tabAwal !== 'pengajuan-revisi') hidden @endif>
    <div class="card-title">Pengajuan sudah direvisi Client</div>
    @forelse ($pengajuanRevisi as $req)
        @include('super-admin.dashboard._pengajuan', ['req' => $req, 'label' => 'Diajukan ulang'])
    @empty
        <x-kosong teks="Tidak ada pengajuan hasil revisi." />
    @endforelse
</div>

<div class="card" role="tabpanel" id="pu-panel-tanpa-pic" aria-labelledby="pu-tab-tanpa-pic" @if ($tabAwal !== 'tanpa-pic') hidden @endif>
    <div class="card-title">Proyek disetujui belum punya PIC</div>
    @forelse ($tanpaPic as $project)
        <div class="dash-row">
            <div>
                <span class="badge badge-pending">PIC/Client belum diisi</span>
                <strong>{{ $project->nama_produksi }}</strong>
                <div class="dash-sub">{{ $project->client?->name ?? 'Client belum diisi' }}</div>
            </div>
            @if ($project->admin_id)
                <a href="{{ route('admin.projects.edit', $project) }}" class="btn">Buka</a>
            @else
                <button type="button" class="btn" onclick="document.getElementById('pu-pic-{{ $project->id }}').showModal()">Tetapkan PIC</button>
            @endif
        </div>
        @unless ($project->admin_id)
            @include('super-admin.dashboard._tetapkan-pic-dialog', ['project' => $project, 'idPrefix' => 'pu-pic-'])
        @endunless
    @empty
        <x-kosong teks="Semua proyek disetujui sudah punya PIC." />
    @endforelse
</div>

<div class="card" role="tabpanel" id="pu-panel-akun" aria-labelledby="pu-tab-akun" @if ($tabAwal !== 'akun') hidden @endif>
    <div class="card-title">Akun perlu perhatian</div>
    @forelse ($akunPerhatianList as $user)
        <a href="{{ route('super-admin.admins.show', $user) }}" class="dash-row">
            <div><strong>{{ $user->name }}</strong><div class="dash-sub">Belum ganti password lebih dari 7 hari</div></div>
            <span class="dash-sub">Buka &rarr;</span>
        </a>
    @empty
        <x-kosong teks="Tidak ada akun yang perlu perhatian." />
    @endforelse
</div>
@endsection

@push('scripts')
<script>
(function () {
    var tabs = [].slice.call(document.querySelectorAll('.pu-tab'));
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
