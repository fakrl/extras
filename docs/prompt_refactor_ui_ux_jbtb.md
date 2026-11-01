# Master Prompt & Spesifikasi Refactoring UI/UX — SIM Casting JBTB (`fakrl/extras`)

> **Cara Pakai Dokumen Ini di Antigravity:**
> Kamu bisa langsung *copy-paste* seluruh isi dokumen ini sebagai prompt ke Antigravity, atau simpan file ini di dalam folder `docs/PROMPT-REFACTOR-UI-UX-JBTB.md` pada repositori proyekmu lalu perintahkan Antigravity:
> *"Baca dan eksekusi seluruh instruksi refactoring UI/UX di `docs/PROMPT-REFACTOR-UI-UX-JBTB.md` secara bertahap per modul, lalu pastikan semua automated test tetap lulus."*

## 1. Konteks Sistem & Aturan Emas (Hard Constraints)

Kamu sedang mengerjakan refactoring antarmuka pengguna (UI/UX) pada proyek **SIM Casting JBTB** (`fakrl/extras`), sebuah Sistem Informasi Manajemen Operasional Casting Agency berbasis **Laravel 13 (PHP 8.3+)**, **MySQL 8**, dan **Blade Templating dengan Custom CSS Design System** (`resources/views/partials/theme-style.blade.php` & `docs/UI-GUIDELINES.md`).

Sebelum mengubah file apa pun, patuhi **Aturan Emas (Hard Constraints)** berikut tanpa pengecualian:

1. **Jangan Merusak Logika Backend & Automated Test Suite (`php artisan test`)**:
   - **DILARANG** mengubah nama route (`route(...)`), struktur URL di `routes/web.php`, HTTP method form (`POST`, `PUT`, `PATCH`, `DELETE`), atribut `name="..."` pada elemen `<input>`, `<select>`, `<textarea>`, maupun nilai string `enum` status di database dan model.
   - Seluruh perubahan difokuskan murni pada **layer presentasi (Blade Views di `resources/views/**/*.blade.php`, komponen di `resources/views/components/*`, partial di `resources/views/partials/*`, dan variabel/class CSS di `resources/views/partials/theme-style.blade.php`)**.
2. **Pertahankan 5 Role Resmi Sistem**:
   - Sistem dikunci pada 5 role resmi: `super_admin`, `admin`, `korlap`, `client`, dan `extras`. Jangan menambah atau mengubah konstanta role.
3. **Pertahankan Identitas Visual & Standar Aksesibilitas JBTB (`docs/UI-GUIDELINES.md`)**:
   - **Tanpa Framework Eksternal**: Jangan menambahkan Tailwind, Bootstrap, Livewire, atau Alpine.js. Gunakan Custom CSS Design System dan Vanilla JS yang sudah ada.
   - **Palet Warna**: Pertahankan latar krem hangat (`#f5f4ef` / `#EEF2EA`), warna aksen utama hijau JBTB (`#15803D`), header gelap (`#0B1A12`), dukungan penuh **Light Mode & Dark Mode** (`#0B1310`), serta semantik warna status: **Hijau** (`#15803D` / `#22C55E`) untuk selesai/aktif/disetujui, **Kuning/Amber** (`#F59E0B` / `#D97706`) untuk menunggu/proses/nego, dan **Merah** (`#EF4444`) untuk batal/ditolak.
   - **Standar "Semua Umur" & Mobile-First**: Pertahankan tinggi minimal tombol utama (`.btn`) sebesar **44px** (dan `.btn-sm` **32px** khusus aksi sekunder di desktop), ukuran font input minimal **16px** (`--fs-md`) agar tidak memicu *auto-zoom* di iOS, serta gunakan Bahasa Indonesia sehari-hari yang jelas dan bebas jargon teknis.

## 2. Tiga Prinsip Arsitektur UX Baru (Global Refactoring Pattern)

Tujuan utama refactoring ini adalah menghilangkan kebingungan pengguna awam (*"Habis ini saya harus ngapain?"*, *"Bola sedang di tangan siapa?"*, *"Tombol ini mengarah ke mana?"*, dan *"Kenapa ada dua tombol dengan fungsi mirip?"*). Terapkan 3 pola arsitektur berikut di seluruh halaman:

