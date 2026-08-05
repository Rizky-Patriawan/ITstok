<?php
/**
 * Konfigurasi koneksi database MySQL.
 *
 * CARA PAKAI:
 * 1. Salin file ini jadi "config.php" (di folder yang sama).
 * 2. Isi kredensial database MySQL kamu di bawah.
 * 3. File "config.php" TIDAK ikut ke git (sudah ada di .gitignore).
 */

return [
    'host'     => 'localhost',
    'port'     => 3306,
    'database' => 'itstok',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];
