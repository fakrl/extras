# SPEC.md — Revisi UI Kelola Akun (Admin) + Guard Assign-CD + Riwayat CD + Simplifikasi Form + Visual Refresh

> Ditulis 12 September 2026, oleh manager-session, atas laporan Fakrul (screenshot halaman `admin/users/index` dan `cd/reviews/index`).
> Bagian A-D sudah dieksekusi (lihat `DEV-NOTES.md` Session 38 — "SPEC.md: UI Kelola Akun + Guard CD + CSS Checkbox + Riwayat CD"). Bagian E belum dieksekusi (restrukturisasi drill-down Riwayat CD).
> Bagian F-J ditambahkan 14 September 2026, dari diskusi terpisah dengan Fakrul (gabung alias/username, rename Nama Kelas, responsive form, refresh warna dark mode + background, homepage lebih corporate). Semua bagian di bawah ini BELUM dieksekusi — jalankan berurutan (F dulu baru G, dst, karena F mengubah data yang dipakai G/lainnya), atau sekaligus kalau mau, tapi urutan file yang disentuh tetap penting untuk dites satu-satu supaya gampang di-bisect kalau ada regresi.
> **PENTING — WAJIB dikerjakan LEBIH DULU sebelum Bagian F:** lihat "Bagian E.1" di bawah, ditemukan saat audit manager-session 14 September. Jangan skip.

## Bagian E.1: Tutup loop Bagian E (housekeeping WAJIB sebelum lanjut Bagian F)

**Temuan audit (manager-session, 14 September 2026, dari `git status` + baca langsung kode, BUKAN cuma percaya `DEV-NOTES.md`):** Bagian E (drill-down Riwayat CD Proyek→Kandidat, tombol Lihat Profil info lengkap, export Excel & PDF) **SUDAH DIIMPLEMENTASI PENUH DI WORKING TREE** — dibaca langsung `ReviewController.php` (method `riwayat()`, `riwayatProyek()`, `exportRiwayatXlsx()`, `exportRiwayatPdf()` semua ada dan sesuai spec), `routes/web.php`, `resources/views/cd/reviews/riwayat.blade.php` + `riwayat-proyek.blade.php` + `riwayat-pdf.blade.php` — semua match persis sama spec final (drill-down 2 level, modal info lengkap usia/gender/tinggi/dst, dua tombol export). **Kodenya kelihatan benar dan lengkap.** Tapi ada 3 masalah proses:

1. **Semuanya uncommitted** (`git status --short` nunjukkin 9 file modified + 6 file baru yang belum di-`git add`/`commit` sama sekali, sejak 12 September — 2 hari nganggur di working tree).
2. **`docs/DEV-NOTES.md` sudah ketinggalan** — entry terakhir ("Session 40 — Bagian E: Tombol Lihat Profil + Export Excel") cuma dokumentasiin versi FLAT LAMA (sebelum direstrukturisasi jadi drill-down + ditambah PDF + info lengkap). Kode aslinya sudah lebih maju dari yang tercatat.
3. **Test belum lengkap** — cuma ada 1 assertion nyasar (`FeeNegotiationFlowTest.php` baris 257, `Excel::assertDownloaded('riwayat-proyek-export.xlsx')`), TIDAK ADA test buat: grouping Level 1 per proyek benar, scoping Level 2 (CD lain/proyek lain tidak kebawa), modal info lengkap tidak expose `nama_asli`/`nik`/`rate_card`/`rekening`/`tautan_tambahan`, PDF export scoped dengan benar — padahal semua ini eksplisit diminta di verifikasi Bagian E.

**Kenapa ini penting ditutup DULU sebelum Bagian F jalan:** Bagian F (gabung alias/username) akan mengubah/menghapus kolom `alias` yang dipakai di SEMUA file yang barusan disebut di atas (`riwayat-proyek.blade.php`, `CdRiwayatExport.php`, dst). Kalau Bagian F jalan di atas kode yang belum di-commit dan belum ditest, bakal susah dibedain nanti mana perubahan dari Bagian E mana dari Bagian F kalau ada bug, dan resiko kehilangan kerjaan (uncommitted) kalau ada apa-apa.

