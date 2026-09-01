# SPEC-PARALEL-ANTIGRAFITY.md — Track Paralel (Bukan `SPEC.md` Utama)

> Diisi 1 September 2026. **File ini TERPISAH dari `docs/SPEC.md`** — Fakrul jalanin 2 CLI AI bersamaan (Claude Code kerjain `SPEC.md` = RF-49 rekap honor, "antigrafity" kerjain file ini). Kedua track SENGAJA nggak overlap file — dicek manual dulu sebelum ditulis. Kalau kamu (asisten yang baca file ini) BUKAN yang dimaksud "antigrafity", atau kalau ternyata `SPEC.md` utama sedang aktif dikerjakan proses lain di repo yang sama, HENTIKAN dan konfirmasi ke Fakrul dulu sebelum commit — risiko konflik git kalau 2 proses commit bersamaan di working tree yang sama.

## Aturan Main

- Kerjakan Bagian A dulu sampai selesai+test hijau, baru Bagian B (independen satu sama lain, tapi urut biar nggak tabrakan commit).
- Ikuti konvensi project ini (`CLAUDE.md` root + `docs/CLAUDE.md`) — terutama: cek `#[Fillable]` existing sebelum nambah kolom baru ke model manapun (pola bug paling sering kejadian di project ini), dan §14.1 (lapor dulu, jangan fix diam-diam, untuk bug di modul auth/RBAC/pembayaran/kontrak di luar scope task ini).
- Update `DEV-NOTES.md` seperti biasa per bagian selesai — tandai jelas di entrinya "(dikerjakan paralel, track antigrafity)" biar riwayatnya jelas kebaca nanti kalau ada 2 sesi jalan bersamaan.
- Commit terpisah per bagian (jangan digabung), supaya gampang di-rebase/reconcile kalau ternyata ada commit dari track `SPEC.md` (Claude Code) yang perlu digabung duluan.

---

## Bagian A — Ponytail: Eloquent Factory buat ExtrasProfile & CastingProject

**Kenapa:** 10+ file test reimplement `buatAplikasi()`/`buatProyek()` manual (helper method privat per file) karena `ExtrasProfile`/`CastingProject` belum punya Eloquent factory — cuma `User` yang punya. Ini murni kerapian test, ZERO risiko ke production code.

**Batasan:** JANGAN ubah assertion/logic test yang sudah ada — cuma ganti cara BIKIN data test-nya (dari manual `Model::create([...])` ke `Model::factory()->create([...])`), hasil datanya harus setara persis.

1. Buat `database/factories/ExtrasProfileFactory.php` — default value masuk akal buat semua kolom (`alias` fake name, `usia` random 20-45, `rate_card` random nominal, dst), relasi `user_id` default bikin `User::factory()->state(['role' => 'extras'])`.
2. Buat `database/factories/CastingProjectFactory.php` — default `nama_produksi`/`client_ph` fake, `deadline` beberapa hari ke depan, `status` default `dibuka`, `kuota` random.
3. Cek dulu (grep) SEMUA file test yang punya helper `buatAplikasi()`/`buatProyek()`/pola manual serupa — ganti PALING TIDAK di 2-3 file dulu sebagai bukti pola jalan (jangan wajib ganti semua 10 file kalau ternyata makan waktu lama, ini nice-to-have bukan fitur — utamakan yang paling sering dipakai/paling banyak duplikasi).
4. **Testing:** full regression `php artisan test` tetap hijau — factory harus menghasilkan data yang lolos semua assertion test yang sudah ada, bukan cuma "tidak error saat dibuat".

---

## Bagian B — Fitur Baru: Badge "Apresiasi" Extras (Admin-Only)

**Keputusan Fakrul:** toggle + catatan singkat, visibilitas ADMIN ONLY (CD dan Extras sendiri TIDAK melihat badge/catatan ini sama sekali). Ini FITUR BARU di luar RF manapun di `BAB-3-DRAFT.md` — dokumentasikan sebagai penambahan pasca-proposal (pola sama seperti RF-53/absensi Korlap sebelumnya), bukan seolah-olah sudah direncanakan sejak awal.

**Batasan:**
- JANGAN tampilkan badge/catatan ini di halaman manapun yang diakses CD (`cd/reviews/*`) atau Extras (`extras/*`) — ini benar-benar internal Admin, cek ulang semua view yang menampilkan data `ExtrasProfile` sebelum selesai, pastikan field baru ini nggak nyasar ke view CD/Extras manapun (termasuk kalau ada query `select()` yang eager-load seluruh kolom `ExtrasProfile` ke view non-admin — perlu di-scope kalau ternyata begitu).
- JANGAN kaitkan otomatis ke logic lain (grade, fee, dsb) — murni internal note, sama prinsipnya kayak absensi Korlap kemarin (informasional, efek otomatis butuh keputusan terpisah).

1. **Migration:** `extras_profiles` — tambah `apresiasi` (boolean, default `false`), `apresiasi_catatan` (text, nullable).
2. **Model:** `ExtrasProfile` — tambah `apresiasi`, `apresiasi_catatan` ke `#[Fillable]` (cek dulu isi existing sebelum nambah, pola bug lama di project ini selalu soal fillable yang keburu-buru ditambah tanpa dicek konteksnya).
3. **Controller:** action baru di `Admin\ApplicantController` (controller yang sudah menangani aksi per-aplikasi lain kayak `tambahCatatan()`/reject dini — cek pola yang sudah ada, reuse struktur yang sama) — method toggle apresiasi (terima `apresiasi_catatan` opsional saat toggle jadi `true`, kosongkan catatan otomatis kalau toggle balik ke `false`).
4. **Route:** `POST /admin/applications/{application}/apresiasi` (atau di level `ExtrasProfile` langsung kalau lebih pas secara struktur — cek dulu apakah aksi serupa lain di-scope per-aplikasi atau per-profil, ikuti pola yang konsisten), middleware `role:admin_default` (sub-role admin TIDAK termasuk — ini keputusan Admin Default, bukan operasional lapangan kayak Korlap).
5. **View:** `admin/projects/applicants.blade.php` — tombol toggle bintang/badge kecil (ikon Tabler yang sudah dipakai project ini, cek `UI-GUIDELINES.md` soal library icon) + dialog kecil buat isi catatan singkat kalau toggle ON (reuse pola `<dialog>` yang sudah ada, JANGAN bikin modal library baru).
6. **Testing:** toggle ON dengan catatan tersimpan benar. Toggle OFF mengosongkan catatan. Role selain `admin_default` (termasuk sub-role Admin) dapat 403 di route ini. Field `apresiasi`/`apresiasi_catatan` TIDAK muncul di response/view manapun yang diakses CD atau Extras (test eksplisit: hit halaman CD review & halaman Extras manapun yang menampilkan data profil, assert tidak ada string catatan apresiasi di response content).

---

## Catatan

Setelah kedua bagian selesai, update `BAB-3-DRAFT.md` — tambahkan Bagian B sebagai RF baru (lanjutan penomoran dari RF-53/absensi, jadi RF-54) ditandai "ditambahkan pasca-proposal, 1 September 2026". Bagian A (ponytail factory) tidak perlu masuk dokumen RF (bukan requirement fungsional, murni internal test tooling).

Setelah file ini selesai dikerjakan semua, HAPUS file ini (`SPEC-PARALEL-ANTIGRAFITY.md`) atau kosongkan isinya — jangan biarkan menumpuk sebagai file SPEC basi di `docs/`, beda dari `SPEC.md` utama yang memang selalu ada (dikosongkan bukan dihapus).
