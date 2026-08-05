<?php
/**
 * Simpan transaksi Barang Keluar: cek stok cukup, insert ke barang_keluar
 * (ledger, insert-only), lalu kurangi stok berjalan di tabel barang.
 * Pakai SELECT ... FOR UPDATE supaya aman dari race condition kalau ada
 * beberapa transaksi keluar bersamaan untuk barang yang sama.
 */
require_once __DIR__ . '/includes/auth.php';

$user = requireLogin();
$db = getDb();

function redirectWithError(string $message): void
{
    header('Location: barang_keluar.php?error=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: barang_keluar.php');
    exit;
}

$barangId = (int) ($_POST['barang_id'] ?? 0);
$jumlah = (int) ($_POST['jumlah'] ?? 0);
$tanggal = $_POST['tanggal'] ?? '';
$penerima = trim($_POST['penerima'] ?? '');
$departemen = trim($_POST['departemen'] ?? '');
$keperluan = trim($_POST['keperluan'] ?? '');

if ($barangId <= 0) {
    redirectWithError('Pilih barang terlebih dahulu');
}
if ($jumlah <= 0) {
    redirectWithError('Jumlah harus lebih dari 0');
}
if ($tanggal === '' || !DateTime::createFromFormat('Y-m-d', $tanggal)) {
    redirectWithError('Tanggal tidak valid');
}
if ($penerima === '' || $departemen === '' || $keperluan === '') {
    redirectWithError('Nama penerima, departemen, dan keperluan wajib diisi');
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare('SELECT stok, nama FROM barang WHERE id = ? FOR UPDATE');
    $stmt->execute([$barangId]);
    $barang = $stmt->fetch();

    if (!$barang) {
        throw new RuntimeException('Barang tidak ditemukan');
    }
    if ($jumlah > $barang['stok']) {
        throw new RuntimeException(
            "Stok {$barang['nama']} tidak cukup (tersedia {$barang['stok']}, diminta $jumlah)"
        );
    }

    $stmt = $db->prepare(
        'INSERT INTO barang_keluar (barang_id, tanggal, jumlah, penerima, departemen, keperluan, petugas_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$barangId, $tanggal, $jumlah, $penerima, $departemen, $keperluan, $user['id']]);

    $stmt = $db->prepare('UPDATE barang SET stok = stok - ? WHERE id = ?');
    $stmt->execute([$jumlah, $barangId]);

    $db->commit();
    header('Location: barang_keluar.php?success=1');
    exit;
} catch (Throwable $ex) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    redirectWithError($ex->getMessage() ?: 'Terjadi kesalahan server');
}
