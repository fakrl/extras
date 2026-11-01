# Master Prompt V2: Rombak Total Arsitektur Layout & Visual (Macro Layout Overhaul) — SIM Casting JBTB (`fakrl/extras`)

## 1. Evaluasi Iterasi Sebelumnya & Mengapa Perlu Rombak Makro (Macro Overhaul)

Pada eksekusi tahap pertama, perubahan yang terjadi di banyak file utama masih terlalu kecil secara struktural (misalnya `partials/extras-card.blade.php` hanya berubah 3 baris, `super-admin/dashboard` hanya 6 baris, `client/dashboard.blade.php` hanya 16 baris, dan `admin/projects/show.blade.php` hanya 24 baris). Akibatnya, pengguna hanya melihat perubahan teks tombol atau tambahan chip kecil, sementara **kerangka tata letak (layout), bentuk kartu, posisi kolom, dan pengalaman visual halamannya masih terasa 90% sama seperti sebelumnya**.

Pada **Iterasi V2 (Macro Layout Overhaul)** ini, kamu **WAJIB merombak struktur tata letak (HTML Grid/Flexbox, arsitektur kartu, Slide-Over Drawer, Split-Screen Workspace, dan Hero Boarding-Pass Card)** sehingga perbedaan sebelum dan sesudah langsung terlihat drastis, modern, dan memandu pengguna awam secara instan.

## 2. Aturan Emas & Strategi Menjaga `962 Test Suite` Tetap Hijau 100%

Agar kamu **berani merombak layout HTML & CSS secara besar-besaran** tanpa takut merusak *automated test* (`CpUiTest`, `CallinganCardTest`, `SuperAdminAdminManagementTest`, dll.), ikuti strategi teknis ini:

1. **Pertahankan Hook Selector yang Diuji Test**:
   - Sebelum merombak `resources/views/admin/projects/applicants.blade.php`, `resources/views/partials/extras-card.blade.php`, dan `resources/views/partials/application-progress.blade.php`, periksa selector yang diuji di `tests/Feature/CpUiTest.php` dan `tests/Feature/CallinganCardTest.php`.
   - **Tetap pasang class/atribut hook tersebut pada struktur HTML baru** (contoh: tetap pertahankan `<dialog class="xmodal xdrawer-right" id="detail-{{ $app->id }}">`, tetap pertahankan class `.xaksi` pada kontainer aksi, dan pastikan tidak ada duplikasi URL link negosiasi yang melanggar `CallinganCardTest`).
   - Jangan pernah mengubah URL `route(...)`, HTTP method (`POST`/`PATCH`/`DELETE`), atau atribut `name="..."` pada input form.
2. **Gunakan Sistem Token & Palet Warna JBTB**:
   - Latar halaman tetap krem hangat (`#f5f4ef` / `#EEF2EA` di Light Mode, `#0B1310` di Dark Mode), warna aksen hijau JBTB (`#15803D`), dan header gelap (`#0B1A12`).
   - Tambahkan class layout makro baru di `resources/views/partials/theme-style.blade.php` (mulai dari `.sa-command-grid`, `.lineup-stage-strip`, `.ecard-v2`, `.xdrawer-right`, `.boarding-pass-card`, `.job-board-grid`, hingga `.project-apply-split`).

## 3. Spesifikasi Rombak Layout Makro Per Halaman (Wajib Dieksekusi Penuh)

### A. Rombak Layout 1: Dashboard Super Admin — "Action-First Asymmetric Command Center"

* **File Target**:
  - `resources/views/super-admin/dashboard.blade.php` (atau `resources/views/super-admin/index.blade.php`)
  - `resources/views/super-admin/dashboard/_butuh-keputusan.blade.php`
  - `resources/views/super-admin/dashboard/_diingatkan.blade.php`
  - `resources/views/partials/theme-style.blade.php`
* **Masalah Layout Saat Ini**:
  - Layout masih berupa tumpukan vertikal standar: 4 kotak KPI di atas $\rightarrow$ Kalender lebar penuh di tengah $\rightarrow$ 2 kotak daftar (*"Butuh keputusan Anda"* & *"Staf perlu diingatkan"*) tersembunyi di bagian paling bawah layar. Saat Super Admin login, mereka harus *scroll* ke bawah dulu untuk melihat pekerjaan terpenting mereka.
