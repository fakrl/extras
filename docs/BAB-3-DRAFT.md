BAB III
ANALISIS KEBUTUHAN DAN METODE PENGEMBANGAN

3.1 Analisis Kebutuhan

Analisis kebutuhan sistem disusun berdasarkan hasil wawancara dan observasi terhadap proses bisnis PT. JBTB Casting Creative Group, sebagaimana diuraikan pada Bab II, serta hasil bimbingan dengan dosen pembimbing yang memperluas cakupan sistem dari sekadar pengelolaan Extras menjadi pengelolaan operasional agensi secara lebih menyeluruh, termasuk pengelolaan karyawan pendukung produksi. Kebutuhan sistem dibagi menjadi kebutuhan fungsional dan kebutuhan non-fungsional.

Penyusunan struktur akses pengguna pada sistem ini mengacu pada konsep Role-Based Access Control (RBAC), yaitu pendekatan pengendalian akses yang memberikan hak dan kewenangan kepada pengguna berdasarkan peran yang dimilikinya di dalam organisasi, bukan berdasarkan identitas individu secara langsung. Pendekatan ini dipilih karena struktur organisasi PT. JBTB Casting Creative Group memiliki peran yang bersifat kondisional — sebagian peran hanya diperlukan pada proyek tertentu, sehingga otorisasi harus dapat diberikan atau ditarik sesuai kebutuhan tanpa mengubah struktur inti sistem.

3.1.1. Aktor Sistem

Sistem ini melibatkan tujuh aktor yang terbagi menjadi dua kelompok: aktor eksternal (Extras dan Casting Director) serta aktor internal agensi (Super Admin, Admin Default, dan tiga sub-role Admin: Talent Coordinator, Koordinator Lapangan, dan Sosial Media/Multimedia).

| Aktor | Deskripsi Peran | Cara Masuk Sistem |
|---|---|---|
| Super Admin | Pemilik/pimpinan agensi (Direktur Utama). Memantau seluruh kegiatan operasional melalui dashboard analitik dan monitoring, menambahkan akun Admin baru beserta penetapan sub-role spesifiknya, serta menetapkan nominal honor staf saat perekrutan. Tidak menjalankan operasional harian (seleksi, negosiasi, dsb). | Akun tunggal, dibuat manual oleh tim internal saat inisialisasi sistem |
| Admin Default | Menjalankan seluruh operasional inti: membuat proyek casting, menyeleksi kandidat, menetapkan grade, menjalankan negosiasi fee, mengajukan kandidat kepada Casting Director, mengelola kontrak dan pembayaran Extras, mengelola akun Extras, serta membuat rekap. Dapat terdiri dari lebih dari satu akun. | Dibuat oleh Super Admin |
| Admin — Talent Coordinator (Talco) | Sub-role Admin yang bertugas mengoordinasikan kebutuhan talent utama/pemeran inti (bukan Extras) pada saat produksi: jadwal wardrobe, make up, pengambilan gambar, serta koordinasi dengan asisten sutradara dan tim produksi. Karena objek kerjanya (talent utama) berada di luar batasan sistem (lihat 3.1.4 poin 1), peran ini tidak memiliki fitur fungsional pada sistem selain sebagai catatan penugasan dan riwayat kerja untuk keperluan penggajian. | Dibuat oleh Super Admin, ditugaskan per proyek sesuai kebutuhan |
| Admin — Koordinator Lapangan (Korlap) | Sub-role Admin yang mengawasi Extras secara langsung di lokasi produksi: mencatat kehadiran, memantau ketepatan waktu, mengelola informasi wardrobe dan callingan, serta memberikan catatan atau sanksi terhadap Extras berdasarkan kondisi di lapangan. | Dibuat oleh Super Admin, ditugaskan per proyek sesuai kebutuhan |
| Admin — Sosial Media/Multimedia | Sub-role Admin yang mengelola kebutuhan publikasi dan dokumentasi media sosial agensi. Sebagaimana Talco, peran ini tidak memiliki fitur fungsional pada sistem selain sebagai catatan penugasan dan riwayat kerja untuk keperluan penggajian. | Dibuat oleh Super Admin, ditugaskan per proyek sesuai kebutuhan |
| Casting Director (CD) | Mewakili kebutuhan client/Production House (PH); melakukan review dan approve/reject terhadap kandidat yang telah diajukan Admin. | Registrasi melalui tautan khusus yang terpisah dari tautan registrasi Extras; siapa pun yang mendaftar melalui tautan ini secara otomatis memperoleh peran Casting Director |
| Extras | Mendaftar secara mandiri, melengkapi profil dan portofolio, mengikuti proses seleksi, melakukan negosiasi fee, serta mengunggah kontrak yang telah ditandatangani. | Registrasi mandiri melalui tautan publik |

