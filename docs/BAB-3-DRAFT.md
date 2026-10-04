BAB III
ANALISIS KEBUTUHAN DAN METODE PENGEMBANGAN

> Revisi 1 Oktober 2026: disesuaikan dengan sistem final (lima peran, fitur sampai SPEC bagian BR). Kode RF lama dipertahankan agar jejak ke proposal tidak putus; kebutuhan baru bernomor RF-60 ke atas; kebutuhan yang dicabut ditandai "Dicabut" beserta alasannya.

3.1 Analisis Kebutuhan

Analisis kebutuhan sistem disusun berdasarkan hasil wawancara dan observasi terhadap proses bisnis PT. JBTB Casting Creative Group, sebagaimana diuraikan pada Bab II, serta hasil bimbingan dengan dosen pembimbing yang memperluas cakupan sistem dari sekadar pengelolaan Extras menjadi pengelolaan operasional agensi secara lebih menyeluruh, termasuk pengelolaan staf pendukung produksi dan keuangan proyek. Kebutuhan sistem dibagi menjadi kebutuhan fungsional dan kebutuhan non-fungsional.

Penyusunan struktur akses pengguna mengacu pada konsep Role-Based Access Control (RBAC), yaitu pendekatan pengendalian akses yang memberikan hak dan kewenangan kepada pengguna berdasarkan peran yang dimilikinya di dalam organisasi, bukan berdasarkan identitas individu. Selama pengembangan, struktur peran yang semula tujuh disederhanakan menjadi lima peran setelah evaluasi bersama mitra: sub-peran Talent Coordinator dan Sosial Media/Multimedia tidak memiliki fitur fungsional pada sistem selain pencatatan penugasan dan honor, sehingga dilebur ke peran Admin, sedangkan Casting Director dipertegas menjadi peran Client.

3.1.1. Aktor Sistem

Sistem ini melibatkan lima aktor: dua aktor eksternal (Client dan Extras) serta tiga aktor internal agensi (Super Admin, Admin, dan Koordinator Lapangan).

| Aktor | Deskripsi Peran | Cara Masuk Sistem |
|---|---|---|
| Super Admin | Pemilik/pimpinan agensi. Memantau seluruh operasional melalui dashboard (ringkasan perlu tindakan, status proyek, arus uang per periode, kalender), mengelola seluruh akun (Admin, Korlap, Client), menyetujui atau menolak pengajuan proyek dari Client, menugaskan Admin pada proyek, menetapkan honor staf, serta melihat log aktivitas. Dapat memasuki mode pemantauan: sebagai Admin atau Korlap dengan aksi penuh (setiap aksi tercatat atas nama Super Admin), dan sebagai Client atau Extras dengan tampilan lihat-saja. | Akun tunggal terproteksi, dibuat saat inisialisasi sistem |
| Admin | Menjalankan operasional inti: membuat dan mengelola proyek casting, menyeleksi kandidat, menetapkan grade, menjalankan negosiasi fee, mengajukan kandidat kepada Client, mengelola kontrak, invoice, pembayaran Extras, keuangan proyek (cashflow), serta kelola akun Extras. Dapat berjumlah lebih dari satu; setiap proyek memiliki satu Admin PIC. | Dibuat oleh Super Admin |
| Koordinator Lapangan (Korlap) | Mengawasi Extras di lokasi produksi: mencatat kehadiran, memvalidasi atau menolak foto absensi Extras, serta mencatat catatan atau sanksi lapangan. Melihat riwayat kerja dan status honor miliknya sendiri. Tidak memiliki akses keuangan maupun pengelolaan akun. | Dibuat oleh Super Admin, ditugaskan per proyek |
| Client | Mewakili Production House (PH). Mengajukan kebutuhan proyek (brief) untuk disetujui Super Admin, melakukan Greenlight (lock atau tolak) terhadap kandidat yang diajukan Admin, melengkapi jadwal shooting, melihat dan menandatangani invoice, serta mengunduh riwayat pilihannya. Seorang Client dapat memiliki lebih dari satu proyek dengan satu akun. | Dibuat oleh Super Admin dengan kata sandi sementara dan wajib ganti kata sandi pada login pertama; registrasi publik Client ditutup |
| Extras | Mendaftar mandiri, melengkapi profil dan portofolio, mendaftar pada peran proyek, bernegosiasi fee, menandatangani kontrak, melakukan absensi selfie, serta mengonfirmasi penerimaan honor. | Registrasi mandiri (formulir atau akun Google) |

