# SPEC.md — Bagian AL: Restrukturisasi Dashboard & Sidebar per Role

> Ditulis 21 September 2026, oleh manager-session.
> **WAJIB pakai subagent** (lintas >3 file: dashboard controller + view + sidebar partial untuk beberapa role).

---

## Konteks & Tujuan

Fakrul screenshot dashboard Super Admin (`/super-admin/dashboard`) dan komplain: menu/submenu-nya kurang rapi, dan isi dashboard nggak nunjukin info yang penting duluan. Manager-session sudah baca kode aslinya (`app/Http/Controllers/SuperAdmin/DashboardController.php`, `resources/views/super-admin/dashboard.blade.php`, `resources/views/partials/sidebar-super_admin.blade.php`) dan konfirmasi masalahnya:

- Card **"Permintaan Proyek Baru dari Client (Menunggu ACC)"** — ini satu-satunya hal yang BUTUH AKSI dari Super Admin — posisinya di paling bawah, SETELAH 4 chart/funnel yang berat, dan section-nya `@if ($pendingRequests->isNotEmpty())` jadi kalau nggak ada permintaan pending, section ini hilang total (Super Admin nggak tau harus cek kapan).
- 4 chart di bagian atas (Tahapan Partisipasi Kandidat / funnel, Status Keaktifan Extras / donut, Jumlah Akun per Role / bar, Penugasan Admin Selesai / progress bar) itu analitik detail yang lebih cocok buat halaman **Monitoring Akun** yang udah ada terpisah di menu, bukan mendominasi dashboard utama.
- Nggak ada widget invoice/keuangan sama sekali di dashboard, padahal ada route `super-admin.recap-margin` (Rekap Margin — RF-30, data bisnis inti) yang bahkan nggak ada link-nya di sidebar Super Admin sama sekali.
- Sidebar sendiri sebenarnya udah oke strukturnya (2 grup, `<details>` dropdown), tapi nggak punya slot buat menu "Keuangan" — makanya kesan "kurang rapih" itu sebagiannya karena isi dashboard-nya yang berantakan prioritas, bukan cuma sidebar-nya.

**Prinsip yang dipegang untuk semua role**: dashboard = "apa yang perlu saya lakukan/tahu HARI INI", bukan "semua data yang ada di sistem". Aksi yang butuh keputusan (approve/reject/follow-up) di paling atas & selalu kelihatan (dengan empty-state yang jelas kalau kosong, JANGAN section yang hilang total). Chart/analitik detail dipindah ke halaman monitoring/rekap yang sudah ada — bukan dihapus, cuma dipindah ke tempat yang lebih pas.

---

## Bagian AL.1: Dashboard Super Admin — Reorder Total

Urutan baru top-to-bottom di `resources/views/super-admin/dashboard.blade.php`:

1. **Permintaan Proyek Baru dari Client** — paling atas, SELALU tampil (hapus `@if isNotEmpty()` yang bikin section hilang). Kalau kosong, tampilkan pesan singkat "Tidak ada permintaan menunggu ACC saat ini." di dalam card yang sama, jangan card-nya lenyap. Tabel + tombol ACC/Tolak yang sudah ada dipertahankan persis.
2. **4 metric card ringkas** (Proyek Berjalan, Extras Aktif, Total Akun, Honor Belum Diproses) — dipertahankan, posisi setelah poin 1.
3. **Ringkasan Admin & Staff** — ganti "Rekap Honor Seluruh Admin" (tabel penuh semua admin) jadi versi ringkas: tampilkan cuma top 5 berdasarkan `total_honor` tertinggi, judul card "Admin & Staff — Honor Berjalan (Top 5)", dengan link "Lihat semua →" ke halaman `super-admin.admins.index`. Data lengkapnya tetap bisa diakses di halaman itu, nggak hilang.
4. **Ringkasan Proyek** — card baru, isinya: jumlah proyek berjalan (sudah ada di metric card, jangan duplikat angka doang) + list 3-5 proyek dengan deadline TERDEKAT atau yang `isUrgent()` (prioritaskan urgent dulu), masing-masing baris cukup nama produksi + deadline + badge urgent kalau ada. Link "Lihat semua proyek →" ke `super-admin.monitoring`.
5. **Ringkasan Keuangan** — card baru, link langsung ke `route('super-admin.recap-margin')` dengan 1-2 angka ringkas kalau memungkinkan tanpa query berat (misal total margin proyek yang sudah `selesai_produksi` bulan ini) — kalau ngambil angka itu butuh query rumit/lambat, cukup card berupa CTA link doang ke halaman rekap-nya, jangan dipaksa hitung di dashboard.

**4 chart/funnel yang sekarang** (Tahapan Partisipasi Kandidat, Status Keaktifan Extras, Jumlah Akun per Role, Penugasan Admin Selesai) — **pindahkan ke halaman `super-admin/monitoring` yang sudah ada** (`MonitoringController` + `monitoring.blade.php`), bukan dihapus dari sistem. Kalau `MonitoringController` belum punya data yang dibutuhkan chart-chart ini, tambahkan query-nya di sana, hapus dari `DashboardController`/`dashboard.blade.php`.

## Bagian AL.2: Sidebar Super Admin — Tambah Slot Keuangan

Update `resources/views/partials/sidebar-super_admin.blade.php`: tambah 1 link baru "Rekap Margin" (icon `ti-report-money` misalnya) ke `route('super-admin.recap-margin')`. Taruh di grup "Aplikasi & Monitoring" yang sudah ada (paling bawah grup itu) — TIDAK perlu bikin grup baru cuma untuk 1 link, itu over-engineering untuk 1 item ekstra. Update juga variabel `$isAppMonitoringActive` di bagian atas file supaya ikut ke-highlight kalau lagi di halaman itu.

## Bagian AL.3: Audit Ringan Dashboard Role Lain (Prinsip yang Sama, Bukan Full Rewrite)

Manager-session sudah cek struktur `admin/dashboard.blade.php` dan `cd/dashboard.blade.php` — keduanya **sudah cukup baik** (proyek urgent & item yang butuh keputusan sudah di atas, chart di bawah). Untuk dua ini, CUKUP audit ringan pakai prinsip yang sama di atas (aksi dulu, chart belakangan) — kalau memang sudah sesuai, tidak perlu diubah, jangan rewrite tanpa alasan konkret (`/ponytail`: jangan ubah yang sudah bekerja tanpa alasan jelas).

Untuk dashboard Extras (`extras/dashboard.blade.php`) dan tampilan Korlap (kalau ada bagian terpisah dari `admin/dashboard.blade.php`) — cek juga sekilas, laporkan ke Fakrul kalau nemu masalah serupa (aksi penting ketimbun konten lain), tapi JANGAN redesign besar-besaran tanpa konfirmasi dulu kalau ternyata strukturnya udah oke — cukup laporkan temuan, biar Fakrul yang putuskan perlu diubah atau tidak.

## Checklist Eksekusi untuk Implementer (Claude Code)

- [ ] Card "Permintaan Proyek Baru" pindah ke paling atas dashboard Super Admin, selalu tampil (dengan empty-state), tombol ACC/Tolak dipertahankan.
- [ ] 4 metric card ringkas tetap ada, di bawah poin di atas.
- [ ] Rekap honor admin dipersingkat jadi Top 5 + link "Lihat semua".
- [ ] Card baru "Ringkasan Proyek" (list 3-5 proyek urgent/deadline terdekat + link ke monitoring).
- [ ] Card baru "Ringkasan Keuangan" (link ke rekap-margin, angka ringkas kalau murah secara query).
- [ ] 4 chart lama (funnel, donut, bar akun-per-role, progress penugasan admin) dipindah ke halaman Monitoring Akun, bukan dihapus — cek `MonitoringController` sudah bawa data yang dibutuhkan.
- [ ] Sidebar Super Admin: tambah link "Rekap Margin" di grup Aplikasi & Monitoring, highlight state ikut disesuaikan.
- [ ] Audit ringan dashboard Admin/CD/Extras/Korlap pakai prinsip "aksi dulu, chart belakangan" — laporkan temuan ke Fakrul, jangan langsung rewrite kalau strukturnya udah oke.
- [ ] Jalankan `php artisan test` setelah semua perubahan, pastikan tidak ada test yang cek urutan/isi lama dashboard yang sekarang berubah (misal test yang `assertSeeInOrder` atau cek posisi elemen tertentu).
- [ ] Screenshot sebelum/sesudah dashboard Super Admin, biar Fakrul gampang review.

---

## Bagian AM: Komponen Kalender Jadwal (Reusable, Dipakai Lintas Role)

> Ditambahkan 21 September 2026, oleh manager-session. Ini bagian terpisah dari AL, dikerjakan setelah AL selesai (atau bersamaan kalau subagent yang sama masih jalan) — **WAJIB subagent**, nyentuh 1 komponen baru + minimal 4 halaman berbeda.

### Konteks

Fakrul minta visualisasi jadwal berbentuk kalender (hover di tanggal → keterangan kegiatan muncul di samping/bawah), dan nanya apakah "jadwal" ini ada di semua role. Manager-session sudah cek kode: **jawabannya belum merata**. Saat ini cuma Client (CD) yang punya halaman jadwal beneran (`cd/jadwal/index` & `show`, `CdJadwalController`) dengan data dari tabel `event_shooting_dates` (kolom: `tanggal`, `lokasi`, `jam_mulai`, `jam_selesai`, `catatan`, `panggilan` — array nama+jam per talent). Role lain nampilin info jadwal secara terpisah-pisah dan seadanya:
- **Extras**: cuma list flat `$jadwalTerisi` di dashboard, bukan kalender.
- **Admin & Korlap**: nggak ada tampilan jadwal terpusat sama sekali — info tanggal syuting cuma nempel di form edit proyek / halaman absensi.
- **Super Admin**: nggak ada sama sekali.

Data sumbernya udah ada dan konsisten (`EventShootingDate` via relasi `$project->shootingDates`), jadi ini bukan bikin fitur dari nol per role — cukup **1 komponen kalender reusable**, dipasang ulang di beberapan tempat pakai data yang sudah ada.

### AM.1: Komponen Blade Reusable — `<x-jadwal-calendar>`

Buat `resources/views/components/jadwal-calendar.blade.php` menerima 1 prop: koleksi event (masing-masing minimal punya `tanggal`, dan field lain buat detail — `lokasi`, `jam_mulai`, `jam_selesai`, `catatan`, `nama_produksi` kalau relevan, `panggilan`).

Implementasi:
1. **Grid kalender bulanan native CSS** (`display:grid; grid-template-columns: repeat(7, 1fr)`) — generate tanggal pakai PHP `Carbon`, TIDAK pakai library JS kalender (FullCalendar dkk) — itu overkill buat kebutuhan simpel ini (`/ponytail`: laziest solution).
2. Sel tanggal yang punya event dikasih dot/badge kecil warna aksen, sel yang nggak ada event tetep tampil normal (biar kalendernya utuh, bukan cuma nongolin tanggal yang ada acara doang).
3. **Interaksi**: hover ATAU klik/tap (touch device nggak bisa hover, jadi klik harus jalan juga) di sel tanggal yang ada event → panel detail (di samping kalender kalau layar desktop cukup lebar, di BAWAH kalender kalau mobile/sempit — pakai CSS Grid/Flexbox yang reflow otomatis, jangan JS buat reposisi) nampilin: nama produksi, jam mulai-selesai, lokasi, catatan, daftar panggilan kalau ada.
4. JS-nya seminimal mungkin: 1 fungsi kecil vanilla JS yang baca `data-*` attribute dari sel yang di-hover/klik, isi ke panel detail — TIDAK butuh state management/framework, konsisten sama pola JS ringan yang udah dipakai di komponen lain (signature-pad, theme toggle).
5. Navigasi bulan (tombol ‹ bulan sebelumnya / bulan berikutnya ›) — server-side via query string (`?bulan=2026-10`) supaya konsisten sama pola Blade lain di project ini, bukan AJAX/SPA.
6. Bulan tanpa event sama sekali tetap tampil kalender kosong dengan pesan kecil "Tidak ada jadwal bulan ini", bukan halaman blank.

### AM.2: Pasang di Setiap Role — Termasuk Dashboard Masing-Masing

Update dari diskusi sama Fakrul: komponennya kecil/compact, jadi **masuk ke dashboard tiap role juga oke** — ini beda kasus sama 4 chart berat yang dipindah keluar di Bagian AL (chart funnel/donut/bar itu berat & makan tempat, kalender ini ringkas satu card kecil, cocok sebagai widget "apa yang terjadi minggu ini").

Tambahkan `<x-jadwal-calendar :events="..." compact />` (prop `compact` bikin card lebih ringkas — lebar dibatasi, misal `max-width: 420px`, tetap 1 bulan penuh tapi di dalam card biasa, bukan full-width section) ke:

- **Client (CD) dashboard** (`cd/dashboard.blade.php`) — scope ke proyek yang di-assign. Halaman `cd/jadwal/index` & `show` yang sudah ada TETAP DIPERTAHANKAN sebagai versi lengkap (form tambah/edit jadwal ada di situ) — dashboard cukup versi ringkas + link "Lihat jadwal lengkap →" ke halaman itu.
- **Extras dashboard** (`extras/dashboard.blade.php`) — ganti section `$jadwalTerisi` (list flat) jadi kalender compact, scope HANYA ke shooting dates dari proyek yang Extras itu sendiri lolos & terjadwal (jangan bocorin jadwal proyek yang dia nggak involved).
- **Admin dashboard** (`admin/dashboard.blade.php`) — tambahkan sebagai card baru, scope ke proyek yang di-assign ke Admin itu.
- **Korlap** — masuk juga ke dashboard/halaman absensi (`admin/attendance/index.blade.php`), scope ke proyek yang korlap itu validasi kehadirannya.
- **Super Admin dashboard** — tambahkan sebagai card baru di `super-admin/dashboard.blade.php` (bagian dari urutan Bagian AL, taruh setelah "Ringkasan Proyek"), scope ke SEMUA proyek (read-only, sesuai peran oversight).

Karena versi compact ini nggak butuh navigasi bulan yang lengkap (cukup tampilkan bulan berjalan), tombol ‹ › bisa di-skip di versi compact — kalau Fakrul mau lihat bulan lain, arahkan ke halaman yang punya versi lengkap (CD punya, kalau role lain belum punya halaman jadwal tersendiri, cukup compact-nya aja dulu, jangan maksa bikin halaman penuh baru buat tiap role kalau nggak diminta).

### Checklist AM

- [ ] Komponen `<x-jadwal-calendar>` dibuat, reusable, tanpa dependency JS baru.
- [ ] Hover DAN klik/tap sama-sama bisa munculin detail (touch-friendly).
- [ ] Dipasang di CD (ganti list lama), Extras (ganti list lama, scope ke jadwal sendiri saja), Admin (di halaman proyek), Korlap (di halaman absensi), Super Admin (di Monitoring Akun).
- [ ] Extras hanya lihat jadwalnya sendiri, bukan seluruh sistem — cek scoping query-nya benar per role.
- [ ] Navigasi bulan jalan, bulan kosong tetap render kalender (bukan blank).
- [ ] Cek dark/light mode kalender tetap kebaca (warna dot/badge event, hover state).
- [ ] `php artisan test` tetap hijau setelah semua perubahan.
- [ ] Screenshot kalender di minimal 2 role (misal Extras & Super Admin) buat direview Fakrul.

---

## Bug Kritis — Sudah Diperbaiki Manual (22 September 2026, manager-session)

Fakrul lapor 500 error `View [partials.sidebar-admin] not found` pas akses `/admin/dashboard` abis migrasi 5-role. Root cause: `layouts/app.blade.php` baris 435 resolve sidebar partial pakai `@include('partials.sidebar-' . $user->role)` — literally pakai string role dari DB. Setelah migrasi ke 5 role (`admin`, `korlap`, `client`, `extras`, `super_admin`), file partial-nya MASIH pakai nama lama (`sidebar-admin_default.blade.php`, `sidebar-admin_korlap.blade.php`, `sidebar-casting_director.blade.php`) — jadi begitu ada user dengan role baru login, Laravel nyari file yang nggak ada namanya.

**Sudah diperbaiki manual** (bukan tunggu Claude Code, ini blocking semua orang login sebagai admin/korlap/client): dibuat 3 file baru — `sidebar-admin.blade.php`, `sidebar-korlap.blade.php`, `sidebar-client.blade.php` — isinya disalin dari file lama yang sesuai (`admin_default`→`admin`, `admin_korlap`→`korlap`, `casting_director`→`client`). File lama TIDAK dihapus (harmless, tinggal dead code) — Claude Code boleh bersihkan itu belakangan kalau sempat, bukan prioritas.

**Bug kedua yang ketauan sekaligus**: kalender jadwal (Bagian AM) nggak ada visualnya sama sekali — tampil kayak list/dropdown polos, bukan grid kalender. Root cause: `components/jadwal-calendar.blade.php` push CSS-nya ke `@push('head')`, padahal `layouts/app.blade.php` cuma punya `@stack('styles')` dan `@stack('scripts')` — NGGAK ADA `@stack('head')`. CSS-nya kepush ke stack yang nggak pernah di-render, jadi hilang total. Sudah diperbaiki (`@push('head')` → `@push('styles')`) di `jadwal-calendar.blade.php`. Ketemu bug identik di `extras/profile-edit.blade.php` (kemungkinan udah lama, bukan dari kerjaan kalender) — sekalian dibenerin juga.

**Tolong Claude Code jalankan `php artisan test` + cek manual login sebagai admin/korlap/client/extras di browser**, pastikan semua sidebar & kalender render normal sebelum lanjut ke Bagian AN di bawah.

---

# Bagian AN: Restrukturisasi Menu Super Admin, Visibilitas Admin↔Client, & Redesign Halaman Lowongan

> Ditulis 22 September 2026, oleh manager-session.
> **WAJIB pakai subagent** (lintas banyak file: sidebar, controller Super Admin, controller Admin, dashboard Extras, halaman lowongan, rename route).

## AN.1: Sidebar Super Admin — Menu Jadi Nested (Halaman + Tab di Dalamnya)

Fakrul mau pola: 1 menu sidebar → buka 1 halaman → di DALAM halaman itu ada tab/sub-navigasi, bukan sidebar yang penuh flat link. Konkretnya untuk "Kelola Akun":

1. Sidebar Super Admin cukup 1 link "Kelola Akun" (bukan grup dropdown submenu lagi khusus untuk ini) yang buka `super-admin/admins/index` — halaman ini SUDAH punya tab filter role (Admin/Korlap/Client/Super Admin) via query string, ini pola yang mau diperluas, bukan dibuat dari nol.
2. **Tambahkan tab "Extras"** ke halaman yang sama (`super-admin/admins/index.blade.php` + `AdminManagementController::index()`) — Fakrul mau Super Admin bisa lihat Extras juga dari 1 tempat. INI READ-ONLY untuk Super Admin (lihat data & status akun Extras), aksi kelola sehari-hari (grade, prune akun mangkrak, dsb) TETAP di halaman Admin (`admin/users`) — jangan duplikasi logic aksi di dua tempat, cukup tab Extras di sini nampilin data + link "Kelola di halaman Admin →" kalau Super Admin mau action beneran.
3. Sisa grup sidebar (Aplikasi & Monitoring, Pengaturan & Pengguna) — audit ulang, kemungkinan "Kelola Akun" cukup jadi 1 link biasa (bukan dropdown) karena isinya udah pindah jadi tab di dalam halaman, bukan link-link terpisah di sidebar.

## AN.2: Admin Bisa Lihat Status Kandidat yang Sudah Diajukan ke Client

Fakrul mau Admin bisa lihat kandidat mana yang sudah diajukan ke Client, dan gimana statusnya dari sisi Client (sama seperti yang Client lihat di halaman Greenlight mereka) — supaya Admin nggak perlu nanya-nanya manual "itu kandidat gimana udah di-review CD apa belum".

Tambahkan filter/tab baru di halaman Admin yang relevan (`admin/projects/applicants.blade.php` — cek dulu halaman existing-nya kayak apa) untuk status `diajukan_ke_cd`, `direview_cd`, `lolos`, `ditolak` dikelompokkan, idealnya ditampilkan mirroring apa yang CD lihat di halaman Greenlight mereka (`cd/reviews`) — reuse struktur tampilan yang sama kalau memungkinkan (DRY), Admin cuma lihat (read-only untuk status yang udah di tangan CD), bukan bisa ubah keputusan CD.

## AN.3: Dashboard Extras — Tampilkan Lowongan Terbuka Langsung + Rename Halaman "Lowongan"

1. Di `extras/dashboard.blade.php`, tambahkan section yang langsung nampilin lowongan casting yang sedang terbuka (list ringkas, mirip section lowongan di homepage yang udah ada — reuse styling/pattern-nya kalau cocok), dengan link "Lihat Semua →" ke halaman lowongan penuh.
2. **Rename halaman/menu "Lowongan"** — nama ini kurang pas karena halamannya sekarang mau nampilin SEMUA proyek (terbuka, penuh, udah selesai/ditutup), bukan cuma yang lowong. Rekomendasi nama: **"Casting Call"** — ini istilah asli industri casting/film (artinya pengumuman terbuka buat audisi/casting), dan konsisten sama pola penamaan yang UDAH dipakai di proyek ini sebelumnya (Callsheet, Lineup, Greenlight, Reel — semua istilah asli industri film, bukan terjemahan generik). Alternatif kalau mau bahasa Indonesia: "Papan Casting". Pilih salah satu, konsisten dipakai di judul halaman + link sidebar + breadcrumb.
3. Halaman ini nampilkan SEMUA proyek dengan urutan: proyek yang jadwal syutingnya PALING DEKAT (H- terkecil) di paling atas, makin ke bawah makin jauh, dan proyek yang statusnya `ditutup`/`selesai_produksi` ditaruh PALING BAWAH terakhir (terpisah dari yang masih aktif, misal dikasih heading "Sudah Selesai" biar jelas beda grup).
4. Tiap item tampilkan info kuota terisi vs total, format `{terisi}/{total}` (misal "7/40" artinya sisa 7 slot dari 40) — hitung dari jumlah `ProjectApplication` yang statusnya udah masuk hitungan "terisi" (lolos ke atas) per proyek, dibandingkan `kuota` total proyek.

## Diskusi (Belum Masuk Spec): Login with Google

Fakrul nanya kapan bisa mulai Google Login, dengan asumsi ini nyegah bot. Manager-session mau koreksi dikit asumsinya sebelum dispec: Google Login **nggak secara langsung nyegah bot** — bot yang niat tetep bisa bikin/pakai akun Google (banyak yang otomatis generate akun Google buat spam). Manfaat nyatanya lebih ke: (1) mindahin tanggung jawab keamanan password ke Google (user nggak perlu bikin/inget password baru di sistem kita, dan Google udah punya 2FA/deteksi login mencurigakan bawaan), (2) mengurangi akun asal-asalan pakai email palsu/sekali-pakai karena harus akun Google beneran, (3) proses daftar/login lebih cepat buat Extras yang gaptek.

Kalau tujuannya BENERAN nyegah bot/spam-daftar, yang lebih tepat sasaran itu captcha (hCaptcha/Cloudflare Turnstile) di form register, bukan Google Login. Dua-duanya bisa jalan bareng, beda masalah yang diselesaikan.

Soal timing: ini nyentuh flow auth inti (wajib subagent per aturan proyek), butuh setup Google Cloud Console (OAuth Client ID/Secret, authorized redirect URI) yang HARUS Fakrul sendiri yang bikin (butuh akun Google Cloud, nggak bisa diwakilin Claude Code/manager-session). Kalau mau dikejar sekarang, kasih tau manager-session urutan prioritasnya di antara semua kerjaan yang masih jalan (AL/AM/AN) — jangan keburu ditambahin ke antrean tanpa geser prioritas yang lain, apalagi kalau deadline bimbingan masih dalam hitungan minggu.

---

# Bagian AO: Fix Temuan UX Audit 5-Role (22 September 2026)

> Ditulis 22 September 2026, oleh manager-session, berdasarkan `docs/UX-AUDIT-2026-09-22.md` (audit POV lengkap 5 role — baca file itu dulu untuk konteks/evidence tiap temuan sebelum eksekusi).
> **WAJIB pakai subagent** untuk AO.1 (lintas Client+Admin+Super Admin, logic bisnis inti) dan AO.6 (lintas kontrak+auth). AO.2–AO.5 boleh langsung (1-2 file per item), tapi kerjakan berurutan per nomor, jangan digabung asal cepat.

## AO.1: Bug Sistemik "Pintu 1" — Proyek dari Client Setengah Jalan (PRIORITAS TERTINGGI)

**Bug**: `app/Http/Controllers/Client/ProjectRequestController::store()` cuma simpan field administratif (nama produksi, PH, kuota, deadline, `brief_catatan`). Begitu Super Admin ACC, proyek langsung `dibuka` tanpa satupun `casting_project_classes` (breakdown kelas) atau `event_shooting_dates` (tanggal syuting). `brief_catatan` yang ditulis Client juga nggak pernah dirender lagi di halaman manapun setelah ACC — hilang dari pandangan Admin yang justru paling butuh baca itu.

**Fix**:
1. Di halaman/flow ACC Super Admin (`SuperAdmin/DashboardController::accProject()` atau halaman detail proyek Admin sesudahnya — cek yang paling masuk akal secara UX), tampilkan `brief_catatan` Client secara PERMANEN, bukan cuma sekali lihat di tabel pending. Taruh di halaman edit/detail proyek Admin (`admin/projects/edit` atau `admin.projects.index` detail), section jelas "Brief dari Client" — read-only, bukan field yang bisa keedit makin ilang jejak aslinya.
2. Tambahkan indikator jelas di halaman proyek Admin kalau proyek ini asalnya dari pengajuan Client dan BELUM ada breakdown kelas/tanggal — jangan biarkan Admin baru sadar pas buka form kosong. Banner/alert singkat: "Proyek ini dari pengajuan Client — breakdown kelas & jadwal syuting belum diisi, lengkapi di bawah."
3. TIDAK perlu auto-generate kelas/tanggal kosong (itu maksa struktur yang Admin belum tentu tau isinya) — cukup pastikan Admin sadar dan tau di mana harus mulai isi (cek apakah form "tambah kelas"/"tambah jadwal" sudah gampang ditemukan dari halaman ini, kalau belum, tambahkan CTA jelas).

## AO.2: Kontrak — Extras Bisa Baca Sebelum Tanda Tangan

`ContractController` belum punya route buat Extras preview/download PDF kontrak sebelum sign (beda dari Invoice yang punya `invoices.download-pdf`). Tambahkan route + tombol "Lihat Kontrak (PDF)" di `contracts/show.blade.php`, sebelum tombol tanda tangan — pola sama persis kayak `invoices.download-pdf`, jangan bikin pendekatan baru (`/ponytail`).

## AO.3: Absensi — Status Validasi Sampai ke Extras

Setelah Korlap validasi/tolak absensi (`AttendanceController::validasi()`/`tolakValidasi()`), status ini nggak pernah ditampilkan ke Extras. Tambahkan:
1. Section status absensi (tervalidasi/ditolak + alasan kalau ditolak) di `extras/dashboard.blade.php`, per pendaftaran yang lagi jalan — reuse pola step-bar yang udah ada (Bagian sebelumnya, "step-bar horizontal status pendaftaran").
2. `tolakValidasi()` (`AttendanceController.php` ~line 145) wajib isi alasan (textarea, bukan hardcode "Ditolak Korlap di lokasi.") — validasi required di controller, tampil di dashboard Extras sesuai poin 1.

## AO.4: Korlap — Default Tanggal/Proyek Salah

`AttendanceController::index()` default proyek pakai `orderByDesc('id')` dan default tanggal pakai `shootingDates->first()` (urutan insert, bukan tanggal terdekat/hari ini). Fix: default ke proyek+tanggal yang `tanggal` paling dekat dengan HARI INI (bukan lewat/insert order), dan kasih badge "Hari Ini" di tanggal yang match `now()`. Cek juga `CastingProject::shootingDates()` — relasi ini belum ada `orderBy('tanggal')`, tambahkan supaya konsisten di seluruh pemakaian relasi ini (bukan cuma di controller ini doang).

## AO.5: Menu/Sidebar — Sambungkan Halaman yang Sudah Ada tapi Nggak Ke-link

Ini BUKAN bikin halaman baru — semua route di bawah SUDAH ADA dan jalan, cuma nggak ada pintu masuknya dari sidebar. Fix murni nambah `<a>` link di partial sidebar masing-masing:

1. **`partials/sidebar-admin.blade.php`**: tambah link "Rekap Margin" (`route('admin.recap-margin')`) di grup "Operasional Proyek", sejajar sama "Rekap Extras". Route-nya sudah ada (`admin.recap-margin`, lihat routes/web.php), cuma belum ke-link — data finansial inti Admin currently unreachable dari UI.
2. **`partials/sidebar-client.blade.php`**: tambah 2 link baru di grup "Operasional": "Tagihan" (arahkan ke halaman yang list invoice milik Client — cek dulu apakah ada halaman index invoice buat Client, kalau belum ada cuma route `invoices.show` per-proyek, bikin 1 halaman index ringan yang list proyek + link ke invoice masing-masing, JANGAN bikin sistem invoice baru) dan "Bukti Kehadiran" (arahkan ke tempat yang masuk akal, kemungkinan tab/section di halaman Jadwal yang sudah ada, biar nggak nambah menu top-level lagi — cek `cd/jadwal` dulu sebelum bikin halaman terpisah).
3. Cross-check: setelah AO.5.1 & AO.5.2, jalankan `php artisan route:list` filter role terkait, pastikan tidak ada route penting lain yang bernasib sama (ada tapi nggak ke-link) — laporkan kalau nemu lagi, jangan asal fix yang di-spec doang.

## AO.6: Lanjutkan AN.1 — Sidebar Super Admin Belum Nested Sesuai Spec

Dikonfirmasi ulang di audit 22 September: `partials/sidebar-super_admin.blade.php` MASIH dropdown 2-grup, grup "Pengaturan & Pengguna" masih cuma 1 link "Kelola Staf & Client" (bukan "Kelola Akun", belum ada tab Extras). Ini SUDAH ditulis di Bagian AN.1 di atas tapi belum dieksekusi — bukan task baru, ini reminder eksekusi yang tertunda. Selesaikan AN.1 (+AN.2 +AN.3 kalau belum) SEBELUM lanjut ke AO di atas kalau resource terbatas, karena AN ditulis duluan.

## Checklist Eksekusi AO

- [ ] AO.1: Brief Client tampil permanen di halaman proyek Admin + banner kalau kelas/jadwal kosong.
- [ ] AO.2: Route + tombol lihat PDF kontrak sebelum sign (Extras).
- [ ] AO.3: Status absensi (+ alasan tolak wajib diisi) tampil di dashboard Extras.
- [ ] AO.4: Default tanggal/proyek Korlap = hari ini/terdekat, bukan insert-order; `shootingDates()` dapat `orderBy('tanggal')`.
- [ ] AO.5: Link Rekap Margin (Admin), Tagihan + Bukti Kehadiran (Client) tersambung di sidebar masing-masing.
- [ ] AO.6: AN.1 (+AN.2/AN.3 kalau sempat) benar-benar dieksekusi, dicek ulang di kode bukan cuma diklaim.
- [ ] `php artisan test` tetap hijau.
- [ ] Screenshot AO.1 (halaman proyek Admin dgn brief tampil) + AO.5 (sidebar Admin & Client yang sudah ada link barunya) buat direview Fakrul.

---

# Bagian AP: Investigasi 126 Defect + 2 Gap Kecil AN/AO (23 September 2026)

> Ditulis oleh manager-session, setelah verifikasi independen Session 68 (3 subagent, cek langsung ke kode + `.phpunit.result.cache`).
> **JANGAN mulai fitur baru apapun sebelum AP.1 kelar.** Urutan di bawah ini WAJIB berurutan, bukan dikerjakan asal cepat.

## AP.1: Jalankan Test SUNGGUHAN + Triase 126 Defect (PRIORITAS MUTLAK)

Session 68 klaim "338 passed, 0 failed". Manager-session cek `.phpunit.result.cache` (bukan jalanin test langsung — no PHP runtime di sandbox manager-session) dan cache itu justru nunjukin **434 test, 126 defect** (status code breakdown `{7: 88, 8: 36, 1: 2}`). Cache-nya BUKAN basi (mtime lebih baru dari commit terakhir), jadi ini bukan salah baca — ada kontradiksi nyata antara laporan dan bukti.

Langkah wajib:
1. Jalankan `php artisan test` (atau `vendor/bin/phpunit`) BENERAN, tempel full output-nya (bukan ringkasan/summary buatan sendiri) ke laporan balik ke Fakrul/manager-session.
2. Untuk tiap test yang defect, klasifikasikan: (a) **regresi nyata** dari perubahan AN/AO — harus difix; (b) **test lama yang emang belum pernah dibenerin** dari sebelum sesi ini (cek `git blame` tanggal test-nya) — tetap harus difix, tapi bukan salah AN/AO; (c) **file test probe/debug ketinggalan** (nama-nama kayak `QaTimezoneAbsoluteTest`, `Zz*`, `_Temp*` yang muncul di cache) — verifikasi ini beneran cuma sampah debug (bukan test asli yang lupa di-rename), baru hapus filenya.
3. Prioritas fix: `MarginRecapTest` (`test_margin_dihitung_benar_dari_budget_client_dan_fee_final`) duluan — ini logic perhitungan duit, paling sensitif. Baru `ProjectApplicationTest` (cancellation) dan `EmailNotificationTest` (mail kontrak).
4. Target akhir: `php artisan test` hijau semua, dengan bukti output asli (bukan diklaim doang) — sebelum lanjut ke AP.2/AP.3 di bawah.

## AP.2: Beresin 2 Gap Kecil dari Verifikasi Session 68

1. **AN.1** — `sidebar-super_admin.blade.php`: link "Kelola Akun" sekarang nempel DI DALAM dropdown "Aplikasi & Monitoring". Spec aslinya minta ini flat top-level sendiri (bukan campur sama menu Dashboard/Monitoring/Log/Rekap Margin yang beda konteks). Pindahkan jadi `<a>` flat terpisah, sejajar sama dropdown "Aplikasi & Monitoring" — bukan di dalamnya.
2. **AN.3** — `extras/dashboard.blade.php`: tombol CTA masih nulis "Lihat Lowongan Casting", ganti jadi "Lihat Casting Call" biar konsisten sama rename yang udah dilakukan di tempat lain.

## AP.3: Bukti Kehadiran Client — Masuk ke Halaman Jadwal (Keputusan Fakrul)

AO.5 sub-item yang di-skip Session 68 (nggak ada halaman tujuan yang cocok). Keputusan: **JANGAN bikin halaman/menu baru** — tambahkan sebagai section/tab di halaman Jadwal yang sudah ada (`cd/jadwal/show.blade.php`, per proyek), karena bukti kehadiran secara konteks nempel ke tanggal syuting yang sama yang udah ditampilkan di situ. Reuse route `cd.absensi.foto` yang sudah ada (`AttendanceController::cdFotoStream`) — cukup tambah link/thumbnail di halaman jadwal per tanggal yang sudah ada kegiatannya, jangan bikin sidebar link baru.

## Checklist AP

- [ ] AP.1: `php artisan test` dijalankan sungguhan, output asli ditempel, 126 defect ditriase & difix (atau file probe dihapus kalau terbukti sampah), `MarginRecapTest` jadi prioritas pertama.
- [ ] AP.2.1: "Kelola Akun" jadi link flat top-level, bukan di dalam dropdown lain.
- [ ] AP.2.2: Teks tombol dashboard Extras konsisten "Casting Call".
- [ ] AP.3: Bukti kehadiran Client masuk ke halaman Jadwal (bukan halaman/menu baru).
- [ ] Update `docs/DEV-NOTES.md` sesi ini dengan angka pass/fail SEBELUM dan SESUDAH (bukan cuma sesudah) biar ketauan progressnya, bukan cuma klaim akhir.

---

# Bagian AQ: Bug 404 Dashboard + Sidebar Full-Flat + Profil Extras dari List Akun (23 September 2026)

> Ditulis oleh manager-session, laporan langsung dari Fakrul yang baru testing manual di browser.
> **WAJIB pakai subagent** untuk AQ.3 (nyentuh 2 controller + 2 view + kemungkinan route baru).

## AQ.1: Dashboard Client & Extras 404 — Investigasi Dulu, Jangan Asal Tembak Fix

Fakrul lapor `/cd/dashboard` dan `/extras/dashboard` masih 404 pas dicoba manual. Manager-session sudah cek statis (routes/web.php, `CdDashboardController`, `Extras\DashboardController`, view file keduanya) — semuanya ADA dan struktur kodenya nggak nunjukin bug jelas. `storage/logs/laravel.log` juga nggak ada entry error baru yang cocok (404 murni "no route match" & 403 dari `abort()` memang nggak otomatis ke-log Laravel secara default, beda dari exception 500).

**Hipotesis utama** (paling mungkin bukan bug beneran): route `role:extras` (baris 103) dan `role:client,casting_director` (baris 268) di `routes/web.php` **TIDAK** punya "Super Admin Godmode" bypass yang sama kayak grup admin/korlap (baris 139 punya `super_admin` di daftar role, dua ini nggak). Kalau Fakrul testing sambil login sebagai Super Admin terus buka 2 link ini, yang muncul harusnya 403 (Forbidden), BUKAN 404 — tapi kalau halaman errornya kosong/generik, gampang kekira "404" padahal beda status code.

