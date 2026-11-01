# Master Prompt V2: Transformasi Statistik Polosan Menjadi Visualisasi Diagram & Grafik Interaktif (Donut Chart, Radial Ring, Financial Bar, & Funnel Diagram) — SIM Casting JBTB (`fakrl/extras`)

> **Cara Pakai Dokumen Ini di Antigravity:**
> Simpan dokumen ini menimpa `docs/prompt_dashboard_all_roles_jbtb.md` di repositori lokalmu (atau *copy-paste* langsung ke Antigravity), lalu beri instruksi:
> *"Perbaikan responsif mobile dan data statistik di Dashboard 5 role sudah jalan, tapi tampilan kartu statistiknya saat ini masih terlalu polosan (hanya kotak berisi teks angka dan garis tipis). Eksekusi spesifikasi V2 di `docs/prompt_dashboard_all_roles_jbtb.md` ini untuk mengubah kartu-kartu statistik polosan tersebut menjadi visualisasi diagram nyata (SVG Donut Chart multi-segmen, Radial Progress Ring, Financial Comparison Bar Chart, dan Visual Funnel Diagram) yang responsif di desktop & mobile, bebas emoji/em-dash, dan pastikan 100% test suite (`php artisan test`) tetap hijau."*

## 1. Evaluasi Saat Ini & Mengapa Kartu Statistik Terlihat "Polosan"

1. **Status Responsivitas Mobile (Sudah Beres 100%)**:
   * Posisi `.sticky-mobile-cta` (`bottom: 68px` di atas `.nav-bawah`), perilaku akordion `.dash-hover-popover` di layar HP (`<= 640px`), `flex-wrap` pada `.sa-mode-banner` & tombol ekspor/header, serta penguncian grid 1 kolom di mobile sudah terverifikasi berjalan rapi.

2. **Kenapa Statistik Dashboard Masih Terlihat "Polosan"**:
   * Saat ini, ke-4 kartu di `.dash-pulse-strip` pada `/super-admin/dashboard` (*Populasi Akun: 36*, *Ekosistem Proyek: 15*, *Finansial & Margin: Rp 13.650.000*, *Funnel Partisipasi: 33*) maupun kartu statistik di `/extras/dashboard`, `/client/dashboard`, `/admin/dashboard`, dan Korlap **hanya menampilkan teks angka besar + subteks sebaris + garis lurus tipis (`.dash-segmented-bar`)**.
   * Mata pengguna tidak melihat **bentuk grafik/diagram visual** yang membedakan karakteristik tiap data (mana yang merupakan proporsi komposisi, mana yang persentase ketercapaian target/kuota, mana yang perbandingan pemasukan vs pengeluaran, dan mana yang alur konversi bertingkat).

## 2. Solusi Arsitektur Visual: Pure Dynamic SVG Diagrams + CSS (Tanpa Library Berat Tambahan)

Gunakan **Dynamic Inline `<svg>` & CSS** yang dihitung langsung dari variabel PHP di Blade (tanpa ketergantungan CDN eksternal sehingga langsung ter-render instan, tajam di semua resolusi, mendukung penuh Light/Dark Mode, dan tidak pernah merusak `php artisan test`).

Tambahkan **4 Jenis Diagram Visual** berikut ke dalam `resources/views/partials/theme-style.blade.php` dan terapkan pada masing-masing kartu statistik sesuai sifat datanya:

| Jenis Data Statistik | Diagram Visual yang Wajib Dipakai | Alasan & Bentuk Visualnya |
| ----- | ----- | ----- |
| **1. Data Komposisi / Proporsi Kategori**  *(Contoh: Populasi Akun Talent vs Client vs Tim, atau Proporsi Keputusan Kandidat Client)* | **Multi-Segment SVG Donut Chart (`.viz-donut`) + Mini Legend Berwarna** | Lingkaran donat (`76px x 76px`) dengan beberapa segmen warna (`<circle stroke-dasharray="...">`) dan angka total di tengah lubang donat, disandingkan dengan daftar legend ber-dot warna & angka di sebelahnya. |
| **2. Data Persentase Target / Kapasitas**  *(Contoh: Keterisian Kuota Proyek 19%, Kesiapan Profil 80%, Skor Kehadiran 100%)* | **SVG Radial Progress Ring / Gauge (`.viz-ring`)** | Cincin progres melingkar (`72px x 72px`) dengan *track* abu-abu lembut dan busur warna aktif (`stroke-linecap: round`) yang mengelilingi angka persentase tebal (`19%`, `80%`, `100%`) tepat di tengah cincin. |
| **3. Data Perbandingan Finansial**  *(Contoh: Omzet Invoice vs Beban Produksi vs Margin Agency 47%)* | **Horizontal Grouped / Waterfall Comparison Bars (`.viz-fin-bars`) + Margin Badge** | 3 batang horizontal berlabel yang membandingkan panjang visual **Tagihan Client (100%)**, **Total Biaya Produksi (X%)**, dan **Margin Kotor Agency (Y%)** lengkap dengan *pill badge* persentase margin yang menonjol. |
| **4. Data Konversi Bertahap**  *(Contoh: Funnel Partisipasi 33 Lamaran dari Awal s.d. Selesai)* | **5-Stage Mini Funnel Bar Chart (`.viz-funnel`)** | Deretan bar horizontal bertingkat dari tahap 1 s.d. tahap akhir di mana lebar tiap bar proporsional terhadap jumlah kandidat di tahap tersebut, lengkap dengan label tahap di kiri dan angka di kanan. |

