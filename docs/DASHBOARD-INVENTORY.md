# Inventaris Dashboard 5 Role — bahan redesign

> Ditulis dari kode per 24 Oktober 2026 (HEAD `97c1f0f`, setelah CM–CQ). Tujuan: referensi untuk mendesain ulang (di tool lain atau oleh Claude Code) tanpa kehilangan fungsi. Isinya **apa yang ada sekarang**, bukan usulan. Usulan arah ada di bagian terakhir.
> File sumber: `resources/views/{super-admin,admin,client,extras}/dashboard.blade.php`, `partials/korlap-ringkasan.blade.php`, `admin/partials/tahapan.blade.php`, `super-admin/dashboard/_pengajuan|_pantau-admin.blade.php`, komponen `x-filter-periode`, `x-agenda`, `x-status-badge`, token `partials/theme-style.blade.php`.

---

## 0. Kerangka yang dipakai semua role

| Bagian | Isi | Catatan |
|---|---|---|
| **Sidebar kiri** (desktop, bisa dilipat; HP = menu bawah untuk Extras) | Menu per role (lihat tiap role). Titik merah di menu bila ada tugas/pengingat belum dibaca (CO) | |
| **Topbar** | Judul halaman, tombol tema terang/gelap, lonceng notifikasi (dropdown, "+N lainnya" → `/notifikasi`), avatar (Edit profil, Ubah kata sandi, Keluar) | |
| **Banner mode SA** | Saat Super Admin "Ambil alih sebagai Admin/Korlap" atau lihat sebagai Client/Extras: banner sticky + "Kembali ke Super Admin" | Hanya SA |
| **Pola kartu "dash-tiga"** | Tiga kartu sejajar di desktop (tumpuk di HP): **Jadwal** · **Perlu Tindakan** · **Ringkasan** | Dipakai SA, Admin, Client → ini sumber kesan "semua dashboard sama" |
| **Baris `dash-row`** | Satu baris = badge + judul tebal + teks kecil abu + "Buka →" di kanan; seluruh baris bisa diklik | Dipakai hampir di semua daftar |
| **Kartu angka `metric-card`** | Label kecil + angka besar, sebagian bisa diklik ke daftar terfilter | |
| **Filter periode** (`x-filter-periode`, CQ) | Dropdown: 7/14/30/60/90 hari terakhir, 1 tahun terakhir, 7/30 hari ke depan, "Pilih tanggal…" (dari–sampai). Diingat per halaman di session | Default: SA/Admin 30 hari terakhir; Client/Extras/Korlap ke depan |
| **Agenda** (`x-agenda`, CQ) | Hanya tanggal yang ada kegiatan, dikelompokkan per tanggal (tanda "Hari ini"): proyek, lokasi, jam (Extras tidak melihat lokasi sebelum lolos) | Pengganti kalender bulanan |
| **Keadaan kosong** | "Semua aman — tidak ada yang perlu ditindak" (centang hijau) atau kalimat abu | Gayanya belum seragam |

Badge (dari `theme-style`): hijau `badge-aktif` (beres), kuning `badge-pending` (perlu tindakan), oranye `badge-urgent` (mendesak/dadakan/bentrok), merah `badge-tolak` (gagal/ditolak), biru `badge-info` (sedang diproses), abu `badge-netral` (info/hitungan).

---

## 1. Super Admin (`/super-admin/dashboard`)

**Pertanyaan utama pengguna:** "Apa yang harus SAYA putuskan hari ini, dan Admin mana yang tertinggal?"

