@php $url = $e->url_proyek ?? null; @endphp
<div class="dash-row">
    <div style="min-width: 0;">
        <strong>{{ $e->nama_produksi }}</strong>
        @if (! empty($e->jumlah_extras))
            <div class="dash-sub">{{ $e->jumlah_extras }} Extras @if (! empty($e->absensi)) &middot; {{ $e->absensi }} @endif</div>
        @endif
        @if (! empty($e->lokasi) || ! empty($e->jam_mulai))
            <div class="dash-sub">
                @if (! empty($e->jam_mulai)){{ substr($e->jam_mulai, 0, 5) }}@if (! empty($e->jam_selesai)) &ndash; {{ substr($e->jam_selesai, 0, 5) }}@endif @endif
                @if (! empty($e->lokasi)) &middot; {{ $e->lokasi }} @endif
            </div>
        @endif
        @if (! empty($e->catatan))
            <div class="dash-sub">{{ $e->catatan }}</div>
        @endif
    </div>
    @if ($url || ! empty($e->url_absensi))
        <div style="display: flex; gap: 10px; flex-shrink: 0;">
            @if ($url)<a href="{{ $url }}" class="dash-sub">Buka &rarr;</a>@endif
            @if (! empty($e->url_absensi))<a href="{{ $e->url_absensi }}" class="dash-sub">Absensi &rarr;</a>@endif
        </div>
    @endif
</div>
