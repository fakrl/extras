# UI-GUIDELINES.md — SIM Casting JBTB

> Design system ringkas. Sumber keputusan asli ada di `CLAUDE.md` §7 (deskripsi prototype) & §8 (Decision Log) — file ini distilasi buat di-mention pas generate view, jangan suapin seluruh CLAUDE.md tiap butuh styling.

## Prinsip Utama: "Semua Umur"

User Extras termasuk orang tua/non-teknis. Ini bukan preferensi estetika, ini requirement (RNF-05). Semua keputusan UI harus lolos tes ini duluan sebelum pertimbangan lain.

- Tombol `.btn` minimal **44px** tinggi (touch target; direvisi dari 48px sesuai kode aktual, AY.3.6 28 Sept 2026). `.btn-sm` (32px) **hanya** untuk aksi sekunder di desktop (mis. "Set Grade", "Breakdown" di kartu pendaftar) — aksi utama/primer dan semua aksi di mobile tetap pakai `.btn` 44px.
- Bahasa Indonesia sederhana, hindari jargon teknis di copy (misal bukan "gagal validasi", tapi "data belum lengkap, cek lagi ya").
- Indikator status pakai warna konsisten di SELURUH sistem: **hijau** = beres/aktif/approved, **kuning** = menunggu/pending, **merah** = batal/tolak/melanggar.
- Focus ring keyboard wajib ada (accessibility dasar).

## Warna & Brand

- **Primary:** hijau `#15803D`
- **Dark/hero:** `#0B1A12`
- **Nilai accent aktual kode** (`theme-style.blade.php`, dicek ulang AY.3.6 28 Sept 2026 — dokumentasi lama menyebut `#22c55e`/`#4ade80` untuk mode gelap, itu SUDAH TIDAK SESUAI kode): mode terang `--accent: #15803d` / `--accent-strong: #0b5e2c`; mode gelap `--accent: #0f9a4c` / `--accent-strong: #22b862`.
- **Konten (body, dashboard):** dark/light toggle tetap dipertahankan (keputusan final 28 Agu 2026, Fakrul) — user pilih sendiri via tombol di topbar, preferensi disimpan `localStorage`. "Semua umur" dipenuhi lewat kontras warna yang cukup di KEDUA tema, bukan dengan memaksa satu tema. Palet dark direvisi 28 Agu jadi netral (`resources/views/partials/theme-style.blade.php`) — bukan hijau tua di semua permukaan, biar nggak terasa gelap/pekat berlebihan. **Direvisi lagi 30 Agu 2026 (Opsi B dari 3 mockup)** — alasan: warna netral versi 28 Agu masih kebawa tint hijau (kurang kontras brand vs netral), `--accent-strong` mode terang identik sama `--accent` (nggak ada sinyal depth buat hover), dan `--warning` ganti hue total antar mode. Value baru: bg/text/border lebih dalam & sedikit lebih netral (bg-page dark `#0b1310`, light `#eef2ea`), `--accent-strong` mode terang jadi `#0b5e2c` (beda shade dari `--accent` `#15803d`), `--warning` konsisten amber di kedua mode (`#f59e0b` dark, `#d97706` light, sebelumnya kuning-emas vs coklat kusam). `--accent`/`--accent-strong` mode GELAP: nilai aktual kode `#0f9a4c`/`#22b862` (lihat butir "Nilai accent aktual" di atas; `#22c55e`/`#4ade80` adalah nilai lama).
- ⚠️ **Perhatian:** hijau brand dan hijau "status sukses" mirip. Kalau dipakai berdampingan (misal badge sukses di atas elemen brand hijau) dan bikin bingung, pisahkan shade (misal brand pakai `#15803D`, status sukses pakai `#22C55E` atau sejenis).
- Font: **Inter**.
- Logo JBTB: skema ijo-hitam.

## Token Desain (AY.3.1, 28 Sept 2026)

