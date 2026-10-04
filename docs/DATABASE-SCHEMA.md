# DATABASE-SCHEMA.md — SIM Casting JBTB

> Skema FINAL per 1 Okt 2026, diturunkan dari migrasi aktual di `database/migrations/` (bukan lagi blueprint). Setelah BM.1–BM.3 jumlah tabel turun dari 36 ke **31** (23 bisnis + 8 framework, belum termasuk tabel internal `migrations`). Kalau skema berubah, update file ini di commit yang sama dengan migrasinya.
>
> Catatan: `users.role` bertipe ENUM hanya di MySQL; di SQLite (test) jadi string tanpa constraint.

## Ringkasan Tabel Bisnis (23)

| # | Tabel | Fungsi |
|---|---|---|
| 1 | `users` | Akun semua role (5 role) + honor staf + data perusahaan Client |
| 2 | `extras_profiles` | Profil Extras (1-1 dengan users) |
| 3 | `casting_projects` | Proyek casting |
| 4 | `casting_project_classes` | Peran/kelas per proyek (kriteria, budget, kuota) |
| 5 | `event_shooting_dates` | Tanggal shooting per proyek (jamak, tidak harus berurutan) |
| 6 | `project_applications` | Pendaftaran Extras ke peran + status partisipasi |
| 7 | `fee_negotiations` | Riwayat ronde tawar-menawar fee |
| 8 | `client_reviews` | Keputusan Client (lock/tolak) + grade dari Client |
| 9 | `contracts` | Kontrak digital + TTD canvas (1-1 dengan aplikasi) |
| 10 | `payments` | Pembayaran honor Extras (1-1 dengan aplikasi) |
| 11 | `payment_addons` | Add-on nominal (polimorfik: payment Extras / honor staf) |
| 12 | `invoices` | Invoice penagihan ke Client per proyek |
| 13 | `admin_project_assignments` | Penugasan Admin/Korlap ke proyek oleh Super Admin |
| 14 | `staff_payrolls` | Honor staf per penugasan (1-1) |
| 15 | `cancellations` | Riwayat pembatalan keikutsertaan |
| 16 | `field_notes` | Catatan/sanksi Korlap terhadap Extras |
| 17 | `attendances` | Absensi per aplikasi per tanggal shooting |
| 18 | `extras_categories` | Tag Extras (bebas ditulis, dikelompokkan lewat `grup`) |
| 19 | `extras_category_extras_profile` | Pivot Extras ↔ tag |
| 20 | `casting_project_class_extras_category` | Pivot peran proyek ↔ tag |
| 21 | `activity_logs` | Audit trail aksi pengguna |
| 22 | `project_expenses` | Biaya lain-lain proyek (input cashflow) |
| 23 | `project_attachments` | Lampiran file proyek (private disk) |

## Tabel Framework (8)

`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `notifications` (notifikasi database Laravel; status kirim WA/email ikut tersimpan di kolom `data`).

## Definisi Tabel

Semua tabel punya `created_at`/`updated_at` kecuali dinyatakan lain. `cascade` = cascadeOnDelete, `nullOnDelete` = set null, `restrict` = default.

### 1. `users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| role | enum | `super_admin`, `admin`, `korlap`, `client`, `extras` |
| status | enum | `aktif`, `nonaktif` (default `aktif`) |
| is_protected | bool | akun sistem terproteksi, tidak bisa dihapus/dinonaktifkan |
| name | string | |
| username | string(50) null, unique | |
| email | string null, unique | opsional (akun Client boleh tanpa email) |
| google_id | string null, unique | login Google |
| password | string null | hashed; null untuk akun yang daftar via Google |
| wajib_ganti_password | bool | dipaksa ganti saat login pertama (akun dibuat SA, reset password) |
| nomor_wa | string null | dinormalisasi ke `62…` sebelum kirim WA |
| nama_perusahaan | string null | Client: nama PH/perusahaan (menggantikan `client_ph`) |
| honor_nominal | decimal(12,2) null | honor per-event staf (menggantikan `admin_profiles`) |
| email_verified_at, remember_token | | bawaan Laravel |
| deleted_at | timestamp null | soft delete |

