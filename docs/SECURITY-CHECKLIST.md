# Security Checklist — SIM Casting JBTB (Pre-Launch)

> **Diverifikasi ulang 1 Okt 2026** terhadap sistem final (5 role, fitur sampai SPEC BR). Kolom Status berasal dari pembacaan kode nyata (`app/`, `routes/web.php`, `bootstrap/app.php`, `resources/views`, `.env.example`), bukan klaim laporan. Riwayat verifikasi sebelumnya (21 Agu, 28 Agu, 30 Agu, 8 Sep) sudah dilebur; detail uji ada di test suite (±600 test).
>
> Legenda: DONE, SEBAGIAN, BACKLOG, TEMUAN (celah yang perlu ditutup sebelum deploy).

Sumber: checklist umum keamanan aplikasi (20 poin) dipetakan ke Laravel + kebutuhan JBTB, ditambah poin 21–26 untuk fitur pasca-Agustus.

| # | Item | Status | Kondisi di sistem |
|---|---|---|---|
| 1 | Hide API Keys | DONE | `.env*` di `.gitignore`; token WA lewat `config('services.whatsapp.token')`; kredensial Google lewat config |
| 2 | Purge Git Secrets | BACKLOG | Scan riwayat repo (`gitleaks`/`trufflehog`). Repo masih PUBLIC (keputusan D13): `docs/CLAUDE.md` dan riwayat git memuat angka sensitif Client. **Jadikan private sebelum demo/sidang**, lalu scan history; kalau ada secret, rotate |
| 3 | Kredensial DB tidak ke client-side | DONE | Semua akses data lewat backend |
| 4 | Role-Level Security | DONE | `CheckRole` + `role:` per grup route; Super Admin hanya lolos ke route admin/korlap; akun `is_protected` dan akun sendiri tidak bisa dijadikan target hapus/nonaktif; Admin tidak bisa mengelola akun Client |
| 5 | Encrypt Sensitive Data | DONE | `nik`, `nama_asli`, `rekening` cast `encrypted`; duplikasi NIK lewat `nik_hash` (HMAC, kunci `NIK_HASH_KEY` terpisah dari `APP_KEY`; jangan dirotasi tanpa re-hash seluruh NIK). Berkas sensitif di `Storage::disk('local')` privat. `nomor_wa` sengaja tidak dienkripsi (disembunyikan lewat `#[Hidden]`) |
| 6 | Enforce server-side auth | DONE | `auth` + `role:` + otorisasi tingkat data di controller (kontrak, pembayaran, invoice, lampiran, media). Halaman publik (`/`, `/event/{token}`, `/p/extras/{token}`) sengaja tanpa auth dan tidak menampilkan data sensitif |
| 7 | Lock record access | DONE (manual) | Scoping manual: Client hanya proyek miliknya (`milikClient()`), Extras hanya miliknya, Korlap tanpa akses keuangan/akun. Belum memakai Laravel Policy formal |
| 8 | Block field tampering | DONE | `#[Fillable]` pada model; status dan grade hanya berubah lewat aksi terotorisasi |
| 9 | Secure session cookies | SEBAGIAN | `.env.example` memuat `SESSION_SECURE_COOKIE=false` dan `SESSION_ENCRYPT=false`. **Wajib `SESSION_SECURE_COOKIE=true` saat deploy HTTPS**; belum ada guard kode otomatis |
| 10 | Hash password | DONE | Cast `hashed`; password sementara/reset acak 10 karakter + `wajib_ganti_password` |
| 11 | Rate limit | SEBAGIAN | `throttle:5,1` pada login, register, forgot-password, apply lowongan, simpan KTP, pendaftaran Google; 10/menit pada redirect/callback Google. **TEMUAN:** `POST /reset-password` (`password.update`) belum di-throttle (butuh token valid, risiko rendah, tapi tambahkan `->middleware('throttle:5,1')`, satu baris + satu test) |
| 12 | Bot protection | BACKLOG | Belum ada CAPTCHA pada register/login (ditunda; mitigasi: throttle) |
| 13 | Parameterize queries | DONE | Eloquent parameterized. Raw query ada di `FilterAkun`, `AdminRingkasan`, `ProjectApplication`, `CastingProject`, `ExtrasCategory`, `TagController`, dan dashboard SA/Admin, namun diverifikasi: isinya ekspresi konstan, atau input pengguna di-bind (`whereRaw('LOWER(nama) = ?', [..])`); tidak ada interpolasi input ke SQL |
| 14 | Validate all input | SEBAGIAN | `$request->validate()` luas, masih inline per method (belum Form Request) |
| 15 | Escape user content | DONE | Output Blade `{{ }}`. Satu-satunya `{!! !!}` ada di `extras/dashboard.blade.php` (teks tindakan dengan tag `<em>`); nama proyek dibungkus `e()` sebelum disisipkan, sehingga aman |
| 16 | Restrict file uploads | DONE | Validasi `mimes`/`max` (lampiran ≤10MB), disimpan privat; hanya poster proyek di disk public |
| 17 | Trim API/view responses | DONE | Profil publik menyembunyikan NIK, nama asli, rekening, tarif, kontak, grade; halaman event publik tidak menampilkan nama client dan budget (ada test). Client tidak melihat nama asli, favorit, atau catatan internal |
| 18 | Security headers | DONE (catatan) | `SecurityHeaders`: `X-Frame-Options: DENY`, nosniff, Referrer-Policy, CSP, HSTS di production. Catatan: CSP tanpa `img-src`/`media-src` terpisah (mengikuti `default-src 'self'`) dan script `'unsafe-inline'`; periksa lagi bila menambah gambar/CDN dari luar |
| 19 | Force HTTPS | DONE | `URL::forceScheme('https')` + HSTS saat production |
| 20 | Scan dependencies | BACKLOG | Belum ada `composer audit` rutin / Dependabot. Jalankan `composer audit` dan `npm audit` sebelum deploy |
| 21 | Mode "lihat sebagai" (Super Admin) | DONE | Middleware `ViewAs`: Client/Extras read-only (semua non-GET ditolak), target Super Admin/terproteksi/nonaktif diblokir; aksi mode Admin/Korlap dicatat atas nama Super Admin "(sebagai X)"; mulai/keluar mode dilog |
| 22 | Audit trail | DONE | `activity_logs` (aktor, role, aksi, IP, user agent), halaman SA dengan pencarian dan filter |
| 23 | Login Google | DONE | Pendaftaran baru hanya Extras; role lain harus menghubungkan akun secara eksplisit (tidak auto-link via email); putus ditolak bila belum punya password; mati otomatis bila `GOOGLE_CLIENT_ID` kosong. Pesan "Akun ini login pakai Google" membuka fakta bahwa akun ada, dibatasi throttle |
| 24 | Paksa ganti password | DONE | `WajibGantiPassword` mengarahkan semua rute ber-auth ke `/ubah-password` |
| 25 | Registrasi Client | DONE | Registrasi publik Client ditutup; akun dibuat Super Admin |
| 26 | Aksi GET tidak mengubah data | DONE | Pembuatan kontrak/payment dipindah ke transisi status (BE.1) agar GET aman dan tidak bisa dipicu lewat tautan |

## Sebelum Deploy (urut prioritas)

1. Repo jadi private (poin 2) lalu scan history.
2. `SESSION_SECURE_COOKIE=true`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` benar.
3. Throttle `POST /reset-password` (poin 11).
4. `composer audit` + `npm audit` (poin 20).
5. Isi `GOOGLE_*` dan `WHATSAPP_SERVICE_TOKEN` produksi; pastikan `whatsapp-service` hanya di `127.0.0.1`.
6. Ganti seluruh password akun demo (`password`) dan hapus data demo bila bukan lingkungan demo.

## Cara Pakai

Cek ulang di akhir tiap sprint, terutama poin 4–8 (akses) saat menambah role/route baru dan 16–17 saat menambah upload.
