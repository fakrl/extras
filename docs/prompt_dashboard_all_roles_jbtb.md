# Master Prompt: Rombak & Pengayaan Dashboard 5 Role (Business Pulse, Hover Breakdown, & Action Shortcuts) — SIM Casting JBTB (`fakrl/extras`)

> **Cara Pakai Dokumen Ini di Antigravity:**
> Simpan dokumen ini sebagai `docs/prompt_dashboard_all_roles_jbtb.md` di repositori lokalmu (atau *copy-paste* langsung ke Antigravity setelah proses sebelumnya selesai), lalu beri instruksi:
> *"Baca dan eksekusi seluruh spesifikasi di `docs/prompt_dashboard_all_roles_jbtb.md` untuk memperkaya Dashboard ke-5 role (`super_admin`, `admin`, `korlap`, `client`, `extras`) dengan statistik ekosistem, hover breakdown interaktif, ringkasan margin keuangan, bottleneck kuota, dan shortcut aksi sesuai alur bisnis database, tanpa emoji Unicode/em-dash dan pastikan 100% test suite (`php artisan test`) tetap hijau."*

## 1. Tujuan & Prinsip Desain Dashboard Baru

Saat ini, halaman Dashboard di kelima role baru menampilkan daftar antrean tugas harian (*to-do list*), namun belum menampilkan **Gambaran Utuh Ekosistem Sistem, Kesehatan Bisnis/Finansial, dan Progres Kuota Operasional** yang sebenarnya sudah tersedia di dalam 21 tabel database JBTB.

Prinsip utama yang wajib diterapkan pada rombak Dashboard ini:
1. **Data-Driven dari Skema Database Asli (Bukan Metrik Fiktif/E-Commerce seperti RFM)**:
   - Seluruh angka statistik, *breakdown*, dan diagram visual wajib dihitung langsung dari tabel nyata di sistem: `users`, `extras_profiles`, `casting_projects`, `casting_project_classes`, `project_applications`, `fee_negotiations`, `contracts`, `invoices`, `payouts`, `payout_adjustments`, `project_expenses`, `staff_payrolls`, `attendances`, `field_notes`, `cancellations`, dan `activity_logs`.
2. **Ringkas di Permukaan, Detail Saat Di-Hover (`.dash-stat-card` + `.dash-hover-popover`)**:
   - Agar tampilan Dashboard tetap bersih dan tidak penuh sesak oleh teks, tampilkan **Angka Utama + 1 Sub-baris Ringkas** di kartu metrik.
   - Ketika pengguna mengarahkan kursor (*hover* di desktop) atau mengetuk ikon info (*focus/tap* di mobile) pada kartu metrik tersebut, muncul **Floating Breakdown Popover (`.dash-hover-popover`)** yang merinci distribusi angkanya (misalnya rincian jumlah akun per role & per grade, atau rincian komponen pemasukan vs pengeluaran).
3. **Setiap Angka Adalah Shortcut Aksi (Clickable Metrics)**:
   - Setiap kartu metrik dan baris *breakdown* harus memiliki tautan pintas (*shortcut*) ke halaman/tab filter terkait sehingga pengguna bisa langsung melompat ke data yang dimaksud.
4. **Aturan Ketat Zero "AI-Slop" & 100% Test Suite Aman**:
   - **DILARANG** menggunakan emoji Unicode, tanda *em-dash*, atau panah teks mentah (`->`, `<-`, `v`). Gunakan murni **Tabler Icons (`<i class="ti ti-..."></i>`)** dan pemisah titik tengah (`·`).
   - **WAJIB** mempertahankan seluruh variabel view eksisting yang diuji di `SuperAdminDashboardTest.php`, `AdminDashboardTest.php`, `ClientDashboardTest.php`, dan `ExtrasDashboardTest.php`. Variabel statistik baru ditambahkan sebagai variabel pendamping tanpa menghapus variabel lama.

## 2. Komponen CSS Baru di `resources/views/partials/theme-style.blade.php`

Tambahkan kumpulan class CSS yang bersih, responsif, dan mendukung Light/Dark Mode di `resources/views/partials/theme-style.blade.php`:

