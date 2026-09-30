# whatsapp-service (RF-37)

Proses Node.js terpisah dari Laravel — gateway WA self-hosted pakai `whatsapp-web.js` (bukan gateway pihak ketiga berbayar). Laravel cuma panggil `POST /send` lewat HTTP, tidak ada dependency Puppeteer/whatsapp-web.js di sisi Laravel.

## Setup (sekali di awal)

```bash
cd whatsapp-service
npm install
cp .env.example .env
# isi WHATSAPP_SERVICE_TOKEN dengan string acak, SAMAKAN dengan
# WHATSAPP_SERVICE_TOKEN di .env Laravel (root project)
```

## Jalankan

```bash
node server.js
```

Pertama kali jalan akan muncul QR code di terminal — scan pakai WhatsApp di HP (nomor khusus sistem, bukan nomor pribadi) lewat menu **Perangkat Tertaut**. Session disimpan persisten di folder `.wwebjs_auth/` (jangan commit, sudah di-`.gitignore`) — restart proses berikutnya TIDAK perlu scan ulang selama folder ini tidak dihapus.

Untuk jalan 24/7 di server, pakai PM2 atau systemd, contoh PM2:

```bash
npm install -g pm2
pm2 start server.js --name whatsapp-service
pm2 save
pm2 startup
```

## Endpoint

- `POST /send` — header `Authorization: Bearer <WHATSAPP_SERVICE_TOKEN>`, body `{ "nomor": "62812xxxxxxxx", "pesan": "..." }`. Balas `{ "sukses": true|false }`.
- `GET /health` — cek status pairing (`{ "siap": true|false }`), untuk dicek manual, bukan dashboard produk.

## Operasional (dipantau manual, bukan fitur produk)

- Status koneksi/QR pairing dipantau manual lewat terminal/log proses ini oleh Fakrul, sesuai `docs/SPEC.md` Batasan — tidak ada dashboard di sisi Laravel.
- Kalau WA logout/ke-unlink dari HP, hapus `.wwebjs_auth/` lalu jalankan ulang untuk scan QR baru.

## Tes kirim dari Laravel (BO.1)

Variabel yang harus cocok:

| `whatsapp-service/.env` | `.env` Laravel (root) |
|---|---|
| `PORT=3001` | `WHATSAPP_SERVICE_URL=http://127.0.0.1:3001` (port sama) |
| `WHATSAPP_SERVICE_TOKEN=abc...` | `WHATSAPP_SERVICE_TOKEN=abc...` (sama persis, tanpa spasi/kutip) |

Setelah ubah `.env` Laravel, jalankan `php artisan config:clear`.

Urutan nyalain:

1. `cd whatsapp-service && node server.js`
2. Scan QR di terminal (sekali saja), tunggu log `WhatsApp client siap.`
3. `php artisan wa:tes 0812xxxxxxxx "Halo tes"` — kirim langsung tanpa queue. Nomor boleh `0812…`, `+62 812…`, `812…` (dinormalisasi ke `62812…`).
4. Kalau terkirim, nyalakan worker notifikasi: `php artisan queue:work`

Arti error `wa:tes`:

- **Koneksi ditolak** — Node belum jalan / port beda.
- **401** — token beda antara dua `.env`.
- **503** — belum scan QR / masih connect.
- **500** — Node gagal kirim (nomor tidak punya WA / sesi putus).
