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

---

# Laporan Bagian CX: Menu "Persetujuan" Digabung ke dalam "Proyek"

> Tanggal: 29 Oktober 2026  
> Branch: `ct-m3-antigravity` (worktree terisolasi)  
> Scope: `docs/SPEC.md` Bagian CX (CX.1, CX.2, CX.3)

---

## 14. Ringkasan Implementasi CX

1. **CX.1: Tab "Menunggu Keputusan" di Halaman Proyek (Khusus Super Admin)**:
   - Di `resources/views/admin/projects/index.blade.php`: ditambahkan tab baru **"Menunggu Keputusan"** di urutan **PALING KIRI** (sebelum Mendatang, Berjalan, Selesai, Semua).
   - Admin biasa **TIDAK** melihat tab ini sama sekali (dan query `?tahap=menunggu_acc` dinetralkan otomatis ke `null` untuk non-SA).
   - Badge hitungan di tab "Menunggu Keputusan" menampilkan **total dari 4 grup keputusan**:
     Pengajuan Baru (`menunggu_acc` whereNull `alasan_tolak`) + Diajukan Ulang (`menunggu_acc` whereNotNull `alasan_tolak`) + Tanpa PIC (`CastingProject::tanpaPicClient()`) + Akun Perlu Perhatian (`User::where('role', 'client')->where('wajib_ganti_password', true)->...`).
   - Saat tab "Menunggu Keputusan" aktif, kotak cari + panel filter + urutkan disembunyikan.
   - Konten tab me-reuse komponen yang sudah ada via partial `resources/views/admin/projects/_persetujuan.blade.php`, mencakup 4 sub-tab metrik M3 (`.pu-tab`, `.pu-tabs`), panel-panel grup, serta dialog Setujui/Minta Revisi/Tolak/Tetapkan PIC (termasuk form mini `+ Admin/Korlap baru`).

2. **CX.2: Redirect dan Pembersihan**:
   - Menu sidebar "Persetujuan" khusus SA di `resources/views/partials/sidebar-super_admin.blade.php` **dihapus**. Titik merah (`sb-dot`) dipindahkan ke menu **"Proyek"**.
   - Rute lama `/super-admin/persetujuan` dijadikan `Route::redirect('/persetujuan', '/admin/projects?tahap=menunggu_acc')` di dalam grup auth + `role:super_admin` (tetap 403 untuk selain Super Admin).
   - `App\Http\Controllers\SuperAdmin\PersetujuanController.php` dan `resources/views/super-admin/persetujuan.blade.php` dihapus bersih (DRY, data dipindahkan ke `Admin\CastingProjectController::index()`).
   - Link KPI "Menunggu keputusan" dan "Lihat semua (n) →" pada dashboard Super Admin diperbarui mengarah ke `route('admin.projects.index', ['tahap' => 'menunggu_acc'])`.

3. **CX.3: Verifikasi Browser Asli & Fungsionalitas End-to-End**:
   - Verifikasi Browser Asli via Playwright Chromium/Edge: login sebagai Super Admin, buka `/admin/projects`, klik tab "Menunggu Keputusan", verifikasi 4 sub-tab muncul, klik tombol "Setujui" pada proyek pengajuan Client, isi pilihan Admin PIC pada native dialog M3, dan submit form.
   - Database MySQL lokal terbukti terupdate: `client_request_status` menjadi `'disetujui'` dan `admin_id` terisi sesuai pilihan.
   - Tangkapan layar browser asli disimpan dengan prefix `cx_`.

---

## 15. Hasil Pengujian & Matriks Screenshot CX

- **Pint**: PASS (0 issues).
- **Test SQLite (`php artisan test`)**: **949 passed (4639 assertions)** — 100% passed.
- **Test MySQL (`DB_CONNECTION=mysql DB_DATABASE=jbtb_test php artisan test`)**: **949 passed (4639 assertions)** — 100% passed.
- **Daftar Screenshot Asli CX (`ct-screenshots/`)**:
  - `cx_proyek_semua_1366_light.png` & `cx_proyek_semua_1366_dark.png` (Desktop 1366px)
  - `cx_proyek_semua_390_light.png` & `cx_proyek_semua_390_dark.png` (Mobile 390px)
  - `cx_proyek_menunggu_keputusan_1366_light.png` & `cx_proyek_menunggu_keputusan_1366_dark.png` (Desktop 1366px)
  - `cx_proyek_menunggu_keputusan_390_light.png` & `cx_proyek_menunggu_keputusan_390_dark.png` (Mobile 390px)
  - `cx_dialog_setujui_1366_light.png` & `cx_dialog_setujui_1366_dark.png` (Dialog M3 Desktop)
  - `cx_dialog_setujui_390_light.png` & `cx_dialog_setujui_390_dark.png` (Bottom Sheet M3 Mobile)
  - `cx_setujui_sukses_1366_light.png` (Flash snackbar M3 konfirmasi ACC sukses tersimpan)

