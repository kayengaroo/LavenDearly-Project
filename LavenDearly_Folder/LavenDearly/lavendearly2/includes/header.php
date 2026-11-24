<?php
declare(strict_types=1);
$currentUser = $currentUser ?? null;
$pageTitle = $pageTitle ?? 'LavenDearly';
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);
$flash = function_exists('take_flash') ? take_flash() : null;
$role = $currentUser['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — LavenDearly</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="app-shell">
    <?php if ($currentUser): ?>
    <aside class="sidebar" id="sidebar">
      <div class="brand-box">
        <div class="brand-mark" aria-hidden="true">
          <svg class="logo-svg" viewBox="0 0 64 52" xmlns="http://www.w3.org/2000/svg">
            <text x="5" y="38" fill="#7E699E" class="logo-d">D</text>
            <path d="M43 44 C43 31, 44 19, 46 10" stroke="#6E7D63" stroke-width="2.2" fill="none" stroke-linecap="round"/>
            <path d="M44 35 C39 33, 38 30, 44 31" fill="#8C9C7E"/>
            <path d="M44 29 C49 27, 50 24, 44 25" fill="#8C9C7E"/>
            <ellipse cx="45" cy="18" rx="3.2" ry="2" fill="#A693C2" transform="rotate(-15 45 18)"/>
            <ellipse cx="48" cy="15" rx="3.2" ry="2" fill="#C6B5D8" transform="rotate(15 48 15)"/>
            <ellipse cx="44" cy="13" rx="3.2" ry="2" fill="#A693C2" transform="rotate(-18 44 13)"/>
            <ellipse cx="48" cy="10" rx="3" ry="1.8" fill="#C6B5D8" transform="rotate(12 48 10)"/>
            <ellipse cx="46" cy="7" rx="2.6" ry="1.8" fill="#7E699E"/>
          </svg>
        </div>
        <div>
          <div class="brand-title">LavenDearly</div>
          <div class="brand-sub">Family Care System</div>
        </div>
      </div>
      <nav class="nav-list">
        <a href="dashboard.php" class="nav-item <?= $activePage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="patient.php" class="nav-item <?= $activePage === 'patient.php' ? 'active' : '' ?>">Patient Profile</a>
        <a href="medicines.php" class="nav-item <?= $activePage === 'medicines.php' ? 'active' : '' ?>">Medicine Schedule</a>
        <a href="doselog.php" class="nav-item <?= $activePage === 'doselog.php' ? 'active' : '' ?>">Dose Log</a>
        <a href="appointments.php" class="nav-item <?= $activePage === 'appointments.php' ? 'active' : '' ?>">Appointments &amp; Docs</a>
        <a href="expenses.php" class="nav-item <?= $activePage === 'expenses.php' ? 'active' : '' ?>">Expenses</a>
        <a href="contributions.php" class="nav-item <?= $activePage === 'contributions.php' ? 'active' : '' ?>">Contributions</a>
        <a href="notes.php" class="nav-item <?= $activePage === 'notes.php' ? 'active' : '' ?>">Daily Care Notes</a>
        <a href="search.php" class="nav-item <?= $activePage === 'search.php' ? 'active' : '' ?>">Search &amp; Filter</a>
        <a href="reports.php" class="nav-item <?= $activePage === 'reports.php' ? 'active' : '' ?>">Reports &amp; CSV</a>
        <a href="summary.php" class="nav-item <?= $activePage === 'summary.php' ? 'active' : '' ?>">Doctor Summary</a>
        <?php if ($role === 'administrator'): ?>
          <div class="nav-divider">Admin Controls</div>
          <a href="members.php" class="nav-item <?= $activePage === 'members.php' ? 'active' : '' ?>">Family Members</a>
          <a href="users.php" class="nav-item <?= $activePage === 'users.php' ? 'active' : '' ?>">User Management</a>
          <a href="audit.php" class="nav-item <?= $activePage === 'audit.php' ? 'active' : '' ?>">Audit Log</a>
        <?php endif; ?>
        <div class="nav-divider">Information</div>
        <a href="about.php" class="nav-item <?= $activePage === 'about.php' ? 'active' : '' ?>">About &amp; Privacy</a>
      </nav>
    </aside>
    <?php endif; ?>

    <div class="main-column">
      <header class="topbar">
        <div class="topbar-left">
          <?php if ($currentUser): ?>
            <button type="button" class="btn-ghost mobile-menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
          <?php endif; ?>
          <a href="<?= $currentUser ? 'dashboard.php' : 'index.php' ?>" class="topbar-brand-group">
            <svg class="topbar-logo-mini" viewBox="0 0 64 52" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <text x="5" y="38" fill="#7E699E" class="logo-d">D</text>
              <path d="M43 44 C43 31, 44 19, 46 10" stroke="#6E7D63" stroke-width="2.2" fill="none" stroke-linecap="round"/>
              <path d="M44 35 C39 33, 38 30, 44 31" fill="#8C9C7E"/>
              <path d="M44 29 C49 27, 50 24, 44 25" fill="#8C9C7E"/>
              <ellipse cx="45" cy="18" rx="3.2" ry="2" fill="#A693C2" transform="rotate(-15 45 18)"/>
              <ellipse cx="48" cy="15" rx="3.2" ry="2" fill="#C6B5D8" transform="rotate(15 48 15)"/>
              <ellipse cx="44" cy="13" rx="3.2" ry="2" fill="#A693C2" transform="rotate(-18 44 13)"/>
              <ellipse cx="48" cy="10" rx="3" ry="1.8" fill="#C6B5D8" transform="rotate(12 48 10)"/>
              <ellipse cx="46" cy="7" rx="2.6" ry="1.8" fill="#7E699E"/>
            </svg>
            <span>LavenDearly</span>
          </a>
          <span class="topbar-tag">Elderly Care Coordination</span>
        </div>
        <div class="topbar-right">
          <?php if ($currentUser): ?>
            <span class="session-badge" id="sessionTimer">Session active</span>
            <div class="user-pill">
              <strong><?= e($currentUser['full_name']) ?></strong>
              <span class="role-badge role-<?= e($currentUser['role']) ?>"><?= e(ucfirst($currentUser['role'])) ?></span>
            </div>
            <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
          <?php else: ?>
            <a href="about.php" class="btn btn-sm btn-ghost">About</a>
            <a href="login.php" class="btn btn-sm btn-primary">Login</a>
          <?php endif; ?>
        </div>
      </header>

      <main class="content-area">
        <?php if ($flash): ?>
          <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