| # | Elemen (urutan tampil) | Isi / data | Aksi / ke mana |
|---|---|---|---|
| 1 | **3 tombol pintasan** | Perlu Tindakan (n), Persetujuan (n), Pantau Admin (n); badge oranye bila ada yang mendesak (pengajuan > 2 hari, tugas Admin > 3 hari) | Scroll ke seksi masing-masing (`#perlu-tindakan`, `#grup-pengajuan`, `#pantau-admin`) |
| 2 | **Filter periode** + teks "30 hari · 25 Sep – 24 Okt 2026" | Berlaku untuk Jadwal, Status Proyek, pertumbuhan % | Reload halaman dengan periode baru |
| 3 | Kartu **Jadwal Shooting** | Agenda periode terpilih | Baris → (tidak ada tautan khusus) |
| 4 | Kartu **Perlu Tindakan** ("semua waktu, tidak ikut filter") | 4 grup: (a) Pengajuan Client menunggu keputusan, (b) Pengajuan sudah direvisi Client, (c) Proyek disetujui belum punya PIC, (d) Akun Client belum ganti password > 7 hari | (a)(b) tiap pengajuan: ringkasan (Client, peran + jumlah + kriteria, tanggal, budget total, lampiran) + tombol **Setujui** (pilih PIC + Korlap opsional) / **Minta revisi** (catatan) / **Tolak** (alasan) dalam dialog. (c) → daftar Proyek `tanpa_client=1`. (d) → Kelola Akun terfilter |
| 5 | Kartu **Status Proyek** | 4 tab angka: Mendatang / Berjalan / Selesai / Menunggu ACC, tiap angka + ▲▼ % dibanding periode sebelumnya; panel di bawah = 5 proyek teratas tahap itu (nama, Client, rentang shooting) | Tab ganti panel (keyboard ←/→). Proyek → detail proyek (lihat saja). "Lihat semua {tahap} →", "Semua proyek →" |
| 6 | (di kartu yang sama) **Akun** | Pil per role: Super Admin / Admin / Korlap / Client / Extras + jumlah aktif | → Kelola Akun terfilter role |
| 7 | Kartu **Pantau Admin** ("semua waktu") | Per Admin aktif: daftar tugas tertunda (proyek, tugas, sejak kapan) | **Ingatkan** → dialog catatan opsional → notifikasi ke Admin PIC; setelahnya "Sudah diingatkan {waktu}" (maks 1×/24 jam) |
| 8 | Kartu **Total saat ini** ("semua periode") | Honor staf belum dibayar (Rp), jumlah invoice belum lunas | Tidak ada tautan |

Sidebar SA: Dashboard, Kelola Akun, Proyek (lihat saja), Log Aktivitas, Monitoring (Admin / Korlap / Client / Extras → pratinjau → "Ambil alih sebagai …").

---

## 2. Admin (`/admin/dashboard`)

**Pertanyaan utama:** "Di proyek SAYA, siapa yang harus saya urus sekarang?" (hanya proyek yang didelegasikan, CM)

| # | Elemen | Isi / data | Aksi / ke mana |
|---|---|---|---|
| 1 | Kartu **Jadwal Shooting** + filter periode di judul | Agenda | — |
| 2 | Kartu **Perlu Tindakan** | (a) Peringatan merah "Ada N proyek Urgent / H-3!" + paragraf penjelasan + daftar proyek urgent; (b) "Pembayaran Bermasalah: N kasus"; (c) baris ringkasan dari `AdminRingkasan` (mis. "2 Nego menunggu balasan Admin", "3 perlu ditransfer", "1 kontrak perlu TTD") — item yang diingatkan SA bertanda "Diingatkan SA" | (a) → detail proyek, "Lihat Proyek Urgent →"; (b) → halaman pembayaran/sengketa; (c) → daftar Proyek/Lineup terfilter |
| 3 | Kartu **Ringkasan** | 3 angka: Proyek Aktif, Total Pendaftar, Sedang nego; + 4 tautan: Kelola Proyek, Kelola Akun Extras & Rekap, Absensi Lapangan, Riwayat & Honor Saya | Angka → daftar Proyek terfilter |
| 4 | Kartu lebar **Tahapan Partisipasi Kandidat** | Step-bar tab per tahap (Review, Nego, Client, Kontrak, Bayar, …) dengan jumlah + "n perlu kamu"; panel = baris kandidat: foto/inisial, @username, kode proyek · peran, kalimat langkah berikutnya, "sejak …" | Baris → Lineup proyek itu di kandidat tersebut |
| 5 | Kartu **Status Pembayaran Extras** | Grafik donat (Belum dibayar / Ditransfer / Dikonfirmasi / Disengketakan) | Tidak bisa diklik |

