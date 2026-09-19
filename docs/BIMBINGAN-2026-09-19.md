# List Revisi Bimbingan — 19 September 2026

> **Fungsi file ini:** kumpulan mentah catatan revisi dari sesi bimbingan tanggal 19 Sept 2026, digabung dari 2 sumber (Fakrul selaku role SuperAdmin, Erlina — 2 batch pesan), dirapikan per tema biar gampang dilacak. **INI BELUM JADI TASK SPEC** — statusnya masih "dikumpulkan dulu" sesuai arahan Fakrul, nunggu kemungkinan tambahan dari role lain sebelum diubah jadi `SPEC.md`. Beberapa poin udah dicek manager-session langsung ke kode/database (ditandai), sisanya masih murni catatan mentah yang butuh diskusi lanjut.
>
> **Tag status per poin:** ✅ Sudah ada/terpenuhi (dicek langsung ke kode) — ⚠️ Gap terverifikasi (dicek ke kode, beneran belum ada/beda) — ❓ Perlu klarifikasi (ambigu, butuh nanya balik ke dospem/tim) — 🆕 Scope baru signifikan (bukan sekadar revisi kecil) — 📝 Kemungkinan cuma soal dokumen/istilah laporan, bukan perubahan aplikasi.

---

## A. Struktur menu, RBAC & penamaan

