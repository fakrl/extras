# Business & Technical Requirement Summary (Final)
## Sistem Informasi Manajemen Casting Talent dan Extras Berbasis Web dengan Fitur Negosiasi Fee Digital
### Studi Kasus: JBTB Casting

> **⚠️ SUPERSEDED (sebagian).** Dokumen riset awal — 3 aktor, kemungkinan CAPTCHA/verifikasi email wajib, NIK di registrasi. Acuan terkini: `docs/CLAUDE.md` & `docs/BAB-3-DRAFT.md` (7 aktor, NIK dikumpulkan saat lolos seleksi bukan saat registrasi/data minimization UU PDP, lihat `CLAUDE.md` §8 poin 4). Jangan jadikan dasar implementasi tanpa cross-check ke dokumen terkini.

| | |
|---|---|
| Versi Dokumen | 2.0 (Final — seluruh poin telah dikonfirmasi) |
| Terkait | 01-Kebutuhan-Fungsional-NonFungsional, 02-Aktor-dan-Narasi-UseCase, 03-UseCase-Diagram, 04–06-Activity-Diagram, 07–08-Sequence-Diagram, 09-ERD, 10-Addendum-Kebutuhan-Teknis |
| Terakhir Diperbarui | 14 Juli 2026 |

---

## 1. Business Information

| Item | Keterangan |
|---|---|
| **Nama Sistem** | Sistem Informasi Manajemen Casting Talent dan Extras Berbasis Web dengan Fitur Negosiasi Fee Digital (disingkat: **SIM Casting JBTB**) |
| **Latar Belakang** | JBTB Casting saat ini menjalankan proses rekrutmen dan manajemen extras/figuran secara manual menggunakan grup WhatsApp (komunikasi & rekrutmen), Excel (rekap data), dan Google Drive (penyimpanan dokumen). |
| **Tujuan Sistem** | Membangun sistem terpusat yang mengelola pendaftaran, seleksi, negosiasi fee, dan kontrak extras secara digital — menggantikan proses manual agar lebih cepat, transparan, dan minim kesalahan. |
| **Permasalahan Saat Ini** | (1) Proses rekrutmen via WhatsApp lambat dan memakan waktu; (2) sering terjadi kesalahpahaman fee antara agensi dan calon extras; (3) data tersebar & sering tidak tersimpan dengan baik di Excel/Gdrive, memakan banyak ruang penyimpanan; (4) keputusan Casting Director (CD) lambat karena tidak ada sistem review terpusat; (5) informasi ke extras yang lolos sering disampaikan dadakan. |

---

## 2. User & Stakeholder

| Item | Keterangan |
|---|---|
| **Aktor** | Admin Agensi, Casting Director (CD), Extras. Production House (PH) **bukan** aktor sistem — kebutuhan casting dari PH disampaikan secara offline ke Admin, kemudian Admin yang menginputnya sebagai Proyek Casting. |
| **Role** | Admin Agensi: 1 tingkatan saja (tanpa sub-role Super Admin/Staff Admin). Casting Director: 1 tingkatan, memiliki akun login sendiri, orangnya berbeda dari PH. Extras: self-register, terbuka untuk publik. |
| **Hak Akses** | Role-Based Access Control (RBAC) — setiap aktor hanya dapat mengakses modul sesuai perannya. **Admin**: akses penuh ke seluruh modul operasional & laporan (manajemen proyek, template kontrak, filter kandidat, negosiasi fee, notifikasi, rekap). **CD**: hanya modul review/approve kandidat & dashboard miliknya sendiri. **Extras**: hanya data & aksi terkait akun/pendaftarannya sendiri (profil, status casting, konfirmasi fee, upload kontrak). |

---

## 3. Platform

| Item | Keterangan |
|---|---|
| **Web** | Platform utama dan satu-satunya sistem ini. |
| **Mobile** | Tidak ada aplikasi mobile native (Android/iOS) — akses dari perangkat mobile dilakukan melalui browser. |
| **Desktop** | Dapat diakses melalui browser desktop. |
| **Responsive Requirement** | Wajib responsif di desktop maupun mobile browser, mengingat mayoritas Extras kemungkinan mengakses melalui HP. |

---

## 4. Authentication & Security