Production House (PH) selaku entitas perusahaan tidak memiliki akun tersendiri; individu yang login dan bertindak adalah Client yang mewakilinya, dengan nama perusahaan tersimpan pada data akun Client.

3.1.2. Kebutuhan Fungsional

Modul Autentikasi dan Manajemen Akun

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-01 | Registrasi mandiri untuk Extras, melalui formulir atau akun Google | Extras |
| RF-02 | Dicabut. Registrasi Client melalui tautan terpisah diganti RF-59 (akun Client dibuat Super Admin) | - |
| RF-03 | Login dengan hak akses berbeda untuk lima peran sesuai Role-Based Access Control; akun nonaktif ditolak login | Semua |
| RF-04 | Validasi duplikasi NIK (satu NIK untuk satu akun) melalui pengecekan pada basis data tanpa integrasi Dukcapil; NIK dilengkapi Extras sebelum penandatanganan kontrak | Sistem |
| RF-05 | Admin menonaktifkan atau mengaktifkan akun Extras yang bermasalah. Pengelolaan akun Client menjadi wewenang Super Admin | Admin |
| RF-54 | Admin menandai Extras sebagai Favorit sebagai catatan internal yang tidak terlihat oleh Client maupun Extras (pengembangan dari badge Apresiasi) | Admin |
| RF-55 | Halaman beranda publik menampilkan perkenalan agensi, alur pendaftaran, jumlah proyek yang membuka pendaftaran, cast terkurasi, dan portofolio proyek terkurasi; budget dan data sensitif tidak pernah ditampilkan | Publik |
| RF-57 | Super Admin mengelola seluruh akun (Admin, Korlap, Client, Extras) melalui satu menu Manajemen Akun: pencarian, filter, aksi massal, nonaktifkan, hapus, pulihkan; akun sistem terproteksi dan akun sendiri tidak dapat dihapus | Super Admin |
| RF-58 | Hanya akun Super Admin terproteksi yang dapat membuat Super Admin baru | Super Admin |
| RF-59 | Super Admin membuat akun Client dengan kata sandi sementara; email bersifat opsional; Client wajib mengganti kata sandi pada login pertama | Super Admin |
| RF-60 | Login dengan akun Google; pendaftaran baru hanya untuk Extras, peran lain harus menghubungkan akun Google lebih dahulu; fitur nonaktif otomatis bila konfigurasi Google belum diisi | Semua |
| RF-61 | Sistem menghapus otomatis akun Extras yang tidak pernah melengkapi profil dan tidak pernah mendaftar selama 30 hari, didahului peringatan H-7; Admin dapat memangkas secara manual | Sistem/Admin |
| RF-62 | Super Admin dapat masuk mode pemantauan per peran: Admin dan Korlap (aksi penuh, tercatat sebagai aksi Super Admin), Client dan Extras (lihat-saja, seluruh aksi tulis ditolak) | Super Admin |
| RF-63 | Sistem mencatat log aktivitas (aktor, peran, aksi, waktu) yang dapat dicari dan difilter oleh Super Admin | Sistem/Super Admin |

Modul Profil Extras

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-06 | Extras melengkapi profil: usia, gender, tinggi dan berat badan, ukuran baju, bahasa, rate card, riwayat pengalaman, tautan portofolio, foto utama, empat foto tambahan, dan video profil | Extras |
| RF-07 | Sistem menandai status Extras (Aktif/Tidak Aktif/Melanggar) berdasarkan riwayat pembatalan | Sistem |
| RF-08 | Pembatalan sebanyak tiga kali mengubah status Extras otomatis menjadi "Melanggar"; seluruh pembatalan dihitung sebagai pembatalan mendadak (keputusan D23) | Sistem |
| RF-64 | Extras memberi tag ciri dengan teks bebas (disarankan lewat autocomplete dan dinormalisasi otomatis); Admin merapikan tag melalui dialog "Rapikan tag" (pindah grup, gabung, hapus) | Extras/Admin |
| RF-65 | Extras menentukan izin tampil profil pada beranda publik; penayangan tetap memerlukan persetujuan Admin | Extras/Admin |