Sidebar Admin: Dashboard · *Operasional Proyek*: Proyek · *Kelola Akun*: Extras, Client · *Lapangan*: Absensi · Riwayat & Honor.
Halaman Proyek (bukan dashboard, tapi paling sering): tab tahap, tabel baris-klik, ikon aksi (Lineup, Edit, Salin link, Buka/Tutup lowongan), tab "Semua proyek (lihat saja)".

---

## 3. Korlap (`/admin/dashboard` dengan role Korlap)

**Pertanyaan utama:** "Shooting hari ini/besok di proyek saya, siapa yang sudah hadir?"

| # | Elemen | Isi / data | Aksi / ke mana |
|---|---|---|---|
| 1 | 2 tombol atas | **Absensi Lapangan** (utama), Riwayat & Honor Saya | → `/admin/absensi`, `/admin/riwayat-kerja` |
| 2 | Kartu **Shooting hari ini & besok** | Per jadwal: proyek, lokasi, jam, Korlap; "x/y hadir · n menunggu validasi · n tidak hadir · n belum diabsen" + bar warna | Baris → Absensi proyek & tanggal itu |
| 3 | Kartu **Menunggu validasi** | Foto selfie kecil, nama Extras, proyek, jam kirim | → Absensi, langsung ke baris Extras itu |
| 4 | Kartu **Catatan lapangan terbaru** | Badge Catatan/Sanksi, isi (90 karakter), siapa → siapa, proyek, waktu | Tidak bisa diklik |
| — | Kosong | "Belum ada proyek yang ditugaskan kepadamu." | |

Halaman Absensi: filter periode (+7 default) + agenda tanggal sebagai pemilih hari, daftar peserta lolos ke atas, tombol Hadir / Tidak hadir, validasi/tolak selfie, catatan/sanksi.

---

## 4. Client (`/client/dashboard`)

**Pertanyaan utama:** "Kandidat mana yang harus saya pilih, apa lagi yang harus saya tanda tangani/isi?"

| # | Elemen | Isi / data | Aksi / ke mana |
|---|---|---|---|
| 1 | Sapaan | "Halo, {nama}." | — |
| 2 | Kartu **Jadwal Shooting** + filter (default ke depan) | Agenda proyek milik Client | — |
| 3 | Kartu **Perlu Tindakan** | (a) "N kandidat Menunggu Dipilih"; (b) per invoice: "Tanda tangan invoice"; (c) per proyek: "Isi jadwal — n tanggal belum ada lokasinya"; (d) pengajuan **Perlu revisi** (catatan JBTB) / **Ditolak** (alasan); (e) "Lengkapi profil" bila belum ada email (bisa ditutup) | (a) → Pilih Kandidat; (b) → halaman invoice; (c) → halaman jadwal proyek; (d) **Ubah** / **Ajukan Ulang**; (e) **Lengkapi** / Tutup |
| 4 | Kartu **Keputusan Pemilihan Saya** | Angka "Perlu Direview", grafik donat Dipilih vs Ditolak, tombol **Buka Pemilihan Kandidat** | → Pilih Kandidat |
| 5 | Kartu **Pengajuan Anda** | Daftar pengajuan: nama, tanggal, badge status (Draft / Menunggu ACC / Perlu Revisi / Disetujui / Ditolak) + alasan | **Ajukan Proyek Pertama** / **Ajukan Proyek Baru** |
| 6 | Kartu **Proyek Berjalan** | Nama + kode + badge tahap, batas pendaftaran | Tidak bisa diklik |
| 7 | Kartu **Peran & Pendaftar** | Tabel: Proyek · Peran · jumlah pendaftar | Tidak bisa diklik |

