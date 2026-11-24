<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();
$patient = get_patient();
$today = date('Y-m-d');
$nowHm = date('H:i');

$meds = $pdo->query("SELECT * FROM medicines WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$logsTodayStmt = $pdo->prepare('SELECT medicine_id, scheduled_time, status, actual_time FROM dose_logs WHERE log_date = :d');
$logsTodayStmt->execute([':d' => $today]);
$loggedMap = [];
foreach ($logsTodayStmt->fetchAll() as $row) {
    $loggedMap[$row['medicine_id'] . '|' . $row['scheduled_time']] = $row;
}

$overdueAlerts = [];
$scheduledCount = 0;
$completedCount = 0;
foreach ($meds as $m) {
    $slots = array_filter(array_map('trim', explode(',', (string)$m['schedule_times'])));
    foreach ($slots as $slot) {
        $scheduledCount++;
        $key = $m['id'] . '|' . $slot;
        if (isset($loggedMap[$key])) {
            if ($loggedMap[$key]['status'] === 'given') {
                $completedCount++;
            }
        } else {
            // Flag as overdue if current time is at least 30 minutes past scheduled slot
            $slotMins = ((int)substr($slot, 0, 2) * 60) + (int)substr($slot, 3, 2);
            $nowMins = ((int)substr($nowHm, 0, 2) * 60) + (int)substr($nowHm, 3, 2);
            if ($nowMins - $slotMins >= 30) {
                $overdueAlerts[] = ['med' => $m['name'], 'dosage' => $m['dosage'], 'slot' => $slot];
            }
        }
    }
}

$refillStmt = $pdo->query("SELECT name, refill_date, DATEDIFF(refill_date, CURDATE()) AS days_left FROM medicines WHERE status = 'active' AND refill_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY refill_date ASC");
$refillAlerts = $refillStmt->fetchAll();

$apptStmt = $pdo->query("SELECT * FROM appointments WHERE appointment_date >= CURDATE() ORDER BY appointment_date ASC LIMIT 3");
$upcomingAppts = $apptStmt->fetchAll();

$totalExp = (float)$pdo->query('SELECT COALESCE(SUM(amount), 0) FROM expenses')->fetchColumn();
$totalContrib = (float)$pdo->query('SELECT COALESCE(SUM(amount), 0) FROM contributions')->fetchColumn();

$latestNotes = $pdo->query("SELECT cn.*, u.full_name AS author_name FROM care_notes cn JOIN users u ON u.id = cn.author_id ORDER BY cn.note_date DESC, cn.id DESC LIMIT 2")->fetchAll();

$pageTitle = 'Care Dashboard';
$activePage = 'dashboard.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Care Dashboard — <?= e($patient['full_name']) ?></h1>
    <p>Today's coordination overview (<?= e($today) ?>) • Primary Doctor: <?= e($patient['primary_doctor']) ?></p>
  </div>
  <div class="btn-row">
    <?php if (in_array($currentUser['role'], ['administrator', 'caregiver'], true)): ?>
      <a href="doselog.php" class="btn btn-primary">Log Dose</a>
      <a href="expenses.php" class="btn btn-outline">Add Expense</a>
    <?php endif; ?>
    <a href="summary.php" class="btn btn-outline">Doctor Summary</a>
  </div>
</div>

<?php foreach ($overdueAlerts as $ov): ?>
  <div class="alert-banner overdue">
    <div><strong>Overdue Dose Alert:</strong> <?= e($ov['med']) ?> (<?= e($ov['dosage']) ?>) scheduled at <strong><?= e($ov['slot']) ?></strong> has not been logged yet.</div>
    <a href="doselog.php" class="btn btn-sm btn-outline">Log Now</a>
  </div>
<?php endforeach; ?>

<?php foreach ($refillAlerts as $rf): ?>
  <div class="alert-banner refill">
    <div><strong>Refill Reminder:</strong> <?= e($rf['name']) ?> refill date is <strong><?= e($rf['refill_date']) ?></strong> (<?= (int)$rf['days_left'] ?> day(s) remaining).</div>
    <a href="medicines.php" class="btn btn-sm btn-outline">View Schedule</a>
  </div>
<?php endforeach; ?>

<div class="grid-4" style="margin-bottom:20px;">
  <div class="kpi-card">
    <div class="kpi-label">Doses Given Today</div>
    <div class="kpi-value"><?= $completedCount ?> / <?= $scheduledCount ?></div>
    <div class="kpi-sub"><?= count($overdueAlerts) ?> overdue slot(s)</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Refills Due (&le;7 Days)</div>
    <div class="kpi-value"><?= count($refillAlerts) ?></div>
    <div class="kpi-sub">Active medicines tracked</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Total Logged Expenses</div>
    <div class="kpi-value">&#8369;<?= number_format($totalExp, 2) ?></div>
    <div class="kpi-sub">Contributions: &#8369;<?= number_format($totalContrib, 2) ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Next Appointment</div>
    <div class="kpi-value"><?= !empty($upcomingAppts) ? e($upcomingAppts[0]['appointment_date']) : 'None' ?></div>
    <div class="kpi-sub"><?= !empty($upcomingAppts) ? e($upcomingAppts[0]['doctor_name']) : 'Schedule a visit' ?></div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Today's Medicine Schedule</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Medicine</th><th>Dosage</th><th>Time</th><th>Today's Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($meds as $m): ?>
            <?php foreach (array_filter(array_map('trim', explode(',', (string)$m['schedule_times']))) as $slot): ?>
              <?php $st = $loggedMap[$m['id'] . '|' . $slot]['status'] ?? 'pending'; ?>
              <tr>
                <td><strong><?= e($m['name']) ?></strong></td>
                <td><?= e($m['dosage']) ?></td>
                <td><?= e($slot) ?></td>
                <td><span class="badge badge-<?= e($st) ?>"><?= e(ucfirst($st)) ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>Recent Daily Care Notes</h2>
    <?php if (empty($latestNotes)): ?>
      <p class="kpi-sub">No care notes recorded yet.</p>
    <?php else: ?>
      <?php foreach ($latestNotes as $n): ?>
        <div style="border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:12px;">
          <div style="display:flex; justify-content:space-between; font-size:13px;">
            <strong><?= e($n['note_date']) ?> — <?= e($n['mood']) ?></strong>
            <span class="kpi-sub">By <?= e($n['author_name']) ?></span>
          </div>
          <div style="font-size:13px; margin-top:4px;"><strong>Meals:</strong> <?= e($n['meals_summary']) ?></div>
          <div style="font-size:13px;"><strong>Sleep:</strong> <?= e($n['sleep_summary']) ?></div>
          <div style="font-size:13px; margin-top:4px;"><?= e($n['concerns']) ?></div>
        </div>
      <?php endforeach; ?>
      <a href="notes.php" class="btn btn-sm btn-outline">Open Care Notes &amp; Comments</a>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
