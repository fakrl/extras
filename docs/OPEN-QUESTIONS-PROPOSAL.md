# Open Questions — Proposal Project Work (SIM Casting JBTB / PT. JBTB Casting Creative Group)

> **Fungsi file ini:** daftar hal yang masih kontradiksi/belum final antara draft proposal terbaru (Google Doc, direvisi 20 Agu 2026) vs keputusan lama di `CLAUDE.md`. Ditemukan pas cross-check sesi 20 Agu 2026. Perlu diselesaikan ke tim (Imanisa, Erlina) dan/atau ke JBTB (Jestika/Erlina selaku CFO) sebelum lanjut nulis BAB III (Analisis Kebutuhan & Metode Pengembangan) — biar nggak nulis dua versi requirement yang beda lagi.

---

## Ke JBTB (Jestika / Erlina selaku CFO)

### 1. Komisi 25% — ✅ RESOLVED
Dikonfirmasi Erlina (21 Agu 2026): **aman, boleh dipublikasikan apa adanya**, nggak rahasia. Catatan `CLAUDE.md` soal margin "rahasia, admin-only, variatif per kepala" berarti berlaku buat **data operasional harian di sistem** (yang emang harus admin-only karena beda per kepala/nego), bukan buat **angka komisi standar 25%** yang dipublikasikan di paper sebagai kebijakan umum agensi. Dua hal beda level — nggak kontradiksi.

### 2. Skala real vs skala di proposal — ✅ RESOLVED, ikutin angka proposal
Keputusan (20 Agu 2026): pakai angka di proposal ("50–80 extras aktif, 4–5 proyek/bulan") apa adanya — itu yang diketik Erlina (manager JBTB, sumber data valid dari lapangan). Kemungkinan angka Excel (`Kado Untuk Ibu` = 30+ extras/hari × 9–13 hari) itu cuma proyek besar/puncak, bukan rata-rata bulanan. **Bab 3 (Analisis Kebutuhan) harus konsisten pakai angka 50–80/4-5 ini**, jangan dicampur sama angka proyek besar tadi biar nggak ada selisih data lagi di mata penguji.

---

## Ke Tim (Imanisa, Erlina) + Dosen Pembimbing

### 3. WhatsApp — Gateway API resmi vs whatsapp-web.js (unofficial)
Dosen pembimbing arahannya pakai WhatsApp Gateway (notif otomatis: konfirmasi apply, hasil seleksi, reminder H-1, kontrak siap TTD). Fakrul belum kebayang implementasinya, sempat mikir pakai `whatsapp-web.js` (library yang sama kayak di project Hermes).

Trade-off yang perlu dipertimbangkan bareng tim, **bukan diputusin sepihak**:
| | Layanan gateway berbayar (Fonnte/Wablas dkk) | `whatsapp-web.js` (self-hosted) |
|---|---|---|
| Cara kerja | HTTP API resmi dari provider, tinggal POST dari Laravel | Puppeteer + sesi WA Web sendiri, butuh proses Node terpisah yang harus nyala terus |
| Biaya | Berbayar (langganan bulanan, biasanya puluhan-ratusan ribu) | Gratis |
| Risiko | Rendah — provider yang nanggung risiko ToS | **Nomor bisa kena banned** WA (melanggar ToS WhatsApp), sesi bisa putus, perlu maintain proses 24/7 terpisah dari Laravel |
| Kompleksitas arsitektur | Simpel — cuma HTTP call | Nambah runtime kedua (Node) di samping Laravel, integrasi antar servis |
| Kesesuaian scope solo dev + AI, 2–4 bulan | Lebih aman buat timeline sempit | Effort ekstra pas resource udah ketat (lihat §"Risiko #1 = scope" di `CLAUDE.md`) |

