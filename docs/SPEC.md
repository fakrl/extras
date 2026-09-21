# SPEC.md — Bagian AK: Revisi Homepage — Editorial Layout dari Referensi "Sideroom"

> Ditulis 21 September 2026, oleh manager-session.

---

## Addendum Bagian AJ (Sebelum Lanjut ke AK)

Manager-session sudah melakukan 5 perbaikan manual langsung ke kode (bukan lewat Claude Code) setelah audit terakhir, karena sandbox manager-session tidak punya PHP runtime untuk verifikasi:

- `app/Http/Controllers/SuperAdmin/DashboardController.php` — bersihin ulang role lama di `$roleDisplayNames` & `$rekapHonorAdmin` (regresi dari sesi sebelumnya).
- `app/Http/Controllers/Auth/RegisterController.php` — registrasi Client sekarang set `role => 'client'` (sebelumnya masih `casting_director`).
- `tests/Feature/RegistrationRoleTest.php` & `tests/Feature/SuperAdminHonorRecapTest.php` — ekspektasi test disesuaikan ke role final.
- `database/factories/CastingProjectFactory.php` — default factory `admin_id` diganti ke role `admin`.

**Tolong jalankan `php artisan test` sekali lagi sebelum mulai kerja AK**, pastikan 5 perubahan ini tidak bikin apa pun merah. Kalau ada yang gagal, itu prioritas — beresin dulu sebelum masuk ke redesign homepage di bawah.

---

## Konteks & Tujuan Bagian AK

Fakrul kasih referensi desain baru (template landing page casting agency bernama "Sideroom", dibuat di v0/Next.js — file `app/page.tsx` + `app/globals.css`) karena homepage JBTB yang sekarang (`resources/views/welcome.blade.php`, hasil redesign teatrikal Bagian AC) masih kerasa "kaku". Fakrul suka **layout & warnanya** dari referensi ini dan minta homepage kita disesuaikan ke arah itu.

**PENTING — ini bukan port 1:1.** Referensi itu React/Next.js/Tailwind v4 murni dengan data dummy (nama talent fiktif, film fiktif). Tugasnya: ambil **bahasa desainnya** (tipografi, grid, rasio, interaksi hover) dan terapkan ke Blade + data asli JBTB (`$proyekTerbuka` dari `HomeController`, dll) — bukan nempel kode React ke Blade.

**Warna: JANGAN pakai warna dari referensi.** Referensi pakai lime accent `#b7ff3c` di atas near-black `#111311`. Proyek ini SUDAH punya sistem warna sendiri di `resources/views/partials/theme-style.blade.php` (var CSS `--accent`/`--accent-strong`/`--bg-page`/`--bg-sidebar`/`--text-primary`, dst, dengan toggle `data-theme="dark"|"light"` via localStorage `jbtb-theme-v2`). **Pertahankan variable itu** — cuma pola *layout & rasio kontras* dari referensi yang diambil, warnanya tetap hijau JBTB (`--accent-strong` dark = `#22b862`, light = `#0b5e2c`).

---

## Bagian AK.1: Hero Section — Tipografi Besar & Negative Space

Referensi (`page.tsx` baris 46-56):
```
<p class="uppercase tracking-wide text-accent">A casting agency for the moving image</p>
<h1 class="font-serif text-[clamp(4.5rem,13vw,12rem)] leading-[0.78] tracking-[-0.07em]">
  Faces<br /><span class="ml-[12vw] italic opacity-70">with</span><br /><span class="text-accent">range.</span>
</h1>
<p class="max-w-xs text-sm">deskripsi pendek</p>
<button>Play showreel</button>
```

