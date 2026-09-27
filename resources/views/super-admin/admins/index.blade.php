@extends('layouts.app')

@section('title', 'Kelola Akun')

@section('content')
<div class="card-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <div>
        <div style="font-size: 18px; font-weight: 700;">Kelola Akun</div>
        <p style="font-size: 13px; color: var(--text-secondary); margin: 2px 0 0;">
            Manajemen akun seluruh pengguna sistem (Admin, Korlap, Client, Extras, Super Admin).
        </p>
    </div>
    <button type="button" class="btn btn-brand" onclick="document.getElementById('add-admin-dialog').showModal()">+ Tambah Staf</button>
</div>

{{-- AU.6: Search Bar Dominan + 1 Tombol Filter Dropdown --}}
<div style="display: flex; gap: 8px; margin-bottom: 16px; align-items: stretch; position: relative;">
    <form method="GET" action="{{ route('super-admin.admins.index') }}" style="display: flex; gap: 8px; flex: 1;">
        <input type="hidden" name="role" value="{{ $roleFilter }}">
        <input type="hidden" name="status" value="{{ $statusFilter }}">
        <div style="position: relative; flex: 1;">
            <input type="text" name="search" value="{{ $search ?? '' }}"
                   placeholder="Cari nama, email, username, atau role..."
                   style="width: 100%; min-height: 42px; padding: 8px 14px 8px 38px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 14px; margin-bottom: 0;">
            <i class="ti ti-search" style="position: absolute; left: 12px; top: 13px; color: var(--text-muted); font-size: 16px;"></i>
        </div>
        <button type="submit" class="btn btn-brand" style="border-radius: 8px; padding: 0 16px;">Cari</button>
    </form>

    {{-- Filter Dropdown --}}
    <details class="filter-dropdown" style="position: relative;">
        <summary class="btn" style="min-height: 42px; border-radius: 8px; display: flex; align-items: center; gap: 6px; cursor: pointer; list-style: none;">
            <i class="ti ti-filter"></i> Filter
            @if ($roleFilter !== 'all' || $statusFilter !== 'all')
                <span class="badge badge-aktif" style="font-size: 10px; padding: 2px 6px;">Aktif</span>
            @endif
        </summary>
        <div style="position: absolute; right: 0; top: 48px; width: 260px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); padding: 14px; z-index: 50;">
            <form method="GET" action="{{ route('super-admin.admins.index') }}">
                @if ($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Filter Role</label>
                    <select name="role" style="width: 100%; font-size: 13px; padding: 6px 8px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-page); color: var(--text-primary); margin-top: 4px;">
                        <option value="all" @selected($roleFilter==='all')>Semua Admin/Staf</option>
                        <option value="admin" @selected(in_array($roleFilter, ['admin','admin_default']))>Admin</option>
                        <option value="korlap" @selected(in_array($roleFilter, ['korlap','admin_korlap']))>Korlap</option>
                        <option value="client" @selected(in_array($roleFilter, ['client','casting_director']))>Client</option>
                        <option value="extras" @selected($roleFilter==='extras')>Extras</option>
                        <option value="super_admin" @selected($roleFilter==='super_admin')>Super Admin</option>
                    </select>
                </div>
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Filter Status</label>
                    <select name="status" style="width: 100%; font-size: 13px; padding: 6px 8px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-page); color: var(--text-primary); margin-top: 4px;">
                        <option value="all" @selected($statusFilter==='all')>Semua Status</option>
                        <option value="aktif" @selected($statusFilter==='aktif')>Aktif</option>
                        <option value="nonaktif" @selected($statusFilter==='nonaktif')>Nonaktif / Arsip</option>
                    </select>
                </div>
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                    <a href="{{ route('super-admin.admins.index') }}" class="btn btn-sm" style="font-size: 12px;">Reset</a>
                    <button type="submit" class="btn btn-sm btn-brand" style="font-size: 12px;">Terapkan</button>
                </div>
            </form>
        </div>
    </details>
</div>

{{-- AU.6: Sticky Bulk Toolbar --}}
<div id="bulk-toolbar" style="display:none; position: sticky; top: 10px; z-index: 30; margin-bottom: 14px; padding: 10px 16px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; gap: 10px; align-items: center; box-shadow: 0 4px 14px rgba(0,0,0,0.12);">
    <span id="bulk-count" style="font-size: 13px; font-weight: 600; color: var(--text-primary);"></span>
    <select name="action" form="bulk-form" id="bulk-action-select" style="padding: 6px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 13px; margin-bottom: 0;">
        <option value="">— Pilih Aksi Massal —</option>
        <option value="nonaktifkan">Nonaktifkan Terpilih</option>
        <option value="aktifkan">Aktifkan Terpilih</option>
    </select>
    <button type="submit" form="bulk-form" class="btn btn-sm btn-brand">Terapkan</button>
</div>

<div id="admins-list-container">
@if ($roleFilter !== 'extras')
    <div style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
        <label style="font-size: 13px; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; gap: 6px;">
            <input type="checkbox" id="select-all-cb"> Pilih Semua di Halaman Ini
        </label>
        <span style="font-size: 12.5px; color: var(--text-muted);">Menampilkan {{ $admins->count() }} dari total {{ $admins->total() }} akun</span>
    </div>
@endif

<form id="bulk-form" method="POST" action="{{ route('super-admin.admins.bulk-action') }}">
    @csrf
    @forelse ($admins as $user)
        <div class="card" style="margin-bottom: 12px; position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                    @if (! $user->is_protected && $user->id !== auth()->id() && $user->role !== 'extras')
                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="bulk-cb">
                    @else
                        <span style="display: inline-block; width: 15px;"></span>
                    @endif
                    <div>
                        <div style="font-weight: 600; font-size: 14.5px; color: var(--text-primary); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            @if ($user->role === 'extras')
                                <a href="{{ route('admin.extras.profil', $user) }}" style="color: inherit; text-decoration: none;">{{ $user->name }}</a>
                            @else
                                <a href="{{ route('super-admin.admins.show', $user) }}" style="color: inherit; text-decoration: none;">{{ $user->name }}</a>
                            @endif
                            <span class="badge badge-pending">{{ $user->role }}</span>
                            <span class="badge {{ $user->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ $user->status }}</span>
                        </div>
                        <div style="color: var(--text-muted); font-size: 12.5px; margin-top: 2px;">
                            {{ $user->email }}
                            @if ($user->username)
                                &bull; <span style="font-family: monospace;">{{ '@'.$user->username }}</span>
                            @endif
                            &bull; Terdaftar: {{ $user->created_at->format('d M Y') }}
                        </div>
                    </div>
                </div>

                {{-- Kebab Menu Aksi (AU.6) --}}
                <details class="kebab-menu" style="position: relative;">
                    <summary style="list-style: none; cursor: pointer; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card);">
                        <i class="ti ti-dots-vertical"></i>
                    </summary>
                    <div style="position: absolute; right: 0; top: 36px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; min-width: 170px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); padding: 4px 0; z-index: 40;">
                        @if ($user->role === 'extras')
                            <a href="{{ route('admin.extras.profil', $user) }}" style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">
                                <i class="ti ti-user"></i> Lihat Profil
                            </a>
                            <a href="{{ url('/admin/users') }}" style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">
                                <i class="ti ti-adjustments"></i> Kelola di Admin
                            </a>
                        @else
                            <a href="{{ route('super-admin.admins.show', $user) }}" style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">
                                <i class="ti ti-id"></i> Lihat Detail
                            </a>
                        @endif

                        @if (! $user->is_protected && $user->id !== auth()->id())
                            <button type="button" onclick="document.getElementById('edit-user-dialog-{{ $user->id }}').showModal()" style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 14px; font-size: 13px; text-align: left; background: none; border: none; color: var(--text-primary); cursor: pointer;">
                                <i class="ti ti-edit"></i> Edit Akun
                            </button>
                            <form method="POST" action="{{ route('super-admin.admins.reset-password', $user) }}">
                                @csrf
                                <button type="submit" onclick="return confirm('Reset password akun {{ $user->name }}?')" style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 14px; font-size: 13px; text-align: left; background: none; border: none; color: var(--text-primary); cursor: pointer;">
                                    <i class="ti ti-key"></i> Reset Password
                                </button>
                            </form>
                            @if ($user->trashed() || $user->status === 'nonaktif')
                                <button type="button" onclick="document.getElementById('restore-dialog-{{ $user->id }}').showModal()" style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 14px; font-size: 13px; text-align: left; background: none; border: none; color: var(--accent-strong); cursor: pointer;">
                                    <i class="ti ti-check"></i> Aktifkan Kembali
                                </button>
                            @else
                                <button type="button" onclick="document.getElementById('deactivate-dialog-{{ $user->id }}').showModal()" style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 14px; font-size: 13px; text-align: left; background: none; border: none; color: var(--danger); cursor: pointer;">
                                    <i class="ti ti-ban"></i> Nonaktifkan
                                </button>
                            @endif
                        @endif
                    </div>
                </details>
            </div>
        </div>

        {{-- Dialog Edit User --}}
        @if (! $user->is_protected && $user->id !== auth()->id())
            <dialog id="edit-user-dialog-{{ $user->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 440px; width: 90%;">
                <div style="padding: 18px;">
                    <div style="font-size: 15px; font-weight: 600; margin-bottom: 14px;">Edit Akun — {{ $user->name }}</div>
                    <form method="POST" action="{{ route('super-admin.admins.update', $user) }}">
                        @csrf @method('PATCH')
                        <label>Nama</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required style="width: 100%; margin-bottom: 12px;">

                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required style="width: 100%; margin-bottom: 12px;">

                        <label>Role</label>
                        <select name="role" required style="width: 100%; margin-bottom: 16px;">
                            <option value="admin" @selected($user->role === 'admin' || $user->role === 'admin_default')>Admin</option>
                            <option value="korlap" @selected($user->role === 'korlap' || $user->role === 'admin_korlap')>Korlap</option>
                            <option value="client" @selected($user->role === 'client' || $user->role === 'casting_director')>Client</option>
                            <option value="extras" @selected($user->role === 'extras')>Extras</option>
                            @if (auth()->user()->is_protected)
                                <option value="super_admin" @selected($user->role === 'super_admin')>Super Admin</option>
                            @endif
                        </select>

                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                            <button type="submit" class="btn btn-sm btn-brand">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </dialog>

            {{-- Dialog Deactivate --}}
            <dialog id="deactivate-dialog-{{ $user->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
                <form method="POST" action="{{ route('super-admin.admins.destroy', $user) }}" style="padding: 18px;">
                    @csrf @method('DELETE')
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Nonaktifkan {{ $user->name }}?</div>
                    <div style="color: var(--text-muted); font-size: 12.5px; margin-bottom: 14px;">Akun akan dinonaktifkan. Data dan histori penugasan tetap tersimpan aman di database.</div>
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger-outline">Nonaktifkan</button>
                    </div>
                </form>
            </dialog>

            {{-- Dialog Restore --}}
            <dialog id="restore-dialog-{{ $user->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
                <form method="POST" action="{{ route('super-admin.admins.restore', $user->id) }}" style="padding: 18px;">
                    @csrf @method('PATCH')
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Aktifkan kembali {{ $user->name }}?</div>
                    <div style="color: var(--text-muted); font-size: 12.5px; margin-bottom: 14px;">Akun akan dapat digunakan kembali untuk login dan menerima penugasan.</div>
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                        <button type="submit" class="btn btn-sm btn-brand">Ya, Aktifkan</button>
                    </div>
                </form>
            </dialog>
        @endif
    @empty
        <div class="card" style="padding: 30px; text-align: center; color: var(--text-muted);">
            Tidak ada akun yang sesuai dengan pencarian atau filter.
        </div>
    @endforelse
</form>

{{-- Pagination Links --}}
<div style="margin-top: 16px;">
    {{ $admins->links() }}
</div>
</div>

{{-- Dialog Tambah Staf/Admin --}}
<dialog id="add-admin-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 480px; width: 90%;">
    <div style="padding: 18px;">
        <div style="font-size: 15px; font-weight: 600; margin-bottom: 14px;">Tambah Akun Staf / Admin</div>
        <form method="POST" action="{{ route('super-admin.admins.store') }}">
            @csrf
            <label>Nama</label>
            <input type="text" name="name" value="{{ old('name') }}" required>

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>

            <x-password-input name="password" label="Password" :minlength="8" />

            <label>Role</label>
            <select name="role" required style="width: 100%; margin-bottom: 4px;">
                <option value="admin" @selected(old('role') === 'admin' || old('role') === 'admin_default')>Admin (operasional proyek penuh)</option>
                <option value="korlap" @selected(old('role') === 'korlap' || old('role') === 'admin_korlap')>Korlap (Koordinator Lapangan)</option>
                <option value="client" @selected(old('role') === 'client')>Client / Production House</option>
                @if (auth()->user()->is_protected)
                    <option value="super_admin" @selected(old('role') === 'super_admin')>Super Admin</option>
                @endif
            </select>
            <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 14px;">Pilih jenis akun staf/karyawan yang ingin ditambahkan.</p>

            <label>Nominal Honor per Event (Rp)</label>
            <input type="number" name="honor_nominal" min="0" value="{{ old('honor_nominal') }}">
            <p style="font-size: 12px; color: var(--text-muted); margin: -10px 0 18px;">Honor standing per proyek (khusus Korlap/staf proyek). Bisa disesuaikan kapan saja.</p>

            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    function initBulkToolbar() {
        var toolbar = document.getElementById('bulk-toolbar');
        var countSpan = document.getElementById('bulk-count');
        var selectAll = document.getElementById('select-all-cb');
        if (!toolbar) return;

        function syncToolbar() {
            var checked = document.querySelectorAll('.bulk-cb:checked');
            var n = checked.length;
            if (n > 0) {
                toolbar.style.display = 'flex';
                countSpan.textContent = n + ' akun dipilih';
            } else {
                toolbar.style.display = 'none';
            }
        }

        document.querySelectorAll('.bulk-cb').forEach(function (cb) {
            cb.addEventListener('change', function () {
                syncToolbar();
                if (!this.checked && selectAll) selectAll.checked = false;
            });
        });

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.bulk-cb').forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
                syncToolbar();
            });
        }
    }

    initBulkToolbar();

    // Debounced Live AJAX Search
    var searchInput = document.querySelector('input[name="search"]');
    var container = document.getElementById('admins-list-container');
    var debounceTimer = null;

    if (searchInput && container) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            var query = this.value;
            debounceTimer = setTimeout(function () {
                var url = new URL(window.location.href);
                if (query.trim()) {
                    url.searchParams.set('search', query.trim());
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.delete('page');

                fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (res) { return res.text(); })
                .then(function (html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var newContainer = doc.getElementById('admins-list-container');
                    if (newContainer) {
                        container.innerHTML = newContainer.innerHTML;
                        window.history.replaceState(null, '', url.toString());
                        initBulkToolbar();
                    }
                })
                .catch(function (err) {
                    console.error('Search request failed', err);
                });
            }, 300);
        });
    }
}());
</script>
@endpush
