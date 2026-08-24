# Dev Notes — SIM Casting JBTB

Catatan pengerjaan build oleh Fakrul + Claude. File ini di-gitignore (kalau belum, tambahkan), tidak ikut push ke repo — beda dari `BAB-3-DRAFT.md` dkk yang jadi dokumen akademik resmi.

---

## Session 1 — Setup Awal + Sprint 1-6 (22 Agustus 2026)

### Setup Environment
- Laravel 13 (bukan 11 seperti draft awal `TECH-STACK.md` — direvisi karena Laravel 11 security fixes berakhir 12 Maret 2026, sudah EOL). PHP 8.3.31, MySQL 8.0.44, Laragon.
- Project diinstall ke folder sementara `jbtb-temp` lalu dipindah manual ke `extras/` (root project sekaligus lokasi `docs/`) karena Composer tidak bisa install ke folder yang sudah ada isi.
- Database `jbtb` dibuat manual via `mysql -u root -p`. `.env` diarahkan ke MySQL (bukan SQLite default installer).
- Package tambahan: `barryvdh/laravel-dompdf` (PDF kontrak/invoice/slip honor), `maatwebsite/excel` v4.0.1 (ekspor rekap RF-52).

### Struktur Database
17 tabel dibuat sesuai `DATABASE-SCHEMA.md`: `users` (+kolom `role`/`status`), `extras_profiles`, `admin_profiles`, `casting_projects`, `casting_project_classes`, `event_shooting_dates`, `project_applications`, `fee_negotiations`, `cd_reviews`, `contracts`, `payments`, `payment_addons` (polymorphic), `invoices`, `admin_project_assignments`, `staff_payrolls`, `cancellations`, `field_notes`, `notifications_log`. Semua migration jalan bersih, urutan foreign key sudah benar tanpa perlu reorder.

Laravel 13 memperkenalkan PHP Attributes untuk `$fillable`/`$hidden` (`#[Fillable([...])]`) — dipakai konsisten di semua model, bukan pola `protected $fillable` versi lama.

### Sprint 1 — Autentikasi RBAC 7-role + Profil Extras
- `CheckRole` middleware (alias `role`), didaftarkan di `bootstrap/app.php` (Laravel 13 tidak lagi pakai `Kernel.php`).
- Login/Register terpisah: Extras (publik, `/register`) vs Casting Director (`/register/casting-director`, link khusus tidak ditautkan dari halaman publik — dibagikan manual Admin).
- Keputusan keamanan: kolom `status` & `cancel_count` di `ExtrasProfile` SENGAJA tidak masuk `$fillable` — hanya bisa diubah lewat method `recordCancellation()`, mencegah mass-assignment (SECURITY-CHECKLIST.md poin 8).
- NIK, nama asli, rekening di-cast `encrypted` di level model.

### Sprint 2 — Manajemen Proyek Casting + Pendaftaran & Seleksi
- `event_shooting_dates` sebagai tabel terpisah (bukan kolom start/end) sesuai keputusan di `DATABASE-SCHEMA.md` — mendukung tanggal jamak tidak berurutan.
- Deteksi bentrok jadwal (RF-13) diimplementasi sebagai soft-warning: cek overlap tanggal shooting dengan aplikasi aktif Extras di proyek lain, TIDAK memblokir pendaftaran.
- Form buka lowongan pakai vanilla JS untuk input dinamis (tanggal jamak + kelas jamak) — tanpa library tambahan.

### Sprint 3 — Negosiasi Fee + Review CD
- Model InDrive-style diimplementasi di method `ProjectApplication`: `ajukanFeeAwal()`, `counterFee()`, `terimaFee()`, `tolakNegosiasi()` — semua insert ke `fee_negotiations` lewat method ini, bukan `create()` langsung dari controller (tabel append-only, jejak audit).
- `ajukanKeCd()` menegakkan urutan bisnis: melempar `LogicException` kalau status belum `deal` — urutan "nego dulu, baru present ke CD" tidak bisa dilewati dari controller mana pun.
- CD review (approve/reject individual + massal via `bulk_batch_id`) hanya melihat data lewat alias — tidak ada field fee/margin/nama asli yang dikirim ke view CD (tembok visibilitas, CLAUDE.md §5).

### Sprint 4 — Kontrak Digital + Invoice
- Komponen `<x-signature-pad>` — canvas signature vanilla JS (mouse + touch), base64 PNG dikirim via hidden input, disimpan sebagai file gambar di private disk. Tanpa library eksternal.
- `PdfGeneratorService` — satu service di-reuse untuk 3 dokumen (kontrak, invoice, slip honor nanti di Sprint 5), sesuai keputusan `TECH-STACK.md`.
- `ContractController`/`InvoiceController` diakses lintas role (Admin & Extras/CD) — otorisasi granular di method `pastikanBolehLihat()`, bukan lewat role middleware group, karena resource yang sama diakses dua pihak berbeda.

