<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1a1a1a; line-height: 1.6;">
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
    <p>Terima kasih,<br>Tim JBTB Casting</p>
</body>
</html>