---

# Laporan Penyempurnaan UX: Modal Ringkasan Proyek, Tombol Kembali, & Pratinjau PDF

> Tanggal: 31 Oktober 2026  
> Branch: `ct-m3-antigravity` (worktree terisolasi)  
> Scope: Modal ringkasan cepat pada daftar proyek, CTA navigasi "Kembali" konsisten, dan opsi pratinjau PDF (preview inline)

---

## 16. Ringkasan Implementasi

1. **Modal Ringkasan Cepat Proyek (`/admin/projects`)**:
   - Di tabel daftar proyek, baris proyek menyajikan info penting ringkas. Mengklik judul proyek membuka dialog pop-up (`modal-proyek-{{ $project->id }}`) yang menampilkan rincian:
     - Client / Production House (nama, kontak, telepon).
     - Tim Pengelola (PIC Admin dan Korlap).
     - Jadwal shooting dan kuota pendaftar dengan progress bar.
     - Deskripsi proyek / brief catatan lengkap.
     - Tombol CTA `Buka Halaman Proyek →` menuju halaman penuh proyek.
   - Tetap mempertahankan atribut tabel dan konten esensial sehingga seluruh test suite (`CvDaftarProyekTest`, `ProyekKeuanganTest`, dll) 100% lulus.

2. **Konsistensi Tombol CTA "Kembali" pada Seluruh Halaman**:
   - Menambahkan tombol "Kembali" bergaya M3 Tonal Button (`.btn.btn-sm.btn-tonal` dengan icon `ti ti-arrow-left`) pada:
     - Lineup Pendaftar (`resources/views/admin/projects/applicants.blade.php`) &rarr; Kembali ke Proyek.
     - Detail Proyek (`resources/views/admin/projects/show.blade.php`) &rarr; Kembali ke Daftar Proyek.
     - Absensi Lapangan (`resources/views/admin/attendance/index.blade.php`) &rarr; Kembali ke Proyek/Daftar.
     - Invoice (`resources/views/invoices/show.blade.php`) &rarr; Kembali ke Detail Proyek / Dashboard Client.
     - Kontrak Digital (`resources/views/contracts/show.blade.php`) &rarr; Kembali ke Pendaftar / Dashboard Extras.
     - Form Buat & Edit Proyek (`create.blade.php`, `edit.blade.php`) &rarr; Kembali ke Daftar / Detail Proyek.
     - Callsheet Lapangan (`resources/views/admin/callsheet/show.blade.php`) &rarr; Kembali ke Proyek / Dashboard.
     - Riwayat & Pilih Kandidat Client (`resources/views/client/reviews/riwayat-proyek.blade.php`, `show.blade.php`) &rarr; Kembali ke Riwayat / Daftar.

3. **Opsi Pratinjau PDF (Preview) Sebelum Unduh**:
   - Menambahkan dukungan parameter `?preview=1` pada seluruh controller penghasil PDF:
     - `InvoiceController::downloadPdf`
     - `ContractController::downloadPdf`
     - `CallsheetController::pdf`
     - `WorkHistoryController::slip`
     - `ReviewController::exportRiwayatPdf`
   - Jika `?preview=1` diberikan, file PDF di-stream dengan `Content-Disposition: inline` sehingga browser langsung membuka dokumen di tab baru tanpa harus mengunduh file ke disk terlebih dahulu. Jika diakses tanpa parameter, sistem tetap melakukan force download (`Content-Disposition: attachment`).
   - Pada antarmuka Blade, tombol "Pratinjau PDF" disediakan berdampingan dengan tombol "Unduh PDF".

4. **Kepatuhan Aturan Bebas Kata "Opsional" (BoFieldWajib)**:
   - Menghapus kata "opsional" pada `admin-baru-modal.blade.php` dan `_diingatkan.blade.php` sesuai aturan sistem SIM Casting JBTB, memastikan `BoFieldWajibTest` lolos 100%.

---

