# Prototype UI — SIM Casting JBTB

Snapshot statis dari desain saat ini (23 September 2026), dibuat buat dikasih ke AI lain yang spesialis UI/UX supaya bisa ngeliat bahasa desain existing sebelum ngasih rekomendasi baru — **bukan** ganti semua 71 halaman Blade, cuma 5 halaman representatif yang nyakup semua pola UI utama sistem.

## Isi

1. `01-dashboard-superadmin.html` — dashboard dengan metric card, chart/kalender, tabel ringkas.
2. `02-kelola-akun.html` — list akun dengan filter, bulk action, dialog.
3. `03-proyek-casting.html` — card grid (entity-card), multi-aksi per kartu.
4. `04-profil-extras.html` — layout 2 kolom (media kiri, info kanan), read-only view.
5. `05-negosiasi-fee.html` — tabel riwayat + form aksi (terima/counter/tolak).

`shared.css` dipakai bareng semua halaman (theme light/dark pakai CSS variable, sama persis kayak `theme-style.blade.php` + `layouts/app.blade.php` di source asli).

## Konteks penting buat AI yang ngerjain redesign

- Ini Laravel Blade app, BUKAN SPA — tiap page reload penuh, form pakai POST/CSRF standar (bukan fetch/AJAX kecuali disebutkan).
- Ada dark/light mode toggle (tombol di topbar), keduanya HARUS didesain — jangan cuma light mode.
- Ada 5 role (Super Admin, Admin, Korlap, Client, Extras) dengan sidebar berbeda per role — desain sidebar harus scalable buat nambah menu di semua role, bukan cuma yang keliatan di sini.
- Mobile: sidebar berubah jadi bottom nav bar (lihat breakpoint `max-width: 860px` di `shared.css`) — Extras kebanyakan akses dari HP, ini krusial.
- Istilah UI sengaja pakai branding sendiri: "Callsheet" = Proyek Casting, "Lineup" = daftar Pendaftar, "Greenlight" = approve/reject, "Reel" = section foto/video. JANGAN diubah ke istilah generik.
- Output yang dibutuhin dari redesign: bukan HTML baru yang bisa langsung dipakai (soalnya bakal ditulis ulang manual ke Blade), tapi cukup arahan desain — palet warna, spacing, tipografi, gaya card/button/table, referensi visual. Semakin konkret/spesifik semakin gampang diterapin balik.

## Yang TIDAK ada di prototype ini (functional gap, sengaja)

Semua data dummy statis, nggak ada JS interaktif beneran (search, form submit, dialog buka/tutup fungsional) selain toggle tema & kalender dummy. Tujuannya murni visual, bukan functional demo.
