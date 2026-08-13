<?php
/**
 * Simpan transaksi Barang Masuk untuk barang yang SUDAH ADA (mode "Stok"):
 * insert ke barang_masuk (ledger, insert-only), lalu tambah stok berjalan
 * di tabel barang. Pakai SELECT ... FOR UPDATE supaya aman dari race
 * condition kalau ada beberapa transaksi masuk bersamaan untuk barang yang
 * sama. Untuk mendaftarkan barang baru sekaligus stok awalnya, lihat
 * barang_baru_save.php.
 */
require_once __DIR__ . '/includes/auth.php';

$user = requireLogin();
$db = getDb();

function redirectWithError(string $message): void
{
    header('Location: barang_masuk.php?error=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: barang_masuk.php');
    exit;
}

$barangId = (int) ($_POST['barang_id'] ?? 0);
$jumlah = (int) ($_POST['jumlah'] ?? 0);
$tanggal = $_POST['tanggal'] ?? '';
$supplier = trim($_POST['supplier'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

if ($barangId <= 0) {
    redirectWithError('Pilih barang terlebih dahulu');
}
if ($jumlah <= 0) {
    redirectWithError('Jumlah harus lebih dari 0');
}
if ($tanggal === '' || !DateTime::createFromFormat('Y-m-d', $tanggal)) {
    redirectWithError('Tanggal tidak valid');
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare('SELECT id FROM barang WHERE id = ? FOR UPDATE');
    $stmt->execute([$barangId]);
    if (!$stmt->fetch()) {
        throw new RuntimeException('Barang tidak ditemukan');
    }

    $stmt = $db->prepare(
        'INSERT INTO barang_masuk (barang_id, tanggal, jumlah, supplier, keterangan, petugas_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$barangId, $tanggal, $jumlah, $supplier, $keterangan, $user['id']]);

    $stmt = $db->prepare('UPDATE barang SET stok = stok + ? WHERE id = ?');
    $stmt->execute([$jumlah, $barangId]);

    $db->commit();
    header('Location: barang_masuk.php?success=1');
    exit;
} catch (Throwable $ex) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    redirectWithError($ex->getMessage() ?: 'Terjadi kesalahan server');
}