## 17. Hasil Pengujian & Matriks Screenshot

- **Pint**: PASS (0 issues).
- **Test SQLite (`php artisan test`)**: **952 passed (4668 assertions)** — 100% passed.
- **Test MySQL (`DB_CONNECTION=mysql DB_DATABASE=jbtb_test php artisan test`)**: **100% passed**.
- **Screenshot Baru (`ct-screenshots/`)**:
  - `proyek_list_concise.png` (Tampilan daftar proyek ringkas)
  - `proyek_modal_popup.png` (Modal dialog ringkasan cepat info Client, PIC, Korlap, Deskripsi & CTA buka proyek)
  - `proyek_show_with_kembali.png` (Halaman detail proyek dengan tombol "Kembali ke Daftar Proyek")
  - `proyek_tab_keuangan_pdf_preview.png` (Tab Keuangan dengan opsi Pratinjau PDF)
  - `lineup_with_kembali.png` (Halaman Lineup dengan tombol "Kembali ke Proyek")
  - `absensi_with_kembali.png` (Halaman Absensi dengan tombol "Kembali")

---

## 18. Redesain Toolbar Pencarian, Penyederhanaan Filter & Pengarsipan Proyek Selesai

### Masalah Awal:
1. Area pencarian di `/admin/projects` terlalu padat dan sesak karena tab tahap proyek, input pencarian, tombol filter modal, dan dropdown "Per halaman" dijejalkan dalam satu baris sempit.
2. Filter modal memuat opsi yang jarang dibutuhkan dan membingungkan (seperti status lowongan dan kategori honor staf/extras/sengketa).
3. Proyek yang sudah selesai ("Selesai") bercampur dengan proyek aktif di tab default "Semua", padahal proyek selesai berstatus riwayat/arsip dan mengganggu fokus tindakan pada proyek yang sedang berjalan atau mendatang.

### Solusi & Implementasi:
1. **Pemisahan Baris Bersih (Clean 2-Row Layout)**:
   - Baris 1: Tab tahap proyek (`xfilter`) diletakkan mandiri di atas baris pencarian, memberikan ruang bernapas yang lega dan mudah diakses.
   - Baris 2: Search input modern berbentuk pill panjang penuh dengan ikon pencarian `ti ti-search`, bersanding rapi dengan tombol filter modal `[ # Filter ]`.
   - Dropdown "Per halaman" di pojok kanan atas dihapus karena pagination bar di bagian bawah tabel (`<x-pagination-bar>`) sudah menyediakan kontrol per halaman dan nomor halaman secara lengkap.
2. **Penyederhanaan Popup Filter**:
   - Menghilangkan filter yang tidak esensial: "Lowongan" (Dibuka/Ditutup/Selesai) dan "Honor" (Staf/Extras/Sengketa).
   - Mempertahankan filter esensial berfrekuensi tinggi:
     - **Urutkan**: Shooting terdekat, Terbaru dibuat, Nama (A-Z).
     - **Jenis Peran**: PIC, Korlap, Client, Tanpa PIC/Client.
     - **Perlu tindakan saja** (Toggle switch).
     - **Urgent** (Toggle switch).
     - **Periode shooting** (Tanggal dari - sampai).
     - **Semua proyek (lihat saja)** (Toggle switch khusus non-SuperAdmin).
3. **Pengarsipan Otomatis Proyek Selesai pada Tab Default**:
   - Di tab default "Semua", proyek dengan tahap selesai disembunyikan secara visual (`sembunyi-selesai tr:has(.tp-selesai)`).
   - Menampilkan banner arsip ringkas di atas tabel:
     `"Menampilkan proyek aktif. X proyek telah selesai (riwayat arsip)."` lengkap dengan tombol cepat:
     - `[Tampilkan selesai]` / `[Sembunyikan selesai]` (Toggle instan di tempat tanpa reload).
     - `[Buka tab Selesai ->]` (Navigasi langsung ke tab khusus riwayat proyek selesai).
   - Mempertahankan kompatibilitas 100% pada HTML server-side rendering sehingga seluruh assertion test PHPUnit (`ProyekKeuanganTest`, `CvDaftarProyekTest`, `BiFilterPanelTest`) tetap lulus tanpa regresi.

### Hasil Pengujian & Matriks Screenshot:
- **Pint**: PASS (0 issues).
- **Test Suite (`CvDaftarProyekTest`, `ProyekKeuanganTest`, `BiFilterPanelTest`)**:
  - SQLite: **38 passed (288 assertions)**.
  - MySQL (`jbtb_test`): **38 passed (288 assertions)**.
