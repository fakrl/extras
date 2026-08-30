# SPEC.md — Menunggu Keputusan 5 Item Session 19

> Terakhir diperbarui 30 Agustus 2026 pagi. Task terakhir ("Fix Nego-Fee Gate + Beres-beres Kecil") **selesai** — lihat `DEV-NOTES.md` Session 20.

## Status

- **Item 1 (fix nego-fee gate) — selesai.** `ProjectApplication::pastikanMasihBisaNego()` sekarang blokir nego di status `kontrak_ditandatangani`/`selesai_produksi`/`dibatalkan` juga. 24 test baru, dibuktikan real (revert-konfirmasi gagal-restore).
- **Item 2 (stop-track DEV-NOTES.md + CLAUDE.local.md) — selesai.** Kedua file tetap ada di disk, cuma nggak lagi ke-track git mulai commit `1d86be1`.
- **Temuan tambahan (dicatat, belum diputuskan):** `ajukanAwal()`/`ajukanFeeAwal()` nggak pernah panggil `pastikanMasihBisaNego()` — nggak reachable lewat alur normal (dianalisis di `DEV-NOTES.md` Session 20), tapi bukan defense-in-depth. Bisa dibereskan sekalian kapan-kapan (satu baris) kalau Fakrul mau.

**Test: 131 passed, 0 regresi.**

## Berikutnya

**6 item dari `DEV-NOTES.md` Session 19 "BUTUH KEPUTUSAN FAKRUL" masih menunggu** (item nego-fee gate sudah selesai di task ini, jadi tinggal 6 dari 7 semula):
1. Akun nonaktif/Melanggar tetap bisa login (status ditulis, tidak pernah dibaca sebagai gate)
2. State machine pembayaran ↔ partisipasi tidak terkait (bisa bayar aplikasi yang belum lolos)
3. CD bisa akses invoice & approve/reject lintas proyek (butuh keputusan: model assignment CD↔proyek?)
4. RF-08 (3x cancel mendadak → Melanggar) nyaris unreachable karena syarat status
5. `nama_asli` (KTP) tidak pernah dipakai di kontrak meski `CLAUDE.md` §5 bilang harus muncul
6. RF-35 Korlap: route ada, halaman buat sampai ke situ nggak reachable

Jangan diisi task baru di sini sebelum Fakrul diskusikan urutan/keputusan item-item di atas satu per satu — kecuali Fakrul eksplisit minta lanjut ke hal lain di luar daftar ini.
