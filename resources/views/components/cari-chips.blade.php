@props(['filter' => [], 'pertahankan' => []])
@php
    $chips = \App\Support\FilterAktif::chips(request(), $filter);
@endphp
<div class="cari-chips">
    <i class="ti ti-search cari-chips-ikon" aria-hidden="true"></i>
    <div class="cari-chips-list" data-live-sync="fchips">
        @foreach ($chips as $c)
            <a href="{{ $c['url'] }}" class="fchip" aria-label="Hapus filter {{ $c['label'] }}">{{ $c['label'] }} <i class="ti ti-x"></i></a>
        @endforeach
    </div>
    {{ $slot }}
    <div class="cari-chips-clear" data-live-sync="fchips-hapus">
        @if ($chips)
            <a href="{{ \App\Support\FilterAktif::hapusSemua(request(), $pertahankan) }}" class="fchips-hapus" aria-label="Hapus semua filter aktif" target="_self"><i class="ti ti-x"></i></a>
        @endif
    </div>
</div>
