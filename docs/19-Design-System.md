# Design System
## Sistem Informasi Manajemen Casting Talent dan Extras Berbasis Web dengan Fitur Negosiasi Fee Digital

> **⚠️ SUPERSEDED (khusus keputusan visual/warna/ikon).** Palet warna biru `#1E3A8A` (§4, §11) dan Lucide Icons (§9) di dokumen ini sudah diganti brand ijo-hitam + Tabler Icons — lihat `docs/UI-GUIDELINES.md` sebagai acuan visual terkini. Bagian IA/flow/prinsip UX generik di bawah masih bisa jadi referensi, tapi jangan pakai warna/ikon dari dokumen ini.

---

## 1. Design Principles

| Prinsip | Penerapan |
|---|---|
| **Clarity** | Status pendaftaran (Diajukan, Lolos, Deal, dll.) harus selalu terlihat jelas melalui label & warna konsisten — mengingat masalah utama sistem lama adalah kebingungan status/fee |
| **Trust & Transparency** | Tampilan fee, riwayat negosiasi, dan status verifikasi kontrak dibuat eksplisit dan dapat ditelusuri, untuk menghilangkan kesalahpahaman fee |
| **Efficiency** | Alur kerja Admin/CD (filter, approve, generate kontrak) diminimalkan jumlah kliknya karena SLA (24 jam/2 hari) menuntut kecepatan |
| **Consistency** | Pola komponen (tombol, badge status, form) konsisten di seluruh 25 halaman agar mudah dipelajari pengguna non-teknis |
| **Accessibility First** | Kontras warna & navigasi keyboard diperhatikan sejak awal, bukan tambahan di akhir |
| **Mobile-Conscious** | Extras kemungkinan besar mengakses via HP — komponen dirancang mobile-first, bukan desktop-first yang diciutkan |

---

## 2. UX Principles

1. **Task-oriented, bukan menu-oriented** — alur mengikuti *state machine* pendaftaran (lihat User Flow), bukan navigasi bebas.
2. **Feedback instan** — setiap aksi (submit, upload, approve) memberi umpan balik visual (toast/alert) dalam <1 detik.
3. **Progressive disclosure** — form registrasi & kontrak dipecah bertahap (stepper), tidak satu halaman panjang.
4. **Status selalu terlihat** — badge status ditampilkan di header halaman detail pendaftaran, tidak tersembunyi di tab.
5. **Cegah kesalahan sebelum terjadi** — validasi *inline* (real-time) lebih diutamakan daripada validasi setelah submit, khususnya untuk aturan bisnis kritis (fee 1x, upload sekali).
6. **Konfirmasi untuk aksi kritis/ireversibel** — Tutup Paksa, Verifikasi Kontrak, Nonaktifkan CD wajib dialog konfirmasi (selaras RF-53, RF-61, RF-46).

---

## 3. Accessibility (WCAG)

**Target: WCAG 2.1 Level AA**

| Kriteria | Ketentuan |
|---|---|
| Kontras warna | Rasio teks normal min. 4.5:1, teks besar (≥18pt) min. 3:1 |
| Navigasi keyboard | Seluruh form, tombol, modal dapat diakses via Tab/Enter/Esc |
| Focus indicator | Outline fokus terlihat jelas pada semua elemen interaktif |
| Label form | Setiap input memiliki `<label>` terasosiasi, bukan hanya placeholder |
| Alt text | Foto profil, ikon status, dan gambar diberi teks alternatif deskriptif |
| Pesan error | Terhubung ke field terkait via `aria-describedby`, tidak hanya warna merah |
| Ukuran target sentuh | Minimal 44x44px untuk tombol di tampilan mobile |
| Video profil | Disertai kontrol play/pause standar (tidak autoplay dengan suara) |

---

## 4. Color System

| Token | Hex | Penggunaan |
|---|---|---|
| `primary` | #1E3A8A (biru tua) | Aksi utama, tombol primer, header |
| `primary-light` | #3B82F6 | Hover state, link |
| `secondary` | #7C3AED (ungu) | Aksen sekunder, highlight |
| `success` | #16A34A | Status "Deal", "Lolos", "Kontrak Ditandatangani" |
| `warning` | #D97706 | Status "Terlambat", "Cadangan", "Menunggu Verifikasi" |
| `danger` | #DC2626 | Status "Ditolak", "Dibatalkan", "Melanggar", pesan error |
| `info` | #0284C7 | Status "Diajukan", "Direview CD" |
| `neutral-900` s.d. `neutral-50` | #111827 → #F9FAFB | Teks, border, background |