- **Status:** ✅ Arah diputuskan (20 Agu 2026) — fakrul milih **gateway berbayar (Fonnte/Wablas dkk), bukan whatsapp-web.js**. Alasan yang benar: risiko banned itu nyata dan naik drastis kalau volumenya "notifikasi" (bukan cuma auto-reply ke beberapa orang) — kirim ke banyak nomor berbeda yang belum pernah chat duluan sama nomor pengirim itu pola yang paling gampang kena flag anti-spam WhatsApp. Ini keputusan yang tepat, bukan cuma soal familiar/nggak.
- **Mitigasi tambahan yang perlu masuk desain:**
  1. Pakai nomor khusus buat notifikasi sistem — jangan nomor pribadi/nomor yang juga dipakai admin nego manual.
  2. WA tetap jadi kanal **pelengkap**, bukan primer — email tetap kanal utama/resmi (ini malah udah rekomendasi di `Business Information.md` sendiri §7). Ini otomatis nurunin volume WA, jadi risiko makin kecil.
  3. Kalau mau lebih aman lagi tapi effort/biaya naik: WhatsApp Business API resmi (via provider kayak 360dialog/Twilio) — perlu verifikasi bisnis + approval template pesan. Kemungkinan **kebanyakan overhead buat timeline solo dev 2-4 bulan**, jadi rekomendasi: Fonnte/Wablas dulu cukup, upgrade ke API resmi kalau sistem udah jalan lama & scale-nya beneran gede.

### 4. Peran Erlina (CFO JBTB) di proposal — ✅ RESOLVED
Diklarifikasi fakrul (20 Agu 2026): **Erlina emang bagian resmi kelompok project work** (mahasiswa terdaftar), sekaligus salah satu manager di JBTB — makanya dia yang paling paham kondisi lapangan buat nulis Bab 2 (Profil Mitra). Tim = **Fakrul + Imanisa + Erlina** (3 orang, masih sesuai syarat max-3).
- Nggak ada masalah administratif — dia posisinya rangkap (mahasiswa tim + orang dalam mitra), bukan pihak luar yang nulis bagian akademik. Ini malah nilai plus buat akurasi data mitra di Bab 2.
- Tetap perlu dipastikan: apakah statusnya sebagai manager JBTB sudah dilaporkan resmi ke dosen pembimbing/prodi (biar nggak dianggap conflict of interest yang nggak diungkap saat sidang nanti — cukup diomongin di awal, bukan disembunyikan).

### 5. Revisi teks Batasan Masalah Bab 1 — poin akun Client/PH — ✅ RESOLVED, tinggal rapiin istilah
Diklarifikasi fakrul (20 Agu 2026) — ternyata teks draft Batasan Masalah **udah benar**, cuma istilahnya ambigu kalau dibaca sepintas:
- **PH (Production House/brand/perusahaan)** = entitas bisnis, betul **tidak punya akun sama sekali**. Kebutuhan casting-nya diwakilkan lewat orangnya.
- **Casting Director (CD)** = orang yang milih/approve extras (mewakili PH), **PUNYA akun** — tapi daftar lewat **link registrasi terpisah** dari link Extras (bukan satu form self-register yang sama; siapa pun yang daftar lewat link itu otomatis dapat role Casting Director).
- Jadi nggak ada kontradiksi sebenernya — "PH tanpa akun" dan "CD punya akun" itu dua hal yang beda level (perusahaan vs individu representasinya).
- **Action tersisa:** di penulisan Bab 1/3, pastiin istilah **"PH"** dan **"Casting Director"** dipakai konsisten dan nggak ketuker (jangan nulis "PH login ke sistem" — yang login itu CD-nya, bukan PH sebagai entitas). Ini gampang bikin penguji bingung/salah tangkep kalau nggak dipertegas dari awal.

### 6. Tanda tangan kontrak — canvas signature vs upload scan — ✅ RESOLVED
**Keputusan (20 Agu 2026): pakai canvas signature** (gambar tanda tangan digambar langsung di browser pakai Signature Pad JS, disematkan ke PDF). `CLAUDE.md` §10/§6 perlu diupdate — bukan lagi "upload scan", tapi canvas drawing. Catatan implementasi: tetap "tidak tersertifikasi" secara hukum (bukan e-signature PSrE), sama seperti rencana awal — cuma mekanisme input tanda tangannya yang beda (gambar langsung vs upload file).