Token tipografi/radius/spacing di `partials/theme-style.blade.php` (`:root`, berlaku dua tema), dipakai kelas bersama `.btn`, `.card`, `.badge`, `.metric-*`, `.alert-*`, dan `input/select/textarea` di `layouts/app.blade.php`. Style di halaman/komponen spesifik (inline `style=""`) TIDAK wajib migrasi kecuali file itu memang lagi disentuh — jangan rewrite massal.

| Token | Nilai | Pemakaian |
|---|---|---|
| `--fs-xs` | 12px | label kecil, badge, metric-label — batas bawah UI, jangan ada teks lebih kecil dari ini |
| `--fs-sm` | 13px | `.btn-sm`, teks sekunder |
| `--fs-base` | 14px | `.btn`, body teks default, `.alert-*` |
| `--fs-md` | 16px | input/select/textarea (termasuk semua input pencarian — anti auto-zoom iOS) |
| `--fs-lg` | 18px | judul kartu/section |
| `--fs-xl` | 22px | `.metric-value` |
| `--radius-sm` | 6px | `.badge` |
| `--radius-md` | 8px | `.btn`, input, `.alert-*` |
| `--radius-lg` | 12px | `.card`, `.metric-card` |
| `--space-1`…`--space-6` | 4px–24px (step 4px) | padding/gap kelas bersama |

## Library UI (keputusan final, Sprint 1 — 22 Agu 2026)

Bukan Bootstrap, bukan Livewire/Alpine — **Blade + custom CSS design system** (satu partial `resources/views/partials/theme-style.blade.php` + `<style>` per layout), vanilla JS untuk yang butuh interaktivitas (canvas signature RF-26, toggle tema). Nego fee multi-round (RF-16-20) pakai POST form + reload biasa, bukan update real-time — cukup untuk skala pemakaian sistem ini.

## Behavior Rules

### Loading State
Semua aksi yang butuh network (submit form, nego fee, upload bukti transfer) HARUS ada indikator loading — spinner di tombol atau skeleton, JANGAN biarkan tombol terasa "diam" tanpa feedback (user non-teknis bisa klik berkali-kali kalau nggak ada sinyal).

### Error Message
- Selalu bahasa manusia, bukan pesan error teknis Laravel mentah (JANGAN tampilkan stack trace atau "SQLSTATE[...]" ke user manapun).
- Spesifik ke field yang salah, bukan generic "terjadi kesalahan".
- Untuk aksi kritis (submit nego fee, konfirmasi pembayaran, approve/reject CD) — pakai konfirmasi dulu (modal/dialog) sebelum aksi final, karena efeknya tercatat permanen di riwayat.

### Field Wajib (BO.3, 30 Sept 2026)
- Field wajib: bintang merah setelah label `<span class="wajib" aria-hidden="true">*</span>` + atribut `required` di input. Wajib/tidaknya ikut aturan validasi server (`required` di controller/FormRequest), jangan asal.
- Field tidak wajib: tanpa keterangan apa pun. JANGAN tulis "(opsional)"/"opsional" di label, placeholder, atau hint.
- Form panjang: satu baris `<p class="wajib-ket"><span class="wajib">*</span> wajib diisi</p>` di atas form.
- Input tanpa label terlihat (inline/placeholder saja): tambahkan ` *` di akhir placeholder.
- CSS `.wajib` / `.wajib-ket` ada di `partials/theme-style` (warna `--danger`, dipakai layout app, auth, publik).

### Empty State
- Halaman list kosong (belum ada lowongan, belum ada riwayat) HARUS ada ilustrasi/copy yang jelas + CTA relevan, bukan tabel kosong polos.

### Status Badge (konsisten di semua role, AY.3.3/3.6 — 28 Sept 2026)

**Aturan wajib: status selalu teks + warna, JANGAN cuma warna doang** (buta warna / layar redup nggak bisa bedain kalau cuma warna). Label dan kelas diambil dari model, satu sumber kebenaran, jangan bikin map label duplikat di view: `ProjectApplication::label()` / `badgeClass()` (konstanta `LABELS`/`BADGES`), `labelClient()` untuk Client, `FeeNegotiation::label()` / `badgeClass()`, `CastingProject::TAHAP_BADGES`. Komponen `<x-status-badge>` TIDAK ada (belum pernah dibuat); badge ditulis `<span class="badge {{ $x->badgeClass() }}">{{ $x->label() }}</span>`. Definisi CSS semua kelas badge: `partials/theme-style.blade.php` (satu sumber; jangan definisi ulang di layout/halaman).

