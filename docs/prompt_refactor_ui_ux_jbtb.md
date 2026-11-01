# Master Prompt V3 (Final Polish): Pembersihan Total "AI-Slop" (Emoji, Em-Dash, Panah Mentah) & Penyempurnaan Visual — SIM Casting JBTB (`fakrl/extras`)

> **Cara Pakai Dokumen Ini di Antigravity:**
> *Copy-paste* seluruh isi dokumen ini ke Antigravity (atau timpa ke file `docs/prompt_refactor_ui_ux_jbtb.md` di repo lokalmu), lalu perintahkan Antigravity:
> *"Struktur layout makro dari perubahan terakhir sudah jauh lebih bagus, tapi dari hasil inspeksi visual langsung di browser masih banyak jejak 'AI-slop' seperti emoji Unicode mentah (`⚡`, `🔔`, `📅`, `👁`, `👥`, `🎯`, `💡`, `🔒`), tanda em-dash (`—` / `&mdash;`), panah teks mentah (`→`, `←`, `↓`, `▾`, `&#blacktriangledown;`), copywriting kaku/typo, dan placeholder `Client: -`. Eksekusi seluruh instruksi pembersihan dan penyempurnaan di `docs/prompt_refactor_ui_ux_jbtb.md` ini sampai bersih 100%, lalu jalankan `php artisan test`."*

## 1. Evaluasi Visual Saat Ini (Apa yang Sudah Bagus vs Apa yang Harus Dibersihkan)

Berdasarkan pengecekan visual langsung di seluruh halaman untuk ke-5 role (`super_admin`, `admin`, `korlap`, `client`, `extras`):
* **Yang Sudah Beres & Bagus**:
  - Layout 2 kolom asimetris di Dashboard Super Admin (panel keputusan di kiri, agenda shooting di kanan) dan *accent bar* warna pada 4 kartu KPI sudah proporsional dan rapi.
  - Halaman Lineup Pendaftar (`/admin/projects/{id}/applicants`) baik di Mode Kartu maupun Mode Daftar, serta pembukaan *drawer/modal* detail pendaftar sudah berjalan mulus dan jelas zonasinya.
  - Timeline progres proyek di Dashboard Client & Extras, *Quick Filter Chips*, *Sticky Mobile CTA*, serta penghapusan efek *grayscale* pada foto profil talent sudah membuat tampilan jauh lebih hidup.
* **Masalah Utama yang Masih Mengganggu ("AI-Slop" & Detail Visual)**:
  1. **Banjir Emoji Unicode Mentah**: Padahal aplikasi JBTB sejak awal menggunakan **Tabler Icons (`<i class="ti ti-..."></i>`)** yang bersih dan monokrom/senada tema, pada kode baru justru banyak disisipkan emoji Unicode warna-warni (`⚡`, `🔔`, `📅`, `👁`, `👥`, `🎯`, `💡`, `🔒`, `🔴`, `🎭`, `💬`, `📸`, `🧾`, `📍`). Ini merusak estetika editorial JBTB dan terlihat sangat buatan AI.
  2. **Kebanyakan Em-Dash (`—` dan `&mdash;`)**: Banyak judul banner, keterangan *bulk action*, dan *callout* menggunakan tanda pisah panjang `—` khas tulisan AI, padahal gaya asli JBTB menggunakan titik tengah (`·`), titik dua (`:`), atau pemisahan baris sub-teks.
  3. **Simbol Panah & Segitiga Teks Mentah (`→`, `←`, `↓`, `▾`, `>`, `&rarr;`, `&darr;`, `&#blacktriangledown;`)**: Di banyak tombol dan menu dropdown (termasuk bug `&#blacktriangledown;` di Manajemen Akun), masih dipakai simbol panah teks mentah alih-alih ikon `<i class="ti ti-..."></i>`.
  4. **Copywriting Kaku/Typo & Teks Placeholder Mentah (`Client: -`)**: Ada kata hubung salah konteks (`"Kendati"` di halaman review Client), judul terlalu mendramatisir (`"Prioritas Utama: Butuh Keputusan Anda"`), serta tampilan `Client: - · JBTB-2026-008` saat nama client kosong/disembunyikan.

