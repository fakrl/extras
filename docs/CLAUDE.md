# CLAUDE.md — SIM Casting JBTB (Project Work / Skripsi Pengganti)

> **Fungsi file ini:** handoff konteks lengkap dari sesi diskusi sebelumnya. Baca ini dulu sebelum lanjut. Semua keputusan, model bisnis, alur, dan backlog ada di sini.
> **Terakhir diperbarui:** 29 Agustus 2026 · **Status:** coding aktif, 9 sesi dev selesai (lihat `DEV-NOTES.md`) — RF-01 s.d. RF-52 mayoritas sudah dibangun+tes (auth RBAC, profil, proyek, nego fee, review CD, kontrak+TTD, pembayaran, karyawan+payroll, WA Gateway self-hosted). Sisa: RF-04 (validasi duplikat NIK), RF-30 (rekap margin), RF-35 (catatan Korlap), RF-38 (link grup WA). Proposal Bab 1-3 pra-sempro (target 1 Sept 2026) berjalan paralel — lihat `BAB-3-DRAFT.md` & `OPEN-QUESTIONS-PROPOSAL.md`.

---

## 1. Ringkasan Proyek

**Nama:** Sistem Informasi Manajemen Casting Talent & Extras Berbasis Web dengan Fitur Negosiasi Fee Digital — **SIM Casting JBTB**.
**Studi kasus:** JBTB Casting (agensi milik **Jestika Aisya Kordak**, brand: *Jbtb.Casting*, Depok).
**Konteks:** Ini **project work pengganti skripsi** — harus **produksi sistem beneran** untuk syarat kelulusan, TAPI dengan scope & batasan jelas biar nggak membengkak.
**Masalah yang diselesaikan:** proses rekrutmen extras masih manual (grup WA + Excel + Google Drive) → lambat, kesalahpahaman fee, data tersebar, **konflik pembayaran ("udah kerja belum dibayar / nggak sesuai deal")**.

**Tim & kapasitas riil:** developer = **user (fakrul) + Claude/agent** (praktis solo dev + AI) + **1 orang urusan data bisnis**. Timeline sedang (2–4 bulan).
**Perusahaan:** Indotech Digital (indotechdigital.id). Pakai **Jira** (indotechdigital.atlassian.net) + alur **spec → developer → tester → reviewer**, konvensi `CLAUDE.md` + `docs/WORKLOG.md`, approval mentor (ka fandi).
**Proyek lain (referensi stack):** *Nobiplay/NobiLive* (Laravel + Reverb + Alpine + video.js, streaming, 3 peran) & *Nobel Akademi* (Laravel + Livewire, queued mail SMTP Gmail). Prototype UI JBTB **niru pola layout Nobiplay** (lihat `../nobiplay ppv/live-pay-per-view.html` & `live-stream-cms.html`).

---

## 2. Aktor (7 peran — diperluas hasil bimbingan dosen, 21-22 Agu 2026)

> **UPDATE PENTING:** struktur aktor lama (3 peran: Extras, Admin Agensi tunggal, Client/CD) di-supersede jadi 7 peran lewat RBAC berjenjang. Detail lengkap di `BAB-3-DRAFT.md` §3.1.1.

| Peran | Keterangan | Cara masuk sistem |
|---|---|---|
| **Super Admin** | Owner (Jestika). Dashboard monitoring/analitik only, **tidak** pegang operasional harian. Nambah akun Admin + tentuin sub-role + set nominal honor staf. | Akun tunggal, dibuat manual saat inisialisasi |
| **Admin Default** | Operasional inti (seleksi, nego fee, kontrak, pembayaran Extras). **Bisa lebih dari satu akun** (beda dari asumsi lama "hanya satu Admin"). | Dibuat oleh Super Admin |
| **Admin — Talco (Talent Coordination)** | Cabang Admin Default, ditugaskan per proyek sesuai kebutuhan (nggak selalu tiap proyek). Zero functional footprint di sistem (talent utama di luar scope) — cuma role+log buat keperluan honor. Akses login read-only riwayat kerja & status honor sendiri. | Dibuat oleh Super Admin |
| **Admin — Korlap (Koordinator Lapangan)** | Cabang Admin Default. Ada fitur fungsional: absensi Extras di lapangan, catatan/sanksi Extras. | Dibuat oleh Super Admin |
| **Admin — Sosial Media/Multimedia** | Cabang Admin Default. Sama seperti Talco: zero functional footprint, role+log only, akses login read-only. | Dibuat oleh Super Admin |
| **Extras / Talent** | Figuran/talent yang daftar casting. User "semua umur" (termasuk orang tua) → UI wajib simpel & kebaca. | Self-register bebas (tanpa kode) |
| **Client / Casting Director (CD)** | Pihak rumah produksi (PH) yang butuh talent. **PH sebagai entitas TIDAK punya akun** — yang login adalah CD yang mewakilinya. | Registrasi lewat tautan khusus terpisah (bukan kode undangan manual — otomatis role CD) |

