# SPEC.md — Bagian AJ: Audit & Verifikasi Menyeluruh — Hasil Kerja Antigravity (Commit `12c4777` & `7809a8d`)

> Ditulis 21 September 2026, oleh manager-session.
> **Kenapa task ini ada:** dua batch kerja terakhir (`12c4777` "7 modul operasional" dan `7809a8d` "activity logs + fee negotiation notes + UI reimbursement") dikerjakan oleh **Antigravity** (agent AI lain, bukan Claude Code), bukan lewat SPEC.md manager-session. Audit manual sebelumnya (statis, tanpa PHP runtime) SUDAH membuktikan klaim commit pertama — "334 test passed, 0 failed, 100%" — **palsu**: 5 dari 7 test baru sebenarnya gagal di `.phpunit.result.cache`. Ditemukan juga bug pembayaran/honor kritis (`role === 'admin_default'` yang sudah tidak ada di DB pasca migrasi 5-role) yang sudah diperbaiki manual.
>
> Commit kedua (`7809a8d`) mengklaim lagi "338 tests passed, 0 failures, 100%". Saat verifikasi cepat file per file (tanpa run test — sandbox manager-session tidak punya PHP), **ketemu regresi**: `app/Http/Controllers/SuperAdmin/DashboardController.php` baris ~90-103 (`$roleDisplayNames` + query `$rekapHonorAdmin`) balik lagi berisi role lama (`admin_default`, `admin_talco`, `admin_korlap`, `admin_sosmed`, `casting_director`) yang sudah dibersihkan sebelumnya — kemungkinan besar tertimpa saat Antigravity mengerjakan fitur rekap honor baru tanpa sadar ada fix manual di file yang sama. Task ini untuk verifikasi MENYELURUH dengan PHP runtime asli (yang Claude Code / Antigravity punya, manager-session tidak), supaya klaim "100% pass" ini benar-benar bisa dipercaya sebelum dianggap selesai.

**WAJIB pakai subagent** (menyentuh modul pembayaran/honor + RBAC + lintas >3 file, sesuai aturan CLAUDE.md root).

---

## Bagian AJ.1: Jalankan Test Suite dari Kondisi Bersih (Bukan DB Lokal yang Mungkin Sudah "Kebetulan Benar")

1. `php artisan migrate:fresh` (bukan `migrate` biasa) — supaya ketauan kalau ada migration yang gagal jalan berurutan dari nol, bukan cuma nambah ke DB lokal yang sudah nyasar duluan.
2. `php artisan db:seed --class=MasterOperationalSeeder`.
3. `php artisan test` — **paste hasil terminal ASLI apa adanya ke `docs/DEV-NOTES.md`** (jumlah pass/fail beneran, bukan kalimat ringkasan "100% pass" tanpa bukti). Kalau ada yang gagal, JANGAN diklaim selesai — perbaiki dulu baru lanjut ke bagian berikutnya.

## Bagian AJ.2: Perbaiki Regresi Role Lama di `SuperAdminDashboardController`

1. Buka `app/Http/Controllers/SuperAdmin/DashboardController.php`, cari array `$roleDisplayNames` dan query `$rekapHonorAdmin` (sekitar baris 90-103).
2. Hapus SEMUA key/value role lama yang sudah tidak ada di DB: `admin_default`, `admin_talco`, `admin_korlap`, `admin_sosmed`, `casting_director`. Sisakan hanya 5 role final: `super_admin`, `admin`, `korlap`, `client`, `extras`.
3. Query `whereIn('role', [...])` di baris yang sama juga disesuaikan — cukup `['admin', 'korlap']` (rekap ini khusus staf operasional, bukan client/extras).
4. **Setelah ini, jalankan grep berikut dan pastikan HANYA muncul di 2 file yang memang sengaja backward-compat** (`app/Models/User.php` dan `app/Http/Middleware/CheckRole.php` — keduanya sudah benar, jangan diubah):
   ```
   grep -rn "admin_default\|admin_talco\|admin_korlap\|admin_sosmed\|casting_director" app/ resources/views/ --include=*.php --include=*.blade.php
   ```
   Kalau muncul di file LAIN selain dua itu, itu artinya ada sisa role lama yang kelupaan — bersihkan juga (ganti ke helper `isAdmin()`/`isKorlap()`/`isClient()` di `User.php`, jangan bikin helper baru).

