@extends('layouts.app')

@section('title', 'Proyek')

@section('content')
<div class="card-header-row">
    <div>
        <div style="font-size: 16px; font-weight: 600;">Proyek</div>
        <div style="font-size: var(--fs-sm); color: var(--text-secondary);">Semua proyek casting. Info lengkap, pendaftar, dan keuangan ada di detail proyek (klik barisnya).</div>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-brand">+ Buat Proyek</a>
</div>

<form method="GET" action="{{ route('admin.projects.index') }}" class="xtoolbar" role="search" id="live-form" data-live>
    <input type="search" name="q" value="{{ $cari }}" class="xtoolbar-cari" placeholder="Cari nama, kode, atau client..." aria-label="Cari proyek">
    @if ($peserta)<input type="hidden" name="peserta" value="{{ $peserta }}">@endif
    @if ($tahap)<input type="hidden" name="tahap" value="{{ $tahap }}">@endif
    <x-per-halaman :pilihan="\App\Support\PerHalaman::KARTU" :nilai="$projects->perPage()" />
    <x-filter-panel :filter="[
        $periode ? [['dari', 'sampai'], 'Periode: '.$periode[0]->translatedFormat($periode[0]->year === $periode[1]->year ? 'd M' : 'd M Y').'–'.$periode[1]->translatedFormat('d M Y')] : null,
        $bayar ? ['bayar', ['staf' => 'Honor staf belum dibayar', 'sengketa' => 'Pembayaran disengketakan'][$bayar] ?? 'Honor Extras belum ditransfer'] : null,
        request('status') ? ['status', 'Lowongan: '.(\App\Models\CastingProject::LABELS[request('status')] ?? request('status'))] : null,
        request()->boolean('urgent') ? ['urgent', 'Urgent'] : null,
        request()->boolean('tanpa_client') ? ['tanpa_client', 'Tanpa PIC/Client'] : null,
        $peserta ? ['peserta', 'Ada kandidat '.(\App\Models\ProjectApplication::LABELS[$peserta] ?? $peserta)] : null,
        $adminId ? ['admin_id', 'PIC: '.($pics->firstWhere('id', $adminId)->name ?? $adminId)] : null,
        $korlapId ? ['korlap_id', 'Korlap: '.($korlaps->firstWhere('id', $korlapId)->name ?? $korlapId)] : null,
        $clientId ? ['client_id', 'Client: '.($clients->firstWhere('id', $clientId)->name ?? $clientId)] : null,
        $tindakan ? ['tindakan', 'Perlu tindakan saja'] : null,
        ! auth()->user()->isSuperAdmin() && $semua ? ['semua', 'Pegangan: Semua (lihat saja)'] : null,
    ]">
        @unless (auth()->user()->isSuperAdmin())
            <label class="fswitch">Semua proyek (lihat saja) <input type="checkbox" name="semua" value="1" @checked($semua)></label>
        @endunless
        <x-filter-panel.grup label="PIC" name="admin_id" :opsi="['' => 'Semua'] + $pics->pluck('name', 'id')->all()" :nilai="$adminId" />
        <x-filter-panel.grup label="Korlap" name="korlap_id" :opsi="['' => 'Semua'] + $korlaps->pluck('name', 'id')->all()" :nilai="$korlapId" />
        <x-filter-panel.grup label="Client" name="client_id" :opsi="['' => 'Semua'] + $clients->pluck('name', 'id')->all()" :nilai="$clientId" />
        <x-filter-panel.grup label="Lowongan" name="status" :opsi="['' => 'Semua'] + \App\Models\CastingProject::LABELS" :nilai="request('status')" />
        <x-filter-panel.grup label="Honor" name="bayar" :opsi="['' => 'Semua', 'staf' => 'Staf belum dibayar', 'extras' => 'Extras belum ditransfer', 'sengketa' => 'Disengketakan']" :nilai="$bayar" />
        <label class="fswitch">Perlu tindakan saja <input type="checkbox" name="tindakan" value="1" @checked($tindakan)></label>
        <label class="fswitch">Urgent <input type="checkbox" name="urgent" value="1" @checked(request()->boolean('urgent'))></label>
        <label class="fswitch">Tanpa PIC/Client <input type="checkbox" name="tanpa_client" value="1" @checked(request()->boolean('tanpa_client'))></label>
        <div>
            <div class="fpanel-label">Periode shooting</div>
            <div class="fpanel-dua">
                <input type="date" name="dari" value="{{ $periode ? $periode[0]->format('Y-m-d') : request('dari') }}" aria-label="Dari tanggal">
                <span style="color: var(--text-muted);">s/d</span>
                <input type="date" name="sampai" value="{{ $periode ? $periode[1]->format('Y-m-d') : request('sampai') }}" aria-label="Sampai tanggal">
            </div>
        </div>
    </x-filter-panel>
    <select name="urut" onchange="this.form.submit()" aria-label="Urutkan" class="btn btn-sm">
        <option value="shooting" @selected($urut === 'shooting')>Urutkan: Shooting terdekat</option>
        <option value="baru" @selected($urut === 'baru')>Urutkan: Terbaru dibuat</option>
        <option value="nama" @selected($urut === 'nama')>Urutkan: Nama</option>
    </select>
