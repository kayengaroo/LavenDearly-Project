<?php
require __DIR__ . '/includes/functions.php';
redirect(empty($_SESSION['uid']) ? 'login.php' : 'dashboard.php');