| Item | Keterangan |
|---|---|
| **Login** | Satu sistem login dengan hak akses berbeda untuk Admin, CD, dan Extras (email & password). |
| **Registrasi** | Self-register **hanya untuk Extras**, dengan input data diri, foto, portofolio, dan video profil. Akun Admin & CD dibuat/dikelola oleh Admin, tidak melalui self-register. |
| **MFA (Multi-Factor Authentication)** | **Tidak digunakan.** Login cukup menggunakan email & password, mempertimbangkan skala pengguna kecil (±70 extras) dan target pengguna non-teknis. |
| **Email Verification** | **Digunakan.** Extras wajib mencantumkan dan memverifikasi email saat registrasi mandiri; akun baru berstatus aktif hanya setelah email diverifikasi. |
| **Forgot Password** | **Disertakan.** Seluruh aktor (Admin, CD, Extras) dapat melakukan reset password melalui link/token yang dikirim ke email. |
| **CAPTCHA** | **Diterapkan** pada form registrasi dan login, untuk mencegah bot mengingat registrasi Extras bersifat terbuka ke publik. |

---

## 5. Functional Requirement

| Item | Keterangan |
|---|---|
| **Modul** | 10 modul: (1) Autentikasi & Akun, (2) Profil Extras, (3) Manajemen Proyek Casting, (4) Pendaftaran & Seleksi, (5) Negosiasi Fee Digital, (6) Kontrak Digital, (7) Pembatalan, (8) Notifikasi, (9) Dashboard, (10) Laporan/Rekap. |
| **Fitur** | Self-register extras dengan verifikasi email, filter kandidat multi-kriteria, approval CD, negosiasi fee maksimal 1x pengajuan alternatif, auto-generate kontrak PDF dari template dinamis, upload dokumen ber-TTD, pencatatan pembatalan otomatis, notifikasi email & web push, dashboard per role, ekspor rekap ke Excel. |
| **Workflow** | State machine berjenjang: `Diajukan → Direview CD → Lolos/Ditolak → Nego Fee → Deal → Kontrak Ditandatangani → Selesai Produksi`, dengan cabang `Dibatalkan` dari status Deal maupun Kontrak Ditandatangani. |
| **Approval Process** | Dua titik approval: (1) CD melakukan approve/reject terhadap kandidat yang diajukan Admin; (2) Admin memutuskan final atas pengajuan fee alternatif dari Extras (bersifat final, tidak dapat diubah lagi). |
| **Business Rules** | 1 NIK = 1 akun (divalidasi internal saat lolos seleksi); fee alternatif maksimal 1x pengajuan per pendaftaran; 3x pembatalan mendadak pada proyek berbeda → status "Melanggar" otomatis; tanda tangan kontrak berupa unggah PDF hasil TTD manual/scan, bukan e-signature tersertifikasi. |

---

## 6. Asset / Data Requirement

| Item | Keterangan |
|---|---|
| **Master Data** | Data akun (Admin, CD, Extras), profil Extras, proyek casting, template kontrak. |
| **Transaction Data** | Pendaftaran per proyek, riwayat negosiasi fee, dokumen kontrak per pendaftar, catatan pembatalan. |
| **Historical Data** | Riwayat negosiasi fee (fee awal/alternatif/final), riwayat pembatalan & akumulasinya, riwayat casting yang pernah diikuti Extras, arsip kontrak final. |
| **Retention Policy** | Data (termasuk dokumen KTP, foto, video profil, dan kontrak) disimpan dengan **masa retensi 1 (satu) tahun**, sebelum dipertimbangkan untuk diarsipkan/dihapus. |

---

## 7. Notification Requirement

| Item | Keterangan |
|---|---|
| **Email** | **Kanal notifikasi utama** sistem (SMTP Gmail) — dikirim untuk: hasil seleksi (lolos/ditolak), permintaan konfirmasi fee, dan permintaan tanda tangan kontrak. |
| **WhatsApp** | **Bukan** integrasi API otomatis. Admin hanya menginput/memperbarui **link grup WhatsApp** per proyek casting secara manual sebagai kanal informasi lanjutan bagi Extras yang lolos. |
| **SMS** | **Tidak digunakan.** Tidak menjadi bagian dari kanal notifikasi sistem ini. |
| **Push Notification** | **Digunakan — Web Push Notification** (notifikasi browser via service worker), sebagai kanal tambahan di luar email untuk mempercepat informasi ke Extras (mis. status pendaftaran, permintaan konfirmasi fee, kontrak siap TTD) — relevan untuk mengatasi masalah "info dadakan" yang menjadi salah satu akar masalah sistem lama. Dipilih Web Push (bukan push notification aplikasi native) karena sistem tidak memiliki aplikasi mobile native. |