Catatan mengenai struktur Admin: Talco, Korlap, dan Sosial Media/Multimedia bukan merupakan peran admin yang berdiri sendiri, melainkan cabang kewenangan terbatas dari Admin Default yang ditetapkan Super Admin sesuai kebutuhan tiap proyek — tidak seluruh proyek memerlukan ketiganya. Talco dan Sosial Media/Multimedia memiliki akses login yang bersifat terbatas (read-only) untuk melihat riwayat penugasan dan status honor mereka sendiri, tanpa akses ke fitur operasional inti. Korlap memiliki akses fungsional karena tugasnya bersinggungan langsung dengan pencatatan kehadiran dan status Extras di lapangan.

Production House (PH) selaku entitas perusahaan tidak memiliki akun pada sistem. Kebutuhan casting dari PH disampaikan melalui Casting Director yang mewakilinya; individu yang login dan bertindak dalam sistem adalah Casting Director, bukan PH sebagai entitas.

3.1.2. Kebutuhan Fungsional

Modul Autentikasi dan Manajemen Akun

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-01 | Registrasi mandiri untuk Extras (data diri, foto, portofolio, video profil) | Extras |
| RF-02 | Registrasi khusus untuk Casting Director melalui tautan terpisah (otomatis berperan sebagai CD) | CD |
| RF-03 | Login dengan hak akses berbeda untuk tujuh aktor sistem sesuai peran (Role-Based Access Control) | Semua |
| RF-04 | Validasi duplikasi NIK saat Extras dinyatakan lolos seleksi (satu NIK untuk satu akun) | Sistem |
| RF-05 | Admin Default dapat mengelola akun CD dan menonaktifkan akun Extras yang bermasalah | Admin Default |

Modul Profil Extras

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-06 | Extras melengkapi profil: usia, gender, tinggi badan, ukuran baju, warna kulit, pengalaman, bahasa, rate card, serta video dan foto profil | Extras |
| RF-07 | Sistem menandai status Extras (Aktif/Tidak Aktif/Melanggar) berdasarkan riwayat pembatalan | Sistem |
| RF-08 | Pembatalan mendadak sebanyak tiga kali pada proyek berbeda mengubah status Extras secara otomatis menjadi "Melanggar" | Sistem |

Modul Manajemen Karyawan (Admin Sub-Role)

Modul ini menjawab kebutuhan tambahan hasil bimbingan: agensi memiliki karyawan pendukung produksi (Talco, Korlap, Sosial Media/Multimedia) yang sifatnya tidak tetap — dibayar per proyek/event, bukan gaji bulanan, sehingga model penugasannya sejalan dengan model fee proyek-basis milik Extras.

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-40 | Super Admin menambahkan akun Admin baru beserta penetapan sub-role spesifik (Admin Default / Talco / Korlap / Sosial Media-Multimedia) | Super Admin |
| RF-41 | Super Admin menetapkan nominal honor per-event untuk tiap sub-admin pada saat perekrutan, dengan nominal yang dapat disesuaikan kembali di kemudian hari | Super Admin |
| RF-42 | Super Admin menugaskan sub-admin (Talco/Korlap/Sosial Media-Multimedia) ke proyek casting tertentu sesuai kebutuhan proyek tersebut | Super Admin |
| RF-43 | Sistem mencatat riwayat kerja setiap Admin (Default maupun sub-role) berupa daftar proyek yang pernah ditangani, sebagai dasar kelayakan honor | Sistem |
| RF-44 | Talco dan Sosial Media/Multimedia dapat mengakses tampilan terbatas (read-only) berisi riwayat penugasan dan status honor milik mereka sendiri | Talco/Sosmed |

