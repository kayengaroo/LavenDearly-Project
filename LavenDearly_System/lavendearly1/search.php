<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
$pdo = db();

$types = ['appointments' => 'Appointments & documents', 'expenses' => 'Expenses'];
if (can('doses.view')) $types = ['doses' => 'Dose logs'] + $types;
$type = array_key_exists($_GET['type'] ?? '', $types) ? $_GET['type'] : array_key_first($types);
$q = trim($_GET['q'] ?? '');
$from = valid_date($_GET['from'] ?? '') ? $_GET['from'] : '';
$to = valid_date($_GET['to'] ?? '') ? $_GET['to'] : '';
$member = (int)($_GET['member'] ?? 0);
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$medicine = (int)($_GET['medicine'] ?? 0);
$searched = isset($_GET['type']);

$users = $pdo->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll();
$meds = $pdo->query('SELECT id, name FROM medicines ORDER BY name')->fetchAll();
$cats = ['Medicines', 'Groceries', 'Check-up fees', 'Supplies', 'Other'];
$results = [];

if ($searched) {
    $w = []; $a = [];
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    if ($type === 'doses') {
        $sql = 'SELECT d.*, m.name, m.dose, u.full_name AS logger FROM dose_log d JOIN medicines m ON m.id=d.medicine_id JOIN users u ON u.id=d.logged_by';
        if ($q !== '') { $w[] = '(m.name LIKE ? OR d.note LIKE ?)'; array_push($a, $like, $like); }
        if ($from) { $w[] = 'd.scheduled_date >= ?'; $a[] = $from; }
        if ($to) { $w[] = 'd.scheduled_date <= ?'; $a[] = $to; }
        if ($member) { $w[] = 'd.logged_by = ?'; $a[] = $member; }
        if (in_array($status, ['given', 'missed', 'skipped'], true)) { $w[] = 'd.status = ?'; $a[] = $status; }
        if ($medicine) { $w[] = 'd.medicine_id = ?'; $a[] = $medicine; }
        $order = ' ORDER BY d.scheduled_date DESC, d.scheduled_time DESC';
    } elseif ($type === 'appointments') {
        $sql = 'SELECT a.*, u.full_name AS creator FROM appointments a JOIN users u ON u.id=a.created_by';
        if ($q !== '') { $w[] = '(a.title LIKE ? OR a.doctor LIKE ? OR a.notes LIKE ?)'; array_push($a, $like, $like, $like); }
        if ($from) { $w[] = 'a.appt_date >= ?'; $a[] = $from; }
        if ($to) { $w[] = 'a.appt_date <= ?'; $a[] = $to; }
        if ($member) { $w[] = 'a.created_by = ?'; $a[] = $member; }
        if (in_array($status, ['visit', 'lab', 'prescription'], true)) { $w[] = 'a.type = ?'; $a[] = $status; }
        $order = ' ORDER BY a.appt_date DESC';
    } else {
        $sql = 'SELECT x.*, u.full_name AS payer FROM expenses x LEFT JOIN users u ON u.id=x.paid_by';
        if ($q !== '') { $w[] = 'x.item LIKE ?'; $a[] = $like; }
        if ($from) { $w[] = 'x.expense_date >= ?'; $a[] = $from; }
        if ($to) { $w[] = 'x.expense_date <= ?'; $a[] = $to; }
        if ($member) { $w[] = 'x.paid_by = ?'; $a[] = $member; }
        if (in_array($category, $cats, true)) { $w[] = 'x.category = ?'; $a[] = $category; }
        $order = ' ORDER BY x.expense_date DESC';
    }
    $st = $pdo->prepare($sql . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . $order . ' LIMIT 200');
    $st->execute($a);
    $results = $st->fetchAll();
}

$pageTitle = 'Search & Filter'; $active = 'search';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>Search &amp; filter</h1><p class="muted">Find dose logs, appointments and expenses by date, medicine, family member or keyword.</p></div></div>

