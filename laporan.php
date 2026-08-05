<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireLogin();
$db = getDb();

$search = trim($_GET['q'] ?? '');

$totalJenis = (int) $db->query('SELECT COUNT(*) FROM barang')->fetchColumn();
$totalUnit = (int) $db->query('SELECT COALESCE(SUM(stok), 0) FROM barang')->fetchColumn();
$totalMasuk = (int) $db->query('SELECT COUNT(*) FROM barang_masuk')->fetchColumn();
$totalKeluar = (int) $db->query('SELECT COUNT(*) FROM barang_keluar')->fetchColumn();

$params = [];
$where = '';
if ($search !== '') {
    $where = 'WHERE b.nama LIKE ? OR b.kode LIKE ? OR b.model LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}

$stmt = $db->prepare(
    "SELECT b.id, b.kode, b.nama, b.model, b.kategori, b.stok,
            COALESCE(m.total_masuk, 0) AS total_masuk,
            COALESCE(k.total_keluar, 0) AS total_keluar
     FROM barang b
     LEFT JOIN (SELECT barang_id, SUM(jumlah) AS total_masuk FROM barang_masuk GROUP BY barang_id) m ON m.barang_id = b.id
     LEFT JOIN (SELECT barang_id, SUM(jumlah) AS total_keluar FROM barang_keluar GROUP BY barang_id) k ON k.barang_id = b.id
     $where
     ORDER BY b.nama ASC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Laporan Stok';
$activeNav = 'laporan';
require __DIR__ . '/includes/layout_start.php';
?>


<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Jenis Barang</div>
        <div class="stat-value" style="color:var(--primary)"><?= $totalJenis ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Unit Stok</div>
        <div class="stat-value" style="color:#2563eb"><?= $totalUnit ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Transaksi Masuk</div>
        <div class="stat-value" style="color:var(--success)"><?= $totalMasuk ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Transaksi Keluar</div>
        <div class="stat-value" style="color:var(--warning)"><?= $totalKeluar ?></div>
    </div>
</div>

<div class="card">
    <form method="get" class="filter-bar">
        <input type="text" name="q" class="search-box" placeholder="Cari nama, kode, model..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-secondary">Cari</button>
        <span class="text-muted" style="font-size:0.85rem"><?= count($rows) ?> item</span>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th><th>Nama Barang</th><th>Model</th><th>Kategori</th>
                    <th>Total Masuk</th><th>Total Keluar</th><th>Stok Saat Ini</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $b): ?>
                    <tr>
                        <td><?= e($b['kode']) ?></td>
                        <td><?= e($b['nama']) ?></td>
                        <td><?= e($b['model'] ?: '-') ?></td>
                        <td><?= e($b['kategori'] ?: '-') ?></td>
                        <td class="qty-in">+<?= (int) $b['total_masuk'] ?></td>
                        <td class="qty-out">-<?= (int) $b['total_keluar'] ?></td>
                        <td><strong><?= (int) $b['stok'] ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-muted" style="text-align:center;padding:2rem">Belum ada data.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
