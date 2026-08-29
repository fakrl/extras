# TECH-STACK.md — SIM Casting JBTB

> Aturan main teknis. Baca ini sebelum generate kode apa pun — biar AI/dev nggak nebak-nebak versi/pola yang dipakai. Status per 29 Agu 2026: coding aktif, 9 sesi dev selesai — semua keputusan di bawah sudah final & terverifikasi jalan di kode nyata, bukan rencana lagi (lihat `DEV-NOTES.md`).

## Stack Wajib

- **Backend:** Laravel 13 (PHP 8.3+), MySQL 8, timezone Asia/Jakarta. (Revisi 22 Agu 2026: awalnya ditulis Laravel 11 mengikuti pola Nobel Akademi, tapi itu keliru — Laravel 11 security fixes sudah berakhir 12 Maret 2026, EOL. Laravel 13 dipilih meski paling baru — pertimbangan: breaking changes minimal dari 12→13, PHP 8.3.31 di environment dev sudah mendukung.)
- **Frontend:** Blade + custom CSS design system (bukan Bootstrap/Tailwind, bukan Livewire/Alpine) — **keputusan final Sprint 1** (22 Agu 2026), lihat `resources/views/partials/theme-style.blade.php` & `docs/UI-GUIDELINES.md`. Nego fee multi-round & canvas signature (RF-16-20, RF-26) dibangun pakai POST form biasa + vanilla JS canvas, bukan library reaktif.
- **Session/Cache/Queue:** database-backed (pola sama seperti Nobel Akademi) kecuali ada alasan spesifik buat pindah ke Redis.
- **PDF:** `barryvdh/laravel-dompdf` (kontrak, invoice, slip honor) — 3 dokumen ini SATU infrastruktur PDF yang di-reuse, jangan bikin 3 generator beda.
- **Canvas signature:** vanilla JS (bukan library) — keputusan final Sprint 4, lihat `resources/views/components/signature-pad.blade.php`. TTD disematkan ke PDF sebagai image, bukan field terpisah.
- **WhatsApp Gateway:** `whatsapp-web.js` self-hosted (revisi 28 Agu 2026 — Fonnte/Wablas TERNYATA juga "unofficial"/risiko banned sama, dicek langsung ke fonnte.com FAQ; lihat `OPEN-QUESTIONS-PROPOSAL.md` poin 3 untuk detail). Jalan sebagai proses Node.js/Express terpisah (24/7, port lokal misal 3001), Laravel panggil lewat `Http::post()` — Laravel tidak perlu tahu detail Puppeteer/WhatsApp Web. Mitigasi wajib: nomor khusus sistem (bukan nomor admin pribadi), volume rendah non-broadcast, WA cuma kanal pelengkap (email tetap primer).
- **Email:** queued Mailable + SMTP (pola Nobel Akademi). `MAIL_MAILER=log` buat dev tanpa DNS.
- **Hosting:** shared hosting/VPS, domain+SSL atas nama agensi (JBTB), bukan atas nama developer — biar sistem nggak mati pas developer lulus.

## Yang TIDAK dipakai (keputusan eksplisit, jangan diulang tanya)

- Payment gateway (Midtrans/Tripay) — ditolak, lihat `CLAUDE.md` §"Keputusan Desain" poin 10.
- E-signature tersertifikasi (PSrE) — canvas signature aja, bukan tanda tangan elektronik legal formal.
- Geolocation/foto check-in — absensi staf cuma log status "proyek selesai".
- API resmi WhatsApp Business (360dialog/Twilio) — overhead kebesaran buat timeline 2-4 bulan solo-dev.
- Gateway berbayar (Fonnte/Wablas) — revisi 28 Agu 2026: TERNYATA sama-sama "unofficial"/risiko banned dengan `whatsapp-web.js` (dicek FAQ resmi fonnte.com), jadi tidak ada alasan bayar hanya demi kemudahan infrastruktur. Lihat baris WhatsApp Gateway di atas & `OPEN-QUESTIONS-PROPOSAL.md` poin 3.

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

## Commands (sudah di-scaffold — Laravel 13, lihat README.md untuk setup lokal lengkap)

```bash
php artisan serve
php artisan migrate
php artisan test
./vendor/bin/pint       # code formatting
cd whatsapp-service && node server.js   # WA gateway, terpisah dari Laravel — lihat README.md-nya
```

## Environment yang Dibutuhkan

```
DB_* (MySQL)
MAIL_MAILER, MAIL_HOST, dst (SMTP)
WHATSAPP_SERVICE_URL / WHATSAPP_SERVICE_TOKEN (endpoint + shared-secret ke `whatsapp-service/` self-hosted, lihat `whatsapp-service/README.md`)
APP_TIMEZONE=Asia/Jakarta
```

## Catatan

Semua keputusan "rencana" dari draft awal sudah final (lihat catatan revisi per baris di atas). Update file ini kalau ada keputusan teknis baru — jangan bikin file stack baru terpisah.
