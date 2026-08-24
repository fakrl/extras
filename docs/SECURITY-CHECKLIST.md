# Security Checklist — SIM Casting JBTB (Pre-Launch)

> **Status: BACKLOG untuk fase development** — dicatat 21 Agu 2026, belum ada yang dikerjakan (proyek masih tahap proposal/Bab 3, belum mulai coding). Dipetakan biar nggak lupa pas mulai sprint nanti. Relevan banget di sistem ini karena nyimpen data sensitif riil: KTP/NIK, foto/video profil, nomor rekening, dan data margin/fee yang harus admin-only.

Sumber: checklist umum keamanan aplikasi (20 poin), dipetakan ke konteks Laravel + kebutuhan spesifik JBTB.

| # | Item | Kenapa relevan di sistem ini | Cara implementasi di Laravel |
|---|---|---|---|
| 1 | Hide API Keys | Kredensial WA Gateway (Fonnte/Wablas), SMTP Gmail, dsb | Simpan di `.env`, akses via `config()` (bukan `env()` langsung di kode aplikasi), pastikan `.env` masuk `.gitignore` |
| 2 | Purge Git Secrets | Repo `extras` udah lumayan lama jalan — worth di-scan riwayatnya | Scan history repo pakai `git-secrets`/`trufflehog`; kalau ketemu secret ke-commit, **rotate** kredensialnya, jangan cuma hapus commit-nya |
| 3 | Jangan expose kredensial DB ke client-side | Semua akses data extras/fee/margin harus lewat backend | Semua query lewat Laravel backend (Eloquent/API), tidak ada akses DB langsung dari JS/frontend |
| 4 | Role-Level Security | 3 role (Admin/CD/Extras) dengan visibilitas data beda-beda (tembok anti-poaching di `CLAUDE.md` §5) | Laravel Policy/Gate per role + middleware per route group; jangan andalkan hide-di-UI doang |
| 5 | Encrypt Sensitive Data | KTP/NIK, foto/video profil, rekening | Eloquent encrypted cast (`'nik' => 'encrypted'`) di model, storage file terenkripsi/private disk |
| 6 | Enforce server-side auth | Role nggak boleh cuma dipercaya dari session client | Semua authorization check di backend (Policy+Middleware), verifikasi ulang tiap request, bukan trust dari frontend state |
| 7 | Lock record access | Extras cuma boleh liat profil/riwayat sendiri; CD cuma liat kandidat yang diajukan ke dia | Laravel Policy `view`/`viewAny` per model, scoped query berdasarkan user login |
| 8 | Block field tampering | Extras nggak boleh bisa self-set Grade atau fee sendiri lewat request yang dimanipulasi | Mass assignment protection (`$fillable`/`$guarded` di Eloquent), validasi field per role di Form Request |
| 9 | Secure session cookies | Standar tapi wajib dicek pas deploy production (HTTPS) | Pastikan `session.php`: `secure=true`, `http_only=true`, `same_site=lax/strict` aktif di production |
| 10 | Hash password | Sudah RNF-03 di Bab 3 | Default Laravel (`bcrypt`) — no action tambahan, cukup jangan diubah ke plain/reversible |
| 11 | Rate limit login/apply | Cegah brute force login & spam apply lowongan | `throttle` middleware di route login/register/apply |
| 12 | Bot protection | Registrasi Extras terbuka ke publik | CAPTCHA di form register & login (udah ada di requirement lama RNF, pastiin kebawa ke build) |
| 13 | Parameterize queries | Cegah SQL injection | Eloquent ORM udah auto-parameterized; kalau ada raw query (`DB::raw`, `whereRaw`) **wajib** pakai parameter binding, jangan concat string input user |
| 14 | Validate all input | Semua form (profil, proyek casting, nego fee, upload) | Laravel Form Request validation di **setiap** endpoint — jangan andalkan validasi frontend doang |
| 15 | Escape user content | Bio/portofolio Extras yang ditampilkan ke Admin/CD | Blade `{{ }}` udah auto-escape (default aman); kalau ada `{!! !!}` buat konten user, wajib di-sanitize dulu (`strip_tags`/HTML purifier) |
| 16 | Restrict file uploads | Foto/video profil, scan KTP, bukti transfer — semua upload dari publik | Whitelist ekstensi & MIME type, limit ukuran file, simpan di private disk (bukan public root), jangan trust nama file asli |
| 17 | Trim API responses | Jangan sampai response API balikin field NIK/rekening/margin ke role yang nggak berhak | Pakai API Resource/transformer per role — jangan `return $model` mentah dari controller |
| 18 | Security headers | Standar hardening | CSP, `X-Frame-Options`, `X-Content-Type-Options` — bisa pakai `spatie/laravel-csp` atau middleware manual |
| 19 | Force HTTPS | Wajib karena ada data KTP/rekening yang dikirim lewat form | `URL::forceScheme('https')` + redirect middleware di production, aktifkan HSTS |
| 20 | Scan dependencies | Composer & npm package bisa punya vulnerability | `composer audit` rutin (bukan sekali doang), aktifkan Dependabot di repo GitHub |

## Cara pakai
Checklist ini dicek ulang di **akhir tiap sprint** (bukan cuma di akhir project) — terutama poin 4–8 (access control) begitu modul Autentikasi & Profil selesai di Sprint 1, dan poin 16–17 begitu modul upload (KTP, kontrak, bukti TF) mulai dibangun. Update kolom status per poin begitu action-nya beres, biar kelihatan progressnya pas laporan akhir.