## 2. Aturan Ketat "Zero AI-Slop" (Wajib Dipatuhi di Seluruh File Blade)

1. **DILARANG Menggunakan Emoji Unicode di Seluruh File `.blade.php` & `.php`**:
   - Hapus semua karakter emoji (`⚡`, `🔔`, `📅`, `👁`, `👥`, `🎯`, `💡`, `🔒`, `🔴`, `🎭`, `💬`, `📸`, `✍️`, `🧾`, `📍`, `💰`, `✨`, `🔥`, `📋`) dan ganti dengan **Tabler Icons (`<i class="ti ti-..."></i>`)** yang sudah terpasang di proyek.
   - Jangan pernah menumpuk `<i class="ti ti-..."></i>` dengan emoji dalam satu elemen yang sama.
2. **DILARANG Menggunakan Em-Dash (`—` maupun `&mdash;`) di Teks Antarmuka**:
   - Ganti seluruh `—` dan `&mdash;` pada teks UI, banner, badge, *callout*, atribut `title="..."`, dan string JavaScript dengan titik tengah (`·`), titik dua (`:`), tanda koma, atau pecah menjadi elemen judul `<strong>` dan `<span class="text-muted">` terpisah.
3. **DILARANG Menggunakan Panah/Segitiga Teks Mentah di Dalam Tombol & Link**:
   - Ganti seluruh `→`, `&rarr;`, `←`, `&larr;`, `↓`, `&darr;`, `▾`, dan `&#blacktriangledown;` dengan Tabler Icons:
     - Panah kanan: `<i class="ti ti-arrow-right"></i>`
     - Panah kiri: `<i class="ti ti-arrow-left"></i>`
     - Panah bawah: `<i class="ti ti-arrow-down"></i>`
     - Dropdown/chevron bawah: `<i class="ti ti-chevron-down"></i>`
     - Chevron kanan: `<i class="ti ti-chevron-right"></i>`
4. **Pertahankan 100% Kelulusan Test (`php artisan test`)**:
   - Jangan mengubah nama route, method form, atribut `name` input, atau selector hook yang diuji di `tests/Feature/` (seperti `.xaksi` atau `id="detail-{{ $app->id }}"`). Jika ada unit test yang secara spesifik mengecek potongan string teks lama yang mengandung emoji/em-dash, sesuaikan string ekspektasi di test tersebut agar selaras dengan teks bersih tanpa emoji/em-dash.

## 3. Daftar Perbaikan Eksak Per File (Before vs After)

Lakukan pembersihan dan perbaikan pada seluruh file berikut:

### A. Portal Super Admin (`resources/views/super-admin/...` & Sidebar)

