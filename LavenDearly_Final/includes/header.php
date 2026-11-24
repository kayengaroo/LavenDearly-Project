<?php
// Expects: $pageTitle, $active. Optional: $public (true for pages that work without login).
$me = me();
$pageTitle = $pageTitle ?? 'LavenDearly';
$active = $active ?? '';
$alertCount = 0;
if ($me) {
    $alertCount = count(overdue_doses(1)) + count(refills_due());
}
// Sidebar sections (items are still filtered by role through nav_items() in functions.php)
$navGroups = [
    'Overview'         => ['dashboard'],
    'Daily care'       => ['patient', 'medicines', 'doselog', 'appointments', 'notes'],
    'Money'            => ['expenses', 'contributions'],
    'Records'          => ['search', 'reports', 'summary'],
    'Family & admin'   => ['members', 'users', 'audit'],
    'Information'      => ['about'],
];
$navLabels = ['contributions' => 'Contributions', 'reports' => 'Reports & CSV', 'about' => 'About & Privacy'];
$navIcons = [
    'dashboard' => '◈', 'patient' => '♡', 'medicines' => '✚', 'doselog' => '✓', 'appointments' => '▣', 'notes' => '✎',
    'expenses' => '₱', 'contributions' => '❖', 'search' => '⌕', 'reports' => '▤', 'summary' => '☰',
    'members' => '☺', 'users' => '⚿', 'audit' => '⌘', 'about' => 'ⓘ',
];
$navByKey = [];
if ($me) foreach (nav_items() as $it) $navByKey[$it[1]] = $it;
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · LavenDearly</title>
<link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if ($me): ?>
<div class="mobile-bar no-print">
  <button type="button" class="btn btn-sm btn-ghost" id="menuBtn" aria-controls="sidebar" aria-expanded="false">☰ Menu</button>
  <a class="brand" href="dashboard.php"><img src="assets/img/logo.svg" width="30" height="30" alt=""><span>LavenDearly</span></a>
</div>
<div class="shell">
  <aside class="sidebar no-print" id="sidebar">
    <a class="brand" href="dashboard.php">
      <img src="assets/img/logo.svg" width="46" height="46" alt="">
      <span>LavenDearly<small>Family Care System</small></span>
    </a>
    <nav class="nav" aria-label="Main">
      <?php foreach ($navGroups as $group => $keys):
        $shown = array_filter($keys, fn($k) => isset($navByKey[$k]));
        if (!$shown) continue; ?>
        <div class="nav-label"><?= e($group) ?></div>
        <?php foreach ($shown as $key): [$href, , $label] = $navByKey[$key]; ?>
          <a href="<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>>
            <span class="ico" aria-hidden="true"><?= $navIcons[$key] ?? '•' ?></span>
            <?= e($navLabels[$key] ?? $label) ?>
            <?php if ($key === 'dashboard' && $alertCount): ?><span class="badge"><?= $alertCount ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-user">
      <div class="who"><?= e($me['full_name']) ?></div>
      <div class="role"><?= e(role_label($me['role'])) ?></div>
      <a class="btn btn-sm btn-light" href="logout.php">Logout</a>
    </div>
  </aside>
  <main class="main">
<?php else: ?>
<div class="public-bar">
  <a class="brand" href="login.php"><img src="assets/img/logo.svg" width="38" height="38" alt=""><span>LavenDearly</span></a>
  <a class="btn btn-sm" href="login.php">Sign in</a>
</div>
<main class="main main-public">
<?php endif; ?>
<?php show_flash(); ?>
