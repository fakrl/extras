# Requirement Baseline Summary
## Sistem Informasi Manajemen Casting Talent dan Extras Berbasis Web dengan Fitur Negosiasi Fee Digital
### Studi Kasus: JBTB Casting

> **⚠️ SUPERSEDED.** Dokumen ini mendeskripsikan desain lama (3 aktor, fee fixed dengan 1x counter-offer, TTD upload scan, storage Google Drive) yang sudah digantikan. Acuan terkini: `docs/BAB-3-DRAFT.md` dan `docs/CLAUDE.md` (7 aktor, nego fee ala InDrive tanpa batas ronde, canvas signature, private local disk). Jangan jadikan dasar implementasi.

> Dokumen ini adalah **baseline** (garis dasar) requirement yang telah disepakati dan dikonfirmasi (per 14 Juli 2026), dirangkum dari seluruh dokumen analisis sebelumnya. Menjadi acuan tetap untuk tahap perancangan (Class Diagram, Rancangan Pengujian) dan implementasi.

---

## Business Summary

SIM Casting JBTB dibangun untuk menggantikan proses rekrutmen dan manajemen extras/figuran yang saat ini masih manual (grup WhatsApp, Excel, Google Drive) di JBTB Casting. Sistem berfokus pada **manajemen extras** (bukan talent profesional), dengan tujuan mempercepat proses seleksi, menghilangkan kesalahpahaman fee melalui mekanisme negosiasi yang transparan, memusatkan penyimpanan data, dan mempercepat penyampaian informasi ke extras yang lolos seleksi.

**Masalah yang diselesaikan:** rekrutmen lambat via WA → kesalahpahaman fee → data tersebar/hilang → keputusan CD lambat → info dadakan ke extras.

---

## Actor Summary

| Aktor | Jumlah Tingkatan | Cara Masuk Sistem |
|---|---|---|
| **Admin Agensi** | 1 (tanpa sub-role) | Dibuat oleh sistem/superuser awal |
| **Casting Director (CD)** | 1, punya akun login sendiri | Dibuat & dikelola oleh Admin |
| **Extras** | 1, publik | Self-register mandiri + verifikasi email |

Production House (PH) **bukan** aktor sistem — berinteraksi dengan sistem secara tidak langsung melalui Admin (offline).

**Hak akses (RBAC):** Admin = akses penuh; CD = review/approve kandidat & dashboard sendiri; Extras = data & aksi akun/pendaftaran sendiri.

---

## Functional Summary

**10 Modul:** Autentikasi & Akun · Profil Extras · Manajemen Proyek Casting · Pendaftaran & Seleksi · Negosiasi Fee Digital · Kontrak Digital · Pembatalan · Notifikasi · Dashboard · Laporan/Rekap.

**Alur inti (state machine):**
```
Diajukan → Direview CD → Lolos/Ditolak → Nego Fee → Deal → Kontrak Ditandatangani → Selesai Produksi
                                                        └─→ Dibatalkan (cabang dari Deal/Kontrak Ditandatangani)
```

**Fitur unggulan:** filter kandidat multi-kriteria (usia, gender, tinggi, ukuran baju, warna kulit, pengalaman, bahasa), negosiasi fee 1x pengajuan alternatif, auto-generate kontrak PDF dari template dinamis dengan alur TTD bertahap (upload dokumen scan, bukan e-signature tersertifikasi), pencatatan pembatalan otomatis.

Total **39 Kebutuhan Fungsional (RF-01 s.d. RF-39)** dan **13 Kebutuhan Non-Fungsional (RNF-01 s.d. RNF-13)** telah didefinisikan.

---

## Business Rules Summary

