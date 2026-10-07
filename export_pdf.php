<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireLogin();
$db = getDb();

$fromDate = $_GET['from'] ?? '';
$toDate = $_GET['to'] ?? '';

if ($fromDate === '' || $toDate === '') {
    $fromDate = date('Y-m-01');
    $toDate = date('Y-m-d');
}

if (!DateTime::createFromFormat('Y-m-d', $fromDate) || !DateTime::createFromFormat('Y-m-d', $toDate)) {
    die('Tanggal tidak valid.');
}

$stmt = $db->prepare(
    "SELECT b.kode, b.nama, b.model, b.kategori, b.satuan,
            COALESCE(m.total_masuk, 0) AS total_masuk,
            COALESCE(k.total_keluar, 0) AS total_keluar,
            b.stok AS stok_akhir
     FROM barang b
     LEFT JOIN (
         SELECT barang_id, SUM(jumlah) AS total_masuk
         FROM barang_masuk
         WHERE tanggal BETWEEN ? AND ?
         GROUP BY barang_id
     ) m ON m.barang_id = b.id
     LEFT JOIN (
         SELECT barang_id, SUM(jumlah) AS total_keluar
         FROM barang_keluar
         WHERE tanggal BETWEEN ? AND ?
         GROUP BY barang_id
     ) k ON k.barang_id = b.id
     ORDER BY b.nama ASC"
);
$stmt->execute([$fromDate, $toDate, $fromDate, $toDate]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalMasuk  = array_sum(array_column($rows, 'total_masuk'));
$totalKeluar = array_sum(array_column($rows, 'total_keluar'));
$totalStok   = array_sum(array_column($rows, 'stok_akhir'));

$periodLabel = formatTanggal($fromDate) . ' s/d ' . formatTanggal($toDate);

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * { font-family: sans-serif; }
  body { font-size: 11px; color: #1e293b; margin: 0; padding: 0; }
  .header { margin-bottom: 16px; border-bottom: 2px solid #1e293b; padding-bottom: 8px; }
  .header h1 { font-size: 15px; margin: 0 0 2px 0; }
  .header .sub { font-size: 10px; color: #64748b; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 5px 8px; text-align: left; }
  th { background: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-weight: 600; }
  td { border-bottom: 1px solid #e2e8f0; }
  .right { text-align: right; }
  .in { color: #16a34a; }
  .out { color: #dc2626; }
  tfoot td { font-weight: bold; border-top: 2px solid #1e293b; background: #f8fafc; }
</style>
</head>
<body>
<div class="header">
    <h1>ITstok &mdash; Laporan Pergerakan Stok</h1>
    <div class="sub">Periode: <?= $periodLabel ?> &nbsp;|&nbsp; Dicetak oleh: <?= htmlspecialchars($user['username']) ?> &nbsp;|&nbsp; <?= date('d M Y, H:i') ?> WIB</div>
</div>
<table>
    <thead>
        <tr>
            <th>Kode</th>
            <th>Nama Barang</th>
            <th>Model</th>
            <th>Kategori</th>
            <th>Satuan</th>
            <th class="right">Masuk</th>
            <th class="right">Keluar</th>
            <th class="right">Stok Saat Ini</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['kode']) ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= htmlspecialchars($r['model'] ?: '-') ?></td>
            <td><?= htmlspecialchars($r['kategori'] ?: '-') ?></td>
            <td><?= htmlspecialchars($r['satuan']) ?></td>
            <td class="right in">+<?= (int) $r['total_masuk'] ?></td>
            <td class="right out">-<?= (int) $r['total_keluar'] ?></td>
            <td class="right"><strong><?= (int) $r['stok_akhir'] ?></strong></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
        <tr><td colspan="8" style="text-align:center;padding:1rem;color:#94a3b8">Tidak ada data pada periode ini.</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">TOTAL</td>
            <td class="right in">+<?= $totalMasuk ?></td>
            <td class="right out">-<?= $totalKeluar ?></td>
            <td class="right"><?= $totalStok ?></td>
        </tr>
    </tfoot>
</table>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'sans-serif');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'laporan-stok_' . $fromDate . '_sd_' . $toDate . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
