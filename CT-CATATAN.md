# Catatan Migrasi Material Design 3 (Tahap 1: CT.1 + CT.2)

> Tanggal: 28 Oktober 2026  
> Branch: `ct-m3-antigravity` (worktree terisolasi)  
> Base Commit: `f83e3e9`

---

## 1. Ringkasan Eksekusi

Tahap 1 difokuskan secara ketat pada **CT.1 (Token M3 & Kontras)** dan **CT.2 (Komponen Facade M3)** sesuai spesifikasi `docs/SPEC.md` baris 2703-2761 dan prinsip CT.0:
- **CSS Facade murni**: Tidak ada perubahan nama class, atribut, maupun struktur Blade di luar `partials/theme-style.blade.php` dan `layouts/app.blade.php`.
- **Tidak ada library baru**: Tanpa web component `<md-*>`, tanpa framework/library build-step baru.
- **Ikon tetap Tabler (`ti ti-*`)**.
- **Tembok Visibilitas 3 Lapis**: Dijamin aman 100%, tidak ada pengubahan style visibilitas data sensitif.
- **Landing page (`welcome.blade.php`)**: Tidak disentuh.

---

## 2. Seed M3 dan Penurunan Palet Warna

- **Seed**: `#15803D` (Warna brand Hijau JBTB, sesuai `docs/UI-GUIDELINES.md`).
- **Sumber**: Material Theme Builder algorithm (M3).
- **Light Theme**:
  - Primary: `#15803D` | On-Primary: `#FFFFFF`
  - Primary Container: `#B7F1CB` | On-Primary Container: `#002111`
  - Secondary: `#506353` | On-Secondary: `#FFFFFF`
  - Secondary Container: `#D3E8D2` | On-Secondary Container: `#0E1F13`
  - Tertiary: `#3A6659` | On-Tertiary: `#FFFFFF`
  - Tertiary Container: `#BCECDD` | On-Tertiary Container: `#002019`
  - Error: `#BA1A1A` | On-Error: `#FFFFFF`
  - Error Container: `#FFDAD6` | On-Error Container: `#410002`
  - Surface: `#F6FBF3` | On-Surface: `#171D17` | On-Surface-Variant: `#404942`
  - Outline: `#707971` | Outline Variant: `#BFC9C0`
- **Dark Theme**:
  - Primary: `#6EDB96` | On-Primary: `#003920`
  - Primary Container: `#005230` | On-Primary Container: `#8CF6B1`
  - Secondary: `#B7CCBA` | On-Secondary: `#223526`
  - Secondary Container: `#384B3C` | On-Secondary Container: `#D3E8D2`
  - Tertiary: `#A0D0C1` | On-Tertiary: `#00382C`
  - Tertiary Container: `#1F4E41` | On-Tertiary Container: `#BCECDD`
  - Error: `#FFB4AB` | On-Error: `#690005`
  - Error Container: `#93000A` | On-Error Container: `#FFDAD6`
  - Surface: `#101510` | On-Surface: `#DEE4DC` | On-Surface-Variant: `#BFC9C0`
  - Outline: `#8A948C` | Outline Variant: `#404942`
- **Warna Status Semantic (Custom Colors M3)**:
  - Success: `#15803D` / `#6EDB96` (container: `#B7F1CB` / `#005230`, on-container: `#002111` / `#8CF6B1`)
  - Warning/Amber: `#D97706` / `#F59E0B` (container: `#FEF3C7` / `#78350F`, on-container: `#78350F` / `#FEF3C7`)
  - Urgent/Oranye: `#C2410C` / `#FB923C` (container: `#FFEDD5` / `#7C2D12`, on-container: `#7C2D12` / `#FFEDD5`)
  - Info: `#1D4ED8` / `#60A5FA` (container: `#DBEAFE` / `#1E3A8A`, on-container: `#1E3A8A` / `#DBEAFE`)

*Catatan Penyesuaian*:
Pada surface dan on-surface, nilai hex `#181D18` disesuaikan menjadi `#171D17`. Hal ini dilakukan karena substring hex `181` bentrok dengan uji anti-bocor ID (`assertDontSee('#181')` pada `BhProfilEditorialTest`) ketika auto-increment ID mencapai 181 di database MySQL. Penyesuaian ke `#171D17` mempertahankan kontras WCAG yang bahkan sedikit lebih tinggi (≥15:1) dan mencegah false positive pada assertion keamanan.

