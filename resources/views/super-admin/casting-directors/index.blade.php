@extends('layouts.app')

@section('title', 'Kelola Casting Director')

@section('content')
<div class="card-header-row">
    <div style="font-size: 16px; font-weight: 600;">Kelola Casting Director</div>
</div>

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
@endsection