Modul Manajemen Proyek Casting

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-09 | Admin Default membuat proyek casting: nama produksi, kriteria per kelas, kuota, deadline, tanggal-tanggal shooting (dapat lebih dari satu tanggal dan tidak harus berurutan), serta penanda "Butuh Dadakan/Urgent" | Admin Default |
| RF-10 | Admin Default mengedit atau menutup proyek casting | Admin Default |
| RF-11 | Extras melihat daftar proyek casting yang dibuka, diurutkan berdasarkan fee tertinggi dan status urgent di posisi teratas | Extras |

Modul Pendaftaran dan Seleksi

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-12 | Extras mendaftar pada proyek casting, termasuk mendaftar secara paralel pada beberapa proyek | Extras |
| RF-13 | Sistem mendeteksi potensi bentrok jadwal; apabila Extras memiliki keterlibatan aktif (status Deal/Lolos) pada proyek lain dengan tanggal shooting yang tumpang tindih, sistem menampilkan peringatan kepada Extras pada saat mendaftar, tanpa melakukan pemblokiran | Sistem |
| RF-14 | Admin Default memfilter pendaftar berdasarkan kriteria proyek dan melihat profil lengkap (media sosial, portofolio, rate card) | Admin Default |
| RF-15 | Admin Default menetapkan Grade (A/B/C) untuk pendaftar sebagai penilaian kualitas yang independen dari besaran fee | Admin Default |

Modul Negosiasi Fee

Negosiasi fee dilakukan pada tahap Admin menyeleksi kandidat, sebelum kandidat diajukan kepada Casting Director. Urutan ini dipilih agar Casting Director hanya menerima kandidat yang fee-nya telah disepakati, sehingga proses review CD terfokus pada kesesuaian talent, bukan pada negosiasi harga.

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-16 | Admin Default mengajukan penawaran fee awal kepada kandidat berdasarkan rate card Extras dan budget dari client | Admin Default |
| RF-17 | Extras dapat menerima penawaran atau mengajukan counter dengan nominal fee yang berbeda, tanpa batas jumlah putaran (mekanisme tawar-menawar bertingkat) | Extras |
| RF-18 | Admin Default dapat menerima counter dari Extras, mengajukan counter balik, atau menghentikan proses negosiasi | Admin Default |
| RF-19 | Sistem mencatat setiap putaran penawaran dan counter (pengaju, nominal, waktu) sebagai riwayat negosiasi fee | Sistem |
| RF-20 | Ketika salah satu pihak menyetujui penawaran, status negosiasi berubah menjadi "Deal" dan fee terkunci pada nominal yang disepakati | Sistem |

Modul Review dan Approval Casting Director

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-21 | Admin Default mengajukan kandidat yang fee-nya telah Deal kepada CD untuk direview | Admin Default |
| RF-22 | Sistem menampilkan peringatan bentrok jadwal kepada Admin Default pada saat kandidat akan diajukan kepada CD, apabila kandidat tersebut memiliki keterlibatan aktif pada proyek lain dengan tanggal yang tumpang tindih | Sistem |
| RF-23 | CD melakukan approve/reject terhadap kandidat yang diajukan, baik secara individual maupun secara massal | CD |
| RF-24 | Sistem mencatat status pendaftar secara berjenjang: Diajukan → Direview Admin → Nego Fee → Deal → Diajukan ke CD → Direview CD → Lolos/Ditolak → Kontrak Ditandatangani → Selesai Produksi, dengan status pembayaran yang tercatat secara terpisah: Belum Dibayar → Ditransfer (dengan bukti) → Dikonfirmasi Diterima | Sistem |

Modul Kontrak Digital

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-25 | Sistem melakukan auto-generate dokumen kontrak (Talent Release) dari data proyek, Extras, dan fee yang disepakati, dengan harga kontrak mengikuti hasil negosiasi | Sistem |
| RF-26 | Admin Default dan Extras menandatangani kontrak melalui canvas signature (tanda tangan digital yang digambar langsung pada peramban), yang disematkan pada dokumen PDF kontrak; tanda tangan ini bukan tanda tangan elektronik tersertifikasi (PSrE) | Admin Default/Extras |
| RF-27 | Sistem menyimpan dan mengarsipkan dokumen kontrak final untuk setiap proyek dan setiap Extras | Sistem |