**Pemetaan warna terhadap taksonomi status** (§8 Information Architecture) wajib konsisten di seluruh halaman — mis. hijau selalu berarti "berhasil/lolos", tidak dipakai untuk arti lain.

---

## 5. Typography

| Elemen | Font | Ukuran | Weight |
|---|---|---|---|
| Font family | Inter (fallback: system-ui, sans-serif) | — | 400/500/600/700 |
| H1 (judul halaman) | Inter | 28px / 1.75rem | 700 |
| H2 (judul section) | Inter | 22px / 1.375rem | 600 |
| H3 (judul card) | Inter | 18px / 1.125rem | 600 |
| Body | Inter | 14–16px | 400 |
| Caption/label | Inter | 12px | 500 |
| Angka fee (nominal) | Inter, `tabular-nums` | 16–20px | 600 |

Alasan pemilihan Inter: keterbacaan tinggi di layar kecil (relevan untuk pengguna Extras yang mayoritas mobile), gratis, dan tersedia luas di Google Fonts/Tailwind/Bootstrap.

---

## 6. Spacing

Skala spasi berbasis kelipatan 4px (selaras dengan Tailwind/Bootstrap utility):

`4px · 8px · 12px · 16px · 24px · 32px · 48px · 64px`

- Padding komponen (card, button): 12–16px
- Jarak antar section: 24–32px
- Jarak antar field form: 16px

---

## 7. Grid System

| Breakpoint | Lebar | Kolom Grid |
|---|---|---|
| Mobile | < 640px | 4 kolom |
| Tablet | 640–1024px | 8 kolom |
| Desktop | > 1024px | 12 kolom |

Gutter: 16px (mobile), 24px (desktop). Container max-width desktop: 1280px.

---

## 8. Layout System

| Tipe Halaman | Layout |
|---|---|
| Dashboard (Admin/CD/Extras) | Sidebar + topbar (desktop); bottom nav + topbar (mobile); grid kartu KPI |
| Tabel data (Pendaftar, Data Extras, Laporan) | Full-width table dengan panel filter collapsible |
| Form (Registrasi, Buat Proyek) | Single column, max-width 640px, stepper untuk multi-tahap |
| Detail Pendaftaran | Header status + tab (Info, Fee, Kontrak) |
| Halaman publik (Login, Casting Terbuka) | Card-centered, minim distraksi |

---

## 9. Iconography

- **Icon set:** Lucide Icons (konsisten dengan ekosistem Tailwind, ringan, konsisten stroke-width 2px) — alternatif Bootstrap Icons bila memakai Bootstrap.
- **Pemetaan ikon status:** ✔️ (check) = Lolos/Deal/Selesai · ⏱️ (clock) = Terlambat/Menunggu · ✖️ (x) = Ditolak/Dibatalkan · ⚠️ (alert-triangle) = Melanggar/Kuota Penuh.
- Ikon aksi standar: `pencil` (edit), `trash` (hapus — nonaktif untuk kontrak immutable), `upload`, `download`, `filter`, `bell` (notifikasi).

---

## 10. Data Visualization

| Kebutuhan | Jenis Chart | Library |
|---|---|---|
| Laporan Funnel per Proyek | Funnel chart / horizontal bar bertingkat | Chart.js atau ApexCharts |
| Rekap Fee per Proyek/Periode | Bar chart | Chart.js |
| Rekap Status Keaktifan Extras | Donut/Pie chart | Chart.js |
| Tren Pendaftar per Bulan (dashboard) | Line chart | Chart.js |
| Indikator SLA (Terlambat/Tidak Merespons) | Badge + progress bar (bukan chart kompleks) | Native komponen |

**Prinsip:** gunakan warna sesuai Color System (§4) agar konsisten dengan badge status di tabel — hindari palet chart default yang tidak selaras.

---

## 11. Design Tokens

