@extends('layouts.app')

@section('title', 'Lowongan Casting')

@section('content')
<div style="font-size: 18px; font-weight: 700; margin-bottom: 2px;">Katalog Lowongan Casting</div>
<p style="color: var(--text-secondary); margin: 0 0 16px; font-size: 13.5px;">Daftar proyek casting yang sedang aktif mencari pemeran figuran (Extras).</p>

{{-- Search Input --}}
<div style="position: relative; margin-bottom: 10px;">
    <input type="text" id="search-casting-call" placeholder="Cari judul proyek, peran, nama PH/Client..."
           style="width: 100%; max-width: 480px; padding: 10px 14px 10px 38px; border: 1px solid var(--border-color); border-radius: 8px; font-size: var(--fs-md); background: var(--bg-card); color: var(--text-primary); margin-bottom: 0;">
    <i class="ti ti-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 16px;"></i>
</div>

{{-- Quick Filter Pills --}}
<div class="quick-filter-bar" id="quick-filters" style="margin-bottom: 16px;">
    <button type="button" class="quick-filter-chip is-active" data-filter="all">Semua Lowongan ({{ $aktif->count() }})</button>
    <button type="button" class="quick-filter-chip" data-filter="urgent">⚡ Butuh Dadakan ({{ $aktif->filter->isUrgent()->count() }})</button>
    <button type="button" class="quick-filter-chip" data-filter="kuota">🎯 Masih Ada Kuota</button>
</div>

<div id="no-casting-match" class="card" style="display: none; text-align: center; color: var(--text-muted); padding: 24px;">
    Tidak ada lowongan yang sesuai dengan filter atau pencarian saat ini.
</div>

<div id="casting-list" class="job-board-grid">
@forelse ($aktif as $project)
    @php
        $isFull = $project->classes->every(fn ($c) => $c->sisaKuota() === 0);
        $searchTerms = strtolower($project->nama_produksi . ' ' . $project->namaClient() . ' ' . $project->classes->pluck('nama_kelas')->join(' '));
    @endphp
    <div class="card casting-card-item" style="margin-bottom: 0; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;"
         data-search="{{ $searchTerms }}"
         data-urgent="{{ $project->isUrgent() ? '1' : '0' }}"
         data-full="{{ $isFull ? '1' : '0' }}">
        
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 16px; font-weight: 700; color: var(--text-primary); line-height: 1.3;">
                        {{ $project->nama_produksi }}
                        @if ($project->isUrgent())
                            <span class="badge badge-urgent" style="margin-left: 4px;">Butuh Dadakan</span>
                        @endif
                        @include('partials.label-nego', ['project' => $project])
                    </div>
                    <div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">
                        Client: <strong>{{ $project->namaClient() }}</strong>
                        @if ($project->kode_proyek)
                            · <span class="badge badge-netral">{{ $project->kode_proyek }}</span>
                        @endif
                    </div>
                </div>
                <div style="text-align: right;">
                    <span class="badge badge-warning" style="font-size: 11.5px;"><i class="ti ti-clock"></i> Deadline: {{ $project->deadline->translatedFormat('d M Y') }}</span>
                </div>
            </div>

            {{-- Metadata Box 2x2 --}}
            <div class="jb-meta-grid">
                <div>
                    <span style="font-size: 10.5px; text-transform: uppercase; color: var(--text-muted); display: block; font-weight: 600;">Jadwal Syuting</span>
                    <strong style="color: var(--text-primary); font-size: 12px;">
                        @if ($project->shootingDates->isNotEmpty())
                            {{ $project->shootingDates->first()->tanggal->translatedFormat('d M Y') }}@if($project->shootingDates->count() > 1) (+{{ $project->shootingDates->count() - 1 }} hari)@endif
                        @else
                            -
                        @endif
                    </strong>
                </div>
                <div>
                    <span style="font-size: 10.5px; text-transform: uppercase; color: var(--text-muted); display: block; font-weight: 600;">Wilayah Produksi</span>
                    <strong style="color: var(--text-primary); font-size: 12px;">Jabodetabek</strong>
                </div>
                <div>
                    <span style="font-size: 10.5px; text-transform: uppercase; color: var(--text-muted); display: block; font-weight: 600;">Sisa Kuota Peran</span>
                    <strong style="color: var(--accent-strong); font-size: 12px;">{{ $project->terisi }}/{{ $project->kuota }} terisi</strong>
                </div>
                <div>
                    <span style="font-size: 10.5px; text-transform: uppercase; color: var(--text-muted); display: block; font-weight: 600;">Pilihan Peran</span>
                    <strong style="color: var(--text-primary); font-size: 12px;">{{ $project->classes->count() }} peran dibuka</strong>
                </div>
            </div>

            @if ($project->classes->isNotEmpty())
                <div style="margin-bottom: 14px;">@include('partials.peran-lowongan', ['classes' => $project->classes])</div>
            @endif
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-color);">
            <a href="{{ route('extras.projects.show', $project) }}" class="btn btn-brand" style="justify-content: center; width: 100%;">Lihat Detail &amp; Lamar &rarr;</a>
        </div>
    </div>