### Sprint 5 — Manajemen Karyawan + Absensi + Penggajian Staf
- Diskusi tambahan dengan Fakrul: Super Admin awalnya diminta bisa "mantau semua akun termasuk Extras & CD" — disepakati **read-only saja**, aksi kelola akun tetap wewenang Admin Default (RF-05 tidak berubah). RF-50 di `BAB-3-DRAFT.md` diupdate untuk mencerminkan ini.
- `AdminProjectAssignment::tandaiSelesai()` — transisi status log + auto-create `StaffPayroll` dalam satu method, memastikan nominal honor tersalin dari `AdminProfile` saat itu (bukan referensi live yang bisa berubah kalau honor diadjust nanti).
- Bug ditemukan & diperbaiki sendiri sebelum sempat dites: form penugasan admin di `super-admin/admins/index.blade.php` awalnya salah pakai `@csrf` di dalam string JS literal (tidak akan ter-render sebagai token). Diperbaiki pakai `csrf_token()` yang di-passing ke variabel JS.

### Sprint 6 — Pembayaran Extras + Dashboard + Riwayat Kerja
- Upload bukti transfer disimpan di disk `local` (private, `storage/app/private`), bukan `public` — konsisten SECURITY-CHECKLIST.md poin 16.
- Rekap Extras (RF-51) + ekspor Excel (RF-52) via `ExtrasRecapExport` (`maatwebsite/excel` v4.0.1, `FromCollection` + `WithHeadings`).

### Bug Ditemukan Saat Testing Manual (Fakrul)
- **Login selalu redirect ke `/admin/dashboard` walau role-nya `super_admin`.** Root cause: `LoginController::login()` pakai `redirect()->intended(...)` — kalau browser sebelumnya sempat nyoba akses `/admin/dashboard` (kena redirect ke `/login` karena belum auth), Laravel nyimpen itu sebagai "intended URL" dan balik ke sana setelah login sukses, menimpa hasil `dashboardRouteFor()`. **Fix:** ganti ke `redirect($this->dashboardRouteFor(...))` biasa (bukan `intended()`) — konsisten sama RF-03 yang mensyaratkan tiap role selalu diarahkan ke dashboard masing-masing, terlepas dari URL yang sempat dicoba sebelum login.

### Belum Ditest End-to-End
Semua 6 sprint dikerjakan berurutan tanpa checkpoint manual di tengah jalan (keputusan Fakrul: "lanjut dulu semua sprint, tes di akhir"). **Belum ada satu pun `php artisan migrate` dijalankan untuk tabel-tabel baru di sesi ini** kecuali migration dasar (users/cache/jobs) dan `admin_profiles` dst yang sudah dikonfirmasi jalan di awal sesi — migration terbaru dari Sprint 2-6 (kalau ada tambahan kolom/tabel setelah checkpoint pertama) perlu diverifikasi jalan sebelum lanjut development fitur baru.

**Keputusan Fakrul (24 Agu 2026):** lanjut ke perapian UI/UX dulu, testing logic end-to-end ditunda. Catatan risiko yang disampaikan sebelum keputusan ini: bug redirect (`intended()` di LoginController) baru ketemu lewat testing manual, bukan dari membaca kode — jadi kemungkinan ada bug logic sejenis di modul lain (nego fee, transisi status kontrak, payroll honor null-check, upload/decode base64 signature) yang belum diketahui sampai benar-benar dicoba di browser. Kalau pas rapi-rapi UI ketemu perilaku aneh, cek dulu apakah itu genuinely soal tampilan atau ternyata logic di baliknya yang salah.

**Actionitem sebelum lanjut ke fitur baru:**
1. Jalankan `php artisan migrate` untuk memastikan semua tabel dari `DATABASE-SCHEMA.md` benar-benar ada.
2. Buat setidaknya 1 user per role (bisa manual via `php artisan tinker` atau seeder) untuk tes alur end-to-end: Extras daftar → apply → nego fee → Deal → CD approve → kontrak+TTD → pembayaran+konfirmasi.
3. Cek RBAC: login sebagai Extras, coba akses `/admin/dashboard` — harus 403 (bukan redirect ke login, karena itu artinya sesi tidak terdeteksi sebagai user yang login).
4. Cek file upload (bukti transfer) benar-benar tersimpan di `storage/app/private/payments/bukti-transfer/`.

---

## Cara Pakai File Ini

Update di sini tiap sesi kerja modul (bukan hanya task besar) — beda dari project lain yang lebih strict soal token, project ini prioritaskan jejak proses untuk bimbingan (minimal 8x tercatat) dan laporan akhir nanti.