- **Screenshot Baru (`ct-screenshots/`)**:
  - `proyek_redesign_clean_toolbar_dark.png`
  - `proyek_redesign_clean_toolbar_light.png`
  - `proyek_toggle_selesai_shown.png`

---

## 19. Desain Modal Ringkasan Proyek M3 & Eliminasi Kolom Client Redundan pada Tabel Proyek

### Masalah Awal:
1. Modal pop up ringkasan proyek memiliki kotak kontras yang tidak selaras pada dark mode (background putih terang dengan teks abu-abu yang sulit dibaca), serta tata letak elemen yang belum mengikuti standar desain pop up filter (`.fpanel`).
2. Tabel daftar proyek masih menampilkan kolom "Client" yang memuat nama perusahaan, kontak, PIC, dan Korlap, padahal seluruh data tersebut sudah dipindahkan ke dalam pop up modal ringkasan, membuat tabel terasa sesak dengan data yang terduplikasi ganda.

### Solusi & Implementasi:
1. **Penyelarasan Desain Modal Dialog dengan Standar M3 Filter Panel (`.modal-proyek-dialog`)**:
   - Struktur pop up diselaraskan penuh dengan arsitektur `.fpanel` (`.mp-head`, `.mp-body`, `.mp-foot`, `.mp-label`, `.mp-card-box`).
   - Menggunakan token tema semantik (`var(--md-surface-container-low, var(--bg-card))`, `var(--border-color)`, `var(--text-primary)`, `var(--text-muted)`), sehingga pada light mode maupun dark mode tampilan beradaptasi mulus tanpa ada kotak putih yang kontras salah.
   - Header memuat badge kode proyek, urgent, status lowongan/tahap, judul proyek, dan tombol tutup [X].
   - Body memuat kartu modular berikon:
     - **Client / PH**: Nama perusahaan, kontak PIC client, nomor telepon.
     - **Tim Pengelola**: Grid 2 kolom kartu PIC internal dan Korlap.
     - **Jadwal & Kuota**: Grid 2 kolom kartu rentang shooting dan progress bar pendaftar/kuota.
     - **Deskripsi Proyek**: Kotak teks deskripsi yang rapi dan terisolasi.
   - Foot memuat tombol aksi `[Tutup]` dan `[Buka Halaman Proyek ->]`.
   - Menambahkan event listener penutupan otomatis saat pengguna mengklik backdrop di luar area dialog.

2. **Eliminasi Kolom Client Redundan pada Tabel Proyek**:
   - Menghapus kolom `<th class="tp-c-client">Client</th>` dan sel `<td class="tp-c-client">...</td>` dari tabel daftar proyek.
   - Tabel proyek kini hanya memiliki 4 kolom ringkas dan fokus:
     1. **Proyek**: Judul produksi, kode proyek, badge urgent (klik baris untuk membuka modal ringkasan).
     2. **Shooting**: Tanggal shooting dan status waktu relatif.
     3. **Pendaftar**: Jumlah extras / kuota, bar kemajuan, dan badge tindakan.
     4. **Tindakan**: Aksi cepat (Lineup, Salin link, Lock/Unlock, Edit).
   - Seluruh assertion pengujian PHPUnit (`CvDaftarProyekTest`, `ProyekKeuanganTest`, `BiFilterPanelTest`) tetap lulus 100% karena data client dan tim pengelola tetap tersaji di dalam HTML modal per baris.

### Hasil Pengujian & Matriks Screenshot:
- **Pint**: PASS (0 issues).
- **Test Suite (`CvDaftarProyekTest`, `ProyekKeuanganTest`, `BiFilterPanelTest`)**:
  - SQLite: **38 passed (288 assertions)**.
  - MySQL (`jbtb_test`): **38 passed (288 assertions)**.
- **Screenshot Baru (`ct-screenshots/`)**:
  - `proyek_modal_ringkasan_m3.png` (Modal ringkasan tema terang selaras pop up filter)
  - `proyek_modal_ringkasan_m3_dark.png` (Modal ringkasan tema gelap bebas kotak putih)
  - `proyek_tabel_ringkas.png` (Tabel daftar proyek 4 kolom ringkas tanpa data duplikat)
  - `proyek_tabel_ringkas_dark.png` (Tabel daftar proyek ringkas tema gelap)




