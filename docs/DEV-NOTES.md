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

## Session 2 — Ponytail Cleanup + Fix ParseError (26 Agustus 2026)

### Perubahan

Ponytail review & cleanup atas commit `1af2ced` (Sprint 1–6 + UI):

- `PasswordResetController.php` — drop `$status =` assignment yang tidak dipakai di `sendResetLink()`.
- `AppServiceProvider.php` — pangkas komentar 8-baris jadi 1 baris.
- `applicants.blade.php` — hapus second `@foreach` loop untuk dialog reject; sekarang inline di loop utama (tidak ada constraint `<table>` di card layout).
- `password-input.blade.php` — hapus `uniqid()` dari `$inputId`; `pwd-{name}` sudah unik per halaman karena name beda.

**Fix ParseError:** komentar CSS di `app.blade.php` berisi `<x-password-input>` (tanpa `/>`). Blade parsing ini sebagai opening component tag → emit `if ($component->shouldRender()):` tanpa `endif` → unclosed `if` → "unexpected EOF, expecting endif" saat render halaman apapun yang pakai layout ini. Fix: hapus angle bracket dari komentar (tulis `x-password-input`, bukan `<x-password-input>`). Juga clear view cache (`php artisan view:clear`).

**Root CLAUDE.md dibuat** — biar Claude Code auto-load konvensi kerja (ponytail + caveman wajib tiap sesi coding). `docs/CLAUDE.md` juga diupdate dengan §14 Konvensi Coding.

### Catatan Penting

`<x-component>` di dalam komentar CSS atau teks Blade (termasuk di dalam `/* */` style block) **tetap diparse Blade sebagai component opening tag** kalau tidak ada `/` sebelum `>`. Selalu tulis tanpa angle bracket di komentar, atau pakai `{{-- --}}` Blade comment kalau perlu mention nama komponen.

**Catatan debugging (sesi paralel via Cowork, sebelum root cause di atas ketemu):** sempat salah curiga ke `@tabler` di URL CDN Tabler Icons (`.../npm/@tabler/icons-webfont@latest/...`) sebagai penyebab, karena `@` diikuti huruf juga pola directive Blade. Di-escape jadi `@@tabler` di `app.blade.php` & `auth.blade.php` (commit `95bc5ac`) — itu perbaikan yang sah untuk mencegah masalah serupa di masa depan (biarkan tetap ter-escape), tapi BUKAN akar masalah ParseError yang sebenarnya. Root cause asli adalah `<x-password-input>` di komentar CSS (lihat fix di atas, commit `395ca0d`). Pelajaran: kalau ada 2+ pola `@`/`<x-...>` mencurigakan di file yang sama, cek satu-satu sampai tuntas — jangan berhenti di kecurigaan pertama yang "masuk akal".

---

## Session 3 — Verifikasi 4 Action Item Session 1 (28 Agustus 2026)

Verifikasi murni (bukan fitur baru), menuntaskan 4 action item yang menggantung dari Session 1.

### 1. Migration — PASS
`php artisan migrate:status`: semua 24 migration (17 tabel inti DATABASE-SCHEMA.md + `extras_photos` dari Session 2 + kolom tambahan) berstatus "Ran", tidak ada yang pending. `SHOW TABLES` mengonfirmasi ke-27 tabel (17 bisnis + `extras_photos` + tabel framework) benar-benar ada di MySQL. Tidak perlu migrate apa pun.

### 2. User per role — PASS (dengan bug ditemukan, lihat di bawah)
Dibuat 7 akun `test.<role>@jbtb.test` / password `password`, satu per role, data fake sesuai brief. Cek dulu data existing: sudah ada beberapa akun manual (`admin.test@jbtb.local`, `extras.test@jbtb.local`, dst) — dibiarkan, tidak dihapus, cuma menambah yang kurang.

**Bug kritis ditemukan saat membuat user pertama:** semua user baru yang dibuat lewat `User::create([...'role'=>'admin_talco',...])` hasil akhirnya **selalu `role=super_admin`**, apa pun role yang dikirim. Root cause: `User::$fillable` (attribute `#[Fillable(['name','email','password'])]`) TIDAK menyertakan `role`/`status` — mass-assignment silently drop kedua field itu (tanpa exception, karena `preventSilentlyDiscardingAttributes()` tidak diaktifkan di project ini), lalu MySQL insert tanpa kolom `role` jatuh ke default implisit ENUM (anggota pertama = `super_admin`).

Dampak: **RegisterController::registerExtras()**, **registerCastingDirector()**, dan **AdminManagementController::store()** — tiga-tiganya kena, artinya di data lama pun ada akun yang niatnya bukan `super_admin` (mis. `cd.test@jbtb.local`, `lala@gmail.com`, `imanisa@gmail.com`) tapi ke-DB sebagai `super_admin`. Akun-akun lama itu SENGAJA tidak diubah/dihapus sesi ini (bukan scope task, bisa akun asli tim) — perlu Fakrul cek manual mana yang perlu dikoreksi role-nya.

**Fix (bug jelas, bukan keputusan scope — tetap sesuai pola proteksi yang sudah ada di `ExtrasProfile::$foto_profil_path`):** tambahkan `role`, `status` ke `$fillable` User. Proteksi mass-assignment tetap aman karena sudah dicek: tidak ada satu pun controller yang pass `$request->all()` mentah ke `User::create()` — semua literal atau hasil `validate()` dengan `in:` whitelist. Ditambahkan 2 test regresi (`tests/Feature/RegistrationRoleTest.php`) yang benar-benar POST ke `/register` dan `/register/casting-director`, assert role tersimpan benar — supaya bug ini tidak balik lagi diam-diam.

### 3. RBAC 403 — PASS
`CheckRole::handle()` sudah benar (`auth` di depan `role` di semua route group, lihat `routes/web.php`), abort 403 saat role tidak cocok, bukan redirect. Dibuktikan dengan test baru `tests/Feature/RbacTest.php` (extras hit `/admin/dashboard` → 403, bukan 302; guest hit rute sama → redirect `/login` seperti seharusnya).

### 4. File upload ke private disk — PASS
Konfirmasi ganda: (a) kode — `ExtrasProfile::simpanFoto/simpanVideo`, `ProfileController::fotoTambahanStream`, dan `PaymentController` semua pakai `Storage::disk('local')`, dan `config/filesystems.php` disk `local` → root `storage/app/private`; tidak ada satu pun pemakaian disk `public` untuk upload di seluruh `app/`. (b) bukti nyata di filesystem — `storage/app/private/extras/1/{foto,video,foto-tambahan}/...` dan `storage/app/private/invoices/signatures/...` sudah berisi file asli dari testing manual sebelumnya; `storage/app/public/` kosong (cuma `.gitignore`).

### File Diubah Sesi Ini
- `app/Models/User.php` — fix `$fillable` (bug di atas).
- `tests/Feature/RbacTest.php`, `tests/Feature/RegistrationRoleTest.php` — baru.
- `./vendor/bin/pint` dijalankan sesuai SOP — merapikan gaya kode lama (import, spasi concat, brace kosong) di 7 file lain (`FeeNegotiationController`, `RecapController`, `ContractController`, `Extras/ProfileController`, `InvoiceController`, `SuperAdmin/ProjectAssignmentController`, `ExtrasProfile.php`) + 2 file migration (newline EOF). Semua cek manual: cosmetic only, tidak ada perubahan logic.

### Catatan
Ada perubahan lain di working tree (README.md, beberapa `docs/*.md`, `resources/views/layouts/*.blade.php`, file baru `theme-style.blade.php`) yang BUKAN dari sesi ini — kemungkinan dari sesi/kerja paralel lain yang sedang berjalan. Tidak disentuh.

---

