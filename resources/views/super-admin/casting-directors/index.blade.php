@extends('layouts.app')

@section('title', 'Kelola Casting Director')

@section('content')
<div class="card-header-row">
    <div style="font-size: 16px; font-weight: 600;">Kelola Casting Director</div>
    <div style="display: flex; gap: 8px;">
        <button type="button" class="btn" data-copy-link="{{ route('register.cd') }}">Copy Link Register CD</button>
        <button type="button" class="btn btn-brand" onclick="document.getElementById('add-cd-dialog').showModal()">+ Tambah Casting Director</button>
    </div>
</div>

<dialog id="add-cd-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 480px; width: 90%;">
    <div style="padding: 18px;">
        <div style="font-size: 15px; font-weight: 600; margin-bottom: 14px;">Tambah Akun Casting Director</div>

        @if ($errors->any())
            <div class="alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('super-admin.casting-directors.store') }}">
            @csrf
            <label>Nama</label>
            <input type="text" name="name" value="{{ old('name') }}" required>

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>

            <x-password-input name="password" label="Password" :minlength="8" />

            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
            </div>
        </form>
    </div>
</dialog>

@if ($errors->any())
    <script>document.getElementById('add-cd-dialog').showModal();</script>
@endif

@foreach ($cds as $cd)
    <div class="card" style="margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
            <div>
                <div style="font-weight: 600; font-size: 14.5px;">
                    {{ $cd->name }}
                    <span class="badge badge-pending">{{ $cd->role }}</span>
                    <span class="badge {{ $cd->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ $cd->status }}</span>
                </div>
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
