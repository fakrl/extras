# SPEC.md — Menunggu Keputusan 6 Item Session 19

> Terakhir diperbarui 30 Agustus 2026 malam. Task terakhir ("Refresh Palet Warna Opsi B") **selesai** — lihat `DEV-NOTES.md` Session 21.

## Status

- **Refresh palet warna — selesai.** `theme-style.blade.php` diupdate sesuai value final Fakrul. Diverifikasi visual beneran (screenshot 4 halaman × 2 tema via Edge headless, login via curl — tidak ada Playwright/internet akses di sandbox ini). Kontras bagus di kedua tema, `--accent-strong` mode terang sekarang beda shade dari `--accent` (sinyal depth), `--warning` konsisten amber.
- **Temuan tambahan yang di-fix sekalian:** 3 dashboard (`super-admin`, `admin`, `cd`) hardcode mirror JS `textColor` buat Chart.js (nggak bisa baca CSS var langsung) — disamakan ke value `--text-secondary` baru, biar nggak ada teks chart yang ketinggalan warna lama.
- `docs/UI-GUIDELINES.md` disinkronkan.

**Test: 131 passed, 0 regresi (task ini CSS doang, tidak ada test yang terpengaruh).**

## Berikutnya

**6 item dari `DEV-NOTES.md` Session 19 "BUTUH KEPUTUSAN FAKRUL" masih menunggu:**
1. Akun nonaktif/Melanggar tetap bisa login (status ditulis, tidak pernah dibaca sebagai gate)
2. State machine pembayaran ↔ partisipasi tidak terkait (bisa bayar aplikasi yang belum lolos)
3. CD bisa akses invoice & approve/reject lintas proyek (butuh keputusan: model assignment CD↔proyek?)
4. RF-08 (3x cancel mendadak → Melanggar) nyaris unreachable karena syarat status
5. `nama_asli` (KTP) tidak pernah dipakai di kontrak meski `CLAUDE.md` §5 bilang harus muncul
6. RF-35 Korlap: route ada, halaman buat sampai ke situ nggak reachable

Jangan diisi task baru di sini sebelum Fakrul diskusikan urutan/keputusan item-item di atas satu per satu — kecuali Fakrul eksplisit minta lanjut ke hal lain di luar daftar ini.
