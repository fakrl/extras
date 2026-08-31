# SPEC.md — Batch Besar: 6 Keputusan Session 19 (Sudah Diputuskan Fakrul)

> Diisi 30 Agustus 2026 malam. Fakrul sudah putuskan semua 6 item `DEV-NOTES.md` Session 19 "BUTUH KEPUTUSAN FAKRUL" satu per satu (lewat manager). Ini batch BESAR — 6 bagian, beberapa nyentuh auth/pembayaran/kontrak (ikuti `CLAUDE.md` §14.1: subagent WAJIB untuk modul-modul itu). **Kerjakan BERURUTAN A→F, full regression `php artisan test` HARUS hijau sebelum lanjut ke bagian berikutnya.** Kalau satu bagian bikin test merah dan tidak bisa diperbaiki wajar, STOP di situ, catat detail di `DEV-NOTES.md`, JANGAN lanjut ke bagian berikutnya dalam kondisi merah.

## Aturan Main

- Tiap bagian ada test barunya sendiri — jangan digabung jadi 1 commit besar, commit/catat per bagian selesai (memudahkan rollback kalau salah 1 bagian bermasalah).
- Kalau nemu ambiguitas teknis DI DALAM satu bagian (bukan soal scope/keputusan bisnis, itu sudah final di bawah) — ambil opsi paling konservatif & reversible, catat di `DEV-NOTES.md`, lanjut. Kalau ambiguitasnya soal SCOPE/keputusan bisnis baru yang belum tercakup di sini — STOP, catat sebagai "BUTUH KEPUTUSAN FAKRUL" seperti sesi-sesi sebelumnya, lanjut ke bagian lain yang tidak terpengaruh.

---

## Bagian A — Login Gate: Akun Nonaktif/Melanggar

**Keputusan:** block total, bukan cuma block aksi tertentu.

Ada 2 status terpisah yang perlu digate:
- `users.status` (enum `['aktif', 'nonaktif']`, default `aktif`) — berlaku SEMUA role.
- `extras_profiles.status` (enum `aktif/tidak_aktif/melanggar`) — khusus Extras.

1. **Controller:** titik setelah `Auth::attempt()` sukses (`Auth\LoginController::login()`) — cek `$user->status !== 'aktif'` ATAU (`$user->role === 'extras'` DAN `$user->extrasProfile->status === 'melanggar'`) → langsung `Auth::logout()`, jangan biarkan sesi terbentuk, redirect balik ke login dengan pesan "Akun Anda dinonaktifkan. Hubungi admin untuk info lebih lanjut." (bahasa manusia, bukan pesan teknis — sesuai `UI-GUIDELINES.md`).
2. **Testing:** akun `nonaktif` gagal login (redirect ke login page, tidak ada sesi terbentuk — assert `Auth::check()` false setelah request). Extras dengan `extras_profiles.status = 'melanggar'` gagal login. Akun aktif normal tetap bisa login (regresi). Extras `tidak_aktif` (bukan `melanggar`) — TIDAK diblokir (`tidak_aktif` beda dari `melanggar`, cek makna masing-masing di `DATABASE-SCHEMA.md` sebelum asumsi — kalau ternyata `tidak_aktif` juga harus diblokir, itu ambiguitas scope, catat BUTUH KEPUTUSAN, untuk sekarang blokir `melanggar` saja sesuai temuan asli Session 19).

---

## Bagian B — Payment Gate: Wajib Status Lolos+

**Keputusan:** `PaymentController` cuma boleh diakses kalau `status_partisipasi` sudah `lolos` ke atas.

1. **Controller:** `PaymentController::show()` (dan method lain yang jadi entry point, `tandaiTransfer()`/`konfirmasiDiterima()`/`addAddon()` — cek semua method publik di controller ini) — tambah guard pakai konstanta yang SUDAH ADA `ProjectApplication::STATUS_LOLOS_KE_ATAS` (jangan bikin array baru, itu sudah dikonsolidasi sesi ponytail cleanup kemarin). `abort_unless(in_array($application->status_partisipasi, ProjectApplication::STATUS_LOLOS_KE_ATAS, true), 422, 'Pembayaran belum bisa diproses untuk status pendaftaran ini.')`.
2. **Testing:** aplikasi status `diajukan`/`nego_fee`/`deal` (belum lolos) → 422 di semua entry point Payment. Status `lolos`/`kontrak_ditandatangani`/`selesai_produksi` tetap normal (regresi).

---

## Bagian C — RF-08: Perluas Status yang Bisa Dibatalkan

