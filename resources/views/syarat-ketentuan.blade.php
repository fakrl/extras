<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <script>try { document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light'); } catch (e) {}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>Syarat &amp; Ketentuan | JBTB Casting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.48.0/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        body { font-family: 'Inter', sans-serif; }
        .tos-header {
            position: sticky; top: 0; z-index: 40;
            background: var(--bg-page); border-bottom: 1px solid var(--border-color);
        }
        .tos-header-inner {
            max-width: 860px; margin: 0 auto; padding: 12px 20px;
            display: flex; justify-content: space-between; align-items: center; gap: 16px;
        }
        .tos-brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 700; font-size: 16px; color: var(--text-primary); text-decoration: none; }
        .tos-brand img { border-radius: 6px; object-fit: cover; }
        .tos-btn-icon {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 40px; min-height: 40px; border-radius: 999px;
            border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); cursor: pointer;
        }
        .tos-back-link {
            display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 500;
            color: var(--text-secondary); text-decoration: none; padding: 8px 14px; border-radius: 999px;
            border: 1px solid var(--border-color); background: var(--bg-card);
        }
        .tos-wrap { max-width: 760px; margin: 0 auto; padding: 40px 20px 80px; }
        .tos-wrap h1 { font-size: 28px; font-weight: 800; margin: 0 0 10px; }
        .tos-updated { font-size: 13px; color: var(--text-muted); margin-bottom: 32px; }
        .tos-wrap h2 { font-size: 18px; font-weight: 700; margin: 32px 0 12px; }
        .tos-wrap p, .tos-wrap li { font-size: 14.5px; color: var(--text-secondary); line-height: 1.7; }
        .tos-wrap ul, .tos-wrap ol { padding-left: 20px; margin: 0 0 14px; }
        .tos-wrap li { margin-bottom: 8px; }
        .tos-wrap strong { color: var(--text-primary); }
        .tos-callout {
            background: var(--bg-card); border-left: 4px solid var(--accent);
            border-radius: 0 10px 10px 0; padding: 14px 18px; margin: 16px 0; font-size: 14px;
        }
        .tos-footer {
            border-top: 1px solid var(--border-color); padding: 24px 20px; font-size: 13px;
            color: var(--text-muted); background: var(--bg-card); margin-top: 48px; text-align: center;
        }
    </style>
</head>
<body>
    <header class="tos-header">
        <div class="tos-header-inner">
            <a href="{{ route('home') }}" class="tos-brand">
                <img src="{{ asset('images/logo-jbtb.jpg') }}" alt="Logo JBTB" width="28" height="28">
                <span>JBTB Casting</span>
            </a>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="tos-btn-icon" id="theme-toggle" aria-label="Ganti tema tampilan" title="Ganti mode terang/gelap">
                    <i class="ti ti-moon" id="theme-icon" aria-hidden="true"></i>
                </button>
                <a href="{{ route('home') }}" class="tos-back-link">
                    <i class="ti ti-arrow-left" aria-hidden="true"></i> Beranda
                </a>
            </div>
        </div>
    </header>

    <main class="tos-wrap">
        <h1>Syarat &amp; Ketentuan</h1>
        <div class="tos-updated">PT. JBTB Casting Creative Group &middot; Berlaku untuk seluruh Talent/Extras yang mendaftar lewat sistem ini</div>

        <h2>1. Tentang Fee dan Potongan Jasa Agensi</h2>
        <p>
            Harga yang kamu lihat dan setujui di sistem ini (lewat negosiasi fee maupun di kontrak) adalah fee bersih
            yang sudah dipotong imbalan jasa agensi sebesar <strong>25% (dua puluh lima persen)</strong> dari nilai yang
            ditetapkan oleh Client kepada JBTB Casting untuk proyek tersebut.
        </p>
        <div class="tos-callout">
            Contoh: Client menetapkan nilai Rp 500.000 untuk satu peran. Setelah dipotong jasa agensi 25%, Talent
            menerima Rp 375.000. Fee yang muncul di sistem untukmu sudah dalam bentuk angka bersih ini, bukan angka
            dari Client.
        </div>
        <p>
            Harga yang ditetapkan Client tidak pernah ditampilkan ke Talent/Extras di sistem ini.
        </p>

        <h2>2. Kuasa Penerbitan Invoice ke Client</h2>
        <p>
            Dengan menandatangani kontrak kerja untuk sebuah proyek, Talent memberi kuasa kepada PT. JBTB Casting
            Creative Group untuk menerbitkan dan menandatangani invoice kepada Client atas proyek tersebut, serta
            menerima pembayaran dari Client atas nama Talent. Rincian kuasa ini (termasuk jangka waktu berlakunya)
            tercantum lengkap di kontrak kerja yang kamu tandatangani per proyek.
        </p>

        <h2>3. Pembayaran Fee ke Talent</h2>
        <p>
            Fee yang sudah disepakati akan dibayarkan oleh agensi kepada Talent segera setelah pembayaran dari Client
            diterima. Kamu akan mendapat notifikasi saat bukti transfer sudah diunggah, dan kamu mengonfirmasi
            penerimaannya lewat fitur konfirmasi pembayaran yang ada di sistem.
        </p>

        <h2>4. Kontak</h2>
        <p>
            Ada pertanyaan soal Syarat &amp; Ketentuan ini? Hubungi Admin JBTB Casting lewat kontak resmi yang tertera
            di <a href="{{ route('privacy-policy') }}">Kebijakan Privasi</a>.
        </p>
    </main>

    <footer class="tos-footer">
        <strong>PT. JBTB Casting Creative Group</strong>
    </footer>

    <script>
        (function () {
            var icon = document.getElementById('theme-icon');
            var btn = document.getElementById('theme-toggle');
            function updateTheme(t) {
                document.documentElement.setAttribute('data-theme', t);
                try { localStorage.setItem('jbtb-theme-v2', t); } catch (e) {}
                if (icon) { icon.className = t === 'dark' ? 'ti ti-sun' : 'ti ti-moon'; }
            }
            if (btn) {
                btn.addEventListener('click', function () {
                    var cur = document.documentElement.getAttribute('data-theme') || 'light';
                    updateTheme(cur === 'dark' ? 'light' : 'dark');
                });
            }
            updateTheme(document.documentElement.getAttribute('data-theme') || 'light');
        })();
    </script>
</body>
</html>
