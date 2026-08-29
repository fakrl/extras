# CLAUDE.local.md — SIM Casting JBTB

> File ini **tidak masuk git** (gitignore) — panduan kerja lokal. Beda dari `CLAUDE.md` (konteks bisnis, publik ke tim) — ini soal *bagaimana* proses kerjanya jalan.
>
> **Konteks beda dari project lain (Nobel Akademi dkk):** itu solo-dev (fakrul+AI) murni. JBTB ini **project work akademik tim 3 orang** (Fakrul=dev, Imanisa=QA/analisis, Erlina=business liaison+orang dalam mitra) dengan dosen pembimbing yang punya otoritas ubah scope, dan mitra bisnis (Jestika/Erlina di JBTB) yang punya otoritas soal kebutuhan riil. Model kerja di bawah disesuaikan ke struktur ini — bukan copy 1:1 dari project lain.

---

## Model Kerja Agent

Update 29 Agustus 2026: coding sudah jalan sejak 22 Agu (9 sesi, lihat `DEV-NOTES.md`) — jauh lebih cepat dari perkiraan "pasca sidang ±3 Sept" di bawah. Struktur developer/tester/reviewer di bawah **sudah aktif dipakai nyata**, bukan lagi rencana — tiap modul non-trivial (SPEC.md) lewat pipeline penuh, termasuk reviewer yang beberapa kali nemu bug/inkonsistensi nyata sebelum ticket ditutup.

- **Orchestrator = sesi utama** (Fakrul ngobrol langsung dengan Claude/Cowork). Brainstorming, keputusan arsitektur, dan penulisan dokumen akademik terjadi di sini.
- **developer** (subagent) — eksekusi koding per modul, baca `SPEC.md` (per-task, dibuat begitu modul mulai) + `TECH-STACK.md` + `DATABASE-SCHEMA.md`.
- **tester** (subagent) — QA read-only, verifikasi terhadap Black Box Testing plan (RF-xx sebagai skenario, sesuai `BAB-3-DRAFT.md` §3.2.3).
- **reviewer** (subagent) — scope-guard: cek scope creep terhadap `PRD-LITE.md` (Core Features), dan konsistensi terhadap keputusan yang udah di-lock di `OPEN-QUESTIONS-PROPOSAL.md` (jangan sampai implementasi diam-diam balik ke model lama, misal fee-nego via WA lagi).

### Alur Kerja Standar (per modul, pasca sempro)

1. Orchestrator + Fakrul (mewakili tim) diskusi → sepakati 1 modul yang mau dikerjain, sesuai urutan Sprint di `BAB-3-DRAFT.md` §3.2.2.
2. Orchestrator tulis `SPEC.md` untuk modul itu — sumber kebenaran tunggal buat developer & tester.
3. Delegasi ke **developer**.
4. Kalau developer nemu ambiguitas requirement → cek dulu ke `BAB-3-DRAFT.md`/`DATABASE-SCHEMA.md`. Kalau masih ambigu setelah itu → eskalasi ke orchestrator, JANGAN nebak.
5. Selesai → delegasi ke **tester**.
6. Tester GAGAL → balik ke developer (step 3). LULUS → lanjut step 7.
7. Delegasi ke **reviewer** — cek scope creep & konsistensi keputusan.
8. Reviewer ada temuan → balik ke developer. Bersih → lanjut step 9.
9. Orchestrator update `DEV-NOTES.md` (session baru: apa yang dikerjain, keputusan, commit hash).

### Kapan Loop-in Siapa (BEDA dari solo-dev project — ini yang paling penting disesuaikan)

| Jenis keputusan | Loop-in |
|---|---|
| Detail implementasi kecil (nama variabel, struktur loop) | developer putusin sendiri |
| Keputusan arsitektur/struktur folder/pola teknis | Orchestrator + Fakrul, nggak perlu tim lain |
| **Perubahan scope/requirement** (nambah/hapus RF, ubah alur bisnis) | **WAJIB ke Fakrul dulu**, karena itu ngubah `BAB-3-DRAFT.md` yang udah jadi dokumen akademik resmi — nggak boleh direvisi sepihak oleh proses coding |
| **Kebutuhan bisnis yang ambigu** (misal: berapa lama retensi data KTP, format klausul kontrak) | Fakrul tanya ke **Erlina** (posisinya manager JBTB, sumber data lapangan paling valid) |
| **Perubahan yang mengubah dokumen akademik** (Bab 1/2/3, timeline, sistematika) | **WAJIB ke Imanisa/Erlina** juga karena mereka penulis Bab 1/2 — jangan cuma Fakrul yang tahu |
| Kebutuhan yang menyentuh legalitas/kontrak/data sensitif (retensi KTP, klausul hukum) | Eskalasi ke tim bisnis JBTB (Jestika/Erlina), BUKAN diputuskan sendiri oleh developer/AI |

### Kapan Pakai Subagent vs Kerjain Langsung

Beda dari Nobel Akademi (solo-dev, threshold ketat karena weekly token limit) — di sini timeline lebih longgar (2-4 bulan dev pasca-acc) tapi tim lebih kecil buat review, jadi threshold-nya:

- **Subagent WAJIB** untuk: modul yang nyentuh auth/RBAC (7 role kompleks), pembayaran/honor, kontrak/tanda tangan, atau lintas >3 file — karena ini yang paling gampang salah dan paling mahal kalau salah (data sensitif, dokumen legal).
- **Kerjain langsung** untuk: perubahan view/Blade/CSS/copy teks, migration tunggal, fix bug kecil 1-2 file.

## HTML Reference & Konversi (kalau ada, isi begitu ada file referensi visual dari mentor/tim)

Belum ada per 22 Agustus 2026. Kalau nanti Jestika/Erlina kasih referensi visual (branding, layout existing Excel/dokumen), simpan di `_html-reference/` (gitignore) dan catat strategi konversi di sini.

## Mode Kerja (disesuaikan — bukan token-limit ketat kayak Nobel Akademi, tapi tetap efisien)

- **Jawaban ringkas** saat eksekusi teknis — tapi untuk hal yang menyentuh keputusan akademik/proposal, tetap perlu penjelasan reasoning (dosen/penguji bisa tanya "kenapa begini" pas sidang, jadi keputusan harus terdokumentasi alasannya, bukan cuma hasil).
- **Jangan re-investigate** yang sudah ada di `BAB-3-DRAFT.md` / `OPEN-QUESTIONS-PROPOSAL.md` / `CLAUDE.md` — itu udah diverifikasi lewat diskusi tim+dosen.
- **Jangan spawn subagent** buat kerjaan kecil (lihat threshold di atas).
- **Update `DEV-NOTES.md`** tiap sesi kerja modul (bukan cuma task besar) — karena ini juga jadi bukti proses bimbingan/pengerjaan buat laporan akhir nanti (min. 8x bimbingan tercatat, dan riwayat kerja ini bisa jadi lampiran pendukung).
- **Batch tool call** yang nggak saling gantung.

## Catatan Khusus Konteks Akademik

- Semua keputusan teknis yang berdampak ke penulisan Bab 3 (perubahan RF, modul, timeline) HARUS tercermin balik ke `BAB-3-DRAFT.md` — jangan biarkan kode dan dokumen proposal drift.
- Kalau developer/AI nemu solusi teknis yang lebih baik dari yang udah ditulis di proposal (misal pola database yang lebih efisien), catat sebagai temuan ke Fakrul dulu — JANGAN diam-diam implementasi beda dari yang udah "dijual" ke dosen pembimbing, karena itu bisa jadi pertanyaan pas sidang kalau kelihatan beda dari dokumen.
