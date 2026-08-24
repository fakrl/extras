# Identifikasi Kebutuhan Sistem
## Rancang Bangun Sistem Informasi Manajemen Casting Talent dan Extras Berbasis Web dengan Fitur Negosiasi Fee Digital
### Studi Kasus: JBTB Casting

---

## 1. Ringkasan Profil Sistem

| Item | Keterangan |
|---|---|
| Studi kasus | JBTB Casting |
| Fokus objek | Extras/figuran (belum mencakup talent profesional) |
| Proses bisnis saat ini | Manual via grup WhatsApp, Excel, Google Drive |
| Masalah utama | Proses lambat, kesalahpahaman fee, data tidak terpusat, keputusan CD lambat, info dadakan ke extras |
| Model fee | Fixed fee dari client, dengan mekanisme 1x pengajuan fee alternatif dari extras |
| Metodologi pengembangan | Agile/Scrum |
| Metodologi pemodelan | UML (Use Case, Activity, Sequence, Class Diagram) |
| Teknologi | Laravel |
| Metode pengujian | Black Box Testing |
| Deployment | Shared hosting/VPS |

---

## 2. Aktor Sistem

| Aktor | Deskripsi Peran |
|---|---|
| **Admin Agensi** | Mengelola seluruh operasional sistem: membuat proyek casting, mereview & mengajukan kandidat ke CD, mengelola fee & kontrak, mengelola data extras, membuat rekap/laporan. Hanya ada 1 tingkatan admin (tidak ada sub-role). |
| **Casting Director (CD)** | Memiliki akun login sendiri. Bertugas mereview dan melakukan approve/reject kandidat extras yang diajukan Admin untuk suatu proyek casting. |
| **Extras** | Mendaftar mandiri (self-register), melengkapi profil & portofolio, mengikuti proses seleksi, menerima/mengajukan fee alternatif, serta mengunggah kontrak yang sudah ditandatangani. |

> **Catatan:** Production House (PH) **bukan** merupakan aktor/pengguna sistem. Kebutuhan casting dari PH disampaikan secara *offline* (di luar sistem) kepada Admin Agensi, yang kemudian menginputnya sebagai "Proyek Casting" baru di dalam sistem.

---

## 3. Kebutuhan Fungsional

### 3.1 Modul Autentikasi & Manajemen Akun
| Kode | Kebutuhan Fungsional | Aktor |
|---|---|---|
| RF-01 | Sistem menyediakan registrasi mandiri (self-register) untuk Extras dengan input data diri, foto, portofolio, dan video profil | Extras |
| RF-02 | Sistem menyediakan login untuk Admin, CD, dan Extras dengan hak akses berbeda | Semua |
| RF-03 | Sistem melakukan validasi duplikasi NIK saat Extras dinyatakan lolos dan wajib mengunggah KTP (1 NIK = 1 akun) | Sistem/Extras |
| RF-04 | Admin dapat mengelola akun CD (tambah/edit/nonaktifkan) | Admin |
| RF-05 | Admin dapat menonaktifkan akun Extras secara manual (pelanggaran, tidak aktif login, dsb.) | Admin |

### 3.2 Modul Profil Extras
| Kode | Kebutuhan Fungsional | Aktor |
|---|---|---|
| RF-06 | Extras dapat melengkapi & memperbarui profil: usia, gender, tinggi badan, ukuran baju, warna kulit, pengalaman perfilman, bahasa yang dikuasai, dan video profil | Extras |
| RF-07 | Sistem menampilkan status keaktifan Extras (Aktif/Tidak Aktif) berdasarkan kriteria yang ditetapkan | Sistem |
| RF-08 | Sistem otomatis menandai Extras sebagai "melanggar" apabila melakukan pembatalan (cancel) mendadak sebanyak 3 kali pada proyek yang berbeda | Sistem |

### 3.3 Modul Manajemen Proyek Casting
| Kode | Kebutuhan Fungsional | Aktor |
|---|---|---|
| RF-09 | Admin dapat membuat proyek casting baru: nama produksi, jumlah extras dibutuhkan, kriteria (usia, gender, tinggi badan, dsb.), deadline pendaftaran, serta informasi tanggal & lokasi shooting (field sederhana) | Admin |
| RF-10 | Admin dapat mengedit/menutup proyek casting | Admin |
| RF-11 | Sistem menampilkan daftar proyek casting yang masih dibuka kepada Extras | Extras |