> Catatan: Talco/Korlap/Sosmed BUKAN karyawan tetap — dibayar per-event/proyek, model konseptual sama seperti fee proyek-basis Extras (lihat §6b baru di bawah).

---

## 3. Model Bisnis & Alur Uang (PENTING — inti pain)

**Alur uang (dua kaki):**
1. **Client (CD) → Admin (JBTB):** JBTB nagih client lewat **Invoice** (uang masuk). Contoh riil pernah ada di `Invoice JBTB CASTING X.xlsx`, sudah dihapus dari repo (data rahasia, repo ini public).
2. **Admin (JBTB) → Extras:** JBTB bayar extras (payout). Contoh riil pernah ada di `JBTB BUDGET EXTRAS KUI APPROVAL.xlsx`, sudah dihapus dari repo (data rahasia, repo ini public).
3. **Margin agensi = SELISIH** antara fee dari client dan payout ke extras (agensi biasa motong ±20%, tapi per-kepala bisa beda).

**Dari data Excel asli (decode kolom):**
- `FEE DAYS / FEE REAL` = budget client per peran per hari (mis. 400rb / 300rb / 200rb).
- `FEE DAY AGC` = yang agensi **bayar** ke extras hasil nego (200rb / 150rb / 125rb).
- `SELISIH` = **margin agensi per kepala** → **RAHASIA, admin-only**.
- Sheet `CATATAN KEUANGAN ADMIN PERDAY` = rekonsiliasi harian (uang masuk vs pengeluaran vs sisa, status AMAN/PLUS/MINUS).
- Skala **jauh lebih besar** dari asumsi SRS ("±70 extras, ±3 proyek/bln"): satu proyek (*Kado Untuk Ibu*/Starvision) = **9–13 hari shooting, 30+ extras/hari/kelas, ~Rp 79,5jt masuk / ~Rp 57,8jt payout / ~Rp 21,7jt margin.** → **Revisi asumsi skala di SRS.**

**Inti value produk:** bukan "generator kontrak", tapi **(a) catatan kesepakatan fee yang nggak bisa dibantah + (b) pelacak status pembayaran transparan (bukti transfer)** → matiin konflik "belum dibayar / nggak sesuai deal".

---

## 4. Grade vs Fee (KOREKSI PENTING)

- **Grade (A/B/C) = penilaian KUALITAS extras oleh admin.** A = terbaik (cakep, jago improve), makin turun → B, C. **Bukan tarif tetap.** Grade diberikan admin **saat seleksi**, bisa berubah seiring waktu (reputasi).
- **Fee = DINAMIS.** Mulai dari **rate card** extras (tarif harapan di profilnya), **dinego via WhatsApp**, lalu **diset admin per-orang saat seleksi**. Grade tinggi biasanya = fee lebih tinggi, tapi angka final selalu per-kesepakatan.
- Grade + fee + keterangan = **internal admin**. Extras cuma lihat **hasil**: *Sedang direview / Lolos / Ditolak* (bukan catatan admin, bukan fee orang lain).
- **Kriteria per event** ≠ grade. Kriteria = kebutuhan client (mis. *Kado Untuk Ibu, set Pasar: ibu-ibu 29–50 th, bapak 29–50 th, look sederhana*). Disesuaikan tiap client.

---

## 5. Tembok Visibilitas 3 Lapis (LOGIKA BISNIS UTAMA — anti-poaching)

Ini bukan sekadar privasi — ini **proteksi bisnis inti**. Kalau client bisa kontak extras langsung, agensi dipotong (margin hilang, talent dibajak). Extras pakai **nama panggung (alias)** saat ditunjukin ke client.

