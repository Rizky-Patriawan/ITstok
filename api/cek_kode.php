<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$user = currentUser();
if (!$user) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }

$kode = trim($_GET['kode'] ?? '');
if ($kode === '') { echo json_encode(['exists' => false]); exit; }

$db = getDb();
$stmt = $db->prepare('SELECT 1 FROM barang WHERE kode = ? LIMIT 1');
$stmt->execute([$kode]);
echo json_encode(['exists' => (bool) $stmt->fetch()]);