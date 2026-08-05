# ITstok v2 — Sistem Manajemen Inventaris IT

Rebuild total dari versi sebelumnya, mengikuti alur kerja dari feedback tim IT
(lihat flowchart) dan desain UI/UX yang di-prototype di Figma. Tetap PHP native
+ MySQL, tanpa framework.

## Perubahan besar dari versi sebelumnya
- **Barang Masuk & Barang Keluar dipisah** jadi 2 tabel ledger insert-only
  (`barang_masuk`, `barang_keluar`), bukan 1 tabel `transactions` yang bisa
  diedit di tempat. Ini lebih sesuai alur kerja gudang beneran: sekali
  transaksi tercatat, riwayatnya permanen — kalau salah, dibatalkan dengan
  transaksi hapus oleh admin (yang otomatis mengoreksi stok), bukan diedit.
- **Field baru**: `lokasi` (gudang), `stok_min` per barang, `departemen`
  (daftar tetap, lihat `includes/functions.php`) dan `petugas` (siapa yang
  input transaksi, dari session).
- Field `condition_status`/kondisi barang dari eksperimen sebelumnya
  **dihapus** — tidak ada di flowchart/prototype baru.

## Cara Menjalankan
1. Salin `includes/config.sample.php` jadi `includes/config.php`, isi
   kredensial MySQL kamu.
2. Jalankan `php -S localhost:8000` dari folder ini, atau taruh di htdocs XAMPP.
3. Buka di browser — database & tabel dibuat otomatis, lalu diarahkan ke
   halaman setup admin pertama.

## Struktur Folder
```
ITstok/
├── schema.sql                  - Skema database (users, barang, barang_masuk, barang_keluar)
├── includes/
│   ├── config.sample.php      - Template kredensial (salin jadi config.php)
│   ├── db.php                  - Koneksi PDO + auto-bootstrap skema
│   ├── auth.php                - Session, login/logout, bootstrap admin
│   ├── functions.php           - Helper: format tanggal, daftar departemen, stats dashboard
│   ├── layout_start.php        - Sidebar + topbar
│   └── layout_end.php
├── assets/
│   ├── style.css
│   └── app.js                  - Modal open/close, konfirmasi hapus
├── login.php / logout.php / index.php
├── dashboard.php                - Ringkasan stok, aktivitas terbaru, stok rendah
├── master_barang.php            - CRUD data barang (tambah/edit via modal)
├── barang_save.php / barang_delete.php
├── barang_masuk.php             - Form penerimaan barang + transaksi terbaru
├── barang_masuk_save.php        - Insert ledger + tambah stok (1 transaksi DB)
├── barang_keluar.php            - Form pengeluaran barang + transaksi terbaru
├── barang_keluar_save.php       - Cek stok cukup, insert ledger, kurangi stok
├── laporan.php                  - Tab Kondisi Stok & Pergerakan Barang
├── riwayat.php                  - Log gabungan masuk+keluar, filter tipe & cari
├── users.php / user_save.php / user_delete.php  - Kelola user (admin only)
└── README.md
```

## Hak Akses
- **Admin**: semua akses + kelola user + hapus barang/transaksi
- **User**: input barang masuk/keluar, kelola master barang (tambah/edit),
  lihat laporan & riwayat — tidak bisa hapus apapun, tidak bisa kelola user

## Catatan Desain
- Stok barang **tidak bisa diedit langsung** di Master Barang (kecuali stok
  awal saat pertama kali barang dibuat) — cuma bisa berubah lewat transaksi
  Barang Masuk/Keluar, supaya angka stok selalu bisa ditelusuri ke riwayat
  transaksinya (audit trail).
- Transaksi Barang Keluar dicegah kalau stok tidak cukup (dicek pakai
  `SELECT ... FOR UPDATE` supaya aman dari race condition).
- Belum ada halaman hapus/edit transaksi individual (Barang Masuk/Keluar) —
  transaksi bersifat permanen sesuai alur kerja gudang. Bisa ditambahkan
  nanti kalau tim IT butuh fitur koreksi.