| Data | Client/CD | Extras | Admin |
|---|:--:|:--:|:--:|
| Nama panggung (alias) + visual (foto/video) + kecocokan kriteria | ✅ | ✅ | ✅ |
| Fee bersih dirinya sendiri + status | — | ✅ | ✅ |
| Kontak/rekening milik sendiri | ❌ | ✅ | ✅ |
| Nama asli, NIK, kontak, rekening, sosmed/porto (semua extras) | ❌ | ❌ (sendiri) | ✅ |
| Fee client, payout, **margin/selisih**, cashflow | ❌ | ❌ | ✅ |

> **Catatan penting:** alias melindungi di tahap **browsing & seleksi**. Begitu deal → **Talent Release** ditandatangani, **nama asli (KTP) muncul di dokumen legal** (lihat template). Jadi tembok "runtuh terkendali" di titik kontrak — wajar & nggak terhindarkan.
> **Sosmed/IG/porto** extras: dilihat extras & admin saja; ke client ditampilkan karyanya **tanpa handle kontak** (biar nggak di-DM langsung).

---

## 6. State Machine / Alur Inti (FINAL — direvisi 21 Agu 2026)

> **Supersede catatan lama:** nego fee BUKAN lagi via WhatsApp manual, dan TTD BUKAN lagi upload scan. Lihat detail di bawah.

```
Admin Default POSTING LOWONGAN (menu Event & Lowongan; toggle "Butuh Dadakan/urgent" + kelas/kriteria + kuota + tanggal-tanggal shooting)
   → EXTRAS DAFTAR (feed ala sosmed; wajib cantumin rekening penerima)
        → Sistem cek bentrok jadwal → warning non-blocking kalau overlap sama proyek lain yang statusnya aktif
   → ADMIN DEFAULT SELEKSI (klik profil lengkap pendaftar: sosmed/porto/rate card):
        • Filter & Beri Grade (A/B/C) — independen dari fee
        • NEGOSIASI FEE di dalam sistem (bukan WA) — ala InDrive, multi-round:
          Admin ajukan fee awal → Extras Terima/Counter → Admin Terima/Counter balik/Tolak → ... → Deal (fee terkunci)
        • 📤 PRESENT ke CD (hanya kandidat yang sudah Deal)  (atau  ✕ Tolak)
        → Sistem tampilkan ulang warning bentrok jadwal ke Admin saat present, kalau relevan
   → CD: review kandidat yang di-present → ✅ Approve/❌ Reject (individual atau massal)
   → ADMIN kabari extras lolos (notif in-app + email + WA self-hosted whatsapp-web.js, gratis)
   → EXTRAS lengkapi KTP + konfirmasi rekening
   → AUTO-GENERATE KONTRAK (Talent Release), HARGA = fee hasil nego (Deal)
   → TTD via CANVAS SIGNATURE (gambar langsung di browser, bukan upload scan, bukan PSrE) — Admin Default & Extras
   → PEMBAYARAN: Admin Default tandai "sudah ditransfer" + UPLOAD BUKTI  → Extras konfirmasi terima
   → SELESAI → masuk RIWAYAT extras + rekap admin
```

**Dua jalur status TERPISAH** (jangan digabung):
- **Partisipasi:** Daftar → Direview Admin → Nego Fee → Deal → Diajukan ke CD → Direview CD → Lolos/Ditolak → Kontrak Ditandatangani → Selesai Produksi / Dibatalkan
- **Pembayaran:** Belum dibayar → Ditransfer (+bukti, ke rekening X) → Dikonfirmasi diterima

**Negosiasi fee = di dalam sistem, model ala InDrive** (bukan WhatsApp lagi): Admin ajukan penawaran awal dari rate card+budget client, Extras terima/counter, berulang tanpa batas ronde sampai salah satu Terima (=Deal) atau mundur. Setiap ronde tercatat (siapa, berapa, kapan) sebagai riwayat — dasar kuat kalau ada sengketa. **Urutan penting:** nego fee ke Deal dulu, baru present ke CD — CD approve kecocokan talent, bukan approve harga.

**Pembatalan:** 3x cancel mendadak (< H-2 shooting) di proyek berbeda → status extras "Melanggar". Korlap bisa kasih catatan/sanksi tambahan dari lapangan.

---

## 6b. Modul Karyawan Internal — Absensi & Penggajian Staf (BARU, hasil bimbingan 21-22 Agu 2026)

Talco, Korlap, dan Sosmed/Multimedia bukan karyawan tetap — dibayar per-event, mirror konsep fee proyek-basis Extras, dengan alur:

```
SUPER ADMIN nambah akun Admin baru → tentuin sub-role (Talco/Korlap/Sosmed) → set nominal honor standing (bisa diadjust nanti)
   → SUPER ADMIN tugaskan sub-admin ke proyek tertentu (sesuai kebutuhan, nggak wajib tiap proyek)
   → Sistem catat log aktivitas sub-admin per proyek
   → Proyek berstatus "Selesai" → log aktivitas jadi "Selesai" → jadi dasar kelayakan honor
   → Super Admin bisa tambah add-on manual (reimburse transport/penginapan dst) ke catatan honor
   → Sistem auto-generate SLIP HONOR (PDF, reuse infra PDF kontrak/invoice) per Admin per proyek Selesai
   → Admin (semua tipe) bisa liat Riwayat Kerja sendiri; Talco/Sosmed dapet akses read-only "Riwayat Kerja & Status Gaji Saya"
   → Super Admin liat rekap honor semua Admin di dashboard monitoring
```

**Alasan fitur ini masuk scope:** Erlina (CFO) konfirmasi JBTB belum punya slip gaji — staf sering "kira-kira" nominal yang bakal didapat. Ini persis pain point transparansi yang sama dengan masalah fee Extras, jadi modul ini bukan fitur tempelan, tapi ekstensi natural dari value prop inti sistem (catatan kesepakatan yang nggak bisa dibantah).

**Batasan modul ini:** bukan payroll formal (tanpa potongan pajak/BPJS), absensi cuma log aktivitas (bukan geolocation/foto check-in), nominal honor di-set manual oleh Super Admin (bukan auto-calculated dari rate/jam kerja).

---

## 7. Prototype: `prototype-jbtb-final.html`

Satu file HTML clickable, 3 peran (login demo → pilih peran; **di produksi TIDAK ada role-picker**). Skin **terang + brand ijo-hitam** (logo JBTB ijo-item) + font Inter. Layout niru Nobiplay.

- **Extras (feed sosmed):** Beranda (section 🚨 Butuh Dadakan di paling atas bila ada + baris kartu per kategori, **diurut fee terbesar di atas**) · Riwayat (gabungan: "Sedang berjalan" + "Selesai" + total pendapatan) · Profil (alias, rate card, video, foto grid, pengalaman luar JBTB, 🔗 portofolio/IG — visibilitas admin-only).
- **Admin (CMS sidebar):** Dashboard (KPI + perlu tindakan) · Event & Lowongan (list + tombol **Buka Lowongan** [form: judul, client, jenis, jadwal, deadline, deskripsi, kelas+fee client+payout+kuota, toggle urgent] + Detail Event [kriteria + hari shooting] + **✅ Extras Approved** + **👥 Pendaftar & Seleksi**) · Seleksi (per-lowongan, tab grade, **klik pendaftar → profil lengkap → grade+WA nego+fee+keterangan → Present/Tolak**) · Pembayaran (fee client/payout/margin "internal" + tandai transfer + upload bukti — **nyambung ke tampilan extras**) · Keuangan (cashflow harian) · Invoice (ke client).
- **Client/CD (sidebar):** Calon Talent (approve yang di-present) · Riwayat Casting (**🔁 Ambil lagi/Booking lagi** = re-book talent untuk project baru; badge "⭐ pernah dipakai") · Detail talent.

**Demo loop yang harus jalan:** Admin → Pembayaran → "Tandai Sudah Ditransfer" → keluar → login Extras → Riwayat/Pembayaran → status berubah "Sudah dibayar + bukti".

---

## 8. Keputusan Desain (Decision Log)