```json
{
  "color": {
    "primary": "#1E3A8A",
    "success": "#16A34A",
    "warning": "#D97706",
    "danger": "#DC2626",
    "info": "#0284C7",
    "neutral-900": "#111827",
    "neutral-50": "#F9FAFB"
  },
  "font": {
    "family": "Inter, system-ui, sans-serif",
    "size-h1": "1.75rem",
    "size-body": "1rem",
    "weight-regular": 400,
    "weight-semibold": 600
  },
  "spacing": {
    "xs": "4px", "sm": "8px", "md": "16px", "lg": "24px", "xl": "32px"
  },
  "radius": {
    "sm": "4px", "md": "8px", "lg": "12px", "full": "9999px"
  },
  "shadow": {
    "sm": "0 1px 2px rgba(0,0,0,0.05)",
    "md": "0 4px 6px rgba(0,0,0,0.1)"
  }
}
```

Token ini menjadi acuan tunggal saat implementasi (baik di Tailwind `tailwind.config.js` maupun variabel CSS Bootstrap `_variables.scss`).

---

## 12. Component Library

| Komponen | Varian |
|---|---|
| Button | Primary, Secondary, Danger, Ghost, Loading state |
| Badge/Status Chip | Sesuai taksonomi status (§8 IA) dengan warna semantik |
| Card | KPI Card (dashboard), Info Card (detail proyek) |
| Table | Dengan pagination, filter panel, sort, export |
| Modal | Konfirmasi aksi kritis, form singkat (ajukan fee, alasan cancel) |
| Form Input | Text, Select, Date Picker, File Upload (dengan preview & validasi tipe/ukuran), Password (dengan indikator kekuatan) |
| Stepper | Registrasi multi-tahap |
| Toast/Alert | Success, Warning, Error, Info |
| Breadcrumb | Sesuai §13 Information Architecture |
| Sidebar Nav / Bottom Nav | Sesuai §10–11 Information Architecture |
| Tab | Navigasi kontekstual (Info/Fee/Kontrak) |
| SLA Timer/Countdown | Menampilkan sisa waktu 24 jam (fee) / 2 hari (review CD) |
| Empty State Illustration | Untuk daftar kosong |
| Skeleton Loader | Untuk tabel & card saat memuat data |

---

## 13. Responsive Rules

| Elemen | Desktop (>1024px) | Tablet (640–1024px) | Mobile (<640px) |
|---|---|---|---|
| Navigasi | Sidebar persistent | Sidebar collapsible | Bottom nav + hamburger |
| Tabel data | Tabel penuh | Tabel scroll horizontal | Diubah jadi list kartu (card list) |
| Form | 2 kolom (jika relevan) | 1 kolom | 1 kolom, tombol full-width |
| Dashboard KPI | Grid 4 kolom | Grid 2 kolom | Grid 1 kolom (stack) |
| Modal | Center, max-width 480px | Center | Full-screen sheet |

---

## 14. Interaction Rules

- **Tombol:** state default → hover (opacity/darken 10%) → active (scale 0.98) → disabled (opacity 50%, cursor not-allowed) → loading (spinner + teks "Memproses...").
- **Aksi kritis/ireversibel** (Tutup Paksa, Verifikasi Kontrak, Nonaktifkan Akun, Konfirmasi Pembatalan) **wajib** modal konfirmasi dengan penjelasan konsekuensi (selaras RF-53, RF-46, RF-61).
- **Validasi status terkini** (RF-70): sebelum submit aksi kritis, sistem menampilkan indikator "memuat status terbaru" untuk mencegah aksi berdasarkan data usang (race condition).
- **Auto-save tidak digunakan** pada form kontrak/fee (mengingat sifatnya legal/finansial) — submit eksplisit selalu diperlukan.
- **Hover tooltip** digunakan pada badge status untuk menjelaskan singkatan/istilah (mis. hover "Cadangan" → "Menunggu slot tersedia").

---

## 15. Validation Rules

| Field/Aksi | Aturan Validasi | Waktu Validasi |
|---|---|---|
| Password | Min. 8 karakter, 1 kapital, 1 angka, 1 simbol (RF-57) | Real-time saat mengetik (indikator kekuatan) |
| NIK | 16 digit, unik | On blur |
| Upload Foto/Video | Whitelist jpg/png/mp4, batas ukuran (RF-44) | Saat file dipilih (sebelum upload) |
| Fee Alternatif | Hanya dapat diajukan 1x | Tombol otomatis disabled setelah dipakai |
| Alasan Pembatalan | Wajib diisi, min. 10 karakter | Saat submit |
| Tutup Paksa Proyek | Wajib konfirmasi eksplisit (checkbox/dialog) | Sebelum submit |
| Deadline Proyek | Tidak boleh tanggal lampau | Saat input |
| Email | Format valid & unik | On blur + saat submit |

