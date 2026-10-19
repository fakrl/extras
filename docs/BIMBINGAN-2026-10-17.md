# Catatan Bimbingan / Review Tim — 17 Oktober 2026

> Sumber: chat grup (Erlina = Product Owner JBTB, Imanisa = QA). **Belum jadi task SPEC**, masih bahan diskusi Fakrul. Bagian 1 = catatan mentah apa adanya; bagian 2 = tafsiran manager + kondisi sistem sekarang; bagian 3 = yang perlu ditanyakan balik.

---

## 1. Catatan mentah

**Erlina, 14.58 — Dashboard Super Admin**
1. Button yang paling bawah pindah ke atas, tambahi button perlu tindakan jadi ada 3 button; pas diklik masing-masing langsung nge-scroll ke bawah ke tujuan button; beri warna highlight tanda urgent.
2. Perlu tindakan: button ACC ganti ke "Proses", masuk ke form edit project; setelah diisi ganti jadi tag "project berjalan".
3. (kosong)

**Imanisa, 15.07 — Extras**
1. Edit profil: button Simpan dan Batal terlalu banyak, dijadikan satu saja.
2. Persentase progres kelengkapan profil.

**Erlina, 15.51** — Fungsi per role.

**Erlina, 16.52 — Dashboard**
1. Filter per tanggal: gambar tanggal dihapus, ganti ke filter list dropdown. Button kalender klik (opsional).
2. Perlu tindakan dibuat jadi pengelompokan.
3. Warna button dibedakan mana yang urgent mana yang enggak.
4. Super admin cuma nerima budget.
5. Tampilkan username di log aktivitas.
6. Monitoring admin nggak perlu melakukan tindakan apa-apa.
7. Tambahkan menu approval di dashboard.
8. Tambahkan menu remind di dashboard.

Scope Super Admin:
1. Monitoring akun.
2. Manajemen akun client + staf (bisa diatur admin).
3. Dealing sama client.
4. Remind kegiatan dari dashboard (tindakan tidak perlu dilakukan super admin).

**Erlina, 17.05 — Staf (Admin/Korlap)**
1. Buat sesuai jobdesk-nya saja.
2. Filtering.
3. Jika super admin remind, masuk ke admin yang mengerjakan project-nya.
4. Ada informasi project: extras-nya siapa saja.
5. Di extras ada informasi project apa saja yang pernah dikerjakan.
6. Rekap project yang pernah di-lock buat di super admin.

Scope Staf:
1. Fokus satu project.
2. Manajemen extras.
3. Tindakan sesuai task.
4. Tanggal (remind callingan).
5. Ajukan extras.
6. Pilih grade.
7. Transfer gaji.
8. Buat lineup.

---

## 2. Tafsiran + kondisi sekarang

Benang merahnya: **Super Admin = pengawas/owner (memantau, menyetujui, mengingatkan, urusan budget dengan Client), bukan pelaksana. Admin = pelaksana per proyek.** Sekarang sistem memberi SA "godmode" (bisa melakukan semua aksi Admin, termasuk lewat mode "lihat sebagai Admin"). Ini pergeseran peran, bukan sekadar tampilan.

| # | Catatan | Tafsiran manager | Kondisi sekarang | Jenis |
|---|---|---|---|---|
| SA-1 | 3 tombol di atas, klik scroll ke bagian, highlight urgent | Dashboard SA dibuka dengan 3 tombol pintasan (mis. Perlu Tindakan / Approval / Remind) yang lompat ke seksinya, yang urgent diberi warna | Tombol pintasan ada di bawah; isi 3 tombol belum jelas | Tampilan |
| SA-2 | ACC → "Proses" → form edit → tag "berjalan" | Brief Client tidak langsung di-ACC; SA klik Proses, melengkapi proyek (peran, tanggal, PIC), simpan = disetujui, tampil sebagai "Berjalan" | ACC tombol langsung; sejak CI.2.3 ACC ditolak bila peran/tanggal kosong | Alur kecil |
| EX-1 | Simpan/Batal terlalu banyak | Profil per bagian (CL) punya Simpan/Batal di tiap kartu → dirasa ramai | Hasil CL 16 Okt | Tampilan, **bertentangan dengan desain CL** |
| EX-2 | Persentase kelengkapan profil | "Profil 70% lengkap" + progress bar | Ada status "siap melamar / kurang X" tanpa persen | Tampilan |
| R-1 | Fungsi per role | Dokumen/penjelasan fungsi tiap role | Sudah ada `docs/RINGKASAN-SISTEM.md` (17 Okt) | Dokumen |
| D-1 | Filter tanggal jadi dropdown, kalender opsional | Ganti tampilan kalender di dashboard jadi filter periode dropdown (Hari ini/Minggu ini/Bulan ini), kalender boleh lewat tombol | Kalender bulanan tampil di dashboard | Tampilan |
| D-2 | Perlu tindakan dikelompokkan | Dikelompokkan per jenis atau per proyek | Daftar campur | Tampilan |
| D-3 | Warna urgent vs tidak | Tombol/item urgent beda warna | Sebagian sudah (`badge-urgent` oranye) | Tampilan |
| D-4 | SA cuma nerima budget | **Belum jelas** (lihat pertanyaan) | — | ? |
| D-5 | Username di log aktivitas | Tambah kolom/teks `@username` | Log tampil nama + deskripsi | Kecil |
| D-6 | Monitoring admin tanpa tindakan | Mode "lihat sebagai Admin/Korlap" jadi **lihat-saja**, sama seperti mode Client/Extras | Mode Admin/Korlap = aksi nyata (tercatat "sebagai X") | Perubahan perilaku |
| D-7 | Menu Approval | Satu tempat untuk semua yang butuh persetujuan SA (brief Client, mungkin budget) | ACC tersebar di Perlu Tindakan | Menu/halaman |
| D-8 | Menu Remind | SA mengirim pengingat ke Admin PIC proyek, bukan mengerjakan sendiri | **Belum ada** | **Fitur baru** |
| SA-scope-2 | Manajemen akun client + staf, bisa diatur admin | Admin boleh membuat/mengelola akun Client (dan staf?) | Hanya SA (CJ.3 menunggu keputusan) | RBAC, **menjawab sebagian CJ.3** |
| SA-scope-3 | Dealing sama client | SA yang menangani kesepakatan dengan Client (budget, invoice?) | Invoice & budget per peran diisi Admin | Alur/peran |
| ST-1 | Sesuai jobdesk | Menu dan dashboard Admin/Korlap hanya berisi tugasnya | Sebagian sudah per role | Tampilan |
| ST-3 | Remind SA masuk ke Admin PIC | Notifikasi + tampil di "Perlu tindakan" Admin PIC | Belum ada | Bagian dari D-8 |
| ST-4 | Info proyek: Extras siapa saja | Di detail proyek tampil daftar Extras yang terlibat | Ada di Lineup | Cek: mungkin cukup tautan/ringkasan |
| ST-5 | Di Extras: proyek yang pernah dikerjakan | Riwayat proyek per Extras di profil/popup Admin | Perlu dicek di popup profil | Cek |
| ST-6 | Rekap proyek yang pernah di-lock untuk SA | Rekap kandidat yang dipilih Client (lock/greenlight) per proyek, untuk SA | Data ada (`client_reviews`), rekap belum | Laporan |
| ST-scope | Fokus satu proyek, manajemen extras, tindakan sesuai task, remind callingan, ajukan extras, grade, transfer gaji, lineup | Admin bekerja dalam konteks satu proyek; "transfer gaji" = transfer honor Extras | Sebagian besar sudah ada; "remind callingan" belum (pengingat jam panggil) | Campuran |

