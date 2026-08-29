# SPEC.md — Menunggu Prioritas Berikutnya

> ⚠️ **Cek dulu: repo GitHub `fakrl/extras` PUBLIC**, dan `docs/DEV-NOTES.md`/`docs/CLAUDE.local.md` (harusnya gitignored per catatan di file masing-masing) ke-track & ke-push sejak Session 2 — detail lengkap di baris paling atas `DEV-NOTES.md` Session 19. Tidak ada credential bocor, tapi worth diputuskan mau ditutup gimana.

> Terakhir diperbarui 30 Agustus 2026 (dini hari, sesi semalaman selagi Fakrul tidur). Batch revisi sebelumnya (nomor WA Extras, warning visual margin, HSTS) **selesai** — lihat `DEV-NOTES.md` Session 18.

## Status Semalam

Setelah batch revisi selesai, tidak ada task baru tertulis di sini, jadi dilanjutkan dengan audit menyeluruh (bukan per-ticket) — pertama kali sejak 18 sesi. Hasil lengkap di `DEV-NOTES.md` Session 19:

- **3 bug nyata diperbaiki** (terverifikasi aman, sudah di-commit+push): mass-assignment 6 model (`*_at` columns), margin/budget bocor ke Extras, WhatsApp dispatch di luar try/catch.
- **Ponytail cleanup diterapkan**: dedup method/status-list/CSS, konsolidasi media upload + NIK hash.
- **Gap test coverage ditutup**: 11 test baru buat skenario yang sempat di-defer beberapa sesi lalu.
- **7 gap butuh keputusan Fakrul** — BELUM disentuh, tercatat lengkap di `DEV-NOTES.md` Session 19 bagian "BUTUH KEPUTUSAN FAKRUL". Ringkas:
  1. Akun nonaktif/Melanggar tetap bisa login (status ditulis, tidak pernah dibaca sebagai gate)
  2. State machine pembayaran ↔ partisipasi tidak terkait (bisa bayar aplikasi yang belum lolos)
  3. Nego fee bisa dibuka lagi setelah kontrak ditandatangani (kemungkinan fix simpel, tapi nyentuh kontrak — ditahan sesuai §14.1)
  4. CD bisa akses invoice & approve/reject lintas proyek (butuh keputusan: model assignment CD↔proyek?)
  5. RF-08 (3x cancel mendadak → Melanggar) nyaris unreachable karena syarat status
  6. `nama_asli` (KTP) tidak pernah dipakai di kontrak meski `CLAUDE.md` §5 bilang harus muncul
  7. RF-35 Korlap: route ada, halaman buat sampai ke situ nggak reachable

**Total test: 107 passed, 0 regresi, semuanya sudah di-commit & push ke `origin/main` (5 commit semalam: checkpoint besar Session 8-15 + RF-10/RF-30 + revisi batch + audit-fix + ponytail cleanup + test-coverage).**

## Yang Perlu Fakrul Putuskan Pagi Ini

Baca `DEV-NOTES.md` Session 19 lengkap dulu (bagian "BUTUH KEPUTUSAN FAKRUL"), lalu tulis SPEC.md berikutnya berdasarkan urutan prioritas yang dipilih. Kandidat lain di luar 7 gap di atas (belum urgent): RF-30 v2 kalau mau breakdown per-kepala lebih presisi lagi, RF-38 visibility ke CD, fitur "Apresiasi" Extras, Inbox re-book CD — semua masih menunggu keputusan/scope yang jelas sebelum dikerjakan.
