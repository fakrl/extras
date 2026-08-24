# SPEC.md — [Nama Modul/Fitur]

> **Template kosong** — belum diisi per 22 Agustus 2026 (belum sempro, belum mulai coding). Isi file ini begitu 1 modul mulai dieksekusi, ikut urutan Sprint di `BAB-3-DRAFT.md` §3.2.2. JANGAN isi lebih dari 1 modul/task sekaligus — kalau butuh spec buat modul lain, buat sesi kerja baru dengan SPEC.md yang di-overwrite untuk task itu (ikuti pola Nobel Akademi: SPEC.md = task AKTIF saat ini, bukan akumulasi semua requirement).

## Goal

[1-3 kalimat: apa yang mau dibangun/diubah, task spesifik — bukan requirement umum. Contoh gaya: "Bangun modul Autentikasi RBAC 7-role sesuai RF-01–RF-05" bukan "Bangun sistem casting".]

## Requirement Terkait

[Sebutkan kode RF-xx / RNF-xx dari `BAB-3-DRAFT.md` yang relevan ke task ini — biar developer/tester tahu batasan lingkupnya persis, jangan generate di luar ini.]

---

## 1. Migration

[Nama file migration, kolom yang ditambah/diubah — rujuk `DATABASE-SCHEMA.md` untuk tabel terkait.]

---

## 2. Model

[File model, `$fillable`, `$casts`, relasi.]

---

## 3. Controller & Routes

[Method, validasi, logic per role.]

---

## 4. Views

[File Blade yang disentuh, elemen UI baru — rujuk `UI-GUIDELINES.md` untuk konvensi styling/behavior.]

---

## 5. Testing Notes (buat tester)

[Skenario Black Box Testing: input → hasil diharapkan, termasuk edge case yang wajib dicek — misal RBAC: pastikan role lain nggak bisa akses endpoint ini.]

---

## Catatan

Setelah task ini selesai (developer→tester→reviewer lulus), pindahkan ringkasannya ke `DEV-NOTES.md` sebagai entri session baru, lalu SPEC.md ini di-overwrite untuk task berikutnya.
