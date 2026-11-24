<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();
$canManage = in_array($currentUser['role'], ['administrator', 'caregiver'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_role(['administrator', 'caregiver'], $currentUser);
    $act = $_POST['act'] ?? 'add';

    if ($act === 'add') {
        $name = trim((string)($_POST['name'] ?? ''));
        $dosage = trim((string)($_POST['dosage'] ?? ''));
        $scheduleTimes = trim((string)($_POST['schedule_times'] ?? ''));
        $instructions = trim((string)($_POST['instructions'] ?? ''));
        $doctor = trim((string)($_POST['prescribing_doctor'] ?? ''));
        $startDate = trim((string)($_POST['start_date'] ?? date('Y-m-d')));
        $refillDate = trim((string)($_POST['refill_date'] ?? ''));

        $d1 = DateTime::createFromFormat('Y-m-d', $startDate);
        $d2 = DateTime::createFromFormat('Y-m-d', $refillDate);

        if ($name === '' || $dosage === '' || $scheduleTimes === '' || !$d1 || $d1->format('Y-m-d') !== $startDate || !$d2 || $d2->format('Y-m-d') !== $refillDate) {
            set_flash('error', 'Please complete medicine name, dosage, schedule times, and valid start/refill dates.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO medicines (patient_id, name, dosage, schedule_times, instructions, prescribing_doctor, start_date, refill_date, status) VALUES (1, :n, :d, :t, :i, :doc, :sd, :rd, "active")');
            $stmt->execute([
                ':n' => $name, ':d' => $dosage, ':t' => $scheduleTimes,
                ':i' => $instructions, ':doc' => $doctor, ':sd' => $startDate, ':rd' => $refillDate,
            ]);
            $newId = (int)$pdo->lastInsertId();
            log_audit((int)$currentUser['id'], 'CREATE', 'Medicine Schedule', 'MED-' . $newId, 'Added medicine ' . $name . ' (' . $dosage . ')');
            set_flash('success', 'Medicine added to schedule.');
        }
    } elseif ($act === 'toggle_status') {
        $medId = (int)($_POST['medicine_id'] ?? 0);
        $newStatus = ($_POST['new_status'] ?? '') === 'discontinued' ? 'discontinued' : 'active';
        $stmt = $pdo->prepare('UPDATE medicines SET status = :st WHERE id = :id');
        $stmt->execute([':st' => $newStatus, ':id' => $medId]);
        log_audit((int)$currentUser['id'], 'UPDATE_STATUS', 'Medicine Schedule', 'MED-' . $medId, 'Marked medicine as ' . $newStatus);
        set_flash('success', 'Medicine status updated to ' . $newStatus . '.');
    }
    header('Location: medicines.php');
    exit;
}

$medicines = $pdo->query('SELECT *, DATEDIFF(refill_date, CURDATE()) AS days_to_refill FROM medicines ORDER BY status ASC, refill_date ASC')->fetchAll();

$pageTitle = 'Medicine Schedule';
$activePage = 'medicines.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Medicine Schedule</h1>
    <p>Active and discontinued maintenance medicines, scheduled times, and refill dates.</p>
  </div>
  <a href="doselog.php" class="btn btn-primary">Go to Dose Log</a>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Scheduled Medicines</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Medicine &amp; Dose</th><th>Times</th><th>Refill</th><th>Status</th><?php if ($canManage): ?><th>Action</th><?php endif; ?></tr>
        </thead>
        <tbody>
          <?php foreach ($medicines as $m): ?>
            <tr>
              <td>
                <strong><?= e($m['name']) ?></strong><br>
                <span class="kpi-sub"><?= e($m['dosage']) ?> • <?= e($m['prescribing_doctor']) ?></span><br>
                <span class="kpi-sub"><?= e($m['instructions']) ?></span>
              </td>
              <td><?= e($m['schedule_times']) ?></td>
              <td>
                <?= e($m['refill_date']) ?><br>
                <?php if ($m['status'] === 'active' && (int)$m['days_to_refill'] <= 7): ?>
                  <span class="badge badge-due"><?= (int)$m['days_to_refill'] ?>d left</span>
                <?php endif; ?>
              </td>
              <td><span class="badge badge-<?= e($m['status']) ?>"><?= e(ucfirst($m['status'])) ?></span></td>
              <?php if ($canManage): ?>
                <td>
                  <form method="post" action="medicines.php" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="act" value="toggle_status">
                    <input type="hidden" name="medicine_id" value="<?= (int)$m['id'] ?>">
                    <input type="hidden" name="new_status" value="<?= $m['status'] === 'active' ? 'discontinued' : 'active' ?>">
                    <button type="submit" class="btn btn-sm btn-outline">
                      <?= $m['status'] === 'active' ? 'Discontinue' : 'Reactivate' ?>
                    </button>
                  </form>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>Add Medicine to Schedule</h2>
    <?php if (!$canManage): ?>
      <div class="flash flash-warning">Contributors have view-only access to the Medicine Schedule.</div>
    <?php else: ?>
      <form method="post" action="medicines.php">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="add">
        <div class="form-group">
          <label for="name">Medicine Name *</label>
          <input type="text" id="name" name="name" required placeholder="e.g., Amlodipine Besylate">
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="dosage">Dosage / Strength *</label>
            <input type="text" id="dosage" name="dosage" required placeholder="e.g., 5 mg (1 tablet)">
          </div>
          <div class="form-group">
            <label for="schedule_times">Times of Day (24h, comma-separated) *</label>
            <input type="text" id="schedule_times" name="schedule_times" required placeholder="08:00,20:00">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="start_date">Start Date *</label>
            <input type="date" id="start_date" name="start_date" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label for="refill_date">Refill Due Date *</label>
            <input type="date" id="refill_date" name="refill_date" required value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="prescribing_doctor">Prescribing Doctor</label>
          <input type="text" id="prescribing_doctor" name="prescribing_doctor" placeholder="Dr. Ramon Cruz, MD">
        </div>
        <div class="form-group">
          <label for="instructions">Special Instructions</label>
          <input type="text" id="instructions" name="instructions" placeholder="e.g., After meals">
        </div>
        <button type="submit" class="btn btn-primary">Save Medicine</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
