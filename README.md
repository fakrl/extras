# SIM Casting JBTB

Sistem Informasi Manajemen Casting Talent & Extras Berbasis Web dengan Fitur Negosiasi Fee Digital — studi kasus **JBTB Casting** (agensi milik Jestika Aisya Kordak, brand *Jbtb.Casting*, Depok).

Proyek ini menggantikan proses rekrutmen extras/figuran yang masih manual (grup WhatsApp, Excel, Google Drive) dengan sistem terpusat: seleksi kandidat, negosiasi fee digital ala InDrive (multi-round, tercatat), kontrak digital dengan tanda tangan canvas, dan pelacakan status pembayaran transparan — untuk menghilangkan kesalahpahaman fee dan konflik "sudah kerja belum dibayar".

Ini adalah **project work pengganti skripsi** (bukan proyek open-source publik). Konteks bisnis, aktor (7 role), alur, dan backlog lengkap ada di `docs/CLAUDE.md`; MVP distilasi di `docs/PRD-LITE.md`.

## Tech Stack

- **Backend:** Laravel 13, PHP 8.3+
- **Database:** MySQL 8
- **Frontend:** Blade + custom CSS design system (bukan Bootstrap/Tailwind — lihat `resources/views/partials/theme-style.blade.php`), dark/light theme, Inter font
- **PDF:** `barryvdh/laravel-dompdf` (kontrak, invoice, slip honor)
- **Timezone:** Asia/Jakarta

Detail & keputusan teknis lengkap ada di `docs/TECH-STACK.md`.

## Setup Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set koneksi database di `.env` (MySQL, bukan SQLite default):

```
DB_CONNECTION=mysql
DB_DATABASE=jbtb
DB_USERNAME=root
DB_PASSWORD=
APP_TIMEZONE=Asia/Jakarta
```

Lalu jalankan migration dan server dev:

```bash
php artisan migrate
php artisan serve
```

### Catatan Penting

- **Storage upload private:** foto/video profil, KTP, tanda tangan, bukti transfer disimpan di disk `local` (`storage/app/private`), bukan `public` — jangan diubah tanpa alasan kuat, ini keputusan keamanan (lihat `docs/SECURITY-CHECKLIST.md`).
- Data sensitif (NIK, nama asli, rekening) di-enkripsi di level model (`encrypted` cast).
- Dokumentasi lengkap ada di folder `docs/` — baca `docs/CLAUDE.md` dan `docs/DEV-NOTES.md` sebelum mulai kerja modul baru.
