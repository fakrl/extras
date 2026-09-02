# SPEC.md — Selesai, Menunggu Prioritas Berikutnya

> Terakhir diperbarui 2 September 2026.

Bagian A (dokumentasi deviasi WhatsApp Gateway dari proposal) dan Bagian B (RF-59 baru untuk Tambah CD manual + Copy Link Register CD, dipisah dari RF-58 supaya scope-nya tidak kabur) selesai — lihat `DEV-NOTES.md` Session 37. Task dokumentasi murni, tidak ada perubahan kode/test.

## Berikutnya

Kosong, tunggu arahan Fakrul. Satu catatan lama yang masih terbuka (bukan tugas baru, sekadar pengingat dari audit sebelumnya): `PaymentController` bisa jalan dari status `status_partisipasi` apapun tanpa cek kontrak sudah ditandatangani, yang bikin rekap margin (RF-30) berpotensi over-report untuk kasus itu — perlu keputusan Fakrul soal guard status apa yang wajib sebelum pembayaran boleh diproses (lihat `DEV-NOTES.md` Session 19 seputar ini).
