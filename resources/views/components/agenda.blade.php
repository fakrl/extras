@props(['events'])

@php
    $grouped = collect($events)
        ->groupBy(fn ($e) => \Illuminate\Support\Carbon::parse($e->tanggal)->format('Y-m-d'))
        ->sortKeys();
@endphp

@once
@push('styles')
<style>
.agenda-tgl-label { font-size: 12.5px; font-weight: 700; color: var(--text-secondary); margin: 14px 0 4px; }
.agenda-tgl-label:first-child { margin-top: 0; }
.agenda-kosong { margin: 0; }
.agenda-item-info { min-width: 0; }
.agenda-item-aksi { display: flex; gap: 10px; flex-shrink: 0; }
</style>
@endpush
@endonce

<div class="agenda">
    @forelse ($grouped as $tanggal => $daftar)
        @php $t = \Illuminate\Support\Carbon::parse($tanggal); @endphp
        <div class="agenda-tgl-label">
            {{ $t->translatedFormat('l, d F Y') }}
            @if ($t->isToday())<span class="badge badge-aktif">Hari ini</span>@endif
        </div>
        @foreach ($daftar as $e)
            @include('components.agenda-item', ['e' => $e])
        @endforeach
    @empty
        <p class="dash-sub agenda-kosong">Tidak ada kegiatan di periode ini.</p>
    @endforelse
</div>
