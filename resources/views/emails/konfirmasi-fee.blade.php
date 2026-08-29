<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1a1a1a; line-height: 1.6;">
    <p>Halo,</p>
    <p>Ada penawaran fee baru untuk proyek "{{ $namaProduksi }}" dari {{ $diajukanOleh === 'admin' ? 'Admin' : 'Extras' }}:</p>
    <p style="font-size: 18px; font-weight: bold;">Rp {{ number_format($nominal, 0, ',', '.') }}</p>
    <p>Silakan masuk ke akunmu untuk terima atau ajukan tawaran balik.</p>
    <p>Terima kasih,<br>Tim JBTB Casting</p>
</body>
</html>