### A.1 Submenu pakai dropdown
[Sumber: Erlina batch 1 #1] — Selaras sama Bagian O/T yang udah ditulis manager-session sebelumnya (navbar homepage + topbar dashboard jadi dropdown). Kemungkinan ini nunjuk ke hal yang sama, atau ke submenu SIDEBAR (bukan topbar) yang belum disentuh. **Perlu klarifikasi:** submenu yang dimaksud yang mana — sidebar kiri (Kelola Admin > Kelola CD, dst jadi collapsible) atau yang lain?

### A.2 Nama "Kelola Casting Director" diganti, cari yang lebih efektif
[Sumber: Erlina batch 1 #2] — Terkait A.3 di bawah (Fakrul juga usul CD digabung ke Kelola Admin). Kalau digabung jadi satu ("Kelola Admin" yang isinya Admin+CD sebagai subadmin), pertanyaan soal nama menu terpisah ini mungkin jadi gak relevan lagi — atau tetap butuh nama baru buat FILTER/tab di dalam halaman gabungan itu. ❓ Perlu diputusin bareng poin Fakrul #9 di bawah.

### A.3 Kelola Casting Director disatuin ke Kelola Admin (CD jadi "subadmin")
[Sumber: Fakrul #9] — ✅ Secara teknis LOW RISK: CD dan Admin sama-sama row di tabel `users` (beda kolom `role`), jadi gabungin ke 1 layar dengan filter/tab role itu murni kerjaan UI, gak butuh migrasi data. Existing: `super-admin/casting-directors/index.blade.php` (terpisah) perlu dilebur ke `AdminManagementController`/view Kelola Admin.

### A.4 Klik nama admin di Kelola Admin → lihat portofolio kerja
[Sumber: Fakrul #7] — Halaman detail baru per-akun (riwayat proyek yang ditangani, rekap gaji — lihat B.3 di bawah). 🆕 Perlu didesain: apa aja isinya, khususnya buat akun CD (portofolio CD = proyek yang di-review?) vs Admin (portofolio = proyek yang dikelola + rekap gaji).

### A.5 Akun jangan pernah dihapus (keep data)
[Sumber: Fakrul #7] — ⚠️ **GAP TERVERIFIKASI, bukan cuma teori.** Dicek langsung ke `AdminManagementController::destroy()`: sekarang MEMANG hard-delete (`$user->delete()`) kalau akun belum punya riwayat yang kena foreign-key constraint. Model `User` belum pakai `SoftDeletes`. Akun yang masih "bersih" (baru dibuat, belum pegang proyek) BISA kehapus permanen sekarang — kontradiksi langsung sama requirement ini. Perlu fix: `SoftDeletes` di `User` + ganti tombol "Hapus" jadi "Nonaktifkan" untuk SEMUA akun, bukan cuma yang kena FK constraint.

---

## B. Database, penggajian, reimburse, history

### B.1 Tabel penggajian (field untuk Extras & Admin)
[Sumber: Fakrul #3] — ✅ Sudah kepisah secara struktur: `staff_payrolls` (gaji staf/Admin) + `payments` (fee Extras per proyek). **Perlu klarifikasi ke dospem:** apakah maksudnya HARUS 1 tabel gabungan, atau cukup dikonfirmasi bahwa dua konsep ini emang beda (gaji tetap staf vs fee per-proyek Extras) dan sengaja dipisah? Manager-session condong ke "sengaja dipisah" (beda lifecycle & field), tapi ini keputusan yang lebih pas dikonfirmasi ke dospem langsung, bukan diasumsikan sepihak.

### B.2 Reimburse beda dari history beda dari penggajian
[Sumber: Fakrul #4] — ✅ Reimburse sudah ada terpisah: tabel `payment_addons` (polymorphic, nempel ke `payments` ATAU `staff_payrolls`, buat transport/penginapan/dst yang sifatnya opsional & jumlahnya gak tetap — beda dari nominal pokok). ❓ "History" yang dimaksud masih perlu diklarifikasi: tabel baru yang beneran diperlukan, atau cuma REKAP/TAMPILAN dari data yang udah ada (payroll + reimburse + riwayat proyek)? Jangan buru-buru bikin tabel baru sebelum ini jelas — bisa jadi cukup query/view, bukan tabel fisik.

### B.3 Rekapan gaji di Kelola Penggajian ditambahkan ke Kelola Admin
[Sumber: Fakrul #8] — Terkait langsung ke A.4 (portofolio admin). Rekap gaji jadi salah satu section di halaman detail/portofolio itu, bukan menu terpisah.

### B.4 Menu Kelola Gaji — masih bingung penempatannya
[Sumber: Fakrul #6] — Fakrul sendiri belum yakin: submenu Admin buat kelola gaji, TAPI SuperAdmin juga perlu monitoring-nya, dan Admin mungkin juga butuh "Kelola Penggajian" sendiri. 🆕❓ Perlu didesain sebagai satu alur menu yang jelas — kemungkinan: SuperAdmin dapat versi READ-ONLY/monitoring lintas-Admin, Admin dapat versi kelola aktif buat tim yang dia pegang. Jangan diputusin sekarang, tunggu gambaran lengkap.

### B.5 Data-nya diisi semua biar kelihatan isinya
[Sumber: Erlina batch 1 #8] — Ini soal SEEDER/dummy data buat demo bimbingan, bukan perubahan struktur. Perlu cek `database/seeders/` — pastikan semua tabel (termasuk yang jarang keisi kayak `payment_addons`, `field_notes`, `cancellations`) punya beberapa baris contoh, bukan cuma tabel utama.

---

## C. Role baru: Korlap (Koordinator Lapangan) + Absensi

**🆕 Ini yang paling signifikan dari semua catatan — kemungkinan nambah 1 ROLE BARU ke sistem (di luar role yang udah ada).** Perlu dibahas serius sebelum ditulis ke SPEC, karena nyerempet RBAC penuh (bukan modul kecil).

### C.1 SuperAdmin harus ada monitoring absen
[Sumber: Erlina batch 1 #5]

### C.2 Absen jangan manual — foto di lapangan + timestamp, koordinator (opsional)
[Sumber: Erlina batch 1 #6] — "koordinator (opsional)" kemungkinan artinya: field foto lokasi + jam otomatis WAJIB, sementara pencatatan siapa koordinator yang jaga itu opsional/nullable.

### C.3 Tugas Korlap: validasi absen
[Sumber: Erlina batch 1 #7 & batch 2 #2 — disebut 2x, berarti ini poin penting]

### C.4 Korlap juga yang kasih penilaian dari lapangan
[Sumber: Erlina batch 1 #22] — Ini bikin sistem grade jadi TIGA pihak, bukan dua: Admin (rekomendasi awal) + Casting Director (keputusan akhir/greenlight, dari Bagian L.5 yang udah ditulis manager-session sebelumnya) + **Korlap (penilaian performa lapangan)**. ❓ Perlu diperjelas: penilaian Korlap ini masuk ke `grade` mana, atau field/kolom baru lagi yang terpisah dari `grade_cd`?

**Pertanyaan besar yang perlu dijawab dospem/tim sebelum ini di-spec:**
- Korlap itu akun/role baru di tabel `users` (butuh RBAC penuh: login sendiri, dashboard sendiri), atau cuma "topi tambahan" yang dipakai salah satu Admin/CD yang lagi ditugaskan ke lokasi syuting?
- Kalau role baru: apakah Korlap ditugaskan per-proyek (mirip `cd_project_assignments`/`admin_project_assignments` yang udah ada polanya) atau permanen?
- Absen yang divalidasi Korlap itu absen EXTRAS di lokasi syuting (bukan absen staff kantor yang udah ada di `attendances` table) — perlu tabel/kolom baru di `event_shooting_dates` atau tabel absensi terpisah?

---

## D. Pendaftaran Extras: validasi, auto-delete, auto-ban

### D.1 Validasi pendaftaran Extras — memenuhi syarat
[Sumber: Erlina batch 1 #14] — Perlu diperjelas syarat apa aja (usia minimum? kelengkapan profil/foto? dokumen?) — belum ada detail kriterianya di catatan ini.

### D.2 Opsi selain validasi manual: auto-delete
[Sumber: Erlina batch 1 #15] — Kemungkinan: akun Extras yang gak lolos validasi/gak lengkapin profil dalam waktu tertentu otomatis kehapus. **Ini perlu di-cross-check ke A.5 (akun gak boleh dihapus)** — apakah auto-delete ini KHUSUS akun yang belum pernah aktif/apply proyek (jadi gak kontradiksi sama "keep data" yang dimaksud buat akun yang UDAH punya riwayat), atau berlaku ke semua akun? ❓ Ini titik yang paling gampang bikin dua requirement kontradiksi kalau gak diperjelas dari awal.

### D.3 Sosialisasikan fitur auto-delete ke user
[Sumber: Erlina batch 1 #16] — Ini soal copy/UX (kasih tau di form pendaftaran: "akun akan dihapus otomatis jika tidak ada aktivitas dalam X hari"), bukan soal backend.

### D.4 Otomasi nge-ban
[Sumber: Erlina batch 1 #12] — Beda dari D.2 (delete) — ini BAN/suspend akun, kemungkinan buat pelanggaran (bukan sekadar gak lengkap profil). ❓ Kriteria ban belum jelas (siapa yang nge-trigger, alasan apa).

---

## E. Proyek Casting: ID, form wajib, detail

### E.1 ID project harus primary key
[Sumber: Erlina batch 1 #19] — ✅ Sudah otomatis terpenuhi — `casting_projects.id` sudah `bigIncrements` primary key by default (standar Laravel). Kemungkinan ini catatan buat LAPORAN/ERD (📝 dokumentasi), bukan ada yang perlu diubah di kode.

### E.2 ID project ditampilkan (di UI)
[Sumber: Erlina batch 1 #20] — Ini baru actionable: tampilkan ID/kode proyek di UI (card/list proyek), belum ada sekarang setahu manager-session (perlu dicek ulang pas action).

### E.3 Field wajib di form input project
[Sumber: Erlina batch 1 #17] — Field mana yang wajib belum disebutkan detail, perlu diperjelas pas masuk fase spec.

### E.4 Halaman "Detail Project"
[Sumber: Erlina batch 1 #25]

### E.5 Jadwal, absensi, dashboard casting disatukan/dilengkapi
[Sumber: Erlina batch 1 #26] — Kemungkinan nunjuk ke halaman yang sama kayak E.4, atau dashboard CD yang perlu section Jadwal+Absensi ditambahkan (nyambung ke Bagian L.3/L.9 yang udah ditulis manager-session — modul Jadwal ringkas & dashboard CD).

---

## F. Penilaian/Grade multi-pihak

### F.1 Dua validasi buat grade Extras: Admin & Casting Director
[Sumber: Erlina batch 1 #21] — ✅ Ini SUDAH sesuai arah Bagian L.5 yang udah ditulis manager-session (Admin = grade rekomendasi/advisory, CD = `grade_cd` final/greenlight, dua kolom terpisah). Tinggal dikonfirmasi ini yang dimaksud, bukan hal baru.

### F.2 + F.3: Korlap juga kasih penilaian, dibuat otomasi penilaian variabel
[Sumber: Erlina batch 1 #22, #23] — Ini yang bikin F.1 jadi TIGA pihak (lihat C.4 di atas). "Otomasi penilaian variabel" — ❓ maksudnya kemungkinan skor gabungan otomatis dihitung dari beberapa variabel (misal: kehadiran + ketepatan waktu + nilai Korlap → skor komposit), tapi ini masih tebakan, perlu dikonfirmasi definisi variabelnya apa aja.

### F.4 Kolom komentar di negosiasi fee
[Sumber: Erlina batch 1 #24] — Actionable & jelas, tambah kolom `komentar`/catatan di `fee_negotiations`.

---

## G. UI/UX kecil

### G.1 Kalau udah ditolak, gak perlu ada tombol lagi
[Sumber: Erlina batch 2 #1] — Perlu diperjelas tombol di halaman/context mana (kemungkinan tombol approve/reject di modal kandidat CD, atau tombol apply Extras yang udah ditolak) — jelas & gampang begitu konteksnya dipastikan.

---

## H. Dokumentasi akademik & proses (kemungkinan bukan perubahan aplikasi)

### H.1 Judul diubah, maksimal 14 karakter
[Sumber: Fakrul #1] — 📝 Ini soal judul skripsi/laporan, ketentuan pasti nunggu dospem. Belum actionable.

### H.2 Kelola Admin: masuk "modul" bukan "fitur"
[Sumber: Fakrul #5] — 📝 Kemungkinan besar ini istilah klasifikasi di BAB 3/dokumen laporan (docs/BAB-3-DRAFT.md), bukan perubahan kode. ❓ Perlu tau definisi persis "modul" vs "fitur" yang dipakai dospem sebelum nulis ulang dokumen.

### H.3 Tentukan roadmap untuk semua role
[Sumber: Erlina batch 1 #9] — 📝 Dokumen perencanaan, bisa disatukan dengan `docs/PRD-LITE.md` yang sudah ada atau jadi file baru.

### H.4 Buat list "done" bahan bimbingan
[Sumber: Erlina batch 1 #10] — 📝 Kemungkinan maksudnya rekap fitur yang UDAH SELESAI dikerjakan (bisa ditarik dari histori kerja/`DEV-NOTES.md` yang udah ada) buat ditunjukkan ke dospem sebagai bukti progres.

### H.5 Logic-nya harus dipahami (tim)
[Sumber: Erlina batch 1 #18] — 📝 Catatan internal buat tim (paham alur sebelum demo ke dospem), bukan task pengembangan.

### H.6 "Yang penting: search, filter, dan grouping"
[Sumber: Erlina batch 1 #13] — Prinsip umum, kemungkinan berlaku ke SEMUA halaman list/tabel di sistem (Kelola Admin, Kelola Extras, Rekap, dst) — bukan 1 fitur spesifik, lebih ke prioritas/filosofi UX yang harus dicek merata.

---

## Ringkasan status & langkah berikutnya

- **Sudah terverifikasi aman/sesuai** (gak perlu kerjaan baru): B.1 (struktur payroll terpisah sudah ada), B.2 sebagian (reimburse sudah ada), E.1 (primary key otomatis), F.1 (dua-grade Admin+CD sudah di-spec Bagian L.5).
- **Gap nyata yang udah dikonfirmasi ke kode** (bukan asumsi): A.5 (hard-delete akun, kontradiksi sama requirement keep-data).
- **Scope baru paling besar, butuh keputusan tim dulu sebelum di-spec:** C (role Korlap + absensi non-manual) — ini AFFECT desain RBAC, bukan modul kecil.
- **Berpotensi kontradiksi kalau gak diperjelas:** D.2 (auto-delete) vs A.5 (jangan pernah hapus akun) — WAJIB diperjelas dulu batasannya sebelum masuk spec, biar gak bikin fitur yang saling makan.
- **Kemungkinan cuma soal dokumen/istilah laporan, bukan kode:** H.1, H.2, H.3, H.4, H.5 — dicek dulu ke dospem sebelum dianggap task aplikasi.

**Belum diubah jadi task `SPEC.md` apapun** — sesuai arahan Fakrul, file ini murni kumpulan catatan dulu. Lanjut kalau ada tambahan dari role lain (Extras? Admin operasional lain?), baru nanti dipilah bareng: mana yang langsung bisa jadi Bagian SPEC baru, mana yang masih butuh jawaban dospem/tim dulu.
