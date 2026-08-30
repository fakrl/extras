# UI-GUIDELINES.md — SIM Casting JBTB

> Design system ringkas. Sumber keputusan asli ada di `CLAUDE.md` §7 (deskripsi prototype) & §8 (Decision Log) — file ini distilasi buat di-mention pas generate view, jangan suapin seluruh CLAUDE.md tiap butuh styling.

## Prinsip Utama: "Semua Umur"

User Extras termasuk orang tua/non-teknis. Ini bukan preferensi estetika, ini requirement (RNF-05). Semua keputusan UI harus lolos tes ini duluan sebelum pertimbangan lain.

- Tombol minimal **48px** tinggi (touch target).
- Bahasa Indonesia sederhana, hindari jargon teknis di copy (misal bukan "gagal validasi", tapi "data belum lengkap, cek lagi ya").
- Indikator status pakai warna konsisten di SELURUH sistem: **hijau** = beres/aktif/approved, **kuning** = menunggu/pending, **merah** = batal/tolak/melanggar.
- Focus ring keyboard wajib ada (accessibility dasar).

## Warna & Brand

- **Primary:** hijau `#15803D`
- **Dark/hero:** `#0B1A12`
- **Konten (body, dashboard):** dark/light toggle tetap dipertahankan (keputusan final 28 Agu 2026, Fakrul) — user pilih sendiri via tombol di topbar, preferensi disimpan `localStorage`. "Semua umur" dipenuhi lewat kontras warna yang cukup di KEDUA tema, bukan dengan memaksa satu tema. Palet dark direvisi 28 Agu jadi netral (`resources/views/partials/theme-style.blade.php`) — bukan hijau tua di semua permukaan, biar nggak terasa gelap/pekat berlebihan. **Direvisi lagi 30 Agu 2026 (Opsi B dari 3 mockup)** — alasan: warna netral versi 28 Agu masih kebawa tint hijau (kurang kontras brand vs netral), `--accent-strong` mode terang identik sama `--accent` (nggak ada sinyal depth buat hover), dan `--warning` ganti hue total antar mode. Value baru: bg/text/border lebih dalam & sedikit lebih netral (bg-page dark `#0b1310`, light `#eef2ea`), `--accent-strong` mode terang jadi `#0b5e2c` (beda shade dari `--accent` `#15803d`), `--warning` konsisten amber di kedua mode (`#f59e0b` dark, `#d97706` light, sebelumnya kuning-emas vs coklat kusam). `--accent`/`--accent-strong` mode GELAP tidak berubah (`#22c55e`/`#4ade80`, sudah benar).
- ⚠️ **Perhatian:** hijau brand dan hijau "status sukses" mirip. Kalau dipakai berdampingan (misal badge sukses di atas elemen brand hijau) dan bikin bingung, pisahkan shade (misal brand pakai `#15803D`, status sukses pakai `#22C55E` atau sejenis).
- Font: **Inter**.
- Logo JBTB: skema ijo-hitam.

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

### Status Badge (konsisten di semua role)
| Status | Warna | Konteks |
|---|---|---|
| Diajukan / Direview | Kuning | project_applications |
| Nego Fee / Deal | Kuning → Hijau saat Deal | fee_negotiations |
| Lolos / Approved | Hijau | cd_reviews |
| Ditolak / Ditolak CD | Merah | |
| Melanggar | Merah | extras_profiles.status |
| Bentrok Jadwal (warning) | Kuning, ikon ⚠️ | non-blocking, RF-13/22 |
| Ditransfer (belum konfirmasi) | Kuning | payments |
| Dikonfirmasi Diterima | Hijau | payments |

## Yang HARUS Dibedakan per Role (jangan reuse layout mentah)

- **Super Admin** — dashboard monitoring/analitik ONLY. JANGAN kasih akses visual ke tombol-tombol operasional (posting lowongan, nego fee) meskipun secara data dia bisa lihat — ini bukan sekadar hide permission, tapi bagian dari desain "Super Admin nggak pegang operasional harian".
- **Talco/Sosmed** — cuma 1 halaman: "Riwayat Kerja & Status Gaji Saya" (read-only). Jangan render sidebar/menu operasional yang nggak relevan buat mereka.
- **Extras** — feed ala media sosial (bukan tabel admin-style), fee tertinggi & urgent di atas.
- **CD** — fokus approve/reject + riwayat booking, JANGAN expose data margin/fee-client mentah (tembok visibilitas, lihat `CLAUDE.md` §5).

## Anti-"Vibecoded Look" (26 Agu 2026)

Fakrul minta hindari ciri khas UI yang keliatan "AI-generated template" (referensi: thread "30 reasons your site looks vibecoded"). Sebagian besar poin di referensi itu buat landing page produk SaaS (pricing tiers, testimonials, TOS) — TIDAK relevan buat SIM Casting JBTB yang merupakan aplikasi internal RBAC, bukan produk yang dijual publik. Fokus cuma ke poin yang genuinely berlaku di sini:

### 1. Border-radius seragam di semua elemen
**Masalah:** `layouts/app.blade.php` saat ini pakai radius 8-20px hampir di semua elemen (`.card` 12px, `.btn` 10px, `.badge` 20px pill, `.sidebar-link` 8px, dst) — semua elemen dibulatkan dengan rasa yang sama, ini yang bikin kesan "generated", bukan didesain sengaja.
**Fix:** variasikan radius berdasarkan fungsi elemen, bukan rata semua:
- Container besar (card, modal, box upload): radius lebih kecil & tegas, misal `8px` konsisten — bukan campur 12-14px.
- Elemen interaktif kecil (button, input, badge): radius lebih kecil lagi (`6px`), JANGAN pill penuh (`20px`/`50%`) kecuali memang avatar/dot status.
- `.badge` saat ini `border-radius: 20px` (pill generik) — ganti ke `6px` biar terasa seperti status tag yang didesain, bukan default Tailwind/shadcn.

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
- Warna aksen hijau (`--accent: #22c55e`) tetap dipertahankan — bukan bagian dari masalah "vibecoded", itu brand JBTB.
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
