# DATABASE-SCHEMA.md — SIM Casting JBTB

> Skema rencana, diturunkan dari `BAB-3-DRAFT.md` (RF-01 s/d RF-52). Belum ada migration nyata per 22 Agu 2026 — ini blueprint buat mulai Sprint 1. Update file ini begitu skema berubah pas implementasi, jangan biarkan drift dari migration asli.

## Ringkasan Tabel (memenuhi syarat minimal 5 tabel non-user dari Panduan Laporan — jauh terlampaui)

| Tabel | Fungsi | Terkait RF |
|---|---|---|
| `users` | Akun dasar semua role (auth) | RF-01–05 |
| `admin_profiles` | Detail Admin (Super Admin/Default/Talco/Korlap/Sosmed) + sub-role + nominal honor | RF-40, RF-41 |
| `extras_profiles` | Data diri Extras: rate card, foto, video, status | RF-06–08 |
| `casting_projects` | Proyek casting (lowongan) | RF-09–11 |
| `event_shooting_dates` | Tanggal shooting per proyek (bisa jamak, nggak harus berurutan) | RF-09, RF-13, RF-22 |
| `project_applications` | Pendaftaran Extras ke proyek + status berjenjang | RF-12–15, RF-24 |
| `fee_negotiations` | Riwayat tawar-menawar fee per ronde | RF-16–20 |
| `cd_reviews` | Approval/reject CD per kandidat | RF-21–23 |
| `contracts` | Kontrak digital (Talent Release) + canvas signature | RF-25–27 |
| `payments` | Status pembayaran Extras + bukti transfer | RF-28–29 |
| `payment_addons` | Add-on/reimburse (transport, penginapan) — dipakai payment Extras & honor staf | RF-32, RF-47 |
| `invoices` | Invoice ke client + TTD Admin+CD | RF-31 |
| `admin_project_assignments` | Penugasan Admin/sub-role ke proyek + log aktivitas | RF-42, RF-43, RF-45 |
| `staff_payrolls` | Slip honor per Admin per proyek Selesai | RF-46, RF-48, RF-49 |
| `cancellations` | Riwayat pembatalan (Extras/Admin) | RF-33–34 |
| `field_notes` | Catatan/sanksi Korlap terhadap Extras | RF-35 |
| `notifications_log` | Log notifikasi email & WA terkirim | RF-36–38 |

## Definisi Tabel

### `users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| password | string (hashed) | RNF-03 |
| role | enum | `super_admin`, `admin_default`, `admin_talco`, `admin_korlap`, `admin_sosmed`, `casting_director`, `extras` |
| status | enum | `aktif`, `nonaktif` — dipakai buat Extras & CD (RF-05); Admin dikelola Super Admin |
| created_at, updated_at | timestamp | |

### `admin_profiles`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | role harus salah satu dari 5 tipe admin |
| honor_nominal | decimal nullable | RF-41, diset Super Admin saat rekrut, null untuk Super Admin/Admin Default kalau nggak relevan |
| honor_updated_at | timestamp nullable | jejak kapan nominal terakhir diubah |
| created_by | FK → users (Super Admin yang nambahin) | RF-40 |

> Catatan desain: Talco/Korlap/Sosmed **BUKAN** tabel terpisah — dibedakan lewat `users.role`. `admin_profiles` cuma nyimpen atribut yang spesifik ke admin (honor), bukan duplikasi identitas.

### `extras_profiles`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | |
| nik | string encrypted, unique | RF-04, RNF-01 — validasi duplikasi di level DB (unique index) + aplikasi |
| nama_asli | string encrypted | tembok visibilitas — nggak pernah dikirim ke CD |
| alias | string | nama panggung, yang ditampilkan ke CD |
| usia, gender, tinggi_badan, ukuran_baju, warna_kulit | berbagai | kriteria fisik RF-06 |
| pengalaman, bahasa | text | |
| rate_card | decimal | tarif harapan awal, dasar `fee_negotiations` |
| foto_profil_path, video_profil_path | string (private disk) | RF-06, RNF-01 |
| rekening | string encrypted | diisi Extras, dilihat admin only |
| status | enum | `aktif`, `tidak_aktif`, `melanggar` — RF-07 |
| cancel_count | integer default 0 | RF-08, increment tiap pembatalan mendadak, auto-flag `melanggar` di 3x |

