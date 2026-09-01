@extends('layouts.app')

@section('title', 'Manajemen Karyawan')

@section('content')
<div class="card-header-row">
    <div style="font-size: 16px; font-weight: 600;">Manajemen Admin & Staf</div>
    <a href="{{ route('super-admin.admins.create') }}" class="btn btn-brand">+ Tambah Admin</a>
</div>

@php
    $subRoleAdmin = ['admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed'];
    $subRoleWithAssignment = ['admin_talco', 'admin_korlap', 'admin_sosmed'];
@endphp

@foreach ($admins as $admin)
    <div class="card" style="margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
            <div>
                <div style="font-weight: 600; font-size: 14.5px;">
                    {{ $admin->name }}
                    <span class="badge badge-pending">{{ $admin->role }}</span>
                    <span class="badge {{ $admin->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ $admin->status }}</span>
                </div>
                <div style="color: var(--text-muted); font-size: 12.5px;">{{ $admin->email }}</div>
            </div>

            <div style="display: flex; gap: 6px; align-items: center;">
                @if (in_array($admin->role, $subRoleAdmin, true))
                    <form method="POST" action="{{ route('super-admin.admins.honor', $admin) }}" style="display: flex; gap: 6px;">
                        @csrf @method('PATCH')
                        <input type="number" name="honor_nominal" value="{{ $admin->adminProfile?->honor_nominal ?? '' }}"
                               style="width:150px; min-height:32px; padding:4px 8px; margin-bottom:0;" placeholder="Honor/event">
                        <button class="btn btn-sm">Simpan</button>
                    </form>
                @endif

                <button type="button" class="btn btn-sm" onclick="document.getElementById('toggle-dialog-{{ $admin->id }}').showModal()">
                    {{ $admin->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>

                @if ($admin->is_protected)
                    <button type="button" class="btn btn-sm" disabled title="Akun ini dilindungi, tidak bisa dihapus.">Hapus</button>
                @elseif ($admin->has_history)
                    <button type="button" class="btn btn-sm" disabled title="Akun ini punya riwayat penugasan, nonaktifkan saja.">Hapus</button>
                @else
                    <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('delete-dialog-{{ $admin->id }}').showModal()">Hapus</button>
                @endif
            </div>
        </div>

        <dialog id="toggle-dialog-{{ $admin->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('super-admin.admins.toggle-status', $admin) }}" style="padding: 18px;">
                @csrf @method('PATCH')
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">
                    {{ $admin->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $admin->name }}?
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Ya, Lanjutkan</button>
                </div>
            </form>
        </dialog>

        @if (! $admin->is_protected && ! $admin->has_history)
            <dialog id="delete-dialog-{{ $admin->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
                <form method="POST" action="{{ route('super-admin.admins.destroy', $admin) }}" style="padding: 18px;">
                    @csrf @method('DELETE')
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Hapus permanen {{ $admin->name }}?</div>
                    <div style="color: var(--text-muted); font-size: 12.5px; margin-bottom: 12px;">Aksi ini tidak bisa dibatalkan.</div>
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger-outline">Hapus Permanen</button>
                    </div>
                </form>
            </dialog>
        @endif

        @if (in_array($admin->role, $subRoleWithAssignment, true))
            <hr>
            <div style="display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label>Tugaskan ke Proyek</label>
                    <select class="assign-project-select" style="width:220px; margin-bottom:0;" data-user="{{ $admin->id }}">
                        <option value="">Pilih proyek</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->nama_produksi }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-sm btn-brand btn-assign" data-user="{{ $admin->id }}">Tugaskan</button>
            </div>

            @if ($admin->adminProjectAssignments->isNotEmpty())
                <table style="margin-top: 10px;">
                    <thead><tr><th>Proyek</th><th>Status</th><th>Honor</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($admin->adminProjectAssignments as $assignment)
                            <tr>
                                <td>{{ $assignment->castingProject->nama_produksi }}</td>
                                <td>{{ $assignment->status_log }}</td>
                                <td>{{ $assignment->payroll ? 'Rp '.number_format($assignment->payroll->nominalTotal(), 0, ',', '.') : '-' }}</td>
                                <td>
                                    @if ($assignment->status_log === 'berjalan')
                                        <form method="POST" action="{{ route('super-admin.assignments.complete', $assignment) }}">
                                            @csrf
                                            <button class="btn btn-sm">Tandai Selesai</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    </div>
@endforeach
@endsection

@push('scripts')
<script>
    var CSRF_TOKEN = '{{ csrf_token() }}';

    document.querySelectorAll('.btn-assign').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var userId = btn.dataset.user;
            var select = document.querySelector('.assign-project-select[data-user="' + userId + '"]');
            var projectId = select.value;
            if (!projectId) { alert('Pilih proyek dulu'); return; }

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '/super-admin/projects/' + projectId + '/assign';
            form.innerHTML = '<input type="hidden" name="_token" value="' + CSRF_TOKEN + '">' +
                '<input type="hidden" name="user_id" value="' + userId + '">';
            document.body.appendChild(form);
            form.submit();
        });
    });
</script>
@endpush
