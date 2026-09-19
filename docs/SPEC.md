# SPEC.md — Homepage v2: Profil Perusahaan Akurat + Logo Asli + Lowongan Terbuka

> Ditulis 15 September 2026, oleh manager-session.
> Bagian A-J (SPEC.md sebelumnya) semua sudah dieksekusi & ter-commit — lihat `DEV-NOTES.md` Session 38-41 dan commit `d09b98f`, `0523c70`, `946e675`, `1572425`, `35cdbcf`. Diverifikasi ulang manager-session hari ini (baca kode langsung + `git log`): semuanya sesuai spec, tidak ada regresi. **Satu catatan proses:** `DEV-NOTES.md` belum ada entry buat Bagian F/G/H/I/J (cuma sampai Session 41 = Bagian E). Tolong tambahkan entry Session 42 yang merangkum Bagian F-J sebelum atau sesudah task ini, biar dokumentasi nggak makin ketinggalan dari kode — ini kejadian yang sama untuk kedua kalinya (Bagian E sempat begini juga), coba lebih disiplin update `DEV-NOTES.md` di akhir tiap sesi kerja, bukan cuma pas commit.

## Konteks

Fakrul cek homepage hasil Bagian J, masih kurang "corporate": (1) profil perusahaan cuma 2 paragraf generik, sebagian datanya malah SALAH (bilang "Depok", padahal proposal bilang Pamulang, Tangsel), (2) belum ada logo asli, masih placeholder huruf "J", (3) mau ada section lowongan/proyek terbuka di homepage sebagai pancingan daftar akun — bukan cuma teaser 1 baris angka doang.

**Sumber kebenaran:** `docs/PROPOSAL PROJECT WORK_JBTB CASTING DRAFT 7.pdf` Bab II (Profil Mitra). Manager-session sudah ekstrak teks & gambar logo asli langsung dari PDF ini — JANGAN karang ulang/parafrase bebas, JANGAN pakai asumsi lokasi/tahun berdiri dari mana pun selain yang dikutip di bawah.

## Bagian K.1: Logo asli

File logo **sudah diekstrak dan ditaruh di `public/images/logo-jbtb.jpg`** (321×298px, JPEG — resolusi pas-pasan buat ukuran besar, jangan di-scale up berlebihan, cukup buat badge/navbar/footer kecil-sedang). Ganti SEMUA placeholder huruf "J" (`.hp-logo` di navbar, area hero kalau ada) jadi `<img src="{{ asset('images/logo-jbtb.jpg') }}" alt="Logo PT. JBTB Casting Creative Group">` dengan `object-fit: cover` di container yang sama ukurannya kayak placeholder sekarang (jangan bikin container baru yang beda proporsi, biar nggak perlu re-tuning layout).

## Bagian K.2: Profil Perusahaan — pakai data ASLI dari proposal, bukan generik

**Fix kesalahan dulu:** domisili yang bener adalah **Pamulang, Tangerang Selatan, Banten** (bukan Depok — ini salah tulis di Bagian J kemarin, tolong dicek juga apakah ada tempat lain di codebase yang kebawa salah nulis "Depok" buat JBTB, misal di dokumentasi lain).

**Data resmi (kutip persis, ini dari Bab II Profil Mitra proposal), tampilkan sebagai "quick facts" grid/list di section Tentang JBTB, BUKAN cuma disebut selewat di paragraf:**
- Nama Perusahaan: PT. JBTB Casting Creative Group
- Tahun Berdiri: 2020
- Bidang Usaha: Talent Agency & Casting Management
- Domisili: Pamulang, Tangerang Selatan, Banten
- Legalitas: Perseroan Terbatas (PT) & Nomor Induk Berusaha (NIB), terdaftar OSS Kemenves RI
- Jumlah Karyawan: 5 orang (tim inti)
- Skala Operasional: 50–80 extras aktif, 4–5 proyek per bulan

**Sejarah (parafrase dari proposal, boleh dipersingkat tapi jangan ubah fakta):**
> PT. JBTB Casting Creative Group adalah perusahaan talent agency dan casting management yang berfokus pada penyediaan extras/pemeran figuran untuk kebutuhan produksi film, iklan, dan konten kreatif di Indonesia. Resmi berdiri sejak 2020 dengan badan hukum PT dan NIB terdaftar OSS. Berkantor pusat di Pamulang, Tangerang Selatan, dengan tim inti 5 orang profesional, JBTB aktif mengelola 50-80 extras dan menangani 4-5 proyek per bulan, melayani klien dari rumah produksi, brand, dan tim iklan di industri hiburan Indonesia.

**Visi (kutip PERSIS):**
> Menjadi platform casting digital terdepan di Indonesia yang transparan, profesional, dan terintegrasi bagi seluruh ekosistem film, periklanan, dan segala yang berhubungan dengan manajemen talent di industri entertainment.

**Misi (kutip PERSIS, 4 poin):**
1. Menyediakan sistem manajemen talent yang rapi, aman, dan mudah digunakan.
2. Menjadi sarana resmi open casting yang kredibel bagi seluruh pelaku industri kreatif.
3. Mempercepat proses seleksi melalui fitur filter dan rekomendasi otomatis.
4. Membantu talent mengembangkan karir profesional di industri hiburan Indonesia.

**Layout section ini (REVISI — lihat Bagian K.6):** jadi 2 blok aja: (1) Sejarah + quick facts grid di sampingnya, (2) Visi & Misi (list bernomor). **Blok "Kenapa Pakai Sistem Ini?" DIHAPUS TOTAL** — instruksi Fakrul: section ini nggak perlu, karena homepage sendiri (hero, stats, cara kerja, lowongan) udah cukup nunjukkin apa yang bisa dilakukan di web ini, nggak perlu dijelasin lagi. Hapus block-nya di `welcome.blade.php` (yang sekarang ada `<div class="section-title">Kenapa Pakai Sistem Ini?</div>` + paragraf di bawahnya) — jangan disembunyiin doang (`display:none`), hapus bersih dari markup.

## Bagian K.3: Section "Lowongan Terbuka" (proyek casting yang lagi buka pendaftaran)

**Konteks:** sekarang cuma ada 1 baris teaser angka ("X proyek buka pendaftaran"). Fakrul mau section beneran nampilin proyek-proyek yang terbuka — biar homepage jadi pancingan konkret buat daftar, bukan cuma angka abstrak.

**Data:** `CastingProject::where('status', 'dibuka')->get()->filter->menerimaPendaftaran()` (pakai method `menerimaPendaftaran()` yang sudah ada di model — mencakup cek deadline & kuota belum penuh, bukan cuma `status` doang). Load relasi `classes` (Nama Peran + kuota per peran, hasil Bagian G).

**Yang boleh ditampilkan per kartu proyek (ikutin pola data-minimization yang SUDAH ada di `PublicEventController`/`public/event.blade.php` — CLAUDE.md §5):**
- Nama Produksi
- Daftar Nama Peran yang dicari (dari `classes`, field `nama_kelas` hasil rename Bagian G) + kuota per peran
- Deadline pendaftaran

**Yang TIDAK BOLEH ikut ke view ini sama sekali (sama kayak larangan di `PublicEventController`):** `client_ph`, `budget_client` (baik level proyek maupun per-kelas/peran). Cek ulang `CastingProjectController`/query yang dipakai — pakai `select()`/`with()` yang membatasi kolom, jangan `->get()` polos yang keikut semua kolom terus cuma nggak dirender di blade (itu masih bocor kalau ada yang buka dev tools/inspect response).

**CTA per kartu:**
- Guest (belum login): tombol "Daftar untuk Apply" → `route('register')` — sesuai instruksi Fakrul, "pancingan" yang maksa bikin akun dulu sebelum bisa apply beneran (apply sungguhan tetap di `extras.projects.apply`, yang sudah di-guard `auth`+`role:extras`, tidak berubah).
- Sudah login sebagai Extras: tombol "Lihat & Apply" → `route('extras.projects.show', $project)`.
- Sudah login sebagai role lain (Admin/CD/dst): jangan tampilkan CTA apply (tidak relevan buat role itu) — tampilkan kartu info doang tanpa tombol, atau sembunyikan section ini sama sekali kalau lagi login sebagai non-Extras (pilih salah satu, yang mana pun boleh, dokumentasikan alasannya).

**Kalau proyek terbuka kosong (0 proyek):** tampilkan empty state yang sopan ("Belum ada lowongan terbuka saat ini, cek lagi nanti" atau semacamnya), jangan section-nya hilang total (biar homepage tetap konsisten strukturnya).

**Batasi jumlah kartu** yang tampil di homepage (misal maks 6, terbaru/deadline terdekat dulu) — kalau proyek terbuka banyak, jangan bikin homepage jadi scroll panjang banget. Kalau lebih dari batas itu, kasih link "Lihat semua lowongan" (boleh ke halaman baru kalau mau bikin, atau ke arah register kalau belum ada halaman listing publik penuh — keputusan implementasi, dokumentasikan pilihannya).

## Bagian K.4: Sentuhan visual dari riset 5 web referensi (agiveteam.co, widescreen.id, casting.mdentertainment.com, mdentertainment.com, virbygroup.com)

**Konteks:** manager-session sudah akses langsung ke-5 web ini atas instruksi Fakrul. Hasil: `virbygroup.com` **tidak dipakai referensi apapun** (aesthetic holding-company banner-ad generik, tidak relevan). 3 pola konkret yang layak diadopsi dari yang lain:

1. **Dari widescreen.id** — ganti paragraf "Kenapa Pakai Sistem Ini?" (di Bagian K.2, blok 3) jadi grid 3-4 card (icon Tabler + 1 keyword bold + 1 kalimat pendek), pakai CSS grid native (`.why-grid`, reuse pola `.stat-card`/`.stats-grid` yang sudah ada biar konsisten), TANPA dependency baru.
2. **Dari agiveteam.co** — section "Lowongan Terbuka" (K.3) styling-nya pakai numbered card (badge angka 01/02/03 di pojok kartu) + badge status **"DIBUKA"** yang jelas kelihatan (warna `--accent`), bukan tabel/kartu polos tanpa penanda. Ini supaya beda sama pola "portfolio proyek lama" — di referensinya ini sempat bikin bingung karena "lowongan dibuka" dan "portfolio lama" nyampur tanpa label; di kita harus tegas kelihatan ini "OPEN".
3. **Dari mdentertainment.com** — kalau ke depannya ada foto behind-the-scene/produksi asli yang representatif, prioritaskan foto itu mendominasi hero ketimbang cuma pattern SVG. **Untuk sekarang belum ada aset foto seperti itu — SKIP dulu, jangan maksa cari stock photo generik, biarkan hero pakai pattern yang sudah ada.**

## Bagian K.5: Navbar jadi menu company beneran (bukan cuma logo+login)

**Konteks:** Fakrul mau homepage kerasa "company" beneran — ada menu navbar kayak web company pada umumnya. Catatan dari Fakrul: item yang emang butuh login ya tetap harus login (nggak digratisin), tapi kasih tanda di menu-nya biar user nggak kaget pas diarahkan ke login.

**Struktur menu (semua anchor di homepage yang sama, TIDAK bikin route/page baru — cek dulu `routes/web.php`: satu-satunya halaman publik beneran cuma `/` dan `/privacy-policy`, sisanya di-guard `auth`, jadi menu berbasis anchor scroll adalah pilihan paling masuk akal, bukan cuma males-malesan):**

- "Beranda" → scroll ke atas/hero (`#`)
- "Tentang Kami" → anchor ke `.about-section` (kasih `id="tentang"`)
- "Cara Kerja" → anchor ke `.how-section` (kasih `id="cara-kerja"`)
- "Lowongan Terbuka" → anchor ke `.lowongan-section` (kasih `id="lowongan"`)

Taruh sebagai `<ul>` horizontal di tengah `.hp-nav`, di antara brand (kiri) dan action buttons (kanan yang sudah ada: theme toggle + Masuk/Daftar/Dashboard). Native CSS flex, sembunyikan jadi hamburger/collapse di mobile (breakpoint 768px yang sudah ada) — jangan bikin dependency JS baru buat ini, cukup toggle class + `display:none/flex` biasa.

**Yang butuh login (tanda-nya):** action button paling kanan yang sudah ada (`Dashboard` kalau `@auth`, `Masuk`/`Daftar` kalau `@guest`) itu SUDAH cukup jadi penanda alami — guest otomatis lihat "Masuk"/"Daftar", bukan "Dashboard". Tidak perlu nambah menu item baru yang isinya cuma redirect ke login — itu cuma bikin bingung karena dobel makna sama tombol yang sudah ada. Kalau nanti mau nambah 1 menu item spesifik yang tujuannya halaman ber-auth (misal "Lihat Semua Lowongan" ke `/extras/projects`), kasih small lock icon (`<i class="ti ti-lock">`) di sebelah teks HANYA kalau state-nya `@guest`, dan link-nya tetap ke `route('register')` (bukan ke halaman yang 404/redirect-loop buat guest) — pola ini SUDAH ada persis di section Lowongan Terbuka (K.3) buat tombol "Lihat semua lowongan", jadi tinggal reuse, jangan bikin variasi baru.

## Bagian K.6: Palet warna dark mode — pakai warna ASLI dari logo, bukan "army green"

**Konteks:** Fakrul masih nggak puas sama dark mode hasil Bagian I (`--accent:#10b981`, `--bg-page:#12161a`) — masih berasa kayak WhatsApp dark mode. Fakrul nanya soal pakai "ijo army + item kaya logo".

**Koreksi dari manager-session (penting, jangan asal ikutin literal "army green"):** manager-session extract warna asli langsung dari pixel `public/images/logo-jbtb.jpg` (bukan nebak) — hasilnya logo BUKAN army/olive green (army green itu hijau pudar kearah kuning-lumut, kesan taktis/militer). Warna asli logo adalah **hijau solid cukup jenuh, kode `#0f9a4c`–`#009342`** (kelly/emerald green) di atas latar **nyaris hitam netral `#1f1f1f`** (abu-abu gelap, BUKAN hitam kebiruan seperti `--bg-page` sekarang). Army green malah bakal keliatan beda sama logo asli & lebih ke arah militer/taktis, bukan look talent-agency/creative yang pas buat JBTB. Jadi arahnya: **"hijau logo asli + gelap netral"**, bukan army green — dipertahankan sesuai brand asli, ini juga otomatis bikin beda dari WhatsApp (WA pakai hijau teal `#25D366` di atas gelap kebiruan `#0b141a`/`#005c4b`; kita pakai hijau solid tanpa unsur teal, di atas gelap abu-abu netral tanpa unsur biru).

**Token dark mode baru (ganti nilai di `resources/views/partials/theme-style.blade.php`, blok `:root[data-theme="dark"]`):**
- `--bg-page: #17181a` (gelap netral, senada sama background asli logo `#1f1f1f`, sedikit lebih dalam biar kontras enak buat teks)
- `--bg-sidebar: #101112`
- `--bg-card: #1c1e1e`
- `--bg-card-hover: #242625`
- `--bg-nav-active: #17251d`
- `--accent: #0f9a4c` (hijau asli dari logo, hasil ekstraksi pixel, bukan hijau Tailwind generik)
- `--accent-strong: #22b862` (versi lebih terang buat hover/highlight)
- `--accent-on: #04140a` (**PENTING:** teks di atas `--accent` HARUS pakai warna gelap ini, BUKAN putih — sudah dicek kontras WCAG: putih di atas `#0f9a4c` cuma dapat rasio ~3.65:1, gagal AA buat teks normal; `#04140a` di atas `#0f9a4c` dapat ~5.18:1, lolos AA. Ini juga sekalian konsisten sama logo asli yang teksnya gelap/hitam di atas hijau, bukan putih.)
- Border/danger/warning: TIDAK berubah dari Bagian I, cuma page/sidebar/card/accent yang di-refresh.
- **Light mode: TIDAK berubah** — ini murni fix buat dark mode, jangan sentuh token light.

**Kenapa nggak army green:** kalau Fakrul abis liat hasil ini masih pengen coba nuansa army/olive/taktis beneran (bukan ngikutin logo), itu keputusan terpisah yang sengaja menyimpang dari brand asli — kasih tau manager-session dulu sebelum Claude Code ubah, karena itu bukan sekadar "refresh warna", tapi ganti identitas visual dari yang udah dipatok logo.

## Bagian K.7: Hero split-image — foto asli casting/film, terinspirasi template teatrikal

**Konteks:** Fakrul upload contoh template PSD Freepik ("theatre-school-template-landing-page", `4759906.psd`) yang temanya perfilman/teatrikal — hero split-screen (setengah foto orang pegang clapperboard dengan tone merah gelap, setengah panel gelap solid + headline + CTA + menu vertikal + garis aksen tipis), minta homepage JBTB dibikin senuansa itu, ga polos, pakai foto asli terkait casting/film.

**PENTING — apa yang DIADAPTASI vs TIDAK dari template itu:**
- **Diadaptasi (pola/struktur, generik, aman dipakai):** layout hero split-screen (foto di satu sisi, panel gelap solid + headline + CTA di sisi lain), garis aksen tipis horizontal sebagai divider, foto di-treatment warna (duotone/overlay) supaya nyatu sama palet brand, panel gelap dikasih texture pattern halus (sudah ada, dari Bagian I).
- **JANGAN disalin:** foto bawaan template itu sendiri (ada wajah anak kecil, model dari template Freepik lain, TIDAK RELEVAN dan TIDAK layak dipakai sebagai identitas JBTB), copy teks aslinya ("Sign up for the theater workshop", dsb — ganti dengan copy JBTB sendiri), wordmark "Ts.", menu vertikal berputar teks (cukup pakai menu navbar horizontal yang sudah didesain di Bagian K.5, jangan nambah pola baru). File PSD/JPG upload itu HANYA referensi visual, TIDAK diekstrak asetnya ke project — itu produk berlisensi Freepik (lihat `License free.txt`/`License premium.txt` di dalam zip-nya) yang nggak match sama brand JBTB sendiri.

**Foto pengganti — sudah dicari manager-session, free untuk komersial (lisensi Unsplash: bebas pakai, atribusi diapresiasi tapi TIDAK wajib secara hukum), relevan tema casting/produksi film:**
1. **Hero (split-image utama):** `https://images.unsplash.com/photo-1485846234645-a62644f84728` (orang pegang clapperboard/clapboard, luar ruangan, tone hangat) — fotografer Jakob Owens.
2. **Section sekunder (opsional, misal di atas footer atau di "Tentang Kami"):** `https://images.unsplash.com/photo-1612544409025-e1f6a56c1152` (kru film di soundstage dengan lighting rig, tone gelap/moody, sudah natural cocok buat dark mode) — fotografer brandsandpeople.

**Cara pakai:**
1. Download kedua file (`curl -L "URL?w=1600&q=80&fm=jpg&fit=crop" -o public/images/hero-clapboard.jpg`, sesuaikan param `w`/`q` biar file nggak kegedean, target ~150-300KB) — jangan hotlink langsung ke `images.unsplash.com` dari production (lambat/nggak reliable jangka panjang), simpan lokal di `public/images/`.
2. **Hero jadi split-screen:** sisi foto (pakai `hero-clapboard.jpg`, `background-size:cover`) dengan overlay gradient warna `--accent`/`--bg-page` (pola `linear-gradient` native CSS, bukan gambar overlay terpisah) supaya foto nyatu ke palet hijau-gelap JBTB (bukan overlay merah kayak template asli) DAN supaya kalau nanti ada teks di atasnya tetap kebaca; sisi lain tetap panel gelap solid (`--bg-sidebar`/`--bg-page`) isinya headline + tagline + CTA yang SUDAH ada sekarang (`.hero h1`, `.tagline`, `.cta-row`) — jangan ganti copy-nya, cuma layout-nya jadi split, bukan center-text penuh.
3. Garis aksen tipis (`border-top`/`border-bottom` 2px warna `--accent`) boleh ditambah sebagai divider antara nav dan hero, atau antara hero dan stats-section — pure CSS, jangan bikin elemen `<hr>` baru kalau bisa numpang di border section yang sudah ada.
4. Foto kedua (soundstage) OPSIONAL — kalau mau dipakai, taruh sebagai full-width background band tipis (misal di atas footer, "Siap gabung produksi berikutnya?" + CTA) dengan overlay gelap yang sama. Kalau dirasa homepage udah cukup panjang/berat, SKIP saja foto kedua ini, foto pertama di hero sudah cukup buat "nggak polos".
5. Kredit fotografer taruh kecil di footer (opsional, bukan wajib secara lisensi, tapi sopan): `Foto: Jakob Owens, brandsandpeople (Unsplash)`.

