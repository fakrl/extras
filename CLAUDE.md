# CLAUDE.md — SIM Casting JBTB (Root)

> Konteks bisnis & requirement lengkap ada di `docs/CLAUDE.md`. File ini berisi konvensi kerja yang selalu berlaku di sesi coding.

## Cara Kerja Coding

Setiap sesi coding **wajib** pakai dua skill ini:

- **`/ponytail`** — enforces the laziest solution that works: stdlib first, native platform features over deps, shortest diff. Hapus sebelum tambah.
- **`/caveman`** — terse prose. Jawaban pendek, no essays.

Keduanya aktif otomatis lewat hook `SessionStart` & `UserPromptSubmit`. Kalau belum aktif, panggil manual di awal sesi.

## Konvensi Singkat

- Update `docs/DEV-NOTES.md` tiap sesi kerja modul (bukan hanya task besar).
- Perubahan scope/requirement → eskalasi ke Fakrul dulu, jangan langsung koding.
- Subagent WAJIB untuk modul auth/RBAC, pembayaran/honor, kontrak/TTD, atau lintas >3 file. Langsung untuk view/Blade/CSS/bug kecil 1-2 file.
- Hati-hati `<x-component>` di dalam komentar CSS/Blade — Blade ikut parse-nya dan bisa bikin unclosed `if`.

## Dokumen Kunci

| File | Isi |
|---|---|
| `docs/SYSTEM-ARCHITECTURE.md` | Master Arsitektur resmi: HLD, LLD, 5-Role RBAC, 11 Core Tables, Scope Boundary |
| `docs/CLAUDE.md` | Konteks bisnis penuh, aktor, alur, backlog |
| `docs/PRD-LITE.md` | MVP distilasi — cek ini sebelum tambah fitur |
| `docs/DATABASE-SCHEMA.md` | Blueprint 17 tabel |
| `docs/DEV-NOTES.md` | Log sesi dev (update tiap sesi) |
| `docs/SPEC.md` | Template spec per modul aktif |
| `docs/UI-GUIDELINES.md` | Design system, warna, behavior rules |
| `docs/SECURITY-CHECKLIST.md` | Checklist keamanan — cek tiap sprint |
| `docs/BIMBINGAN-2026-09-19.md` | Catatan mentah revisi bimbingan dospem 19 Sept — belum jadi task SPEC, masih dikumpulkan |