@empty
    <p style="color: var(--text-muted); grid-column: 1 / -1;">Belum ada lowongan yang dibuka saat ini.</p>
@endforelse

@if ($selesai->isNotEmpty())
    <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); margin: 24px 0 10px; padding-bottom: 6px; border-bottom: 1px solid var(--border-color);">Sudah Selesai</div>
    @foreach ($selesai->take(5) as $project)
        <div class="card casting-card-item" style="margin-bottom: 14px; opacity: 0.7;" data-search="{{ strtolower($project->nama_produksi . ' ' . $project->namaClient()) }}" data-urgent="0" data-full="1">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="font-size: 15px; font-weight: 600;">{{ $project->nama_produksi }}</div>
                <x-status-badge :model="$project" />
            </div>
            <p style="margin: 8px 0 4px; font-size: 13.5px;">Client: {{ $project->namaClient() }}</p>
            <p style="margin: 0 0 12px; font-size: 12.5px; color: var(--text-muted);">
                Kuota terisi: {{ $project->terisi }}/{{ $project->kuota }}
            </p>
            <a href="{{ route('extras.projects.show', $project) }}" class="btn btn-tonal">Lihat Detail</a>
        </div>
    @endforeach
    @if ($selesai->count() > 5)
        <p style="color: var(--text-muted); font-size: 12.5px;">Menampilkan 5 dari {{ $selesai->count() }} lowongan yang sudah selesai.</p>
    @endif
@endif
</div>

@push('scripts')
<script>
(function () {
    var searchInput = document.getElementById('search-casting-call');
    var cards = document.querySelectorAll('.casting-card-item');
    var noMatch = document.getElementById('no-casting-match');
    var filterButtons = document.querySelectorAll('#quick-filters .quick-filter-chip');
    var currentFilter = 'all';

    function filterCards() {
        var q = searchInput ? searchInput.value.toLowerCase().trim() : '';
        var visibleCount = 0;

        cards.forEach(function (card) {
            var text = card.dataset.search || card.textContent.toLowerCase();
            var isUrgent = card.dataset.urgent === '1';
            var isFull = card.dataset.full === '1';

            var matchSearch = !q || text.includes(q);
            var matchFilter = true;

            if (currentFilter === 'urgent') {
                matchFilter = isUrgent;
            } else if (currentFilter === 'kuota') {
                matchFilter = !isFull;
            }

            var visible = matchSearch && matchFilter;
            card.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        if (noMatch) {
            noMatch.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterCards);
    }

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterButtons.forEach(function (b) { b.classList.remove('is-active'); });
            this.classList.add('is-active');
            currentFilter = this.dataset.filter;
            filterCards();
        });
    });
})();
</script>
@endpush
@endsection
