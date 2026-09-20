# SPEC.md — Bagian AI: Refaktoring RBAC & Manajemen Akun — SoftDeletes Akun, Proteksi Keep Data, & Submenu Dropdown Sidebar

> Ditulis 20 September 2026, oleh manager-session.
> **Sumber Utama:** `docs/BIMBINGAN-2026-09-19.md` (Tema A: Struktur menu, RBAC & penamaan, Poin A.1 & A.5).
> **Status Sesi Sebelumnya:** Bagian AG (Penyatuan CD ke Kelola Admin) dan Bagian AH (Kunci Grade Admin 2 Bulan) telah selesai dieksekusi dan ter-commit (`9173daf`). Catatan di `DEV-NOTES.md` ter-update sampai Session 59. Task ini difokuskan penuh pada penuntasan Tema A Bimbingan 19 September 2026.

---

## Konteks & Tujuan

Dari hasil sesi bimbingan tanggal 19 September 2026 bersama Dosen Pembimbing (Erlina) dan Super Admin (Fakrul), terdapat dua poin penting terkait struktur menu dan manajemen akun pengguna yang harus diselaraskan:

1. **A.5 Akun Jangan Pernah Dihapus (Keep Data & Audit Trail)**:
   - *Problem*: Saat ini `AdminManagementController@destroy` masih melakukan hard-delete (`$user->delete()`) jika akun belum memiliki relasi foreign key DB. Ini berisiko menghilangkan histori/audit trail pengguna. Model `User` juga belum memiliki trait `SoftDeletes`.
   - *Solusi*: Tambahkan trait `SoftDeletes` pada model `User` + buat migrasi kolom `deleted_at`. Hapus tombol "Hapus Permanen" dari UI Super Admin / Admin. Ganti menjadi mekanisme "Nonaktifkan / Arsipkan (Soft Delete)" agar seluruh data akun dan histori kinerjanya tetap tersimpan aman di database.

2. **A.1 Submenu Sidebar Menggunakan Dropdown / Collapsible**:
   - *Problem*: Navigasi sidebar Super Admin dan Admin saat ini masih berupa daftar flat text link.
   - *Solusi*: Terapkan struktur grup menu berbasis dropdown / collapsible `<details>` / `<summary>` pada komponen sidebar di `resources/views/partials/sidebar-*.blade.php`. Tampilan menu akan lebih rapi, terkelompok dengan jelas (misal: "Pengaturan & Pengguna", "Operasional Proyek") tanpa menambah dependency JavaScript eksternal.

---

## Bagian AI.1: Migration & Model `User` — Implementasi `SoftDeletes`

1. **Buat File Migration Baru**:
   - `database/migrations/2026_09_20_000001_add_soft_deletes_to_users_table.php`
   - Dalam method `up()`: `$table->softDeletes();` pada tabel `users`.
   - Dalam method `down()`: `$table->dropSoftDeletes();`.

2. **Update Model `app/Models/User.php`**:
   - Tambahkan `use Illuminate\Database\Eloquent\SoftDeletes;`
   - Masukkan trait `SoftDeletes` ke dalam class `User`.
   - Pastikan atribut `deleted_at` ter-cast sebagai `datetime`.

---

## Bagian AI.2: Refaktoring `AdminManagementController` & UI "Keep Data"

1. **Update Controller `app/Http/Controllers/SuperAdmin/AdminManagementController.php`**:
   - **`destroy(User $user)`**:
     - Panggil `$this->guardTarget($user);` (proteksi mutlak akun protected `fahrulmukhlisin13@gmail.com` dan akun login sendiri).
     - Eksekusi `$user->delete()` yang secara otomatis menjadi **Soft Delete** (`deleted_at = now()`).
     - Set status pengguna menjadi `"nonaktif"`.
     - Kembalikan redirect dengan pesan flash: `"Akun telah dinonaktifkan/diarsipkan. Data histori tetap tersimpan aman."`
   - **`restore(int $id)` (Method Baru)**:
     - Tambahkan method `restore($id)` untuk mengembalikan akun yang di-soft-delete jika dibutuhkan: `User::withTrashed()->findOrFail($id)->restore();`
   - **Filtering Soft Deleted Users**:
     - Pada `index()`, sediakan tab/filter status (`Semua`, `Aktif`, `Nonaktif / Arsip`).
     - Pengguna yang berada dalam status soft-deleted dapat ditampilkan pada tab "Nonaktif / Arsip" dengan opsi "Aktifkan Kembali" (restore).