**Yang perlu dikerjakan (sebelum apapun lagi):**
1. Jalankan `php artisan test` full suite — laporkan hasil sebenarnya (berapa passed/failed), jangan asumsi hijau semua.
2. Kalau ada test yang fail, benerin dulu.
3. Tambah 4 test yang hilang (lihat poin 3 di atas) — persis seperti yang diminta di verifikasi Bagian E versi lama.
4. Update `docs/DEV-NOTES.md` — tulis entry baru yang akurat mendeskripsikan kondisi FINAL (drill-down + info lengkap + Excel & PDF), bukan cuma tempel di atas entry lama yang sudah nggak akurat.
5. `git add` + commit semua ini jadi satu commit yang bersih, message jelas.
6. Baru lanjut Bagian F.

## Bagian A: Revisi UI halaman "Kelola Akun CD & Extras" (`resources/views/admin/users/index.blade.php`)

Target: `App\Http\Controllers\Admin\UserManagementController::index()` + view di atas. TIDAK mengubah `toggleStatus()` — behaviornya tetap sama, cuma tampilannya.

1. **Tombol "Ubah Status" jadi icon-only.** Ganti `<button class="btn btn-sm">Ubah Status</button>` (baris CD maupun Extras) jadi tombol icon pakai Tabler Icons (sudah dipakai di seluruh app, jangan ganti ke icon set lain — lihat `UI-GUIDELINES.md`). Teks "Ubah Status" pindah jadi `title="Ubah Status"` (tooltip/alt), bukan teks yang keliatan. Pilih icon yang masuk akal buat toggle aktif/nonaktif (mis. `ti ti-toggle-left` / `ti ti-power`) — konsisten untuk kedua tabel.

2. **Tambah filter.** Untuk tabel Casting Director dan tabel Extras, tambah filter status (Semua / Aktif / Nonaktif) di atas tiap tabel. Untuk tabel Extras yang bisa sampai puluhan baris, tambah juga search box (filter by nama/alias/email). Karena datanya kecil (CD cuma segelintir, Extras puluhan, bukan ribuan), **cukup filter client-side pakai vanilla JS** (show/hide baris `<tr>`, tanpa reload halaman, tanpa query param baru di controller) — jangan bikin server-side pagination/filtering, itu over-engineering buat ukuran data segini (ref: `/ponytail` — laziest solution yang jalan).

## Bagian B: Guard "belum ada CD ditugaskan" saat ajukan kandidat ke CD

**Konteks temuan (dari audit code, bukan asumsi):** Fakrul lapor kandidat yang statusnya sudah `diajukan_ke_cd` (dari Admin) tidak muncul di halaman Greenlight (`cd/reviews/index`) punya CD tertentu. Setelah baca `Cd\ReviewController::index()` dan `tests/Feature/CdProjectAssignmentTest.php`: filter itu **BUKAN bug** — CD memang cuma boleh lihat kandidat dari proyek yang dia di-assign (`cd_project_assignments`), ini SPEC.md Bagian E lama dan sudah ditest. Jadi kemungkinan besar kasus Fakrul itu proyeknya belum di-assign ke CD manapun, atau di-assign ke CD lain (bukan akun CD yang dipakai buat cek).

**Tapi ada gap nyata yang ditemukan sekalian:** `ProjectApplication::ajukanKeCd()` (dipanggil dari `Admin\FeeNegotiationController::ajukanKeCd()`) TIDAK cek apakah proyek itu sudah punya CD yang di-assign sebelum mengubah status jadi `diajukan_ke_cd`. Akibatnya Admin bisa "mengajukan ke CD" kandidat dari proyek yang belum ada CD-nya sama sekali — kandidat itu lolos ke status `diajukan_ke_cd` tapi tidak akan pernah muncul di Greenlight siapa pun, tanpa pesan error apapun. Ini silent gap yang perlu ditutup terlepas dari apakah ini akar masalah punya Fakrul atau bukan.

**Perbaikan:** di `ProjectApplication::ajukanKeCd()`, sebelum update status, cek `$this->castingProject->cdAssignments()->exists()`. Kalau kosong, lempar `\LogicException` dengan pesan jelas (mis. "Proyek ini belum ada Casting Director yang ditugaskan. Assign CD dulu lewat halaman proyek sebelum mengajukan kandidat."), supaya `FeeNegotiationController::ajukanKeCd()` bisa balikin error yang jelas ke Admin (bukan silent-vanish).

