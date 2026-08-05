<?php
require_once __DIR__ . '/includes/auth.php';

requireAdmin();
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $stmt = $db->prepare('DELETE FROM barang WHERE id = ?');
    $stmt->execute([$_POST['id']]);
}

header('Location: master_barang.php');
exit;
