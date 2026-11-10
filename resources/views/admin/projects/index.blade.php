@extends('layouts.app')

@php
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $isTabMenungguKeputusan = $isSuperAdmin && $tahap === 'menunggu_acc';

    // CX.1: Deretan tab tahap - tab "Semua" sebagai default di posisi paling kiri,
    // lalu "Menunggu Keputusan" (khusus Super Admin), Mendatang, Berjalan, Selesai.
    $tabsTahap = [
        '' => ['label' => 'Semua', 'jumlah' => $jumlahTahap[''] ?? 0],
    ];
    if ($isSuperAdmin) {
        $tabsTahap['menunggu_acc'] = ['label' => 'Menunggu Keputusan', 'jumlah' => $menungguKeputusanCount];
    }
    $tabsTahap['mendatang'] = ['label' => 'Mendatang', 'jumlah' => $jumlahTahap['mendatang'] ?? 0];
    $tabsTahap['berjalan'] = ['label' => 'Berjalan', 'jumlah' => $jumlahTahap['berjalan'] ?? 0];
    $tabsTahap['selesai'] = ['label' => 'Selesai', 'jumlah' => $jumlahTahap['selesai'] ?? 0];
@endphp

@section('title', $isTabMenungguKeputusan ? 'Persetujuan' : 'Proyek')

@push('styles')
<style>
.tp-wrap.sembunyi-selesai tr:has(.tp-selesai) { display: none; }
.tabel-proyek th.tp-c-shoot { width: 22%; }
.tabel-proyek th.tp-c-daftar { width: 180px; }
.tabel-proyek th.tp-aksi { width: 200px; }
</style>
@endpush

@section('content')

<div class="card-header-row">
    <div>
        <div style="font-size: 18px; font-weight: 700;">{{ $isTabMenungguKeputusan ? 'Persetujuan' : 'Proyek' }}</div>
        <div style="font-size: var(--fs-sm); color: var(--text-secondary);">
            {{ $isTabMenungguKeputusan
                ? 'Pengajuan proyek dan akun yang menunggu keputusan Super Admin.'
                : 'Semua proyek casting. Info lengkap, pendaftar, dan keuangan ada di detail proyek (klik barisnya).' }}
        </div>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-brand">+ Buat Proyek</a>
</div>

<div data-live-target>

{{-- Baris 1: Filter Tab Tahap Proyek --}}
<div class="xfilter" role="tablist" aria-label="Tahap proyek" style="margin-bottom: 12px; display: flex; gap: 6px; flex-wrap: wrap;">
    @foreach ($tabsTahap as $key => $info)
        <a href="{{ route('admin.projects.index', array_merge(request()->except(['tahap', 'page']), $key === '' ? [] : ['tahap' => $key])) }}" role="tab"
           class="btn btn-sm {{ (string) $tahap === (string) $key ? 'btn-brand' : '' }}" @if ((string) $tahap === (string) $key) aria-selected="true" @endif>{{ $info['label'] }} ({{ $info['jumlah'] }})</a>
    @endforeach
</div>

