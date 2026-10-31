@props(['events'])

@php
    $grouped = collect($events)
        ->groupBy(fn ($e) => \Illuminate\Support\Carbon::parse($e->tanggal)->format('Y-m-d'))
        ->sortKeys();
@endphp

@once
@push('styles')
<style>
.agenda-tgl-label { font-size: 12.5px; font-weight: 700; color: var(--text-secondary); margin: 14px 0 6px; display: flex; align-items: center; gap: 8px; }
.agenda-tgl-label:first-child { margin-top: 0; }
.agenda-kosong { margin: 0; }
.agenda-card-row:hover { background: var(--bg-card-hover) !important; border-color: var(--accent) !important; }
</style>
@endpush
@endonce

<div class="agenda">
    @forelse ($grouped as $tanggal => $daftar)
        @php $t = \Illuminate\Support\Carbon::parse($tanggal); @endphp
        <div class="agenda-tgl-label">
            <span>{{ $t->translatedFormat('l, d F Y') }}</span>
            @if ($t->isToday())<span class="badge badge-aktif">Hari ini</span>@endif
        </div>
        @foreach ($daftar as $e)
            @include('components.agenda-item', ['e' => $e, 'tanggalCarbon' => $t, 'itemIndex' => $loop->parent->index . '-' . $loop->index])
        @endforeach
    @empty
        <p class="dash-sub agenda-kosong">Tidak ada kegiatan di periode ini.</p>
    @endforelse
</div>