**Batasan ukuran/performa:** ini foto pertama yang benar-benar dipasang di homepage (sebelumnya cuma pattern SVG) — compress secukupnya (JPEG q=75-80, lebar maks ~1600px buat hero), jangan pasang file asli beresolusi penuh Unsplash (bisa >2MB), itu bakal bikin homepage lambat di mobile.

## Bagian K.8: Pindahin "Cara Kerja buat Calon Extras" dari homepage ke dashboard Extras

**Konteks:** instruksi Fakrul — section step-bar "Cara Kerja buat Calon Extras" di homepage (`.how-section` di `welcome.blade.php`) di-drop dari homepage publik, dipindah ke dashboard Extras yang sudah login (`resources/views/extras/dashboard.blade.php`).

**Kenapa masuk akal:** step-bar ini isinya "Daftar akun → Lengkapi profil → Apply proyek → Seleksi → TTD kontrak → Kerja & dibayar" — lebih berguna buat Extras yang UDAH login dan lagi jalanin proses itu (orientasi "gua sekarang di tahap mana"), daripada buat visitor anonim di homepage yang belum tentu daftar.

**Implementasi:**
1. Hapus `.how-section` beserta isinya dari `welcome.blade.php` (dan CSS terkait `.how-section`/`.step-bar*` kalau memang cuma dipakai di situ — cek dulu apa ada dipakai di tempat lain sebelum hapus CSS-nya).
2. Tambahkan section yang sama (step-bar, boleh reuse HTML/CSS persis) di `extras/dashboard.blade.php`. **Penempatan yang disarankan:** tampilkan di posisi `@empty` yang sekarang cuma nulis "Belum ada pendaftaran." — jadi extras yang belum pernah apply lihat step-bar orientasi di situ; extras yang sudah punya pendaftaran aktif nggak perlu lihat step-bar lagi tiap buka dashboard (progress mereka sendiri sudah kelihatan lewat `partials.application-progress` per kartu pendaftaran). Kalau mau selalu tampil di atas (bukan cuma pas kosong), itu juga boleh — implementer pilih salah satu, dokumentasikan alasannya di komentar Blade singkat.

## Bagian K.9: Beresin tombol Daftar/Masuk yang kebanyakan (4x jadi 2x) + animasi close popup

**Konteks:** Fakrul hitung sendiri tombol Daftar/Masuk muncul sampai 4 kali di homepage: (1) navbar, (2) `.cta-row` di dalam `.hero`, (3) blok CTA sebelum footer (`@guest ... <div style="padding: 0 32px 48px...">`), (4) welcome modal popup. Kebanyakan, bikin homepage berasa maksa.

**Fix:**
1. **Hapus** CTA row di dalam `.hero` (`@guest <div class="cta-row">...Daftar Jadi Extras / Masuk ke Sistem</div>@endguest`) — SISA cukup headline+tagline aja di hero, TANPA tombol (foto split-image dari Bagian K.7 sudah cukup jadi visual anchor di hero).
2. **Hapus** blok CTA sebelum footer (`@guest <div style="padding: 0 32px 48px...">...Daftar Akun Extras / Masuk ke Sistem</div>@endguest`) — dihapus total, bukan disembunyikan.
3. **SISAKAN cuma 2 tempat:** navbar (`Masuk`/`Daftar` di `.hp-nav-actions`, sudah ada, JANGAN diubah) dan welcome modal popup (sudah ada, JANGAN diubah kontennya). CTA di kartu "Lowongan Terbuka" (K.3, "Daftar untuk Apply" per kartu proyek) itu BEDA KONTEKS (bukan CTA umum, tapi CTA spesifik per lowongan) — itu TETAP ADA, tidak termasuk hitungan yang mau dikurangi.
4. **Animasi close popup mengarah ke navbar:** sekarang di `welcome-modal` pas ditutup cuma fade+turun dikit (`transform: translateY(8px)`, generic, nggak ngarah ke mana-mana). Ganti jadi animasi yang keliatan "ketarik ke atas ke arah navbar" — biar user connect "oh iya, Masuk/Daftar ada di pojok atas situ". Caranya: tambah class `.is-closing` sebelum manggil `dlg.close()` (bukan langsung hapus `.is-open`), dengan CSS `dialog.is-closing { opacity: 0; transform: translateY(-140px) scale(0.75); transition: opacity .25s ease, transform .25s ease; }` (native CSS transition, JANGAN pakai library animasi baru). Update JS `dismiss()`:
   ```js
   function dismiss() {
       localStorage.setItem('homepage_modal_dismissed', '1');
       dlg.classList.remove('is-open');
       dlg.classList.add('is-closing');
       setTimeout(function () { dlg.close(); }, 250);
   }
   ```
   Sesuaikan durasi `setTimeout` supaya pas sama durasi transition CSS-nya (250ms, jangan beda dari nilai di CSS).

## Bagian K.10: Palet monokrom + 1 aksen — terinspirasi agiveteam.co

**Konteks:** Fakrul suka arah warna dark di agiveteam.co. Manager-session cek langsung computed style situs itu (bukan cuma nebak dari mata) — hasilnya TERNYATA seluruh palet mereka itu grayscale murni (background nyaris hitam netral, teks putih/abu-abu, NOL elemen berwarna sama sekali di UI — semua warna cuma datang dari FOTO, bukan dari elemen desain). Fakrul juga tanya "klo kita ambil tpi ttp pertahanin ijo nya oke ga?" — **jawabannya: OKE BANGET, malah ini justru cara yang paling bener buat niru gaya itu tanpa kehilangan identitas hijau JBTB.**

**Prinsipnya:** UI JBTB tetap netral/grayscale (token dark mode dari Bagian K.6: `--bg-page`, `--bg-card`, `--text-primary/secondary`, dst — semua sudah netral, nggak berubah), dan `--accent` (hijau logo asli) dipakai SANGAT SELEKTIF — cuma buat 1 elemen yang butuh perhatian per section (misal: 1 tombol utama, 1 badge status "Dibuka", 1 garis aksen divider). **JANGAN taruh hijau di semua tempat sekaligus** (border card, background section, teks biasa, dll) — itu yang bikin kesan "terlalu ramai/norak". Foto-foto dari Bagian K.7 yang bawa warna & tekstur visual, bukan elemen UI-nya.

**Cek ulang (audit cepat) elemen-elemen yang sudah ada di homepage** — kalau ada yang pakai `--accent` secara berlebihan/dekoratif (bukan buat elemen actionable/status), turunkan ke `--text-secondary`/`--border` biasa. Prioritaskan `--accent` cuma buat: tombol CTA utama, badge status "Dibuka", link aktif di navbar (kalau ada state aktif), garis divider tipis dari Bagian K.7.

## Bagian K.11: Rapihin section Visi & Misi — terinspirasi widescreen.id

**Konteks:** Fakrul sebut section "Our Vision."/"Our Mission." di widescreen.id rapi banget, minta diadopsi. Manager-session cek langsung strukturnya: masing-masing Visi dan Misi dipisah jadi blok sendiri-sendiri dengan heading besar + 1 paragraf padat (bukan list bernomor panjang), whitespace lega antar blok, tanpa dekorasi berlebihan — kesan "editorial", bukan tabel/list biasa.

**Adaptasi ke `.vm-section` (sudah ada di Bagian K.2):**
- Visi: heading besar (boleh naikin dari 14px sekarang ke ~18-20px) + paragraf, tetap 1 kalimat sesuai kutipan resmi proposal (JANGAN diedit isinya, cuma styling).
- Misi: daripada `<ol>` numbered list yang sekarang, coba format jadi 4 baris pendek dengan sedikit spasi antar poin (masih boleh ada angka/bullet kecil, tapi kasih line-height lebih lega, ukuran font sedikit dinaikkan) — intinya kesan "napas" antar poin, bukan padat mepet kayak sekarang.
- Tetap 2-kolom di desktop (Visi kiri, Misi kanan — sudah bener sekarang), stack di mobile (sudah ada breakpoint-nya).
- Ini murni CSS/typography tweak, TIDAK ada perubahan struktur HTML besar, TIDAK ada dependency baru.

## Bagian K.12: Section "Produksi yang Pernah Kami Tangani" — poster placeholder, siap diisi poster asli nanti

**Konteks:** terinspirasi section "Works." di widescreen.id (grid poster film). Fakrul konfirmasi JBTB punya poster asli dari produksi yang client-nya pernah pakai jasa JBTB, tapi belum di-upload/tersedia sekarang — jadi section ini dibangun SEKARANG dengan placeholder, siap diisi poster asli belakangan lewat Admin (bukan hardcode gambar dummy permanen).

**Data & migration:**
- Tambah kolom nullable `poster_path` (string) ke tabel `casting_projects` (migration baru, jangan modif migration lama).
- `CastingProject::$fillable` tambah `poster_path`.
- Admin form create/edit proyek (`admin/projects/create.blade.php`/`edit.blade.php`): tambah field upload opsional "Poster/Cover Produksi (opsional — bisa diisi nanti)", `<input type="file" accept="image/*">`, simpan ke disk PUBLIC (`Storage::disk('public')`, BUKAN private disk yang dipakai foto profil Extras — poster ini memang buat konsumsi publik/marketing, beda konteks sama data privasi Extras). Validasi tipe file gambar + ukuran maks wajar (misal 2MB).

**Section homepage:**
- Query `CastingProject::where('status', 'ditutup')->latest()->take(8)->get()` (proyek yang sudah selesai/ditutup — pakai `nama_produksi`, field yang SUDAH aman publik, sama seperti di Lowongan Terbuka). **`client_ph` TETAP TIDAK BOLEH ikut ke view ini** (pakai `select()`/`with()` yang membatasi kolom, sama seperti section K.3).
- Render grid kartu bergaya poster (rasio potret, mirip poster film — misal `aspect-ratio: 2/3`):
  - Kalau `poster_path` ADA: tampilkan gambar aslinya (`object-fit: cover`).
  - Kalau `poster_path` KOSONG (placeholder): tampilkan kartu bergaya poster generik — background gradient netral (pakai token warna yang ada, BUKAN warna baru), ikon film (`ti ti-movie` atau `ti ti-clapperboard`) di tengah, `nama_produksi` sebagai judul di bawah ikon. Placeholder ini HARUS terlihat jelas sebagai placeholder (bukan berpura-pura jadi poster asli), tapi tetap rapi, bukan kotak abu-abu kosong.
- **Empty state:** kalau belum ada proyek berstatus `ditutup` sama sekali, section ini disembunyikan total (jangan tampilkan section kosong ke publik) — beda dari section Lowongan Terbuka yang punya empty-state message, karena ini section "portofolio", nggak relevan ditampilkan kalau belum ada portofolio.
- Cap tampilan (misal maks 8 kartu, terbaru dulu) — kalau lebih, TIDAK perlu link "lihat semua" (beda dari K.3), cukup batasi query-nya di 8.

## Bagian K.13: Section "Talent Kami" — placeholder ilustrasi, BUKAN foto/nama extras asli

**Konteks:** terinspirasi "Meet Our Stars." di widescreen.id, TAPI (sudah didiskusikan & dikonfirmasi Fakrul) TIDAK boleh pakai foto+nama extras asli tanpa mekanisme consent — itu beda konteks sama widescreen.id yang talent-nya representasi resmi berkontrak. Fakrul minta versi placeholder pakai ilustrasi/kartun figure, bukan foto real.

**Implementasi (murni dekoratif, TIDAK query data Extras individual sama sekali):**
- Grid 4-6 kartu kategori talent generik, pakai kategori yang SUDAH ada konsepnya di aplikasi (selaras `nama_kelas`/Nama Peran yang lazim dipakai: misal "Ibu-ibu", "Bapak-bapak", "Remaja", "Anak-anak", "Dewasa" — implementer boleh sesuaikan daftar, yang penting generik/kategori, BUKAN nama orang).
- Tiap kartu: ilustrasi figure kartun sederhana (SVG inline flat-color, cukup bentuk siluet orang dengan variasi warna dari palet yang ada — TIDAK perlu asset/library ilustrasi baru, gambar tangan sendiri pakai `<svg>` shape dasar: lingkaran buat kepala + bentuk badan simpel, ini styling decorative doang) + label kategori di bawahnya.
- Copy section boleh menyinggung "talent yang sering diminta klien dari berbagai kategori" secara GENERIK (tanpa menyebut/menampilkan individu spesifik manapun).
- **TIDAK ADA query ke tabel `extras_profiles`/`users` untuk section ini** — full static/generic, supaya nggak ada risiko privasi sama sekali.

**Catatan buat Fakrul (follow-up terpisah, TIDAK dikerjakan sekarang):** kalau nanti mau nampilin talent spesifik asli (misal "aktor favorit klien" yang disebut Fakrul) dengan foto+nama, itu butuh fitur consent opt-in baru dulu (checkbox di profil Extras: "saya setuju foto & alias saya ditampilkan di homepage publik") sebelum boleh ditampilkan — TIDAK dibangun di Bagian K ini, perlu SPEC terpisah kalau Fakrul mau lanjutkan ke situ.

## Verifikasi

- Full regression `php artisan test` — laporkan angka riil sebelum & sesudah.
- Test baru: homepage menampilkan proyek yang `menerimaPendaftaran()` true, TIDAK menampilkan proyek yang sudah lewat deadline/kuota penuh/status bukan dibuka. Test `assertDontSee` untuk memastikan `client_ph`/`budget_client` tidak ada di response homepage sama sekali (termasuk kalau ada proyek yang lagi buka).
- Manual: cek logo tampil dengan benar di navbar (tidak pecah/gepeng), cek quick facts + visi-misi ke-render rapi di mobile/desktop (2 blok aja, section "Kenapa Pakai Sistem Ini?" sudah hilang), cek kartu lowongan responsive, cek menu navbar anchor-scroll jalan + collapse rapi di mobile, cek lock icon cuma muncul buat guest, cek dark mode baru: bg netral gelap (bukan kebiruan), accent hijau logo asli, teks di atas tombol accent gelap (bukan putih) dan kontrasnya jelas kebaca.
- Manual tambahan (Bagian K.7): cek hero split-image tampil proporsional di mobile (foto jangan hilang total atau jadi terlalu kecil — kalau layar sempit boleh foto naik ke atas, panel teks di bawah, stack vertikal), ukuran file foto di bawah ~300KB, foto kebaca jelas nggak ke-cover overlay gelap berlebihan, teks headline tetap kontras di atas panel gelap.
- Manual tambahan (K.8-K.13): cek step-bar "Cara Kerja" udah bener-bener hilang dari homepage dan muncul di dashboard Extras; cek cuma ada 2 titik Daftar/Masuk umum di homepage (navbar + popup, CTA per-kartu Lowongan tidak dihitung); cek animasi close popup keliatan ngarah ke atas/navbar; cek pemakaian `--accent` udah nggak berlebihan (audit visual singkat); cek section poster placeholder nggak nongol kalau belum ada proyek `ditutup`, dan `client_ph` tetap nggak ke-leak (test `assertDontSee` baru); cek section "Talent Kami" nggak ada query ke data Extras individual sama sekali (grep controller/view-nya).
- **Tambahkan entry `DEV-NOTES.md` untuk Bagian F-J yang masih kosong DULU sebelum entry Bagian K** (lihat catatan di bagian atas) — supaya urutan sesi tetap kronologis dan akurat.

---

# Bagian L: Karakter/Kriteria, Modul Jadwal, Grade CD, Kategori Extras, Konsolidasi Menu CD

> Ditulis 17 September 2026, oleh manager-session, setelah diskusi & konfirmasi Fakrul (bukan cuma restyle kayak Bagian K — ini core-domain: workflow, RBAC, migration baru). **WAJIB pakai subagent per `CLAUDE.md` §"Cara Kerja Coding"** (lintas >3 file + menyentuh RBAC/workflow inti). **SANGAT DISARANKAN dipecah jadi beberapa sesi kerja/komit** (misal: L.1-L.2 dulu, lalu L.3, lalu L.5-L.6, lalu L.8-L.9) — JANGAN digabung jadi satu commit raksasa, biar kalau ada regresi gampang di-bisect. Update `DEV-NOTES.md` di akhir TIAP sub-bagian, bukan cuma di akhir semua.

## Konteks

Fakrul kirim contoh call sheet asli (WA + PDF lengkap produksi film). Dari situ + diskusi, ada beberapa gap besar di sistem: (1) field "kriteria" karakter yang di-drop di Bagian G ternyata dibutuhkan lagi, (2) belum ada mekanisme "link grup koordinasi" yang kebuka otomatis begitu kandidat lolos+kontrak, (3) belum ada modul jadwal/rundown shooting sama sekali (cuma ada tanggal shooting buat deteksi bentrok, `event_shooting_dates`, tanpa detail lokasi/jam/catatan), (4) grade kandidat sekarang cuma dari Admin — CD (yang harusnya validator final) belum punya grade sendiri, (5) belum ada kategori/tag buat Extras (anak/dewasa/dll) buat filtering rekap, (6) menu CD "Greenlight" dan "Riwayat" kepisah padahal isinya sama-sama "kandidat yang diajukan Admin", cuma beda status.

**Keputusan default yang manager-session ambil (Fakrul belum spesifikasi eksplisit, didokumentasikan di sini biar bisa dikoreksi):**
- Kategori Extras: **many-to-many** (1 extras bisa punya lebih dari 1 kategori sekaligus, misal "Anak" + "Chinese" bareng) — karena kombinasi kategori realistis muncul di kebutuhan casting beneran.
- Nama menu gabungan CD: tetap **"Greenlight"** (label yang sudah established), bukan bikin nama baru — link "Riwayat" di sidebar dihapus, semua fungsinya pindah ke Greenlight.
- Modul Jadwal versi **ringkas** (rundown per hari: lokasi, jam, panggilan per karakter/kategori, catatan) — BUKAN scene-by-scene breakdown lengkap kayak PDF contoh (itu scope manajemen produksi penuh, di luar peran JBTB sebagai vendor casting/extras).

## Bagian L.1: Karakter & Kriteria

1. **Rename tampilan** "Nama Peran"/"Peran yang Dicari" (hasil Bagian G) jadi **"Karakter"**/"Karakter yang Dibutuhkan" di semua tempat yang render label ini (`admin/projects/create.blade.php`, `edit.blade.php`, dan tempat lain yang nampilin `nama_kelas` sebagai label ke user — CD, Extras). **Field/kolom DB tetap `nama_kelas`, JANGAN rename kolom** (cukup ubah label tampilan) — hindari migration yang gak perlu.
2. **Hidupkan lagi field `kriteria`:** kolom `kriteria` di tabel `casting_project_classes` MASIH ADA di DB (sengaja nggak di-drop di Bagian G), cuma dihapus dari `$fillable`/`casts()` di model. Balikin ke `CastingProjectClass`: `$fillable` tambah `kriteria`, `casts()` tambah `'kriteria' => 'array'` (atau `string` kalau mau simpel free-text — pilih salah satu, free-text lebih cocok buat deskripsi kebutuhan kayak "wanita, 25-35th, ekspresi sedih", JANGAN dipaksa jadi array terstruktur kalau kontennya emang narasi bebas).
3. Tambah input **textarea** "Kriteria yang dibutuhkan (opsional)" di form create/edit proyek, per baris Karakter.
4. Tampilkan `kriteria` ini ke CD di menu Greenlight (Bagian L.8) — CD perlu lihat kriteria pas mutusin grade/approve, bukan cuma nama karakter doang.

## Bagian L.2: Link Grup Koordinasi (1 per proyek, terbuka pas lock + kontrak)

1. Migration baru: tambah kolom nullable `link_grup` (string) ke `casting_projects`.
2. `CastingProject::$fillable` tambah `link_grup`.
3. Admin form create/edit proyek: field "Link Grup Koordinasi (WA/Telegram, opsional — bisa diisi belakangan)".
4. **Kondisi tampil ke Extras:** link muncul di halaman kontrak (`contracts.show`) dan/atau dashboard Extras HANYA kalau `$application->status_partisipasi` sudah `kontrak_ditandatangani` atau `selesai_produksi` (pakai `ProjectApplication::STATUS_LOLOS_KE_ATAS` yang sudah ada, tapi filter yang sudah TTD kontrak — cek constant itu, kalau perlu buat constant baru `STATUS_KONTRAK_KE_ATAS = ['kontrak_ditandatangani', 'selesai_produksi']`). **JANGAN tampilkan link cuma dari status `lolos`** — harus SETELAH kontrak TTD juga, sesuai instruksi Fakrul ("berbarengan dengan TTD kontrak").
5. CD juga bisa lihat `link_grup` proyek yang dia pegang (di menu Greenlight, Bagian L.8) — CD butuh gabung ke grup yang sama buat koordinasi.
6. Kalau `link_grup` kosong (belum diisi Admin), jangan tampilkan section link-nya sama sekali (bukan link kosong/rusak).