| Pola UX Baru | Masalah Saat Ini | Solusi & Standar Implementasi |
| :--- | :--- | :--- |
| **1. Next-Action Callout (Kotak Pemandu Langkah Selanjutnya)** | Stepper (`application-progress.blade.php` & `stepper-proyek.blade.php`) dan badge status hanya bersifat pasif. Saat status menunggu (`diajukan`, `direview_admin`, `diajukan_ke_client`), pengguna awam tidak tahu apakah mereka kurang mengklik sesuatu atau memang sedang menunggu pihak lain. | Di setiap kartu lamaran dan halaman detail proyek, tampilkan **Kotak Pemandu Aksi** yang menjawab 3 hal secara eksplisit: **(a) Status saat ini**, **(b) Giliran siapa yang memproses (Kamu / Admin / Client)**, dan **(c) 1 Tombol Aksi Utama (`.btn-brand`)** langsung di dalam kotak tersebut jika giliran aksi berada di tangan pengguna. |
| **2. Aturan "1 Primary Button per Card/Row"** | Banyak baris tabel dan kartu menampilkan beberapa tombol dengan bobot visual serupa (atau menumpuk banyak form inline dalam satu sel tabel), sehingga mata pengguna bingung menentukan prioritas klik. | Dalam satu kartu atau satu baris item, hanya boleh ada **maksimal 1 tombol berwarna hijau solid (`.btn-brand`)** yang mewakili langkah utama berikutnya sesuai status saat itu. Seluruh aksi sekunder (lihat profil, hubungi WA, set grade, edit tag, tolak/batal) wajib menggunakan gaya sekunder (`.btn-tonal` / `.btn-outline`) atau dimasukkan ke dalam modal/drawer detail. |
| **3. Penyatuan Konteks Proyek (Anti-Disorientasi Rute)** | Pengguna dilempar ke banyak rute berbeda (`projects.show`, `negotiations.show`, `lengkapi-ktp`, `contracts.show`, `payments.show`, `applicants`) untuk menyelesaikan satu alur proyek yang sama sehingga kehilangan konteks. | Setiap halaman turunan (ruang negosiasi, lengkapi KTP untuk kontrak, tanda tangan kontrak, lineup pendaftar, dan invoice) wajib menampilkan **Header Konteks Proyek + Breadcrumb Kembali yang Konsisten** di bagian atas halaman (memuat Nama Proyek, Kode Proyek, Peran/Kelas, dan posisi tahap saat ini). |

## 3. Daftar Perombakan Spesifik Per Role & File Blade

Lakukan perubahan terstruktur pada setiap file berikut sesuai perbandingan kondisi saat ini dan target perubahannya:

### A. Role `extras` (Talent / Pemeran Figuran)

#### 1. Dashboard Extras & Progress Lamaran
* **File Target**:
  - `resources/views/extras/dashboard.blade.php`
  - `resources/views/partials/application-progress.blade.php`
