# Staging InfinityFree — Runbook (Bagian DB)

> Staging gratis buat kelompok buka app tanpa ngrok. BUKAN CI/CD penuh, BUKAN produksi. Data dummy saja, selalu.

## 1. Build & deploy

1. Jalankan workflow **Deploy Staging (InfinityFree)** manual (`workflow_dispatch`) dari tab Actions — tidak pernah jalan otomatis.
2. Workflow menjalankan `scripts/build-staging.sh` lalu upload lewat FTP (secret `FTP_HOST`/`FTP_USERNAME`/`FTP_PASSWORD` di GitHub Actions, bukan di repo).
3. Layout bundle (pilih lewat input `layout` saat trigger):
   - **`sibling`** (default): isi `public/` → `htdocs/`; sisanya (`app`, `bootstrap`, `config`, `database`, `resources`, `routes`, `storage`, `vendor`) → **root akun InfinityFree, satu tingkat di atas `htdocs/`**. `index.php` TIDAK diubah — `__DIR__.'/../'` dari `htdocs/` otomatis balik ke root akun, sama persis seperti `public/` → project root di repo asli.
   - **`fallback`**: semua kode core masuk `htdocs/app-core/` + `.htaccess` (`Require all denied`), `index.php` disesuaikan.
   - **Belum diverifikasi langsung di File Manager InfinityFree** apakah upload di luar `htdocs/` (ke root akun) benar-benar diizinkan/web-aman — DB.0 cuma mengonfirmasi root berisi `htdocs/`, `.htaccess`, `.override`, marker "DO NOT UPLOAD FILES HERE". Marker itu bisa dibaca dua arah (jangan upload *web files* di sini vs jangan upload apa pun di sini). **Fakrul: cek ini dulu di panel/File Manager sebelum deploy pertama** — kalau ragu/ditolak, pakai `layout=fallback`.
4. `.env` staging **dibuat manual di File Manager server**, tidak pernah lewat git/bundle/Action. `APP_KEY` staging **harus beda** dari produksi (`php artisan key:generate --show` lokal, tempel manual).

## 2. Nilai `.env` staging yang dipakai