### 3.4 Modul Pendaftaran & Seleksi
| Kode | Kebutuhan Fungsional | Aktor |
|---|---|---|
| RF-12 | Extras dapat mendaftar pada proyek casting yang dibuka (mendaftar paralel di beberapa proyek diperbolehkan) | Extras |
| RF-13 | Admin dapat memfilter pendaftar berdasarkan kriteria proyek (usia, gender, tinggi badan, ukuran baju, warna kulit, pengalaman, bahasa) | Admin |
| RF-14 | Admin mengajukan kandidat terfilter kepada CD untuk direview | Admin |
| RF-15 | CD dapat melakukan approve/reject terhadap kandidat yang diajukan | CD |
| RF-16 | Sistem mencatat status pendaftar secara berjenjang: *Diajukan → Direview CD → Lolos/Ditolak → Nego Fee → Deal → Kontrak Ditandatangani → Selesai Produksi* | Sistem |

### 3.5 Modul Negosiasi Fee Digital
| Kode | Kebutuhan Fungsional | Aktor |
|---|---|---|
| RF-17 | Admin mengajukan fee tetap (fixed fee dari client) kepada Extras yang lolos seleksi CD | Admin |
| RF-18 | Extras dapat memilih **Terima** fee, atau **Mengajukan Fee Alternatif** (maksimal 1 kali) | Extras |
| RF-19 | Admin memutuskan status final atas fee alternatif yang diajukan Extras (setuju/tolak) | Admin |
| RF-20 | Sistem mencatat riwayat negosiasi fee (fee awal, fee alternatif, fee final) per pendaftar | Sistem |

### 3.6 Modul Kontrak Digital
| Kode | Kebutuhan Fungsional | Aktor |
|---|---|---|
| RF-21 | Admin dapat membuat dan mengedit template kontrak secara dinamis di dalam sistem | Admin |
| RF-22 | Sistem melakukan *auto-generate* dokumen kontrak PDF dari template berdasarkan data proyek, extras, dan fee yang disepakati | Sistem |
| RF-23 | Admin mengunduh kontrak, menandatangani secara manual, lalu mengunggah kembali PDF ber-TTD Admin | Admin |
| RF-24 | Extras mengunduh kontrak ber-TTD Admin, menandatangani secara manual, lalu mengunggah kembali PDF ber-TTD lengkap (Admin & Extras) sebagai dokumen final | Extras |
| RF-25 | Sistem menyimpan dan mengarsipkan dokumen kontrak final per proyek per extras | Sistem |

### 3.7 Modul Pembatalan (Cancel)
| Kode | Kebutuhan Fungsional | Aktor |
|---|---|---|
| RF-26 | Extras/Admin dapat melakukan pembatalan keikutsertaan pada status "Deal" dengan mengisi alasan pembatalan | Admin/Extras |
| RF-27 | Sistem mencatat riwayat pembatalan sebagai data historis dan menghitung akumulasi pembatalan mendadak per extras | Sistem |

### 3.8 Modul Notifikasi
| Kode | Kebutuhan Fungsional | Aktor |
|---|---|---|
| RF-28 | Sistem mengirim notifikasi email kepada Extras untuk: hasil seleksi (lolos/ditolak), permintaan konfirmasi fee, dan permintaan tanda tangan kontrak | Sistem |
| RF-29 | Admin dapat menginput/memperbarui link grup WhatsApp khusus per proyek casting sebagai kanal informasi lanjutan bagi Extras yang lolos | Admin |

### 3.9 Modul Dashboard
| Kode | Kebutuhan Fungsional | Aktor |
|---|---|---|
| RF-30 | Dashboard Admin menampilkan: jumlah proyek berjalan, jumlah CD, jumlah extras terdaftar, jumlah casting dibuka, jumlah pendaftar, jadwal shooting terdekat, dan notifikasi data yang perlu diproses | Admin |
| RF-31 | Dashboard CD menampilkan: jumlah proyek ditangani, daftar casting aktif, jumlah pendaftar per casting, daftar extras lolos seleksi, jadwal casting hari ini, dan notifikasi pendaftar baru | CD |
| RF-32 | Dashboard Extras menampilkan: profil singkat, status pendaftaran (Pending/Diterima/Ditolak), daftar casting dibuka, jadwal shooting (jika diterima), riwayat mengikuti casting, dan notifikasi terkait | Extras |