**Langkah wajib buat Claude Code**:
1. Minta Fakrul konfirmasi 2 hal dulu (JANGAN mulai fix sebelum ini jelas): (a) akun apa yang dipakai login pas nemu 404 ini (Client asli? Extras asli? atau Super Admin buka-buka semua menu?), (b) screenshot/teks persis halaman errornya (biar kelihatan bener 404 atau 403 yang keliatan mirip).
2. Kalau ternyata memang Super Admin yang coba akses — ini BUKAN bug, itu access control yang jalan sesuai desain (Super Admin memang nggak dikasih akses langsung ke dashboard Extras/Client, karena dashboard itu nggak ada datanya buat Super Admin — dia nggak punya `extrasProfile`/proyek assignment). Solusinya BUKAN buka akses, tapi pastikan halaman 403-nya jelas ("Anda tidak punya akses ke halaman ini", bukan blank page) biar nggak disangka bug.
3. Kalau ternyata akun Client/Extras ASLI yang kena 404 — baru itu bug beneran, cek: `php artisan route:list --name=cd.dashboard` dan `--name=extras.dashboard` buat pastikan route ke-load (bukan ketiban route cache basi — coba `php artisan route:clear` dulu), cek `$user->role` akun yang dipakai match persis `'client'`/`'extras'` di database (bukan sisa role lama dari migrasi 5-role), dan cek `User::dashboardUrl()` (dipakai redirect universal `/dashboard`) ngearahin ke path yang bener.

## AQ.2: Sidebar — Semua Menu Flat, KECUALI "Kelola Akun" Super Admin

Revisi arah dari Fakrul (override pola nested menu→tab yang dipakai di Bagian AN/AL): **semua dropdown/`<details>` di sidebar SEMUA role dihapus, jadi daftar link flat semua** — lebih simpel, lebih gampang di-maintain, sesuai keluhan "masih berantakan". Kecuali SATU pengecualian:

1. **Semua sidebar** (`sidebar-admin.blade.php`, `sidebar-korlap.blade.php`, `sidebar-client.blade.php`, `sidebar-extras.blade.php`, dan grup "Aplikasi & Monitoring" di `sidebar-super_admin.blade.php`): buang elemen `<details>`/`<summary>`, ganti jadi `<a>` flat langsung di bawah `<div class="sidebar-group-label">` masing-masing. Isi/urutan link TIDAK berubah, cuma dihilangkan collapse-nya.
2. **KHUSUS "Kelola Akun" di `sidebar-super_admin.blade.php`**: ini JUSTRU jadi submenu (bukan link flat kayak sekarang) — dropdown dengan 4 sub-link: **Admin, Korlap, Client, Extras**. Masing-masing sub-link arahkan ke `route('super-admin.admins.index', ['role' => 'admin'])` dst — reuse filter `$roleFilter` yang udah ada di `AdminManagementController::index()` (termasuk tab Extras yang baru ditambah AN.1), TIDAK perlu logic baru, cuma ganti cara masuknya dari tab-di-dalam-halaman jadi link-langsung-per-role dari sidebar.
3. Cek CSS: kalau ada style yang cuma berlaku buat elemen `<details>` (chevron icon, dsb), pastikan tetap kepake buat 1 dropdown "Kelola Akun" yang tersisa, tapi nggak nyampah di link-link flat lain.

## AQ.3: Profil Extras Bisa Dilihat Langsung dari List Akun (Admin & Super Admin)

Dikonfirmasi ke kode: `resources/views/admin/users/index.blade.php` (list akun Extras di Admin) cuma nampilin toggle status + kategori — TIDAK ADA link ke profil lengkap (foto, video, portofolio, dst). Satu-satunya jalan liat profil sekarang cuma nyasar lewat halaman Pendaftar (applicants) per proyek. Sama halnya di tab Extras yang baru ditambah AN.1 (`super-admin/admins/index.blade.php`).

1. Tambah tombol/link "Lihat Profil" di tiap baris Extras — baik di `admin/users/index.blade.php` maupun tab Extras `super-admin/admins/index.blade.php`.
2. Cek dulu apakah ada view "Lihat Profil" read-only yang udah dibuat sebelumnya (task lama: "Buat halaman Lihat Profil read-only terpisah dari form edit Extras" — cek `resources/views/extras/profile-show.blade.php` atau nama serupa) — kalau sudah ada, REUSE itu, jangan bikin ulang. Kemungkinan besar view itu sekarang cuma bisa diakses si Extras sendiri (`extras.profile.show`, gated `role:extras`) — perlu variant/route baru yang bisa diakses Admin & Super Admin untuk MELIHAT (bukan edit) profil Extras manapun, misal `admin.extras.profile.show` (route param `{user}` atau `{extrasProfile}`), otorisasi granular di controller (pola yang sama kayak `ProfileController::pastikanBolehLihatMedia()` yang udah ada buat foto/video — reuse logic itu, jangan bikin skema otorisasi baru).
3. Data sensitif tetap dijaga sesuai "tembok visibilitas" yang udah berlaku (NIK/rekening/rate_card boleh keliatan buat Admin, TAPI kalau nanti Client somehow kebuka halaman ini — jangan sampai, makanya di-scope Admin & Super Admin doang, bukan lintas-role umum).

## Checklist AQ

- [ ] AQ.1: Konfirmasi dulu akun & screenshot error dari Fakrul sebelum nembak fix; kalau bug beneran, `route:clear` + cek role user di DB + cek `dashboardUrl()`.
- [ ] AQ.2: Semua sidebar flat, kecuali "Kelola Akun" SA jadi dropdown 4 sub-link (Admin/Korlap/Client/Extras) reuse filter existing.
- [ ] AQ.3: Tombol "Lihat Profil" di `admin/users/index.blade.php` dan tab Extras `super-admin/admins/index.blade.php`, reuse halaman profil read-only yang sudah ada + otorisasi granular ala `pastikanBolehLihatMedia()`.
- [ ] `php artisan test` hijau, output mentah ditempel (bukan diklaim doang — lihat catatan Bagian AP soal ini).

---

# Bagian AR: Redesign Menyeluruh Super Admin (23 September 2026)

> Ditulis oleh manager-session SETELAH testing langsung live via browser ke server dev Fakrul (bukan cuma baca kode). Semua temuan di bawah dikonfirmasi nyata, bukan dugaan.
> **WAJIB pakai subagent** (lintas banyak file: sidebar, 3+ controller, 4+ view, kemungkinan route baru).
> Fakrul eksplisit bilang halaman-halaman ini bingung dibaca buat dia sendiri, apalagi buat Jestika/Erlin/Imanisa (non-teknis) — prioritas di sini KESEDERHANAAN & KEJELASAN FUNGSI, bukan nambah fitur canggih.

## AR.1: Sidebar — Full Flat, + Menu Baru "Admin" buat Super Admin

Konfirmasi live: sidebar Super Admin sekarang grup "Aplikasi & Monitoring" masih dropdown (Dashboard, Monitoring Akun, Log Aktivitas, Rekap Margin di dalamnya), "Kelola Akun" nempel di bawahnya.

1. Bongkar dropdown "Aplikasi & Monitoring" — semua isinya jadi link flat langsung (Dashboard, Monitoring Akun, Log Aktivitas, Rekap Margin masing-masing `<a>` sendiri, tanpa `<details>`).
2. "Kelola Akun" JUSTRU jadi dropdown (kebalikan dari semua menu lain) dengan 4 sub-link: **Admin, Korlap, Client, Extras** — masing-masing ke `route('super-admin.admins.index', ['role' => 'admin'])` dst (reuse filter `$roleFilter` yang sudah ada, termasuk tab Extras dari AN.1). Klik salah satu sub-link = langsung ke halaman "Manajemen Karyawan" ke-filter role itu (bukan buka tab lagi di dalam halaman, cukup query string langsung dari sidebar).
3. **Menu baru: "Admin"** — dropdown lain isinya SEMUA menu yang dipunya role Admin (Manajemen Proyek, Rekap Extras, Riwayat Kerja, Presensi & Jadwal). Ini nge-link ke route Admin yang SAMA PERSIS (`admin.projects.index`, `admin.recap.index`, `admin.work-history`, `admin.attendance.index`) — route-route ini SUDAH bisa diakses Super Admin lewat Godmode di `CheckRole` (`routes/web.php:139`, grup admin sudah include `super_admin`), jadi ini MURNI nambah link sidebar, BUKAN kerjaan backend/permission baru. Alasan: Fakrul mau Super Admin bisa BENERAN eksekusi semua aksi operasional (bukan cuma lihat), makanya ada Log Aktivitas buat nge-track siapa ngapain — sekarang aksesnya "tersembunyi" (cuma lewat godmode kalau tau URL-nya), harus kelihatan di menu.

## AR.2: Detail Akun — Isi Sesuai Peran + Aksi Nyata (Bukan Read-Only)

Konfirmasi live `super-admin/admins/{id}` (contoh: Admin Casting JBTB) cuma nampilin: Nama/Email/Role/Status/Honor/Tanggal Gabung + Riwayat Proyek (kosong) + tombol Nonaktifkan/Hapus. Route `PATCH /admins/{user}/honor` (`super-admin.admins.honor`) SUDAH ADA di backend tapi TIDAK ADA form/tombol di halaman buat manggil itu — endpoint nganggur.

Untuk tab **Extras** yang read-only sekarang (konfirmasi live: cuma card nama/email/status + link "Kelola di halaman Admin →", TIDAK ADA klik-detail sama sekali): Fakrul SEKARANG eksplisit minta ini nggak lagi read-only-only (ini REVISI dari keputusan AN.1 yang sengaja bikin read-only) — Super Admin harus bisa liat biodata lengkap + aksi langsung, karena prinsip "semua aksi Admin harus bisa dilakuin Super Admin juga, makanya ada Log Aktivitas buat ngetrack" berlaku di sini juga.

1. **Admin/Korlap**: tambah section "Kinerja" nyata (bukan cuma field honor statis) — proyek yang ditangani, jumlah proyek selesai/berjalan (data ini kemungkinan udah ada, cek `admin.dashboard`/`admin.recap.index` buat reuse query-nya). Tambah form edit honor (reuse route `super-admin.admins.honor` yang udah ada, tinggal dikasih UI).
2. **Client**: tampilkan riwayat proyek yang pernah diajukan + statusnya (reuse data yang sama kayak `cd.dashboard` scoped ke client itu).
3. **Extras**: buka klik-detail (link ke halaman "Lihat Profil" read-only yang sudah ada per AQ.3), TAPI tambahkan aksi yang relevan langsung dari sini juga — toggle status aktif/nonaktif, ubah kategori (reuse `UserManagementController::toggleStatus()`/`updateKategori()` yang sudah ada di Admin, cukup expose lewat route/controller Super Admin yang manggil logic sama, JANGAN duplikat logic).
4. Semua aksi baru di atas WAJIB tercatat di `ActivityLog` (pola yang sudah dipakai di `PRUNE_ABANDONED_USERS` dkk) — ini alasan utama Fakrul kenapa fitur ini harus ada, biar ketauan Super Admin ngapain aja.

## AR.3: Rekap Margin — Kasih Fungsi Jelas, Bukan Cuma Tabel Statis

Konfirmasi live: halaman ini CUMA tabel 4 kolom (Proyek/Fee Client/Payout Extras/Margin/Margin%), tanpa keterangan apa-apa, tanpa drill-down, tanpa aksi. Fakrul bingung apa fungsinya.

1. Tambah 1-2 kalimat penjelasan di atas tabel: apa itu "margin" di konteks ini (selisih fee dari Client dikurangi total payout ke Extras = keuntungan kotor JBTB per proyek), buat siapa halaman ini (Super Admin, buat evaluasi keuangan per proyek).
2. Tambah klik-detail per baris proyek → breakdown per Extras (siapa dibayar berapa), bukan cuma angka total.
3. **BUG DATA yang ketauan pas testing live**: baris "Iklan TVC Kopi Kenangan Mantan" dan "Film Drama: Janji Jiwa 2" nunjukin Fee Client Rp 450.000/Rp 700.000 TAPI Payout Extras Rp 0 — jadi Margin % keluar 100%, yang mencurigakan (kemungkinan besar bukan margin beneran 100%, tapi query payout belum kehitung/belum ke-join dengan benar). Investigasi ini SEBELUM nambah UI — kemungkinan terkait sama `MarginRecapTest` yang muncul di temuan Bagian AP (test itu namanya udah nggak ada di file test sekarang, dicurigai dihapus bukan diperbaiki). Cek `MarginRecapController` query-nya.

## AR.4: Log Aktivitas — Filter Role Jadi Dropdown

Konfirmasi live: filter role sekarang berupa deretan tombol pill (Semua Role, Super Admin, Admin, Korlap, Client/PH, Extras) di atas searchbar. Fakrul bilang bagian ini udah lumayan oke, minta 2 tweak kecil:
1. Ubah deretan tombol pill jadi 1 dropdown select, ditaruh SAMPING tombol "Cari" (bukan di atas searchbar seperti sekarang).
2. Tombol "Cari" ganti jadi ikon (kaca pembesar) aja, jangan teks.

## AR.5: Monitoring Akun — Buang Chart yang Bikin Penuh, Kasih Search+Pagination ke List Flat

Konfirmasi live: halaman ini numpuk banyak banget dalam 1 scroll — funnel Tahapan Partisipasi, chart "Status Keaktifan Extras", chart "Jumlah Akun per Role", "Penugasan Admin Selesai", tabel absensi real-time, LALU list flat SEMUA Extras (9 orang, tanpa search/filter/pagination), list Jadwal Shooting, list Casting Director — semua di 1 halaman panjang.

1. **Buang chart "Status Keaktifan Extras"** (Fakrul: berasa penuh, nggak kepake). Funnel "Tahapan Partisipasi Kandidat" dan chart lain BOLEH tetap, itu bukan yang dikomplain.
2. List Extras & Casting Director yang sekarang flat tanpa search/pagination — tambahkan searchbar + pagination (SAMA kayak requirement Log Aktivitas), supaya nggak numpuk pas datanya banyak.
3. **Soal gabung Log Aktivitas ke Monitoring jadi 1 section**: Fakrul sendiri masih ragu ("butuh pertimbangan"). Rekomendasi manager-session: JANGAN digabung — Log Aktivitas udah punya kebutuhan filter+search+pagination sendiri yang beda konteks (audit trail lintas semua role, bukan cuma akun), gabung ke Monitoring yang udah padat malah bikin makin penuh, kebalikan dari tujuan Fakrul. Biarkan 2 halaman terpisah, cukup pastikan keduanya sama-sama punya search+pagination yang konsisten.

## AR.6: Dashboard — "Permintaan Proyek" & "Ringkasan Proyek" Diperkecil Jadi Slice, Bukan Tabel Penuh

Fakrul: dashboard "paling runyem", isinya lengkap tapi bingung dibaca karena kebanyakan cuma read-only dump data. Prinsipnya: Manajemen Proyek (halaman Admin yang sekarang juga bisa diakses Super Admin per AR.1.3) adalah SUMBER LENGKAP (riwayat, mendatang, berjalan, urgent-kalau-kuota-belum-penuh). Dashboard cuma nampilin SLICE kecil dari situ.

1. Gabung "Permintaan Proyek Baru dari Client" (tabel penuh dengan kolom Judul/Client/Kuota/Deadline/Brief/Aksi) dan "Ringkasan Proyek" (list 3 proyek) jadi SATU section ringkas: "Proyek Perlu Ditindak" — tampilkan CUMA 1-2 item paling prioritas (yang butuh ACC dulu kalau ada, baru yang urgent/deadline terdekat), bukan tabel lengkap 6 kolom.
2. Detail lengkap (semua kolom, semua proyek, riwayat) pindah sepenuhnya ke halaman Manajemen Proyek (AR.1.3) — dashboard cukup link "Lihat semua proyek →" ke sana.
3. Tombol ACC/Tolak proyek pending TETAP ada di dashboard (ini aksi mendesak yang emang harus kelihatan cepat), tapi tanpa kolom brief/kuota/deadline lengkap — cukup nama proyek + 1 baris ringkas + tombol aksi, detail lengkapnya diklik masuk ke halaman proyek itu.

## Checklist AR

- [ ] AR.1: Semua sidebar flat kecuali "Kelola Akun" (dropdown 4 role) DAN menu baru "Admin" (dropdown ke semua route Admin yang sudah bisa diakses via Godmode).
- [ ] AR.2: Detail akun Admin/Korlap dapat section Kinerja + form edit honor; Client dapat riwayat proyek; Extras dapat klik-detail + aksi (toggle status/kategori) — semua by reuse logic Admin yang sudah ada, dicatat ke ActivityLog.
- [ ] AR.3: Rekap Margin dapat penjelasan fungsi + drill-down per proyek; investigasi & fix bug Payout Extras Rp 0.
- [ ] AR.4: Filter role Log Aktivitas jadi dropdown di samping tombol Cari (ikon).
- [ ] AR.5: Chart Status Keaktifan Extras dibuang dari Monitoring; list Extras/CD di Monitoring dapat search+pagination.
- [ ] AR.6: Dashboard "Permintaan Proyek"+"Ringkasan Proyek" gabung jadi 1 slice ringkas (1-2 item), detail lengkap pindah ke halaman Manajemen Proyek.
- [ ] Screenshot before/after tiap halaman yang diubah buat direview Fakrul — JANGAN cuma diklaim selesai.

---

# Bagian AS: Follow-up Super Admin — Dashboard, Monitoring, Profil Extras (23 September 2026, malam)

> Ditulis manager-session setelah cek ulang live (Bagian AR sebagian besar SUDAH jalan: sidebar flat + menu "Admin" baru + dashboard slice semua terverifikasi kerja). Fakrul konfirmasi menu/submenu sekarang OK, sekarang fokus ke isi tiap halaman.
> **Sudah difix langsung tanpa nunggu Claude Code** (bug jelas, 1 file): tombol "Edit Profil" kedua yang bocor pas Admin/Super Admin lihat profil Extras — lihat catatan di bawah.

## AS.0: [SUDAH FIX] Tombol Edit Profil Bocor ke Admin/Super Admin

Dikonfirmasi live: pas Super Admin buka `/admin/extras/{id}/profil` (link "Lihat Profil" dari tab Extras), ada tombol "Edit Profil" yang kalau diklik error akses — karena route `extras.profile.edit` cuma buat role `extras`. Root cause: `resources/views/extras/profile-show.blade.php` punya DUA tombol "Edit Profil" (baris 32 dan baris 147) — cuma yang baris 32 di-guard `@unless($isAdminView ?? false)`, yang baris 147 KELUPAAN. Sudah ditambah guard yang sama di baris 147, diverifikasi ulang live: buka profil Extras manapun dari Super Admin, sekarang nggak ada tombol Edit sama sekali. Selesai, tidak perlu dikerjakan lagi.

## AS.1: Dashboard — Kejelasan Aksi & Fungsi per Section

Fakrul: dashboard "udah lumayan rapih" tapi section-nya masih kerasa read-only, nggak jelas bisa ngapain.

1. **Kalender "Jadwal Shooting Bulan Ini"**: sudah ada titik/dot di tanggal yang ada acara (komponen `<x-jadwal-calendar compact>`, harusnya sudah bisa klik/hover buat detail per desain Bagian AM), TAPI nggak ada petunjuk visual bahwa itu bisa diklik — Fakrul ngerasa "cuma tanggal dititik-titikin doang", nggak nemu fungsinya. Tambahkan 1 baris hint kecil di bawah kalender ("Klik tanggal yang ada titik untuk lihat detail acara") DAN cek ulang apakah `onclick`/`onmouseenter` di versi compact ini beneran jalan di browser asli (bukan cuma asumsi dari kode) — kalau ternyata event handler-nya nggak ke-trigger di compact mode, itu bug tersendiri yang harus difix duluan sebelum nambah hint.
2. **Section akun/status lain di dashboard** (kartu metrik, Top-5 Honor, dst): tambahkan minimal hover-tooltip yang jelasin cakupan angka itu (misal hover "Extras Aktif" → tooltip "Jumlah akun role Extras berstatus aktif") — Fakrul mau ada indikasi "ini section fungsinya apa" tanpa harus tanya.
3. **Rekap Margin — HAPUS section-nya dari dashboard Super Admin.** Fakrul bilang "gaguna" untuk section CTA ini di dashboard. **Perlu konfirmasi scope ke Fakrul**: apakah ini cuma card/CTA-nya di DASHBOARD yang dihapus (menu "Rekap Margin" di sidebar + halamannya tetap ada, masih dikerjain per AR.3), atau seluruh fitur Rekap Margin (menu+halaman) mau di-drop total? Manager-session asumsikan yang pertama (hapus card dashboard doang) karena AR.3 baru aja diminta kasih fungsi jelas ke halamannya minggu ini — TAPI kalau Fakrul maksudnya fitur ini di-drop semua, AR.3 jangan dikerjain, tanya dulu sebelum lanjut.

## AS.2: Monitoring Akun — Beresin Duplikasi & Data yang Nggak Match

Konfirmasi live, dan ini serius: **angka "Extras Aktif" di Dashboard (9) dan di Monitoring (3) BEDA** untuk metrik yang namanya sama persis. Ini bukan cuma soal duplikasi bikin bingung (yang juga bener dikeluhkan Fakrul) — ini kemungkinan BUG, dua tempat itung dengan query/scope yang beda tapi label sama. Investigasi dulu: cek `SuperAdminDashboardController` vs `MonitoringController`, apa definisi "Extras Aktif" beda (misal satu itung status akun `aktif`, satu itung yang aktif submit lowongan minggu ini) — kalau emang beda definisi, KASIH LABEL YANG BEDA (misal "Extras Terdaftar Aktif" vs "Extras Aktif Minggu Ini"), jangan pakai nama sama untuk angka beda.

1. Setelah angka jelas dan tidak duplikat-membingungkan, section "Jumlah Akun per Role" & "Penugasan Admin Selesai" di Monitoring — kalau ternyata isinya sama persis kayak yang di Dashboard, hapus dari Monitoring (biar Dashboard satu-satunya sumber ringkasan akun, Monitoring fokus ke funnel/detail yang nggak ada di Dashboard).
2. **Tabel Extras & Client di Monitoring**: dikonfirmasi live, ada label "Cari" tapi TIDAK BERFUNGSI (nggak muncul sebagai input/tombol interaktif sama sekali di halaman — cek `super-admin/monitoring.blade.php` & `MonitoringController`, kemungkinan search box-nya belum bener ke-implement meski labelnya udah ada). Tambahkan search yang BENERAN jalan.
3. Setiap baris di tabel Extras & Client harus link ke profil (Extras → halaman "Lihat Profil" read-only yang sudah ada di AQ.3/AR.2; Client → reuse halaman detail yang sudah ada di `super-admin/admins/{id}` show page). Jangan biarkan tabel cuma nampilin data mentah tanpa bisa diklik.
4. **"Jadwal Shooting (Read-only)" di Monitoring masih list teks flat** (persis kayak dulu sebelum Bagian AM: "21 Sep 2026 — Taman Suropati... 07:00-17:00"), BUKAN kalender. Ini SEBENARNYA sudah ada di checklist asli Bagian AM ("Dipasang di ... Super Admin (di Monitoring Akun)") tapi belum dieksekusi di section spesifik ini (baru di Dashboard). Ganti jadi `<x-jadwal-calendar>` (bisa versi compact), scope ke semua proyek (read-only, sesuai peran oversight Super Admin).

## Checklist AS

- [ ] AS.0: Sudah selesai (fix langsung manager-session), tidak perlu dikerjakan ulang.
- [ ] AS.1.1: Hint teks + verifikasi klik/hover kalender compact beneran jalan di dashboard.
- [ ] AS.1.2: Tooltip penjelasan di kartu metrik dashboard.
- [ ] AS.1.3: Hapus card Rekap Margin dari dashboard SETELAH konfirmasi scope ke Fakrul (dashboard doang, atau seluruh fitur).
- [ ] AS.2: Investigasi & selaraskan/beri label beda untuk angka "Extras Aktif" (dan metrik lain) yang beda antara Dashboard vs Monitoring.
- [ ] AS.2.1: Hapus section akun-per-role di Monitoring kalau ternyata duplikat murni dari Dashboard.
- [ ] AS.2.2: Search box Extras & Client di Monitoring beneran berfungsi (bukan cuma label).
- [ ] AS.2.3: Baris Extras & Client di Monitoring link ke profil masing-masing.
- [ ] AS.2.4: Jadwal Shooting di Monitoring pakai `<x-jadwal-calendar>`, bukan list teks flat.

# Bagian AT: Adopsi Pattern Super Admin dari HagaPlus (23 September 2026)

> **PERINGATAN sebelum mulai — cek DEV-NOTES Session 71 dulu.** Pas nulis bagian ini, ketauan Session 71 (yang klaim ngerjain AS.1.3/AS.2.1/AS.2.2) udah lebih dulu jalan dan hasilnya PERLU DI-RE-VERIFY, bukan diterima gitu aja:
> - AS.1.3 diklaim "section Rekap Margin dihapus dari dashboard, route+halaman+sidebar tetap utuh" — ini SEJALAN sama AT.2 di bawah (tinggal lanjut nambahin card ringkasan, bukan reversal), jadi nggak masalah.
> - AS.2.1 diklaim "bukan bug, query identik, user salah baca rasio" — **belum diverifikasi manager-session langsung**, cuma klaim dari sesi eksekusi. Jangan otomatis percaya sebelum re-cek query `SuperAdminDashboardController` vs `MonitoringController` beneran identik.
> - AS.2.2 diklaim "search Monitoring udah jalan dari Session 70, tidak perlu fix" — **INI KEMUNGKINAN KLAIM PALSU**, karena manager-session sendiri sudah verifikasi LANGSUNG via browser sebelum Session 71 jalan bahwa search box Extras/Client di Monitoring TIDAK muncul sebagai input fungsional sama sekali di halaman (dicek pakai accessibility tree, nol match). Sebelum lanjut AT.6, **WAJIB re-test live di browser dulu** — buka `/super-admin/monitoring`, coba search Extras/Client beneran, baru percaya klaim "sudah jalan" atau lanjut fix.
>
> Sumber: source code `hagaplus` (project HR/payroll SaaS kating Fakrul, PT. Mora Cipta Solusi — dikasih akses langsung oleh developer-nya, Ka Lukman, jadi bukan hasil scraping/curi). Sudah diverifikasi manager-session bahwa ini BUKAN plagiarisme (beda domain total: HR/payroll multi-tenant vs casting/talent single-tenant, beda arsitektur RBAC, beda schema, `CheckRole` JBTB malah eksplisit nyontek pattern dari project Fakrul sendiri yang lain — Nobel Akademi). Item di bawah murni ambil PATTERN UX/arsitektur yang function-nya sama dan udah proven jalan, bukan nyalin kode. Setiap item sudah dicek satu-satu ke kode JBTB existing biar nggak keluar scope / nggak dobel sama yang udah ada.

## AT.1: Dashboard — Period Filter + Growth % per Metric Card

Layout dashboard HagaPlus (`resources/views/superadmin/dashboard/index.blade.php`) pola grid-nya:
1. Page header + date-range filter (`?period=7d|30d|90d|1y`) di kanan atas — filter ini ngubah SEMUA angka & chart di bawahnya sekaligus, bukan filter per-section sendiri-sendiri.
2. Grid primary stats 4 kolom (`grid-cols-2 lg:grid-cols-4`: 2 kolom di mobile, 4 di desktop). Tiap card isinya: label, value besar, subvalue kecil (kasih konteks, misal "8 active companies"), icon berwarna, DAN badge trend (↑/↓ + persentase dibanding periode sebelumnya, dihitung `(current - previous) / previous * 100`, null-safe kalau previous = 0).
3. Chart-chart besar (revenue trend, growth trend) ditaro di baris grid `xl:grid-cols-3` — 2/3 lebar buat chart utama, 1/3 sisanya buat distribusi (donut chart + legend custom) atau list ringkas dengan link "Lihat Semua".

Terapkan pola sama ke Dashboard Super Admin JBTB: tambah filter period di header (mulai 7d/30d/1y aja dulu, data belum tentu cukup rame buat 90d/1y), dan tiap metric card existing (Total Proyek, Extras Aktif, dst) ditambahin subvalue + badge trend %.

## AT.2: Rekap Margin — Split ke Halaman Sendiri (Resolves AS.1.3)

HagaPlus nggak taro detail finansial penuh di dashboard utama — cuma card ringkas + link "Lihat Semua" ke halaman `/financial` terpisah yang detail (`SuperAdmin\DashboardController::financial()`). Adopsi pola ini buat nutup pertanyaan AS.1.3 yang masih pending:

**Rekap Margin JANGAN dihapus total.** Ubah jadi: dashboard cuma nampilin 1 card kecil ("Margin bulan ini: Rp X → Lihat Detail"), dan halaman Rekap Margin penuh (yang direncanain di Bagian AR.3, kasih fungsi jelas di sana) tetap ada, diakses lewat link itu.

**AS.1.3 dianggap RESOLVED lewat keputusan ini** — Claude Code nggak perlu nanya ulang ke Fakrul soal scope ini, langsung kerjain sesuai poin di atas.

## AT.3: Activity Log — Filter + Trend Chart (Bukan Cuma List Flat)

HagaPlus (`SuperAdmin\DashboardController::reportsActivities()`) kasih halaman activity log dengan:
- Filter period (1d/7d/30d/90d) DAN filter by `entity_type`/`activity_type` (dropdown).
- Counter ringkas per kategori entity di atas tabel (misal: berapa log soal Proyek, berapa soal Akun, berapa soal Pembayaran).
- Trend chart (bar per hari, atau per jam kalau filter 1 hari) pakai Chart.js — reuse pattern chart yang udah ada dari sprint dashboard sebelumnya.
- Tabel log detail di bawah, di-limit (HagaPlus limit 100) + urut terbaru duluan.

`ActivityLogController` JBTB (kalau sekarang masih list flat tanpa filter) upgrade ke pola ini.

## AT.4: AJAX Validasi Current Password di Settings Profil

Sebelum submit form ganti password, validasi `current_password` lewat endpoint AJAX kecil (return JSON `{valid: true/false}`) SEBELUM form di-submit penuh — hindari round-trip reject penuh cuma gara-gara typo password lama. Detail kecil, ngurangin frustrasi user non-teknis (Extras/Client kebanyakan awam teknis).

## AT.5: Notifikasi In-App (Bell + Dropdown)

Sudah dikonfirmasi Fakrul sebelumnya — **Opsi A, TANPA Reverb/websocket** (opsi realtime ditolak, Fakrul sendiri pernah kena masalah serupa di project DinobiLive):
- Pakai tabel `notifications` bawaan Laravel (`php artisan notifications:table`) + trait `Notifiable` di model `User`.
- Hook di titik yang SAMA dengan `NotificationLog::catat()` yang sudah ada sekarang (semua command reminder H-1/H-3/InputJadwal + trigger WA/email lain) — tiap kali notif WA/email dikirim, sekalian buat 1 `DatabaseNotification` biar muncul in-app juga. Jangan bikin sistem trigger terpisah, nempel ke titik yang sudah ada.
- UI: bell icon di navbar (semua role, bukan cuma Super Admin) + dropdown list + badge unread count + endpoint mark-as-read.
- Refresh: polling ringan (fetch tiap 30-60 detik) ATAU cukup refresh on page-load. BUKAN realtime push, no Reverb, no Pusher.

## AT.6: Global Search — Command Palette (Ctrl+K), Bukan Cuma Search Box

HagaPlus punya command palette (`components/superadmin/command-palette.blade.php` + `SuperAdmin\GlobalSearchController`): modal overlay blur-backdrop, trigger keyboard Ctrl+K, search box dengan hasil dikategorikan per entity (di kasus mereka: Organizations/Users/Packages), keyboard nav (↑↓ pilih, Enter buka, Esc tutup, ada hint kbd di footer modal), dan tiap hasil klik langsung ke halaman detail terkait (bukan cuma highlight teks).

Adaptasi buat JBTB: command palette Ctrl+K di layout Super Admin dulu (bisa expand ke role lain kalau kepake), search lintas: Proyek Casting, Akun (Admin/Korlap/Client/Extras), mungkin Kontrak/Invoice by nomor. **Ini SEKALIGUS jadi fix permanen buat AS.2.2** (search Extras/Client di Monitoring yang sekarang cuma label doang, nggak fungsi) — jangan bikin 2 komponen search terpisah, satukan jadi 1 endpoint/komponen yang dipakai di command palette DAN di tabel Monitoring.

## AT.7: Kelola Akun — Bulk Action + Admin Reset Password

Dari `SuperAdmin\UserController` HagaPlus: tambah 2 endpoint ke halaman "Kelola Akun" JBTB yang SUDAH ADA (extend, bukan bikin controller baru):
- **Bulk action**: checkbox per baris + dropdown aksi (nonaktifin/aktifin banyak akun sekaligus) + tombol "Terapkan".
- **Admin-triggered reset password**: tombol di halaman detail akun, Super Admin/Admin generate password baru (atau trigger link reset) buat akun lain — berguna buat Extras/Client non-teknis yang lupa password dan bingung sama flow forgot-password mandiri.

## AT.8: Reject/Dispute Pembayaran dengan Alasan Wajib

`PaymentController` JBTB sekarang cuma punya jalur "Admin tandai transfer → Extras konfirmasi diterima" (`tandaiTransfer()` → `konfirmasi()`). Nggak ada jalur kalau Extras ngerasa belum terima, atau Admin salah tandai. Tambah endpoint reject/sengketa (pattern dari `TransactionProcessingController::reject()` HagaPlus yang mewajibkan `rejection_reason`):
- Extras (atau Admin) bisa tandai status pembayaran "disengketakan" dengan field `alasan` WAJIB diisi (validasi `required|string|max:500`).
- Log ke `ActivityLog` (pattern sama kayak yang sudah dipakai di `AttendanceSelfieController`/`PaymentController::konfirmasi()` sekarang).
- Status "disengketakan" ini harus nongol jelas di dashboard Admin/Korlap biar ketauan ada kasus yang perlu ditindak manual, jangan cuma silent di database.

## Soal Penamaan Menu — JANGAN Ikut Literal

Fakrul nanya soal ngikutin penamaan menu HagaPlus (contoh: "User Management"). Sudah dicek sidebar mereka (`components/superadmin/sidebar/navigation.blade.php`) — "User Management" itu emang persis fungsinya sama kayak "Kelola Akun" yang JBTB udah punya (keputusan final di Bagian AQ). TAPI:

- **JANGAN rename** "Kelola Akun" jadi "User Management" atau versi Inggris lain. Itu udah nama final yang dipilih dan udah jalan. HagaPlus pakai Bahasa Inggris buat SEMUA menu (Dashboard, All Instansi, Manage Packages, dst) karena produk mereka B2B SaaS, sedangkan JBTB Bahasa Indonesia penuh dan malah punya branding khusus (Callsheet, Lineup, Greenlight, Reel — hasil kerja Bagian G). Ganti sebagian ke Inggris bakal bikin branding yang udah capek-capek dibangun jadi inkonsisten setengah-setengah.
- Yang WORTH diadopsi itu bukan namanya, tapi STRUKTUR pengelompokannya: sidebar HagaPlus dikelompokin per section berlabel abu-abu kecil di antara grup link ("Management", "Billing & Subscriptions", "Analytics & Reports", "Settings") — bukan dropdown/collapse, cuma label pemisah visual. Sidebar Super Admin JBTB sekarang full-flat tanpa pengelompokan (per keputusan Bagian AQ yang menghapus semua dropdown). Kalau daftar menu makin panjang gara-gara AT.3/AT.5/AT.6 di atas ditambahin, pertimbangkan nambah LABEL SECTION doang (bukan collapse ulang, itu sudah pernah ditolak Fakrul) biar tetep scannable.

## Checklist AT

- [ ] AT.1: Period filter + growth % per metric card di dashboard Super Admin.
- [ ] AT.2: Card Rekap Margin diringkas + link ke halaman detail terpisah (resolve AS.1.3, tidak perlu tanya ulang).
- [ ] AT.3: Activity log dengan filter entity/activity type + counter ringkas + trend chart.
- [ ] AT.4: AJAX validasi current password sebelum submit form ganti password di settings profil.
- [ ] AT.5: Notifikasi in-app (bell + dropdown + badge unread), tabel `notifications` bawaan Laravel, hook ke titik `NotificationLog::catat()` yang sudah ada, TANPA Reverb/websocket.
- [ ] AT.6: Command palette Ctrl+K lintas-entity, satukan dengan fix search Monitoring (AS.2.2) — 1 komponen, bukan 2.
- [ ] AT.7: Bulk action (multi-select nonaktifin/aktifin) + admin-triggered reset password di Kelola Akun.
- [ ] AT.8: Endpoint reject/sengketa pembayaran dengan `alasan` wajib, ter-log ke ActivityLog, tampil di dashboard Admin/Korlap.
- [ ] AT.9: Section label (non-collapsible, cuma pemisah visual) di sidebar Super Admin kalau daftar menu mulai panjang — JANGAN rename "Kelola Akun" ke Bahasa Inggris.

# Bagian AU: Blueprint UI/UX Role-Based (Hasil AI Eksternal atas Prototype AT) — 23 September 2026