## Bagian L.3: Modul Jadwal (ringkas) — diinput CD, dibaca semua dashboard

**Perluas tabel yang SUDAH ADA** (`event_shooting_dates` — jangan bikin tabel tanggal baru):
1. Migration baru (alter table): tambah kolom nullable `lokasi` (string), `jam_mulai` (time), `jam_selesai` (time), `catatan` (text), `panggilan` (json — array bebas berisi entri seperti `[{"nama": "Ara (Faris)", "jam": "06:30"}, {"nama": "Perawat Klinik (kategori, 2 orang)", "jam": "18:30"}]`). **`panggilan` sengaja JSON bebas, BUKAN relasi ketat ke `casting_project_classes`** — dari contoh call sheet asli, satu hari bisa campur nama karakter spesifik DAN kategori+kuota extras generik dalam satu daftar call time, maksa jadi 1 bentuk relasional kaku cuma bikin over-engineered buat versi "ringkas" ini.
2. Cek dulu apakah sudah ada Model `EventShootingDate` (kemungkinan cuma diakses lewat relasi Eloquent tanpa file model eksplisit) — kalau belum ada, buat modelnya, `$fillable` sesuai kolom di atas + `casting_project_id`, `tanggal`.
3. **Menu baru "Jadwal" di sidebar CD** (`cd/jadwal`), scoped ke proyek yang CD itu di-assign (`cdAssignments`). CD bisa: pilih proyek → tambah/edit entry per tanggal (lokasi, jam mulai/selesai, catatan, daftar panggilan — pakai pola dynamic add-row JS yang SUDAH ada di form Karakter, jangan bikin pola baru).
4. **Visibilitas baca (read-only):**
   - **Admin:** lihat jadwal di halaman detail/Kelola Proyek (Bagian L.4).
   - **SuperAdmin:** lihat semua jadwal semua proyek, read-only, taruh di menu Monitoring yang sudah ada atau menu baru kecil "Jadwal" read-only — implementer pilih, dokumentasikan.
   - **Extras:** lihat jadwal proyek yang dia ikuti (status aplikasi aktif) di dashboard-nya — tampilkan APA ADANYA (satu hari itu, lokasi+jam+catatan+daftar panggilan), TIDAK perlu filter "yang relevan ke dia doang" buat versi pertama ini (over-engineering kalau dipaksa sekarang, dan konten `panggilan` toh JSON bebas tanpa data pribadi/kontak Extras lain yang sensitif — cuma nama karakter/kategori+jam).
5. **Reminder terkait jadwal** (Artisan command baru, ikutin pola `ReminderH1ShootingCommand` yang SUDAH ADA — pelajari dulu code-nya sebelum bikin yang baru):
   - `reminder:h3-pilih-extras` — jalan harian, cek proyek dengan `event_shooting_dates.tanggal` = H+3, DAN masih ada `project_applications` berstatus `diajukan_ke_cd` (belum direview CD) di proyek itu → kirim WA ke CD yang di-assign. **Kalau H-1 masih ada yang pending juga**, kirim lagi (boleh reuse command yang sama dengan cek tambahan `now()->addDay()`, atau bikin logic di 1 command yang cek H+3 DAN H+1 sekaligus — implementer pilih, yang penting jangan dobel-kirim di hari yang sama).
   - `reminder:input-jadwal` — jalan harian, cek proyek dengan shooting date H+3 yang barisnya di `event_shooting_dates` masih kosong `lokasi`/`panggilan` → kirim WA ke CD yang di-assign, minta lengkapi jadwal.
   - Daftarkan keduanya di `routes/console.php` pakai `Schedule::command(...)->dailyAt(...)`, ikutin jam yang sudah ada (08:00 WIB) atau jam lain yang masuk akal — jangan numpuk semua reminder di menit yang sama persis.

## Bagian L.4: Rename menu "Callsheet" (Admin) → "Kelola Proyek"

Ganti label tampilan doang (sidebar Admin, judul halaman `admin/projects/index.blade.php`) dari "Callsheet" balik jadi **"Kelola Proyek"** — route name/URL TIDAK perlu diubah (biar gak ada breaking change ke link/bookmark/test yang udah ada), cukup teks yang user lihat. Ini membebaskan istilah "Jadwal" (Bagian L.3) sebagai satu-satunya makna "callsheet" yang sebenarnya di sistem ini.

## Bagian L.5: Grade CD (validator final, terpisah dari rekomendasi Admin)

1. Migration baru: tambah kolom nullable `grade_cd` (enum `A,B,C`) ke tabel `cd_reviews`.
2. `CdReview::$fillable` tambah `grade_cd`.
3. `ReviewController::review()` — request validation tambah `'grade_cd' => ['required_if:keputusan,approve', 'in:A,B,C']` (grade cuma wajib kalau approve, gak relevan buat reject), simpan ke `cdReviews()->create([...])` bareng `keputusan`.
4. UI Greenlight (Bagian L.8): tambah pilihan grade (A/B/C) di form approve — muncul bareng tombol approve, bukan step terpisah, sesuai instruksi Fakrul ("nentuin grade pas nge-lock extras").
5. **Grade Admin (`project_applications.grade`) TIDAK dihapus** — cuma di-relabel jadi **"Rekomendasi Grade (Admin)"** di UI Lineup Admin (`applicants.blade.php`), dan ditampilkan sebagai referensi read-only di Greenlight CD (biar CD lihat rekomendasi Admin sebelum nentuin grade final sendiri).

## Bagian L.6: Kategori Extras (anak/dewasa/orangtua/chinese/custom)

1. Migration baru: tabel `extras_categories` (`id`, `nama` unique, timestamps) — daftar kategori yang bisa ditambah Admin.
2. Migration baru: tabel pivot `extras_category_extras_profile` (`extras_profile_id`, `extras_category_id`) — many-to-many.
3. Seeder: isi kategori default (`Anak-anak`, `Remaja`, `Dewasa`, `Orang Tua`, `Chinese/Tionghoa`) — Admin bisa nambah kategori baru sendiri lewat UI kecil (form tambah kategori, taruh di halaman Kelola Akun atau halaman kecil terpisah "Kategori Extras").
4. Model `ExtrasCategory` (fillable `nama`) + relasi many-to-many di `ExtrasProfile` (`categories()`).
5. **Admin yang assign kategori** ke tiap Extras (dikonfirmasi Fakrul) — di halaman Kelola Akun (`admin/users/index.blade.php`) atau modal "Ubah Kategori" per baris Extras, checkbox multi-select dari daftar `extras_categories`.
6. Tambahkan kolom "Kategori" di Rekap Extras (Bagian L.7) dan di export Excel.

## Bagian L.7: Rekap Extras — filter lebih lengkap

Audit dulu `RecapController` yang SUDAH ADA (jangan bikin ulang dari nol) — tambahkan filter berdasarkan kategori Extras (Bagian L.6) ke query yang sudah ada, plus filter lain yang make sense dari kolom yang sudah tersedia (status aktif/nonaktif user, dll — implementer cek kolom apa yang berguna buat difilter, dokumentasikan pilihannya). Filter pakai query string (`request()->query('kategori')`, dst), native, TIDAK perlu library filter baru.

## Bagian L.8: Konsolidasi menu CD — "Greenlight" jadi satu-satunya menu lihat kandidat (drill-down semua status)

**Konteks:** instruksi Fakrul eksplisit — jangan kepisah antara "Greenlight" (pending) dan "Riwayat" (sudah diputus), gabung jadi 1 menu.

1. **`ReviewController::index()`** diubah total: dari cuma nampilin `status_partisipasi = diajukan_ke_cd`, jadi **drill-down Level 1 per proyek** (reuse struktur `riwayat()` yang sudah ada) — per proyek yang CD di-assign, tampilkan breakdown jumlah: **Menunggu Review** (`diajukan_ke_cd`) + **Approved** (`lolos`/`kontrak_ditandatangani`/`selesai_produksi`) + **Rejected** (`ditolak`).
2. **Level 2** (klik 1 proyek): tabel SEMUA `project_applications` proyek itu dengan status `diajukan_ke_cd` ke atas (bukan cuma yang sudah ada `CdReview`-nya) — kolom status, kriteria karakter (Bagian L.1), rekomendasi grade Admin (Bagian L.5). Baris berstatus `diajukan_ke_cd`: tampilkan tombol Approve (+ pilih grade CD, Bagian L.5) / Reject. Baris yang sudah diputus: tampilkan keputusan + grade CD + tanggal, read-only (kayak Riwayat sekarang). Filter dropdown status (Semua/Menunggu/Approved/Rejected).
3. Export Excel/PDF (`CdRiwayatExport`, `riwayat-pdf`) — extend query-nya supaya bisa include baris yang masih pending juga (bukan cuma yang sudah ada `CdReview`), atau biarkan export cuma untuk yang sudah diputus dan dokumentasikan alasannya kalau itu pilihannya — implementer putuskan, yang penting KONSISTEN sama apa yang ditampilkan di Level 2.
4. **Hapus** route/menu `cd.riwayat` terpisah dari sidebar CD — semua fungsinya sudah pindah ke `cd.reviews.index` (Greenlight). Method `riwayat()`/`riwayatProyek()` di controller boleh di-merge ke `index()`/`show()` yang baru, atau tetap ada sebagai method terpisah yang dipanggil dari route yang sama — implementer pilih struktur kode, yang penting user-facing cuma ada 1 menu.
5. **PENTING — test yang SUDAH ADA akan kena dampak** (`CdRiwayatExport`, test drill-down Riwayat dari Bagian E, test Greenlight/`ReviewController::index` yang lama): WAJIB update test-test itu supaya sesuai struktur baru, JANGAN cuma nambah test baru dan biarin yang lama gagal/dihapus diam-diam. Laporkan di DEV-NOTES.md test mana yang diubah dan kenapa.

## Bagian L.9: Dashboard CD — tambahan reminder & ringkasan

Tambahkan ke `Cd\DashboardController::index()` (yang sekarang cuma "perlu direview" count + chart approve/reject):
1. **List pelunasan:** `Payment::whereIn('status', ['belum_dibayar', 'ditransfer'])` scoped ke proyek yang CD itu di-assign (lewat relasi `application.castingProject.cdAssignments`) — tampilkan ringkas (nama produksi, jumlah pending).
2. **Project berjalan:** proyek yang CD di-assign dengan `status = 'dibuka'`.
3. **List karakter yang dibutuhkan + jumlah pendaftar:** per proyek yang CD pegang, group `project_applications` by `casting_project_class_id`, hitung jumlah pendaftar per karakter (data ini SUDAH bisa diquery karena `casting_project_class_id` sudah ada di `project_applications` — TIDAK perlu skema baru).
4. Reminder H-3/H-1 (Bagian L.3) TIDAK perlu widget dashboard terpisah — itu dikirim via WA langsung ke CD, dashboard cukup nampilin ringkasan 1-3 di atas.

## Verifikasi Bagian L

- **Ini scope besar — jalankan `php artisan test` SETELAH TIAP sub-bagian selesai** (L.1-L.2, lalu L.3, dst), bukan cuma sekali di akhir. Laporkan angka riil tiap kali.
- Test baru minimal: `kriteria` tersimpan & tampil (L.1); `link_grup` cuma muncul saat status kontrak sudah TTD, TIDAK muncul saat status masih `lolos` doang (L.2); CRUD Jadwal oleh CD, scoped ke proyek yang di-assign aja — CD lain TIDAK bisa input jadwal proyek yang bukan miliknya (L.3); `grade_cd` wajib diisi saat approve, tersimpan benar (L.5); assign kategori Extras many-to-many berfungsi, filter Rekap by kategori menghasilkan data yang benar (L.6, L.7); Level 1 & Level 2 Greenlight baru menampilkan SEMUA status (bukan cuma yang sudah diputus), tombol approve/reject/grade cuma muncul di baris pending (L.8); dashboard CD menampilkan pelunasan/project berjalan/karakter+pendaftar dengan angka yang benar dan scoped ke CD yang login (L.9).
- **Tembok visibilitas (CLAUDE.md §5) tetap berlaku** di semua fitur baru ini — cek ulang: `link_grup`/`panggilan` jadwal TIDAK mengandung `nama_asli`/`nik`/`rate_card`/`rekening` Extras; kategori Extras bukan data sensitif tapi tetap TIDAK perlu diekspos ke publik (cuma internal Admin/CD).
- Update `DEV-NOTES.md` per sub-bagian selesai (bukan ditumpuk di akhir) — ini poin yang udah 2x jadi catatan proses di SPEC sebelumnya, tolong benar-benar dijalanin kali ini.

---

# Bagian M: Grid kartu foto buat kandidat CD + profil Extras ala widescreen.id/amara/

> Ditulis 17 September 2026. **KERJAKAN SETELAH Bagian L (khususnya L.5 grade_cd dan L.8 drill-down) SELESAI DAN TER-TEST** — Bagian M ini restyle di atas struktur yang dibangun L.8, kalau dikerjakan duluan/bareng bakal tabrakan. Cek `DEV-NOTES.md` dulu, pastikan L.5 & L.8 sudah ada entry-nya sebelum mulai M.

## Konteks

Fakrul kasih referensi 2 halaman widescreen.id: (1) grid "Meet Our Stars" (kartu foto talent, klik → halaman detail) buat tampilan kandidat yang diajukan ke CD; (2) halaman profil individual (`/amara/`) buat gaya halaman profil Extras, terutama section Gallery. Manager-session sudah cek langsung dua-duanya:
- Grid "Meet Our Stars": kartu foto + nama, klik kartu → halaman profil individual terpisah.
- Halaman `/amara/`: hero foto besar + nama, lalu info block ringkas (Gender/Age Range/Height/Location/Language — **ini persis sekelas sama field yang SUDAH diizinkan tembok visibilitas buat CD**: usia, gender, tinggi_badan, dst), lalu section "Gallery" isinya foto full-bleed berurutan, lalu paragraf riwayat karir. **Galerinya BUKAN click-to-zoom** — itu cuma hover-swap gambar. Jadi permintaan lu soal "onclick ngezoom" itu fitur TAMBAHAN dari lu sendiri, bukan niru yang ada di situ — dicatat di sini biar jelas asalnya, dan ini ide bagus, dilanjutkan.

## Bagian M.1: Kandidat CD — grid kartu foto (bukan tabel), klik → modal detail + putuskan

**Ganti tampilan Level 2 Greenlight** (hasil Bagian L.8 — daftar kandidat per proyek) dari tabel ke **grid kartu foto**, mirip pola "Meet Our Stars":
- Tiap kartu: foto profil kandidat (`foto_profil_path`, fallback ikon kalau kosong) sebagai gambar utama kartu, alias di bawahnya, badge kecil status (Menunggu/Approved/Rejected) di pojok kartu.
- **Klik kartu (bukan checkbox) → buka modal "Lihat Profil & Putuskan"** (native `<dialog>`, reuse pola yang SUDAH ADA di `riwayat-proyek.blade.php`, JANGAN bikin komponen modal baru dari nol). Isi modal:
  - Foto profil + galeri foto tambahan (Bagian M.3, sekalian dibikin zoomable di sini) + video kalau ada.
  - Atribut terbatas yang SUDAH diizinkan (usia, gender, tinggi_badan, ukuran_baju, warna_kulit, pengalaman, bahasa) — TIDAK ada field baru, TIDAK ada `nama_asli`/`nik`/`rate_card`/`rekening`.
  - Karakter yang dilamar + `kriteria` (Bagian L.1).
  - Rekomendasi Grade Admin (Bagian L.5) — read-only, label jelas "Rekomendasi Admin".
  - **Ringkasan riwayat SINGKAT dengan CD ini** (BUKAN riwayat lintas-CD lain — itu bocor info proyek CD lain, dilarang): hitung dari `cd_reviews` milik CD yang login, berapa kali kandidat ini pernah di-approve/reject OLEH CD YANG SAMA di proyek-proyek lain yang CD itu pegang. Contoh tampilan: "Pernah di-approve 2x, ditolak 0x sama kamu sebelumnya."
  - **Form keputusan DI DALAM modal** (bukan submit terpisah dari luar): kalau status masih `diajukan_ke_cd`, tampilkan pilihan Grade CD (A/B/C, wajib) + tombol Approve, dan tombol Reject terpisah (reject tidak butuh grade). Kalau status sudah diputus, tampilkan keputusan+grade+tanggal read-only, tanpa form.
- **Kenapa approve jadi per-kandidat (bukan bulk lagi):** karena grade CD (Bagian L.5) itu wajib diisi PER KANDIDAT saat approve — nggak masuk akal 1 grade buat banyak kandidat sekaligus dalam 1 aksi bulk. **Reject boleh tetap bulk** (checkbox di kartu + tombol "Reject Terpilih" di luar grid, reject nggak butuh grade) — pertahankan itu dari UI yang sekarang, jangan dihilangin, cuma approve yang wajib lewat modal per-kandidat.
- Filter status (Semua/Menunggu/Approved/Rejected) tetap ada di atas grid, sama seperti rencana L.8.

## Bagian M.2: Halaman profil Extras — restyle terinspirasi `/amara/`

**Terapkan ke halaman "Lihat Profil" Extras yang read-only** (yang dilihat Extras sendiri, DAN yang dilihat Admin/CD kalau di project mereka ada halaman serupa — cek dulu semua tempat yang render profil lengkap Extras sebelum mulai, biar konsisten):
1. Header: foto profil jadi elemen dominan (bukan avatar kecil), nama/alias besar di bawah atau overlay foto — sesuai proporsi yang wajar buat layout kita (JANGAN niru ukuran font raksasa ala widescreen.id yang emang gaya editorial mereka, sesuaikan skala normal aplikasi ini).
2. Info block ringkas di bawah header: field yang MEMANG milik pemilik profil itu sendiri boleh full (kalau Extras lihat profil sendiri, dia boleh lihat semua datanya sendiri termasuk yang dibatasi buat CD) — tembok visibilitas cuma berlaku pas Admin/CD yang lihat profil ORANG LAIN, BUKAN pas Extras lihat profilnya sendiri. Pastikan controller/view yang dipakai membedakan konteks ini dengan benar (jangan sampai gara-gara restyle malah kebalik: Extras lihat profil sendiri jadi dibatasin, atau CD lihat profil orang lain malah kebuka penuh).
3. **Section Galeri** (Bagian M.3).

## Bagian M.3: Galeri foto — klik buat zoom (lightbox), reusable di semua tempat

Ini FITUR BARU (bukan niru referensi, sudah dijelaskan di Konteks), tapi diminta eksplisit oleh Fakrul. Bikin SEKALI sebagai partial/komponen Blade yang reusable (misal `partials.foto-lightbox` atau serupa — cek dulu konvensi partial yang sudah ada di codebase ini, ikutin polanya), lalu pakai di SEMUA tempat yang nampilin galeri foto Extras:
- Halaman Lihat Profil Extras (M.2).
- Modal "Lihat Profil & Putuskan" CD (M.1).
- Halaman Lineup Admin (`applicants.blade.php`) yang juga nampilin foto tambahan.

**Mekanisme (native, TIDAK ada library lightbox baru):**
- Tiap thumbnail foto (`.thumb-photo-mini` dst) dikasih `onclick` yang buka `<dialog>` lightbox — isi `<dialog>` cuma 1 `<img>` besar (`max-width:90vw; max-height:90vh; object-fit:contain`) + tombol close.
- Kalau galerinya lebih dari 1 foto (kasus `extras_photos`, 4 slot): tambahkan tombol Prev/Next di dalam dialog buat geser antar foto tanpa nutup dialog dulu (native JS, ganti `src` gambar di dialog yang sama).
- Pastikan foto yang di-load di lightbox tetap lewat route yang sudah ada (`extras.media.foto`, `extras.media.foto-tambahan`) — JANGAN expose path storage langsung.

## Verifikasi Bagian M

