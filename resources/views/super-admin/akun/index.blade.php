@extends('layouts.app')

@section('title', 'Manajemen Akun')

@push('styles')
<style>
    .akun-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: var(--space-3); }
    .akun-head-btn { display: flex; gap: 8px; flex-wrap: wrap; }
    .akun-row { display: flex; gap: 10px; align-items: flex-start; padding: 12px 0; border-top: 1px solid var(--border-color); }
    .akun-row:first-of-type { border-top: 0; }
    .akun-row > input[type=checkbox] { margin-top: 4px; flex-shrink: 0; }
    .akun-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
    .akun-nama { font-weight: 600; font-size: var(--fs-md); display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
    .akun-nama a { color: inherit; text-decoration: none; overflow-wrap: anywhere; }
    .akun-meta { font-size: var(--fs-xs); color: var(--text-muted); overflow-wrap: anywhere; }
    .akun-log { font-size: var(--fs-xs); color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .akun-log i { color: var(--text-muted); }
    .akun-kebab { position: relative; flex-shrink: 0; }
    .akun-kebab > summary { list-style: none; cursor: pointer; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--bg-card); }
    .akun-kebab > summary::-webkit-details-marker { display: none; }
    .akun-kebab-isi { position: absolute; right: 0; top: 44px; min-width: 190px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 8px 24px rgba(0,0,0,0.15); padding: 4px 0; z-index: 40; }
    .akun-menu-item { display: flex; align-items: center; gap: 8px; width: 100%; min-height: 40px; padding: 8px 14px; font-size: var(--fs-sm); text-align: left; background: none; border: 0; color: var(--text-primary); text-decoration: none; cursor: pointer; margin: 0; }
    .akun-menu-item:hover { background: var(--bg-card-hover); }
    .akun-menu-item.is-danger { color: var(--danger); }
    .akun-menu-item.is-brand { color: var(--accent-strong); }
    .akun-menu-item.is-muted { color: var(--text-muted); cursor: default; }
    .akun-dialog { border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 0; width: min(440px, calc(100% - 32px)); background: var(--bg-card); color: var(--text-primary); }
    .akun-dialog > form { padding: 18px; margin: 0; }
    .akun-dialog-judul { font-size: var(--fs-md); font-weight: 600; margin-bottom: 10px; }
    .akun-dialog-teks { color: var(--text-muted); font-size: var(--fs-sm); margin: 0 0 14px; }
    .akun-dialog-btn { display: flex; gap: 8px; justify-content: flex-end; margin-top: 8px; }
    #bulk-toolbar { display: none; position: sticky; top: 10px; z-index: 30; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: var(--space-3); padding: 10px 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 4px 14px rgba(0,0,0,0.12); }
    #bulk-toolbar select { width: auto; margin: 0; min-height: 36px; }
</style>
@endpush

@section('content')
@php
    $modeExtras = $f['role'] === 'extras';
    $perPilihan = $modeExtras ? \App\Support\PerHalaman::KARTU : \App\Support\PerHalaman::TABEL;
    $statusBadge = fn ($u) => $u->trashed() ? ['Dihapus', 'badge-tolak'] : [ucfirst($u->status), $u->status === 'aktif' ? 'badge-aktif' : 'badge-tolak'];
    $sejak = fn ($u) => $u->trashed() ? 'dihapus '.$u->deleted_at->translatedFormat('d M Y') : ($u->status === 'aktif' ? 'aktif sejak ' : 'terdaftar sejak ').$u->created_at->translatedFormat('d M Y');
@endphp

<div class="akun-head">
    <p style="font-size: var(--fs-sm); color: var(--text-secondary); margin: 0;">Semua akun sistem: Admin, Korlap, Client, Extras, Super Admin.</p>
    <div class="akun-head-btn">
        <a href="{{ route('admin.tags.index') }}" class="btn btn-sm"><i class="ti ti-tags"></i> Kelola Tag</a>
        <button type="button" class="btn btn-sm" onclick="document.getElementById('client-baru-dialog').showModal()"><i class="ti ti-plus"></i> Client</button>
        <button type="button" class="btn btn-sm btn-brand" onclick="document.getElementById('add-admin-dialog').showModal()"><i class="ti ti-plus"></i> Staf</button>
    </div>
</div>
@include('partials.client-baru-modal')

@php
    $tagNama = $tagGroups->flatten()->pluck('nama', 'id');
    $roleOpsi = ['' => 'Semua'] + \Illuminate\Support\Arr::except(\App\Models\User::LABELS, 'super_admin') + ['super_admin' => 'Super Admin'];
@endphp
<form method="GET" action="{{ route('super-admin.akun.index') }}" class="xtoolbar" id="live-form" data-live>
    <input type="search" name="q" value="{{ $f['q'] }}" class="xtoolbar-cari" placeholder="Cari nama, username, email, WA…" aria-label="Cari akun">
    <x-per-halaman :pilihan="$perPilihan" :nilai="$users->perPage()" />
    <x-filter-panel :filter="[
        $f['role'] ? ['role', 'Role: '.\App\Models\User::LABELS[$f['role']]] : null,
        $f['status'] ? ['status', 'Status: '.ucfirst($f['status'])] : null,
        $f['sedang_aktif'] ? ['sedang_aktif', 'Sedang aktif di proyek'] : null,
        $f['akan_dihapus'] ? ['akan_dihapus', 'Akan dihapus'] : null,
        $f['favorit'] ? ['favorit', '⭐ Favorit'] : null,
        $f['urut'] ? ['urut', 'Urut: Favorit dulu'] : null,
        $f['grade'] ? ['grade', 'Grade: '.($f['grade'] === 'belum' ? 'Belum' : $f['grade'])] : null,
        ...array_map(fn ($id) => ['tag', 'Tag: #'.($tagNama[$id] ?? $id), $id], $f['tag']),
    ]">
        <x-filter-panel.grup label="Role" name="role" :opsi="$roleOpsi" :nilai="$f['role']" baris />
        <x-filter-panel.grup label="Status" name="status" :opsi="['' => 'Semua', 'aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'dihapus' => 'Dihapus']" :nilai="$f['status']" />
        <label class="fswitch">Sedang aktif di proyek <input type="checkbox" name="sedang_aktif" value="1" @checked($f['sedang_aktif'])></label>
        @if (in_array($f['role'], [null, 'extras'], true))
            <div class="fpanel-sub">
                <div class="fpanel-judul">Khusus Extras</div>
                <label class="fswitch">⭐ Favorit <input type="checkbox" name="favorit" value="1" @checked($f['favorit'])></label>
                <x-filter-panel.grup label="Urutkan" name="urut" :opsi="['' => 'Terbaru', 'favorit' => 'Favorit dulu']" :nilai="$f['urut']" />
                <label class="fswitch">Akan dihapus (sudah diperingatkan) <input type="checkbox" name="akan_dihapus" value="1" @checked($f['akan_dihapus'])></label>
                <x-filter-panel.grup label="Grade" name="grade" :opsi="['' => 'Semua', 'A' => 'A', 'B' => 'B', 'C' => 'C', 'belum' => 'Belum']" :nilai="$f['grade']" />
                @foreach ($tagGroups as $grup => $tags)
                    @php $n = $tags->whereIn('id', $f['tag'])->count(); @endphp
                    <details class="fpanel-acc" data-k="tag-{{ $grup }}">
                        <summary>{{ $grup }}@if ($n) ({{ $n }})@endif</summary>
                        <x-filter-panel.grup label="" name="tag[]" :opsi="$tags->mapWithKeys(fn ($t) => [$t->id => '#'.$t->nama])->all()" :nilai="$f['tag']" multi />
                    </details>
                @endforeach
                <p class="xfilter-note">Menampilkan yang punya <strong>semua</strong> tag terpilih</p>
            </div>
        @endif
    </x-filter-panel>
