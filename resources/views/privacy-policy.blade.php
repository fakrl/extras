<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <script>try { document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light'); } catch (e) {}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>Kebijakan Privasi | JBTB Casting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.48.0/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        :root[data-theme="dark"] {
            --priv-bg: #111311;
            --priv-card: #181b18;
            --priv-surface: #1e221e;
            --priv-fg: #f1f3ed;
            --priv-muted: #9aa298;
            --priv-border: rgba(255, 255, 255, 0.12);
            --priv-accent: #6edb96;
            --priv-accent-on: #003920;
            --priv-chip-bg: rgba(110, 219, 150, 0.12);
            --priv-chip-fg: #8cf6b1;
            --priv-warning-bg: rgba(245, 158, 11, 0.12);
            --priv-warning-fg: #fbbf24;
            --priv-warning-border: rgba(245, 158, 11, 0.25);
        }
        :root[data-theme="light"] {
            --priv-bg: #f8faf6;
            --priv-card: #ffffff;
            --priv-surface: #f1f4ee;
            --priv-fg: #171d17;
            --priv-muted: #555f54;
            --priv-border: #dfe5dc;
            --priv-accent: #15803d;
            --priv-accent-on: #ffffff;
            --priv-chip-bg: #e0f2e5;
            --priv-chip-fg: #0e5627;
            --priv-warning-bg: #fffbeb;
            --priv-warning-fg: #b45309;
            --priv-warning-border: #fde68a;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--priv-bg);
            color: var(--priv-fg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Header */
        .priv-header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: var(--priv-bg);
            border-bottom: 1px solid var(--priv-border);
            backdrop-filter: blur(8px);
        }
        .priv-header-inner {
            max-width: 960px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .priv-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 16px;
            color: var(--priv-fg);
            text-decoration: none;
        }
        .priv-brand img {
            border-radius: 6px;
            object-fit: cover;
        }
        .priv-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .priv-btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            min-height: 40px;
            border-radius: 999px;
            border: 1px solid var(--priv-border);
            background: var(--priv-card);
            color: var(--priv-fg);
            cursor: pointer;
            transition: all .15s ease;
        }
        .priv-btn-icon:hover {
            border-color: var(--priv-accent);
            color: var(--priv-accent);
        }
        .priv-back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--priv-muted);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid var(--priv-border);
            background: var(--priv-card);
            transition: all .15s ease;
        }
        .priv-back-link:hover {
            color: var(--priv-accent);
            border-color: var(--priv-accent);
        }

        /* Main Container */
        .priv-wrap {
            max-width: 860px;
            margin: 0 auto;
            padding: 40px 20px 80px;
        }

        /* Title Area */
        .priv-title-area {
            margin-bottom: 36px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--priv-border);
        }
        .priv-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            background: var(--priv-chip-bg);
            color: var(--priv-chip-fg);
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        h1 {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.25;
            margin: 0 0 10px;
            letter-spacing: -0.02em;
        }
        .priv-updated {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 13px;
            color: var(--priv-muted);
        }

        /* TOC Quick Links */
        .priv-toc {
            background: var(--priv-surface);
            border: 1px solid var(--priv-border);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 36px;
        }
        .priv-toc-title {
            font-size: 13.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--priv-muted);
            margin-bottom: 12px;
        }
        .priv-toc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 8px 16px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .priv-toc-grid a {
            color: var(--priv-fg);
            font-size: 13.5px;
            text-decoration: none;
            display: flex;
            align-items: flex-start;
            gap: 6px;
            transition: color .15s ease;
        }
        .priv-toc-grid a i {
            flex-shrink: 0;
            margin-top: 3px;
        }
        .priv-toc-grid a:hover {
            color: var(--priv-accent);
        }

        /* Content Sections */
        .priv-section {
            margin-bottom: 40px;
        }
        h2 {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 14px;
            color: var(--priv-fg);
            display: flex;
            align-items: center;
            gap: 8px;
            scroll-margin-top: 80px;
        }
        h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0 8px;
            color: var(--priv-fg);
        }
        p {
            font-size: 14.5px;
            color: var(--priv-muted);
            margin: 0 0 14px;
        }
        ul, ol {
            padding-left: 22px;
            margin: 0 0 16px;
        }
        li {
            font-size: 14.5px;
            color: var(--priv-muted);
            margin-bottom: 8px;
        }
        strong {
            color: var(--priv-fg);
        }
        a {
            color: var(--priv-accent);
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        /* Callout Box */
        .priv-callout {
            background: var(--priv-surface);
            border-left: 4px solid var(--priv-accent);
            border-radius: 0 12px 12px 0;
            padding: 16px 20px;
            margin: 18px 0;
            font-size: 14px;
        }
        .priv-callout.warning {
            border-left-color: var(--priv-warning-fg);
            background: var(--priv-warning-bg);
            color: var(--priv-warning-fg);
        }
        .priv-callout.warning strong {
            color: var(--priv-warning-fg);
        }

        /* Table Style */
        .table-container {
            overflow-x: auto;
            border: 1px solid var(--priv-border);
            border-radius: 12px;
            margin: 16px 0 24px;
            background: var(--priv-card);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            min-width: 600px;
        }
        thead tr {
            background: var(--priv-surface);
            border-bottom: 1px solid var(--priv-border);
        }
        th {
            text-align: left;
            padding: 12px 16px;
            font-weight: 600;
            color: var(--priv-fg);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .02em;
        }
        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--priv-border);
            color: var(--priv-muted);
            vertical-align: top;
        }
        tr:last-child td {
            border-bottom: none;
        }

        /* Footer */
        .priv-footer {
            border-top: 1px solid var(--priv-border);
            padding: 28px 20px;
            font-size: 13px;
            color: var(--priv-muted);
            background: var(--priv-surface);
            margin-top: 60px;
        }
        .priv-footer-inner {
            max-width: 860px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        @media (max-width: 640px) {
            h1 { font-size: 24px; }
            h2 { font-size: 18px; }
            .priv-wrap { padding-top: 24px; }
            .priv-footer-inner { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

    <!-- Header Navigasi -->
    <header class="priv-header">
        <div class="priv-header-inner">
            <a href="{{ route('home') }}" class="priv-brand">
                <img src="{{ asset('images/logo-jbtb.jpg') }}" alt="Logo JBTB" width="28" height="28">
                <span>JBTB Casting</span>
            </a>
            <div class="priv-actions">
                <button type="button" class="priv-btn-icon" id="theme-toggle" aria-label="Ganti tema tampilan" title="Ganti mode terang/gelap">
                    <i class="ti ti-moon" id="theme-icon" aria-hidden="true"></i>
                </button>
                <a href="{{ route('home') }}" class="priv-back-link">
                    <i class="ti ti-arrow-left" aria-hidden="true"></i> Beranda
                </a>
            </div>
        </div>
    </header>

    <main class="priv-wrap">
        <!-- Area Judul Dokumen -->
        <div class="priv-title-area">
            <span class="priv-badge">
                <i class="ti ti-shield-check" aria-hidden="true"></i> Kepatuhan UU No. 27 Tahun 2022 (UU PDP)
            </span>
            <h1>Kebijakan Privasi &amp; Perlindungan Data</h1>
            <div class="priv-updated">
                <span><strong>Pengendali Data:</strong> PT. JBTB Casting Creative Group (NIB terdaftar OSS)</span>
                <span>&bull;</span>
                <span>Terakhir diperbarui: 31 Oktober 2026 &middot; Berlaku efektif sejak 12 Oktober 2026</span>
            </div>
        </div>

        <!-- Daftar Isi Cepat -->
        <nav class="priv-toc" aria-label="Daftar Isi Kebijakan Privasi">
            <div class="priv-toc-title">Daftar Isi &amp; Navigasi Cepat</div>
            <ul class="priv-toc-grid">
                <li><a href="#pendahuluan"><i class="ti ti-chevron-right" aria-hidden="true"></i> 1. Pendahuluan &amp; Pengendali Data</a></li>
                <li><a href="#data-disimpan"><i class="ti ti-chevron-right" aria-hidden="true"></i> 2. Data yang Kami Simpan</a></li>
                <li><a href="#dasar-hukum"><i class="ti ti-chevron-right" aria-hidden="true"></i> 3. Dasar Hukum &amp; Penggunaan Data</a></li>
                <li><a href="#tembok-visibilitas"><i class="ti ti-chevron-right" aria-hidden="true"></i> 4. Tembok Visibilitas &amp; Hak Akses</a></li>
                <li><a href="#keamanan-data"><i class="ti ti-chevron-right" aria-hidden="true"></i> 5. Keamanan &amp; Enkripsi Data</a></li>
                <li><a href="#retensi-hapus"><i class="ti ti-chevron-right" aria-hidden="true"></i> 6. Masa Retensi &amp; Akun Mangkrak</a></li>
                <li><a href="#pihak-ketiga"><i class="ti ti-chevron-right" aria-hidden="true"></i> 7. Pemroses Data &amp; Pihak Ketiga</a></li>
                <li><a href="#cookie-sesi"><i class="ti ti-chevron-right" aria-hidden="true"></i> 8. Cookie &amp; Penyimpanan Lokal</a></li>
                <li><a href="#hak-subjek-data"><i class="ti ti-chevron-right" aria-hidden="true"></i> 9. Hak Pemilik Data Pribadi</a></li>
                <li><a href="#kontak-aduan"><i class="ti ti-chevron-right" aria-hidden="true"></i> 10. Kontak Resmi &amp; Aduan</a></li>
            </ul>
        </nav>

        <!-- 1. Pendahuluan -->
        <section class="priv-section" id="pendahuluan">
            <h2>1. Pendahuluan &amp; Pengendali Data</h2>
            <p>
                Selamat datang di Sistem Informasi Manajemen (SIM) Casting JBTB. Kami di <strong>PT. JBTB Casting Creative Group</strong>
                (selanjutnya disebut "JBTB Casting", "kami", atau "pengendali data") berkomitmen penuh melindungi hak privasi dan kerahasiaan
                data pribadi seluruh pengguna layanan kami, baik talenta pemeran tambahan (Extras), mitra Production House (Client),
                maupun staf operasional.
            </p>
            <p>
                Kebijakan Privasi ini disusun secara transparan sesuai dengan mandat <strong>Undang-Undang Republik Indonesia Nomor 27 Tahun 2022
                tentang Pelindungan Data Pribadi (UU PDP)</strong> untuk menerangkan data pribadi apa saja yang kami kumpulkan, dasar hukum dan tujuan
                pemrosesannya, batasan visibilitas peran, langkah-langkah keamanan teknis, serta hak-hak Anda atas data pribadi Anda.
            </p>
        </section>

        <!-- 2. Data yang Kami Simpan -->
        <section class="priv-section" id="data-disimpan">
            <h2>2. Data yang Kami Simpan</h2>
            <p>
                Kami menerapkan prinsip <em>data minimization</em> (minimasi data), yaitu hanya mengumpulkan data yang benar-benar relevan dan
                diperlukan untuk operasional seleksi casting, koordinasi jadwal shooting, penandatanganan kontrak kerja, dan pencairan honorarium.
            </p>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 32%;">Jenis Data Pribadi</th>
                            <th style="width: 25%;">Kategori (UU PDP)</th>
                            <th style="width: 43%;">Disimpan Untuk &amp; Tujuan Penggunaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Nama lengkap &amp; NIK</strong></td>
                            <td>Spesifik / Sensitif</td>
                            <td><strong>Extras:</strong> Verifikasi identitas tunggal, keabsahan kontrak kerja sama proyek, dan kepatuhan administrasi perpajakan.</td>
                        </tr>
                        <tr>
                            <td><strong>Nomor rekening</strong></td>
                            <td>Spesifik / Keuangan</td>
                            <td><strong>Extras:</strong> Digunakan untuk pembayaran honorarium dan penggantian biaya (reimbursement) resmi dari proyek yang telah diselesaikan.</td>
                        </tr>
                        <tr>
                            <td><strong>Nama panggung/alias, foto, video perkenalan</strong></td>
                            <td>Umum</td>
                            <td><strong>Extras:</strong> Digunakan dalam materi kompilasi seleksi casting (lineup) yang ditinjau oleh Client dan Tim Casting Director.</td>
                        </tr>
                        <tr>
                            <td><strong>Tautan sosial media/portofolio (opsional)</strong></td>
                            <td>Umum</td>
                            <td><strong>Extras:</strong> Referensi portofolio pengalaman akting dan visualisasi gaya talent untuk kebutuhan kurasi internal Admin.</td>
                        </tr>
                        <tr>
                            <td><strong>Nomor WhatsApp</strong></td>
                            <td>Umum / Kontak</td>
                            <td><strong>Extras, Client, dan Staf:</strong> Dipakai untuk mengirim notifikasi seleksi, kontrak, dan pengingat jadwal shooting resmi secara otomatis.</td>
                        </tr>
                        <tr>
                            <td><strong>Akun Google</strong></td>
                            <td>Umum / Autentikasi</td>
                            <td><strong>Extras, Client, dan staf yang memakainya:</strong> Nama dan email yang diverifikasi Google, bila kamu masuk atau daftar lewat Google (OAuth).</td>
                        </tr>
                        <tr>
                            <td><strong>Profil publik</strong></td>
                            <td>Umum</td>
                            <td><strong>Extras yang mengizinkan:</strong> Nama panggung, foto, usia dalam rentang, tinggi badan, dan tag penampilan, bila kamu mengizinkan profilmu ditampilkan atau dibagikan lewat tautan kurasi.</td>
                        </tr>
                        <tr>
                            <td><strong>Nama, email, kata sandi (terenkripsi)</strong></td>
                            <td>Umum / Kredensial</td>
                            <td><strong>Semua akun (Admin, Client, Extras):</strong> Identifikasi kredensial akun, pemulihan akses, dan autentikasi login yang aman.</td>
                        </tr>
                        <tr>
                            <td><strong>Riwayat pendaftaran, negosiasi fee, kontrak, pembayaran</strong></td>
                            <td>Umum &amp; Transaksional</td>
                            <td><strong>Extras yang mendaftar proyek:</strong> Pencatatan status seleksi, jejak audit kesepakatan nilai honor, arsip penandatanganan kontrak digital, serta riwayat transfer bank.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 3. Dasar Hukum & Penggunaan Data -->
        <section class="priv-section" id="dasar-hukum">
            <h2>3. Dasar Hukum &amp; Penggunaan Data</h2>
            <p>Sesuai dengan Pasal 20 UU PDP, pemrosesan data pribadi oleh JBTB Casting didasarkan pada:</p>
            <ul>
                <li><strong>Persetujuan yang Sah dan Eksplisit (Consent):</strong> Persetujuan yang Anda berikan secara sukarela saat melakukan registrasi akun, memperbarui profil, atau mencentang persetujuan kebijakan privasi.</li>
                <li><strong>Pelaksanaan Kewajiban Kontrak (Contractual Necessity):</strong> Diperlukan untuk memproses pendaftaran casting, negosiasi fee, penerbitan kontrak kerja proyek, serta transfer pembayaran honorarium Extras.</li>
                <li><strong>Kewajiban Hukum (Legal Obligation):</strong> Pemenuhan ketentuan pembukuan keuangan, pelaporan pajak penghasilan, dan peraturan ketenagakerjaan Republik Indonesia.</li>
                <li><strong>Kepentingan yang Sah (Legitimate Interest):</strong> Menjaga keandalan platform, mencegah manipulasi data, memitigasi pembatalan mendadak yang merugikan produksi, dan mengamankan integritas agensi.</li>
            </ul>
            <div class="priv-callout">
                Data yang kamu berikan digunakan semata-mata untuk proses seleksi casting, negosiasi fee, pembuatan kontrak, dan pembayaran honor, bukan untuk tujuan lain, dan tidak dibagikan ke pihak ketiga di luar proses tersebut.
            </div>
        </section>

        <!-- 4. Tembok Visibilitas & Hak Akses -->
        <section class="priv-section" id="tembok-visibilitas">
            <h2>4. Tembok Visibilitas &amp; Hak Akses Berjenjang (RBAC)</h2>
            <p>
                Untuk melindungi privasi talenta dan menjaga profesionalisme alur kerja industri hiburan, sistem JBTB menerapkan
                <strong>Tembok Visibilitas Tiga Lapis</strong> berbasis peran (Role-Based Access Control):
            </p>
            <ul>
                <li>
                    <strong>Client (Production House / Sutradara / Casting Director):</strong>
                    Hanya melihat nama panggung (alias), foto, dan video perkenalan. Nama asli, NIK, kontak (nomor WhatsApp), rekening bank,
                    dan tautan sosial media/portofolio Extras <strong>tidak pernah</strong> ditampilkan ke Client. Hal ini guna melindungi
                    privasi identitas kependudukan talenta sekaligus mencegah disintermediasi langsung di luar naungan agensi.
                </li>
                <li>
                    <strong>Admin &amp; Super Admin:</strong>
                    Dapat melihat data lengkap Extras (termasuk nama asli &amp; NIK) untuk keperluan verifikasi identitas, administrasi kontrak kerja,
                    serta data fee/pembayaran untuk keperluan operasional keuangan.
                </li>
                <li>
                    <strong>Extras:</strong>
                    Hanya bisa melihat profilnya sendiri secara lengkap beserta riwayat proyek pribadinya, dan tidak memiliki akses untuk melihat data sensitif Extras lain.
                </li>
                <li>
                    <strong>Pengunjung Umum:</strong>
                    Hanya bisa melihat profil publik Extras yang secara sukarela mengaktifkan izin tampil publik dan telah dikurasi oleh Admin,
                    tanpa NIK, nama asli, kontak, dan rekening.
                </li>
            </ul>
        </section>

        <!-- 5. Keamanan & Enkripsi Data -->
        <section class="priv-section" id="keamanan-data">
            <h2>5. Keamanan &amp; Enkripsi Data</h2>
            <p>
                Keamanan data Anda adalah prioritas utama kami. SIM Casting JBTB menerapkan standar keamanan berlapis:
            </p>
            <ul>
                <li>
                    <strong>Enkripsi Database Tingkat Kolom:</strong> NIK, nama asli, dan nomor rekening disimpan dalam bentuk terenkripsi
                    (menggunakan algoritma standar industri AES-256) di database kami. Data hanya didekripsi saat dibutuhkan oleh proses internal berizin.
                </li>
                <li>
                    <strong>Blind Indexing (HMAC):</strong> Untuk mendeteksi pendaftaran ganda NIK tanpa harus mendekripsi seluruh database,
                    kami menggunakan teknik <em>blind index</em> berbasis hashing HMAC dengan kunci rahasia terpisah (<code>NIK_HASH_KEY</code>).
                </li>
                <li>
                    <strong>Penyimpanan Media Privat:</strong> Berkas foto profil, foto kartu identitas, dan video perkenalan disimpan di penyimpanan
                    privat yang tidak dapat diakses lewat tautan publik langsung. Setiap akses berkas diverifikasi secara ketat melalui middleware otorisasi aplikasi.
                </li>
                <li>
                    <strong>Tanda Tangan Digital Kanvas:</strong> Tanda tangan kontrak dibubuhkan langsung secara digital melalui kanvas sentuh dan disematkan
                    langsung ke berkas PDF kontrak resmi yang terkunci.
                </li>
            </ul>
        </section>

        <!-- 6. Masa Retensi & Akun Mangkrak -->
        <section class="priv-section" id="retensi-hapus">
            <h2>6. Masa Retensi &amp; Ketentuan Akun Mangkrak</h2>
            <p>
                Kami menyimpan data pribadi Anda selama akun Anda aktif dan Anda terus berpartisipasi dalam layanan JBTB Casting.
                Untuk menjaga akurasi basis data dan kepatuhan minimasi data:
            </p>
            <div class="priv-callout warning">
                <strong>Kebijakan Pembersihan Akun Mangkrak:</strong><br>
                Akun Extras yang profilnya tidak dilengkapi (foto &amp; NIK) dan belum pernah mendaftar proyek dalam
                <strong>{{ \App\Models\User::HARI_MANGKRAK }} hari</strong> sejak daftar akan <strong>dihapus otomatis</strong> beserta datanya,
                setelah kami kirim pemberitahuan minimal 7 hari sebelumnya.
            </div>
            <p>
                Untuk data kontrak kerja sama, bukti transaksi transfer, dan catatan perpajakan yang telah terlaksana, kami wajib menyimpannya
                selama jangka waktu retensi hukum yang dipersyaratkan oleh undang-undang pembukuan dan perpajakan Republik Indonesia (umumnya 5 sampai 10 tahun)
                sebelum dihapus atau dianonimkan secara permanen.
            </p>
        </section>

        <!-- 7. Pemroses Data & Pihak Ketiga -->
        <section class="priv-section" id="pihak-ketiga">
            <h2>7. Pemroses Data &amp; Pihak Ketiga (Third-Party Sub-processors)</h2>
            <p>
                Kami tidak pernah menjual, menyewakan, atau memperdagangkan data pribadi Anda kepada pihak ketiga mana pun.
                Dalam menjalankan sistem operasional, kami bermitra dengan penyedia layanan tepercaya berikut:
            </p>
            <ul>
                <li><strong>Google LLC (Google Sign-In / OAuth):</strong> Memfasilitasi autentikasi login yang cepat dan aman bagi pengguna yang memilih masuk menggunakan Akun Google.</li>
                <li><strong>Gateway Pengiriman Pesan WhatsApp:</strong> Layanan perantara pengiriman notifikasi transaksional (seperti konfirmasi pendaftaran, undangan proyek, dan notifikasi pencairan honor). Layanan ini hanya memproses nomor telepon dan isi pesan tanpa menyimpan data pribadi lainnya.</li>
                <li><strong>Penyedia Infrastruktur Cloud &amp; Hosting:</strong> Layanan komputasi peladen (server) dan penyimpanan data terisolasi yang menerapkan sertifikasi kepatuhan keamanan ISO/IEC 27001 dan enkripsi transmisi (SSL/TLS).</li>
            </ul>
        </section>

        <!-- 8. Cookie & Penyimpanan Lokal -->
        <section class="priv-section" id="cookie-sesi">
            <h2>8. Cookie &amp; Penyimpanan Lokal (Local Storage)</h2>
            <p>
                SIM Casting JBTB hanya menggunakan teknologi penyimpanan lokal yang esensial untuk fungsi aplikasi:
            </p>
            <ul>
                <li><strong>Cookie Sesi (Strictly Necessary):</strong> Cookie terenkripsi (seperti <code>laravel_session</code> dan token CSRF) untuk memelihara sesi autentikasi Anda agar tetap aman dan mencegah serangan pemalsuan permintaan antar-situs.</li>
                <li><strong>Penyimpanan Lokal (Local Storage):</strong> Kunci <code>jbtb-theme-v2</code> yang disimpan di peramban Anda semata-mata untuk mengingat preferensi tampilan antarmuka (mode terang atau mode gelap).</li>
            </ul>
            <p>
                Kami <strong>tidak menggunakan</strong> cookie pelacak iklan pihak ketiga (third-party tracking cookies) atau alat pemasaran pihak ketiga yang melacak aktivitas Anda di luar situs ini.
            </p>
        </section>

        <!-- 9. Hak Pemilik Data Pribadi -->
        <section class="priv-section" id="hak-subjek-data">
            <h2>9. Hak Pemilik Data Pribadi (Sesuai UU PDP)</h2>
            <p>Sebagai subjek data, Anda memiliki hak-hak yang dijamin oleh peraturan perundang-undangan di Indonesia:</p>
            <ol>
                <li><strong>Hak Mendapatkan Kejelasan Informasi:</strong> Hak mengetahui tujuan pemrosesan, jenis data yang diproses, dan pihak yang memiliki akses terhadap data Anda.</li>
                <li><strong>Hak Mengakses dan Melengkapi Data:</strong> Anda dapat melihat dan memperbarui foto, nama panggung, portofolio, dan nomor rekening Anda kapan saja melalui dashboard akun Anda.</li>
                <li><strong>Hak Menghapus dan Menarik Persetujuan:</strong> Anda berhak mengajukan permohonan penutupan akun dan penghapusan data pribadi Anda, sepanjang data tersebut tidak lagi dipersyaratkan oleh ketentuan hukum retensi keuangan/perpajakan.</li>
                <li><strong>Hak Membatasi Visibilitas:</strong> Talenta Extras memiliki kebebasan penuh untuk mengaktifkan atau mematikan izin penampilan profil pada direktori publik atau beranda web.</li>
                <li><strong>Hak Mengajukan Keberatan &amp; Pengaduan:</strong> Anda berhak menyampaikan keluhan apabila mendapati adanya dugaan ketidaksesuaian dalam pemrosesan data pribadi Anda.</li>
            </ol>
        </section>

        <!-- 10. Kontak Resmi & Aduan -->
        <section class="priv-section" id="kontak-aduan">
            <h2>10. Kontak Resmi Pengendali Data &amp; Pengaduan</h2>
            <p>
                Apabila Anda memiliki pertanyaan, klarifikasi, atau hendak menggunakan hak-hak perlindungan data pribadi Anda
                sebagaimana dijelaskan di atas, silakan hubungi tim kami melalui:
            </p>
            <div class="priv-callout">
                <strong>PT. JBTB Casting Creative Group</strong><br>
                <span>Alamat Kantor: Pamulang, Kota Tangerang Selatan, Banten, Indonesia</span><br>
                @if (config('app.kontak.email'))
                    <span>Email Resmi: <a href="mailto:{{ config('app.kontak.email') }}">{{ config('app.kontak.email') }}</a></span><br>
                @endif
                @if (config('app.kontak.wa'))
                    <span>WhatsApp Layanan: <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', config('app.kontak.wa'))) }}" target="_blank" rel="noopener">{{ config('app.kontak.wa') }}</a></span><br>
                @endif
                <span>Instagram: <a href="https://instagram.com/jbtb.casting" target="_blank" rel="noopener">@jbtb.casting</a></span>
            </div>
            <p>
                Kalau kamu punya pertanyaan soal data pribadimu di sistem ini, hubungi Admin JBTB Casting.
            </p>
            <p style="margin-top: 24px;">
                <a href="{{ route('home') }}" class="priv-back-link" style="display: inline-flex;">
                    <i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali ke Beranda
                </a>
            </p>
        </section>
    </main>

    <!-- Footer Dokumen -->
    <footer class="priv-footer">
        <div class="priv-footer-inner">
            <div>
                <strong>PT. JBTB Casting Creative Group</strong> &middot; NIB terdaftar OSS
            </div>
            <div>
                <a href="{{ route('home') }}" style="color: var(--priv-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-arrow-left"></i> Kembali ke Beranda JBTB</a>
            </div>
        </div>
    </footer>

    <!-- Script Penggantian Tema -->
    <script>
        (function () {
            var icon = document.getElementById('theme-icon');
            var btn = document.getElementById('theme-toggle');
            function updateTheme(t) {
                document.documentElement.setAttribute('data-theme', t);
                try { localStorage.setItem('jbtb-theme-v2', t); } catch (e) {}
                if (icon) {
                    icon.className = t === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
                }
            }
            if (btn) {
                btn.addEventListener('click', function () {
                    var cur = document.documentElement.getAttribute('data-theme') || 'light';
                    var next = cur === 'dark' ? 'light' : 'dark';
                    updateTheme(next);
                });
            }
            var initial = document.documentElement.getAttribute('data-theme') || 'light';
            updateTheme(initial);
        })();
    </script>
</body>
</html>
