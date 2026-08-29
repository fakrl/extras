# SPEC.md — Tutup Gap RF-10 (Edit Proyek) + RF-30 Akurasi Margin Per-Kepala

> Diisi 30 Agustus 2026. Ini hasil audit kepatuhan scope menyeluruh (Sprint 1 s/d Session 15, RF-01–RF-52 dicek satu-satu terhadap `PRD-LITE.md`/`BAB-3-DRAFT.md`) — **tidak ditemukan scope creep** di kode yang sudah dibangun, semua balik ke RF resmi atau checklist yang memang direncanakan sejak awal. Dua ide di luar RF (badge "Apresiasi", inbox re-book CD) sengaja BELUM dibangun — benar, jangan dikerjakan di sini juga.
>
> Ditemukan 1 gap nyata dari rencana awal (bukan fitur baru): **RF-10** cuma separuh jalan — `toggleStatus()` (tutup proyek) sudah ada, tapi **edit proyek tidak ada sama sekali** (dicek langsung ke `Admin\CastingProjectController`, tidak ada method `edit()`/`update()`). RF-30 (rekap margin) juga dikonfirmasi Fakrul butuh presisi per-kepala, bukan aproksimasi level-proyek seperti sekarang (`MarginRecapController` Session 12).
>
> **Keputusan Fakrul: ini kemungkinan besar batch fitur BARU terakhir sebelum masuk fase revisi/polish murni** — kerjakan dengan hati-hati (bukan speed-batch seperti Session 12/15), karena Bagian B mengubah alur inti (`apply()`) yang sudah dipakai di banyak test existing.

## Batasan (jangan keluar dari ini)

- JANGAN kerjakan badge "Apresiasi" atau inbox re-book CD — itu ide belum formal, bukan RF, butuh diskusi desain terpisah dengan Fakrul dulu.
- JANGAN sentuh WA gateway, security hardening, atau modul lain yang sudah selesai — scope ini murni RF-10 + RF-30.
- Kalau nemu bug di luar scope (auth/RBAC/pembayaran/kontrak yang bukan bagian task ini) — lapor dulu sesuai `CLAUDE.md` §14.1.
- Kerjakan Bagian A dulu sampai selesai+test hijau, baru Bagian B (B mengubah `project_applications`, lebih berisiko regresi ke banyak test existing — jangan digabung sekaligus).

---

## Bagian A — RF-10: Edit Proyek Casting

**Goal:** Admin Default bisa mengedit proyek casting yang sudah dibuat (nama produksi, client PH, deadline, kuota, is_urgent, tanggal shooting, kelas+budget+kuota per kelas), bukan cuma bisa tutup/buka.

**Batasan:**
- Kalau proyek sudah punya pendaftar (`applications()->exists()`), JANGAN blokir edit total, tapi field yang mengubah dasar kesepakatan yang sudah berjalan (`budget_client`/`kuota_kelas` per kelas yang sudah ada aplikasinya) butuh peringatan eksplisit di UI ("X kelas ini sudah ada Y pendaftar, ubah budget tidak mengubah fee yang sudah di-nego") — bukan diblokir, sekadar warning, konsisten dengan pola RF-13 (soft-warning bukan blocking).
- JANGAN hapus kelas yang sudah punya pendaftar (`casting_project_classes` yang direferensikan `project_applications` — lihat Bagian B) — kalau admin coba hapus kelas berpendaftar, tolak dengan pesan jelas. Kelas tanpa pendaftar boleh dihapus/tambah bebas.
- Tanggal shooting: boleh tambah/hapus bebas untuk sekarang (tidak ada aplikasi yang langsung terikat ke tanggal spesifik, cuma dipakai buat deteksi bentrok agregat) — tidak perlu proteksi tambahan.

1. **Migration:** tidak ada (semua kolom sudah ada dari `create_casting_projects_table`/`create_casting_project_classes_table`/`create_event_shooting_dates_table`).
2. **Controller:** `Admin\CastingProjectController` — tambah `edit(CastingProject $castingProject)` (form, load relasi `classes`+`shootingDates`) dan `update(Request $request, CastingProject $castingProject)` (validasi sama seperti `store()`, replace `shootingDates`+`classes` — hapus yang lama lalu buat ulang untuk yang TIDAK berpendaftar, update in-place untuk kelas yang SUDAH berpendaftar bukan delete-recreate supaya `casting_project_class_id` di `project_applications` tetap valid setelah Bagian B jalan).
3. **Routes:** `GET /admin/projects/{castingProject}/edit` dan `PATCH /admin/projects/{castingProject}`, di grup middleware yang sama dengan route proyek lain.
4. **View:** `admin/projects/edit.blade.php` — reuse struktur form dari `create.blade.php` (field sama persis, cuma pre-filled + kelas/tanggal existing bisa dihapus/ditambah dinamis kalau JS-nya sudah ada di create; kalau belum ada JS dynamic-add, cukup render list existing + tombol tambah baris baru dengan pola JS yang sama seperti `create.blade.php`). Tombol "Edit" di `admin/projects/index.blade.php`.
5. **Testing:** edit berhasil update semua field; kelas berpendaftar tidak bisa dihapus (assert tetap ada di DB / pesan error); kelas tanpa pendaftar bebas dihapus; regresi `store()`/`toggleStatus()` tetap jalan.

---

## Bagian B — RF-30: Margin Per-Kepala (Akurat)

