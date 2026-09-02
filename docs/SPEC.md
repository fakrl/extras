# SPEC.md — Selesai, Menunggu Prioritas Berikutnya

> Terakhir diperbarui 2 September 2026.

Bagian A (3 akun test + data terkait dihapus dari DB dev lewat tinker, audit trail lengkap di `DEV-NOTES.md`), Bagian C (modal "+ Tambah Casting Director"), Bagian D (tombol Copy Link Register CD), dan Bagian E (email Extras di Monitoring) selesai — lihat `DEV-NOTES.md` Session 36. Bagian B tidak ada task teknis (klarifikasi saja). 276 test passing, 0 regresi.

## Berikutnya

Kosong, tunggu arahan Fakrul. Satu catatan lama yang masih terbuka (bukan tugas baru, sekadar pengingat dari audit sebelumnya): `PaymentController` bisa jalan dari status `status_partisipasi` apapun tanpa cek kontrak sudah ditandatangani, yang bikin rekap margin (RF-30) berpotensi over-report untuk kasus itu — perlu keputusan Fakrul soal guard status apa yang wajib sebelum pembayaran boleh diproses (lihat `DEV-NOTES.md` Session 19 seputar ini).