Sidebar Client: Dashboard, Pilih Kandidat, Jadwal · *Operasional*: Ajukan Proyek, Tagihan, Profil.
**Rahasia:** Client tidak pernah melihat nomor WA/NIK Extras, budget per orang, atau kandidat `diundang`.

---

## 5. Extras (`/extras/dashboard`) — paling panjang

**Pertanyaan utama:** "Apa langkah saya berikutnya, kapan dan di mana saya harus datang, ada lowongan baru?"

| # | Elemen | Isi / data | Aksi / ke mana |
|---|---|---|---|
| 1 | Sapaan + 1 kalimat | "Halo, {nama}! Cek lowongan casting terbaru …" | — |
| 2 | Kartu **Perlu Tindakan** (hanya bila ada) | Baris badge: Profil belum lengkap / Jadwal bentrok / Undangan proyek / Nego fee / Lengkapi KTP / Kontrak siap TTD / Konfirmasi bayar / Absen hari ini | Lengkapi (profil), Terima/Tolak (undangan, inline), Absen Selfie (dialog), atau lompat ke kartu pendaftaran ↓ |
| 3 | Seksi **Pendaftaran Saya** — satu kartu besar per pendaftaran aktif | Nama proyek + badge status + **satu tombol aksi** (Lihat Undangan / Lanjut Nego / Lengkapi KTP / TTD Kontrak / Konfirmasi Bayar / Pembayaran); badge bentrok + tombol batal; **step-bar progres** 6 tahap; tombol Batalkan keikutsertaan (lolos/kontrak); bila lolos: blok **Jam Kumpul & Info Lokasi** (jam kumpul, peran, scene/kostum, kontinuitas), blok **Absen di Lokasi** (hanya hari shooting) + dialog selfie, **Jadwal Shooting Detail** per tanggal (lokasi, jam, catatan, daftar panggilan) | Tombol aksi → halaman nego/kontrak/KTP/pembayaran/undangan; dialog batal (menjelaskan aturan H-2 dan hitungan n/3) |
| — | Bila belum pernah daftar | Kartu **Cara Kerja** (step-bar 6 langkah) + tautan lowongan | |
| 4 | `<details>` **Riwayat (n)** | Pendaftaran selesai/ditolak/batal: nama + badge | Pembayaran → / Lihat proyek → |
| 5 | Kartu **Lowongan Terbuka** | Per lowongan: nama, badge Dadakan, peran paling cocok + "% cocok" + "+n peran lain", sisa kuota, deadline | **Daftar** → detail lowongan; "Lihat semua →" |
| 6 | Kartu **Status Talenta & Linimasa Aktivitas** | Badge Grade (A/B/C atau "Belum Dinilai") + paragraf penjelasan grade + daftar aktivitas akun (judul, deskripsi, waktu relatif) | Tidak bisa diklik |
| 7 | Kartu **Status Absensi Saya** (bila ada) | Per absensi: proyek, tanggal, badge Hadir Tervalidasi / Tidak Hadir (+alasan) / Menunggu Validasi | — |
| 8 | Kartu **Jadwal Shooting** + filter (default ke depan) | Agenda | — |

Desktop ≥1100 px: kolom kiri (Perlu Tindakan, Pendaftaran, Riwayat) + kolom kanan 380 px (Lowongan, Status Talenta, Absensi, Jadwal). HP: semua menumpuk (±2.000 px).
Sidebar/menu bawah Extras: Beranda, Lowongan, Profil (+ persen kelengkapan di halaman Profil, CP).

---

## 6. Masalah yang terlihat sekarang (diagnosis jujur, untuk brief desain)