**Test baru:** tambah test di `tests/Feature/` (bisa di `CdProjectAssignmentTest.php` atau bikin baru) yang memverifikasi: proyek tanpa CD assignment → `ajukanKeCd()` melempar exception, status tidak berubah dari `deal`.

## Bagian C: Rapikan ukuran checkbox & radio button (global, semua halaman)

**Konteks:** di halaman Greenlight (`cd/reviews/index.blade.php`), checkbox pemilih kandidat (`#check-all` dan `.app-checkbox`) kelihatan kegedean — soalnya nggak ada satupun CSS custom buat `input[type="checkbox"]`/`input[type="radio"]` di seluruh app (cek `resources/css/app.css` dan `layouts/app.blade.php`), jadi yang kepake ukuran default browser, nggak konsisten sama skala UI lain (font 13-13.5px, badge kecil, dst). Ada juga radio button (di `extras/projects/show.blade.php`) yang kena masalah sama.

**Perbaikan:** tambah rule global di `<style>` block `resources/views/layouts/app.blade.php` (satu tempat, berlaku ke semua halaman termasuk filter baru di Bagian A):

```css
input[type="checkbox"],
input[type="radio"] {
    width: 15px;
    height: 15px;
    accent-color: var(--accent);
    cursor: pointer;
    vertical-align: middle;
}
```

Cukup segini — `accent-color` native CSS, tidak perlu custom SVG/JS (ref: `/ponytail`). Jangan taruh di `resources/css/app.css` (itu cuma Tailwind import + font theme, style komponen lain semua ada di `layouts/app.blade.php`, ikutin konvensi yang sudah ada).

## Bagian D: Menu baru — Riwayat Keputusan CD

**Konteks:** dashboard CD (`cd/dashboard`) sekarang cuma nampilin ringkasan chart (Approve/Reject) dari `CdReview`, tapi CD nggak punya cara buat lihat *daftar* kandidat mana aja yang udah dia approve/reject — cuma ada "Greenlight" (yang perlu direview doang). Fakrul minta menu riwayat baru.

**Perbaikan:**
- Route baru `GET /cd/riwayat` → nama `cd.riwayat`, di `Cd\ReviewController` (method baru `riwayat()`, satu controller sama `index()`/`review()` karena masih domain yang sama — jangan bikin controller terpisah cuma buat 1 halaman).
- Query: `CdReview::where('cd_id', $cdId)->with('projectApplication.extras:id,user_id,alias', 'projectApplication.castingProject:id,nama_produksi')->latest()->get()`. **Tetap jaga tembok visibilitas (CLAUDE.md §5)** — cuma `select()` kolom alias/nama proyek, JANGAN eager-load fee/margin/nama asli/NIK sama kayak `index()` sekarang.
- View baru `resources/views/cd/reviews/riwayat.blade.php`: tabel Alias, Proyek, Keputusan (badge approve=hijau/reject=merah), Tanggal. Filter keputusan (Semua/Approve/Reject) client-side aja (pattern sama kayak Bagian A — dataset kecil, nggak perlu server-side).
- Tambah link "Riwayat" di `partials/sidebar-casting_director.blade.php`, di bawah "Greenlight". Pakai path `/cd/riwayat` (bukan nested di bawah `/cd/reviews`) supaya active-state sidebar (`str_starts_with($route, 'cd/reviews')`) nggak ketuker sama link Greenlight.

## Bagian E: Restrukturisasi `cd/riwayat` jadi drill-down Proyek → Kandidat (SUPERSEDES tabel flat di Bagian D)

**Konteks:** setelah lihat hasil Bagian D, Fakrul minta struktur halaman diubah — JANGAN langsung tampilin flat list semua kandidat (kayak sekarang), tapi dua level: pertama tampilin daftar PROYEK yang pernah CD ini review kandidatnya, baru pas diklik satu proyek, baru muncul daftar kandidat (Extras) yang di-review di proyek itu beserta keputusannya. Alasannya: kalau CD udah banyak proyek, satu tabel flat semua kandidat dari semua proyek jadi susah dibaca — mending dikelompokkan per proyek dulu.

Ini **mengganti** (bukan menambah di atas) tabel flat `cd/reviews/riwayat.blade.php` dari Bagian D. Tombol "Lihat Profil" + export Excel/PDF (yang tadinya direncanakan nempel di tabel flat) sekarang pindah ke level KANDIDAT (halaman detail per-proyek), bukan di level daftar proyek.