* **Kondisi & Masalah Visual Saat Ini**:
  - Bagian **"Perlu Tindakan"** di `extras/dashboard.blade.php` mencampurkan item yang membuka `<dialog>` modal, partial `@include('partials.undangan-aksi')`, dan link pindah halaman tanpa pembeda yang seragam.
  - Pada kartu **"Pendaftaran Saya"**, tombol aksi melempar pengguna ke 5 halaman berbeda (`extras.projects.show`, `extras.negotiations.show`, `extras.kontrak.lengkapi-ktp`, `contracts.show`, `payments.show`) tergantung statusnya tanpa memberi gambaran alur utuh.
  - Pada `partials/application-progress.blade.php`, ketika status sedang pasif (`diajukan`, `direview_admin`, `deal`, `diajukan_ke_client`), pengguna hanya melihat bar/badge status tanpa kalimat instruksi mengenai apa yang sedang terjadi. Ketika status ditolak/berhenti (`isStopped`), hanya muncul `alasan_tolak` tanpa arah langkah selanjutnya.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Di `resources/views/partials/application-progress.blade.php`, tambahkan blok **Next-Action Callout** tepat di bawah bar linimasa tahapan (`Diajukan -> Nego fee -> Dipilih -> Kontrak -> Selesai`) dengan teks panduan manusiawi untuk setiap status:
     - Status `diundang`: *"Kamu mendapat undangan langsung dari Admin untuk peran ini. Silakan terima atau tolak undangan."* $\rightarrow$ Tampilkan tombol `.btn-brand` **`[Jawab Undangan Casting]`**.
     - Status `diajukan` / `direview_admin`: *"Lamaranmu sudah masuk dan sedang diperiksa oleh Admin JBTB. Kamu belum perlu melakukan apa-apa saat ini."* (Indikator info tenang, tanpa tombol membingungkan).
     - Status `nego_fee`: *"Admin mengajukan penawaran honor untukmu. Silakan tinjau nominalnya, setujui, atau ajukan penawaran balik."* $\rightarrow$ Tampilkan tombol `.btn-brand` **`[Buka Ruang Nego Honor]`**.
     - Status `deal` / `diajukan_ke_client`: *"Nominal honor sudah disepakati! Profilmu sedang diajukan ke pihak PH/Client untuk pemilihan akhir. Mohon tunggu pengumuman hasilnya."*
     - Status `lolos` (jika KTP belum lengkap): *"Selamat, kamu terpilih! Langkah 1 dari 2 sebelum tanda tangan kontrak: lengkapi data KTP-mu terlebih dahulu."* $\rightarrow$ Tampilkan tombol `.btn-brand` **`[Langkah 1/2: Lengkapi KTP]`**.
     - Status `lolos` (jika KTP sudah lengkap): *"Selamat, kamu terpilih! Langkah terakhir sebelum syuting: baca dan tanda tangani kontrak kerja digitalmu."* $\rightarrow$ Tampilkan tombol `.btn-brand` **`[Tanda Tangani Kontrak Kerja]`**.
     - Status `kontrak_ditandatangani`: *"Kontrak sudah aktif. Jangan lupa hadir di lokasi sesuai jadwal Callsheet dan lakukan Absen Selfie saat tiba di lokasi syuting."* $\rightarrow$ Tampilkan tombol `.btn-brand` **`[Absen Selfie Lokasi]`** (pada hari-H syuting) dan tombol sekunder **`[📍 Buka Maps / Detail Jadwal]`**.
     - Status `selesai_produksi` (dengan payout `ditransfer`): *"Honor syutingmu sudah ditransfer oleh Admin. Silakan cek rekeningmu dan konfirmasi penerimaan."* $\rightarrow$ Tampilkan tombol `.btn-brand` **`[Cek & Konfirmasi Honor]`**.
     - Status `ditolak` / `dibatalkan`: Tampilkan kotak alasan penolakan (`alasan_tolak`) dengan sopan, disertai tombol sekunder `.btn-tonal` **`[Cari Lowongan Casting Lainnya →]`** yang mengarah ke `extras.projects.index`.
  2. Di `resources/views/extras/dashboard.blade.php`, seragamkan tata letak kartu di bagian **"Perlu Tindakan"** agar setiap item memiliki struktur kiri-ke-kanan (atau atas-ke-bawah di layar HP) yang konsisten: **[Label Jenis Tugas + Nama Proyek]** $\rightarrow$ **[Keterangan Singkat Apa yang Harus Dilakukan]** $\rightarrow$ **[1 Tombol Utama `.btn-brand`]**.

#### 2. Detail Lowongan Casting Extras
* **File Target**: `resources/views/extras/projects/show.blade.php`
* **Kondisi & Masalah Visual Saat Ini**:
  - Pilihan radio peran dan tombol `"Daftar ke Proyek Ini"` terletak paling bawah setelah deskripsi panjang dan rincian jadwal, sehingga pengguna di HP sering tidak sadar di mana tempat memilih peran.
  - Apabila jadwal proyek bentrok dengan jadwal syuting lain milik Extras, peringatan hanya muncul lewat pop-up `<dialog id="dialog-bentrok">` setelah tombol diklik.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Tambahkan **Bar Ringkasan / Sticky CTA** di bagian atas atau bawah layar mobile yang menampilkan kisaran honor, deadline, dan tombol cepat **`[Pilih Peran & Daftar ↓]`** yang menggulung layar (*smooth scroll*) langsung ke kartu pemilihan peran.
  2. Beri bingkai visual yang jelas pada kartu **"Pilih Peran yang Ingin Dilamar"** agar tampak interaktif sebagai kartu pilihan (*selectable role cards*).
  3. Jika variabel bentrok jadwal bernilai `true`, tampilkan **Inline Warning Alert** berwarna kuning/amber langsung di atas tombol daftar (menjelaskan tanggal dan proyek mana yang beririsan) sebelum pengguna menekan tombol submit, sambil tetap mempertahankan `<dialog id="dialog-bentrok">` agar alur konfirmasi yang diuji test case tetap berjalan normal.