---

## 16. Empty States

| Konteks | Pesan | CTA |
|---|---|---|
| Belum ada proyek casting terbuka (Extras) | "Belum ada casting yang dibuka saat ini" | — |
| Belum ada pendaftar (Admin) | "Belum ada extras yang mendaftar pada proyek ini" | Tombol "Bagikan info proyek" (opsional) |
| Belum ada notifikasi | "Belum ada notifikasi baru" | — |
| Belum ada riwayat casting (Extras) | "Anda belum pernah mengikuti casting" | Tombol "Lihat Casting Terbuka" |
| Belum ada template kontrak | "Buat template kontrak pertama Anda" | Tombol "Buat Template" |

Setiap empty state menggunakan ilustrasi sederhana (line-art, bukan foto) + teks singkat, konsisten dengan nada bahasa aplikasi yang ramah dan tidak teknis.

---

## 17. Error States

| Tipe | Contoh | Penanganan UI |
|---|---|---|
| Validasi form | Field kosong/salah format | Border merah + pesan di bawah field |
| Upload gagal | Koneksi terputus (RF-69) | Alert merah di atas form: "Unggah gagal, silakan coba lagi" |
| 403 Forbidden | Extras mencoba akses halaman Admin | Halaman khusus: "Anda tidak memiliki akses" + tombol kembali |
| 404 Not Found | URL/ID tidak ditemukan | Halaman khusus dengan tombol ke Dashboard |
| Akun terkunci | 3x gagal login (RF-58) | Alert kuning + link "Reset Password" |
| Data tidak lengkap saat generate kontrak (RF-68) | Field profil belum lengkap | Alert + daftar field yang perlu dilengkapi (bukan pesan generik) |
| Kegagalan sistem (500) | Error server | Halaman generik + saran refresh/hubungi Admin |

---

## 18. Loading States

- **Skeleton loader** untuk tabel, card dashboard, dan daftar casting saat data dimuat (bukan spinner polos) — mengurangi persepsi lambat.
- **Spinner inline pada tombol** saat submit form (bukan overlay layar penuh), agar konteks tetap terlihat.
- **Progress bar** untuk proses upload file (foto, video, PDF), menampilkan persentase.
- **Skeleton khusus** untuk chart pada Laporan/Dashboard saat data agregat sedang dihitung.

---

## 19. Notification Patterns

| Jenis | Pola UI |
|---|---|
| Toast (real-time feedback aksi) | Muncul kanan-atas, auto-dismiss 4 detik, warna sesuai semantik (§4) |
| Panel Notifikasi (bell icon) | Dropdown/panel berisi list notifikasi in-app, badge angka belum dibaca |
| Web Push (browser) | Judul singkat + 1 baris deskripsi + ikon aplikasi, klik mengarah ke halaman terkait (RF-39) |
| Email | Template konsisten: header logo, judul jelas, ringkasan status, tombol CTA tunggal, footer kontak Admin |
| Link Grup WA | Ditampilkan sebagai kartu info terpisah di halaman Detail Pendaftaran, bukan notifikasi push |

---

## 20. Dashboard Standards

- **KPI Card:** format konsisten — ikon + label + angka besar + indikator tren (opsional panah naik/turun). Contoh: "Pendaftar Aktif: 24".
- **Grid dashboard:** 4 kolom KPI di desktop → 1 kolom di mobile (§13).
- **Zona indikator SLA:** ditempatkan menonjol di bagian atas dashboard Admin & CD (badge merah/kuning jika ada item "Terlambat"/"Tidak Merespons").
- **Chart** memakai palet warna dari Color System, label sumbu dalam Bahasa Indonesia, dan legenda selalu terlihat.
- **"Terakhir diperbarui"** timestamp ditampilkan kecil di pojok setiap widget dashboard.
- **Personalisasi minimal:** dashboard tidak dapat disusun ulang pengguna (fixed layout) — sesuai skala sistem kecil, menghindari kompleksitas tak perlu.

