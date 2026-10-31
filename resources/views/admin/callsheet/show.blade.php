@extends('layouts.app')

@section('title', 'Callsheet '.$castingProject->nama_produksi)

@section('content')
<div style="margin-bottom: 12px;">
    <a href="{{ $bolehKelola ? route('admin.projects.show', $castingProject) : route('admin.dashboard') }}" class="btn btn-sm btn-tonal" style="display: inline-flex; align-items: center; gap: 6px;">
        <i class="ti ti-arrow-left"></i> Kembali ke {{ $bolehKelola ? 'Proyek' : 'Dashboard' }}
    </a>
</div>
<div class="card-header-row" style="flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
    <div>
        <div style="font-size: 18px; font-weight: 700;">Callsheet · {{ $shootingDate->tanggal->translatedFormat('l, d F Y') }}</div>
        <div style="font-size: var(--fs-sm); color: var(--text-secondary);">{{ $castingProject->nama_produksi }} <span class="kode-proyek">{{ $castingProject->kode_proyek }}</span></div>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="https://api.whatsapp.com/send?text={{ rawurlencode($teksWa) }}" target="_blank" rel="noopener" class="btn btn-brand callsheet-btn-wa" data-log-url="{{ route('admin.callsheet.salin', [$castingProject, $shootingDate->tanggal->toDateString()]) }}" style="display: inline-flex; align-items: center; gap: 6px;">
            <i class="ti ti-brand-whatsapp"></i> Share ke WA
        </a>
        <button type="button" class="btn btn-tonal" id="callsheet-salin-wa" data-log-url="{{ route('admin.callsheet.salin', [$castingProject, $shootingDate->tanggal->toDateString()]) }}">
            <i class="ti ti-copy"></i> Salin format WA
        </button>
        <a href="{{ route('admin.callsheet.pdf', [$castingProject, $shootingDate->tanggal->toDateString(), 'preview' => 1]) }}" target="_blank" class="btn btn-tonal">
            <i class="ti ti-eye"></i> Pratinjau PDF
        </a>
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
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
        <div class="card-title" style="margin: 0;">Pratinjau Teks WA</div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <a href="https://api.whatsapp.com/send?text={{ rawurlencode($teksWa) }}" target="_blank" rel="noopener" class="btn btn-sm btn-brand callsheet-btn-wa" data-log-url="{{ route('admin.callsheet.salin', [$castingProject, $shootingDate->tanggal->toDateString()]) }}" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="ti ti-brand-whatsapp"></i> Share ke WA
            </a>
            <button type="button" class="btn btn-sm btn-tonal" id="callsheet-salin-wa-card" data-log-url="{{ route('admin.callsheet.salin', [$castingProject, $shootingDate->tanggal->toDateString()]) }}" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="ti ti-copy"></i> Salin format WA
            </button>
        </div>
    </div>
    <textarea id="callsheet-wa-text" readonly rows="10" style="width: 100%; font-family: monospace; font-size: var(--fs-sm);">{{ $teksWa }}</textarea>
</div>

@push('scripts')
<script>
(function () {
    var teks = document.getElementById('callsheet-wa-text');
    var copyBtns = [
        document.getElementById('callsheet-salin-wa'),
        document.getElementById('callsheet-salin-wa-card')
    ].filter(Boolean);

    copyBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var originalHtml = btn.innerHTML;
            var ok = function () {
                btn.innerHTML = '<i class="ti ti-check"></i> Tersalin';
                setTimeout(function () { btn.innerHTML = originalHtml; }, 2000);
                if (btn.dataset.logUrl) {
                    fetch(btn.dataset.logUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                }
            };
            var fallback = function () { teks.select(); try { document.execCommand('copy'); ok(); } catch (e) {} };
            if (navigator.clipboard) navigator.clipboard.writeText(teks.value).then(ok, fallback); else fallback();
        });
    });

    document.querySelectorAll('.callsheet-btn-wa').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var logUrl = el.dataset.logUrl;
            if (logUrl) {
                fetch(logUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
            }
        });
    });
})();
</script>
@endpush
@endsection