- Jalankan SETELAH Bagian L beres & di-test — regresi test L.5/L.8 harus masih hijau setelah M diterapkan (restyle doang, logic approve/reject/grade jangan berubah).
- Manual: kartu kandidat CD nampilin foto+alias+status dengan benar; klik kartu buka modal, approve WAJIB isi grade dulu (test validasi), reject bulk masih jalan dari luar modal; profil Extras (lihat punya sendiri) masih nampilin semua data sendiri (nggak kebatasi keliru); lightbox foto bisa dibuka-tutup-geser di 3 tempat (Lihat Profil Extras, modal CD, Lineup Admin) dan foto yang tampil tetap lewat route media yang aman (bukan path storage bocor).
- Test baru: `assertDontSee` nama_asli/nik/dll tetap lolos di modal kandidat CD yang baru (regresi tembok visibilitas harus di-cek ulang karena tampilannya berubah total).
- Update `DEV-NOTES.md` setelah Bagian M selesai.

---

# Bagian N: Sentuhan cream beige di dark mode (partial-adopt moodboard Fakrul, TETAP pertahankan hijau logo asli)

> Ditulis 17 September 2026. Restyle warna kecil, TIDAK terkait/tergantung Bagian L atau M — boleh dikerjakan kapan saja, prioritas rendah, kerjakan setelah L & M selesai.

## Konteks

Fakrul share moodboard warna ("Dark Emerald Green" `#02110c`, "Olive Green" `#1a422f`, "Cream Beige" `#f3ebd6`) — ini template moodboard generik (dipakai contoh buat brand fashion/perhiasan/produk natural/kafe, bukan yang dirancang khusus buat casting agency). Manager-session evaluasi: dua hijau di situ (dark emerald & olive) BEDA dari hijau logo JBTB yang sudah diverifikasi langsung dari pixel logo asli (`#0f9a4c`, kelly/emerald jenuh — bukan olive pudar). Kalau full-adopt, bakal ada 2 "hijau" yang saling nggak konsisten di brand (logo vs UI). **Keputusan: JANGAN ganti `--accent` yang sudah ada, TAPI ambil "Cream Beige" (`#f3ebd6`) sebagai warna sekunder baru**, dipakai sangat selektif — konsisten sama prinsip monokrom+1-aksen dari Bagian K.10 (bukan nambah aksen ke-2 yang rame, tapi warna netral hangat pengganti putih polos di tempat-tempat tertentu).

## Implementasi

1. Tambah 1 token CSS baru di `resources/views/partials/theme-style.blade.php`, blok `:root[data-theme="dark"]`: `--highlight-cream: #f3ebd6;`. **TIDAK mengubah token yang sudah ada** (`--accent`, `--accent-strong`, `--bg-page`, dst dari Bagian K.6 tetap persis sama).
2. **Pakai `--highlight-cream` HANYA di 2-3 tempat yang butuh sentuhan "hangat/premium" tanpa jadi aksen actionable** (bukan tombol, bukan link, bukan badge status — itu tetap punya `--accent`):
   - Angka besar di `.stat-number` (stats section homepage) — ganti dari `--accent` jadi `--highlight-cream`, biar angka statistik kerasa "premium" tapi nggak berebut perhatian sama tombol CTA hijau.
   - Judul besar section editorial (misal heading "Visi"/"Misi" dari Bagian K.11, atau angka nomor urut kartu poster dari Bagian K.12) — opsional, implementer pilih 1-2 tempat yang paling pas, JANGAN taruh di semua heading (nanti malah jadi aksen ke-2 yang rame, ngelawan prinsip K.10).
3. **JANGAN dipakai buat:** background section manapun, border card, teks body/paragraf biasa (`--text-secondary` tetap dipakai buat itu) — cream ini aksen kecil pemanis, bukan warna dasar baru.
4. **Light mode TIDAK berubah sama sekali** — ini murni dark mode, sama seperti Bagian K.6.
5. "Dark Emerald" (`#02110c`) dan "Olive Green" (`#1a422f`) dari moodboard **TIDAK dipakai sama sekali** — `--bg-page` dkk tetap nilai dari Bagian K.6, sudah cukup akurat & dekat secara visual, ganti-ganti tanpa alasan kuat cuma nambah risiko regresi kontras yang udah diverifikasi WCAG di K.6.

## Verifikasi

- Manual: cek kontras `--highlight-cream` di atas `--bg-page`/`--bg-card` tetap gampang dibaca (cream di atas gelap harusnya kontrasnya tinggi, tapi tetap cek).
- Cek `--accent` (hijau logo) TIDAK berubah nilainya di mana pun setelah perubahan ini — grep cepat pastikan tidak ada tempat yang keliru ganti `--accent` jadi `--highlight-cream` atau sebaliknya.
- Update `DEV-NOTES.md`.

---

# Bagian O: Navbar homepage — avatar+submenu kalau login, 1 tombol kalau guest

## Konteks

Sekarang di `.hp-nav-actions` (`welcome.blade.php`): kalau `@guest` ada 2 tombol terpisah (Masuk, Daftar); kalau `@auth` ada 1 tombol teks "Dashboard". Fakrul minta: kalau sudah login, tombol itu diganti jadi **foto profil** (avatar), diklik baru muncul submenu isinya "Dashboard" dan "Keluar" — bukan langsung link ke dashboard. Kalau belum login, 2 tombol Masuk/Daftar digabung jadi **1 tombol aja**.

## Implementasi

1. **State guest:** ganti 2 tombol (`Masuk`, `Daftar`) jadi 1 tombol `<a href="{{ route('login') }}" class="btn-brand">Masuk / Daftar</a>` — halaman login yang sudah ada SUDAH punya link ke halaman Daftar di dalamnya (cek dulu, kalau belum ada, tambahkan link "Belum punya akun? Daftar" di situ), jadi 1 titik masuk ini tetap mencakup dua-duanya.
2. **State auth:** ganti tombol "Dashboard" jadi avatar + dropdown native (`<details><summary>`, TIDAK perlu JS/library baru buat dropdown-nya, `<details>` udah native browser support):
   ```html
   <details class="navbar-user-menu">
       <summary class="avatar-badge-summary">
           @if (auth()->user()->isExtras() && auth()->user()->extrasProfile?->foto_profil_path)
               <img src="{{ route('extras.media.foto', auth()->user()->extrasProfile) }}" class="avatar-badge avatar-badge-img" alt="Foto profil">
           @else
               <div class="avatar-badge">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
           @endif
       </summary>
       <div class="navbar-user-menu-dropdown">
           <a href="/dashboard"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
           <form method="POST" action="{{ route('logout') }}">
               @csrf
               <button type="submit"><i class="ti ti-logout"></i> Keluar</button>
           </form>
       </div>
   </details>
   ```
   **Reuse class `.avatar-badge`** yang SUDAH ADA di `layouts/app.blade.php` (dipakai di topbar dashboard, inisial 2 huruf nama) — jangan bikin style avatar baru dari nol, cukup import/duplikasi CSS itu ke `welcome.blade.php` (atau pindahkan ke partial shared kalau lebih rapi). Tambah varian `.avatar-badge-img` (buat `<img>` biar `object-fit:cover` bukan cuma teks).
3. **Styling dropdown:** `.navbar-user-menu-dropdown` posisinya `position:absolute` di bawah avatar, native CSS (`details[open] .navbar-user-menu-dropdown { display:block }` atau manfaatin behavior default `<details>`), background `--bg-card`, border `--border-color`, shadow tipis. Tutup dropdown otomatis kalau klik di luar (native `<details>` sudah handle sebagian, tapi tambahkan listener kecil buat close-on-outside-click biar konsisten dengan pola dialog yang sudah ada di app ini).
4. Route `extras.media.foto` sudah ada guard kepemilikan-nya sendiri (dipakai di banyak tempat) — pastikan ini masih aman dipanggil dari context navbar (user lihat foto dirinya sendiri, harusnya selalu boleh).

## Verifikasi

- Manual: guest lihat 1 tombol doang; Extras yang punya foto lihat foto sendiri di navbar; role lain (Admin/CD/SuperAdmin, yang gak punya `extrasProfile`) fallback ke inisial, tidak error; klik avatar buka dropdown Dashboard+Keluar; klik luar nutup dropdown; Keluar beneran logout.
- Test: cek route dashboard & logout dari dropdown baru masih münuju tempat yang benar per role.

---

# Bagian P: Rapihin form "Lengkapi Profil" Extras — upload progress, error inline, tata letak

## Konteks

Form `extras/profile-edit.blade.php` (9 section, banyak field) — Fakrul komplain: (1) upload foto/video gak ada feedback progress sama sekali (form submit polos, reload halaman, kelamaan buat video sampai 50MB tanpa ada tanda "lagi proses"), (2) field berasa berantakan, (3) ada error handling (blok `@if ($errors->any())` di atas) tapi "ketutup fieldnya" — manager-session cek kodenya: BENAR, cuma ada 1 blok error generik di paling atas halaman, TIDAK ADA pesan error di dekat masing-masing field yang salah — jadi kalau errornya soal field di section 7 (misal), user harus scroll ke atas buat baca pesannya lalu nebak-nebak field mana yang dimaksud, ini yang bikin "kerasa ketutup".

## Bagian P.1: Progress bar upload (foto & video)

1. Ubah mekanisme upload dari `onchange="this.form.submit()"` (submit polos, reload halaman) jadi **AJAX pakai `XMLHttpRequest`** (BUKAN `fetch`, karena `fetch` TIDAK punya event progress upload native — `XMLHttpRequest.upload.onprogress` yang punya) — tetap TIDAK ada library baru, native browser API.
2. Tambah elemen `<progress>` HTML native (bukan div custom, elemen paling ringan yang ada) di bawah tiap `media-upload-box`, `display:none` default, muncul begitu file dipilih, `value`/`max` di-update dari event `onprogress` (`e.loaded`, `e.total`).
3. Setelah upload sukses (response 200), refresh preview gambar/video di tempat (ganti `src` elemen yang relevan pakai response/timestamp cache-bust) TANPA reload halaman penuh — lebih smooth. Kalau gagal (validasi ukuran/format dari server), tampilkan pesan error di dekat situ juga (P.2).
4. Terapkan pola yang sama di 3 tempat upload: foto profil, video profil, 4 slot foto tambahan — buat 1 fungsi JS reusable (`function uploadWithProgress(form, progressEl, onSuccess)`), jangan copy-paste 6x.

## Bagian P.2: Error inline per field (bukan cuma 1 blok generik di atas)

1. **Pertahankan** blok generik `@if ($errors->any())` di atas (buat ringkasan cepat), TAPI tambahkan JUGA pesan error di bawah TIAP input yang relevan: `@error('nama_field') <span class="field-error">{{ $message }}</span> @enderror` — pola standar Laravel Blade, terapkan ke semua field yang punya validasi (`nama_asli`, `username`, `usia`, dst).
2. Tambah class visual `input-error` (border merah tipis, pakai `--danger` yang sudah ada) ke input yang error: `class="{{ $errors->has('nama_field') ? 'input-error' : '' }}"`.
3. **Auto-scroll ke field error pertama** kalau halaman reload dengan error (JS kecil: `document.querySelector('.input-error, .field-error')?.scrollIntoView({behavior:'smooth', block:'center'})` di `@push('scripts')`) — biar user langsung diarahkan, gak perlu scroll manual cari-cari.
4. Untuk upload foto/video (P.1, AJAX): error dari situ ditampilkan INLINE di dekat upload box yang gagal (bukan reload halaman + blok generik di atas, karena dengan AJAX halaman gak reload).

## Bagian P.3: Tata letak — kurangi kesan berantakan

1. Cek styling `.profile-section` yang ada sekarang (di `layouts/app.blade.php` atau tempat lain) — pastikan tiap section punya pemisah visual yang jelas (border/background card konsisten, spacing yang cukup antar section, BUKAN cuma judul angka doang nempel ke field berikutnya).
2. **Pertimbangkan gabungkan section yang kependekan** (section "5. Data Diri" 3 field + "6. Ciri-ciri Fisik" 2 field — dua-duanya sama-sama atribut fisik, bisa digabung jadi 1 section "Data Diri & Ciri Fisik" biar gak kebanyakan angka section buat info yang dikit) — implementer boleh eksekusi ini atau tidak, tapi kalau tidak, minimal rapikan spacing/visual card per section supaya tetap keliatan terpisah jelas.
3. Ini murni CSS/restrukturisasi Blade, TIDAK ada perubahan kolom DB atau field baru.

## Verifikasi Bagian P

- Manual: upload foto/video nunjukin progress bar native, preview update tanpa reload penuh; submit form dengan sengaja bikin error (misal username sudah dipakai) → pesan error muncul PAS di bawah field yang salah, bukan cuma di atas, dan halaman auto-scroll ke situ; tampilan form nggak lagi kerasa berantakan (subjektif, tapi minimal ada pemisah visual jelas antar section).
- Test: upload foto/video via AJAX tetap lolos validasi server yang sudah ada (ukuran, format) — test existing jangan sampai regresi cuma gara-gara ganti ke AJAX.

---

## Catatan buat Fakrul — soal penyimpanan foto/video (Google Drive vs S3 vs disk VPS)

**Ini jawaban langsung, BUKAN ditulis jadi task SPEC** (belum ada keputusan buat dieksekusi) — kalau Fakrul mau lanjut ke salah satu opsi, kasih tau baru manager-session tulis speknya.

**Google Drive: TIDAK disarankan buat penyimpanan utama foto/video sistem ini.** Alasan: (1) Drive API dirancang buat kolaborasi dokumen, bukan backend storage aplikasi — kena rate limit yang gak didesain buat trafik aplikasi; (2) kuota gampang penuh (15GB gratis per akun, video 50MB x puluhan Extras abis cepat); (3) model permission-nya (share link, per-file) rawan salah-setting jadi ke-expose publik — ini SENSITIF karena video/foto Extras itu data yang sudah kita jaga ketat (tembok visibilitas), 1 kesalahan toggle "siapa aja yang punya link" bisa bocorin video orang ke publik; (4) gak ada signed-URL/expiry kayak S3, gak cocok buat kontrol akses granular yang udah dibangun sistem ini (private disk, route ber-guard).

**Rekomendasi:** tetap pakai disk lokal VPS yang SUDAH jalan sekarang (private disk, sudah ada guard akses via route) — buat skala JBTB sekarang (50-80 extras aktif, 4-5 proyek/bulan) ini masih cukup, TIDAK perlu migrasi storage sekarang. Yang perlu dipikirkan: **backup otomatis** (rsync/rclone harian ke tempat lain, bukan Google Drive juga sih buat live storage, tapi Drive/cloud lain OK KHUSUS buat backup arsip, beda konteks sama live serving). Kalau nanti beneran butuh upgrade (jumlah file makin banyak, butuh CDN buat streaming video lebih cepat, atau mau scale ke multi-server), baru pindah ke **S3-compatible** (S3 asli, atau Cloudflare R2/DigitalOcean Spaces yang lebih murah, R2 malah gak ada biaya egress) — Laravel udah native support lewat `league/flysystem-aws-s3-v3`, tinggal ganti disk config, gak perlu ubah banyak kode karena udah pakai `Storage::disk()` abstraction.

Kalau mau, gua bisa tulis spec buat setup backup otomatis (opsi murah, gak ganggu storage utama) — bilang aja.

## Bagian Q: FIX BUG — page freeze pas CD klik foto tambahan di modal kandidat

> Ditulis 18 September 2026, oleh manager-session. **PRIORITAS TERTINGGI, kerjakan duluan sebelum R/S/T/U.**
> Bug ini dari perubahan di commit `67f3c0c` (obrolan langsung Fakrul-Claude Code, belum lewat SPEC ini) — jadi bukan salah spec sebelumnya, tapi tetap harus dibenerin via jalur ini biar tercatat.

### Konteks

Reproduksi: CD buka Greenlight → Lihat Kandidat → klik card kandidat (buka modal profil) → klik salah satu foto tambahan di dalam modal itu → **seluruh halaman freeze total**, gak bisa diapa-apain, cuma bisa di-refresh. Kejadian konsisten, bukan sesekali.

**Root cause udah ketemu, pasti, bukan tebakan** — di `resources/views/cd/reviews/show.blade.php`, function `window.bukaModalKandidat(appId)`:

```js
var slot = document.getElementById('mk-lightbox-slot');
slot.innerHTML = '';
var pre = document.getElementById('lb-pre-' + appId);
if (pre) {
    var clone = pre.cloneNode(true);
    clone.style.display = '';
    slot.appendChild(clone);
}
```

Ini nge-`cloneNode(true)` sebuah `<div id="lb-pre-{appId}" style="display:none">` yang isinya `@include('partials.foto-lightbox', ['lightboxId' => 'lb-' . $app->id])` — dan `foto-lightbox.blade.php` bikin `<dialog id="{lightboxId}-dialog">`. Dua masalah gabung jadi satu:

1. `cloneNode(true)` **gak ikut nge-eksekusi ulang `<script>`** yang ada di dalamnya — jadi handler klik `onclick="_lbOpen(...)"` di foto clone itu manggil `window._lbOpen` global (yang cuma didefinisikan SEKALI, dari instance foto-lightbox PERTAMA di halaman), bukan masalah utamanya sih karena `_lbOpen` emang didesain global.
2. Masalah utamanya: **ada 2 elemen dengan `id` yang SAMA** di DOM sekarang (`lb-{appId}-dialog` asli yang tersembunyi di `lb-pre-{appId}` yang `display:none`, DAN clone-nya yang barusan di-append ke `#mk-lightbox-slot`). `document.getElementById()` di dalam `_lbOpen` cuma nemu yang PERTAMA — yaitu yang ASLI, yang masih ada di dalam ancestor `display:none`. Manggil `.showModal()` pada `<dialog>` yang ancestornya `display:none` itu invalid per spec HTML — dan lebih parah lagi, `<dialog id="modal-kandidat">` (modal profil) SUDAH dalam kondisi `showModal()` terbuka, jadi ini juga nested-dialog-inside-open-dialog lewat DOM insertion dinamis, kombinasi yang emang dikenal fragile di banyak browser engine. Ini yang bikin browser hang/freeze total.

**Fix-nya BUKAN nambah tambalan (misal ganti id jadi unik per-clone) — itu cuma nutupin, `cloneNode` tetep salah pendekatan.** Fix yang benar: satu `<dialog>` lightbox SHARED di level halaman (sibling dari `#modal-kandidat`, JANGAN di-nest, JANGAN di-clone), datanya diisi ulang tiap kali dibuka — sama persis pola yang udah dipakai `#modal-kandidat` sendiri buat data kandidat (baca dari `data-*` attribute, bukan clone DOM).

### Implementasi

1. Di `cd/reviews/show.blade.php`, HAPUS semua `<div id="lb-pre-{{ $app->id }}" style="display:none;">...@include('partials.foto-lightbox', ...)...</div>` per-kandidat yang ada sekarang — ini sumber masalahnya, gak perlu ada lagi.
2. Tambah SATU array foto per kandidat ke `data-*` attribute yang udah ada di `.kandidat-card` (yang sekarang sudah bawa `data-foto`, `data-video`, dst) — tambah `data-fotos='{{ json_encode(array_column($app->fotosArr ?? [], "url")) }}'` (sesuaikan nama variabel foto array yang emang dipakai di file itu sekarang, ganti `$app->fotosArr` sesuai nama real-nya).
3. Tambah SATU `@include('partials.foto-lightbox', ['fotos' => [], 'lightboxId' => 'mk-lb'])` di level halaman (sibling `#modal-kandidat`, BUKAN di dalam grid card, BUKAN hidden) — ini bikin satu `<dialog id="mk-lb-dialog">` yang re-usable buat kandidat manapun, persis pola yang dipakai `extras/profile-show.blade.php` dan `admin/projects/applicants.blade.php` (dua file itu udah bener, gak ada bug ini — cek sudah, mereka include `foto-lightbox` langsung di badan halaman, gak di-clone ke modal lain).
4. Di `bukaModalKandidat(appId)`, ganti logic yang lama jadi: baca `data-fotos` dari card yang diklik, `JSON.parse`, lalu update `dataset.fotos` milik `<dialog id="mk-lb-dialog">` langsung (`document.getElementById('mk-lb-dialog').dataset.fotos = ...`) plus render ulang thumbnail grid-nya (`.lightbox-thumbs`) via JS — TANPA clone, tanpa nested-open-dialog. Kalau mau lebih lazy lagi: render thumbnail grid-nya di `#mk-foto-wrap`/`#mk-lightbox-slot` pakai `<img onclick="_lbOpen('mk-lb', idx)">` yang di-generate lewat JS loop dari array foto, dialog viewer-nya tetap yang satu shared itu.
5. **JANGAN** panggil `.showModal()` pada `<dialog>` manapun selagi `<dialog>` lain masih `showModal()`-open dan yang baru itu nested/di-append ke dalam DOM subtree dialog yang lama — kalau lightbox harus muncul "di atas" modal profil, itu otomatis kejadian by native top-layer behavior asal dua dialog itu SIBLING di DOM (bukan salah satu jadi child dari yang lain).