2. **Perubahan Tampilan Frontend UI (`resources/views/super-admin/admins/index.blade.php`)**:
   - **Hapus Total** tombol/modal bertuliskan "Hapus Permanen" atau icon `ti-trash` yang melakukan hard delete.
   - Ganti tombol aksi menjadi **"Nonaktifkan"** (jika akun aktif) atau **"Aktifkan Kembali"** (jika akun nonaktif/trashed).
   - Berikan badge visual transparan `Status: Nonaktif` / `Diarsipkan` pada baris tabel pengguna yang non-aktif.

---

## Bagian AI.3: Restrukturisasi Sidebar dengan Submenu Dropdown (`<details>` / `<summary>`)

1. **Update Partial Sidebar Admin & Super Admin**:
   - `resources/views/partials/sidebar-super_admin.blade.php`
   - `resources/views/partials/sidebar-admin_default.blade.php`
   - `resources/views/partials/sidebar-admin_korlap.blade.php`
   - `resources/views/partials/sidebar-casting_director.blade.php`

2. **Pola Desain Submenu Collapsible**:
   - Gunakan elemen native HTML `<details class="sidebar-dropdown" {{ $isGroupActive ? "open" : "" }}>` dengan `<summary class="sidebar-dropdown-summary">`.
   - Tambahkan CSS untuk `.sidebar-dropdown`, `.sidebar-dropdown-summary`, dan `.sidebar-submenu` di `resources/views/layouts/app.blade.php`:
     - Chevron icon (`ti ti-chevron-right`) yang berotasi otomatis saat `<details open>`.
     - Penanda status `.active` tetap menonjolkan submenu yang sedang diakses.

3. **Grouping Menu Super Admin**:
   - **Aplikasi & Monitoring** (Dropdown):
     - Dashboard (`/super-admin/dashboard`)
     - Monitoring Akun (`/super-admin/monitoring`)
   - **Pengaturan & Pengguna** (Dropdown):
     - Kelola Admin & CD (`/super-admin/admins`)

4. **Grouping Menu Admin Default**:
   - **Operasional Proyek** (Dropdown):
     - Kelola Proyek (`/admin/projects`)
     - Rekap Extras (`/admin/recap`)
     - Absensi (`/admin/absensi`)
   - **Pengaturan & Akun** (Dropdown):
     - Kelola Akun (`/admin/users`)
     - Riwayat Kerja (`/admin/riwayat-kerja`)

---

## Bagian AI.4: Verifikasi & Test Coverage

1. **Jalankan Command Test**:
   - `php artisan test --filter=AdminManagementTest`
   - `php artisan test --filter=SuperAdminCdManagementTest`

2. **Ekspektasi Hasil Verification**:
   - `assertSoftDeleted("users", ["id" => $user->id])` saat aksi hapus/nonaktifkan dijalankan.
   - Akun tidak pernah terhapus secara fisik dari DB (`users` table).
   - Sidebar renders submenu dropdown dengan benar pada layar desktop dan mobile.

---

## Checklist Eksekusi untuk Implementer (Claude Code / Antigrafity)

- [ ] Jalankan migration `add_soft_deletes_to_users_table`.
- [ ] Tambahkan `SoftDeletes` trait di `User.php`.
- [ ] Refactor `AdminManagementController.php` (`destroy`, `restore`, filtering).
- [ ] Perbarui view `super-admin/admins/index.blade.php` (hapus hard delete UI, ganti toggle nonaktif/restore).
- [ ] Terapkan submenu dropdown `<details>` di `partials/sidebar-*.blade.php` dan tambahkan style pendukung di `layouts/app.blade.php`.
- [ ] Jalankan `php artisan test` dan pastikan 100% PASS.
- [ ] Document hasil di `docs/DEV-NOTES.md` untuk Session 60.

