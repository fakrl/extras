@extends('layouts.app')

@section('title', 'Callsheet — Proyek Casting')

@section('content')
<div class="card-header-row">
    <div>
        <div style="font-size: 16px; font-weight: 600;">Callsheet</div>
        <div style="font-size: 12.5px; color: var(--text-secondary);">Semua proyek casting yang sedang & pernah dibuka</div>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-brand">+ Buka Lowongan Baru</a>
</div>

@if ($projects->isEmpty())
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">
        Belum ada proyek casting. Klik "+ Buka Lowongan Baru" untuk membuat yang pertama.
    </div>
@else
    <div class="entity-card-grid">
        @foreach ($projects as $project)
            <div class="entity-card">
                <div class="entity-card-title">
                    {{ $project->nama_produksi }}
                    @if ($project->is_urgent)
                        <span class="badge badge-tolak">Urgent</span>
                    @endif
                </div>
                <div class="entity-card-sub">{{ $project->client_ph }}</div>

                @if ($project->wa_group_link)
                    <div class="entity-card-row">
                        <span class="entity-card-row-label">Grup WA</span>
                        <span class="entity-card-row-value">
                            <a href="{{ $project->wa_group_link }}" target="_blank">Buka Link</a>
                        </span>
                    </div>
                @endif

                <div class="entity-card-row">
                    <span class="entity-card-row-label">Deadline</span>
                    <span class="entity-card-row-value">{{ $project->deadline->format('d M Y') }}</span>
                </div>
                <div class="entity-card-row">
                    <span class="entity-card-row-label">Status</span>
                    <span class="entity-card-row-value">
                        <span class="badge {{ $project->status === 'dibuka' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $project->status }}
                        </span>
                    </span>
                </div>
                <div class="entity-card-row">
                    <span class="entity-card-row-label">Pendaftar</span>
                    <span class="entity-card-row-value">{{ $project->applications_count }} orang</span>
                </div>

                <div class="entity-card-actions">
                    <a href="{{ route('admin.projects.applicants', $project) }}" class="btn btn-brand" style="flex: 1; text-align: center;">
                        Lihat Lineup ({{ $project->applications_count }})
                    </a>
                </div>
                <div class="entity-card-actions">
                    <a href="{{ route('admin.projects.edit', $project) }}" class="btn" style="flex: 1; text-align: center;">Edit</a>
                </div>
                <div class="entity-card-actions">
                    <form method="POST" action="{{ route('admin.projects.toggle-status', $project) }}" style="flex: 1;">
                        @csrf @method('PATCH')
                        <button class="btn" style="width: 100%;">
                            {{ $project->status === 'dibuka' ? 'Tutup Lowongan' : 'Buka Lagi' }}
                        </button>
                    </form>
                    <a href="{{ route('invoices.show', $project) }}" class="btn" style="flex: 1; text-align: center;">Invoice</a>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
