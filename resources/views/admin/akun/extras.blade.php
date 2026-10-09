@extends('layouts.app')

@section('title', 'Kelola Akun · Extras')

@push('styles')
<style>
    .tampil-toggle { display: inline-flex; border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; flex-shrink: 0; }
    .tampil-toggle a { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); text-decoration: none; font-size: 18px; }
    .tampil-toggle a[aria-current] { background: var(--bg-nav-active); color: var(--accent-strong); }
    .akx-info { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; font-size: var(--fs-xs); color: var(--text-muted); }
    .akx-foto { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: var(--bg-card-hover); display: inline-flex; align-items: center; justify-content: center; font-size: var(--fs-xs); font-weight: 700; color: var(--text-muted); }
    .akx-nama { display: flex; align-items: center; gap: 10px; min-width: 190px; }
    .akx-nama a { font-weight: 600; color: inherit; overflow-wrap: anywhere; }
    .akx-nama small { display: block; color: var(--text-muted); font-size: var(--fs-xs); overflow-wrap: anywhere; }
    .akx-fav { margin: 0; }
    .akx-fav button { background: none; border: 0; cursor: pointer; font-size: 18px; width: 40px; height: 40px; color: var(--text-muted); padding: 0; }
</style>
@endpush

@section('content')
@php
    $tagNama = $tagGroups->flatten()->pluck('nama', 'id');
    $dasar = \Illuminate\Support\Arr::except(request()->query(), ['tampil', 'per', 'page']);
@endphp

@if (($mangkrakCount ?? 0) > 0)
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 18px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <div style="font-weight: 600; font-size: 13.5px;"><i class="ti ti-trash"></i> Pembersihan Akun Mangkrak (>30 Hari)</div>
            <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">
                Ditemukan <strong>{{ $mangkrakCount }}</strong> akun extras yang terdaftar lebih dari 30 hari lalu dengan profil tidak lengkap dan 0 riwayat apply proyek.
            </div>
        </div>
        <form method="POST" action="{{ route('admin.users.prune') }}" onsubmit="return confirm('Yakin ingin menghapus {{ $mangkrakCount }} akun extras mangkrak (>30 hari tanpa kelengkapan profil & pendaftaran)?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-danger-outline">Bersihkan {{ $mangkrakCount }} Akun Mangkrak</button>
        </form>
    </div>
@endif

<form method="GET" action="{{ route('admin.akun.extras') }}" class="xtoolbar" id="live-form" data-live>
    <input type="search" name="q" value="{{ $f['q'] }}" class="xtoolbar-cari" placeholder="Cari nama, username, email, WA…" aria-label="Cari Extras">
    @if ($daftar)<input type="hidden" name="tampil" value="daftar">@endif
    <div class="tampil-toggle" role="group" aria-label="Tampilan">
        <a href="{{ \App\Support\FilterAktif::url(request(), $dasar) }}" title="Kartu" aria-label="Tampilan kartu" @if (! $daftar) aria-current="true" @endif><i class="ti ti-layout-grid"></i></a>
        <a href="{{ \App\Support\FilterAktif::url(request(), $dasar + ['tampil' => 'daftar']) }}" title="Daftar" aria-label="Tampilan daftar" @if ($daftar) aria-current="true" @endif><i class="ti ti-list"></i></a>
    </div>
    <x-per-halaman :pilihan="$perPilihan" :nilai="$extras->perPage()" />
    <x-filter-panel :filter="[
        $f['status'] ? ['status', 'Status: '.ucfirst($f['status'])] : null,
        $f['sedang_aktif'] ? ['sedang_aktif', 'Sedang aktif di proyek'] : null,
        $f['akan_dihapus'] ? ['akan_dihapus', 'Akan dihapus'] : null,
        $f['favorit'] ? ['favorit', '⭐ Favorit'] : null,
        $f['urut'] ? ['urut', 'Urut: '.\App\Support\FilterAkun::URUT[$f['urut']]] : null,
        $f['grade'] ? ['grade', 'Grade: '.($f['grade'] === 'belum' ? 'Belum' : $f['grade'])] : null,
        ...array_map(fn ($id) => ['tag', 'Tag: #'.($tagNama[$id] ?? $id), $id], $f['tag']),
    ]">
        <x-filter-panel.grup label="Status" name="status" :opsi="['' => 'Semua', 'aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" :nilai="$f['status']" />
        <label class="fswitch">Sedang aktif di proyek <input type="checkbox" name="sedang_aktif" value="1" @checked($f['sedang_aktif'])></label>
        <label class="fswitch">⭐ Favorit <input type="checkbox" name="favorit" value="1" @checked($f['favorit'])></label>
        <label class="fswitch">Akan dihapus (sudah diperingatkan) <input type="checkbox" name="akan_dihapus" value="1" @checked($f['akan_dihapus'])></label>
        <x-filter-panel.grup label="Urutkan" name="urut" :opsi="\App\Support\FilterAkun::URUT" :nilai="$f['urut']" />
        <x-filter-panel.grup label="Grade" name="grade" :opsi="['' => 'Semua', 'A' => 'A', 'B' => 'B', 'C' => 'C', 'belum' => 'Belum']" :nilai="$f['grade']" />
        <div class="fpanel-sub">
            <div class="fpanel-judul">Tag</div>
            @foreach ($tagGroups as $grup => $tags)
                @php $n = $tags->whereIn('id', $f['tag'])->count(); @endphp
                <details class="fpanel-acc" data-k="tag-{{ $grup }}">
                    <summary>{{ $grup }}@if ($n) ({{ $n }})@endif</summary>
                    <x-filter-panel.grup label="" name="tag[]" :opsi="$tags->mapWithKeys(fn ($t) => [$t->id => '#'.$t->nama])->all()" :nilai="$f['tag']" multi />
                </details>
            @endforeach
            @if ($tagRapikan->isNotEmpty())<button type="button" class="fpanel-rapikan" data-rapikan-tag>Rapikan tag ({{ $tagRapikan->count() }})</button>@endif
            <p class="xfilter-note">Menampilkan yang punya <strong>semua</strong> tag terpilih</p>
        </div>
    </x-filter-panel>