Terapkan ke hero `welcome.blade.php` (ganti hero yang sekarang):
1. Eyebrow kecil uppercase tracked (reuse class `.section-eyebrow` yang sudah ada) — isi: "Sistem Manajemen Casting Extras".
2. Headline SANGAT besar, pakai `clamp()` biar responsive tanpa media query manual — misal `font-size: clamp(3rem, 11vw, 9rem)`, `line-height: 0.85`, `letter-spacing: -0.03em`. Isi 3 baris pendek ala referensi, contoh: "Talenta" / baris kedua di-indent + italic + redup ("yang" ) / baris ketiga warna aksen hijau ("dipercaya."). Sesuaikan copy-nya biar related ke casting/extras, bukan asal terjemahan literal.
3. JANGAN pakai webfont baru kalau tidak perlu (`/ponytail`: laziest solution) — headline besar ini bisa pakai `font-family: Georgia, 'Times New Roman', serif` (serif sistem) untuk kontras dengan body Inter, TANPA nambah `<link>` Google Font baru. Kalau Fakrul mau lebih premium, opsional pakai 1 Google Font display serif (misal "Fraunces") — tapi ini opsional, tanya dulu kalau ragu, jangan langsung nambah dependency.
4. Tombol "Play showreel" — ganti fungsinya jadi CTA yang masuk akal buat JBTB: misal scroll ke section proyek terbuka, atau kalau ada video reel/showreel JBTB yang beneran ada filenya, boleh dipertahankan sebagai modal video. Kalau nggak ada asset video, JANGAN buat modal kosong — ganti jadi CTA teks biasa ("Lihat proyek terbuka ↗").

## Bagian AK.2: Grid Galeri dengan Hairline Divider + Hover Grayscale→Color

Referensi (`page.tsx` baris 58-61) pakai trik `grid gap-px bg-white/15` — celah antar grid item 1px yang mengekspos warna background di baliknya, jadi kelihatan seperti garis pembatas tipis tanpa perlu `border` di tiap cell. Foto grayscale, jadi warna pas di-hover (`grayscale group-hover:grayscale-0`), ada badge nomor urut di kiri-atas dan badge "Lihat ↗" yang muncul saat hover di kanan-bawah.

Terapkan ke section galeri/proyek terbuka JBTB:
1. Ganti grid proyek yang sekarang (card-based, dari Bagian 57 dulu) — TAPI JANGAN buang logic urgent-badge/data dinamisnya, cuma ubah presentasi visualnya ke pola hairline-grid ini.
2. Pakai foto poster/cover proyek asli (`$project->poster_path` / `cover_path`) — kalau proyek belum punya foto, kasih fallback placeholder solid warna `--bg-card`, jangan foto abu-abu generik dari internet.
3. Efek grayscale→color di hover: `filter: grayscale(1); transition: filter .5s;` lalu `:hover { filter: grayscale(0); }` — CSS native, tidak butuh JS.
4. Grid pakai `gap: 1px; background: var(--border-color);` supaya hairline effect otomatis muncul dari background di balik gap (trik yang sama kayak referensi, translate ke CSS biasa).

## Bagian AK.3: Section "Layanan/Kenapa JBTB" — Split 2 Kolom + Accordion Angka

Referensi (`page.tsx` baris 64) — kiri: judul besar. Kanan: list layanan dengan nomor urut (`01`, `02`, `03`) + chevron, dipisah garis horizontal tipis antar item (`divide-y`).

Terapkan: ganti/gabungkan ke section "Kenapa JBTB" atau "Yang Kami Lakukan" yang sudah ada — pola nomor + divider tipis ini reuse dari `.section-eyebrow` yang sudah ada di codebase (sudah ada precedent-nya), tinggal disusun ulang jadi 2 kolom di desktop, 1 kolom di mobile.

## Bagian AK.4: Footer CTA Besar

Referensi (`page.tsx` baris 70) — footer dengan email/CTA gede pakai font serif besar, bukan tombol biasa. Adaptasi: CTA "Daftar jadi Extras" atau "Hubungi Kami" ditulis besar ala headline (bukan button kotak generik), plus kolom lokasi/kontak di sebelahnya.

## Revisi 2 (21 September 2026, setelah review Fakrul lihat hasil AK.1-AK.4)