---

## Rekomendasi Framework CSS/UI

### Perbandingan

| Kriteria | Bootstrap 5 | Tailwind CSS | Material Design |
|---|:---:|:---:|:---:|
| Kecocokan dengan Laravel Blade (bukan SPA) | 5 | 5 | 2 *(lebih natural di React/Vue via MUI/Vuetify)* |
| Kecepatan pengembangan solo developer/skripsi | 5 | 3 | 3 |
| Ketersediaan template dashboard admin siap pakai (gratis) | 5 *(AdminLTE, CoreUI, SB Admin, dll.)* | 3 *(perlu Flowbite/Preline, sebagian berbayar)* | 3 *(MDBootstrap gratis terbatas)* |
| Kemudahan kustomisasi branding JBTB Casting | 3 | 5 | 2 *(gaya visual Material sangat khas/kaku)* |
| Ukuran bundle & performa (relevan untuk shared hosting/VPS skala kecil, RNF-04) | 3 | 5 *(purge CSS, jauh lebih ringan)* | 3 |
| Dukungan aksesibilitas bawaan (WCAG) | 4 | 3 *(tergantung implementasi)* | 5 |
| Kurva belajar untuk mahasiswa/skripsi | 5 *(paling banyak tutorial berbahasa Indonesia untuk sistem informasi)* | 3 | 2 |
| Konsistensi komponen kompleks (tabel, modal, form) out-of-the-box | 5 | 2 *(perlu dibangun/pakai plugin tambahan)* | 4 |
| **Total (dari 40)** | **35** | **29** | **24** |

### Analisis & Rekomendasi

**Bootstrap 5 direkomendasikan sebagai framework utama** untuk implementasi sistem ini, dengan pertimbangan:

1. **Kecocokan konteks skripsi** — proyek ini dikerjakan oleh satu pengembang (mahasiswa) dengan tenggat waktu akademik. Bootstrap menyediakan komponen siap pakai (tabel, modal, form, navbar, badge) yang langsung dapat dipakai tanpa membangun dari nol, sangat mengurangi waktu pengembangan UI dibanding Tailwind yang bersifat utility-first (butuh menyusun komponen sendiri atau bergantung pada plugin tambahan).
2. **Ketersediaan template dashboard admin gratis** (AdminLTE, SB Admin 2, CoreUI) berbasis Bootstrap sangat relevan karena sistem ini memiliki 3 dashboard berbeda (Admin, CD, Extras) dengan banyak tabel & filter (RF-30–32) — dapat diadaptasi cepat.
3. **Ekosistem Laravel + Bootstrap** memiliki dokumentasi dan tutorial berbahasa Indonesia paling banyak untuk kasus "sistem informasi manajemen" seperti ini, memudahkan proses bimbingan skripsi dan debugging mandiri.
4. **Cukup untuk skala sistem** (±70 extras, ±3 proyek/bulan, RNF-04) — perbedaan performa dengan Tailwind tidak signifikan pada skala sekecil ini, sehingga keunggulan Tailwind di sisi bundle size kurang menjadi prioritas.

**Tailwind CSS** tetap merupakan alternatif kuat bila prioritas utama adalah **branding kustom yang khas** untuk JBTB Casting (bukan tampilan generik admin panel) dan performa maksimal — cocok dipilih bila pengembang punya lebih banyak waktu atau pengalaman frontend, dan berencana menggunakan komponen tambahan seperti Flowbite atau Preline UI untuk mempercepat pembangunan komponen.

**Material Design tidak direkomendasikan** untuk proyek ini karena ekosistemnya lebih natural pada framework berbasis komponen seperti React (MUI) atau Vue (Vuetify), sementara sistem ini menggunakan Laravel Blade (server-rendered). Gaya visual Material yang khas (elevation, ripple effect) juga kurang fleksibel untuk membangun identitas visual agensi casting yang lebih personal.

### Rekomendasi Akhir
**Bootstrap 5** (dipadukan dengan template dashboard open-source seperti **SB Admin 2** atau **CoreUI Free** sebagai starting point), dengan warna & tipografi disesuaikan mengikuti **Design Tokens (§11)** di atas agar tetap memiliki identitas visual JBTB Casting meskipun menggunakan Bootstrap.