#### 3. Halaman Profil Extras
* **File Target**:
  - `resources/views/extras/profile-show.blade.php`
  - `resources/views/extras/profile-edit.blade.php`
* **Kondisi & Masalah Visual Saat Ini**:
  - Di samping header nama akun terdapat tombol **`[Lihat seperti Client]`** (`/extras/profil/lengkapi`). Bagi pengguna awam dari kalangan talent/figuran, kalimat *"Lihat seperti Client"* menimbulkan salah paham seolah-olah tombol tersebut mengubah akun mereka menjadi akun Client/PH.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Ubah teks tombol `[Lihat seperti Client]` menjadi **`[👁 Pratinjau Portofolio Publik]`** dengan keterangan/title *"Lihat bagaimana profil dan fotomu tampil saat dinilai oleh Client/PH"*.

#### 4. Halaman Katalog Lowongan Extras
* **File Target**: `resources/views/extras/projects/index.blade.php` (`/extras/lowongan`)
* **Kondisi & Masalah Visual Saat Ini**:
  - Ukuran tinggi kartu lowongan (*card height*) cukup besar sehingga hanya sedikit lowongan yang dapat dilihat dalam satu layar tanpa melakukan *scroll* panjang.
  - Kotak pencarian belum didampingi filter kategori/kriteria cepat yang mudah diklik dengan satu jari.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Rapikan proporsi *padding* dalam kartu lowongan dan susun metadata proyek (tanggal syuting, lokasi, deadline, kuota peran) dalam baris *grid/flex* yang padat namun mudah dipindai (*scannable*).
  2. Tambahkan deretan **Quick Filter Chips/Pills** di bawah kotak pencarian (misalnya: `Semua Lowongan`, `Sesuai Profilku`, `Butuh Dadakan`) agar talent bisa menyaring lowongan dengan sekali sentuh.

### B. Role `admin` & `korlap` (Operasional Proyek, Lineup, & Lapangan)

#### 1. Daftar Proyek Admin
* **File Target**: `resources/views/admin/projects/index.blade.php` (`/admin/projects`)
* **Kondisi & Masalah Visual Saat Ini**:
  - Di bagian atas halaman sudah ada tab filter `Selesai (7)`, namun di bawah tabel masih muncul banner informasi dengan tombol `Tampilkan selesai` dan tautan `Buka tab Selesai ->`. Navigasi ini tumpang tindih dan redundan.
  - Pada kolom paling kanan tabel proyek, tombol `[Lineup]` dan `[Salin link]` ditumpuk vertikal dengan bobot visual yang hampir sama.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Hapus atau ringkas banner redundan `Tampilkan selesai` / `Buka tab Selesai ->` di bagian bawah jika tab `Selesai` di bagian atas sudah menampilkan jumlah proyek selesai secara jelas.
  2. Pada kolom aksi tabel proyek, jadikan **`[Kelola Lineup]`** atau **`[Buka Proyek]`** sebagai tombol utama yang menonjol, sedangkan **`[🔗 Salin Link]`** dijadikan tombol ikon/sekunder kecil (`.btn-sm` / `.btn-tonal`) agar tidak sejajar dengan aksi utama.

#### 2. Halaman Detail Proyek Admin
* **File Target**: `resources/views/admin/projects/show.blade.php` (`/admin/projects/{id}`)
* **Kondisi & Masalah Visual Saat Ini**:
  - Di pojok kanan atas terdapat dua tombol outline **`[Lineup]`** dan **`[Invoice]`**, sedangkan tepat di bawah banner terdapat sub-tab **`Info`**, **`Pendaftar`**, **`Keuangan`**, **`Lampiran`**, dan **`Riwayat`**. Pengguna awam bingung membedakan fungsi tombol `[Lineup]` di atas vs tab `Pendaftar` di bawah, serta tombol `[Invoice]` di atas vs tab `Keuangan` di bawah.
  - Banner mode lihat-saja (`👁 Lihat saja, proyek ini dipegang Rina Kartika.`) menggunakan latar abu-abu terang yang kurang mencolok sebagai penanda pembatasan akses edit.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Hilangkan ambiguitas antara tombol pojok kanan atas dan sub-tab:
     - Ubah label tombol di kanan atas menjadi aksi spesifik yang jelas tujuannya: **`[👥 Buka Workspace Lineup Penuh →]`** (mengarah ke `admin.projects.applicants`) dan **`[🧾 Lihat Dokumen Invoice]`** (atau pindahkan tombol dokumen Invoice ke dalam header tab `Keuangan`).
     - Pada tab **`Pendaftar`** di `show.blade.php`, tampilkan ringkasan metrik kuota per peran dan daftar pendaftar terbaru dengan banner jelas: *"Menampilkan ringkasan pendaftar. Untuk memfilter tag, mengatur grade, dan memproses seleksi massal, gunakan tombol **[Buka Workspace Lineup Penuh]**."*
  2. Ubah gaya visual banner `👁 Lihat saja, proyek ini dipegang [Nama Admin]` menggunakan aksen info/kuning lembut (border kiri amber/biru tegas) disertai keterangan *"Anda sedang dalam mode baca karena proyek ini ditugaskan kepada [Nama Admin]."*