Modul Pembayaran Extras

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-28 | Admin Default menandai status pembayaran "Sudah Ditransfer" beserta unggahan bukti transfer | Admin Default |
| RF-29 | Extras mengonfirmasi penerimaan pembayaran | Extras |
| RF-30 | Sistem menampilkan rekap keuangan proyek (penerimaan dari client, payout kepada Extras, dan margin) khusus untuk Admin Default dan Super Admin | Admin Default/Super Admin |
| RF-31 | Sistem melakukan auto-generate invoice penagihan kepada client, yang ditandatangani melalui canvas signature oleh Admin Default dan Casting Director | Admin Default/CD |
| RF-32 | Admin Default atau Extras dapat menambahkan komponen tambahan (add-on) pada catatan pembayaran, seperti reimbursement transport atau penginapan, berupa label bebas dan nominal yang diisi manual sesuai kebutuhan | Admin Default |

Modul Absensi dan Penggajian Karyawan

Modul ini menjawab kebutuhan hasil bimbingan sekaligus permasalahan nyata di lapangan yang disampaikan pihak mitra: agensi belum memiliki slip gaji yang jelas untuk karyawannya, sehingga staf sering tidak mengetahui secara pasti nominal honor yang akan diterima. Modul ini menerapkan prinsip transparansi yang sama dengan modul negosiasi fee Extras, diterapkan untuk konteks internal staf agensi.

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-45 | Sistem mencatat status keaktifan setiap Admin pada suatu proyek sebagai log aktivitas, ditandai selesai ketika status proyek berubah menjadi "Selesai" | Sistem |
| RF-46 | Status "Selesai" pada log aktivitas Admin menjadi dasar kelayakan honor untuk proyek terkait | Sistem |
| RF-47 | Super Admin dapat menambahkan komponen tambahan (add-on) pada catatan honor Admin, seperti reimbursement transport atau penginapan, berupa label bebas dan nominal yang diisi manual sesuai kebutuhan | Super Admin |
| RF-48 | Sistem melakukan auto-generate slip honor (PDF) untuk setiap Admin pada saat proyek berstatus "Selesai", berisi rincian nominal honor pokok dan komponen tambahan (add-on) | Sistem |
| RF-49 | Super Admin dapat melihat rekap honor seluruh Admin pada dashboard monitoring | Super Admin |

Modul Pembatalan

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-33 | Admin Default atau Extras dapat membatalkan keikutsertaan pada status "Deal" dengan mengisi alasan pembatalan | Admin Default/Extras |
| RF-34 | Sistem mencatat riwayat pembatalan dan menghitung akumulasi pembatalan mendadak untuk setiap Extras | Sistem |
| RF-35 | Korlap dapat memberikan catatan atau sanksi terhadap Extras berdasarkan kondisi di lapangan (misalnya keterlambatan atau pelanggaran ketentuan wardrobe) | Korlap |

Modul Notifikasi

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-36 | Sistem mengirimkan notifikasi email untuk hasil seleksi, permintaan konfirmasi fee, dan permintaan tanda tangan kontrak | Sistem |
| RF-37 | Sistem mengirimkan notifikasi WhatsApp otomatis melalui `whatsapp-web.js` self-hosted (gratis, bukan gateway pihak ketiga berbayar) sebagai kanal pelengkap, untuk konfirmasi apply, hasil seleksi, pengingat jadwal shooting (H-1), dan pemberitahuan kontrak siap ditandatangani | Sistem |
| RF-38 | Admin Default dapat menginput tautan grup WhatsApp untuk setiap proyek sebagai kanal informasi lanjutan | Admin Default |

Modul Dashboard, Riwayat Kerja, dan Laporan

| Kode | Kebutuhan | Aktor |
|---|---|---|
| RF-39 | Sistem menyediakan dashboard sesuai kebutuhan masing-masing peran | Semua |
| RF-50 | Super Admin memiliki dashboard monitoring khusus berisi ringkasan seluruh kegiatan operasional (proyek berjalan, status honor staf) serta ringkasan seluruh akun sistem (jumlah dan status Extras, Casting Director, dan Admin) secara read-only, tanpa akses langsung ke operasional harian maupun aksi pengelolaan akun (aksi tersebut tetap wewenang Admin Default sesuai RF-05) | Super Admin |
| RF-51 | Admin Default dapat melihat rekap Extras yang paling sering terpilih dan rekap status keaktifan Extras | Admin Default |
| RF-52 | Admin Default dan Super Admin dapat mengekspor data rekap ke format Excel | Admin Default/Super Admin |

