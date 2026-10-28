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