#### 3. Halaman Lineup Pendaftar (Mode Kartu & Mode Daftar)
* **File Target**:
  - `resources/views/admin/projects/applicants.blade.php` (`/admin/projects/{id}/applicants`)
  - `resources/views/partials/extras-card.blade.php`
* **Kondisi & Masalah Visual Saat Ini**:
  - Pada **Mode Kartu (`[ 🌁 Kartu ]`)**, setiap kartu memuat banyak elemen (foto/inisial, badge status di pojok kiri atas, tombol bintang favorit, circular progress match `33%/67%/100%`, username, nama + grade, peran, fisik, deretan tag `#...`, riwayat proyek) dengan 2 tombol outline di bawah (`[ Lihat Profil ]` dan `[ Hubungi via WA ]`). Sementara itu, kontrol untuk mengubah status kandidat, mengatur grade, mencocokkan tag, dan mengisi alasan penolakan menumpuk padat di tabel/modal tanpa hierarki tombol utama yang jelas.
  - Pada **Mode Daftar (`[ ☰ Daftar ]` / `?tampil=daftar`)**, setiap baris hanya menampilkan avatar, username, peran, badge status, dan ikon bintang favorit di ujung kanan—tanpa tombol eksplisit untuk membuka profil atau memproses status kandidat tersebut.
  - Kartu kandidat yang **sedang menunggu tindakan Admin** (misal status `diajukan` atau `direview_admin` atau `nego_fee` giliran Admin) terlihat serupa secara visual dengan kartu yang sudah `ditolak` atau sedang `diajukan_ke_client`.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. **Tambahkan Filter Chip Status Cepat di Atas Bar Pencarian**: Di bawah tab `Lineup` / `Sudah ke Client`, sediakan chip filter cepat beserta angkanya: `Semua`, `Perlu Direview Admin`, `Sedang Nego Fee`, `Siap Diajukan ke Client`, `Lolos / Kontrak`, dan `Ditolak`.
  2. **Perjelas Hierarki Aksi pada Mode Kartu (`extras-card.blade.php`)**:
     - Berikan aksen border atas atau highlight halus pada kartu yang statusnya membutuhkan tindakan Admin.
     - Di bagian footer kartu, tampilkan **1 Tombol Aksi Utama (`.btn-brand` / `.btn-tonal` tegas)** untuk membuka modal/panel pemrosesan kandidat (contoh: **`[Proses Kandidat]`** atau **`[Lihat & Proses]`**), sedangkan tombol WhatsApp dijadikan tombol ikon sekunder **`[💬 WA]`** di sebelahnya agar footer kartu tidak penuh sesak.
  3. **Lengkapi Tombol Aksi pada Mode Daftar (`?tampil=daftar`)**:
     - Di sisi kanan setiap baris pada Mode Daftar (di samping badge status dan bintang favorit), tambahkan tombol **`[Lihat / Proses]`** (serta ikon cepat WA) supaya pengguna yang memilih tampilan tabel/daftar tidak kehilangan akses ke tindakan kandidat.
  4. **Rapikan Penumpukan Kontrol di Modal/Baris Detail Pendaftar (`applicants.blade.php`)**:
     - Pisahkan area di dalam modal/baris kelola pelamar menjadi 2 zona visual yang bersih:
       - **Zona 1 — Keputusan Tahap Ini (Primary Action Box)**: Hanya menampilkan tombol aksi yang relevan dengan status pelamar saat itu (misal: jika status `diajukan` $\rightarrow$ tombol utama `[Lanjut Nego Fee / Terima]` dan tombol sekunder `[Tolak]`).
       - **Zona 2 — Atribut & Penilaian Internal (Secondary Box)**: Mengelompokkan tombol `.btn-sm` *Set Grade (A/B/C)*, *Tag Matching*, dan catatan internal ke dalam panel yang rapi dan tidak berebut fokus dengan tombol keputusan utama.

