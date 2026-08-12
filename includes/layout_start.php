<?php
/**
 * Kerangka halaman (sidebar + topbar). Butuh $pageTitle dan $activeNav
 * sudah di-set oleh halaman pemanggil sebelum require file ini.
 */
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'ITstok') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">IT<span>stok</span></div>
        <a class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
        <a class="nav-item <?= $activeNav === 'master' ? 'active' : '' ?>" href="master_barang.php">Master Barang</a>
        <a class="nav-item <?= $activeNav === 'masuk' ? 'active' : '' ?>" href="barang_masuk.php">Barang Masuk</a>
        <a class="nav-item <?= $activeNav === 'keluar' ? 'active' : '' ?>" href="barang_keluar.php">Barang Keluar</a>
        <a class="nav-item <?= $activeNav === 'laporan' ? 'active' : '' ?>" href="laporan.php">Laporan Stok</a>
        <a class="nav-item <?= $activeNav === 'riwayat' ? 'active' : '' ?>" href="riwayat.php">Riwayat Transaksi</a>
        <?php if ($user['role'] === 'admin'): ?>
            <a class="nav-item <?= $activeNav === 'users' ? 'active' : '' ?>" href="users.php">Kelola User</a>
        <?php endif; ?>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <span class="topbar-title"><?= e($pageTitle ?? '') ?></span>
            <div class="topbar-user">
                <span class="topbar-username"><?= e($user['username']) ?> &middot; <?= $user['role'] === 'admin' ? 'Administrator' : 'User' ?></span>
                <a href="logout.php" class="btn btn-secondary btn-sm">Keluar</a>
            </div>
        </header>
        <main class="content">