### 2. `extras_profiles`
| Kolom | Tipe | Keterangan |
|---|---|---|
| user_id | FK → users, unique, cascade | 1-1 |
| nik | string null, unique | **encrypted** |
| nik_hash | string(64) null, unique | blind index untuk cek duplikasi NIK |
| nama_asli | text null | **encrypted**, tidak ditampilkan ke Client |
| usia, tinggi_badan | int null | |
| berat_badan | smallint null | |
| gender, ukuran_baju, bahasa | string null | |
| riwayat_pengalaman | json null | daftar pengalaman (menggantikan `pengalaman` teks) |
| tautan_tambahan | json null | tautan portofolio/sosmed |
| rate_card | decimal(12,2) null | tarif harapan |
| foto_profil_path, video_profil_path | string null | private disk |
| foto_tambahan | json null | slot 1–4 → path (menggantikan `extras_photos`) |
| rekening | text null | **encrypted** |
| status | enum | `aktif`, `tidak_aktif`, `melanggar` |
| apresiasi, apresiasi_catatan | bool, text | dipakai sebagai **Favorit ⭐** Admin, tak pernah tampil ke Client/Extras |
| grade_saat_ini, grade_diberikan_at | enum A/B/C null, timestamp | |
| share_token | string(32) null, unique | profil publik `/p/extras/{token}` |
| izin_tampil_publik, tampil_di_beranda, tampil_di_beranda_at | bool, bool, timestamp | kurasi landing page (butuh izin Extras + persetujuan Admin) |

> Ciri warna kulit kini berupa tag (`extras_categories`), bukan kolom. `cancel_count` dihapus; hitungan batal mendadak diturunkan dari `cancellations`.

### 3. `casting_projects`
| Kolom | Tipe | Keterangan |
|---|---|---|
| admin_id | FK → users null, restrict | Admin PIC |
| client_id | FK → users null, nullOnDelete | Client pemilik (menggantikan `cd_project_assignments`, `client_ph`) |
| nama_produksi | string | kode proyek `JBTB-{tahun}-{id 3 digit}` dihitung (accessor), bukan kolom |
| deadline | date | |
| kuota | unsigned int | |
| is_urgent | bool | |
| status | enum | `dibuka`, `ditutup` |
| client_request_status | enum | `draft`, `menunggu_acc`, `disetujui`, `ditolak` (pengajuan brief Client, perlu ACC Super Admin) |
| brief_catatan, alasan_tolak | text null, string(500) null | |
| poster_path | string null | satu-satunya file di disk public |
| link_grup | string null | tautan grup WA proyek |
| share_token | string(32) null, unique | tautan pendaftaran publik `/event/{token}` |
| tampil_portofolio, portofolio_judul, portofolio_jenis, portofolio_tahun | | kurasi portofolio di landing |
| tampilkan_nama_client | bool | |

### 4. `casting_project_classes`
`casting_project_id` (FK, cascade), `nama_kelas`, `kriteria` (text), `budget_client` (decimal), `kuota_kelas`, `jam_callsheet`, `jam_callingan`, `keterangan_scene`, `tipe_continuity` (`continuity`/`free`). Kuota antrian dihitung per peran dari semua aplikasi selain `ditolak`/`dibatalkan`.

### 5. `event_shooting_dates`
`casting_project_id` (FK, cascade), `tanggal`, `lokasi`, `jam_mulai`, `jam_selesai`, `catatan`, `panggilan` (json). Index `(casting_project_id, tanggal)`. Satu baris per tanggal, dasar deteksi bentrok jadwal.

