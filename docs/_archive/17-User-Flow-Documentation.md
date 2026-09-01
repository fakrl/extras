# User Flow Documentation
## Sistem Informasi Manajemen Casting Talent dan Extras Berbasis Web dengan Fitur Negosiasi Fee Digital

> **⚠️ SUPERSEDED (sebagian).** Flow berbasis desain 3-aktor lama (fee fixed, TTD upload scan, dsb). Acuan terkini: state machine `docs/CLAUDE.md` §6 (7 aktor, nego fee InDrive, canvas signature). Struktur/pola dokumentasi flow (preconditions, alternative/exception flow) masih berguna sebagai referensi format, tapi isi aktor/alur bisnisnya jangan dipakai langsung.

Dokumen ini mendefinisikan 9 user flow utama sistem, masing-masing dengan Flow Name, Actor, Trigger, Preconditions, Main Flow, Alternative Flow, Exception Flow, Validation Rules, Error Scenario, Post Conditions, dan Mermaid Flowchart.

---

## Flow 1 — Registrasi Extras

| Item | Deskripsi |
|---|---|
| **Flow Name** | Registrasi Mandiri Extras |
| **Actor** | Extras (calon pengguna publik) |
| **Trigger** | Pengguna mengakses halaman registrasi dan mengisi form pendaftaran |
| **Preconditions** | Belum memiliki akun terdaftar dengan NIK/email yang sama |
| **Main Flow** | 1. Isi data diri, foto, portofolio, video profil<br>2. Isi email & password<br>3. Selesaikan CAPTCHA<br>4. Submit form<br>5. Sistem kirim email verifikasi<br>6. Pengguna klik link verifikasi<br>7. Akun berstatus "Aktif" |
| **Alternative Flow** | Jika usia < 17 tahun → sistem meminta unggah dokumen persetujuan orang tua/wali sebelum submit dapat dilanjutkan (RF-54) |
| **Exception Flow** | - Link verifikasi kadaluarsa (>24 jam) → pengguna dapat meminta kirim ulang link dengan token baru (RF-51)<br>- Email belum dikonfirmasi → akses fitur lain tetap terkunci |
| **Validation Rules** | NIK unik (RF-03); email format valid & unik; password minimal 8 karakter + kapital + angka + simbol (RF-57); whitelist tipe file: foto (jpg/png), video (mp4) serta batas ukuran maksimum (RF-44) |
| **Error Scenario** | - NIK/email sudah terdaftar → pesan error<br>- Tipe/ukuran file tidak sesuai → pesan error & diminta unggah ulang<br>- CAPTCHA gagal → diminta ulangi |
| **Post Conditions** | Akun Extras tercipta dengan status "Menunggu Verifikasi" → berubah "Aktif" setelah email diverifikasi |

```mermaid
flowchart TD
    A([Mulai]) --> B[Isi form registrasi:<br/>data diri, foto, portofolio, video]
    B --> C{Usia < 17 tahun?}
    C -- Ya --> D[Unggah dokumen<br/>persetujuan orang tua/wali]
    D --> E
    C -- Tidak --> E[Isi email, password, CAPTCHA]
    E --> F{Validasi data & file valid?}
    F -- Tidak --> G[Tampilkan pesan error]
    G --> B
    F -- Ya --> H{NIK/Email sudah terdaftar?}
    H -- Ya --> I[Error: akun sudah ada]
    I --> Z1([Selesai])
    H -- Tidak --> J[Sistem buat akun: status Menunggu Verifikasi]
    J --> K[Sistem kirim email verifikasi]
    K --> L{Klik link dalam 24 jam?}
    L -- Tidak --> M[Kirim ulang link verifikasi<br/>dengan token baru]
    M --> L
    L -- Ya --> N[Status akun: Aktif]
    N --> Z2([Selesai])
```

---

## Flow 2 — Login & Proteksi Akun

