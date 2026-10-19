# Ringkasan Sistem — SIM Casting JBTB

> Contekan cepat untuk memahami dan mencoba sistem (per 17 Oktober 2026). Detail teknis: `DATABASE-SCHEMA.md`, `SYSTEM-ARCHITECTURE.md`. Login demo dan password: `AKUN-DEMO.md` (gitignored, ada di laptop Fakrul). Login bisa pakai email **atau** username.
>
> Siapkan data: `php artisan migrate:fresh --seed` (menghapus isi database lokal, mengisi ulang data demo; tanggal ikut hari dijalankan, jadi jalankan di hari demo).

## 1. Masalah yang diselesaikan

JBTB menyalurkan extras (pemeran figuran) ke production house (Client). Sebelumnya semua lewat WhatsApp dan spreadsheet: fee sering berbeda dari kesepakatan, tidak ada bukti kontrak, absensi dan honor sulit dilacak. Sistem ini mencatat satu alur dari lowongan sampai honor diterima.

## 2. Alur utama (satu Extras di satu proyek)

```
Client ajukan brief ─> Super Admin ACC ─> Admin buka lowongan
                                               │
          (atau Admin UNDANG Extras langsung) ─┤
                                               ▼
Extras daftar ─> Admin review + grade ─> Penawaran / nego fee ─> Deal
      ─> Diajukan ke Client ─> Client pilih (Lolos) ─> Kontrak TTD Admin + Extras
      ─> Shooting: absen selfie, Korlap validasi ─> Admin transfer honor
      ─> Extras konfirmasi terima (atau laporkan masalah → Admin selesaikan)
```

Status pendaftaran: `diundang` → `diajukan` → `direview_admin` → `nego_fee` → `deal` → `diajukan_ke_client` → `lolos` → `kontrak_ditandatangani` → `selesai_produksi`, cabang `ditolak` / `dibatalkan`.
Status pembayaran: `belum_dibayar` → `ditransfer` → `dikonfirmasi_diterima`, cabang `disengketakan`.

## 3. Lima role: bisa apa dan cara mencobanya

| Role | Bisa apa | Coba dengan (data demo) |
|---|---|---|
| **Super Admin** (owner) | Semua yang Admin bisa, plus: ACC/tolak brief Client, kelola semua akun (buat Staf & Client, nonaktifkan, ganti role), tugaskan Admin/Korlap ke proyek, tandai penugasan selesai → slip honor staf, log aktivitas, mode pantau "lihat sebagai" role lain | `owner_jbtb` → Dashboard → ACC proyek P4 "Nadaria"; Proyek P2 > tab Keuangan > Penugasan Staf; Monitoring > lihat sebagai Extras |
| **Admin** (PIC proyek) | Buat/edit proyek (peran, kuota, tanggal shooting, fee bisa dinego atau fee tetap), buka/tutup lowongan, Lineup pendaftar (grade A/B/C, tag, filter), undang Extras + tombol WhatsApp, nego fee, ajukan kandidat ke Client, TTD kontrak, transfer honor + upload bukti, selesaikan sengketa, invoice ke Client, catat biaya lain-lain | `admin_rina` → Proyek P2 "Minuman Segar" > Lineup (satu Extras di tiap tahap); nego dengan `rehan_x`; TTD kontrak `bagas22` |
| **Korlap** (lapangan) | Dashboard proyek yang ditugaskan saja, absensi hari shooting (catat hadir/tidak, validasi atau tolak selfie Extras), catatan & sanksi Extras, riwayat kerja + unduh slip honor | `korlap_bambang` → Absensi P3 "Kampus Biru" (shooting hari ini): validasi selfie `melati_k`. `korlap_dedi` hanya melihat P2 |
| **Client** (production house) | Ajukan brief proyek, lihat kandidat yang diajukan Admin (tanpa kontak/NIK), pilih atau tolak kandidat, atur jadwal & jam panggil, TTD invoice | `client_andini` → Pilih Kandidat P2: `dimas_rk` dan `kevin_t` menunggu dipilih; `client_maya` punya brief P4 yang belum di-ACC |
| **Extras** (pemeran) | Daftar (email atau Google), lengkapi profil per bagian (foto wajib), lamar lowongan, terima/tolak undangan, nego atau terima/tolak penawaran fee, TTD kontrak (isi KTP & rekening saat lolos), absen selfie di hari shooting, konfirmasi honor atau laporkan masalah, batal ikut | `arga_p` → balas nego P2; `sari_mei` → terima/tolak undangan P2; `bagas22` → TTD kontrak; `clara_b` → konfirmasi honor P3 |

