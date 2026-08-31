<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        table { width: 100%; margin-top: 20px; }
        td { padding: 4px 0; }
        .signature-box { width: 45%; display: inline-block; margin-top: 40px; text-align: center; }
        .signature-box img { max-height: 80px; }
        .signature-line { border-top: 1px solid #333; margin-top: 5px; padding-top: 5px; }
    </style>
</head>
<body>
    <h2>SURAT PERSETUJUAN TALENT (TALENT RELEASE)</h2>

    <table>
        <tr><td width="30%">Nama Produksi</td><td>: {{ $application->castingProject->nama_produksi }}</td></tr>
        <tr><td>Client / Production House</td><td>: {{ $application->castingProject->client_ph }}</td></tr>
        <tr><td>Nama Talent (sesuai KTP)</td><td>: {{ $application->extras->nama_asli }}</td></tr>
        <tr><td>Nama Panggung (Alias)</td><td>: {{ $application->extras->alias }}</td></tr>
        <tr><td>Fee Disepakati</td><td>: Rp {{ number_format($application->fee_final, 0, ',', '.') }}</td></tr>
        <tr><td>Grade</td><td>: {{ $application->grade }}</td></tr>
    </table>

    <p style="margin-top:20px">
        Dengan ini kedua belah pihak menyetujui kerja sama produksi sesuai ketentuan di atas.
        Fee yang tercantum merupakan hasil kesepakatan final melalui proses negosiasi pada sistem.
    </p>

    <div style="margin-top:40px">
        <div class="signature-box">
            <p>Pihak Agensi (Admin)</p>
            @if ($application->contract->ttd_admin_signature_path)
                <img src="{{ storage_path('app/private/' . $application->contract->ttd_admin_signature_path) }}">
            @endif
            <div class="signature-line">Admin PT. JBTB Casting Creative Group</div>
        </div>
        <div class="signature-box" style="float:right">
            <p>Pihak Talent</p>
            @if ($application->contract->ttd_extras_signature_path)
                <img src="{{ storage_path('app/private/' . $application->contract->ttd_extras_signature_path) }}">
            @endif
            <div class="signature-line">{{ $application->extras->nama_asli }}</div>
        </div>
    </div>
</body>
</html>