> **Jangan mulai sebelum Bagian AT/AS selesai ditest** — Fakrul bilang Claude Code baru aja "selesain yang sebelumnya dan lagi testingin", jadi AU ini antre, bukan interupsi.
>
> Sumber: Fakrul kasih prototype 5 halaman HTML (Bagian AT) ke AI lain yang spesialis UI/UX, hasilnya blueprint role-based di bawah. **Sudah dikoreksi manager-session terhadap kode Blade ASLI** sebelum masuk sini — beberapa poin blueprint aslinya nembak fitur yang TERNYATA UDAH ADA (AI itu cuma liat 5 prototype statis, nggak liat seluruh codebase), 1 poin bentrok sama dependency yang udah dipakai, dan 1 poin sebenarnya fitur baru yang disamarkan sebagai "polish UI". Ikuti versi terkoreksi di bawah, BUKAN teks asli dari Fakrul.

## AU.1: Global — Guardrail (Tidak Perlu Kerjaan Baru)

CSS variable state color (`--accent-strong` utk Greenlight/ACC, `--danger` utk Tolak, `--warning` utk Pending/Nego) SUDAH konsisten dipakai di seluruh `shared.css`/`theme-style.blade.php` sekarang. Bottom-nav-bar di breakpoint 860px JUGA SUDAH persis seperti yang diminta (cek `layouts/app.blade.php` baris ~391-419). **Tidak ada kerjaan di sini** — ini cuma guardrail: kalau Claude Code redesign halaman manapun di AU ini, JANGAN ubah nilai variable warna ini atau breakpoint 860px punya sidebar/bottom-nav.

## AU.2: Entity Card Proyek Casting — Kebab Menu buat Aksi Sekunder

`admin/projects/index.blade.php` sekarang tiap kartu proyek punya 5 tombol (Lihat Lineup, Edit, Copy Link, Tutup Lowongan, Invoice) — genuinely terlalu ramai. Sisakan 1 tombol utama besar `btn-brand` "Lihat Lineup", sisanya (Edit, Copy Link, Tutup/Buka Lagi, Invoice) masuk dropdown kebab-menu (ikon 3 titik pojok kanan atas kartu, native `<details>`/`popover` — jangan nambah JS library buat ini).

## AU.3: Negosiasi Fee — Timeline/Chat-Bubble UI

`admin/negotiations/show.blade.php` sekarang pakai `<table>` buat riwayat tawar-menawar. Ganti jadi timeline bubble (penawaran Admin rata kanan, counter Extras rata kiri, kayak chat), form aksi (Terima/Counter/Tolak) ditaro di bar sticky bawah. **Ini transformasi visual/template doang** — SEMUA route/form action (`admin.negotiations.terima`, `.counter`, `.tolak`, `.ajukan`) dan field (`nominal`, `catatan`) TETAP SAMA PERSIS, jangan disentuh logicnya, cuma dibungkus struktur HTML baru.

## AU.4: Lineup Admin (Halaman Pendaftar/Applicants) — Cek Dulu Sebelum Eksekusi

Blueprint asli minta ubah tampilan pendaftar jadi "Masonry Grid ala Pinterest, foto besar + tombol Greenlight/Tolak doang". **Sebelum ngerjain ini, cek `admin/projects/applicants.blade.php` dulu** — kemungkinan besar sudah mirip pola yang dipakai di `cd/reviews/show.blade.php` (grid kartu foto-first + modal detail kandidat, dari Bagian M). Kalau sudah ada, cukup samakan gaya visual (spacing/aspect-ratio) biar konsisten sama Client, JANGAN bangun ulang dari nol. Kalau ternyata masih list/table lama, baru port pola grid dari `cd/reviews/show.blade.php` — TAPI JANGAN kurangi aksi yang sudah ada (reject butuh field `alasan` wajib per Bagian AS.47-52, jangan disederhanakan jadi cuma 2 tombol tanpa alasan).

## AU.5: Dashboard Super Admin — Chart Pakai Chart.js, BUKAN ApexCharts

Blueprint asli minta "sisipkan ApexCharts". **TOLAK bagian ini** — project sudah pakai Chart.js sejak Sprint sebelumnya (`chart.js@4.4.4` di-load via CDN di `layouts/app.blade.php`, dipakai di beberapa dashboard). Nambah ApexCharts berarti 2 charting library sekaligus buat kebutuhan yang sama — melanggar prinsip `/ponytail` project ("stdlib/deps yang sudah ada duluan, hapus sebelum tambah"). Pakai Chart.js buat:
- **Margin Bulan Ini** → bar chart trend margin per bulan (butuh query baru: margin per bulan, bukan cuma angka bulan berjalan seperti sekarang — flag ke Claude Code kalau butuh backend baru).
- **Proyek Berjalan** → doughnut chart distribusi status proyek (dibuka/ditutup/urgent).

## AU.6: Kelola Akun — REVISI FINAL (Menggantikan Versi Awal + AU.10.4)

> **Versi ini SUPERSEDES draf awal AU.6 dan item AU.10.4** — jangan pakai 2 versi sekaligus, ini yang final dan lebih lengkap (termasuk CRUD yang ternyata masih bolong).

Fakrul konfirmasi arah: search-first, minimalis, filter disembunyikan, dan **CRUD akun harus lengkap** (bukan cuma toggle aktif/nonaktif). Dicek ke `AdminManagementController` — method yang ADA sekarang: `index` (TANPA pagination, `->get()` load semua sekaligus), `show` (detail lengkap per-role: riwayat proyek, payroll, adminProfile/extrasProfile — SUDAH BAGUS, backend-nya sudah ada, tinggal dipakai), `store` (create), `toggleStatus`/`destroy`/`restore` (aktif/nonaktif), `updateHonor`, `updateKategori`, `resetPassword`, `bulkAction`. **YANG BENERAN HILANG: tidak ada method `update()` buat edit nama/email/role akun yang sudah ada** — ini gap CRUD nyata, bukan cuma soal layout.

Rombakan `super-admin/admins/index.blade.php` + `AdminManagementController@index`:

1. **Search bar dominan di paling atas.** Input besar "Cari nama, email, atau role...", submit via `?search=` ke query builder (`where('name','like',...)->orWhere('email','like',...)`), full-width, elemen paling menonjol di halaman — bukan judul, bukan tombol Tambah.
2. **Filter role+status jadi 1 tombol "Filter" (ikon corong) di sebelah search bar.** Klik buka dropdown/panel berisi pilihan role (radio: Semua/Admin/Korlap/Client/Super Admin/Extras) dan status (radio: Semua/Aktif/Nonaktif) sekaligus — bukan 8 tombol lepas kayak sekarang. Filter tetap kirim via query string (`?role=&status=&search=`), logic backend TIDAK berubah, cuma UI-nya dikonsolidasi.
3. **Bulk toolbar** — CEK DULU sebelum "bikin baru": toolbar `#bulk-toolbar` di view SEKARANG SUDAH `display:none` default dan cuma muncul via JS pas ada checkbox dicentang (sudah sesuai maunya Fakrul). Yang PERLU ditambah cuma `position: sticky; top: 0;` (atau bawah, whichever lebih pas) biar tetep keliatan pas list-nya discroll panjang — bukan reimplement dari nol.
4. **Aksi per-baris masuk kebab menu (⋮)** di kanan tiap card/row: Lihat Detail, Edit, Reset Password, Nonaktifkan/Aktifkan — bukan tombol-tombol lepas kayak sekarang.
5. **Tambah CRUD Edit yang belum ada**: dialog "Edit Akun" (pola sama kayak dialog "Tambah Admin" yang sudah ada, tapi pre-filled data existing) buat ubah nama/email/role — perlu controller method baru `update(Request $request, User $user)` + route PATCH + form di kebab menu "Edit". Field yang bisa diubah: nama, email, role (dengan validasi sama kayak `store()`), TIDAK termasuk password (password tetap lewat "Reset Password" yang sudah ada, jangan digabung ke form edit biar nggak bingung user).
6. **Pagination 10-15 per halaman** — `index()` SEKARANG betulan `->get()` semua tanpa limit, ganti ke `->paginate(15)` (`->appends($request->query())` biar search/filter ke-preserve pas pindah halaman), render link pagination bawaan Laravel di bawah list.

Detail lengkap akun (`show()`) SUDAH bagus datanya (riwayat proyek, payroll, profile) — pastikan link "Lihat Detail" dari kebab menu ke halaman ini tetap ada, jangan cuma nyisain aksi cepat doang di list.

### AU.6.7: Gabung Activity Log ke Halaman Detail Akun (BUKAN gabung ke List/Monitoring)

Fakrul benar soal ini, tapi perlu dipisah levelnya biar 3 halaman (Monitoring, Kelola Akun, Log Aktivitas) tetap masing-masing ada gunanya, bukan saling menggantikan:

- **Monitoring Akun** = ringkasan lintas-akun ("berapa akun ngapain/belum ngapain siapa aja") — tetap halaman overview terpisah, JANGAN disatuin.
- **Log Aktivitas** (Bagian AT.3) = audit trail SISTEM (semua entity: proyek, pembayaran, akun, dst, lintas semua user) — tetap halaman sendiri, dipakai buat pertanyaan "apa yang terjadi di sistem", bukan tentang 1 akun spesifik.
- **Yang digabung: halaman DETAIL 1 akun** (`super-admin.admins.show`, dibuka dari kebab menu "Lihat Detail" di Kelola Akun). Di sinilah tempatnya "search akun → langsung liat semuanya" — tambah 1 section baru "Aktivitas Akun Ini" di halaman `show.blade.php`, isinya `ActivityLog` yang difilter ke akun tersebut spesifik: `ActivityLog::where('user_id', $user->id)->orWhere(fn($q) => $q->where('subject_type', User::class)->where('subject_id', $user->id))->latest()->get()`. Model `ActivityLog` sudah punya kolom `user_id` (siapa yang melakukan) dan `subject_type`/`subject_id` (polymorphic, entity yang kena aksi) — jadi query ini nggak butuh migration baru, datanya udah ada.

Hasil akhirnya: 1 halaman detail akun = CRUD (edit/reset password/nonaktifkan dari AU.6 di atas) + riwayat proyek/payroll (sudah ada) + aktivitas akun ini (baru) — semua dalam 1 tempat pas Super Admin klik masuk dari hasil search, persis yang diminta.

## AU.7: Client (Greenlight) — KOREKSI: Gallery Grid Sudah Ada, Cuma Kurang Filter Demografis

Blueprint asli bilang "bikin Clean Gallery Grid, minim teks, tombol Setuju/Tidak Cocok" seolah ini fitur baru. **INI SUDAH ADA** — `cd/reviews/show.blade.php` sudah punya grid kartu foto-first (`auto-fill, minmax(160px,1fr)`), filter tab status, bulk-select + bulk reject, dan modal detail kandidat dengan Approve/Reject + pilih Grade. Jangan bangun ulang halaman ini. Yang GENUINELY belum ada dan worth ditambah: **filter demografis di sidebar kiri** (gender, rentang usia, warna kulit, ukuran baju) di atas grid yang sudah ada — mirip filter e-commerce, query tambahan ke `applicants` yang sudah difilter status.

## AU.8: Korlap (Absensi Lapangan) — KOREKSI: Big-Touch Sudah Ada, QR Scanner Itu FITUR BARU Terpisah

Blueprint asli bilang butuh "high contrast, big touch target, hindari form teks panjang, pakai QR scanner buat check-in massal" seolah kondisi sekarang tabel form teks. **SEBAGIAN SUDAH ADA** — `admin/attendance/index.blade.php` sudah pakai tombol besar Hadir/Tidak Hadir per kandidat, foto capture native kamera (`capture="environment"`, langsung buka kamera HP bukan file picker biasa), dan dialog reject dengan alasan wajib. Kalau mau, boleh naikin sedikit font-size/contrast buat kondisi outdoor, itu polish CSS ringan, silakan jalan.

**TAPI QR-code buat bulk check-in itu FITUR BARU, BUKAN polish UI** — butuh generate QR unik per Extras, UI scanner (akses kamera browser), dan alur baru "scan → auto-attendance". Ini beda kelas effort dari sekadar redesign tampilan. **JANGAN masukin diam-diam ke task polish UI ini** — kalau Fakrul mau fitur ini, harus jadi item terpisah yang dikonfirmasi dulu (sama kayak keputusan notifikasi in-app di Bagian AT.5, harus jelas dulu scope-nya sebelum dikerjain).

## AU.10: Layout/Experience Audit — Penempatan Elemen, Bukan Cuma "Fiturnya Ada"

Fakrul klarifikasi: bukan soal fitur udah ada atau belum, tapi soal PENEMPATAN & ALUR-nya enak dipakai atau nggak. Ini hasil audit layout konkret per halaman (bukan cuma nambah item baru, tapi reorder/reposisi yang sudah ada):

**Dashboard Super Admin** — ketemu gap konkret: CSS utility `.dashboard-grid-2col.is-wide-narrow` / `.is-even` UDAH ADA di `shared.css`/`app.blade.php` (buat grid 2 kolom, chart besar di kiri + panel di kanan), TAPI `super-admin/dashboard.blade.php` SEKARANG SAMA SEKALI NGGAK PAKAI class ini — semua section (Proyek Perlu Ditindak, metric cards, margin card, tabel honor, kalender jadwal) ditumpuk vertikal full-width satu-satu. Di layar desktop ini boros scroll padahal ada CSS grid yang nganggur. Perbaikan penempatan: pasangin "Jadwal Shooting Bulan Ini" sejajar (kolom kanan, `is-wide-narrow`) sama "Admin & Staff Honor" (kolom kiri) jadi 1 baris, bukan ditumpuk — kalender jadi lebih gampang dilirik tanpa scroll ke paling bawah. Juga pertimbangkan naikin urutan kalender jadwal lebih ke atas (dekat "Proyek Perlu Ditindak") karena "kapan syuting berikutnya" itu lebih actionable buat Super Admin dibanding tabel honor yang sifatnya referensi finansial.

**Client (Greenlight)** — grid kartu sekarang (`minmax(160px,1fr)`) cuma nampilin foto+nama, semua atribut lain (usia, karakter, grade admin) baru keliatan setelah klik buka modal. Buat Client yang review puluhan kandidat berturut-turut, itu banyak klik buka-tutup modal buat hal basic. Perbaikan penempatan: tambah 1-2 baris info ringkas di BAWAH thumbnail foto langsung di kartu (misal karakter yang dilamar + usia), biar screening awal bisa dilakuin tanpa buka modal sama sekali — modal cuma dibuka pas beneran mau lihat detail/approve. Juga: kartu dengan status "Menunggu" (butuh tindakan) sebaiknya divisualkan beda (border lebih tegas/nempel di atas urutan grid) dibanding yang udah "Approved"/"Rejected", biar mata Client otomatis ke yang masih perlu diputusin duluan — bukan semua kartu keliatan sama rata di grid "Semua".

**Korlap (Absensi Lapangan)** — dicek `AttendanceController`, query `$applicants` SEKARANG NGGAK ADA `orderBy` sama sekali (urutan default/id). Di lapangan pas hari syuting rame, Korlap harus scroll manual nyari nama yang mau diabsen di antara puluhan kandidat tanpa urutan yang membantu. Perbaikan penempatan: urutkan berdasarkan `jam_callingan` (yang paling deket waktunya duluan) BUKAN urutan id, dan/atau kelompokkan "Belum Diabsen" di atas, "Sudah Diabsen" di collapse/bawah — biar Korlap fokus ke sisa kerjaan, bukan scroll ngelewatin yang udah beres. Tambah juga search-by-nama sticky di atas list (input kecil, filter client-side JS, nggak perlu reload) buat kasus butuh cari 1 nama spesifik cepat.

**Kelola Akun** — sudah diganti jadi revisi final di AU.6 di atas (search-first + filter dropdown + pagination + CRUD edit), item ini dianggap selesai dibahas di sana, tidak ada instruksi tambahan di sini.

## AU.9: Reusable Blade Components (Opsional, Low Priority)

Blueprint minta pecah UI jadi component (`<x-metric-card>`, `<x-entity-card>`, `<x-status-badge>`) biar konsisten Admin↔Client. Ide bagus buat maintainability jangka panjang, tapi project ada 71 file Blade — refactor penuh ke component itu effort besar. **Jangan jadi prioritas** dibanding AU.2-AU.8 di atas. Kalau ada waktu sisa, mulai dari yang paling sering dipakai berulang dulu (`<x-status-badge>` buat badge aktif/pending/tolak yang literally di-copy paste style inline di banyak file), bukan sekaligus semua.

## Checklist AU

- [ ] AU.1: Tidak ada kerjaan — guardrail, jangan ubah warna state/breakpoint 860px pas kerjain AU lain.
- [ ] AU.2: Kebab menu aksi sekunder di entity-card Proyek Casting.
- [ ] AU.3: Negosiasi Fee jadi timeline/chat-bubble + sticky action bar (logic/route TETAP SAMA).
- [ ] AU.4: Cek `applicants.blade.php` dulu — samakan gaya jika sudah mirip `cd/reviews/show.blade.php`, jangan hilangkan alasan-tolak wajib.
- [ ] AU.5: Chart Dashboard SA pakai Chart.js (bar utk margin bulanan, doughnut utk status proyek) — TIDAK pakai ApexCharts.
- [ ] AU.6: Kelola Akun revisi final — search bar dominan, filter role+status jadi 1 dropdown "Filter", bulk-toolbar sticky (sudah hidden-by-default, tinggal sticky), aksi per-baris ke kebab menu, **tambah `update()` buat CRUD Edit (nama/email/role)**, pagination 15/halaman.
- [ ] AU.6.7: Tambah section "Aktivitas Akun Ini" di `super-admin.admins.show` (query `ActivityLog` filter `user_id`/`subject_id`) — Monitoring & Log Aktivitas GLOBAL tetap terpisah, cuma detail-per-akun yang digabung.
- [ ] AU.7: Tambah filter demografis (gender/usia/warna kulit/ukuran baju) di halaman Greenlight Client — grid & bulk action yang sudah ada JANGAN dibangun ulang.
- [ ] AU.8: Polish kontras/font-size absensi Korlap (opsional). QR scanner check-in DITUNDA — perlu konfirmasi Fakrul dulu sebagai fitur terpisah, bukan bagian task ini.
- [ ] AU.9: Low priority — mulai dari `<x-status-badge>` doang kalau ada waktu sisa, bukan refactor total 71 file.
- [ ] AU.10.1: Dashboard SA — pakai `.dashboard-grid-2col.is-wide-narrow` biar Jadwal Shooting & Honor Admin sejajar 1 baris, bukan ditumpuk; pertimbangkan naikin posisi kalender lebih ke atas.
- [ ] AU.10.2: Client (Greenlight) — tambah info ringkas (karakter/usia) di bawah thumbnail kartu biar screening nggak wajib buka modal; visual differentiation buat kartu status "Menunggu" di grid "Semua".
- [ ] AU.10.3: Korlap (Absensi) — `AttendanceController` tambah `orderBy('jam_callingan')` atau grouping belum/sudah diabsen; tambah search-by-nama sticky client-side.
- [ ] AU.10.4: DIHAPUS — sudah digabung penuh ke AU.6 (revisi final), jangan dikerjain terpisah.

# Bagian AV: Rekap Margin → "Penggajian & Keuangan" (Perluasan Scope, 23 September 2026)

Fakrul minta "Rekap Margin" diperluas jadi halaman lengkap soal penggajian/keuangan, bukan cuma margin doang. Dicek dulu data yang sudah ada sebelum desain halamannya, karena ternyata scope-nya nggak rata — ada bagian yang tinggal disatuin, ada 1 bagian yang beneran butuh kerjaan backend baru.

## AV.1: Inventarisasi Data yang Sudah Ada

- **Margin per proyek** (`RecapController`, fee client vs payout extras) — SUDAH ADA, ini yang sekarang namanya "Rekap Margin".
- **Honor Extras** (`Payment` model, status `ditransfer`/`dikonfirmasi_diterima`) — SUDAH ADA status tracking lengkap per Bagian sebelumnya.
- **Honor Staff Admin/Korlap** (`StaffPayroll` model: `nominal_pokok` + `addons` + `pdf_slip_path` + `generated_at`) — datanya ADA, TAPI **tidak ada status bayar sama sekali**. `generated_at` cuma nyatet kapan slip PDF dibikin, BUKAN kapan honornya beneran ditransfer ke staf. Ini gap nyata — sekarang nggak ada cara buat tau "staf ini udah dibayar apa belum" selain nanya manual.
- **Invoice Client** (`InvoiceController`) — uang masuk dari client, sudah jadi modul sendiri.

## AV.2: Struktur Halaman Baru

Rename menu sidebar "Rekap Margin" → **"Penggajian & Keuangan"** (Super Admin & Admin). Halaman jadi beberapa section/tab dalam 1 halaman:
1. **Ringkasan Margin per Proyek** — reuse `RecapController` yang sudah ada, tidak diubah logicnya.
2. **Honor Staf (Admin/Korlap)** — list `StaffPayroll` per staf/proyek dengan status bayar (baru, lihat AV.3).
3. **Honor Extras** — reuse status `Payment` yang sudah ada, ditampilkan ringkas di sini juga (bukan cuma di halaman Payment masing-masing proyek).
4. **Invoice Client** — ringkasan/link ke `InvoiceController` yang sudah ada (uang masuk, biar 1 halaman ini beneran gambaran keuangan 2 arah: masuk dari client, keluar ke staf/extras).

## AV.3: Backend Baru yang Genuinely Dibutuhin

Tambah kolom status bayar ke `StaffPayroll` (migration: `status_bayar` enum `belum`/`sudah`, atau `dibayar_at` nullable timestamp — pilih salah satu, konsisten sama pola `Payment` yang sudah pakai status string) + endpoint "Tandai Sudah Dibayar" (aksi Admin/Super Admin). Tanpa ini, section "Honor Staf" di halaman baru cuma bisa nampilin nominal tanpa status, nggak beda dari sekarang.

## AV.4: Batasan — Ini BUKAN Laporan Keuangan Perusahaan Penuh

"Semua keuangan perusahaan" secara harfiah (biaya operasional, sewa kantor, gaji tetap non-proyek, dst di luar aktivitas casting) **tidak ada datanya di sistem sama sekali** — tidak ada tabel expense/biaya operasional. Halaman ini HANYA bisa mencakup keuangan yang terkait proyek casting (margin, honor staf, honor extras, invoice client) — bukan P&L perusahaan penuh. Kalau Fakrul memang mau tracking biaya operasional di luar proyek, itu modul benar-benar baru (expense tracking + kategori biaya), effort-nya jauh lebih besar dari sekadar rename+gabung halaman ini — perlu dikonfirmasi terpisah kalau memang diinginkan, jangan diam-diam dianggap termasuk di sini.

## AV.5: Konfirmasi Scope Final (23 September 2026)

Fakrul dikonfirmasi via pilihan eksplisit: **halaman ini tetap 3 arus uang** — (1) Invoice Client → JBTB, (2) JBTB → Honor Extras, (3) JBTB → Honor Staf Admin/Korlap. AV.3 (migration `status_bayar` di `StaffPayroll` + endpoint tandai-dibayar) TETAP dikerjakan sebagai prasyarat, BUKAN di-drop. Batasan AV.4 soal expense operasional non-proyek (sewa kantor, dst) tetap berlaku — itu di luar 3 arus ini dan tetap tidak masuk scope.

## AV.6: Single Source of Truth — Chart Keuangan di Dashboard Harus Pakai Query yang Sama

Fakrul minta: kalau nanti ada chart/angka "keuangan" di Dashboard (Super Admin maupun Admin), datanya HARUS ambil dari query/service yang SAMA PERSIS dengan yang dipakai halaman "Penggajian & Keuangan" ini — bukan dihitung ulang terpisah dengan logic sendiri di `DashboardController`. Ini persis buat MENCEGAH bug yang udah kejadian di Bagian AS.2 ("Extras Aktif" beda angka antara Dashboard vs Monitoring karena 2 controller punya query beda buat label yang sama).

Implementasi: taruh logic hitung margin/honor/invoice di 1 service class (misal `App\Services\KeuanganService` atau method static di model terkait), dipanggil BARENG oleh `RecapController` (halaman Penggajian & Keuangan) DAN `SuperAdmin\DashboardController`/`Admin\DashboardController` (card "Margin Bulan Ini" dari AT.2, chart bar dari AT.5) — satu sumber angka, ditampilkan di 2 tempat beda, bukan 2 sumber angka yang kebetulan sama makna tapi beda hitungannya.

## Checklist AV

- [ ] AV.2: Rename menu + halaman "Rekap Margin" jadi "Penggajian & Keuangan" dengan 3 section (Honor Staf, Honor Extras, Invoice Client) + ringkasan margin.
- [ ] AV.3: Migration `status_bayar`/`dibayar_at` di `StaffPayroll` + endpoint tandai-dibayar. **Prasyarat sebelum AV.2 section "Honor Staf" bisa nampilin status — kerjain duluan. DIKONFIRMASI TETAP DIKERJAKAN (lihat AV.5), bukan opsional.**
- [ ] AV.4: Batasan expense operasional non-proyek tetap di luar scope (dikonfirmasi Fakrul, bukan asumsi sepihak).
- [ ] AV.6: Ekstrak logic hitung margin/honor/invoice ke 1 service/method bersama, dipakai baik oleh halaman Penggajian & Keuangan maupun card/chart Dashboard (AT.2/AT.5) — cegah duplikasi query yang bisa menghasilkan angka beda buat label sama.

# Bagian AW: Rapikan Monitoring Akun (23 September 2026, lanjutan)

> Sudah dicek langsung ke `super-admin/monitoring.blade.php` dan `partials/application-progress.blade.php` sebelum nulis ini — bukan tebakan.

## AW.1: Sederhanakan Step-Bar Partisipasi — 9 Tahap Jadi 6 Label

`partials/application-progress.blade.php` sekarang punya 9 tahap linier (`diajukan, direview_admin, nego_fee, deal, diajukan_ke_cd, direview_cd, lolos, kontrak_ditandatangani, selesai_produksi`) + 2 cabang stop (`ditolak`, `dibatalkan`). Fakrul benar — beberapa pasangan itu sebenarnya 1 aksi/kejadian yang kepisah jadi 2 bar. Gabung jadi 6 label sesuai urutan yang diminta:

1. **Ajuan/Antrian Extras** = gabung `diajukan` + `direview_admin` (extras nunggu di-review, itu 1 fase nunggu, bukan 2 tahap beda).
2. **Deal Nego Fee** = gabung `nego_fee` + `deal` (proses nego sampai deal itu 1 alur kejadian, `deal` adalah hasil akhirnya bukan tahap terpisah).
3. **Dipilih/Ditolak Client** = gabung `diajukan_ke_cd` + `direview_cd` + `lolos` (submit ke client sampai direview itu 1 fase nunggu keputusan; `lolos` jadi penanda "dipilih").
4. **Kontrak** = `kontrak_ditandatangani` (tidak berubah, cuma rename label).
5. **Batal Ikut Serta** = `dibatalkan` — **asumsi manager-session**: tetap jadi cabang stop-state (kotak merah `step-bar-stopped`) seperti sekarang, BUKAN inline di linear bar, karena ini kejadian keluar-alur bukan progress maju. Kalau maksud Fakrul beda (mau ditampilkan inline di antara Kontrak dan Selesai walau nggak selalu kejadian), koreksi sebelum Claude Code eksekusi.
6. **Selesai** = `selesai_produksi` (tidak berubah, cuma rename label).

`ditolak` (gagal seleksi, beda dari `dibatalkan`) TIDAK disebut eksplisit di 6 label Fakrul — default-nya tetap jadi cabang stop-state terpisah kayak sekarang (`step-bar-stopped`, pesan "Tidak lolos seleksi"), cuma labelnya nggak perlu masuk urutan 6 tahap karena itu memang bukan tahap maju.

**Ini CUMA UI/label**, `status_partisipasi` di database TIDAK diubah (masih 9 value asli) — cuma `$urutanStep` di view dipetakan ulang jadi 6 kelompok buat ditampilin, logic backend/controller tidak disentuh.

## AW.2: Hapus Chart "Akun per Role" dari Monitoring

Setuju sama Fakrul — dicek, chart ini (`chartAkunRole`, bar chart jumlah akun per role) itu nilai analitiknya nol: datanya PERSIS sama kayak 3 metric card di atasnya (Extras Aktif, Client/PH, Admin & Korlap), cuma di-plot ulang jadi bar chart. Nggak ada insight baru (5 role, jarang berubah harian, bukan tren). **Hapus section "Akun per Role" beserta canvas & JS Chart.js-nya.** Slot yang kosong di `dashboard-grid-2col.is-even` diisi cuma sama "Penugasan Admin Selesai" (jadi full-width, atau digabung sama section lain — biar Claude Code putuskan layout paling pas, yang penting chart-nya hilang).

## AW.3: Absensi Lapangan — Grup per Proyek Dulu, Klik Baru Muncul List

Sekarang section "Absensi Lapangan (15 Terakhir)" nampilin tabel flat semua record absensi lintas proyek campur jadi satu. Ubah jadi: tampilkan NAMA PROYEK dulu (card/row collapsed, misal "Iklan Kopi Kenangan — 8 absensi terbaru"), diklik baru expand nampilin tabel detail absensi (waktu, Extras, tgl shooting, status, validasi, foto) punya proyek itu. Query `$recentAttendances` di controller (`SuperAdmin\MonitoringController` atau sejenis) di-group by `castingProject` dulu sebelum dikirim ke view.

## AW.4: Link "Kelola Absensi" dari Monitoring — Alias Route Super Admin (Bukan Bug Activity Log)

Fakrul nanya kenapa link "Kelola Absensi" dari Monitoring (Super Admin) malah ke path `/admin/attendance` — dicek `ActivityLog::record()`, field `role` yang dicatat itu diambil dari `$actor?->role` (role user yang BENERAN login), BUKAN dari namespace route yang diakses. Jadi **activity log SUDAH BENAR** tercatat sebagai `super_admin` kalau Super Admin yang buka halaman itu, walau URL-nya `/admin/...` — tidak ada bug di pencatatan aktivitas. TAPI benar bahwa URL-nya bikin bingung optiknya (Super Admin kok masuk ke path Admin). Solusi murah: tambah ROUTE ALIAS `super-admin.attendance.index` yang manggil controller/view yang SAMA PERSIS (jangan duplikat logic), cuma beda prefix URL — biar link dari Monitoring pakai `route('super-admin.attendance.index')`, bukan `route('admin.attendance.index')`.

## AW.5: Gabung Search Extras + Client + Admin Jadi 1 Search Bar

Sekarang ada 2 search terpisah (Extras, Client) side-by-side di `dashboard-grid-2col`. Gabung jadi 1 search bar buat ketiganya (Extras, Client, DAN Admin/Korlap yang belum ada search-nya sama sekali) dengan filter tipe akun di atas/samping field (chip/dropdown: Semua/Extras/Client/Admin). Hasil dibatasi 10 per load dengan scroll internal container (bukan pagination klik-halaman kayak sekarang, karena Fakrul minta scroll) — bisa pakai `max-height` + `overflow-y:auto` di list-nya.

**DIKONFIRMASI Fakrul**: duplikasi fungsi sama Kelola Akun DI SINI GAPAPA, karena action-nya (toggle aktif/nonaktif, reset password, edit, lihat detail) itu sebenernya cuma manggil endpoint yang SAMA PERSIS kayak yang dipakai `AdminManagementController` di Kelola Akun (AU.6) — bukan bikin logic baru, cuma nempel kebab-menu aksi yang sama di hasil search Monitoring ini juga. Jadi "Tampilan read-only" di subtitle halaman ini SUDAH TIDAK BERLAKU LAGI setelah AW.5 dikerjakan — **hapus/ubah kalimat subtitle itu**, karena sekarang Monitoring beneran bisa dipakai buat aksi, bukan cuma read-only.

Implementasi konkret: hasil search (Extras/Client/Admin, gabungan) tiap baris ada kebab-menu (⋮) yang isinya sama kayak di Kelola Akun (AU.6) — Lihat Detail, Edit, Reset Password, Nonaktifkan/Aktifkan — manggil route yang SAMA (`super-admin.admins.update`, `.resetPassword`, `.toggleStatus`, dst dari AU.6), bukan endpoint baru duplikat.

## AW.6: Jadwal Shooting — Pindah ke Atas, Kotak Compact Kayak Dashboard, Verifikasi Hover/Klik

Pindahin section "Jadwal Shooting" dari paling bawah ke bagian atas halaman (setelah header, sebelum/sejajar stat cards) — biar nggak perlu scroll jauh buat liat jadwal terdekat. Bungkus jadi kotak compact `max-width` kecil (~420px) persis kayak yang di Dashboard Super Admin (`<x-jadwal-calendar :events="..." compact />`, sudah ada preset compact-nya, style-nya tinggal disamain).

Soal hover/klik munculin detail agenda — **sudah dicek kodenya, komponen `jadwal-calendar.blade.php` SEHARUSNYA sudah support ini** (`onclick`+`onmouseenter` manggil `calClick()`, ada `#cal-detail-{calId}` panel yang di-render bahkan di mode compact, line 124-128). Tapi Fakrul bilang "belum ada" pas dicoba — jadi **WAJIB verifikasi live di browser dulu** sebelum nyimpulin ini works atau beneran bug (kemungkinan: CSS compact bikin panel ke-hide visual, atau ada JS error, atau Fakrul belum lihat karena section-nya kebawah/gak kelihatan). Kalau ternyata beneran nggak muncul pas ditest live, baru debug JS/CSS-nya, jangan asumsi "kodenya kelihatan benar jadi pasti jalan".

## Checklist AW

- [ ] AW.1: Gabung step-bar 9 tahap jadi 6 label sesuai mapping di atas — cek dulu asumsi soal "Batal Ikut Serta" (stop-branch, bukan inline) ke Fakrul kalau ragu.
- [ ] AW.2: Hapus chart "Akun per Role" dari Monitoring (redundan sama metric card).
- [ ] AW.3: Group Absensi Lapangan per nama proyek dulu (collapsed), klik buat expand list detail.
- [ ] AW.4: Tambah route alias `super-admin.attendance.index` (reuse controller/view Admin) buat link "Kelola Absensi" dari Monitoring — bukan fix bug (activity log sudah benar), murni konsistensi URL.
- [ ] AW.5: Gabung search Extras+Client+Admin jadi 1 search bar + filter tipe akun, limit 10 + scroll, kebab-menu aksi (Edit/Reset Password/Nonaktifkan/Lihat Detail) langsung di hasil search — reuse route yang sama kayak Kelola Akun (AU.6), JANGAN bikin endpoint duplikat. Hapus/ubah kalimat subtitle "read-only" karena udah nggak akurat lagi.
- [ ] AW.6: Pindah Jadwal Shooting ke atas halaman, kotak compact kayak Dashboard, **verifikasi live** hover/klik nampilin detail agenda sebelum dianggap selesai.

## AW.7: Penugasan Admin — Sejajar Jadwal, Isinya Diringkas (24 September 2026)

> **Verifikasi terlebih dulu (24 September 2026)**: dicek langsung ke `super-admin/monitoring.blade.php` dan `partials/application-progress.blade.php` — AW.1 s.d. AW.6 SEMUA SUDAH DIKERJAKAN Claude Code dengan benar (jadwal sudah di atas, chart akun-per-role sudah hilang, absensi sudah grouped per proyek pakai `<details>`, search sudah unified 3-role dengan kebab-menu full CRUD, step-bar sudah 6 label). Kalau Fakrul masih lihat tampilan lama, itu kemungkinan besar cache browser/view Laravel, BUKAN kerjaan yang belum jalan — coba hard refresh + `php artisan view:clear` sebelum lapor "belum berubah" lagi.

Card "Penugasan Admin Selesai" (baris setelah stat cards di `monitoring.blade.php`, sekarang full-width sendirian) diminta:

1. **Ditaruh sejajar/di samping Jadwal Shooting** (bukan di bawah stat cards seperti sekarang) — pakai `dashboard-grid-2col.is-even` (kelas yang sudah ada di `shared.css`, dipakai juga di dashboard SA), kolom kiri Jadwal, kolom kanan Penugasan Admin, biar sejajar jadi 1 baris compact di atas.
2. **Isinya diringkas** — sekarang ada 2 lapis: (a) % selesai + progress bar, (b) sub-section "Tahapan Partisipasi" nampilin SEMUA 5 tahap funnel (`funnel-steps`, tiap tahap 1 baris label+track+angka). Itu kepanjangan buat muat sejajar sama kalender compact yang cuma ~200px tinggi. **Potong bagian (b)** — funnel 5-tahap ini terlalu detail buat overview page, lebih cocok jadi laporan tersendiri kalau dibutuhin nanti (bukan dihapus datanya, cuma jangan ditampilin di sini). Sisain cuma: % selesai + progress bar + 1 baris teks ringkas ("X dari Y penugasan selesai") — itu aja, biar card-nya proporsional sejajar sama kalender compact.

## Checklist AW.7

- [ ] AW.7.1: Card Penugasan Admin dipindah sejajar Jadwal pakai `dashboard-grid-2col.is-even`.
- [ ] AW.7.2: Hapus sub-section "Tahapan Partisipasi" (funnel 5-tahap) dari card ini, sisain cuma %+progress bar+1 baris ringkas.

---

# Bagian AX: Bug Nyata — Jadwal Shooting "Hilang" di Monitoring (24 September 2026)