| Item | Deskripsi |
|---|---|
| **Flow Name** | Login dengan Proteksi Brute-Force |
| **Actor** | Admin, Casting Director, Extras |
| **Trigger** | Pengguna mengakses halaman login dan submit kredensial |
| **Preconditions** | Akun sudah terdaftar dan berstatus aktif (khusus Extras: email sudah terverifikasi) |
| **Main Flow** | 1. Input email & password<br>2. Selesaikan CAPTCHA<br>3. Submit<br>4. Sistem validasi kredensial<br>5. Redirect ke dashboard sesuai role |
| **Alternative Flow** | Akun Extras belum verifikasi email → diarahkan ke halaman "kirim ulang verifikasi" |
| **Exception Flow** | Password salah 3x berturut-turut → akun terkunci sementara (RF-58); pengguna diarahkan ke reset password atau menunggu Admin membuka kunci |
| **Validation Rules** | Kombinasi email & password harus cocok; CAPTCHA valid |
| **Error Scenario** | - Kredensial salah → pesan error + counter percobaan bertambah<br>- Akun terkunci → pesan "Akun terkunci, silakan reset password" |
| **Post Conditions** | Sesi (session) pengguna terbentuk sesuai role; atau akun berstatus terkunci (`is_locked = true`) |

```mermaid
flowchart TD
    A([Mulai]) --> B[Input email, password, CAPTCHA]
    B --> C{Akun ditemukan & aktif?}
    C -- Tidak --> D[Error: akun tidak ditemukan/belum aktif]
    D --> Z1([Selesai])
    C -- Ya --> E{Password benar?}
    E -- Ya --> F[Reset counter gagal login]
    F --> G[Buat sesi sesuai role]
    G --> H{Role?}
    H -- Admin --> I1([Dashboard Admin])
    H -- CD --> I2([Dashboard CD])
    H -- Extras --> I3([Dashboard Extras])
    E -- Tidak --> J[Counter gagal login += 1]
    J --> K{Counter >= 3?}
    K -- Tidak --> B
    K -- Ya --> L[Akun terkunci: is_locked = true]
    L --> M([Arahkan ke Reset Password])
```

---

## Flow 3 — Forgot Password / Kirim Ulang Verifikasi

| Item | Deskripsi |
|---|---|
| **Flow Name** | Pemulihan Akun (Forgot Password & Resend Verification) |
| **Actor** | Admin, Casting Director, Extras |
| **Trigger** | Pengguna klik "Lupa Password" atau "Kirim Ulang Email Verifikasi" |
| **Preconditions** | Akun dengan email tersebut terdaftar di sistem |
| **Main Flow** | 1. Input email<br>2. Sistem membuat token reset/verifikasi baru dengan masa berlaku 24 jam<br>3. Sistem kirim email berisi link<br>4. Pengguna klik link<br>5. Untuk reset password: input password baru sesuai kebijakan<br>6. Submit → password/akun diperbarui |
| **Alternative Flow** | Link belum diterima/hilang → pengguna dapat meminta kirim ulang (token lama otomatis tidak berlaku) |
| **Exception Flow** | Token kadaluarsa (>24 jam) → link tidak valid, pengguna diminta memulai proses ulang |
| **Validation Rules** | Password baru wajib memenuhi kebijakan: min. 8 karakter, 1 kapital, 1 angka, 1 simbol (RF-57) |
| **Error Scenario** | - Email tidak terdaftar → pesan generik (tidak mengonfirmasi/menyangkal keberadaan akun, demi keamanan)<br>- Token kadaluarsa → pesan "Link telah kedaluwarsa" |
| **Post Conditions** | Password diperbarui dan akun otomatis terbuka (`is_locked = false`) jika sebelumnya terkunci; atau akun terverifikasi |

