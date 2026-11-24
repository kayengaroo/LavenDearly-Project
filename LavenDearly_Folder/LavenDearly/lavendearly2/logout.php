<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    log_audit($uid, 'LOGOUT', 'Authentication', 'USER-' . $uid, 'User logged out.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

session_start();
set_flash('success', 'You have been safely signed out.');
header('Location: login.php');
exit;
