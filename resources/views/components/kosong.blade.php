@props(['ikon' => 'circle-check', 'teks', 'href' => null, 'aksi' => null])

@once
@push('styles')
<style>.ks-aksi { margin-left: 6px; }</style>
@endpush
@endonce

<div class="dash-aman" role="status">
    <i class="ti ti-{{ $ikon }}"></i> {{ $teks }}
    @if ($href && $aksi)
        <a href="{{ $href }}" class="ks-aksi">{{ $aksi }}</a>
    @endif
</div>
