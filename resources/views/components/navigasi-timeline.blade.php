@props(['dari', 'sampai', 'skala', 'action' => null])

@php
    $base = $action ?? url()->current();
    $bawa = collect(request()->except(['skala', 'mulai']))->filter(fn ($v) => is_scalar($v));
    $tautan = fn (string $skalaBaru, string $mulai) => $base.'?'.http_build_query($bawa->toArray() + ['skala' => $skalaBaru, 'mulai' => $mulai]);
    $sekarang = \App\Support\Timeline::iniSekarang($dari, $sampai);
    $hariIni = today()->format('Y-m-d');
@endphp

@once
@push('styles')
<style>
.nt-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 10px; }
.nt-seg { display: inline-flex; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; }
.nt-seg a { padding: 10px 12px; min-height: 44px; display: inline-flex; align-items: center; font-size: 13px; color: inherit; text-decoration: none; }
.nt-seg a[aria-current=true] { background: var(--accent); color: #fff; }
.nt-nav { display: inline-flex; gap: 4px; }
.nt-judul { font-weight: 600; font-size: 13.5px; margin-left: auto; }
</style>
@endpush
@endonce

<div class="nt-bar" data-k="navigasi-timeline">
    <div class="nt-seg" aria-label="Skala waktu">
        @foreach (\App\Support\Timeline::SKALA as $val => $lbl)
            <a href="{{ $tautan($val, $hariIni) }}" aria-current="{{ $skala === $val ? 'true' : 'false' }}">{{ $lbl }}</a>
        @endforeach
    </div>
    <div class="nt-nav">
        <a href="{{ $tautan($skala, \App\Support\Timeline::geser($dari, $skala, -1)) }}" class="btn" aria-label="Sebelumnya">&lsaquo;</a>
        <a href="{{ $tautan($skala, \App\Support\Timeline::geser($dari, $skala, 1)) }}" class="btn" aria-label="Berikutnya">&rsaquo;</a>
    </div>
    @unless ($sekarang)
        <a href="{{ $tautan($skala, $hariIni) }}" class="btn">Kembali ke hari ini</a>
    @endunless
    <span class="nt-judul">{{ \App\Support\Timeline::label($dari, $sampai) }}</span>
</div>