```mermaid
flowchart TD
    A([Mulai]) --> B[Input email]
    B --> C[Sistem buat token baru,<br/>masa berlaku 24 jam]
    C --> D[Sistem kirim email berisi link]
    D --> E{Klik link dalam 24 jam?}
    E -- Tidak --> F[Token kedaluwarsa]
    F --> G([Minta kirim ulang link])
    G --> C
    E -- Ya --> H[Input password baru]
    H --> I{Sesuai kebijakan password?}
    I -- Tidak --> H
    I -- Ya --> J[Update password, buka kunci akun]
    J --> Z([Selesai])
```

---

## Flow 4 — Pendaftaran Extras pada Proyek Casting

| Item | Deskripsi |
|---|---|
| **Flow Name** | Pendaftaran Extras pada Proyek Casting |
| **Actor** | Extras |
| **Trigger** | Extras memilih proyek casting yang terbuka dan menekan tombol "Daftar" |
| **Preconditions** | Akun aktif & profil lengkap; proyek berstatus "Terbuka" dan belum melewati deadline |
| **Main Flow** | 1. Extras membuka daftar proyek casting terbuka<br>2. Memilih satu proyek<br>3. Mengonfirmasi data profil yang akan diajukan<br>4. Submit pendaftaran<br>5. Sistem menyimpan status "Diajukan" |
| **Alternative Flow** | Extras dapat mendaftar ke beberapa proyek sekaligus secara paralel (multi-proyek diperbolehkan) |
| **Exception Flow** | Deadline proyek sudah lewat → tombol "Daftar" nonaktif, pesan informasi ditampilkan |
| **Validation Rules** | Profil Extras harus sudah lengkap (data wajib RF-06 terisi) sebelum dapat mendaftar |
| **Error Scenario** | - Proyek sudah ditutup di antara waktu Extras membuka halaman dan submit → pesan error "Proyek telah ditutup" |
| **Post Conditions** | Baris data `pendaftaran` baru tercipta dengan status "Diajukan"; proyek dapat mulai difilter oleh Admin |

```mermaid
flowchart TD
    A([Mulai]) --> B[Extras membuka daftar<br/>proyek casting terbuka]
    B --> C[Pilih proyek casting]
    C --> D{Deadline masih berlaku?}
    D -- Tidak --> E[Tombol Daftar nonaktif]
    E --> Z1([Selesai])
    D -- Ya --> F{Profil Extras lengkap?}
    F -- Tidak --> G[Diminta melengkapi profil]
    G --> Z2([Selesai])
    F -- Ya --> H[Konfirmasi & submit pendaftaran]
    H --> I[Sistem: status = Diajukan]
    I --> Z3([Selesai])
```

---

## Flow 5 — Seleksi Kandidat (Filter Admin → Review CD)

| Item | Deskripsi |
|---|---|
| **Flow Name** | Seleksi Kandidat: Filter Admin dan Review Casting Director |
| **Actor** | Admin Agensi, Casting Director |
| **Trigger** | Admin membuka daftar pendaftar suatu proyek casting |
| **Preconditions** | Proyek casting memiliki pendaftar berstatus "Diajukan" |
| **Main Flow** | 1. Admin memfilter pendaftar sesuai kriteria proyek<br>2. Admin mengajukan kandidat terpilih ke CD (status → "Direview CD")<br>3. CD meninjau profil kandidat<br>4. CD memutuskan Approve/Reject<br>5. Sistem memperbarui status & mengirim notifikasi ke Extras |
| **Alternative Flow** | Jika kandidat "Lolos" dan usia < 17 tahun, sistem mewajibkan verifikasi ulang kelengkapan dokumen wali sebelum lanjut ke tahap fee |
| **Exception Flow** | Review CD melewati SLA 2 hari → sistem menampilkan indikator "Terlambat" pada dashboard CD dan Admin (tidak menghentikan proses, hanya penanda) |
| **Validation Rules** | Filter kriteria harus sesuai field kriteria proyek (usia, gender, tinggi, dll.) |
| **Error Scenario** | CD menolak kandidat tanpa alasan wajib → sistem tetap memproses namun disarankan mengisi catatan untuk audit |
| **Post Conditions** | Status pendaftar menjadi "Lolos" (lanjut ke Negosiasi Fee) atau "Ditolak" (proses berakhir) |