Modul Manajemen Staf (Admin dan Korlap)

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-40 | Super Admin menambahkan akun Admin atau Korlap | Super Admin |
| RF-41 | Super Admin menetapkan nominal honor per-event staf, dapat disesuaikan kembali | Super Admin |
| RF-42 | Super Admin menugaskan Admin pada proyek dan menandai penugasan selesai | Super Admin |
| RF-43 | Sistem mencatat riwayat kerja staf berupa daftar proyek yang ditangani, sebagai dasar kelayakan honor | Sistem |
| RF-44 | Korlap dan Admin melihat riwayat kerja dan status honor milik sendiri | Admin/Korlap |

Modul Manajemen Proyek Casting

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-09 | Admin atau Super Admin membuat proyek casting: nama produksi, peran beserta kriteria, tag, budget dan kuota per peran, deadline, tanggal-tanggal shooting (jamak, tidak harus berurutan), Admin PIC, Client, serta penanda "Urgent". Setiap proyek memperoleh kode otomatis berformat JBTB-tahun-nomor | Admin |
| RF-10 | Admin mengedit, membuka, atau menutup proyek; lampiran file proyek (naskah, moodboard) disimpan pada penyimpanan privat | Admin |
| RF-11 | Extras melihat daftar proyek yang dibuka beserta tingkat kecocokan tag, kuota tersisa, dan status Penuh | Extras |
| RF-56 | Admin membagikan tautan publik per proyek; pengunjung dapat melihat kriteria dan kuota tanpa nama client lalu diarahkan ke pendaftaran; proyek tertutup menampilkan halaman pemberitahuan | Admin/Extras/Publik |
| RF-66 | Client mengajukan brief proyek; proyek baru aktif setelah disetujui Super Admin, dan penolakan disertai alasan serta notifikasi | Client/Super Admin |
| RF-67 | Admin dapat mengaitkan proyek dengan portofolio terkurasi untuk beranda publik | Admin |

Modul Pendaftaran dan Seleksi

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-12 | Extras mendaftar pada peran proyek, termasuk secara paralel pada beberapa proyek | Extras |
| RF-13 | Sistem menegakkan aturan bentrok jadwal: pendaftaran ditolak bila bentrok dengan keterlibatan berstatus Lolos atau Kontrak Ditandatangani; bila bentrok dengan proses yang belum pasti, Extras diberi peringatan dan dapat melanjutkan dengan konfirmasi; penandatanganan kontrak ditolak bila bentrok dengan kontrak lain | Sistem |
| RF-14 | Admin memfilter pendaftar (tag, grade, status, favorit) dan melihat profil lengkap | Admin |
| RF-15 | Admin menetapkan Grade (A/B/C) sebagai penilaian kualitas yang independen dari fee | Admin |
| RF-68 | Kuota peran bersifat antrian: setiap pendaftar yang tidak ditolak atau dibatalkan menempati slot; pendaftaran diblokir saat penuh dan slot terbuka kembali saat ada penolakan atau pembatalan | Sistem |

Modul Negosiasi Fee

Negosiasi fee dilakukan saat Admin menyeleksi kandidat, sebelum kandidat diajukan kepada Client, agar Client hanya menerima kandidat yang fee-nya telah disepakati dan fokus pada kesesuaian talent.

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-16 | Admin mengajukan penawaran fee awal berdasarkan rate card dan budget peran | Admin |
| RF-17 | Extras menerima penawaran atau mengajukan counter, tanpa batas jumlah putaran | Extras |
| RF-18 | Admin menerima counter, mengajukan counter balik, atau menghentikan negosiasi dengan alasan | Admin |
| RF-19 | Sistem mencatat setiap putaran (pengaju, nominal, waktu, catatan) sebagai riwayat | Sistem |
| RF-20 | Saat disepakati, status menjadi "Deal" dan fee terkunci pada nominal tersebut | Sistem |

