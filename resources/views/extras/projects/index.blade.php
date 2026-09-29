@extends('layouts.app')

@section('title', 'Casting Call')

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Casting Call</div>
<p style="color: var(--text-secondary); margin: 0 0 16px; font-size: 13.5px;">Daftar proyek casting yang sedang mencari Extras.</p>

<div style="position: relative; margin-bottom: 16px;">
    <input type="text" id="search-casting-call" placeholder="Cari judul proyek, client PH..."
           style="width: 100%; max-width: 400px; padding: 8px 14px 8px 36px; border: 1px solid var(--border-color); border-radius: 8px; font-size: var(--fs-md); background: var(--bg-card); color: var(--text-primary); margin-bottom: 0;">
    <i class="ti ti-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 15px;"></i>
</div>

<div id="no-casting-match" class="card" style="display: none; text-align: center; color: var(--text-muted); padding: 24px;">
    Tidak ada lowongan yang sesuai dengan pencarian.
</div>

<div id="casting-list">
@forelse ($aktif as $project)
    <div class="card casting-card-item" style="margin-bottom: 14px;" data-search="{{ strtolower($project->nama_produksi . ' ' . $project->client_ph) }}">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="font-size: 15px; font-weight: 600;">
                {{ $project->nama_produksi }}
                @if ($project->isUrgent())
                    <span class="badge badge-tolak">Butuh Dadakan</span>
                @endif
            </div>
            <span style="color: var(--text-muted); font-size: 13px;">Deadline: {{ $project->deadline->format('d M Y') }}</span>
        </div>
        <p style="margin: 8px 0 4px; font-size: 13.5px;">Client: {{ $project->client_ph }}</p>
        <p style="margin: 0 0 12px; font-size: 12.5px; color: var(--text-muted);">
            {{ $project->classes->count() }} peran · Kuota terisi: {{ $project->terisi }}/{{ $project->kuota }}
        </p>
        @if ($project->classes->isNotEmpty())
            <div style="margin-bottom: 14px;">@include('partials.peran-lowongan', ['classes' => $project->classes])</div>
        @endif
        <a href="{{ route('extras.projects.show', $project) }}" class="btn btn-brand btn-sm">Lihat Detail & Daftar</a>
    </div>
@empty
    <p style="color: var(--text-muted);">Belum ada casting call yang dibuka saat ini.</p>
@endforelse

@if ($selesai->isNotEmpty())
    <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); margin: 24px 0 10px; padding-bottom: 6px; border-bottom: 1px solid var(--border-color);">Sudah Selesai</div>
    @foreach ($selesai as $project)
        <div class="card casting-card-item" style="margin-bottom: 14px; opacity: 0.7;" data-search="{{ strtolower($project->nama_produksi . ' ' . $project->client_ph) }}">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="font-size: 15px; font-weight: 600;">{{ $project->nama_produksi }}</div>
                <x-status-badge :model="$project" />
            </div>
            <p style="margin: 8px 0 4px; font-size: 13.5px;">Client: {{ $project->client_ph }}</p>
            <p style="margin: 0 0 12px; font-size: 12.5px; color: var(--text-muted);">
                Kuota terisi: {{ $project->terisi }}/{{ $project->kuota }}
            </p>
            <a href="{{ route('extras.projects.show', $project) }}" class="btn btn-sm">Lihat Detail</a>
        </div>
    @endforeach
@endif
</div>

@push('scripts')
<script>
(function () {
    var searchInput = document.getElementById('search-casting-call');
    var cards = document.querySelectorAll('.casting-card-item');
    var noMatch = document.getElementById('no-casting-match');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        var visibleCount = 0;
        cards.forEach(function (card) {
            var text = card.dataset.search || card.textContent.toLowerCase();
            var match = !q || text.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });
        if (noMatch) {
            noMatch.style.display = (visibleCount === 0 && q.length > 0) ? 'block' : 'none';
        }
    });
})();
</script>
@endpush
@endsection
