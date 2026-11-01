# Master Prompt: Adaptasi Terfilter Desain Google Stitch (Cinematic Editorial M3) ke SIM Casting JBTB (`fakrl/extras`)

> **Instruksi Pemakaian di Antigravity:**
> Simpan dokumen ini sebagai `docs/prompt_stitch_filtered_adaptation_jbtb.md` di repositori lokalmu (atau *copy-paste* langsung ke Antigravity), lalu beri instruksi:
> *"Baca dan eksekusi seluruh spesifikasi di `docs/prompt_stitch_filtered_adaptation_jbtb.md`. Kita mengadaptasi komponen-komponen visual terbaik dari 7 layar hasil redesign Google Stitch (`Cinematic Editorial Agency` — Material 3), tetapi dengan FILTER KETAT: jangan gunakan Tailwind CDN, jangan gunakan Google Material Symbols (wajib Tabler Icons `<i class="ti ti-..."></i>`), jangan gunakan efek grayscale pada foto, jangan gunakan fitur QR code fiktif, jangan gunakan em-dash (`—`), pertahankan layout mobile-first Extras, dan pastikan 100% test suite (`php artisan test`) tetap hijau."*

## 1. Konteks, Filosofi Adaptasi, & Filter Ketat (Hard Constraints)

Dari hasil ekstraksi dan audit 7 layar Google Stitch (`DESIGN.md` + 7 file `code.html`), terdapat elemen desain *Cinematic Editorial Agency* yang sangat berkelas, namun ada pula bawaan prototipe statis yang **WAJIB DIFILTER/DIBERSIHKAN** sebelum masuk ke kode produksi JBTB.

### A. Daftar Larangan Mutlak (Yang WAJIB DIFILTER dari Stitch)

1. **DILARANG Menyuntikkan Tailwind CDN (`cdn.tailwindcss.com`)**:
   * Sistem JBTB menggunakan **Laravel 13 Blade + Custom CSS Design System di `resources/views/partials/theme-style.blade.php`**. Seluruh token warna, tipografi, border, dan komponen dari Stitch wajib diterjemahkan menjadi CSS variabel dan class native di `theme-style.blade.php` (tanpa dependensi CSS framework eksternal).

2. **DILARANG Menggunakan Google Material Symbols (`<span class="material-symbols-outlined">`)**:
   * Di file HTML Stitch tertulis teks ikon mentah seperti `movie`, `how_to_reg`, `verified`, `chevron_right`. **Jangan pernah menyalin `<span class="material-symbols-outlined">` ke Blade!** Semua ikon wajib menggunakan **Tabler Icons (`<i class="ti ti-..."></i>`)** yang sudah terpasang di proyek.

3. **DILARANG Menggunakan Efek Grayscale pada Foto (`grayscale contrast-105`)**:
   * Di beberapa kartu Stitch terdapat filter hitam-putih pada foto talent. Ini **DITOLAK** karena menyulitkan Admin dan Client melihat warna kulit/rambut asli talent. Foto profil wajib tampil berwarna jernih dengan efek *hover zoom* lembut (`transform: scale(1.025)`).

4. **DILARANG Menyalin Fitur Fiktif / Copywriting AI-Slop dari Stitch**:
   * Di layar Extras Stitch tertulis *"Pencatatan check-in QR code di lokasi set"* dan *"0% cocok"*. Ini **DITOLAK** karena sistem JBTB asli menggunakan **Presensi Foto Selfie + Geotagging GPS**, bukan QR Code.
   * Di header Stitch masih terdapat *em-dash* (`Dashboard Operasional Admin — Rina Kartika` dan `Halo, Andini Prameswari— PT Layar Senja Films`). **Ganti semua em-dash (`—`) dengan titik tengah (`·`)**.

5. **DILARANG Mengubah Navigasi Mobile Extras Menjadi Sidebar Desktop-Only**:
   * Stitch membuat `extras_dashboard_talenta_desktop` dengan sidebar kiri desktop. Di sistem JBTB, pengguna `extras` wajib tetap memiliki **Bottom Navigation (`nav-bawah`)** yang ramah layar HP.

6. **DILARANG Merusak Route, Form Input, atau Test Suite (`php artisan test`)**:
   * Seluruh URL `route(...)`, HTTP method (`POST`/`PATCH`/`DELETE`), atribut `name="..."`, variabel view, dan hook selector test (`.xaksi`, `#detail-...`) wajib dipertahankan 100%.