### 3.10 Modul Laporan/Rekap
| Kode | Kebutuhan Fungsional | Aktor |
|---|---|---|
| RF-33 | Admin dapat melihat rekap extras yang paling sering terpilih per periode | Admin |
| RF-34 | Admin dapat melihat rekap status keaktifan seluruh extras | Admin |
| RF-35 | Admin dapat mengekspor data rekap (misal ke Excel) | Admin |

---

## 4. Kebutuhan Non-Fungsional

| Kode | Kategori | Kebutuhan |
|---|---|---|
| RNF-01 | **Keamanan** | Data sensitif (KTP, foto, video profil) disimpan dengan enkripsi |
| RNF-02 | **Keamanan** | Sistem menerapkan hak akses berlapis (role-based access control) sesuai 3 aktor |
| RNF-03 | **Keamanan** | Password akun disimpan dalam bentuk hash (bukan plain text) |
| RNF-04 | **Performa** | Sistem mampu menangani ± 70 extras terdaftar dan ± 3 proyek casting aktif per bulan tanpa penurunan performa signifikan |
| RNF-05 | **Usability** | Antarmuka berbasis web, responsif, dan mudah digunakan oleh pengguna non-teknis (khususnya Extras & CD) |
| RNF-06 | **Reliability** | Data tersimpan terpusat di database (menggantikan Excel/Gdrive) untuk menghindari kehilangan/duplikasi data |
| RNF-07 | **Maintainability** | Sistem dibangun dengan framework Laravel mengikuti struktur MVC agar mudah dikembangkan lebih lanjut |
| RNF-08 | **Compatibility** | Dapat diakses melalui browser desktop maupun mobile |
| RNF-09 | **Availability** | Sistem di-hosting pada shared hosting/VPS agar dapat diakses kapan saja oleh Admin, CD, dan Extras |

---

## 5. Batasan Sistem (Final)

1. Sistem hanya mengelola **extras/figuran**, tidak mencakup talent profesional/artis utama.
2. **Production House (PH) tidak memiliki akun** di sistem; kebutuhan casting dari PH disampaikan offline ke Admin.
3. Sistem **tidak memproses pembayaran** (bukan payment gateway); fee dibayarkan via transfer bank di luar sistem, sistem hanya mencatat status fee.
4. Fee bersifat **fixed dari client**, dengan mekanisme extras dapat mengajukan **1x fee alternatif** yang diputuskan final oleh Admin.
5. **Tanda tangan digital** yang dimaksud adalah unggah dokumen PDF hasil tanda tangan manual/scan oleh kedua pihak — **bukan** tanda tangan elektronik tersertifikasi (PSrE).
6. **Jadwal shooting** hanya berupa field informasi sederhana (tanggal & lokasi) pada data proyek casting, bukan modul penjadwalan produksi (call sheet, shift kru, dll.).
7. Sistem berbasis **web**, bukan aplikasi mobile native.
8. Tidak mencakup modul payroll/akuntansi agensi secara menyeluruh.
9. Tidak mencakup fitur rating/review performa extras pasca-produksi (dapat menjadi saran pengembangan).
10. Validasi NIK hanya dilakukan **secara internal** (cek duplikasi di database), tidak terintegrasi dengan API Dukcapil.
11. Hanya terdapat 1 tingkatan Admin Agensi (tidak ada sub-role Super Admin/Staff Admin).
12. Grup WhatsApp bersifat **dinamis per proyek**, dan link-nya diinput manual oleh Admin — sistem tidak melakukan integrasi API WhatsApp otomatis.

---

## 6. Rencana Tahap Selanjutnya

Dokumen ini akan dilanjutkan dengan:
1. **Daftar Aktor & Use Case Diagram** (narasi use case + diagram)
2. **Activity Diagram** untuk alur proses utama (pendaftaran, seleksi, negosiasi fee, kontrak)
3. **Sequence Diagram** untuk interaksi antar objek pada proses kritis
4. **ERD & Class Diagram**
5. **Rancangan Pengujian Black Box** (tabel skenario pengujian)
