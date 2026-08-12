<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireLogin();
$db = getDb();
$stats = getDashboardStats($db);

$pageTitle = 'Dashboard Inventaris';
$activeNav = 'dashboard';
require __DIR__ . '/includes/layout_start.php';
?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-error"><?= e($_GET['error']) ?></div>
<?php endif; ?>



<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Item</div>
        <div class="stat-value"><?= (int) $stats['total_barang'] ?></div>
        <div class="stat-sub"><?= (int) $stats['stok_total'] ?> unit tersedia</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Masuk Hari Ini</div>
        <div class="stat-value" style="color:var(--success)">+<?= (int) $stats['masuk_hari_ini']['total_qty'] ?></div>
        <div class="stat-sub"><?= (int) $stats['masuk_hari_ini']['jml_transaksi'] ?> transaksi</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Keluar Hari Ini</div>
        <div class="stat-value" style="color:var(--warning)">-<?= (int) $stats['keluar_hari_ini']['total_qty'] ?></div>
        <div class="stat-sub"><?= (int) $stats['keluar_hari_ini']['jml_transaksi'] ?> transaksi</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Stok Total</div>
        <div class="stat-value"><?= (int) $stats['stok_total'] ?></div>
        <div class="stat-sub"><?= count($stats['stok_rendah']) ?> item hampir habis</div>
    </div>
</div>

<div class="dash-grid">
    <div class="card">
        <div class="page-header" style="margin-bottom:0.75rem">
            <h1 style="font-size:1.05rem">Aktivitas Terbaru</h1>
            <span class="text-muted" style="font-size:0.85rem"><?= count($stats['aktivitas_terbaru']) ?> transaksi</span>
        </div>
        <?php if (empty($stats['aktivitas_terbaru'])): ?>
            <p class="text-muted">Belum ada transaksi.</p>
        <?php endif; ?>
        <?php foreach ($stats['aktivitas_terbaru'] as $a): ?>
            <div class="activity-row">
                <div>
                    <div class="activity-name"><?= e($a['nama_barang']) ?></div>
                    <div class="activity-meta"><?= e($a['pihak'] ?: '-') ?> &middot; <?= formatWaktu($a['created_at']) ?></div>
                </div>
                <div class="activity-qty <?= $a['tipe'] === 'masuk' ? 'qty-in' : 'qty-out' ?>">
                    <?= $a['tipe'] === 'masuk' ? '+' : '-' ?><?= (int) $a['jumlah'] ?> Unit
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="page-header" style="margin-bottom:0.75rem">
            <h1 style="font-size:1.05rem">⚠️ Stok Rendah</h1>
            <span class="badge badge-warn"><?= count($stats['stok_rendah']) ?></span>
        </div>
        <?php if (empty($stats['stok_rendah'])): ?>
            <p class="text-muted">Semua stok aman.</p>
        <?php endif; ?>
        <?php foreach ($stats['stok_rendah'] as $b): ?>
            <div class="low-stock-row">
                <div style="display:flex;justify-content:space-between">
                    <span class="activity-name"><?= e($b['nama']) ?></span>
                    <span class="badge badge-warn"><?= (int) $b['stok'] ?> Unit</span>
                </div>
                <div class="activity-meta">Minimum: <?= (int) $b['stok_min'] ?> &middot; <?= e($b['kode']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
