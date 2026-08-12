<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireLogin();
$db = getDb();

$search = trim($_GET['q'] ?? '');
$sort = in_array($_GET['sort'] ?? '', ['kode', 'nama'], true) ? $_GET['sort'] : 'nama';

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE nama LIKE ? OR kode LIKE ? OR model LIKE ? OR kategori LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
}

$stmt = $db->prepare("SELECT * FROM barang $where ORDER BY $sort ASC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Master Barang';
$activeNav = 'master';
require __DIR__ . '/includes/layout_start.php';
?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-error"><?= e($_GET['error']) ?></div>
<?php endif; ?>

<div class="card">
    <form method="get" class="filter-bar">
        <input type="text" name="q" class="search-box" placeholder="Cari nama, kode, model, kategori..." value="<?= e($search) ?>">
        <input type="hidden" name="sort" value="<?= e($sort) ?>">
        <button type="submit" class="btn btn-secondary">Cari</button>

        <!-- Filter urutan -->
        <div class="custom-select-wrap" style="min-width:160px">
            <button type="button" class="custom-select-trigger" onclick="toggleCustomSelect('popupSort', this)">
                <span>Urutan: <strong><?= $sort === 'kode' ? 'Kode' : 'Nama' ?></strong></span>
                <span class="select-arrow">&#9660;</span>
            </button>
            <div class="custom-select-popup hidden" id="popupSort">
                <div class="custom-select-options">
                    <div class="custom-select-option <?= $sort === 'nama' ? 'active' : '' ?>"
                         onclick="pilihSort('nama')">Nama Barang</div>
                    <div class="custom-select-option <?= $sort === 'kode' ? 'active' : '' ?>"
                         onclick="pilihSort('kode')">Kode Barang</div>
                </div>
            </div>
        </div>

        <span class="text-muted" style="font-size:0.85rem"><?= count($rows) ?> item</span>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th><th>Nama Barang </th><th>Model</th><th>Kategori</th>
                    <th>Lokasi</th><th>Stok Min</th><th>Stok</th><th>Satuan</th><th>Kondisi</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $b): ?>
                    <tr>
                        <td><?= e($b['kode']) ?></td>
                        <td><?= e($b['nama']) ?></td>
                        <td><?= e($b['model'] ?: '-') ?></td>
                        <td><?= e($b['kategori'] ?: '-') ?></td>
                        <td><?= e($b['lokasi'] ?: '-') ?></td>
                        <td class="text-muted"><?= (int) $b['stok_min'] ?></td>
                        <td><?= (int) $b['stok'] ?></td>
                        <td><?= e($b['satuan']) ?></td>
                        <td><?= kondisiBadge($b['kondisi']) ?></td>
                        <td>
                            <div class="aksi-wrap" style="display:inline-block;position:relative">
                                <button type="button" class="btn-icon" title="Aksi"
                                        onclick="event.stopPropagation(); toggleAksiPopup('aksiPopup<?= (int) $b['id'] ?>', this)">&#9998;</button>
                                <div class="aksi-popup hidden" id="aksiPopup<?= (int) $b['id'] ?>">
                                    <div onclick="event.stopPropagation(); closeAllAksiPopups();"
                                         data-edit-barang="<?= htmlspecialchars(json_encode($b, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
                                         onmousedown="editBarang(JSON.parse(this.dataset.editBarang))">
                                         Edit
                                    </div>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <div class="text-danger"
                                             onclick="event.stopPropagation(); closeAllAksiPopups(); hapusBarang(<?= (int) $b['id'] ?>, '<?= e($b['nama']) ?>')">
                                             Hapus
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="10" class="text-muted" style="text-align:center;padding:2rem">Belum ada barang. Tambahkan lewat menu Barang Masuk.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Form hapus (tersembunyi, dipicu JS) -->
<form method="post" action="barang_delete.php" id="formHapus">
    <input type="hidden" name="id" id="hapusId">
</form>

<!-- Modal Edit Barang -->
<div class="modal-overlay hidden" id="editModal">
    <div class="modal-box">
        <div class="modal-box-header">
            <h2>Edit Barang</h2>
            <button type="button" class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="post" action="barang_save.php">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-row">
                <div class="form-group">
                    <label>Kode Barang <span class="required">*</span></label>
                    <input type="text" name="kode" id="edit_kode" required maxlength="50">
                </div>
                <div class="form-group">
                    <label>Nama Barang <span class="required">*</span></label>
                    <input type="text" name="nama" id="edit_nama" required maxlength="255">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Model</label>
                    <input type="text" name="model" id="edit_model" maxlength="255">
                </div>
                <div class="form-group">
                    <label>Lokasi</label>
                    <input type="text" name="lokasi" id="edit_lokasi" maxlength="100">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="kategori" id="edit_kategori" maxlength="100">
                </div>
                <div class="form-group">
                    <label>Satuan</label>
                    <input type="text" name="satuan" id="edit_satuan" maxlength="50">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Stok Minimum</label>
                    <input type="number" name="stok_min" id="edit_stok_min" min="0">
                    <div class="field-hint">Stok hanya berubah lewat Barang Masuk/Keluar.</div>
                </div>
                <div class="form-group">
                    <label>Kondisi</label>
                    <div style="display:flex;gap:0.4rem;margin-top:0.35rem;flex-wrap:wrap">
                        <?php foreach (['baik' => 'Baik', 'rusak' => 'Rusak', 'servis' => 'Servis'] as $val => $lbl): ?>
                            <label style="display:flex;align-items:center;gap:0.3rem;font-weight:400;cursor:pointer">
                                <input type="radio" name="kondisi" value="<?= $val ?>" id="edit_kondisi_<?= $val ?>"> <?= $lbl ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editBarang(b) {
    document.getElementById('edit_id').value = b.id;
    document.getElementById('edit_kode').value = b.kode;
    document.getElementById('edit_nama').value = b.nama;
    document.getElementById('edit_model').value = b.model;
    document.getElementById('edit_lokasi').value = b.lokasi || '';
    document.getElementById('edit_kategori').value = b.kategori;
    document.getElementById('edit_satuan').value = b.satuan;
    document.getElementById('edit_stok_min').value = b.stok_min;
    const radio = document.getElementById('edit_kondisi_' + b.kondisi);
    if (radio) radio.checked = true;
    openModal('editModal');
}
function hapusBarang(id, nama) {
    if (!confirmDelete('Hapus ' + nama + '? Semua riwayat transaksinya juga akan terhapus.')) return;
    document.getElementById('hapusId').value = id;
    document.getElementById('formHapus').submit();
}
function pilihSort(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', val);
    window.location.href = url.toString();
}
</script>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
