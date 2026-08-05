<?php
/**
 * Session, login/logout, dan bootstrap admin pertama.
 */
require_once __DIR__ . '/db.php';

session_start();

function needsSetup(): bool
{
    $db = getDb();
    $count = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    return $count === 0;
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
    ];
}

function requireLogin(): array
{
    $user = currentUser();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function requireAdmin(): array
{
    $user = requireLogin();
    if ($user['role'] !== 'admin') {
        header('Location: dashboard.php?error=' . urlencode('Hanya admin yang bisa mengakses halaman ini'));
        exit;
    }
    return $user;
}

function loginAs(array $userRow): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userRow['id'];
    $_SESSION['username'] = $userRow['username'];
    $_SESSION['role'] = $userRow['role'];
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}