### B. Permata Desain Stitch yang WAJIB DIADAPTASI ke Sistem JBTB

1. **Material 3 "Cinematic Editorial" Tonal Surface & Hairline Architecture (`DESIGN.md`)**:
   * Hirarki permukaan berlapis (`#FAF9F4` surface utama, `#F5F4EF` container low, `#EFEEE9` container sedang, `#FFFFFF` kartu utama dengan hairline border `1px solid rgba(11, 26, 18, 0.08)` dan studio ambient shadow halus).
   * Aksen warna Deep Moss Green (`#0B5E2C` / `#15803D`), Midnight Olive (`#0B1A12`), dan Warm Amber (`#D97706` dengan container `#FEF3C7`).

2. **Pasangan Tipografi Editorial (`Newsreader` Serif + `Inter` Sans-Serif)**:
   * Judul proyek film/iklan, judul utama halaman, dan nama panggung talent di halaman portofolio menggunakan font Serif elegan (`Newsreader` / Georgia), sedangkan seluruh label metrik (`uppercase`, `letter-spacing: 0.04em`), angka, dan form menggunakan `Inter`.

3. **Desain Komponen Terbaik dari Ke-7 Layar Stitch**:
   * **Layar 1 (`admin_dashboard_operasional_proyek_desktop`)**: Banner/Kartu *"Perlu Tindakan Mendesak"* bergaya tiket editorial, visualisasi *5-Tahap Pipeline Kandidat (T1 s.d. T5)* berbentuk kolom tahapan terstruktur, dan kartu *Portofolio Produksi Proyek Aktif*.
   * **Layar 2 (`client_dashboard_rumah_produksi_desktop`)**: Hero Action Banner *"X Kandidat Menunggu Keputusan Pemilihan Anda (Greenlight)"* dengan CTA langsung ke halaman review, serta kartu proyek aktif ber-stepper 4 tahap.
   * **Layar 3 (`client_lookbook_kurasi_kandidat_desktop`)**: Kartu Kandidat Lookbook berasio potret sinema **2:3 (`aspect-ratio: 2/3`)** dengan overlay badge Grade di kiri atas, badge `% Cocok` di kanan atas, badge nama alias `@username` + ikon gembok *anti-poaching* di kiri bawah foto, dan tombol `[Video Slate]` di kanan bawah foto.
   * **Layar 4 (`korlap_presensi_komando_lapangan_mobile`)**: Tampilan *Sentra Kendali Set* dengan *Alert Strip* antrean selfie di atas, *Meteran Rasio Presensi (`X / Y Tervalidasi di Set · Z% Siap Set`)*, kartu *Validasi Cepat Selfie* berindikator jarak GPS (`GPS Match`), dan *Roster Figuran Lapangan*.
   * **Layar 5 (`extras_dashboard_talenta_desktop`)**: Kartu *Pendaftaran Aktif Saya* dengan badge kategori produksi, kartu *Status Talenta & Penjelasan Grade (A/B/C)* yang transparan dan mengedukasi talent, serta rekomendasi lowongan terbuka.
   * **Layar 6 (`extras_profil_portofolio_talenta_desktop` / `profil-extras-editorial.blade.php`)**: Tampilan portofolio talent bergaya *Editorial Comp Card / Lookbook* dengan foto utama 2:3, grid *Foto Tambahan Lookbook*, serta tabel spesifikasi fisik 2 kolom bernomor (`01 DATA DIRI & CIRI FISIK`, `02 PENGALAMAN & KEMAMPUAN`).
   * **Layar 7 (`jbtb_casting_beranda_landing_page`)**: Polesan tipografi *Newsreader* dan tata letak kartu editorial pada seksi *3 Langkah Mudah*, *Lowongan Casting Terbuka*, dan *Roster Cast*.

## 2. Penambahan Token & Utilitas CSS di `resources/views/partials/theme-style.blade.php`

Tambahkan definisi CSS berikut ke dalam `resources/views/partials/theme-style.blade.php` (pastikan selaras dengan Light Mode & Dark Mode yang sudah ada):

