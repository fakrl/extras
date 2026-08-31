# SPEC.md — Batch 6-Bagian (A-F) Selesai, Menunggu Review Fakrul

> Terakhir diperbarui 31 Agustus 2026. Seluruh batch besar "6 Keputusan Session 19" (Bagian A-F) **SELESAI**, dikerjakan berurutan sesuai instruksi, tiap bagian di-commit terpisah dan (untuk bagian berisiko tinggi) diverifikasi independen oleh tester subagent sebelum lanjut.

## Ringkasan Tiap Bagian

- **Bagian A** — Gate login akun nonaktif/melanggar. Commit `a3e5279`.
- **Bagian B** — Payment gate wajib status lolos+. Commit `b572018`.
- **Bagian C** — RF-08: perluas status yang bisa dibatalkan (`deal`/`lolos`/`kontrak_ditandatangani`). Commit `a361dcf`. **Temuan dilaporkan, TIDAK difix**: `Contract` model tidak punya kolom void/status — kontrak yang sudah ditandatangani lalu dibatalkan tetap terlihat sah di DB.
- **Bagian D** — Nama asli di kontrak + username unik + login via username. Commit `43ee7de`. Bagian paling berisiko (nyentuh login) — diverifikasi 2x (saya + tester independen), fokus regresi login email.
- **Bagian E** — Model assignment CD↔proyek, fix akses lintas-proyek CD. Commit `d0973a1`. Diverifikasi tester termasuk serangan batch-ID-smuggling.
- **Bagian F** — Absensi formal Korlap (fitur baru pasca-proposal, di luar RF manapun). Commit `7add2f7`.

**Total test: 131 → 215 (+84 test baru sepanjang batch), 0 regresi di titik manapun.** Semua commit sudah di-push ke `origin/main`.

## Yang Masih Perlu Diketahui/Diputuskan Fakrul

1. **Contract voiding gap (dari Bagian C)** — kontrak yang sudah ditandatangani lalu dibatalkan tidak punya penanda "sudah tidak berlaku" di database. Perlu keputusan desain (kolom `voided_at`/`status` baru di `contracts`, atau cukup implisit lewat status aplikasi) sebelum di-fix.
2. **Dokumen akademik belum di-update** (bukan saya kerjakan, sesuai batasan — nyentuh dokumen resmi):
   - `BAB-3-DRAFT.md`/`PRD-LITE.md`: RF-33/34 perlu dicatat statusnya diperluas (Bagian C).
   - Bagian F (absensi Korlap) perlu dicatat sebagai fitur baru pasca-proposal — kandidat nomor RF-53, atau ditulis sebagai "penambahan pasca-proposal" sesuai konvensi dokumen ini.
3. **`docs/DEV-NOTES.md` sekarang gitignored** (keputusan Fakrul, dieksekusi sesi sebelumnya) — masih ada dan terisi lengkap per-sesi di working directory, tapi tidak lagi ke-push ke GitHub. Kalau butuh baca riwayat kerja lengkap, buka file itu langsung di lokal.

## Berikutnya

SPEC.md ini kosong dulu — tunggu Fakrul cek hasil batch besar ini (terutama Bagian D yang nyentuh login) sebelum mengisi task baru, sesuai catatan penutup batch asli.