### `casting_projects`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| admin_id | FK → users (Admin Default pembuat) | RF-09 |
| nama_produksi | string | |
| client_ph | string | nama Production House (entitas, tanpa akun) |
| deadline | date | |
| kuota | integer | |
| is_urgent | boolean default false | RF-09 flag "Butuh Dadakan" |
| status | enum | `dibuka`, `ditutup` — RF-10 |
| created_at, updated_at | timestamp | |

### `casting_project_classes`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| casting_project_id | FK | |
| nama_kelas | string | kriteria per kelas (misal "ibu-ibu 29-50th") |
| kriteria | text/JSON | |
| budget_client | decimal | dasar penawaran fee awal, RF-16 |
| kuota_kelas | integer | |

### `event_shooting_dates`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| casting_project_id | FK | |
| tanggal | date | satu row per tanggal shooting — mendukung tanggal jamak nggak berurutan |

> Ini kunci buat deteksi bentrok jadwal (RF-13, RF-22) — query overlap antar `event_shooting_dates` milik proyek berbeda yang extras-nya sama dan statusnya aktif (Deal/Lolos).

### `project_applications`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| casting_project_id | FK | |
| extras_id | FK → extras_profiles | |
| status_partisipasi | enum | `diajukan`, `direview_admin`, `nego_fee`, `deal`, `diajukan_ke_cd`, `direview_cd`, `lolos`, `ditolak`, `kontrak_ditandatangani`, `selesai_produksi`, `dibatalkan` — RF-24 (dua jalur status terpisah, lihat `CLAUDE.md` §6) |
| grade | enum nullable | `A`, `B`, `C` — RF-15, independen dari fee |
| fee_final | decimal nullable | diisi begitu status `deal` |
| bentrok_jadwal_flag | boolean default false | ditandai sistem saat apply/present, RF-13/RF-22, non-blocking |
| created_at, updated_at | timestamp | |

### `fee_negotiations`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| project_application_id | FK | |
| round | integer | urutan ronde |
| diajukan_oleh | enum | `admin`, `extras` |
| nominal | decimal | |
| aksi | enum | `tawar`, `counter`, `terima`, `tolak` |
| created_at | timestamp | RF-19, jejak lengkap tiap ronde |

### `cd_reviews`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| project_application_id | FK | |
| cd_id | FK → users | |
| keputusan | enum | `approve`, `reject` |
| bulk_batch_id | uuid nullable | dipakai kalau approve/reject massal (RF-23) biar bisa dilacak sebagai satu aksi |
| created_at | timestamp | |

### `contracts`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| project_application_id | FK | |
| pdf_path | string (private disk) | RF-25 |
| ttd_admin_signature_path | string nullable | canvas signature image, RF-26 |
| ttd_extras_signature_path | string nullable | |
| signed_at | timestamp nullable | |

### `payments`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| project_application_id | FK | |
| status | enum | `belum_dibayar`, `ditransfer`, `dikonfirmasi_diterima` |
| bukti_transfer_path | string nullable (private disk) | RF-28 |
| ditransfer_at, dikonfirmasi_at | timestamp nullable | |

### `payment_addons`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| addable_type, addable_id | polymorphic | bisa nempel ke `payments` (Extras) ATAU `staff_payrolls` (staf) — RF-32, RF-47 |
| label | string | bebas, misal "Reimburse transport" |
| nominal | decimal | |
| created_by | FK → users | |