| File Target | Teks / Kode Saat Ini (Mengandung AI-Slop) | Ubah Menjadi (Bersih & Konsisten Tabler Icons) |
| :--- | :--- | :--- |
| `resources/views/super-admin/index.blade.php` & `dashboard/_butuh-keputusan.blade.php` | Judul dengan emoji `⚡ Prioritas Utama: Butuh Keputusan Anda` | `<i class="ti ti-alert-circle"></i> Butuh Keputusan Anda` *(lugas dan sesuai gaya asli JBTB)* |
| `resources/views/super-admin/dashboard/_diingatkan.blade.php` | Judul atau tombol dengan emoji `🔔 Staf perlu diingatkan` / `🔔 Ingatkan` | Gunakan `<i class="ti ti-bell"></i> Staf Perlu Diingatkan` dan `<i class="ti ti-bell"></i> Ingatkan` |
| `resources/views/super-admin/index.blade.php` (Widget Jadwal) | Header dengan emoji `📅 Jadwal & Agenda Shooting` | `<i class="ti ti-calendar-event"></i> Jadwal & Agenda Shooting` |
| `resources/views/partials/sidebar-super_admin.blade.php` | Indikator `Monitoring 🔴 >` (menggunakan lingkaran emoji merah / siku mentah `>`) | Gunakan `<span class="sb-dot"></span>` (atau `.sb-badge-count`) dan ikon `<i class="ti ti-chevron-right"></i>` |
| `resources/views/super-admin/akun/index.blade.php` & `aksi-menu.blade.php` | • Baris 121 & 279 (JS): `0 dipilih — Centang akun untuk aksi massal`<br>• Baris 206: `<i class="ti ti-adjustments"></i> Kelola &#blacktriangledown;` atau `Kelola ▾` | • Ubah teks di HTML & JS menjadi: `0 dipilih · Centang akun untuk aksi massal` *(pakai titik tengah `·`, bukan em-dash `—`)*<br>• Ubah tombol menu aksi menjadi: `<i class="ti ti-adjustments"></i> Kelola <i class="ti ti-chevron-down"></i>` *(hapus `&#blacktriangledown;` dan `▾`)* |
| `resources/views/super-admin/monitoring/admin.blade.php` & `korlap.blade.php` & `partials/korlap-ringkasan.blade.php` | • Banner dengan emoji `👁 Pratinjau pekerjaan Admin, lihat saja — Klik kartu...` / `👁 Pratinjau lapangan...`<br>• Tombol dengan panah mentah `Masuk Mode Korlap Ini →` dan `Buka & Validasi Absensi →` | • Ganti `👁` dengan `<i class="ti ti-eye"></i>` dan ganti em-dash `—` dengan titik tengah `·` atau kalimat terpisah: `Pratinjau pekerjaan Admin (Mode baca). Klik baris untuk melihat rincian.`<br>• Ganti panah teks `→` dengan `<i class="ti ti-arrow-right"></i>` |
| `resources/views/partials/switch-role-banner.blade.php` | Emoji `👁 Super Admin · sebagai Client` dan tombol `↩ Kembali ke Super Admin` | Gunakan `<i class="ti ti-eye"></i> Super Admin · Mode {{ $roleLabel }}` dan `<i class="ti ti-arrow-back-up"></i> Kembali ke Super Admin` |

### B. Portal Admin & Korlap (`resources/views/admin/...` & Partials)

| File Target | Teks / Kode Saat Ini (Mengandung AI-Slop) | Ubah Menjadi (Bersih & Konsisten Tabler Icons) |
| :--- | :--- | :--- |
| `resources/views/admin/projects/show.blade.php` | • Tombol kanan atas dengan emoji/panah mentah: `👥 Buka Workspace Lineup Penuh &rarr;` dan `🧾 Lihat Dokumen Invoice`<br>• Banner mode baca (Baris 47): `👁` / `🔒` `<strong>Mode Lihat saja</strong> &mdash; Anda sedang dalam mode baca karena proyek ini ditugaskan kepada {{ $p->admin->name ?? 'belum ada PIC' }}.`<br>• Tombol kembali: `← Kembali ke Daftar Proyek` | • Tombol Lineup: `<i class="ti ti-users-group"></i> Kelola Lineup <i class="ti ti-arrow-right"></i>` (tanpa emoji `👥` dan tanpa `&rarr;`)<br>• Tombol Invoice: `<i class="ti ti-receipt"></i> Dokumen Invoice` (tanpa emoji `🧾`)<br>• Banner mode baca: `<i class="ti ti-lock"></i> <strong>Mode Baca:</strong> Proyek ini ditugaskan kepada {{ $p->admin->name ?? 'belum ada PIC' }}.` (tanpa emoji `👁`/`🔒`, tanpa `&mdash;`, kalimat lugas)<br>• Tombol kembali: `<i class="ti ti-arrow-left"></i> Daftar Proyek` |
| `resources/views/admin/projects/applicants.blade.php` & `resources/views/partials/extras-card.blade.php` | • Toggle tampilan dengan emoji `[ 🌁 Kartu ]` dan `[ ☰ Daftar ]`<br>• Emoji di dalam kartu/modal seperti `🎭`, `💬`, `⚡`, atau panah `← Kembali ke Proyek` | • Toggle tampilan: `<i class="ti ti-layout-grid"></i> Kartu` dan `<i class="ti ti-list"></i> Daftar`<br>• Gunakan `<i class="ti ti-brand-whatsapp"></i>` untuk tombol WA, `<i class="ti ti-user"></i>` untuk profil, dan `<i class="ti ti-arrow-left"></i>` untuk tombol kembali. |