**Goal:** Ganti perhitungan margin dari aproksimasi level-proyek (Session 12) jadi eksak per-kepala: tiap `ProjectApplication` tahu dia daftar untuk kelas yang mana, jadi margin = SUM(`budget_client` kelas milik aplikasi tsb − `fee_final` aplikasi tsb) per aplikasi yang lolos+, bukan lagi SUM(`budget_client` × `kuota_kelas`) di level kelas.

**Batasan — WAJIB baca sebelum mulai:**
- Ini mengubah `project_applications`, tabel yang di-`create()` LANGSUNG oleh minimal 9 file test (`ProjectApplicationTest`, `EmailNotificationTest`, `WhatsAppNotificationTest`, `LengkapiKtpTest`, `PaymentAddonTest`, `ApplicantGradeFilterTest`, `MarginRecapTest`, `FieldNoteTest`, `WaGroupLinkTest` — grep dulu semua `ProjectApplication::create(`/`->applications()->create(` di `tests/` sebelum mulai, jangan asumsi cuma yang disebut di sini). **Kolom baru HARUS nullable** di level DB — supaya factory/helper test lama yang belum di-update tidak langsung patah. Jangan bikin ini kolom wajib (`NOT NULL`) di migration.
- Margin untuk aplikasi dengan `casting_project_class_id` NULL (data lama/test yang belum di-update) di-exclude dari breakdown per-kelas TAPI tetap dihitung transparan — tampilkan baris terpisah "Belum terklasifikasi" di halaman rekap kalau ada, JANGAN silently drop dari total (supaya angka rekap tidak diam-diam salah kurang).
- JANGAN ubah alur nego fee (`ajukanFeeAwal` dkk) untuk auto-derive nominal dari `budget_client` kelas — itu tetap manual sesuai desain existing, di luar scope task ini.

1. **Migration:** `project_applications` — tambah `casting_project_class_id` (foreignId, nullable, `constrained('casting_project_classes')->nullOnDelete()`).
2. **Model:**
   - `ProjectApplication` — tambah `casting_project_class_id` ke `#[Fillable]` (cek dulu list existing sebelum nambah, pola bug lama selalu di sini), relasi `castingProjectClass(): BelongsTo`.
   - `CastingProjectClass` — cek apakah sudah ada relasi `applications()`/`hasMany(ProjectApplication::class)`, tambah kalau belum ada (dipakai proteksi hapus-kelas-berpendaftar di Bagian A).
3. **Controller:**
   - `Extras\CastingProjectController::apply()` — terima input `casting_project_class_id` (required, validasi milik `$castingProject` yang sama — jangan percaya ID dari request begitu saja, cek `$castingProject->classes()->findOrFail($id)` dulu). Set ke `project_applications` saat create.
   - `Admin\MarginRecapController::index()` — ganti kalkulasi: per `CastingProject`, group `applications` yang statusnya `lolos`+ berdasarkan `castingProjectClass`, `total_fee_client` per aplikasi = `budget_client` kelasnya (bukan lagi × kuota kelas), `total_payout` tetap `fee_final`, margin = selisih per aplikasi lalu di-sum. Aplikasi dengan `casting_project_class_id` null masuk baris terpisah "Belum terklasifikasi" (lihat Batasan).
4. **View:**
   - `extras/lowongan/show.blade.php` (halaman detail lowongan sebelum apply) — tambah pilihan kelas (radio/select, tampilkan `nama_kelas` + `kriteria` biar Extras tahu bedanya) di form apply.
   - `admin/recap/margin.blade.php` — breakdown per kelas kalau proyeknya punya lebih dari 1 kelas (nested/sub-row), plus baris "Belum terklasifikasi" kalau ada data lama nullable.
5. **Testing:**
   - Migration nullable jalan tanpa error di data existing.
   - `apply()` dengan `casting_project_class_id` milik proyek lain (bukan proyek yang di-apply) → ditolak (403/422, cek ownership).
   - `MarginRecapTest` — update test kalkulasi existing (angka `400rb×3 − 250rb×3 = 450rb` dari Session 12 kemungkinan perlu direvisi jadi skenario per-kelas: mis. 2 kelas beda budget di 1 proyek, assert total margin = sum masing-masing, BUKAN lagi dikali kuota kelas).
   - Aplikasi dengan class null tetap muncul di rekap (baris "Belum terklasifikasi"), tidak hilang dari total.
   - Full regression: SEMUA test lama yang bikin `ProjectApplication` langsung (9 file di atas) — pastikan masih PASS tanpa perlu diubah satu-satu (karena kolom nullable). Kalau ternyata ada yang patah, itu tandanya ada asumsi NOT NULL tersembunyi — laporkan, jangan asal ubah test lama supaya lolos.

---

## Catatan

Setelah Bagian A+B selesai dan test hijau, update `DEV-NOTES.md` (entri session baru) dan `BAB-3-DRAFT.md` kalau ada detail implementasi yang perlu diketahui pembaca proposal (biasanya tidak perlu, RF-10/RF-30 sudah sesuai teks aslinya, cuma implementasinya yang nyusul).

**Setelah ini, SPEC.md berikutnya kemungkinan besar murni task revisi/polish** (bug kecil, UX, dokumentasi) — bukan fitur RF baru, sesuai keputusan Fakrul di awal sesi ini. Ingatkan Fakrul: masih ada 96+ file uncommitted dari Session 8-15 yang belum pernah di-`git commit` — sebaiknya commit checkpoint SEBELUM mulai batch ini, supaya kalau ada masalah di tengah jalan (terutama Bagian B yang menyentuh banyak test), gampang rollback ke titik sebelum Bagian A/B tanpa kehilangan Session 8-15.
