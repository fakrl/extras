# Antigravity Rules — SIM Casting JBTB (Extras)

## Dev Server & Environment (Sesuai info.txt)
- Tiap kali menjalankan `php artisan serve` untuk project ini, **WAJIB ikuti petunjuk di `info.txt`**:
  - **Port WAJIB 9999**: `php artisan serve --port=9999` (URL: `http://127.0.0.1:9999`).
  - **JANGAN PERNAH gunakan port 8000** karena port 8000 dicadangkan khusus untuk project lain milik user.
- **Ngrok Public Tunnel**:
  - Menggunakan domain tetap: `ngrok http --url=ferris-hardy-judo.ngrok-free.dev 9999`
  - URL publik yang dibagikan ke teman/kelompok: `https://ferris-hardy-judo.ngrok-free.dev`
- **Cache Clearing**:
  - Setelah ubah view/config: `php artisan config:clear`, `php artisan view:clear`, `php artisan cache:clear`.
- Jangan mengubah database production / konfigurasi sensitif.
