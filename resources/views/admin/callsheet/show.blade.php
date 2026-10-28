@extends('layouts.app')

@section('title', 'Callsheet '.$castingProject->nama_produksi)

@section('content')
<div style="font-size: var(--fs-xs); color: var(--text-muted); margin-bottom: 4px;">
    @if ($bolehKelola)
        <a href="{{ route('admin.projects.show', $castingProject) }}">&larr; {{ $castingProject->nama_produksi }}</a>
    @else
        <a href="{{ route('admin.dashboard') }}">&larr; Dashboard</a>
    @endif
</div>
<div class="card-header-row" style="flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
    <div>
        <div style="font-size: 18px; font-weight: 700;">Callsheet · {{ $shootingDate->tanggal->translatedFormat('l, d F Y') }}</div>
        <div style="font-size: var(--fs-sm); color: var(--text-secondary);">{{ $castingProject->nama_produksi }} <span class="kode-proyek">{{ $castingProject->kode_proyek }}</span></div>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn btn-brand" id="callsheet-salin-wa" data-log-url="{{ route('admin.callsheet.salin', [$castingProject, $shootingDate->tanggal->toDateString()]) }}">
            <i class="ti ti-copy"></i> Salin format WA
        </button>
        <a href="{{ route('admin.callsheet.pdf', [$castingProject, $shootingDate->tanggal->toDateString()]) }}" class="btn">
            <i class="ti ti-file-download"></i> Unduh PDF
        </a>
    </div>
</div>

@if ($warnings)
    <div role="alert" style="display: flex; align-items: flex-start; gap: 8px; padding: 10px 14px; border: 1px solid var(--warning); border-radius: 10px; background: rgba(234,179,8,0.1); color: var(--warning); font-size: var(--fs-sm); margin-bottom: 14px;">
        <i class="ti ti-alert-triangle" style="margin-top: 2px;"></i>
        <div>
            <strong>Lengkapi dulu:</strong> {{ implode(', ', $warnings) }}.
            @if ($bolehKelola)
                <a href="{{ route('admin.projects.edit', $castingProject) }}" style="display: block; margin-top: 2px;">Buka form edit proyek &rarr;</a>
            @endif
        </div>
    </div>
@endif

<div class="card" style="margin-bottom: 14px;">
    <div class="card-title">Info Umum</div>
    @foreach (array_filter([
        'Client' => $namaClient,
        'Lokasi' => $shootingDate->lokasi ?: '(belum diisi)',
        'Jam Callsheet' => $shootingDate->jam_mulai ? substr($shootingDate->jam_mulai, 0, 5).($shootingDate->jam_selesai ? '–'.substr($shootingDate->jam_selesai, 0, 5) : '').' WIB' : '(belum diisi)',
    ], fn ($v) => $v !== null) as $label => $value)
        <div class="entity-card-row">
            <span class="entity-card-row-label">{{ $label }}</span>
            <span class="entity-card-row-value">{{ $value }}</span>
        </div>
    @endforeach
    @if ($mapsUrl)
        <div class="entity-card-row">
            <span class="entity-card-row-label">Google Maps</span>
            <span class="entity-card-row-value"><a href="{{ $mapsUrl }}" target="_blank" rel="noopener">Buka lokasi &rarr;</a></span>
        </div>
    @endif
</div>

@foreach ($peran as $p)
    <div class="card" style="margin-bottom: 14px;">
        <div class="card-title">{{ $p['nama'] }}</div>
        <div class="entity-card-row"><span class="entity-card-row-label">Jam Kumpul</span><span class="entity-card-row-value">{{ $p['jam_kumpul'] ?: '(belum diisi)' }}</span></div>
        <div class="entity-card-row"><span class="entity-card-row-label">Kostum / Scene</span><span class="entity-card-row-value">{{ $p['kostum_scene'] ?: '(belum diisi)' }}</span></div>
        <div class="entity-card-row"><span class="entity-card-row-label">Kontinuitas</span><span class="entity-card-row-value">{{ $p['kontinuitas'] }}</span></div>
        <div style="margin-top: 10px; font-size: var(--fs-sm);">
            @foreach ($p['talents'] as $t)
                <div style="padding: 4px 0; border-top: 1px solid var(--border-color);">
                    {{ $t['no'] }}. {{ $t['nama'] }}
                    @if ($t['catatan'])
                        <span style="color: var(--text-muted);">({{ $t['catatan'] }})</span>
                    @endif
                    @if ($t['kontrak_belum_ttd'] && $bolehKelola)
                        <span class="badge badge-pending" title="Tidak ikut tampil di teks WA/PDF">Kontrak belum TTD</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endforeach

@if ($panggilan)
    <div class="card" style="margin-bottom: 14px;">
        <div class="card-title">Daftar Panggilan</div>
        @foreach ($panggilan as $pg)
            <div class="entity-card-row"><span class="entity-card-row-label">{{ $pg['nama'] }}</span><span class="entity-card-row-value">{{ $pg['jam'] }}</span></div>
        @endforeach
    </div>
@endif

<div class="card" style="margin-bottom: 14px;">
    <div class="card-title">Korlap Bertugas</div>
    @forelse ($korlap as $k)
        <div class="entity-card-row"><span class="entity-card-row-label">{{ $k['nama'] }}</span><span class="entity-card-row-value">{{ $k['wa'] ? '+'.$k['wa'] : '(belum diisi)' }}</span></div>
    @empty
        <div style="color: var(--text-muted); font-size: var(--fs-sm);">Belum ada Korlap ditugaskan.</div>
    @endforelse
</div>

@if ($shootingDate->catatan)
    <div class="card" style="margin-bottom: 14px;">
        <div class="card-title">Catatan</div>
        <div style="font-size: var(--fs-sm);">{{ $shootingDate->catatan }}</div>
    </div>
@endif

<div class="card">
    <div class="card-title">Pratinjau Teks WA</div>
    <textarea id="callsheet-wa-text" readonly rows="10" style="width: 100%; font-family: monospace; font-size: var(--fs-sm);">{{ $teksWa }}</textarea>
</div>

@push('scripts')
<script>
(function () {
    var btn = document.getElementById('callsheet-salin-wa');
    var teks = document.getElementById('callsheet-wa-text');
    btn.addEventListener('click', function () {
        var ok = function () {
            btn.innerHTML = '<i class="ti ti-check"></i> Tersalin';
            setTimeout(function () { btn.innerHTML = '<i class="ti ti-copy"></i> Salin format WA'; }, 2000);
            fetch(btn.dataset.logUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
        };
        var fallback = function () { teks.select(); try { document.execCommand('copy'); ok(); } catch (e) {} };
        if (navigator.clipboard) navigator.clipboard.writeText(teks.value).then(ok, fallback); else fallback();
    });
})();
</script>
@endpush
@endsection
