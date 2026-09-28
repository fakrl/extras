{{-- BA.4: rincian cocok (tag dicari peran) + tag Extras per grup. Param: profile, aplikasi? --}}
@php
    $aplikasi ??= null;
    $dicari = $aplikasi?->castingProjectClass?->categories ?? collect();
    $punya = $profile->categories->modelKeys();
    $urutanGrup = array_flip(array_keys(\App\Models\ExtrasCategory::GRUP));
    $perGrup = $profile->categories->sortBy('nama')->groupBy(fn ($c) => $c->grup ?: 'Lainnya')
        ->sortBy(fn ($v, $grup) => $urutanGrup[$grup] ?? 99);
@endphp
@if ($dicari->isNotEmpty())
    <div class="xsec">Cocok dengan peran · {{ $aplikasi->persenCocok() }}%</div>
    @foreach ($dicari as $tag)
        @php $ada = in_array($tag->id, $punya); @endphp
        <div class="xrow">
            <span><i @class(['ti', 'ti-circle-check xrow-ok' => $ada, 'ti-circle xrow-no' => ! $ada]) aria-hidden="true"></i> #{{ $tag->nama }}</span>
            <span class="xrow-muted">{{ $ada ? 'sesuai' : 'belum punya' }}</span>
        </div>
    @endforeach
@endif
<div class="xsec">Tag</div>
@forelse ($perGrup as $grup => $tags)
    <div class="xtag-grup">{{ $grup }}</div>
    <div class="xcard-tags">
        @foreach ($tags as $tag)
            <span @class(['xtag', 'is-hit' => $dicari->contains('id', $tag->id)])>#{{ $tag->nama }}</span>
        @endforeach
    </div>
@empty
    <div class="xrow-muted" style="font-size: var(--fs-sm);">Belum pilih tag.</div>
@endforelse