Modul Greenlight Client

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-21 | Admin mengajukan kandidat berstatus Deal kepada Client | Admin |
| RF-22 | Sistem menampilkan peringatan bentrok jadwal kepada Admin saat kandidat akan diajukan | Sistem |
| RF-23 | Client meninjau kandidat dalam tampilan grid dengan filter demografis dan tag, memberi grade, lalu melakukan lock (Lolos) atau tolak, secara individual maupun massal | Client |
| RF-24 | Sistem mencatat status pendaftar secara berjenjang: Diajukan, Direview Admin, Nego Fee, Deal, Diajukan ke Client, Lolos, Kontrak Ditandatangani, Selesai Produksi, dengan cabang Ditolak dan Dibatalkan; status pembayaran dicatat terpisah: Belum Dibayar, Ditransfer (dengan bukti), Dikonfirmasi Diterima, dengan cabang Disengketakan | Sistem |
| RF-69 | Setiap keputusan Client (lock maupun tolak) memicu notifikasi kepada Admin PIC proyek berisi nama Client, kandidat, proyek, dan tautan langsung ke halaman terkait | Sistem |
| RF-70 | Admin melihat, secara hanya-baca, daftar Client beserta proyek dan status kandidat yang diajukan (Menunggu, Lock, Ditolak) | Admin |

Modul Kontrak Digital

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-25 | Sistem membuat dokumen kontrak (Talent Release) otomatis saat kandidat Lolos, dengan harga mengikuti hasil negosiasi; pembuatan terjadi pada transisi status, bukan saat halaman dibuka | Sistem |
| RF-26 | Admin dan Extras menandatangani kontrak melalui canvas signature yang disematkan pada PDF; bukan tanda tangan elektronik tersertifikasi (PSrE) | Admin/Extras |
| RF-27 | Sistem menyimpan dan mengarsipkan kontrak final; kontrak otomatis dibatalkan (void) bila keikutsertaan dibatalkan | Sistem |

Modul Pembayaran dan Keuangan

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-28 | Admin menandai "Sudah Ditransfer" beserta bukti transfer; "perlu ditransfer" didefinisikan sebagai kontrak sudah ditandatangani lengkap dan belum dibayar | Admin |
| RF-29 | Extras mengonfirmasi penerimaan honor atau mengajukan sengketa beralasan | Extras |
| RF-30 | Sistem menampilkan keuangan per proyek dengan satu sumber perhitungan: Masuk (invoice lunas), Piutang (invoice belum lunas), Keluar (honor Extras, honor staf, biaya lain-lain), Saldo, dan Proyeksi; ringkasan per periode tersedia pada dashboard Super Admin | Admin/Super Admin |
| RF-31 | Sistem membuat invoice penagihan kepada Client, ditandatangani canvas oleh Admin dan Client, dengan opsi dokumen kustom; Admin menandai lunas | Admin/Client |
| RF-32 | Admin menambahkan komponen tambahan (add-on) pada pembayaran, berupa label bebas dan nominal manual | Admin |
| RF-71 | Admin mencatat biaya lain-lain proyek sebagai komponen pengeluaran cashflow | Admin |

Modul Penggajian Staf

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-45 | Sistem mencatat status penugasan staf per proyek, ditandai selesai saat proyek selesai | Sistem |
| RF-46 | Status selesai menjadi dasar kelayakan honor proyek terkait | Sistem |
| RF-47 | Super Admin menambahkan add-on pada honor staf | Super Admin |
| RF-48 | Sistem membuat slip honor (PDF) untuk setiap staf saat penugasan selesai | Sistem |
| RF-49 | Super Admin melihat rekap honor seluruh staf dan menandai honor staf telah dibayar | Super Admin |

Modul Pembatalan dan Lapangan

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-33 | Admin atau Extras membatalkan keikutsertaan pada status Deal, Lolos, atau Kontrak Ditandatangani dengan alasan | Admin/Extras |
| RF-34 | Sistem mencatat riwayat pembatalan dan akumulasinya per Extras | Sistem |
| RF-35 | Korlap memberi catatan atau sanksi terhadap Extras berdasarkan kondisi lapangan | Korlap |
| RF-53 | Korlap atau Admin mencatat kehadiran Extras per tanggal shooting | Korlap/Admin |
| RF-72 | Extras mengirim foto selfie absensi; Korlap memvalidasi atau menolaknya; Client dapat melihat foto absensi proyeknya | Extras/Korlap/Client |

