@extends('layouts.app')

@section('title', 'Kelola Akun · Client')

@push('styles')
<style>
    .akc-acc { border-top: 1px solid var(--border-color); }
    .akc-acc:first-child { border-top: 0; }
    .akc-acc > summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 10px; padding: 12px 4px; min-height: 44px; }
    .akc-acc > summary::-webkit-details-marker { display: none; }
    .akc-acc > summary .chevron-icon { transition: transform .15s; color: var(--text-muted); flex-shrink: 0; }
    .akc-acc[open] > summary .chevron-icon { transform: rotate(90deg); }
    .akc-main { flex: 1; min-width: 0; }
    .akc-nama { font-weight: 600; overflow-wrap: anywhere; }
    .akc-meta { font-size: var(--fs-xs); color: var(--text-muted); overflow-wrap: anywhere; }
    .akc-angka { display: flex; gap: 6px; flex-wrap: wrap; flex-shrink: 0; }
    .akc-isi { padding: 0 0 10px 26px; }
    .akc-isi .akc-acc > summary { padding: 10px 4px; }
    .akc-ex { list-style: none; margin: 0 0 8px; padding: 0 0 0 26px; }
    .akc-ex li { display: flex; align-items: center; gap: 10px; padding: 6px 0; border-top: 1px dashed var(--border-color); flex-wrap: wrap; }
    .akc-ex li:first-child { border-top: 0; }
    .akc-foto { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: var(--bg-card-hover); display: inline-flex; align-items: center; justify-content: center; font-size: var(--fs-xs); font-weight: 700; color: var(--text-muted); }
    .akc-ex a { font-weight: 600; color: inherit; overflow-wrap: anywhere; }
    .akc-peran { font-size: var(--fs-xs); color: var(--text-muted); flex: 1; min-width: 80px; }
    .akc-waktu { font-size: var(--fs-xs); color: var(--text-muted); white-space: nowrap; }
    .akc-kosong { font-size: var(--fs-sm); color: var(--text-muted); margin: 4px 0 10px; }
    @media (max-width: 560px) {
        .akc-acc > summary { flex-wrap: wrap; }
        .akc-angka { flex-basis: 100%; padding-left: 26px; }
        .akc-isi { padding-left: 10px; }
        .akc-ex { padding-left: 10px; }
    }
</style>
@endpush

@section('content')
@php
    $sisi = ['menunggu' => ['Menunggu keputusan', 'badge-pending'], 'lock' => ['Lock', 'badge-aktif'], 'ditolak' => ['Ditolak', 'badge-tolak']];
@endphp
<p style="font-size: var(--fs-sm); color: var(--text-secondary); margin: 0 0 var(--space-3);">Riwayat Client: proyek & Extras yang diajukan. Akun Client dikelola Super Admin.</p>

@include('partials.keputusan-client', ['keputusan' => $keputusan])

<form method="GET" action="{{ route('admin.akun.client') }}" class="xtoolbar" id="live-form" data-live style="margin-top: var(--space-3);">
    <input type="search" name="q" value="{{ $q }}" class="xtoolbar-cari" placeholder="Cari nama, perusahaan, email Client…" aria-label="Cari Client">
    <x-per-halaman :pilihan="\App\Support\PerHalaman::TABEL" :nilai="$clients->perPage()" />
</form>

<div data-live-target>
<div class="card" style="padding-top: 4px; padding-bottom: 4px;">
    @forelse ($clients as $c)
        <details class="akc-acc">
            <summary>
                <i class="ti ti-chevron-right chevron-icon"></i>
                <div class="akc-main">
                    <div class="akc-nama">{{ $c->name }}</div>
                    <div class="akc-meta">{{ $c->nama_perusahaan ?: 'Perusahaan belum diisi' }} · {{ $c->proyek_client_count }} proyek @if ($p = $c->proyekClient->first())· terakhir: {{ $p->nama_produksi }}@endif</div>
                </div>
            </summary>
            <div class="akc-isi">
                @forelse ($c->proyekClient as $p)
                    @php
                        $n = $p->applications->countBy(fn ($a) => $a->sisiClient());
                        $tahap = $p->tahap();
                    @endphp
                    <details class="akc-acc">
                        <summary>
                            <i class="ti ti-chevron-right chevron-icon"></i>
                            <div class="akc-main">
                                <div class="akc-nama">{{ $p->nama_produksi }}</div>
                                <div class="akc-meta">{{ $p->kode_proyek }} · shooting {{ $p->rentangShooting() }}@if ($tahap) · <span class="badge {{ \App\Models\CastingProject::TAHAP_BADGES[$tahap] }}">{{ \App\Models\CastingProject::TAHAP[$tahap] }}</span>@endif</div>
                            </div>
                            <div class="akc-angka">
                                <span class="badge badge-netral" title="Diajukan ke Client">{{ $p->applications->count() }} diajukan</span>
                                <span class="badge badge-aktif">{{ $n['lock'] ?? 0 }} lock</span>
                                <span class="badge badge-tolak">{{ $n['ditolak'] ?? 0 }} ditolak</span>
                            </div>
                        </summary>
                        @if ($p->applications->isEmpty())
                            <p class="akc-kosong" style="padding-left: 26px;">Belum ada Extras yang diajukan.</p>
                        @else
                            <ul class="akc-ex">
                                @foreach ($p->applications as $a)
                                    @php $ex = $a->extras; $s = $a->sisiClient(); $r = $a->reviewClientTerakhir; @endphp
                                    <li>
                                        @if ($ex?->foto_profil_path)
                                            <img src="{{ route('extras.media.foto', $ex) }}" alt="" class="akc-foto" loading="lazy">
                                        @else
                                            <span class="akc-foto" aria-hidden="true">{{ strtoupper(mb_substr($ex?->user?->username ?? '?', 0, 2)) }}</span>
                                        @endif
                                        @if ($ex?->user)
                                            <a href="{{ route('admin.extras.profil', $ex->user_id) }}" data-profil-modal>{{ '@'.($ex->user->username ?? 'tanpa-username') }}</a>
                                        @endif
                                        <span class="akc-peran">{{ $a->getKarakter() }}</span>
                                        <span class="badge {{ $sisi[$s][1] }}">{{ $sisi[$s][0] }}@if ($s === 'lock' && $r?->grade_client) · Grade {{ $r->grade_client }}@endif</span>
                                        @if ($s !== 'menunggu' && $r)
                                            <time class="akc-waktu" datetime="{{ $r->created_at->toIso8601String() }}" title="{{ $r->created_at->translatedFormat('d M Y H:i') }}">{{ $r->created_at->locale('id')->diffForHumans() }}</time>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </details>
                @empty
                    <p class="akc-kosong">Belum ada proyek.</p>
                @endforelse
            </div>
        </details>
    @empty
        <p style="text-align: center; color: var(--text-muted); padding: 24px 0; margin: 0;">Tidak ada Client yang cocok.</p>
    @endforelse
</div>

<x-pagination-bar :paginator="$clients" :pilihan="\App\Support\PerHalaman::TABEL" />
</div>
@endsection
