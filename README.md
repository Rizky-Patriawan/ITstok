# ITstok

Sistem manajemen inventaris barang IT berbasis web, dibangun dengan PHP native + MySQL/MariaDB. Dirancang untuk penggunaan internal satu kantor — sederhana, langsung jalan, tanpa dependensi build tool atau framework besar.

---

## Cara Menjalankan

**Syarat:** PHP 8.1+, MySQL/MariaDB, dan XAMPP (atau web server lokal lainnya).

1. Salin folder `ITstok` ke dalam `htdocs` XAMPP:
   ```
   /Applications/XAMPP/xamppfiles/htdocs/ITstok/
   ```

2. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.

3. Salin file konfigurasi database:
   ```bash
   cp includes/config.sample.php includes/config.php
   ```
   Lalu isi `config.php` dengan kredensial database kamu (default XAMPP: host `localhost`, user `root`, password kosong).

4. Buka browser dan akses:
   ```
   http://localhost/ITstok/
   ```
   Database dan tabel dibuat otomatis saat pertama kali diakses. Akun admin pertama bisa dibuat langsung dari halaman login (muncul otomatis jika belum ada user).

---

## Struktur File

```
ITstok/
│
├── index.php                  # Redirect ke dashboard / login
├── login.php                  # Halaman login
├── logout.php                 # Handler logout
├── dashboard.php              # Ringkasan stok & aktivitas terbaru
│
├── master_barang.php          # Daftar & edit data barang
├── barang_masuk.php           # Form penerimaan barang (stok lama / barang baru)
├── barang_keluar.php          # Form pengeluaran barang
├── laporan.php                # Laporan pergerakan stok (total masuk/keluar per barang)
├── riwayat.php                # Riwayat semua transaksi masuk & keluar
├── users.php                  # Kelola akun user (admin only)
│
├── barang_baru_save.php       # Handler simpan barang baru + stok awal
├── barang_save.php            # Handler edit metadata barang
├── barang_delete.php          # Handler hapus barang
├── barang_masuk_save.php      # Handler simpan transaksi barang masuk
├── barang_keluar_save.php     # Handler simpan transaksi barang keluar
├── user_save.php              # Handler simpan / edit user
├── user_delete.php            # Handler hapus user
│
├── includes/
│   ├── auth.php               # Session, login, cek role
│   ├── db.php                 # Koneksi PDO MySQL + auto-bootstrap skema
│   ├── functions.php          # Fungsi bantu (query dashboard, format tanggal, dll)
│   ├── layout_start.php       # Header HTML, sidebar, topbar
│   ├── layout_end.php         # Penutup HTML + load app.js
│   ├── config.php             # Kredensial database (tidak ikut git)
│   └── config.sample.php      # Template konfigurasi
│
├── assets/
│   ├── style.css              # Semua styling (dark mode, komponen, layout)
│   └── app.js                 # JavaScript (dropdown, modal, filter urutan)
│
├── schema.sql                 # Skema database (dibaca otomatis oleh db.php)
├── .gitignore
└── README.md
```

---

## Penjelasan Singkat Sistem

ITstok adalah aplikasi inventaris untuk mencatat pergerakan barang IT di kantor. Setiap unit stok yang ada selalu punya jejak transaksi — tidak ada stok yang "muncul begitu saja" tanpa riwayat.

**Alur utama:**

- **Barang baru** didaftarkan lewat menu Barang Masuk → Barang Baru. Kode barang di-generate otomatis dengan format `IT-XXXX` sesuai urutan. Stok awal langsung dicatat sebagai transaksi masuk pertama.
- **Penambahan stok** barang yang sudah ada dicatat lewat Barang Masuk → Stok, beserta tanggal, supplier, dan keterangan.
- **Pengeluaran barang** dicatat lewat menu Barang Keluar, dengan nama penerima, departemen, dan keperluan — supaya setiap barang keluar bisa ditelusuri siapa yang mengambil dan untuk apa.
- **Laporan** menampilkan total barang masuk, total keluar, dan stok saat ini per item, bisa dicari dan diurutkan.
- **Riwayat Transaksi** menampilkan semua transaksi masuk dan keluar secara kronologis.

**Role pengguna:**

| Role | Akses |
|------|-------|
| `admin` | Semua fitur termasuk Kelola User dan hapus barang |
| `user` | Barang Masuk, Barang Keluar, Laporan, Riwayat |

**Database** terdiri dari 4 tabel: `users`, `barang`, `barang_masuk`, `barang_keluar`. Skema lengkap ada di `schema.sql`.