## 3. Spesifikasi CSS & Struktur HTML Diagram Baru

### A. Tambahkan Styling Diagram di `resources/views/partials/theme-style.blade.php`

Tambahkan class CSS berikut agar seluruh diagram tersusun rapi di dalam `.dash-stat-card` (baik di desktop maupun mobile `<= 640px`):

```css
/* Layout Split di Dalam Kartu Statistik Berdiagram */
.dash-viz-body {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 8px;
}

.viz-donut-wrap,
.viz-ring-wrap {
    position: relative;
    width: 76px;
    height: 76px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.viz-donut-svg,
.viz-ring-svg {
    width: 76px;
    height: 76px;
    transform: rotate(-90deg);
}

.viz-center-label {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    line-height: 1.1;
    pointer-events: none;
}

.viz-center-num {
    font-size: 16px;
    font-weight: 800;
    color: var(--text-primary);
}

.viz-center-sub {
    font-size: 9.5px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

/* Legend di Samping Donut / Ring */
.viz-legend {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.viz-legend-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    font-size: 12px;
    color: var(--text-secondary);
}

.viz-legend-label {
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.viz-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.viz-legend-val {
    font-weight: 700;
    color: var(--text-primary);
    flex-shrink: 0;
}

/* Financial Comparison Bars (.viz-fin-bars) */
.viz-fin-bars {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin-top: 8px;
}

.viz-fin-item {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.viz-fin-head {
    display: flex;
    justify-content: space-between;
    font-size: 11.5px;
}

.viz-fin-track {
    height: 7px;
    border-radius: 999px;
    background: var(--surface-2, rgba(0,0,0,0.06));
    overflow: hidden;
    display: flex;
}

.viz-fin-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.3s ease;
}

/* Mini Funnel Chart (.viz-funnel) */
.viz-funnel {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-top: 8px;
}

.viz-funnel-row {
    display: grid;
    grid-template-columns: 78px 1fr 28px;
    align-items: center;
    gap: 8px;
    font-size: 11.5px;
}

.viz-funnel-label {
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.viz-funnel-track {
    height: 8px;
    background: var(--surface-2, rgba(0,0,0,0.06));
    border-radius: 4px;
    overflow: hidden;
}

.viz-funnel-bar {
    height: 100%;
    border-radius: 4px;
}

.viz-funnel-count {
    text-align: right;
    font-weight: 700;
    color: var(--text-primary);
}
```

### B. Rombak Visual 4 Kartu Ekosistem di Dashboard Super Admin (`resources/views/super-admin/dashboard.blade.php`)

Ubah ke-4 kartu di `.dash-pulse-strip` pada `/super-admin/dashboard` dari kotak teks polosan menjadi 4 kartu berdiagram visual (tetap pertahankan `.dash-hover-popover` untuk rincian ekstra saat di-hover/tap):

1. **Kartu 1 (`Populasi Akun`)** -> **Gunakan Multi-Segment SVG Donut Chart (`.viz-donut-wrap` + `.viz-legend`)**:
   * Hitung keliling lingkaran SVG (`r = 30`, keliling C = 2 * pi * 30 ≈ 188.5).
   * Gambar 3 `<circle>` segmen di atas track lingkaran untuk:
     * **Talent / Extras**: Warna `#15803D` (Hijau JBTB)
     * **Client / PH**: Warna `#D97706` (Amber)
     * **Tim Internal (Admin + Korlap + SA)**: Warna `#0284C7` (Biru)
   * Di tengah donat (`.viz-center-label`), tampilkan angka total akun (misal `36`) dan sub-label `AKUN`.
   * Di sebelah kanan donat (`.viz-legend`), tampilkan 3 baris legend berdot warna (`Talent: 23`, `Client: 5`, `Tim: 8`).

2. **Kartu 2 (`Ekosistem Proyek & Kuota`)** -> **Gunakan SVG Radial Gauge Ring (`.viz-ring-wrap`) + Mini Status Breakdown**:
   * Di sisi kiri (`.viz-ring-wrap`), tampilkan **Cincin Radial Persentase Keterisian Kuota (`19%`)** menggunakan `<circle r="30" stroke-dasharray="{{ ($persenKuota / 100) * 188.5 }} 188.5" stroke="#15803D" stroke-width="7" stroke-linecap="round" fill="none" />` dengan teks tengah `{{ $persenKuota }}%` dan sub-label `KUOTA`.
   * Di sisi kanan (`.viz-legend`), tampilkan total proyek (`15 Proyek`) beserta 3 baris status proyek utama dengan dot warna (`Berjalan/Seleksi`, `Menunggu ACC`, `Selesai`).

