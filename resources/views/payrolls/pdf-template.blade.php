<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        .total-row { font-weight: bold; }
    </style>
</head>
<body>
    <h2>SLIP HONOR</h2>
    <p>
        Nama: {{ $assignment->user->name }}<br>
        Peran: {{ $assignment->user->role }}<br>
        Proyek: {{ $assignment->castingProject->nama_produksi }}<br>
        Status: Selesai pada {{ $assignment->completed_at->format('d M Y') }}
    </p>

    <table>
        <thead><tr><th>Komponen</th><th>Nominal</th></tr></thead>
        <tbody>
            <tr><td>Honor Pokok</td><td>Rp {{ number_format($payroll->nominal_pokok, 0, ',', '.') }}</td></tr>
            @foreach ($payroll->addons as $addon)
                <tr><td>{{ $addon->label }}</td><td>Rp {{ number_format($addon->nominal, 0, ',', '.') }}</td></tr>
            @endforeach
            <tr class="total-row">
                <td>Total</td>
                <td>Rp {{ number_format($payroll->nominalTotal(), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top:30px; font-size:10px; color:#666;">
        Slip ini dihasilkan otomatis oleh SIM Casting JBTB berdasarkan status penyelesaian proyek.
        Nominal honor bersifat per-event, bukan gaji bulanan tetap.
    </p>
</body>
</html>