**Level 1 — daftar proyek (`GET /cd/riwayat`, tetap nama route `cd.riwayat`, tidak perlu ubah sidebar):**
- `ReviewController::riwayat()` diubah: `CdReview::where('cd_id', $cdId)->with('projectApplication.castingProject:id,nama_produksi')->get()`, di-`groupBy(fn ($r) => $r->projectApplication->casting_project_id)` (pakai Collection, bukan raw SQL groupBy — dataset kecil per-CD, nggak perlu ribet).
- Per proyek tampilin: nama proyek, jumlah approve, jumlah reject, tanggal review terakhir (`max` dari `created_at` grup itu).
- View `cd/reviews/riwayat.blade.php` ditulis ULANG (isi lama dibuang) — tabel/card ringkasan per proyek, tiap baris/card link ke Level 2 (`route('cd.riwayat.show', $castingProjectId)`).
- Proyek dengan 0 review nggak usah muncul (cuma proyek yang CD ini pernah kasih keputusan).

**Level 2 — detail kandidat per proyek (`GET /cd/riwayat/{castingProject}`, route baru `cd.riwayat.show`):**
- Method baru `ReviewController::riwayatProyek(CastingProject $castingProject)`. Query: `CdReview::where('cd_id', $cdId)->whereHas('projectApplication', fn ($q) => $q->where('casting_project_id', $castingProject->id))->with('projectApplication.extras:id,user_id,alias,usia,gender,tinggi_badan,ukuran_baju,warna_kulit,pengalaman,bahasa,foto_profil_path,video_profil_path', 'projectApplication.extras.photos')->latest()->get()`. Kalau CD ini nggak punya review sama sekali di proyek itu (nyasar/coba-coba URL), tampilin state kosong yang sopan — TIDAK PERLU 403 (query udah otomatis nggak bakal expose apapun karena di-scope `cd_id`, aman meskipun cuma halaman kosong).
- View baru `cd/reviews/riwayat-proyek.blade.php`: tabel Alias, Keputusan, Tanggal, + kolom Aksi. Filter keputusan (Semua/Approve/Reject) client-side, sama pola kayak sebelumnya. Breadcrumb/link "← Kembali ke Riwayat" balik ke Level 1.
- **Tombol aksi "Lihat Profil" per baris — info lengkap, bukan cuma foto.** Tombol icon (`ti ti-eye`, `title="Lihat Profil"`, teks bukan visible label, pola sama Bagian A) buka `<dialog>` modal isinya: foto profil, grid foto lain, link video, DITAMBAH atribut fisik CD-safe (`usia`, `gender`, `tinggi_badan`, `ukuran_baju`, `warna_kulit`, `pengalaman`, `bahasa`) — data CURRENT dari `ExtrasProfile` (bukan snapshot, sesuai keputusan Fakrul). **Tembok visibilitas (CLAUDE.md §5) tetap wajib** — `nama_asli`/`nik`/`rate_card`/`rekening`/`tautan_tambahan` TIDAK BOLEH ke-load/ke-render sama sekali. Data taruh di `data-*`/JSON per baris, modal diisi via JS kecil — tidak perlu endpoint AJAX baru.
- **Export Excel & PDF** — ditaruh di Level 2 ini (scope: kandidat proyek itu aja, bukan semua proyek), karena ini level yang paling masuk akal buat "data yang mau di-export" sekarang (satu proyek = satu laporan). Tombol "Ekspor ke Excel" (`route('cd.riwayat.export.xlsx', $castingProject)`) dan "Ekspor ke PDF" (`route('cd.riwayat.export.pdf', $castingProject)`), dua-duanya route baru nested di bawah `{castingProject}`.
  - Excel: method `exportRiwayatXlsx(CastingProject $castingProject)`, `App\Exports\CdRiwayatExport` (pola `ExtrasRecapExport`: `FromCollection`+`WithHeadings`), constructor terima `cd_id` DAN `casting_project_id`. Kolom: Alias, Keputusan, Tanggal.
  - PDF: method `exportRiwayatPdf(CastingProject $castingProject)`, reuse `barryvdh/laravel-dompdf` yang sudah jadi dependency (dipakai `PdfGeneratorService` buat kontrak/invoice/slip honor) — TAPI panggil `Barryvdh\DomPDF\Facade\Pdf::loadView(...)->download(...)` LANGSUNG di controller, jangan lewat `PdfGeneratorService` (itu servicenya khusus generate-lalu-simpan-ke-disk, kebutuhan di sini beda: download langsung, tidak perlu disimpan). View baru `cd/reviews/riwayat-pdf.blade.php`, tabel HTML sederhana (dompdf nggak render CSS modern/flexbox). Kolom sama: Alias, Keputusan, Tanggal.