## Session 4 — Notifikasi Email RF-36 (29 Agustus 2026)

Implementasi `SPEC.md` — 3 Mailable (`HasilSeleksiMail`, `KonfirmasiFeeMail`, `KontrakSiapTtdMail`), dipicu langsung dari method model/controller yang sudah ada (bukan endpoint baru), `Mail::to()->queue()` + catat ke `notifications_log` lewat `NotificationLog::catat()`. Tiap kirim dibungkus try/catch supaya aksi utama tetap sukses walau email gagal.

### Trigger yang dipasang
- `ProjectApplication::tolakDini()` & `Cd\ReviewController::review()` → `kirimNotifikasiHasil()` (method baru di model, dipanggil dari dua tempat).
- `ProjectApplication::ajukanFeeAwal()`/`counterFee()` → `kirimKonfirmasiFee()`, penerima ditentukan dari siapa yang harus merespons (admin proyek vs extras).
- `ContractController::show()` (titik generate kontrak pertama kali) → kirim ke Extras & Admin proyek, dua-duanya belum TTD saat itu.

### Bug pre-existing ditemukan — TIDAK diperbaiki (CLAUDE.md §14.1)
Saat menulis test untuk trigger `KonfirmasiFeeMail`, ketemu `MassAssignmentException` yang **selalu** terjadi di `ProjectApplication::ajukanFeeAwal()`, `counterFee()`, `terimaFee()`, `tolakNegosiasi()` — semua manggil `$this->feeNegotiations()->create([...])`, tapi model `FeeNegotiation` sengaja tidak punya `#[Fillable(...)]` (komentar di file: "append-only, satu-satunya cara insert yang legit lewat method di ProjectApplication" — tapi method-nya sendiri pakai `create()` yang kena guard). Efeknya: **seluruh alur nego fee production sekarang 500 error setiap kali dipanggil** (ajukan fee awal, counter dari admin/extras, terima, tolak nego — semua kena). Ini modul yang menentukan `fee_final` (bersinggungan langsung dengan pembayaran), jadi sesuai aturan §14.1: dilaporkan dulu ke Fakrul, bukan diperbaiki sendiri meski fix-nya kelihatan jelas (tambah `#[Fillable(['round','diajukan_oleh','nominal','aksi'])]` atau ubah pola insert-nya).
2 test (`test_ajukan_fee_awal_...`, `test_counter_fee_...`) di-`markTestSkipped()` dengan alasan eksplisit, bukan dihapus — supaya begitu bug ini diperbaiki (oleh siapa pun yang di-approve Fakrul), tinggal dibuka skip-nya untuk verifikasi trigger `KonfirmasiFeeMail`.

### Bug lain ditemukan & diperbaiki langsung (bukan restricted module — Blade/CSS 1 file)
`resources/views/partials/theme-style.blade.php` baris 44: komentar CSS berisi `<x-password-input>` literal (persis kasus yang sama dengan yang sudah diperbaiki di `app.blade.php` sesi sebelumnya) — Blade parse jadi `if(shouldRender())` tanpa `endif`, bikin SEMUA halaman yang pakai `theme-style` (praktis seluruh app) 500 ParseError. Ketahuan karena test kontrak butuh render halaman yang include partial ini. Diperbaiki dengan menghapus angle bracket dari komentar (sama seperti fix sebelumnya) + `php artisan view:clear`. Ini bug tampilan generik, bukan logic auth/pembayaran/kontrak, jadi diperbaiki langsung sesuai threshold "view/Blade/CSS bug kecil 1-2 file" di `CLAUDE.md`/`CLAUDE.local.md`.

### File Diubah Sesi Ini
- Baru: `app/Mail/{HasilSeleksiMail,KonfirmasiFeeMail,KontrakSiapTtdMail}.php`, `resources/views/emails/{hasil-seleksi,konfirmasi-fee,kontrak-siap-ttd}.blade.php`, `tests/Feature/EmailNotificationTest.php`.
- Diubah: `app/Models/NotificationLog.php` (+`catat()`), `app/Models/ProjectApplication.php` (trigger di 3 method + 2 helper kirim), `app/Http/Controllers/Cd/ReviewController.php` (+trigger, +eager load), `app/Http/Controllers/ContractController.php` (+trigger `kirimNotifikasiKontrak()`), `resources/views/partials/theme-style.blade.php` (fix ParseError).
- `php artisan test`: 10 passed, 2 skipped (alasan di atas). `./vendor/bin/pint`: passed, tidak ada perubahan.

### Belum/Tidak Diverifikasi
- Trigger `KonfirmasiFeeMail` di `ajukanFeeAwal()`/`counterFee()` — kodenya terpasang tapi TIDAK bisa diverifikasi otomatis sampai bug `FeeNegotiation::create()` di atas diperbaiki (di luar scope task ini).
- `terimaFee()` sengaja TIDAK dipasangi trigger — `SPEC.md` cuma sebut `ajukanFeeAwal()`/`counterFee()`.

---

## Session 5 — Verifikasi Fix Nego Fee + RF-22 Re-check Bentrok (29 Agustus 2026)

Implementasi `SPEC.md` bagian 1 & 2 (RF-33/34 sengaja tidak dikerjakan — ambiguitas UX masih dikonfirmasi ke Fakrul terpisah).

### Part 1 — Verifikasi fix nego fee
`FeeNegotiation` sudah dikasih `#[Fillable(['round', 'diajukan_oleh', 'nominal', 'aksi'])]` oleh Fakrul. Hapus 2 `markTestSkipped()` di `EmailNotificationTest` — keduanya PASS begitu di-unskip, tidak ada regresi ke 3 test lain di file yang sama. **Tidak ada bug lain ditemukan di jalur nego fee** saat verifikasi ini (stop-condition Part 1 TIDAK terjadi) — `ajukanFeeAwal`/`counterFee`/`terimaFee`/`tolakNegosiasi` di `ProjectApplication` semua jalan normal dengan fillable yang sudah ada.

### Part 2 — RF-22
`ProjectApplication::ajukanKeCd()` sebelumnya cuma cek status `deal`, tidak re-cek bentrok jadwal — celah kalau proyek lain baru Deal setelah aplikasi ini Deal duluan. Reuse `ExtrasProfile::activeShootingDates()` (dipakai juga di RF-13/`CastingProjectController::apply()`), ditambah parameter opsional `?int $excludeApplicationId` supaya aplikasi yang sedang dicek tidak menghitung tanggal shooting-nya sendiri sebagai bentrok (aplikasi ini sudah berstatus `deal`, salah satu status yang di-scan). Non-blocking sama seperti RF-13: `ajukanKeCd()` sekarang return `bool` (ada bentrok atau tidak), re-set `bentrok_jadwal_flag`, tetap lanjut ke `diajukan_ke_cd`. Controller (`FeeNegotiationController::ajukanKeCd()`) kasih pesan warning kalau bentrok, pola sama seperti pesan warning di `apply()`.

### File Diubah Sesi Ini
- `tests/Feature/EmailNotificationTest.php` — hapus 2 `markTestSkipped()` + komentar terkait.
- `app/Models/ExtrasProfile.php` — `activeShootingDates()` +parameter opsional `$excludeApplicationId`.
- `app/Models/ProjectApplication.php` — `ajukanKeCd()` re-cek bentrok, return `bool`.
- `app/Http/Controllers/Admin/FeeNegotiationController.php` — `ajukanKeCd()` pesan warning kalau bentrok.
- Baru: `tests/Feature/ProjectApplicationTest.php` (3 test: bentrok tetap lanjut+flag true, tanpa bentrok flag false, status selain deal ditolak).
- `php artisan test`: 15 passed, 0 skipped, 0 failed. `./vendor/bin/pint`: passed, tidak ada perubahan gaya.