* **Arsitektur Layout Baru yang Wajib Dibangun**:
  1. **Baris 1 — Interactive KPI Strip (4 Kolom dengan Accent Border)**:
     - Ubah ke-4 kartu KPI di bagian atas agar memiliki **Left Accent Bar (`border-left: 5px solid ...`)** sesuai tingkat urgensi:
       - *Menunggu keputusan*: Aksen Amber/Kuning tegas (`#D97706`) + ikon menonjol.
       - *Tugas staf tertunda*: Aksen Merah/Oranye (`#DC2626`).
       - *Proyek berjalan*: Aksen Hijau JBTB (`#15803D`).
       - *Invoice belum lunas*: Aksen Biru/Slate (`#2563EB`).
  2. **Baris 2 — Asymmetric 2-Column Command Grid (`.sa-command-grid`) — NAIK KE ATAS**:
     - Ubah susunan di bawah KPI menjadi **Grid 2 Kolom Asimetris (`grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 20px; align-items: start;`)** di layar desktop (otomatis 1 kolom di mobile):
     - **Kolom Kiri Utama (60% Lebar — Panel Tindakan Prioritas)**:
       - Tempatkan kartu **"⚡ Prioritas Utama: Butuh Keputusan Anda"** (`_butuh-keputusan.blade.php`) **paling atas di kolom kiri** (sehingga langsung terlihat begitu login tanpa perlu scroll!). Beri header kartu beraksen kontras lembut dan tata setiap item proyek yang butuh ACC/PIC seperti **Action Ticket Row** (Kiri: Judul Proyek + Client + Tanggal Shooting + Badge; Kanan: Tombol `.btn-brand` `[Tetapkan PIC / Tinjau]`).
       - Tepat di bawahnya (masih di kolom kiri), tempatkan kartu **"🔔 Staf Perlu Diingatkan"** (`_diingatkan.blade.php`) dengan desain kartu baris yang rapi dan tombol sekunder `.btn-tonal` `[🔔 Ingatkan]`.
     - **Kolom Kanan Pendamping (40% Lebar — Panel Kalender & Jadwal Sticky)**:
       - Tempatkan widget **Jadwal / Agenda Shooting** di kolom kanan dengan `position: sticky; top: 84px;` pada desktop, sehingga Super Admin bisa mengambil keputusan proyek di kiri sambil melirik jadwal shooting di kanan secara berdampingan!

### B. Rombak Layout 2: Halaman Lineup & Kartu Kandidat Admin — "Pipeline Stage Strip + Card V2 + Slide-Over Drawer"

* **File Target**:
  - `resources/views/admin/projects/applicants.blade.php` (`/admin/projects/{id}/applicants`)
  - `resources/views/partials/extras-card.blade.php`
  - `resources/views/admin/projects/show.blade.php` (`/admin/projects/{id}`)
  - `resources/views/partials/theme-style.blade.php`
* **Masalah Layout Saat Ini**:
  - File `partials/extras-card.blade.php` sebelumnya hanya berubah 3 baris, sehingga tampilan grid 4 kolom kartu pendaftar masih identik dengan tampilan lama.
  - Mode Daftar (`?tampil=daftar`) hanya berupa baris polos berisi avatar, username, badge status, dan bintang favorit tanpa informasi fisik ringkas maupun tombol aksi `[Proses Kandidat]`.
  - Saat Admin mengklik kandidat, yang terbuka adalah `<dialog class="xmodal">` pop-up kecil di tengah layar yang sempit, padahal di dalamnya ada banyak informasi dan form keputusan.