Kalau nanti Fakrul mau ada juga "export semua proyek sekaligus" di Level 1, itu gampang ditambah belakangan (query-nya tinggal buang filter `casting_project_id`) — untuk sekarang cukup per-proyek dulu sesuai yang diminta.

**Test baru:**
- Level 1: CD cuma lihat proyek yang dia sendiri punya review-nya, dengan hitungan approve/reject yang benar.
- Level 2: CD cuma lihat kandidat proyek itu; CD lain / proyek yang bukan dia-assign nggak kebawa; modal "Lihat Profil" tidak expose `nama_asli`/`nik`/`rate_card`/`rekening`/`tautan_tambahan` (`assertDontSee`, murah, sekalian ditest).
- Export Excel: `Excel::fake()` + `assertDownloaded()` (test export PERTAMA di project ini). Export PDF: assert status 200 + `Content-Type` PDF. Kedua export discoping ke `cd_id` + `casting_project_id` yang benar — data CD lain/proyek lain tidak ikut.

## Bagian F: Gabung Alias + Username jadi satu field "Nama Panggung"

**Keputusan Fakrul:** alias (nama panggung, `extras_profiles.alias`) dan username (`users.username`) digabung jadi SATU field wajib, bukan dua field terpisah. Diskusi lengkap soal kenapa keduanya awalnya dianggap redundant ada di history chat — kesimpulannya: fungsinya emang beda (alias = display non-unique, username = unique+login), tapi Fakrul tetap putuskan digabung demi kesederhanaan form, bukan salah paham soal fungsinya.

**Field yang dipertahankan:** `users.username` — udah unique, udah `alpha_dash`, udah dipakai login (`LoginController`). Ini jadi SATU-SATUNYA sumber nama panggung Extras.

**Yang dihapus:**
- Kolom `extras_profiles.alias` — migration baru `drop column alias` (BUKAN dibiarkan nganggur di DB).
- `ExtrasProfile::aliasTampil()` accessor — sudah nggak relevan, nggak ada lagi 2 nilai buat digabung jadi "Alias (@username)".
- Field `'alias'` dari `$fillable` `ExtrasProfile`.

**Yang diganti — semua referensi `->alias`/`->alias_tampil` ganti ke `->user->username` (grep dulu di seluruh `app/` dan `resources/views/`, bukan cuma yang disebut di bawah, ini cuma yang manager-session temukan sejauh ini):**
- `app/Models/ExtrasProfile.php` (hapus accessor)
- `app/Http/Controllers/Cd/ReviewController.php` (eager-load `extras:id,user_id,alias,...` → buang `alias`, langsung `extras.user:id,username`)
- `resources/views/cd/reviews/index.blade.php`, `riwayat.blade.php`, `riwayat-proyek.blade.php`, `riwayat-pdf.blade.php` (SETELAH Bagian E.1 kelar & commit)
- `resources/views/admin/users/index.blade.php`, `resources/views/admin/projects/applicants.blade.php`, `resources/views/admin/recap/index.blade.php`, `resources/views/super-admin/monitoring.blade.php`
- `resources/views/extras/profile-show.blade.php`, `profile-edit.blade.php`
- `resources/views/contracts/pdf-template.blade.php`, `resources/views/admin/attendance/index.blade.php`
- `app/Exports/CdRiwayatExport.php`, `app/Exports/ExtrasRecapExport.php`
- `database/factories/ExtrasProfileFactory.php` — factory jangan generate `alias` lagi, pastikan `User` factory yang dipakai sekalian generate `username` unik (fake `alpha_dash`-safe, bukan nama dengan spasi)
- SEMUA test file yang bikin data pakai `'alias' => '...'` (banyak, lihat hasil grep — satu-satu perlu disesuaikan supaya bikin `username` di `User` bukan `alias` di `ExtrasProfile`)