</form>

<div data-live-target>

<div class="xfilter" role="tablist" aria-label="Tahap proyek">
    @foreach (['' => 'Semua'] + \App\Models\CastingProject::TAHAP as $key => $label)
        <a href="{{ route('admin.projects.index', array_merge(request()->except(['tahap', 'page']), $key === '' ? [] : ['tahap' => $key])) }}" role="tab"
           class="btn btn-sm {{ (string) $tahap === (string) $key ? 'btn-brand' : '' }}" @if ((string) $tahap === (string) $key) aria-selected="true" @endif>{{ $label }} ({{ $jumlahTahap[$key] }})</a>
    @endforeach
</div>

@if ($projects->isEmpty())
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">
        {{ array_filter(request()->except(['per', 'page'])) ? 'Tidak ada proyek yang sesuai filter.' : 'Belum ada proyek casting. Klik "+ Buat Proyek" untuk membuat yang pertama.' }}
    </div>
@else
    <div class="table-container tp-wrap" id="projects-grid">
    <table class="tabel-proyek">
        <thead>
            <tr><th>Proyek</th><th class="tp-c-client">Client</th><th class="tp-c-shoot">Shooting</th><th class="tp-c-daftar">Pendaftar</th><th class="tp-aksi" aria-label="Tindakan"></th></tr>
        </thead>
        <tbody>
        @foreach ($projects as $project)
            @php
                $tahapProyek = $project->tahap();
                $perluTindakan = $perlu[$project->id] ?? 0;
                $kuota = (int) $project->kuota;
                $ket = $project->keteranganShooting();
                $ditolak = $project->client_request_status === 'ditolak';
                $pudar = $ditolak || $tahapProyek === 'selesai';
                $dibuka = $project->status === 'dibuka';
                $tutup = $tahapProyek === 'mendatang' && ! $dibuka;
                $adaPerlu = $perluTindakan && ! $pudar;
                $pernahDibuka = $project->applications_count > 0;
            @endphp
            <tr @class(['tp-pudar' => $pudar])>
                <td class="tp-c-proyek">
                    <a href="{{ route('admin.projects.show', $project) }}" class="tp-link">{{ $project->nama_produksi }}</a>
                    <div class="tp-kode">
                        <span class="kode-proyek">{{ $project->kode_proyek }}</span>
                        @if ($project->isUrgent() && $project->status === 'dibuka')
                            <span class="badge badge-urgent">Urgent</span>
                        @endif
                    </div>
                </td>
                <td class="tp-c-client">
                    @if ($project->client)
                        <div class="tp-nama">{{ $project->namaClient() }}</div>
                        @if ($project->client->nama_perusahaan)<div class="tp-sub">{{ $project->client->name }}</div>@endif
                    @else
                        <span class="tp-kosong">Belum ada Client</span>
                    @endif
                    <div class="tp-sub">PIC {{ $project->admin?->name ?? '-' }} &middot; Korlap {{ $project->korlap()?->name ?? '-' }}</div>
                </td>
                <td class="tp-c-shoot">
                    @if ($ket)
                        <div>{{ $project->rentangShooting() }}</div>
                        <div class="tp-sub">{{ $ket }}</div>
                    @else
                        <span class="tp-kosong">Belum dijadwalkan</span>
                    @endif
                </td>
                <td class="tp-c-daftar">
                    <div>{{ $project->applications_count }} / {{ $kuota }}</div>
                    @if ($kuota > 0)
                        <div class="tp-bar"><i style="width: {{ min(100, round($project->applications_count / $kuota * 100)) }}%;"></i></div>
                    @endif
                    @if ($ditolak || $tutup || $adaPerlu || $project->client_request_status === 'menunggu_acc')
                    <span class="tp-badges">
                        @if ($ditolak)<span class="badge badge-tolak">Pengajuan ditolak</span>@endif
                        @if ($project->client_request_status === 'menunggu_acc')<span class="badge badge-pending">Menunggu ACC</span>@endif
                        @if ($tutup)<span class="badge badge-netral">Lowongan ditutup</span>@endif
                        @if ($adaPerlu)
                            <a href="{{ route('admin.projects.show', [$project, 'tab' => 'pendaftar']) }}" class="badge badge-pending tp-perlu" title="Pendaftar yang menunggu tindakan Admin">Perlu tindakan ({{ $perluTindakan }})</a>
                        @endif
                    </span>
                    @endif
                </td>
                <td class="tp-aksi"><div class="tp-aksi-isi" style="flex-wrap: wrap;">
                    <a href="{{ route('admin.projects.applicants', [$project, 'status' => $peserta]) }}" class="btn btn-sm" title="Lineup" aria-label="Lineup"><i class="ti ti-users-group" aria-hidden="true"></i> Lineup</a>
                    @can('kelola', $project)
                    <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-sm" title="Edit proyek" aria-label="Edit proyek"><i class="ti ti-pencil" aria-hidden="true"></i> Edit</a>
                    @endcan
                    @if ($dibuka && $project->share_token)
                        <button type="button" class="btn btn-sm" title="Salin link pendaftaran" aria-label="Salin link pendaftaran" data-copy-link="{{ url('/event/'.$project->share_token) }}"><i class="ti ti-link" aria-hidden="true"></i> Salin link</button>
                    @endif
 @if ($project->client_request_status === 'disetujui')
                    @can('kelola', $project)
                    @php $labelBuka = $pernahDibuka ? 'Buka lagi lowongan' : 'Buka lowongan'; @endphp
                    <x-confirm-form :action="route('admin.projects.toggle-status', $project)" method="PATCH" :message="$dibuka ? 'Tutup lowongan '.$project->nama_produksi.'? Extras tidak bisa mendaftar lagi.' : $labelBuka.' '.$project->nama_produksi.'?'">
                        <button type="submit" class="btn btn-sm" title="{{ $dibuka ? 'Tutup lowongan' : $labelBuka }}" aria-label="{{ $dibuka ? 'Tutup lowongan' : $labelBuka }}"><i class="ti {{ $dibuka ? 'ti-lock-open' : 'ti-lock' }}" aria-hidden="true"></i> {{ $dibuka ? 'Tutup' : 'Buka' }}</button>
                    </x-confirm-form>
                    @endcan
                    @endif
                </div></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
@endif
<x-pagination-bar :paginator="$projects" :pilihan="\App\Support\PerHalaman::KARTU" />
</div>

<script>
    // delegasi: tombol tetap jalan setelah daftar diganti live search
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy-link]');
        if (!btn) return;
        navigator.clipboard.writeText(btn.dataset.copyLink).then(function () {
            var ikon = btn.querySelector('i'), judul = btn.title;
            ikon.className = 'ti ti-check';
            btn.title = 'Link disalin!'; btn.setAttribute('aria-label', btn.title);
            setTimeout(function () { ikon.className = 'ti ti-link'; btn.title = judul; btn.setAttribute('aria-label', judul); }, 2000);
        });
    });
</script>
@endsection