---

## 2b. Keputusan Fakrul (17 Oktober 2026, diskusi dengan manager)

1. **SA mendelegasikan kerja operasional ke Admin.** SA tidak lagi melakukan aksi Admin langsung sebagai Super Admin ("godmode" dicabut). Kalau SA mau ikut menangani proyek, ia masuk **Monitoring > mode Admin** (bertindak sebagai Admin tertentu, tercatat "sebagai X"). Ini sejalan dengan catatan Erlina D-6 bila dibaca: halaman pratinjau Monitoring tidak punya tombol aksi; aksi hanya setelah masuk mode.
2. **"Perlu tindakan" SA hanya berisi hal yang memang tugas SA.** Hal-hal kecil milik Admin tidak ditampilkan sebagai tugas SA; SA cukup **mengingatkan (remind)** Admin PIC.
3. **Batas keras Admin:** dashboard, menu, aksi, dan notifikasi Admin hanya untuk proyek yang didelegasikan kepadanya. Proyek lain boleh dilihat (read-only), tanpa tombol aksi dan tanpa notifikasi.
4. **Pembayaran honor Extras = urusan Admin**, bukan SA. **SA = urusan Client** (brief/ACC, dealing, invoice).
5. "Didelegasikan" = Admin PIC **atau** ditugaskan lewat penugasan SA.
6. Pengajuan Client harus lengkap sebelum sampai SA: Client mengisi peran, kriteria, jumlah orang, **budget proyek total**, tanggal. SA tinggal **Setujui (pilih PIC) / Minta revisi / Tolak**. **Budget per orang tiap peran diatur Admin PIC** setelah disetujui (ada potongan agensi). Admin melihat budget.
7. Proyek buatan Admin sendiri tetap tanpa ACC.
8. Remind SA → Admin PIC, plus titik merah di menu Admin.
9. Penggajian staf: **ditahan**, dirapikan nanti setelah Fakrul memastikan.
10. Warna tombol dan seksi yang sedang dibuka dibedakan.

→ Dituangkan ke `SPEC.md` Bagian **CM** (gerbang + godmode dicabut), **CN** (pengajuan Client lengkap), **CO** (dashboard SA + pengingat), **CP** (warna, persen profil, info proyek).

## 3. Perlu ditanyakan balik ke Erlina/Imanisa

1. **SA-1:** tiga tombol di atas itu isinya apa saja? Tombol "paling bawah" yang dimaksud yang mana?
2. **D-4 "SA cuma nerima budget":** maksudnya SA hanya melihat/menyetujui budget dari Client (Admin tidak melihat budget), atau SA tidak perlu melihat detail lain selain budget?
3. **SA-scope-3 "dealing sama client":** apakah artinya SA yang mengisi budget per peran dan invoice, sedangkan Admin hanya fee Extras?
4. **D-6:** kalau mode lihat Admin jadi lihat-saja, dan SA tidak melakukan tindakan, apakah SA masih boleh melakukan aksi Admin dari menu biasa (godmode), atau benar-benar dicabut?
5. **D-8 Remind:** isinya bebas (teks) atau dipilih dari tugas yang tertunda? Lewat in-app saja atau juga WA? Perlu ada tanda "sudah dikerjakan"?
6. **SA-scope-2 "bisa diatur admin":** Admin boleh membuat akun Client saja, atau juga akun staf (Admin/Korlap)?
7. **EX-1:** yang mengganggu itu tombolnya muncul di setiap kartu, atau lebih suka satu form panjang dengan satu Simpan di bawah?
8. **ST-scope "remind callingan":** pengingat otomatis ke Extras soal jam panggil H-1, atau pengingat ke Admin untuk mengisi jam panggil?
9. **ST-6 "di-lock":** "lock" = kandidat yang dipilih Client? Rekapnya per proyek atau per Extras?