### Verifikasi

- Manual, ulang skenario reproduksi PERSIS: CD → Greenlight → Lihat Kandidat → klik card → klik foto tambahan di modal → **halaman TIDAK freeze**, lightbox foto muncul di atas modal, tombol prev/next/close normal, close lightbox balik ke modal profil (modal profil gak ikut ketutup).
- Ulangi buat MINIMAL 2 kandidat berbeda di halaman yang sama (buka kandidat A → tutup semua → buka kandidat B → klik foto) — pastikan foto yang muncul itu foto kandidat yang bener, bukan ke-cache dari kandidat sebelumnya (ini konsekuensi dari hapus `data-*` approach yang lama, harus dicek gak ada state nyangkut).
- Cek console browser (DevTools) gak ada error `Failed to execute 'showModal'` atau warning duplicate `id`.
- Grep ulang `cloneNode` di seluruh `resources/views/` — harus 0 hasil kalau ini satu-satunya tempat yang pakai pattern itu (kalau ada lagi di file lain, laporkan balik ke manager-session, jangan langsung fix sendiri kalau itu di luar scope Bagian Q ini).

---

## Bagian R: Ganti progress bar upload jadi loading-spinner sederhana

> Ditulis 18 September 2026, oleh manager-session.

### Konteks

Progress bar native `<progress>` di `extras/profile-edit.blade.php` (dipasang Bagian P.1) dilaporkan Fakrul "ga work, aneh kliatannya". Sudah dicek kode `uploadWithProgress()` — logic JS-nya sendiri sebenarnya BENER (`xhr.upload.onprogress` + `e.lengthComputable` guard itu standar). Masalahnya lebih ke sifat elemen `<progress>` native: cuma di-style `accent-color` doang di CSS (`.upload-progress`), sementara TRACK background-nya (bagian belum terisi) tetap default browser (abu-abu/putih terang) yang GAK ngikutin `--bg-page`/`--border-color` tema gelap — jadi kelihatan aneh/gak nyatu pas dark mode. Ditambah untuk file kecil di koneksi cepat, event `onprogress` bisa cuma sempat fire 1-2 kali (langsung lompat ke 100%) — kerasa "kaya gak jalan" padahal emang cuma kelewat cepat buat kelihatan animasinya.

Fakrul sendiri minta diganti loading-cycle/spinner aja — ini emang lebih masuk akal buat use-case ini: upload foto/video biasanya cepat, byte-progress yang presisi gak terlalu berguna dibanding sekadar indikator "sedang proses", dan spinner CSS jauh lebih gampang konsisten di semua browser + tema dibanding native `<progress>`.

### Implementasi

1. Di `<style>` `@push('head')` `extras/profile-edit.blade.php`, HAPUS `.upload-progress` (elemen `<progress>`), ganti dengan spinner CSS murni (gak ada library baru):
   ```css
   .upload-spinner {
       display: none; width: 22px; height: 22px; margin-top: 8px;
       border: 3px solid var(--border-color); border-top-color: var(--accent-strong);
       border-radius: 50%; animation: spin 0.7s linear infinite;
   }
   @keyframes spin { to { transform: rotate(360deg); } }
   ```
2. Ganti ketiga elemen `<progress id="progress-foto"|"progress-video"|"progress-slot-{{ $slot }}" class="upload-progress" ...>` jadi `<div id="..." class="upload-spinner"></div>` (id sama, cuma tag & class berubah).
3. Di `uploadWithProgress()`: HAPUS blok `xhr.upload.onprogress` (gak perlu lagi, gak ada value yang diisi). Tampilkan spinner (`progressEl.style.display = 'block'`) pas mulai upload (sebelum `xhr.send(form)`), sembunyikan (`style.display = 'none'`) di `xhr.onload` dan `xhr.onerror` — pola show/hide-nya sama kayak sekarang, cuma tanpa `.value` assignment.
4. Rename variabel `progressEl` biar gak nyesatin? Boleh dibiarin nama lama juga gak masalah, minor, terserah implementer — jangan buang waktu di ini.

### Verifikasi

- Manual: upload foto/video di form edit profil Extras → spinner muncul selagi proses, ketutup otomatis pas selesai (sukses ATAU gagal), gak ada elemen `<progress>` yang keliatan sama sekali lagi.
- Cek dark mode DAN light mode — spinner harus keliatan jelas di kedua tema (border color pakai variable tema, bukan hardcode).

---

## Bagian S: Fitur share link profil Extras — publik doang, tampilan terbatas

> Ditulis 18 September 2026, oleh manager-session. **Disarankan pakai subagent** (>3 file: migration, model, controller, route, view). **Revisi dari draf awal** — awalnya didesain 3 tingkat (publik/CD/Admin beda tampilan), Fakrul putuskan disederhanain: SATU tampilan doang, buat siapapun yang buka link (termasuk kalau yang buka itu ternyata Admin/CD yang lagi login) — gak perlu deteksi role viewer sama sekali. Lebih `/ponytail`, lebih lazy, lebih dikit permukaan bug.

### Konteks

Extras bisa generate link share profil dia sendiri. SIAPAPUN yang buka link itu (publik anonim, atau kebetulan yang buka lagi login sebagai CD/Admin/role lain) dapat tampilan YANG SAMA — terbatas, gak ada tingkatan.

**Field yang ditampilin** = SET YANG SAMA PERSIS kayak yang CD lihat sekarang di dashboard internal (aturan tembok visibilitas yang sudah ada: exclude `nama_asli`, `nik`, `rate_card`, `rekening`, `tautan_tambahan`) — reuse langsung, gak bikin aturan baru.

**Konfirmasi soal pertanyaan Fakrul** (data pribadi Extras aman, CD juga gak bisa liat): **Bener.** Ini bukan cuma soal fitur share ini doang — aturan "tembok visibilitas" yang udah berlaku dari awal proyek ini (di `CLAUDE.md` §5) emang udah bikin `nik`, `rekening`, `nama_asli`, `rate_card`, `tautan_tambahan` itu HANYA kebaca sama Admin/SuperAdmin lewat dashboard internal — CD (dan tentu publik) gak pernah dikasih akses ke field-field itu sama sekali, baik di halaman review kandidat, modal, maupun (sekarang) link share ini. Kalau mau nambahin note buat Extras di halaman profile-edit, boleh tulis kira-kira: *"Data sensitif kamu (NIK, nomor rekening, nama asli) cuma bisa dilihat Admin — Casting Director dan publik (termasuk lewat link share) TIDAK bisa melihatnya."*

### Implementasi

1. **Migration**: tambah kolom `share_token` (string, nullable, unique) ke `extras_profiles` — generate pakai `Str::random(32)` sekali pas pertama kali di-generate (bukan auto pas registrasi, Extras yang milih kapan mau bikin), simpan permanen (gak expire, `/ponytail` — kalau nanti mau expire/regenerate, itu revisi lain).
2. **Model** `ExtrasProfile`: JANGAN taruh `share_token` di `$fillable` (biar gak bisa di-mass-assign lewat form biasa) — generate lewat method khusus `generateShareToken()` yang cek dulu, kalau udah ada gak usah generate ulang (biar link lama gak keputus tiap generate).
3. **Controller**: tambah endpoint di controller profil Extras yang sudah ada, buat generate/lihat/copy link share-nya sendiri (tombol "Buat/Copy Link Share" di halaman profile-edit atau profile-show Extras).
4. **Public controller baru**: `PublicExtrasProfileController`, reuse pola yang SAMA kayak `PublicEventController` yang udah ada (cek dulu route prefix yang dia pakai sekarang, ikutin biar konsisten) — resolve `ExtrasProfile::where('share_token', $token)->firstOrFail()`, route contoh `GET /p/extras/{token}`.
5. **TIDAK ADA logic deteksi role viewer** — controller ini SELALU render field terbatas (set yang sama kayak CD), gak peduli yang buka link itu login sebagai apa atau logout total. Ini justru bikin controllernya lebih simpel dari draf sebelumnya.
6. **View**: satu Blade view baru khusus halaman publik ini (jangan reuse `extras/profile-show.blade.php` langsung — beda konteks akses, nanti gampang salah kalau digabung), layout minimal ala `layouts/auth.blade.php` tapi buat nampilin profil bukan form.

### Verifikasi

- Test: generate token 1 Extras, akses link dalam kondisi logout total, login CD, login Admin, login Extras lain → SEMUA kondisi dapat field yang SAMA (terbatas), gak ada bedanya sama sekali.
- Test: token salah/gak ada → 404, bukan error 500.
- Test: `nik`, `rekening`, `nama_asli`, `rate_card`, `tautan_tambahan` TIDAK ADA di response HTML sama sekali (cek raw HTML, bukan cuma "gak keliatan di UI").

---

## Bagian T: Dropdown menu di topbar dashboard (konsolidasi avatar + Keluar)

> Ditulis 18 September 2026, oleh manager-session. **Disarankan pakai subagent** (nyentuh flow auth-adjacent buat bagian ubah password).

### Konteks

Sama kayak Bagian O di homepage (avatar jadi dropdown), sekarang mau diterapin juga di topbar dashboard yang sudah login — sekarang masih avatar-badge + tombol "Keluar" berdiri sendiri (`layouts/app.blade.php` baris ~400-408).

Fakrul nanya isi dropdown-nya apa — ini jawaban gua: dropdown berisi **"Profil Saya"** (khusus role Extras, link ke halaman profile-edit yang udah ada — role lain BELUM punya halaman "profil" sendiri yang setara, jadi jangan dipaksa ada buat semua role dulu), **"Ubah Kata Sandi"** (baru, semua role, form sederhana current password + new password, reuse `Hash::check()`/`Hash::make()` standar Laravel — sudah dicek, sekarang CUMA ada flow forgot-password/reset-password buat yang LUPA password waktu logout, belum ada cara ganti password waktu udah login, ini gap nyata bukan cuma nice-to-have), dan **"Keluar"** (pindahin form logout yang udah ada ke dalam dropdown item, bukan tombol terpisah lagi).

### Implementasi

1. Di `layouts/app.blade.php`, ganti avatar-badge + tombol Keluar jadi 1 tombol trigger dropdown (reuse class `.avatar-badge` yang sudah ada buat visualnya), dropdown panel isi 2-3 item di atas — pola dropdown-nya SAMA kayak yang dibuat di Bagian O buat homepage (reuse CSS/JS-nya kalau memungkinkan, jangan bikin dropdown component kedua yang beda pattern).
2. "Profil Saya" cuma muncul kalau `auth()->user()->role === 'extras'` — role lain gak usah dikasih (belum ada halamannya).
3. **"Ubah Kata Sandi"**: route baru `GET/POST /ubah-password` (nama route bebas, konsisten sama pola existing), controller baru (atau tambah method di controller auth yang sudah ada) — form: current password (validasi `Hash::check` ke `auth()->user()->password`), password baru + konfirmasi (validasi sama kayak form register yang sudah ada). Sukses → flash message, redirect balik ke dashboard (JANGAN auto-logout, itu UX buruk buat ganti password biasa).
4. Tetap tampilkan theme-toggle button di luar dropdown seperti sekarang (itu dipakai sering banget, kalau ditaruh dalam dropdown extra klik buat toggle tema, kurang enak) — dropdown ini CUMA gantiin avatar+Keluar, bukan theme toggle.

### Verifikasi

- Manual per role: SuperAdmin/Admin/CD → dropdown isi "Ubah Kata Sandi" + "Keluar" doang (2 item). Extras → 3 item ("Profil Saya" + "Ubah Kata Sandi" + "Keluar").
- Test ubah password: salah current password → error jelas, gak ke-submit. Benar → password ke-update, bisa login pakai password baru, SESSION SEKARANG TETAP JALAN (gak ke-logout otomatis).
- Test: field password baru pakai validasi minimal yang SAMA kayak form register Extras/CD sekarang (jangan bikin aturan panjang-password baru yang beda sendiri).

---

## Bagian U: Sinkronkan tema dark/light di halaman login & register

> Ditulis 18 September 2026, oleh manager-session.

### Konteks

`layouts/auth.blade.php` (dipakai login/register) sekarang HARDCODE `<html data-theme="light">` dan gak baca `localStorage`, beda sama `welcome.blade.php` yang punya inline script di `<head>` (`document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light');`) buat sinkron tema SEBELUM render (biar gak ada flash warna salah).

### Implementasi

1. Di `layouts/auth.blade.php`, tambah SATU baris inline `<script>` di `<head>`, PERSIS sama kayak yang di `welcome.blade.php`, sebelum `@include('partials.theme-style')` biar gak ada flash:
   ```html
   <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light');</script>
   ```
2. Hapus/biarkan `data-theme="light"` di tag `<html>` — gak masalah dibiarin sebagai fallback awal karena script di atas langsung override-nya sebelum body ke-render.
3. **Itu doang** — gak perlu tambah theme-toggle button di halaman login/register (Fakrul cuma minta ikut state, bukan minta bisa di-toggle dari situ juga; kalau nanti mau toggle-nya juga ada di sini, bilang, ini beda scope).

### Verifikasi

- Manual: set dark mode dari homepage → buka /login atau /register di tab/kunjungan baru → harus langsung dark (gak flash putih dulu). Balik ke light dari salah satu halaman manapun (kalau ada toggle-nya) → cek konsisten di semua halaman lain juga.

## Bagian V: Center-in canvas signature-pad

> Ditulis 18 September 2026, oleh manager-session.

### Konteks

Fakrul minta field tanda tangan digital dirapihin jadi center. Sudah dicek `components/signature-pad.blade.php` (dipakai di `contracts/show.blade.php` DAN `invoices/show.blade.php` — reuse component yang sama) — `.signature-pad-wrap` sekarang gak punya alignment sama sekali, `<canvas>` (default `inline-block`) numpuk rata kiri karena parent-nya gak `text-align:center`. Fix di 1 file component ini otomatis berlaku ke SEMUA halaman yang pakai `<x-signature-pad>` (contracts & invoices), gak perlu sentuh 2 file itu.

### Implementasi

Di `resources/views/components/signature-pad.blade.php`, tambah styling ke `.signature-pad-wrap`:

```html
<div class="signature-pad-wrap" style="text-align: center;">
    <canvas id="canvas-{{ $name }}" width="500" height="200"
            style="border:1px solid #ccc; border-radius:8px; background:#fff; touch-action:none; max-width:100%; display:inline-block;"></canvas>
    <input type="hidden" name="{{ $name }}" id="input-{{ $name }}">
    <div style="margin-top: 8px;">
        <button type="button" class="btn btn-sm" onclick="clearSignature('{{ $name }}')">Hapus & Ulangi</button>
    </div>
</div>
```

Cuma nambah `text-align: center` ke wrapper + `display:inline-block` eksplisit ke canvas (biar gak gantung ke default browser) — tombol "Hapus & Ulangi" di bawahnya ikut ke-center juga karena dia `<div>` block penuh, teksnya sendiri di dalam tombol gak masalah. TIDAK ada perubahan JS, TIDAK ada perubahan struktur canvas (ukuran 500×200 tetap, cuma soal posisi).

### Verifikasi

- Manual: buka halaman kontrak (Admin & Extras) dan invoice (Admin & CD) yang ada signature pad-nya → canvas + tombol "Hapus & Ulangi" keliatan center secara horizontal, bukan rata kiri.
- Cek di layar kecil (mobile width) — canvas tetap `max-width:100%`, gak overflow, tetap center.
- Coba gambar tanda tangan & submit — pastikan `syncSignature`/`clearSignature` masih jalan normal (murni CSS, harusnya gak ada regresi JS, tapi tetap dicek).

## Bagian W: FIX BUG — share link publik belum opt-in + foto/video 403 buat guest + tampilan desktop & galeri

> Ditulis 18 September 2026, oleh manager-session. **REVISI** dari draf pertama (yang kemarin nyaranin ganti URL ke token) — Fakrul kasih concern valid: URL isi token 32-karakter random itu gak bisa "dieja"/didiktein manual sama Extras ke orang lain (misal lewat telepon/WA voice note), beda sama username yang gampang disebut. Jadi desain final di bawah ini: **URL yang keliatan/dibagi tetap pakai username** (gampang dibaca/diucapin), tapi ADA flag terpisah yang nentuin boleh diakses publik apa belum — biar konsen "opt-in"-nya tetap jalan tanpa Extras harus nyebut token acak.
> **PRIORITAS TINGGI, sebelum X/Y.** **WAJIB pakai subagent per `CLAUDE.md` §"Cara Kerja Coding"** (auth/access-control + >3 file: `routes/web.php`, `PublicExtrasProfileController.php`, `profile-show.blade.php`, `public/extras-profile.blade.php`). **SANGAT DISARANKAN dipecah beberapa komit** (fix opt-in gate dulu, baru media publik, baru video+layout+galeri).

### Konteks

Fakrul minta video ditambahin ke tampilan share publik, plus tampilan share publik ini dirasa masih kurang pas di desktop (numpuk sempit, "fullscreen aja") dan galeri foto tambahannya kurang rapi. Pas manager-session cek buat nulis speknya, ketemu **dua bug nyata** yang belum kelihatan karena Fakrul testing sambil login (foto tampil normal di screenshot karena itu sesi Fakrul sendiri sebagai pemilik profil):

**Bug 1 — share link kepake TANPA Extras pernah opt-in.** Route sekarang: `Route::get('/p/extras/{username}', [PublicExtrasProfileController::class, 'show'])`, controller lookup `User::where('username', $username)` doang — TANPA cek apapun soal apakah Extras itu pernah klik "Share"/generate link. Efeknya: **SEMUA profil Extras bisa diakses publik lewat `/p/extras/{username}`, walau dia belum pernah setuju di-share.** Kolom `share_token` + `ExtrasProfile::generateShareToken()` udah ada tapi gak dipakai buat gating apapun di route ini (cuma nyimpen nilai, gak dicek). Fitur "opt-in" yang diminta Fakrul dari awal jadi gak beneran opt-in.

**Fix-nya (setelah revisi):** TETAP pakai username di URL (gampang diucap/diketik Extras, sesuai concern Fakrul), tapi controller WAJIB cek `share_token` udah pernah di-generate (gak null) sebelum nampilin apapun — kalau belum pernah klik "Share", 404 walau username-nya bener. Jadi username tetap jadi "alamat", `share_token` (gak keliatan di URL utama) jadi "kunci apakah alamat itu lagi dibuka buat umum". Ini standar yang sama kayak profil publik Instagram/LinkedIn — URL gampang dibaca, tapi tetap ada toggle "boleh dilihat publik apa nggak".

**Bug 2 — foto/video di halaman share publik 403/gak muncul buat visitor yang beneran logout.** Route media (`/media/foto/{extrasProfile}`, `/media/video/{extrasProfile}`, `/media/foto-tambahan/{extrasProfile}/{slot}`) itu di-wrap `Route::middleware('auth')` (`routes/web.php` baris ~289) — visitor yang GENUINE logout bakal keredirect ke `/login` pas browser coba load `<img src="...">`/`<video src="...">`, hasilnya broken image. Fix-nya beda dari halaman utama: route media INI tetap pakai `share_token` (bukan username) sebagai parameter-nya — karena URL media ini cuma dipakai sebagai `src` attribute di HTML (gak pernah diketik/diucapin manual sama siapapun), jadi gak masalah dia acak/gak gampang dieja, dan justru itu nge-block orang scraping foto/video langsung dari URL media tanpa lewat halaman profil resminya.

### Implementasi

