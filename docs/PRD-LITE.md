# PRD Lite — SIM Casting JBTB

> Sumber kebenaran ringkas buat mulai coding. Detail lengkap tetap di `BAB-3-DRAFT.md` (Bab 3 Proposal) dan `CLAUDE.md` (konteks bisnis penuh) — file ini cuma distilasi biar AI/dev nggak perlu nyuap seluruh dokumen tiap mulai fitur baru.

## Tujuan Utama

Ganti proses rekrutmen & pembayaran extras/figuran PT. JBTB Casting Creative Group yang masih manual (grup WA + Excel + Google Drive) jadi sistem terpusat. Dua masalah inti yang diselesaikan:
1. **Transparansi fee** — kesepakatan fee antara Admin dan Extras harus tercatat, nggak bisa dibantah ("udah kerja belum dibayar / nggak sesuai deal").
2. **Transparansi honor staf** — karyawan pendukung (Talco/Korlap/Sosmed) belum punya slip gaji, sering "kira-kira" nominal yang didapat.

**User:** 7 role — Super Admin (owner), Admin Default (operasional, bisa >1 akun), 3 sub-role Admin (Talco/Korlap/Sosmed — akses terbatas), Casting Director (representasi client, akses via link khusus), Extras (figuran, self-register publik).

## Core Features (MVP) — JANGAN nambah di luar ini tanpa diskusi

Urutan sesuai dependency, bukan prioritas bisnis:

1. **Autentikasi RBAC 7-role** — 1 sistem login, hak akses beda per role.
2. **Profil Extras** — data diri, rate card, foto/video, status Aktif/Melanggar.
3. **Manajemen Proyek Casting** — Admin Default posting lowongan (kelas, kuota, tanggal shooting jamak, urgent flag).
4. **Pendaftaran & Seleksi** — Extras apply, deteksi bentrok jadwal (soft-warning, bukan blocking), Admin filter + set Grade.
5. **Negosiasi Fee in-app** — model ala InDrive: tawar-menawar multi-round tercatat, sampai Deal. HARUS terjadi sebelum present ke CD.
6. **Review & Approval CD** — CD approve/reject kandidat yang fee-nya udah Deal (individual/massal).
7. **Kontrak Digital** — auto-generate PDF Talent Release, TTD via canvas signature (bukan upload scan, bukan PSrE).
8. **Pembayaran Extras** — manual bukti transfer (BUKAN payment gateway), status Belum Dibayar → Ditransfer → Dikonfirmasi.
9. **Manajemen Karyawan (baru)** — Super Admin tambah Admin + sub-role + set nominal honor per-event.
10. **Absensi & Penggajian Staf (baru)** — log aktivitas nempel status "proyek selesai" → slip honor PDF auto-generate.
11. **Notifikasi** — email (primer) + WhatsApp gateway berbayar Fonnte/Wablas (pelengkap, BUKAN whatsapp-web.js).
12. **Dashboard & Riwayat Kerja** — per-role, termasuk view read-only Talco/Sosmed.

**Eksplisit DI LUAR scope** (lihat `BAB-3-DRAFT.md` §3.1.4 buat detail penuh):
- Talent profesional/pemeran utama (bukan Extras) — di luar sistem, cuma tercatat sebagai log kerja Talco.
- Payment gateway (Midtrans/Tripay) — ditolak, biaya transaksi nggak sepadan + beda produk (payout vs receive).
- Verifikasi Dukcapil buat NIK — validasi cuma duplikasi internal.
- Geolocation/foto check-in buat absensi staf — cuma log status proyek.
- E-signature tersertifikasi (PSrE) — canvas signature aja.

## User Flow Utama (happy path)

```
Extras daftar → lengkapi profil+rate card
   → lihat lowongan → apply (+warning bentrok jadwal kalau relevan)
   → Admin Default filter+grade → NEGO FEE in-app (multi-round) → Deal
   → Admin present ke CD → CD approve
   → Extras lengkapi KTP+rekening → kontrak auto-generate → TTD canvas (Admin+Extras)
   → Admin tandai transfer+bukti → Extras konfirmasi terima → Selesai
```

```
Super Admin tambah Admin+sub-role+set honor
   → tugaskan ke proyek (Talco/Korlap/Sosmed, opsional per proyek)
   → sistem log aktivitas → proyek Selesai → slip honor PDF auto-generate
   → staf lihat riwayat kerja & status honor sendiri (read-only)
```

## Cara pakai file ini

Kalau lagi kerjain 1 modul spesifik, cukup mention file ini + `DATABASE-SCHEMA.md` bagian tabel yang relevan — jangan suapin seluruh `BAB-3-DRAFT.md` (39+ RF) ke tiap prompt. Detail requirement lengkap per modul (kode RF-xx) tetap rujuk `BAB-3-DRAFT.md` kalau butuh presisi.