</form>

<div data-live-target>
{{-- Form bulk berdiri sendiri, checkbox terhubung lewat atribut form (AY.1: tanpa form bersarang). --}}
<form id="bulk-form" method="POST" action="{{ route('super-admin.admins.bulk-action') }}" style="display: none;">
    @csrf
</form>
<div id="bulk-toolbar">
    <span id="bulk-count" style="font-size: var(--fs-sm); font-weight: 600;"></span>
    <select name="action" form="bulk-form" aria-label="Aksi massal">
        <option value="">Pilih aksi massal</option>
        <option value="nonaktifkan">Nonaktifkan terpilih</option>
        <option value="aktifkan">Aktifkan terpilih</option>
    </select>
    <button type="submit" form="bulk-form" class="btn btn-sm btn-brand">Terapkan</button>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 8px; font-size: var(--fs-xs); color: var(--text-muted);">
    <span>{{ $users->total() }} akun</span>
    @unless ($modeExtras)
        <label style="display: flex; align-items: center; gap: 6px; margin: 0; cursor: pointer;"><input type="checkbox" id="select-all-cb" style="min-height: 0;"> Pilih semua di halaman ini</label>
    @endunless
</div>

@if ($modeExtras)
    <div class="xgrid">
        @forelse ($users as $u)
            @php $bisaProfil = $u->extrasProfile && ! $u->trashed(); @endphp
            @include('partials.extras-card', [
                'profile' => $u->extrasProfile,
                'user' => $u,
                'badge' => $statusBadge($u),
                'sub' => $u->name.' · '.$sejak($u),
                'lihat' => $bisaProfil ? ['href' => route('admin.extras.profil', $u), 'data-profil-modal' => true, 'data-aksi-dialog' => 'kelola-'.$u->id, 'data-aksi-label' => 'Kelola'] : ['onclick' => "document.getElementById('kelola-{$u->id}').showModal()"],
                'aksi' => ['label' => 'Kelola', 'onclick' => "document.getElementById('kelola-{$u->id}').showModal()"],
                'favorit' => ! $u->trashed(),
            ])
        @empty
            <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 24px 0;">Tidak ada akun yang cocok.</div>
        @endforelse
    </div>
    @foreach ($users as $u)
        <dialog class="xmodal" id="kelola-{{ $u->id }}" aria-label="Kelola {{ $u->username ?? $u->name }}" onclick="if (event.target === this) this.close()">
            <div class="xmodal-body">
                <div class="xmodal-head">
                    <div style="min-width: 0;">
                        <div class="xmodal-name">{{ $u->username ? '@'.$u->username : $u->name }}</div>
                        <div class="xmodal-sub">{{ $u->name }} · {{ $u->email ?? 'tanpa email' }}</div>
                    </div>
                    <button type="button" class="xmodal-x" style="position: static; flex-shrink: 0; background: var(--bg-card-hover); color: var(--text-primary);" aria-label="Tutup" onclick="this.closest('dialog').close()"><i class="ti ti-x"></i></button>
                </div>
                <div class="xmodal-badges">
                    <span class="badge {{ $statusBadge($u)[1] }}">{{ $statusBadge($u)[0] }}</span>
                    <span class="badge badge-netral">{{ ($g = $u->extrasProfile?->grade_saat_ini) ? 'Grade '.$g : 'Belum dinilai' }}</span>
                </div>
                @if ($u->extrasProfile && ! $u->trashed())
                    <div class="xsec">Tag</div>
                    <form method="POST" action="{{ route('admin.users.kategori', $u) }}">
                        @csrf @method('PATCH')
                        @include('partials.tag-input', ['name' => 'tag_nama', 'selected' => $u->extrasProfile->categories])
                        <button type="submit" class="btn btn-sm btn-brand" style="margin-top: 12px;">Simpan Tag</button>
                    </form>
                @endif
                <div class="xsec">Aktivitas terakhir</div>
                <div class="akun-log" style="white-space: normal;">{{ $u->aktivitasTerakhir?->description ?? 'Belum ada aktivitas.' }}</div>
                <div class="xsec">Aksi</div>
                @include('super-admin.akun.aksi-menu', ['u' => $u])
            </div>
        </dialog>
        @include('super-admin.akun.aksi-dialogs', ['u' => $u])
    @endforeach