### C. Portal Extras (`resources/views/extras/...` & `partials/application-progress.blade.php`)

| File Target | Teks / Kode Saat Ini (Mengandung AI-Slop / Kurang Rapi) | Ubah Menjadi (Bersih & Konsisten Tabler Icons) |
| :--- | :--- | :--- |
| `resources/views/extras/projects/index.blade.php` (`/extras/lowongan`) | • Chip filter dengan emoji: `⚡ Butuh Dadakan`, `💰 Fee Tinggi (>500rb)`, `🎯 Sesuai Usia & Gender`, `🎯 Masih Ada Kuota`<br>• Teks meta proyek saat nama client kosong menampilkan strip mentah: `Client: - · JBTB-2026-008`<br>• Tombol dengan panah mentah: `Lihat Detail & Lamar →`<br>• Banyak `style="..."` inline yang menumpuk di tiap elemen kartu | • Ganti semua emoji di chip filter dengan Tabler Icons: `<i class="ti ti-bolt"></i> Butuh Dadakan`, `<i class="ti ti-coin"></i> Fee Tinggi`, `<i class="ti ti-user-check"></i> Sesuai Profil`, `<i class="ti ti-users"></i> Kuota Tersedia`<br>• **Perbaiki `Client: -`**: Jika nama client kosong/null/strip, jangan tampilkan teks `Client: - ·`; langsung tampilkan kode proyek `{{ $project->kode_proyek }}` atau jenis produksinya saja!<br>• Ganti `→` dengan `<i class="ti ti-arrow-right"></i>`<br>• Pindahkan inline `style="..."` yang berulang ke class CSS rapi di `theme-style.blade.php`. |
| `resources/views/extras/projects/show.blade.php` (`/extras/lowongan/{id}`) | • Emoji `📅 Tanggal Shooting`, `👥 Peran yang Dibutuhkan`, `📍 Lokasi`<br>• Sticky Mobile CTA memakai panah HTML mentah: `Pilih Peran &darr;`<br>• Teks `Client: -` jika client kosong | • Ganti emoji dengan `<i class="ti ti-calendar-event"></i>`, `<i class="ti ti-users"></i>`, `<i class="ti ti-map-pin"></i>`<br>• Ubah tombol Sticky CTA menjadi: `Pilih Peran <i class="ti ti-arrow-down"></i>`<br>• Sembunyikan label `Client: -` jika nama client tidak ditampilkan ke publik. |
| `resources/views/extras/profile-edit.blade.php` & `profile-show.blade.php` | • Tombol dengan emoji `👁 Pratinjau Portofolio Publik`<br>• Atribut title (Baris 113) memakai `&mdash;`: `title="Lihat seperti Client &mdash; Lihat bagaimana profil dan fotomu tampil saat dinilai oleh Client/PH"`<br>• Kotak tips dengan emoji `💡 Profil dengan 3+ foto...` | • Tombol: `<i class="ti ti-eye"></i> Pratinjau Portofolio Publik`<br>• Atribut title: `title="Pratinjau tampilan portofoliomu saat dilihat oleh Client"` (singkat, tanpa `&mdash;`)<br>• Kotak tips: gunakan `<i class="ti ti-bulb"></i>` (hapus emoji `💡`). |
| `resources/views/partials/application-progress.blade.php` & `extras/dashboard.blade.php` | • Judul callout status `deal`/`diajukan_ke_client`: `Honor Deal — Menunggu Keputusan Client` (memakai em-dash `—`)<br>• Emoji `📍 Buka Maps` pada kartu jadwal<br>• Saat status `ditolak` / `dibatalkan`, belum ada tombol pintas untuk mencari lowongan lain<br>• Saat status `kontrak_ditandatangani`, tombol callout hanya mengarah ke `Rincian Honor` | • Ubah judul menjadi: `Honor Disepakati · Menunggu Keputusan Client` (gunakan titik tengah `·`)<br>• Ganti `📍` dengan `<i class="ti ti-map-pin"></i>`<br>• Tambahkan blok callout `.nac-danger` / `.nac-info` untuk status `ditolak` dengan tombol `.btn-tonal` `<a href="{{ route('extras.projects.index') }}">Cari Lowongan Lain <i class="ti ti-arrow-right"></i></a>`<br>• Pada status `kontrak_ditandatangani`, pastikan informasi jadwal syuting/absensi tampil jelas bersama tautan rincian honor. |