### C. Role `client` (Rumah Produksi / Casting Director / PH)

#### 1. Halaman Daftar Tagihan / Invoice Client
* **File Target**: `resources/views/invoices/index-client.blade.php` (`/invoice`)
* **Kondisi & Masalah Visual Saat Ini**:
  - Pada baris proyek yang tertulis *"Belum ada invoice"* dan memiliki badge *"Belum TTD"*, sistem tetap merender tombol berwarna hijau mencolok **`[Lihat Invoice]`**. Saat diklik oleh Client, halaman tujuan belum memiliki invoice yang diterbitkan.
  - Badge status **`Belum TTD`** di halaman tagihan bersifat ambigu karena tidak menjelaskan dokumen apa dan pihak mana yang belum tanda tangan (Client bisa mengira mereka yang harus menandatangani invoice).
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Ubah logika tampilan tombol pada baris proyek di `invoices/index-client.blade.php`:
     - Jika proyek **sudah memiliki invoice yang diterbitkan/lunas**, tampilkan tombol hijau solid `.btn-brand` **`[Lihat Invoice]`**.
     - Jika proyek **belum memiliki invoice** (atau invoice masih berstatus `draft` internal Admin), **jangan tampilkan tombol hijau aktif**. Ganti dengan badge/tombol nonaktif (*disabled* / `.btn-tonal` abu-abu) berlabel **`Invoice Belum Terbit`** accompanied oleh keterangan singkat *"Diterbitkan setelah kontrak & produksi berjalan"*.
  2. Ubah teks badge `Belum TTD` menjadi lebih spesifik dan jelas konteksnya, misalnya **`Kontrak Talent Proses TTD`** atau **`Menunggu Kontrak Selesai`**.

#### 2. Dashboard Client & Halaman Pemilihan Kandidat
* **File Target**:
  - `resources/views/client/dashboard.blade.php` (`/client/dashboard`)
  - `resources/views/client/reviews/index.blade.php` & `resources/views/client/reviews/show.blade.php`
* **Kondisi & Masalah Visual Saat Ini**:
  - Di `/client/dashboard`, terdapat beberapa tombol untuk fungsi pengajuan proyek yang tampil bersamaan (`[Ajukan Proyek Baru]`, `[Ajukan Ulang]`, dan menu sidebar `Ajukan Proyek`).
  - Pada halaman `/client/reviews/show.blade.php`, ketika status proyek atau kandidat berada di tahap *"Menunggu Persetujuan"* atau *"Proses Admin"*, kurang ada penjelasan mengenai langkah selanjutnya yang sedang dikerjakan oleh pihak agency JBTB.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Di `client/dashboard.blade.php`, pertahankan hanya satu tombol utama hijau solid **`.btn-brand` `[+ Ajukan Proyek Baru]`** di bagian header. Untuk fitur pengajuan ulang pada riwayat proyek lama, ubah gaya tombolnya menjadi sekunder (`.btn-outline` / `.btn-tonal`) dengan label yang lebih akurat: **`[Gunakan Sebagai Template]`** atau **`[Ajukan Proyek Serupa]`**.
  2. Di `client/reviews/show.blade.php`, tambahkan banner informasi tahapan di bagian atas daftar kandidat yang menjelaskan posisi proses saat ini (contoh: *"Pilih kandidat yang sesuai dengan kriteria produksi Anda. Kandidat yang Anda setujui akan langsung diproses tanda tangan kontraknya oleh tim Admin JBTB."*).

### D. Role `super_admin` (Owner / Pengelola Utama)

#### 1. Dashboard Super Admin
* **File Target**:
  - `resources/views/super-admin/index.blade.php` (`/super-admin/dashboard`)
  - `resources/views/partials/sidebar-super_admin.blade.php`
  - `resources/views/partials/sidebar-admin.blade.php`
