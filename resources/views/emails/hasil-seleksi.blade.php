<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JBTB Casting</title>
</head>
<body style="margin: 0; background: #f3f4f1; font-family: Arial, sans-serif; color: #1a1a1a; line-height: 1.6;">
<div style="max-width: 560px; margin: 0 auto; background: #ffffff;">
    <div style="padding: 16px 24px; background: #0b1a12; color: #ffffff; font-size: 16px; font-weight: bold;">JBTB Casting</div>
    <div style="padding: 24px;">
    @if ($lolos)
        <p>Halo,</p>
        <p><strong>Selamat!</strong> Kamu dinyatakan <strong>lolos</strong> seleksi untuk proyek "{{ $namaProduksi }}".</p>
        <p>Silakan masuk ke akunmu untuk lanjut ke tahap berikutnya (lengkapi KTP & rekening, lalu tanda tangan kontrak).</p>
    @else
        <p>Halo,</p>
        <p>Terima kasih sudah mendaftar di proyek "{{ $namaProduksi }}". Kali ini kamu belum lolos seleksi.</p>
        @if ($alasan)
            <p>Catatan dari admin: {{ $alasan }}</p>
        @endif
        <p>Jangan berkecil hati, terus pantau lowongan lain yang tersedia ya.</p>
    @endif
    <p style="margin: 24px 0;"><a href="{{ route('login') }}" style="display: inline-block; padding: 12px 24px; background: #15803d; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">Masuk</a></p>
    <p>Terima kasih,<br>Tim JBTB Casting</p>
    <p style="font-size: 12px; color: #5b6b60;">Tombol tidak berfungsi? Buka {{ route('login') }}</p>
    </div>
</div>
</body>
</html>