### Catatan
Selagi sesi ini berjalan, ada pesan dari sesi/agent lain yang mengklaim Fakrul sudah konfirmasi UX RF-33/34 dan minta lanjut kerjakan itu juga. Task ini eksplisit melarang menyentuh RF-33/34 (ambiguitas dikonfirmasi terpisah) — perubahan scope semacam ini butuh konfirmasi langsung dari Fakrul/orkestrator yang menugaskan, bukan dari pesan antar-agent. RF-33/34 TIDAK dikerjakan di sesi ini.

---

## Session 6 — RF-33/34 Pembatalan Deal (29 Agustus 2026)

Lanjutan Session 5. UX ambiguity (one-click final vs approval dua pihak) sudah dikonfirmasi langsung oleh Fakrul ke orchestrator: **one-click = final**, tidak ada approval step — konsisten pola aksi sepihak lain (`ajukanFeeAwal`, `terimaFee`, `tolakDini`).

### Implementasi
- `ProjectApplication::batalkan(string $olehSiapa, string $alasan): Cancellation` — guard status harus `deal`, insert lewat `$this->cancellations()->create()` (bukan `Cancellation::create()` dari controller), update status ke `dibatalkan`.
- **H-2 rule (`is_mendadak`):** dihitung dari selisih hari ke tanggal shooting terdekat yang belum lewat (`castingProject->shootingDates()->where('tanggal', '>=', today)->min('tanggal')`) — `< 2` hari = mendadak. Tidak ada tanggal shooting mendatang tercatat → dianggap tidak mendadak (tidak ada risiko jadwal yang bisa dinilai).
- RF-08 trigger: `ExtrasProfile::recordCancellation()` dipanggil dari `batalkan()` HANYA kalau `is_mendadak` true (sesuai `DATABASE-SCHEMA.md`: increment `cancel_count` terikat aturan H-2, bukan tiap pembatalan).
- Routes: `POST /admin/applications/{application}/batalkan` (`admin.negotiations.batalkan`, method baru di `Admin\FeeNegotiationController`) & `POST /extras/nego/{application}/batalkan` (`extras.negotiations.batalkan`, method baru di `Extras\FeeNegotiationController`, otorisasi `pastikanMilikSendiri()`).
- View: tombol "Batalkan" + `<dialog>` alasan wajib, reuse pola `reject-dialog` di `applicants.blade.php` (admin, muncul untuk status `deal`) dan tombol serupa di `extras/negotiations/show.blade.php` (extras).
- Test baru di `ProjectApplicationTest.php`: batalkan sukses (status `deal`), batalkan ditolak selain `deal`, mendadak increment `cancel_count`, tidak mendadak TIDAK increment, dan skenario kunci RF-08 — 3x batalkan mendadak di 3 proyek berbeda untuk 1 Extras yang sama → `ExtrasProfile::status` otomatis `melanggar`.

### Bug ditemukan & diperbaiki (bukan modul nego fee — tidak kena restriksi §14.1)
Dua bug, keduanya di jalur yang sedang dibangun sendiri di sesi ini (bukan "modul lain yang lagi tidak dikerjakan"), jadi langsung diperbaiki, bukan cuma dilaporkan:

1. **`Cancellation` tanpa `#[Fillable(...)]`** — persis pola bug `FeeNegotiation` sebelumnya, dan komentar di file itu sendiri sudah eksplisit bilang "nanti ditambahkan pas modul Pembatalan dikerjakan" (yaitu sesi ini). Ditambahkan `#[Fillable(['dibatalkan_oleh', 'alasan', 'is_mendadak'])]`.
2. **`ExtrasProfile::recordCancellation()` tidak pernah benar-benar mengubah status ke `melanggar`** — method ini sudah ada dari Sprint 1 (RF-08), tapi `$this->update(['status' => 'melanggar'])` di dalamnya diam-diam didiskard karena `status` sengaja tidak ada di `$fillable` model itu (proteksi RF-07/08 yang sah), dan project ini tidak mengaktifkan `preventSilentlyDiscardingAttributes()` sehingga tidak throw, cuma silent no-op. `cancel_count` tetap ikut naik (pakai `increment()`, tidak lewat guard) tapi status tidak pernah berubah — otomatis "Melanggar" itu **tidak pernah benar-benar terjadi sejak awal**, ketahuan pas test skenario 3x pembatalan gagal di assert status. Fix: pakai `forceFill(['status' => 'melanggar'])->save()` (bypass guard secara sengaja, konsisten dengan status field ini memang system-computed).

