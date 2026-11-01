@extends('layouts.app')

@section('title', 'Manajemen Akun')

@push('styles')
<style>
    .akun-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: var(--space-3); }
    .akun-head-btn { display: flex; gap: 8px; flex-wrap: wrap; }
    .akun-row { display: flex; gap: 12px; align-items: center; padding: 10px 14px; border-top: 1px solid var(--border-color); border-radius: var(--radius-md); transition: background .15s ease; }
    .akun-row:hover { background: var(--bg-card-hover); }
    .akun-row:first-of-type { border-top: 0; }
    .akun-row.is-selected { background: var(--bg-nav-active); }

    .akun-avatar-wrap {
        position: relative;
        width: 38px;
        height: 38px;
        flex-shrink: 0;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        user-select: none;
    }
    .akun-avatar-wrap .bulk-cb {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        margin: 0;
        pointer-events: none;
    }
    .akun-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: var(--bg-nav-active);
        color: var(--accent-strong);
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        flex-shrink: 0;
        transition: all .15s ease;
    }
    .akun-avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .akun-avatar-initial {
        font-size: 15px;
        font-weight: 700;
        line-height: 1;
    }
    .akun-avatar-check {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.45);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        opacity: 0;
        transition: opacity .15s ease, background .15s ease;
    }
    .akun-row:hover .akun-avatar-wrap:not(.is-checked) .akun-avatar-check {
        opacity: 0.85;
    }
    .akun-avatar-wrap.is-checked .akun-avatar,
    .akun-avatar-wrap:has(.bulk-cb:checked) .akun-avatar {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }
    .akun-avatar-wrap.is-checked .akun-avatar-img,
    .akun-avatar-wrap.is-checked .akun-avatar-initial,
    .akun-avatar-wrap:has(.bulk-cb:checked) .akun-avatar-img,
    .akun-avatar-wrap:has(.bulk-cb:checked) .akun-avatar-initial {
        opacity: 0;
    }
    .akun-avatar-wrap.is-checked .akun-avatar-check,
    .akun-avatar-wrap:has(.bulk-cb:checked) .akun-avatar-check {
        opacity: 1;
        background: var(--accent);
        color: #fff;
    }

    .akun-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
    .akun-nama { font-weight: 600; font-size: var(--fs-md); display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
    .akun-nama a { color: inherit; text-decoration: none; overflow-wrap: anywhere; }
    .akun-meta { font-size: var(--fs-xs); color: var(--text-muted); overflow-wrap: anywhere; }
    .akun-log { font-size: var(--fs-xs); color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .akun-log i { color: var(--text-muted); }
    .akun-kebab { position: relative; flex-shrink: 0; }
    .akun-kebab > summary { list-style: none; cursor: pointer; min-height: 34px; padding: 6px 12px; display: inline-flex; align-items: center; justify-content: center; gap: 4px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--bg-card); font-size: var(--fs-xs); font-weight: 500; }
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
    <div>
        <p style="font-size: var(--fs-sm); color: var(--text-secondary); margin: 0;">Semua akun sistem: Admin, Korlap, Client, Extras, Super Admin.</p>
        <p style="font-size: var(--fs-xs); color: var(--text-muted); margin: 3px 0 0;"><i class="ti ti-info-circle"></i> Akun Extras mendaftar secara mandiri melalui portal registrasi publik.</p>
    </div>
    <div class="akun-head-btn">
        <button type="button" class="btn btn-sm btn-tonal" onclick="document.getElementById('client-baru-dialog').showModal()"><i class="ti ti-plus"></i> Tambah Client</button>
        <button type="button" class="btn btn-sm btn-brand" onclick="document.getElementById('add-admin-dialog').showModal()"><i class="ti ti-plus"></i> Tambah Staf (Admin/Korlap)</button>
    </div>
</div>
@include('partials.client-baru-modal')

@php
    $tagNama = $tagGroups->flatten()->pluck('nama', 'id');
    $roleOpsi = ['' => 'Semua'] + \Illuminate\Support\Arr::except(\App\Models\User::LABELS, 'super_admin') + ['super_admin' => 'Super Admin'];
@endphp
<form method="GET" action="{{ route('super-admin.akun.index') }}" class="xtoolbar" id="live-form" data-live>
    <input type="search" name="q" value="{{ $f['q'] }}" class="xtoolbar-cari" placeholder="Cari akun…" aria-label="Cari nama, username, email, atau WA">
    <x-per-halaman :pilihan="$perPilihan" :nilai="$users->perPage()" />
    <x-filter-panel :filter="[
        $f['role'] ? ['role', 'Role: '.\App\Models\User::LABELS[$f['role']]] : null,
        $f['status'] ? ['status', 'Status: '.ucfirst($f['status'])] : null,
        $f['sedang_aktif'] ? ['sedang_aktif', 'Sedang aktif di proyek'] : null,
        $f['ganti_password'] ? ['ganti_password', 'Belum ganti password'] : null,
        $f['akan_dihapus'] ? ['akan_dihapus', 'Akan dihapus'] : null,
        $f['favorit'] ? ['favorit', '⭐ Favorit'] : null,
        $f['urut'] ? ['urut', 'Urut: Favorit dulu'] : null,
        $f['grade'] ? ['grade', 'Grade: '.($f['grade'] === 'belum' ? 'Belum' : $f['grade'])] : null,
        ...array_map(fn ($id) => ['tag', 'Tag: #'.($tagNama[$id] ?? $id), $id], $f['tag']),
    ]">
        <x-filter-panel.grup label="Role" name="role" :opsi="$roleOpsi" :nilai="$f['role']" baris />
        <x-filter-panel.grup label="Status" name="status" :opsi="['' => 'Semua', 'aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'dihapus' => 'Dihapus']" :nilai="$f['status']" />
        <label class="fswitch">Sedang aktif di proyek <input type="checkbox" name="sedang_aktif" value="1" @checked($f['sedang_aktif'])></label>
        <label class="fswitch">Belum ganti password <input type="checkbox" name="ganti_password" value="1" @checked($f['ganti_password'])></label>
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
                <button type="button" class="fpanel-rapikan" data-rapikan-tag>Kelola tag{{ $tagRapikan->isNotEmpty() ? ' ('.$tagRapikan->count().' perlu dirapikan)' : '' }}</button>
                <p class="xfilter-note">Menampilkan yang punya <strong>semua</strong> tag terpilih</p>
            </div>
        @endif
    </x-filter-panel>
