<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();

$q = trim((string)($_GET['q'] ?? ''));
$module = $_GET['module'] ?? 'all';
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));

$doseRows = [];
$apptRows = [];
$expRows = [];

if ($module === 'all' || $module === 'doses') {
    $sql = "SELECT dl.log_date, dl.scheduled_time, dl.status, dl.note, m.name AS med_name, u.full_name AS user_name
            FROM dose_logs dl
            JOIN medicines m ON m.id = dl.medicine_id
            JOIN users u ON u.id = dl.logged_by WHERE 1=1";
    $params = [];
    if ($q !== '') {
        $sql .= " AND (m.name LIKE :q OR dl.note LIKE :q OR dl.status LIKE :q OR u.full_name LIKE :q)";
        $params[':q'] = '%' . $q . '%';
    }
    if ($dateFrom !== '') { $sql .= " AND dl.log_date >= :df"; $params[':df'] = $dateFrom; }
    if ($dateTo !== '') { $sql .= " AND dl.log_date <= :dt"; $params[':dt'] = $dateTo; }
    $sql .= " ORDER BY dl.log_date DESC, dl.id DESC LIMIT 50";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $doseRows = $st->fetchAll();
}

if ($module === 'all' || $module === 'appointments') {
    $sql = "SELECT appointment_date, doctor_name, clinic_or_hospital, purpose_or_notes
            FROM appointments WHERE 1=1";
    $params = [];
    if ($q !== '') {
        $sql .= " AND (doctor_name LIKE :q OR clinic_or_hospital LIKE :q OR purpose_or_notes LIKE :q)";
        $params[':q'] = '%' . $q . '%';
    }
    if ($dateFrom !== '') { $sql .= " AND appointment_date >= :df"; $params[':df'] = $dateFrom; }
    if ($dateTo !== '') { $sql .= " AND appointment_date <= :dt"; $params[':dt'] = $dateTo; }
    $sql .= " ORDER BY appointment_date DESC LIMIT 50";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $apptRows = $st->fetchAll();
}

if ($module === 'all' || $module === 'expenses') {
    $sql = "SELECT e.expense_date, e.item_name, e.category, e.amount, e.notes, u.full_name AS payer_name
            FROM expenses e JOIN users u ON u.id = e.paid_by WHERE 1=1";
    $params = [];
    if ($q !== '') {
        $sql .= " AND (e.item_name LIKE :q OR e.category LIKE :q OR e.notes LIKE :q OR u.full_name LIKE :q)";
        $params[':q'] = '%' . $q . '%';
    }
    if ($dateFrom !== '') { $sql .= " AND e.expense_date >= :df"; $params[':df'] = $dateFrom; }
    if ($dateTo !== '') { $sql .= " AND e.expense_date <= :dt"; $params[':dt'] = $dateTo; }
    $sql .= " ORDER BY e.expense_date DESC LIMIT 50";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $expRows = $st->fetchAll();
}

$pageTitle = 'Search & Filter';
$activePage = 'search.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Search &amp; Filtering</h1>
    <p>Search dose logs, appointments, and expenses by keyword, category/status, or date range.</p>
  </div>
</div>

<div class="card">
  <form method="get" action="search.php" class="form-grid">
    <div class="form-group">
      <label for="q">Keyword (medicine, item, doctor, member, note)</label>
      <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g., Metformin, Losartan, Cruz">
    </div>
    <div class="form-group">
      <label for="module">Record Type</label>
      <select id="module" name="module">
        <option value="all" <?= $module === 'all' ? 'selected' : '' ?>>All Records</option>
        <option value="doses" <?= $module === 'doses' ? 'selected' : '' ?>>Dose Logs</option>
        <option value="appointments" <?= $module === 'appointments' ? 'selected' : '' ?>>Appointments</option>
        <option value="expenses" <?= $module === 'expenses' ? 'selected' : '' ?>>Expenses</option>
      </select>
    </div>
    <div class="form-group">
      <label for="date_from">From Date</label>
      <input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>">
    </div>
    <div class="form-group">
      <label for="date_to">To Date</label>
      <input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>">
    </div>
    <div class="btn-row" style="grid-column: 1 / -1;">
      <button type="submit" class="btn btn-primary">Apply Filters</button>
      <a href="search.php" class="btn btn-outline">Reset</a>
    </div>
  </form>
</div>

<?php if ($module === 'all' || $module === 'doses'): ?>
<div class="card">
  <h2>Dose Logs (<?= count($doseRows) ?>)</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Medicine</th><th>Slot</th><th>Status</th><th>Logged By / Note</th></tr></thead>
      <tbody>
        <?php foreach ($doseRows as $r): ?>
          <tr>
            <td><?= e($r['log_date']) ?></td>
            <td><strong><?= e($r['med_name']) ?></strong></td>
            <td><?= e($r['scheduled_time']) ?></td>
            <td><span class="badge badge-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
            <td><?= e($r['user_name']) ?><?= $r['note'] ? ' — ' . e($r['note']) : '' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($module === 'all' || $module === 'appointments'): ?>
<div class="card">
  <h2>Appointments (<?= count($apptRows) ?>)</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Doctor</th><th>Clinic</th><th>Notes</th></tr></thead>
      <tbody>
        <?php foreach ($apptRows as $r): ?>
          <tr>
            <td><?= e($r['appointment_date']) ?></td>
            <td><strong><?= e($r['doctor_name']) ?></strong></td>
            <td><?= e($r['clinic_or_hospital']) ?></td>
            <td><?= e($r['purpose_or_notes']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($module === 'all' || $module === 'expenses'): ?>
<div class="card">
  <h2>Expenses (<?= count($expRows) ?>)</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Item</th><th>Category</th><th>Amount</th><th>Paid By</th></tr></thead>
      <tbody>
        <?php foreach ($expRows as $r): ?>
          <tr>
            <td><?= e($r['expense_date']) ?></td>
            <td><strong><?= e($r['item_name']) ?></strong></td>
            <td><?= e(ucfirst($r['category'])) ?></td>
            <td>&#8369;<?= number_format((float)$r['amount'], 2) ?></td>
            <td><?= e($r['payer_name']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