3. **Kartu 3 (`Finansial & Margin`)** -> **Gunakan Financial Comparison Bars (`.viz-fin-bars`) + Margin Pill**:
   * Di header kartu, tampilkan badge hijau/amber **`Margin {{ $persenMargin }}%`**.
   * Di body kartu, tampilkan **3 Bar Perbandingan Proporsional (`.viz-fin-bars`)**:
     * **Bar 1 (`Tagihan Client`)**: Nominal `Rp ...` dengan bar 2 warna (Hijau `#15803D` untuk *Lunas*, Kuning `#F59E0B` untuk *Piutang Belum Lunas*).
     * **Bar 2 (`Beban Produksi`)**: Nominal `Rp ...` (Payout Extras + Pengeluaran + Payroll) dengan lebar proporsional terhadap Tagihan dan warna Oranye/Merah lembut (`#EA580C`).
     * **Bar 3 (`Estimasi Margin`)**: Nominal `Rp ...` dengan bar warna Hijau Terang (`#16A34A`) proporsional terhadap Tagihan.

4. **Kartu 4 (`Funnel Partisipasi`)** -> **Gunakan 5-Stage Mini Funnel Bar Chart (`.viz-funnel`)**:
   * Tampilkan 4–5 baris `.viz-funnel-row` yang memvisualisasikan bentuk corong (*funnel*) dari tahap awal hingga selesai:
     * `Awal/Review` (lebar bar proporsional terhadap total lamaran, warna `#64748B`)
     * `Nego Fee` (warna `#D97706`)
     * `Ke Client` (warna `#0284C7`)
     * `Kontrak` (warna `#15803D`)
     * `Selesai` (warna `#16A34A`)

### C. Terapkan Diagram Visual Serupa di Dashboard Role Lainnya (`admin`, `korlap`, `client`, `extras`)

1. **Dashboard `extras` (`resources/views/extras/dashboard.blade.php`)**:
   * Pada kartu **Kesiapan Profil (`$persenProfil%`)** dan kartu **Skor Kehadiran (`100%`)** di strip atas Beranda Extras, tambahkan **SVG Radial Progress Ring (`.viz-ring-wrap`)** di sisi kiri kartu sehingga persentase `80%` dan `100%` tampil sebagai grafik cincin melingkar yang menarik, bukan sekadar teks angka polos.
   * Pada kartu **Dompet Honor**, tampilkan `.viz-fin-bars` mini yang membandingkan bar *Sudah Diterima (Cair)* vs *Sedang Diproses/Ditransfer*.
   * Pada kartu **Aktivitas & Proyek**, tampilkan mini *legend/bar* *Proyek Selesai* vs *Lamaran Aktif*.

2. **Dashboard `client` (`resources/views/client/dashboard.blade.php`)**:
   * Pada kartu **Keputusan Pemilihan Saya**, tambahkan **Multi-Segment SVG Donut Chart (`.viz-donut-wrap`)** di samping daftar metrik yang memvisualisasikan proporsi kandidat **Disetujui (`$approve` - Hijau)**, **Menunggu Review (`$perluDireview` - Kuning)**, dan **Ditolak (`$reject` - Merah/Abu)**.
   * Pada kartu **Ringkasan Tagihan**, tampilkan bar perbandingan visual *Invoice Lunas* vs *Menunggu Pembayaran*.

3. **Dashboard `admin` & `korlap` (`resources/views/admin/dashboard.blade.php` & `partials/korlap-ringkasan.blade.php`)**:
   * Di **Mode Admin**: Tambahkan **SVG Donut / Radial Ring** pada kartu *Ringkasan* untuk memvisualisasikan proporsi *Proyek yang Saya Pegang (PIC)* vs *Total Proyek Aktif* serta bar visual pada daftar *Bottleneck Kuota Proyek*.
   * Di **Mode Korlap (`partials/korlap-ringkasan.blade.php`)**: Pada kartu **Tingkat Kehadiran Lapangan**, gunakan **SVG Radial Progress Ring (`.viz-ring-wrap`)** untuk menampilkan persentase kehadiran beserta legend `Hadir`, `Menunggu Validasi`, dan `Belum Absen`.

## 4. Aturan Kualitas & Verifikasi Akhir

1. **Responsif di Desktop & Mobile**:
   * Pada layar sangat sempit (`<= 380px`), pastikan `.dash-viz-body` tetap muat dengan nyaman (`gap: 10px;` dan ukuran `.viz-donut-wrap` dapat mengecil ke `64px x 64px` jika diperlukan) tanpa pernah memotong teks di `.viz-legend`.
2. **Zero AI-Slop**:
   * Jangan menambahkan emoji Unicode, *em-dash* (`—`), atau panah teks mentah. Gunakan Tabler Icons (`<i class="ti ti-..."></i>`).
3. **Verifikasi Test Suite (`php artisan test`)**:
   * Pastikan seluruh angka dan teks yang di-assert oleh `SuperAdminDashboardTest`, `AdminDashboardTest`, `ClientDashboardTest`, dan `ExtrasDashboardTest` tetap ada di dalam kartu sehingga **100% test suite (962+ tests) tetap PASS (Hijau)**.