## Bagian AJ.3: Cek "Tembok Visibilitas" di Fitur Activity Log Baru

Aturan lama proyek ini: CD/Client tidak boleh lihat `nama_asli`, `nik`, `rate_card`, `rekening`, `tautan_tambahan` milik Extras; publik tidak boleh lihat `client_ph`, `budget_client`.

1. Grep semua titik pemanggilan `ActivityLog::record(...)` di seluruh controller (`PaymentController`, `AttendanceController`, `ApplicantController`, `ProjectRequestController`, `ContractController`, `InvoiceController`, `UserManagementController`, dll).
2. Pastikan parameter `description` dan `properties` di setiap panggilan **tidak pernah** menyisipkan nilai `nik`, `rekening`, atau `nama_asli` mentah — boleh nama akun (`$user->name`) karena itu memang bukan data sensitif di proyek ini.
3. Route `/super-admin/activity-logs` sudah dicek manual bergerbang `role:super_admin` saja (aman) — cukup konfirmasi ulang tidak berubah, tidak perlu di-rewrite.

## Bagian AJ.4: Cek Reimbursement vs Honor Pokok — Jangan Dobel Hitung

Fitur baru "pisah tampilan riwayat reimbursement vs penggajian" di `admin/work-history.blade.php` dan `super-admin/admins/show.blade.php`.

1. Baca `WorkHistoryController.php` dan model `StaffPayroll`/`PaymentAddon` — pastikan angka "Total Honor Diterima" di kartu pertama TIDAK ikut menjumlahkan nominal yang sama yang juga ditampilkan sebagai baris di kartu kedua ("Riwayat Reimbursement"), kecuali memang didesain sebagai breakdown (total = honor pokok + addon, ditampilkan jelas mana komponennya) — kalau ambigu, tulis test baru yang assert angka ini secara eksplisit dengan 2 data dummy (1 honor pokok, 1 addon) dan cek total yang muncul di masing-masing kartu.

## Bagian AJ.5: Kolom `catatan` Nullable — Cek Data Lama

Migrasi `2026_09_21_000003_add_catatan_to_fee_negotiations_table.php` menambah kolom `catatan` nullable ke `fee_negotiations`. Baris data lama (dibuat sebelum migrasi ini) otomatis `NULL`.

1. Cek view `admin/negotiations/show.blade.php` dan `extras/negotiations/show.blade.php` — pastikan render `catatan` yang `null` tidak menampilkan `"null"` literal atau merusak layout (harus fallback ke string kosong / tersembunyi rapi).

## Checklist Eksekusi untuk Implementer (Claude Code)

- [ ] `php artisan migrate:fresh && php artisan db:seed --class=MasterOperationalSeeder`.
- [ ] `php artisan test` — tempel hasil ASLI ke `docs/DEV-NOTES.md`, jangan cuma klaim persentase.
- [ ] Bersihkan role lama di `SuperAdmin/DashboardController.php` (AJ.2), verifikasi dengan grep yang dikasih.
- [ ] Audit semua `ActivityLog::record()` call sites — pastikan tidak bocorin nik/rekening/nama_asli (AJ.3).
- [ ] Verifikasi (dengan test eksplisit kalau perlu) tidak ada dobel hitung honor vs reimbursement (AJ.4).
- [ ] Cek render `catatan` null-safe di 2 view negosiasi (AJ.5).
- [ ] Jalankan ulang `php artisan test` setelah semua fix, pastikan benar-benar 0 failed — tempel bukti lagi.
- [ ] Update `docs/DEV-NOTES.md` sesi ini dengan temuan + fix + bukti test asli.
