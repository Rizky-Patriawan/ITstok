<?php
/**
 * Handler edit metadata barang (dari modal "Edit Barang" di Master Barang).
 * Menambah barang baru TIDAK lewat sini lagi — itu sekarang lewat
 * Barang Masuk > Barang Baru (lihat barang_baru_save.php), supaya setiap
 * unit stok yang masuk selalu tercatat di ledger barang_masuk.
 * Stok yang sudah berjalan juga tidak bisa diubah lewat sini, cuma lewat
 * Barang Masuk/Keluar, supaya angka stok selalu bisa ditelusuri.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireLogin();
$db = getDb();

function redirectWithError(string $message): void
{
    header('Location: master_barang.php?error=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: master_barang.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$nama = trim($_POST['nama'] ?? '');
$model = trim($_POST['model'] ?? '');
$lokasi = trim($_POST['lokasi'] ?? '');
$kategori = trim($_POST['kategori'] ?? '');
$satuan = trim($_POST['satuan'] ?? '') ?: 'Unit';
$kondisi = in_array($_POST['kondisi'] ?? '', ['baik', 'rusak', 'servis'], true) ? $_POST['kondisi'] : 'baik';

if ($id <= 0) {
    redirectWithError('Barang tidak ditemukan');
}
if ($nama === '') {
    redirectWithError('Nama barang wajib diisi');
}

try {
    $stmt = $db->prepare(
        'UPDATE barang SET nama=?, model=?, lokasi=?, kategori=?, satuan=?, kondisi=? WHERE id=?'
    );
    $stmt->execute([$nama, $model, $lokasi, $kategori, $satuan, $kondisi, $id]);

    header('Location: master_barang.php');
    exit;
} catch (PDOException $ex) {
    $msg = isUniqueConstraintError($ex) ? 'Kode barang sudah digunakan' : 'Terjadi kesalahan server';
    redirectWithError($msg);
}