> **Rekomendasi kanal notifikasi:** Email tetap menjadi kanal utama & resmi (tercatat, formal, cocok untuk konfirmasi fee dan kontrak). Web Push digunakan sebagai kanal pelengkap untuk notifikasi cepat/real-time saat Extras sedang membuka browser. Link grup WA tetap dipertahankan sebagai kanal informasi informal/komunitas per proyek, sesuai kebiasaan pengguna saat ini. Kombinasi ini paling sesuai dengan skala sistem (±70 extras) tanpa perlu integrasi API pihak ketiga yang kompleks (WhatsApp Business API, SMS gateway berbayar, dsb.).

---

## 8. Reporting Requirement

| Item | Keterangan |
|---|---|
| **Dashboard** | Tiga dashboard berbeda per role: **Admin** (ringkasan proyek berjalan, jumlah CD, jumlah extras terdaftar, jumlah casting dibuka, jumlah pendaftar, jadwal shooting terdekat, notifikasi data yang perlu diproses); **CD** (jumlah proyek ditangani, daftar casting aktif, jumlah pendaftar per casting, daftar extras lolos, jadwal casting hari ini, notifikasi pendaftar baru); **Extras** (profil singkat, status pendaftaran, daftar casting dibuka, jadwal shooting jika diterima, riwayat mengikuti casting, notifikasi terkait). |
| **Report** | Rekap extras yang paling sering terpilih per periode; rekap status keaktifan seluruh extras. |
| **Export** | Admin dapat mengekspor data rekap ke format Excel. |

---

## 9. Integration Requirement

| Item | Keterangan |
|---|---|
| **API** | Tidak ada kebutuhan API eksternal untuk fungsi bisnis inti — sistem bersifat berdiri sendiri (monolitik berbasis Laravel). |
| **Third Party** | Secara eksplisit **tidak** terintegrasi dengan API Dukcapil (validasi NIK hanya internal) dan **tidak** terintegrasi API WhatsApp otomatis (hanya link grup manual). |
| **Payment** | Sistem **tidak** memproses pembayaran / bukan payment gateway. Fee dibayarkan via transfer bank di luar sistem; sistem hanya mencatat status kesepakatan fee. |
| **Email** | Menggunakan layanan **SMTP Gmail** untuk pengiriman seluruh notifikasi email sistem. |
| **WhatsApp** | Tidak ada integrasi API WhatsApp otomatis; hanya link grup WA yang diinput manual oleh Admin per proyek casting. |

---

## 10. Technical Requirement

| Item | Keterangan |
|---|---|
| **Hosting** | Shared hosting atau VPS, agar dapat diakses kapan saja oleh Admin, CD, dan Extras. |
| **Database** | **MySQL**, mengikuti konvensi standar framework Laravel. |
| **Security** | Enkripsi data sensitif (KTP, foto, video profil); Role-Based Access Control sesuai 3 aktor; password disimpan dalam bentuk hash (bukan plain text); CAPTCHA pada form publik; verifikasi email wajib untuk aktivasi akun Extras. |
| **Scalability** | Dirancang untuk menangani ±70 extras terdaftar dan ±3 proyek casting aktif per bulan tanpa penurunan performa signifikan — skala kecil-menengah, tidak memerlukan arsitektur scaling kompleks (load balancer, microservices, dsb.) pada tahap ini. |

---

## Status Dokumen

Seluruh poin pada dokumen ini **sudah final dan terkonfirmasi** (tidak ada lagi item ⚠️ terbuka). Dokumen ini menjadi rujukan tunggal (*single source of truth*) untuk melanjutkan ke tahap perancangan **Class Diagram** dan **Rancangan Pengujian Black Box**.