```css
/* ==========================================================================
   CINEMATIC EDITORIAL M3 DESIGN SYSTEM (FILTERED ADAPTATION FROM STITCH)
   ========================================================================== */
@import url('https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap');

:root {
    --md-surface: #faf9f4;
    --md-surface-dim: #dbdad5;
    --md-surface-container-lowest: #ffffff;
    --md-surface-container-low: #f5f4ef;
    --md-surface-container: #efeee9;
    --md-surface-container-high: #e9e8e3;
    --md-surface-container-highest: #e3e3de;
    --md-primary: #00441d;
    --md-primary-container: #0b5e2c;
    --md-on-primary-container: #89d596;
    --md-secondary-container: #fef3c7;
    --md-on-secondary-container: #904d00;
    --md-tertiary-dark: #0b1a12;
    --md-outline-hairline: rgba(11, 26, 18, 0.08);
    --md-shadow-ambient: 0 4px 16px -2px rgba(11, 26, 18, 0.06), 0 1px 3px 0 rgba(11, 26, 18, 0.04);
    --font-editorial: 'Newsreader', Georgia, 'Times New Roman', serif;
}

[data-theme="dark"] {
    --md-surface: #0b1310;
    --md-surface-dim: #070d0b;
    --md-surface-container-lowest: #0e1713;
    --md-surface-container-low: #131e19;
    --md-surface-container: #17241e;
    --md-surface-container-high: #1c2b24;
    --md-surface-container-highest: #22322a;
    --md-secondary-container: rgba(245, 158, 11, 0.16);
    --md-on-secondary-container: #fbbf24;
    --md-outline-hairline: rgba(255, 255, 255, 0.08);
    --md-shadow-ambient: 0 4px 16px -2px rgba(0, 0, 0, 0.35);
}

/* Tipografi Editorial & Label Metadata */
.font-editorial {
    font-family: var(--font-editorial);
    font-weight: 500;
    letter-spacing: -0.015em;
}

.label-editorial-sm {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

/* 2:3 Cinematic Portrait Card Framing (Lookbook & Comp Card) */
.lookbook-card {
    background: var(--md-surface-container-lowest);
    border: 1px solid var(--md-outline-hairline);
    border-radius: var(--radius-md);
    box-shadow: var(--md-shadow-ambient);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.lookbook-card:hover {
    box-shadow: 0 10px 24px -4px rgba(11, 26, 18, 0.1);
}

.lookbook-media-2-3 {
    position: relative;
    width: 100%;
    aspect-ratio: 2 / 3;
    background: var(--md-surface-container-high);
    overflow: hidden;
}

.lookbook-media-2-3 img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.45s ease;
}

.lookbook-card:hover .lookbook-media-2-3 img {
    transform: scale(1.025);
}

.lookbook-overlay-top {
    position: absolute;
    top: 10px;
    left: 10px;
    right: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    pointer-events: none;
    z-index: 2;
}

.lookbook-overlay-bottom {
    position: absolute;
    bottom: 10px;
    left: 10px;
    right: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    z-index: 2;
}

.lookbook-glass-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(250, 249, 244, 0.92);
    backdrop-filter: blur(6px);
    color: var(--text-primary);
    font-size: 11.5px;
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

[data-theme="dark"] .lookbook-glass-pill {
    background: rgba(14, 23, 19, 0.9);
}

/* Client Greenlight Callout Banner */
.greenlight-banner {
    background: var(--md-secondary-container);
    color: var(--md-on-secondary-container);
    border: 1px solid rgba(217, 119, 6, 0.25);
    border-radius: var(--radius-md);
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
}

/* Admin 5-Stage Pipeline Board (T1-T5) */
.pipeline-5stage-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
}

@media (max-width: 960px) {
    .pipeline-5stage-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 540px) {
    .pipeline-5stage-grid {
        grid-template-columns: 1fr;
    }
}

.pipeline-stage-box {
    background: var(--md-surface-container-low);
    border: 1px solid var(--md-outline-hairline);
    border-radius: var(--radius-md);
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

/* Korlap Set Command Strip & Selfie Validation Card */
.korlap-alert-strip {
    background: var(--md-secondary-container);
    color: var(--md-on-secondary-container);
    padding: 10px 14px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 14px;
}

.korlap-selfie-card {
    background: var(--md-surface-container-lowest);
    border: 1px solid var(--md-outline-hairline);
    border-radius: var(--radius-md);
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: var(--md-shadow-ambient);
}
```

## 3. Instruksi Adaptasi Detail Per Halaman (7 Area Berdasarkan Hasil Stitch)