1. **Login:** produksi = login email/password, role dibaca dari akun. Registrasi yang membedakan: Extras (bebas) · Client/CD (**butuh kode undangan admin**) · Admin (dibuat manual IT). Role-picker prototype = demo only.
2. **Warna:** brand ijo-hitam (primary hijau `#15803D`, gelap `#0B1A12`) di login/hero/tombol. **Update 28 Agu 2026:** konten/dashboard pakai dark/light toggle (bukan terang paksa) — user pilih sendiri, tersimpan `localStorage`; "semua umur" dipenuhi lewat kontras cukup di kedua tema, lihat `UI-GUIDELINES.md`. ⚠️ Hijau brand & hijau "success" mirip — kalau ganggu, bedain shade.
3. **Usability "semua umur":** tombol besar (≥48px), bahasa polos, status warna konsisten (hijau=beres, kuning=nunggu, merah=batal/tolak), focus ring keyboard.
4. **Data minimization (UU PDP):** NIK/KTP **hanya diminta saat deal/mau dibayar**, bukan saat registrasi. **NIK JANGAN dipakai jadi login/primary key** — login = email, PK = auto-increment, NIK = field unik tervalidasi (1 NIK = 1 akun).
5. **Buka Lowongan** hanya di menu Event & Lowongan (dihapus dari dashboard).
6. **Re-book dari client** → masuk **inbox "Permintaan Client" di admin** (tercatat), **BUKAN** langsung WA.
7. **Kelola Akun Extras** = perlu (nonaktifkan yang bermasalah + apresiasi yang rajin/disukai client + rekap sering-lock).
8. **Storage kontrak/dokumen:** SRS lama sebut Google Drive → **ada kontradiksi dengan klaim "enkripsi data sensitif"**. Untuk produksi, pertimbangkan disk VPS + backup (lebih simpel, tanpa dependency OAuth). Video/foto profil = file berat → keputusan storage penting.
9. **Web Push / realtime:** opsional. Tim punya pengalaman Reverb (Nobiplay) tapi realtime produksi belum kebukti. Prioritas = email (proven, pola Nobel Akademi) + notif in-app.
10. **Payment gateway (Midtrans/Tripay) — ditolak, tetap manual bukti transfer.** Alasan: (a) gateway kayak Midtrans/Tripay didesain buat *menerima* pembayaran (kartu/VA/e-wallet), bukan *disbursement/payout* ke banyak rekening extras — beda produk (Payout API, tier bisnis lebih tinggi + KYC ribet); (b) selalu ada biaya transaksi (MDR), nggak sepadan buat scope solo-dev + timeline akademik; (c) membalikkan keputusan lama "sistem nggak proses uang, cuma catat status".

---

## 9. Backlog Build (status per 29 Agustus 2026 — lihat `DEV-NOTES.md` untuk detail sesi)

- [x] Layar **auto-generate kontrak Talent Release** (canvas signature, vanilla JS, tanpa library) — Sprint 4.
- [x] Modul **Negosiasi Fee in-app** (ala InDrive, multi-round, tercatat) — Sprint 3, sempat 500 total gara-gara bug `$fillable` (Session 4), fixed & verified Session 5.
- [x] **Deteksi bentrok jadwal** (soft-warning, 2 checkpoint: saat apply & saat present ke CD) — Sprint 2 (apply), Session 5 (RF-22, checkpoint present-ke-CD yang sempat kelewat).
- [x] **WhatsApp Gateway** (`whatsapp-web.js` self-hosted) untuk notif otomatis (apply, hasil seleksi, reminder H-1, kontrak siap TTD) — Session 8-9, termasuk fix bind-localhost & queue job biar bulk-approve nggak blocking.
- [ ] Menu **Kelola Extras** (aktif/nonaktif + apresiasi + rekap sering-lock) — belum ada sesi yang menyentuh ini.
- [x] **Rekap extras** (extras paling sering dipilih) + [x] **Ekspor rekap ke Excel** — Sprint 6 (RF-51/52). **Rekap margin per-project (RF-30)** — BELUM, beda dari rekap extras, masih di backlog.
- [x] **Modul Manajemen Karyawan** (Super Admin nambah Admin + sub-role + honor) — Sprint 5.
- [x] **Absensi staf** (log aktivitas) + **auto-generate slip honor PDF** — Sprint 5.
- [x] **Riwayat Kerja** unified + akses read-only Talco/Sosmed — Sprint 5 (RF-43/44).
- [ ] **Inbox "Permintaan Client"** (re-book) di admin — belum ada sesi yang menyentuh ini.
- [x] Notifikasi email (queued) — Session 4 (RF-36).
- [ ] Grade filter (tab sudah ada, logika filter belum) — belum dikonfirmasi built.
- [ ] **RF-04** (validasi duplikat NIK saat lolos seleksi) — belum dibangun. **Catatan arsitektur:** `nik` di-cast `encrypted` (IV random tiap enkripsi) sekaligus `unique` di `DATABASE-SCHEMA.md` — `UNIQUE` constraint DB biasa TIDAK bisa mendeteksi duplikat di kolom terenkripsi non-deterministik (ciphertext beda tiap kali walau plaintext sama). Butuh kolom hash deterministik terpisah (HMAC) buat lookup, `nik` tetap `encrypted` buat display.
- [ ] **RF-35** (catatan/sanksi Korlap) — tabel `field_notes` sudah ada sejak Sprint 1, controller/UI belum dibangun.
- [ ] **RF-38** (link grup WA per proyek) — sengaja di luar scope task WhatsApp Gateway (Session 8), belum dikerjakan.
- [ ] Checklist keamanan pre-launch — `SECURITY-CHECKLIST.md` sudah ada kolom Status terverifikasi kode (bukan cuma rencana), tapi masih ada beberapa poin BACKLOG (rate limit, CAPTCHA, security headers, force HTTPS, dependency scan) — cek dokumen itu langsung buat status per-poin.