**Keputusan:** `batalkan()` boleh dipanggil di status `lolos`/`kontrak_ditandatangani` juga, tidak cuma `deal` — biar RF-08 (3x mendadak → Melanggar) beneran bisa kejadian.

1. **Model:** `ProjectApplication::batalkan()` — guard awal ganti dari `!== 'deal'` jadi cek `!in_array($this->status_partisipasi, ['deal', 'lolos', 'kontrak_ditandatangani'], true)`. JANGAN masukkan `selesai_produksi` (produksi sudah selesai, tidak masuk akal dibatalkan) atau status pra-lolos (`diajukan_ke_cd`/`direview_cd`, itu belum ada komitmen shooting yang bisa "dibatalkan mendadak").
2. **Perhatian (bukan keputusan baru, cuma verifikasi):** kalau aplikasi status `kontrak_ditandatangani` dibatalkan, cek apakah perlu ada efek ke record `Contract`/PDF yang sudah ada (mis. ditandai batal). Task ini TIDAK diminta mengubah `Contract` model — kalau nemu gap di sana saat nulis test, itu bug terpisah, laporkan dulu (§14.1, nyentuh kontrak), jangan fix sekalian tanpa lapor.
3. **Testing:** `batalkan()` di status `lolos` dan `kontrak_ditandatangani` sekarang berhasil (sebelumnya `LogicException`). Status `selesai_produksi`/pra-`deal` tetap ditolak (regresi). Skenario RF-08 penuh: 3x batalkan mendadak di status `lolos` pada 3 proyek berbeda untuk 1 Extras yang sama → `ExtrasProfile::status` jadi `melanggar` (test baru, ini yang sebelumnya nyaris mustahil ditest).

---

## Bagian D — Nama Asli di Kontrak + Username (nyentuh Auth — hati-hati)

**Keputusan Fakrul (verbatim):** "ada fieldnya pakai di pdf juga, diisinya dari awal bareng alias, niknya kalo mau ke kontrak aja. btw alias ini kasi dalem kurung username dh, jangan sampe ada username yg sama, trus login bisa pake username itu juga."

Artinya 3 sub-bagian:

### D1. `nama_asli` dikumpulkan lebih awal (bareng alias, BUKAN bareng NIK)

- `alias` SEKARANG cuma diisi/wajib di `Extras\ProfileController::update()` (RF-06 "melengkapi profil", BUKAN di registrasi) — `nama_asli` ikut ditambahkan di titik YANG SAMA, wajib bareng `alias`. NIK TETAP di titik terpisah (`lengkapiKtp()`, cuma pas mau generate kontrak) — JANGAN pindahkan NIK ke sini, itu keputusan lama yang tidak berubah (kolektif NIK harus di titik paling akhir sesuai desain minimal-data-sensitif).
- **Model:** `ExtrasProfile` — `nama_asli` sudah ada di `#[Fillable]` dan sudah `encrypted` cast (cek ulang, jangan asumsi, tapi setahu audit terakhir sudah benar). Kalau belum ada di Fillable, tambahkan.
- **Controller:** `Extras\ProfileController::update()` — tambah validasi `nama_asli` (`required`, `string`, `max:255`).
- **View:** `resources/views/extras/profile-edit.blade.php` — tambah field "Nama Asli (sesuai KTP)" dekat field Alias, dengan helper text singkat: "Dipakai di dokumen kontrak resmi, bukan yang tampil ke publik."

### D2. Kontrak PDF pakai `nama_asli`, bukan `alias`

- `resources/views/contracts/pdf-template.blade.php` (atau nama file template PDF kontrak yang benar — cek dulu) — ganti referensi nama penandatangan Extras dari `alias` ke `nama_asli`.
- **Perhatian:** Extras LAMA (data sudah ada sebelum fitur ini) mungkin belum punya `nama_asli` terisi kalau belum sempat update profil lagi. Kalau `nama_asli` kosong saat generate kontrak, JANGAN biarkan PDF nampilin string kosong/null — tambahkan guard di `ContractController` (atau di titik yang sama dengan gate `lengkapiKtp()` yang sudah ada buat NIK): kalau `nama_asli` belum diisi, redirect ke halaman lengkapi-profil dengan pesan jelas, SEBELUM sampai ke tahap generate kontrak. Reuse pola gate yang sudah ada untuk NIK (`ContractController::show()`), jangan bikin pola baru.
- **Testing:** generate kontrak dengan `nama_asli` terisi → PDF pakai nama itu (bukan alias). `nama_asli` kosong → di-gate, tidak sampai generate kontrak (mirip test NIK yang sudah ada).

