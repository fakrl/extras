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

## Berikutnya

Kosong, tunggu hasil task ini.
