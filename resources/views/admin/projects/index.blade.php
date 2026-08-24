@extends('layouts.app')

@section('title', 'Manajemen Proyek Casting')

@section('content')
<div class="card-header-row">
    <div style="font-size: 16px; font-weight: 600;">Proyek Casting</div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-brand">+ Buka Lowongan Baru</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Nama Produksi</th><th>Client/PH</th><th>Deadline</th><th>Pendaftar</th><th>Status</th><th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projects as $project)
                <tr>
                    <td>
                        {{ $project->nama_produksi }}
                        @if ($project->is_urgent)
                            <span class="badge badge-tolak">Urgent</span>
                        @endif
                    </td>
                    <td>{{ $project->client_ph }}</td>
                    <td>{{ $project->deadline->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('admin.projects.applicants', $project) }}">{{ $project->applications_count }} orang</a>
                    </td>
                    <td>
                        <span class="badge {{ $project->status === 'dibuka' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $project->status }}
                        </span>
                    </td>
                    <td style="display: flex; gap: 6px;">
                        <form method="POST" action="{{ route('admin.projects.toggle-status', $project) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm">
                                {{ $project->status === 'dibuka' ? 'Tutup' : 'Buka Lagi' }}
                            </button>
                        </form>
                        <a href="{{ route('invoices.show', $project) }}" class="btn btn-sm">Invoice</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