Modul Notifikasi

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-36 | Sistem mengirim notifikasi dalam aplikasi (ikon lonceng, tautan relatif, penanda dibaca) untuk kejadian penting, dengan email sebagai kanal tambahan bila tersedia | Sistem |
| RF-37 | Sistem mengirim notifikasi WhatsApp otomatis melalui `whatsapp-web.js` self-hosted sebagai kanal pelengkap (hasil seleksi, kontrak siap ditandatangani, pengingat), melalui antrian sehingga kegagalan kirim tidak menggagalkan aksi utama; nomor dinormalisasi ke format 62 | Sistem |
| RF-38 | Admin menginput tautan grup WhatsApp proyek sebagai kanal informasi lanjutan | Admin |
| RF-73 | Pengingat terjadwal: H-3 pemilihan Extras, pengingat input jadwal, H-1 shooting, dan peringatan akun mangkrak | Sistem |

Modul Dashboard dan Laporan

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-39 | Dashboard sesuai kebutuhan tiap peran, termasuk kalender jadwal bergaya mobile dan tahapan partisipasi kandidat | Semua |
| RF-50 | Dashboard Super Admin berisi ringkasan "perlu tindakan", status proyek, arus uang periode, dan kalender; pemantauan per peran melalui RF-62 | Super Admin |
| RF-51 | Admin melihat rekap Extras (sering dipilih, sering batal) pada satu halaman Kelola Akun Extras dengan opsi pengurutan | Admin |
| RF-52 | Admin dan Client mengekspor data ke Excel (rekap Extras sesuai filter; riwayat pilihan Client, juga PDF) | Admin/Client |

3.1.3. Kebutuhan Non-Fungsional

| Kode | Kategori | Kebutuhan |
|---|---|---|
| RNF-01 | Keamanan | Data sensitif (NIK, nama asli, rekening) dienkripsi pada basis data; berkas sensitif (kontrak, TTD, bukti transfer, foto absensi, foto dan video profil, lampiran) disimpan pada penyimpanan privat dan disajikan melalui kontrol akses |
| RNF-02 | Keamanan | RBAC lima peran dengan pembatasan akses per rute dan otorisasi tingkat data (misalnya Client hanya melihat proyek miliknya) |
| RNF-03 | Keamanan | Kata sandi disimpan dalam bentuk hash; pembatasan percobaan login dan pendaftaran; kata sandi sementara wajib diganti |
| RNF-04 | Performa | Mampu menangani sekitar 50–80 Extras aktif dan 4–5 proyek aktif per bulan tanpa penurunan performa signifikan |
| RNF-05 | Usability | Antarmuka responsif; pada perangkat mobile, Extras memakai navigasi bawah tiga menu dan peran lain memakai menu hamburger; tidak terjadi overflow horizontal pada lebar 360 dan 390 piksel (diverifikasi pada seluruh halaman lintas peran); indikator status berwarna konsisten |
| RNF-06 | Reliability | Data tersimpan terpusat, menggantikan Excel, WhatsApp, dan Google Drive; aksi terbaca (GET) tidak mengubah data |
| RNF-07 | Maintainability | Laravel dengan arsitektur MVC; cakupan pengujian otomatis di atas 600 pengujian pada SQLite dan MySQL |
| RNF-08 | Compatibility | Dapat diakses melalui peramban desktop maupun mobile |
| RNF-09 | Availability | Dihosting pada shared hosting atau VPS |
| RNF-10 | Keamanan | Header keamanan (CSP, X-Frame-Options, nosniff, HSTS pada produksi) dan seluruh aksi penting tercatat pada log aktivitas |

3.1.4. Batasan Sistem