---

## 10. Tech Stack (untuk build)

- **Backend:** Laravel (PHP) + **MySQL**. Ikuti pola tim (queued Mailable + SMTP Gmail seperti Nobel Akademi). **INGAT: restart `queue:work` tiap deploy/ubah kode job** — kalau tidak, worker pakai versi lama. Untuk tes email tanpa DNS: `MAIL_MAILER=log`.
- **Frontend:** Blade + **Bootstrap 5** (rekomendasi Design System doc; banyak template admin gratis: AdminLTE/SB Admin/CoreUI) ATAU Livewire/Alpine (pola Nobiplay). Terapkan design token dari `_archive/19-Design-System.md` tapi warna disesuaikan ke brand ijo-hitam.
- **WhatsApp:** `whatsapp-web.js` self-hosted (revisi 28 Agu 2026 — Fonnte/Wablas dicek ke sumbernya sendiri, TERNYATA juga "unofficial"/risiko banned sama, tidak ada keuntungan bayar; detail lihat `OPEN-QUESTIONS-PROPOSAL.md` poin 3). Jalan sebagai proses Node.js/Express terpisah 24/7 (port lokal, mis. 3001), Laravel panggil via `Http::post()`. Mitigasi wajib: nomor khusus sistem, volume rendah non-broadcast, WA kanal pelengkap — email tetap primer.
- **Hosting:** shared hosting / VPS. Domain/SSL/akun infra **harus atas nama agensi** (biar sistem nggak mati pas developer lulus/cabut).
- **Deployment/ops:** environment terpisah (dev/staging/prod, apalagi ada data KTP asli), backup terjadwal + tes restore, handover + manual admin (admin non-teknis).

---

## 11. Data / Concern Produksi yang Masih Perlu Diklarifikasi

- **Model uang final:** konfirmasi 2 kaki (Client→JBTB invoice; JBTB→Extras payout). Kadang di kwitansi client bayar talent langsung (cek `TALENT RELEASE EROS.pdf`, sudah dihapus dari repo — data rahasia, repo ini public) — pastikan sistem lacak kaki yang mana.
- **Teks kontrak & invoice riil** (klausul, penomoran, pajak/PPN) → PR orang bisnis.
- **Consent & UU PDP:** form persetujuan + kebijakan privasi + retensi (SRS: 1 tahun) + mekanisme hapus, karena nyimpen **KTP asli**.
- **Master data riil:** nilai kriteria (kategori warna kulit, ukuran baju), jenis proyek, rentang fee.
- **"Penanggung jawab"** & struktur penandatangan kontrak bervariasi per client (lihat 4 template) — model tanda tangan fleksibel.

---

## 12. Dokumen Sumber di Folder Ini

- `_archive/Business Information.md`, `_archive/Requirement Baseline Summary.md`, `_archive/01-Kebutuhan-Fungsional-NonFungsional.md` — requirement awal (baseline 35 RF), diarsipkan (historis, masih dikutip di sini buat konteks).
- `_archive/17-SRS-IEEE29148-UML-Lengkap.md` — SRS lengkap (membengkak jadi **70 RF** + 18 RNF; UML, ERD, LRS, class diagram). ⚠️ scope besar, perlu disaring ke MVP. Diarsipkan.
- `_archive/17-User-Flow-Documentation.md`, `_archive/18-Information-Architecture.md`, `_archive/19-Design-System.md` — flow, IA, design system. Diarsipkan.
- **Excel** (`Invoice JBTB CASTING X.xlsx`, `JBTB BUDGET EXTRAS KUI APPROVAL.xlsx`) — **sudah dihapus dari repo** (1 September 2026): berisi data keuangan riil & rahasia, repo ini public. Isinya: invoice JBTB→client per proyek, dan tracking payout extras + grade/kelas + rekening + cashflow harian.
- **Template kontrak (Talent Release)** (`TALENT RELEASE - Fanita (1).pdf`, `TALENT RELEASE EROS.pdf` + kwitansi, `Talent Release CAMEO.docx`, `Talent Release Kinema.docx`) — **sudah dihapus dari repo** (1 September 2026): berisi PII orang beneran (nama, KTP, tanda tangan), repo ini public. Formatnya beda-beda per client — JBTB nampung format client, bukan 1 template baku.
- `prototype-jbtb-final.html` — prototype UI final (acuan visual/alur).

