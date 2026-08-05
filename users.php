<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireAdmin();
$db = getDb();

$users = $db->query('SELECT id, username, role, created_at FROM users ORDER BY created_at ASC')->fetchAll();

$pageTitle = 'Kelola User';
$activeNav = 'users';
require __DIR__ . '/includes/layout_start.php';
?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-error"><?= e($_GET['error']) ?></div>
<?php endif; ?>



<div class="dash-grid">
    <div class="card">
        <h2 style="font-size:1.05rem;margin-top:0">Daftar User</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Username</th><th>Role</th><th>Dibuat</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= e($u['username']) ?></td>
                            <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-in' : 'badge-ok' ?>"><?= e($u['role']) ?></span></td>
                            <td class="text-muted"><?= formatWaktu($u['created_at']) ?></td>
                            <td>
                                <?php if ($u['id'] != $user['id']): ?>
                                    <form method="post" action="user_delete.php" style="display:inline"
                                          onsubmit="return confirmDelete('Hapus user <?= e($u['username']) ?>?')">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 style="font-size:1.05rem;margin-top:0">Tambah User Baru</h2>
        <form method="post" action="user_save.php">
            <div class="form-group">
                <label>Username <span class="required">*</span></label>
                <input type="text" name="username" required maxlength="100">
            </div>
            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <input type="password" name="password" required minlength="6">
                <div class="field-hint">Minimal 6 karakter</div>
            </div>
            <div class="form-group">
                <label>Role</label>
                <div class="filter-popup-wrap" style="position:relative">
                    <button type="button" class="btn btn-secondary btn-block" id="roleTrigger"
                            style="justify-content:space-between" onclick="toggleRolePopup()">User</button>
                    <div class="hidden" id="rolePopup" style="position:absolute;top:105%;left:0;right:0;background:var(--surface);border:1px solid var(--border);border-radius:8px;box-shadow:var(--shadow);z-index:10;padding:0.4rem;display:flex;flex-direction:column;gap:0.2rem">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="pilihRole('user','User')">User</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="pilihRole('admin','Admin')">Admin</button>
                    </div>
                </div>
                <input type="hidden" name="role" id="roleInput" value="user">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Buat User</button>
        </form>
    </div>
</div>

<script>
function toggleRolePopup() {
    document.getElementById('rolePopup').classList.toggle('hidden');
}
function pilihRole(value, label) {
    document.getElementById('roleInput').value = value;
    document.getElementById('roleTrigger').textContent = label;
    document.getElementById('rolePopup').classList.add('hidden');
}
document.addEventListener('click', (e) => {
    if (!e.target.closest('.filter-popup-wrap')) {
        document.getElementById('rolePopup')?.classList.add('hidden');
    }
});
</script>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