{{-- Baris 2: Search Bar & Filter Toolbar --}}
<div class="xtoolbar xtoolbar-proyek" style="margin-bottom: 16px;">
    <form method="GET" action="{{ route('admin.projects.index') }}" class="xtoolbar-search-area" role="search" id="live-form" style="display: flex; gap: 10px; align-items: center; width: 100%; margin: 0;" data-live>
        <div style="position: relative; flex: 1; min-width: 200px;">
            <i class="ti ti-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
            <input type="search" name="q" value="{{ $cari }}" class="xtoolbar-cari" id="pu-search-input" placeholder="Cari nama, kode, atau client..." aria-label="Cari proyek" style="margin: 0; width: 100%; padding-left: 38px; height: 42px; border-radius: var(--radius-full, 9999px);">
        </div>
        @if ($tahap)<input type="hidden" name="tahap" value="{{ $tahap }}">@endif
        @unless ($isTabMenungguKeputusan)
            @if ($peserta)<input type="hidden" name="peserta" value="{{ $peserta }}">@endif
            <x-filter-panel :filter="[
                $urut && $urut !== 'shooting' ? ['urut', 'Urutkan: '.['baru' => 'Terbaru dibuat', 'nama' => 'Nama'][$urut]] : null,
                $peran ? ['peran', 'Peran: '.['pic' => 'Hanya PIC saja', 'korlap' => 'Hanya Korlap saja', 'client' => 'Hanya Client saja', 'tanpa_pic' => 'Tanpa PIC', 'tanpa_client' => 'Tanpa PIC/Client'][$peran]] : null,
                $periode ? [['dari', 'sampai'], 'Periode: '.$periode[0]->translatedFormat($periode[0]->year === $periode[1]->year ? 'd M' : 'd M Y').'–'.$periode[1]->translatedFormat('d M Y')] : null,
                $bayar ? ['bayar', ['staf' => 'Honor staf belum dibayar', 'sengketa' => 'Pembayaran disengketakan'][$bayar] ?? 'Honor Extras belum ditransfer'] : null,
                request('status') ? ['status', 'Lowongan: '.(\App\Models\CastingProject::LABELS[request('status')] ?? request('status'))] : null,
                request()->boolean('urgent') ? ['urgent', 'Urgent'] : null,
                request()->boolean('tanpa_client') && ! $peran ? ['tanpa_client', 'Tanpa PIC/Client'] : null,
                $peserta ? ['peserta', 'Ada kandidat '.(\App\Models\ProjectApplication::LABELS[$peserta] ?? $peserta)] : null,
                $adminId ? ['admin_id', 'PIC: '.($pics->firstWhere('id', $adminId)->name ?? $adminId)] : null,
                $korlapId ? ['korlap_id', 'Korlap: '.($korlaps->firstWhere('id', $korlapId)->name ?? $korlapId)] : null,
                $clientId ? ['client_id', 'Client: '.($clients->firstWhere('id', $clientId)->name ?? $clientId)] : null,
                $tindakan ? ['tindakan', 'Perlu tindakan saja'] : null,
                ! auth()->user()->isSuperAdmin() && $semua ? ['semua', 'Pegangan: Semua (lihat saja)'] : null,
            ]">
                <x-filter-panel.grup label="Urutkan" name="urut" :opsi="[
                    'shooting' => 'Shooting terdekat',
                    'baru' => 'Terbaru dibuat',
                    'nama' => 'Nama (A-Z)',
                ]" :nilai="$urut" />

                <x-filter-panel.grup label="Jenis Peran" name="peran" :opsi="[
                    '' => 'Semua peran',
                    'pic' => 'Hanya PIC saja',
                    'korlap' => 'Hanya Korlap saja',
                    'client' => 'Hanya Client saja',
                    'tanpa_client' => 'Tanpa PIC/Client',
                ]" :nilai="$peran" />

                <label class="fswitch">Perlu tindakan saja <input type="checkbox" name="tindakan" value="1" @checked($tindakan)></label>
                <label class="fswitch">Urgent <input type="checkbox" name="urgent" value="1" @checked(request()->boolean('urgent'))></label>
                @unless (auth()->user()->isSuperAdmin())
                    <label class="fswitch">Semua proyek (lihat saja) <input type="checkbox" name="semua" value="1" @checked($semua)></label>
                @endunless
                <div>
                    <div class="fpanel-label">Periode shooting</div>
                    <div class="fpanel-dua">
                        <input type="date" name="dari" value="{{ $periode ? $periode[0]->format('Y-m-d') : request('dari') }}" aria-label="Dari tanggal">
                        <span style="color: var(--text-muted);">s/d</span>
                        <input type="date" name="sampai" value="{{ $periode ? $periode[1]->format('Y-m-d') : request('sampai') }}" aria-label="Sampai tanggal">
                    </div>
                </div>
            </x-filter-panel>
        @endunless
    </form>
</div>

@if ($isTabMenungguKeputusan)
    @include('admin.projects._persetujuan')
@else
@if ($projects->isEmpty())
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">
        {{ array_filter(request()->except(['per', 'page']))
            ? 'Tidak ada proyek yang sesuai filter.'
            : ($isSuperAdmin
                ? 'Belum ada proyek casting. Klik "+ Buat Proyek" untuk membuat yang pertama.'
                : 'Belum ada proyek yang kamu kelola. Proyek yang sebelumnya kamu pegang mungkin sudah dialihkan.') }}
    </div>