1. **`.dash-pulse-strip`**: Grid responsif (`grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; margin-bottom: 18px;`) untuk menampung kartu-kartu statistik ekosistem & bisnis.
2. **`.dash-stat-card` & `.dash-hover-popover`**:
   - `.dash-stat-card` memiliki `position: relative;`, transisi halus saat di-hover, dan indikator kecil `<i class="ti ti-info-circle"></i>` di pojok kanan atas yang menandakan adanya rincian *hover*.
   - `.dash-hover-popover` adalah panel *breakdown* melayang (`position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 60; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.14); opacity: 0; visibility: hidden; transform: translateY(4px); transition: all 0.18s ease;`) yang otomatis tampil saat `.dash-stat-card:hover` atau `:focus-within`.
   - Di dalam `.dash-hover-popover`, sediakan class `.popover-row` (`display: flex; justify-content: space-between; font-size: 12.5px; padding: 4px 0; border-bottom: 1px dashed var(--border-color);`) untuk menampilkan pasangan label dan nilai rincian secara rapi.
3. **`.dash-segmented-bar`**: Bar horizontal multi-segmen (`height: 8px; border-radius: 999px; overflow: hidden; display: flex; gap: 2px; background: var(--surface-2);`) untuk menampilkan proporsi visual (seperti proporsi tahapan kandidat, keterisian kuota, atau perbandingan Invoice vs Biaya Produksi) tanpa perlu me-load library grafik berat.
4. **`.dash-mini-feed`**: Kontainer daftar aktivitas terbaru yang ringkas dengan titik timeline vertikal, badge kategori aksi, dan waktu relatif (`diffForHumans()`).

## 3. Spesifikasi Lengkap Per Role (Controller Query + View Layout)

### A. Role 1: `super_admin` — "Owner Business Pulse & System Ecosystem Dashboard"

* **File Target**:
  - `app/Http/Controllers/SuperAdmin/DashboardController.php`
  - `resources/views/super-admin/dashboard.blade.php` (serta partial di `resources/views/super-admin/dashboard/`)
* **Analisa Kekurangan Saat Ini**:
  - Saat ini hanya menampilkan 4 kartu antrean tugas (*Menunggu keputusan*, *Tugas staf tertunda*, *Proyek berjalan*, *Invoice belum lunas*), widget Jadwal, serta daftar *Butuh keputusan Anda* dan *Staf perlu diingatkan*.
  - Belum ada informasi mengenai **Total Populasi Pengguna Sistem per Role/Grade**, **Kesehatan Finansial & Margin Kotor Agency**, **Distribusi Funnel Kandidat**, maupun **Feed Aktivitas Sistem Terbaru (`activity_logs`)**.