1. **Route halaman utama TETAP pakai username** — `routes/web.php`, TIDAK berubah dari sekarang: `Route::get('/p/extras/{username}', [PublicExtrasProfileController::class, 'show'])->name('public.extras.profile');`
2. **Controller** `PublicExtrasProfileController::show(string $username)`: tambah 1 baris cek — `$profile = $user->extrasProfile; abort_if(!$profile || !$profile->share_token, 404);` SEBELUM render view. Ini satu-satunya perubahan di controller ini buat nutup Bug 1 — kalau `share_token` masih null (Extras belum pernah klik "Share"), langsung 404.
3. **Di controller halaman "Profil Saya" internal** (`ExtrasProfileController` atau sejenisnya yang render `profile-show.blade.php`): panggil `$profile->generateShareToken()` (method udah ada, cek dulu kalau udah ada gak generate ulang) SEBELUM view di-render — ini yang "menyalakan" opt-in-nya pertama kali halaman ini pernah dibuka Extras. Kalau Fakrul mau opt-in-nya BENERAN cuma nyala pas Extras SENGAJA klik tombol "Share" (bukan otomatis pas buka halaman profil) — kasih tau, ini gampang disesuaikan tinggal pindah pemanggilannya ke handler klik tombol Share aja (butuh 1 endpoint kecil, POST doang, balikin token). Default implementasi ini pilih yang paling `/ponytail` (auto-generate pas halaman dibuka, gak nambah endpoint baru) — kalau kurang cocok bilang aja.
4. **Method `ProfileController::generateShareLink()` yang sekarang orphan** (gak dipanggil dari mana-mana): boleh dihapus (`/ponytail`, dead code) — TIDAK dipakai di desain final ini.
5. **Buat route media KHUSUS publik**, TETAP pakai `{token}` (bukan username, sesuai alasan di Konteks), unguarded oleh `auth` middleware:
   ```php
   Route::get('/p/extras/media/{token}/foto', [PublicExtrasProfileController::class, 'foto'])->name('public.extras.foto');
   Route::get('/p/extras/media/{token}/video', [PublicExtrasProfileController::class, 'video'])->name('public.extras.video');
   Route::get('/p/extras/media/{token}/foto-tambahan/{slot}', [PublicExtrasProfileController::class, 'fotoTambahan'])->whereNumber('slot')->name('public.extras.foto-tambahan');
   ```
   Masing-masing method resolve `ExtrasProfile::where('share_token', $token)->firstOrFail()` dulu (404 kalau token invalid ATAU null), baru `Storage::disk('local')->response(...)` — **JANGAN** reuse `ProfileController::fotoStream()` dkk yang lama (butuh `$request->user()`, error kalau dipanggil tanpa auth) — method independen di controller publik ini.
6. **Tambah section Video di `public/extras-profile.blade.php`** (belum ada sama sekali sekarang) — copy pola section Foto Tambahan yang udah ada, buat video, pakai route baru `public.extras.video` dari langkah 5 (parameter-nya `$profile->share_token`, sudah pasti ada karena kalau kosong halaman ini gak akan ke-render, sudah ke-block di langkah 2). Kalau `video_profil_path` null, placeholder kosong (pola sama kayak `profile-show.blade.php` internal — icon `ti-video-off` + teks "Belum ada video").
7. Foto profil & foto tambahan yang udah ada di file ini ikut diganti src-nya ke route publik baru (`public.extras.foto`, `public.extras.foto-tambahan`, parameter `$profile->share_token`) — BUKAN `extras.media.foto` yang lama (itu tetep dipertahankan buat dashboard internal, jangan diubah/dihapus).
8. **Layout desktop, `public/extras-profile.blade.php`**: sekarang `.wrap { max-width: 560px; margin: 0 auto; ... }` — sama persis masalahnya kayak Bagian X di halaman internal. Terapin pola YANG SAMA: pisah jadi 2 kolom di atas breakpoint 900px (media kiri: foto profil + video + galeri foto tambahan, sticky; info kanan: data diri, pengalaman, tautan, tarif), single-column di bawah 900px (behaviour sekarang, jangan diubah). Reuse CSS grid pattern dari Bagian X (`.profile-layout`/`.profile-media-col`/`.profile-info-col`), boleh reuse literal class name yang sama supaya konsisten kalau nanti mau di-share ke 1 file CSS umum, TAPI ini file terpisah (public, layout minimal `layouts/auth.blade.php`-style, bukan `layouts/app.blade.php`) jadi definisikan ulang class-nya di `<style>` block file ini sendiri.
9. **Rapiin galeri foto tambahan** (`.thumb-grid`) — sekarang `grid-template-columns: repeat(auto-fill, minmax(72px, 1fr))`, itu bikin thumbnail kegencet kecil-kecil kalau lebar kolom media gede di desktop (bisa jadi kebanyakan kolom rapat). Ganti jadi `grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px;` (thumbnail lebih besar, jarak lebih lega) — dan taruh galeri ini SETELAH video di `.profile-media-col`, biar urutan media-nya: foto profil → video → galeri foto tambahan, konsisten sama urutan di halaman internal.

### Verifikasi

- **Paling penting**: test dalam kondisi **BENERAN LOGOUT** (browser lain/incognito) — buka link `/p/extras/{username}` punya Extras yang UDAH PERNAH buka halaman "Profil Saya" (jadi `share_token`-nya udah ke-generate) → foto profil, video, DAN galeri foto tambahan semua tampil, gak ada broken image/redirect ke login.
- Test: Extras yang BELUM PERNAH buka halaman "Profil Saya" sama sekali (`share_token` masih null, kalau ada data seperti ini) → buka `/p/extras/{username-dia}` → HARUS 404.
- Test: `nik`, `rekening`, `nama_asli`, `rate_card`, `tautan_tambahan` tetap TIDAK ADA di response (regresi check dari Bagian S, jangan sampai ke-reset pas refactor ini).
- Manual: buka halaman share publik di desktop lebar (>1200px) → 2 kolom, media kiri (foto+video+galeri) info kanan, TIDAK numpuk sempit di tengah lagi. Resize ke mobile → balik single-column kayak sekarang, urutan foto→video→galeri→data tetap bener.
- Manual: galeri foto tambahan (4 slot) di desktop lebar → thumbnail keliatan proporsional, gak kegencet kecil-kecil, spacing enak dilihat.

---

## Bagian X: Layout "Profil Saya" (internal) — responsif desktop, bukan cuma mobile

> Ditulis 18 September 2026, oleh manager-session.

### Konteks

`extras/profile-show.blade.php` sekarang `.card` di-cap `max-width: 560px; margin: 0 auto` — di layar desktop lebar (lihat screenshot Fakrul), ini bikin konten numpuk sempit di tengah dengan banyak ruang kosong di kiri-kanan yang kebuang. Ini emang sengaja dibuat sempit awalnya buat mobile (single column, gampang dibaca), tapi Fakrul minta versi desktop yang lebih "make sense" pakai lebar yang ada, TANPA bikin versi mobile jadi jelek.

### Implementasi

1. Bikin 2 wrapper baru buat pisahin section media (foto profil, video, foto tambahan) dari section teks/data (data diri, pengalaman, tautan, tarif) — bungkus jadi `<div class="profile-media-col">...</div>` dan `<div class="profile-info-col">...</div>`, isi section-section yang udah ada dipindah ke masing-masing wrapper TANPA ubah isi/urutan internalnya.
2. CSS baru:
   ```css
   .profile-layout { max-width: 560px; margin: 0 auto; }
   @media (min-width: 900px) {
       .profile-layout { max-width: 900px; display: grid; grid-template-columns: 320px 1fr; gap: 24px; align-items: start; }
       .profile-media-col { position: sticky; top: 24px; }
   }
   ```
   Ganti `.card` yang sekarang jadi pembungkus `.profile-layout` (title+tombol Share/Edit di atas, di luar grid, full-width), lalu di dalamnya `.profile-media-col` + `.profile-info-col` sebagai 2 grid item.
3. **Di bawah breakpoint 900px** (termasuk semua ukuran mobile) — behaviour TETAP SAMA seperti sekarang, single column, media di atas baru data di bawah, urutan gak berubah. Breakpoint 900px dipilih karena di bawah itu 2 kolom bakal kegencet/kesempitan buat konten media (foto 180px + text), sesuaikan angka ini kalau pas dicoba masih kerasa aneh di lebar tablet (~700-900px), itu wewenang implementer buat fine-tune.
4. Foto profil & video biarin ukurannya proporsional ke `.profile-media-col` yang lebih lebar (~320px) di desktop, bukan tetep 180px hardcode kayak sekarang — foto profil boleh full-width dari kolom itu (jaga aspect-ratio 3/4 yang udah ada).

### Verifikasi

- Manual: buka halaman "Profil Saya" (bukan yang public share, yang internal ini) di lebar desktop (>1200px) → konten make sense pakai lebar yang ada, GAK numpuk sempit di tengah dengan whitespace gede kiri-kanan, tapi juga GAK stretch berantakan/kosong aneh.
- Manual: resize ke lebar tablet (~800px) dan mobile (~375px) → balik ke single-column, tetep enak dibaca kayak sekarang, gak ada elemen kepotong/overflow.
- Cek breakpoint transition-nya (resize browser window pelan-pelan lewatin 900px) — gak ada layout yang "patah"/jump aneh pas nyebrang breakpoint.

---

## Bagian Y: Samain style tombol copy-link ke input URL-nya

> Ditulis 18 September 2026, oleh manager-session.

### Konteks

Di modal "Bagikan Profil Kamu" (`profile-show.blade.php`, `#modal-share`), input URL (`#share-url-modal`) pakai `background: var(--bg-card-hover)`, sementara tombol copy (`#btn-copy-link`) di sebelahnya pakai `background: var(--bg-card)` — beda satu shade, keliatan kayak 2 elemen lepas yang gak senada padahal fungsinya nempel jadi 1 unit (url + tombol salin-nya).

### Implementasi

Di `profile-show.blade.php`, ganti `background: var(--bg-card)` jadi `background: var(--bg-card-hover)` di style inline `#btn-copy-link` (baris yang sekarang `style="flex-shrink:0; align-self:stretch; padding:0 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); ...`) — samain persis kayak background input di sebelahnya. Border-nya udah sama (`var(--border-color)`), jadi cukup 1 property ini doang yang diganti, biar keduanya keliatan 1 kesatuan grup.

### Verifikasi

- Manual: buka modal Share di halaman Profil Saya → input URL dan tombol copy-nya keliatan senada (warna dasar sama), bukan 2 shade beda kayak sekarang. Cek di dark mode DAN light mode.

---

## Bagian Z: Rename label "Foto Tambahan" → "Gallery"

> Ditulis 18 September 2026, oleh manager-session. Pola yang sama kayak rename "Callsheet"/"Lineup"/"Greenlight"/"Reel" sebelumnya (Session-session lama) — label tampilan doang, TIDAK ada perubahan nama kolom DB/route/variabel.

### Konteks

Fakrul minta section "Foto Tambahan" diganti jadi "Gallery" (konsisten sama gaya label lain yang udah dipake — Callsheet, Lineup, Greenlight, Reel — semuanya istilah Inggris walau UI-nya Indonesia).

### Implementasi

Ganti TEKS TAMPILAN doang (bukan nama variabel/kolom/route, itu semua tetap `foto_tambahan`/`fotoTambahan`/dst seperti sekarang) di:

1. `resources/views/extras/profile-show.blade.php` baris 62: `<div class="profile-section-title">Foto Tambahan</div>` → `Gallery`
2. `resources/views/public/extras-profile.blade.php` baris 111: `<div class="card-title">Foto Tambahan</div>` → `Gallery`
3. `resources/views/extras/profile-edit.blade.php` baris 100: `<div class="profile-section-title">Foto Tambahan</div>` → `Gallery` (baris 98, komentar `{{-- ===== Foto Tambahan ===== --}}`, boleh ikut diganti `Gallery` juga, tapi opsional karena cuma komentar)
4. `resources/views/partials/foto-lightbox.blade.php` baris 2: teks empty-state `Belum ada foto tambahan.` → `Gallery masih kosong.`
5. `resources/views/cd/reviews/show.blade.php` baris 260: `p.textContent = 'Belum ada foto tambahan.';` → `'Gallery masih kosong.'`

Grep ulang `Foto Tambahan` (case-sensitive, exact) di `resources/views/` setelah selesai — harus 0 hasil kalau semua kepake udah keganti (di luar komentar kalau poin 3 gak diikutin).

### Verifikasi

- Manual: cek ke-4 tempat di atas (halaman edit profil Extras, lihat profil Extras internal, halaman share publik, modal kandidat di CD) — semua nampilin "Gallery", bukan "Foto Tambahan" lagi.
- Cek empty-state (Extras yang belum upload foto tambahan sama sekali) → teksnya "Gallery masih kosong.", bukan "Belum ada foto tambahan." lagi.

---

## Bagian AA: Gallery — thumbnail lebih gede & lebih lebar, full-width di desktop

> Ditulis 18 September 2026, oleh manager-session. **Koreksi sekaligus revisi** — tolong baca catatan koreksi di Konteks sebelum eksekusi, ada instruksi di Bagian W poin 9 yang manager-session sendiri salah target selector-nya, supaya gak dobel-kerja atau bingung mana yang bener.

### Konteks

Fakrul minta thumbnail Gallery dibuat lebih besar & lebih lebar ke samping — masih kurang enak dilihat.

**Koreksi dulu:** di Bagian W poin 9 kemarin, manager-session nyaranin ganti `.thumb-grid` di `public/extras-profile.blade.php`. Itu SALAH TARGET — `.thumb-grid` di file itu emang ke-define di `<style>` block-nya, tapi **gak pernah kepake** (section Gallery di file itu render lewat `@include('partials.foto-lightbox', ...)`, yang grid-nya pakai class `.lightbox-thumbs` DARI PARTIAL itu, bukan `.thumb-grid`). Jadi kalau Bagian W poin 9 udah dieksekusi persis kayak ditulis, itu gak ngefek apa-apa ke tampilan (ganti CSS yang gak dipakai) — **abaikan Bagian W poin 9, ganti sesuai instruksi di bawah ini aja.**

Karena `.lightbox-thumbs` itu di SATU partial yang dipakai di SEMUA tempat (form edit, lihat profil internal, share publik, modal kandidat CD, halaman applicants Admin), fix di 1 file ini otomatis berlaku ke semua surface itu.

### Implementasi

1. **`resources/views/partials/foto-lightbox.blade.php`**, ganti `.lightbox-thumbs`:
   ```css
   .lightbox-thumbs { display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
   ```
   (dari `minmax(72px, 1fr)` + `gap: 6px` yang sekarang) — thumbnail jadi jauh lebih besar, dan otomatis lebih dikit per baris di kolom yang sama makanya kerasa "lebih lega".
2. **Biar beneran "lebih lebar ke samping" (bukan cuma gede tapi tetep kegencet di kolom sempit)** — di layout 2-kolom desktop yang udah dispesifikasiin Bagian W (public share) dan Bagian X (internal profile), section Gallery JANGAN ikut ditaruh di dalam `.profile-media-col` (kolom sempit ~320px) — pindahin section Gallery jadi FULL-WIDTH di BAWAH area 2-kolom itu (span kedua kolom), pakai `grid-column: 1 / -1` di container section Gallery-nya. Urutan render halaman jadi: [baris 2-kolom: media kiri (foto profil+video) | info kanan (data diri dst)], BARU di bawahnya section Gallery yang lebar penuh.
3. Ini berarti REVISI KECIL ke Bagian W poin 8 dan Bagian X poin 1 (yang kemarin nyebut galeri ikut masuk `.profile-media-col`) — Gallery-nya DIKELUARIN dari kolom media, jadi section terpisah full-width di bawah. Kalau Bagian W/X udah dieksekusi duluan dengan galeri di dalam kolom media, tinggal pindahin section-nya keluar grid 2-kolom, gak perlu bongkar ulang yang lain.
4. Di mobile/single-column (di bawah breakpoint 900px) — urutan tetap sama kayak sekarang (foto profil → video → Gallery → data), gak ada perubahan.

### Verifikasi

- Manual: buka halaman manapun yang render Gallery (edit profil, lihat profil internal, share publik, modal kandidat CD, applicants Admin) di desktop lebar → thumbnail keliatan jauh lebih besar & lega, gallery-nya lebar penuh (span semua lebar konten, gak kegencet di kolom sempit).
- Manual: mobile → tetap 2-3 thumbnail per baris (grid auto-fill otomatis nyesuaiin), gak overflow, urutan section gak berubah dari sekarang.
- Cross-check: pastikan `.thumb-grid` yang gak kepake di `public/extras-profile.blade.php` gak ketinggalan (boleh dihapus aja sekalian dari `<style>` block-nya, `/ponytail` — dead CSS).

---

## Bagian AB: "Fullscreen" beneran = fit 1 viewport tanpa scroll di desktop (REVISI Gallery jadi filmstrip)

> Ditulis 18 September 2026, oleh manager-session. **Baca dulu sebelum eksekusi Bagian AA** — Bagian ini REVISI cara Gallery ditampilkan (poin 2 di bawah gantiin desain "grid lebar" di Bagian AA poin 2), karena ternyata maksud "fullscreen" Fakrul itu soal TINGGI (gak sampai scroll ke bawah), bukan cuma lebar.

### Konteks

Fakrul klarifikasi: "fullscreen" itu maksudnya di desktop kontennya harus fit 1 layar penuh, gak sampai perlu scroll ke bawah.