1. Sistem hanya mengelola Extras/figuran dan tidak mencakup talent profesional/pemeran utama.
2. Production House (PH) tidak memiliki akun tersendiri; diwakili Client yang akunnya dibuat Super Admin.
3. Sistem tidak memproses pembayaran (bukan payment gateway); transfer dilakukan di luar sistem dan sistem hanya mencatat status.
4. Tanda tangan berupa canvas signature, bukan tanda tangan elektronik tersertifikasi (PSrE).
5. Notifikasi WhatsApp menggunakan `whatsapp-web.js` self-hosted, bukan API resmi WhatsApp Business maupun gateway berbayar (lihat catatan penyimpangan di bawah).
6. Aturan bentrok jadwal memblokir hanya pada keterlibatan yang sudah pasti (Lolos atau Kontrak Ditandatangani); selain itu bersifat peringatan.
7. Validasi NIK dilakukan internal melalui pengecekan duplikasi, tanpa integrasi API Dukcapil.
8. Staf Admin dan Korlap bukan karyawan tetap; honor bersifat per-proyek dan tidak mencakup pajak maupun BPJS.
9. Absensi berupa pencatatan kehadiran dan foto selfie yang divalidasi Korlap, tanpa verifikasi geolokasi.
10. Pembaruan informasi bersifat polling dan notifikasi dalam aplikasi, bukan komunikasi real-time (WebSocket).
11. Pengiriman email melalui domain perusahaan belum diaktifkan pada tahap ini dan menjadi bagian pengembangan lanjutan.

Catatan Penyimpangan dari Proposal: WhatsApp Gateway

Dokumen proposal awal (Bab 3.1.4 Batasan Sistem, poin 5) menetapkan: *"Notifikasi WhatsApp menggunakan layanan gateway pihak ketiga berbayar, bukan integrasi API resmi WhatsApp Business maupun otomasi tidak resmi berbasis sesi pribadi."*

Implementasi akhir menggunakan pendekatan yang berlawanan: `whatsapp-web.js` self-hosted (otomasi sesi WhatsApp Web, gratis). Keputusan diambil secara sadar pada 28 Agustus 2026. Kajian layanan gateway berbayar (Fonnte, Wablas, dan sejenisnya) menunjukkan layanan tersebut pada praktiknya juga bersifat unofficial dan memiliki risiko pemblokiran nomor yang setara, sehingga biaya tambahan dinilai tidak sepadan bagi tim pengembang berskala kecil. Mitigasi risiko (nomor khusus sistem, volume rendah non-broadcast, WhatsApp sebagai kanal pelengkap dengan notifikasi dalam aplikasi sebagai kanal utama) diterapkan pada desain akhir. Detail trade-off didokumentasikan pada `docs/OPEN-QUESTIONS-PROPOSAL.md` poin 3.

3.1.5. Pengembangan Lanjutan (Di Luar Cakupan Implementasi Saat Ini)

Gagasan berikut muncul selama bimbingan dan pengembangan, dinilai bernilai, tetapi sengaja ditunda agar sistem inti dapat diselesaikan dan diuji secara menyeluruh.

| Gagasan | Alasan Ditunda |
|---|---|
| Pemeriksaan kelayakan otomatis saat registrasi Extras | Kriteria kelayakan belum disepakati bersama mitra |
| Penilaian sikap Extras 1–5 oleh Korlap dan skor otomatis | Memerlukan data historis dan rumus penilaian yang disepakati |
| Fitur "panggil lagi" (re-book) Extras terdahulu | Kebutuhan tertutup sementara oleh fitur Favorit dan filter |
| Email melalui domain perusahaan (Lark Suite) dan login Google pada lingkungan produksi | Bergantung pada domain dan konfigurasi saat penayangan |
| Pemindaian QR untuk check-in absensi | Memerlukan konfirmasi alur dengan mitra |
| Pembaruan real-time (WebSocket) | Pengalaman penggunaan sebelumnya kurang stabil; polling dan notifikasi dinilai cukup |
| Tanda tangan elektronik tersertifikasi (PSrE) dan API Dukcapil | Biaya dan ketergantungan pihak ketiga |

3.2 Metode Pengembangan

Sistem ini dikembangkan menggunakan metodologi Agile dengan kerangka kerja Scrum, sejalan dengan sifat proses bisnis agensi yang dinamis serta kebutuhan validasi bertahap dari mitra, PT. JBTB Casting Creative Group. Pendekatan iteratif dipilih karena ruang lingkup sistem mencakup beberapa kelompok modul yang saling terintegrasi (pengelolaan Extras, proyek dan Client, keuangan, serta staf internal), sehingga validasi bertahap memungkinkan penyesuaian kebutuhan tanpa menunggu seluruh sistem selesai.