* **Penambahan Data di `SuperAdmin/DashboardController.php` & Tampilan di `super-admin/dashboard.blade.php`**:
  1. **Pertahankan 4 KPI Antrean Operasional Eksisting** agar `SuperAdminDashboardTest` tetap 100% lulus, lalu tambahkan **Section "Ikhtisar Ekosistem & Finansial Sistem" (`.dash-pulse-strip`)** yang berisi **4 Kartu Statistik Interaktif dengan Hover Popover**:
     - **Kartu 1 — Populasi Pengguna Sistem (`$statPopulasi`)**:
       - *Tampilan Utama*: Total seluruh akun di tabel `users` + shortcut ke `super_admin.akun.index`.
       - *Hover Popover Breakdown*:
         - `Extras (Talent)`: Jumlah total akun `role = 'extras'`
         - `Extras Siap Kontrak (Sudah NIK)`: Jumlah `extras_profiles` dengan `nik_hash != null`
         - `Distribusi Grade Extras`: `Grade A: X · Grade B: Y · Grade C: Z · Belum: W`
         - `Client / PH`: Jumlah akun `role = 'client'`
         - `Staf Internal`: `Admin: X · Korlap: Y · Super Admin: Z`
     - **Kartu 2 — Ekosistem Proyek & Kuota Casting (`$statProyekKuota`)**:
       - *Tampilan Utama*: Total seluruh proyek di `casting_projects` + persentase keterisian kuota pada proyek yang sedang aktif (*Fulfillment Rate* + `.dash-segmented-bar`).
       - *Hover Popover Breakdown*:
         - `Menunggu ACC Super Admin`: `status = 'diajukan_client'`
         - `Buka Lowongan & Seleksi`: `status IN ('disetujui_super_admin', 'buka_lowongan', 'proses_seleksi')`
         - `Shooting Berjalan`: `status = 'shooting_berjalan'`
         - `Selesai Produksi`: `status = 'selesai'`
         - `Ditolak / Dibatalkan`: `status IN ('ditolak_super_admin', 'dibatalkan')`
         - `Keterisian Kursi Aktif`: `X Terisi dari Y Target Kuota` (dari `casting_project_classes`)
     - **Kartu 3 — Kesehatan Finansial & Estimasi Margin Agency (`$statKeuangan`)**:
       - *Tampilan Utama*: Total Nilai Invoice Client Terbit/Lunas (`invoices` di mana `status IN ('diterbitkan', 'lunas')`) beserta persentase **Estimasi Margin Kotor Agency** dan `.dash-segmented-bar` (Hijau untuk Lunas, Kuning untuk Belum Lunas).
       - *Hover Popover Breakdown*:
         - `Invoice Lunas (Kas Masuk)`: `Rp ...` (`invoices.status = 'lunas'`)
         - `Piutang Client (Belum Lunas)`: `Rp ...` (`invoices.status = 'diterbitkan'`)
         - `Total Payout Extras`: `Rp ...` (`payouts`)
         - `Pengeluaran Operasional Proyek`: `Rp ...` (`project_expenses`)
         - `Payroll Staf & Korlap`: `Rp ...` (`staff_payrolls`)
         - `Estimasi Margin Kotor Agency`: `Rp ... (X%)` *(Selisih Total Invoice Terbit/Lunas dikurangi Total Payout + Expenses + Payroll)*
     - **Kartu 4 — Funnel Partisipasi Kandidat Aktif (`$statFunnelKandidat`)**:
       - *Tampilan Utama*: Total lamaran aktif di `project_applications` beserta `.dash-segmented-bar` 5 warna tahapan.
       - *Hover Popover Breakdown*:
         - `Tahap Awal (Diundang/Diajukan/Direview)`: `X kandidat`
         - `Tahap Negosiasi Honor (Nego Fee)`: `Y kandidat`
         - `Tahap Review Client (Deal/Diajukan)`: `Z kandidat`
         - `Lolos & Kontrak Aktif`: `W kandidat`
         - `Selesai Produksi`: `V kandidat`
  2. **Tambahkan Widget "Aktivitas Sistem Terbaru" (`$aktivitasTerbaru` dari `ActivityLog`)**:
     - Ambil 6 entri terbaru dari `ActivityLog::with('user')->latest()->take(6)->get()`.
     - Tampilkan dalam kartu **Aktivitas Sistem Terbaru (`.dash-mini-feed`)** di bagian bawah dashboard Super Admin (menampilkan ikon kategori aksi, nama user & badge role, deskripsi singkat aktivitas, dan waktu relatif `diffForHumans()`), lengkap dengan tombol header `<a href="{{ route('super_admin.activity-logs.index') }}" class="btn btn-sm btn-tonal">Semua Log <i class="ti ti-arrow-right"></i></a>`.

### B. Role 2: `admin` — "Project Execution, Quota Bottleneck, & Finance Action Dashboard"

* **File Target**:
  - `app/Http/Controllers/Admin/DashboardController.php`
  - `resources/views/admin/dashboard.blade.php`
* **Analisa Kekurangan Saat Ini**:
  - Kartu *Ringkasan* di `admin/dashboard.blade.php` hanya menampilkan angka dasar (`proyekAktif`, `totalPendaftar`, `perluDinego`) tanpa rincian *hover* dan belum menyoroti **proyek mana yang kuota perannya masih kurang padahal jadwal syuting sudah dekat (*Quota Bottleneck*)** maupun rincian status kontrak & pencairan honor.
* **Penambahan Data & Penyempurnaan di `admin/dashboard.blade.php` (Mode Admin)**:
  1. **Perkaya Kartu Ringkasan dengan Hover Popover & Shortcut Spesifik**:
     - Pada metrik `Proyek Aktif` (`$proyekAktif`): Tambahkan *hover popover* yang membedakan **Proyek yang Saya Pegang (PIC)** vs **Total Proyek Aktif Agency**, serta rincian berapa yang berstatus *Buka Lowongan*, *Proses Seleksi*, dan *Shooting Berjalan*.
     - Pada metrik `Total Pendaftar` (`$totalPendaftar`): Tambahkan *hover popover* yang merinci jumlah kandidat yang **Menunggu Direview Admin**, **Sedang Nego Fee (`$perluDinego`)**, **Menunggu Pilihan Client**, dan **Belum TTD Kontrak**.
  2. **Tambahkan Indikator "Peringatan Kuota Proyek (Bottleneck Kuota)" di Kartu Perlu Tindakan / Ringkasan**:
     - Hitung keterisian kuota untuk proyek aktif (jumlah kandidat berstatus `deal`, `diajukan_ke_client`, `lolos`, `kontrak_ditandatangani`, `selesai_produksi` dibanding total kuota di `casting_project_classes`).
     - Tampilkan bar progres keterisian kuota per proyek aktif yang belum mencapai 100%, lengkap dengan tombol shortcut `<i class="ti ti-users-group"></i> Penuhi Lineup` yang langsung mengarah ke `admin.projects.applicants` proyek tersebut.
  3. **Pertahankan Elemen yang Diuji Test (`AdminDashboardTest`)**:
     - Pastikan semua variabel eksisting (`proyekAktif`, `totalPendaftar`, `perluDinego`, `chartStatusPembayaran`, `urgentProjects`, `agendaEvents`, `pembayaranSengketa`, `ringkasan`, `tahapan`) dan canvas `#chartPembayaran` tetap utuh.

