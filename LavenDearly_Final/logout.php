<?php
require __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['uid'])) audit('logout', 'user #' . $_SESSION['uid'], 'Signed out');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();
flash('You have been signed out.');
redirect('login.php');