</form>
@if ($tagRapikan->isNotEmpty())
    @include('admin.tags.rapikan', ['tags' => $tagRapikan])
@endif

<div data-live-target>
<div class="akx-info">
    <span>{{ $extras->total() }} akun Extras</span>
    <a href="{{ route('admin.akun.extras.export', \Illuminate\Support\Arr::except(request()->query(), ['tampil', 'per', 'page'])) }}" class="btn btn-sm"><i class="ti ti-file-spreadsheet"></i> Export Excel</a>
</div>

@if ($daftar)
    <div class="card" style="padding: 0;">
    <div class="table-container">
    <table>
        <thead><tr><th>Extras</th><th>Status</th><th>Grade</th><th>Tag</th><th>Terpilih</th><th>Batal mendadak</th><th><span class="sr-only">Favorit</span></th><th></th></tr></thead>
        <tbody>
            @forelse ($extras as $ex)
                @php $p = $ex->extrasProfile; @endphp
                <tr>
                    <td>
                        <div class="akx-nama">
                            @if ($p?->foto_profil_path)
                                <img src="{{ route('extras.media.foto', $p) }}" alt="" class="akx-foto" loading="lazy">
                            @else
                                <span class="akx-foto" aria-hidden="true">{{ $ex->username ? strtoupper(mb_substr($ex->username, 0, 2)) : '?' }}</span>
                            @endif
                            <div style="min-width: 0;">
                                @if ($p)
                                    <a href="{{ route('admin.extras.profil', $ex) }}" data-profil-modal data-aksi-dialog="kelola-{{ $ex->id }}" data-aksi-label="Kelola">{{ $ex->username ? '@'.$ex->username : '(belum isi username)' }}</a>
                                @else
                                    <strong>{{ $ex->username ? '@'.$ex->username : '(belum isi username)' }}</strong>
                                @endif
                                <small>{{ $ex->name }}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge {{ $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ ucfirst($ex->status) }}</span></td>
                    <td>{{ $p?->grade_saat_ini ?? '–' }}</td>
                    <td>{{ $p?->categories->take(3)->map(fn ($t) => '#'.$t->nama)->implode(' ') ?: '–' }}@if (($p?->categories->count() ?? 0) > 3) +{{ $p->categories->count() - 3 }}@endif</td>
                    <td>{{ $p->terpilih_count ?? 0 }}</td>
                    <td>{{ $p->cancel_count ?? 0 }}</td>
                    <td>
                        @if ($p)
                            <form method="POST" action="{{ route('admin.extras.favorit', $ex) }}" class="akx-fav" id="fav-{{ $p->id }}">
                                @csrf @method('PATCH')
                                <button type="submit" aria-pressed="{{ $p->apresiasi ? 'true' : 'false' }}" aria-label="{{ $p->apresiasi ? 'Hapus dari Favorit' : 'Jadikan Favorit' }}">@if ($p->apresiasi)⭐@else<i class="ti ti-star"></i>@endif</button>
                            </form>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        @include('partials.tombol-wa', ['user' => $ex, 'pesanWa' => null])
                        <button type="button" class="btn btn-sm" onclick="document.getElementById('kelola-{{ $ex->id }}').showModal()">Kelola</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px 0;">Tidak ada Extras yang cocok.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    </div>