### C. Role 3: `korlap` — "Field Operations & Real-Time Attendance Command Dashboard"

* **File Target**:
  - `app/Http/Controllers/Admin/DashboardController.php` (bagian data `$korlap`)
  - `resources/views/admin/dashboard.blade.php` (blok `bisaSebagaiKorlap()`)
  - `resources/views/partials/korlap-ringkasan.blade.php`
* **Analisa Kekurangan Saat Ini**:
  - Tampilan Korlap langsung memunculkan daftar proyek hari ini dan validasi, tetapi belum memiliki **Strip Ringkasan Metrik Lapangan** di bagian atas serta tombol pintas (*quick actions*) untuk tugas-tugas lapangan terpenting.
* **Penambahan Data & Penyempurnaan di `partials/korlap-ringkasan.blade.php`**:
  1. **Tambahkan Strip 4 Kartu Metrik Lapangan Korlap di Bagian Atas**:
     - **Proyek Lapangan Aktif**: Jumlah jadwal shooting hari ini & terdekat yang ditugaskan ke Korlap.
     - **Tingkat Kehadiran Lapangan**: Total talent yang sudah `hadir` dibanding total panggilan pada jadwal aktif + *hover breakdown* (`Hadir`, `Menunggu Validasi`, `Belum Absen / Tidak Hadir`).
     - **Antrean Validasi Selfie**: Jumlah presensi selfie yang menunggu validasi Korlap (`count($menunggu)`), diberi aksen kuning/amber tegas jika > 0.
     - **Catatan & Evaluasi Lapangan (`field_notes`)**: Jumlah catatan lapangan terbaru (`count($catatan)`) dengan *hover breakdown* jenis catatan (*Info* vs *Peringatan/Sanksi*).
  2. **Rapikan Tombol Shortcut di Setiap Kartu Proyek Shooting Hari Ini**:
     - Pastikan setiap kartu proyek di kolom *"Shooting Hari Ini / Terdekat"* memiliki grup tombol pintas yang jelas dengan Tabler Icons: **`<i class="ti ti-clipboard-check"></i> Kelola Absensi`** dan **`<i class="ti ti-file-text"></i> Buka Callsheet`**.

### D. Role 4: `client` — "Executive Project Tracker, Quota Progress, & Billing Dashboard"

* **File Target**:
  - `app/Http/Controllers/Client/DashboardController.php`
  - `resources/views/client/dashboard.blade.php`
* **Analisa Kekurangan Saat Ini**:
  - Kartu *Keputusan Pemilihan Saya* dan *Pengajuan Anda* belum menampilkan secara visual **di tahap mana posisi setiap proyek berada** (dari 4 tahap bisnis utama) dan **berapa persen kuota talent yang sudah terpenuhi** untuk proyek tersebut, serta belum ada ringkasan finansial/tagihan di dashboard.
* **Penambahan Data & Penyempurnaan di `client/dashboard.blade.php`**:
  1. **Tambahkan Ringkasan Status Proyek & Tagihan dengan Hover Popover**:
     - Di bagian atas atau di dalam kartu ringkasan Client, tampilkan metrik **Ringkasan Tagihan & Kontrak** (berapa invoice berstatus *Menunggu Pembayaran*, *Lunas*, atau *Belum Terbit*, lengkap dengan *hover breakdown* nominal tagihan dan shortcut ke `/invoice`).
  2. **Tambahkan "4-Step Project Stage Indicator" & "Bar Kuota Kandidat" pada Kartu Pengajuan / Proyek Anda**:
     - Untuk setiap proyek milik Client di daftar `$pengajuan` / `$proyekBerjalan`, tampilkan:
       - **Mini Stepper 4 Tahap**: `1. Pengajuan Brief` -> `2. Pemilihan Kandidat` -> `3. Produksi Shooting` -> `4. Selesai` (tahap aktif diberi warna aksen hijau JBTB).
       - **Indikator Kuota Kandidat**: Tampilkan jumlah kandidat yang sudah disetujui Client dibanding total kuota yang dibutuhkan proyek tersebut, disertai tombol shortcut kontekstual (misalnya jika ada kandidat yang menunggu dipilih Client, tampilkan tombol `.btn-brand` `<i class="ti ti-user-check"></i> Pilih Kandidat ({{ $jumlahMenunggu }})` yang mengarah ke `client.reviews.show`).

