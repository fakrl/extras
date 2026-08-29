# SPEC.md — Revision Batch: Nomor WA Extras + Margin UX Warning + HSTS

> Diisi 30 Agustus 2026. RF-10+RF-30 sudah PASS (diverifikasi independen, 81 test, 0 regresi, pint clean — lihat `DEV-NOTES.md` Session 17). **Fakrul off untuk malam ini** — 3 item di batch ini dipilih KHUSUS karena masing-masing sudah 100% jelas scope-nya, tidak butuh keputusan/klarifikasi Fakrul di tengah jalan. Kerjakan berurutan (1 → 2 → 3), commit/catat progress tiap selesai satu item supaya kalau berhenti di tengah, jelas sampai mana.

## Aturan Main Khusus Batch Ini (WAJIB dibaca)

- **Kalau nemu ambiguitas di salah satu item** (bukan bug di luar scope, tapi ketidakjelasan DI DALAM item ini) — JANGAN nunggu jawaban Fakrul (dia lagi tidur). Catat pertanyaannya di `DEV-NOTES.md` sebagai "BUTUH KEPUTUSAN FAKRUL", ambil jalan paling konservatif/reversible yang masuk akal, lanjut ke item berikutnya. Jangan stall.
- Kalau nemu bug di luar scope (auth/RBAC/pembayaran/kontrak) — tetap lapor sesuai `CLAUDE.md` §14.1 (catat, JANGAN fix diam-diam), lanjut ke item berikutnya.
- Selesai tiap item: `php artisan test` full suite harus tetap hijau sebelum lanjut ke item berikutnya. Kalau merah dan tidak bisa diperbaiki, STOP di situ, catat di `DEV-NOTES.md` apa yang gagal dan kenapa, jangan lanjut ke item berikutnya dalam kondisi merah.
- Ini murni revisi/polish — JANGAN nambah fitur baru, JANGAN refactor besar di luar yang diminta tiap item.

---

## Item 1 — Extras Bisa Isi Nomor WA Sendiri (tutup gap Session 8)

**Kenapa:** Sejak WA Gateway dibangun (Session 8), `nomor_wa` cuma bisa diisi manual lewat tinker — belum ada form. Akibatnya SEMUA notifikasi WA ke Extras (konfirmasi apply, hasil seleksi, kontrak, reminder H-1) otomatis skip+log gagal karena `nomor_wa` selalu null. Fitur WA Gateway yang sudah dibangun 5 sesi lalu efektif nggak ngirim apa-apa ke siapa-siapa sampai ini ditutup.

**Detail teknis penting:** `nomor_wa` ada di tabel **`users`**, BUKAN `extras_profiles` (keputusan Session 8: reusable lintas role). `Extras\ProfileController::update()` sekarang cuma nyimpen ke `$profile` (`ExtrasProfile`) — field `nomor_wa` HARUS disimpan terpisah ke `$request->user()->update(['nomor_wa' => ...])`, JANGAN ditambahkan ke `#[Fillable]` `ExtrasProfile` (itu model yang salah).

1. **Migration:** tidak ada (kolom sudah ada dari Session 8).
2. **Controller:** `Extras\ProfileController::update()` — tambah validasi `nomor_wa` (`nullable`, `string`, cukup terima format bebas 08xx/+62xx/62xx — mutator normalisasi di `User::nomorWa()` sudah handle semua format itu sejak Session 8, jangan bikin validasi format ketat baru yang malah nolak input sah). Simpan lewat `$request->user()->update(['nomor_wa' => $data['nomor_wa'] ?? null])`, terpisah dari `$profile->update($dataDisimpan)`.
3. **View:** `resources/views/extras/profile-edit.blade.php` — tambah 1 field input "Nomor WhatsApp" (reuse style input yang sudah ada di form ini, taruh dekat field kontak/alias). Kasih helper text singkat: "Buat notifikasi WhatsApp (apply, hasil seleksi, kontrak, pengingat jadwal)."
4. **Testing:** update profil dengan nomor WA baru → tersimpan di `users.nomor_wa` (bukan di `extras_profiles`), format 08xx/+62xx/62xx semua ternormalisasi sama (test mutator sudah ada di `User`, tinggal test end-to-end lewat controller). Update profil TANPA isi nomor WA (kosong) → tidak error, tetap null. Regresi: field-field `ExtrasProfile` lain yang di-update bareng (alias, rate_card, dst) tidak terganggu.