### D. Portal Client (`resources/views/client/...` & `invoices/index-client.blade.php`)

| File Target | Teks / Kode Saat Ini (Mengandung AI-Slop / Typo) | Ubah Menjadi (Bersih & Natural) |
| :--- | :--- | :--- |
| `resources/views/client/reviews/show.blade.php` | • Kalimat kaku/salah kata hubung: *"&lt;strong&gt;Panduan Pemilihan Kandidat:&lt;/strong&gt; Pilih kandidat yang sesuai dengan kriteria produksi Anda. &lt;strong&gt;Kendati&lt;/strong&gt; kandidat yang Anda setujui akan langsung diproses tanda tangan kontraknya oleh tim Admin JBTB."*<br>• Tombol kembali: `← Kembali ke Pilih Kandidat` | • Perbaiki copywriting menjadi natural & lugas: *"&lt;strong&gt;Panduan Pemilihan:&lt;/strong&gt; Pilih kandidat yang sesuai dengan kebutuhan produksi Anda. Kandidat yang disetujui akan langsung dijadwalkan untuk tanda tangan kontrak oleh tim Admin JBTB."* (hapus kata `"Kendati"`)<br>• Tombol kembali: `<i class="ti ti-arrow-left"></i> Daftar Proyek Review` |
| `resources/views/client/dashboard.blade.php` & `invoices/index-client.blade.php` | Cek seluruh link seperti `Lihat semua →` atau emoji pada tombol | Pastikan seluruh ikon menggunakan `<i class="ti ti-..."></i>` dan tidak ada satu pun emoji Unicode atau em-dash `—`. |

## 4. Verifikasi Otomatis Wajib Sebelum Selesai

Setelah seluruh perubahan di atas selesai dikerjakan, jalankan langkah audit otomatis berikut di terminal:

1. **Audit Zero Emoji & Zero Em-Dash di Folder Views**:
   - Cari di seluruh `resources/views/` untuk memastikan **TIDAK ADA LAGI**:
     - String `&mdash;` atau karakter `—` pada teks UI yang baru ditambahkan.
     - String `&#blacktriangledown;`, `&rarr;`, `&larr;`, `&darr;`, atau karakter panah mentah `→`, `←`, `↓`, `▾` di dalam tombol/link.
     - Emoji Unicode (`⚡`, `🔔`, `📅`, `👁`, `👥`, `🎯`, `💡`, `🔒`, `🔴`, `🎭`, `💬`, `📸`, `🧾`, `📍`, `💰`).
2. **Bersihkan Cache & Jalankan Full Test Suite**:
   - Jalankan `php artisan view:clear`
   - Jalankan `php artisan test` dan pastikan **100% test suite (962+ tests) tetap PASS (Hijau)** tanpa error.