</form>
    @include('admin.tags.rapikan', ['tags' => $tagRapikan, 'routeNames' => ['update' => 'super-admin.tags.update', 'gabung' => 'super-admin.tags.gabung', 'destroy' => 'super-admin.tags.destroy']])

<div data-live-target>
{{-- Form bulk berdiri sendiri, checkbox terhubung lewat atribut form (AY.1: tanpa form bersarang). --}}
<form id="bulk-form" method="POST" action="{{ route('super-admin.admins.bulk-action') }}" style="display: none;">
    @csrf
</form>
<div id="bulk-toolbar">
    <span id="bulk-count" style="font-size: var(--fs-sm); font-weight: 600;"></span>
    <select name="action" id="bulk-aksi" form="bulk-form" aria-label="Aksi massal" onchange="document.getElementById('bulk-terapkan').disabled = !this.value">
        <option value="">Pilih aksi massal</option>
        <option value="nonaktifkan">Nonaktifkan terpilih</option>
        <option value="aktifkan">Aktifkan terpilih</option>
    </select>
    <button type="submit" id="bulk-terapkan" form="bulk-form" class="btn btn-sm btn-brand" disabled onclick="var s = document.getElementById('bulk-aksi'); var n = document.querySelectorAll('.bulk-cb:checked').length; return !!s.value && n > 0 && confirm((s.value === 'nonaktifkan' ? 'Nonaktifkan ' : 'Aktifkan ') + n + ' akun terpilih?' + (s.value === 'nonaktifkan' ? ' Mereka tidak bisa login sampai diaktifkan lagi.' : ''));">Terapkan</button>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 8px; font-size: var(--fs-xs); color: var(--text-muted); flex-wrap: wrap;">
    <span>{{ $users->total() }} akun</span>
    @unless ($modeExtras)
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span id="select-all-context" style="color: var(--text-muted); font-size: var(--fs-xs);">0 dipilih · Centang akun untuk aksi massal</span>
            <label style="display: flex; align-items: center; gap: 6px; margin: 0; cursor: pointer; color: var(--text-primary); font-weight: 500;"><input type="checkbox" id="select-all-cb" style="min-height: 0;"> Pilih semua di halaman ini</label>
        </div>
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
                    <form method="POST" action="{{ route('super-admin.admins.extras-update', $u) }}">
                        @csrf @method('PATCH')
                        <div class="xsec">Grade Extras</div>
                        <select name="grade" style="margin-top: 4px; margin-bottom: 8px;">
                            <option value="belum" @selected(! $u->extrasProfile?->grade_saat_ini)>Belum dinilai</option>
                            <option value="A" @selected($u->extrasProfile?->grade_saat_ini === 'A')>Grade A (Utama / Profesional)</option>
                            <option value="B" @selected($u->extrasProfile?->grade_saat_ini === 'B')>Grade B (Standar / Berpengalaman)</option>
                            <option value="C" @selected($u->extrasProfile?->grade_saat_ini === 'C')>Grade C (Pemula / Baru)</option>
                        </select>
                        <div class="xsec" style="margin-top: 10px;">Tag</div>
                        @include('partials.tag-input', ['name' => 'tag_nama', 'selected' => $u->extrasProfile->categories])
                        <button type="submit" class="btn btn-sm btn-brand" style="margin-top: 12px;">Simpan Perubahan</button>
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
            <div class="akun-row" style="cursor: pointer;" onclick="document.getElementById('modal-akun-preview-{{ $u->id }}').showModal()">
                @if (! $u->is_protected && ! $u->trashed())
                    <label class="akun-avatar-wrap" onclick="event.stopPropagation()" title="Pilih {{ $u->name }}">
                        <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="bulk-cb" form="bulk-form" aria-label="Pilih {{ $u->name }}">
                        <div class="akun-avatar">
                            @if ($u->role === 'extras' && $u->extrasProfile?->foto_profil_path)
                                <img src="{{ route('extras.media.foto', $u->extrasProfile) }}" alt="" class="akun-avatar-img">
                            @else
                                <span class="akun-avatar-initial">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                            @endif
                            <div class="akun-avatar-check">
                                <i class="ti ti-check"></i>
                            </div>
                        </div>
                    </label>
                @else
                    <div class="akun-avatar" style="opacity: 0.65;" title="{{ $u->name }}">
                        @if ($u->role === 'extras' && $u->extrasProfile?->foto_profil_path)
                            <img src="{{ route('extras.media.foto', $u->extrasProfile) }}" alt="" class="akun-avatar-img">
                        @else
                            <span class="akun-avatar-initial">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                        @endif
                    </div>
                @endif
                <div class="akun-main">
                    <div class="akun-nama">
                        @if ($u->trashed() || $u->is_protected)
                            <span>{{ $u->name }}</span>
                        @else
                            <a href="{{ route('super-admin.admins.show', $u) }}" onclick="if (!event.ctrlKey && !event.metaKey && event.button === 0) { event.preventDefault(); event.stopPropagation(); document.getElementById('modal-akun-preview-{{ $u->id }}').showModal(); }">{{ $u->name }}</a>
                        @endif
                        <x-status-badge :model="$u" />
                        <span class="badge {{ $statusBadge($u)[1] }}">{{ $statusBadge($u)[0] }}</span>
                        <span class="akun-meta">{{ $sejak($u) }}</span>
                    </div>
                    <div class="akun-meta">
                        {{ implode(' · ', array_filter([$u->username ? '@'.$u->username : null, $u->email])) }}
                    </div>
                </div>
                <details class="akun-kebab" onclick="event.stopPropagation()">
                    <summary aria-label="Aksi {{ $u->name }}"><i class="ti ti-adjustments"></i> Kelola <i class="ti ti-chevron-down"></i></summary>
                    <div class="akun-kebab-isi">
                        @include('super-admin.akun.aksi-menu', ['u' => $u])
                    </div>
                </details>
            </div>
            @include('super-admin.akun.modal-preview', ['u' => $u])
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
        <input type="hidden" name="_dialog" value="add-admin">
        <div class="akun-dialog-judul">Tambah akun staf</div>
        @if (old('_dialog') === 'add-admin' && $errors->any())
            <div class="alert-danger" role="alert">
                @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif
        <label>Nama <span class="wajib" aria-hidden="true">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" required>
        <label>Email <span class="wajib" aria-hidden="true">*</span></label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        <p class="muted" style="margin:0 0 10px;font-size:var(--fs-xs);">Kata sandi sementara dibuat otomatis dan ditampilkan sekali setelah akun dibuat. Staf wajib menggantinya saat login pertama.</p>
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
    @if (old('_dialog') === 'add-admin' && $errors->any())
    document.getElementById('add-admin-dialog').showModal();
    @endif
    // SA-08: kebab ditutup begitu dialog dibuka
    document.addEventListener('click', function (e) {
        var b = e.target.closest('.akun-kebab-isi button[onclick]');
        if (b) b.closest('details').removeAttribute('open');
    });
    // delegasi di document: daftar bisa diganti live search (AJAX) tanpa kehilangan listener
    function sync() {
        var toolbar = document.getElementById('bulk-toolbar');
        var ctx = document.getElementById('select-all-context');
        var n = document.querySelectorAll('.bulk-cb:checked').length;
        if (toolbar) {
            toolbar.style.display = n ? 'flex' : 'none';
            document.getElementById('bulk-count').textContent = n + ' akun dipilih';
        }
        if (ctx) {
            ctx.textContent = n > 0 
                ? n + ' akun dipilih untuk aksi massal' 
                : '0 dipilih · Centang akun untuk aksi massal';
            ctx.style.color = n > 0 ? 'var(--accent-strong)' : 'var(--text-muted)';
            ctx.style.fontWeight = n > 0 ? '600' : '400';
        }
        document.querySelectorAll('.bulk-cb').forEach(function (cb) {
            var wrap = cb.closest('.akun-avatar-wrap');
            var row = cb.closest('.akun-row');
            if (wrap) wrap.classList.toggle('is-checked', cb.checked);
            if (row) row.classList.toggle('is-selected', cb.checked);
        });
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
