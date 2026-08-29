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