Pada tahap akhir diterapkan pembekuan fitur (feature freeze): setelah paket fitur terakhir selesai, hanya perbaikan bug dan perapian tampilan yang dikerjakan, dilanjutkan pengujian manual per peran oleh anggota tim.

3.2.1. Peran Scrum

Product Owner dijalankan bersama oleh tim peneliti dan pihak PT. JBTB Casting Creative Group, yaitu Jestika Aisya Kordak selaku Direktur Utama/Super Admin dan Erlina Stepani Gultom selaku Direktur Keuangan yang memvalidasi kebutuhan data dan proses keuangan. Scrum Master dan Development Team dijalankan oleh tim peneliti, dengan Fakhrul Mukhlisin sebagai pengembang utama dan Imanisa yang berperan dalam analisis kebutuhan serta pengujian. Dosen pembimbing memberi arahan melalui sesi bimbingan berkala.

3.2.2. Pembagian Sprint

Pengembangan dibagi menjadi enam sprint berdurasi dua minggu, disusun berdasarkan ketergantungan antarmodul dan tingkat risiko. Modul baru atau yang bergantung pada pihak ketiga ditempatkan lebih awal. Peran yang disebut pada tabel mengikuti struktur lima peran final.

| Sprint | Modul | Fokus |
|---|---|---|
| Sprint 1 | Autentikasi, Manajemen Akun, Profil Extras | Login RBAC lima peran, registrasi Extras, profil Extras, inisiasi integrasi WhatsApp |
| Sprint 2 | Manajemen Proyek Casting, Pendaftaran dan Seleksi | Posting proyek per peran, pendaftaran, filter kandidat, kuota, aturan bentrok jadwal |
| Sprint 3 | Grade, Negosiasi Fee, Greenlight Client | Grade, tawar-menawar bertingkat hingga Deal, lock atau tolak oleh Client |
| Sprint 4 | Kontrak Digital, Invoice | Pembuatan kontrak otomatis, canvas signature, invoice |
| Sprint 5 | Manajemen Staf dan Penggajian | Akun Admin dan Korlap, penugasan, honor, slip, dashboard dan pemantauan Super Admin |
| Sprint 6 | Pembayaran, Keuangan, Dashboard, Laporan | Transfer dan bukti, cashflow, ekspor Excel, notifikasi, perapian UI/UX dan pengujian |

3.2.3. Pemodelan dan Pengujian

Pemodelan sistem menggunakan Unified Modeling Language (UML): Use Case Diagram, Activity Diagram, Sequence Diagram, dan Class Diagram. Pengujian menggunakan Black Box Testing pada setiap modul dengan skenario berupa input, hasil diharapkan, hasil aktual, dan status (Pass/Fail), dilengkapi pengujian otomatis (feature test) pada basis data SQLite dan MySQL serta audit tampilan mobile pada lebar 360 dan 390 piksel.

3.3 Timeline

Timeline berikut mencakup tahapan pengajuan proposal hingga sidang judul (sempro), sesuai jadwal program studi pada September 2026. Tahapan pengembangan sistem (pembagian sprint pada 3.2.2) dituangkan pada dokumen Laporan Akhir.

| Tahapan | Aktivitas | Estimasi Waktu |
|---|---|---|
| Penyusunan Proposal | Revisi dan konsolidasi proposal (Bab I–III) bersama tim dan hasil bimbingan dosen pembimbing | Agustus 2026 |
| Pendaftaran Seminar Proposal (Sempro) | Pengajuan berkas proposal ke program studi | 1 September 2026 |
| Sidang Judul | Presentasi dan validasi judul serta ruang lingkup proyek | 3 September 2026 (dapat berubah menyesuaikan jadwal dosen) |

Setelah sidang judul disetujui, pengembangan berjalan sesuai pembagian sprint pada 3.2.2, dengan estimasi total sekitar tiga bulan, diikuti pengujian dan penyusunan Laporan Akhir sebelum sidang/UAPS sesuai jadwal program studi.
