# UI-GUIDELINES.md — SIM Casting JBTB

> Design system ringkas. Sumber keputusan asli ada di `CLAUDE.md` §7 (deskripsi prototype) & §8 (Decision Log) — file ini distilasi buat di-mention pas generate view, jangan suapin seluruh CLAUDE.md tiap butuh styling.

## Prinsip Utama: "Semua Umur"

User Extras termasuk orang tua/non-teknis. Ini bukan preferensi estetika, ini requirement (RNF-05). Semua keputusan UI harus lolos tes ini duluan sebelum pertimbangan lain.

- Tombol minimal **48px** tinggi (touch target).
- Bahasa Indonesia sederhana, hindari jargon teknis di copy (misal bukan "gagal validasi", tapi "data belum lengkap, cek lagi ya").
- Indikator status pakai warna konsisten di SELURUH sistem: **hijau** = beres/aktif/approved, **kuning** = menunggu/pending, **merah** = batal/tolak/melanggar.
- Focus ring keyboard wajib ada (accessibility dasar).

## Warna & Brand

- **Primary:** hijau `#15803D`
- **Dark/hero:** `#0B1A12`
- **Konten (body, dashboard):** tetap terang/putih — JANGAN pakai dark mode buat area kerja utama, demi keterbacaan semua umur.
- ⚠️ **Perhatian:** hijau brand dan hijau "status sukses" mirip. Kalau dipakai berdampingan (misal badge sukses di atas elemen brand hijau) dan bikin bingung, pisahkan shade (misal brand pakai `#15803D`, status sukses pakai `#22C55E` atau sejenis).
- Font: **Inter**.
- Logo JBTB: skema ijo-hitam.

## Library UI (rencana, belum final — putuskan di Sprint 1)

- **Bootstrap 5** — opsi utama, alasan: banyak template admin gratis (AdminLTE/SB Admin/CoreUI) yang bisa dipercepat, tim udah familiar dari Nobel Akademi.
- **Livewire/Alpine** — alternatif kalau modul tertentu butuh interaktivitas tanpa reload penuh, terutama: negosiasi fee multi-round (RF-16-20, butuh update real-time tiap ronde tawar), canvas signature (RF-26).
- Keputusan boleh **campur** (Bootstrap buat layout umum, Livewire buat komponen interaktif spesifik) — bukan harus all-in satu pilihan. Catat keputusan final di sini begitu Sprint 1 mulai.

## Behavior Rules

### Loading State
Semua aksi yang butuh network (submit form, nego fee, upload bukti transfer) HARUS ada indikator loading — spinner di tombol atau skeleton, JANGAN biarkan tombol terasa "diam" tanpa feedback (user non-teknis bisa klik berkali-kali kalau nggak ada sinyal).

### Error Message
- Selalu bahasa manusia, bukan pesan error teknis Laravel mentah (JANGAN tampilkan stack trace atau "SQLSTATE[...]" ke user manapun).
- Spesifik ke field yang salah, bukan generic "terjadi kesalahan".
- Untuk aksi kritis (submit nego fee, konfirmasi pembayaran, approve/reject CD) — pakai konfirmasi dulu (modal/dialog) sebelum aksi final, karena efeknya tercatat permanen di riwayat.

### Empty State
- Halaman list kosong (belum ada lowongan, belum ada riwayat) HARUS ada ilustrasi/copy yang jelas + CTA relevan, bukan tabel kosong polos.

### Status Badge (konsisten di semua role)
| Status | Warna | Konteks |
|---|---|---|
| Diajukan / Direview | Kuning | project_applications |
| Nego Fee / Deal | Kuning → Hijau saat Deal | fee_negotiations |
| Lolos / Approved | Hijau | cd_reviews |
| Ditolak / Ditolak CD | Merah | |
| Melanggar | Merah | extras_profiles.status |
| Bentrok Jadwal (warning) | Kuning, ikon ⚠️ | non-blocking, RF-13/22 |
| Ditransfer (belum konfirmasi) | Kuning | payments |
| Dikonfirmasi Diterima | Hijau | payments |

## Yang HARUS Dibedakan per Role (jangan reuse layout mentah)

- **Super Admin** — dashboard monitoring/analitik ONLY. JANGAN kasih akses visual ke tombol-tombol operasional (posting lowongan, nego fee) meskipun secara data dia bisa lihat — ini bukan sekadar hide permission, tapi bagian dari desain "Super Admin nggak pegang operasional harian".
- **Talco/Sosmed** — cuma 1 halaman: "Riwayat Kerja & Status Gaji Saya" (read-only). Jangan render sidebar/menu operasional yang nggak relevan buat mereka.
- **Extras** — feed ala media sosial (bukan tabel admin-style), fee tertinggi & urgent di atas.
- **CD** — fokus approve/reject + riwayat booking, JANGAN expose data margin/fee-client mentah (tembok visibilitas, lihat `CLAUDE.md` §5).

## Konvensi Kode (kalau pakai Blade)

Ikuti pola Nobel Akademi kalau relevan:
- CSS scoped per halaman pakai class prefix (misal `.casting-`, `.payroll-`) untuk isolasi style — hindari bentrok dengan Bootstrap global.
- Section yang belum final/CTA belum jelas: comment pakai Blade comment `{{-- --}}`, JANGAN dihapus, biar mudah diaktifkan lagi:
  ```blade
  {{-- TODO-XX: alasan disembunyikan --}}
  {{-- <section ...>...</section> --}}
  ```
