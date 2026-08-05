<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireLogin();
$db = getDb();

$barangList = $db->query('SELECT id, kode, nama, model, satuan, stok FROM barang ORDER BY nama ASC')->fetchAll();

$terbaru = $db->query(
    "SELECT bm.*, b.nama AS nama_barang, b.model, b.satuan
     FROM barang_masuk bm JOIN barang b ON b.id = bm.barang_id
     ORDER BY bm.created_at DESC LIMIT 10"
)->fetchAll();

$modeAwal = ($_GET['mode'] ?? '') === 'baru' ? 'baru' : 'stok';

$pageTitle = 'Barang Masuk';
$activeNav = 'masuk';
require __DIR__ . '/includes/layout_start.php';
?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-error"><?= e($_GET['error']) ?></div>
<?php endif; ?>
<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success">Transaksi barang masuk berhasil disimpan.</div>
<?php endif; ?>


<div class="dash-grid">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h2 style="font-size:1.05rem;margin:0">Form Penerimaan Barang</h2>
            <div style="display:flex;gap:0.5rem">
                <button type="button" id="btnStok" class="btn <?= $modeAwal === 'stok' ? 'btn-primary' : 'btn-secondary' ?>"
                        onclick="setMode('stok')">Stok</button>
                <button type="button" id="btnBaru" class="btn <?= $modeAwal === 'baru' ? 'btn-primary' : 'btn-secondary' ?>"
                        onclick="setMode('baru')">Barang Baru</button>
            </div>
        </div>

        <!-- Mode: Stok -->
        <form method="post" action="barang_masuk_save.php" id="formStok" class="<?= $modeAwal !== 'stok' ? 'hidden' : '' ?>">
            <div class="form-group">
                <label>Barang <span class="required">*</span></label>
                <div class="custom-select-wrap" id="wrapBarangStok">
                    <input type="hidden" name="barang_id" id="barangIdStok" required>
                    <button type="button" class="custom-select-trigger" id="triggerBarangStok"
                            onclick="toggleCustomSelect('popupBarangStok', 'triggerBarangStok')">
                        <span id="labelBarangStok">— Pilih barang —</span>
                        <span class="select-arrow">&#9660;</span>
                    </button>
                    <div class="custom-select-popup hidden" id="popupBarangStok">
                        <input type="text" class="custom-select-search" placeholder="Cari barang..." oninput="filterSelect(this, 'popupBarangStok')">
                        <div class="custom-select-options">
                            <?php foreach ($barangList as $b): ?>
                                <div class="custom-select-option"
                                     data-value="<?= (int) $b['id'] ?>"
                                     data-stok="<?= (int) $b['stok'] ?>"
                                     data-satuan="<?= e($b['satuan']) ?>"
                                     data-label="<?= e($b['kode'] . ' — ' . $b['nama'] . ($b['model'] ? ' (' . $b['model'] . ')' : '')) ?>"
                                     onclick="pilihBarang(this, 'barangIdStok', 'labelBarangStok', 'popupBarangStok', 'hintStok')">
                                    <div><?= e($b['nama']) ?><?= $b['model'] ? ' <span class="text-muted">(' . e($b['model']) . ')</span>' : '' ?></div>
                                    <div class="text-muted" style="font-size:0.78rem"><?= e($b['kode']) ?> &middot; Stok: <?= (int) $b['stok'] ?> <?= e($b['satuan']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="field-hint" id="hintStok"></div>
            </div>
            <div class="form-group">
                <label>Jumlah <span class="required">*</span></label>
                <input type="number" name="jumlah" min="1" required>
            </div>
            <div class="form-group">
                <label>Tanggal Terima <span class="required">*</span></label>
                <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Supplier / Vendor</label>
                <input type="text" name="supplier" maxlength="255" placeholder="Nama perusahaan supplier">
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" maxlength="255" placeholder="Catatan tambahan (opsional)"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Simpan Transaksi</button>
        </form>

        <!-- Mode: Barang Baru -->
        <form method="post" action="barang_baru_save.php" id="formBaru" class="<?= $modeAwal !== 'baru' ? 'hidden' : '' ?>">
            <div class="form-group">
                <label>Kode Barang <span class="required">*</span></label>
                <input type="text" name="kode" required maxlength="50">
            </div>
            <div class="form-group">
                <label>Nama Barang (Merek) <span class="required">*</span></label>
                <input type="text" name="nama" required maxlength="255">
            </div>
            <div class="form-group">
                <label>Model</label>
                <input type="text" name="model" maxlength="255">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="kategori" maxlength="100">
                </div>
                <div class="form-group">
                    <label>Satuan</label>
                    <input type="text" name="satuan" value="Unit" maxlength="50">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Stok Minimum</label>
                    <input type="number" name="stok_min" value="5" min="0">
                </div>
                <div class="form-group">
                    <label>Kondisi</label>
                    <div style="display:flex;gap:0.75rem;margin-top:0.35rem;flex-wrap:wrap">
                        <label style="display:flex;align-items:center;gap:0.3rem;font-weight:400;cursor:pointer">
                            <input type="radio" name="kondisi" value="baik" checked> <span class="badge badge-ok">Baik</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:0.3rem;font-weight:400;cursor:pointer">
                            <input type="radio" name="kondisi" value="rusak"> <span class="badge badge-rusak">Rusak</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:0.3rem;font-weight:400;cursor:pointer">
                            <input type="radio" name="kondisi" value="servis"> <span class="badge badge-servis">Servis</span>
                        </label>
                    </div>
                </div>
            </div>
            <hr style="border:none;border-top:1px solid var(--border);margin:1rem 0">
            <div class="form-row">
                <div class="form-group">
                    <label>Jumlah (stok awal) <span class="required">*</span></label>
                    <input type="number" name="jumlah" min="1" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Terima <span class="required">*</span></label>
                    <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Supplier / Vendor</label>
                <input type="text" name="supplier" maxlength="255" placeholder="Nama perusahaan supplier">
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" maxlength="255" placeholder="Catatan tambahan (opsional)"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Simpan Barang &amp; Stok Awal</button>
        </form>
    </div>

    <div class="card">
        <h2 style="font-size:1.05rem;margin-top:0">Transaksi Terbaru</h2>
        <?php if (empty($terbaru)): ?>
            <p class="text-muted">Belum ada transaksi barang masuk.</p>
        <?php endif; ?>
        <?php foreach ($terbaru as $t): ?>
            <div class="activity-row">
                <div>
                    <div class="text-muted" style="font-size:0.78rem">TM-<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?></div>
                    <div class="activity-name"><?= e($t['nama_barang']) ?><?= $t['model'] ? ' <span class="text-muted" style="font-weight:400">(' . e($t['model']) . ')</span>' : '' ?></div>
                    <div class="activity-meta"><?= e($t['supplier'] ?: '-') ?> &middot; <?= formatTanggal($t['tanggal']) ?></div>
                </div>
                <div class="activity-qty qty-in">+<?= (int) $t['jumlah'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function setMode(mode) {
    document.getElementById('formStok').classList.toggle('hidden', mode !== 'stok');
    document.getElementById('formBaru').classList.toggle('hidden', mode !== 'baru');
    document.getElementById('btnStok').className = 'btn ' + (mode === 'stok' ? 'btn-primary' : 'btn-secondary');
    document.getElementById('btnBaru').className = 'btn ' + (mode === 'baru' ? 'btn-primary' : 'btn-secondary');
    closeAllCustomSelects();
}
</script>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
