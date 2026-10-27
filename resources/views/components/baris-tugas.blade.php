@props(['judul', 'meta' => null, 'umur' => null])

@once
@push('styles')
<style>
.bt-judul { white-space: normal; }
.bt-aksi { display: flex; gap: 8px; flex-shrink: 0; flex-wrap: wrap; align-items: center; }
.bt-aksi .btn { min-height: 44px; }
.bt-info { min-width: 0; }
</style>
@endpush
@endonce

<div class="dash-row">
    <div class="bt-info">
        <strong class="bt-judul">{{ $judul }}</strong>
        @if ($meta)<div class="dash-sub">{{ $meta }}</div>@endif
        @if ($umur)<div class="dash-sub">{{ $umur }}</div>@endif
    </div>
    <div class="bt-aksi">{{ $slot }}</div>
</div>
