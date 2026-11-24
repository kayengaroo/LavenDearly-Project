<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();
$canLog = in_array($currentUser['role'], ['administrator', 'caregiver'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_role(['administrator', 'caregiver'], $currentUser);

    $medicineId = (int)($_POST['medicine_id'] ?? 0);
    $logDate = trim((string)($_POST['log_date'] ?? date('Y-m-d')));
    $scheduledTime = trim((string)($_POST['scheduled_time'] ?? '08:00'));
    $actualTime = trim((string)($_POST['actual_time'] ?? date('H:i')));
    $status = $_POST['status'] ?? 'given';
    $note = trim((string)($_POST['note'] ?? ''));

    $dObj = DateTime::createFromFormat('Y-m-d', $logDate);
    if ($medicineId <= 0 || !$dObj || $dObj->format('Y-m-d') !== $logDate || !in_array($status, ['given', 'missed', 'skipped'], true)) {
        set_flash('error', 'Please select a valid medicine, date, scheduled slot, and dose status.');
    } else {
        // Prevent duplicate entry for the same medicine + date + scheduled slot
        $dupStmt = $pdo->prepare('SELECT id, status FROM dose_logs WHERE medicine_id = :mid AND log_date = :ld AND scheduled_time = :st LIMIT 1');
        $dupStmt->execute([':mid' => $medicineId, ':ld' => $logDate, ':st' => $scheduledTime]);
        $existing = $dupStmt->fetch();

        if ($existing) {
            set_flash('warning', 'Duplicate prevented: this scheduled dose (' . $scheduledTime . ' on ' . $logDate . ') was already logged as "' . $existing['status'] . '".');
        } else {
            $ins = $pdo->prepare('INSERT INTO dose_logs (medicine_id, logged_by, log_date, scheduled_time, actual_time, status, note) VALUES (:mid, :uid, :ld, :st, :at, :status, :note)');
            $ins->execute([
                ':mid' => $medicineId,
                ':uid' => (int)$currentUser['id'],
                ':ld' => $logDate,
                ':st' => $scheduledTime,
                ':at' => $actualTime,
                ':status' => $status,
                ':note' => $note,
            ]);
            $newId = (int)$pdo->lastInsertId();
            log_audit((int)$currentUser['id'], 'LOG_DOSE', 'Dose Log', 'DOSE-' . $newId, "Logged med #{$medicineId} slot {$scheduledTime} on {$logDate} as {$status}");
            set_flash('success', 'Dose logged as ' . strtoupper($status) . '.');
        }
    }
    header('Location: doselog.php');
    exit;
}

$activeMeds = $pdo->query("SELECT id, name, dosage, schedule_times FROM medicines WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$recentLogs = $pdo->query("SELECT dl.*, m.name AS medicine_name, m.dosage, u.full_name AS logged_by_name FROM dose_logs dl JOIN medicines m ON m.id = dl.medicine_id JOIN users u ON u.id = dl.logged_by ORDER BY dl.log_date DESC, dl.scheduled_time DESC, dl.id DESC LIMIT 40")->fetchAll();

$pageTitle = 'Dose Log';
$activePage = 'doselog.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Medicine Dose Log</h1>
    <p>Record each scheduled dose as given, missed, or skipped with duplicate-entry protection.</p>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Record a Dose</h2>
    <?php if (!$canLog): ?>
      <div class="flash flash-warning">Contributors can view dose history but cannot log or modify doses.</div>
    <?php else: ?>
      <form method="post" action="doselog.php">
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="medicine_id">Medicine *</label>
          <select id="medicine_id" name="medicine_id" required>
            <?php foreach ($activeMeds as $m): ?>
              <option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?> — <?= e($m['dosage']) ?> (Slots: <?= e($m['schedule_times']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="log_date">Date *</label>
            <input type="date" id="log_date" name="log_date" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label for="scheduled_time">Scheduled Slot (HH:MM) *</label>
            <input type="text" id="scheduled_time" name="scheduled_time" required value="08:00" placeholder="08:00">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="actual_time">Actual Time (HH:MM) *</label>
            <input type="text" id="actual_time" name="actual_time" required value="<?= date('H:i') ?>">
          </div>
          <div class="form-group">
            <label for="status">Status *</label>
            <select id="status" name="status" required>
              <option value="given">Given</option>
              <option value="missed">Missed</option>
              <option value="skipped">Skipped</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label for="note">Optional Note</label>
          <input type="text" id="note" name="note" placeholder="e.g., Taken after breakfast; BP 124/78">
        </div>
        <button type="submit" class="btn btn-primary">Save Dose Entry</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Recent Dose Log Entries</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Date &amp; Slot</th><th>Medicine</th><th>Status</th><th>Logged By</th></tr>
        </thead>
        <tbody>
          <?php if (empty($recentLogs)): ?>
            <tr><td colspan="4" class="kpi-sub">No doses logged yet.</td></tr>
          <?php else: ?>
            <?php foreach ($recentLogs as $r): ?>
              <tr>
                <td>
                  <strong><?= e($r['log_date']) ?></strong><br>
                  <span class="kpi-sub">Sched: <?= e($r['scheduled_time']) ?> • Actual: <?= e($r['actual_time']) ?></span>
                </td>
                <td>
                  <strong><?= e($r['medicine_name']) ?></strong><br>
                  <span class="kpi-sub"><?= e($r['dosage']) ?><?= $r['note'] ? ' — ' . e($r['note']) : '' ?></span>
                </td>
                <td><span class="badge badge-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                <td><?= e($r['logged_by_name']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
