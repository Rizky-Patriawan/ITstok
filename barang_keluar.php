<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireLogin();
$db = getDb();

$barangList = $db->query('SELECT id, kode, nama, model, satuan, stok FROM barang ORDER BY nama ASC')->fetchAll();

$terbaru = $db->query(
    "SELECT bk.*, b.nama AS nama_barang, b.model, b.satuan
     FROM barang_keluar bk JOIN barang b ON b.id = bk.barang_id
     ORDER BY bk.created_at DESC LIMIT 10"
)->fetchAll();

$pageTitle = 'Barang Keluar';
$activeNav = 'keluar';
require __DIR__ . '/includes/layout_start.php';
?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-error"><?= e($_GET['error']) ?></div>
<?php endif; ?>
<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success">Transaksi barang keluar berhasil disimpan.</div>
<?php endif; ?>


<div class="dash-grid">
    <div class="card">
        <h2 style="font-size:1.05rem;margin-top:0">Form Pengeluaran Barang</h2>
        <form method="post" action="barang_keluar_save.php">
            <div class="form-group">
                <label>Barang <span class="required">*</span></label>
                <div class="custom-select-wrap">
                    <input type="hidden" name="barang_id" id="barangIdKeluar" required>
                    <button type="button" class="custom-select-trigger" id="triggerBarangKeluar"
                            onclick="toggleCustomSelect('popupBarangKeluar', 'triggerBarangKeluar')">
                        <span id="labelBarangKeluar">— Pilih barang —</span>
                        <span class="select-arrow">&#9660;</span>
                    </button>
                    <div class="custom-select-popup hidden" id="popupBarangKeluar">
                        <input type="text" class="custom-select-search" placeholder="Cari barang..." oninput="filterSelect(this, 'popupBarangKeluar')">
                        <div class="custom-select-options">
                            <?php foreach ($barangList as $b): ?>
                                <div class="custom-select-option"
                                     data-value="<?= (int) $b['id'] ?>"
                                     data-stok="<?= (int) $b['stok'] ?>"
                                     data-satuan="<?= e($b['satuan']) ?>"
                                     data-label="<?= e($b['kode'] . ' — ' . $b['nama'] . ($b['model'] ? ' (' . $b['model'] . ')' : '')) ?>"
                                     onclick="pilihBarang(this, 'barangIdKeluar', 'labelBarangKeluar', 'popupBarangKeluar', 'hintKeluar')">
                                    <div><?= e($b['nama']) ?><?= $b['model'] ? ' <span class="text-muted">(' . e($b['model']) . ')</span>' : '' ?></div>
                                    <div class="text-muted" style="font-size:0.78rem"><?= e($b['kode']) ?> &middot; Stok: <?= (int) $b['stok'] ?> <?= e($b['satuan']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="field-hint" id="hintKeluar"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Jumlah <span class="required">*</span></label>
                    <input type="number" name="jumlah" min="1" required>
                </div>
                <div class="form-group">
                    <label>Tanggal <span class="required">*</span></label>
                    <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Nama Penerima <span class="required">*</span></label>
                <input type="text" name="penerima" required maxlength="255" placeholder="Nama lengkap penerima">
            </div>
            <div class="form-group">
                <label>Departemen <span class="required">*</span></label>
                <input type="text" name="departemen" required maxlength="100" placeholder="Nama departemen">
            </div>
            <div class="form-group">
                <label>Keperluan / Tujuan <span class="required">*</span></label>
                <textarea name="keperluan" required maxlength="255" placeholder="Jelaskan keperluan penggunaan barang"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="background:var(--warning)">Simpan Transaksi</button>
        </form>
    </div>

    <div class="card">
        <h2 style="font-size:1.05rem;margin-top:0">Transaksi Terbaru</h2>
        <?php if (empty($terbaru)): ?>
            <p class="text-muted">Belum ada transaksi barang keluar.</p>
        <?php endif; ?>
        <?php foreach ($terbaru as $t): ?>
            <div class="activity-row">
                <div>
                    <div class="text-muted" style="font-size:0.78rem">TK-<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?></div>
                    <div class="activity-name"><?= e($t['nama_barang']) ?><?= $t['model'] ? ' <span class="text-muted" style="font-weight:400">(' . e($t['model']) . ')</span>' : '' ?></div>
                    <div class="activity-meta"><?= e($t['penerima']) ?> &middot; <?= e($t['departemen']) ?> &middot; <?= formatTanggal($t['tanggal']) ?></div>
                </div>
                <div class="activity-qty qty-out">-<?= (int) $t['jumlah'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
