@extends('layouts.app')

@section('title', 'Jadwal Shooting')

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Jadwal Shooting</div>

@if ($projects->isEmpty())
    <div class="card" style="color: var(--text-muted);">Belum ada proyek yang ditugaskan ke kamu.</div>
@else
    @foreach ($projects as $project)
        <div class="card" style="margin-bottom: 14px;">
            <div style="font-size: 14.5px; font-weight: 600; margin-bottom: 8px;">{{ $project->nama_produksi }}</div>
            @if ($project->shootingDates->isEmpty())
                <div style="color: var(--text-muted); font-size: 13px; margin-bottom: 8px;">Belum ada tanggal shooting.</div>
            @else
                <div style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 8px;">
                    {{ $project->shootingDates->count() }} tanggal shooting
                    @php $kosong = $project->shootingDates->whereNull('lokasi')->count(); @endphp
                    @if ($kosong > 0)
                        &mdash; <span style="color: var(--color-warning, #b45309);">{{ $kosong }} belum diisi jadwal</span>
                    @endif
                </div>
            @endif
            <a href="{{ route('cd.jadwal.show', $project) }}" class="btn btn-brand" style="min-height:32px; padding:0 14px; font-size:12.5px;">Input Jadwal</a>
        </div>
    @endforeach
@endif
@endsection