---

## 13. Langkah Berikutnya (status 29 Agustus 2026)

Section ini sejarahnya rekomendasi pra-coding (22 Agu) — sudah dieksekusi: MVP sudah disaring & di-Sprint (lihat §9, `DEV-NOTES.md` Session 1-9), bukan lagi rencana. Sisa kerja riil per hari ini:
1. **Modul belum dibangun**: RF-04 (validasi duplikat NIK), RF-30 (rekap margin), RF-35 (catatan Korlap), RF-38 (link grup WA) — kandidat task berikutnya, lihat `SPEC.md` untuk yang sedang aktif.
2. **Belum end-to-end tested secara menyeluruh** di luar yang sudah lewat pipeline dev→tester→reviewer (lihat DEV-NOTES per modul mana yang sudah/belum).
3. **Concern produksi §11** masih perlu klarifikasi tim bisnis (Jestika/Erlina) sebelum benar-benar launch — bukan blocking buat lanjut coding modul lain.

> Risiko scope besar (70 RF SRS lama) sudah dimitigasi — scope final ada di `BAB-3-DRAFT.md` (~52 RF, 7 aktor), bukan 70 RF SRS lama yang sudah SUPERSEDED.

---

## 14. Konvensi Coding (berlaku setiap sesi)

Dua skill wajib aktif di setiap sesi coding:

- **`/ponytail`** — shortest solution that works. Stdlib dulu, native platform dulu, hapus sebelum tambah. Enforced via hook.
- **`/caveman`** — jawaban pendek, no prose. Enforced via hook.

Lihat `CLAUDE.md` (root project) untuk ringkasan konvensi per sesi.

### 14.1 Stop-and-report sebelum eksekusi (26 Agu 2026)

Kejadian nyata: subagent verifikasi RBAC nemu bug kritis (`User::$fillable` nggak include `role`/`status`, bikin semua akun baru ke-DB jadi `super_admin`), lalu FIX DULU baru lapor — dan di jalan yang sama, langsung bikin 7 akun test baru di database asli tanpa nanya dulu. Fix bug-nya sendiri secara teknis benar (sudah diverifikasi aman dari mass-assignment sebelum diterapkan), tapi urutan kerjanya salah.

Aturan tambahan, berlaku di atas aturan eskalasi yang sudah ada di README/CLAUDE.local.md:

- **Nemu bug di modul auth/RBAC/pembayaran/kontrak saat sedang mengerjakan task LAIN** (misal task-nya "verifikasi migration", tapi ketemu bug fillable) → LAPOR DULU sebelum fix, meski fix-nya kelihatan jelas dan aman. Bug di modul ini selalu masuk kategori "subagent wajib" sesuai Root `CLAUDE.md`, dan itu berarti juga wajib dikonfirmasi ke Fakrul dulu sebelum diterapkan — bukan cuma soal siapa yang ngerjain, tapi juga soal siapa yang approve.
- **Membuat/mengubah/menghapus data di database asli** (akun user, project, dsb — bukan migration/schema) untuk keperluan testing/verifikasi → tanya dulu, sebutkan konkret apa yang mau dibuat (berapa akun, role apa, data apa), baru eksekusi. Jangan asumsikan "ini kan cuma buat testing, aman" — database dev ini juga dipakai buat demo ke tim non-teknis (Erlina, Imanisa, mitra), jadi akun asing yang muncul tiba-tiba bisa membingungkan.
- Kalau menemukan efek samping dari sebuah fix (mis. akun lama yang datanya jadi salah gara-gara bug lama) — boleh lanjut cek/diagnosa datanya (read-only), tapi JANGAN ubah data itu sampai dikonfirmasi user, sesuai yang sudah dilakukan dengan benar di kasus 26 Agu ini.
