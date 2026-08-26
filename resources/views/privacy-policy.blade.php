<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kebijakan Privasi — SIM Casting JBTB</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root[data-theme="dark"] {
            --bg-page: #0d1b12; --bg-card: #132a1b; --text-primary: #f0fdf4;
            --text-secondary: #a8c2ae; --border-color: rgba(255,255,255,0.06); --accent: #22c55e;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif; background: var(--bg-page); color: var(--text-primary);
            margin: 0; padding: 32px 20px; line-height: 1.65;
        }
        .wrap { max-width: 680px; margin: 0 auto; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .updated { color: var(--text-secondary); font-size: 13px; margin-bottom: 28px; }
        h2 { font-size: 15.5px; margin-top: 28px; margin-bottom: 8px; }
        p, li { font-size: 14px; color: var(--text-secondary); }
        ul { padding-left: 20px; }
        a { color: var(--accent); }
        .back { display: inline-block; margin-top: 32px; font-size: 13.5px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 13.5px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--border-color); }
        th { color: var(--text-primary); font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Kebijakan Privasi</h1>
        <p class="updated">SIM Casting JBTB · Terakhir diperbarui {{ now()->translatedFormat('d F Y') }}</p>

        <p>
            Halaman ini menjelaskan data pribadi apa saja yang dikumpulkan dan disimpan oleh SIM Casting JBTB,
            untuk apa data itu dipakai, dan siapa saja yang bisa melihatnya. Sistem ini dipakai secara internal
            oleh JBTB Casting untuk mengelola proses casting talent (Extras).
        </p>

        <h2>Data yang kami simpan</h2>
        <table>
            <thead>
                <tr><th>Data</th><th>Disimpan untuk siapa</th></tr>
            </thead>
            <tbody>
                <tr><td>Nama lengkap & NIK</td><td>Extras</td></tr>
                <tr><td>Nomor rekening</td><td>Extras (untuk pembayaran)</td></tr>
                <tr><td>Nama panggung/alias, foto, video perkenalan</td><td>Extras</td></tr>
                <tr><td>Tautan sosial media/portofolio (opsional)</td><td>Extras</td></tr>
                <tr><td>Nama, email, password (terenkripsi)</td><td>Semua akun (Admin, CD, Extras)</td></tr>
                <tr><td>Riwayat pendaftaran, negosiasi fee, kontrak, pembayaran</td><td>Extras yang mendaftar proyek</td></tr>
            </tbody>
        </table>

        <h2>Siapa yang bisa melihat data kamu</h2>
        <p>Kami membatasi akses data berdasarkan peran (role), bukan membuka semua data ke semua orang:</p>
        <ul>
            <li><strong>Casting Director / Client</strong> hanya melihat nama panggung (alias), foto, dan video — nama asli, NIK, kontak, rekening, dan tautan sosial media/portofolio Extras <strong>tidak pernah</strong> ditampilkan ke Casting Director.</li>
            <li><strong>Admin</strong> dapat melihat data lengkap Extras (termasuk nama asli & NIK) untuk keperluan verifikasi dan administrasi, serta data fee/pembayaran untuk keperluan operasional.</li>
            <li><strong>Extras</strong> hanya bisa melihat profilnya sendiri secara lengkap — bukan data Extras lain.</li>
        </ul>

        <h2>Penggunaan data</h2>
        <p>Data yang kamu berikan digunakan semata-mata untuk proses seleksi casting, negosiasi fee, pembuatan kontrak, dan pembayaran honor — bukan untuk tujuan lain, dan tidak dibagikan ke pihak ketiga di luar proses tersebut.</p>

        <h2>Keamanan data</h2>
        <p>NIK, nama asli, dan nomor rekening disimpan dalam bentuk terenkripsi di database. Foto dan video disimpan di penyimpanan privat yang hanya bisa diakses lewat aplikasi (bukan link publik langsung), dengan pengecekan otorisasi setiap kali diakses.</p>

        <h2>Pertanyaan</h2>
        <p>Kalau kamu punya pertanyaan soal data pribadimu di sistem ini, hubungi Admin JBTB Casting.</p>

        <a href="javascript:history.back()" class="back">&larr; Kembali</a>
    </div>
</body>
</html>