### E. Role 5: `extras` — "Talent Career, Earnings Wallet, & Active Job Dashboard"

* **File Target**:
  - `app/Http/Controllers/Extras/DashboardController.php`
  - `resources/views/extras/dashboard.blade.php`
* **Analisa Kekurangan Saat Ini**:
  - Dashboard Extras baru menampilkan progress kelengkapan profil, jadwal hari ini, perlu tindakan, dan pendaftaran aktif, tetapi **belum memiliki ringkasan pencapaian karir & dompet honor (`payouts`)** yang membuat talent tahu berapa proyek yang sudah mereka selesaikan, berapa honor yang sudah cair vs sedang diproses, dan bagaimana skor kehadiran mereka.
* **Penambahan Data & Penyempurnaan di `extras/dashboard.blade.php`**:
  1. **Tambahkan Strip 4 Kartu "Ringkasan Karir & Honor Saya" (`.dash-pulse-strip`)**:
     - Hitung data dari relasi user yang sedang login di `Extras/DashboardController.php` (atau dari koleksi yang sudah ada) dan tampilkan dalam 4 kartu ringkas dengan *hover/tap breakdown*:
       - **Kartu 1 — Aktivitas & Proyek Saya**: Menampilkan jumlah **Proyek Selesai (`selesai_produksi`)** dan **Lamaran Aktif** saat ini. Saat di-hover menampilkan rincian: *Sedang Proses Seleksi/Nego*, *Kontrak Aktif*, dan *Total Riwayat Melamar*.
       - **Kartu 2 — Dompet Honor Saya (`payouts`)**: Menampilkan total nominal honor yang **Sudah Diterima** (`dikonfirmasi_diterima`) dan indikator jika ada honor yang **Sedang Ditransfer / Menunggu Konfirmasi** (`ditransfer` / `belum_dibayar`). Saat di-hover menampilkan rincian nominal *Sudah Cair* vs *Dalam Proses*.
       - **Kartu 3 — Skor Kehadiran & Reputasi**: Menampilkan persentase kehadiran positif dari `$riwayatAbsensi` dan catatan pembatalan (`$jumlahBatalMendadak`). Saat di-hover menampilkan rincian jumlah *Hadir Tepat Waktu*, *Terlambat/Izin*, dan *Batal Mendadak*.
       - **Kartu 4 — Kesiapan Profil & Kontrak**: Menampilkan persentase kelengkapan profil (`$persenProfil%`), status verifikasi KTP/NIK (Siap Kontrak vs Belum Lengkap NIK), dan shortcut `<i class="ti ti-user-edit"></i> Edit Profil`.
  2. **Rapikan Banner Kelengkapan Profil**:
     - Jika `$persenProfil >= 100` dan NIK sudah lengkap, jangan tampilkan banner peringatan besar yang memakan ruang vertikal di bagian paling atas; cukup tampilkan informasi status siap kerja yang ringkas di Kartu 4.

## 4. Verifikasi & Pengujian Wajib Sebelum Selesai

1. **Audit Zero AI-Slop**:
   - Pastikan seluruh kode baru di ke-4 `DashboardController.php` dan seluruh view dashboard **sama sekali tidak mengandung emoji Unicode, tidak mengandung `&mdash;` / `—`, dan tidak mengandung panah teks mentah (`->`, `<-`, `v`)**. Semua ikon wajib menggunakan `<i class="ti ti-..."></i>`.
2. **Kompilasi View & Full Test Suite**:
   - Jalankan `php artisan view:clear` dan `php artisan config:clear`.
   - Jalankan `php artisan test --filter=Dashboard` terlebih dahulu untuk memastikan `SuperAdminDashboardTest`, `AdminDashboardTest`, `ClientDashboardTest`, dan `ExtrasDashboardTest` lulus 100%.
   - Jalankan `php artisan test` secara penuh dan pastikan seluruh **967+ tests** tetap **PASS (Hijau 100%)**.
