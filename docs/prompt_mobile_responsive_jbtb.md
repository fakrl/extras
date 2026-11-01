# Master Prompt: Perbaikan Total Responsivitas Mobile (Mobile Viewport 360px–860px) — SIM Casting JBTB (`fakrl/extras`)

> **Cara Pakai Dokumen Ini di Antigravity:** Simpan dokumen ini sebagai `docs/prompt_mobile_responsive_jbtb.md` di repositori lokalmu (atau *copy-paste* langsung ke Antigravity), lalu beri instruksi: *"Dari hasil inspeksi live di viewport mobile (375px–640px), perubahan Dashboard dan V3 sudah tampil bagus di desktop, tetapi ditemukan 6 masalah responsivitas mobile pada CSS dan struktur flex/grid (termasuk `.sticky-mobile-cta` yang menindih `.nav-bawah`, `.dash-hover-popover` yang keluar layar HP, `.sa-mode-banner` yang terhimpit, serta grup tombol ekspor/filter yang belum `flex-wrap`). Eksekusi seluruh perbaikan responsif mobile di `docs/prompt_mobile_responsive_jbtb.md` ini dan pastikan 100% test suite (`php artisan test`) tetap hijau."*

## 1. Ringkasan Temuan Inspeksi Live di Situs Ngrok (Desktop vs Mobile)

* **Status Fitur Baru (Sudah Aktif & Terverifikasi di Live)**:
  * Ke-4 kartu ekosistem baru di `/super-admin/dashboard` (*Populasi Akun*, *Ekosistem Proyek*, *Finansial & Margin*, *Funnel Partisipasi*) serta kartu karir/dompet di `/extras/dashboard` dan progress kuota di `/client/reviews/3` **sudah tampil dan berjalan baik di layar desktop**.
  * Pembersihan emoji Unicode dan *em-dash* (V3) sudah diterapkan dengan bersih di teks UI.

* **6 Bug Responsivitas Mobile (`360px–860px`) yang Ditemukan dari Inspeksi DOM & CSS Live**:
  1. **Tabrakan `.sticky-mobile-cta` dengan `.nav-bawah` di `/extras/lowongan/{id}`**: `.sticky-mobile-cta` memiliki `bottom: 0; z-index: 100;`, sedangkan `.nav-bawah .sidebar` memiliki `position: fixed; bottom: 0; height: 68px; z-index: 50;`. Akibatnya, saat halaman detail lowongan dibuka di HP, bar CTA melayang **menindih/menutupi menu navigasi bawah Extras**.
  2. **`.dash-hover-popover` Terpotong & Sulit Ditutup di Layar Sentuh HP (`<= 640px`)**: Popover rincian pada `.dash-stat-card` menggunakan `position: absolute` berbasis `:hover` desktop tanpa aturan khusus mobile, sehingga saat di-tap di layar HP sempit (`375px`), popover keluar dari batas kartu atau terpotong di tepi layar.
  3. **Banner Simulasi Role (`.sa-mode-banner` / `switch-role-banner.blade.php`) Terhimpit di Layar Sempit**: `.sa-mode-banner` memakai `display: flex; justify-content: space-between;` tanpa `flex-wrap: wrap`, sehingga teks status dan tombol `[Kembali ke Super Admin]` berdesakan di layar `375px`.
  4. **Grup Tombol Header & Ekspor Tanpa `flex-wrap` di `/client/reviews/{id}`, `/admin/projects/{id}`, dan `/super-admin/akun`**: Tombol `[Pratinjau PDF]`, `[Ekspor PDF]`, `[Ekspor Excel]` di halaman review Client serta deretan tombol aksi di kanan atas header proyek/akun terpotong di tepi kanan layar HP.
  5. **Bar Pencarian, Filter, & Sort di `/admin/projects/{id}/applicants` Terlalu Sempit di HP**: Kotak search, tombol Filter, dropdown Sort, dropdown Per Halaman, dan toggle `[Kartu]`/`[Daftar]` bertumpuk kaku ketika layar menyempit ke `375px`.
  6. **Grid Dashboard (`.dash-pulse-strip`, `.dashboard-grid-2col`, `.dash-tiga`) & Canvas Tanda Tangan (`signature-pad`)**: Beberapa container grid/flex inline dan elemen `<canvas>` tanda tangan kontrak perlu penguncian `max-width: 100%` agar tidak memicu *horizontal page scroll* di HP.

## 2. Instruksi Perbaikan Eksak Per Masalah (CSS & Blade)

### A. Perbaikan 1: Selesaikan Tabrakan `.sticky-mobile-cta` vs `.nav-bawah`
* **File Target**:
  - `resources/views/partials/theme-style.blade.php`
  - `resources/views/extras/projects/show.blade.php`
