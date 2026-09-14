<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: sans-serif; font-size: 12px; color: #222; }
h2 { font-size: 14px; margin-bottom: 4px; }
p { margin: 0 0 12px; font-size: 11px; color: #666; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
th { background: #f0f0f0; }
</style>
</head>
<body>
<h2>Riwayat Keputusan — {{ $castingProject->nama_produksi }}</h2>
<p>Diekspor {{ now()->format('d M Y') }}</p>
<table>
    <thead>
        <tr><th>Alias</th><th>Keputusan</th><th>Tanggal</th></tr>
    </thead>
    <tbody>
        @foreach ($reviews as $review)
        <tr>
            <td>{{ $review->projectApplication->extras->alias ?? '-' }}</td>
            <td>{{ ucfirst($review->keputusan) }}</td>
            <td>{{ $review->created_at->format('d M Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