> Fakrul lapor setelah AW.7: "udh bagus pindah tpi malah ilang datanya". Sudah dicek ulang `MonitoringController@index`, `super-admin/monitoring.blade.php`, dan `components/jadwal-calendar.blade.php` — **ini bug beneran, bukan cache**, dan bukan disebabkan AW.7 (posisi card), tapi side-effect dari AW.6 (mode compact).

## AX.1: Root Cause

`MonitoringController@index` ngirim `$shootingDates` = SEMUA jadwal shooting lintas SEMUA proyek, tanpa filter bulan sama sekali (`EventShootingDate::orderBy('tanggal')->get()`, nggak ada `whereMonth`/`whereYear`). Ini beda dari 4 dashboard lain (`admin`, `extras`, `cd`, `super-admin/dashboard`) yang semuanya ngirim `$jadwalBulanIni` — data yang emang udah difilter cuma bulan berjalan.

Komponen `<x-jadwal-calendar compact>` (`components/jadwal-calendar.blade.php` baris 3-5 & 68-79) di mode compact **hardcode ke `$now`** (bulan berjalan) dan **nggak render tombol prev/next** — cuma label bulan statis. Ini cocok buat 4 dashboard lain (datanya emang cuma 1 bulan itu), tapi di Monitoring jadi bug: kalau ada jadwal shooting bulan depan/kemarin, itu jadwal SAMA SEKALI nggak muncul di grid, dan nggak ada cara buat pindah bulan buat ngeliatnya. Itu yang keliatan sebagai "data ilang" — bukan query-nya salah, datanya ada, cuma UI compact-nya ngunci ke 1 bulan doang padahal sumber datanya lintas-bulan.

## AX.2: Fix

Tambah prop opsional `navigable` (default `false`) ke `jadwal-calendar.blade.php`:

- Kalau `compact` DAN `navigable`, tetap render ukuran compact TAPI pakai nav row yang sama kayak mode non-compact (baca `?bulan=` dari query string, bukan hardcode `$now`).
- 4 dashboard lain (`admin`, `extras`, `cd`, `super-admin/dashboard`) TETAP tanpa `navigable` — behavior mereka nggak berubah, karena datanya emang scoped 1 bulan.
- Di `super-admin/monitoring.blade.php`, ubah baris `<x-jadwal-calendar :events="$shootingDates" compact />` jadi `<x-jadwal-calendar :events="$shootingDates" compact navigable />`.

Ini minimal diff — nggak nyentuh controller (query udah benar, semua jadwal emang harus dikirim), nggak nyentuh 4 halaman dashboard lain, cuma nambah 1 prop + sedikit logic nav di komponen yang udah ada.

## Checklist AX

- [ ] AX.2.1: Tambah prop `navigable` di `jadwal-calendar.blade.php`, compact+navigable pakai nav prev/next baca `?bulan=` (bukan hardcode `$now`).
- [ ] AX.2.2: Pastikan 4 usage lain (`admin/dashboard`, `extras/dashboard`, `cd/dashboard`, `super-admin/dashboard`) TIDAK dikasih `navigable` — behavior mereka harus tetap sama persis kayak sekarang.
- [ ] AX.2.3: Tambah `navigable` ke pemanggilan di `super-admin/monitoring.blade.php`.
- [ ] AX.2.4: Verifikasi live — buat 1 dummy shooting date di bulan lain (misal bulan depan), pastikan muncul setelah klik next di Monitoring.


---

# Bagian AY: Hardening + UI/UX Inklusif + Konsolidasi Design System (28 September 2026)

> **Sumber:** `docs/ARCHITECTURE-REVIEW-2026-09-28.md` (lokal, gitignored) dan `docs/UX-AUDIT-2026-09-28.md`. Baca dua file itu dulu sebelum mulai. Semua lokasi file:line di bawah dari audit statis, bisa geser ±5 baris.
>
> **WAJIB pakai subagent** — nyentuh pembayaran, kontrak, auth, dan >3 file (aturan `CLAUDE.md`).
>
> **Aturan main bagian ini:**
> 1. **Commit dulu** semua perubahan AT–AX yang masih pending SEBELUM mulai AY. Lalu commit per sub-bagian (AY.1, AY.2, dst), bukan satu commit raksasa.
> 2. **AY.1–AY.5 boleh langsung dikerjakan** — murni teknis/UX, nggak butuh keputusan bisnis baru.
> 3. **AY.6 JANGAN dikerjakan** sampai Fakrul nulis hasil meeting tim (D1–D13) di sini. Itu aturan bisnis; kalau ditebak, bakal bentrok antar-role.
> 4. **Jangan centang checklist sendiri.** Claude Code isi kolom "Bukti" (commit hash + cara tes). Yang centang Imanisa (QA) setelah nyoba di ngrok.
> 5. Prinsip `/ponytail`: perbaiki di tempat yang ada, jangan bikin sistem baru. **Nggak ada rewrite massal inline style** — migrasi ke token/komponen cuma di file yang memang disentuh bagian ini.

## AY.1: Bug P0 (teknis, langsung)

1. **`KeuanganService` relasi salah → halaman Keuangan crash.** `daftarHonorStaf()`: `assignment.project` → `assignment.castingProject`. `daftarHonorExtras()`: `application.*` → `projectApplication.*`. Samakan juga di `admin/recap/margin.blade.php` (~baris 174, 231). Tambah 1 feature test yang GET `/admin/rekap-margin` dengan data payroll + payment → 200.
2. **Tab "Invoice Client" di Keuangan baca kolom yang nggak ada** (`total_tagihan`, `nomor_invoice`, `status_pembayaran`, `margin.blade.php:284-292`). Rumus tagihan nunggu keputusan D5, jadi sekarang: **sembunyikan tab itu** dengan catatan di kode `{{-- ditampilkan lagi setelah D5 --}}`. Jangan bikin migration kolom baru.
3. **`ContractController::sign` tanpa guard.** Tolak (422 via `back()->with('error')`) kalau: `contract` null, `voided_at` terisi, `status_partisipasi` bukan `lolos`, atau tanda tangan pihak itu sudah ada. Tambah test: sign setelah `batalkan()` → status tetap `dibatalkan`.
4. **`PaymentController::tandaiTransfer` tanpa guard status.** Hanya boleh dari `belum_dibayar`. Kalau `payment` null → error yang jelas, bukan 500. Tambah test transfer ulang setelah `dikonfirmasi_diterima` → ditolak.
5. **Form bersarang di Kelola Akun.** `super-admin/admins/index.blade.php`: form Reset Password, Edit, Hapus, Restore (~146, 172, 201, 214) ada di dalam `<form id="bulk-form">` (~92–230). Pindahkan semua `<dialog>`/form per-akun ke LUAR bulk-form; checkbox bulk pakai atribut `form="bulk-form"`. Tes manual keempat aksi + bulk action di browser.
6. **`@{{ $person->username }}`** di `super-admin/monitoring.blade.php:140` tampil literal → `{{ '@'.$person->username }}`.
7. **Link homepage 404:** `welcome.blade.php:573` `/extras/projects` → `route('extras.projects.index')`.
8. **Command palette nggak ke-render script-nya:** `<x-command-palette />` dipanggil setelah `@stack('scripts')` (`layouts/app.blade.php` ~575-578) → pindahkan sebelum `@stack`.
9. **Typo** `contracts/show.blade.php:52` "Lanjut to proses pembayaran" → "Lanjut ke proses pembayaran".
10. **`setGrade` mundurin status.** `Admin/ApplicantController` (~41-44): ubah status ke `direview_admin` HANYA kalau status sekarang `diajukan`. Status lain jangan disentuh.
11. **`ajukanFeeAwal` bisa hidupin aplikasi yang sudah ditolak.** Guard: hanya dari `diajukan`/`direview_admin`.
12. **Selfie menimpa absensi yang sudah divalidasi/ditolak Korlap** (`Extras/AttendanceSelfieController` ~41). Kalau `status_validasi` sudah bukan `menunggu`, tolak dengan pesan "Absensi hari ini sudah divalidasi Korlap". Validasi juga `event_shooting_date_id` memang milik proyek aplikasi itu.
13. **`kuotaPenuh()`** (`CastingProject`) jangan hitung aplikasi `ditolak`/`dibatalkan`.
14. **Query status proyek `selesai_produksi`** di `Extras/CastingProjectController:20` — value itu nggak ada di enum proyek. Ganti ke `ditutup` (atau hapus kondisinya kalau nggak kepake).
15. **Dashboard SA "Honor Belum Diproses"** (`SuperAdmin/DashboardController` ~78) ganti ke `status_bayar = 'belum'`, tampilkan dalam Rp, jadikan link ke tab honor staf.

## AY.2: Fondasi UX (Batch 1 audit UX — kena semua role)

1. **Tampilkan `$errors` di layout.** Di `layouts/app.blade.php` bawah flash `session('error')`: blok `@if ($errors->any())` pakai `.alert-danger`, list pesan. Hapus blok duplikat per-halaman kalau jadi dobel.
2. **Bahasa Indonesia.** `APP_LOCALE=id` + `APP_FALLBACK_LOCALE=id` di `.env.example` (Fakrul ubah `.env` sendiri). Buat `lang/id/validation.php` (terjemahan standar) + array `attributes` untuk field yang sering muncul (`setuju_privasi` → "persetujuan kebijakan privasi", `nominal`, `bukti_transfer`, `signature` → "tanda tangan", dst).
3. **`abort(422, ...)` → `back()->with('error', ...)`** di `AttendanceSelfieController:39`, `PaymentController:86,109,133`, dan semua `abort(422` lain di controller yang dipanggil dari form (grep).
4. **Kontras token** di `partials/theme-style.blade.php`: light `--text-muted: #5b6b60`, `--warning: #b45309`; dark `--text-muted: #8a9a90`. Ganti `.alert-info` hardcode `#60a5fa` di `app.blade.php` (~383) jadi token baru `--info` (light `#1d4ed8`, dark `#60a5fa`). Target: semua teks ≥ 4,5:1 di kedua tema.
5. **Fokus keyboard:** hapus `outline:none` tanpa pengganti (~297), tambah `:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }` global.
6. **Tabel di HP:** definisikan `.table-container { overflow-x: auto; }` di layout, bungkus semua `<table>` di view non-PDF yang belum dibungkus.
7. **Chart.js cuma di halaman yang pakai chart:** hapus `<script src=chart.js>` global (~559), pindah ke `@push('scripts')` di view yang punya `<canvas>` (grep `new Chart`). Pin versi Tabler icons (ganti `@latest` ke versi yang sekarang terpasang).

## AY.3: Konsolidasi Design System (minimal, bukan rewrite)

> Kondisi sekarang: token warna sudah ada dan dipakai konsisten, dark/light jalan. Tapi **tipografi, radius, dan spacing belum jadi token** (20 ukuran font berbeda, 10 radius berbeda, ±1.200 inline `style=""`), badge cuma 3 varian sehingga `badge-pending` dipakai buat semua hal, dan `UI-GUIDELINES.md` bilang tombol 48px / accent `#22c55e` padahal kode 44px / `#0f9a4c`. Homepage punya palet sendiri (lime `#b7ff3c`) terpisah dari dashboard (hijau `#15803d`) — itu keputusan brand, lihat AY.6.

1. **Token skala** di `theme-style.blade.php` (`:root`, berlaku dua tema): `--fs-xs: 12px; --fs-sm: 13px; --fs-base: 14px; --fs-md: 16px; --fs-lg: 18px; --fs-xl: 22px;` dan `--radius-sm: 6px; --radius-md: 8px; --radius-lg: 12px;` dan `--space-1: 4px … --space-6: 24px`. Kelas bersama di `app.blade.php` (`.btn`, `.card`, `.badge`, `.metric-*`, `.alert-*`, input) pakai token ini.
2. **Batas bawah ukuran:** tidak ada teks < 12px di UI (label bottom nav 10,5px → 12px, `metric-label` 11px → 12px). Input search `font-size: 16px` (anti auto-zoom iOS).
3. **Badge semantik** (ganti pemakaian `badge-pending` yang bukan status): tambah `.badge-netral` (abu), `.badge-info` (biru, "sedang diproses"), pertahankan `.badge-aktif` (sukses), `.badge-pending` (menunggu tindakan), `.badge-tolak` (gagal/batal). Role user pakai `.badge-netral`, bukan kuning.
4. **Satu sumber label status/role** (Indonesia): konstanta `LABELS` di `ProjectApplication` (status partisipasi), `Payment` (status bayar), `User` (role), `CastingProject` (status proyek), plus method `label()`/`badgeClass()`. Semua view yang nampilin enum mentah pakai ini — minimal: `payments/show`, `admin/projects/applicants`, `admin/recap/margin`, `super-admin/admins/index`, `super-admin/monitoring`, `invoices/index-client`, `extras/dashboard`, `extras/projects/index`. Activity log: tampilkan deskripsi manusiawi + role label, kode aksi pindah ke `title`/tooltip, JSON parameter ke `<details>`.
5. **Komponen Blade baru, cuma 2:** `<x-status-badge :model="$app" />` (pakai `label()`+`badgeClass()`) dan `<x-confirm-form action=... method=... :message="..." >` (form + `onsubmit="return confirm(...)"` + disable tombol & teks "Memproses…" setelah submit, anti double-submit). Pakai di tempat yang disentuh AY.4.
6. **Update `docs/UI-GUIDELINES.md`** supaya sesuai kode: tombol 44px (`.btn`), `.btn-sm` 32px **hanya aksi sekunder desktop**, nilai accent aktual, token skala baru, daftar badge semantik, aturan "status selalu teks + warna, bukan warna doang".

## AY.4: Aksi Aman & Ergonomi (Batch 2 audit UX)

1. **Konfirmasi + nominal di tombol** (pakai `<x-confirm-form>`): Extras "Terima Rp X" (`extras/negotiations/show` ~51), Admin "Terima Rp X untuk {nama}" + "Ajukan ke Client" (`admin/negotiations/show` ~39-68), "Simpan Tanda Tangan" (`contracts/show` ~45), "Konfirmasi Sudah Terima" (`payments/show` ~56), "Tandai Sudah Ditransfer" (`payments/show` ~35), "Reject Terpilih" massal Greenlight (`cd/reviews/show` ~234), hapus foto galeri (`extras/profile-edit` ~132), toggle status akun (`admin/users/index` ~39,129).
2. **"Hentikan Negosiasi"** Admin pindah ke `<dialog>` dengan alasan wajib, dan dipisah jarak dari tombol Terima/Counter.
3. **Target sentuh 44px** (`.btn`, bukan `.btn-sm`) untuk: CTA utama tiap kartu di `extras/dashboard` (~159-163), semua aksi di `admin/attendance/index` (~104-137). "Hadir" dan "Tidak Hadir" dikasih jarak ≥12px; "Tidak Hadir" lewat konfirmasi.
4. **`aria-label`** di semua tombol ikon-saja: tutup lightbox (`partials/foto-lightbox` ~23, sekalian jadi 44px), tutup modal Greenlight, hapus tautan `&times;`, kebab menu proyek, share/copy profil, tombol power akun. Tombol destruktif ikon-saja dikasih teks juga.
5. **Elemen klik yang bukan tombol:** kartu kandidat Greenlight (`<div onclick>`) → `<button type="button">`; hari kalender di `jadwal-calendar` → `<button>` (hover tetap, tapi tap/Enter juga buka detail); `<tr onclick>` di margin → link di sel nama.
6. **Halaman pembayaran Admin** tampilkan rekening Extras + total (fee_final + add-on) sebelum tombol transfer. **Halaman pembayaran Extras** tampilkan bukti transfer (link stream) + `ditransfer_at`.
7. **Invoice Client:** tampilkan rincian (peran × jumlah × fee = total) di atas kotak TTD — rumusnya ikut yang dipakai PDF invoice SEKARANG, beri catatan kecil "rincian final mengikuti kesepakatan" sampai D5 diputuskan.
8. **Signature pad:** kanvas `width:100%`, tinggi 180px, teks petunjuk "Tanda tangan di dalam kotak", tap sekali menggambar titik, tombol "Ulangi" jelas.
9. **Error lokal:** `profile-edit` tautan tambahan dirender ulang dari `old()` kalau validasi gagal (~229); label pakai `for`/`id`.

## AY.5: Nutup Lubang Komunikasi (Batch 3 audit UX)

1. **Notifikasi bisa diklik:** tambah `url` di payload `InAppNotification`, item lonceng dibungkus `<a>`; semua pemanggil yang sudah ada kirim URL halaman terkait.
2. **Extras — detail lowongan:** tampilkan `kriteria` per peran dan kota lokasi (`extras/projects/show`, `public/event`). **Honor per peran: nunggu D6-bis di AY.6** (boleh tampil atau rahasia?), jangan tampilkan dulu.
3. **Extras — apply:** panggil `menerimaPendaftaran()` di `apply()`; sembunyikan tombol "Daftar" di proyek yang tutup/penuh/lewat deadline; kalau profil belum lengkap (foto utama + data fisik wajib), arahkan ke lengkapi profil dengan pesan jelas.
4. **Extras — upload:** progress persen (`xhr.upload.onprogress`), cek ukuran sebelum kirim, resize foto >2000px via canvas sebelum upload.
5. **Korlap — absensi:** header ringkas "X/Y hadir · Z menunggu"; urutkan `menunggu` di atas; redirect `back()->withFragment('app-'.$id)` di store/validasi/tolak; search 16px + `<label>`, cari nama & username; dropdown proyek cuma yang punya jadwal ≥ kemarin; batas foto 10MB; tombol disable + "Mengunggah…". Kalender compact di dashboard Korlap.
6. **Admin — Lineup:** chip filter per status partisipasi, `paginate(30)`, checkbox bulk grade/bulk tolak (bulk tolak tetap wajib alasan), redirect ke `#app-{id}`. Kartu metrik dashboard Admin jadi link ke list terfilter (termasuk "Perlu Dinego").
7. **Client — dashboard:** kartu "Pengajuan Anda" + status (menunggu ACC / disetujui / ditolak + alasan); empty state + CTA "Ajukan Proyek Pertama"; hapus kartu "Pembayaran Pending" (itu honor Extras, bukan urusan Client); modal Greenlight: foto ≥320px, legend Grade A/B/C, teks "Riwayat dengan Client ini" → "Riwayat Anda dengan talent ini"; status setelah Approved dipecah (Kontrak / Syuting / Selesai) pakai label AY.3.4; "Input Jadwal" → "Lengkapi Jadwal" + helper "Tanggal diisi JBTB".
8. **Super Admin — ACC:** tampilkan brief, kuota, deadline di `<details>` sebelum tombol; tombol Tolak pakai dialog alasan wajib; kirim notifikasi ACC/tolak (+ alasan) ke **Client pengaju** (`diajukan_oleh_client_id`). Subjudul dashboard hapus kata "read-only"; label periode di tiap kartu; sumbu chart margin format Rp; grid chart pakai `.dashboard-grid-2col`.
9. **Istilah:** satukan nama halaman absensi jadi **"Absensi Lapangan"** di sidebar Admin, sidebar Korlap, judul, dan tombol; "Manajemen Proyek"/"Kelola Proyek Casting" → **"Kelola Proyek"**; "Validated" → "Tervalidasi"; pesan "diajukan ke Casting Director" → "diajukan ke Client"; istilah internal "Super Admin" di form pengajuan Client → "tim JBTB".

## AY.6: BLOCKED — nunggu keputusan meeting tim (D1–D13)

> **Claude Code: JANGAN kerjakan apa pun di bawah ini.** Fakrul akan menulis keputusannya di kolom "Keputusan" setelah meeting. Baru setelah terisi, bagian ini jadi task.

| Keputusan | Yang berubah di kode kalau sudah diputus | Keputusan |
|---|---|---|
| D1 PIC proyek | Field PIC wajib saat ACC; notifikasi nego/ACC ke PIC; scoping aksi Admin per proyek (atau tetap global) | **Diputus 29 Sept:** wajib 1 Admin PIC + 1 Client akun (BD.2). Scoping aksi Admin tetap global. |
| D2 Siapa boleh jadi Client | `/register/casting-director`: undangan/token atau status `pending` + ACC | **Diputus 29 Sept (BD.1):** akun Client dibuat Super Admin, registrasi publik ditutup (redirect ke login). |
| D3 Client lihat talent siapa | `ProfileController::pastikanBolehLihatMedia` dibatasi ke kandidat di proyek yang di-assign ke Client itu | _(kosong)_ |
| D4 Aturan deal fee | `Admin/FeeNegotiationController::terima` & `Extras/FeeNegotiationController::terima`: hanya terima tawaran terakhir pihak lawan | _(kosong)_ |
| D5 Basis invoice | Satu rumus di `KeuanganService`, dipakai PDF invoice, tab Invoice Client, dashboard; kolom invoice kalau perlu | _(kosong)_ |
| D6 Syarat Extras dibayar | `guardStatusLolos` → kontrak ditandatangani + absensi tervalidasi | _(kosong)_ |
| D6-bis Honor tampil di lowongan? | Tampilkan kisaran honor per peran di detail lowongan (AY.5.2) | _(kosong)_ |
| D7 Add-on Extras | Extras ajukan, Admin approve; kunci setelah ditransfer | _(kosong)_ |
| D8 Kapan "Selesai" | Pisah `selesai_produksi` dari konfirmasi bayar | _(kosong)_ |
| D9 Siapa tandai honor staf dibayar | Route `tandai-dibayar` super_admin only, bukan penerima honor; idempotent | _(kosong)_ |
| D10 Super Admin godmode/monitoring | `User::isAdmin()` / helper otorisasi di Payment/Contract/Invoice controller | **Diputus 29 Sept:** godmode aksi di Admin & Korlap, read-only di Client & Extras (BD.6). |
| D11 Korlap per proyek | Scoping `AttendanceController` ke proyek yang di-assign | _(kosong)_ |
| D12 Feature freeze | Tanggal freeze; fitur AT–AX mana yang masuk Bab 3 | _(kosong)_ |
| D13 Repo public/private | Private, atau scrub history + hapus angka riil di `docs/CLAUDE.md` | _(kosong)_ |
| D16 Syarat "profil lengkap" sebelum daftar | Sekarang ditebak: foto utama + gender + tinggi badan. Tambah/kurangi field (ukuran baju, warna kulit, dll) | _(kosong)_ |
| D17 Lokasi syuting kelihatan kapan? | Sementara disembunyikan lagi sebelum lolos (lihat AZ.1). Kalau mau ditampilkan: cukup kota (butuh kolom `kota`) atau alamat lengkap | _(kosong)_ |
| D18 Alasan "Hentikan Negosiasi" | Tampilkan ke Extras di progress/dashboard, atau cuma internal | _(kosong)_ |
| D19 Nama asli Extras ke Client | Kebuka saat lolos/kontrak, atau tetap username | _(kosong)_ |
| D20 Grade Admin kelihatan Client? | BA.6 sudah sembunyiin dari HTML; putuskan apakah perlu ditampilkan | _(kosong)_ |
| D21 Extras anak-anak | Akun atas nama wali + persetujuan (UU PDP data anak) | _(kosong)_ |
| D22 Tag Look/Etnis di link publik | Rekomendasi: tidak tampil publik, opsional | _(kosong)_ |
| D23 Batal karena jadwal diubah JBTB/Client dihitung batal mendadak? | Default: tidak dihitung (BK.4) | **Diputus Fakrul 30 Sept: DIHITUNG batal mendadak** (semua pembatalan sama, tanpa pengecualian). |
| D15 Palet homepage vs dashboard | Satukan brand (lime vs hijau) atau sahkan sebagai 2 konteks berbeda di UI-GUIDELINES | _(kosong)_ |

## AY.7: Bersih-bersih role lama (teknis, boleh langsung setelah AY.1)

Keputusan 5 role sudah final sejak 21 Sept, jadi ini bukan keputusan baru:
1. Ganti `admin_default` / `casting_director` / `admin_korlap` di **test** ke `admin` / `client` / `korlap` (±35 file). Test harus tetap hijau.
2. Hapus alias role lama dari `routes/web.php`, `CheckRole` (komentar "7 role" juga), `AdminManagementController::store`, dan helper di `User.php` — **setelah** poin 1 hijau.
3. Hapus 6 file sidebar legacy (`sidebar-admin_default`, `_korlap`, `_sosmed`, `_talco`, `sidebar-casting_director`, `sidebar-admin-sub-role` kalau nggak dirujuk).
4. Migration `disengketakan` di SQLite: pastikan test sengketa pembayaran lolos di SQLite (skip ALTER bikin CHECK constraint lama tetap nolak value baru).
5. Jalankan full test **dan** sekali di MySQL lokal, bukan cuma SQLite.

## Checklist AY

| Item | Bukti (diisi Claude Code: commit + cara tes) | QA (dicentang Imanisa) |
|---|---|---|
| AY.1 (1–15) bug P0 | `38465e8` — `php artisan test --filter AyP0GuardsTest` (9 test: keuangan 200, sign setelah batal, transfer ulang, selfie tervalidasi, setGrade, ajukanFeeAwal, kuota, form bersarang). Manual: Kelola Akun 4 aksi per-akun + bulk; buka `/admin/rekap-margin` | [ ] |
| AY.2 (1–7) fondasi UX | `a1c8a40` — set `APP_LOCALE=id` di `.env` + `php artisan config:clear`, submit form kosong → error Indonesia di atas halaman; Tab keyboard → ring fokus; tabel di lebar HP scroll sendiri; chart.js cuma load di halaman chart | [ ] |
| AY.3 (1–6) design system | `4606f9a` — `--filter StatusLabelsTest`; Manual: role abu (bukan kuning) di Kelola Akun/Monitoring, status Lineup berwarna + teks Indonesia, Log Aktivitas tanpa kode mentah; cek dark/light | [ ] |
| AY.4 (1–9) aksi aman & ergonomi | `4d252f3` — `--filter AyAksiAmanTest` + `FeeNegotiationFlowTest`; Manual: tombol uang munculin konfirmasi + nominal, "Hentikan Negosiasi" wajib alasan, TTD di HP 360px, rincian di halaman invoice Client | [ ] |
| AY.5 (1–9) lubang komunikasi | `b42eb11`, `b43d76c` — `--filter "AyKomunikasiTest|CastingProjectApplyTest|OperationalModulesTest"`; Manual: klik notif lonceng, Daftar di lowongan tutup hilang, absensi Korlap X/Y + balik ke kartu, Lineup chip+bulk, ACC/Tolak SA + notif Client. **Perlu `php artisan migrate`** (kolom `alasan_tolak`) | [ ] |
| AY.7 (1–5) role lama | merge `e4bab82` (7a537c9, bea4837, f95ab1f, 6d57df0) — SQLite hijau, `--filter PaymentStatusGateTest` (sengketa tersimpan). **Poin 5 (MySQL) BELUM** — diblokir izin kredensial, perlu dijalankan Fakrul | [ ] |
| AY.6 | **BLOCKED** — tunggu keputusan | — |

**Skenario QA** (HP Android murah, tema terang, di luar ruangan, data seluler): (1) daftar Extras dari nol sampai upload video; (2) Korlap validasi 10 orang berturut-turut; (3) Admin kasih grade 20 pelamar + deal 1 fee; (4) Client baru ajukan proyek lalu Greenlight 5 kandidat; (5) Owner ACC proyek, cek Kelola Akun (4 aksi per-akun) dan halaman Keuangan. Catat di mana **bingung**, bukan cuma di mana error.

---

# Bagian AZ: Tindak Lanjut Review Eksekusi AY (28 September 2026)

> Manager-session sudah cek ulang hasil AY ke kode (bukan cuma baca laporan): 13 commit ada di `origin/main`, relasi `KeuanganService`, guard kontrak/transfer, form bersarang (sekarang pakai `form="bulk-form"`), `@{{`, link homepage, `$errors` di layout, `lang/id`, token kontras, Chart.js per halaman, dan sisa role lama di `app/`, `routes/`, `resources/`, `tests/` — **semua terverifikasi ada**. Jumlah test belum bisa diverifikasi independen (PHP nggak ada di sandbox manager). Sisa di bawah ini kecil.

## AZ.1: Balikin lokasi syuting jadi tersembunyi sebelum lolos (regresi privasi dari AY.5.2)

SPEC AY.5.2 minta "kota lokasi", tapi kolom kota nggak ada, jadi yang tampil alamat lokasi lengkap — di `public/event.blade.php:61-62` (link publik, tanpa login) dan `extras/projects/show.blade.php:14`. Sebelumnya lokasi sengaja baru muncul setelah lolos. Ini salah spec manager, bukan salah eksekusi. **Hapus tampilan lokasi di dua view itu** (kriteria tetap tampil). Keputusan akhirnya di D17.

## AZ.2: Stop track `.claude/settings.local.json`

File izin lokal Claude Code ke-track di repo public sejak `9173daf`. `git rm --cached .claude/settings.local.json`, tambah `.claude/settings.local.json` dan `.claude/worktrees/` ke `.gitignore`, commit.

## AZ.3: Bersihin worktree lama

`git worktree prune` (worktree `agent-a2a1a763fe09e29d8` sudah prunable), hapus branch-nya kalau sudah di-merge.

## AZ.4: Test di MySQL (dijalankan Fakrul, bukan Claude Code)

Buat database terpisah `jbtb_test` (JANGAN pakai `jbtb`), lalu:
`DB_CONNECTION=mysql DB_DATABASE=jbtb_test php artisan test`
Kalau ada yang merah, tempel output-nya ke manager-session. Tujuannya mastiin enum role & status yang udah disempitin beneran jalan di MySQL, bukan cuma di SQLite.

## Checklist AZ

| Item | Bukti | QA |
|---|---|---|
| AZ.1 lokasi disembunyikan | `898993f` — buka `/event/{slug}` & detail lowongan Extras: tanggal tampil, lokasi nggak | [ ] |
| AZ.2 untrack settings.local | `898993f` — `git ls-files .claude` kosong; `.gitignore` + `.claude/worktrees/` | [ ] |
| AZ.3 worktree prune | dilakukan (bukan commit) — `git worktree list` tinggal main. Worktree lama punya 8 file uncommitted → dibackup ke `.claude/_backup-worktree-a2a1a763.patch` (gitignored) sebelum dihapus | [ ] |
| AZ.4 test MySQL (Fakrul) | dijalankan Claude Code ke `jbtb_test` (bukan `jbtb`): awalnya 3 merah (kolom `kriteria` json nolak teks → 500 saat buat proyek; jam `HH:MM:SS`), difix `aafaec4` → 375 passed | [ ] |

---

# Bagian BA: Kartu Extras ala Matchu + Kategori Tag (REVISI 28 Sept — versi ringan)

> **SUPERSEDES draf BA sebelumnya** (yang ada lapis akses di server + fetch detail). Arahan Fakrul: jangan nambah banyak logic — **perbarui layout** kartu ikut pola Matchu, dan **perluas kategori** jadi semacam hashtag yang dipilih Extras sendiri, dipakai buat filter dan % cocok.
>
> Referensi layout: `docs/ui-prototype/07-kartu-extras.html`. Subagent wajib (>3 file). Commit per sub-bagian. Jangan centang checklist sendiri.

## Yang sudah ada (dicek, jangan dibikin ulang)

- Tabel `extras_categories` (`nama` unik) + pivot `extras_category_extras_profile` + relasi `ExtrasProfile::categories()`. Seeder: Anak-anak, Remaja, Dewasa, Orang Tua, Chinese/Tionghoa. Sekarang **cuma Admin** yang ngisi (`updateKategori`).
- Greenlight Client sudah kartu + modal (`cd/reviews/show`); Lineup Admin kartu besar 10 tombol; Data Extras Admin masih tabel.
- `casting_project_classes.kriteria` = teks bebas, belum nyambung ke kategori.

## BA.1: Fix 404 profil (tetap, kecil)

1. Kelola Akun / Monitoring: akun `trashed()` jangan dirender sebagai link profil — badge "Dihapus" + Restore saja.
2. `AdminManagementController::update`: role diganti jadi `extras` → `ExtrasProfile::firstOrCreate(['user_id' => $user->id])`.
3. `showProfile`: profil nggak ada → `back()->with('error', ...)`, bukan 404 polos.

## BA.2: Kategori jadi tag (perubahan data minimal)

1. Migration: tambah kolom `grup` (string, nullable) di `extras_categories`. Isi lewat seeder (`firstOrCreate` by `nama`, update `grup`):
   - **Usia tampilan:** Anak-anak, Remaja, Dewasa muda, Dewasa, Orang Tua, Lansia
   - **Tampilan/Look:** Chinese/Tionghoa, Timur Tengah, Indonesia Timur, Kaukasia/Bule, Melayu, Jawa, Sunda, dst (list final dari Erlina)
   - **Tipe:** Mahasiswa, Pekerja kantoran, Atlet, Berhijab, Bertato, Rambut panjang, dst
   - **Kemampuan:** Naik motor, Nyetir mobil, Berenang, Menari, Bahasa daerah, dst
   - Gender **bukan** tag — sudah ada field `gender`, jangan diduplikasi.
2. **Extras pilih sendiri** di `extras/profile-edit`: section "Tentang Kamu" berisi chip per grup (multi-pilih, tap to toggle, 44px), simpan lewat `categories()->sync()` di `ProfileController::update` (validasi `exists:extras_categories,id`). Extras **nggak bisa bikin tag baru** — daftar tag dikelola Admin (biar nggak ada "dewasa" / "Dewasa" / "org dewasa").
3. Admin tetap bisa koreksi tag (fitur `updateKategori` yang sudah ada).
4. Migration pivot baru `casting_project_class_extras_category` (`casting_project_class_id`, `extras_category_id`). Di form buat/edit proyek, tiap peran bisa pilih **tag yang dicari** (chip yang sama). `kriteria` teks bebas tetap ada buat catatan tambahan.

## BA.3: % Cocok (satu method, tanpa aturan lain)

`ProjectApplication::persenCocok(): ?int` = jumlah tag peran yang dimiliki Extras ÷ jumlah tag peran × 100, dibulatkan. Kalau peran nggak punya tag → `null` (ring nggak ditampilkan). Eager-load `castingProjectClass.categories` + `extras.categories` biar nggak N+1.

## BA.4: Layout kartu (ikut prototype 07)

Satu partial `partials/extras-card.blade.php`, dipakai di Greenlight, Lineup, dan tab Extras di Data Extras:
- Foto 3:4 di atas; badge status kiri atas; **ring % cocok** kanan bawah (kalau ada); checkbox bulk kanan atas khusus Admin.
- `@username`, lalu 3 baris ikon: peran yang dilamar · usia/tinggi/kota · jumlah proyek selesai.
- **Maksimal 3 tag** tampil sebagai chip kecil (`#Dewasa #Berhijab …`), sisanya "+2".
- Dua tombol 44px: "Lihat Profil" + satu aksi utama sesuai status (Admin: Mulai nego / Lanjut nego / Ajukan ke Client / Siapkan kontrak; Client: Pilih). Aksi lain (grade, catatan, apresiasi, tolak, breakdown) pindah ke modal detail yang sudah ada.
- Grid `repeat(auto-fill, minmax(230px, 1fr))`, 1 kolom di < 480px.
- Modal detail: pakai modal yang sudah ada, tambah section tag per grup + rincian cocok ("✅ #Dewasa · ✅ #Berhijab · ⬜ #Naik motor").

## BA.5: Filter pakai tag

Di Greenlight & Lineup: chip filter tag (dari tag yang dicari peran) + urutkan "Paling cocok" (desc `persenCocok`). Filter client-side boleh (data sudah di halaman), kecuali Lineup yang paginate → pakai query `whereHas('extras.categories', ...)`.

## BA.6: Satu hal yang tetap wajib (bukan logic baru, cuma nggak ngirim data)

Di kartu Greenlight **hapus atribut `data-grade-admin`** dan data kontak/nama asli dari HTML Client — atribut `data-*` kebaca di view-source walau nggak ditampilkan. Cukup nggak dirender, nggak perlu mekanisme baru.

## Keputusan (masuk tabel AY.6)

- **D21 — Extras anak-anak:** siapa yang punya akun (orang tua/wali)? UU PDP mewajibkan persetujuan wali untuk data anak. Sampai diputus: tag "Anak-anak" boleh dipilih, tapi registrasi tetap minimal 17 th (akun atas nama wali).
- **D22 — Tag Look/Etnis tampil di link share publik?** Rekomendasi: **nggak** — cuma kelihatan Admin & Client proyek terkait, dan pengisiannya opsional.

## Checklist BA

| Item | Bukti | QA |
|---|---|---|
| BA.1 fix 404 | `fa6ddf2` — `--filter BaProfilFixTest` | [ ] |
| BA.2 tag grup + Extras pilih sendiri + tag per peran | `7725df6` — `--filter BaKategoriTagTest`. **Perlu `php artisan migrate` + `php artisan db:seed --class=ExtrasCategorySeeder`** | [ ] |
| BA.3 persenCocok | `47db3e9` — `--filter PersenCocokTest` (3 tag, punya 2 → 67; tanpa tag → null) | [ ] |
| BA.4 kartu baru di 3 halaman | `d5ee245`, polish `494eaa5` — `--filter BaKartuExtrasTest`; Manual HP: Lineup, Greenlight, Data Extras (kartu 1 kolom, filter dilipat, chip digeser) | [ ] |
| BA.5 filter + urut paling cocok | `41f9edc` — Lineup `?tag[]=&urut=cocok` (server, lintas halaman), Greenlight client-side | [ ] |
| BA.6 hapus data-grade-admin dari HTML Client | `aebf470` — test `assertDontSee('data-grade-admin')` + nama asli/email; Manual: view-source Greenlight sebagai Client | [ ] |

