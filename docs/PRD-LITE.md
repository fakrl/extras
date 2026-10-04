# PRD Lite — SIM Casting JBTB

> Distilasi ringkas per 1 Okt 2026 (sistem final, **feature freeze**). Detail lengkap: `BAB-3-DRAFT.md` (kebutuhan RF), `DATABASE-SCHEMA.md` (31 tabel), `SPEC.md` (riwayat tugas AY–BR), `docs/CLAUDE.md` (konteks bisnis). Jangan tambah fitur tanpa eskalasi ke Fakrul.

## Tujuan Utama

Ganti proses rekrutmen dan pembayaran Extras/figuran PT. JBTB Casting Creative Group yang manual (grup WA + Excel + Google Drive) jadi sistem terpusat. Masalah inti:

1. **Transparansi fee** — kesepakatan fee Admin dan Extras tercatat (nego bertingkat), tidak bisa dibantah.
2. **Transparansi honor staf** — slip honor jelas per proyek.
3. **Keuangan proyek terlihat** — Masuk, Piutang, Keluar, Saldo, Proyeksi dari satu sumber hitung.

## User: 5 Role

| Role | Ringkas |
|---|---|
| Super Admin | Pimpinan. Dashboard + monitoring, kelola semua akun, ACC proyek Client, tugaskan Admin, honor staf, log aktivitas. Mode pantau: sebagai Admin/Korlap (aksi penuh), sebagai Client/Extras (lihat-saja) |
| Admin | Operasional inti: proyek, seleksi, grade, nego, ajukan ke Client, kontrak, invoice, pembayaran, keuangan, Kelola Akun Extras/Client |
| Korlap | Lapangan: absensi, validasi selfie, catatan/sanksi, riwayat kerja sendiri |
| Client | Wakil PH. Ajukan brief, Greenlight (lock/tolak), jadwal, invoice. Akun dibuat Super Admin |
| Extras | Figuran. Daftar mandiri (form/Google), profil, daftar peran, nego, TTD kontrak, absen selfie, konfirmasi honor |

## Fitur Final (yang sudah ada)

1. **Autentikasi RBAC 5 role** + login Google (aktif bila config diisi) + wajib ganti password sementara.
2. **Profil Extras** — data diri, tag bebas, pengalaman, foto utama + 4 foto tambahan, video, izin tampil di beranda publik.
3. **Proyek casting** — peran + budget + kuota per peran, tanggal shooting jamak, lampiran, kode `JBTB-tahun-nomor`, tautan publik `/event/{token}`, pengajuan brief Client (ACC Super Admin).
4. **Pendaftaran & seleksi** — kuota antrian, aturan bentrok jadwal (blokir hanya bila bentrok dengan Lolos/Kontrak), filter tag/grade/favorit, grade A/B/C, Favorit ⭐ internal.
5. **Nego fee in-app** — multi-round tercatat sampai Deal, sebelum diajukan ke Client.
6. **Greenlight Client** — grid kandidat, filter, lock/tolak (individual/massal), notifikasi ke Admin PIC.
7. **Kontrak digital** — PDF otomatis saat Lolos, TTD canvas, void otomatis bila batal.
8. **Pembayaran & keuangan** — bukti transfer manual (bukan payment gateway), sengketa, add-on, biaya lain-lain, invoice + TTD + tandai lunas, cashflow per proyek dan per periode.
9. **Staf** — akun Admin/Korlap, penugasan, honor per-event, slip PDF, riwayat kerja.
10. **Lapangan** — absensi (Admin/Korlap catat, Extras selfie, Korlap validasi), catatan/sanksi.
11. **Notifikasi** — in-app (lonceng) utama; WhatsApp via `whatsapp-web.js` self-hosted sebagai pelengkap (queue); pengingat terjadwal (H-3, input jadwal, H-1, akun mangkrak).
12. **Dashboard per role, Manajemen Akun SA, Log Aktivitas, Monitoring per role, Landing publik terkurasi, Export Excel/PDF.**
13. **UI mobile** — Extras bottom-nav 3 menu; role lain hamburger + drawer; sidebar desktop bisa diciutkan; audit overflow 360/390px lulus.

## Di Luar Scope (future work, dicatat di Bab 3 §3.1.5)

- Cek kelayakan registrasi, rating sikap 1–5 Korlap, scoring otomatis, re-book "panggil lagi".
- Email domain perusahaan (Lark) dan konfigurasi Google produksi — ditunda sampai deploy.
- QR check-in absensi, realtime/WebSocket (polling + notifikasi dinilai cukup).
- Payment gateway, PSrE, API Dukcapil, geolokasi absensi, pajak/BPJS staf.
- Talent profesional/pemeran utama.

## User Flow Utama

```
Client ajukan brief → Super Admin ACC (atau SA/Admin buat proyek langsung)
Extras daftar peran → Admin review + grade → NEGO FEE → Deal
   → Admin ajukan ke Client → Client lock → Lolos (kontrak + payment dibuat)
   → Extras lengkapi KTP → TTD canvas (Admin + Extras)
   → hari H: absensi → Admin transfer + bukti → Extras konfirmasi → Selesai
   → Invoice ke Client → tandai lunas → cashflow
```

```
Super Admin buat akun Admin/Korlap + honor → tugaskan ke proyek
   → proyek Selesai → slip honor PDF → staf lihat riwayat & status honor
```

## Keputusan Bisnis Masih Terbuka

Tabel `SPEC.md` AY.6 (D3–D9, D11–D22) menunggu rapat dengan Erlina dan Direktur. Paling berdampak: D5 (dasar invoice), D4 (aturan deal fee), D13 (repo public vs private).

## Cara Pakai File Ini

Mengerjakan satu modul: sebut file ini + bagian tabel relevan di `DATABASE-SCHEMA.md`. Presisi kebutuhan per kode RF ada di `BAB-3-DRAFT.md`.