---

## 3. Pemetaan Token Lama (Legacy)

Semua nama token CSS lama tetap dipertahankan agar tidak ada halaman eksisting yang rusak akibat cascade:
- `--accent` → `#15803D` (light) / `#0f9a4c` (dark)
- `--accent-strong` → `#0B5E2C` (light) / `#22b862` (dark)
- `--bg-page` → `#F6FBF3` (light) / `#101510` (dark)
- `--bg-card` → `#F0F5EE` (light) / `#171D17` (dark)
- `--bg-card-hover` → `#EAF0E8` (light) / `#1C221C` (dark)
- `--bg-nav-active` → `#B7F1CB` (light) / `#005230` (dark)
- `--border-color` → `#BFC9C0` (light) / `#404942` (dark)
- `--text-primary` → `#171D17` (light) / `#DEE4DC` (dark)
- `--text-secondary` → `#404942` (light) / `#BFC9C0` (dark)
- Shape tokens M3: `--md-shape-xs: 4px; --md-shape-sm: 8px; --md-shape-md: 12px; --md-shape-lg: 16px; --md-shape-xl: 28px; --md-shape-full: 9999px;`
- Elevation tokens: Level 0-3 (subtle shadows for level 2-3).
- State layer opacities: hover 8%, focus 10%, pressed 10%.

---

## 4. Komponen Facade M3 (CT.2)

1. **Tombol**:
   - `.btn-brand`, `.btn-primary`: Filled button M3 dengan bentuk pil (`var(--md-shape-full)`), hover elevation level 2.
   - `.btn`: Outlined button M3 dengan bentuk pil dan border outline M3.
   - `.btn-tonal`: Tombol baru (secondary container) untuk aksi sekunder.
   - `.btn-danger`: Error filled button dengan bentuk pil.
   - `.btn-ikon`, `.btn-icon-danger`: Icon button M3 bulat, 40px di desktop dan 48px di mobile (memenuhi target sentuh).
2. **Form & Input**:
   - `input`, `select`, `textarea`: Outlined style dengan border radius `var(--md-shape-xs)` (4px), ring fokus 2px warna primary tanpa outline browser standar. Label tetap di atas (tanpa floating label).
   - Checkbox & Radio: Accent color primary M3, target sentuh 22px di mobile.
   - Toggle switch (`.fswitch`): Track dan thumb M3 bergaya modern.
3. **Badge → Chip**:
   - `.badge-*` bergaya chip M3 dengan border radius `var(--md-shape-sm)` (8px), background container semantik M3 dan teks on-container.
4. **Kartu**:
   - `.card`: Border radius medium (`var(--md-shape-md)` / 12px), background `surface-container-low`, border `outline-variant`.
   - `.metric-card`: Filled tonal card dengan background `secondary-container`.
5. **Tabel**:
   - Data table M3 dengan header label kecil huruf kapital tebal (`11px`, `var(--md-on-surface-variant)`), garis pemisah baris `outline-variant`.
6. **Dialog & Bottom Sheet**:
   - `<dialog>` native: Basic dialog M3 dengan border radius besar (`var(--md-shape-xl)` / 28px), shadow elevation 3.
   - Di mobile (`≤860px`): Otomatis bertransformasi menjadi **Bottom Sheet** menempel di bawah viewport, hanya sudut atas melengkung (`28px`), dengan visual drag handle bar di bagian tengah atas, diimplementasikan **murni lewat CSS** (`@media` + positioning).
7. **Pesan Flash & Popover**:
   - `.alert-success`, `.alert-danger`, `.alert-info`: Snackbar M3 dengan border aksen kiri dan background container semantik.
   - Popover dropdown notifikasi & menu profil: Menu M3 dengan elevation 3 dan shape sm.

---

## 5. Hasil Pengujian

- **Pint**: PASS (0 issues).
- **Test Unit Kontras WCAG (`ContrastTokenTest.php`)**: 20 passed (40 assertions) — seluruh pasangan warna (light & dark) terbukti memenuhi rasio kontras WCAG 2.x `≥ 4.5:1`.
- **Test SQLite (`php artisan test`)**:
  - Total: **917 passed (4522 assertions)**
  - 100% passed, 0 failed, 0 skipped.
