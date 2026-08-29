@extends('layouts.app')

@section('title', 'Lowongan Casting')

@section('content')
@forelse ($projects as $project)
    <div class="card" style="margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="font-size: 15px; font-weight: 600;">
                {{ $project->nama_produksi }}
                @if ($project->is_urgent)
                    <span class="badge badge-tolak">Butuh Dadakan</span>
                @endif
            </div>
            <span style="color: var(--text-muted); font-size: 13px;">Deadline: {{ $project->deadline->format('d M Y') }}</span>
        </div>
        <p style="margin: 8px 0 4px; font-size: 13.5px;">Client: {{ $project->client_ph }}</p>
        <p style="margin: 0 0 12px; font-size: 12.5px; color: var(--text-muted);">
            {{ $project->classes->count() }} kelas dibuka
        </p>
        <a href="{{ route('extras.projects.show', $project) }}" class="btn btn-brand btn-sm">Lihat Detail & Daftar</a>
    </div>
@empty
    <p style="color: var(--text-muted);">Belum ada lowongan yang dibuka saat ini.</p>
@endforelse
@endsection
