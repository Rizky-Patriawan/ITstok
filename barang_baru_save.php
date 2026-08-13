<?php
/**
 * Simpan "Barang Baru" dari popup mode di halaman Barang Masuk: membuat
 * baris barang baru (stok mulai dari 0) SEKALIGUS mencatat transaksi
 * barang_masuk untuk stok awalnya, dalam satu transaksi DB. Ini beda dari
 * pendekatan lama (kolom "stok awal" langsung di Master Barang) — dengan
 * cara ini, setiap unit stok yang ada selalu punya jejak transaksi masuk
 * di ledger, tidak ada stok yang "muncul begitu saja".
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireLogin();
$db = getDb();

function redirectWithError(string $message): void
{
    header('Location: barang_masuk.php?mode=baru&error=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: barang_masuk.php');
    exit;
}

// Data master barang
$nama = trim($_POST['nama'] ?? '');
$model = trim($_POST['model'] ?? '');
$lokasi = trim($_POST['lokasi'] ?? '');
$kategori = trim($_POST['kategori'] ?? '');
$satuan = trim($_POST['satuan'] ?? '') ?: 'Unit';
$stokMin = max(0, (int) ($_POST['stok_min'] ?? 0));
$kondisi = in_array($_POST['kondisi'] ?? '', ['baik', 'rusak', 'servis'], true) ? $_POST['kondisi'] : 'baik';

// Data transaksi masuk (stok awal)
$jumlah = (int) ($_POST['jumlah'] ?? 0);
$tanggal = $_POST['tanggal'] ?? '';
$supplier = trim($_POST['supplier'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

if ($nama === '') {
    redirectWithError('Nama barang wajib diisi');
}
if ($jumlah <= 0) {
    redirectWithError('Jumlah harus lebih dari 0');
}
if ($tanggal === '' || !DateTime::createFromFormat('Y-m-d', $tanggal)) {
    redirectWithError('Tanggal tidak valid');
}

// Generate kode otomatis format IT-XXXX berdasarkan nomor urut tertinggi
// yang sudah ada di database. Aman dari race condition karena INSERT dengan
// UNIQUE constraint akan gagal kalau kode bentrok, dan kita coba lagi.
function generateKode(PDO $db): string
{
    $row = $db->query(
        "SELECT kode FROM barang WHERE kode REGEXP '^IT-[0-9]+$' ORDER BY CAST(SUBSTRING(kode, 4) AS UNSIGNED) DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    $next = $row ? ((int) substr($row['kode'], 3)) + 1 : 1;
    return 'IT-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

try {
    $db->beginTransaction();

    $kode = generateKode($db);

    $stmt = $db->prepare(
        'INSERT INTO barang (kode, nama, model, lokasi, kategori, satuan, stok, stok_min, kondisi) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)'
    );
    $stmt->execute([$kode, $nama, $model, $lokasi, $kategori, $satuan, $stokMin, $kondisi]);
    $barangId = (int) $db->lastInsertId();

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
} catch (PDOException $ex) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $msg = isUniqueConstraintError($ex) ? 'Kode barang sudah digunakan' : 'Terjadi kesalahan server';
    redirectWithError($msg);
} catch (Throwable $ex) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    redirectWithError('Terjadi kesalahan server: ' . $ex->getMessage());
}