**Catatan penting saat mencoba:**
- Extras demo **belum punya foto**, jadi tidak bisa melamar lowongan sampai foto diunggah di Profil (sengaja: profil lengkap = syarat melamar).
- Batal ikut kurang dari 2 hari sebelum shooting = **batal mendadak**; 3× → akun `melanggar` dan tidak bisa login (contoh: `joko_s`).
- Client tidak pernah melihat budget/margin JBTB, nomor WA, NIK, atau Extras yang masih `diundang`.
- Akun nonaktif langsung ter-logout (contoh: `admin_hendra`, `putri_a`).

## 4. Fitur per kelompok

| Kelompok | Fitur |
|---|---|
| Akun & keamanan | 5 role, login email/username/Google (Extras), wajib ganti password untuk akun buatan SA, blokir akun nonaktif/melanggar, NIK/nama asli/rekening terenkripsi, log aktivitas |
| Proyek & lowongan | Brief Client → ACC, peran + kuota (antrean), tanggal shooting jamak, lowongan urgent, nego terbuka/fee tetap, tautan pendaftaran publik `/event/{token}`, lampiran, kode `JBTB-2026-001` |
| Seleksi | Lineup + grade + tag (% cocok), favorit, undang Extras, cek bentrok jadwal antarproyek, Client pilih kandidat |
| Fee & kontrak | Nego multi-ronde tercatat, kontrak digital + TTD di browser + PDF |
| Lapangan | Absensi selfie + validasi Korlap, catatan/sanksi, kalender jadwal |
| Uang (pencatatan) | Honor Extras (transfer, bukti, konfirmasi, sengketa, add-on), invoice ke Client (nominal manual, TTD), biaya lain-lain, penugasan & slip honor staf |
| Komunikasi | Notifikasi in-app (+ email/WA bila diaktifkan), tombol "Hubungi via WA" untuk Admin |
| Publik | Beranda (cara kerja, lowongan, cast, portofolio, FAQ), profil publik Extras, halaman privasi |

## 5. Database: 31 tabel (23 bisnis + 8 bawaan Laravel)

**Inti alur (6):** `users` (semua akun 5 role) · `extras_profiles` (profil Extras) · `casting_projects` (proyek) · `casting_project_classes` (peran per proyek) · `event_shooting_dates` (tanggal shooting) · `project_applications` (pendaftaran = pusat alur, menyimpan status)

**Turunan pendaftaran (8):** `fee_negotiations` (ronde nego) · `client_reviews` (keputusan Client) · `contracts` (kontrak + TTD) · `payments` (honor Extras) · `payment_addons` (komponen tambahan honor) · `cancellations` (riwayat batal) · `attendances` (absensi) · `field_notes` (catatan/sanksi Korlap)

**Proyek & staf (5):** `invoices` (tagihan ke Client) · `project_expenses` (biaya lain-lain) · `project_attachments` (lampiran) · `admin_project_assignments` (penugasan Admin/Korlap) · `staff_payrolls` (honor staf per penugasan)

**Tag (3):** `extras_categories` (daftar tag) · `extras_category_extras_profile` (tag milik Extras) · `casting_project_class_extras_category` (tag yang dicari peran)

**Audit (1):** `activity_logs`

**Bawaan Laravel (8):** `notifications`, `sessions`, `password_reset_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`

### Hubungan antartabel

```
users ──1:1── extras_profiles ──N:N── extras_categories ──N:N── casting_project_classes
  │                 │
  │ (admin_id PIC,   │ 1:N
  │  client_id)      ▼
  └──1:N── casting_projects ──1:N── project_applications ──┬─1:N── fee_negotiations
              │                       (status alur)       ├─1:N── client_reviews
              ├─1:N── casting_project_classes             ├─1:N── cancellations
              ├─1:N── event_shooting_dates ──1:N──┐       ├─1:N── field_notes
              ├─1:N── invoices                    └──────>├─1:N── attendances
              ├─1:N── project_expenses                    ├─1:1── contracts
              ├─1:N── project_attachments                 └─1:1── payments ──1:N── payment_addons
              └─1:N── admin_project_assignments ──1:1── staff_payrolls ──1:N── payment_addons
```

Cara bacanya: **satu proyek punya banyak pendaftaran; satu pendaftaran = satu Extras di satu peran**, dan semua kejadian setelahnya (nego, keputusan Client, kontrak, honor, absensi, batal) menempel ke pendaftaran itu. `payment_addons` dipakai bersama oleh honor Extras dan honor staf (polimorfik).