Fakrul sudah lihat hasil implementasi pertama. Feedback: konsep section "cast" ketuker sama section lowongan (jadi keliatan kotak-kotak kosong karena banyak proyek belum punya poster), background image hero kosong/hilang, spacing antar section masih dempet, dan **warna mau diganti total ke palet referensi zip** (bukan dipertahankan ke hijau JBTB lama seperti instruksi AK awal — ini supersede instruksi warna di AK di atas). Detail per poin:

### AK.5: Pisahkan Section "Cast" (Foto Extras) dari Section "Lowongan"

Ini BUKAN section yang sama. Sekarang jadi dua section terpisah:

1. **Section lowongan proyek** (yang sekarang, hasil AK.2) — TETAP ADA, tetap pakai data `$proyekTerbuka`, tetap hairline-grid. Ini section informasional/fungsional, jangan dihapus.
2. **Section "cast" BARU** — section terpisah, taruh di antara hero dan section lowongan (posisi mirip "Meet the cast" di referensi). Isinya foto profil Extras asli, BUKAN poster proyek.
   - **Wajib pakai consent gate yang sudah ada**: HANYA tampilkan Extras yang `extras_profiles.share_token` sudah terisi (opt-in dari fitur share-profile Bagian W). JANGAN tampilkan foto Extras yang belum opt-in — itu melanggar prinsip privasi yang udah dibangun dari awal proyek ini. Query kira-kira: `User::where('role','extras')->whereHas('extrasProfile', fn($q) => $q->whereNotNull('share_token'))->with('extrasProfile')->inRandomOrder()->limit(12)->get()`.
   - **Src foto**: reuse route publik yang SUDAH ADA `route('public.extras.foto', $profile->share_token)` — jangan bikin route baru, jangan expose path storage privat langsung.
   - Kalau jumlah Extras yang opt-in di bawah, katakanlah, 4 orang, kasih fallback yang masih enak dilihat (placeholder silhouette/inisial nama, bukan section kosong/error).
   - **Auto-slide**: bikin marquee horizontal yang auto-scroll infinite (CSS `@keyframes` translateX loop, duplikat list-nya 2x biar looping-nya mulus, `animation-play-state: paused` saat `:hover` di container biar user bisa berhenti liat kalau mau). Ini native CSS, tidak perlu library JS tambahan (`/ponytail`).
   - Style per kartu: foto grayscale → warna pas hover (dari AK.2, dipertahankan), nama Extras + kota/domisili di bawah foto (jangan tampilkan data sensitif lain — nama asli/NIK/rekening tetap nggak boleh muncul, cukup `name` akun & domisili kalau ada).

### AK.6: Background Image Hero — Pastikan Kepasang

Sekarang hero kosong/putih, harusnya ada background image dengan gradient overlay (biar teks tetap kebaca). Pola dari referensi (`hero-backdrop`):
```css
.hero-backdrop {
  background-image: linear-gradient(90deg, var(--bg-page) 0%, color-mix(in srgb, var(--bg-page) 82%, transparent) 52%, color-mix(in srgb, var(--bg-page) 50%, transparent) 100%), url('/images/homepage-hero-bg.png');
  background-size: cover;
  background-position: center;
  min-height: 560px;
}
@media (max-width: 767px) { .hero-backdrop { min-height: 540px; background-position: 68% center; } }
```
Pakai `color-mix()` dengan `var(--bg-page)` (bukan hardcode `rgba(17,19,17,...)` dari referensi) supaya gradient-nya otomatis ikut warna dark/light mode yang aktif — ini triknya biar satu CSS jalan di kedua tema tanpa override manual per tema.

Untuk gambarnya: manager-session sudah extract 1 file gambar dari referensi (`sideroom-cinematic-bg.png` — foto studio/backstage dengan glow hijau di sisi kanan, cocok banget sama tema casting). Kalau Fakrul oke, taruh di `public/images/homepage-hero-bg.jpg` dan pakai itu sebagai placeholder. **Catatan lisensi**: itu asset dari template pihak ketiga (v0/Sideroom), aman dipakai untuk demo/skripsi tapi kalau nanti mau go-live publik idealnya diganti foto behind-the-scenes JBTB sendiri — bukan blocker sekarang, cuma dicatat.

