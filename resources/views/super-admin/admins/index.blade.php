@extends('layouts.app')

@section('title', 'Manajemen Karyawan')

@section('content')
<div class="card-header-row">
    <div style="font-size: 16px; font-weight: 600;">Manajemen Admin & Staf</div>
    <a href="{{ route('super-admin.admins.create') }}" class="btn btn-brand">+ Tambah Admin</a>
</div>

@foreach ($admins as $admin)
    <div class="card" style="margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="font-weight: 600; font-size: 14.5px;">{{ $admin->name }} <span class="badge badge-pending">{{ $admin->role }}</span></div>
                <div style="color: var(--text-muted); font-size: 12.5px;">{{ $admin->email }}</div>
            </div>
            <form method="POST" action="{{ route('super-admin.admins.honor', $admin) }}" style="display: flex; gap: 6px;">
                @csrf @method('PATCH')
                <input type="number" name="honor_nominal" value="{{ $admin->adminProfile->honor_nominal ?? '' }}"
                       style="width:150px; min-height:32px; padding:4px 8px; margin-bottom:0;" placeholder="Honor/event">
                <button class="btn btn-sm">Simpan</button>
            </form>
        </div>

        @if ($admin->role !== 'admin_default')
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