---

## Item 2 — Margin: Baris "Belum Terklasifikasi" Dikasih Visual Warning

**Kenapa:** Tester independen (Session 17 verifikasi RF-30) flag: baris "Belum terklasifikasi" di rekap margin sekarang render dengan styling abu-abu polos, visual weight SAMA kayak baris breakdown per-kelas normal. Admin yang skim cepat cuma liat angka "Total Margin" nggak ada sinyal visual buat bedain "proyek ini emang kurang untung" vs "ada kandidat yang datanya belum lengkap". Sistem ini positioning-nya "angka keuangan yang bisa dipercaya" — ambiguitas visual kayak gini worth ditutup.

1. **View:** `resources/views/admin/recap/margin.blade.php` — baris "Belum terklasifikasi" (dan total yang kepengaruh olehnya, kalau ada) dikasih treatment warna amber/warning (bukan abu-abu biasa yang dipakai baris breakdown normal) — reuse warna warning yang sudah ada di `theme-style.blade.php` (cek variable CSS yang dipakai badge/alert warning existing di file itu, jangan bikin warna baru). Tambah ikon/label kecil kayak "⚠ Data belum lengkap" di baris itu biar jelas bedanya bukan margin negatif beneran.
2. **Testing:** tidak perlu test baru (perubahan visual/CSS doang) — cukup pastikan `MarginRecapTest` existing (termasuk test "belum terklasifikasi" dari RF-30) tetap PASS setelah perubahan class/style di view (test itu ngecek data, bukan HTML styling, jadi harusnya tidak kepengaruh — kalau ternyata ada test yang assert HTML class tertentu dan jadi gagal, sesuaikan test-nya mengikuti class baru, BUKAN batalkan perubahan style).

---

## Item 3 — HSTS Header (tutup catatan SECURITY-CHECKLIST poin 18/19)

**Kenapa:** `SECURITY-CHECKLIST.md` poin 18 & 19 (Session 15) sama-sama catat "HSTS belum ditambahkan, pertimbangkan nanti". Kecil, konsisten sama pola yang sudah ada (conditional production), aman dikerjakan tanpa keputusan tambahan.

1. **Middleware:** `app/Http/Middleware/SecurityHeaders.php` — tambah header `Strict-Transport-Security` (`max-age=31536000; includeSubDomains`), TAPI cuma set kalau `app()->isProduction()` (sama pola conditional yang dipakai `URL::forceScheme('https')` di `AppServiceProvider`) — JANGAN aktif di local/testing (HSTS di browser lokal bisa nyusahin, browser bakal maksa HTTPS ke domain itu bahkan buat dev lain yang pakai domain sama).
2. **Dokumentasi:** update `SECURITY-CHECKLIST.md` poin 18 & 19 — hapus catatan "HSTS belum ditambahkan".
3. **Testing:** tambah 1 assertion ke `SecurityHardeningTest` existing (atau test baru kecil) — environment `testing` TIDAK ada header HSTS di response (karena bukan production), assert kode `isProduction()`-gated dengan benar (sama pola test force-HTTPS yang sudah ada, tidak perlu test end-to-end production asli).

---

## Catatan

Setelah ketiga item selesai (atau berhenti di salah satu karena merah, sesuai Aturan Main di atas), tulis 1 entri `DEV-NOTES.md` (Session 18) merangkum ketiganya — tidak perlu 3 entri terpisah, ini batch kecil. Kalau ada item yang di-skip/stall karena butuh keputusan Fakrul, tulis jelas di bagian atas entri itu apa yang perlu dijawab pas Fakrul bangun.

SPEC.md ini kemungkinan jadi penutup untuk sementara — sesuai rencana awal, setelah batch ini fokus geser ke polish/revisi kalau ada temuan baru, bukan fitur RF baru lagi kecuali Fakrul putuskan lain.