* **Arsitektur Layout Baru yang Wajib Dibangun**:
  1. **Visual Pipeline Stage Strip di Atas Lineup (`applicants.blade.php`)**:
     - Ganti deretan chip filter biasa menjadi **6 Kotak Tahapan Pipeline Interaktif (`.lineup-stage-strip`)** yang tersusun dalam grid horizontal (`grid-template-columns: repeat(6, minmax(130px, 1fr)); gap: 10px;`):
       1. `Semua Pendaftar`
       2. `Perlu Direview` (aksen kuning/amber jika $>0$)
       3. `Sedang Nego Fee` (aksen biru/amber)
       4. `Siap ke Client` (aksen hijau muda)
       5. `Lolos / Kontrak` (aksen hijau solid)
       6. `Ditolak / Batal` (aksen abu-abu/merah)
     - Setiap kotak menampilkan **Angka Metrik Besar (`20px bold`)** di atas dan **Label Tahap** di bawahnya, serta memberikan state aktif (`.is-active`) yang jelas saat dipilih.
  2. **Rombak Total Desain Kartu Pendaftar (`resources/views/partials/extras-card.blade.php`)**:
     - Ubah struktur visual kartu pendaftar (`.ecard-v2`) agar tampak jauh lebih terstruktur dan informatif:
       - **Top Status Bar**: Tambahkan pita tipis (`height: 4px`) di bagian paling atas kartu yang warnanya mengikuti status kandidat (Amber untuk *Diajukan/Direview/Nego*, Hijau untuk *Deal/Lolos*, Merah/Abu untuk *Ditolak*).
       - **Area Foto & Floating Badges**: Pertahankan foto profil/inisial, badge status di kiri atas, tombol bintang favorit, dan lingkaran persentase *match* (`33%/67%/100%`). Jika kandidat sudah memiliki Grade (`Grade A/B/C`), tampilkan sebagai *pill badge* tegas di pojok kanan atas foto.
       - **Blok Spesifikasi Fisik 3-Kotak (`.ecard-specs-grid`)**: Alih-alih menampilkan umur, tinggi, dan gender hanya sebagai teks sebaris `20 th · 157 cm · Perempuan`, bungkus ke dalam **Grid 3 Kotak Mikro Berlatar Tonal (`background: var(--surface-2); border-radius: 6px; padding: 6px; text-align: center;`)**:
         - Kotak 1: Label kecil `USIA` $\rightarrow$ Nilai tebal `20 th`
         - Kotak 2: Label kecil `TINGGI` $\rightarrow$ Nilai tebal `157 cm`
         - Kotak 3: Label kecil `GENDER` $\rightarrow$ Nilai tebal `P / L`
       - **Blok Peran & Pengalaman**: Tampilkan peran yang dilamar dalam kotak *badge* berikon (`🎭 Peran: Mahasiswa kampus`), diikuti deretan tag `#...` dan jumlah proyek selesai.
       - **Footer Kartu Terpadu**: Susun footer kartu dengan 1 tombol aksi utama yang jelas untuk membuka panel kandidat (`[⚡ Proses / Detail]` atau `[Lihat Profil]`) didampingi tombol ikon ringkas WhatsApp (`[💬 WA]`). *(Pastikan semua class/selector yang dibutuhkan `CpUiTest` seperti `.xaksi` tetap dipertahankan!)*
  3. **Rombak Mode Daftar (`?tampil=daftar`) di `applicants.blade.php`**:
     - Pada tampilan daftar (`$tampil === 'daftar'`), ubah setiap baris kandidat dari baris kosong menjadi **Structured Action Row 5-Kolom** yang rapi:
       - **Kolom 1 (Identitas)**: Avatar + Nama Lengkap + `@username` + Badge Grade.
       - **Kolom 2 (Peran & Kecocokan)**: Nama peran yang dilamar + pill persentase Match (`100% Match`).
       - **Kolom 3 (Ciri Fisik Ringkas)**: Pill tonal `20 th` · `157 cm` · `Perempuan` + 3 tag utama.
       - **Kolom 4 (Status & Honor)**: Badge Status pendaftaran + info singkat nominal nego/deal jika ada.
       - **Kolom 5 (Tombol Aksi)**: Tombol **`[⚡ Proses / Detail]`** (pemicu `<dialog id="detail-{{ $app->id }}">`), tombol ikon **`[💬 WA]`**, dan tombol bintang favorit.
  4. **Ubah `<dialog class="xmodal" id="detail-{{ $app->id }}">` Menjadi Slide-Over Right Drawer / Split-View Studio**:
     - Tetap gunakan elemen `<dialog class="xmodal xdrawer-right" id="detail-{{ $app->id }}">` agar seluruh fungsi JS `.showModal()` dan pengujian `CpUiTest` berjalan 100% tanpa error.
     - Namun di CSS (`theme-style.blade.php`), ubah `.xmodal.xdrawer-right` agar tampil sebagai **Slide-Over Panel Samping Kanan Tinggi Penuh** (`margin: 0 0 0 auto; height: 100dvh; max-height: 100dvh; width: min(680px, 96vw); border-radius: 16px 0 0 16px; display: flex; flex-direction: column;`):
       - **Sticky Drawer Header**: Foto avatar kandidat, Nama Lengkap, `@username`, Badge Status Saat Ini, dan tombol tutup `[✕]` di kanan atas.
       - **Scrollable Drawer Body dengan 2 Zona Tegas**:
         - **Zona 1 (Kotak Keputusan Utama Tahap Ini — `.decision-console-box`)**: Kotak berbingkai hijau/amber tegas di paling atas drawer yang menampilkan posisi tahapan kandidat saat ini dan tombol aksi utama (Terima / Nego Fee / Ajukan ke Client / Tolak).
         - **Zona 2 (Profil Fisik, Galeri, & Penilaian Internal Admin)**: Menampilkan kartu atribut fisik, pencocokan tag proyek vs tag kandidat, tombol *Set Grade (A/B/C)*, dan riwayat lamaran.
  5. **Tambahkan Visual Funnel Bar di `admin/projects/show.blade.php`**:
     - Di halaman detail proyek (`/admin/projects/{id}`), tambahkan **Project Quota & Pipeline Summary Strip** tepat di atas deretan tab (`Info`, `Pendaftar`, `Keuangan`, `Lampiran`, `Riwayat`) yang menampilkan perbandingan visual antara **Total Kuota Dicari**, **Total Pendaftar Masuk**, **Sedang Diproses/Nego**, dan **Sudah Lolos/Deal**, lengkap dengan progress bar visual.