@else
    <div class="table-container tp-wrap @if (! $tahap) sembunyi-selesai @endif" id="projects-grid">
    <table class="tabel-proyek">
        <thead>
            <tr><th>Proyek</th><th class="tp-c-shoot">Shooting</th><th class="tp-c-daftar">Pendaftar</th><th class="tp-aksi" aria-label="Tindakan"></th></tr>
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
                    <a href="{{ route('admin.projects.show', $project) }}" class="tp-link" onclick="event.preventDefault(); document.getElementById('modal-proyek-{{ $project->id }}').showModal()" title="Klik untuk ringkasan proyek">{{ $project->nama_produksi }}</a>
                    <div class="tp-kode">
                        <span class="kode-proyek">{{ $project->kode_proyek }}</span>
                        @if ($project->isUrgent() && $project->status === 'dibuka')
                            <span class="badge badge-urgent">Urgent</span>
                        @endif
                    </div>

                    @include('partials.modal-ringkasan-proyek', ['project' => $project])
                </td>
                <td class="tp-c-shoot">
                    @if ($ket)
                        <div>{{ $project->rentangShooting() }}</div>
                        <div class="tp-sub @if ($ket === 'selesai') tp-selesai @endif">{{ $ket }}</div>
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
                <td class="tp-aksi"><div class="tp-aksi-isi" style="flex-wrap: wrap; gap: 4px;">
                    <a href="{{ route('admin.projects.applicants', [$project, 'status' => $peserta]) }}" class="btn btn-sm btn-brand" title="Lineup" aria-label="Lineup"><i class="ti ti-users-group" aria-hidden="true"></i> Lineup</a>
                    @can('kelola', $project)
                    <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-sm btn-tonal" title="Edit proyek" aria-label="Edit proyek"><i class="ti ti-pencil" aria-hidden="true"></i> Edit</a>
                    @endcan
                    @if ($dibuka && $project->share_token)
                        <button type="button" class="btn btn-sm btn-tonal" title="Salin link pendaftaran" aria-label="Salin link pendaftaran" data-copy-link="{{ url('/event/'.$project->share_token) }}"><i class="ti ti-link" aria-hidden="true"></i> Salin link</button>
                    @endif
 @if ($project->client_request_status === 'disetujui')
                    @can('kelola', $project)
                    @php $labelBuka = $pernahDibuka ? 'Buka lagi lowongan' : 'Buka lowongan'; @endphp
                    <x-confirm-form :action="route('admin.projects.toggle-status', $project)" method="PATCH" :message="$dibuka ? 'Tutup lowongan '.$project->nama_produksi.'? Extras tidak bisa mendaftar lagi.' : $labelBuka.' '.$project->nama_produksi.'?'">
                        <button type="submit" class="btn btn-sm btn-tonal" title="{{ $dibuka ? 'Tutup lowongan' : $labelBuka }}" aria-label="{{ $dibuka ? 'Tutup lowongan' : $labelBuka }}"><i class="ti {{ $dibuka ? 'ti-lock-open' : 'ti-lock' }}" aria-hidden="true"></i> {{ $dibuka ? 'Tutup' : 'Buka' }}</button>
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
@endif
</div>

<script>
    function toggleSelesai() {
        var grid = document.getElementById('projects-grid');
        if (!grid) return;
        var sembunyi = grid.classList.toggle('sembunyi-selesai');
        var teks = document.getElementById('teks-toggle-selesai');
        var ikon = document.getElementById('ikon-toggle-selesai');
        if (teks) teks.textContent = sembunyi ? 'Tampilkan selesai' : 'Sembunyikan selesai';
        if (ikon) ikon.className = sembunyi ? 'ti ti-eye' : 'ti ti-eye-off';
    }

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

    // tutup dialog saat klik backdrop
    document.addEventListener('click', function (e) {
        if (e.target.tagName === 'DIALOG' && e.target.classList.contains('modal-proyek-dialog')) {
            e.target.close();
        }
    });
</script>
@endsection
