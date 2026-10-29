<div class="pu-tabs" role="tablist" aria-label="Grup persetujuan">
    @foreach (['pengajuan-baru' => ['Pengajuan baru', $pengajuanBaru->count()], 'pengajuan-revisi' => ['Diajukan ulang', $pengajuanRevisi->count()], 'tanpa-pic' => ['Tanpa PIC', $tanpaPic->count()], 'akun' => ['Akun perlu perhatian', $akunPerhatianList->count()]] as $key => [$label, $jumlah])
        <button type="button" class="metric-card pu-tab" role="tab" id="pu-tab-{{ $key }}" aria-controls="pu-panel-{{ $key }}" aria-selected="{{ $key === $tabAwalPersetujuan ? 'true' : 'false' }}" tabindex="{{ $key === $tabAwalPersetujuan ? 0 : -1 }}">
            <div class="metric-label">{{ $label }}</div>
            <div class="metric-value">{{ $jumlah }}</div>
        </button>
    @endforeach
</div>

<div class="card" role="tabpanel" id="pu-panel-pengajuan-baru" aria-labelledby="pu-tab-pengajuan-baru" @if ($tabAwalPersetujuan !== 'pengajuan-baru') hidden @endif>
    <div class="card-title">Pengajuan Client menunggu keputusan</div>
    @forelse ($pengajuanBaru as $req)
        @include('super-admin.dashboard._pengajuan', ['req' => $req, 'label' => 'Menunggu ACC'])
    @empty
        <x-kosong teks="Tidak ada pengajuan baru." />
    @endforelse
</div>

<div class="card" role="tabpanel" id="pu-panel-pengajuan-revisi" aria-labelledby="pu-tab-pengajuan-revisi" @if ($tabAwalPersetujuan !== 'pengajuan-revisi') hidden @endif>
    <div class="card-title">Pengajuan sudah direvisi Client</div>
    @forelse ($pengajuanRevisi as $req)
        @include('super-admin.dashboard._pengajuan', ['req' => $req, 'label' => 'Diajukan ulang'])
    @empty
        <x-kosong teks="Tidak ada pengajuan hasil revisi." />
    @endforelse
</div>

<div class="card" role="tabpanel" id="pu-panel-tanpa-pic" aria-labelledby="pu-tab-tanpa-pic" @if ($tabAwalPersetujuan !== 'tanpa-pic') hidden @endif>
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

<div class="card" role="tabpanel" id="pu-panel-akun" aria-labelledby="pu-tab-akun" @if ($tabAwalPersetujuan !== 'akun') hidden @endif>
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

<script>
(function () {
    var tabs = [].slice.call(document.querySelectorAll('.pu-tab'));
    if (!tabs.length) return;
    var pilih = function (t) {
        tabs.forEach(function (x) {
            var aktif = x === t;
            x.setAttribute('aria-selected', aktif);
            x.tabIndex = aktif ? 0 : -1;
            var panel = document.getElementById(x.getAttribute('aria-controls'));
            if (panel) panel.hidden = !aktif;
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