### AK.7: Ganti Total ke Palet Warna Referensi (Supersede Instruksi Warna di AK Awal)

Fakrul explicitly minta pakai warna dari zip langsung, bukan warna hijau JBTB yang lama. Update `resources/views/partials/theme-style.blade.php` KHUSUS untuk konteks homepage (jangan ubah var global yang dipakai dashboard admin/extras/CD — biar nggak ganggu tampilan internal yang udah settle), pakai class scope baru misal `.homepage-shell`:

```css
.homepage-shell[data-theme="dark"] {
  --hp-bg: #111311;
  --hp-fg: #f2f1eb;
  --hp-muted: rgba(242, 241, 235, 0.6);
  --hp-accent: #b7ff3c;
  --hp-accent-on: #111311;
  --hp-line: rgba(255, 255, 255, 0.15);
  --hp-card: #1a1c1a;
}
.homepage-shell[data-theme="light"] {
  --hp-bg: #f1f0e9;
  --hp-fg: #171a16;
  --hp-muted: rgba(23, 26, 22, 0.6);
  --hp-accent: #76a51b;
  --hp-accent-on: #ffffff;
  --hp-line: rgba(23, 26, 22, 0.16);
  --hp-card: #e6e4da;
}
```
(Nilai light-mode ini persis dari `globals.css` referensi — sudah dituning biar nggak kesilauan/gelap, jangan diubah lagi kecuali Fakrul minta.)

Semua elemen di `welcome.blade.php` (hero, cast grid, lowongan grid, section layanan, footer) pakai var `--hp-*` ini, BUKAN `--accent`/`--bg-page` lama. **PENTING**: nav bar & logo di homepage (yang sekarang masih hijau forest lama, keliatan beda sama section di bawahnya begitu discroll) HARUS ikut diubah ke `--hp-accent` juga, biar satu halaman konsisten — jangan cuma section baru yang ganti warna sementara header-nya masih warna lama. Ini konsisten hanya di halaman publik (`/`); begitu user login masuk dashboard, tetap pakai `--accent` hijau JBTB yang lama seperti biasa (dashboard TIDAK terpengaruh perubahan ini).

### AK.8: Spacing Antar Section

Naikkan padding vertikal tiap `<section>` di homepage — sekarang kerasa dempet. Target minimal `padding-block: 5rem` di mobile, `padding-block: 7-8rem` di desktop (referensi pakai `py-16 md:py-24` yaitu ~4rem mobile/6rem desktop, tapi Fakrul minta lebih jelas lagi — boleh dinaikkan sedikit dari referensi). Juga pastikan ada jarak yang cukup antara heading section dan konten di bawahnya (minimal `margin-bottom: 2-2.5rem` setelah heading section), jangan cuma padding luar section yang dibesarkan.

## Checklist Eksekusi untuk Implementer (Claude Code)

- [ ] Section cast (foto Extras, opt-in only via `share_token`) dipisah dari section lowongan proyek — dua section beda, keduanya tetap ada.
- [ ] Cast grid pakai route publik existing (`public.extras.foto`), auto-slide marquee CSS-only, pause on hover.
- [ ] Hero pakai background image + gradient overlay berbasis `color-mix(var(--bg-page)...)`, tidak kosong lagi.
- [ ] Palet warna homepage diganti total ke nilai AK.7 (`--hp-*` vars, scope `.homepage-shell`), termasuk nav bar & logo — TIDAK mengubah var global dashboard.
- [ ] Padding antar section dibesarkan sesuai AK.8, termasuk jarak heading-ke-konten.
- [ ] Cek toggle dark/light tetap jalan normal dengan palet baru.
- [ ] Cek responsive mobile lagi setelah semua perubahan ini.
- [ ] Screenshot sebelum/sesudah, biar Fakrul gampang review tanpa perlu buka browser sendiri.