3.1.3. Kebutuhan Non-Fungsional

| Kode | Kategori | Kebutuhan |
|---|---|---|
| RNF-01 | Keamanan | Data sensitif (KTP, foto, video profil) disimpan dalam bentuk terenkripsi |
| RNF-02 | Keamanan | Penerapan Role-Based Access Control (RBAC) sesuai tujuh aktor sistem, termasuk pembatasan akses read-only untuk sub-role Talco dan Sosial Media/Multimedia |
| RNF-03 | Keamanan | Password disimpan dalam bentuk hash |
| RNF-04 | Performa | Sistem mampu menangani kurang lebih 50–80 Extras aktif dan 4–5 proyek casting aktif per bulan tanpa penurunan performa yang signifikan |
| RNF-05 | Usability | Antarmuka responsif pada perangkat desktop dan mobile, mudah digunakan oleh pengguna non-teknis (tombol berukuran besar, bahasa yang sederhana, indikator status berwarna konsisten) |
| RNF-06 | Reliability | Data tersimpan secara terpusat pada basis data, menggantikan penggunaan Excel, WhatsApp, dan Google Drive |
| RNF-07 | Maintainability | Sistem dibangun menggunakan framework Laravel dengan arsitektur Model-View-Controller |
| RNF-08 | Compatibility | Sistem dapat diakses melalui peramban pada perangkat desktop maupun mobile |
| RNF-09 | Availability | Sistem di-hosting pada shared hosting atau Virtual Private Server (VPS) |

3.1.4. Batasan Sistem

1. Sistem hanya mengelola Extras/figuran dan tidak mencakup talent profesional/pemeran utama; koordinasi kebutuhan talent utama (Talent Coordination) tercatat pada sistem hanya sebagai data penugasan dan riwayat kerja untuk keperluan honor, tanpa fitur operasional terhadap talent itu sendiri.
2. Production House (PH) sebagai entitas tidak memiliki akun pada sistem; PH diwakili oleh Casting Director yang memiliki akun melalui tautan registrasi terpisah.
3. Sistem tidak memproses pembayaran (bukan payment gateway); transfer dilakukan di luar sistem, dan sistem hanya mencatat status pembayaran.
4. Tanda tangan kontrak berupa canvas signature (tanda tangan digital yang digambar pada peramban), bukan tanda tangan elektronik tersertifikasi (PSrE).
5. Notifikasi WhatsApp menggunakan `whatsapp-web.js` self-hosted (otomasi sesi WhatsApp Web pribadi, gratis), bukan integrasi API resmi WhatsApp Business maupun layanan gateway pihak ketiga berbayar — konsisten dengan RF-37.
6. Deteksi bentrok jadwal bersifat peringatan (warning) dan tidak melakukan pemblokiran otomatis.
7. Validasi NIK dilakukan secara internal melalui pengecekan duplikasi pada basis data, tanpa integrasi dengan API Dukcapil.
8. Karyawan sub-role Admin (Talco, Korlap, Sosial Media/Multimedia) bukan merupakan karyawan tetap agensi; penugasan dan honor bersifat per-proyek, bukan gaji bulanan, dan tidak mencakup perhitungan potongan pajak maupun BPJS.
9. Absensi karyawan bersifat sederhana berupa log aktivitas sistem yang mengikuti status penyelesaian proyek, bukan verifikasi kehadiran berbasis lokasi (geolocation) atau foto check-in.

3.2 Metode Pengembangan

Sistem ini dikembangkan menggunakan metodologi Agile dengan kerangka kerja Scrum, sejalan dengan sifat proses bisnis agensi yang dinamis serta kebutuhan validasi bertahap dari mitra, PT. JBTB Casting Creative Group. Pendekatan iteratif dipilih karena ruang lingkup sistem — sebagaimana diuraikan pada 3.1 — mencakup dua kelompok modul yang saling terintegrasi (pengelolaan Extras dan pengelolaan karyawan internal agensi), sehingga validasi bertahap per sprint memungkinkan tim menyesuaikan detail kebutuhan tanpa menunggu seluruh sistem selesai dibangun.