@else
    <div class="xgrid">
        @forelse ($extras as $ex)
            @php $cancelCount = $ex->extrasProfile->cancel_count ?? 0; $terpilih = $ex->extrasProfile->terpilih_count ?? 0; @endphp
            @include('partials.extras-card', [
                'profile' => $ex->extrasProfile,
                'user' => $ex,
                'badge' => [ucfirst($ex->status), $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak'],
                'sub' => $ex->name.($terpilih ? ' · terpilih '.$terpilih.'x' : ''),
                'lihat' => $ex->extrasProfile ? ['href' => route('admin.extras.profil', $ex), 'data-profil-modal' => true, 'data-aksi-dialog' => 'kelola-'.$ex->id, 'data-aksi-label' => 'Kelola'] : ['onclick' => "document.getElementById('kelola-{$ex->id}').showModal()"],
                'aksi' => ['label' => 'Kelola', 'onclick' => "document.getElementById('kelola-{$ex->id}').showModal()"],
                'favorit' => true,
                'wa' => true,
                'peringatan' => $cancelCount ? $cancelCount.'x batal mendadak' : null,
            ])
        @empty
            <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 24px 0;">Tidak ada Extras yang cocok.</div>
        @endforelse
    </div>
@endif

<x-pagination-bar :paginator="$extras" :pilihan="$perPilihan" />

@foreach ($extras as $ex)
    @php $cancelCount = $ex->extrasProfile->cancel_count ?? 0; @endphp
    <dialog class="xmodal" id="kelola-{{ $ex->id }}" aria-label="Kelola {{ $ex->username ?? $ex->name }}" onclick="if (event.target === this) this.close()">
        <div class="xmodal-body">
            <div class="xmodal-head">
                <div style="min-width: 0;">
                    <div class="xmodal-name">{{ $ex->username ? '@'.$ex->username : '(belum isi username)' }}</div>
                    <div class="xmodal-sub">{{ $ex->name }} · {{ $ex->email }}</div>
                </div>
                <button type="button" class="xmodal-x" style="position: static; flex-shrink: 0; background: var(--bg-card-hover); color: var(--text-primary);" aria-label="Tutup" onclick="this.closest('dialog').close()"><i class="ti ti-x"></i></button>
            </div>
            <div class="xmodal-badges">
                <span class="badge {{ $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ ucfirst($ex->status) }}</span>
                {{-- RF-08: hitungan dari cancellations (mendadak <H-2 oleh Extras), lihat ExtrasProfile::batalMendadak() --}}
                <span class="badge {{ $cancelCount >= 3 ? 'badge-tolak' : ($cancelCount > 0 ? 'badge-pending' : 'badge-aktif') }}">Batal mendadak {{ $cancelCount }}x</span>
            </div>

            @if ($ex->extrasProfile)
                <div class="xsec">Tag (koreksi Admin)</div>
                <form method="POST" action="{{ route('admin.users.kategori', $ex) }}">
                    @csrf @method('PATCH')
                    @include('partials.tag-input', ['name' => 'tag_nama', 'selected' => $ex->extrasProfile->categories])
                    <button type="submit" class="btn btn-brand" style="margin-top: 14px;">Simpan Tag</button>
                </form>
            @else
                <div class="alert-info" style="margin: 14px 0 0;">Profil Extras belum dibuat, tag belum bisa diisi.</div>
            @endif

            <div class="xsec">Status akun</div>
            <x-confirm-form action="{{ route('admin.users.toggle-status', $ex) }}" method="PATCH" message="{{ $ex->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $ex->name }}?">
                <button type="submit" @class(['btn', 'btn-danger-outline' => $ex->status === 'aktif'])><i class="ti ti-power"></i> {{ $ex->status === 'aktif' ? 'Nonaktifkan akun' : 'Aktifkan akun' }}</button>
            </x-confirm-form>
        </div>
    </dialog>
@endforeach
</div>
@endsection