```
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://ujikom-fakhrul.site.je
APP_KEY=<generate baru, beda dari produksi>

DB_CONNECTION=mysql
DB_HOST=<isi dari panel InfinityFree>
DB_PORT=3306
DB_DATABASE=<isi dari panel>
DB_USERNAME=<isi dari panel>
DB_PASSWORD=<isi dari panel>

QUEUE_CONNECTION=sync
MAIL_MAILER=log
WHATSAPP_ENABLED=false

SESSION_DRIVER=file
CACHE_STORE=file

STAGING_NO_SYMLINK=true
STAGING_PHOTO_MAX_KB=2048
STAGING_VIDEO_UPLOAD_ENABLED=false

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

Alasan keputusan:
- **`SESSION_DRIVER=file` + `CACHE_STORE=file`** (bukan `database`): server tidak menerima koneksi DB dari luar (lihat §3) dan tiap request bikin write sesi/cache — pakai `file` menghindari dependency tabel `sessions`/`cache` yang harus ikut proses dump/impor manual, dan menghindari beban tulis DB tiap request di shared hosting. Tidak butuh kode tambahan, cuma nilai env.
- **`GOOGLE_CLIENT_ID` kosong**: `GoogleController::aktif()` sudah `return filled(config('services.google.client_id'))` — tombol Google otomatis hilang, tidak perlu flag baru.
- **`WHATSAPP_ENABLED=false`**: flag baru (`config('services.whatsapp.enabled')`, default `true` di produksi) — `WhatsAppService::kirim()` log-only, tidak pernah panggil Node service yang memang tidak ada di staging.
- **`STAGING_NO_SYMLINK=true`**: mengaktifkan `'serve' => true` di disk `public` (`config/filesystems.php`) — fitur bawaan Laravel (route `/storage/{path}` baca langsung dari disk), bukan kode custom. `storage:link` TIDAK PERNAH dijalankan di server ini (symlink di-disable InfinityFree).
- **`STAGING_PHOTO_MAX_KB=2048`**: dipakai di `ProfileController` (foto profil + foto tambahan) dan `AttendanceSelfieController` (selfie absensi). Default tanpa env = `5120` (perilaku produksi tidak berubah).
- **`STAGING_VIDEO_UPLOAD_ENABLED=false`**: `ProfileController::uploadVideo()`/`uploadVideoJson()` balas 403 kalau false. Default tanpa env = `true` (fitur tetap ada di produksi). **UI tombol upload video TIDAK disembunyikan** — kalau dicoba di staging, hasilnya 403, cukup buat staging (bukan UX akhir).

## 3. Migrasi DB — WAJIB manual, bukan lewat web

Server InfinityFree **tidak menerima koneksi MySQL dari luar** (tidak bisa `php artisan migrate` dari laptop ke DB server). Alur:

1. Migrasi + seed demo **lokal** ke MySQL lokal: `php artisan migrate:fresh --seed` (pakai seeder demo yang sudah ada, data dummy saja — JANGAN seed data KTP/rekening asli).
2. `mysqldump` hasil lokal itu: `mysqldump -u root -p nama_db_lokal > staging-dump.sql`.
3. Buka phpMyAdmin di panel InfinityFree → buat database → Import → upload `staging-dump.sql`.
4. Tidak ada route/endpoint web yang menjalankan migrate di server mana pun — ini dilarang keras (celah keamanan serius, unauthenticated remote code execution kalau sampai lupa diproteksi).

### 3.1 Dump & impor (dikerjakan 28 Nov 2026, hasil untuk Fakrul)

Dikerjakan di database **lokal terpisah** (`sicasti_staging_dump`, BUKAN DB dev `jbtb` sehari-hari), supaya tidak mencampur data kerja dengan data demo:

1. `CREATE DATABASE sicasti_staging_dump` kosong di MySQL lokal.
2. Migrate + seed dengan **`APP_KEY` dan `NIK_HASH_KEY` yang SAMA PERSIS** dengan yang akan dipakai di `.env` server (override lewat env proses, bukan file — `.env` lokal asli tidak pernah diubah):
   ```
   DB_DATABASE=sicasti_staging_dump APP_KEY=<key staging> NIK_HASH_KEY=<key staging> \
     php artisan migrate --force
   DB_DATABASE=sicasti_staging_dump APP_KEY=<key staging> NIK_HASH_KEY=<key staging> \
     php artisan db:seed --class=DemoLengkapSeeder --force
   ```
   **PENTING — alasan wajib pakai key yang sama:** `ExtrasProfile.nik`/`nama_asli`/`rekening` adalah cast `encrypted` (pakai `APP_KEY`), dan `nik_hash` adalah HMAC pakai `NIK_HASH_KEY` (`config/app.php:123`, lihat `SECURITY-CHECKLIST.md` poin 5). Kalau dump dibuat dengan key lokal lalu diimpor ke server yang `.env`-nya pakai key staging berbeda, ketiga kolom itu **tidak bisa didekripsi** (`DecryptException` saat dibuka di server) dan validasi duplikat NIK (RF-04) ikut rusak karena `nik_hash` dihitung dengan key yang beda. Seeder dummy resmi: `docs/AKUN-DEMO.md` (gitignored).
3. Verifikasi SEBELUM dump final: baca beberapa baris `ExtrasProfile` pakai key staging yang sama, pastikan nol `DecryptException` dan `nik_hash` cocok dengan `hash_hmac('sha256', $nik_digits, $NIK_HASH_KEY)` hasil dekripsi. (Dilakukan 28 Nov 2026: 5 sampel, semua cocok.)
4. Dump kompatibel phpMyAdmin:
   ```
   mysqldump -u root --single-transaction --no-tablespaces --default-character-set=utf8mb4 \
     --skip-add-locks --skip-comments --routines=false --triggers=false --no-create-db \
     sicasti_staging_dump > sicasti-staging-dump.sql
   ```
   Tanpa `CREATE DATABASE`/`USE` (nama DB di server InfinityFree beda, dibuat manual dulu di phpMyAdmin). Hasil: 32 `CREATE TABLE`, ~180 KB — jauh di bawah limit mana pun, tidak perlu dipecah/di-gzip. Tabel `sessions`/`cache`/`cache_locks`/`jobs`/`job_batches`/`failed_jobs` dicek kosong (0 baris) sebelum dump — tidak ada data sesi/cache lokal yang ikut.
5. Database lokal sementara **dihapus** setelah dump diverifikasi ada `CREATE TABLE` utuh untuk semua tabel migrasi + `INSERT` seeder.
6. Impor di server: phpMyAdmin → database staging (dibuat manual sesuai nama di panel) → tab Import → upload file dump.
7. **Lingkup APP_KEY/NIK_HASH_KEY lainnya** (dicek 28 Nov 2026): cuma `ExtrasProfile` yang punya cast `encrypted`/HMAC di seluruh `app/Models/` — tidak ada model lain, tidak ada pemakaian `Crypt::`/`encrypt()`/`decrypt()` manual di `app/`. `Hash::make()` di seeder (password login) pakai bcrypt, independen dari `APP_KEY`, aman dipindah key apa pun.

**Catatan isi data demo** (relevan buat keputusan privasi/identitas sebelum dipakai publik):
- Seeder pakai **nama selebriti asli** sebagai `nama_asli` extras (mis. "Tom Holland", "Zendaya", "Jackie Chan") — ini SENGAJA (lihat `docs/AKUN-DEMO.md`), dipasangkan dengan NIK dummy (`3276...`) dan nomor rekening dummy, BUKAN data orang sungguhan yang di-scrape.
- **Tidak ada foto orang sungguhan.** `fotoDemo()` ambil foto stok generik dari `pravatar.cc` (bukan foto figur yang namanya dipakai) kalau ada koneksi internet, fallback ke avatar SVG inisial kalau offline — dua-duanya tidak terkait dengan selebriti yang namanya dipinjam.
- Akun login demo (password semua **`password`**, lihat `docs/AKUN-DEMO.md` lengkap): `owner_jbtb` (Super Admin), `admin_rina`/`admin_yoga`/`admin_lestari`/`admin_hendra` (Admin, `admin_hendra` nonaktif), `korlap_bambang`/`korlap_dedi` (Korlap), `client_andini`/`client_rudy`/`client_maya` (Client), + 24 akun Extras (username nama karakter, mis. `spiderman`, `goyounjung`).

## 4. Yang diterima apa adanya (batasan hosting, bukan bug)

- **Tidak ada cron**: 5 jadwal di `routes/console.php` tidak pernah jalan di InfinityFree. Tidak dibuatkan endpoint web pemicu (itu sendiri celah keamanan).
- **`queue:work`/`schedule:run`/`schedule:work` JANGAN PERNAH dijalankan di server ini** — `sleep()` ada di `disable_functions`, worker/scheduler akan fatal. `QUEUE_CONNECTION=sync` sudah cukup (job jalan inline).
- **Challenge bot InfinityFree** (`?i=1` redirect + cookie JS di kunjungan pertama): browser normal lolos otomatis. `curl`/health-check HTTP polos dan Playwright headless yang tidak jalankan JS **tidak bisa dipakai** buat verifikasi otomatis di domain ini — jangan bikin step CI yang mengandalkan itu.
- **Service worker (`public/sw.js`)**: precache path semua relatif (`/manifest.webmanifest`, `/favicon.ico`, ikon) — tidak ada asumsi protokol/host, harusnya aman di subdomain HTTPS InfinityFree. **Belum diverifikasi langsung**: apakah challenge bot `?i=1` ikut mencegat fetch `cache.addAll()` saat install SW pertama kali (kemungkinan tidak, karena SW register terjadi setelah halaman utama sudah lolos challenge dan cookie-nya ke-set, tapi ini DUGAAN, bukan hasil tes langsung di domain staging). Catat kalau manual smoke test DB.4 nemu SW gagal install.
- **Disk `local`** (foto absensi, slip gaji, foto profil via `response()`): sudah lewat controller (`Storage::disk('local')->response()`), tidak pernah bergantung symlink — tidak perlu perubahan, tinggal dites manual.

## 5. Utang teknis (dicatat, bukan dibereskan di sini)

- `vite.config.js` + `resources/css/app.css` + `resources/js/app.js` ada, tapi **tidak ada satu pun `@vite(...)` di Blade manapun** (grep seluruh `resources/views`: 0 hit) — `public/build` tidak pernah dipakai runtime sama sekali. **28 Nov 2026**: langkah `npm ci && npm run build` di `scripts/build-staging.sh` DIHAPUS (bukan cuma dicatat) — selain hasilnya mubazir, repo ini tidak punya `package-lock.json` jadi `npm ci` gagal keras dan bikin run deploy pertama gagal total. Build sekarang cuma `composer install --no-dev`. Kalau nanti ada yang benar-benar pasang `@vite` di Blade, tambahkan lagi langkah npm build di skrip — jangan lupa commit `package-lock.json` juga.

## 6. Checklist smoke test manual (DB.4 — dijalankan Antigravity di situs live, bukan di sini)

- [ ] Login sebagai tiap 5 role: Super Admin, Admin, Korlap, Client, Extras.
- [ ] Ganti password paksa (akun baru / `must_change_password`).
- [ ] Extras: upload foto profil (cek batas 2MB benar-benar ditolak di atas itu, diterima di bawah).
- [ ] Extras: coba upload video perkenalan → harus 403 (dimatikan di staging).
- [ ] Extras: upload selfie absensi on-set.
- [ ] Admin: buka lowongan, lihat pendaftar, grade + nego fee.
- [ ] Admin: generate & export PDF (kontrak/slip/invoice — pilih satu yang paling gampang diuji).
- [ ] Admin/Super Admin: export Excel (rekap extras).
- [ ] Halaman landing publik (`/`) + halaman roster/event publik.
- [ ] Akun Extras manual dibuat Admin/Super Admin (Bagian DA) kalau sudah selesai saat smoke test ini jalan.
- [ ] Banner "STAGING — data dummy" kelihatan di semua halaman di atas.
- [ ] Tombol "Lanjut dengan Google" TIDAK muncul (karena `GOOGLE_CLIENT_ID` kosong).
- [ ] Foto/poster yang pernah diupload tampil normal tanpa symlink (`STAGING_NO_SYMLINK=true`).
- [ ] Catat tiap kegagalan yang disebabkan batasan hosting (bukan bug asli) ke `docs/DEV-NOTES.md`, bukan dibuka sebagai bug ticket.

## 7. Keamanan staging

- Seed demo saja, tanpa KTP/rekening asli — kalau seeder demo pernah diisi data mirip asli, cek ulang sebelum dipakai di sini.
- Repo ini public — sudah di-grep ulang (27 Nov 2026) untuk kredensial FTP/API key/`.env` ter-commit: bersih, tidak ada temuan baru dari pekerjaan Bagian DB ini.
- Beri tahu kelompok: situs ini staging/uji coba, bukan sistem final buat data produksi.