### Area 1: Halaman Kurasi Kandidat / Lookbook Client (`resources/views/client/reviews/show.blade.php`)
*Sumber inspirasi: `client_lookbook_kurasi_kandidat_desktop`*
1. **Header Proyek Editorial**:
   * Tampilkan label kecil di atas judul: `<span class="label-editorial-sm">Kurasi Talenta · Pilih Kandidat & Lookbook</span>`.
   * Gunakan class `.font-editorial` pada judul proyek film/iklan dan tampilkan badge kode proyek serta progres kuota (`Terpilih: X dari Y Kuota`).
2. **Desain Kartu Kandidat Lookbook Rasio 2:3 (`.lookbook-card`)**:
   * Gunakan pembungkus foto `.lookbook-media-2-3` (**tanpa grayscale!**).
   * **Overlay Atas Foto (`.lookbook-overlay-top`)**:
     * Kiri: `<span class="lookbook-glass-pill"><i class="ti ti-certificate"></i> Grade {{ strtoupper($grade) }}</span>` (jika memiliki grade).
     * Kanan: Badge persentase kecocokan (`% Cocok`) jika tersedia.
   * **Overlay Bawah Foto (`.lookbook-overlay-bottom`)**:
     * Kiri (Badge Anti-Poaching):
       ```blade
       <span class="lookbook-glass-pill" title="Identitas dan kontak pribadi dilindungi sistem JBTB">
           <span class="font-editorial">@{{ $kandidatUsername }}</span>
           <i class="ti ti-lock" style="font-size: 13px; color: var(--text-muted);"></i>
       </span>
       ```
     * Kanan: Jika kandidat memiliki video perkenalan, tampilkan tombol kecil `<span class="lookbook-glass-pill"><i class="ti ti-player-play"></i> Video Profil</span>`.
   * **Body & Footer Kartu**:
     * Tampilkan ciri fisik utama (Usia, Tinggi, Gender, Ukuran Baju) dalam pill tonal yang rapi dan mudah dipindai, diikuti tombol keputusan Client (`Pilih` / `Tolak`) yang sudah ada di form Blade (jangan ubah route atau atribut `name` form-nya!).

### Area 2: Dashboard Rumah Produksi / Client (`resources/views/client/dashboard.blade.php`)
*Sumber inspirasi: `client_dashboard_rumah_produksi_desktop`*
1. **Header Sapaan Editorial**:
   * Tambahkan sub-label `<span class="label-editorial-sm">Ruang Kerja Produksi</span>` di atas sapaan `Halo, {{ auth()->user()->name }}` (gunakan `.font-editorial` pada nama/perusahaan, **tanpa em-dash `—`**; gunakan pemisah `·`).
2. **Banner Prioritas "Menunggu Greenlight / Pemilihan Kandidat" (`.greenlight-banner`)**:
   * Jika variabel `$perluDireview > 0`, tampilkan banner `.greenlight-banner` yang menonjol di bagian atas dashboard:
     * Kiri: `<i class="ti ti-sparkles"></i> <strong>{{ $perluDireview }} Kandidat Menunggu Keputusan Pemilihan Anda</strong> · Tinjau lookbook portofolio kandidat yang telah dikurasi tim Admin JBTB.`
     * Kanan: Tombol `.btn-brand` menuju halaman pemilihan kandidat (`client.reviews.index`).

### Area 3: Dashboard Operasional Admin (`resources/views/admin/dashboard.blade.php` & `admin/partials/tahapan.blade.php`)
*Sumber inspirasi: `admin_dashboard_operasional_proyek_desktop`*
1. **Header & Tiket Tindakan Mendesak**:
   * Berikan label `<span class="label-editorial-sm">Studio Operasional Proyek</span>` dan judul `.font-editorial` (`Dashboard Operasional Admin · {{ auth()->user()->name }}`).
   * Pada bagian *Perlu Tindakan*, rapikan setiap baris item mendesak (seperti `$pembayaranSengketa` atau `$urgentProjects`) agar tampil seperti kartu tiket aksi dengan badge jenis kasus dan tombol tindakan di sisi kanan.
2. **Visualisasi 5-Tahap Partisipasi Kandidat (`admin/partials/tahapan.blade.php`)**:
   * Rapikan tampilan tahapan kandidat menggunakan struktur kartu tahapan terindeks (`T1: Ajuan & Review`, `T2: Nego Fee`, `T3: Pilihan Client`, `T4: Kontrak`, `T5: Selesai/Payout`) dengan angka metrik tegas dan bar proporsi visual.

