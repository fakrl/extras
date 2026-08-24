# Ringkasan Keputusan — Project Work SIM Casting JBTB

> Dibuat 22 Agustus 2026, buat Imanisa & Erlina. Ini rangkuman hasil diskusi Fakrul + Claude pasca bimbingan dosen pembimbing. Kalau mau detail teknis lengkap, ada di `BAB-3-DRAFT.md` (draft resmi Bab 3) dan `OPEN-QUESTIONS-PROPOSAL.md` (riwayat semua keputusan + alasannya).

## 1. Scope tambahan dari bimbingan dosen — udah difinalkan

Dosen minta nambah aktor & modul. Setelah didiskusikan, ini yang disepakati:

**Aktor baru (dari 3 jadi 7):**
- **Super Admin** (Jestika) — cuma monitoring/dashboard analitik, nggak pegang operasional harian. Bisa nambah akun Admin + tentuin sub-role-nya + set nominal honor.
- **Admin Default** — tetap kayak Admin lama (operasional penuh), sekarang bisa lebih dari 1 akun.
- **Admin - Talco (Talent Coordination)** — role tambahan opsional per proyek, ngurusin talent utama (bukan Extras). Nggak ada fitur sistem, cuma pencatatan role+riwayat kerja buat keperluan honor.
- **Admin - Korlap (Koordinator Lapangan)** — role tambahan opsional per proyek, ngurusin Extras di lokasi (absensi, catatan/sanksi).
- **Admin - Sosial Media/Multimedia** — sama kayak Talco, cuma pencatatan role+log, nggak ada fitur sistem.
- Extras dan Casting Director tetap seperti sebelumnya.

**Modul baru:**
- Manajemen Karyawan (Super Admin nambah Admin/sub-role + set honor)
- Absensi Staf (simple: log aktivitas nempel ke status "proyek selesai", bukan geolocation/foto)
- Penggajian Staf per-event + slip honor PDF otomatis (ini jawab masalah nyata: JBTB belum punya slip gaji, staf sering "kira-kira" nominal yang didapat — Erlin yang cerita ini)
- Riwayat Kerja — berlaku ke semua tipe Admin

## 2. Timeline — dipisah jadi 2 dokumen berbeda

Ini yang sempat bikin bingung, sekarang udah jelas:
- **Timeline di Proposal (Bab 3.3)** cuma nyakup proses sempro: pendaftaran 1 September 2026, sidang judul ±3 September 2026 (bisa ngaret tergantung jadwal dosen pembimbing).
- **Timeline pengembangan sistem** (5-6 sprint) pindah total ke dokumen Laporan Akhir nanti (Bab 1.6), baru relevan setelah sidang judul di-acc. Jangan dicampur ke Proposal.

## 3. Legalitas PT — sudah dikonfirmasi aman

Panduan Laporan kampus mensyaratkan mitra wajib PT resmi (UMKM nggak boleh). JBTB sudah confirmed berbadan hukum PT — nggak ada masalah di sini.

## 4. Landasan Teori — bukan sub-bab baru di Bab 3

Struktur resmi Bab 3 (3.1 Analisis Kebutuhan, 3.2 Metode Pengembangan, 3.3 Timeline) tetap dipertahankan. Sitasi teori dianyam ke prosa yang udah ada (biar tetap masuk Daftar Pustaka), bukan bikin section teori berdiri sendiri.

## 5. Pembagian tugas tim (buat Bab 1 — bagian Imanisa)

Disepakati sebelumnya:
- **Fakhrul Mukhlisin** — Project Lead & Lead Developer (nulis Bab 3, bangun sistem)
- **Imanisa** — Business Analyst & QA Lead (nulis Bab 1, analisis kebutuhan & pengujian)
- **Erlina Stepani Gultom** — Business Liaison & UAT (nulis Bab 2, juga Product Owner bareng Jestika buat validasi kebutuhan bisnis)

## 6. Yang perlu ditindaklanjuti tim (belum kelar, action item)

- Ganti placeholder sitasi `[Nama Peneliti, tahun]` di Kajian Penelitian Terdahulu dengan sumber jurnal asli (jangan dikarang) — cari di Google Scholar, kata kunci: "sistem informasi talent agency", "e-casting system web", "digitalisasi agensi kreatif Indonesia".
- Bersihkan draft Google Doc: masih ada 2 versi Latar Belakang & Batasan Masalah yang beda isi (draft lama vs baru), dan section "Metodologi Penelitian" yang harusnya masuk Bab 3.2, bukan nempel di bawah 1.6.
- Perbaiki Sistematika Penulisan (Bab 1.6): masih nulis struktur 5-BAB (versi Laporan Akhir), padahal Proposal itu 3-BAB (I Pendahuluan, II Profil Mitra, III Analisis Kebutuhan+Metode).
- Rumusan Masalah ada 7 poin — worth dipertimbangkan ulang apakah semua perlu jadi rumusan masalah tersendiri, atau ada yang bisa digabung (misal Agile/Scrum lebih tepat sebagai metode menjawab masalah, bukan masalah itu sendiri). Ini keputusan Imanisa sebagai penulis Bab 1.
- Tambahin diagram Bab 2 (Kerangka Berpikir) dengan modul "Negosiasi Fee & Grade" — sempat ketinggalan dari 7 modul yang digambar, padahal ini fitur paling distinctive di sistem.

## 8. Dokumen teknis buat development (baru, disiapkan dari sekarang meski belum sempro)

Selain dokumen akademik, sekarang juga ada dokumen teknis buat persiapan build (belum mulai coding, cuma disiapkan dulu supaya begitu sempro di-acc bisa langsung jalan):

- `PRD-LITE.md` — ringkasan tujuan, core features MVP, user flow (distilasi dari Bab 3, dipakai biar nggak perlu baca seluruh Bab 3 tiap mau generate 1 fitur)
- `TECH-STACK.md` — stack wajib, struktur folder, coding convention
- `DATABASE-SCHEMA.md` — blueprint tabel database dari semua RF
- `UI-GUIDELINES.md` — design system, warna, behavior rules
- `CLAUDE.local.md` — workflow tim development (siapa loop-in siapa untuk keputusan apa) — gitignored, nggak masuk repo publik
- `SPEC.md` — template kosong, diisi per-modul begitu development mulai

Ini murni buat kebutuhan build sistem — nggak perlu dibaca buat keperluan penulisan proposal.

## 7. Catatan buat kelulusan (di luar konten project, tapi wajib dipenuhi tiap mahasiswa)

Dari Panduan Laporan Project Work:
- Minimal 8 kali bimbingan tercatat di sistem akademik.
- Minimal 4 sertifikat kegiatan ilmiah/Seminar Nasional per mahasiswa.
- Wajib registrasi HAKI buat sistem yang dibangun (bukan opsional) — biasanya diurus mendekati akhir project.