@else
    <div class="card" style="padding-top: 4px; padding-bottom: 4px;">
        @forelse ($users as $u)
            <div class="akun-row">
                @if (! $u->is_protected && ! $u->trashed())
                    <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="bulk-cb" form="bulk-form" aria-label="Pilih {{ $u->name }}">
                @else
                    <span style="width: 15px; flex-shrink: 0;"></span>
                @endif
                <div class="akun-main">
                    <div class="akun-nama">
                        @if ($u->trashed() || $u->is_protected)
                            <span>{{ $u->name }}</span>
                        @else
                            <a href="{{ route('super-admin.admins.show', $u) }}">{{ $u->name }}</a>
                        @endif
                        <x-status-badge :model="$u" />
                        @if ($grade = $u->extrasProfile?->grade_saat_ini)
                            <span class="badge badge-netral">Grade {{ $grade }}</span>
                        @endif
                    </div>
                    <div class="akun-meta">
                        {{ implode(' · ', array_filter([$u->username ? '@'.$u->username : null, $u->email, $u->nomor_wa ? '+'.$u->nomor_wa : null, $u->nama_perusahaan])) }}
                    </div>
                    <div class="akun-meta"><span class="badge {{ $statusBadge($u)[1] }}">{{ $statusBadge($u)[0] }}</span> {{ $sejak($u) }}</div>
                    <div class="akun-log">
                        <i class="ti ti-activity"></i>
                        @if ($log = $u->aktivitasTerakhir)
                            <span title="{{ $log->description }}">{{ $log->description }}</span> · <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->locale('id')->diffForHumans() }}</time>
                        @else
                            Belum ada aktivitas.
                        @endif
                    </div>
                </div>
                <details class="akun-kebab">
                    <summary aria-label="Aksi {{ $u->name }}"><i class="ti ti-dots-vertical"></i></summary>
                    <div class="akun-kebab-isi">
                        @include('super-admin.akun.aksi-menu', ['u' => $u])
                    </div>
                </details>
            </div>
            @include('super-admin.akun.aksi-dialogs', ['u' => $u])
        @empty
            <p style="text-align: center; color: var(--text-muted); padding: 24px 0; margin: 0;">Tidak ada akun yang cocok.</p>
        @endforelse
    </div>