* **Kondisi & Masalah Visual Saat Ini**:
  - Pada 2 kolom bawah di `/super-admin/dashboard` (*"Butuh keputusan Anda"* di kiri vs *"Staf perlu diingatkan"* di kanan), gaya visual tombol aksinya terbalik secara hierarki: tombol **`[Tetapkan PIC]`** memakai *outline* putih dan **`[Buka]`** memakai *outline* abu-abu, sedangkan tombol **`[Ingatkan]`** memakai warna hijau solid dengan ikon lonceng. Akibatnya fokus mata tertarik ke `[Ingatkan]`, padahal persetujuan proyek dan penetapan PIC adalah keputusan paling krusial bagi Super Admin.
  - Tombol navigasi tanggal `<` dan `>` pada widget Jadwal hanya berupa lingkaran tipis dengan kontras yang terlalu rendah terhadap latar belakang.
  - Indikator titik merah (`sb-dot`) pada menu `Proyek` dan `Monitoring` di sidebar tidak menampilkan angka jumlah item yang membutuhkan keputusan.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. **Perbaiki Hierarki Tombol di `/super-admin/dashboard`**:
     - Pada kolom **"Butuh keputusan Anda"**, jadikan tombol aksi keputusan seperti **`[Tetapkan PIC]`** dan **`[Tinjau & ACC]`** sebagai tombol utama **Hijau Solid (`.btn-brand`)**.
     - Pada kolom **"Staf perlu diingatkan"**, ubah tombol **`[🔔 Ingatkan]`** menjadi gaya sekunder (`.btn-tonal` atau `.btn-outline`) agar tidak mengalahkan tombol keputusan utama.
  2. **Perjelas Navigasi Kalender/Jadwal**: Berikan border dan warna latar (`.btn-tonal`) yang lebih tegas pada tombol panah `<` dan `>` di widget Jadwal agar mudah terlihat dan diklik.
  3. **Tingkatkan Indikator Badge di Sidebar (`sidebar-super_admin.blade.php` & `sidebar-admin.blade.php`)**: Jika variabel jumlah tugas/keputusan tersedia (atau bernilai $> 0$), tampilkan sebagai **badge kapsul kecil berisi angka** (atau tambahkan tooltip jelas) alih-alih hanya titik merah kecil tanpa keterangan.

#### 2. Halaman Manajemen Akun (Godmode Users)
* **File Target**: `resources/views/super-admin/godmode-users.blade.php` (`/super-admin/akun`)
* **Kondisi & Masalah Visual Saat Ini**:
  - Tombol pembuatan akun di pojok kanan atas hanya dibagi menjadi **`[+ Client]`** dan **`[+ Staf]`**, sehingga pengguna awam bingung apakah role *Korlap* dibuat lewat tombol `+ Staf` dan bagaimana cara kerja pendaftaran akun *Extras*.
  - Checkbox **`Pilih semua di halaman ini`** tidak memperlihatkan informasi atau tombol aksi massal (*bulk action*) apa yang tersedia sebelum checkbox dicentang.
  - Aksi manajemen tiap pengguna disembunyikan di balik tombol titik tiga (`...`) kecil di ujung kanan baris tanpa label teks.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Perjelas label tombol pembuatan akun di kanan atas menjadi **`[+ Tambah Staf (Admin/Korlap)]`** (solid hijau) dan **`[+ Tambah Client]`** (outline/tonal), serta tambahkan catatan info singkat bahwa akun *Extras* mendaftar mandiri melalui portal registrasi publik.
  2. Pada area *bulk action* di sebelah checkbox `Pilih semua di halaman ini`, tampilkan bar/label kontekstual (misalnya teks indikator *"0 dipilih — Centang akun untuk mengubah status massal"* yang berubah menjadi tombol aksi aktif saat ada item yang dicentang).
  3. Ubah tombol ikon titik tiga (`...`) pada tiap baris pengguna menjadi tombol berlabel **`[Kelola ▾]`** agar pengguna langsung tahu di mana tempat mengedit atau menonaktifkan akun.

#### 3. Halaman Monitoring Admin & Korlap
* **File Target**:
  - Halaman Monitoring Admin (`/super-admin/monitoring/admin`)
  - Halaman Monitoring Korlap (`/super-admin/monitoring/korlap`)
