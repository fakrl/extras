# Catatan Bicara — Sidang Proposal (Sempro) SIM Casting JBTB

> Pendamping slide Canva "PPT SEMPRO PW JBTB". Bukan naskah kaku — pakai sebagai kerangka, sampaikan pakai bahasa sendiri. Tiap slide: poin inti + antisipasi pertanyaan penguji.

---

## Slide 1 — Judul

Buka dengan perkenalan singkat: nama, posisi di tim (lead developer), sebut Erlina (CFO JBTB, sekaligus co-Product Owner) dan Imanisa (analisis kebutuhan & pengujian). Tegaskan satu hal penting di awal: **mitra ini nyata** — PT. JBTB Casting Creative Group, badan hukum resmi, bukan studi kasus hipotetis. Ini penting karena membedakan proposal ini dari proposal berbasis asumsi.

**Fix teks:** `PT.JBTB.CASTING CREATIVE GROUP` → `PT. JBTB CASTING CREATIVE GROUP` (titik jadi spasi, konsisten sama penulisan di slide lain).

---

## Slide 2 — Latar Belakang

**Fix teks (paragraf → 4 poin):**
```
- Industri kreatif Indonesia (film & periklanan) tumbuh signifikan — kontribusi Rp1.280 triliun
  ke PDB nasional (Kemenparekraf, 2023), mendorong kebutuhan talent agency termasuk
  pengelolaan extras/pemeran figuran
- Agensi talent skala menengah-kecil di Indonesia — termasuk PT. JBTB Casting Creative Group
  (50–80 extras aktif, 4–5 proyek/bulan) — masih manual: WhatsApp, Google Spreadsheet,
  Google Docs, Google Slides
- Dampak nyata: admin balas 15–80 pesan WA/hari, seleksi 1 hari–1 minggu, miskomunikasi
  3–7x/bulan, belum ada kontrak kerja tertulis terstandarisasi, komisi 25% dihitung manual
  (rawan salah, kurang transparan)
- → Perlu sistem informasi digital yang terpusat, efisien, dan akuntabel
```

**Cara bawakan:** angka Rp1.280 triliun bukan cuma pemanis — pakai buat jawab pertanyaan "kenapa topik ini penting/relevan", bukan cuma masalah 1 perusahaan kecil. Baru turun ke JBTB sebagai *studi kasus konkret* dari masalah yang lebih besar itu. Kalau ditanya "kenapa nggak pakai software siap pakai (Notion/Trello/dll)?" — jawab: kebutuhan spesifik industri casting (approval berjenjang CD, komisi 25% otomatis, kontrak digital bertanda tangan) nggak ada di tools generik, harus custom.

---

## Slide 3 — Permasalahan

**Fix teks (pisah metode dev dari gap analysis):**
```
- Gap Analysis: data tersebar → perlu basis data terpusat; seleksi manual via grup WA →
  perlu apply mandiri & approval tercatat; kontrak/keuangan manual → perlu digital & otomatis
- Dikembangkan secara iteratif dengan metode Agile/Scrum
```

**Penjelasan tambahan (poin ini yang diminta Fakrul, paling penting disiapin karena biasanya jadi pertanyaan penguji "state of the art"-nya apa):**

Sebelum ke gap analysis, jelasin dulu posisi penelitian ini relatif ke penelitian sebelumnya — ini yang bikin slide ini kuat, bukan cuma daftar masalah:

1. **Penelitian manajemen artis/talent** — biasanya cuma sebatas *katalog/profil* talent (database CV, portofolio), tidak ada alur seleksi berjenjang atau approval dari pihak eksternal (client/PH).
2. **Penelitian e-recruitment** — punya alur apply-seleksi yang matang, tapi didesain buat rekrutmen karyawan tetap, bukan buat kebutuhan *per-proyek* dengan deteksi bentrok jadwal dan negosiasi fee bertingkat yang khas industri casting.
3. **Penelitian notifikasi WA untuk event** — menyelesaikan sisi *komunikasi* (reminder, notifikasi), tapi tidak menyentuh sisi *transaksional*: approval CD, kontrak digital, atau keuangan.

→ **Celahnya**: belum ada penelitian yang menggabungkan ketiganya (seleksi+approval CD, kontrak digital, keuangan otomatis) dalam SATU sistem terintegrasi. Ini yang jadi novelty/kebaruan penelitian — bukan menciptakan modul yang belum pernah ada satu-satu, tapi *integrasinya*.

Baru habis itu masuk ke Gap Analysis (4 pasangan masalah→kebutuhan). Untuk poin metode Agile/Scrum yang sekarang dipisah: jelasin ini keputusan *manajemen proyek*, bukan requirement — dipilih karena kebutuhan sistem berkembang selama bimbingan (awalnya cuma Extras, meluas ke karyawan internal), jadi butuh pendekatan iteratif yang bisa menyesuaikan scope per-sprint tanpa menunggu seluruh sistem selesai dulu.

**Antisipasi pertanyaan:** "sumber gap analysis dari mana?" — dari kombinasi tinjauan pustaka (3 kategori di atas) + hasil wawancara langsung dengan JBTB (Bab II).

---

## Slide 4 — Solusi & Tujuan