### C. Rombak Layout 3: Portal Extras — "Boarding-Pass Hero Card + 2-Column Job Board + Split Application View"

* **File Target**:
  - `resources/views/extras/dashboard.blade.php` (`/extras/dashboard`)
  - `resources/views/partials/application-progress.blade.php`
  - `resources/views/extras/projects/index.blade.php` (`/extras/lowongan`)
  - `resources/views/extras/projects/show.blade.php` (`/extras/lowongan/{id}`)
  - `resources/views/partials/theme-style.blade.php`
* **Masalah Layout Saat Ini**:
  - Halaman `/extras/dashboard` masih berupa daftar kartu satu kolom standar dari atas ke bawah.
  - Halaman `/extras/lowongan` masih menampilkan kartu-kartu besar satu kolom yang memakan banyak ruang vertikal.
  - Halaman detail lowongan (`/extras/lowongan/{id}`) menaruh pilihan peran dan tombol daftar di bagian paling bawah setelah seluruh deskripsi.
* **Arsitektur Layout Baru yang Wajib Dibangun**:
  1. **Desain "Boarding-Pass / Tiket Shooting" di `/extras/dashboard` (`extras/dashboard.blade.php`)**:
     - Ubah kartu jadwal shooting terdekat dan kartu **"Perlu Tindakan Segera"** menjadi komponen **`.boarding-pass-card`**:
       - Memiliki header pita gelap/hijau JBTB (`#0B1A12` / `#15803D`) dengan label `🎬 JADWAL SHOOTING AKTIF` atau `⚡ TINDAKAN DIPERLUKAN SEGERA`.
       - Bagian badan kartu dibagi menjadi **Grid Info Tiket** (Nama Proyek, Peran, Tanggal & Jam Kumpul, Lokasi Venue) yang dipisahkan oleh garis putus-putus horizontal/vertikal ala tiket (*perforated ticket divider* `border-top: 2px dashed var(--border)`).
       - Bagian kaki tiket menampilkan **1 Tombol Aksi Utama Besar (`.btn-brand`)** (misalnya `[📸 Absen Selfie Sekarang]`, `[✍️ Tanda Tangani Kontrak]`, `[💬 Jawab Nego Honor]`, atau `[📍 Buka Google Maps]`).
  2. **Rombak Katalog Lowongan `/extras/lowongan` Menjadi 2-Column Magazine Job Grid (`extras/projects/index.blade.php`)**:
     - Di layar tablet/desktop (`min-width: 768px`), ubah daftar kartu lowongan menjadi **Grid 2 Kolom (`.job-board-grid` — `display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px;`)**.
     - Di dalam setiap kartu lowongan, gunakan **Metadata Box 2x2** berlatar tonal lembut untuk menampilkan: `📅 Tanggal Shooting`, `📍 Lokasi`, `⏳ Deadline Daftar`, dan `👥 Total Kuota Peran`, diikuti daftar *pill* peran yang dibuka dan footer kartu berisi tombol `.btn-brand` `[Lihat Detail & Lamar →]`.
  3. **Rombak Detail Lowongan `/extras/lowongan/{id}` Menjadi Split-Screen 2 Kolom di Desktop + Sticky Bottom Sheet di Mobile (`extras/projects/show.blade.php`)**:
     - Bungkus konten halaman `extras/projects/show.blade.php` ke dalam **Grid 2 Kolom (`.project-apply-split` — `grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.9fr); gap: 24px; align-items: start;`)** di layar desktop:
       - **Kolom Kiri**: Header proyek, deskripsi lengkap, jadwal tanggal syuting per hari, dan lampiran/ketentuan.
       - **Kolom Kanan (Sticky di Desktop — `position: sticky; top: 84px;`)**: Kartu **"Pilih Peran & Kirim Lamaran"** langsung tampil di sebelah kanan atas tanpa perlu *scroll* ke bawah! Di layar HP (`< 768px`), kolom kanan berada di bawah namun dipandu oleh `.sticky-mobile-cta` yang menggulung layar langsung ke kotak pilihan peran.