### D3. Username unik + tampil "Alias (username)" + bisa login pakai username

- **Migration:** `users` — tambah kolom `username` (string, nullable, **unique**).
- **Model:** `User` — tambah `username` ke `#[Fillable]` (cek dulu isi existing sebelum nambah, pola bug lama selalu di sini).
- **Controller:** `Extras\ProfileController::update()` — tambah validasi `username` (`required` — WAJIB begitu Extras melengkapi profil, `string`, `alpha_dash` (huruf/angka/underscore/dash, biar aman dipakai sebagai login identifier, JANGAN izinkan spasi/karakter aneh), `max:50`, `unique:users,username,` . `$request->user()->id` (exclude diri sendiri saat update ulang)).
- **Login:** `Auth\LoginController::login()` — SEKARANG cuma terima email. Deteksi input: kalau mengandung `@` treat sebagai email (`Auth::attempt(['email' => $input, 'password' => ...])`), kalau tidak treat sebagai username (`Auth::attempt(['username' => $input, 'password' => ...])`). Field form login tetap 1 input teks (label diubah jadi "Email atau Username"), BUKAN 2 field terpisah.
- **View tampilan "Alias (username)":** di halaman yang menampilkan alias ke Admin/CD (`admin/projects/applicants.blade.php`, `cd/reviews/index.blade.php`, halaman lihat-profil) — tampilkan `{{ $profile->alias }}` + `(@{{ $profile->user->username }})` kalau `username` terisi (nullable buat data lama yang belum sempat isi — JANGAN tampilkan "(@)" kosong kalau null).
- **Testing:** register/update profil dengan username yang sudah dipakai user lain → ditolak validasi. Login pakai username (bukan email) → berhasil, sesi terbentuk sama seperti login via email. Login pakai email tetap jalan normal (regresi — JANGAN sampai fitur username mematahkan login email yang sudah ada). Alias yang sama tapi username beda tampil beda di halaman Admin (test tampilan, bukan cuma data).
- **PENTING — ini nyentuh Auth:** ikuti `CLAUDE.md` §14.1, kerjakan dengan subagent kalau itu konvensi project ini untuk modul auth, dan test coverage WAJIB lebih dari cukup (login adalah pintu masuk seluruh sistem, regresi di sini paling parah dampaknya).

---

## Bagian E — Model Assignment CD ↔ Proyek

**Keputusan:** JBTB bisa pegang banyak client bersamaan — perlu model assignment beneran, bukan asumsi 1 CD aktif.

1. **Migration:** tabel baru `cd_project_assignments` (pola sama `admin_project_assignments` yang sudah ada) — `casting_project_id` (FK), `cd_user_id` (FK ke `users`, role `casting_director`), timestamps. Unique constraint `[casting_project_id, cd_user_id]` (1 CD per assignment per proyek, tapi 1 proyek bisa punya beberapa CD kalau memang perlu — TIDAK unique per casting_project_id saja).
2. **Model:** `CastingProject` — tambah relasi `assignedCds(): BelongsToMany` (lewat tabel pivot di atas) atau `HasMany` ke model assignment (ikuti pola persis `AdminProjectAssignment` yang sudah ada, jangan bikin pola beda).
3. **Controller — siapa yang assign:** `Admin\CastingProjectController` (Admin Default, karena RF-05 bilang "Admin Default mengelola akun CD") — tambah action assign CD ke proyek (mis. di halaman edit proyek atau halaman terpisah kecil, reuse pola UI select existing kalau ada).
4. **Guard akses CD:** `InvoiceController::pastikanBolehLihat()` — untuk role `casting_director`, tambah cek `$castingProject->assignedCds()->where('cd_user_id', $user->id)->exists()`, bukan cuma cek role. `Cd\ReviewController` — filter query kandidat yang CD lihat/approve/reject supaya cuma proyek yang dia di-assign (tambah `whereHas('assignedCds', fn ($q) => $q->where('cd_user_id', auth()->id()))` atau setara, di titik yang sama semua list/approve/reject CD ambil data).
5. **Testing:** CD A cuma bisa akses invoice/approve-reject proyek yang dia di-assign, CD B (proyek beda) dapat 403/data kosong. Admin Default bisa assign CD ke proyek. Regresi: alur approve/reject yang sudah ada tetap jalan normal untuk CD yang memang di-assign.

---