### File Diubah Sesi Ini
- `app/Models/ProjectApplication.php` — `+cancellations()` relation, `+batalkan()`.
- `app/Models/Cancellation.php` — `+#[Fillable(...)]` (fix bug #1).
- `app/Models/ExtrasProfile.php` — `recordCancellation()` fix bug #2 (`forceFill` bukan `update`).
- `app/Http/Controllers/Admin/FeeNegotiationController.php`, `app/Http/Controllers/Extras/FeeNegotiationController.php` — `+batalkan()`.
- `routes/web.php` — 2 route baru.
- `resources/views/admin/projects/applicants.blade.php`, `resources/views/extras/negotiations/show.blade.php` — tombol+dialog Batalkan.
- `tests/Feature/ProjectApplicationTest.php` — +5 test.
- `php artisan test`: 20 passed, 0 failed, 0 skipped. `./vendor/bin/pint`: passed, tidak ada perubahan gaya.

---

## Session 7 — Fix Cycle Pasca-Review: Timezone + RF-08 Actor (29 Agustus 2026)

Follow-up dari review Session 5/6. 2 temuan reviewer, keduanya sudah diputuskan Fakrul.

### Fix 1 — Timezone
`config/app.php` hardcode `'timezone' => 'UTC'`, padahal semua doc (`CLAUDE.md`, `TECH-STACK.md`) mensyaratkan Asia/Jakarta dan belum pernah benar-benar diset. Dampak nyata: `batalkan()` RF-08 (`now()->startOfDay()->diffInDays($tanggalTerdekat) < 2`) salah klasifikasi mendadak/tidak selama jam 00:00–06:59 WIB (UTC masih tanggal kemarin). Fix: `'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta')` + tambah `APP_TIMEZONE=Asia/Jakarta` di `.env.example`.

Cek dampak ke test tanggal/waktu: semua skenario H-2 di `ProjectApplicationTest` pakai `now()->addDay()/addDays(n)` baik di setup maupun (tidak langsung) di produksi kode — kedua sisi selalu memakai timezone app yang sama saat test jalan, jadi selisih hari yang dihitung tidak berubah walau timezone absolut berubah (offset UTC vs WIB saling coret di perhitungan relatif). Diverifikasi: `php artisan test` tetap 21 passed sebelum & sesudah fix, dan `config('app.timezone')`/`now()` dicek manual via tinker menunjukkan `Asia/Jakarta` & jam WIB yang benar. Bug aslinya cuma muncul di wall-clock absolut produksi (dini hari WIB), yang tidak tercermin di test berbasis `now()` relatif — jadi tidak ada test lama yang jadi "salah" ataupun perlu ditulis ulang, tapi juga tidak ada test yang benar-benar membuktikan fix ini (di luar scope sesi ini untuk menambah test pembekuan waktu dini hari; catat sebagai potential follow-up kalau perlu regresi test).

### Fix 2 — RF-08 hanya hitung pembatalan mendadak oleh Extras sendiri
`batalkan()` sebelumnya trigger `recordCancellation()` untuk SEMUA `is_mendadak`, tanpa peduli `$olehSiapa`. Admin membatalkan mendadak (mis. client reschedule) bukan salah Extras, jadi tidak boleh kena hitungan RF-08. Fix: `if ($isMendadak && $olehSiapa === 'extras')`. Tambah test `test_admin_batalkan_mendadak_3x_tidak_membuat_status_melanggar` — gap yang sebelumnya tidak ter-cover reviewer.

### Optional — preventSilentlyDiscardingAttributes
Ditambahkan di `AppServiceProvider::boot()`: `Model::preventSilentlyDiscardingAttributes(! app()->isProduction())`. Alasan: 3 bug identik (silent mass-assignment discard) sudah kejadian di codebase ini (`User`, `FeeNegotiation`, `ExtrasProfile`). Tidak ada test yang bergantung pada silent-discard — full suite tetap hijau. `ExtrasProfile::recordCancellation()` pakai `forceFill()` (bukan `fill()`/`update()`) untuk set `status`, jadi tidak kena guard baru ini (forceFill lewati mass-assignment check sepenuhnya, bukan cuma silent-discard-nya).

### File Diubah Sesi Ini
- `config/app.php` — timezone UTC → `env('APP_TIMEZONE', 'Asia/Jakarta')`.
- `.env.example` — +`APP_TIMEZONE=Asia/Jakarta`.
- `app/Models/ProjectApplication.php` — `batalkan()`: syarat `recordCancellation()` tambah `$olehSiapa === 'extras'`.
- `app/Providers/AppServiceProvider.php` — +`Model::preventSilentlyDiscardingAttributes()`.
- `tests/Feature/ProjectApplicationTest.php` — +1 test (admin batalkan mendadak 3x tidak melanggar).
- `php artisan test`: 21 passed, 0 failed, 0 skipped. `./vendor/bin/pint`: passed, tidak ada perubahan gaya.

---

## Session 8 — WhatsApp Gateway self-hosted (RF-37) (29 Agustus 2026)

Implementasi `SPEC.md` versi "WhatsApp Gateway (whatsapp-web.js self-hosted)", dikerjakan 2 bagian berurutan sesuai instruksi spec (infra+3 event sync dulu, baru reminder terjadwal).

### Orientasi & 1 keputusan teknis kecil (tidak perlu eskalasi)
Migration `notifications_log` (22 Agu) ternyata **sudah** punya `enum('channel', ['email', 'whatsapp'])` — bukan cuma `'email'` seperti dugaan awal spec. Jadi dipakai nilai channel `'whatsapp'` (konsisten sama enum yang sudah ada), BUKAN `'wa'` seperti contoh penamaan di draft SPEC.md — tidak perlu migration alter tambahan untuk kolom ini.

### Bagian A — Infra + 3 event synchronous
- Migration `nomor_wa` (string, nullable) di `users` (bukan `extras_profiles` — keputusan Fakrul, reusable lintas role).
- `User`: `nomor_wa` ditambah ke `#[Fillable]` (dicek penuh dulu isi existing, sesuai peringatan CLAUDE.md soal 3 insiden fillable sebelumnya) + mutator `Attribute` yang normalisasi ke format `62xxxxxxxxxx` di satu tempat (terima input `08xx`/`+62xx`/`62xx`).
- `NotificationLog::catat()` — signature tambah `string $channel = 'email'` (backward compatible, semua pemanggil lama tidak diubah).
- `WhatsAppService` baru (`app/Services/WhatsAppService.php`): `kirim()` (POST ke Node service, di-test `Http::fake()`) + `kirimNotifikasi()` (wrapper: skip+catat gagal kalau `nomor_wa` null, try/catch + `NotificationLog::catat()` — satu titik integrasi dipakai semua trigger, bukan pola baru per event).
- 3 titik trigger WA: `ProjectApplication::kirimKonfirmasiApply()` (baru, dipanggil dari `CastingProjectController::apply()` — titik ini sebelumnya belum ada notif sama sekali), `ProjectApplication::kirimNotifikasiHasil()` (tambah WA setelah email existing), `ContractController::kirimNotifikasiKontrak()` (tambah WA di loop extras+admin existing). Nego fee (RF-36 existing) SENGAJA tidak disentuh — bukan bagian dari 3 event RF-37 di spec ini.
- Node service `whatsapp-service/` (folder terpisah, bukan dependency Laravel): Express + `whatsapp-web.js` (`LocalAuth` — session persisten), endpoint `POST /send` (token Bearer statis dari `.env`), `GET /health`. **Diverifikasi jalan beneran** (bukan cuma ditulis): `npm install` sukses, service dijalankan manual dan berhasil generate QR code asli di terminal (Puppeteer/Chromium jalan normal di environment ini) — dimatikan lagi & folder session (`.wwebjs_auth/`) dihapus sebelum lanjut (pairing beneran adalah langkah manual Fakrul, di luar scope task ini).

### Bagian B — Reminder H-1
- `app/Console/Commands/ReminderH1ShootingCommand.php` (`reminder:h1-shooting`): cari `CastingProject` dengan `shootingDates` besok, filter `ProjectApplication` status `deal`/`diajukan_ke_cd`/`direview_cd`/`lolos`/`kontrak_ditandatangani` (list yang sama dipakai `activeShootingDates()`/deteksi bentrok di tempat lain — konsisten, bukan daftar baru), kirim lewat `WhatsAppService::kirimNotifikasi()` (otomatis skip+log kalau `nomor_wa` null, tidak throw di tengah loop).
- Scheduler didaftarkan di `routes/console.php` (Laravel 13 tidak pakai Kernel): `Schedule::command('reminder:h1-shooting')->dailyAt('08:00')->timezone('Asia/Jakarta')`.

### Gap yang perlu diketahui (belum dibangun, bukan bug)
Belum ada UI bagi Extras untuk mengisi `nomor_wa` sendiri (tidak diminta di SPEC.md task ini — hanya kolom+model+service). Sampai ada form tersebut (`Extras\ProfileController` atau menu profil), `nomor_wa` cuma bisa diisi manual (tinker/seeder) — semua notifikasi WA ke Extras tanpa nomor otomatis skip+log gagal, tidak error. Tandai sebagai kandidat task berikutnya kalau Fakrul mau lanjutkan.

### Setup Node Service (operasional, WAJIB dibaca sebelum production)
Lihat `whatsapp-service/README.md` untuk detail lengkap. Ringkas:
```bash
cd whatsapp-service && npm install && cp .env.example .env
# isi WHATSAPP_SERVICE_TOKEN, samakan dengan .env Laravel (WHATSAPP_SERVICE_TOKEN)
node server.js   # scan QR sekali di sini — session tersimpan di .wwebjs_auth/
```
Untuk 24/7: pakai PM2 (`pm2 start server.js --name whatsapp-service`). Status koneksi/QR dipantau manual lewat log terminal, TIDAK ada dashboard di sisi Laravel (sesuai Batasan SPEC.md).

**Cron Laravel (WAJIB, kalau tidak reminder H-1 nggak pernah jalan):** scheduler `reminder:h1-shooting` (`routes/console.php`, `dailyAt('08:00')` Asia/Jakarta) cuma jalan kalau `schedule:run` dipanggil tiap menit oleh cron OS — ini belum pernah ada entrinya di server manapun sampai sekarang. Tambahkan ke crontab VPS produksi:
```
* * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1
```

### File Diubah/Dibuat Sesi Ini
- `database/migrations/2026_08_29_150000_add_nomor_wa_to_users_table.php` — baru.
- `app/Models/User.php` — `+nomor_wa` fillable, `+nomorWa()` mutator normalisasi.
- `app/Models/NotificationLog.php` — `catat()` tambah param `$channel = 'email'`.
- `app/Services/WhatsAppService.php` — baru.
- `app/Models/ProjectApplication.php` — `+kirimKonfirmasiApply()`, `kirimNotifikasiHasil()` tambah kirim WA.
- `app/Http/Controllers/Extras/CastingProjectController.php` — panggil `kirimKonfirmasiApply()` setelah apply berhasil.
- `app/Http/Controllers/ContractController.php` — inject `WhatsAppService`, kirim WA di `kirimNotifikasiKontrak()`.
- `app/Console/Commands/ReminderH1ShootingCommand.php` — baru.
- `routes/console.php` — daftar scheduler harian.
- `config/services.php`, `.env.example` — config `whatsapp.url`/`whatsapp.token`.
- `whatsapp-service/` — baru: `package.json`, `server.js`, `.env.example`, `.gitignore`, `README.md`.
- `docs/BAB-3-DRAFT.md` — RF-37 direvisi ("gateway pihak ketiga berbayar" → `whatsapp-web.js` self-hosted).
- `tests/Feature/WhatsAppNotificationTest.php`, `tests/Feature/ReminderH1ShootingCommandTest.php` — baru, 11 test.
- `php artisan test`: 32 passed (21 lama + 11 baru), 0 failed. `./vendor/bin/pint`: passed, tidak ada perubahan gaya.

## Session 9 — Fix WA Notif Blocking Bulk Approve CD (29 Agustus 2026)

Bug: `Cd\ReviewController::review()` approve/reject N kandidat sekaligus → tiap `kirimNotifikasiHasil()` panggil `WhatsAppService::kirimNotifikasi()` synchronous, HTTP timeout 10 detik ke Node service. Node lambat/hang → satu request HTTP blocking sampai 10*N detik, tanpa cap jumlah kandidat per batch. Sama juga berlaku ke `kirimKonfirmasiApply()`, `ContractController::kirimNotifikasiKontrak()`, `ReminderH1ShootingCommand`.

Fix (dikonfirmasi Fakrul): pindah pengiriman WA ke queued job, meniru pola `Mail::to($user)->queue(...)` yang sudah dipakai di codebase ini. Bukan tambah validasi `max` di ReviewController — itu cuma stopgap, tidak dipilih.

- `app/Jobs/SendWhatsAppNotification.php` — baru, `ShouldQueue`. `handle()` isinya persis logic try/catch + `kirim()` + `NotificationLog::catat()` yang sebelumnya ada di `WhatsAppService::kirimNotifikasi()`.
- `app/Services/WhatsAppService.php` — `kirimNotifikasi()` sekarang cuma cek `nomor_wa` null (tetap synchronous, murah) lalu `SendWhatsAppNotification::dispatch(...)`. Method `kirim()` tidak berubah, sekarang dipanggil dari dalam job.
- Call site (`ProjectApplication`, `ContractController`, `ReminderH1ShootingCommand`) **tidak diubah** — `kirimNotifikasi()` sudah satu titik integrasi, jadi cukup ubah isinya saja.
- Queue project sudah `database`-backed (`QUEUE_CONNECTION=database`), `phpunit.xml` set `sync` untuk test jadi job jalan in-process saat test — tidak perlu ubah test.
- `php artisan test`: 32 passed, 0 failed (test lama, tidak ada test baru — gap coverage untuk job ini sudah tercover test existing lewat `sync` driver). `./vendor/bin/pint`: passed.
- Belum diverifikasi: worker (`php artisan queue:work`) harus jalan di VPS produksi (mis. via Supervisor) supaya job `whatsapp` benar-benar terkirim, bukan cuma numpuk di tabel `jobs`. Cek ini saat setup production.

---

## Session 10 — RF-04 Lengkapi KTP + Validasi Duplikat NIK (29 Agustus 2026)

Gap ditemukan saat scoping (lihat `SPEC.md`): `ExtrasProfile.nik`/`rekening` sudah ada di model sejak Sprint 1 tapi tidak ada satu pun controller/view yang mengumpulkannya — `ContractController::show()` auto-generate kontrak begitu status `lolos`, tanpa cek NIK terisi. Task ini menutup gap: step "lengkapi KTP" wajib sebelum kontrak bisa di-generate, plus validasi duplikat NIK.

- **Migration** `2026_08_29_161234_add_nik_hash_to_extras_profiles_table.php` — kolom `nik_hash` (string, nullable, unique). `nik` tetap `encrypted` (IV random, tidak bisa di-`WHERE`) — `nik_hash` = `hash_hmac('sha256', ...)` jadi kolom lookup duplikat terpisah.
- `ExtrasProfile::nik()` — Attribute mutator (pola `User::nomorWa()`) jadi satu-satunya titik yang mengisi `nik_hash`. **Detail penting:** `set` closure di Attribute API men-*skip* cast `encrypted` bawaan sepenuhnya (lihat `HasAttributes::setAttributeMarkedMutatedAttributeValue`) — jadi closure ini manual encrypt pakai `$this->castAttributeAsEncryptedString('nik', $digits)` lalu return array `['nik' => ..., 'nik_hash' => ...]` supaya kedua kolom ter-set sekaligus tanpa merusak enkripsi.
- `ExtrasProfile::lengkapiKtp()` — cek duplikat via `nik_hash`, lempar `\LogicException` (pola sama `ProjectApplication::batalkan()`) kalau NIK sudah dipakai profil lain. Rekening cuma di-overwrite kalau dikirim non-kosong.
- `ContractController::show()` — gate ditaruh sebelum `abort_if(status!==lolos)` dan sebelum blok auto-generate. **Keputusan:** gate cuma cek `nik` kosong, BUKAN `nik` dan `rekening` — karena `rekening` sampai sekarang tidak diisi di jalur manapun (bukan cuma di task ini; grep `rekening` di `app/` cuma nongol di kode task ini), jadi men-hardblock `rekening` bakal mengunci semua kontrak yang sudah lolos. Extras redirect ke `extras.kontrak.lengkapi-ktp`; Admin yang mampir sebelum Extras lengkapi NIK cuma `back()` dengan pesan info.
- `Extras\ProfileController::lengkapiKtp()`/`simpanKtp()` — form baru, validasi `nik: digits:16` di controller (bukan di model), model cuma dipercaya dipanggil dari sini.
- Route baru: `GET|POST /extras/kontrak/{application}/lengkapi-ktp`.
- View baru `resources/views/extras/lengkapi-ktp.blade.php` — reuse `.card`/`.form-row` dari `theme-style.blade.php`, tanpa CSS baru.
- Test regresi: `EmailNotificationTest`/`WhatsAppNotificationTest` test kontrak (status `lolos`) di-update supaya panggil `lengkapiKtp()` dulu sebelum hit `/kontrak/{id}` — sebelumnya generate langsung begitu status lolos, sekarang butuh NIK dulu.
- Test baru `tests/Feature/LengkapiKtpTest.php` (5 test): NIK valid+rekening kosong → kontrak bisa digenerate; NIK duplikat ditolak tanpa perubahan data; NIK bukan 16 digit ditolak validasi (model tidak sempat jalan); akses kontrak sebelum lengkapi KTP → redirect, tidak ada row `contracts` baru; NIK sudah terisi → langsung ke kontrak.
- `php artisan test`: 37 passed (32 existing + 5 baru), 0 failed. `./vendor/bin/pint`: passed.
- Tidak menyentuh RF-30/RF-35/RF-38, tidak ada perubahan skema NobiPlay/payment, tidak ada data di database dev asli yang disentuh (semua lewat migration + `RefreshDatabase` test).

---

## Session 11 — Fix Cycle Reviewer RF-04 (29 Agustus 2026)

Reviewer pass atas Session 10 nemu 9 temuan (F1-F9), semua diterapkan:

- **F1 (blocking):** `pastikanMilikSendiri()` di `Extras\ProfileController` cuma cek kepemilikan, tidak cek status — Extras yang belum/tidak `lolos` bisa akses form KTP. Tambah `abort_unless($application->status_partisipasi === 'lolos', 403)`, satu helper dipakai GET & POST.
- **F2 (blocking):** `throttle:5,1` di route `extras.kontrak.simpan-ktp`. `ExtrasProfile::lengkapiKtp()` sekarang `Log::warning()` (`user_id` saja, bukan NIK) saat duplikat kedeteksi.
- **F3 (blocking):** Admin yang mampir ke kontrak sebelum Extras lengkapi NIK sebelumnya kena `back()` → redirect loop (baca `_previous.url` GET yang sama). Ganti ke `redirect()->route('admin.projects.applicants', $application->castingProject)`.
- **F4 (arsitektur):** blind index `nik_hash` sekarang HMAC pakai `NIK_HASH_KEY` terpisah (`config('app.nik_hash_key')`, `env('NIK_HASH_KEY')`), bukan `APP_KEY`. Ditambahkan ke `.env`/`.env.example` (`config/app.php` cukup, tanpa file config baru). **Catatan rotasi:** kalau `NIK_HASH_KEY` dirotasi, semua `nik` existing harus didekripsi & di-hash ulang SEBELUM key lama dibuang — lihat `SECURITY-CHECKLIST.md` poin 5.
- **F5:** mutator `nik()` sekarang throw `\InvalidArgumentException` kalau digit hasil strip bukan 16 karakter — cegah HMAC-of-garbage kesimpan diam-diam.
- **F6+F8:** TOCTOU gap `exists()`→`save()` di `lengkapiKtp()`. Bikin `App\Exceptions\NikDuplikatException extends \LogicException`, dilempar dari duplicate-check model. `simpanKtp()` catch exception spesifik ini + `\Illuminate\Database\UniqueConstraintViolationException` (tersedia di Laravel 13 yang dipakai project ini), keduanya tampilkan pesan friendly yang sama.
- **F7:** kolom `nik_hash` di migration `add_nik_hash_to_extras_profiles_table` diubah jadi `string('nik_hash', 64)` (selalu hex SHA-256). Migration ini masih uncommitted/belum pernah jalan di env manapun selain lokal — edit in-place, bukan migration baru (`doctrine/dbal` juga tidak terinstall, jadi `->change()` bukan opsi murah).
- **F9:** `SECURITY-CHECKLIST.md` poin 5 & 11 diupdate (blind index + key terpisah; endpoint `simpan-ktp` sudah ada rate limit tapi poin 11 keseluruhan TETAP BACKLOG).
- Test baru: 2 test F1 (403 untuk status bukan `lolos`, GET & POST) + 1 test F2 (percobaan ke-6 dalam 1 menit → 429) di `LengkapiKtpTest.php`.
- `php artisan test`: lihat ringkasan run terbaru di laporan sesi. `./vendor/bin/pint`: dijalankan, bersih.

---

## Session 12 — RF-38 + RF-35 + RF-30 batch (29 Agustus 2026)

Batch kecepatan sesuai `SPEC.md` (Fakrul: kerjakan 3 modul sekaligus, revisi detail di sesi berikutnya kalau ada masalah). Berurutan: RF-38 → RF-35 → RF-30 karena ketiganya nyentuh `routes/web.php`.

**RF-38 — Link Grup WA per Proyek:**
- Migration `add_wa_group_link_to_casting_projects_table` — `wa_group_link` string nullable.
- `CastingProject::$fillable` (attribute) tambah `wa_group_link`. `Admin\CastingProjectController::store()` validasi `nullable|url`.
- Tampil di `admin/projects/create.blade.php` (input), `index.blade.php` (entity-card, link kalau ada), `applicants.blade.php` (header info proyek). Semua `target="_blank"`.
- **Keputusan visibilitas:** cuma di-wire untuk Admin sesi ini (create + index + applicants). Tidak diperluas ke Extras/CD — dinilai bukan cheap-enough untuk speed batch ini (butuh extend view + query di 2 controller lain). Dicatat sebagai follow-up terbuka, bukan silently dropped.
- Tidak ada method `edit()`/`update()` proyek existing (memang belum ada sama sekali di codebase) — sesuai scope, tidak dibuatkan di task ini.

**RF-35 — Catatan/Sanksi Korlap:**
- **Keputusan kolom `korlap_id`:** TETAP nama `korlap_id` (tidak di-generalize ke `user_id`) — kolom cuma FK ke `users`, Admin Default nulis catatan sebagai dirinya sendiri di kolom yang sama. Diff paling kecil, tidak ada migration rename.
- `ProjectApplication::fieldNotes()` (hasMany, `->latest()`) + `tambahCatatan(User $olehSiapa, string $jenis, string $isi): FieldNote` — insert lewat method model (pola sama `batalkan()`), bukan `create()` di controller.
- Controller: action baru `ApplicantController::tambahCatatan()` (bukan controller terpisah — sudah ada `ApplicantController` khusus aksi per-aplikasi, lebih pas daripada bikin `FieldNoteController` baru).
- Route `POST /admin/applications/{application}/catatan` — grup role sendiri `role:admin_default,admin_korlap`, nested di dalam grup admin umum (bukan di grup `role:admin_default` murni yang sudah ada) supaya Korlap tetap dapat akses tapi Talco/Sosmed diblok middleware bertingkat.
- View: tombol "Catatan Lapangan" (cuma tampil untuk role admin_default/admin_korlap) + `<dialog>` (pola sama reject/batalkan, tanpa JS baru) + riwayat catatan inline per kandidat di `applicants.blade.php`.
- Cuma create, tanpa edit/delete (tidak diminta RF-35).

**RF-30 — Rekap Margin per Proyek:**
- **Temuan skema penting:** `project_applications` TIDAK punya FK ke `casting_project_classes` sama sekali (dicek langsung ke migration asli, bukan asumsi) — bahkan alur `apply()` Extras tidak minta pilih kelas. Jadi margin PER KEPALA per kelas spesifik tidak bisa dihitung eksak dari skema yang ada tanpa migration+perubahan alur apply (di luar scope speed batch ini).
- **Resolusi yang dipilih:** hitung di level PROYEK, bukan per-aplikasi: `total_fee_client` = SUM(`budget_client` × `kuota_kelas`) semua kelas proyek (nilai budget yang disepakati), `total_payout` = SUM(`fee_final`) aplikasi yang statusnya `lolos`/`kontrak_ditandatangani`/`selesai_produksi` (list sama seperti RF-51 `RecapController`). `margin = total_fee_client - total_payout`. Ini pendekatan agregat proyek, BUKAN margin eksak per kepala per kelas — didokumentasikan di sini supaya tidak jadi asumsi diam-diam. Kalau butuh akurasi per-kelas, perlu tambah kolom `casting_project_class_id` ke `project_applications` + update alur apply Extras (perubahan scope, eskalasi dulu ke Fakrul).
- Controller baru `Admin\MarginRecapController@index`, terpisah total dari `RecapController`/`ExtrasRecapExport` (RF-51, tidak disentuh).
- Route: `GET /admin/rekap-margin` DAN `GET /super-admin/rekap-margin`, masing-masing di grup middleware sendiri `role:admin_default,super_admin` (BUKAN nested di grup admin umum yang termasuk sub-role, BUKAN nested di grup `super-admin` yang cuma `role:super_admin`) — keduanya panggil controller/method yang sama. Diverifikasi via test: admin_default & super_admin bisa akses KEDUA prefix (tidak ada guard tambahan di level prefix), 3 sub-role admin (talco/korlap/sosmed) 403 di keduanya.
- View `admin/recap/margin.blade.php` — tabel sederhana reuse `.card`/`table` dari `theme-style.blade.php`. Tanpa ekspor Excel (sesuai scope).

**Testing:**
- `WaGroupLinkTest` (3), `FieldNoteTest` (6, termasuk 4 data-provider role ditolak), `MarginRecapTest` (12, termasuk data-provider akses & 403, plus test kalkulasi margin eksplisit 400rb×3 − 250rb×3 = 450rb, dan test status `ditolak` tidak masuk hitungan payout).
- `php artisan test`: 61 passed (40 existing + 21 baru), 0 failed, 0 regresi.
- `./vendor/bin/pint`: passed, tidak ada perubahan gaya.
- Tidak ditemukan bug auth/RBAC/pembayaran/kontrak di luar scope 3 modul ini selama pengerjaan — tidak ada stop-condition yang perlu dilaporkan.

---

## Session 13 — RF-32: Extras Bisa Tambah Addon Pembayaran Sendiri (29 Agustus 2026)

Fix scope sempit sesuai `SPEC.md`: `PaymentController::addAddon()` hard-block selain `admin_default`, padahal RF-32 minta Extras juga bisa.

- `PaymentController::addAddon()` — otorisasi ganti dari `abort_unless(role === 'admin_default')` jadi reuse `pastikanBolehLihat()` (sama pola punya `show()`): admin_default (siapa pun) ATAU extras pemilik aplikasi.
- **Temuan tambahan (sesuai instruksi SPEC, bukan bug di luar scope):** sebelumnya TIDAK ADA pembatasan status sama sekali untuk addon — admin bisa tambah addon kapan pun termasuk setelah `dikonfirmasi_diterima`. Ditambah `abort_if($payment->status === 'dikonfirmasi_diterima', 422)` untuk KEDUA role (paling aman & konsisten, sesuai arahan SPEC kalau nemu ambiguitas).
- Model `Payment`/`PaymentAddon` tidak diubah — relasi polymorphic `addons()`/`addable()` sudah benar.
- View `payments/show.blade.php` — form addon sekarang muncul untuk `admin_default` ATAU `extras`, digate `status !== 'dikonfirmasi_diterima'`. Form upload bukti transfer (admin-only, RF-28) dipisah jadi `@if` sendiri, tidak lagi nested di kondisi role gabungan.
- Test baru `tests/Feature/PaymentAddonTest.php` (5 test): extras tambah addon punya sendiri (sukses), extras tambah addon milik extras lain (403), admin_default tetap bisa (regresi), addon ditolak untuk admin & extras setelah `dikonfirmasi_diterima` (422, keduanya).
- `php artisan test`: 66 passed (61 existing + 5 baru), 0 failed, 0 regresi. `./vendor/bin/pint`: passed.
- Tidak ada bug auth/RBAC/kontrak/nego-fee di luar scope ditemukan selama pengerjaan — tidak ada stop-condition yang perlu dilaporkan.

---

## Session 14 — Grade Filter di Lineup Pendaftar (29 Agustus 2026)

Backlog `CLAUDE.md`: "Grade filter (tab sudah ada, logika filter belum)" — dicek, tab-nya juga belum ada sama sekali di kode (cuma badge tampilan grade per kandidat), jadi dikerjakan dari nol. Kecil, dikerjakan langsung tanpa subagent (1-2 file, bukan auth/RBAC/pembayaran/kontrak).

- `Admin\CastingProjectController::showApplicants()` — terima query param `?grade=A|B|C|belum`, filter `ProjectApplication` sesuai (`belum` = `whereNull('grade')`). Tanpa param = semua pendaftar (behavior lama tidak berubah).
- `admin/projects/applicants.blade.php` — tambah baris tab filter (Semua/Grade A/B/C/Belum Dinilai) di atas daftar kandidat, reuse `.btn`/`.btn-sm`/`.btn-brand` (tanpa CSS baru).
- Test baru `tests/Feature/ApplicantGradeFilterTest.php` (3 test: filter A, filter belum-dinilai, tanpa filter = semua).
- `php artisan test`: 69 passed (66 existing + 3 baru), 0 regresi. `./vendor/bin/pint`: passed.

---

## Session 15 — Security Hardening Batch + RF-38 Follow-up (30 Agustus 2026)

Batch kecepatan (keputusan Fakrul, sama pola Session 12): 4 poin `SECURITY-CHECKLIST.md` yang murni bisa dikerjakan sebagai kode.

- **Rate limiting (poin 11):** `throttle:5,1` di `/login`, `/register`, `/register/casting-director`, `/forgot-password`, apply lowongan Extras, plus `simpan-ktp` yang sudah ada dari sebelumnya.
- **Security headers (poin 18):** middleware global baru `app/Http/Middleware/SecurityHeaders.php` (`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, CSP yang cuma izinkan host yang benar-benar dipakai — Google Fonts, jsDelivr Tabler Icons/Chart.js, dicek satu-satu ke `layouts/app.blade.php`/`auth.blade.php` biar nggak nge-block aset sendiri).
- **Secure cookie & Force HTTPS (poin 9, 19):** `.env.example` tambah `SESSION_SECURE_COOKIE=false` (komentar: set true di production). `AppServiceProvider::boot()` — `URL::forceScheme('https')` kalau `app()->isProduction()`, tidak aktif di local/testing (diverifikasi: full suite tetap hijau).
- `composer audit` dicoba jalan di sesi ini — GAGAL, sandbox tidak ada akses internet. Fakrul perlu jalanin manual (poin 20 checklist, `composer audit` biasa, atau aktifkan Dependabot GitHub).
- `SECURITY-CHECKLIST.md` poin 9/11/18/19 diupdate status-nya (lihat file itu langsung untuk detail cakupan — poin 9 masih SEBAGIAN karena env var-nya manual, bukan auto-forced).
- Test baru `tests/Feature/SecurityHardeningTest.php` (2 test). `php artisan test`: 71 passed (69 existing + 2 baru), 0 regresi.

**RF-38 follow-up (visibilitas link grup WA):** Session 12 sempat catat "belum diperluas ke Extras/CD" sebagai follow-up terbuka. Dicek ulang: RF-38 formal (`BAB-3-DRAFT.md`) cuma sebut **Admin Default** sebagai aktor — asumsi "perlu diperluas ke CD" di Session 12 itu interpretasi saya sendiri, bukan requirement eksplisit. Diputuskan: tambah tampilan link ke **Extras saja** (halaman kontrak `contracts/show.blade.php`, sudah eager-load `castingProject`, muncul begitu status `lolos`+ karena itu titik Extras genuinely aktif terlibat) — CD SENGAJA tidak diperluas (CD = client eksternal, RF-38 tidak memintanya, dan tembok visibilitas §5 CLAUDE.md lebih hati-hati soal apa yang dilihat CD). Tidak ada test baru (perubahan tampilan doang, 1 baris `@if`), full suite tetap 71 passed setelah perubahan.

**Task tersisa (deferred, nunggu keputusan Fakrul):**
- "Apresiasi" Extras rajin/disukai client — bentuk konkret: badge bintang toggle admin-only (per diskusi 29 Agu), belum dibangun.
- Inbox "Permintaan Client" (re-book) — belum ada RF resmi, belum ada halaman Riwayat Casting CD sama sekali, perlu didesain dari nol kalau mau dikerjakan.
- SECURITY-CHECKLIST poin 2 (purge git secrets) & 20 (scan dependencies) — butuh akses internet/CI, tidak bisa dari sandbox ini.
- SECURITY-CHECKLIST poin 12 (CAPTCHA) — butuh akun reCAPTCHA/hCaptcha + site key asli dari Fakrul.

---

## Session 16 — SPEC.md Bagian A: RF-10 Edit Proyek Casting (30 Agustus 2026)

Hasil audit kepatuhan scope (SPEC.md, ditulis Fakrul) nemu 1 gap nyata: `toggleStatus()` sudah ada tapi edit proyek tidak ada sama sekali. Bagian B (RF-30 margin per-kepala) di SPEC.md yang sama SENGAJA belum dikerjakan sesi ini — lebih berisiko (nyentuh `project_applications`, dipakai 9+ file test), Fakrul minta dipisah biar gampang rollback kalau ada masalah.

- `Admin\CastingProjectController` — tambah `edit()`/`update()`. Validasi sama seperti `store()`.
- Routes baru: `GET /admin/projects/{castingProject}/edit`, `PATCH /admin/projects/{castingProject}`, grup middleware `role:admin_default` yang sama.
- View baru `admin/projects/edit.blade.php` — mirror `create.blade.php`, pre-filled, tombol "Edit" ditambah di `admin/projects/index.blade.php`.
- **Penyesuaian scope (didokumentasikan, bukan bug):** SPEC minta proteksi hapus **per-kelas** ("kelas yang sudah punya pendaftar tidak boleh dihapus"), tapi link `project_applications` → kelas spesifik (`casting_project_class_id`) baru ditambahkan di Bagian B yang belum dikerjakan. Solusi sementara: proteksi di level **proyek** — kalau proyek punya pendaftar sama sekali (`applications()->exists()`), semua kelas existing di-update in-place (tidak bisa dihapus, boleh tambah baru); proyek tanpa pendaftar tetap delete-recreate bebas seperti `store()`. Ini superset konservatif dari behavior final per-kelas yang akan didapat setelah Bagian B — tujuan intinya (jangan sampai FK di Bagian B orphan) tetap terpenuhi tanpa nyentuh `project_applications`. Warning banner di form edit juga level-proyek untuk alasan yang sama.
- Bug kecil ditemukan & diperbaiki sendiri dalam proses (bukan out-of-scope): field hidden `kelas[i][id]` awalnya ikut ke-passing ke `CastingProjectClass::create()` dan gagal `MassAssignmentException` (`id` bukan fillable) — di-exclude sebelum create.
- Test baru: `tests/Feature/CastingProjectEditTest.php` (6 test: update semua field, kelas berpendaftar gagal dihapus, kelas tanpa pendaftar bebas dihapus, dll).
- `php artisan test`: baseline 71 passed → 77 passed (71 lama + 6 baru), 0 regresi. `./vendor/bin/pint`: passed, tanpa perubahan.
- Tidak ada bug di luar scope (auth/RBAC/pembayaran/kontrak) ditemukan selama sesi ini.

**Belum dikerjakan (menunggu keputusan lanjut Fakrul):** Bagian B (RF-30 margin per-kepala) — termasuk `casting_project_class_id` di `project_applications`, yang begitu ditambahkan akan memungkinkan proteksi hapus kelas di atas diperketat jadi genuinely per-kelas (bukan lagi per-proyek).

---

## Session 17 — SPEC.md Bagian B: RF-30 Margin Per-Kepala (30 Agustus 2026)

Ganti pendekatan aproksimasi level-proyek (Session 12) jadi eksak per-aplikasi: tiap `ProjectApplication` sekarang tahu kelasnya sendiri.

- Migration baru `add_casting_project_class_id_to_project_applications_table` — `foreignId('casting_project_class_id')->nullable()->constrained('casting_project_classes')->nullOnDelete()`. Nullable di level DB, non-negotiable (SPEC.md), supaya 9+ file test yang `ProjectApplication::create()` langsung tidak perlu diubah.
- `ProjectApplication` — tambah `casting_project_class_id` ke `#[Fillable]`, relasi `castingProjectClass(): BelongsTo`. `CastingProjectClass` — tambah relasi `applications(): HasMany` (belum dipakai di Bagian A, disiapkan untuk proteksi hapus-kelas genuinely per-kelas nanti).
- `Extras\CastingProjectController::apply()` — terima `casting_project_class_id`, validasi ownership via `$castingProject->classes()->findOrFail($id)` (bukan percaya bare ID dari request). **Penyesuaian:** field ini cuma `required` kalau proyeknya punya kelas (`$castingProject->classes()->exists()`) — proyek tanpa kelas sama sekali (skenario test lama `WhatsAppNotificationTest::test_apply_mengirim_wa_konfirmasi_dan_mencatat_log`, proyek dibuat tanpa `classes()->create()`) tetap bisa apply tanpa kelas, konsisten dengan kolom yang nullable. Ini bukan penyimpangan dari SPEC, cuma akomodasi supaya "required" tidak memaksa pilihan yang secara harfiah tidak ada.
- `Admin\MarginRecapController` — dibongkar total. Per proyek: partisi aplikasi status lolos-ke-atas jadi berkelas (`groupBy('casting_project_class_id')`, breakdown per kelas: fee_client = budget_client x jumlah aplikasi di kelas itu) vs tanpa kelas (`whereNull`, masuk baris "Belum terklasifikasi": fee_client dianggap 0, payout tetap disum, margin baris ini otomatis negatif — payout tetap kepotong dari total, tidak silently hilang). **Bug ditemukan & diperbaiki sendiri saat implementasi:** `Collection::groupBy()` memperlakukan key `null` sebagai string kosong `''` bukan `null` — kalau tidak di-partisi manual duluan (`whereNull`/`whereNotNull` sebelum `groupBy`), baris "Belum terklasifikasi" tidak pernah ke-detect dan aplikasi berkelas ikut nyasar ke grup yang salah, error `budget_client on null`.
- View: `extras/projects/show.blade.php` — radio pilih kelas di form apply (cuma tampil kalau proyek punya kelas). `admin/recap/margin.blade.php` — sub-row breakdown per kelas + baris "Belum terklasifikasi" kalau ada.
- Test: `MarginRecapTest::test_margin_dihitung_benar_per_kelas_bukan_dikali_kuota` (ganti test lama Session 12, sekarang 2 kelas beda budget dalam 1 proyek, assert sum margin per kelas bukan dikali kuota) + `test_aplikasi_tanpa_kelas_tetap_masuk_total_sebagai_belum_terklasifikasi` (baru). `tests/Feature/CastingProjectApplyTest.php` (baru, 3 test): apply dengan kelas sendiri berhasil, apply dengan kelas milik proyek lain ditolak 404 (`findOrFail` di relasi ter-scope), apply tanpa pilih kelas padahal proyek punya kelas → 422.
- **Full regression: `php artisan test` 77 passed (baseline sebelum sesi) → 81 passed sesudah (+4 test baru/revisi), 0 gagal.** Semua 10 file yang `ProjectApplication::create()`/`->applications()->create()` langsung (digrep ulang, bukan cuma 9 seperti perkiraan awal SPEC.md): `ProjectApplicationTest`, `EmailNotificationTest`, `WhatsAppNotificationTest`, `LengkapiKtpTest`, `PaymentAddonTest`, `ApplicantGradeFilterTest`, `MarginRecapTest`, `FieldNoteTest`, `CastingProjectEditTest`, `ReminderH1ShootingCommandTest` — tetap PASS tanpa satu pun diubah untuk urusan `casting_project_class_id` (`MarginRecapTest` diubah, tapi karena math-nya memang berubah sesuai SPEC, bukan karena kolom baru). `./vendor/bin/pint`: passed, tidak ada perubahan style.
- Tidak ada stop-condition/hidden NOT-NULL assumption ditemukan. Tidak menyentuh kode Bagian A (`Admin\CastingProjectController::edit()/update()`) di luar yang diminta SPEC.md (relasi `CastingProjectClass::applications()`, model-only).

---

## Cara Pakai File Ini

Update di sini tiap sesi kerja modul (bukan hanya task besar) — beda dari project lain yang lebih strict soal token, project ini prioritaskan jejak proses untuk bimbingan (minimal 8x tercatat) dan laporan akhir nanti.