### Area 4: Komando Lapangan Korlap Mobile (`resources/views/partials/korlap-ringkasan.blade.php`)
*Sumber inspirasi: `korlap_presensi_komando_lapangan_mobile`*
1. **Alert Strip Antrean Validasi Selfie (`.korlap-alert-strip`)**:
   * Jika `count($menunggu) > 0`, tampilkan `.korlap-alert-strip` di bagian paling atas:
     `<span><i class="ti ti-camera-check"></i> {{ count($menunggu) }} Presensi Selfie Menunggu Validasi Lapangan</span>`.
2. **Kartu "Rasio Presensi Figuran" & "Validasi Cepat Selfie" (`.korlap-selfie-card`)**:
   * Pada kartu jadwal shooting hari ini/terdekat, tampilkan rasio kehadiran (`Hadir / Target`) dengan bar progres jelas dan persentase kesiapan set (`% Siap Set`).
   * Pada kartu antrean `$menunggu`, bungkus tiap item dengan `.korlap-selfie-card` yang menampilkan foto selfie, `@username`, jam absen, lokasi/titik GPS, serta tombol validasi kehadiran yang mudah disentuh di layar HP (`min-height: 44px`).

### Area 5: Dashboard & Penjelasan Grade Talenta Extras (`resources/views/extras/dashboard.blade.php`)
*Sumber inspirasi: `extras_dashboard_talenta_desktop` (diadaptasi tetap Mobile-First!)*
1. **Pertahankan Navigasi Mobile (`nav-bawah`) & Kartu Boarding-Pass**:
   * Jangan ubah navigasi Extras menjadi sidebar desktop; pertahankan layout mobile-first yang sudah responsif.
2. **Tambahkan Info Edukasi "Status Talenta & Klasifikasi Grade" yang Rapi**:
   * Di kartu/bagian status profil Extras, tampilkan badge Grade talent saat ini (jika sudah dinilai Admin) disertai keterangan singkat yang ramah seperti di desain Stitch: *"Klasifikasi Grade ditentukan oleh tim Casting berdasarkan kelengkapan portofolio, kesesuaian karakter, dan kedisiplinan kehadiran di lokasi syuting."*

### Area 6: Profil Portofolio Publik / Editorial Extras (`resources/views/partials/profil-extras-editorial.blade.php` & `extras/profile-show.blade.php`)
*Sumber inspirasi: `extras_profil_portofolio_talenta_desktop`*
1. Pastikan tampilan profil publik/lookbook talent menonjolkan bingkai foto utama 2:3 (`.lookbook-media-2-3`), nama panggung besar dengan `.font-editorial`, serta pemisahan blok spesifikasi yang rapi (`01 DATA DIRI & CIRI FISIK` dan `02 PENGALAMAN & KEMAMPUAN`) dengan garis *hairline* tipis (`--md-outline-hairline`).

### Area 7: Landing Page Publik (`resources/views/welcome.blade.php` / `public/landing.blade.php`)
*Sumber inspirasi: `jbtb_casting_beranda_landing_page`*
1. Pastikan judul utama hero, seksi *3 Langkah Mudah*, *Lowongan Casting Terbuka*, dan *Roster Cast* menggunakan perpaduan tipografi `.font-editorial` (`Newsreader`) dan `.label-editorial-sm` (`Inter` uppercase) dengan permukaan kartu `--md-surface-container-lowest` dan border tipis `--md-outline-hairline`.

## 4. Checklist Verifikasi & Audit Akhir Wajib
Setelah seluruh adaptasi selesai dikerjakan:
1. **Audit Zero Tailwind CDN & Zero Material Symbols**:
   * Pastikan tidak ada string `cdn.tailwindcss.com` atau `material-symbols-outlined` di file `.blade.php` mana pun.
2. **Audit Zero AI-Slop**:
   * Pastikan tidak ada emoji Unicode warna-warni, tidak ada `—` / `&mdash;`, tidak ada filter `grayscale` pada foto talent, dan tidak ada teks fiktif *"QR code"*.
3. **Verifikasi Kompilasi & Full Test Suite**:
   * Jalankan `php artisan view:clear`
   * Jalankan `php artisan test` dan pastikan **100% test suite (967+ tests) tetap PASS (Hijau)**.