3.2.1. Peran Scrum

Product Owner dijalankan bersama oleh tim peneliti dan pihak PT. JBTB Casting Creative Group, yaitu Jestika Aisya Kordak selaku Direktur Utama/Super Admin dan Erlina Stepani Gultom selaku Direktur Keuangan yang memvalidasi kebutuhan data dan proses keuangan, termasuk kebutuhan penggajian karyawan. Scrum Master dan Development Team dijalankan oleh tim peneliti, dengan Fakhrul Mukhlisin sebagai pengembang utama dan Imanisa yang berperan dalam analisis kebutuhan serta pengujian.

3.2.2. Pembagian Sprint

Pengembangan dibagi menjadi enam sprint dengan durasi masing-masing dua minggu, disusun berdasarkan urutan ketergantungan antarmodul serta tingkat risiko implementasi. Modul yang bersifat baru atau melibatkan ketergantungan pada pihak ketiga ditempatkan pada sprint yang lebih awal, sedangkan modul yang bersifat pelengkap dan berisiko rendah ditempatkan pada sprint akhir.

| Sprint | Modul | Fokus |
|---|---|---|
| Sprint 1 | Autentikasi dan Manajemen Akun, Profil Extras | Fondasi: login RBAC tujuh peran, registrasi terpisah untuk CD, profil Extras, serta inisiasi integrasi WhatsApp Gateway |
| Sprint 2 | Manajemen Proyek Casting, Pendaftaran dan Seleksi | Posting lowongan, pendaftaran, filter kandidat, deteksi bentrok jadwal |
| Sprint 3 | Grade, Negosiasi Fee, Review dan Approval CD | Penetapan grade dan tawar-menawar fee bertingkat hingga Deal, dilanjutkan dengan approve/reject kandidat oleh CD |
| Sprint 4 | Kontrak Digital, Invoice | Auto-generate kontrak, canvas signature, invoice dengan tanda tangan Admin Default dan CD |
| Sprint 5 | Manajemen Karyawan, Absensi, dan Penggajian Staf | Penambahan akun Admin dan sub-role oleh Super Admin, log aktivitas per proyek, auto-generate slip honor, dashboard monitoring Super Admin |
| Sprint 6 | Pembayaran, Dashboard, Riwayat Kerja, Laporan | Penandaan transfer dan bukti pembayaran Extras, rekap riwayat kerja seluruh Admin, ekspor rekap ke Excel |

3.2.3. Pemodelan dan Pengujian

Pemodelan sistem menggunakan Unified Modeling Language (UML), yang meliputi Use Case Diagram, Activity Diagram, Sequence Diagram, dan Class Diagram. Pengujian sistem menggunakan metode Black Box Testing pada setiap modul fungsional, dengan skenario pengujian berupa input, hasil yang diharapkan, hasil aktual, dan status (Pass/Fail).

3.3 Timeline

Timeline berikut mencakup tahapan pengajuan proposal hingga sidang judul (sempro), sesuai jadwal program studi pada September 2026. Tahapan pengembangan sistem (pembagian sprint pada 3.2.2) disusun terpisah dan akan dituangkan pada dokumen Laporan Akhir setelah judul dinyatakan disetujui.

| Tahapan | Aktivitas | Estimasi Waktu |
|---|---|---|
| Penyusunan Proposal | Revisi dan konsolidasi proposal (Bab I–III) bersama tim dan hasil bimbingan dosen pembimbing | Agustus 2026 |
| Pendaftaran Seminar Proposal (Sempro) | Pengajuan berkas proposal ke program studi | 1 September 2026 |
| Sidang Judul | Presentasi dan validasi judul serta ruang lingkup proyek di hadapan dosen pembimbing/penguji | 3 September 2026 (dapat berubah menyesuaikan jadwal dosen pembimbing) |

Setelah sidang judul disetujui ("di-acc"), tahapan pengembangan sistem berjalan sesuai pembagian sprint pada 3.2.2, dengan estimasi total durasi pengembangan kurang lebih tiga bulan, diikuti tahapan pengujian dan penyusunan Laporan Akhir sebelum sidang/UAPS sesuai jadwal program studi.