<form method="get" class="card">
  <div class="form-grid">
    <div class="field"><label for="type">Search in</label><select id="type" name="type"><?php foreach ($types as $k => $v): ?><option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="q">Keyword</label><input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="medicine, doctor, item, note…"></div>
    <div class="field"><label for="from">From date</label><input type="date" id="from" name="from" value="<?= e($from) ?>"></div>
    <div class="field"><label for="to">To date</label><input type="date" id="to" name="to" value="<?= e($to) ?>"></div>
    <div class="field"><label for="member">Family member</label><select id="member" name="member"><option value="0">Anyone</option><?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= $member === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option><?php endforeach; ?></select><div class="hint">Dose logs: who logged · Appointments: who added · Expenses: who paid</div></div>
    <div class="field"><label for="status">Status / record type</label><select id="status" name="status"><option value="">Any</option>
      <?php foreach (['given', 'missed', 'skipped'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>Dose: <?= $s ?></option><?php endforeach; ?>
      <?php foreach (['visit' => 'Doctor visit', 'lab' => 'Lab result', 'prescription' => 'Prescription'] as $k => $v): ?><option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="category">Expense category</label><select id="category" name="category"><option value="">Any</option><?php foreach ($cats as $c): ?><option <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
    <?php if (can('doses.view')): ?><div class="field"><label for="medicine">Medicine</label><select id="medicine" name="medicine"><option value="0">Any</option><?php foreach ($meds as $m): ?><option value="<?= (int)$m['id'] ?>" <?= $medicine === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
  </div>
  <div class="actions"><button class="btn" type="submit">Search</button><a class="btn btn-ghost" href="search.php">Clear</a></div>
</form>

<?php if ($searched): ?>
<section class="card">
  <h2><?= count($results) ?> result<?= count($results) === 1 ? '' : 's' ?><?= count($results) === 200 ? ' (showing the first 200)' : '' ?></h2>
  <?php if (!$results): ?><div class="empty">Nothing matches those filters.</div><?php else: ?>
  <div class="table-wrap"><table>
  <?php if ($type === 'doses'): ?>
    <thead><tr><th>Scheduled</th><th>Medicine</th><th>Status</th><th>Actual time</th><th>Logged by</th><th>Note</th></tr></thead><tbody>
    <?php foreach ($results as $r): ?><tr><td><?= fdate($r['scheduled_date']) ?> · <?= ftime($r['scheduled_time']) ?></td><td><?= e($r['name']) ?> <span class="muted small"><?= e($r['dose']) ?></span></td><td><?= chip($r['status']) ?></td><td><?= ftime($r['actual_time']) ?></td><td><?= e($r['logger']) ?></td><td><?= e($r['note'] ?: '') ?></td></tr><?php endforeach; ?>
  <?php elseif ($type === 'appointments'): ?>
    <thead><tr><th>Date</th><th>Type</th><th>Title</th><th>Doctor</th><th>Notes</th><th>File</th></tr></thead><tbody>
    <?php foreach ($results as $r): ?><tr><td><?= fdate($r['appt_date']) ?></td><td><?= e(ucfirst($r['type'])) ?></td><td><?= e($r['title']) ?></td><td><?= e($r['doctor'] ?: '—') ?></td><td><?= e($r['notes'] ?: '') ?></td><td><?= $r['file_path'] ? '<a href="file.php?t=appointment&id=' . (int)$r['id'] . '" target="_blank" rel="noopener">View</a>' : '—' ?></td></tr><?php endforeach; ?>
  <?php else: ?>
    <thead><tr><th>Date</th><th>Item</th><th>Category</th><th class="num">Amount</th><th>Paid by</th></tr></thead><tbody>
    <?php foreach ($results as $r): ?><tr><td><?= fdate($r['expense_date']) ?></td><td><?= e($r['item']) ?></td><td><?= e($r['category']) ?></td><td class="num"><?= peso($r['amount']) ?></td><td><?= $r['payer'] ? e($r['payer']) : 'Shared care fund' ?></td></tr><?php endforeach; ?>
  <?php endif; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
