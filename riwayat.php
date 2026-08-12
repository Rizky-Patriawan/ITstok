<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireLogin();
$db = getDb();

$tipe = $_GET['tipe'] ?? 'semua'; // semua | masuk | keluar
if (!in_array($tipe, ['semua', 'masuk', 'keluar'], true)) {
    $tipe = 'semua';
}
$search = trim($_GET['q'] ?? '');
$like = '%' . $search . '%';

$sqlMasuk =
    "SELECT 'masuk' AS tipe, bm.id, bm.tanggal, b.nama AS nama_barang, b.kode, bm.jumlah,
            bm.supplier AS pihak, bm.keterangan AS catatan, u.username AS petugas,
            bm.created_at AS created_at
     FROM barang_masuk bm
     JOIN barang b ON b.id = bm.barang_id
     LEFT JOIN users u ON u.id = bm.petugas_id";

$sqlKeluar =
    "SELECT 'keluar' AS tipe, bk.id, bk.tanggal, b.nama AS nama_barang, b.kode, bk.jumlah,
            bk.penerima AS pihak, CONCAT(bk.departemen, ' — ', bk.keperluan) AS catatan, u.username AS petugas,
            bk.created_at AS created_at
     FROM barang_keluar bk
     JOIN barang b ON b.id = bk.barang_id
     LEFT JOIN users u ON u.id = bk.petugas_id";

$searchClause = '';
$params = [];
if ($search !== '') {
    $searchClause = " WHERE (b.nama LIKE ? OR b.kode LIKE ?)";
}

if ($tipe === 'masuk') {
    $sql = $sqlMasuk . $searchClause . ' ORDER BY bm.created_at DESC';
    if ($search !== '') { $params = [$like, $like]; }
} elseif ($tipe === 'keluar') {
    $sql = $sqlKeluar . $searchClause . ' ORDER BY bk.created_at DESC';
    if ($search !== '') { $params = [$like, $like]; }
} else {
    $sqlMasukFiltered = $sqlMasuk . $searchClause;
    $sqlKeluarFiltered = $sqlKeluar . $searchClause;
    $sql = "($sqlMasukFiltered) UNION ALL ($sqlKeluarFiltered) ORDER BY created_at DESC";
    if ($search !== '') { $params = [$like, $like, $like, $like]; }
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Riwayat Transaksi';
$activeNav = 'riwayat';
require __DIR__ . '/includes/layout_start.php';
?>


<div class="card">
    <form method="get" class="filter-bar">
        <div class="tab-bar" style="margin-bottom:0">
            <a href="riwayat.php?tipe=semua<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="tab-btn <?= $tipe === 'semua' ? 'active' : '' ?>">Semua</a>
            <a href="riwayat.php?tipe=masuk<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="tab-btn <?= $tipe === 'masuk' ? 'active' : '' ?>">Masuk</a>
            <a href="riwayat.php?tipe=keluar<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="tab-btn <?= $tipe === 'keluar' ? 'active' : '' ?>">Keluar</a>
        </div>
        <input type="hidden" name="tipe" value="<?= e($tipe) ?>">
        <input type="text" name="q" class="search-box" placeholder="Cari nama, kode..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-secondary">Cari</button>
        <span class="text-muted" style="font-size:0.85rem"><?= count($rows) ?> entri</span>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID Transaksi</th><th>Tipe</th><th>Tanggal</th><th>Barang</th>
                    <th>Jml</th><th>Pihak Terkait</th><th>Keterangan</th><th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= $r['tipe'] === 'masuk' ? 'TM' : 'TK' ?>-<?= str_pad($r['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><span class="badge <?= $r['tipe'] === 'masuk' ? 'badge-in' : 'badge-out' ?>"><?= $r['tipe'] === 'masuk' ? 'MASUK' : 'KELUAR' ?></span></td>
                        <td><?= formatTanggal($r['tanggal']) ?></td>
                        <td><?= e($r['nama_barang']) ?><div class="text-muted" style="font-size:0.78rem"><?= e($r['kode']) ?></div></td>
                        <td class="<?= $r['tipe'] === 'masuk' ? 'qty-in' : 'qty-out' ?>"><?= $r['tipe'] === 'masuk' ? '+' : '-' ?><?= (int) $r['jumlah'] ?></td>
                        <td><?= e($r['pihak'] ?: '-') ?></td>
                        <td><?= e($r['catatan'] ?: '-') ?></td>
                        <td><?= e($r['petugas'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-muted" style="text-align:center;padding:2rem">Tidak ada transaksi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_end.php'; ?>