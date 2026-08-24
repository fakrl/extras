<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        .signature-box { width: 45%; display: inline-block; margin-top: 40px; text-align: center; }
        .signature-box img { max-height: 80px; }
    </style>
</head>
<body>
    <h2>INVOICE</h2>
    <p>Produksi: {{ $castingProject->nama_produksi }}<br>Client: {{ $castingProject->client_ph }}</p>

    <table>
        <thead><tr><th>Kelas</th><th>Kuota</th><th>Budget per Orang</th><th>Subtotal</th></tr></thead>
        <tbody>
            @foreach ($castingProject->classes as $class)
                <tr>
                    <td>{{ $class->nama_kelas }}</td>
                    <td>{{ $class->kuota_kelas }}</td>
                    <td>Rp {{ number_format($class->budget_client, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($class->budget_client * $class->kuota_kelas, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:40px">
        <div class="signature-box">
            <p>PT. JBTB Casting Creative Group</p>
            @if ($invoice->ttd_admin_signature_path)
                <img src="{{ storage_path('app/private/' . $invoice->ttd_admin_signature_path) }}">
            @endif
        </div>
        <div class="signature-box" style="float:right">
            <p>Casting Director</p>
            @if ($invoice->ttd_cd_signature_path)
                <img src="{{ storage_path('app/private/' . $invoice->ttd_cd_signature_path) }}">
            @endif
        </div>
    </div>
</body>
</html>