**Form `extras/profile-edit.blade.php`:** 2 input ("Nama Panggung / Alias" dan "Username") DIGABUNG jadi SATU input. Label baru: `Nama Panggung`. Validasi TETAP `required|alpha_dash|max:50|unique:users,username` (jangan dilonggarkan — ini yang bikin aman dipakai login). **Placeholder/contoh ganti** dari `"Rina, Bang Jack"` (ada spasi, sekarang invalid) jadi contoh yang `alpha_dash`-friendly, misal `"rina_id"` atau `"bangjack99"`. Field hint diperbarui: jelasin ini dipakai buat tampilan ke CD/Admin DAN buat login alternatif selain email.

**Migrasi data akun lama (WAJIB, jangan asal drop column):** ada akun Extras existing yang `alias` udah keisi tapi `username` masih kosong (kolom ini ditambahkan belakangan, nullable). Sebelum drop `alias`, migration data harus: kalau `username` NULL tapi `alias` ada isinya, isi `username` dari slug `alias` (huruf kecil, spasi→underscore, buang karakter non-alpha_dash), fallback ke pola `extras_{user_id}` kalau hasil slug kosong/sudah kepakai (collision check terhadap unique constraint). **Backup/dry-run dulu di dev DB, laporkan berapa akun yang kena migrasi data ini sebelum jalanin ke DB asli.**

**Test baru/diupdate:** `NamaAsliUsernameTest.php`, `LoginUsernameTest.php`, `ExtrasProfileUpdateTest.php` (sudah ada, base test soal alias/username — pastikan disesuaikan bukan dihapus), tambah test migrasi data (akun lama alias-only ter-backfill username dengan benar, tidak ada collision).

## Bagian G: Rename "Nama Kelas" → "Nama Peran" + putuskan nasib `kriteria`

**Perubahan label (bukan rename kolom/tabel DB — `nama_kelas`/`casting_project_classes` TETAP, cuma teks yang keliatan user yang berubah):**
- `admin/projects/create.blade.php` & `edit.blade.php` (termasuk JS yang nambah row baru): label `Nama Kelas` → `Nama Peran`. Section header `Kelas / Kriteria (minimal satu kelas)` → `Peran yang Dicari (minimal satu peran)`.
- `public/event.blade.php`: section title `Kelas / Kriteria yang Dicari` → `Peran yang Dicari`.
- `extras/projects/show.blade.php`: section title `Kelas / Kriteria` → `Peran yang Dicari`, table header `Kelas` → `Peran`.

**Keputusan soal kolom `kriteria` (orphan — dicek manager-session, kolom ini ADA di DB & model, di-cast array, DITAMPILKAN di `public/event.blade.php` & `extras/projects/show.blade.php`, TAPI TIDAK PERNAH punya input di form manapun — selalu kosong):** dihapus semua referensinya (bukan ditambahin input baru) — konsisten sama alasan Fakrul gabung alias/username minggu ini (simplifikasi, bukan nambah field yang nggak jelas gunanya). Kalau nanti Fakrul berubah pikiran dan mau kriteria jadi field beneran, itu scope terpisah, jangan campur di sini.
- Hapus baris `@if ($class->kriteria) ... @endif` di `public/event.blade.php` & `extras/projects/show.blade.php`.
- Kolom `kriteria` di `casting_project_classes` — TIDAK USAH di-drop dulu (biar migration nggak ganda sama Bagian F di sprint yang sama), cukup dihapus dari `$fillable`/pemakaian aktif. Drop kolom beneran bisa nyusul kapan-kapan kalau mau beres-beres schema sekalian.

**Test:** update test yang assert teks "Kelas"/label lama (`CastingProjectEditTest.php`, `PublicEventLinkTest.php`, dll — grep `Nama Kelas`/`nama_kelas` di test buat nemu semua yang perlu disesuaikan assertion teksnya, BUKAN logic-nya).

## Bagian H: Responsive form Buat/Edit Event Casting Project

**Konteks:** Fakrul minta form `admin/projects/create.blade.php` & `edit.blade.php` (form paling kompleks di app — banyak field: nama produksi, tanggal shooting multi-row, kelas/peran multi-row) dicek & dirapiin biar bagus di 3 breakpoint: mobile (~375px), iPad (~768-834px), desktop (>1024px).