* **Instruksi Perbaikan**:
  1. Di `resources/views/partials/theme-style.blade.php`, samakan breakpoint `.sticky-mobile-cta` menjadi `@media (max-width: 860px)` dan ketika berada di dalam layout `.nav-bawah` (atau secara default di mobile), atur posisinya agar **duduk tepat di atas `.nav-bawah` (`bottom: 68px`)**, bukan menindihnya:
     ```css
     @media (max-width: 860px) {
         .sticky-mobile-cta {
             display: flex;
             align-items: center;
             justify-content: space-between;
             gap: 12px;
             position: fixed;
             bottom: 0;
             left: 0;
             right: 0;
             z-index: 48;
             background: var(--bg-card);
             border-top: 1px solid var(--border-color);
             padding: 10px 16px;
             box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
         }
         .nav-bawah .sticky-mobile-cta {
             bottom: 68px;
         }
         .nav-bawah main,
         .nav-bawah .main-content {
             padding-bottom: 148px !important;
         }
     }
     ```

### B. Perbaikan 2: Buat `.dash-hover-popover` Ramah Layar Sentuh HP (`<= 640px`)
* **File Target**: `resources/views/partials/theme-style.blade.php`
* **Instruksi Perbaikan**:
  1. Pada desktop (`@media (min-width: 641px)`), pertahankan perilaku floating popover saat `.dash-stat-card:hover` dan `:focus-within`, namun pastikan `left: 0; right: 0; max-width: 100%; box-sizing: border-box;` agar tidak pernah melebar melebihi lebar kartu/layar.
  2. Pada layar mobile (`@media (max-width: 640px)`), ubah `.dash-hover-popover` agar ketika kartu di-tap (`:focus-within` / `:hover` / `.is-open`), rinciannya tampil sebagai **panel akordion di dalam kartu (`position: static`)** yang mendorong isi kartu ke bawah secara rapi dan tidak pernah keluar dari tepi layar HP:
     ```css
     @media (max-width: 640px) {
         .dash-stat-card {
             cursor: pointer;
         }
         .dash-hover-popover {
             position: static !important;
             opacity: 1 !important;
             visibility: visible !important;
             transform: none !important;
             display: none;
             margin-top: 10px;
             padding: 10px;
             background: var(--surface-2, rgba(0,0,0,0.03));
             border: 1px solid var(--border-color);
             border-radius: var(--radius-sm);
             box-shadow: none !important;
             width: 100%;
             box-sizing: border-box;
         }
         .dash-stat-card:hover .dash-hover-popover,
         .dash-stat-card:focus-within .dash-hover-popover,
         .dash-stat-card:active .dash-hover-popover {
             display: block;
         }
     }
     ```
  3. Tambahkan atribut `tabindex="0"` pada elemen `.dash-stat-card` di Blade view dashboard agar di perangkat iOS/Android pengguna cukup mengetuk (*tap*) kartu untuk membuka rinciannya dan mengetuk di luar kartu untuk menutupnya kembali.

### C. Perbaikan 3: Rapikan `.sa-mode-banner` (`switch-role-banner.blade.php`) & `.topbar` di HP
* **File Target**:
  - `resources/views/partials/switch-role-banner.blade.php`
  - `resources/views/partials/theme-style.blade.php`
* **Instruksi Perbaikan**:
  1. Tambahkan aturan responsif pada `.sa-mode-banner` di `theme-style.blade.php`:
     ```css
     .sa-mode-banner {
         display: flex;
         align-items: center;
         justify-content: space-between;
         flex-wrap: wrap;
         gap: 8px 12px;
     }
     @media (max-width: 640px) {
         .sa-mode-banner {
             padding: 10px 12px !important;
             font-size: 12px !important;
             top: 4px !important;
         }
         .sa-mode-banner .btn,
         .sa-mode-banner form,
         .sa-mode-banner a {
             width: 100%;
             justify-content: center;
             text-align: center;
         }
     }
     ```
  2. Pada `.topbar` di `@media (max-width: 640px)`, pastikan judul halaman panjang di topbar menggunakan `white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px;` sehingga tidak mendorong ikon notifikasi, toggle dark mode, dan avatar keluar dari tepi kanan layar.

### D. Perbaikan 4: Tambahkan `flex-wrap` & Responsivitas Mobile pada Grup Tombol Header, Ekspor, dan Filter
* **File Target**:
  - `resources/views/client/reviews/show.blade.php` (`/client/reviews/{id}`)
  - `resources/views/admin/projects/show.blade.php` (`/admin/projects/{id}`)
  - `resources/views/admin/projects/applicants.blade.php` (`/admin/projects/{id}/applicants`)
  - `resources/views/super-admin/akun/index.blade.php` (`/super-admin/akun`)
  - `resources/views/partials/theme-style.blade.php`