### `invoices`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| casting_project_id | FK | |
| pdf_path | string (private disk) | RF-31 |
| ttd_admin_signature_path, ttd_cd_signature_path | string nullable | |

### `admin_project_assignments`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| casting_project_id | FK | |
| user_id | FK → users (admin/sub-role yang ditugaskan) | RF-42 |
| assigned_by | FK → users (Super Admin) | |
| status_log | enum | `berjalan`, `selesai` — nempel ke status proyek, RF-45 |
| completed_at | timestamp nullable | RF-46, dasar kelayakan honor |

### `staff_payrolls`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| admin_project_assignment_id | FK | satu payroll per penugasan proyek |
| nominal_pokok | decimal | disalin dari `admin_profiles.honor_nominal` saat proyek selesai |
| pdf_slip_path | string nullable (private disk) | RF-48, auto-generate saat status jadi `selesai` |
| generated_at | timestamp nullable | |

### `cancellations`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| project_application_id | FK | |
| dibatalkan_oleh | enum | `admin`, `extras` |
| alasan | text | RF-33 |
| is_mendadak | boolean | H-2 rule, RF-34 — increment `extras_profiles.cancel_count` |
| created_at | timestamp | |

### `field_notes`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| project_application_id | FK | |
| korlap_id | FK → users | RF-35 |
| jenis | enum | `catatan`, `sanksi` |
| isi | text | |
| created_at | timestamp | |

### `notifications_log`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | FK | penerima |
| channel | enum | `email`, `whatsapp` |
| jenis | string | misal `hasil_seleksi`, `reminder_h1`, `kontrak_siap_ttd` |
| status | enum | `terkirim`, `gagal` |
| sent_at | timestamp | RF-36–37 |

## Relasi Kunci (ringkas)

```
users 1—1 extras_profiles (role=extras)
users 1—1 admin_profiles (role=admin_*)
users 1—N casting_projects (sebagai pembuat, Admin Default)
casting_projects 1—N event_shooting_dates
casting_projects 1—N project_applications
project_applications 1—N fee_negotiations
project_applications 1—1 contracts
project_applications 1—1 payments
payments 1—N payment_addons (polymorphic)
casting_projects 1—N admin_project_assignments
admin_project_assignments 1—1 staff_payrolls
staff_payrolls 1—N payment_addons (polymorphic)
```

## Data Dummy Minimum (syarat Panduan Laporan: min 50 entri)

Rencana seeder: 60-80 `extras_profiles` (sesuai skala real RNF-04), 5-8 `casting_projects` dengan `event_shooting_dates` bervariasi (termasuk yang overlap buat testing deteksi bentrok), lengkap dengan `project_applications` di berbagai status biar semua state machine ter-cover pas demo/sidang.

## Catatan Desain Penting

- **Kenapa `payment_addons` polymorphic**, bukan kolom langsung di `payments`/`staff_payrolls`: add-on sifatnya optional & jumlahnya nggak tetap (RF-32/RF-47 bilang "manual sesuai kebutuhan") — satu tabel shared lebih rapi daripada duplikasi struktur di dua tempat.
- **Kenapa `event_shooting_dates` tabel terpisah**, bukan kolom `start_date`/`end_date`: shooting bisa tanggal-tanggal terpisah nggak berurutan (temuan data riil `Kado Untuk Ibu` = 9-13 hari, kemungkinan nggak berurutan) — dan ini yang bikin query deteksi bentrok jadwal (RF-13) jadi simple overlap-check, bukan constraint solver rumit.
- **Kenapa dua status terpisah** (`status_partisipasi` di `project_applications` vs `status` di `payments`): partisipasi dan pembayaran adalah dua lifecycle independen — Extras bisa `selesai_produksi` tapi pembayaran masih `belum_dibayar`. Digabung jadi satu state machine bakal bikin kombinasi state yang membingungkan (lihat `CLAUDE.md` §6).