| Aturan | Ketentuan |
|---|---|
| Validasi identitas | 1 NIK = 1 akun, divalidasi internal saat extras dinyatakan lolos seleksi (bukan via Dukcapil) |
| Negosiasi fee | Extras hanya boleh mengajukan **1x** fee alternatif per pendaftaran; keputusan Admin atas pengajuan tersebut bersifat **final** |
| Approval berjenjang | CD approve/reject kandidat → Admin putuskan fee final → kedua pihak TTD kontrak |
| Pembatalan mendadak | **3x** pembatalan pada proyek berbeda → status Extras otomatis berubah menjadi "Melanggar" |
| Tanda tangan kontrak | Berupa unggah PDF hasil TTD manual/scan oleh Admin & Extras — bukan digital signature tersertifikasi (PSrE) |
| Fee & pembayaran | Fee bersifat tetap dari client; pembayaran dilakukan transfer bank **di luar sistem** — sistem hanya mencatat status |
| Retensi data | Data (termasuk KTP, foto, video, kontrak) disimpan dengan masa retensi **1 tahun** |

---

## Dashboard Summary

| Role | Konten Dashboard |
|---|---|
| **Admin** | Jumlah proyek berjalan, jumlah CD, jumlah extras terdaftar, jumlah casting dibuka, jumlah pendaftar, jadwal shooting terdekat, notifikasi data yang perlu diproses |
| **Casting Director** | Jumlah proyek ditangani, daftar casting aktif, jumlah pendaftar per casting, daftar extras lolos seleksi, jadwal casting hari ini, notifikasi pendaftar baru |
| **Extras** | Profil singkat, status pendaftaran (Pending/Diterima/Ditolak), daftar casting dibuka, jadwal shooting (jika diterima), riwayat mengikuti casting, notifikasi |

---

## Report Summary

| Laporan | Deskripsi | Output |
|---|---|---|
| Rekap Extras Paling Sering Terpilih | Ranking extras berdasarkan frekuensi keterpilihan per periode | Excel |
| Rekap Status Keaktifan Extras | Status Aktif / Tidak Aktif / Melanggar seluruh extras terdaftar | Excel |

Kedua laporan dapat diekspor oleh Admin Agensi ke format **Excel**.

---

## Security Summary

| Aspek | Ketentuan |
|---|---|
| Autentikasi | Email & password, **tanpa MFA** (skala kecil, target pengguna non-teknis) |
| Verifikasi akun | Wajib verifikasi email saat registrasi Extras sebelum akun aktif |
| Pemulihan akun | Forgot password via email untuk seluruh aktor |
| Anti-bot | CAPTCHA pada form registrasi & login |
| Kontrol akses | Role-Based Access Control (RBAC) sesuai 3 aktor |
| Enkripsi data | Data sensitif (KTP, foto, video profil) dienkripsi |
| Password | Disimpan dalam bentuk hash, bukan plain text |

---

## Integration Summary

| Integrasi | Status |
|---|---|
| API eksternal (umum) | Tidak dibutuhkan — sistem monolitik berdiri sendiri (Laravel) |
| Dukcapil | **Tidak** terintegrasi — validasi NIK hanya internal |
| WhatsApp API | **Tidak** terintegrasi otomatis — hanya link grup WA manual per proyek |
| Payment Gateway | **Tidak** digunakan — pembayaran fee di luar sistem (transfer bank manual) |
| Email Service | **SMTP Gmail** untuk seluruh notifikasi email |
| Web Push | Native Web Push API (service worker browser), tanpa pihak ketiga eksternal wajib |

---

## Technical Summary

| Aspek | Ketentuan |
|---|---|
| Platform | Web (responsif desktop & mobile browser), tanpa aplikasi native |
| Bahasa/Framework | Laravel (PHP) |
| Database | MySQL |
| Hosting | Shared hosting / VPS |
| Metodologi Pengembangan | Agile/Scrum |
| Metodologi Pemodelan | UML (Use Case, Activity, Sequence, Class Diagram) |
| Metode Pengujian | Black Box Testing |
| Skala Sistem | ±70 extras terdaftar, ±3 proyek casting aktif per bulan (skala kecil-menengah) |

---

## Status

**Baseline ini final dan siap dijadikan acuan** untuk tahap perancangan **Class Diagram** dan **Rancangan Pengujian Black Box**. Perubahan requirement setelah titik ini sebaiknya dicatat sebagai *change request* terpisah agar traceability skripsi tetap terjaga.