```mermaid
flowchart TD
    A([Mulai]) --> B[Admin: filter pendaftar<br/>sesuai kriteria]
    B --> C[Admin: ajukan kandidat ke CD]
    C --> D[Sistem: status = Direview CD<br/>mulai hitung SLA 2 hari]
    D --> E[CD: review profil kandidat]
    E --> F{SLA 2 hari terlampaui?}
    F -- Ya --> G[Tampilkan indikator Terlambat<br/>di dashboard CD/Admin]
    G --> E
    F -- Tidak --> H{Keputusan CD}
    H -- Reject --> I[Status = Ditolak + notifikasi Extras]
    I --> Z1([Selesai])
    H -- Approve --> J[Status = Lolos]
    J --> K[Extras: upload KTP]
    K --> Z2([Lanjut ke Negosiasi Fee])
```

---

## Flow 6 — Negosiasi Fee Digital

| Item | Deskripsi |
|---|---|
| **Flow Name** | Negosiasi Fee Digital |
| **Actor** | Admin Agensi, Extras |
| **Trigger** | Kandidat dinyatakan "Lolos" oleh CD |
| **Preconditions** | Status pendaftaran = "Lolos"; KTP telah diunggah |
| **Main Flow** | 1. Admin mengajukan fee tetap<br>2. Extras meninjau tawaran<br>3. Extras memilih Terima atau Ajukan Fee Alternatif<br>4. Jika Terima → status "Deal"<br>5. Jika Ajukan Alternatif → Admin memutuskan setuju/tolak (final) |
| **Alternative Flow** | Jika kuota proyek sudah tercapai oleh pendaftar lain yang lebih dulu "Deal" → status otomatis "Cadangan (Back Up)" atau "Ditolak (Kuota Penuh)" |
| **Exception Flow** | Extras tidak merespons dalam 24 jam → status "Tidak Merespons"; Admin dapat mengalihkan tawaran ke kandidat cadangan |
| **Validation Rules** | Pengajuan fee alternatif hanya dapat dilakukan **maksimal 1 kali** per pendaftaran |
| **Error Scenario** | Extras mencoba mengajukan fee alternatif kedua kalinya → sistem menolak dan menampilkan pesan bahwa kesempatan sudah digunakan |
| **Post Conditions** | Status "Deal" dengan fee final tercatat (lanjut ke Kontrak Digital), atau "Tidak Merespons"/"Nego Gagal" |

```mermaid
flowchart TD
    A([Mulai: Status Lolos]) --> B[Admin: ajukan fee tetap]
    B --> C[Sistem: notifikasi ke Extras]
    C --> D{Respons dalam 24 jam?}
    D -- Tidak --> E[Status = Tidak Merespons]
    E --> F([Admin alihkan ke kandidat cadangan])
    D -- Ya, Terima --> G{Kuota proyek masih tersedia?}
    G -- Tidak --> H[Status = Cadangan/Ditolak Kuota Penuh]
    H --> Z1([Selesai])
    G -- Ya --> I[Status = Deal, fee awal]
    I --> Z2([Lanjut ke Kontrak Digital])
    D -- Ya, Ajukan Alternatif 1x --> J[Extras: input nominal alternatif]
    J --> K[Admin: tinjau & putuskan]
    K --> L{Setuju?}
    L -- Ya --> M[Status = Deal, fee baru]
    M --> Z2
    L -- Tidak --> N[Status = Nego Gagal]
    N --> Z3([Selesai])
```

---

## Flow 7 — Kontrak Digital

