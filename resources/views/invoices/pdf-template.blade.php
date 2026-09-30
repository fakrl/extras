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
    <p>Produksi: {{ $castingProject->nama_produksi }}<br>Client: {{ $castingProject->namaClient() }}</p>

    <table>
        <thead><tr><th>Kelas</th><th>Kuota</th><th>Budget per Orang</th><th>Subtotal</th></tr></thead>
        <tbody>
            @foreach ($rincian->rows as $row)
                <tr>
                    <td>{{ $row->nama_kelas }}</td>
                    <td>{{ $row->kuota_kelas }}</td>
                    <td>Rp {{ number_format($row->budget_client, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($row->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" style="text-align:right; font-weight:bold;">Total</td>
                <td style="font-weight:bold;">Rp {{ number_format($rincian->total, 0, ',', '.') }}</td>
            </tr>
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
            <p>Client</p>
            @if ($invoice->ttd_cd_signature_path)
                <img src="{{ storage_path('app/private/' . $invoice->ttd_cd_signature_path) }}">
            @endif
        </div>
    </div>
</body>
</html>
