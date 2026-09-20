@extends('layouts.app')

@section('title', 'Manajemen Karyawan')

@section('content')
<div class="card-header-row">
    <div style="font-size: 16px; font-weight: 600;">Manajemen Admin & Staf</div>
    <button type="button" class="btn btn-brand" onclick="document.getElementById('add-admin-dialog').showModal()">+ Tambah Admin</button>
</div>

<!-- Bagian AG & AI: Filter/Tab Role & Status 5-Role -->
<div style="display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap;">
    <a href="?role=all&status={{ $statusFilter }}" class="btn {{ $roleFilter === 'all' ? 'btn-brand' : '' }}">Semua Role</a>
    <a href="?role=admin&status={{ $statusFilter }}" class="btn {{ in_array($roleFilter, ['admin', 'admin_default']) ? 'btn-brand' : '' }}">Admin</a>
    <a href="?role=korlap&status={{ $statusFilter }}" class="btn {{ in_array($roleFilter, ['korlap', 'admin_korlap']) ? 'btn-brand' : '' }}">Korlap</a>
    <a href="?role=client&status={{ $statusFilter }}" class="btn {{ in_array($roleFilter, ['client', 'casting_director']) ? 'btn-brand' : '' }}">Client</a>
    <a href="?role=super_admin&status={{ $statusFilter }}" class="btn {{ $roleFilter === 'super_admin' ? 'btn-brand' : '' }}">Super Admin</a>
</div>

<div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; align-items: center;">
    <span style="font-size: 12.5px; color: var(--text-muted); font-weight: 600;">Filter Status:</span>
    <a href="?role={{ $roleFilter }}&status=all" class="btn {{ $statusFilter === 'all' ? 'btn-brand' : '' }}">Semua</a>
    <a href="?role={{ $roleFilter }}&status=aktif" class="btn {{ $statusFilter === 'aktif' ? 'btn-brand' : '' }}">Aktif</a>
    <a href="?role={{ $roleFilter }}&status=nonaktif" class="btn {{ $statusFilter === 'nonaktif' ? 'btn-brand' : '' }}">Nonaktif / Arsip</a>
</div>

<dialog id="add-admin-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 480px; width: 90%;">
    <div style="padding: 18px;">
        <div style="font-size: 15px; font-weight: 600; margin-bottom: 14px;">Tambah Akun Staf / Admin</div>

        @if ($errors->any())
            <div class="alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

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
                @if (auth()->user()->is_protected)
                    <option value="super_admin" @selected(old('role') === 'super_admin')>Super Admin</option>
                @endif
            </select>
            <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 14px;">Pilih jenis akun staf yang ingin ditambahkan.</p>

            <label>Nominal Honor per Event (Rp)</label>
            <input type="number" name="honor_nominal" min="0" value="{{ old('honor_nominal') }}">
            <p style="font-size: 12px; color: var(--text-muted); margin: -10px 0 18px;">Honor standing per proyek (khusus Korlap/staf proyek). Bisa diadjust kapan saja.</p>

            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
            </div>
        </form>
    </div>
</dialog>

@foreach ($admins as $cd)
    <div class="card" style="margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
            <div>
                <a href="{{ route('super-admin.admins.show', $cd) }}" style="text-decoration: none;">
                    <div style="font-weight: 600; font-size: 14.5px; color: var(--text-primary);">
                        {{ $cd->name }}
                        <span class="badge badge-pending">{{ $cd->role }}</span>
                        <span class="badge {{ $cd->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ $cd->status }}</span>
                    </div>
                </a>
                <div style="color: var(--text-muted); font-size: 12.5px;">{{ $cd->email }}</div>
            </div>

            <div style="display: flex; gap: 6px; align-items: center;">
                @if ($cd->trashed() || $cd->status === 'nonaktif')
                    <button type="button" class="btn btn-sm btn-brand" onclick="document.getElementById('restore-dialog-{{ $cd->id }}').showModal()">
                        Aktifkan Kembali
                    </button>
                @else
                    @if ($cd->is_protected)
                        <button type="button" class="btn btn-sm" disabled title="Akun ini terproteksi sistem.">Nonaktifkan</button>
                    @else
                        <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('deactivate-dialog-{{ $cd->id }}').showModal()">
                            Nonaktifkan
                        </button>
                    @endif
                @endif
            </div>
        </div>

        @if ($cd->trashed() || $cd->status === 'nonaktif')
            <dialog id="restore-dialog-{{ $cd->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
                <form method="POST" action="{{ route('super-admin.admins.restore', $cd->id) }}" style="padding: 18px;">
                    @csrf @method('PATCH')
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">
                        Aktifkan kembali {{ $cd->name }}?
                    </div>
                    <div style="color: var(--text-muted); font-size: 12.5px; margin-bottom: 12px;">Akun akan dapat digunakan kembali untuk login dan menerima penugasan.</div>
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                        <button type="submit" class="btn btn-sm btn-brand">Ya, Aktifkan</button>
                    </div>
                </form>
            </dialog>
        @else
            @if (! $cd->is_protected)
                <dialog id="deactivate-dialog-{{ $cd->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
                    <form method="POST" action="{{ route('super-admin.admins.destroy', $cd) }}" style="padding: 18px;">
                        @csrf @method('DELETE')
                        <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Nonaktifkan {{ $cd->name }}?</div>
                        <div style="color: var(--text-muted); font-size: 12.5px; margin-bottom: 12px;">Akun akan dinonaktifkan. Data dan histori penugasan tetap tersimpan aman di database.</div>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                            <button type="submit" class="btn btn-sm btn-danger-outline">Nonaktifkan</button>
                        </div>
                    </form>
                </dialog>
            @endif
        @endif
    </div>
@endforeach

<script>
    document.querySelectorAll('[data-copy-link]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(btn.dataset.copyLink).then(function () {
                var original = btn.textContent;
                btn.textContent = 'Link disalin!';
                setTimeout(function () { btn.textContent = original; }, 2000);
            });
        });
    });
</script>
@endsection
