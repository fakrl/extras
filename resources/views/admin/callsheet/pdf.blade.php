<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { text-align: center; margin-bottom: 4px; }
        .sub { text-align: center; color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        td { padding: 3px 0; vertical-align: top; }
        .peran-title { font-size: 13px; font-weight: bold; background: #f0f0f0; padding: 4px 6px; margin-top: 10px; }
        .talent-list { margin: 4px 0 0; padding-left: 16px; }
        .catatan { color: #555; }
    </style>
</head>
<body>
    <h2>CALLSHEET - {{ $castingProject->nama_produksi }}</h2>
    <div class="sub">{{ $castingProject->kode_proyek }}@if ($namaClient) &middot; {{ $namaClient }} @endif &middot; {{ $shootingDate->tanggal->translatedFormat('l, d F Y') }}</div>

    <table>
        <tr><td width="22%">Lokasi</td><td>: {{ $shootingDate->lokasi ?: '(belum diisi)' }}@if ($mapsUrl) &nbsp; ({{ $mapsUrl }}) @endif</td></tr>
        <tr><td>Jam Callsheet</td><td>: {{ $shootingDate->jam_mulai ? substr($shootingDate->jam_mulai, 0, 5).($shootingDate->jam_selesai ? '-'.substr($shootingDate->jam_selesai, 0, 5) : '') : '(belum diisi)' }} WIB</td></tr>
    </table>

    @foreach ($peran as $p)
        <div class="peran-title">{{ $p['nama'] }} &middot; Kumpul {{ $p['jam_kumpul'] ?: '(belum diisi)' }} &middot; {{ $p['kostum_scene'] ?: '(belum diisi)' }} &middot; {{ $p['kontinuitas'] }}</div>
        <ol class="talent-list">
            @foreach ($p['talents'] as $t)
                <li>{{ $t['nama'] }} @if ($t['catatan'])<span class="catatan">({{ $t['catatan'] }})</span>@endif</li>
            @endforeach
        </ol>
    @endforeach

    @if ($panggilan)
        <div class="peran-title">Daftar Panggilan</div>
        <ul class="talent-list">
            @foreach ($panggilan as $pg)
                <li>{{ $pg['nama'] }} ({{ $pg['jam'] }})</li>
            @endforeach
        </ul>
    @endif

    <div class="peran-title">Korlap Bertugas</div>
    <ul class="talent-list">
        @forelse ($korlap as $k)
            <li>{{ $k['nama'] }}{{ $k['wa'] ? ' - +'.$k['wa'] : '' }}</li>
        @empty
            <li>(belum ditugaskan)</li>
        @endforelse
    </ul>

    @if ($shootingDate->catatan)
        <div class="peran-title">Catatan</div>
        <p>{{ $shootingDate->catatan }}</p>
    @endif
</body>
</html>
