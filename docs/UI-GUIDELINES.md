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
- **Konten (body, dashboard):** dark/light toggle tetap dipertahankan (keputusan final 28 Agu 2026, Fakrul) — user pilih sendiri via tombol di topbar, preferensi disimpan `localStorage`. "Semua umur" dipenuhi lewat kontras warna yang cukup di KEDUA tema, bukan dengan memaksa satu tema. Palet dark direvisi 28 Agu jadi netral (`resources/views/partials/theme-style.blade.php`) — bukan hijau tua di semua permukaan, biar nggak terasa gelap/pekat berlebihan. **Direvisi lagi 30 Agu 2026 (Opsi B dari 3 mockup)** — alasan: warna netral versi 28 Agu masih kebawa tint hijau (kurang kontras brand vs netral), `--accent-strong` mode terang identik sama `--accent` (nggak ada sinyal depth buat hover), dan `--warning` ganti hue total antar mode. Value baru: bg/text/border lebih dalam & sedikit lebih netral (bg-page dark `#0b1310`, light `#eef2ea`), `--accent-strong` mode terang jadi `#0b5e2c` (beda shade dari `--accent` `#15803d`), `--warning` konsisten amber di kedua mode (`#f59e0b` dark, `#d97706` light, sebelumnya kuning-emas vs coklat kusam). `--accent`/`--accent-strong` mode GELAP tidak berubah (`#22c55e`/`#4ade80`, sudah benar).
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

### Empty State
- Halaman list kosong (belum ada lowongan, belum ada riwayat) HARUS ada ilustrasi/copy yang jelas + CTA relevan, bukan tabel kosong polos.

### Status Badge (konsisten di semua role, AY.3.3/3.6 — 28 Sept 2026)

**Aturan wajib: status selalu teks + warna, JANGAN cuma warna doang** (buta warna / layar redup nggak bisa bedain kalau cuma warna). Pakai `<x-status-badge :model="$x" />` yang otomatis manggil `label()`/`badgeClass()` dari model (`ProjectApplication`, `Payment`, `User`, `CastingProject` — lihat konstanta `LABELS`/`BADGES` di masing-masing model, satu sumber kebenaran, jangan bikin map label duplikat di view).

5 varian badge semantik (bukan cuma 3 lagi):

| Kelas | Warna | Arti | Contoh status |
|---|---|---|---|
| `.badge-netral` | Abu | Menunggu diproses / belum mulai; role user | `diajukan`, `belum_dibayar`, role (semua) |
| `.badge-info` | Biru (`var(--info)`) | Sedang diproses | `direview_admin`, `diajukan_ke_cd`, `direview_cd`, `ditransfer` |
| `.badge-aktif` | Hijau | Beres / sukses | `deal`, `lolos`, `kontrak_ditandatangani`, `selesai_produksi`, `dikonfirmasi_diterima`, project `dibuka` |
| `.badge-pending` | Kuning | Menunggu TINDAKAN (bukan sekadar antre) | `nego_fee`, `disengketakan` |
| `.badge-tolak` | Merah | Gagal / batal | `ditolak`, `dibatalkan` |

Role (`User`) SELALU `.badge-netral` — bukan kuning, biar tidak keliru dianggap "menunggu tindakan".

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
