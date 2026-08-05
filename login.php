<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (currentUser()) {
    header('Location: dashboard.php');
    exit;
}

$db = getDb();
$isSetup = needsSetup();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($isSetup) {
        // Mode setup: bikin akun admin pertama
        if ($username === '' || strlen($password) < 6) {
            $error = 'Username wajib diisi, password minimal 6 karakter.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
            $stmt->execute([$username, $hash, 'admin']);
            $newUser = ['id' => (int) $db->lastInsertId(), 'username' => $username, 'role' => 'admin'];
            loginAs($newUser);
            header('Location: dashboard.php');
            exit;
        }
    } else {
        // Mode login biasa
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password_hash'])) {
            loginAs($row);
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $isSetup ? 'Setup Admin' : 'Login' ?> - ITstok</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-brand">IT<span>stok</span></div>
        <p class="login-sub"><?= $isSetup ? 'Buat akun admin pertama' : 'Masuk ke sistem inventaris' ?></p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
                <?php if ($isSetup): ?>
                    <div class="field-hint">Minimal 6 karakter</div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= $isSetup ? 'Buat Admin & Masuk' : 'Masuk' ?></button>
        </form>
    </div>
</div>
</body>
</html>