| Item | Deskripsi |
|---|---|
| **Flow Name** | Pembuatan & Penandatanganan Kontrak Digital |
| **Actor** | Admin Agensi, Extras |
| **Trigger** | Status pendaftaran berubah menjadi "Deal" |
| **Preconditions** | Fee final telah disepakati; data Extras & proyek lengkap |
| **Main Flow** | 1. Sistem generate PDF kontrak dari template<br>2. Admin unduh, TTD manual, unggah PDF ber-TTD Admin<br>3. Sistem notifikasi ke Extras<br>4. Extras unduh, TTD manual, unggah PDF ber-TTD lengkap<br>5. Admin memverifikasi dokumen<br>6. Jika disetujui → status "Kontrak Ditandatangani", dokumen immutable |
| **Alternative Flow** | Admin dapat mengelola/mengedit template kontrak sebelum proses generate dilakukan |
| **Exception Flow** | - Data tidak lengkap saat generate → proses dibatalkan, diminta melengkapi data (RF-68)<br>- Admin menolak verifikasi dokumen final → Extras diminta unggah ulang |
| **Validation Rules** | File yang diunggah harus format PDF, sesuai batas ukuran maksimum (RF-44) |
| **Error Scenario** | Upload gagal (koneksi terputus) → sistem menampilkan pesan error standar, tanpa resume otomatis (RF-69) |
| **Post Conditions** | Status "Kontrak Ditandatangani"; dokumen kontrak final tidak dapat diedit/dihapus (`is_immutable = true`) |

```mermaid
flowchart TD
    A([Mulai: Status Deal]) --> B{Data lengkap?}
    B -- Tidak --> C[Batalkan proses,<br/>minta lengkapi data]
    C --> A
    B -- Ya --> D[Sistem: generate PDF kontrak]
    D --> E[Admin: unduh, TTD manual, unggah]
    E --> F{Upload berhasil?}
    F -- Tidak --> G[Pesan error, ulangi upload]
    G --> E
    F -- Ya --> H[Sistem: notifikasi ke Extras]
    H --> I[Extras: unduh, TTD manual, unggah]
    I --> J[Admin: verifikasi dokumen final]
    J --> K{Disetujui?}
    K -- Tidak --> L[Extras diminta unggah ulang]
    L --> I
    K -- Ya --> M[Status = Kontrak Ditandatangani<br/>dokumen immutable]
    M --> Z([Selesai])
```

---

## Flow 8 — Pembatalan (Cancel)

| Item | Deskripsi |
|---|---|
| **Flow Name** | Pembatalan Keikutsertaan (Cancel) |
| **Actor** | Admin Agensi, Extras |
| **Trigger** | Salah satu pihak mengajukan pembatalan pada pendaftaran berstatus "Deal" atau "Kontrak Ditandatangani" |
| **Preconditions** | Status pendaftaran = "Deal" atau "Kontrak Ditandatangani" |
| **Main Flow** | 1. Pihak yang membatalkan mengisi alasan<br>2. Submit permintaan pembatalan<br>3. Sistem menghitung selisih waktu ke tanggal shooting<br>4. Sistem menandai "mendadak" jika < H-2<br>5. Status pendaftaran → "Dibatalkan" |
| **Alternative Flow** | Jika kontrak sudah berstatus immutable, dokumen kontrak tetap tersimpan sebagai arsip meskipun pendaftaran dibatalkan (dokumen tidak dihapus) |
| **Exception Flow** | Pendaftaran yang sudah berstatus "Dibatalkan" tidak dapat dibatalkan ulang |
| **Validation Rules** | Alasan pembatalan wajib diisi |
| **Error Scenario** | Percobaan submit tanpa alasan → sistem menolak dan meminta alasan diisi |
| **Post Conditions** | Status = "Dibatalkan"; jika "mendadak", `jumlah_pembatalan` Extras bertambah 1 — jika mencapai 3x pada proyek berbeda, status Extras otomatis "Melanggar" |

