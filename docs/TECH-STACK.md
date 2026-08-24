# TECH-STACK.md — SIM Casting JBTB

> Aturan main teknis. Baca ini sebelum generate kode apa pun — biar AI/dev nggak nebak-nebak versi/pola yang dipakai. Belum ada kode berjalan per 22 Agu 2026 (masih tahap proposal) — beberapa keputusan di bawah masih **rencana**, ditandai eksplisit.

## Stack Wajib

- **Backend:** Laravel 13 (PHP 8.3+), MySQL 8, timezone Asia/Jakarta. (Revisi 22 Agu 2026: awalnya ditulis Laravel 11 mengikuti pola Nobel Akademi, tapi itu keliru — Laravel 11 security fixes sudah berakhir 12 Maret 2026, EOL. Laravel 13 dipilih meski paling baru — pertimbangan: breaking changes minimal dari 12→13, PHP 8.3.31 di environment dev sudah mendukung.)
- **Frontend:** Blade + Bootstrap 5 (rekomendasi, sejalan RNF-08 kebutuhan compatibility desktop+mobile). Livewire/Alpine BOLEH dipakai kalau butuh interaktivitas tanpa reload (misal negosiasi fee multi-round, canvas signature) — keputusan final nanti pas Sprint 1, catat di sini begitu fix.
- **Session/Cache/Queue:** database-backed (pola sama seperti Nobel Akademi) kecuali ada alasan spesifik buat pindah ke Redis.
- **PDF:** `barryvdh/laravel-dompdf` (kontrak, invoice, slip honor) — 3 dokumen ini SATU infrastruktur PDF yang di-reuse, jangan bikin 3 generator beda.
- **Canvas signature:** library JS signature-pad (belum dipilih spesifik — cari yang lightweight, nggak perlu backend service terpisah). TTD disematkan ke PDF sebagai image, bukan field terpisah.
- **WhatsApp Gateway:** Fonnte atau Wablas (berbayar). **DILARANG** pakai `whatsapp-web.js`/sesi unofficial — risiko banned tinggi di volume notifikasi (lihat `OPEN-QUESTIONS-PROPOSAL.md` poin 3).
- **Email:** queued Mailable + SMTP (pola Nobel Akademi). `MAIL_MAILER=log` buat dev tanpa DNS.
- **Hosting:** shared hosting/VPS, domain+SSL atas nama agensi (JBTB), bukan atas nama developer — biar sistem nggak mati pas developer lulus.

## Yang TIDAK dipakai (keputusan eksplisit, jangan diulang tanya)

- Payment gateway (Midtrans/Tripay) — ditolak, lihat `CLAUDE.md` §"Keputusan Desain" poin 10.
- E-signature tersertifikasi (PSrE) — canvas signature aja, bukan tanda tangan elektronik legal formal.
- Geolocation/foto check-in — absensi staf cuma log status "proyek selesai".
- API resmi WhatsApp Business (360dialog/Twilio) — overhead kebesaran buat timeline 2-4 bulan solo-dev, gateway berbayar biasa udah cukup.

## Struktur Folder (rencana, ikuti konvensi Laravel default + pola Nobel Akademi)

```
app/
├── Models/
├── Http/Controllers/
│   ├── Admin/          # Super Admin & Admin Default & sub-role
│   ├── CD/             # Casting Director
│   └── Extras/          # Extras
├── Services/            # WA Gateway wrapper, PDF generator wrapper
├── Enums/               # UserRole (7 role), StatusPartisipasi, StatusPembayaran
└── Helpers/

resources/views/
├── layouts/             # per-role layout (super-admin, admin, cd, extras)
├── components/
├── partials/
├── pages/               # public-facing (registrasi, lowongan publik)
└── dashboard/           # per-role dashboard
```

## Coding Convention

- **Satu class per file** (PSR-4) — jangan multiple class dalam satu file controller.
- **RBAC via Policy/Gate**, bukan `if (auth()->user()->role === 'admin')` bertebaran di controller/view. Middleware per route group (`role:super-admin`, `role:admin-default`, dll — pola `CheckRole` seperti Nobel Akademi, bukan middleware terpisah per role).
- **Mass assignment protection wajib** — Extras nggak boleh bisa self-set Grade/fee lewat request yang dimanipulasi. `$fillable` ketat per model, validasi field per role di Form Request.
- **JSON array fields** di-cast di model (misal `tanggal_shooting` kalau disimpan array, tapi lihat `DATABASE-SCHEMA.md` — direkomendasikan tabel terpisah `event_shooting_dates`, bukan JSON, karena butuh query overlap tanggal buat deteksi bentrok).
- **Enum untuk status**: `UserRole` (7 value), status partisipasi & status pembayaran dua enum terpisah (JANGAN digabung jadi satu state machine — lihat `CLAUDE.md` §6).
- **API Resource/transformer** kalau ada endpoint yang return data ke role terbatas (misal Extras jangan pernah nerima field margin/fee-client mentah).
- **Encrypted cast** untuk NIK (`'nik' => 'encrypted'`), private disk storage untuk upload KTP/kontrak/bukti transfer — lihat `SECURITY-CHECKLIST.md` buat checklist lengkap.

## Commands (isi begitu project di-scaffold)

```bash
composer create-project laravel/laravel . "^11.0"
php artisan serve
npm run dev
php artisan queue:listen --tries=1
php artisan migrate
php artisan db:seed
./vendor/bin/pint       # code formatting
```

## Environment yang Dibutuhkan

```
DB_* (MySQL)
MAIL_MAILER, MAIL_HOST, dst (SMTP)
FONNTE_TOKEN / WABLAS_TOKEN (WA Gateway — pilih salah satu, jangan implement dua-duanya)
APP_TIMEZONE=Asia/Jakarta
```

## Catatan

File ini akan diperbarui begitu keputusan teknis konkret diambil pas Sprint 1 (misal: Bootstrap vs Livewire/Alpine, library signature-pad spesifik). Update di sini, jangan bikin file stack baru terpisah.