### 6. `project_applications`
| Kolom | Tipe | Keterangan |
|---|---|---|
| casting_project_id | FK, cascade | |
| extras_id | FK → extras_profiles, cascade | unique `(casting_project_id, extras_id)` |
| casting_project_class_id | FK null, nullOnDelete | peran yang dilamar |
| status_partisipasi | enum | `diajukan`, `direview_admin`, `nego_fee`, `deal`, `diajukan_ke_client`, `lolos`, `kontrak_ditandatangani`, `selesai_produksi`, `ditolak`, `dibatalkan` |
| grade | enum A/B/C null | |
| fee_final | decimal(12,2) null | terkunci saat `deal` |
| karakter_override, scene_override, jam_callingan_override, tipe_continuity_override | | breakdown per kandidat |
| bentrok_jadwal_flag | bool | penanda bentrok (peringatan, bukan pemblokiran, kecuali BK.4) |
| alasan_tolak | text null | |

### 7. `fee_negotiations`
`project_application_id` (FK, cascade), `round`, `diajukan_oleh` (`admin`/`extras`), `nominal`, `aksi` (`tawar`/`counter`/`terima`/`tolak`), `catatan`. Index `(project_application_id, round)`.

### 8. `client_reviews` (dulu `cd_reviews`)
`project_application_id` (FK, cascade), `client_id` (FK → users, restrict), `keputusan` (`approve`/`reject`), `grade_client` (A/B/C null).

### 9. `contracts`
`project_application_id` (FK, **unique**, cascade), `pdf_path`, `ttd_admin_signature_path`, `ttd_extras_signature_path`, `signed_at`, `voided_at` (kontrak dibatalkan bila aplikasi `dibatalkan`).

### 10. `payments`
`project_application_id` (FK, **unique**, cascade), `status` (`belum_dibayar`, `ditransfer`, `dikonfirmasi_diterima`, `disengketakan`), `bukti_transfer_path`, `ditransfer_at`, `dikonfirmasi_at`, `alasan_sengketa`. Nominal pokok = `fee_final` aplikasi; total = pokok + add-on.

### 11. `payment_addons`
`addable_type` + `addable_id` (polimorfik → `payments` atau `staff_payrolls`), `label`, `nominal`, `created_by` (FK → users).

### 12. `invoices`
`casting_project_id` (FK, cascade), `pdf_path`, `template_type`, `custom_doc_path`, `ttd_admin_signature_path`, `ttd_client_signature_path`, `nominal` (decimal(15,2)), `status_bayar` (`belum`/`lunas`), `dibayar_at`.

### 13. `admin_project_assignments`
`casting_project_id` (FK, cascade), `user_id` (FK → users), `assigned_by` (FK → users), `status_log` (`berjalan`/`selesai`), `completed_at`. Unique `(casting_project_id, user_id)`.

### 14. `staff_payrolls`
`admin_project_assignment_id` (FK, **unique**, cascade), `nominal_pokok`, `pdf_slip_path`, `generated_at`, `status_bayar` (`belum`/`sudah`), `dibayar_at`.

### 15. `cancellations`
`project_application_id` (FK, cascade), `dibatalkan_oleh` (`admin`/`extras`), `alasan`, `is_mendadak`. Sesuai keputusan D23, semua pembatalan dihitung batal mendadak; 3× → status Extras `melanggar`.

### 16. `field_notes`
`project_application_id` (FK, cascade), `korlap_id` (FK → users), `jenis` (`catatan`/`sanksi`), `isi`.

### 17. `attendances`
`project_application_id`, `event_shooting_date_id` (FK, cascade; unique pasangan), `status` (`hadir`/`tidak_hadir`), `dicatat_oleh`, `catatan`, `foto_path` (selfie, private), `status_validasi` (`menunggu`/`tervalidasi`/`ditolak`), `divalidasi_oleh`, `divalidasi_at`.

### 18–20. Tag
`extras_categories` (`nama` unique, `grup` null, `dibuat_oleh` FK null). Pivot `extras_category_extras_profile` dan `casting_project_class_extras_category` (PK komposit, tanpa timestamps). Dipakai untuk filter dan skor "Paling cocok" (`persenCocok()`).