## Bagian F — Absensi Formal Extras (Korlap, Fitur Baru)

**Keputusan:** Korlap butuh tandai hadir/tidak-hadir Extras per tanggal shooting (formal, bukan cuma catatan bebas RF-35 yang sudah ada) — INI FITUR BARU, di luar RF asli manapun di `BAB-3-DRAFT.md`, catat sebagai penambahan scope resmi (bukan gap-fill RF lama) saat update dokumentasi nanti.

**Batasan:** JANGAN otomatis kaitkan status absen ke pembayaran atau ke hitungan RF-08 Melanggar — itu keputusan terpisah yang belum diminta Fakrul. Absensi ini INFORMASIONAL dulu (tercatat, terlihat), efek otomatis ke sistem lain nunggu keputusan lanjutan.

1. **Migration:** tabel baru `attendances` — `project_application_id` (FK), `event_shooting_date_id` (FK ke `event_shooting_dates`, biar spesifik per tanggal shooting mana), `status` (enum `hadir`/`tidak_hadir`), `dicatat_oleh` (FK `users`), `catatan` (text nullable, opsional), timestamps. Unique `[project_application_id, event_shooting_date_id]` (1 record per aplikasi per tanggal).
2. **Model:** `Attendance` baru, relasi ke `ProjectApplication` dan `EventShootingDate`. `ProjectApplication` — tambah `hasMany(Attendance::class)`.
3. **Controller & Routes:** controller baru (mis. `Admin\AttendanceController`), method buat tandai hadir/tidak-hadir per Extras per tanggal shooting sebuah proyek. Route `POST /admin/applications/{application}/absen`, middleware `role:admin_default,admin_korlap` (pola sama RF-35 field notes).
4. **View — halaman Korlap yang DIREDESAIN, bukan reuse applicants.blade.php penuh:** karena Korlap kerjanya on-site (bukan di depan komputer kantor kayak Admin Default), bikin halaman TERSENDIRI yang ringkas: daftar Extras yang aktif di proyek+tanggal shooting yang relevan, tombol tandai hadir/tidak-hadir per orang, plus form catatan lapangan (reuse `FieldNote` yang sudah ada dari RF-35). JANGAN tampilkan tombol Grade/Nego/Batalkan/aksi finansial lain sama sekali di halaman ini — itu bukan wewenang Korlap dan bikin halaman berantakan buat dipakai cepat di lapangan (mobile-friendly, tombol besar, sesuai prinsip "semua umur" di `UI-GUIDELINES.md`).
5. **Route akses halaman ini:** pastikan Korlap BENERAN bisa sampai ke halaman baru ini (ini akar masalah RF-35 sebelumnya — route POST ada tapi halaman GET-nya kegembok admin_default murni). Middleware halaman baru ini: `role:admin_default,admin_korlap`.
6. **Testing:** Korlap bisa akses halaman baru, tandai hadir/tidak-hadir, submit catatan lapangan. Role lain (Extras, CD, Talco, Sosmed) 403. Talco/Sosmed TIDAK bisa akses (mereka read-only riwayat sendiri saja, bukan operasional). 1 aplikasi cuma bisa punya 1 record absensi per tanggal shooting (constraint unique teruji, percobaan submit 2x untuk kombinasi sama → update bukan duplicate record).

---

## Testing Notes (keseluruhan batch)

- Full regression `php artisan test` WAJIB hijau di index tiap bagian sebelum lanjut ke bagian berikutnya (lihat Aturan Main).
- `./vendor/bin/pint` clean di akhir tiap bagian.
- Update `DEV-NOTES.md` — SATU entri per bagian selesai (6 entri kalau semua selesai), bukan 1 entri raksasa di akhir — supaya kalau berhenti di tengah (misal di Bagian D karena kompleks), progressnya jelas kebaca.

## Catatan

Setelah SEMUA 6 bagian selesai (atau berhenti di salah satu karena stop-condition, sesuai Aturan Main), update `docs/BAB-3-DRAFT.md`/`PRD-LITE.md` untuk:
- RF-33/34 dicatat statusnya diperluas (Bagian C).
- Bagian F (absensi formal) dicatat sebagai FITUR BARU di luar RF asli — beri nomor RF baru kalau perlu konsistensi dokumen akademik (mis. RF-53), atau tulis sebagai "penambahan pasca-proposal" sesuai konvensi dokumentasi project ini.

SPEC.md berikutnya kosong dulu setelah ini — tunggu Fakrul cek hasil batch besar ini sebelum lanjut task baru.