### 7. Fitur "deteksi konflik jadwal otomatis" — desain solusi (dibahas 20 Agu 2026)
Disebut di draft Bab 1 (Tujuan #2) sebagai fitur baru — belum ada di backlog `CLAUDE.md` lama. Setelah ditelaah, ini **nggak butuh scope besar** — cukup query overlap tanggal, bukan constraint solver rumit. Opsi desain:

- **Data model:** event/project butuh tabel tanggal shooting eksplisit (`event_shooting_dates`: event_id, tanggal) — bukan cuma kolom start/end — karena shooting bisa tanggal-tanggal terpisah (bukan rentang berurutan), sesuai temuan data riil (`Kado Untuk Ibu` = 9–13 hari shooting, kemungkinan nggak berurutan).
- **Titik pengecekan konflik (2 checkpoint, bukan blokir total):**
  1. **Saat extras apply** ke lowongan baru → sistem cek: apakah extras ini punya *engagement aktif* (status Deal/Lolos, bukan Ditolak/Dibatalkan) di event lain yang tanggalnya overlap? Kalau ya → tampilkan warning ke extras ("kamu udah ada jadwal di project lain tanggal ini, yakin lanjut daftar?"), **tidak** hard-block (karena bisa aja extras memang berniat batalin komitmen lama).
  2. **Saat admin present ke CD / approve** → admin lihat badge "⚠️ bentrok jadwal" di profil pendaftar kalau overlap terdeteksi, supaya admin nggak asal approve dan bikin overbooking tanpa sadar. Admin yang putuskan lanjut/tolak dengan info lengkap.
- **Kenapa soft-warning, bukan hard block:** lebih fleksibel buat kasus nyata (extras minta di-release dari komitmen lama, atau overlap cuma sebagian hari bukan bentrok penuh), dan konsisten sama alur "3x cancel mendadak → Melanggar" yang udah ada — fitur ini malah bantu **kurangi** kasus pembatalan mendadak itu, bukan nambah proses baru yang berat.
- **Status:** desain awal siap didiskusikan ke tim — kalau disetujui, ini masuk MVP karena effort implementasinya kecil (1 query + 2 badge UI), sejalan sama pain point inti (bukan fitur tempelan).

---

### 8. Model Negosiasi Fee — ✅ RESOLVED (20 Agu 2026): mekanisme ala InDrive
Dokumen requirement lama (`01-Kebutuhan-Fungsional-NonFungsional.md`, RF-17–RF-20) pakai model **fee fixed + 1x pengajuan alternatif**. `CLAUDE.md` (lebih baru) pakai model **Grade terpisah dari fee dinamis via WA**. Keduanya di-supersede oleh keputusan hari ini:

- **Grade (A/B/C)** tetap independen dari fee — penilaian kualitas admin saat seleksi (sesuai `CLAUDE.md`).
- **Fee = tawar-menawar bertingkat (multi-round), model ala InDrive:** Admin ajukan penawaran awal (dari rate card + budget client) → Extras **Terima** atau **Counter** (jumlah beda) → Admin **Terima** atau **Counter balik** → berulang sampai salah satu Terima (=Deal) atau salah satu mundur/reject. **Tidak dibatasi 1x** seperti model lama.
- Setiap ronde penawaran/counter **tercatat** (siapa ngajuin, berapa, kapan) — jadi dasar kuat buat klaim "kesepakatan final" pas ada sengketa, lebih kuat daripada nego lewat WA yang nggak ke-log.
- Ini **menggantikan** RF-17 s/d RF-20 di baseline lama. Perlu update juga ke `CLAUDE.md` §6 (state machine) & §9 (backlog) supaya semua dokumen selaras — belum dilakukan, dicatat sebagai TODO.

### 9. Payment gateway (Midtrans/Tripay) — ✅ RESOLVED, tetap TIDAK pakai
Dikonfirmasi (21 Agu 2026): tetap pakai bukti transfer manual, **bukan** payment gateway. Alasan yang benar (instinct fakrul udah pas): (1) gateway kayak Midtrans/Tripay selalu ada biaya (MDR/fee per transaksi), nggak ada versi gratis buat transaksi produksi; (2) produk ini didesain buat **nerima** pembayaran dari customer (kartu/VA/e-wallet), bukan buat **disbursement/payout** ke banyak rekening extras — itu produk beda (Payout API), biasanya tier bisnis lebih tinggi + KYC ribet; (3) nambah ini = ngebalikin keputusan lama yang udah bener (sistem nggak proses uang, cuma catat status) dan nambah cost yang nggak sepadan buat scope solo-dev + timeline akademik. Model bukti-TF-manual (RF-28/29 di Bab 3) sudah cukup buat matiin pain point utama (transparansi status bayar).

### 10. Jumlah modul — "minimal 10" itu bukan aturan resmi kampus
Fakrul nanya: apa Panduan Proposal kampus mensyaratkan minimal 10 modul? **Jawaban: nggak ada** — sudah dicek langsung ke `PANDUAN PROPOSAL PROJECT WORK.pdf` (Bab I-IV resmi prodi), isinya cuma syarat struktur 3 BAB (Pendahuluan, Profil Mitra, Analisis Kebutuhan+Metode) dan kriteria topik (relevansi/keberlanjutan/keterbaruan/manfaat) — **tidak ada** ketentuan jumlah modul minimum di mana pun.

Angka "10 modul" itu asalnya dari dokumen internal tim sendiri (`Requirement Baseline Summary.md` — 10 modul: Autentikasi&Akun, Profil Extras, Manajemen Proyek, Pendaftaran&Seleksi, Negosiasi Fee, Kontrak Digital, Pembatalan, Notifikasi, Dashboard, Laporan/Rekap). Jadi itu preferensi/self-organisasi lama tim, bukan aturan kampus.

**Update (21 Agu 2026) — udah keliatan diagram final Kerangka Berpikir Bab 2**, 7 modul jelas: Profil & Verifikasi Extras · Manajemen Proyek & Slot Kebutuhan · Apply Proyek + Deteksi Konflik Jadwal · Review & Approval Casting Director · Kontrak Digital (TTD Canvas+PDF) · Keuangan & Invoice Proyek · Integrasi Notifikasi WhatsApp Gateway.

🔴 **Tapi ketemu gap nyata, bukan cuma soal angka:** **Modul Negosiasi Fee (Grade + tawar-menawar ala InDrive) nggak ada di 7 modul ini** — padahal itu fitur paling distinctive yang baru kita desain detail (lihat §8). Kemungkinan keasumsikan nempel di "Review & Approval CD" atau "Keuangan & Invoice", tapi nggak eksplisit. **Action:** minta Imanisa/Erlina nambahin modul ke-8 "Negosiasi Fee & Grade" di diagram Bab 2, biar Bab 3 (yang bakal eksplisit nulis RF-nya) match sama diagramnya sendiri.

### 11. Sistematika Penulisan (Bab 1.6) masih pakai struktur 5-BAB — perlu diperbaiki
Draft Google Doc §1.6 masih nulis struktur **5 BAB** (I Pendahuluan, II Landasan Teori, III Metodologi Penelitian, IV Hasil dan Pembahasan, V Penutup) — itu struktur **Laporan Akhir**, bukan **Proposal**. Panduan resmi kampus untuk PROPOSAL cuma **3 BAB**: I Pendahuluan, II Profil Mitra dan Kerangka Berpikir, III Analisis Kebutuhan dan Metode Pengembangan. Perlu direvisi sebelum submit — kalau nggak, penguji proposal bisa bingung liat sistematika yang nggak sesuai formatnya sendiri.

### 12. Draft doc ada konten dobel/nggak sinkron — perlu bersih-bersih sebelum final
Ditemukan pas cross-check 21 Agu 2026: Google Doc-nya masih nyimpen **dua versi Latar Belakang** dan **dua versi Batasan Masalah** yang beda isi (draft lama pendek vs draft baru lebih detail+kuantitatif), plus ada section **"Metodologi Penelitian"** yang ditulis di bawah Bab 1.6 padahal isinya (metode pengumpulan data, Agile/Scrum, UML, Black Box) itu harusnya masuk **Bab 3.2** (yang lagi kita tulis). Sebelum proposal final: hapus draft lama yang udah kesuperseded, dan pindahin "Metodologi Penelitian" itu supaya nggak dobel sama draft Bab 3 di sini.

### 13. Kajian Penelitian Terdahulu masih pakai placeholder "[Nama Peneliti, tahun]"
Ada 3 studi acuan yang formatnya masih ditandain `[Nama Peneliti, tahun]` (belum diisi nama/jurnal asli) — ini WAJIB diganti sumber beneran (bukan dikarang) sebelum submit, karena kalau kecantum di paper yang dipublikasikan, sitasi fiktif itu masalah integritas akademik. Tab 1 udah ada to-do buat cari 3 jurnal di Google Scholar (kata kunci: "sistem informasi talent agency", "e-casting system web", "digitalisasi agensi kreatif Indonesia") — tinggal dieksekusi, gua nggak bisa nyariin/mastiin isi jurnal asli pihak lain di sini.

### 14. Rumusan Masalah 7 poin — worth didiskusikan lagi
Ada catatan pribadi tim di draft: *"Rumusan masalah nya 7 kebanyakan ga sih gais wkwkw"* — gua sependapat itu perlu dipikir ulang. Poin ke-6 (notifikasi WA) sebenernya fitur pendukung, bisa digabung ke poin kontrak/keuangan. Poin ke-7 (Agile/Scrum sebagai rumusan masalah tersendiri) agak nggak lazim secara akademik — metode pengembangan biasanya jadi **cara menjawab** rumusan masalah, bukan rumusan masalah itu sendiri. Ini bagian Imanisa (Bab 1) — sampaikan sebagai bahan diskusi tim, bukan keputusan sepihak dari sini.

---

## Hasil Bimbingan Dosen Pembimbing (21-22 Agu 2026) — 12 poin scope tambahan

Dosen pembimbing menambah scope signifikan pas bimbingan: aktor baru (RBAC diperluas) + modul manajemen karyawan. Semua dibahas & diputuskan di sesi ini.

### 15. Aktor tambahan — Super Admin + 3 sub-role Admin — ✅ RESOLVED
Struktur RBAC baru (7 aktor total, dari 3):
- **Super Admin** (Jestika, owner) — dashboard monitoring/analitik only, nggak megang operasional harian. Bisa nambah akun Admin + tentuin sub-role spesifiknya + set nominal honor saat rekrut.
- **Admin Default** (bisa lebih dari 1 akun) — tetap seperti Admin lama, full capability.
- **Talco (Talent Coordination)** — cabang Admin Default, ditugaskan per proyek kalau perlu. **Zero functional footprint di sistem** (objek kerjanya, talent utama, di luar batasan sistem) — cuma jadi entri role+log buat keperluan honor, muncul di dashboard monitoring Super Admin. Dapet akses login read-only lihat riwayat kerja & status honor sendiri.
- **Korlap (Koordinator Lapangan)** — cabang Admin Default, ditugaskan per proyek kalau perlu. **Ada fitur fungsional beneran**: absensi Extras di lapangan, catatan/sanksi Extras.
- **Sosial Media/Multimedia** — dikonfirmasi ini sama dengan "Multimedia" yang disebut dosen (bukan dua role beda, cuma beda penyebutan/typo). Sama seperti Talco: zero functional footprint, role+log only, akses login read-only.
- **Konfirmasi eksplisit fakrul:** Talco & Sosmed nggak pernah didrop dari scope (sempat ambigu di sesi sebelumnya) — keduanya **tetap ada** dengan akses login read-only "Riwayat Kerja & Status Gaji Saya".

### 16. Modul Manajemen Karyawan, Absensi, Penggajian Staf — ✅ RESOLVED
- Karyawan (Talco/Korlap/Sosmed) **bukan karyawan tetap** — dibayar per-event/proyek, bukan gaji bulanan. Model konseptual sama seperti fee proyek-basis Extras.
- **Absensi disederhanakan** jadi log aktivitas sistem yang nempel ke status "project selesai" — bukan geolocation/foto check-in. Ini yang nentuin kelayakan honor.
- **Penggajian**: nominal di-set/diadjust Super Admin pas rekrut sub-admin (standing rate, bisa diubah lagi nanti) — bukan auto-calculated, bukan payroll formal dengan potongan pajak/BPJS.
- **Add-on/reimburse**: line-item manual (label bebas + nominal) yang bisa ditempelin ke pembayaran Extras ATAU honor staf — misal reimburse transport/penginapan. Ditambah manual sesuai kebutuhan, bukan field wajib terstruktur.
- **Riwayat Kerja** berlaku ke SEMUA tipe Admin (Default, Talco, Korlap, Sosmed) secara uniform — karena semua tetap dibayar sama Jestika, riwayat proyeknya jadi basis honor masing-masing.
- **Slip Gaji Staff (PDF)** — ✅ diputuskan MASUK SCOPE (22 Agu 2026): auto-generate PDF pas project berstatus Selesai, reuse infrastruktur PDF kontrak/invoice yang udah ada. Ini langsung jawab pain point riil yang Erlin ceritain: JBTB belum punya slip gaji, staf sering "kira-kira" doang nominal yang bakal didapat — mirroring persis masalah transparansi fee yang jadi value prop awal sistem buat Extras.

### 17. Bab 3 — Landasan Teori TIDAK jadi sub-bab baru — ✅ RESOLVED
Dikonfirmasi fakrul: struktur resmi panduan (3.1 Analisis Kebutuhan, 3.2 Metode Pengembangan, 3.3 Timeline) tetap dipertahankan apa adanya. Sitasi teori dianyam ke dalam prosa yang udah ada sebagai penjelasan pendukung (biar sumbernya bisa masuk Daftar Pustaka), bukan didirikan sebagai section teori berdiri sendiri.

### 18. Timeline 3.3 — hanya proposal-stage, BUKAN sprint dev — ✅ RESOLVED
Timeline di Bab 3 Proposal cuma nyakup proses pendaftaran sempro → sidang judul (September 2026) — bukan 5/6 sprint pengembangan. Tanggal: **pendaftaran sempro 1 September 2026 (fix)**, **sidang judul ~3 September 2026 (bisa ngaret, tergantung jadwal dosen pembimbing — dicatat sebagai estimasi bukan tanggal pasti)**. Timeline sprint pengembangan pindah total ke dokumen Laporan Akhir (Bab I.6 Timeline, sesuai struktur `PANDUAN LAPORAN PROJECT WORK.pdf`) — baru relevan setelah sidang judul di-acc.

Referensi tambahan dari contoh kating (`LEMBAR PERSETUJUAN PROJECT WORK FIX.pdf`): template resmi timeline Laporan Akhir formatnya tabel Gantt (kegiatan × bulan × PIC) dimulai dari tanggal kontrak kerja sama (IA/MoA) dengan mitra. Realitanya di contoh kating itu, target formal "6 bulan" ternyata meleset jadi ~10-11 bulan riil (dilihat dari rentang tanggal kartu bimbingan) — jadi wajar kalau nanti timeline dev beneran nggak pas 100% sama target di dokumen resmi.

### 19. Legalitas PT JBTB — ✅ RESOLVED, dikonfirmasi aman
`PANDUAN LAPORAN PROJECT WORK.pdf` (Bab II.1 Batasan Studi Kasus) mensyaratkan mitra WAJIB berbadan hukum PT dengan izin usaha resmi — UMKM tidak diperbolehkan jadi mitra project work. Dikonfirmasi fakrul (22 Agu 2026): **JBTB memang sudah berbadan hukum PT beneran, aman**. Ini jadi alasan kenapa penulisan konsisten pakai "PT. JBTB Casting Creative Group" + sebut NIB/OSS di profil mitra Bab 2 — bukan cuma gaya penulisan, itu emang syarat wajib yang harus match sama kondisi riil.

### 20. Item tambahan dari Panduan Laporan yang perlu masuk radar (belum urgent, dicatat buat nanti)
Ditemukan pas baca `PANDUAN LAPORAN PROJECT WORK.pdf` (relevan setelah sempro lolos, dicatat dari sekarang biar nggak kelewat):
- **HKI wajib** — project work punya kewajiban luaran tambahan tercatat di HAKI (Hak Kekayaan Intelektual), bukan opsional.
- **Minimal 8 kali bimbingan** tercatat & divalidasi via sistem akademik.
- **Minimal 4 sertifikat kegiatan ilmiah/Seminar Nasional** — syarat kelulusan personal per mahasiswa, di luar project itu sendiri.
- **Batasan teknis minimum sistem** (Bab IV Panduan Laporan): minimal 2 modul terintegrasi, minimal 5 tabel database (di luar tabel user), minimal 50 entri data (dummy oke kecuali topik SPK/ML), use case minimal 3 aktor — **sistem JBTB udah jauh melebihi semua ini, aman, nggak perlu tindakan tambahan**.
- Laporan Akhir formatnya **4 BAB** (beda dari proposal 3 BAB): Pendahuluan → Analisis dan Perancangan (ERD/LRS/spek DB/UML lengkap) → Implementasi dan Penggunaan (instalasi, cara pakai, troubleshoot) → Kesimpulan.
- Hardcover laporan akhir: warna coklat `#341d08`, tulisan emas, font Tahoma.
- Biaya UAPS bertingkat mulai semester 8 (Rp1.000.000) + biaya keterlambatan Rp1.000.000/semester mulai semester 10+ kalau belum lulus.

---

## Cara pakai file ini
Update status tiap poin begitu ada keputusan (dari diskusi tim/dosen/JBTB). Kalau semua udah kelar, isi keputusan final ke `CLAUDE.md` bagian Decision Log, dan file ini bisa diarsipkan.