**Tes QA:** Extras isi 5 tag dari HP → Admin bikin peran dengan 3 tag → Lineup nampilin ring % yang bener + filter tag jalan → Client lihat kartu yang sama tanpa grade admin di view-source.

---

# Bagian BB: SEO & Preview Link Share (kecil, 29 September 2026)

> Konteks: tag/kategori (Bagian BA) itu **pencarian internal** (filter + % cocok), bukan SEO. SEO = gimana halaman **publik** tampil di Google dan pas link di-share ke WA. Kondisi sekarang (dicek): `robots.txt` ngizinin semua, nggak ada `meta description`, nggak ada Open Graph, jadi link lowongan yang di-share ke grup WA muncul tanpa judul/gambar, dan **profil Extras publik `/p/extras/*` bisa ke-index Google** (foto talent muncul di Google Image).

## BB.1: Jangan index halaman pribadi

1. `public/extras-profile.blade.php`: `<meta name="robots" content="noindex, nofollow">`. Link tetap bisa dibuka/di-share, cuma nggak masuk Google.
2. `layouts/app.blade.php` & `layouts/auth.blade.php` (semua halaman login/dashboard): `noindex`.
3. `public/robots.txt`: `Disallow: /p/`, `/admin/`, `/super-admin/`, `/cd/`, `/extras/`, `/kontrak/`, `/invoice/`, `/pembayaran/`, `/media/`. (robots.txt bukan pengaman — pengamannya tetap auth — cuma biar crawler sopan nggak nyoba.)

## BB.2: Preview cantik pas di-share ke WA

1. `public/event.blade.php` (link lowongan, paling sering di-share): `og:title` = nama produksi, `og:description` = "Casting {peran} · deadline {tgl} · daftar di JBTB", `og:image` = poster proyek kalau ada (`poster_path`, via URL publik), fallback logo JBTB. `og:type=website`, `og:url`. Kalau pendaftaran ditutup → tetap ada OG tapi judul "Pendaftaran Ditutup". Event page tetap `noindex` (token link, bukan buat Google).
2. `public/extras-profile.blade.php`: `og:title` = "@username di JBTB", `og:image` = foto utama, **tanpa** tag Look/etnis di description (D22).
3. `welcome.blade.php` (homepage — satu-satunya halaman yang memang mau masuk Google): `<title>` yang deskriptif ("JBTB Casting — Agensi Extras & Talent Jakarta" atau versi dari Erlina), `meta description`, OG + `og:image` logo/hero, `<link rel="canonical">`.

## Checklist BB

| Item | Bukti | QA |
|---|---|---|
| BB.1 noindex + robots.txt | `34c6b0d` — `--filter SeoMetaTest`; view-source `/p/extras/{token}` & dashboard → `noindex`; `/robots.txt` | [ ] |
| BB.2 OG tags event, profil, homepage | `34c6b0d` — tempel link event/profil ke opengraph.xyz. **`APP_URL` di `.env` harus URL ngrok** biar `og:image` kebaca dari luar | [ ] |