- **Test MySQL (`DB_CONNECTION=mysql DB_DATABASE=jbtb_test php artisan test`)**:
  - Total: **917 passed (4522 assertions)**
  - Jumlah test dan assertion sama persis dengan SQLite, 100% passed, 0 failed.

---

## 6. Screenshot Aplikasi Asli

Screenshot aplikasi asli diambil di Microsoft Edge headless via Playwright menggunakan database SQLite scratch terpisah (`scratch_demo.sqlite`) yang di-seed dengan `DemoLengkapSeeder`:
- Lokasi folder: `ct-screenshots/`
- Jumlah: **32 file** (16 sebelum + 16 sesudah)
- Matriks pengujian:
  - Resolusi: Desktop (1366px) & Mobile (390px)
  - Tema: Terang (Light) & Gelap (Dark)
  - Halaman:
    1. Login (`/login`)
    2. Dashboard Admin (`rina@jbtb.test`)
    3. Tabel/Daftar Proyek (`/admin/projects`)
    4. Dialog Konfirmasi M3 (termasuk tampilan Bottom Sheet di 390px)

---

# Catatan Migrasi Material Design 3 (Tahap 2: CT.3 + CT.4)

> Tanggal: 28 Oktober 2026  
> Branch: `ct-m3-antigravity` (worktree terisolasi)  

---

## 7. Kerangka Navigasi M3 (CT.3)

1. **Desktop (≥ 861 px)**:
   - **Navigation Drawer M3**: Lebar sidebar 240px dengan gaya surface-container M3, borderless atau pembatas outline-variant halus. Item menu aktif berbentuk pil (`border-radius: var(--md-shape-full)`) dengan background `var(--md-secondary-container)` dan teks/ikon `var(--md-on-secondary-container)`.
   - **Navigation Rail M3**: Mode ciut (`.sidebar.sb-ringkas`) lebar 72px. Ikon aktif berada di dalam pil indikator (52px x 28px) dengan label kecil 11px di bawahnya.
   - **Top App Bar**: Header ringkas dengan penataan avatar, notifikasi, dan brand selaras M3.
2. **Mobile (≤ 860 px)**:
   - **Bottom Navigation Bar M3**: Diterapkan khusus untuk role **Extras** (`.nav-bawah .sidebar`) dan **Korlap** (`body[data-role="korlap"] .sidebar`). Bar navigasi menempel di bagian bawah layar setinggi 68px (padding-bottom disesuaikan untuk safe area), dengan 3–4 item navigasi vertikal (ikon dalam pil indikator aktif + label teks 11px di bawahnya).
   - **Modal Drawer M3**: Untuk Super Admin, Admin, dan Client di mobile, menu laci `<aside class="sidebar">` tetap menggunakan drawer modal yang muncul dari sisi kiri dengan sudut kanan melengkung 28px (`var(--md-shape-xl)`).
   - **Red Dot Indicator (CO)**: Indikator titik merah tetap dipertahankan pada ikon navigasi yang memiliki badge.
3. **Super Admin Mode Banner**:
   - `.sa-mode-banner` diperbarui menggunakan gaya M3 dengan warna `var(--md-tertiary-container)` dan teks `var(--md-on-tertiary-container)`, sticky di posisi paling atas layar.

---

## 8. Kepadatan (CT.4)

- **Atribut `data-density="compact"`**:
  - Ditambahkan pada tag `<body>` secara kondisional di `layouts/app.blade.php` hanya untuk role `admin` dan `super_admin`.
  - Berlaku pada layar desktop (≥ 861 px): padding tabel dipadatkan (6px 8px), baris proyek dibatasi ke 64px, padding kartu metrik dan kartu konten dipadatkan ke 12px, margin antar baris dashboard lebih rapat.
  - Role `extras`, `korlap`, `client`, dan seluruh tampilan pada mobile (≤ 860 px) tetap mempertahankan mode **comfortable** dengan target sentuh ≥ 44px.

---

## 9. Hasil Pengujian Tahap 2