1. **Satu kerangka untuk semua role.** SA, Admin, Client memakai pola sama (Jadwal · Perlu Tindakan · Ringkasan), padahal pertanyaan utama tiap role berbeda. Yang paling penting tidak selalu di posisi pertama (Admin & Client: Jadwal di kiri atas, padahal pekerjaannya di Perlu Tindakan).
2. **Hierarki datar.** Hampir semua teks berukuran mirip, banyak teks kecil abu ("semua waktu, tidak ikut filter", paragraf penjelasan grade, peringatan urgent dua kalimat). Badge hitungan abu-abu sehingga angka penting tidak menonjol.
3. **Aksi ganda dan "Buka →" berulang.** Extras: langkah yang sama ada di Perlu Tindakan **dan** tombol kartu. Admin/SA/Client: puluhan baris berakhir "Buka →" yang sama bentuknya.
4. **Grafik yang tidak menjawab pertanyaan.** Donat status pembayaran (Admin) dan Dipilih/Ditolak (Client) tidak bisa diklik dan tidak memicu tindakan.
5. **Kartu Extras terlalu padat.** Satu pendaftaran lolos memuat status, step-bar, blok jam kumpul, blok absen, jadwal detail, tombol batal → dashboard sangat panjang di HP.
6. **Gaya tersebar inline.** Banyak `style="font-size: 12.5px; …"` langsung di view, sehingga ukuran, jarak, dan warna tidak konsisten dengan token (`--fs-*`, `--space-*`).
7. **Keadaan kosong/khusus belum seragam.** Kadang centang hijau "Semua aman", kadang kalimat abu, kadang kartu hilang; tidak ada skeleton/loading (wajar untuk Blade, tapi keadaan "belum ada data" perlu satu pola).

## 7. Batasan yang TIDAK boleh berubah saat redesign

- Aturan data: `budget_client`/`budget_total` tidak ke Extras/publik; Client tidak melihat WA/NIK/`diundang`; lokasi Extras tersembunyi sebelum lolos; grade Extras (menunggu keputusan CJ.2).
- Gerbang CM: Admin hanya beraksi di proyeknya; SA tanpa mode hanya melihat; tombol aksi hanya dirender bila `@can('kelola')`.
- Mobile 360/390 px tanpa overflow horizontal (`BrOverflowMobileTest`), target sentuh ≥ 44 px, kontras ≥ 4,5:1, tema terang & gelap.
- Semua tautan/aksi di tabel di atas harus tetap bisa dicapai (boleh pindah tempat, tidak boleh hilang).
- Stack: Blade + CSS biasa + token `theme-style`, tanpa framework UI baru (Tailwind/React) dan tanpa library chart baru.

## 8. Arah perbaikan (prinsip, bukan desain jadi)

- **Hierarki & scannability:** tiap dashboard dibuka dengan *satu* blok "Langkah berikutnya" sesuai pertanyaan utama role (bagian 1–5); angka mendesak berwarna, sisanya netral; teks penjelas dipindah ke tooltip/ikon info atau dihapus.
- **Arsitektur info & progressive disclosure:** ringkasan dulu, detail saat diklik (mis. kartu pendaftaran Extras: status + 1 tombol; jam kumpul/lokasi/jadwal detail di dalam `<details>` atau halaman proyek).
- **Defensive UX & system states:** satu pola untuk kosong / semua beres / error / diblokir (lihat saja, akun dibatasi), lengkap dengan kalimat dan aksi berikutnya.
- **Komponen & token (atomic):** `dash-row`, kartu angka, kartu tugas, agenda, empty-state jadi komponen Blade dengan varian; hapus `style=""` inline di dashboard, pakai token.
- **Beda per role:** SA = keputusan & pengawasan, Admin = antrean kerja per proyek, Korlap = hari ini di lapangan, Client = pilih & tanda tangan, Extras = langkah berikutnya + jadwal + lowongan.