* **Instruksi Perbaikan**:
  1. **Di `/client/reviews/{id}` (`client/reviews/show.blade.php`)**:
     - Pada container yang membungkus tombol **`[Pratinjau PDF]`**, **`[Ekspor PDF]`**, dan **`[Ekspor Excel]`**, tambahkan `display: flex; flex-wrap: wrap; gap: 8px;`.
     - Pada `@media (max-width: 640px)`, buat tombol-tombol tersebut memiliki `flex: 1 1 calc(50% - 8px); justify-content: center;` sehingga tersusun rapi dalam grid 2 kolom di layar HP tanpa terpotong ke kanan.
  2. **Di `/admin/projects/{id}` (`admin/projects/show.blade.php`) & `/super-admin/akun` (`super-admin/akun/index.blade.php`)**:
     - Pastikan container tombol aksi di kanan atas header proyek (`Edit Proyek`, `Kelola Lineup`, `Dokumen Invoice`) dan header akun (`+ Tambah Client`, `+ Tambah Staf`) memiliki `display: flex; flex-wrap: wrap; gap: 8px; width: 100%;` pada `@media (max-width: 640px)`, di mana tombol utama (`.btn-brand`) mengambil lebar penuh (`flex: 1 1 100%`) dan tombol sekunder berbagi baris di bawahnya (`flex: 1 1 calc(50% - 4px)`).
  3. **Di `/admin/projects/{id}/applicants` (`admin/projects/applicants.blade.php`)**:
     - Pada bar kontrol pencarian, tombol `[Filter]`, dropdown sorting (`Terbaru`), dan dropdown jumlah per halaman (`12`), pastikan di `@media (max-width: 640px)` kotak input pencarian menempati baris pertama penuh (`flex: 1 1 100%; width: 100%;`), sementara tombol `[Filter]` dan kedua dropdown tersusun rapi di baris kedua dengan `flex: 1 1 auto; min-width: 0;`.
     - Pada header tab `Lineup` / `Sudah ke Client` dan switch `[Kartu]` / `[Daftar]`, pastikan container induknya memiliki `flex-wrap: wrap; gap: 10px;` agar di layar `375px` tidak bertabrakan.
     - Pada **Mode Daftar (`?tampil=daftar`)** di layar HP (`<= 640px`), pastikan setiap baris kandidat memiliki `flex-wrap: wrap; gap: 8px;` dan grup tombol aksinya (`Lihat Profil`, `WA`, bintang) berada di baris bawah yang rapi di dalam kartu baris tersebut.

### E. Perbaikan 5: Kunci Semua Grid Dashboard, Tabel, Modal, & Canvas Tanda Tangan di Layar HP (`<= 640px`)
* **File Target**:
  - `resources/views/partials/theme-style.blade.php`
  - `resources/views/components/signature-pad.blade.php`
* **Instruksi Perbaikan**:
  1. **Grid Dashboard di `@media (max-width: 640px)`**:
     - Pastikan `.dash-pulse-strip`, `.dashboard-grid-2col`, `.dash-tiga`, dan `.sa-command-grid` di `@media (max-width: 640px)` memiliki aturan tegas:
       ```css
       @media (max-width: 640px) {
           .dash-pulse-strip,
           .dashboard-grid-2col,
           .dash-tiga,
           .sa-command-grid {
               display: grid !important;
               grid-template-columns: 1fr !important;
               gap: 12px !important;
           }
       }
       ```
  2. **Pembungkus Tabel & Modal di Layar HP**:
     - Pastikan setiap tabel data di halaman `/super-admin/akun`, `/admin/projects`, `/super-admin/activity-logs`, dan `/invoice` terbungkus container dengan `overflow-x: auto; -webkit-overflow-scrolling: touch; max-width: 100%;` sehingga halaman utama (`body`) tidak pernah mengalami *horizontal scroll*.
     - Pastikan semua `<dialog>` modal (termasuk modal detail kandidat `#detail-...` dan `#profil-...`) pada `@media (max-width: 640px)` memiliki `width: calc(100vw - 16px) !important; max-width: 100% !important; margin: auto 8px !important; box-sizing: border-box;`.
  3. **Canvas Tanda Tangan Kontrak (`resources/views/components/signature-pad.blade.php`)**:
     - Pastikan elemen `<canvas>` tanda tangan digital memiliki CSS `width: 100% !important; max-width: 100%; height: auto; touch-action: none; box-sizing: border-box;` agar di layar HP `375px` tidak melebar keluar batas kartu kontrak.

## 3. Verifikasi Akhir Wajib
1. Jalankan `php artisan view:clear` dan `php artisan config:clear`.
2. Pastikan tidak ada penambahan emoji Unicode, *em-dash* (`—`), atau panah teks mentah baru.
3. Jalankan `php artisan test` dan pastikan seluruh **967+ tests** tetap **PASS (Hijau 100%)**.
