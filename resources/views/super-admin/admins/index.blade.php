@extends('layouts.app')

@section('title', 'Manajemen Karyawan')

@section('content')
<div class="card-header-row">
    <div style="font-size: 16px; font-weight: 600;">Manajemen Admin & Staf</div>
    <button type="button" class="btn btn-brand" onclick="document.getElementById('add-admin-dialog').showModal()">+ Tambah Admin</button>
</div>

<!-- Bagian AG: Filter/Tab Role -->
<div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
    <a href="?role=all" class="btn {{ $roleFilter === 'all' ? 'btn-brand' : '' }}">Semua</a>
    <a href="?role=admin_default" class="btn {{ $roleFilter === 'admin_default' ? 'btn-brand' : '' }}">Admin Default</a>
    <a href="?role=admin_talco" class="btn {{ $roleFilter === 'admin_talco' ? 'btn-brand' : '' }}">Talco</a>
    <a href="?role=admin_korlap" class="btn {{ $roleFilter === 'admin_korlap' ? 'btn-brand' : '' }}">Korlap</a>
    <a href="?role=admin_sosmed" class="btn {{ $roleFilter === 'admin_sosmed' ? 'btn-brand' : '' }}">Sosmed</a>
    <a href="?role=super_admin" class="btn {{ $roleFilter === 'super_admin' ? 'btn-brand' : '' }}">Super Admin</a>
    <a href="?role=casting_director" class="btn {{ $roleFilter === 'casting_director' ? 'btn-brand' : '' }}">Casting Director</a>
</div>

<dialog id="add-admin-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 480px; width: 90%;">
    <div style="padding: 18px;">
        <div style="font-size: 15px; font-weight: 600; margin-bottom: 14px;">Tambah Akun Admin</div>

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
                <option value="admin_default" @selected(old('role') === 'admin_default')>Admin Default (operasional penuh)</option>
                <option value="admin_talco" @selected(old('role') === 'admin_talco')>Talent Coordinator (Talco)</option>
                <option value="admin_korlap" @selected(old('role') === 'admin_korlap')>Koordinator Lapangan (Korlap)</option>
                <option value="admin_sosmed" @selected(old('role') === 'admin_sosmed')>Sosial Media / Multimedia</option>
                @if (auth()->user()->is_protected)
                    <option value="super_admin" @selected(old('role') === 'super_admin')>Super Admin</option>
                @endif
            </select>
            <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 14px;">Talco/Korlap/Sosmed adalah cabang kewenangan terbatas, ditugaskan per proyek sesuai kebutuhan.</p>

            <label>Nominal Honor per Event (Rp)</label>
            <input type="number" name="honor_nominal" min="0" value="{{ old('honor_nominal') }}">
            <p style="font-size: 12px; color: var(--text-muted); margin: -10px 0 18px;">Kosongkan jika tidak relevan (misal untuk Admin Default/Super Admin). Bisa diadjust kapan saja.</p>

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
                <button type="button" class="btn btn-sm" onclick="document.getElementById('toggle-dialog-{{ $cd->id }}').showModal()">
                    {{ $cd->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>

                @if ($cd->is_protected)
                    <button type="button" class="btn btn-sm" disabled title="Akun ini dilindungi, tidak bisa dihapus.">Hapus</button>
                @elseif ($cd->has_history)
                    <button type="button" class="btn btn-sm" disabled title="Akun ini punya riwayat penugasan, nonaktifkan saja.">Hapus</button>
                @else
                    <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('delete-dialog-{{ $cd->id }}').showModal()">Hapus</button>
                @endif
            </div>
        </div>

        <dialog id="toggle-dialog-{{ $cd->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('super-admin.admins.toggle-status', $cd) }}" style="padding: 18px;">
                @csrf @method('PATCH')
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">
                    {{ $cd->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $cd->name }}?
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Ya, Lanjutkan</button>
                </div>
            </form>
        </dialog>

        @if (! $cd->is_protected && ! $cd->has_history)
            <dialog id="delete-dialog-{{ $cd->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
                <form method="POST" action="{{ route('super-admin.admins.destroy', $cd) }}" style="padding: 18px;">
                    @csrf @method('DELETE')
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Hapus permanen {{ $cd->name }}?</div>
                    <div style="color: var(--text-muted); font-size: 12.5px; margin-bottom: 12px;">Aksi ini tidak bisa dibatalkan.</div>
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger-outline">Hapus Permanen</button>
                    </div>
                </form>
            </dialog>
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