```mermaid
flowchart TD
    A([Mulai]) --> B{Status saat ini}
    B -- Sudah Dibatalkan --> C[Error: tidak dapat dibatalkan ulang]
    C --> Z1([Selesai])
    B -- Deal/Kontrak Ditandatangani --> D[Isi alasan pembatalan]
    D --> E{Alasan diisi?}
    E -- Tidak --> D
    E -- Ya --> F[Sistem: hitung selisih hari<br/>ke tanggal shooting]
    F --> G{"kurang dari H-2 dari shooting?"}
    G -- Ya --> H[Tandai: mendadak]
    H --> I[jumlah_pembatalan += 1]
    I --> J{"jumlah_pembatalan mendadak >= 3 pada proyek berbeda?"}
    J -- Ya --> K[Status Extras = Melanggar]
    K --> L[Status pendaftaran = Dibatalkan]
    J -- Tidak --> L
    G -- Tidak --> L
    L --> Z2([Selesai])
```

---

## Flow 9 — Penutupan Proyek Casting

| Item | Deskripsi |
|---|---|
| **Flow Name** | Penutupan Proyek Casting |
| **Actor** | Admin Agensi |
| **Trigger** | Deadline proyek tercapai, atau Admin memilih menutup proyek secara manual |
| **Preconditions** | Proyek berstatus "Terbuka" |
| **Main Flow** | 1. Sistem mengecek apakah seluruh pendaftar sudah berstatus akhir (Deal/Ditolak/Dibatalkan)<br>2. Jika ya → proyek otomatis berstatus "Ditutup"<br>3. Jika tidak → Admin memilih: perpanjang deadline, atau tutup manual |
| **Alternative Flow** | Admin dapat memilih **"Tutup Paksa"** meskipun masih ada pendaftar aktif |
| **Exception Flow** | Tutup Paksa memerlukan **konfirmasi eksplisit** dari Admin sebelum dieksekusi |
| **Validation Rules** | Opsi "Tutup Paksa" wajib menampilkan dialog konfirmasi sebelum dieksekusi |
| **Error Scenario** | Admin membatalkan konfirmasi Tutup Paksa → proyek tetap berstatus "Terbuka" |
| **Post Conditions** | Proyek berstatus "Ditutup" (normal) atau "Ditutup Paksa"; jika Tutup Paksa → seluruh pendaftar aktif tersisa otomatis berstatus "Dibatalkan Sistem" |

```mermaid
flowchart TD
    A([Mulai: Deadline tercapai / Admin pilih tutup]) --> B{Semua pendaftar<br/>berstatus akhir?}
    B -- Ya --> C[Status Proyek = Ditutup]
    C --> Z1([Selesai])
    B -- Tidak --> D{Admin pilih tindakan}
    D -- Perpanjang Deadline --> E[Update deadline proyek]
    E --> Z2([Kembali Terbuka])
    D -- Tutup Manual --> F[Status Proyek = Ditutup<br/>tanpa mengubah status pendaftar]
    F --> Z3([Selesai])
    D -- Tutup Paksa --> G[Tampilkan dialog konfirmasi]
    G --> H{Admin konfirmasi?}
    H -- Tidak --> D
    H -- Ya --> I[Status Proyek = Ditutup Paksa]
    I --> J[Sisa pendaftar aktif jadi<br/>status Dibatalkan Sistem]
    J --> Z4([Selesai])
```

---

## Ringkasan Cakupan

| # | Flow | Modul Terkait |
|---|---|---|
| 1 | Registrasi Extras | Autentikasi & Akun |
| 2 | Login & Proteksi Akun | Autentikasi & Akun |
| 3 | Forgot Password / Resend Verification | Autentikasi & Akun |
| 4 | Pendaftaran Proyek Casting | Pendaftaran & Seleksi |
| 5 | Seleksi Kandidat (Filter → Review) | Pendaftaran & Seleksi |
| 6 | Negosiasi Fee Digital | Negosiasi Fee Digital |
| 7 | Kontrak Digital | Kontrak Digital |
| 8 | Pembatalan (Cancel) | Pembatalan |
| 9 | Penutupan Proyek Casting | Manajemen Proyek Casting |

Seluruh flow di atas konsisten dengan RF-01–70 dan RNF-01–18 pada dokumen SRS (`16-SRS-IEEE29148-Final.md`).