#### Palet badge aktual (dicek terhadap kode, 13 Oktober 2026)

6 kelas semantik. Badge = latar tint transparan + border tipis + teks berwarna token (kecuali `badge-urgent`, solid). `.badge-warning` hanya alias `.badge-pending` (jangan dipakai di kode baru).

| Kelas | Warna | Arti dan kapan dipakai | Kapan TIDAK dipakai | Contoh status |
|---|---|---|---|---|
| `.badge-netral` | Abu (teks `--text-secondary`) | Belum mulai / antre / informasi biasa; role user; counter; "Belum Dinilai" | Jangan untuk hal yang butuh tindakan atau sudah gagal | `diajukan`, tahap `mendatang`, role (semua), "Belum Dibayar", counter |
| `.badge-info` | Biru (`--info`) | Sedang diproses pihak lain / sedang berjalan | Jangan untuk selesai (hijau) atau perlu tindakan kamu (kuning) | `diundang`, `direview_admin`, `diajukan_ke_client`, tahap `berjalan` |
| `.badge-aktif` | Hijau (`--accent-strong`) | Beres / sukses / aktif | Jangan untuk sekadar "sedang diproses" dan jangan di atas blok hijau brand tanpa pemisah | `deal`, `lolos`, `kontrak_ditandatangani`, `selesai_produksi`, tahap `selesai`, nego `terima`, lowongan `dibuka` |
| `.badge-pending` | Kuning/amber (`--warning`) | Menunggu TINDAKAN seseorang (bukan sekadar antre) | Jangan untuk penghitung atau info; jangan untuk antre pasif (itu netral/info) | `nego_fee`, tahap `menunggu_acc`, nego `tawar`/`counter` |
| `.badge-tolak` | Merah (`--danger`) | Gagal / batal / ditolak / melanggar. Satu-satunya pemakaian merah untuk status | Jangan untuk urgensi (itu `badge-urgent`) dan jangan untuk "peringatan biasa" | `ditolak`, `dibatalkan`, akun nonaktif/dihapus, nego `tolak` |
| `.badge-urgent` | Oranye solid (`--urgent` / teks `--urgent-on`), tebal, ≥12 px | Mendesak yang masih bisa ditindak: Dadakan, Bentrok jadwal, Absen hari ini, Jam panggil | Jangan untuk proyek yang sudah ditutup/selesai dan jangan jadi pengganti merah (gagal) | "Dadakan", "Bentrok", "Absen hari ini" |

Role (`User`) SELALU `.badge-netral` — bukan kuning, biar tidak keliru dianggap "menunggu tindakan". Lowongan ditutup tidak berbadge Dadakan.

Warna teks badge per tema (token `theme-style`): terang aktif `#0b5e2c`, pending `#92400e`, tolak `#b91c1c`, netral `#435449`, info `#1d4ed8`, urgent `#ffffff` di `#c2410c`; gelap aktif `#22b862`, pending `#f59e0b`, tolak `#fa7a7f`, netral `#9db3a2`, info `#60a5fa`, urgent `#1a0f05` di `#fb923c`.

**Kontras terhitung** (rumus WCAG 2.x: L = 0,2126 R + 0,7152 G + 0,0722 B dengan kanal sRGB dilinearkan; rasio = (L1 + 0,05) / (L2 + 0,05); target teks ≥ 4,5:1). Latar badge = tint rgba dicampur di atas latar kartu (terang `#ffffff`, gelap `#1c1e1e`) atau halaman (terang `#eef2ea`, gelap `#17181a`). Dihitung sendiri 13 Okt 2026:

| Badge | Terang: kartu / halaman | Gelap: kartu / halaman |
|---|---|---|
| `badge-aktif` | 6,95 / 6,22 | 4,97 / 5,32 |
| `badge-pending` | 6,45 / 5,77 | 5,78 / 6,18 |
| `badge-tolak` | 5,55 / 4,91 | 5,76 / 6,15 |
| `badge-netral` | 6,95 / 6,26 | 5,45 / 5,85 |
| `badge-info` | 5,57 / 4,93 | 6,11 / 6,51 |
| `badge-urgent` (solid) | 5,18 | 8,33 |

Teks umum (rasio): terang primary di kartu 17,94, secondary 8,07, muted 5,65 (di halaman 4,98); gelap secondary 7,50, muted 5,67. Tombol `.btn-brand`: terang `#ffffff` di `#15803d` 5,02; gelap `#04140a` di `#0f9a4c` 5,18. Terendah di tabel badge = 4,91 (tolak di halaman terang), masih lolos; jangan menggelapkan latar tint atau meringankan teks tanpa menghitung ulang.

#### Glosarium istilah pengguna (CG.1.2)

Sapaan: Extras = "kamu"; Client dan halaman publik = "Anda". Admin/Korlap/Super Admin: bahasa operasional netral. Jangan ubah nama route/kolom/enum, hanya teks yang tampil. Ambil label status dari model (di atas), bukan ditulis ulang.

| Pakai | Jangan pakai | Audiens / catatan |
|---|---|---|
| Pilih / Tolak, Dipilih / Ditolak | Approve / Reject | Client (`labelClient()`: `diajukan_ke_client` tampil "Menunggu keputusan"; `lolos`/`kontrak_ditandatangani`/`selesai_produksi` tampil "Dipilih") |
| Pilih Kandidat | Greenlight | Menu, judul, dashboard Client |
| Menunggu Client | (kode status mentah) | Extras, di bilah langkah (`application-progress`) |
| Jam panggil | Callingan | Admin, Client, Korlap (jadwal dan absensi) |
| Absen di Lokasi | Check-in | Tombol Extras di hari shooting |
| Absensi Lapangan | Absensi on-set | Menu Admin/Korlap, dashboard Admin |
| Kata Sandi | Password | Semua form, label, dan tombol mata. Sisa "Reset password" di kebab Manajemen Akun SA adalah label internal, ganti bila file itu disentuh |
| Dadakan / Butuh Dadakan | Urgent | Judul lowongan dan toggle Admin: "Butuh Dadakan"; badge: "Dadakan" |
| Arsipkan | Hapus (untuk soft-delete akun) | SA/Admin. Dialog: akun diarsipkan, tidak bisa login, histori aman, bisa dipulihkan lewat filter "Dihapus" lalu Restore. "Hapus Permanen" hanya untuk akun mangkrak, selalu dengan jumlah + daftar + kata "permanen" |
| Batalkan | Cancel | Extras: "Batalkan keikutsertaan" |
| Lowongan, Pendaftar, Lineup | Job, Applicant | Semua |

Buang dari teks Client: Lock, Draft, Continuity, Greenlight. Pesan ke Extras memakai nama produksi, bukan kode internal "(JBTB-…)". Tanggal berbulan memakai `translatedFormat` (locale `id`), bukan `format('d M Y')`.

#### Palet landing vs aplikasi

Landing (`welcome.blade.php`) SENGAJA berbeda dari aplikasi: nuansa editorial (judul serif `Georgia, 'Times New Roman'`, aksen lime tua) untuk calon Extras/Client yang belum login. Aplikasi (semua layout `layouts/app`, auth, `public/event`, error) memakai token `theme-style` (aksen `#15803d`, font Inter) dan TIDAK boleh meminjam gaya landing. Token landing (variabel `--hp-*`, dua tema, definisi di `<style>` `welcome.blade.php`):