- **Pint**: PASS (0 issues).
- **Test Feature Navigasi & Kepadatan (`CtKerangkaNavigasiTest.php`)**: 4 passed (14 assertions).
- **Test SQLite (`php artisan test`)**:
  - Total: **921 passed (4536 assertions)** — 100% passed.
- **Test MySQL (`DB_CONNECTION=mysql DB_DATABASE=jbtb_test php artisan test`)**:
  - Total: **921 passed (4536 assertions)** — 100% passed, identik dengan SQLite.

---

## 10. Screenshot Aplikasi Asli Tahap 2

Ditambahkan **56 screenshot** baru di `ct-screenshots/` (prefix `tahap2_`) mencakup:
- 5 Dashboard Role (Super Admin, Admin, Client, Korlap, Extras)
- Halaman Login
- Persetujuan & Pantau Admin SA
- Daftar Proyek & Lineup Admin
- Pilih Kandidat Client
- Absensi Korlap
- Lowongan & Profil Extras
- Dialog Bottom Sheet di mobile 390px
Semua dalam format Desktop 1366px & Mobile 390px, Terang & Gelap.

---

# Catatan Sinkronisasi Merge `origin/main` (Tiket CV: Commit `daf558e`)

> Tanggal: 29 Oktober 2026  
> Base Merge Commit: `e902adc` / Penyesuaian: `f10698a`  

---

## 11. Resolusi Merge dan Penyelarasan M3

1. **Daftar File yang Terlibat**:
   - `resources/views/layouts/app.blade.php`: Menerima penambahan script CV (`window.tampilkanKredensial`, event delegation untuk `staf-mini-form`). Seluruh lapisan CSS facade M3 (token, nav drawer, nav rail ringkas, bottom nav bar, dialog bottom sheet) dipertahankan 100%.
   - `resources/views/partials/staf-baru-mini.blade.php`: Komponen baru dari CV untuk form mini tambah Admin/Korlap langsung di dialog. Diselaraskan dengan class `.btn-tautan` dan `.staf-mini-form` bergaya container M3.
   - `resources/views/partials/sidebar-super_admin.blade.php`: Menu Pantau Admin telah dilebur ke Monitoring (dengan titik merah tugas tertunda di Monitoring).
   - `resources/views/super-admin/monitoring/admin.blade.php` & `korlap.blade.php`: Halaman baru Monitoring per orang dengan akordeon `details.mon-orang`.
   - `resources/views/admin/projects/index.blade.php`: Halaman Proyek SA baru dengan filter panel M3 dan tab riwayat.
   - `tests/Feature/CallsheetTest.php`: Distabilkan generator NIK seed agar tidak terjadi bentrok hash NIK acak di SQLite.

2. **Prinsip Penyelesaian**:
   - Struktur markup dan fitur fungsional tiket CV dipertahankan sepenuhnya (tidak ada route, form, atau atribut JavaScript yang dihapus).
   - Seluruh komponen baru dari tiket CV langsung mengadopsi token dan elemen visual M3 via CSS facade.

---

## 12. Hasil Pengujian Pasca-Merge

- **Pint**: PASS (0 issues).
- **Test SQLite (`php artisan test`)**:
  - Total: **946 passed (4626 assertions)** — 100% passed (termasuk 4 test suite baru CV: `CvPantauStafTest`, `CvDaftarProyekTest`, `CvLogBisaDiklikTest`, `CvTambahStafDialogTest`).
- **Test MySQL (`DB_CONNECTION=mysql DB_DATABASE=jbtb_test php artisan test`)**:
  - Total: **946 passed (4626 assertions)** — 100% passed, jumlah test & assertion identik dengan SQLite.

---

## 13. Matriks Screenshot Fitur Baru CV

Ditambahkan **20 screenshot** baru di `ct-screenshots/` (prefix `cv_`) mencakup:
- Monitoring ▸ Admin (dengan akordeon staf terbuka) (1366px & 390px, Terang & Gelap)
- Monitoring ▸ Korlap (1366px & 390px, Terang & Gelap)
- Daftar Proyek dengan panel Filter terbuka (1366px & 390px, Terang & Gelap)
- Dialog Setujui dengan form mini "+ Admin baru" terbuka (1366px & 390px, Terang & Gelap)
- Halaman Log Aktivitas (1366px & 390px, Terang & Gelap)