**Fix teks:**
```
- Solusi: sistem web berbasis Laravel (metode Agile-Scrum), mencakup pengelolaan Extras DAN
  karyawan internal agensi (Talco/Korlap/Sosmed) secara terintegrasi — seleksi extras+deteksi
  bentrok jadwal, approval CD dengan privasi nama panggung, kontrak digital+keuangan otomatis,
  manajemen karyawan — terintegrasi WhatsApp Gateway
```
(baris "Tujuan utama" di bawahnya tidak perlu diubah, sudah konsisten.)

**Cara bawakan:** jelasin kenapa "privasi nama panggung" — Casting Director hanya melihat alias/nama panggung Extras saat proses approval, BUKAN nama asli/data KTP. Nama asli baru relevan saat kontrak resmi diterbitkan. Ini melindungi data pribadi Extras sebelum ada kepastian deal. Kalau ditanya "kenapa Laravel?" — ekosistem matang, dokumentasi luas, cocok untuk timeline solo-dev 2-4 bulan.

---

## Slide 5 — Manfaat Penelitian

**Cara bawakan (biar nggak kedengaran template generik):**
- **Mitra (JBTB)**: bukan cuma "efisiensi" abstrak — konkretnya, mengurangi waktu admin balas WA manual (15-80 pesan/hari), dan transparansi komisi 25% biar nggak ada sengketa/kecurigaan dari Extras soal potongan fee.
- **Universitas**: jadi referensi konkret buat penelitian digitalisasi UMKM kreatif — bukti empiris metode Agile diterapkan di project nyata dengan mitra industri riil, bukan simulasi.
- **Penulis**: pengalaman end-to-end analisis-desain-implementasi, plus pengalaman nyata integrasi API pihak ketiga (WhatsApp) dan praktik Scrum dengan stakeholder asli (bukan role-play kelas).

---

## Slide 6 — Profil Mitra

**Cara bawakan:** tekankan PT. JBTB itu badan hukum resmi (PT + NIB/OSS) — bukan usaha informal, jadi kredibel sebagai mitra riset. Angka "5 karyawan kelola 50-80 extras aktif" itu rasio beban kerja yang tinggi per kepala — dipakai buat perkuat urgensi (bukan cuma disebutin doang, tapi ditarik balik ke argumen "makanya butuh otomasi").

---

## Slide 7 — Kebutuhan Mitra

**Cara bawakan:** ini slide paling padat (12 kategori) — JANGAN dibacain satu-satu di depan panel, itu bikin ngebosenin. Highlight 3-4 yang paling signifikan/baru aja (misal: approval CD massal, kontrak digital dengan canvas signature, absensi & penggajian otomatis), lalu bilang "detail lengkap 52 kebutuhan fungsional ada di dokumen SRS terlampir". Nonfungsional cukup disebut singkat: enkripsi data sensitif (NIK), performa untuk skala 50-80 extras aktif (bukan skala enterprise, jadi arsitektur MVC standar sudah cukup).

---

## Slide 8 — Metode Pengembangan

**Cara bawakan:** jelasin KENAPA Scrum dipilih — bukan cuma "karena metodologi populer", tapi karena kebutuhan sistem ini sendiri BERKEMBANG selama bimbingan (dari cuma Extras jadi juga karyawan internal) — bukti nyata kenapa pendekatan iteratif cocok, bukan waterfall. Product Owner ganda (Jestika sebagai CEO/kebutuhan bisnis, Erlina sebagai CFO/kebutuhan keuangan) karena dua concern itu punya sudut pandang beda yang perlu divalidasi terpisah. "52 RF + 9 RNF" jadi Product Backlog awal — sebutkan ini angka DI PROPOSAL; kalau penguji tanya progres aktual, boleh disebut sudah ada penambahan pasca-proposal (RF-53 s/d RF-59) hasil bimbingan lanjutan/pengembangan — tapi itu bagian sidang akhir nanti, bukan fokus sempro ini.

---

## Slide 9 — Penutup

**Cara bawakan:** ringkas 3 gap utama lagi (data terpusat, seleksi terdokumentasi, kontrak+keuangan otomatis), lalu tutup dengan klaim kontribusi yang lebih luas: sistem ini didesain generik cukup untuk jadi model buat agensi talent sejenis lain (bukan cuma khusus JBTB) — ini nambah nilai kontribusi akademis (generalisasi), bukan cuma proyek jasa custom buat 1 klien.

---

## Slide 10 — Terima Kasih

Tutup formal + buka sesi tanya jawab: *"Kami terbuka untuk masukan dan pertanyaan dari Bapak/Ibu dosen penguji."*

---

## Pertanyaan yang Kemungkinan Muncul (siap-siap)

- **"Kenapa WhatsApp Gateway, bukan API resmi WhatsApp Business?"** — proposal ini masih merujuk opsi gateway berbayar (sesuai draft), tapi kalau ditanya soal implementasi aktual: jelaskan singkat ada evaluasi ulang di tahap pengembangan (gateway berbayar ternyata sama-sama "unofficial", tidak ada keuntungan konkret) — tapi ini detail sidang akhir, jangan diungkit duluan di sempro kecuali ditanya.
- **"Kenapa cuma Black Box Testing, bukan White Box juga?"** — karena fokus penelitian pada validasi fungsional dari sisi pengguna (7 aktor), bukan pada efisiensi algoritma internal.
- **"Data 50-80 extras dan komisi 25% itu sumbernya dari mana?"** — hasil wawancara langsung dengan pihak JBTB (Bab II, metode pengumpulan data).
