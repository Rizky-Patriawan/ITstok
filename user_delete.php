<?php
require_once __DIR__ . '/includes/auth.php';

$currentUserData = requireAdmin();
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $targetId = (int) $_POST['id'];

    if ($targetId === (int) $currentUserData['id']) {
        header('Location: users.php?error=' . urlencode('Tidak bisa menghapus akun sendiri'));
        exit;
    }

    $totalAdmin = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if ($target && $target['role'] === 'admin' && $totalAdmin <= 1) {
        header('Location: users.php?error=' . urlencode('Tidak bisa menghapus admin terakhir'));
        exit;
    }

    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$targetId]);
}

header('Location: users.php');
exit;
