<?php
// Serves uploaded files only to signed-in family members (uploads/ is blocked from direct web access).
require __DIR__ . '/includes/functions.php';
require_login();

$map = ['appointment' => 'appointments', 'expense' => 'expenses'];
$t = $_GET['t'] ?? '';
$id = (int)($_GET['id'] ?? 0);
if (!isset($map[$t])) { http_response_code(404); exit('Not found.'); }

$col = $t === 'appointment' ? 'file_path' : 'receipt_path';
$st = db()->prepare("SELECT $col FROM {$map[$t]} WHERE id = ?");
$st->execute([$id]);
$rel = $st->fetchColumn();

$full = $rel ? realpath(__DIR__ . '/uploads/' . $rel) : false;
$root = realpath(__DIR__ . '/uploads');
if (!$full || strpos($full, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($full)) { http_response_code(404); exit('File not found.'); }

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($full);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($full));
header('Content-Disposition: inline; filename="' . basename($full) . '"');
header('X-Content-Type-Options: nosniff');
readfile($full);