| Token | Gelap | Terang | Kontras terhitung |
|---|---|---|---|
| `--hp-bg` | `#111311` | `#f1f0e9` | (latar) |
| `--hp-fg` | `#f2f1eb` | `#171a16` | gelap 16,49; terang 15,37 (di bg) |
| `--hp-muted` | `rgba(242,241,235,.55)` (≈ `#8d8d89`) | `#52564e` | gelap 5,60 bg / 5,42 kartu; terang 6,56 bg / 5,88 kartu |
| `--hp-accent` | `#b7ff3c` | `#486610` | gelap 15,45 bg / 14,19 kartu; terang 5,77 bg / 5,17 kartu |
| `--hp-accent-on` (teks di atas aksen) | `#111311` | `#ffffff` | gelap 15,45; terang 6,60 |
| `--hp-line` | `rgba(255,255,255,.13)` | `rgba(23,26,22,.16)` | dekoratif (garis), tidak membawa teks |
| `--hp-card` | `#1a1c1a` | `#e6e4da` | (latar kartu) |

Aturan: (1) landing boleh berbeda palet/tipografi, tetapi kontras teks tetap ≥ 4,5:1 di kedua tema (hitung ulang bila token diubah; jangan memakai `opacity` untuk meredupkan teks, pakai token muted); (2) target sentuh 44 px dan `:focus-visible` tetap berlaku; (3) badge dan label status di landing memakai kelas badge aplikasi (mis. Dadakan = `badge-urgent`) dan istilah glosarium di atas; (4) elemen yang sama fungsinya dengan aplikasi (tombol masuk/daftar, tema) tetap terbaca sama maknanya; (5) halaman lain (event, privasi, auth, error) ikut palet aplikasi, bukan landing; (6) jangan menambah efek dekoratif (film grain, spotlight) yang sudah dihapus.

#### CSP gambar dan target sentuh

- CSP (`app/Http/Middleware/SecurityHeaders.php`): `img-src 'self' data: blob:`. Gambar eksternal (CDN gambar, hotlink) DIBLOKIR; simpan di storage lokal. Teks `data:` dan `blob:` dipakai untuk pratinjau unggahan. Menambah sumber gambar baru = ubah middleware itu sekaligus, bukan tempel `<img src="https://…">`.
- Target sentuh: ≥ 44 × 44 px untuk semua kontrol di layar ≤ 860 px (DS-09, `layouts/app`: `.btn-sm`, `.btn-ikon`, chip, tema/lonceng/avatar, tombol paginasi, checkbox/radio 22 px dengan area klik label). Di landing dan halaman publik: menu, tema, avatar, footer link ≥ 44 px. Kontrol baru wajib lolos aturan ini tanpa perlu dikoreksi belakangan.

#### Mode lihat Super Admin (SA-15/SA-16)

Banner `.sa-mode-banner` menempel (sticky) di atas konten saat mode Admin/Korlap/Client/Extras. Tombol/form yang terkunci mode lihat otomatis diberi ikon gembok (`ti ti-lock`) dan `aria-label` "(terkunci, mode lihat saja)"; penegakan sebenarnya tetap di middleware `ViewAs`, bukan di skrip.

## Yang HARUS Dibedakan per Role (jangan reuse layout mentah)

- **Super Admin** — dashboard monitoring/analitik ONLY. JANGAN kasih akses visual ke tombol-tombol operasional (posting lowongan, nego fee) meskipun secara data dia bisa lihat — ini bukan sekadar hide permission, tapi bagian dari desain "Super Admin nggak pegang operasional harian".
- **Talco/Sosmed** — cuma 1 halaman: "Riwayat Kerja & Status Gaji Saya" (read-only). Jangan render sidebar/menu operasional yang nggak relevan buat mereka.
- **Extras** — feed ala media sosial (bukan tabel admin-style), fee tertinggi & urgent di atas.
- **CD** — fokus approve/reject + riwayat booking, JANGAN expose data margin/fee-client mentah (tembok visibilitas, lihat `CLAUDE.md` §5).

## Anti-"Vibecoded Look" (26 Agu 2026)