@endif

<x-pagination-bar :paginator="$users" :pilihan="$perPilihan" />
</div>

<dialog id="add-admin-dialog" class="akun-dialog">
    <form method="POST" action="{{ route('super-admin.admins.store') }}">
        @csrf
        <div class="akun-dialog-judul">Tambah akun staf</div>
        <label>Nama <span class="wajib" aria-hidden="true">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" required>
        <label>Email <span class="wajib" aria-hidden="true">*</span></label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        <x-password-input name="password" label="Password" :minlength="8" />
        <label>Role <span class="wajib" aria-hidden="true">*</span></label>
        <select name="role" required>
            <option value="admin" @selected(old('role') === 'admin')>Admin (operasional proyek penuh)</option>
            <option value="korlap" @selected(old('role') === 'korlap')>Korlap (koordinator lapangan)</option>
            @if (auth()->user()->is_protected)
                <option value="super_admin" @selected(old('role') === 'super_admin')>Super Admin</option>
            @endif
        </select>
        <label>Honor per event (Rp)</label>
        <input type="number" name="honor_nominal" min="0" value="{{ old('honor_nominal') }}">
        <div class="akun-dialog-btn">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    // delegasi di document: daftar bisa diganti live search (AJAX) tanpa kehilangan listener
    function sync() {
        var toolbar = document.getElementById('bulk-toolbar');
        if (!toolbar) return;
        var n = document.querySelectorAll('.bulk-cb:checked').length;
        toolbar.style.display = n ? 'flex' : 'none';
        document.getElementById('bulk-count').textContent = n + ' akun dipilih';
    }
    document.addEventListener('change', function (e) {
        if (e.target.id === 'select-all-cb') {
            document.querySelectorAll('.bulk-cb').forEach(function (cb) { cb.checked = e.target.checked; });
        }
        if (e.target.id === 'select-all-cb' || e.target.classList.contains('bulk-cb')) sync();
    });
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.akun-kebab[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });
}());
</script>
@endpush