* **Kondisi & Masalah Visual Saat Ini**:
  - Di **Monitoring Admin**: Visualisasi garis *pipeline* tahapan kandidat (*Ajuan $\rightarrow$ Nego Fee $\rightarrow$ Dipilih Client $\rightarrow$ Kontrak $\rightarrow$ Pembayaran*) memiliki label merah *"perlu tindakan"* yang berdesakan di atas garis tipis, serta memunculkan *horizontal scrollbar* tebal tepat di bawah angka pada layar laptop standar.
  - Di **Monitoring Korlap**: Terdapat petunjuk teks *"Klik baris untuk masuk mode Korlap..."* pada tabel daftar Korlap yang berbenturan secara konsep dengan tombol **`[Ambil alih sebagai Korlap]`** di banner atas. Selain itu, kartu pada modul **"Menunggu validasi"** di kolom kanan menampilkan placeholder avatar abu-abu tanpa tombol aksi cepat untuk membuka halaman validasi tersebut.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Di **Monitoring Admin**: Ubah layout CSS container *pipeline* tahapan menggunakan `display: grid` (`grid-template-columns: repeat(auto-fit, minmax(140px, 1fr))`) atau kartu tahapan yang responsif sehingga **tidak memunculkan horizontal scrollbar** pada layar desktop/laptop, serta berikan jarak vertikal (*gap*) yang proporsional antara judul tahapan, angka utama, dan badge merah *"X perlu tindakan"*.
  2. Di **Monitoring Korlap**:
     - Selaraskan instruksi pengambilan alih role: tambahkan tombol sekunder kecil **`[Masuk Mode Korlap Ini →]`** di sisi kanan tiap baris nama Korlap pada tabel (sehingga pengguna tidak perlu menebak apakah baris tabel bisa diklik), dan perjelas bahwa tombol di banner atas adalah untuk masuk ke tampilan Korlap secara umum.
     - Pada kartu **"Menunggu validasi"**, tambahkan tombol aksi eksplisit **`[Buka & Validasi Absensi →]`** di sisi kanan tiap item agar pengguna bisa langsung menindaklanjuti antrean validasi tersebut.

#### 4. Halaman Log Aktivitas (Audit Trail)
* **File Target**: `resources/views/super-admin/activity-logs.blade.php` (`/super-admin/activity-logs`)
* **Kondisi & Masalah Visual Saat Ini**:
  - Baris-baris riwayat aktivitas memiliki kepadatan teks tinggi (*high text density*) dengan garis pemisah antar baris yang terlalu tipis sehingga melelahkan mata saat memindai riwayat audit.
* **Instruksi Perubahan (Ubah Menjadi)**:
  1. Berikan jarak ruang (*padding* vertikal `12px–16px`), perjelas garis batas antar item (`border-bottom`), dan tambahkan warna latar berselang-seling halus (*subtle zebra striping* atau pengelompokan kartu per tanggal) agar antara waktu kejadian, nama pelaku, badge role, deskripsi aksi, dan akordion `> Lihat Parameter` dapat dipindai mata dengan cepat.

## 4. Urutan Eksekusi & Verifikasi Wajib

Saat mengeksekusi prompt ini, lakukan secara berurutan:

1. **Tahap 1 — Komponen Bersama & Navigasi**: Perbarui `partials/application-progress.blade.php`, `partials/sidebar-super_admin.blade.php`, `partials/sidebar-admin.blade.php`, dan helper CSS di `partials/theme-style.blade.php`.
2. **Tahap 2 — Portal Extras**: Perbarui `extras/dashboard.blade.php`, `extras/projects/show.blade.php`, `extras/projects/index.blade.php`, `extras/profile-show.blade.php`, dan `extras/profile-edit.blade.php`.
3. **Tahap 3 — Portal Admin & Korlap**: Perbarui `admin/projects/index.blade.php`, `admin/projects/show.blade.php`, `admin/projects/applicants.blade.php`, dan `partials/extras-card.blade.php`.
4. **Tahap 4 — Portal Client**: Perbarui `invoices/index-client.blade.php`, `client/dashboard.blade.php`, dan `client/reviews/show.blade.php`.
5. **Tahap 5 — Portal Super Admin**: Perbarui `super-admin/index.blade.php`, `super-admin/godmode-users.blade.php`, view monitoring (`admin` & `korlap`), dan `super-admin/activity-logs.blade.php`.
6. **Tahap 6 — Pengujian Akhir**:
   - Jalankan `php artisan view:clear` dan pastikan seluruh view berhasil dikompilasi tanpa error sintaks Blade.
   - Jalankan `php artisan test` dan pastikan seluruh *test suite* yang ada tetap **PASS (Hijau 100%)** tanpa ada regresi.
