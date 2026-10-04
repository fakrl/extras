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
    <input type="search" name="q" value="{{ $cari }}" class="xtoolbar-cari" placeholder="Cari nama produksi atau client..." aria-label="Cari proyek">
    @if ($peserta)<input type="hidden" name="peserta" value="{{ $peserta }}">@endif
    @if ($tahap)<input type="hidden" name="tahap" value="{{ $tahap }}">@endif
    <x-per-halaman :pilihan="\App\Support\PerHalaman::KARTU" :nilai="$projects->perPage()" />
    <x-filter-panel :filter="[
        $periode ? [['dari', 'sampai'], 'Periode: '.$periode[0]->translatedFormat($periode[0]->year === $periode[1]->year ? 'd M' : 'd M Y').'–'.$periode[1]->translatedFormat('d M Y')] : null,
        $bayar ? ['bayar', $bayar === 'staf' ? 'Honor staf belum dibayar' : 'Honor Extras belum ditransfer'] : null,
        request('status') ? ['status', 'Lowongan: '.(\App\Models\CastingProject::LABELS[request('status')] ?? request('status'))] : null,
        request()->boolean('urgent') ? ['urgent', 'Urgent'] : null,
        request()->boolean('tanpa_client') ? ['tanpa_client', 'Client belum diisi'] : null,
        $peserta ? ['peserta', 'Ada kandidat '.(\App\Models\ProjectApplication::LABELS[$peserta] ?? $peserta)] : null,
    ]">
        <x-filter-panel.grup label="Lowongan" name="status" :opsi="['' => 'Semua'] + \App\Models\CastingProject::LABELS" :nilai="request('status')" />
        <x-filter-panel.grup label="Honor" name="bayar" :opsi="['' => 'Semua', 'staf' => 'Staf belum dibayar', 'extras' => 'Extras belum ditransfer']" :nilai="$bayar" />
        <label class="fswitch">Urgent <input type="checkbox" name="urgent" value="1" @checked(request()->boolean('urgent'))></label>
        <label class="fswitch">Client belum diisi <input type="checkbox" name="tanpa_client" value="1" @checked(request()->boolean('tanpa_client'))></label>
        <div>
            <div class="fpanel-label">Periode shooting</div>
            <div class="fpanel-dua">
                <input type="date" name="dari" value="{{ $periode ? $periode[0]->format('Y-m-d') : request('dari') }}" aria-label="Dari tanggal">
                <span style="color: var(--text-muted);">s/d</span>
                <input type="date" name="sampai" value="{{ $periode ? $periode[1]->format('Y-m-d') : request('sampai') }}" aria-label="Sampai tanggal">
            </div>
        </div>
    </x-filter-panel>
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
            <tr><th>Proyek</th><th>Client</th><th>Shooting</th><th>Pendaftar</th><th style="text-align: right;">Tindakan</th></tr>
        </thead>
        <tbody>
        @foreach ($projects as $project)
            @php
                $tahapProyek = $project->tahap();
                $perluTindakan = $perlu[$project->id] ?? 0;
                $kuota = (int) $project->kuota;
                $ket = $project->keteranganShooting();
            @endphp
            <tr>
                <td class="tp-c-proyek">
                    <a href="{{ route('admin.projects.show', $project) }}" class="tp-link">{{ $project->nama_produksi }}</a>
                    <div class="tp-kode">
                        <span class="kode-proyek">{{ $project->kode_proyek }}</span>
                        @if ($project->isUrgent())
                            <span class="badge badge-tolak">Urgent</span>
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
                </td>
                <td class="tp-aksi"><div class="tp-aksi-isi">
                @if ($project->client_request_status === 'ditolak')<span class="badge badge-tolak">Ditolak</span>@endif
                @if ($tahapProyek === 'mendatang' && $project->status === 'ditutup')<span class="badge badge-netral">Lowongan ditutup</span>@endif
                @if ($perluTindakan)
                    <a href="{{ route('admin.projects.show', [$project, 'tab' => 'pendaftar']) }}" class="badge badge-pending" style="text-decoration: none;" title="Pendaftar yang menunggu tindakan Admin">Perlu tindakan ({{ $perluTindakan }})</a>
                @endif
                <details class="tp-menu">
                    <summary aria-label="Menu proyek" style="list-style: none; cursor: pointer; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card);">
                        <i class="ti ti-dots-vertical"></i>
                    </summary>
                    <div style="position: absolute; right: 0; top: 36px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; min-width: 160px; box-shadow: 0 4px 12px rgba(0,0,0,.12); padding: 4px 0; z-index: 20;">
                        <a href="{{ route('admin.projects.show', $project) }}" style="display: block; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">Detail Proyek</a>
                        <a href="{{ route('admin.projects.applicants', [$project, 'status' => $peserta]) }}" style="display: block; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">Lineup</a>
                        <a href="{{ route('admin.projects.edit', $project) }}" style="display: block; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">Edit Proyek</a>
                        @if ($project->status === 'dibuka' && $project->share_token)
                            <button type="button" style="display: block; width: 100%; padding: 8px 14px; font-size: 13px; text-align: left; background: none; border: none; color: var(--text-primary); cursor: pointer;" data-copy-link="{{ url('/event/'.$project->share_token) }}">Copy Link Pendaftaran</button>
                        @endif
                        <form method="POST" action="{{ route('admin.projects.toggle-status', $project) }}">
                            @csrf @method('PATCH')
                            <button style="display: block; width: 100%; padding: 8px 14px; font-size: 13px; text-align: left; background: none; border: none; color: var(--text-primary); cursor: pointer;">
                                {{ $project->status === 'dibuka' ? 'Tutup Lowongan' : 'Buka Lagi' }}
                            </button>
                        </form>
                        <a href="{{ route('invoices.show', $project) }}" style="display: block; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">Invoice</a>
                    </div>
                </details>
                <i class="ti ti-chevron-right tp-chevron" aria-hidden="true"></i>
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
            var original = btn.textContent;
            btn.textContent = 'Link disalin!';
            setTimeout(function () { btn.textContent = original; }, 2000);
        });
    });
</script>
@endsection