**Yang sudah OK (jangan diubah, konfirmasi manager-session baca CSS-nya):** `.form-row { flex-wrap: wrap }` + `.form-row > div { min-width: 180px }` sudah bikin field-field kelas/peran reflow otomatis di layar sempit. Breakpoint umum app (`480px`/`640px`/`860px` di `layouts/app.blade.php`) sudah standar dipakai di banyak halaman lain.

**Yang perlu benar-benar dicek (bukan diasumsikan benar/salah dari baca kode doang — coba render beneran di 3 lebar viewport, screenshot kalau ada tool buat itu):**
1. Row "Tanggal Shooting" (`tanggal-wrap`, tiap row `display:flex; gap:8px` inline style TANPA `flex-wrap`) — cek apa tombol hapus (`&times;`) kepotong/numpuk di layar sempit.
2. Row "Kelas/Peran" (`kelas-row`) — 3 field + 1 tombol hapus, `align-items:flex-end` — cek posisi tombol hapus pas field-nya wrap ke baris baru (kemungkinan ke-alignment aneh).
3. Tombol "+ Tambah Peran"/"+ Tambah Tanggal" — cek ukuran tap-target masih nyaman di mobile (min 40px tinggi disarankan buat elemen yang di-tap jari).
4. Card/container utama form — cek padding/margin nggak kepotong di layar sempit (375px).

**Perbaikan kalau ada masalah ditemukan:** tambah `flex-wrap: wrap` ke style inline `tanggal-wrap` row kalau perlu, sesuaikan `align-items` `kelas-row` jadi tidak "flex-end" kalau bikin tombol hapus nge-jump aneh pas wrap. Perbaikan sekecil mungkin (ref `/ponytail`) — jangan redesign total kalau cuma butuh beberapa CSS rule tambahan.

**Test:** ini murni visual/CSS, tidak ada logic yang berubah — TIDAK PERLU test otomatis baru, tapi WAJIB laporan manual (deskripsikan apa yang dicek di 3 breakpoint, apa yang diperbaiki kalau ada) di `DEV-NOTES.md`, karena regresi visual tidak kebaca dari `php artisan test` result.

## Bagian I: Refresh warna dark mode + background bertekstur

**Konteks:** Fakrul & tim ngerasa dark mode sekarang (`--bg-page:#0b1310`, `--accent:#22c55e`) kelewat mirip WhatsApp dark mode (kombinasi near-black-hijau + hijau terang itu emang identik sama asosiasi WhatsApp buat kebanyakan orang Indonesia). Juga minta background nggak polos, dikasih tekstur/gradient kayak referensi (screenshot dashboard "Kredensia" — sidebar hijau gelap dengan pola geometris halus, bukan flat 1 warna).

**Palet dark mode baru (diusulkan manager-session, TETAP di keluarga hijau biar konsisten brand, tapi neutral background dijauhin dari hijau-hitam & accent dibikin lebih muted/emerald daripada hijau terang ala WhatsApp):**
```css
:root[data-theme="dark"] {
    --bg-page: #12161a;        /* was #0b1310 — charcoal netral, bukan hijau-hitam */
    --bg-sidebar: #0d1013;     /* was #070c09 */
    --bg-card: #1a1f24;        /* was #131c16 */
    --bg-card-hover: #232a30;  /* was #1a2620 */
    --bg-nav-active: #1c2621;  /* was #17251b — tetap ada hint hijau buat active state */
    --accent: #10b981;         /* was #22c55e — emerald-500, lebih muted */
    --accent-strong: #34d399;  /* was #4ade80 — emerald-400 */
    --accent-on: #052e16;      /* tetap, masih kontras baik */
}
```
Light mode TIDAK diubah (Fakrul cuma keberatan soal dark mode). Kalau Fakrul nggak suka nilai di atas pas dilihat langsung, gampang di-tweak — ini cuma titik awal yang jelas beda dari WhatsApp, bukan keputusan final yang kaku.

**Background bertekstur (app-wide, di `layouts/app.blade.php` `.sidebar` dan di homepage nanti Bagian J):** ganti `background: var(--bg-sidebar)` flat jadi gradient + pola geometris halus pakai CSS murni (SVG data-URI inline sebagai `background-image`, DITUMPUK di atas `background-color` fallback) — TIDAK PERLU asset gambar baru/build step (ref `/ponytail`). Pola: diamond/facet halus, opacity rendah (~4-6%), warna turunan dari `--accent`, supaya nggak ganggu keterbacaan teks/menu di atasnya. Sertakan fallback polos kalau browser lama nggak support data-URI SVG (jarang terjadi, tapi `background-color` di bawahnya tetap ada).

