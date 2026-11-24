<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$currentUser = check_session_timeout() ? require_login() : null;
$pageTitle = 'Welcome to LavenDearly';
$activePage = 'index.php';
include __DIR__ . '/includes/header.php';
?>
<section class="hero-box">
  <h1>LavenDearly: Family Caregiving Coordination System</h1>
  <p>A shared web-based coordination space for families caring for an elderly loved one at home—bringing medicine schedules, dose logs, overdue and refill alerts, medical records, expense splits, daily care notes, and a printable doctor summary into one reliable record.</p>
  <div class="btn-row">
    <?php if ($currentUser): ?>
      <a href="dashboard.php" class="btn btn-primary">Go to Care Dashboard</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-primary">Sign In to LavenDearly</a>
      <a href="about.php" class="btn btn-outline">Project Overview &amp; Privacy</a>
    <?php endif; ?>
  </div>
</section>

<div class="grid-3">
  <div class="card">
    <h3>Safe Medication Tracking</h3>
    <p>Log each scheduled dose as <strong>given</strong>, <strong>missed</strong>, or <strong>skipped</strong> with timestamp and caregiver attribution, duplicate-slot protection, and refill/overdue alerts.</p>
  </div>
  <div class="card">
    <h3>Transparent Family Expenses</h3>
    <p>Record purchases with receipts and track family contributions against agreed shares so siblings across cities or abroad stay aligned without guesswork.</p>
  </div>
  <div class="card">
    <h3>Doctor &amp; ER Readiness</h3>
    <p>Keep allergies, conditions, active medicines, emergency contacts, and appointments organized—and print a clean one-page medical summary when needed.</p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