### D. Rombak Layout 4: Portal Client & Halaman Monitoring Super Admin

* **File Target**:
  - `resources/views/client/dashboard.blade.php` (`/client/dashboard`)
  - `resources/views/client/reviews/show.blade.php` (`/client/reviews/{id}`)
  - `resources/views/invoices/index-client.blade.php` (`/invoice`)
  - `resources/views/super-admin/monitoring/admin.blade.php`, `korlap.blade.php`, `_gaya.blade.php`
* **Arsitektur Layout Baru yang Wajib Dibangun**:
  1. **Sticky Quota Progress Header & Lookbook Card di `client/reviews/show.blade.php`**:
     - Tambahkan **Quota Fulfillment Banner** di bagian atas halaman pemilihan kandidat Client yang menampilkan bar progres keterisian kandidat terpilih dibandingkan kuota peran yang dibutuhkan.
     - Berikan bingkai visual kontras (border hijau `2px solid #15803D` + badge sudut `"✓ Kandidat Terpilih"`) pada kartu talent yang sudah disetujui oleh Client agar langsung terlihat bedanya dengan kandidat yang masih menunggu keputusan.
  2. **Visual Pipeline Tracker pada Proyek Berjalan di `client/dashboard.blade.php`**:
     - Pada daftar proyek berjalan di Dashboard Client, tambahkan **4-Step Mini Progress Bar** (*1. Brief Diajukan $\rightarrow$ 2. Seleksi & Pemilihan $\rightarrow$ 3. Shooting $\rightarrow$ 4. Selesai & Invoice*) di setiap kartu proyek sehingga Client langsung melihat posisi proyeknya secara visual.

## 4. Prosedur Eksekusi & Verifikasi Wajib untuk Antigravity

1. **Baca Test Terlebih Dahulu**: Cek isi `tests/Feature/CpUiTest.php` dan `tests/Feature/CallinganCardTest.php` agar semua class/id/route yang di-assert oleh test tetap ada di dalam struktur HTML baru.
2. **Tambahkan CSS Layout Makro di `resources/views/partials/theme-style.blade.php`**: Definisikan dengan rapi `.sa-command-grid`, `.lineup-stage-strip`, `.ecard-v2`, `.ecard-specs-grid`, `.xdrawer-right`, `.decision-console-box`, `.boarding-pass-card`, `.job-board-grid`, dan `.project-apply-split` lengkap dengan `@media (max-width: 768px)` responsif dan dukungan Dark Mode (`[data-theme="dark"]`).
3. **Terapkan Rombak Layout di Seluruh Blade View Target** (bukan sekadar mengganti teks tombol, melainkan benar-benar menyusun ulang struktur Grid, Kartu, Drawer, dan Split-View sesuai Spesifikasi Bagian 3A–3D).
4. **Jalankan Verifikasi Penuh**:
   - `php artisan view:clear`
   - `php artisan test` (Pastikan seluruh **962 tests** tetap **PASS 100%**).