**Test:** visual murni, TIDAK PERLU test otomatis. Manual: cek kontras teks di sidebar tetap oke (WCAG AA minimal) di kedua tema setelah background berubah — jangan sampai pola baru bikin teks susah dibaca.

## Bagian J: Homepage lebih "corporate look"

**Konteks:** homepage sekarang (`welcome.blade.php`) itu single-column sempit (max-width 640px), isinya cuma 1 paragraf + step-bar + 1 teaser angka — kesannya kayak teaser app konsumen, bukan company profile. Fakrul mau lebih "corporate", nggak sesingkat itu. Dari catatan Fakrul (dua pesan yang di-relay: "history project, admin, estras, pendapatan, goals, tercapai") — ini tampaknya outline section STATS yang mau ditambahin: riwayat/jumlah proyek (history), jumlah admin, jumlah extras terdaftar, pendapatan (total yang diproses sistem), goals/target tercapai.

**Perubahan:**
1. **Layout lebih lebar** — bukan 640px single column lagi. Hero section full-width dengan background bertekstur dari Bagian I, konten di dalam container yang lebih lega (misal max-width 1100-1200px), responsive tetap (stack ke 1 kolom di mobile).
2. **Section Stats/Pencapaian baru** — grid angka: Total Proyek (history, `CastingProject::count()` atau yang status selesai), Jumlah Admin (`User::where('role','like','admin_%')->count()`), Jumlah Extras terdaftar (`User::where('role','extras')->count()`), kalau "pendapatan" & "goals tercapai" datanya belum jelas sumbernya (perlu Fakrul konfirmasi definisinya — pendapatan dari mana, goals apa) — taruh placeholder dulu ATAU skip 2 item ini sampai Fakrul kasih definisi konkret, JANGAN ngarang angka.
3. **Section "Tentang JBTB"** — company profile singkat (bisa reuse teks dari proposal/`docs/BAB-3-DRAFT.md` kalau ada yang cocok, atau Fakrul isi sendiri).
4. **Pertahankan** yang sudah ada dan masih relevan: step-bar "Cara Kerja buat Calon Extras", CTA daftar/masuk, modal welcome buat guest.
5. **Footer baru** — kontak/copyright, belum ada sama sekali sekarang.

**PENTING:** poin 2 (angka pendapatan & goals tercapai) BUTUH konfirmasi Fakrul dulu soal sumber datanya sebelum dikerjain — jangan ngarang atau hardcode angka statis, itu bakal kelihatan palsu kalau ketauan pas sidang/demo.

**Test:** `test_homepage_menampilkan_stats_akurat` (angka yang ditampilkan match `count()` asli di DB, bukan hardcode). Test existing homepage (`ExampleTest`, dll) disesuaikan kalau ada assertion teks lama yang sekarang berubah.

## Verifikasi

- Full regression tetap passing — jalankan `php artisan test`, laporkan angka riil (jangan asumsi).
- Bagian E.1 kelar & ter-commit SEBELUM Bagian F mulai.
- Test baru Bagian F: migrasi data akun lama benar (backfill username dari alias, no collision), form gabungan tetap validasi unique+alpha_dash, tidak ada lagi referensi `alias` yang ketinggalan (grep ulang `->alias\b` di akhir, harus nol hasil di luar `git log`/dokumentasi historis).
- Test baru Bagian G: label baru muncul di semua tempat yang disebutkan, tidak ada lagi teks "Kelas"/"Nama Kelas" yang keliatan user (grep blade files).
- Bagian H: laporan manual 3 breakpoint di `DEV-NOTES.md`.
- Bagian I: kontras warna dicek, tidak ada regresi keterbacaan.
- Bagian J: angka stats akurat dari DB, TIDAK ada data pendapatan/goals yang di-hardcode tanpa konfirmasi Fakrul.

## Berikutnya

Kosong, tunggu hasil task ini. Urutan eksekusi: **E.1 → F → G → H → I → J** (E.1 dan F wajib berurutan karena keduanya nyentuh file yang sama; G-J relatif independen satu sama lain kalau mau diparalelkan, tapi tetap disarankan berurutan biar gampang di-review satu-satu).
