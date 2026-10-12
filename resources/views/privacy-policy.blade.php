<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <script>try { document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light'); } catch (e) {}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>Kebijakan Privasi | JBTB Casting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.theme-style')
    <style>
        body { padding: 32px 20px; line-height: 1.65; }
        .wrap { max-width: 680px; margin: 0 auto; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .updated { color: var(--text-secondary); font-size: 13px; margin-bottom: 28px; }
        h2 { font-size: 15.5px; margin-top: 28px; margin-bottom: 8px; }
        p, li { font-size: 14px; color: var(--text-secondary); }
        ul { padding-left: 20px; }
        a { color: var(--accent-strong); }
        .back { display: inline-flex; align-items: center; min-height: 44px; margin-top: 24px; font-size: 13.5px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 13.5px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--border-color); }
        th { color: var(--text-primary); font-weight: 600; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="wrap">
        <h1>Kebijakan Privasi</h1>
        <p class="updated">JBTB Casting · Terakhir diperbarui 12 Oktober 2026</p>

        <p>
            Halaman ini menjelaskan data pribadi apa saja yang dikumpulkan dan disimpan oleh SIM Casting JBTB,
            untuk apa data itu dipakai, dan siapa saja yang bisa melihatnya. Sistem ini dipakai secara internal
            oleh JBTB Casting untuk mengelola proses casting talent (Extras).
        </p>

        <h2>Data yang kami simpan</h2>
        <div class="table-container">
        <table>
            <thead>
                <tr><th>Data</th><th>Disimpan untuk siapa</th></tr>
            </thead>
            <tbody>
                <tr><td>Nama lengkap & NIK</td><td>Extras</td></tr>
                <tr><td>Nomor rekening</td><td>Extras (untuk pembayaran)</td></tr>
                <tr><td>Nama panggung/alias, foto, video perkenalan</td><td>Extras</td></tr>
                <tr><td>Tautan sosial media/portofolio (opsional)</td><td>Extras</td></tr>
                <tr><td>Nomor WhatsApp (dipakai untuk mengirim notifikasi seleksi, kontrak, dan pengingat jadwal)</td><td>Extras, Client, dan staf yang mengisinya</td></tr>
                <tr><td>Akun Google (nama dan email, bila kamu masuk atau daftar lewat Google)</td><td>Extras, Client, dan staf yang memakainya</td></tr>
                <tr><td>Profil publik (nama panggung, foto, usia dalam rentang, tinggi badan, dan tag penampilan), bila kamu mengizinkan profilmu ditampilkan atau dibagikan lewat tautan</td><td>Extras yang mengizinkan</td></tr>
                <tr><td>Nama, email, kata sandi (terenkripsi)</td><td>Semua akun (Admin, Client, Extras)</td></tr>
                <tr><td>Riwayat pendaftaran, negosiasi fee, kontrak, pembayaran</td><td>Extras yang mendaftar proyek</td></tr>
            </tbody>
        </table>
        </div>

        <h2>Siapa yang bisa melihat data kamu</h2>
        <p>Kami membatasi akses data berdasarkan peran (role), bukan membuka semua data ke semua orang:</p>
        <ul>
            <li><strong>Client</strong> hanya melihat nama panggung (alias), foto, dan video. Nama asli, NIK, kontak, rekening, dan tautan sosial media/portofolio Extras <strong>tidak pernah</strong> ditampilkan ke Client.</li>
            <li><strong>Admin</strong> dapat melihat data lengkap Extras (termasuk nama asli & NIK) untuk keperluan verifikasi dan administrasi, serta data fee/pembayaran untuk keperluan operasional.</li>
            <li><strong>Extras</strong> hanya bisa melihat profilnya sendiri secara lengkap, bukan data Extras lain.</li>
            <li><strong>Pengunjung umum</strong> hanya bisa melihat profil publik Extras yang mengizinkannya, tanpa NIK, nama asli, kontak, dan rekening.</li>
        </ul>

        <h2>Penggunaan data</h2>
        <p>Data yang kamu berikan digunakan semata-mata untuk proses seleksi casting, negosiasi fee, pembuatan kontrak, dan pembayaran honor, bukan untuk tujuan lain, dan tidak dibagikan ke pihak ketiga di luar proses tersebut.</p>
        <p>Akun Extras yang profilnya tidak dilengkapi (foto &amp; NIK) dan belum pernah mendaftar proyek dalam {{ \App\Models\User::HARI_MANGKRAK }} hari sejak daftar akan dihapus otomatis beserta datanya, setelah kami kirim pemberitahuan minimal 7 hari sebelumnya.</p>

        <h2>Keamanan data</h2>
        <p>NIK, nama asli, dan nomor rekening disimpan dalam bentuk terenkripsi di database. Foto dan video disimpan di penyimpanan privat yang hanya bisa diakses lewat aplikasi (bukan link publik langsung), dengan pengecekan otorisasi setiap kali diakses.</p>

        <h2>Pertanyaan</h2>
        <p>Kalau kamu punya pertanyaan soal data pribadimu di sistem ini, hubungi Admin JBTB Casting.</p>

        <a href="{{ route('home') }}" class="back">&larr; Kembali ke Beranda</a>
    </main>
</body>
</html>
