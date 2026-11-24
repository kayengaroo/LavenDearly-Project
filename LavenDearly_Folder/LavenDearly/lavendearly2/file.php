<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0 || !in_array($type, ['doc', 'receipt'], true)) {
    http_response_code(400);
    exit('Invalid file request.');
}

if ($type === 'doc') {
    $stmt = $pdo->prepare('SELECT attachment_path AS path, attachment_label AS label FROM appointments WHERE id = :id LIMIT 1');
} else {
    $stmt = $pdo->prepare('SELECT receipt_path AS path, item_name AS label FROM expenses WHERE id = :id LIMIT 1');
}
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

if (!$row || empty($row['path'])) {
    http_response_code(404);
    exit('File not found.');
}

$baseDir = realpath(__DIR__ . '/uploads');
$fullPath = realpath(__DIR__ . '/' . $row['path']);

if ($baseDir === false || $fullPath === false || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(403);
    exit('File path rejected.');
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

log_audit((int)$currentUser['id'], 'VIEW_FILE', 'File Access', strtoupper($type) . '-' . $id, 'Accessed file ' . basename($fullPath));

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
header('Content-Length: ' . (string)filesize($fullPath));
readfile($fullPath);
exit;