**Pushback jujur dulu, sebelum masuk desain:** "gak sampai scroll SAMA SEKALI" itu klaim yang gampang meleset kalau kontennya panjang — misal Extras yang isi "Pengalaman Main/Kerja" panjang banget (beberapa paragraf), atau Gallery-nya keisi penuh 4 slot + banyak variasi tinggi layar (laptop 13" ~768px vs monitor besar ~1080px+). Kalau dipaksa "no scroll" mutlak apapun kontennya, ujungnya konten yang kepotong/ke-hide, itu lebih buruk daripada scroll dikit. Jadi target yang REALISTIS & tetap keliatan "fullscreen": **halaman-nya sendiri gak nge-scroll (gak ada scrollbar browser di level page)**, tapi kalau ada 1 bagian yang isinya kebetulan kepanjangan (biasanya kolom info teks), bagian ITU AJA yang scroll internal di dalam kotaknya — bukan seluruh halaman. Ini pola umum buat "app-like fullscreen layout" (mirip dashboard), bukan artikel yang di-scroll dari atas ke bawah.

### Implementasi

Berlaku buat KEDUA halaman yang udah dikasih layout 2-kolom di Bagian W (share publik) & Bagian X (profil internal) — TERAPIN CUMA DI BREAKPOINT DESKTOP (≥900px), mobile TETAP scroll normal kayak sekarang (di HP, scroll itu wajar & diharapkan, jangan dipaksain juga di situ):

1. **Container utama jadi tinggi 1 viewport, gak lebih:**
   ```css
   @media (min-width: 900px) {
       .profile-layout { height: calc(100vh - <tinggi topbar/header yang ada>); overflow: hidden; }
       .profile-info-col { height: 100%; overflow-y: auto; }
       .profile-media-col { height: 100%; overflow: hidden; }
   }
   ```
   (`<tinggi topbar/header yang ada>` sesuaikan sama tinggi real elemen header di masing-masing file — di `profile-show.blade.php` itu topbar dari `layouts/app.blade.php`, di `public/extras-profile.blade.php` itu `.top-row` yang udah ada.)
2. **REVISI Gallery (gantiin Bagian AA poin 2-3):** Gallery JANGAN jadi grid yang wrap ke bawah (itu bikin tinggi halaman gak menentu, gampang bikin overflow) — ganti jadi **filmstrip horizontal 1 baris**: thumbnail besar (tetap pakai ukuran dari Bagian AA, ~140px), tapi `.lightbox-thumbs` diganti lagi:
   ```css
   .lightbox-thumbs { display:flex; gap:10px; overflow-x:auto; padding-bottom:4px; }
   .lightbox-thumbs img { flex: 0 0 140px; width:140px; aspect-ratio:1/1; object-fit:cover; border-radius:8px; cursor:pointer; }
   ```
   Ini gantiin CSS yang ditulis di Bagian AA poin 1 (jangan pakai grid lagi, pakai flex+scroll-x ini). Efeknya: Gallery tetap keliatan gede & lebar (sesuai request sebelumnya), TAPI tingginya selalu tetap (1 baris) gak peduli ada 1 atau 4 foto — foto ekstra scroll ke SAMPING (di dalam strip-nya sendiri), BUKAN bikin halaman jadi lebih tinggi ke bawah.
3. Gallery ini taruh sebagai bagian PALING BAWAH di `.profile-info-col` (kolom info yang boleh scroll internal) — BUKAN full-width lintas 2 kolom lagi seperti Bagian AA poin 2 (itu direvisi, dibatalkan) — karena kalau Gallery taruh di luar kedua kolom sebagai baris ke-3, dia nambah tinggi total halaman lagi, balik lagi ke masalah scroll. Ditaruh di ujung kolom info yang emang udah dikasih `overflow-y:auto`, jadi kalaupun kolom itu penuh, yang scroll cuma kolom itu sendiri (dalam kotaknya), bukan seluruh halaman/browser.
4. Kolom media (foto profil + video, TANPA gallery lagi karena udah dipindah ke poin 3) otomatis lebih santai tingginya, harusnya cukup dalam 1 viewport tanpa masalah.

### Verifikasi

- Manual: buka di beberapa tinggi layar berbeda (resize browser height, termasuk yang pendek ~700px) → TIDAK ada scrollbar di level halaman/browser di desktop. Kalau kolom info-nya kebetulan panjang (extras dengan bio panjang + gallery penuh), yang muncul scrollbar cuma DI DALAM kolom info itu (ada border/batas jelas kolom itu beda dari scroll halaman).
- Manual: coba Extras dengan Gallery 4 foto penuh → filmstrip bisa di-scroll ke samping (drag/scroll horizontal), foto ke-4 gak "ketutup"/gak keakses.
- Manual: mobile (di bawah 900px) → TETAP scroll normal seperti sebelumnya, semua perubahan di atas cuma nyala di breakpoint desktop.
- Ini exception dari filosofi umum "jangan overengineer" — internal-scroll-di-dalam-box itu emang lebih kompleks dari scroll halaman biasa, tapi itu trade-off yang perlu buat beneran capai "gak sampai scroll" yang diminta tanpa motong data.

---

## Bagian AC: Tema theatrical/cinema di landing page — grain, film-strip, "reel" carousel, spotlight

> Ditulis 19 September 2026, oleh manager-session. **WAJIB pakai subagent per `CLAUDE.md` §"Cara Kerja Coding"** (bukan karena banyak file — semua kontennya di `welcome.blade.php` doang — tapi karena kompleksitas: CSS/JS animasi baru yang harus bener secara aksesibilitas & fallback browser, bukan sekadar tempel gambar). **SANGAT DISARANKAN dipecah beberapa komit** (grain+film-strip dulu, baru reel carousel, baru spotlight — masing-masing bisa didemoin terpisah).

### Konteks — riset yang dipakai (bukan asal tebak)

Fakrul minta nuansa theatrical/movie/casting yang lebih kental, terutama di dark theme tapi tetap "nyambung" di light theme, plus animasi yang lebih dari sekadar gambar background — dia sebut contoh "roll film". Manager-session riset dulu sebelum nulis spec ini (bukan modal feeling):

**Riset desain** — baca artikel roundup Qode Interactive ["24 Stunning Examples of Movie Industry Websites"](https://qodeinteractive.com/magazine/24-stunning-examples-of-movie-industry-websites/), nyari pola yang BENERAN dipakai situs produksi film/studio asli (Skyline Films, A24, 20/20 Films, Ali Ali, Brother Film, Faliro House, dll). Pola yang konsisten muncul & relevan buat JBTB (bukan semua — beberapa kita skip, lihat catatan di bawah): **tekstur grain/noise hitam-putih di background** (20/26 Films, Ali Ali, Brother Film — "grainy texture, subtle nod to film industry"), **foto/poster yang default hitam-putih lalu jadi warna pas di-hover** (American Documentary, Faliro House), **tipografi cinematic uppercase buat menu/label** (The Greatest Showman — "menu links displayed in purely cinematic style as if they were movie titles"), **restraint/gak berlebihan animasinya** (A24 — "animation and transition effects aren't too wild... matching the simplicity of displayed content", ini yang paling penting diikuti karena sesuai arah branding JBTB selama ini: korporat-profesional, BUKAN experimental/gimmicky kayak situs single-movie-hype).

**Yang SENGAJA di-skip** dari roundup itu (biar gak overboard, gak sesuai konteks JBTB yang situs company profile + portal casting, bukan situs hype 1 film): custom cursor berbentuk lingkaran/kotak, infinite-canvas portfolio, choose-your-own-adventure interaktif, background music otomatis, horizontal-scroll-menggantikan-navigasi-utama. Itu semua keren buat situs film/director portfolio, tapi kebanyakan buat B2B casting agency yang mau keliatan bisa dipercaya klien produksi.

**Riset teknis** — sebelum nulis kode animasi, manager-session cek `modern-web-guidance` (skill wajib buat kerjaan CSS/JS klien-side) biar gak kasih instruksi API/animasi yang udah usang. Hasil yang dipakai: `scroll-driven animations` (`animation-timeline: view()`) buat efek scroll pada carousel, `interactive-content-reveal` (CSS `mask-image` + `@property` custom property) buat efek spotlight ikut kursor, dan aturan wajib `prefers-reduced-motion` + `@supports` feature-detection buat semuanya (Firefox belum support scroll-driven animations per data guide ini, jadi WAJIB ada fallback, bukan opsional).

Implementasi di bawah SEMUA reuse variable warna tema yang udah ada (`--bg-page`, `--bg-card`, `--accent`, `--border-color`, `--text-primary`, dst dari `partials/theme-style.blade.php`) — TIDAK ada warna hardcode baru, biar otomatis kerja di dark & light theme sesuai constraint Fakrul ("related juga di light theme").

### AC.0: Disiplin warna — ijo brand TETAP dipertahankan, jangan ke-geser jadi abu-abu semua

**Warna hijau brand (`--accent`/`--accent-strong`) TIDAK BOLEH hilang/tergeser** oleh tema theatrical ini — semua elemen ijo yang UDAH ADA sekarang (navbar, tombol CTA, badge "DIBUKA", dst) TIDAK disentuh sama sekali di Bagian AC ini, dan elemen BARU yang cocok dikasih warna (AC.5 eyebrow label) SENGAJA pakai `var(--accent-strong)` biar ijo-nya keliatan di tempat baru juga, bukan cuma dibiarin di tempat lama doang.

Yang SENGAJA dibuat netral/hitam-putih di Bagian ini cuma yang sifatnya TEKSTUR murni (grain di AC.1, film-strip divider di AC.2, spotlight glow di AC.6) — ini BUKAN kelupaan warnain, ini disiplin yang SAMA kayak keputusan "monochrome + selective accent" pas kita adopsi referensi agiveteam.co dulu (liat riwayat SPEC sebelumnya): base netral, warna cuma nongol di titik yang emang mau ditonjolkan (tombol, label, CTA), biar hijau-nya kerasa "istimewa" bukan dipake di semua tempat sampai encer. Spotlight di AC.6 khususnya sengaja putih/netral (`rgba(255,255,255,0.12)`) niru cahaya panggung/studio asli (lampu sorot panggung itu putih/warm, bukan hijau — kalau dihijauin malah kesannya horor bukan glamor). Kalau Fakrul tetap mau spotlight-nya bertema hijau juga, gampang, tinggal ganti value rgba itu ke `var(--accent)` dengan alpha rendah — bilang aja, ini 1 baris doang.

### AC.1: Grain/noise texture overlay (global, seluruh landing page)

Tekstur grain halus yang nge-nod ke "film" tanpa norak — dari referensi Ali Ali/Brother Film/20/20 Films.

```css
.film-grain {
    position: fixed; inset: 0; z-index: 1; pointer-events: none;
    opacity: var(--grain-opacity, 0.05);
    mix-blend-mode: overlay;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}
:root[data-theme="dark"] { --grain-opacity: 0.05; }
:root[data-theme="light"] { --grain-opacity: 0.02; }
```

Taruh `<div class="film-grain"></div>` sekali di awal `<body>` (bukan di dalam section manapun, biar nutup seluruh viewport & ikut scroll pakai `position:fixed`). Opacity beda jauh antara dark (kerasa) & light (samar tapi tetap "ada", biar related sesuai request Fakrul, gak dominan ganggu keterbacaan teks korporat di light mode).

### AC.2: Sprocket-hole film-strip divider

Divider horizontal literal "pita film" — CSS murni, gak ada gambar/asset baru.

```css
.film-strip-divider {
    height: 18px;
    background:
        repeating-linear-gradient(90deg, var(--bg-sidebar) 0 14px, transparent 14px 28px),
        var(--border-color);
    background-position: center;
    background-size: 28px 10px, 100% 2px;
    background-repeat: repeat-x, no-repeat;
    opacity: 0.6;
}
```
(Ini bikin garis tipis dengan "lubang-lubang" berulang mirip sprocket hole di tepi pita film — sesuaikan angka `14px`/`28px` pas dicoba visual, itu wewenang implementer buat fine-tune biar proporsional.)

Taruh SATU div ini di antara `.hero` dan `.about-section`, dan SATU lagi di antara `.lowongan-section` dan `.produksi-section` — jangan taruh di semua celah section (kebanyakan jadi norak), cukup 2 titik strategis itu.

### AC.3: "Reel" — `.produksi-grid` jadi horizontal scroll-driven film reel (INI YANG PALING SESUAI REQUEST "ANIMASI ROLL FILM")

Section "Produksi yang Pernah Kami Tangani" (`.produksi-grid`/`.produksi-card`/`.produksi-poster`, udah ada, isinya poster produksi asli) diubah dari grid statis jadi **strip horizontal yang bisa di-scroll, dengan poster yang MEMBESAR pas di tengah viewport dan MENGECIL di tepi** — persis efek film yang lewat di depan lensa proyektor, dan sekaligus fungsional (bukan cuma dekorasi, based on real ex-project data yang emang mau ditonjolkan).

1. Ganti CSS `.produksi-grid`:
   ```css
   .produksi-grid {
       display: flex; gap: 20px; overflow-x: auto; scroll-snap-type: x proximity;
       padding: 24px 8px 32px; margin-top: 20px;
   }
   .produksi-card { flex: 0 0 160px; scroll-snap-align: center; }
   ```
2. Animasi scroll-driven (progressive enhancement — HANYA nyala di browser yang support, TIDAK memblokir apapun di browser yang gak support):
   ```css
   @media (prefers-reduced-motion: no-preference) {
       @supports ((animation-timeline: view()) and (animation-range: entry)) {
           @keyframes reel-scale {
               0% { scale: 0.82; opacity: 0.6; }
               50% { scale: 1; opacity: 1; }
               100% { scale: 0.82; opacity: 0.6; }
           }
           .produksi-card {
               animation: reel-scale auto linear both;
               animation-timeline: view(inline);
           }
       }
   }
   ```
3. **Fallback WAJIB** buat browser yang belum support (Firefox, per data `modern-web-guidance`) — JANGAN pakai package `scroll-timeline-polyfill` (dilarang di guide, banyak bug), pakai `IntersectionObserver` manual:
   ```js
   if (!CSS.supports('(animation-timeline: view()) and (animation-range: entry)')
       && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
       const io = new IntersectionObserver((entries) => {
           entries.forEach((entry) => {
               const scale = 0.82 + entry.intersectionRatio * 0.18;
               entry.target.style.scale = scale;
               entry.target.style.opacity = 0.6 + entry.intersectionRatio * 0.4;
           });
       }, { threshold: Array.from({ length: 21 }, (_, i) => i / 20), root: document.querySelector('.produksi-grid') });
       document.querySelectorAll('.produksi-card').forEach((el) => io.observe(el));
   }
   ```
4. Kalau `prefers-reduced-motion: reduce` AKTIF: JANGAN jalanin animasi maupun fallback JS-nya sama sekali — biarin `.produksi-grid` tetap scrollable horizontal biasa tanpa efek scale (poster tetap kebaca semua ukurannya konsisten). Ini bukan opsional, ini wajib per aturan aksesibilitas.
5. Tambah `.film-strip-divider` (AC.2) sebagai border atas-bawah tipis di container `.produksi-grid` biar makin berasa literally "pita film yang diputar".
6. Kalau item poster cuma dikit (1-3), pertimbangin nonaktifin scroll-snap/animasi (gak ada gunanya kalau gak ada yang discroll) — cek dulu `count($proyekSelesai)` di Blade, kalau ≤3 biarin grid statis biasa aja (`display:flex; flex-wrap:wrap; justify-content:center`) tanpa animasi.

### AC.4: Poster hitam-putih → warna pas di-hover

Dari pola American Documentary/Faliro House — reuse target yang sama, `.produksi-poster`:

```css
.produksi-poster { filter: grayscale(100%); transition: filter 0.4s ease; }
.produksi-card:hover .produksi-poster,
.produksi-card:focus-within .produksi-poster { filter: grayscale(0%); }

@media (prefers-reduced-motion: reduce) {
    .produksi-poster { transition: none; }
}
```
(`:focus-within` disertain biar keyboard user yang nge-tab ke card juga dapet efeknya, bukan cuma mouse hover — bagian dari aksesibilitas dasar.)

### AC.5: Label "eyebrow" cinematic sebelum section title

Sentuhan tipografi murah tapi kena — label kecil huruf kapital berspasi lebar sebelum tiap `.section-title`, gaya "menu ala judul film" (pola dari The Greatest Showman).

```css
.section-eyebrow {
    display: block; font-size: 11px; font-weight: 700; letter-spacing: 3px;
    text-transform: uppercase; color: var(--accent-strong); margin-bottom: 6px;
}
```
Tambahin `<span class="section-eyebrow">Sedang Tayang</span>` sebelum `.section-title` "Lowongan Casting Terbuka", dan `<span class="section-eyebrow">Arsip Produksi</span>` sebelum "Produksi yang Pernah Kami Tangani". Teks eyebrow-nya boleh disesuaikan implementer asal tetap pendek & kapital (2-3 kata), yang penting pola visualnya konsisten di semua section-title yang ada.

### AC.6: Spotlight ikutin kursor di `.hero` (efek paling "wah", tapi murni dekoratif)

Pakai pola `interactive-content-reveal` dari `modern-web-guidance` — spotlight yang ngikutin pointer, nyoroti tekstur grain di background hero biar berasa "panggung/studio".

1. Register custom property (taruh di `<style>` awal file, sekali):
   ```css
   @property --spot-size { syntax: "<length-percentage>"; inherits: true; initial-value: 0%; }
   ```
2. Tambah 1 layer div dekoratif di dalam `.hero` (SEBELUM `.hero-content`, sibling-nya, bukan pembungkus):
   ```html
   <div class="hero-spotlight" aria-hidden="true"></div>
   ```
   ```css
   .hero-spotlight {
       position: absolute; inset: 0; pointer-events: none;
       transition: --spot-size 0.3s ease-out;
       mask-image: radial-gradient(circle at var(--spot-x, 50%) var(--spot-y, 50%), black var(--spot-size, 0%), transparent calc(var(--spot-size, 0%) + 15%));
       background: radial-gradient(circle at var(--spot-x, 50%) var(--spot-y, 50%), rgba(255,255,255,0.12), transparent 60%);
   }
   .hero:hover .hero-spotlight { --spot-size: 35%; }
   @media (prefers-reduced-motion: reduce) { .hero-spotlight { transition: none; } }
   ```
   (`.hero` butuh `position: relative` biar `.hero-spotlight` yang `position:absolute` ke-anchor bener — cek CSS `.hero` yang ada sekarang belum punya `position`, tambahin.)
3. JS buat update posisi (`pointermove` di `.hero`, `ResizeObserver` buat rect-nya) — reuse PERSIS pola dari guide, cukup ganti nama variabel `--mouse-x/y` jadi `--spot-x/y` biar gak bentrok penamaan.
4. **WAJIB (bukan opsional) per aturan aksesibilitas dari guide-nya sendiri**: konten asli di `.hero` (judul, tagline, tombol CTA) HARUS tetap kebaca & bisa diklik penuh TANPA butuh spotlight ini — spotlight cuma dekorasi tambahan buat pointer user, `pointer-events:none` di layer-nya WAJIB ada biar klik tembus ke elemen asli di baliknya. Keyboard-only/screen-reader user gak kehilangan apapun kalau spotlight-nya gak pernah nongol buat mereka.

### Verifikasi

- **Reduced motion**: aktifin "Reduce motion" di OS (Windows: Settings > Accessibility > Visual effects; atau emulasi lewat DevTools `Rendering > Emulate CSS media: prefers-reduced-motion`) → SEMUA animasi di atas (reel scale, hover transition, spotlight transition) harus MATI/instant, TAPI konten & fungsinya (scroll poster, baca hero, klik CTA) tetap 100% jalan.
- **Cross-browser**: test reel carousel (AC.3) di Chrome/Edge (native scroll-driven animation harus jalan) DAN di Firefox (harus jalan pakai fallback `IntersectionObserver`, BUKAN diem aja tanpa efek) — buka DevTools Console, gak boleh ada error JS di kedua browser.
- Manual: hover/tab-focus ke poster produksi → grayscale ke warna smooth, keyboard focus juga dapet efek yang sama (`:focus-within`).
- Manual: hero spotlight ngikutin kursor dengan smooth, dan area di luar hover tetap keliatan teks/tombolnya normal (gak keitutup/gak keburamin).
- Manual: cek di LIGHT theme — grain masih ada tapi jelas lebih halus dari dark, film-strip divider keliatan proporsional (warnanya ngikut `--border-color` yang beda tiap tema).
- Manual: resize ke mobile — reel carousel tetep bisa discroll pakai jari (touch), grain & spotlight gak bikin lag/scroll patah-patah (test di HP beneran kalau ada, bukan cuma resize browser desktop).
- Lighthouse/Performance check kalau sempat: pastiin `.film-grain` (SVG data-URI, ukuran kecil, di-`mix-blend-mode`) gak bikin jank pas scroll — kalau kerasa berat, kecilin size SVG-nya (`120x120` → `80x80` misalnya) atau turunin opacity lagi.

---

## Bagian AD: Absensi Korlap — foto lapangan + validasi, Admin Default drop akses

> Ditulis 19 September 2026, oleh manager-session, hasil diskusi bimbingan 19 Sept (`docs/BIMBINGAN-2026-09-19.md`). **WAJIB pakai subagent** (nyentuh RBAC/middleware).

### Konteks

Korlap = `admin_korlap` (role yang UDAH ADA, bukan role baru — jangan bikin migration role baru). `AttendanceController`/`attendances` table juga udah ada (Bagian F lama), tapi sekarang absen dicatat manual oleh siapapun yang punya akses (`role:admin_default,admin_korlap` di route `/absensi`). Keputusan bimbingan: (1) absen HARUS pakai foto dari lokasi, bukan checkbox manual doang; (2) Extras yang ambil foto (selfie langsung dari kamera, opsional — Korlap boleh validasi langsung tanpa nunggu Extras foto, misal buat Extras yang gaptek); (3) `admin_default` DIHAPUS aksesnya dari fitur absen ini — cuma Korlap yang pegang.

### Implementasi

1. **Migration** tambah ke `attendances`: `foto_path` (string, nullable, private disk — Extras yang isi kalau dia sempet foto sendiri), `status_validasi` (enum `menunggu`, `tervalidasi`, default `tervalidasi` — biar existing flow manual-oleh-Korlap tanpa foto Extras tetep jalan tanpa keharusan approval tambahan), `divalidasi_oleh` (nullable, `foreignId` ke `users`), `divalidasi_at` (nullable timestamp).
2. **Endpoint baru buat Extras** upload foto absen sendiri (bukan lewat `AttendanceController` yang punya Admin — bikin endpoint terpisah di controller Extras, misal `Extras\AttendanceSelfieController@store`): input file WAJIB pakai `<input type="file" accept="image/*" capture="environment">` (native HTML, maksa buka kamera langsung bukan galeri — TIDAK butuh library JS tambahan). Simpan sebagai `Attendance` baru dengan `status_validasi = 'menunggu'`, `foto_path` keisi, `dicatat_oleh` = Extras sendiri.
3. **Halaman `/admin/absensi` (Korlap)**: tampilin foto yang Extras submit (`status_validasi = menunggu`) dengan tombol "Validasi" (set `tervalidasi`, `divalidasi_oleh`, `divalidasi_at`) — DAN tetap sediain jalur manual lama (Korlap langsung pilih hadir/tidak tanpa foto, buat Extras yang gak sempet/gak ngerti selfie), yang otomatis `status_validasi = tervalidasi` (self-validated by Korlap, gak perlu approval kedua).
4. **Cabut akses `admin_default`**: `routes/web.php` baris ~196-208, pisah jadi dua middleware group — `/catatan` (field notes) TETAP `role:admin_default,admin_korlap` (RF-35, jangan diubah), `/absensi` + `/absen` ganti jadi `role:admin_korlap` DOANG.
5. Cek sidebar `partials/sidebar-admin_default.blade.php` (atau file sejenis) — hapus link menu "Absensi" kalau ada, biar gak nunjuk ke halaman yang udah di-gate.

### Verifikasi

- Login `admin_default` → coba akses `/admin/absensi` langsung via URL → 403, dan link menu-nya juga udah gak ada.
- Login Extras → submit foto absen (test pakai file upload biasa di environment testing, `capture` cuma ngefek di browser HP asli) → masuk status `menunggu`.
- Login `admin_korlap` → liat entri `menunggu` itu, klik validasi → status berubah, `divalidasi_oleh` keisi.
- Login `admin_korlap` → langsung tandain hadir manual tanpa foto Extras → langsung `tervalidasi`, gak nyangkut di `menunggu`.

---

## Bagian AE: Auto-ban (3x batal proyek terkunci) + fold `cancellations` ke `project_applications`

> Ditulis 19 September 2026, oleh manager-session. **WAJIB pakai subagent** (logic otomatis yang berefek ke akses akun orang — harus di-test ketat).

### Konteks

Keputusan bimbingan: Extras yang batalin proyek yang udah "di-lock" (didefinisikan di sini sebagai status masuk `ProjectApplication::STATUS_LOLOS_KE_ATAS` — `lolos`, `kontrak_ditandatangani`, `selesai_produksi`, sesuai konstanta yang UDAH ADA di model, biar konsisten) sebanyak 3 KALI → auto-ban. Admin/Korlap juga bisa ban manual kapan aja dengan alasan (pola yang sama kayak `alasan_tolak` yang udah ada). Sekalian, manager-session liat tabel `cancellations` (`dibatalkan_oleh`, `alasan`, `is_mendadak`) itu kejadian SEKALI per aplikasi (bukan berulang) — cocok dilebur jadi kolom di `project_applications` langsung, sesuai concern Fakrul soal kebanyakan tabel, DAN mempermudah query hitung "3x batal" (gak perlu join lagi).

### Implementasi

1. **Migration**: tambah ke `project_applications`: `dibatalkan_oleh` (enum `admin`,`extras`, nullable), `alasan_batal` (text, nullable), `is_batal_mendadak` (boolean, nullable), `dibatalkan_at` (timestamp, nullable). Pindahkan data dari `cancellations` ke kolom baru ini via migration data-backfill SEBELUM drop tabel lama. Model `Cancellation` & tabel `cancellations` dihapus SETELAH backfill sukses & semua kode yang refer ke situ (cek `app/Models/Cancellation.php` dan semua controller/test yang pakai) di-update ke kolom baru.
2. **Migration** tambah ke `users`: `alasan_nonaktif` (text, nullable — dipakai baik buat ban manual maupun nonaktifasi biasa, reuse 1 kolom, `/ponytail`) dan `dibanned_at` (timestamp nullable, buat bedain "nonaktif biasa" vs "kena ban" kalau perlu ditampilin beda di UI nanti).
3. **Job/listener**: tiap kali `project_applications.dibatalkan_oleh = 'extras'` ke-set DAN statusnya sebelumnya masuk `STATUS_LOLOS_KE_ATAS`, hitung ulang total pembatalan sejenis milik Extras itu (`ExtrasProfile::whereHas('applications', ...)` — query ke kolom baru, bukan tabel `cancellations` lama). Kalau totalnya udah 3, otomatis set `users.status = 'nonaktif'`, `alasan_nonaktif = 'Otomatis: 3x membatalkan proyek yang sudah terkunci.'`, `dibanned_at = now()`.
4. **UI manual ban**: tombol "Ban" di halaman Kelola Extras (Admin) & halaman Extras di Korlap (kalau ada) — modal isi alasan wajib, pola sama persis kayak modal "Tolak" yang udah ada (`alasan_tolak`).
5. Extras yang `status = nonaktif` gak bisa login (cek middleware/`LoginController` yang udah ada — pasti udah ada guard buat `status`, cek dulu sebelum nambah, jangan duplikasi logic).

### Verifikasi

- Test: bikin Extras dengan 2 pembatalan proyek ber-status lolos ke atas → batal ke-3 → `status` otomatis `nonaktif`, `alasan_nonaktif` keisi teks otomatis.
- Test: pembatalan proyek yang BELUM lolos (misal masih `diajukan_ke_cd`) → TIDAK dihitung ke counter 3x ini (harus proyek yang beneran udah "dikunci").
- Test: ban manual by Admin → `status` nonaktif, `alasan_nonaktif` sesuai yang diisi Admin, `dibanned_at` keisi.
- Test: Extras yang ke-ban gak bisa login lagi (redirect/pesan error yang jelas, bukan 500).
- Regresi: semua test yang lama nyebut `Cancellation`/`cancellations` (`BatalkanAuthorizationTest.php` dan sejenisnya) di-update, harus tetap lolos.

---

## Bagian AF: Akun Admin & Casting Director TIDAK PERNAH dihapus (soft-deactivate doang) + auto-delete Extras yang gak aktif

> Ditulis 19 September 2026, oleh manager-session. **WAJIB pakai subagent** (auth/RBAC, berefek ke data retensi).

### Konteks

Bimbingan tegas: akun Admin & Casting Director **TIDAK BOLEH dihapus permanen SAMA SEKALI**, cuma boleh dinonaktifin — beda dari Extras yang justru butuh **auto-delete** kalau daftar tapi gak pernah lengkapin profil (interaksi minim banget, dianggap sampah data). Manager-session udah verifikasi ke kode: `AdminManagementController::destroy()` SEKARANG masih genuine hard-delete (`$user->delete()`) kalau akun belum kena FK constraint — ini harus difix, bukan cuma "kebanyakan udah aman karena keburu ada riwayat".

### Implementasi

1. **HAPUS TOTAL tombol & route "Hapus" buat role Admin/CD** di `AdminManagementController` — `destroy()` untuk target role `admin_default`/`admin_talco`/`admin_korlap`/`admin_sosmed`/`casting_director` diganti JADI `nonaktifkan()` (set `status = 'nonaktif'`, TIDAK PERNAH panggil `$user->delete()`). Kalau UI sekarang punya tombol "Hapus" buat akun-akun ini, ganti label + behaviour jadi "Nonaktifkan" aja, hapus try/catch FK-exception yang sekarang ada (gak perlu lagi, wong gak pernah delete).
2. **Extras auto-delete**: scheduled command baru (pola sama kayak `ReminderH1ShootingCommand` yang udah ada), jalan harian, cari `User::where('role','extras')->where('status','aktif')` yang: (a) `extrasProfile` masih kosong/gak lengkap (definisi "lengkap" — minimal ada `foto_profil_path` + data diri dasar keisi, sesuaikan sama validasi form yang udah ada) DAN (b) `created_at` lebih dari **30 hari lalu** (angka ini asumsi manager-session, BELUM dikonfirmasi Fakrul — gampang diubah, taruh di config bukan hardcode biar gampang di-tweak) DAN (c) belum pernah punya `project_applications` sama sekali. Kalau 3 syarat itu semua benar → `$user->delete()` (di sini BOLEH hard-delete beneran, karena emang belum ada histori apapun yang perlu di-keep).
3. **Sosialisasi di form registrasi Extras**: tambah 1 baris teks kecil di halaman `register-extras` — "Akun yang belum lengkapi profil dalam 30 hari akan dihapus otomatis." (sesuaikan angka hari kalau poin 2 diubah).

### Verifikasi

- Test: coba `destroy()` akun `admin_default`/`casting_director` apapun kondisinya → HARUS selalu jadi nonaktifkan, TIDAK PERNAH benar-benar hilang dari database (assert row masih ada, `status = nonaktif`).
- Test: Extras baru daftar, profil kosong, `created_at` di-mock 31 hari lalu → command hapus dia. Extras yang SAMA tapi udah pernah 1x apply proyek → TIDAK dihapus walau profil masih kosong (ada histori, harus di-keep).
- Test: Extras yang profilnya kosong tapi baru daftar kemarin (belum 30 hari) → TIDAK dihapus (belum waktunya).

---

## Bagian AG: Portofolio/riwayat kerja akun (Kelola Admin) + gabung Kelola Casting Director

> Ditulis 19 September 2026, oleh manager-session. **WAJIB pakai subagent** (lintas >3 file, RBAC).

### Konteks

Klik nama Admin/CD di "Kelola Admin" (SuperAdmin) → buka halaman detail yang nunjukin riwayat kerja lengkap sejak akun dibuat (proyek yang ditangani, rekap gaji dari `staff_payrolls`). Sekalian, "Kelola Casting Director" (`super-admin/casting-directors/index.blade.php`, sekarang terpisah) DILEBUR ke "Kelola Admin" — CD ditampilin di list yang sama dengan filter/tab role, bukan halaman terpisah lagi.

### Implementasi

1. **Halaman baru**: `super-admin/admins/show.blade.php` (detail per-akun), route `super-admin.admins.show`, controller method baru di `AdminManagementController@show`. Isi: data profil dasar, daftar `admin_project_assignments`/`cd_project_assignments` yang pernah dipegang (tanggal mulai/selesai), rekap `staff_payrolls` (total honor per periode, link ke slip PDF yang udah ada), status akun (aktif/nonaktif + alasan kalau nonaktif dari Bagian AF/AE).
2. **Lebur Kelola CD ke Kelola Admin**: `super-admin/admins/index.blade.php` tambah tab/filter role (`admin_default`, `admin_talco`, `admin_korlap`, `admin_sosmed`, `casting_director` — semua dari 1 query `User` yang sama, filter di frontend/query string, bukan 2 controller terpisah). Rute `super-admin.casting-directors.*` di-redirect/dihapus, semua fungsinya (create/list) pindah ke `AdminManagementController` yang udah ada (tambahin `casting_director` ke `$allowedRoles` yang relevan). Test lama `SuperAdminCdManagementTest.php` disesuaikan (jangan dihapus testnya, update assertion ke route baru).
3. Nama menu sidebar SuperAdmin: ganti "Kelola Casting Director" (yang mau dihapus) — sisa 1 menu "Kelola Admin" aja yang nampung semua sub-role termasuk CD.

### Verifikasi

- SuperAdmin buka Kelola Admin → lihat SEMUA sub-role (4 tipe admin + CD) dalam 1 list, bisa difilter per role.
- Klik nama siapapun → halaman detail muncul, isinya proyek yang pernah ditangani + rekap gaji (buat Admin) — buat CD tampilin proyek yang di-review sebagai gantinya (`cd_project_assignments`).
- Route lama `/super-admin/casting-directors` — pastikan gak 404 nyasar (redirect ke Kelola Admin dengan filter CD, atau minimal gak broken link dari tempat lain yang masih refer ke situ — grep dulu semua `route('super-admin.casting-directors...')` di codebase).

---

## Bagian AH: Grade Admin — kunci 2 bulan sebelum bisa diubah lagi

> Ditulis 19 September 2026, oleh manager-session.

### Konteks

Grade itu URUSAN SELEKSI (Admin kasih rekomendasi grade A/B/C, CD validasi/putuskan lewat `grade_cd` — ini SUDAH benar dan TIDAK berubah, dikonfirmasi Fakrul: **Korlap TIDAK ikut kasih grade**, catatan lapangan Korlap itu hal terpisah lewat `field_notes`, sudah ada). Yang baru: grade Admin gak boleh gonta-ganti sebentar-sebentar buat orang yang sama — sekali dikasih, "mengunci" 2 bulan, biar Extras dapet kesempatan berkembang sebelum di-grade ulang. Karena efeknya harus LINTAS proyek (bukan cuma di 1 aplikasi), grade "yang berlaku" dipindah konsepnya ke level `ExtrasProfile` (persisten), sementara `project_applications.grade` TETAP ADA sebagai SNAPSHOT historis (riwayat: "di proyek ini, gradenya berapa waktu itu").

### Implementasi

1. **Migration**: tambah ke `extras_profiles`: `grade_saat_ini` (enum A/B/C, nullable), `grade_diberikan_at` (timestamp, nullable).
2. **`ApplicantController::setGrade()`** (yang udah ada): SEBELUM nyimpen grade baru, cek `grade_diberikan_at` di profil Extras terkait — kalau belum lewat 2 bulan (`now()->lt($profile->grade_diberikan_at?->addMonths(2))`), TOLAK perubahan (response error jelas: "Grade masih terkunci sampai {tanggal}, gak bisa diubah dulu"), KECUALI ini pemberian grade PERTAMA KALI (`grade_diberikan_at` masih null — Extras baru, belum pernah di-grade, langsung boleh).
3. Kalau lolos pengecekan: update `extras_profiles.grade_saat_ini` + `grade_diberikan_at = now()`, DAN copy nilai yang sama ke `project_applications.grade` (buat aplikasi yang lagi diproses) sebagai snapshot historis — jangan cuma nyimpen di satu tempat.
4. **UI**: form/tombol set grade di halaman applicants Admin — kalau lagi terkunci, tombolnya disable + tooltip/teks "Terkunci sampai {tanggal}" (jangan cuma gagal silent pas submit, kasih tau dari awal).

### Verifikasi

- Extras baru, belum pernah di-grade → Admin kasih grade C → sukses, `grade_diberikan_at` ke-set.
- Extras yang sama apply ke proyek lain minggu depan → Admin coba ubah ke grade A → DITOLAK, masih dalam masa kunci.
- Extras yang sama, setelah lewat 2 bulan dari `grade_diberikan_at` → Admin ubah grade → sukses, `grade_diberikan_at` ke-reset ke waktu baru.
- Cek `project_applications.grade` di proyek-proyek lama tetap nunjukin grade APA ADANYA waktu itu (riwayat), gak ikut berubah retroaktif walau `grade_saat_ini` di profil udah beda sekarang.

---

## Bagian AI: Frame foto "Grid" di Gallery dibikin lebih besar

> Ditulis 19 September 2026, oleh manager-session. **REVISI** dari draf sebelumnya yang salah dengar "grid" jadi "grip" — udah dikoreksi Fakrul. Ini BUKAN kategori/tabel baru, murni soal UKURAN FRAME tampilan.

### Konteks

Salah satu slot di Gallery ("Foto Tambahan" yang udah di-rename jadi "Gallery" di Bagian Z) itu biasanya diisi Extras dengan foto yang UDAH JADI KOLASE sendiri ala grid Instagram (beberapa foto digabung jadi 1 gambar, gaya feed 3x3/2x2 IG). Masalahnya, di tampilan Gallery sekarang (filmstrip horizontal, thumbnail seragam ~140px per Bagian AA), foto kolase kayak gitu keliatan kekecilan/gak kebaca kalau di-frame sama kayak foto tunggal biasa — isinya kepadetan, detail tiap sub-foto ilang. Fix-nya: kasih frame KHUSUS yang lebih gede buat slot ini doang, sisanya tetap ukuran normal.

### Implementasi

1. **Gak ada perubahan skema/tabel sama sekali** — `extras_photos` tetap 4 slot seperti sekarang, cukup TANDAI salah satu slot (default: slot pertama/`urutan = 1`, atau slot yang emang dipakai Extras buat upload grid — pola pemakaiannya di lapangan biar implementer sesuaikan, tanya Fakrul kalau ragu slot mana) sebagai "slot grid".
2. Di `partials/foto-lightbox.blade.php` (`.lightbox-thumbs`), kasih 1 modifier class khusus buat thumbnail slot ini, misal `.lightbox-thumbs img.is-grid { flex: 0 0 280px; width: 280px; }` (dobel dari ukuran normal 140px yang udah ada dari Bagian AA) — sisanya (`img` biasa) tetap ukuran standar. Class `is-grid` ditempel via Blade berdasarkan index slot yang ditandai di poin 1.
3. Di form edit profil Extras (`profile-edit.blade.php`), kasih label kecil di slot itu — "Foto Grid (kolase gaya Instagram)" — biar Extras ngerti slot mana yang cocok buat upload jenis foto ini, dan preview upload-nya di form juga dikasih ukuran box yang lebih lega (bukan kotak sama kecil kayak slot lain) biar konsisten sama tampilan akhirnya di Gallery.
4. Berlaku di SEMUA tempat yang render Gallery lewat partial ini (form edit, lihat profil internal, share publik, modal kandidat CD, applicants Admin) — otomatis konsisten karena 1 partial yang sama.

### Verifikasi

- Manual: upload foto ke slot yang ditandai "grid" → cek di Gallery (halaman manapun) frame-nya keliatan jelas lebih besar dari 3 foto lain di sebelahnya.
- Manual: slot lain (non-grid) tetap ukuran normal, gak ikut kebesaran.
- Cek filmstrip horizontal (Bagian AB) — foto grid yang lebih lebar ini gak bikin filmstrip-nya jadi aneh/kepotong, tetap bisa di-scroll normal.

---

## Bagian AJ: Konsolidasi tabel — gabung `admin_project_assignments`+`cd_project_assignments`, tambah `activity_logs`

> Ditulis 19 September 2026, oleh manager-session. **WAJIB pakai subagent, SANGAT DISARANKAN kerjakan TERAKHIR** (dari semua Bagian AD-AJ) **dan di sesi terpisah sendirian** — ini refactor ke tabel yang udah dipakai banyak fitur existing (dashboard, honor, rekap), risiko regresi paling tinggi di batch ini. **WAJIB jalanin full test suite sebelum DAN sesudah**, bukan cuma test yang berhubungan langsung.

### Konteks

Fakrul concern soal 22 tabel yang bikin ERD/DFD laporan berat, tapi JUGA minta ditambah activity log buat semua tindakan (yang notabene nambah tabel lagi) — dua permintaan yang saling tarik. Solusinya: gabung yang BENERAN redundan (bukan asal gabung), dan buat log-nya 1 tabel POLYMORPHIC generik (nyatet tindakan dari model manapun), bukan tabel log per-modul. Net: -1 tabel dari `cancellations` (udah di Bagian AE), -1 tabel dari gabung assignments di sini, +1 tabel `activity_logs` di sini — hasil akhir tetep lebih sedikit dari 22 sekarang, TANPA korbanin fitur (many-to-many kategori, class per-proyek, dst — itu semua TETAP terpisah, jangan disentuh, itu emang perlu 2 tabel buat fungsinya).

### Implementasi

1. **Migration**: bikin `project_assignments` baru — `casting_project_id`, `user_id`, `assigned_by` (nullable, buat CD yang mungkin gak ada "assigned_by" eksplisit dulu), `status_log` (enum berjalan/selesai, nullable buat row CD lama), `completed_at` (nullable), `unique(casting_project_id, user_id)`. **Migration data-backfill**: copy semua baris dari `admin_project_assignments` DAN `cd_project_assignments` ke tabel baru ini (role penugasan diketahui dari `users.role`, gak perlu kolom diskriminator terpisah).
2. **Grep SEMUA referensi** ke `AdminProjectAssignment`/`CdProjectAssignment` model & tabel lama (controllers, views, tests — banyak, termasuk `CdProjectAssignmentTest.php`, query di `SuperAdmin\DashboardController`, `SuperAdmin\ProjectAssignmentController`, recap/honor calculation) — update SEMUA ke model/tabel baru `ProjectAssignment`. Ini kerjaan paling makan waktu di Bagian ini, jangan buru-buru, cek satu-satu.
3. Drop `admin_project_assignments` & `cd_project_assignments` HANYA SETELAH poin 2 selesai total & full test suite hijau.
4. **Migration baru** `activity_logs`: `id`, `user_id` (siapa yang ngelakuin), `loggable_type`+`loggable_id` (morphs — objek yang kena tindakan, misal `ProjectApplication`, `User`, `CastingProject`), `aksi` (string pendek, misal "grade_diberikan", "akun_dinonaktifkan", "proyek_dibuat"), `keterangan` (text nullable, detail tambahan), `timestamps`.
5. **Jangan pasang logging di SEMUA tempat sekaligus** — mulai dari titik-titik yang paling penting buat audit (perubahan status akun, grade, ban, approve/reject CD, pembayaran) dulu, biar gak jadi kerjaan raksasa dalam 1 komit. Sisanya nyusul di sesi terpisah kalau Fakrul minta diperluas.

### Verifikasi

- **WAJIB**: jalanin `php artisan test` PENUH sebelum mulai (baseline) dan sesudah selesai — bandingin, HARUS 0 test yang tadinya lolos jadi gagal.
- Manual: buka Dashboard SuperAdmin, Rekap Honor, halaman assignment CD/Admin — semua data yang sebelumnya nongol dari `admin_project_assignments`/`cd_project_assignments` HARUS tetap nongol sama persis setelah migrasi ke `project_assignments`.
- Test: trigger beberapa aksi yang udah dipasangin log (misal ban akun, grade Extras) → cek row muncul di `activity_logs` dengan `user_id`/`loggable_type`/`aksi` yang bener.
- Grep ulang seluruh `resources/views` dan `app/` buat nama tabel/model lama — harus 0 hasil kalau migrasi referensinya udah tuntas semua.

## Berikutnya

Kosong, tunggu hasil task ini.
