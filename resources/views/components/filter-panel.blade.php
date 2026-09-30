@props(['filter' => []])
@php
    $chips = \App\Support\FilterAktif::chips(request(), $filter);
    $reset = \App\Support\FilterAktif::reset(request());
@endphp
<details class="fpanel" data-k="fpanel">
    <summary class="btn btn-sm" aria-label="Filter" data-live-sync="fpanel-btn"><i class="ti ti-adjustments-horizontal"></i> Filter @if ($chips)<span class="fpanel-n">{{ count($chips) }}</span>@endif</summary>
    <div class="fpanel-isi" role="group" aria-label="Pilihan filter">
        <div class="fpanel-body">{{ $slot }}</div>
        <div class="fpanel-foot" data-live-sync="fpanel-foot">
            <a href="{{ $reset }}" class="btn btn-sm" target="_self">Reset</a>
            <button type="button" class="btn btn-sm btn-brand" data-fpanel-tutup>Tutup</button>
        </div>
    </div>
</details>
<div class="fchips" data-live-sync="fchips">
    @if ($chips)
        @foreach ($chips as $c)
            <a href="{{ $c['url'] }}" class="fchip" aria-label="Hapus filter {{ $c['label'] }}">{{ $c['label'] }} <i class="ti ti-x"></i></a>
        @endforeach
        <a href="{{ \App\Support\FilterAktif::hapusSemua(request()) }}" class="fchips-hapus" target="_self">Hapus semua</a>
    @endif
</div>
