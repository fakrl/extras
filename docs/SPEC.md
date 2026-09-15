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

## Berikutnya

Kosong, tunggu hasil task ini.