Fakrul minta hindari ciri khas UI yang keliatan "AI-generated template" (referensi: thread "30 reasons your site looks vibecoded"). Sebagian besar poin di referensi itu buat landing page produk SaaS (pricing tiers, testimonials, TOS) — TIDAK relevan buat SIM Casting JBTB yang merupakan aplikasi internal RBAC, bukan produk yang dijual publik. Fokus cuma ke poin yang genuinely berlaku di sini:

### 1. Border-radius seragam di semua elemen
**Status: SUDAH DIPERBAIKI (AY.3.1, 28 Sept 2026)** — radius sekarang pakai token (`--radius-sm: 6px` untuk `.badge`, `--radius-md: 8px` untuk `.btn`/input/`.alert-*`, `--radius-lg: 12px` untuk `.card`/`.metric-card`), bukan lagi rata 8-20px di semua elemen. Jangan tambah radius baru di luar 3 token ini tanpa alasan kuat.

### 2. Hover animation generik tanpa tujuan
**Masalah:** `.btn:hover { background: var(--bg-card-hover); }` — transisi ada tapi tidak "berbicara" apa-apa, sekadar checklist "web modern harus ada hover state".
**Fix:** hover state boleh tetap ada (jangan dihapus, itu accessibility dasar), tapi kombinasikan dengan micro-feedback yang purposeful — misal tombol primary (`.btn-brand`) sedikit `transform: translateY(-1px)` + shadow saat hover, bukan cuma ganti warna background datar.

### 3. Tidak ada skeleton/loading state nyata
**Masalah:** `UI-GUIDELINES.md` di atas (§Behavior Rules → Loading State) sudah mensyaratkan ini, tapi belum diimplementasi konsisten di semua halaman yang fetch data (dashboard chart, list Callsheet, Lineup applicants). Halaman yang "loncat" dari kosong ke penuh konten tanpa transisi kerasa kaku/generic.
**Fix:** untuk minimal viable — tombol submit yang memicu network call (nego fee, upload bukti transfer, reject dini) kasih state `disabled` + teks berubah jadi "Memproses..." saat diklik, bukan wajib skeleton loader penuh (itu nice-to-have, bukan prioritas sekarang).

### 4. Badge/status pill generik
**Masalah:** semua badge status (Diajukan/Lolos/Ditolak dll, lihat tabel Status Badge di atas) sekadar `background + color` datar dengan pill radius — pola paling umum ditemukan di template AI manapun.
**Fix:** kasih border tipis (`1px solid` dengan warna yang sama tapi lebih pekat dari background transparan-nya) di badge, bukan cuma background transparan tanpa outline — sedikit detail ini yang membedakan "didesain" dari "di-generate".

### Yang JANGAN diubah
- Warna aksen hijau (`--accent`, lihat nilai aktual di §"Warna & Brand") tetap dipertahankan — bukan bagian dari masalah "vibecoded", itu brand JBTB. Begitu juga `--accent-strong`, `--danger`, `--warning` — JANGAN diubah tanpa eskalasi ke Fakrul (guardrail AU.1).
- Dark/light toggle dipertahankan — lihat §"Warna & Brand" di atas (kontradiksi lama SUDAH resolved 28 Agu 2026).
- JANGAN ganti Tabler Icons ke Lucide atau icon set lain — bukan bagian dari masalah, mengganti cuma buang waktu.
- JANGAN tambah gradient, radial orb, dot grid, atau efek dekoratif baru — itu solusi ke arah SEBALIKNYA dari yang diminta (justru banyak dekorasi generik itu sendiri salah satu ciri "vibecoded" di poin 19-29 referensi).

## Konvensi Kode (kalau pakai Blade)

Ikuti pola Nobel Akademi kalau relevan:
- CSS scoped per halaman pakai class prefix (misal `.casting-`, `.payroll-`) untuk isolasi style — hindari bentrok dengan Bootstrap global.
- Section yang belum final/CTA belum jelas: comment pakai Blade comment `{{-- --}}`, JANGAN dihapus, biar mudah diaktifkan lagi:
  ```blade
  {{-- TODO-XX: alasan disembunyikan --}}
  {{-- <section ...>...</section> --}}
  ```
