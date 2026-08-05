<?php
require_once __DIR__ . '/includes/auth.php';

requireAdmin();
$db = getDb();

function redirectWithError(string $message): void
{
    header('Location: users.php?error=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$role = in_array($_POST['role'] ?? '', ['admin', 'user'], true) ? $_POST['role'] : 'user';

if ($username === '' || strlen($password) < 6) {
    redirectWithError('Username wajib diisi, password minimal 6 karakter.');
}

try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
    $stmt->execute([$username, $hash, $role]);

    header('Location: users.php');
    exit;
} catch (PDOException $ex) {
    $msg = isUniqueConstraintError($ex) ? 'Username sudah digunakan' : 'Terjadi kesalahan server';
    redirectWithError($msg);
}