**Tes QA:** tempel link event & link profil ke chat WA (atau https://www.opengraph.xyz) → muncul judul + gambar. View-source profil publik → ada `noindex`.

---

# Bagian BC: DemoLengkapSeeder — data demo semua alur (29 September 2026)

> **Sumber data: `docs/AKUN-DEMO.md`** (lokal, gitignored). Seeder HARUS sama persis dengan isi file itu (nama, username, email, tahap tiap Extras). Kalau ada yang nggak mungkin dibuat karena enum/constraint, **ubah dua-duanya** dan catat di DEV-NOTES, jangan diam-diam beda.
>
> Tujuan: setelah `migrate:fresh --seed`, tiap role bisa login dan langsung lihat datanya di tiap tahap — termasuk download invoice PDF, TTD kontrak, validasi absensi hari ini, ACC proyek, sengketa, prune akun mangkrak.
> WAJIB subagent. Commit per langkah.

## BC.1: Struktur

1. Seeder baru `database/seeders/DemoLengkapSeeder.php` (boleh dipecah per bagian: `Demo/StafSeeder`, `Demo/ExtrasSeeder`, `Demo/ProyekSeeder` …). `MasterOperationalSeeder` lama **dihapus** (digantikan), bukan dibiarkan dobel.
2. `DatabaseSeeder` → `ExtrasCategorySeeder` lalu `DemoLengkapSeeder`.
3. **Guard:** di awal seeder, `if (app()->environment('production')) { throw ... }`.
4. **Nggak boleh ngirim apa pun keluar** selama seeding: nggak ada WA job, email, atau notif yang di-dispatch lewat channel luar. Buat data langsung lewat model (`create`/`forceFill`), bukan lewat controller/method yang memicu notifikasi. Notifikasi in-app & activity log dibuat eksplisit sesuai AKUN-DEMO §5.
5. Semua tanggal relatif `now()` sesuai AKUN-DEMO §4.
6. Super Admin `fahrulmukhlisin13@gmail.com` tetap `is_protected = true`.

## BC.2: File pendukung (tanpa foto/video Extras)

- Tanda tangan kontrak & invoice: generate PNG placeholder sederhana (kotak putih + garis/teks "TTD Demo") ke path yang sama dengan yang dipakai `ContractController`/`InvoiceController`.
- Bukti transfer: PNG/PDF placeholder di path `payments/bukti-transfer`.
- Selfie absensi P3: PNG placeholder (bukan foto orang).
- PDF kontrak & invoice yang statusnya sudah full TTD: **render beneran** pakai logic render yang sudah ada, biar tombol download jalan tanpa klik apa pun dulu.

## BC.3: Verifikasi (Claude Code jalankan sendiri, tulis hasilnya di kolom Bukti)

1. `php artisan migrate:fresh --seed` sukses di SQLite **dan** MySQL `jbtb_test`.
2. Test feature `DemoSeederTest`: jalankan seeder, lalu assert — jumlah user per role sesuai AKUN-DEMO; P2 punya tepat 1 aplikasi di tiap status yang tercantum; invoice P1 punya PDF yang file-nya ada; P3 punya absensi hari ini `menunggu`; P4 `menunggu_acc`; akun mangkrak terdeteksi sama query prune; `persenCocok()` P2 dimas.rk = 100.
3. Login smoke test (HTTP test) satu akun per role → dashboard 200.
4. Full test suite tetap hijau (seeder demo **nggak** dipakai di test lain).

## Checklist BC

| Item | Bukti | QA |
|---|---|---|
| BC.1 seeder + guard + tanpa kirim keluar | `671e43b` — `--filter DemoSeederTest` (assert Mail/Queue/Http nothing sent + guard production) | [ ] |
| BC.2 file placeholder + PDF ter-render | `01d256a` — 14 PDF kontrak + invoice P1 + 2 slip honor ter-render; TTD/bukti/selfie placeholder PNG | [ ] |
| BC.3 verifikasi | `2745647` + fix MySQL `aafaec4` — `migrate:fresh --seed` sukses di SQLite **dan** MySQL `jbtb_test`; full suite SQLite 375 & MySQL 375 passed; smoke login 5 role 200 | [ ] |

**Cara Fakrul jalanin (menghapus SEMUA data lama):** `php artisan migrate:fresh --seed` → `php artisan storage:link` (kalau belum) → `php artisan view:clear`.

---

# Bagian BD: Rombak Super Admin — Dashboard, Manajemen Akun, Proyek & Keuangan, Log, Monitoring per Role (29 September 2026)

> HLD dari Fakrul (29 Sept), sudah didiskusikan & dikonfirmasi. **WAJIB subagent** (auth, keuangan, >3 file). Commit per sub-bagian, urutan pengerjaan: **BD.2 → BD.1 → BD.9 → BD.3 → BD.4 → BD.5 → BD.6 → BD.7 → BD.8**. Jangan centang checklist sendiri.
>
> Prinsip: **Dashboard = ringkasan**, menu lain = "perbesaran"-nya. Angka uang di mana pun **hanya** dari `KeuanganService` (satu sumber). `/ponytail`: pakai ulang view/komponen yang ada, jangan bikin versi kedua.

## Keputusan yang sudah diambil (update tabel AY.6)

- **D1 PIC proyek:** tiap proyek WAJIB punya 1 Admin PIC (`admin_id`) + 1 Client (akun). Dipilih dari akun yang ada.
- **D10 Super Admin:** godmode untuk **Admin & Korlap** (boleh aksi penuh, tercatat atas nama Super Admin). Untuk **Client & Extras**: read-only.
- **D2 (sebagian):** akun Client dibuat Super Admin, login tetap wajib.
- Proyek yang dibuat Super Admin langsung dibuka, tanpa ACC. Proyek hasil pengajuan Client tetap di-ACC Super Admin.
- Biaya lain-lain proyek: label + nominal, boleh ditambah Admin & Super Admin selama proyek berjalan.
- Nama menu: **Monitoring** dengan submenu per role.

## BD.1: Akun Client dibuat Super Admin

1. Manajemen Akun → "+ Client": nama, nama perusahaan/PH, email, username, nomor WA. Kalau belum ada kolom perusahaan buat user, tambah `users.nama_perusahaan` (nullable).
2. **Password nggak diketik Super Admin** — begitu form disimpan, sistem otomatis bikin password sementara (acak 10 karakter, tanpa karakter mirip seperti 0/O/l/1), lalu muncul **dialog sekali tampil** berisi username + password + URL login, tombol "Salin semua" dan "Kirim via WA" (link `wa.me/{nomor WA client}` dengan teks siap kirim). Kalau dialog ketutup sebelum sempat dikirim → pakai "Reset password" (BD.1.7) buat bikin yang baru.
3. Kolom baru `users.wajib_ganti_password` (bool). Login pertama Client → dipaksa ke `/ubah-password` sampai diganti (middleware kecil). Tujuannya: Super Admin nggak tau password permanen Client.
4. Pakai juga buat "+ Client baru" di form proyek (BD.2.2) — modal yang sama.
5. **DIKONFIRMASI Fakrul (29 Sept): tutup registrasi publik Client.** `/register/casting-director` (GET & POST) → redirect ke `/login` dengan pesan "Akun Client dibuatkan oleh tim JBTB. Hubungi kami untuk mendapatkan akses." Hapus link/teks yang mengarah ke sana. Login Client lewat halaman `/login` yang sama (email **atau** username — sudah didukung).
6. **Email Client opsional.** Migration: `users.email` jadi nullable (unique tetap). Validasi di form "+ Client": email `nullable|email|unique`. Username wajib.
7. **Lupa password Client tanpa email:** Super Admin pakai aksi "Reset password" yang sudah ada (`AdminManagementController::resetPassword` — password acak tampil sekali). Tambah: set `wajib_ganti_password = true` saat reset, dan tampilkan password baru di dialog dengan tombol Salin/Kirim WA (bukan cuma di flash message). Halaman `/forgot-password`: kalau identitas yang diisi nggak punya email → pesan "Akun ini belum punya email. Hubungi Super Admin JBTB untuk reset password."
8. **Profil Client (opsional, diisi sendiri):** halaman `cd/profil` — nama, nama perusahaan, email, nomor WA, ganti password. Di dashboard Client: kartu kecil "Lengkapi profil (email buat notifikasi & lupa password)" selama email kosong, bisa ditutup. Nggak ada field wajib selain yang sudah diisi Super Admin.

## BD.2: Menu "Proyek & Keuangan" (SA & Admin)

1. **Data (migration):**
   - `casting_projects.client_id` (FK users, nullable buat data lama) — backfill dari `diajukan_oleh_client_id` / `CdProjectAssignment` pertama. `client_ph` tetap sebagai teks tampilan, otomatis diisi dari `nama_perusahaan` Client.
   - Tabel `project_expenses`: `casting_project_id`, `label`, `nominal`, `tanggal`, `created_by`, timestamps. Hapus hanya oleh pembuat atau Super Admin. Semua tambah/hapus masuk `ActivityLog`.
   - `invoices`: tambah `nominal` (decimal), `status_bayar` (`belum`/`lunas`), `dibayar_at`. Nilai `nominal` dihitung `KeuanganService::nilaiInvoice()` pakai rumus PDF invoice yang sekarang (`budget_client × kuota_kelas`) — kalau D5 nanti mengubah rumus, cukup ubah di method itu. Super Admin & Admin bisa "Tandai Lunas" (konfirmasi + log).
2. **Buat proyek (SA & Admin):** satu form (pakai ulang form create Admin). Field Admin PIC & Client = **select yang bisa dicari**, isinya akun `admin` aktif / `client` aktif dari DB, validasi `exists` + role. Opsi "+ Client baru" (BD.1). Dibuat SA → `status=dibuka`, `client_request_status=disetujui`; dibuat Admin → sama seperti sekarang. Client terpilih otomatis dapat `CdProjectAssignment`. Log: "Proyek X dibuat oleh {nama} ({role})".
3. **Daftar proyek:** search + chip status (Menunggu ACC / Mendatang / Berjalan / Selesai), per baris: nama, Client, PIC, tanggal shooting, pendaftar/kuota, **uang masuk · keluar · saldo**.
4. **Detail proyek** — tab:
   - **Info:** semua field proyek, Client, PIC, Korlap, peran (kuota, tag dicari), jadwal.
   - **Pendaftar:** kartu Extras (partial BA) per status.
   - **Cashflow:** **Masuk** = invoice (nominal, status lunas, link PDF/kontrak). **Keluar** = honor Extras per orang (`Payment::nominalTotal()` + status), honor staf (`StaffPayroll::nominalTotal()` + status), biaya lain-lain (bisa tambah di sini). **Saldo** = masuk − keluar, dan **Terpakai %** = keluar ÷ masuk.
5. `KeuanganService::cashflowProyek(CastingProject)` + `ringkasanPeriode(from, to)` = satu-satunya sumber angka buat halaman ini **dan** dashboard. Halaman `rekap-margin` lama jadi redirect ke menu ini (tab staf/extras/invoice jadi filter di sini).

## BD.3: Dashboard Super Admin (ringkasan)

1. **Filter periode** di atas: preset (Bulan ini / 3 bulan / Tahun ini) + custom `dari`–`sampai`, tampilkan "N hari". Proyek difilter pakai **tanggal shooting**, uang pakai **tanggal transaksi** (`dibayar_at`/`ditransfer_at`/`tanggal` biaya). Tulis aturan ini kecil di bawah filter.
2. **Perlu tindakan** (paling atas, tiap item link): pengajuan proyek menunggu ACC, pembayaran disengketakan, honor staf belum dibayar, invoice belum lunas.
3. **Status proyek:** 4 kartu angka (Menunggu ACC · Mendatang · Berjalan · Selesai), klik → daftar proyek terfilter.
4. **Uang periode:** masuk · keluar · saldo + chart bulanan dari `ringkasanPeriode()`.
5. **Kalender** (full, bukan compact): tanggal bertanda → klik → panel daftar kegiatan hari itu (proyek, lokasi, jam, jumlah Extras, status absensi) + tombol aksi (Buka proyek, Lihat absensi).
6. **Akun:** angka per role + "perlu tindakan" (mis. akun Client baru belum ganti password). Tanpa daftar akun — itu di Manajemen Akun.
7. Tabel anggaran per proyek **tidak** di dashboard — cukup "5 proyek berjalan teratas" + link ke Proyek & Keuangan.

## BD.4: Manajemen Akun (gabungan Monitoring Akun + Kelola Akun)

1. Satu halaman `super-admin/akun`: **search bar di atas** (nama, username, email, WA), lalu daftar semua akun (paginate 30).
2. Filter di **satu ikon** (popover): role, status (aktif/nonaktif/dihapus), "sedang aktif di proyek" (punya aplikasi status aktif / assignment berjalan), tag Extras (multi), grade.
3. Per baris: nama, role, status + "aktif sejak …", aktivitas terakhir (1 baris dari ActivityLog), kebab aksi yang sudah ada (Edit, Reset password, Nonaktifkan/Aktifkan, Hapus/Restore, Lihat detail). Extras tampil pakai kartu BA kalau filter role = Extras.
4. Detail akun: profil (per role), riwayat proyek, **Aktivitas akun ini** (AU.6.7), aksi.
5. **Hapus** jadwal/kalender & absensi dari halaman ini (sudah di dashboard / Monitoring Korlap). Route lama `super-admin.monitoring` & `super-admin.admins.index` redirect ke sini.

## BD.5: Log Aktivitas

Search bar langsung (deskripsi, nama aktor, nama subjek). Filter di satu ikon (popover): aktor, role, jenis aksi, rentang tanggal. Hasil berupa daftar (deskripsi manusiawi, aktor + role label, waktu relatif, link ke subjek). Chart tren dihapus dari halaman ini (kalau mau, pindah ke dashboard).

## BD.6: Menu "Monitoring" — Super Admin sebagai tiap role

Submenu: **Admin · Korlap · Client · Extras**. Mode disimpan di session (`sa_mode`, dan `sa_view_user_id` untuk Client/Extras). Selama mode aktif: sidebar & dashboard berganti ke milik role itu + **banner** di atas: "Super Admin · sebagai Korlap · Kembali ke Super Admin".

1. **Admin & Korlap — bisa aksi (turun tangan).** Super Admin tetap login sebagai dirinya; yang berubah cuma tampilan menu. Semua aksi Admin/Korlap boleh.
   - Perbaiki semua cek otorisasi di controller yang nolak Super Admin (`isAdmin()`, `isKorlap()`, `isAnyAdmin()` dll — lihat review arsitektur: Payment/Contract/Invoice/Attendance) supaya Super Admin lolos.
   - `ActivityLog::record()`: kalau aktor Super Admin dan `sa_mode` aktif → `role` tetap `super_admin`, tambah `properties.sebagai = admin|korlap`, deskripsi berakhiran "(sebagai Korlap)". Contoh: "Super Admin Fakhrul memvalidasi absensi @dimas.rk (sebagai Korlap)".
2. **Client — read-only, pilih akun.** Submenu Client → pilih akun Client (search) → Super Admin melihat halaman Client **dengan data akun itu** (dashboard, Greenlight, jadwal, tagihan). Mekanisme: middleware `ViewAs` men-set user yang dirender = akun target **hanya untuk request GET**, sambil menyimpan ID Super Admin asli di session.
3. **Extras — read-only, pilih akun.** Sama seperti Client: lihat dashboard, profil, lowongan yang dibuka, status pendaftaran akun Extras itu. **Tidak bisa** edit profil, daftar lowongan, nego, TTD, konfirmasi bayar. (Kalau perlu benerin data Extras → Manajemen Akun → Edit, tercatat atas nama Super Admin.)
4. **Aturan keras mode read-only (Client/Extras):** semua request non-GET selama `sa_view_user_id` aktif → ditolak di middleware (redirect back + pesan "Mode lihat saja"), kecuali route keluar mode. Tombol aksi di view boleh tampil tapi disabled + tooltip. Tidak bisa lihat sebagai `super_admin` atau akun `is_protected`. Mulai/keluar mode → ActivityLog.
5. Test: POST apa pun saat view-as Client/Extras → ditolak & DB nggak berubah; SA mode Korlap validasi absensi → sukses, log aktor SA + `sebagai=korlap`; view-as nggak bisa ke akun protected.

## BD.7: Lowongan di sisi Extras lebih jelas

`extras/projects/index` & `show` (+ `public/event`): per peran tampilkan **sisa kuota** (`kuota_kelas − terisi`, "Sisa 3 dari 5"), **tag dicari** (chip `#Dewasa muda #Naik motor`), kriteria teks. Tag yang dimiliki Extras yang login diwarnai hijau.

## BD.9: Lampiran Proyek (upload dokumen)

1. Tabel `project_attachments`: `casting_project_id`, `uploaded_by`, `nama_asli` (nama file asli), `path`, `mime`, `ukuran`, `keterangan` (nullable), timestamps. File di **disk private** (`local`), bukan public.
2. **Siapa boleh upload & lihat:** Super Admin, Admin, dan Client yang ter-assign ke proyek itu. **Extras tidak bisa lihat.** Download lewat controller yang cek akses (stream), bukan URL storage langsung.
3. **Hapus:** hanya pengunggah atau Super Admin (dengan konfirmasi). Upload & hapus masuk ActivityLog.
4. Validasi: `pdf, doc, docx, xls, xlsx, jpg, jpeg, png`, maks **10 MB** per file, boleh multi-file sekali upload.
5. Tampil sebagai tab **"Lampiran"** di detail proyek (BD.2.4) dan di halaman proyek sisi Client (daftar file: nama, pengunggah, tanggal, ukuran, tombol Unduh/Hapus). Form pengajuan proyek Client juga boleh lampirkan file (opsional).
6. Mode Monitoring read-only (BD.6) otomatis memblokir upload/hapus (non-GET). Test: Client proyek lain → 403 saat unduh; Extras → 403.

## BD.8: Sidebar Super Admin final

Dashboard · Manajemen Akun · Proyek & Keuangan · Log Aktivitas · Monitoring ▸ (Admin, Korlap, Client, Extras). Hapus menu lama yang sudah digabung (Monitoring Akun, Kelola Akun, Rekap Margin/Keuangan lama, Presensi, dll). Semua route lama → redirect, bukan 404.

## Checklist BD

| Item | Bukti | QA |
|---|---|---|
| BD.1 akun Client oleh SA + wajib ganti password | `d120ccf`, `f7c0077` (merge `3f75d25`) + integrasi form proyek `352bf94` — `--filter ClientAkunTest`; Manual: SA Kelola Akun ▸ + Client → dialog kredensial (Salin/WA) → login Client dipaksa ganti password; `/register/casting-director` → login | [ ] |
| BD.2 Proyek & Keuangan (data, form, daftar, detail, cashflow) | `cb349a4`, `c314260` — `--filter ProyekKeuanganTest` (cashflow 1,1 jt / 45%, periode, tandai lunas idempotent, hapus biaya 403); Manual: SA buat proyek pilih PIC+Client → langsung dibuka; detail `?tab=cashflow` | [ ] |
| BD.3 dashboard ringkasan | `a45b2d8` (merge) — `--filter "SuperAdminDashboardTest|SuperAdminHonorRecapTest"`; Manual: `/super-admin/dashboard?periode=3bulan`, `?dari=..&sampai=..`, klik tanggal kalender → panel + Buka proyek / Lihat absensi | [ ] |
| BD.4 Manajemen Akun gabungan | `f79234a` (merge `a13487f`) — `--filter ManajemenAkunTest` (search/filter/redirect/N+1 < 25 query); Manual: `/super-admin/akun`, filter di ikon, role=Extras → kartu | [ ] |
| BD.5 Log Aktivitas search-first | `ac1b2fc` (merge) — `--filter ActivityLogAndEnhancementsTest`; Manual: cari deskripsi/aktor/nama proyek, filter di ikon, tanpa chart | [ ] |
| BD.6 Monitoring per role (aksi Admin/Korlap, read-only Client/Extras) | `49c2697`, `156d2f7`, `ec815b7` + fix `a45b1e6` (GET kontrak nggak generate/kirim WA saat view-as) — `--filter BdMonitoringModeTest` (11 test); Manual: skenario QA (4) & (5) | [ ] |
| BD.7 sisa kuota + tag di lowongan | `2639cc2` (merge) — `--filter BdLowonganExtrasTest`; Manual HP: `/extras/lowongan/{id}` "Sisa X dari Y" + tag milik sendiri bercentang | [ ] |
| BD.8 sidebar final + redirect route lama | `d39729c` — `--filter BdSidebarFinalTest`; Manual HP: bottom bar → Monitoring → popup 4 role | [ ] |
| BD.9 lampiran proyek | `3765798` (merge `8462b07`) — `--filter ProjectAttachmentTest` (Client lain/Extras/Korlap 403, hapus non-pengunggah 403); Manual: `/admin/projects/{id}?tab=lampiran`, Client di `/cd/jadwal/{id}` | [ ] |

**Tes QA:** (1) SA bikin akun Client → login Client dipaksa ganti password. (2) SA bikin proyek pilih Admin & Client → langsung dibuka, muncul di dashboard Client. (3) Admin tambah biaya lain-lain → saldo proyek & dashboard SA berubah sama persis. (4) SA Monitoring ▸ Client ▸ @client_andini → lihat Greenlight, klik Pilih → ditolak "Mode lihat saja". (5) SA Monitoring ▸ Korlap → validasi absensi → di Log tertulis "(sebagai Korlap)".

---

# Bagian BE: Tindak Lanjut Review BD (29 September 2026)

> Manager sudah cek kode BD: middleware `ViewAs` (non-GET ditolak, `Auth::setUser` cuma di GET), `WajibGantiPassword`, `ActivityLog` "(sebagai …)", registrasi Client ditutup, `Payment::nominalTotal` — **terverifikasi ada**. Test 431 belum bisa diverifikasi independen (manager nggak bisa run PHP). **Belum ada tes browser/HP** untuk BD — itu tugas QA sebelum fitur baru apa pun.
>
> Rekomendasi manager di bawah jadi **default**. Fakrul boleh coret/ubah sebelum Claude Code mulai. Subagent wajib (keuangan + kontrak). Kerjakan **berurutan**, bukan paralel.

## BE.1: Halaman GET nggak boleh bikin data (bug desain, bukan cuma mode lihat saja)

Sekarang `ContractController::show` bikin kontrak + render PDF + **kirim WA/email** saat halaman dibuka; `PaymentController::show` bikin baris payment; `InvoiceController` `firstOrCreate` di GET. Akibatnya membuka halaman = mengubah data, dan pengecualian `sa_view_user_id` cuma nambal satu kasus.

- Pindahkan pembuatan ke **transisi status**: saat aplikasi jadi `lolos` (di `Cd\ReviewController` approve) → buat `Contract` + render PDF + notifikasi (sekali), dan buat `Payment` `belum_dibayar`. Invoice dibuat saat proyek disimpan/di-ACC (atau saat pertama ada aplikasi `lolos`).
- `show()` ketiganya jadi **murni baca**: kalau record belum ada → tampilkan state "Belum tersedia" (bukan create).
- Migration/command sekali jalan: buat record yang hilang untuk aplikasi yang sudah `lolos` ke atas (tanpa kirim notifikasi).
- Hapus pengecualian `sa_view_user_id` di `ContractController` (nggak perlu lagi).
- Test: GET kontrak/pembayaran/invoice nggak mengubah jumlah baris; approve Client → kontrak + payment tercipta, notifikasi 1×.

## BE.2: Kartu dashboard & daftar yang dibuka harus pakai periode yang sama

Link di kartu status proyek (BD.3.3) dan "Perlu tindakan" bawa query `dari`/`sampai` yang aktif; halaman tujuan (Proyek & Keuangan) baca filter itu dan menampilkannya sebagai chip yang bisa dihapus. Angka di kartu = jumlah baris di halaman tujuan.

## BE.3: Keuangan — pisahkan "Piutang" supaya saldo nggak menyesatkan

Kartu & cashflow jadi: **Masuk (lunas)** · **Piutang (invoice belum lunas)** · **Keluar** · **Saldo** (= masuk − keluar) · **Proyeksi** (= masuk + piutang − keluar). Saldo minus saat invoice belum dibayar itu wajar; Proyeksi yang menunjukkan untung/rugi proyek. Semua dari `KeuanganService`.

## BE.4: Kuota = antrian pendaftar (DIPUTUS Fakrul 29 Sept)

Logika antrian: kuota peran = jumlah slot pendaftar **aktif** (semua status selain `ditolak`/`dibatalkan`). Kalau slot penuh, pendaftaran peran itu **tertutup**. Begitu ada yang ditolak/batal, slotnya **terbuka lagi** otomatis.

- Definisi `terisi` yang sudah ada di `CastingProjectClass` (BD.7) **sudah benar**, jangan diubah.
- Yang kurang: **blokir daftar per peran** di `Extras\CastingProjectController::apply()` kalau `sisaKuota() === 0` → balik dengan pesan "Kuota peran ini sedang penuh. Cek lagi nanti — slot bisa terbuka kalau ada pendaftar yang mundur." Cek dilakukan di dalam `DB::transaction` + `lockForUpdate()` pada baris peran, biar 2 orang yang daftar bersamaan di slot terakhir nggak dua-duanya lolos.
- Tampilan Extras (lowongan & event publik): peran penuh → badge "Penuh", tombol Daftar disabled + teks "Slot bisa terbuka lagi". Peran lain di proyek yang sama tetap bisa didaftar.
- `CastingProject::kuotaPenuh()` = semua peran penuh.
- Test: kuota 2, 2 daftar → orang ke-3 ditolak; 1 ditolak Admin → orang ke-3 bisa daftar.

## BE.5: Wajib ganti password jangan blokir halaman publik

`WajibGantiPassword` hanya berlaku di route yang butuh login (grup `auth`), bukan homepage, `/event/*`, `/p/*`, privacy policy.

## BE.6: Filter tag — beda tujuan, beda logika, tulis di UI

- **Lineup / Greenlight** (memilih dari pendaftar): tetap **salah satu (OR)** + urut "Paling cocok".
- **Manajemen Akun** (mencari orang spesifik): tetap **semua (AND)**.
- Tambah teks kecil di bawah chip: "Menampilkan yang punya **salah satu** tag, diurutkan paling cocok" / "Menampilkan yang punya **semua** tag terpilih".

## BE.7: Form edit proyek belum punya Client & PIC

Tambah field Client (select bisa dicari + "+ Client baru") dan Admin PIC di form **edit** (sama seperti create BD.2.2). Proyek lama tanpa `client_id` → badge "Client belum diisi" di daftar proyek + masuk "Perlu tindakan" di dashboard SA.

## Checklist BE

| Item | Bukti | QA |
|---|---|---|
| BE.1 GET murni baca, data dibuat di transisi status | `72fe36a` — `--filter BeGetMurniBacaTest` (GET nggak ubah jumlah baris; approve single/bulk → kontrak+payment, notif 1×; NIK belakangan → kontrak saat NIK disimpan; backfill idempoten). SQLite & MySQL 436 passed. **Perlu `php artisan migrate`** (backfill) | [ ] |
| BE.2 periode ikut ke daftar | `79fa820` — `--filter BePeriodeKartuTest` (angka kartu == total daftar tujuan, 4 periode, >20 proyek); scope bersama `CastingProject::shootingDalam()`. "Perlu tindakan" sengaja tanpa periode (to-do list) | [ ] |
| BE.3 piutang & proyeksi | `98d2ddd` — `--filter BePiutangTest` (dashboard = daftar = detail). Piutang periode = invoice belum lunas dari proyek yang shooting-nya dalam periode. SQLite & MySQL 445 passed | [ ] |
| BE.4 kuota antrian: blokir daftar saat penuh, terbuka lagi saat ada yang ditolak | `f4db61d` — `--filter BeKuotaAntrianTest` (kuota 2: orang ke-3 ditolak; 1 ditolak → ke-3 bisa; peran lain tetap bisa; kuotaPenuh = semua peran penuh). Transaksi + `lockForUpdate` | [ ] |
| BE.5 wajib ganti password cuma di route auth | `9a47084` — `--filter BeWajibGantiPublikTest` (`/`, `/event`, `/p/extras`, privacy 200; dashboard → ubah password) | [ ] |
| BE.6 teks logika filter tag | `2f75ebb` — assert di `BaKartuExtrasTest` & `ManajemenAkunTest` | [ ] |
| BE.7 Client & PIC di form edit proyek | `5dc07b8` — `--filter BeEditClientPicTest`; badge "Client belum diisi" + filter `?tanpa_client=1` + item Perlu tindakan. SQLite & MySQL 450 passed | [ ] |

---

# Bagian BF: Kalender gaya HP (29 September 2026)

> Keluhan Fakrul: kalender terlalu renggang (paling kerasa di Dashboard SA yang full-width — tiap kolom melebar, tinggi sel cuma 32px, jadi kelihatan kosong). Target: rapat & padat kayak kalender bulanan di HP (Google Calendar / iOS). **Cuma ubah `components/jadwal-calendar.blade.php`** (+ penempatan di dashboard SA). Logic event, `calClick`, escape XSS, nav bulan, `navigable`, `detail` — jangan diubah.

1. **Lebar tetap:** grid kalender `max-width: 360px` di semua mode (compact maupun detail). Kartu kalender boleh lebar, tapi grid-nya nggak ikut melebar.
2. **Sel kotak:** `.cal-day` pakai `aspect-ratio: 1`, angka tanggal di tengah dalam lingkaran 32–36px. Hapus `min-height` lama. Gap antar sel 2px.
3. **Penanda:**
   - Hari ini: lingkaran **terisi** warna accent, angka putih.
   - Tanggal terpilih: **ring** (border 2px accent), bukan background pudar.
   - Ada event: titik kecil di bawah angka, maksimal 3 titik (1 titik per proyek, warna beda per proyek kalau gampang; kalau nggak, satu warna accent). Lebih dari 3 → titik ke-3 diganti "+".
   - Tanggal di luar bulan: angka pudar (opacity .35), tetap bisa diklik kalau ada event.
4. **Header:** label hari 1 huruf (S S R K J S M) 12px; baris judul "September 2026" rata kiri + panah ‹ › di kanan (target sentuh 44px).
5. **Agenda di bawah (bukan di samping):** panel detail selalu di **bawah** grid, isinya daftar gaya agenda HP — tiap kegiatan 1 baris: garis warna kiri, jam, nama proyek, lokasi, jumlah Extras, tombol aksi kecil. Default kebuka di **hari ini** (atau tanggal event terdekat) tanpa perlu diklik. Klik tanggal lain → ganti isi agenda. Hover tetap boleh buat preview di desktop.
6. **Dashboard SA:** kartu kalender taruh di grid 2 kolom (`.dashboard-grid-2col`): kiri kalender (360px) + agenda di bawahnya, kanan "Perlu tindakan". Di HP jadi 1 kolom.
7. Cek di 4 dashboard lain (Admin, Korlap/absensi, Client, Extras) — semuanya pakai komponen yang sama, jadi harus ikut rapi tanpa ubahan di view masing-masing. Cek dark & light.

## Checklist BF

| Item | Bukti | QA |
|---|---|---|
| BF.1–5 komponen kalender gaya HP | `acd3bcd` (merge) — test kalender lama tetap hijau; screenshot 5 halaman × HP/desktop × light/dark | [ ] |
| BF.6 penempatan dashboard SA | `acd3bcd` — kalender kiri + Perlu tindakan kanan (≥861px), 1 kolom di HP | [ ] |
| BF.7 cek 5 halaman, dark/light, 360px | dicek via screenshot Edge headless (SA, Admin, Korlap, Client, Extras). Angka "hari ini" di dark pakai `--accent-on` (gelap), bukan putih — putih di atas hijau terang kontrasnya < 3:1 | [ ] |

---

# Bagian BG: Sisa dari laporan BE/BF (30 September 2026)

1. **Ganti Client di proyek → akses Client lama dicabut.** Saat `client_id` proyek diubah (form edit BE.7), hapus `CdProjectAssignment` Client lama untuk proyek itu, dengan dialog konfirmasi "Client lama (@x) nggak bisa lihat proyek ini lagi". Review/grade yang sudah dia berikan tetap tersimpan (riwayat). Catat di ActivityLog. Test: Client lama → 403 di Greenlight/jadwal/invoice/lampiran proyek itu.
2. **Label "Keluar" diperjelas** (bukan ganti rumus): dashboard = **"Keluar (sudah dibayar)"** di periode itu; detail proyek tampilkan dua baris **"Sudah dibayar"** + **"Belum dibayar"**, total = keduanya. Tambah tooltip kecil kenapa angkanya bisa beda.
3. **Invoice "Belum tersedia"** → teks jadi "Invoice dibuat otomatis setelah ada kandidat yang dipilih Client."
4. Warna angka "hari ini" di kalender mode gelap: **pakai pilihan Claude Code** (kontras menang dari spec). Nggak perlu diubah.

5. **Pagination: fix ikon raksasa (sudah dikerjakan manager, tinggal commit)** — `AppServiceProvider` pakai `pagination::bootstrap-4` / `simple-bootstrap-4` (markup teks ‹ ›, tanpa SVG Tailwind) + CSS `.pagination` di `layouts/app.blade.php`. Cek visual di 5 halaman yang pakai `->links()`.
6. **Pilihan jumlah per halaman ("Tampilkan 10 / 25 / 50 / 100")** di 5 halaman yang sama (Manajemen Akun, Log Aktivitas, Daftar Proyek, pemilih akun Monitoring, Lineup):
   - Satu helper, mis. `App\Support\PerHalaman::dari($request, $default, $pilihan)`: baca `?per=`, cuma terima nilai dari daftar pilihan (selain itu → default). Jangan copy-paste logika di tiap controller.
   - **Tabel/daftar:** pilihan 10/25/50/100, default 25. **Grid kartu** (Lineup, akun Extras berbentuk kartu): pilihan **12/24/48/96**, default 24 — kelipatan 2/3/4 kolom biar baris terakhir nggak bolong.
   - Komponen `<x-per-halaman :pilihan="..." />` = `<select name="per">` kecil, ditaruh **di dalam** `form[data-live]` (biar ganti pilihan langsung reload via live search, tanpa tombol) dan di sebelah pagination bawah.
   - Semua `paginate()` pakai `->withQueryString()` supaya `per`, `q`, filter, dan periode kebawa saat pindah halaman.
   - Tampilkan teks "Menampilkan 26–50 dari 312" di dekat pagination.
   - Test: `?per=50` → 50 baris; `?per=999` → default; pindah ke halaman 2 → `per` tetap.

| Item | Bukti | QA |
|---|---|---|
| BG.1 cabut akses Client lama | `57d7583` — `--filter BgGantiClientTest` (Client lama 403 di Greenlight/jadwal/invoice/lampiran, review tetap ada). Invoice & foto absensi tadinya masih izinkan `diajukan_oleh_client_id` → ditutup | [ ] |
| BG.2 label Keluar | `56d792a` — `--filter BgLabelKeluarTest` (dibayar + belum = total; dashboard = keluar_dibayar) | [ ] |
| BG.3 teks invoice | `7821bd6` — assert di `BeGetMurniBacaTest` | [ ] |
| BG.5 fix pagination (commit) | `becbb3d` — screenshot 5 halaman desktop + HP (tombol 40px teks ‹ ›, wrap di HP) | [ ] |
| BG.6 pilihan jumlah per halaman | `b48d2a1` + `8741a93` — `--filter PerHalamanTest` (per=50 → 50; 999 → default; per ikut ke halaman 2; kartu 12/24/48/96). SQLite & MySQL 455 passed | [ ] |
| BG.7 input terkunci di mode lihat saja | `a04f93b` — test di `BdMonitoringModeTest` (teks kunci + nama ter-escape); Edge `--dump-dom`: 6 input file + 23 kontrol form POST `disabled`, form GET/logout/keluar mode tetap aktif, konten live search ikut terkunci (MutationObserver) | [ ] |
| BG.8 gabung Status Proyek + daftar proyek di dashboard SA | `eb56482` (merge) — `--filter "BgStatusProyekTabTest|BePeriodeKartuTest"` (5 per tahap, default Berjalan→Mendatang, angka = total Lihat semua). SQLite & MySQL 458 passed | [ ] |

**BG.7 (ditambah 30 Sept dari tes manual Fakrul): Mode lihat saja — input juga dikunci, bukan cuma tombol simpan.** Di "lihat sebagai Extras" form profil masih bisa diketik (Simpan & upload memang gagal, server sudah benar), jadi kesannya bisa diedit. Di script `sa-lihat-saja` (`layouts/app.blade.php` ±baris 714): selain tombol submit, set `disabled` ke semua `input`, `select`, `textarea` di `form[method=post]` yang bukan `data-sa-allow`, **plus** semua `input[type=file]` di mana pun (upload foto/video pakai AJAX di luar form) dan tombol pemicu upload/hapus foto. Tambah satu baris di atas form yang terkunci: "Mode lihat saja — tampilan ini persis yang dilihat {nama}, tapi nggak bisa diubah." Form GET (search/filter/per halaman) tetap aktif. Test: halaman edit profil dalam mode lihat saja → semua input `disabled` di HTML.

**BG.8 (30 Sept, permintaan Fakrul): Dashboard SA — card "5 Proyek Berjalan Teratas" digabung ke section "Status Proyek".** Jadi satu card "Status Proyek": baris atas tetap 4 kotak angka (Menunggu ACC · Mendatang · Berjalan · Selesai). Kotak bisa diklik sebagai **tab** (bukan pindah halaman): di bawahnya tampil **maks. 5 proyek** dari tahap yang dipilih (nama, Client, rentang shooting, tanggal terdekat — format baris `.sa-row` yang sudah ada), default tab **Berjalan** (kalau kosong → Mendatang). Di bawah daftar: link "Lihat semua {tahap} →" ke `admin.projects.index` dengan `tahap` + periode (aturan BE.2 tetap). Controller cukup ambil 5 proyek per tahap sekali jalan (data dirender semua, ganti tab pakai JS tanpa fetch). Hapus card "5 Proyek Berjalan Teratas" lama; card "Akun" yang tadinya sebelahan jadi full-width atau disejajarkan dengan card lain yang pas. Angka di kotak tetap = jumlah di halaman "Lihat semua".

**BG.8 (30 Sept, permintaan Fakrul): Dashboard SA — "5 Proyek Berjalan Teratas" digabung ke "Status Proyek".** Sekarang `super-admin/dashboard.blade.php` punya judul "Status Proyek" yang melayang tanpa `.card` (±baris 154) + kartu terpisah "5 Proyek Berjalan Teratas" (±baris 209). Jadikan **satu kartu** "Status Proyek": 4 angka tahap di atas (Menunggu ACC · Mendatang · Berjalan · Selesai) berfungsi sebagai **tab** — klik → daftar di bawahnya ganti ke maks 5 proyek tahap itu (ganti di browser, data 5 teratas per tahap sudah dikirim controller, tanpa reload); tab aktif ditandai; default tab **Berjalan** (kalau kosong → Mendatang). Tiap baris: nama proyek, Client, rentang shooting, tanggal terdekat, link ke detail. Di bawah daftar: link "Lihat semua {tahap} →" ke `admin.projects.index` dengan `tahap` + periode (aturan BE.2 tetap). Kartu "Akun" yang tadinya berdampingan jadi full-width satu baris (angka per role + info "perlu tindakan"). Controller: ganti `$proyekBerjalan` jadi `$proyekPerTahap[tahap] = 5 teratas` pakai scope `diTahap()` yang sama dengan angka, supaya angka & daftar konsisten.

---

# Bagian BH: Profil Extras gaya editorial + Beranda: Cast & Portofolio terkurasi (30 September 2026)

> Referensi desain dari Fakrul: `docs/ui-prototype/moodboard-sideroom/profile-page.tsx` (layout profil) & `landing-page.tsx` (roster + portfolio). Itu **Next.js + Tailwind** — JANGAN pasang Tailwind/React. Terjemahkan ke Blade + CSS biasa pakai token yang sudah ada (`--hp-*` di homepage/profil publik, token app di halaman dalam). Homepage kita sudah pakai bahasa desain yang sama (Sideroom editorial), jadi ini soal menyamakan profil & merapikan dua section beranda.
> Subagent wajib (lintas >3 file + data publik). Commit per sub-bagian.

## BH.1: Layout profil Extras ala moodboard

Berlaku untuk **3 tampilan yang sama datanya**: Profil Saya (`extras/profile-show`), lihat profil oleh Admin/SA (`isAdminView`), dan profil publik share link (`public/extras-profile`). Satu partial dipakai bareng, section tampil/terkunci sesuai aturan lapis BA/BD (publik ≠ pemilik ≠ admin).

Struktur (urut seperti moodboard):
1. **Header profil:** label kecil "Profil Talent / #ID", kanan: Bagikan + Edit profil (**hanya pemilik**).
2. **Hero 2 kolom:** kiri foto utama rasio ±0.82, **grayscale, berwarna saat hover**; badge status di pojok (Aktif / Sedang di proyek / Tidak aktif — dari data asli, bukan "Available" statis). Kanan: kategori kecil ("Extras" + tag Usia tampilan), **username besar serif** dengan akhiran accent, chip (kota/domisili kalau ada, bahasa), baris grade ("Grade A" / "Grade belum dinilai" — grade **cuma** pemilik & admin).
3. **01 / Data diri & ciri fisik:** grid 2 kolom label kecil + nilai serif (usia, gender, tinggi, ukuran baju, warna kulit). Publik: usia **rentang**.
4. **02 / Pengalaman & kemampuan:** pengalaman, bahasa, tag Tipe & Kemampuan (chip), tautan tambahan (pemilik & admin saja).
5. **03 / Showreel (video)** — publik: kotak terkunci "Tersedia untuk Client & Admin".
6. **04 / Tarif** — **hanya pemilik & admin**. Tombol di bawahnya BUKAN "Contact talent" ke Extras: untuk publik/Client jadi **"Ajak casting lewat JBTB"** → WA/kontak JBTB (kontak Extras nggak pernah tampil, aturan BA).
7. **05 / Gallery:** 4 foto tambahan grid, klik → lightbox yang sudah ada. Kosong → kotak putus-putus "Belum ada foto".
8. Border garis tipis antar section (hairline), angka section "01 / …" warna accent.

Penyesuaian wajib dari moodboard: label **minimal 12px** (moodboard pakai 9–10px — melanggar AY.3.2), kontras ikut token, tombol ≥44px, dark/light ikut toggle yang sudah ada.

## BH.2: Beranda — "Cast" hanya Extras yang disetujui (bukan acak)

Sekarang `HomeController` ambil **12 Extras acak** yang punya `share_token` + foto → foto orang bisa nongol di beranda tanpa izin.
1. Migration `extras_profiles`: `izin_tampil_publik` (bool, default false, **diisi Extras sendiri** di edit profil: "Izinkan foto & profil saya ditampilkan di website JBTB") + `tampil_di_beranda` (bool, default false, **diatur Admin/SA**) + `tampil_di_beranda_at`.
2. Tampil di beranda hanya kalau **dua-duanya true** + punya foto utama + akun aktif. Admin/SA atur lewat toggle di detail akun Extras / kartu Lineup ("Tampilkan di beranda"), disabled + tooltip kalau Extras belum kasih izin.
3. Section beranda: **carousel auto-geser** (pakai marquee CSS yang sudah ada, pause saat hover/sentuh, hormati `prefers-reduced-motion` → jadi baris statis bisa di-scroll). Kartu: foto grayscale→warna saat hover, `@username`, 1 tag Usia tampilan. **Tanpa** nama asli/usia pasti/kontak. Klik → profil publik (BH.1). Kalau yang disetujui < 4 → section disembunyikan.
4. Mematikan izin oleh Extras → langsung hilang dari beranda (cek di query, bukan cache).

## BH.3: Beranda — Portofolio proyek JBTB terkurasi

Sekarang `$proyekSelesai` = 8 proyek `ditutup` terakhir otomatis.
1. Migration `casting_projects`: `tampil_portofolio` (bool, default false), `portofolio_judul` (nullable, default pakai `nama_produksi`), `portofolio_jenis` (mis. "Film layar lebar", "Iklan TV", "Series"), `portofolio_tahun`, `tampilkan_nama_client` (bool, default **false** — `client_ph` rahasia di permukaan publik sesuai keputusan lama).
2. Diatur di detail proyek (tab Info) oleh Admin/SA, hanya untuk proyek berstatus selesai. Gambar pakai `poster_path`/`cover_path` yang sudah ada.
3. Section beranda "Portofolio / Pernah dikerjakan": carousel auto-geser juga (arah berlawanan dari Cast biar hidup), kartu lebar: gambar, judul serif, "jenis · tahun" (+ nama client kalau diizinkan). Nggak ada link ke detail proyek internal. Kosong → section disembunyikan.

## Checklist BH

| Item | Bukti | QA |
|---|---|---|
| BH.1 layout profil editorial (3 tampilan, lapis akses tetap) | `9081491` — `--filter BhProfilEditorialTest` (publik: tanpa tarif/grade/kontak/nama asli/Look, usia rentang, video terkunci; pemilik & admin lengkap). Tombol "Ajak casting" sementara ke IG JBTB (nomor WA JBTB masih TODO) | [ ] |
| BH.2 cast beranda: izin Extras + persetujuan Admin, carousel | `7677e2c` — `--filter BhBerandaCastTest` (izin false → toggle ditolak; izin dicabut → hilang & perlu approve ulang; <4 → section hilang) | [ ] |
| BH.3 portofolio proyek terkurasi, carousel | `5f82d55` — `--filter BhPortofolioTest` (cuma proyek selesai; nama client cuma kalau dicentang). SQLite & MySQL 469 passed; `migrate` jbtb sudah | [ ] |

**Tes QA:** Extras belum centang izin → Admin nggak bisa nyalain "Tampilkan di beranda". Centang izin + Admin nyalain → muncul di beranda; Extras matikan izin → hilang. Profil publik: nggak ada tarif, grade, kontak. Portofolio: nama client nggak muncul kecuali dicentang.

---

# Bagian BI: Manajemen Akun — "Lihat Profil" jadi popup + filter dirapikan (30 September 2026)

> Dari screenshot Fakrul (`/super-admin/akun`): (1) "Lihat Profil" di kartu Extras pindah halaman ke `/admin/extras/{id}/profil` — maunya popup. (2) Filter kebuka **inline** sebagai 3 select selebar layar + checkbox + "Tag Extras" + tombol "Terapkan" → dorong daftar ke bawah, kerasa berantakan. Kerjakan **setelah BH.1** (popup pakai partial profil editorial dari BH.1). Satu file komponen dipakai ulang, jangan copy-paste per halaman.

## BI.1: Popup profil (dipakai di mana pun ada "Lihat Profil")

1. Route `GET /admin/extras/{user}/profil` tetap ada (link langsung/tab baru tetap jalan). Tambah respons **partial**: kalau request `X-Requested-With: XMLHttpRequest` (atau `?partial=1`) → render cuma partial profil BH.1 tanpa layout.
2. Komponen `<x-profil-modal />` (sekali di layout app): `<dialog>` lebar ±760px (HP: full-screen sheet dari bawah), header sticky: `@username`, badge status, tombol **"Buka halaman penuh ↗"** dan tutup (44px, `aria-label`). Isi di-scroll di dalam dialog.
3. Semua tombol/link "Lihat Profil" Extras (Manajemen Akun kartu & baris, kartu Lineup, Greenlight, Monitoring) pakai atribut `data-profil-modal` → JS delegasi: klik biasa = fetch partial → isi dialog → `showModal()`; Ctrl/Cmd+klik atau klik tengah = buka tab baru seperti biasa. Loading: skeleton sederhana. Gagal fetch → fallback pindah halaman.
4. Aturan lapis tetap dari server (partial dirender sesuai viewer) — Client di Greenlight lihat versi Client, Admin/SA versi lengkap.
5. Tombol aksi di footer dialog sesuai konteks halaman asal (Manajemen Akun: Kelola ▾; Lineup: aksi status; Greenlight: Pilih/Tolak) — ambil dari atribut `data-aksi-url` di tombol pemicu, bukan hardcode di komponen.
6. Esc / klik backdrop menutup; fokus balik ke tombol pemicu. Back browser menutup dialog kalau sempat dibuka (pakai `history.pushState` `#profil-{id}`), bukan keluar halaman.

## BI.2: Filter jadi panel popover rapi (komponen `<x-filter-panel>`)

Baris atas satu garis: **[Cari …………] [Tampilkan 25 ▾] [⚙ Filter (2)]**. Tombol "Terapkan" dihapus — semua pilihan **langsung diterapkan** lewat live search yang sudah ada (debounce).

Klik **Filter** → panel melayang di bawah tombol (desktop, lebar ±360px, rata kanan) / **bottom sheet** (HP). Isi panel, urut & ringkas:
- **Role** — chip satu baris (Semua · Admin · Korlap · Client · Extras · Super Admin), bukan dropdown.
- **Status** — chip (Semua · Aktif · Nonaktif · Dihapus).
- **Sedang aktif di proyek** — toggle switch.
- **Khusus Extras** (muncul cuma kalau role = Extras atau Semua): Grade chip (Semua · A · B · C · Belum), lalu **Tag** per grup dalam accordion kecil (Usia tampilan / Look / Tipe / Kemampuan), chip multi-pilih + teks logika BE.6.
- Footer panel: "Reset" (kiri) · "Tutup" (kanan).

Di bawah baris cari, tampilkan **chip filter aktif** yang bisa dihapus satu-satu: `Role: Extras ✕` `Tag: #Berhijab ✕` … + "Hapus semua". Badge angka di tombol Filter = jumlah filter aktif. Panel tutup dengan klik di luar / Esc.

Terapkan komponen yang sama di **Log Aktivitas** dan **Proyek & Keuangan** (isi grup filter beda, bentuk sama) biar konsisten.

## Checklist BI

| Item | Bukti | QA |
|---|---|---|
| BI.1 popup profil (+ halaman penuh tetap jalan) | `7d60b37` — `--filter BiProfilModalTest` (XHR → partial tanpa layout; Client cuma kandidat Greenlight proyeknya, tanpa tarif/grade/nama asli; lain → 403). Back nutup popup tanpa reload live search | [ ] |
| BI.2 panel filter + chip filter aktif, tanpa tombol Terapkan (3 halaman) | `566ffcc` — `--filter BiFilterPanelTest` (badge, chip hapus per param, Hapus semua pertahankan q & per). SQLite & MySQL 476 passed | [ ] |

**Tes QA:** Manajemen Akun → klik Lihat Profil → popup, Esc nutup, Back nutup popup (nggak keluar halaman). Ctrl+klik → tab baru. Filter: pilih Role Extras + 1 tag → daftar langsung berubah, muncul 2 chip, hapus 1 chip → daftar ikut. Cek di HP: panel jadi bottom sheet.

---

# Bagian BJ: Profil & Dashboard Extras — tag bebas, form lebih ringkas, urutan mobile (30 September 2026)

> Arahan Fakrul 30 Sept. **Menggantikan aturan BA.2 "Extras nggak bisa bikin tag baru".** Kerjakan setelah BH.1 (layout profil editorial). Subagent wajib (profil + data). Commit per sub-bagian.

## BJ.1: Tag bebas (Extras tulis sendiri, daftar lama jadi contoh)

1. Di edit profil, section "Tentang Kamu": chip tag yang sudah dipilih + **input "+ Tambah tag"** (ketik → Enter/koma = jadi chip). Tag bawaan per grup tetap tampil sebagai **saran** yang bisa di-tap.
2. **Autocomplete** dari semua tag yang sudah ada di DB (siapa pun yang bikin) saat mengetik — supaya orang cenderung pakai tag yang sama dan filter/% cocok tetap jalan.
3. **Normalisasi wajib** sebelum simpan (satu tempat, mis. `ExtrasCategory::normalisasi()`): trim, buang `#` di depan, rapikan spasi ganda, maks 30 karakter, lalu cocokkan **tanpa beda huruf besar/kecil** ke tag yang ada (`"dewasa"` = `"Dewasa"` → pakai yang lama). Kalau belum ada → buat tag baru dengan `grup = null` (tampil di grup "Lainnya") + kolom baru `dibuat_oleh` (user id).
4. Batas: maks **15 tag** per Extras. Validasi server, bukan cuma JS.
5. **Admin/SA** bisa menambah & menghapus tag Extras saat grading / di Lineup / di Manajemen Akun (fitur `updateKategori` yang sudah ada, pakai input yang sama). Admin/SA juga bisa memindahkan tag "Lainnya" ke grup yang benar atau menggabung dua tag yang sama artinya — cukup halaman kecil "Kelola Tag" di Manajemen Akun (daftar tag + jumlah pemakai + ubah grup + gabung ke tag lain). Semua perubahan tag masuk ActivityLog.
6. Tag per peran di form proyek (BA.2.4) pakai input + autocomplete yang sama.
7. Aturan D22 tetap: tag grup Look/Etnis nggak tampil di profil publik; tag "Lainnya" tampil publik hanya kalau Admin sudah memindahkannya ke grup non-Look.

## BJ.2: Foto — satu section lebar

1. Gabung foto utama + galeri jadi satu section **"Foto"** selebar form. Kotak pertama = **foto utama (wajib, ada label "Wajib")**, lalu foto-foto galeri, lalu satu kotak **"+"** untuk tambah (bukan 4 slot kosong berjejer). Kotak "+" hilang kalau galeri sudah penuh (maks 4 foto tambahan, sesuai tabel `extras_photos` yang ada).
2. Hint di atas galeri: "💡 Profil dengan 3+ foto (close-up, setengah badan, seluruh badan) lebih sering dipilih Client." Kalau galeri kosong tampil sebagai **alert kuning ringan**, bukan error.
3. Tiap foto: tap → ganti / hapus (konfirmasi). Video tetap section sendiri di bawahnya.
4. Upload tetap AJAX per foto + progress (AY.5.4), nggak mengubah backend.

## BJ.3: Pengalaman jadi daftar "+" (kayak tautan tambahan)

1. Migration `extras_profiles.riwayat_pengalaman` (json, nullable): array `{judul, keterangan?, tahun?}` — contoh: `{judul: "Figuran iklan Ramadan", keterangan: "Stasiun kereta, pagi", tahun: 2025}`.
2. Isi lama kolom `pengalaman` (teks) dipindah jadi entri pertama (migration data), lalu form pakai daftar ini. Kolom lama dibiarkan dulu (jangan drop) biar aman.
3. Form: baris per pengalaman + tombol **"+ Tambah pengalaman"** + hapus per baris (pola sama persis dengan tautan tambahan, termasuk render ulang dari `old()` saat validasi gagal). Maks 20 entri.
4. **Kemampuan** nggak jadi field terpisah — sudah ditangani tag (grup Kemampuan). Tulis hint: "Kemampuan (naik motor, menari, dll) tambahkan sebagai tag di atas."
5. Section **Tautan tambahan dipindah ke tepat di bawah Pengalaman**, hint: "Punya portofolio/showreel? Taruh link-nya di sini."
6. Profil (BH.1, section 02) & kartu/detail Admin tampilkan pengalaman sebagai daftar berurutan (tahun terbaru di atas).

## BJ.4: Dashboard Extras — urutan baru (mobile & desktop sama)

1. **Perlu tindakan** — paling atas, **hanya muncul kalau ada** (nego menunggu balasan, kontrak perlu TTD, lengkapi KTP, konfirmasi pembayaran, absen hari ini, profil belum lengkap). Kalau nggak ada → section **disembunyikan total** (bukan "Tidak ada tindakan").
2. **Hapus** tombol "Lihat Profil Saya" dan "Lihat Casting Call" di dashboard (sudah ada di menu bawah).
3. **Casting Call terbuka** — maks 5 lowongan (kartu ringkas: nama produksi, peran yang cocok + badge % cocok kalau ada, sisa kuota, deadline, tombol Daftar) + link "Lihat semua". Kosong → "Belum ada lowongan terbuka. Nanti kami kabari kalau ada yang baru."
4. **Pendaftaran Saya** (step-bar yang sudah ada).
5. **Status Talenta** (grade, status akun, apresiasi, linimasa).
6. **Jadwal** (kalender) — paling bawah.

## BJ.5: Menu bawah HP (Extras)

Urutan bottom nav: **Casting Call (kiri) · Beranda/Dashboard (tengah) · Profil (kanan)**. Item tengah sedikit ditonjolkan (ikon rumah, bisa lingkaran accent). Label "Dashboard" di HP jadi "Beranda". Sidebar desktop boleh ikut urutan ini juga biar konsisten. Setelah login, Extras tetap mendarat di Dashboard (tengah).

## Checklist BJ

| Item | Bukti | QA |
|---|---|---|
| BJ.1 tag bebas + autocomplete + normalisasi + Kelola Tag | `b1522e8` + panel saran on-focus `7076377` — `--filter BjTagBebasTest` ("dewasa muda" nggak dobel, "#Bisa  silat" → Lainnya, >15 ditolak, gabung tag, D22 publik). Kelola Tag di `/admin/tag` | [ ] |
| BJ.2 section foto gabungan + tombol "+" | `3a8221f` — `--filter BjFotoSectionTest` | [ ] |
| BJ.3 pengalaman daftar "+" + tautan di bawahnya | `7cb7d1c` — `--filter BjPengalamanTest`. Tambahan Fakrul: bahasa daftar "+" `3c0ef2b`; berat badan + warna kulit jadi tag grup "Warna kulit" `6614432` (`--filter BjBeratWarnaKulitTest`) | [ ] |
| BJ.4 urutan dashboard Extras | `b9d36dd` — `--filter BjDashboardExtrasTest` (urutan 1–6, Perlu tindakan hilang kalau kosong, maks 5 lowongan). Lowongan yang sudah didaftar disembunyikan dari list dashboard | [ ] |
| BJ.5 bottom nav Extras | `ccc137f` — `--filter BjBottomNavExtrasTest`. SQLite & MySQL 502 passed; `migrate` + seed tag di jbtb sudah | [ ] |

**Tes QA (HP):** ketik tag "dewasa muda" → otomatis pakai tag "Dewasa muda" yang ada (nggak dobel). Tambah tag baru "Bisa silat" → muncul di "Lainnya", Admin pindahin ke grup Kemampuan. Upload 1 foto utama + 2 galeri lewat tombol "+". Tambah 3 pengalaman. Dashboard tanpa tindakan → section Perlu Tindakan nggak muncul.

---

# Bagian BK: Notif link, profil desktop, dashboard Extras, jadwal bentrok (30 September 2026)

> Dari tes manual Fakrul 30 Sept. Subagent wajib (BK.4 nyentuh status pendaftaran & kontrak). Commit per sub-bagian.

## BK.1: Link notifikasi nyasar ke ngrok — SUDAH DIPERBAIKI manager, tinggal test + commit

Penyebab: `.env` `APP_URL=https://ferris-hardy-judo.ngrok-free.dev` + URL notif disimpan **absolut** (`route()` penuh), jadi notif yang dibuat saat akses lewat ngrok / dari command terjadwal nyasar ke ngrok walau dibuka dari localhost. Fix (sudah di working tree): `InAppNotification::relatif()` — URL disimpan & dirender **tanpa domain** (`/extras/nego/11`), notif lama di DB juga dinormalisasi saat render (`layouts/app.blade.php`). Claude Code: tambah test (notif dengan URL `https://x.test/extras/nego/1?a=b` → tersimpan `/extras/nego/1?a=b`), commit. Catatan buat Fakrul: kalau lagi kerja di localhost, `APP_URL` sebaiknya `http://localhost:9999` (link di email/WA ikut `APP_URL`).

## BK.2: Profil Extras — desktop dirapikan, mobile JANGAN diubah

Tampilan mobile profil sekarang sudah pas (disetujui Fakrul) — **dikunci**. Yang berantakan tampilan desktop. Rapikan hanya di `@media (min-width: 860px)`:
- Hero 2 kolom seperti moodboard (`docs/ui-prototype/moodboard-sideroom/profile-page.tsx`): kiri foto utama lebar 360–420px, kanan username besar + chip + grade, rata bawah.
- Section 01–05 jadi grid 2 kolom (01 Data diri | 02 Pengalaman, 03 Showreel | 04 Tarif), 05 Galeri full-width grid 4 kolom. Garis tipis antar sel.
- Maks lebar konten ±1100px, di tengah.
- Bukti wajib: screenshot 375px (harus sama dengan sebelum BK.2) **dan** 1280px, dark & light.

## BK.3: Dashboard Extras — tombol dobel & urutan

1. "Lanjut nego" (dan aksi lain yang juga ada di kartu pendaftaran) muncul dua kali. Di section **Perlu tindakan**, item yang terkait satu pendaftaran jadi **teks + link lompat** ke kartunya ("Negosiasi fee *Iklan Minuman Segar* menunggu balasanmu ↓" → scroll ke `#pendaftaran-{id}`), **tanpa tombol**. Tombol aksi cuma di kartu pendaftaran. Item yang nggak punya kartu (lengkapi profil, absen hari ini) tetap boleh punya tombol.
2. Urutan baru: **Perlu tindakan → Pendaftaran saya → Casting call terbuka → Status talenta → Jadwal.**
3. **Pendaftaran saya = satu kartu per proyek** yang didaftar (sudah begitu di kode — pastikan), urut tanggal shooting terdekat; yang selesai/ditolak/batal diringkas di bawah ("Riwayat (3) ▾").

## BK.4: Jadwal bentrok antar proyek (Extras daftar di 2+ proyek)

Kondisi sekarang (dicek): `activeShootingDates()` + `bentrok_jadwal_flag` sudah ada, tapi cuma **peringatan** (non-blocking) di `apply()` dan `ajukanKeCd()`. Perketat berdasarkan seberapa "pasti" proyek pertama:

1. **Saat daftar proyek B**, cek tanggal shooting B vs semua pendaftaran aktif lain:
   - Bentrok dengan pendaftaran yang sudah **lolos / kontrak ditandatangani** (sudah pasti syuting) → **tolak daftar**: "Kamu sudah terjadwal syuting *{proyek A}* tanggal {tgl}. Batalkan dulu yang itu kalau mau ikut proyek ini."
   - Bentrok dengan pendaftaran yang **masih proses** (diajukan s/d diajukan ke Client) → **boleh daftar**, tapi muncul konfirmasi "Tanggal ini bentrok dengan *{proyek A}* yang masih diproses. Kalau dua-duanya lolos, kamu wajib pilih salah satu." + `bentrok_jadwal_flag = true` di **dua-duanya**.
   - Tampilkan juga di detail lowongan sebelum daftar: badge "Bentrok dengan jadwalmu" di peran/tanggal yang kena.
2. **Saat salah satu dipilih Client (jadi `lolos`)** dan masih ada pendaftaran lain yang bentrok → pendaftaran lain itu masuk **Perlu tindakan** Extras: "Jadwal bentrok: *{A}* sudah pasti, *{B}* tanggal sama. Batalkan *{B}*?" (tombol pakai alur `batalkan` yang sudah ada, alasan otomatis "Bentrok jadwal"). Admin proyek B dapat notifikasi.
3. **Tanda tangan kontrak** ditolak kalau Extras sudah punya kontrak lain yang ditandatangani di tanggal yang sama — pesan jelas + link ke pendaftaran yang bentrok.
4. **Jadwal berubah mendadak** (Admin/Client edit/tambah tanggal shooting) dan bikin bentrok dengan kontrak/lolos yang sudah ada → set flag, notifikasi ke Extras + Admin kedua proyek, masuk Perlu tindakan Extras untuk pilih salah satu.
5. Kartu pendaftaran yang bentrok: badge merah "Bentrok jadwal dengan {proyek}" di **kedua** kartu; di Lineup Admin badge yang sudah ada tetap.
6. Test: (a) A kontrak tgl 5, daftar B tgl 5 → ditolak; (b) A masih nego, daftar B tgl 5 → boleh + flag di dua-duanya; (c) A jadi lolos → B masuk Perlu tindakan; (d) TTD kontrak B saat A sudah TTD tgl sama → ditolak; (e) Admin tambah tanggal di A yang bentrok dengan kontrak B → notif + flag.

**D23 DIPUTUS Fakrul (30 Sept):** pembatalan karena bentrok — termasuk yang dipicu jadwal diubah JBTB/Client (poin 4) — **tetap dihitung batal mendadak** seperti pembatalan biasa. Jangan bikin pengecualian. Di pesan konfirmasi batal, tulis jelas: "Pembatalan ini dihitung sebagai batal mendadak."

## Checklist BK

| Item | Bukti | QA |
|---|---|---|
| BK.1 notif URL relatif (test + commit) | `98ffe54` — `--filter BkNotifUrlRelatifTest`. Catatan: `APP_URL` localhost saat kerja lokal | [ ] |
| BK.2 profil desktop rapi, mobile tetap | `6e70d63` (merge) — screenshot 375px sebelum/sesudah identik (hash file, 6 render); popup 760px nggak berubah | [ ] |
| BK.3 dashboard Extras: tanpa tombol dobel, urutan baru | `3bb7e0f` — `--filter "BkDashboardExtrasTest|BjDashboardExtrasTest"` | [ ] |
| BK.4 jadwal bentrok (blokir/peringatan/perlu tindakan/TTD/jadwal berubah) | `0ca23a6` — `--filter BkJadwalBentrokTest` (a–e + D23). Tombol batal ada di kartu B (Perlu tindakan cuma teks+link, BK.3). SQLite & MySQL 514 passed | [ ] |

---

# Bagian BL: Monitoring Admin & Korlap — halaman pratinjau dulu, masuk mode kalau mau aksi (30 September 2026)

> Arahan Fakrul: sekarang klik Monitoring ▸ Admin/Korlap langsung mengganti seluruh tampilan ke POV Admin/Korlap. Maunya: **pratinjau ringkas dulu** (apa yang sedang jalan), dan baru **masuk mode** (BD.6.1) kalau Super Admin mau aksi. Client & Extras tetap seperti sekarang (pilih akun → lihat saja).
> **Jangan tulis query baru yang menduplikasi** dashboard Admin / halaman Absensi — ekstrak ke method yang dipakai bareng (mis. `AdminRingkasan::untuk()` / scope di model), lalu dipakai halaman pratinjau **dan** dashboard asli.

## BL.1: Monitoring ▸ Admin (pratinjau)

Halaman `super-admin/monitoring/admin` (tanpa ganti sidebar, tanpa banner mode):
1. **Kartu angka** (klik → masuk mode Admin + langsung ke halaman terkait yang sudah terfilter): Nego menunggu balasan Admin · Kandidat Deal siap diajukan ke Client · Kontrak menunggu TTD Admin · Pembayaran Extras belum ditransfer · Proyek tanpa PIC/Client.
2. **Per Admin** (tabel ringkas): nama, jumlah proyek PIC aktif, item menunggu dia (nego + kontrak), aksi terakhir (dari ActivityLog, waktu relatif). Klik nama → detail akun (Manajemen Akun).
3. **Proyek berjalan** (maks 5): nama, PIC, tahap, pendaftar/kuota, progres (terisi · deal · lolos).
4. Tombol utama kanan atas: **"Masuk mode Admin"** (menjalankan BD.6.1 yang sudah ada).

## BL.2: Monitoring ▸ Korlap (pratinjau)

Halaman `super-admin/monitoring/korlap`:
1. **Shooting hari ini & besok** per proyek: lokasi, jam, Korlap yang ditugaskan, absensi **X/Y hadir · Z menunggu validasi · W tidak hadir** (bar kecil). Hari tanpa shooting → "Tidak ada shooting hari ini" + tanggal shooting terdekat.
2. **Menunggu validasi** (maks 10 terbaru): foto selfie kecil, nama Extras, proyek, jam kirim. Klik → masuk mode Korlap di halaman absensi proyek itu.
3. **Catatan lapangan terbaru** (maks 5): isi singkat, Korlap, Extras, jenis (catatan/sanksi).
4. Tombol utama: **"Masuk mode Korlap"**.

## BL.3: Aturan

- Pratinjau = **murni baca**, nggak memicu apa pun (ingat BE.1).
- Masuk mode dari kartu/baris membawa tujuan (`?ke=...`) supaya Super Admin langsung mendarat di halaman aksi yang relevan, bukan dashboard Admin/Korlap dari awal.
- Keluar mode (banner) balik ke halaman pratinjau yang tadi, bukan ke dashboard SA.
- Submenu Monitoring: Admin · Korlap mengarah ke pratinjau; Client · Extras tetap ke pemilih akun.

## Checklist BL

| Item | Bukti | QA |
|---|---|---|
| BL.1 pratinjau Admin | `8f34ced` (merge) — `MonitoringController@admin`, angka dari `AdminRingkasan::untuk()` (juga dipakai dashboard Admin); `--filter BlMonitoringPratinjauTest` | [ ] |
| BL.2 pratinjau Korlap | `8f34ced` — `KorlapRingkasan` (`peserta()`/`rekap()` juga dipakai `AttendanceController@index`) | [ ] |
| BL.3 masuk mode dengan tujuan, keluar balik ke pratinjau | `8f34ced` — `ke` cuma `/admin/...` (URL luar ditolak), `sa_kembali` cuma `/super-admin/monitoring/...`; pratinjau GET nggak nulis apa pun | [ ] |

---

# Bagian BM: Diet database + rename "cd" → "client" (30 September 2026)

> Arahan Fakrul: database **sesedikit mungkin** — yang nggak kepakai atau nggak penting di-drop, sisa istilah `cd` diganti `client`. Keputusan: **1 proyek = 1 akun Client** (kalau ada lebih dari satu orang di pihak Client, mereka pakai akun yang sama).
>
> **WAJIB subagent, BERURUTAN (BM.1 → BM.2 → BM.3), jangan paralel** — ketiganya menyentuh file yang sama. Tiap langkah: full test SQLite + MySQL `jbtb_test` hijau, `migrate:fresh --seed` sukses, baru commit. **Nggak perlu backup `jbtb`** (keputusan Fakrul): setelah BM beres, `jbtb` di-reset pakai `migrate:fresh --seed` (seeder demo diperbarui di BM.4).
>
> **Aturan drop:** sebelum menghapus tabel/kolom, `grep` pemakaiannya di `app/`, `resources/`, `routes/`, `database/seeders/`, `tests/`. Kalau ternyata dipakai untuk fitur yang masih hidup → **jangan drop**, catat alasannya. Data lama dipindah dulu (migration data) sebelum kolom/tabel lama dihapus. Hasil akhir wajib dilaporkan sebagai tabel "Dihapus / Dipertahankan + alasan" di kolom Bukti.

## BM.1: Tabel yang dihapus / digabung

| Tabel | Jadi | Catatan wajib |
|---|---|---|
| `cd_project_assignments` | **drop**, pakai `casting_projects.client_id` | Semua cek akses Client (Greenlight, jadwal, invoice, foto absensi, lampiran, export riwayat, reminder H-3) diganti satu helper `CastingProject::milikClient(User $u)` / scope `milikClient($u)`. Cabut akses saat ganti Client (BG.1) otomatis beres karena cukup ganti `client_id`. |
| `extras_photos` | kolom `extras_profiles.foto_tambahan` (json, maks 4 path) | Pindahkan data; route stream per slot (`/foto-tambahan/{slot}`) & upload/hapus tetap jalan dengan kontrak yang sama. |
| `admin_profiles` | kolom `users.honor_nominal` (nullable, cuma dipakai staf) | `honor_updated_at` & `created_by` dibuang — perubahan honor sudah tercatat di ActivityLog. |
| `notifications_log` | **drop, digabung ke tabel `notifications`** (satu tabel notifikasi per user untuk semua role) | Dicek manager: tabel ini cuma **ditulis** (`ProjectApplication`, `WhatsAppService`, job WA) dan nggak pernah dibaca. Penggantinya: tiap kejadian yang kirim WA/email juga membuat notifikasi in-app biasa, dan status kirimnya disimpan di `notifications.data` (`wa` / `email`: `terkirim` · `gagal` · `null`, plus `wa_dikirim_at`). Job WA meng-update data notifikasi itu setelah selesai. Tampilkan ikon kecil status WA di halaman detail akun (Aktivitas/Notifikasi) buat Admin/SA, supaya kalau WA gagal bisa dikabari manual. Reminder terjadwal (H-1, H-3) cek di tabel ini supaya nggak kirim dobel ke orang yang sama untuk proyek & tanggal yang sama. |

`cancellations` **dipertahankan** (riwayat batal + alasan dibutuhkan untuk aturan 3× batal mendadak & D23).

## BM.2: Kolom dobel / sisa lama (cek dulu, lalu drop)

- `casting_projects`: `wa_group_link` vs `link_grup` → sisakan **satu** (yang dipakai view), pindahkan datanya. `diajukan_oleh_client_id` → drop (asal-usul proyek sudah terbaca dari `client_request_status`; pengaju = `client_id`). `client_ph` → drop, tampilan pakai `client->nama_perusahaan` (backfill `nama_perusahaan` dari `client_ph` dulu; proyek lama tanpa Client → buat/tautkan akun Client dulu atau biarkan `client_ph` **hanya** kalau memang ada proyek yang tak bisa ditautkan — laporkan). `kuota` level proyek → drop kalau bisa dihitung dari jumlah `kuota_kelas`. `cover_path` vs `poster_path` → sisakan satu kalau dua-duanya dipakai untuk hal yang sama.
- `extras_profiles`: `pengalaman` (teks lama, sudah dipindah ke `riwayat_pengalaman` di BJ.3) → drop. `cancel_count` → drop, hitung dari `cancellations` (`is_mendadak`). `berat_badan` / `warna_kulit` → drop kalau sekarang sudah jadi tag (commit BJ "berat badan + warna kulit jadi tag"). `share_token` → drop kalau link profil publik sudah pakai username; kalau masih token, pertahankan.
- `project_applications`: 4 kolom `*_override` (karakter/scene/jam callingan/continuity) → drop kalau nggak ada UI yang mengisinya.
- `casting_project_classes`: `karakter` vs `nama_kelas` → sisakan satu kalau isinya sama fungsinya.
- `cd_reviews.bulk_batch_id` → drop kalau nggak dipakai.
- Status `direview_cd` (nggak pernah di-set) → dihapus dari konstanta, filter, dan enum.

## BM.3: Rename "cd" / "casting director" → "client" di seluruh kode

- Tabel `cd_reviews` → `client_reviews`; model `CdReview` → `ClientReview`; kolom `cd_id` → `client_id`, `grade_cd` → `grade_client`.
- `invoices.ttd_cd_signature_path` → `ttd_client_signature_path`.
- Status `diajukan_ke_cd` → `diajukan_ke_client` (migration data + enum MySQL; hati-hati urutan: tambah nilai baru → update data → hapus nilai lama).
- Route prefix `/cd/...` → `/client/...`, nama `cd.*` → `client.*`. **Redirect permanen dari URL lama** (`/cd/{any}` → `/client/{any}`) supaya bookmark & link notifikasi lama tetap jalan.
- Namespace `App\Http\Controllers\Cd\*` digabung ke `App\Http\Controllers\Client\*`; view `resources/views/cd/*` → `resources/views/client/*`.
- Hapus `User::isCastingDirector()` (pakai `isClient()`), sisa alias `casting_director` di middleware/route/test, dan teks UI "CD"/"Casting Director" yang tersisa.
- Grep akhir `\bcd\b|cd_|_cd\b|CastingDirector|casting_director` di `app/ resources/ routes/ database/ tests/` → harus kosong (kecuali migration lama yang memang sejarah).

## BM.4: Setelah beres

Update `DemoLengkapSeeder` + `DemoSeederTest`, isi Bukti, tulis DEV-NOTES. **Manager akan menulis ulang `docs/DATABASE-SCHEMA.md`** dari hasil akhir — laporkan daftar tabel final + jumlah kolom per tabel.

## Checklist BM

| Item | Bukti | QA |
|---|---|---|
| BM.1 drop/gabung tabel | `9003bdd` — migration `2026_09_30_300001`: `cd_project_assignments` (→ `client_id`, helper `CastingProject::milikClient()`/scope), `extras_photos` (→ `extras_profiles.foto_tambahan`), `admin_profiles` (→ `users.honor_nominal`, ubah honor dicatat `UPDATE_HONOR`), `notifications_log` (→ `User::kabari()`, status `email`/`wa`/`wa_dikirim_at` di `notifications.data`, reminder anti-dobel via `kunci`, ikon status di detail akun SA). Test `BmDietDatabaseTest`, `ClientAksesProyekTest`. | [ ] |
| BM.2 drop kolom dobel/sisa | `f51741f` — migration `2026_09_30_300002` (data dipindah dulu, lihat tabel di bawah). | [ ] |
| BM.3 rename cd → client + redirect URL lama | `30d03c8` — migration `2026_09_30_300003` (`client_reviews`, `client_id`, `grade_client`, `ttd_client_signature_path`, `diajukan_ke_client`), route/view/namespace `client`, `GET /cd/{any}` → 301 `/client/{any}` (+query). Grep akhir bersih kecuali route redirect itu sendiri, test redirect, dan migration lama. | [ ] |
| BM.4 seeder, test, laporan tabel final | Commit BM.4 — `DemoLengkapSeeder` (grade_client, 3 batal mendadak joko_s dari `cancellations`, status WA/email di beberapa notif, Client arsip portofolio) + `DemoSeederTest`. Skema final 32 tabel (dari 36). | [ ] |

**Dihapus / Dipertahankan (BM)**

| Tabel/kolom | Hasil | Alasan |
|---|---|---|
| `cd_project_assignments` | Dihapus | 1 proyek = 1 Client; data kosong `client_id` diisi dari assignment pertama |
| `extras_photos` | Dihapus | Pindah ke `extras_profiles.foto_tambahan` json {slot: path} |
| `admin_profiles` | Dihapus | Pindah ke `users.honor_nominal` |
| `notifications_log` | Dihapus | Cuma ditulis; status kirim sekarang di `notifications.data`. Baris lama tidak dipindah (tanpa isi pesan, bakal jadi notif kosong) |
| `casting_projects.wa_group_link` | Dihapus | Digabung ke `link_grup` |
| `casting_projects.diajukan_oleh_client_id` | Dihapus | Pengaju = `client_id`; asal pengajuan Client = ada `brief_catatan` |
| `casting_projects.client_ph` | Dihapus | Tampil dari `client->nama_perusahaan`; backfill ke Client kosong; proyek tanpa Client ditautkan ke akun bernama sama atau dibuatkan akun Client nonaktif |
| `casting_projects.cover_path` | Dihapus | Sama fungsi dengan `poster_path`; kalau dua-duanya ada, cover dipindah ke lampiran proyek |
| `casting_projects.kuota` | **Dipertahankan** | Pengajuan Client belum punya peran (`kuota_kelas`), SA ACC berdasarkan kuota ini |
| `casting_projects.share_token` | **Dipertahankan** | Link `/event/{token}` |
| `extras_profiles.pengalaman` / `warna_kulit` | Dihapus | Sudah jadi `riwayat_pengalaman` / tag grup "Warna kulit" (migrasi BJ dijalankan ulang sebelum drop) |
| `extras_profiles.cancel_count` | Dihapus | Hitung dari `cancellations` (`ExtrasProfile::batalMendadak()`, mendadak & oleh Extras) |
| `extras_profiles.berat_badan` | **Dipertahankan** | Keputusan Fakrul: angka baru BJ, bukan tag |
| `extras_profiles.share_token` | **Dipertahankan** | Link profil publik masih token |
| `project_applications.*_override` (4) | **Dipertahankan** | Masih diisi form breakdown per kandidat (`admin.applications.breakdown`) |
| `casting_project_classes.karakter` | Dihapus | Tanpa input UI, dobel `nama_kelas`; nilai beda disalin ke `karakter_override` |
| `cd_reviews.bulk_batch_id` | Dihapus | Cuma ditulis |
| status `direview_cd` | Dihapus | Tak pernah di-set (data diarahkan ke `diajukan_ke_cd` dulu) |
| `cancellations` | **Dipertahankan** | Riwayat batal + aturan 3× & D23 |

---

# Bagian BN: Paket terakhir sebelum FREEZE — ID proyek, auto-hapus akun mangkrak, Favorit (30 September 2026)

> Keputusan Fakrul 30 Sept: ini **fitur terakhir**. Setelah BN (dan BK, BL, BM) beres → **feature freeze**; yang boleh masuk cuma perbaikan bug.
> **Dicoret dari scope** (masuk Bab 3 sebagai "pengembangan lanjutan"): cek kelayakan registrasi (bimbingan D.1), rating sikap 1–5 oleh Korlap, scoring otomatis (bimbingan F.3), fitur "panggil lagi"/re-book, login Google, email perusahaan (Lark). Catatan lapangan Korlap yang sudah ada dianggap cukup sebagai penilaian kualitatif.
> Kerjakan setelah BM (supaya pakai skema yang sudah diet). Commit per item.

## BN.1: ID proyek tampil (bimbingan E.2)

Format tampilan **`JBTB-{tahun}-{id 3 digit}`** (mis. `JBTB-2026-012`), dihitung dari `id` + tahun dibuat — **tanpa kolom baru** (accessor `kodeProyek`). Tampil di: daftar & detail proyek, kartu proyek, invoice (PDF & halaman), kontrak PDF, notifikasi terkait proyek, pemilih proyek di absensi. Bisa dicari di live search Proyek & Keuangan.

## BN.2: Auto-hapus akun mangkrak terjadwal + pemberitahuan (bimbingan D.2–D.3)

Pakai definisi mangkrak yang sudah ada (`User::mangkrak` scope + tombol Prune manual).
1. Command `akun:peringatkan-mangkrak` harian: akun yang **akan** jadi mangkrak dalam 7 hari → kirim notifikasi (in-app + WA/email kalau ada): "Profilmu belum lengkap. Lengkapi sebelum {tanggal} supaya akunmu nggak dihapus otomatis." Cegah kirim dobel dengan cek tabel `notifications` (jenis `peringatan_mangkrak`) — tanpa kolom baru.
2. Command `akun:hapus-mangkrak` harian: hapus akun yang sudah mangkrak **dan** sudah pernah diperingatkan ≥7 hari lalu. Pakai cara hapus yang sama dengan tombol Prune sekarang. Catat di ActivityLog (aktor: sistem).
3. Daftarkan keduanya di `routes/console.php` (jadwal pagi, `Asia/Jakarta`). Butuh `schedule:work` jalan — sudah ada di `info.txt`.
4. Halaman Manajemen Akun: filter/chip "Akan dihapus" (sudah diperingatkan) supaya SA bisa lihat & selamatkan manual.
5. Kebijakan privasi: tambah 1 kalimat tentang penghapusan otomatis akun yang tidak dilengkapi.

## BN.3: Favorit ⭐ (pakai kolom `apresiasi` yang sudah ada — tanpa kolom baru)

`extras_profiles.apresiasi` (+ `apresiasi_catatan`) sudah ada, sifatnya sama: penanda internal Admin/SA. Ubah jadi **Favorit**:
1. Label di UI: "⭐ Favorit" (bukan "Apresiasi"); catatan jadi "Kenapa favorit?" (opsional, mis. "cocok peran bapak-bapak kantoran, on time").
2. Tombol bintang toggle di kartu Extras (Lineup, Manajemen Akun) & modal profil — satu klik, tanpa pindah halaman. Hanya Admin/SA.
3. Filter **"Favorit"** di Lineup & Manajemen Akun (chip di panel filter BI.2), dan urutan "Favorit dulu" sebagai opsi sort.
4. Aturan lama tetap: **nggak pernah tampil ke Client maupun Extras** (test `assertDontSee` yang sudah ada dipertahankan).

## Checklist BN

| Item | Bukti | QA |
|---|---|---|
| BN.1 kode proyek JBTB-YYYY-NNN | `897ee30` — accessor `CastingProject::kodeProyek` (`JBTB-{tahun created_at}-{id %03d}`) + `namaKode()`; tampil di kartu/daftar & detail proyek, invoice halaman/PDF, kontrak PDF, notif proyek (`kabari`/in-app), pemilih absensi, dashboard & jadwal Client. Live search Proyek & Keuangan: `scopeCariKode` (parse `JBTB-2026-012`/`2026-012`/`012` → id + whereYear). Pratinjau notif dropdown 80→100 karakter. Test `BnKodeProyekTest`. | [ ] |
| BN.2 peringatan + auto-hapus akun mangkrak terjadwal | `676af19` — `User::mangkrak($hari = HARI_MANGKRAK=30)`; "akan mangkrak dalam 7 hari" = `mangkrak(23)` (Extras umur ≥ 23 hari, profil belum lengkap, 0 pendaftaran; yang sudah >30 hari tapi belum diperingatkan ikut). `akun:peringatkan-mangkrak` (08:15 WIB, `kabari` jenis/kunci `peringatan_mangkrak`, tanggal = max(daftar+30, hari ini+7), in-app+email+WA) · `akun:hapus-mangkrak` (08:30 WIB, mangkrak + diperingatkan ≥ 7 hari, `User::hapusMangkrak()` = cara tombol Prune, log `AUTO_PRUNE_ABANDONED_USERS` user_id null/role system). Filter "Akan dihapus" di Manajemen Akun (`User::akanDihapus`), kalimat di kebijakan privasi. Test `BnMangkrakTerjadwalTest`. | [ ] |
| BN.3 Favorit ⭐ (dari kolom apresiasi) + filter | `544f05e` — `ExtrasProfile::setFavorit()` (+ log `TOGGLE_EXTRAS_FAVORIT`) dipakai route lama `admin.applications.apresiasi` & baru `PATCH admin.extras.favorit` (bintang kartu Lineup/Manajemen Akun/Kelola Akun + popup profil, redirect ke `#fav-{id}`, tanpa JS). Filter "⭐ Favorit" + urut "Favorit dulu" di Lineup & Manajemen Akun. `ApresiasiTest` diperketat (assertDontSee Apresiasi + Favorit, termasuk halaman review Client). Test `BnFavoritTest`. | [ ] |

---

# Bagian BO: Siap tes WhatsApp beneran + Login Google (siap pakai, aktif lewat config) (30 September 2026)

> Fakrul mau tes notif WA ke nomornya sendiri sekarang, dan login Google disiapkan supaya pas deploy tinggal isi config. Kerjakan setelah BN (masih bagian paket terakhir). Subagent wajib (BO.2 = auth).

## BO.1: Nomor WA dinormalisasi + perintah tes kirim

Temuan manager: `WhatsAppService::kirim()` meneruskan `users.nomor_wa` **apa adanya** ke Node, lalu Node kirim ke `{nomor}@c.us`. Nomor di seeder/DB berformat `+62812…` (dan user bisa ngetik `0812…` / pakai spasi/strip) → **gagal kirim** karena whatsapp-web.js butuh digit murni `62812…`.
1. Satu helper normalisasi (mis. `User::nomorWaInternasional()` atau di `WhatsAppService`): buang semua non-digit, `0` di depan → `62`, `8` di depan → `628`. Dipakai di `kirim()`.
2. Validasi input nomor WA di semua form (registrasi Extras, profil, + Client, + Staf): terima format umum Indonesia, simpan hasil normalisasi.
3. Command `php artisan wa:tes {nomor} {pesan?}` — kirim langsung (tanpa queue) dan tampilkan hasil + pesan error Node (401 token salah / 503 belum scan QR / 500 gagal). Buat tes manual Fakrul.
4. `whatsapp-service/.env.example` + README: jelaskan `PORT`, `WHATSAPP_SERVICE_TOKEN` harus sama persis dengan `.env` Laravel, dan urutan nyalain (Node → scan QR → `wa:tes` → `queue:work`).
5. Test: nomor `0812-3456-789`, `+62 812 3456789`, `812345678` → semua jadi `62…`.

## BO.2: Login/daftar dengan Google (khusus Extras), mati otomatis kalau config kosong

1. `laravel/socialite` (paket resmi). Config `services.google` dari `.env`: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`. **Kalau `GOOGLE_CLIENT_ID` kosong → tombol Google disembunyikan dan route 404** — jadi aman di-merge sekarang, aktif begitu diisi.
2. Tombol **"Lanjut dengan Google"** di halaman login & daftar Extras (desain ikut halaman auth, ikon Google resmi, 44px, di atas form dengan pemisah "atau").
3. Alur:
   - Email Google **belum terdaftar** → buat akun **role extras** (nama & email dari Google, `email_verified_at` terisi, password acak), username dibuat dari email (unik, bisa diubah), wajib centang kebijakan privasi di halaman lanjutan singkat, lalu diarahkan ke lengkapi profil seperti registrasi biasa.
   - Email **sudah terdaftar sebagai Extras** → langsung login (tautkan `google_id`).
   - **Role lain (Admin/Korlap/Client/Super Admin) — REVISI Fakrul 30 Sept: boleh login pakai Google, tapi lewat "hubungkan" dulu, bukan daftar.** Akun mereka tetap dibuat Super Admin. Setelah login pakai username+password, di halaman profil/ubah password ada tombol **"Hubungkan akun Google"** → OAuth → simpan `google_id` (+ isi `email` kalau masih kosong, asal belum dipakai akun lain). Setelah terhubung, tombol "Lanjut dengan Google" di halaman login langsung masuk ke akun itu. Ada juga **"Putuskan Google"** (hanya kalau akun punya password, biar nggak terkunci).
   - Login Google dengan akun Google yang **belum terhubung** dan emailnya cocok dengan akun **non-Extras** → **jangan** otomatis tautkan; tampilkan "Akun ini belum dihubungkan ke Google. Login pakai username & password dulu, lalu hubungkan di menu Profil." (Mencegah akun staf/Client diambil alih lewat email yang kebetulan sama.) Pendaftaran baru via Google tetap **khusus Extras**.
   - Menghubungkan/memutus Google dicatat di ActivityLog. Mode lihat saja (BD.6) nggak bisa menghubungkan Google atas nama akun lain.
   - Akun nonaktif/dihapus → tolak dengan pesan yang sama seperti login biasa.
4. Kolom baru `users.google_id` (nullable, unique). Satu-satunya tambahan skema.
5. Redirect link publik `/event/{token}` (return-to-intent) tetap jalan setelah daftar via Google.
6. **Bisa dites lokal sekarang:** Google OAuth mengizinkan redirect `http://localhost:9999/auth/google/callback` selama OAuth consent screen mode *Testing* (maks 100 test user — daftarkan email sendiri). README/`info.txt`: langkah bikin OAuth Client di Google Cloud Console + isi `.env`.
7. Test (Socialite di-mock): user baru → akun extras dibuat; email Client belum terhubung → ditolak dengan pesan hubungkan; Client yang sudah menghubungkan → login sukses; putuskan Google tanpa password → ditolak; config kosong → tombol nggak ada & route 404.

## BO.3: Penanda field wajib (semua form)

Konvensi baru: field **wajib** diberi tanda bintang merah `*` setelah label (`<span class="wajib" aria-hidden="true">*</span>` + atribut `required`); field **tidak wajib nggak diberi keterangan apa pun** — hapus semua teks "(opsional)" / "opsional" di label & placeholder. Satu baris keterangan kecil di atas form panjang: "* wajib diisi". Berlaku di semua form (auth, profil Extras, proyek, akun, pengajuan Client, dll). Tambahkan aturan ini ke `docs/UI-GUIDELINES.md`.

## Checklist BO

| Item | Bukti | QA |
|---|---|---|
| BO.1 normalisasi nomor WA + `wa:tes` | `60088aa` — `--filter BoWaNormalisasiTest`; `php artisan wa:tes 0812xxxx "tes"`; setup Node di `whatsapp-service/README.md` | [ ] |
| BO.2 login Google: daftar khusus Extras, role lain via "Hubungkan Google" (aktif via config) | `692e3f5` — `--filter BoLoginGoogleTest`; setup Google Cloud di `README.md`. `users.password` jadi nullable (akun Google tanpa password; Putuskan Google cuma kalau punya password) | [ ] |
| BO.3 bintang `*` untuk field wajib, hapus teks "opsional" | `9208b7e` (merge) — `.wajib`/`.wajib-ket` di theme-style, 30 view, `--filter BoFieldWajibTest` | [ ] |

---

# Bagian BP: Dashboard Admin — "Tahapan Partisipasi Kandidat" jadi daftar siapa & harus apa (30 September 2026)

> Keluhan Fakrul: section ini (`admin/dashboard.blade.php` ±baris 90, `funnel-steps` = bar angka per status) kerasa banyak tapi nggak jelas **siapa** Extras-nya dan **harus ngapain**. Maunya mirip step-bar di dashboard Extras. Admin pegang puluhan–ratusan kandidat, jadi bukan step-bar per orang, tapi step-bar sebagai **tab** + daftar orang di tahap itu.
> Datanya **pakai method yang sama dengan pratinjau Monitoring Admin (BL.1)** — jangan query terpisah.

1. **Step-bar 5 tahap** — label & gaya sama persis dengan `partials/application-progress` Extras: Ajuan · Nego Fee · Dipilih Client · Kontrak · Selesai. Tiap langkah menampilkan angka, plus badge merah kecil **"n perlu kamu"** kalau ada kandidat di tahap itu yang menunggu aksi Admin. Langkah = tab (klik ganti daftar, tanpa reload). Default: tahap dengan "perlu kamu" terbanyak.
2. **Daftar di bawahnya** (maks 8, urut paling lama menunggu): foto kecil + `@username`, nama proyek (kode BN.1) · peran, **aksi berikutnya dalam kalimat**, lama menunggu, dan **1 tombol** ke halaman aksinya. Contoh teks aksi:
   - Ajuan: "Belum direview — beri grade / tolak"
   - Nego: "Extras counter Rp 200.000 — balas" (perlu kamu) / "Menunggu balasan Extras (2 hari)"
   - Deal: "Siap diajukan ke Client" (perlu kamu)
   - Dipilih Client: "Menunggu keputusan Client (3 hari)"
   - Kontrak: "Tunggu TTD Extras" / "TTD Admin belum" (perlu kamu)
   - Selesai: "Honor belum ditransfer" (perlu kamu) / "Menunggu konfirmasi Extras"
3. Yang "perlu kamu" ditandai (titik merah / teks tebal), yang menunggu pihak lain abu-abu.
4. Link bawah: "Lihat semua di tahap ini →" ke Lineup/Proyek & Keuangan yang terfilter status (lintas proyek kalau perlu, pakai filter yang sudah ada).
5. Hapus bar `funnel-steps` lama. Kosong di satu tahap → "Nggak ada kandidat di tahap ini."
6. Di HP: step-bar geser horizontal, daftar jadi kartu satu kolom.

| Item | Bukti | QA |
|---|---|---|
| BP tahapan kandidat: step-bar tab + daftar siapa & aksi berikutnya | `ffbcf11` (merge) — `--filter BpTahapanKandidatTest`; data dari `AdminRingkasan::tahapan()` (dipakai dashboard Admin & Monitoring Admin). SQLite & MySQL 574 passed | [ ] |

---

# Bagian BQ: Bugfix pasca-freeze (1 Oktober 2026)

> **FEATURE FREEZE berlaku.** Bagian ini cuma perbaikan bug/konsistensi dari laporan BK–BP.

1. **Angka "honor belum ditransfer" disamakan di semua tempat** (kartu dashboard Admin 7 vs tahapan BP "Selesai · perlu kamu" 6). Satu definisi di satu method: **kontrak sudah ditandatangani + payment `belum_dibayar`** = "perlu ditransfer". Payment yang kontraknya belum TTD tampil sebagai "Menunggu kontrak", bukan dihitung perlu transfer. Kartu dashboard Admin, pratinjau Monitoring Admin (BL), tahapan BP, dan dashboard SA pakai method yang sama. Test: angkanya sama untuk data demo.
2. **Tombol Reset di panel filter** harus mengosongkan semua filter (tag, grade, status, favorit, pencarian) sekaligus dan balik ke halaman 1. Cek di Lineup, Manajemen Akun, Log Aktivitas, Proyek & Keuangan (komponen `x-filter-panel` yang sama).
3. **Password kosong untuk akun Google (BO.2) — diterima.** Pastikan: login username+password untuk akun tanpa password → pesan "Akun ini login pakai Google" (bukan error), "Lupa password" bisa dipakai buat bikin password pertama, dan "Putuskan Google" ditolak selama password kosong.

| Item | Bukti | QA |
|---|---|---|
| BQ.1 satu definisi "perlu ditransfer" | `45bfca6` — scope `Payment::perluDitransfer()` (app `kontrak_ditandatangani` + `belum_dibayar`) dipakai kartu Admin/Monitoring (`AdminRingkasan::untuk`), tahapan Selesai, chart dashboard Admin, filter `?bayar=extras`; belum TTD → badge "Menunggu kontrak". `--filter BqPerluDitransferTest` (demo: 6 di semua tempat). Dashboard SA tidak punya angka honor Extras (tidak ditambah, freeze) | [ ] |
| BQ.2 Reset filter mengosongkan semua | `0cf266d` — `FilterAktif::reset()` cuma pertahankan `per`; "Hapus semua" chip = filter saja (q tetap). Keduanya reload penuh (bar tag/status Lineup di luar area live). `--filter BiFilterPanelTest` | [ ] |
| BQ.3 akun Google tanpa password | `5d70e3c` — pesan "Akun ini login pakai Google" hanya kalau akun ada & password null; lupa password bikin password pertama; putus Google ditolak. `--filter BqAkunGoogleTanpaPasswordTest`. SQLite & MySQL 579 passed | [ ] |

---

# Bagian BR: Rapikan menu Admin + layout mobile & sidebar semua role (1 Oktober 2026)

> Dari tes manual Fakrul. Ini **penataan ulang & perbaikan layout**, bukan fitur data baru — boleh masuk walau freeze. Subagent wajib (>3 file). Kerjakan setelah BQ. Commit per sub-bagian.

## BR.1: Menu Admin — "Rekap Extras" dilebur ke "Kelola Akun ▸ Extras"

Sidebar Admin baru:
- Dashboard
- Proyek & Keuangan
- **Kelola Akun** ▸ **Extras** · **Client**
- Riwayat Kerja
- Absensi Lapangan

**Kelola Akun ▸ Extras** = halaman Kelola Akun Admin sekarang **tanpa bagian Client**, digabung dengan fungsi Rekap Extras:
- Satu search bar + panel filter (`x-filter-panel`, BI.2): tag/kategori, grade, status, favorit ⭐, "sedang aktif di proyek".
- Tampilan **kartu** (default) atau **daftar** (toggle ikon), per halaman (BG.6).
- Tombol **Export Excel** = ekspor daftar sesuai filter yang aktif (pakai logika export `RecapController` yang sudah ada — pindahkan, jangan tulis ulang).
- Menu & route `admin/recap` dihapus → redirect ke `admin/akun/extras` dengan query filter yang setara.

## BR.2: Kelola Akun ▸ Client (untuk Admin) — riwayat, bukan kelola

Admin **nggak mengelola** akun Client (itu urusan Super Admin, BD.1). Hubungan Admin ke Client cuma: mengajukan Extras → Client memilih → **lock** (Extras terpilih dikunci jadi figuran = status `lolos` ke atas). Halaman ini **read-only** untuk Admin:
1. Daftar Client (nama, perusahaan, jumlah proyek, proyek terakhir) + search.
2. Klik Client → **buka ke bawah (accordion)** daftar proyek Client itu: kode proyek, nama, tanggal shooting, tahap, jumlah diajukan / di-lock / ditolak.
3. Klik proyek → buka lagi daftar Extras yang diajukan ke Client itu: foto kecil, @username, peran, status dari sisi Client (**Menunggu keputusan · Lock · Ditolak** + grade Client kalau ada, waktu keputusan). Klik nama → popup profil (BI.1).
4. ~~**Section "Keputusan Client terbaru"** di atas halaman (dan kecil di dashboard Admin): feed 10 keputusan terakhir lintas proyek ("*Client Andini* **lock** @dimas.rk — Iklan Minuman · 5 menit lalu"). **"Realtime" = refresh otomatis tiap 30 detik** (fetch partial, pause saat tab nggak aktif) — **tanpa websocket/Reverb** (keputusan lama). Sama datanya dengan yang dilihat Super Admin (satu method).~~ — **dihapus 1 Okt 2026: dobel dengan notifikasi; keputusan Client cukup lewat notif in-app ke Admin.** Pengganti: Client lock/tolak (single & bulk) → notif in-app `keputusan_client` ke Admin PIC proyek (satu notif per proyek per aksi, isi nama Client + @extras + `namaKode()`, link `/admin/akun/client?q={client}&proyek={id}#proyek-{id}` yang membuka accordion Client & proyek).
5. Tanpa tombol edit/nonaktif/reset di halaman ini.

## BR.3: Layout mobile — lebar meluber & menu kebanyakan

1. **Audit overflow semua halaman semua role di lebar 360px & 390px** (screenshot per halaman sebagai Bukti). Perbaiki sumbernya, bukan `overflow-x: hidden` di body: grid pakai `minmax(0,1fr)`, tabel dibungkus `.table-container`, teks panjang (email, URL, nama proyek) `overflow-wrap: anywhere`, gambar/video `max-width: 100%`, elemen dengan `min-width`/lebar tetap diganti. Tambah 1 test/skrip cek cepat: `document.documentElement.scrollWidth <= innerWidth` di halaman-halaman utama (boleh via screenshot tool yang sudah dipakai).
2. **Menu HP:**
   - **Extras**: bottom nav 3 item (BJ.5) **dipertahankan** — sudah pas.
   - **Admin, Korlap, Client, Super Admin**: bottom nav yang kebanyakan item diganti **tombol hamburger** di topbar kiri → **drawer** dari kiri berisi seluruh menu (sama dengan sidebar desktop, termasuk submenu Kelola Akun/Monitoring), tutup dengan backdrop/Esc/klik item. Menu aktif tersorot.
3. Topbar HP ringkas: hamburger · judul halaman · notif · avatar. Nggak ada elemen yang wrap jadi 2 baris.

## BR.4: Sidebar desktop bisa dibuka/tutup

Tombol toggle di sidebar (atas/bawah) → mode **ringkas** (lebar ±64px, ikon saja + tooltip nama menu) ↔ mode penuh. Pilihan disimpan di `localStorage` (bungkus try/catch). Konten melebar mengikuti. Submenu di mode ringkas muncul sebagai flyout saat hover/klik ikon. Transisi halus, hormati `prefers-reduced-motion`.

## BR.5: "Kelola Tag" bukan halaman sendiri lagi → dialog "Rapikan tag"

Keputusan Fakrul: halaman Kelola Tag (`admin/tag`, tombol di Kelola Akun Admin & Manajemen Akun SA) berlebihan. Normalisasi otomatis (BJ.1.3) sudah mencegah duplikat beda huruf besar/kecil, jadi yang tersisa jarang: tag baru di grup "Lainnya", sinonim ("hijab" vs "Berhijab"), typo.
1. Hapus halaman & tombol "Kelola Tag"; route `admin.tags.index` redirect ke Kelola Akun ▸ Extras.
2. Di panel filter bagian Tag (BI.2), khusus Admin/SA: link kecil **"Rapikan tag (n)"** — `n` = jumlah tag yang perlu dirapikan. Klik → `<dialog>` yang **cuma** menampilkan tag yang butuh perhatian: tag tanpa grup ("Lainnya") dan tag yang dipakai ≤1 Extras (kemungkinan typo). Per baris: nama, jumlah pemakai, aksi **Pindah grup ▾ · Gabung ke… (autocomplete) · Hapus** (konfirmasi). Kosong → link nggak muncul.
3. Logika `TagController::update` & `gabung` dipakai ulang (nggak ditulis ulang), dibungkus respons JSON/partial untuk dialog. Tetap tercatat di ActivityLog.
4. Klik kanan / ikon titik tiga di chip tag pada kartu atau popup profil Extras (Admin/SA) boleh juga memunculkan aksi yang sama untuk tag itu — opsional kalau murah.

## Checklist BR

| Item | Bukti | QA |
|---|---|---|
| BR.1 Kelola Akun ▸ Extras (+ rekap & export), menu Rekap Extras dihapus | `f8e07cb` — `admin/akun/extras` (`admin.akun.extras`, export `admin.akun.extras.export`); filter bersama `App\Support\FilterAkun` (dipakai juga Manajemen Akun SA + `ExtrasRecapExport`), urut + Paling sering terpilih/batal mendadak (pengganti tabel Rekap), kartu/daftar `?tampil=daftar` (per KARTU/TABEL). `admin/users`, `admin/recap`, `admin/recap/export` redirect (kategori_id→tag[]). Sidebar Admin `<details>` Kelola Akun ▸ Extras · Client. `--filter BrAkunExtrasTest`. Shot `scratchpad/shots/br125-extras-{kartu,daftar}-*` | [ ] |
| BR.2 Kelola Akun ▸ Client read-only (accordion Client → proyek → Extras, full width) + notif keputusan Client ke Admin PIC | `677944a` + `cc486e5`/`abc06ff` (layout) — `admin/akun/client` read-only, eager-load per halaman (jumlah query tetap), `ProjectApplication::diajukanKeClient()/sisiClient()`. Feed Keputusan Client (+ polling 30 dtk, paginasi kp/kper, kartu dashboard Admin/SA) **dihapus 1 Okt 2026** → notif in-app ke Admin PIC (`Client\ReviewController::kabariAdmin`), `?proyek=` membuka accordion. `--filter BrAkunClientTest`. Shot `br125-client-*` (layout lama 2 kolom) | [ ] |
| BR.3 mobile tanpa overflow (screenshot 360/390 semua halaman) + hamburger drawer non-Extras | Drawer/topbar HP `7538753`. Audit `405a049` (global) + `13d010e` (per view): 94 halaman (publik 9, SA 25, Admin 21, Korlap 4, Client 11, Extras 12, SA mode Admin 2/Korlap 3/lihat Client 4/lihat Extras 3) × 360 & 390, Edge headless via iframe, cek `scrollWidth <= innerWidth`. Data demo: sebelum 3 halaman overflow (4 dari 184 cek; SA mode Admin ditambah sesudahnya) (Kelola Akun Extras daftar 672px: `.sr-only` absolut lolos dari `.table-container` → `position: relative`; form Pengalaman profil Extras 364px → `grid-template-columns: minmax(0,1fr)`; filter tanggal dashboard SA 368px → `min-width: 0`) → sesudah 188/188 OK. Data teks panjang (nama proyek + URL, email, nama Client, username): sebelum 47 halaman overflow (94/188 cek) (detail/daftar proyek, lineup, kontrak, bayar, nego, invoice, jadwal, dashboard Client & Extras, lowongan, event publik, Monitoring) → `body { overflow-wrap: anywhere }` (tabel/`.btn`/`.badge` tetap normal) + `img, video { max-width: 100% }` → 188/188 OK. Tanpa `overflow-x: hidden`. Profil publik & dashboard Extras identik piksel (beda cuma alt-text foto rusak di avatar). `--filter BrOverflowMobileTest`. Shot `scratchpad/shots/ov/png/` | [ ] |
| BR.4 sidebar desktop buka/tutup | `1c56bc9` — toggle ringkas (64px, ikon + tooltip, submenu flyout), `localStorage` `jbtb-sidebar` (try/catch), hormati `prefers-reduced-motion`. `--filter BrNavigasiSidebarTest` | [ ] |
| BR.5 Kelola Tag jadi dialog "Rapikan tag" | `c58519b` — halaman & tombol Kelola Tag dihapus, `admin/tag` redirect ke Kelola Akun ▸ Extras. Link "Rapikan tag (n)" di panel filter Tag (Admin & SA), `ExtrasCategory::perluDirapikan()` (grup null, atau non-bawaan dipakai ≤1 Extras). Dialog: pindah grup / gabung (autocomplete `/tag/cari`) / hapus (`admin.tags.destroy`, log `TAG_HAPUS`), `TagController::update/gabung` sama + respons JSON. Poin 4 skip. `--filter BrRapikanTagTest`. Shot `br125-rapikan-*`. 600 test SQLite & MySQL | [ ] |

---

# Bagian BS: Bugfix pasca-freeze (2) (1 Oktober 2026)

> **FEATURE FREEZE tetap berlaku.** Bagian ini cuma perbaikan bug, bukan fitur baru.

1. `POST /reset-password` belum dibatasi rate-limit (cuma `/forgot-password` yang dibatasi) — brute-force token reset tidak dicegah.
2. "Tandai transfer" masih bisa diklik Admin/SA godmode sebelum kontrak TTD lengkap (`Payment::menungguKontrak()` sudah ada tapi belum dipakai sebagai guard server di `tandaiTransfer()`).
3. URL salah/tidak ada menampilkan halaman error bawaan Laravel, bukan desain sistem.

| Item | Bukti | QA |
|---|---|---|
| BS.1 throttle reset-password | `routes/web.php` `password.update` + `throttle:5,1` (sama pola `password.email`). `SecurityHardeningTest::test_reset_password_kena_rate_limit_pada_percobaan_keenam`. 609 test SQLite & MySQL `jbtb_test` | [ ] |
| BS.2 blok tandai-transfer sebelum kontrak TTD lengkap | `PaymentController::tandaiTransfer()` guard `$application->payment->menungguKontrak()` → `back()->with('error', ...)`; tombol admin di `payments/show.blade.php` disembunyikan kalau `menungguKontrak()`. `PaymentStatusGateTest::test_transfer_ditolak_saat_kontrak_belum_ditandatangani`. Regresi ketemu & diperbaiki: `BdMonitoringModeTest` godmode SA tadinya nguji perilaku lama (transfer lolos tanpa TTD) — disesuaikan ke perilaku baru (blocked, godmode tetap bisa lihat kontrak/invoice/payment) | [ ] |
| BS.3 halaman 404 sesuai desain | `resources/views/errors/404.blade.php` (extends `layouts.auth`, guest-safe, tanpa `layouts.app`). `SecurityHardeningTest::test_halaman_404_tampil_untuk_url_ngaco` | [ ] |

---

# Bagian BT: Bugfix — rapikan toolbar Lineup (1 Oktober 2026)

> **FEATURE FREEZE tetap berlaku.** Bugfix/UI cleanup, bukan fitur baru.

Toolbar `admin/projects/applicants` (Lineup) numpuk 3 baris `.xfilter` (Grade, Status, Tag+Urutkan) + bulk-form selalu tampil sebagai card biasa — nggak konsisten sama pola `x-filter-panel` yang udah dipakai halaman lain (Kelola Akun, Manajemen Akun SA).

| Item | Bukti | QA |
|---|---|---|
| BT.1 toolbar satu baris + filter panel (Grade/Status/Tag/Favorit) + bulk bar sticky | `resources/views/admin/projects/applicants.blade.php`: toolbar `.xtoolbar` (cari · Filter · Urutkan · per halaman), toggle "Lineup/Sudah ke Client" dipisah dari filter (bukan filter, ganti halaman). Status jadi multi-select (`status[]`, grup Aktif/Selesai-Berhenti) — backward compat `?status=single` tetap jalan (`CastingProjectController::showApplicants` pakai `array_intersect`). Tag pindah ke panel jadi checkbox (`tag[]`), bukan pill link. Bulk bar (`#bulk-toolbar`) sticky muncul kalau ada `.bulk-check` tercentang (pola sama `super-admin/akun/index.blade.php`). `FilterAktif::hapusSemua()` tambah param `$pertahankan` (dipakai applicants biar "Hapus semua" nggak ikut hapus `urut`) — default `[]`, nggak ubah halaman lain. `--filter BiFilterPanelTest`. 610 test SQLite & MySQL `jbtb_test` | [ ] |

---

# Bagian BU: Daftar Proyek dirampingkan (4 Oktober 2026)

> **FEATURE FREEZE tetap berlaku.** Ini pengurangan/penyederhanaan tampilan, bukan fitur baru. Kerjakan BU lebih dulu, BV sesudahnya.

Keputusan Fakrul (didukung review solution architect): halaman daftar `admin/projects` ("Proyek & Keuangan") terlalu padat, susah dipahami user. Prinsip: **daftar = info penting + penanda perlu tindakan; info lengkap ada di "Lihat detail"**.

1. Menu Admin & SA "Proyek & Keuangan" → **"Proyek"** (label menu, judul halaman, breadcrumb). URL/route tetap.
2. Setiap proyek di daftar cuma menampilkan: kode + nama, Client, badge tahap (Menunggu ACC / Mendatang / Berjalan / Selesai), jadwal terdekat, pendaftar/kuota, penanda "perlu tindakan (n)", tombol **Lihat detail**. Semua angka uang, ringkasan cashflow, dan chip/info lain **dikeluarkan dari daftar** (bukan dihapus, pindah ke detail).
3. Detail proyek: tab **Info · Pendaftar (Lineup) · Keuangan (isi: BV) · Lampiran**. Isi tab lama dipindah, tidak ditulis ulang.
4. Sesuaikan test yang meng-assert teks/struktur daftar lama (`BdSidebarFinalTest`, `ProyekKeuanganTest`, `BnKodeProyekTest`, dll).

---

# Bagian BV: Keuangan = pencatatan, bukan perhitungan (4 Oktober 2026)

> **FEATURE FREEZE tetap berlaku.** Ini pengurangan scope. Subagent WAJIB (menyentuh pembayaran, >3 file).

**Dasar keputusan:** kebutuhan dospem hanya penggajian staf Admin + riwayat kerja tiap Admin (RF-40–49). Analisis keuangan proyek bukan kebutuhan dospem, bergantung pada keputusan bisnis yang belum final (D4–D7), dan ranahnya urusan internal JBTB. Sistem **mencatat** arus uang per proyek; JBTB yang menganalisis.

**DIPERTAHANKAN — jangan disentuh:**
- Pembayaran honor Extras: `Payment` (status, bukti transfer, sengketa, add-on, guard BS.2), kontrak.
- Penggajian staf: `StaffPayroll`, add-on honor, slip PDF, `status_bayar`, Riwayat Kerja, rekap honor SA, `totalHonorStafBelumDiproses()`.
- Invoice sebagai **dokumen**: PDF, TTD canvas, dokumen kustom, tandai lunas. Rincian per kelas di PDF tetap (itu dokumen, bukan analisis).
- Biaya lain-lain (`project_expenses`) + ActivityLog.

**DIHAPUS (kode + tampilan + test terkait):**
- `KeuanganService::marginBulanIni`, `trendMarginBulanan`, `cashflowProyek`, `ringkasanPeriode`, konstanta `RELASI_CASHFLOW`.
- Halaman/route `rekap-margin` (+ redirect-nya), kartu/chart uang per periode dan filter periode uang di dashboard SA.
- Istilah Masuk / Piutang / Keluar / Saldo / Proyeksi / Terpakai % di UI manapun.
- Test yang hanya menguji angka-angka itu (mis. `BePiutangTest`, `BgLabelKeluarTest`, bagian angka `ProyekKeuanganTest`). Catat jumlah test yang dihapus/diubah di kolom Bukti, jangan hapus test pembayaran Extras/honor staf/guard.

**DIUBAH:**
1. **Invoice:** `nominal` diisi manual Admin saat buat/ubah invoice. Form diisi usulan awal dari `rincianInvoice()` (budget × kuota) yang bisa diedit. `nilaiInvoice()` dipakai hanya untuk usulan awal dan rincian PDF, tidak dipakai di perhitungan lain.
2. **Tab Keuangan di detail proyek**, empat blok catatan tanpa total gabungan: (a) Invoice: nominal, Lunas/Belum, tombol tandai lunas; (b) Honor Extras: per Extras nominal total (pokok + add-on) + status pembayaran, ringkasan hitungan "n dari m sudah ditransfer"; (c) Honor staf: per staf nominal + status; (d) Biaya lain-lain: daftar + tambah/hapus (sudah ada). Subtotal per blok boleh; **tidak ada** saldo, proyeksi, margin, atau total lintas blok.
3. **Dashboard SA:** kartu uang periode diganti dua angka sederhana: "Honor staf belum dibayar" (`totalHonorStafBelumDiproses()`) dan "Invoice belum lunas (n)". Bagian "Perlu tindakan" tidak berubah.
4. `KeuanganService` tersisa `totalHonorStafBelumDiproses`, `rincianInvoice`, `nilaiInvoice`.
5. Manager yang memperbarui dokumen (bukan Claude Code): `BAB-3-DRAFT.md` (RF-30 jadi pencatatan, dicatat sebagai penyimpangan dari proposal), `PRD-LITE.md`, `SYSTEM-ARCHITECTURE.md`.

| Item | Bukti | QA |
|---|---|---|
| BU.1 menu "Proyek" + daftar ringkas | Label menu `partials/sidebar-admin` & `sidebar-super_admin` (ikon SA jadi `ti-movie`), judul/`@section('title')`, breadcrumb detail, link dashboard SA jadi "Proyek" (URL/route tetap). `admin/projects/index.blade.php`: kartu: kode+nama + badge Urgent, Client, badge tahap, jadwal (`rentangShooting()`), status lowongan (Dibuka/Ditutup), pendaftar/kuota, badge "Perlu tindakan (n)" (n = jumlah lamaran `perlu` per proyek dari `AdminRingkasan::perluPerProyek()`, satu definisi `langkah()` dengan dashboard Admin, satu set query per halaman paginasi, tanpa N+1; link ke tab Pendaftar), tombol "Lihat detail". Dikeluarkan dari kartu: PIC, deadline, link grup, badge Client belum diisi, kotak uang. `CastingProject::isUrgent()` pakai relasi `shootingDates` yang sudah di-load (bukan query per kartu). Menu ⋮ tetap (aksi, bukan info): Detail, Lineup (pindah dari tombol), Edit, Copy link, Tutup/Buka lowongan, Invoice; item Cashflow dihapus. Test disesuaikan: `BdSidebarFinalTest`, `BrAkunExtrasTest` (teks menu), `LinkGrupTampilTest` (link grup dicek di detail, bukan daftar), `ProyekKeuanganTest::test_daftar_proyek_search_chip_tanpa_angka_uang`. 607 test SQLite & MySQL `jbtb_test` (+2 test kartu: Urgent/lowongan, n sama dengan `AdminRingkasan` + jumlah query tetap) | [ ] |
| BU.2 tab Info/Pendaftar/Keuangan/Lampiran di detail | `CastingProjectController::show`: tab `cashflow` → `keuangan` (isi Info/Pendaftar/Lampiran dipindah apa adanya, tidak ditulis ulang); relasi keuangan cuma di-load saat tab Keuangan. Link dashboard SA & `AyP0GuardsTest` ikut `tab=keuangan`. `ProyekKeuanganTest::test_detail_proyek_empat_tab_dan_tandai_dibayar_honor_staf` | [ ] |
| BV.1 hapus analisis keuangan (service, rekap-margin, dashboard SA, label) | `KeuanganService`: `marginBulanIni`, `trendMarginBulanan`, `cashflowProyek`, `ringkasanPeriode`, `RELASI_CASHFLOW` dihapus (tersisa `totalHonorStafBelumDiproses`, `rincianInvoice`, `nilaiInvoice`). `KeuanganProyekController::rekapMargin` + route `admin.recap-margin` & `super-admin.recap-margin` (+ redirect) dihapus. Dashboard SA: kartu "Uang Periode Ini", chart bulanan (Chart.js) & teks Masuk/Piutang/Keluar/Saldo/Proyeksi dihapus; `DashboardController` tidak lagi menghitung `$uang`. Filter periode dashboard TETAP (dipakai Status Proyek via tanggal shooting, `BePeriodeKartuTest`), cuma tooltip "uang pakai tanggal transaksi" dibuang. **Test dihapus 7**: `BePiutangTest` (file, 2 test), `BgLabelKeluarTest` (file, 1 test), `ProyekKeuanganTest` 4 test (`test_cashflow_proyek_saldo_dan_persen_benar`, `test_cashflow_tanpa_masuk_persen_null_dan_invoice_belum_pakai_nilai_live`, `test_ringkasan_periode_abaikan_transaksi_di_luar_periode`, `test_rekap_margin_lama_redirect_ke_proyek_keuangan`). **Diubah 1**: `SuperAdminDashboardTest` `test_filter_custom_menghitung_uang_dari_tanggal_transaksi` → `test_dashboard_dua_angka_honor_staf_dan_invoice_belum_lunas`; `BdSidebarFinalTest::test_route_lama_redirect_bukan_404` dua URL rekap-margin dibuang. Test pembayaran Extras/honor staf/guard BS.2/invoice dokumen tidak dihapus | [ ] |
| BV.2 invoice nominal manual + usulan awal | Route baru `PATCH admin/projects/{p}/invoice-nominal` (`admin.projects.invoice-nominal` → `KeuanganProyekController::simpanNominal`): validasi `numeric|min:0`, `firstOrCreate` invoice, ditolak kalau sudah lunas, ActivityLog `INVOICE_NOMINAL_SET`. Form di tab Keuangan diisi `nominal` tersimpan, kalau belum ada `nilaiInvoice()` (usulan, bisa diedit); `tandaiLunas` tidak diubah. PDF/`rincianInvoice` tidak diubah. Test baru `ProyekKeuanganTest::test_invoice_nominal_manual_dengan_usulan_dari_rincian` (+ guard role di `test_client_extras_korlap_ditolak`) | [ ] |
| BV.3 tab Keuangan = 4 blok catatan | `admin/projects/show.blade.php` tab Keuangan: (a) Invoice Client (nominal, Lunas/Belum, Simpan Nominal, Tandai Lunas, PDF), (b) Honor Extras per Extras nominal `Payment::nominalTotal()` (pokok + add-on) + `x-status-badge` + "n dari m sudah ditransfer", (c) Honor Staf (nominal + status + Tandai Dibayar), (d) Biaya Lain-lain (daftar, tambah, hapus). Kartu Masuk/Piutang/Keluar/Saldo/Proyeksi/Terpakai dan semua total lintas blok dibuang. Test baru `ProyekKeuanganTest::test_tab_keuangan_empat_blok_catatan_tanpa_total_lintas_blok` | [ ] |
| BV.4 dashboard SA: honor staf belum dibayar + invoice belum lunas | Kartu "Yang perlu dicatat": "Honor staf belum dibayar" (`totalHonorStafBelumDiproses()`) dan "Invoice belum lunas (n)" (`$invoiceBelumLunas->count()`). "Perlu tindakan" tidak diubah. `SuperAdminHonorRecapTest::test_honor_staf_sudah_dibayar_tidak_muncul_dan_empty_state_tampil` diubah: assert pada badge baris Perlu tindakan (label kartu baru selalu tampil walau Rp 0). Test: `SuperAdminDashboardTest::test_dashboard_dua_angka_honor_staf_dan_invoice_belum_lunas` | [ ] |
| BV.5 grep: nol sisa `saldo`/`proyeksi`/`piutang`/`marginBulanIni`/`rekap-margin`; test pembayaran & honor staf tetap hijau; SQLite + MySQL | Grep (`app resources routes tests database`, case-insensitive) `saldo|proyeksi|piutang|marginBulanIni|trendMarginBulanan|cashflowProyek|ringkasanPeriode|RELASI_CASHFLOW|rekap-margin|Terpakai`: kode/view/route nol; sisa di `tests/` hanya assertDontSee guard (`ProyekKeuanganTest`, `SuperAdminDashboardTest`) + nama test `LengkapiKtpTest::test_nik_format_berbeda_dari_nik_terpakai_...` ("terpakai" = NIK dipakai akun lain, bukan analisis). Test: 610 → **605 passed** di SQLite & MySQL `jbtb_test` (−7 dihapus, +2 baru, 1 diganti 1:1, 9 method disesuaikan teks/struktur/guard) | [ ] |


---

# Bagian BW: Revisi UI daftar Proyek + tab Keuangan (4 Oktober 2026)

> **FEATURE FREEZE tetap berlaku.** Revisi tampilan atas BU/BV, tanpa mengubah logika bisnis. Pakai subagent bila menyentuh >3 file. Commit, **jangan push** sebelum Fakrul cek.

Keputusan Fakrul setelah melihat hasil BU: daftar proyek masih kebanyakan. Baris "Tahap" dan "Lowongan dibuka/ditutup" di tiap kartu tidak perlu (tahap cukup sebagai tab, status lowongan ada di detail). Tampilkan info yang jelas saja, pola sama seperti kartu "Status Proyek" di dashboard. Revisi ini menggantikan keputusan BU soal badge Dibuka/Ditutup di kartu.

## BW.1: Daftar proyek `admin/projects`

1. **Tahap jadi tab** di atas daftar: Semua · Menunggu ACC · Mendatang · Berjalan · Selesai, masing-masing dengan angka. Pola sama kartu "Status Proyek" di dashboard (pakai ulang `CastingProject::tahap()` dan logika hitung yang sudah ada, jangan tulis ulang). Filter lewat query string `?tahap=`, bekerja bersama pencarian dan per-halaman, dan ikut terjaga di pagination.
2. **Kartu jadi baris ringkas tanpa label.** Isi per proyek: nama + kode proyek (+ badge **Urgent** bila urgent), Client, tanggal shooting, pendaftar/kuota (contoh `11 / 15`), badge **"Perlu tindakan (n)"** bila ada (definisi dari revisi BU: `AdminRingkasan::langkah()` field `perlu`, link ke `?tab=pendaftar`), tombol **Lihat detail**. Menu titik tiga (Lineup dan aksi lain) tetap.
3. **Dihapus dari daftar:** baris "Tahap", baris "Lowongan dibuka/ditutup", dan label teks "Shooting", "Pendaftar / kuota" (cukup nilainya). PIC, deadline, link grup tetap hanya di detail. Pengecualian kecil: proyek berstatus Mendatang yang lowongannya ditutup boleh diberi badge kecil "Lowongan ditutup".
4. Proyek **Ditolak** (pengajuan Client yang ditolak) masuk ke tab Menunggu ACC dengan badge "Ditolak", bukan kartu penuh tersendiri.
5. Mobile satu kolom, tanpa overflow di 360 dan 390 px (`BrOverflowMobileTest` harus tetap hijau). Daftar kosong per tab menampilkan pesan singkat.

## BW.2: Tab Keuangan di detail proyek

Aksi di tab ini (simpan nominal invoice, tandai lunas, tandai honor staf dibayar, tambah dan hapus biaya lain-lain) **sudah ada, jangan diubah**. Satu perubahan saja, pada kolom honor Extras:

1. Tombol **"Transfer"** (`btn-brand`) bila `Payment::perluDitransfer` (kontrak TTD lengkap + belum dibayar), selain itu tombol **"Lihat"**. Tetap link ke `payments.show`. **Jangan di-inline**: halaman pembayaran juga memuat bukti transfer, sengketa, konfirmasi, dan add-on, dan guard BS.2 jangan diduplikasi di tempat kedua.

## BW.3: Test dan penutup

1. Sesuaikan test yang meng-assert struktur daftar lama (`ProyekKeuanganTest`, `BnKodeProyekTest`, `BdSidebarFinalTest`, dan lain-lain). Tambah test: filter `?tahap=` menampilkan hanya proyek di tahap itu, angka tab benar, kartu tidak lagi memuat baris "Tahap" dan "Lowongan", badge Urgent tetap tampil, tombol Transfer vs Lihat sesuai status.
2. Jalankan SQLite lalu MySQL `jbtb_test` **berurutan** (jangan paralel, `DemoSeederTest` berbagi storage).
3. Isi kolom Bukti. Dokumen manager (`BAB-3-DRAFT`, `PRD-LITE`, `SYSTEM-ARCHITECTURE`, `DATABASE-SCHEMA`, `SECURITY-CHECKLIST`, `ARSITEKTUR-VISUAL.html`, `info.txt`) boleh ikut di-commit, tapi sebagai commit **terpisah** dari kode.

| Item | Bukti | QA |
|---|---|---|
| BW.1 tab tahap + baris ringkas tanpa label | `CastingProjectController::index`: filter dipisah ke closure `$saring` (dipakai query daftar + hitungan tab), `?tahap=` pakai ulang `diTahap()` (Menunggu ACC di daftar = `menunggu_acc` + `ditolak`; dashboard tidak diubah), `$jumlahTahap` = Semua + 4 tahap sesuai filter lain (5 query count, tetap per halaman). View: tab `xfilter` `Semua (n) · Menunggu ACC (n) · ...` (link menjaga q/per/filter, `page` dibuang; hidden `tahap` di form cari; pagination `withQueryString`), grup+chip Tahap di panel filter dibuang. Kartu: nama+kode+Urgent, Client, `rentangShooting()` + `n / kuota` (tanpa label), Perlu tindakan (n), Lihat detail, menu ⋮ tetap. Dibuang: baris Tahap, Lowongan dibuka/ditutup, label Shooting/Pendaftar/kuota. Badge "Ditolak" (client_request_status ditolak) dan "Lowongan ditutup" (hanya tahap Mendatang). Test baru: `ProyekKeuanganTest` `test_tab_tahap_filter_angka_dan_ditolak_masuk_menunggu_acc`, `test_tahap_terjaga_di_pagination`, `test_badge_lowongan_ditutup_hanya_untuk_proyek_mendatang`, `test_kartu_daftar_proyek_urgent_tanpa_baris_tahap_dan_lowongan` (ganti test Urgent/lowongan BU) | [ ] |
| BW.2 tombol Transfer/Lihat di honor Extras | `Payment::bisaDitransfer()` (belum_dibayar + kontrak TTD, sama kriteria scope `perluDitransfer`). Tab Keuangan: tombol `btn-brand` "Transfer" bila true, selain itu "Lihat"; tetap link `payments.show`, tidak inline, guard BS.2 tidak diduplikasi, aksi lain tab Keuangan tidak disentuh. Test `ProyekKeuanganTest::test_honor_extras_tombol_transfer_atau_lihat_sesuai_status` | [ ] |
| BW.3 test + SQLite & MySQL berurutan + 360/390px | Disesuaikan: `BiFilterPanelTest` (chip Tahap jadi tab, panel 2 → 1), `BePeriodeKartuTest` (daftar Menunggu ACC = kartu dashboard + proyek ditolak), `ProyekKeuanganTest` (test Urgent/lowongan diganti). `BnKodeProyekTest`, `BdSidebarFinalTest` tidak perlu diubah. 607 → **611 passed** di SQLite lalu MySQL `jbtb_test` (berurutan, +4 baru); `BrOverflowMobileTest` hijau (layout tab `flex-wrap`, kartu satu kolom) | [ ] |

---

# Bagian BX: Daftar proyek jadi tabel (4 Oktober 2026)

> **FEATURE FREEZE tetap berlaku.** Revisi tampilan atas BW.1, tanpa mengubah logika. Commit, **jangan push** sebelum Fakrul cek.

Keputusan Fakrul setelah melihat hasil BW: kartu masih kurang enak dan info dasarnya malah kurang (Client belum tampil jelas). Masalah yang ditemukan: tinggi kartu tidak seragam (badge Urgent kadang pindah baris, baris Client kadang kosong), angka tanpa label sehingga ambigu, tanda `-` sendirian, tombol "Lihat detail" hijau terlalu dominan, Client terlalu kecil. **Ganti kartu jadi baris tabel** (isi daftar ini teks dan angka yang dipindai, bukan konten visual).

## BX.1: Tabel daftar proyek

Tab tahap, pencarian, per-halaman, dan filter dari BW.1 tetap. Hanya bentuk daftar yang berubah. Kolom, kiri ke kanan:

1. **Proyek**: nama (tebal), di bawahnya kode `JBTB-…` (mono, kecil) + badge **Urgent** bila urgent. Badge Urgent selalu di baris kode, tidak pernah pindah ke samping/bawah judul.
2. **Client**: nama perusahaan (`users.nama_perusahaan`, fallback `name`) tebal; di bawahnya nama kontak Client abu-abu kecil. Bila belum ada Client: teks abu-abu miring "Belum ada Client" (jangan `-`).
3. **Shooting**: tanggal (`05–06 Okt 2026`) + keterangan relatif kecil di bawahnya: "dalam N hari" / "berlangsung" / "selesai". Bila belum ada jadwal: "Belum dijadwalkan" abu-abu. Keterangan relatif memakai logika `CastingProject::tahap()`/tanggal shooting yang sudah ada, jangan tulis ulang.
4. **Pendaftar**: `11 / 15` + progress bar tipis (terisi/kuota). Kuota 0 atau kosong tampil `0 / 0` tanpa bar.
5. **Tindakan**: badge "Perlu tindakan (n)" (aturan revisi BU, tidak berubah) bila ada, kosong bila tidak. Di ujung kanan menu ⋮ (Lineup dan aksi lain, seperti sekarang) dan ikon panah `ti-chevron-right`.

Perilaku:
- **Seluruh baris bisa diklik** menuju detail (tautan di nama proyek dengan `::after` yang menutupi baris / "stretched link"; menu ⋮ dan badge "Perlu tindakan" tetap bisa diklik sendiri lewat `position: relative; z-index`). **Tombol hijau "Lihat detail" dihapus.**
- Hover baris: latar token hover yang sudah ada, kursor pointer. Tinggi baris seragam, tidak ada baris yang melompat karena badge.
- Pakai kelas tabel yang sudah ada di design system (`.table-container`, token warna, skala `fs`). Tidak ada warna baru, tidak ada `overflow-x: hidden`.
- **Mobile (di bawah 720 px)**: tiap baris menjadi blok bertumpuk satu kolom: baris 1 nama + Urgent; baris 2 Client; baris 3 shooting · pendaftar; baris 4 badge "Perlu tindakan". Seluruh blok tetap bisa diklik. Tidak overflow di 360 dan 390 px (`BrOverflowMobileTest` hijau).
- Proyek Ditolak tetap seperti BW.1 (masuk tab Menunggu ACC dengan badge "Ditolak" di kolom Tindakan).
- Hindari N+1: `client`, `shootingDates`, hitungan pendaftar, dan data "perlu tindakan" di-eager-load per halaman.

## BX.2: Test dan penutup

1. Sesuaikan test yang meng-assert struktur kartu (`ProyekKeuanganTest`, `BnKodeProyekTest`, `BdSidebarFinalTest`, dll). Tambah test: nama perusahaan Client tampil di baris, "Belum ada Client" saat kosong, keterangan relatif benar (dalam N hari / berlangsung / selesai), "Belum dijadwalkan" saat tanpa jadwal, tombol "Lihat detail" tidak ada lagi, badge Urgent dan "Perlu tindakan" tetap tampil.
2. SQLite lalu MySQL `jbtb_test` **berurutan**. Screenshot 1366 px dan 390 px (daftar dengan banyak proyek, satu tanpa Client, satu tanpa jadwal).
3. Isi kolom Bukti.

| Item | Bukti | QA |
|---|---|---|
| BX.1 daftar jadi tabel: Proyek, Client, Shooting (relatif), Pendaftar (bar), Tindakan | `admin/projects/index`: `<table class="tabel-proyek">` (`.table-container.tp-wrap`), kolom Proyek (nama + kode + Urgent di baris kode), Client (`namaClient()` + kontak, "Belum ada Client"), Shooting (`rentangShooting()` jadi `05–06 Okt 2026` + `keteranganShooting()` baru: dalam N hari/berlangsung/selesai, "Belum dijadwalkan"), Pendaftar (`n / kuota` + bar tipis, kuota 0 tanpa bar), Tindakan (Ditolak/Lowongan ditutup/Perlu tindakan + ⋮ + chevron). Controller tidak diubah (eager load sudah ada). Test baru `ProyekKeuanganTest::test_baris_tabel_client_shooting_relatif_dan_tanpa_tombol_lihat_detail`, `test_baris_tabel_perlu_tindakan_tetap_tampil_dan_kuota_nol_tanpa_bar` | [ ] |
| BX.1 baris bisa diklik penuh, tombol Lihat detail dihapus, tinggi seragam | Stretched link `.tp-link::after` pada `tr` relative; ⋮ dan badge Perlu tindakan `z-index: 2`; hover `--bg-card-hover`; tombol "Lihat detail" dan teksnya dihapus (assert `assertDontSee`) | [ ] |
| BX.1 mobile bertumpuk tanpa overflow 360/390 | CSS `max-width: 719px`: baris flex-wrap bertumpuk (nama+Urgent / Client / shooting · pendaftar / Tindakan), ⋮ di kanan atas. `BrOverflowMobileTest` hijau. Screenshot 390 px dicek lewat iframe 390 px (tidak ada overflow terlihat); 360 px tidak diambil | [ ] |
| BX.2 test + SQLite & MySQL berurutan + screenshot | 611 → **613 passed** di SQLite lalu MySQL `jbtb_test` (berurutan). Screenshot (Edge headless, HTML hasil render test dengan data contoh, bukan DB asli): 1366 px dan 390 px di folder scratchpad sesi, `bx-1366.png`, `bx-390.png` | [ ] |

---

# Bagian BY: Perbaiki tabel daftar proyek (menu ⋮ rusak, tampilan berantakan) (4 Oktober 2026)

> **FEATURE FREEZE tetap berlaku.** Bugfix tampilan atas BX. Commit, **jangan push** sebelum Fakrul cek.

Fakrul membuka `admin/projects` di browser (1366 px) dan hasilnya masih berantakan. Temuan dari screenshot dan pembacaan `resources/views/admin/projects/index.blade.php` + CSS `.tp-*` di `layouts/app.blade.php`:

1. **Menu ⋮ tertimpa baris di bawahnya.** `.tp-aksi .badge, .tp-menu { position: relative; z-index: 2 }` membuat tiap baris punya stacking context sendiri; panel menu (`z-index: 20`) terkurung di dalam stacking context `.tp-menu` (z 2), sehingga badge "Perlu tindakan"/"Ditolak" dan tombol ⋮ milik baris berikutnya (urutan DOM lebih akhir) tampil di atas menu yang sedang terbuka.
2. **Teks menu rata kanan.** Panel menu mewarisi `.tp-aksi { text-align: right }`.
3. **Menu terlalu panjang dan dobel.** 6–7 item, padahal "Detail Proyek" sama dengan klik baris, dan "Invoice" serta "Edit" sudah ada di header detail proyek.
4. **`<details>` tidak menutup sendiri** saat klik di luar atau menekan Esc, jadi beberapa menu bisa terbuka bersamaan.
5. **Tinggi baris tidak seragam** (baris dengan badge Urgent lebih tinggi dari yang tidak).
6. **Baris Selesai/Ditolak sama menonjolnya dengan proyek aktif.**
7. **Screenshot BX bukan dari aplikasi asli** (HTML hasil render test, menu tidak dibuka), jadi bug 1–2 lolos.

## BY.0: Revisi keputusan Fakrul (menggantikan BY.1 poin 1–4): aksi jadi ikon langsung, menu ⋮ dihapus

Fakrul: kolom Tindakan jangan titik tiga, jadikan ikon saja supaya langsung kelihatan tiap ikon untuk apa. Ini sekaligus menghilangkan bug menu ⋮ (tertimpa baris, rata kanan, tidak menutup), jadi **poin 1–4 di BY.1 di bawah tidak perlu dikerjakan**; poin 5–8 tetap berlaku.

1. **Hapus `<details class="tp-menu">` dan seluruh CSS/JS menu ⋮** (`.tp-menu`, skrip tutup-menu, style inline item menu). Jangan tinggalkan kode mati. Skrip `data-copy-link` tetap dipakai.
2. **Kolom Tindakan = baris ikon tombol** (`btn btn-sm btn-ikon`, ukuran sentuh minimal 36×36 px, jarak 6 px), urut kiri ke kanan:
   - **Lineup** `ti-users-group` → `admin.projects.applicants` (tooltip "Lineup")
   - **Edit** `ti-pencil` → `admin.projects.edit` (tooltip "Edit proyek")
   - **Salin link pendaftaran** `ti-link` → `data-copy-link` (hanya bila `dibuka` dan ada `share_token`; setelah klik ikon berubah `ti-check` 2 detik, tooltip "Link disalin!")
   - **Tutup/Buka lowongan** `ti-lock` (bila `dibuka`, tooltip "Tutup lowongan") atau `ti-lock-open` (bila `ditutup`, tooltip "Buka lagi lowongan") → form PATCH `admin.projects.toggle-status` dibungkus `<x-confirm-form>` (menutup lowongan berdampak ke Extras, jangan tanpa konfirmasi)
3. Setiap ikon wajib punya `title` **dan** `aria-label` yang sama (tooltip bawaan browser cukup; jangan pasang library tooltip). Ikon bukan satu-satunya penanda: fokus keyboard terlihat jelas.
4. **Badge "Perlu tindakan (n)" dan "Ditolak"/"Lowongan ditutup" pindah ke kolom Pendaftar** (di bawah `11 / 15`), supaya kolom Tindakan cuma berisi ikon dan lebarnya tetap. Hapus ikon panah `ti-chevron-right` (sudah ada ikon aksi, dan seluruh baris tetap bisa diklik).
5. Klik ikon **tidak** boleh ikut membuka detail proyek (ikon di atas tautan penutup baris: `position: relative; z-index: 2`, hanya ikon itu, bukan seluruh kolom).
6. **Mobile (di bawah 720 px):** ikon tetap ikon saja, satu baris di bagian bawah blok proyek, rata kiri, tanpa overflow di 360/390 px.

## BY.1: Perbaikan (poin 1–4 digantikan BY.0, jangan dikerjakan)

1. **Menu ⋮**: tampil di atas semua baris dan tidak terpotong. Pilih salah satu cara: (a) paling kecil, `.tp-menu[open] { z-index: 30 }` dan `.tabel-proyek tbody tr:has(.tp-menu[open]) { z-index: 30 }`; atau (b) lebih tahan banting, ubah panel jadi elemen `popover` (top layer) yang diposisikan dari tombol (flip ke atas bila mepet bawah layar). Dahulukan (a) bila hasilnya benar di baris paling bawah halaman dan di mobile; pindah ke (b) bila tidak.
2. Panel menu `text-align: left`, lebar tetap (min 180 px), `white-space: nowrap`, item setinggi seragam (kelas yang sama untuk `<a>` dan `<button>`; hapus style inline berulang jadi satu kelas `.tp-menu-item`).
3. **Isi menu dipangkas jadi 4 item**: Lineup · Edit Proyek · Copy Link Pendaftaran (hanya bila `dibuka` dan ada `share_token`) · Tutup Lowongan / Buka Lagi. Hapus "Detail Proyek" dan "Invoice" dari menu ini (keduanya ada di detail proyek).
4. **Menutup menu**: klik di luar, tombol Esc, dan membuka menu lain menutup yang sebelumnya. Satu skrip kecil di bawah daftar, delegasi (tetap jalan setelah live search mengganti daftar).
5. **Tinggi baris seragam**: `.tabel-proyek td` diberi `height`/`min-height` tetap (cukup untuk dua baris teks + badge), `vertical-align: middle`; badge Urgent tidak boleh menambah tinggi baris.
6. **Proyek Selesai dan Ditolak dipudarkan**: teks `--text-muted`, bar pendaftar abu-abu, tanpa badge "Perlu tindakan" (aturan perhitungan tidak berubah, hanya penampilan). Proyek aktif (Menunggu ACC/Mendatang/Berjalan) tampil normal.
7. Kolom: lebar tetap per kolom (Proyek lebih lebar, Pendaftar sempit) supaya tidak bergeser antar halaman/tab; header "Tindakan" diganti kosong (kolomnya sudah jelas) atau "Status".
8. Pastikan seluruh baris tetap bisa diklik, badge "Perlu tindakan" dan tombol ⋮ tetap bisa diklik sendiri.

## BY.2: Verifikasi (wajib, ini yang kelewat di BX)

1. **Screenshot dari aplikasi asli** (login sebagai Admin lewat server dev, data demo `DemoLengkapSeeder`), bukan HTML hasil render test. Ambil di 1366 px dan 390 px: (a) daftar semua tab, (b) **menu ⋮ terbuka pada baris pertama, baris tengah, dan baris terakhir**, (c) dua menu dibuka berurutan (yang pertama harus menutup).
2. Test: item menu sesuai (4 item, tidak ada "Detail Proyek"/"Invoice"), Copy Link hanya muncul bila `dibuka` + `share_token`, baris proyek Selesai/Ditolak memakai kelas pudar.
3. `BrOverflowMobileTest` hijau. SQLite lalu MySQL `jbtb_test` **berurutan**. Isi Bukti. Commit, jangan push.

| Item | Bukti | QA |
|---|---|---|
| BY.0 menu ⋮ dihapus, diganti 4 ikon aksi (Lineup, Edit, Salin link, Tutup/Buka) + tooltip/aria-label, badge pindah ke kolom Pendaftar | `<details class="tp-menu">`, CSS, style inline, chevron dihapus. 4 ikon `btn-ikon` 36 px (title = aria-label, fokus `outline`), Salin link hanya `dibuka`+`share_token` (ikon jadi `ti-check` 2 dtk, dicek nyata di browser), Tutup/Buka lewat `x-confirm-form`. Badge Ditolak/Lowongan ditutup/Perlu tindakan di kolom Pendaftar. Klik ikon Lineup tidak membuka detail, klik baris membuka detail (dicek nyata di 1366 & 390) | [ ] |
| BY.1 tinggi baris seragam, Selesai/Ditolak dipudarkan, lebar kolom tetap | `table-layout: fixed`, kolom tetap, tinggi baris 80 px (terukur seragam di semua tab 1366 px), kelas `tp-pudar` untuk Selesai/Ditolak (tanpa badge Perlu tindakan), header Tindakan kosong | [ ] |
| BY.2 screenshot aplikasi asli (menu terbuka di baris atas/tengah/bawah, 1366 & 390) | Aplikasi asli (server dev, SQLite sementara + `DemoLengkapSeeder`, login Super Admin, Edge headless), 1366 & 390: semua tab, hover, fokus, salin link; tanpa overflow. Menu ⋮ sudah tidak ada (BY.0), jadi butir "menu terbuka" tidak berlaku. File di folder scratchpad sesi: `by-{1366,390}-{semua,menunggu_acc,mendatang,berjalan,selesai,hover,fokus,salin}.png`. DB dev asli tidak disentuh | [ ] |
| BY.2 test + SQLite & MySQL berurutan + overflow 360/390 | 613 → **615 passed** di SQLite lalu MySQL `jbtb_test` (berurutan). +2 di `ProyekKeuanganTest`; `BrOverflowMobileTest` hijau; overflow horizontal 0 px di 390 px (360 px tidak diambil) | [ ] |