### 21. `activity_logs`
`user_id` (null), `role`, `action`, `subject_type`/`subject_id` (nullable morph), `description`, `properties` (json), `ip_address`, `user_agent`, `created_at` saja (tanpa `updated_at`). Aksi saat mode lihat-sebagai dicatat dengan akhiran "(sebagai X)".

### 22. `project_expenses`
`casting_project_id` (FK, cascade), `label`, `nominal` (decimal(15,2)), `tanggal`, `created_by` (null).

### 23. `project_attachments`
`casting_project_id` (FK, cascade), `uploaded_by` (null), `nama_asli`, `path` (private disk), `mime`, `ukuran`, `keterangan`.

## Tabel yang Dihapus / Digabung (BM.1–BM.3, AY.7)

| Dulu | Sekarang |
|---|---|
| `cd_project_assignments` | `casting_projects.client_id` |
| `extras_photos` | `extras_profiles.foto_tambahan` (json) |
| `admin_profiles` | `users.honor_nominal` |
| `notifications_log` | status kirim di `notifications.data` |
| `cd_reviews` | di-rename `client_reviews` (`cd_id`→`client_id`, `grade_cd`→`grade_client`) |
| `invoices.ttd_cd_signature_path` | `ttd_client_signature_path` |
| `casting_projects.client_ph` | `users.nama_perusahaan` |
| `casting_projects.wa_group_link`, `cover_path` | `link_grup`, `poster_path` / lampiran |
| `extras_profiles.pengalaman`, `warna_kulit`, `cancel_count` | `riwayat_pengalaman`, tag, dihitung dari `cancellations` |
| role `admin_default/talco/sosmed`, `admin_korlap`, `casting_director` | `admin`, `korlap`, `client` |
| status `direview_cd`, `diajukan_ke_cd` | dihapus, `diajukan_ke_client` |

## Relasi Kunci

```
users 1—1 extras_profiles                    (role=extras)
users 1—N casting_projects                   (admin_id = PIC, client_id = pemilik)
casting_projects 1—N casting_project_classes, event_shooting_dates, project_applications,
                     invoices, project_expenses, project_attachments, admin_project_assignments
extras_profiles 1—N project_applications
project_applications 1—N fee_negotiations, client_reviews, cancellations, field_notes, attendances
project_applications 1—1 contracts
project_applications 1—1 payments
admin_project_assignments 1—1 staff_payrolls
payments / staff_payrolls 1—N payment_addons (polimorfik)
extras_profiles N—N extras_categories N—N casting_project_classes
```

## Catatan Desain

- **`payment_addons` polimorfik**: add-on sifatnya opsional dan jumlahnya tidak tetap; satu tabel dipakai honor Extras dan honor staf.
- **`event_shooting_dates` tabel terpisah**: tanggal bisa tidak berurutan; query bentrok jadwal jadi overlap-check sederhana.
- **Dua status terpisah** (`status_partisipasi` vs `payments.status`): partisipasi dan pembayaran adalah dua lifecycle independen.
- **Penggabungan tabel kecil (BM.1)**: tabel yang isinya 1–3 kolom dan selalu diakses bersama induknya digabung ke induk agar query dan relasi lebih sederhana. `notifications_log` hanya ditulis tapi tak pernah dibaca, jadi dihapus.
- **Enkripsi**: `nik`, `nama_asli`, `rekening` memakai cast `encrypted`; pencarian duplikasi NIK lewat `nik_hash`. Password memakai cast `hashed`.
- **Soft delete** hanya pada `users`.
- **Data demo**: `DemoLengkapSeeder` (sumber kebenaran: `docs/AKUN-DEMO.md`) mengisi 24 Extras dan 6 proyek di berbagai tahap, memenuhi syarat minimal 50 entri data uji.
