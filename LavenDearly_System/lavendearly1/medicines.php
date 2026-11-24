<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
$pdo = db();
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_perm('medicine.manage');
    $action = post('action');
    if ($action === 'save') {
        $id = (int)post('id');
        $name = post('name'); $dose = post('dose'); $times = parse_times(post('times'));
        $start = post('start_date'); $refill = post('refill_date'); $doc = post('prescribing_doctor');
        $err = null;
        if ($name === '' || $dose === '') $err = 'Medicine name and dose are required.';
        elseif (!$times) $err = 'Enter times of day like 08:00, 20:00.';
        elseif (!valid_date($start)) $err = 'Enter a valid start date.';
        elseif ($refill !== '' && (!valid_date($refill) || $refill < $start)) $err = 'The refill date must be a valid date on or after the start date.';
        if ($err) { flash($err, 'error'); redirect('medicines.php' . ($id ? "?edit=$id" : '')); }
        if ($id) {
            $pdo->prepare('UPDATE medicines SET name=?, dose=?, times_of_day=?, start_date=?, refill_date=?, prescribing_doctor=? WHERE id=?')
                ->execute([$name, $dose, implode(',', $times), $start, $refill ?: null, $doc, $id]);
            audit('update', "medicine #$id", "$name $dose");
            flash('Medicine updated.');
        } else {
            $pdo->prepare("INSERT INTO medicines (name, dose, times_of_day, start_date, refill_date, prescribing_doctor, status) VALUES (?,?,?,?,?,?, 'active')")
                ->execute([$name, $dose, implode(',', $times), $start, $refill ?: null, $doc]);
            audit('create', 'medicine #' . $pdo->lastInsertId(), "$name $dose");
            flash('Medicine added to the schedule.');
        }
    } elseif ($action === 'discontinue' || $action === 'reactivate') {
        $id = (int)post('id');
        if ($action === 'discontinue') {
            $pdo->prepare("UPDATE medicines SET status='discontinued', end_date=? WHERE id=?")->execute([$today, $id]);
        } else {
            $pdo->prepare("UPDATE medicines SET status='active', end_date=NULL WHERE id=?")->execute([$id]);
        }
        audit($action, "medicine #$id", '');
        flash($action === 'discontinue' ? 'Medicine marked as discontinued. Past dose history is kept.' : 'Medicine reactivated.');
    }
    redirect('medicines.php');
}

$editRow = null;
if (can('medicine.manage') && isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM medicines WHERE id = ?'); $st->execute([(int)$_GET['edit']]);
    $editRow = $st->fetch() ?: null;
}
$activeMeds = $pdo->query("SELECT * FROM medicines WHERE status='active' ORDER BY name")->fetchAll();
$stopped = $pdo->query("SELECT * FROM medicines WHERE status='discontinued' ORDER BY name")->fetchAll();

$pageTitle = 'Medicine Schedule'; $active = 'medicines';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div><h1>Medicine schedule</h1><p class="muted">Follow the prescription and the doctor's instructions. Discontinue a medicine instead of deleting it, so dose history stays intact.</p></div>
</div>

<?php if (can('medicine.manage')): ?>
<details class="card" <?= $editRow ? 'open' : '' ?>>
  <summary><?= $editRow ? 'Edit medicine' : 'Add a medicine' ?></summary>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $editRow ? (int)$editRow['id'] : 0 ?>">
    <div class="form-grid">
      <div class="field"><label for="name">Medicine name</label><input type="text" id="name" name="name" required value="<?= e($editRow['name'] ?? '') ?>"></div>
      <div class="field"><label for="dose">Dose</label><input type="text" id="dose" name="dose" required placeholder="e.g. 50 mg, 1 tablet" value="<?= e($editRow['dose'] ?? '') ?>"></div>
      <div class="field"><label for="times">Times of day</label><input type="text" id="times" name="times" required placeholder="08:00, 20:00" value="<?= e($editRow['times_of_day'] ?? '') ?>"><div class="hint">24-hour format, separated by commas.</div></div>
      <div class="field"><label for="start_date">Start date</label><input type="date" id="start_date" name="start_date" required value="<?= e($editRow['start_date'] ?? $today) ?>"></div>
      <div class="field"><label for="refill_date">Refill date</label><input type="date" id="refill_date" name="refill_date" value="<?= e($editRow['refill_date'] ?? '') ?>"></div>
      <div class="field"><label for="prescribing_doctor">Prescribing doctor</label><input type="text" id="prescribing_doctor" name="prescribing_doctor" value="<?= e($editRow['prescribing_doctor'] ?? '') ?>"></div>
    </div>
    <div class="actions"><button class="btn" type="submit"><?= $editRow ? 'Save changes' : 'Add medicine' ?></button>
      <?php if ($editRow): ?><a class="btn btn-ghost" href="medicines.php">Cancel</a><?php endif; ?></div>
  </form>
</details>
<?php endif; ?>

<section class="card">
  <h2>Active medicines</h2>
  <?php if (!$activeMeds): ?><div class="empty">No active medicines.</div><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Medicine</th><th>Dose</th><th>Times</th><th>Started</th><th>Refill date</th><th>Prescribed by</th><?php if (can('medicine.manage')): ?><th></th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($activeMeds as $m):
      $left = $m['refill_date'] ? (int)floor((strtotime($m['refill_date']) - strtotime($today)) / 86400) : null; ?>
      <tr>
        <td><strong><?= e($m['name']) ?></strong></td>
        <td><?= e($m['dose']) ?></td>
        <td><?= e(implode(', ', array_map('ftime', explode(',', $m['times_of_day'])))) ?></td>
        <td><?= fdate($m['start_date']) ?></td>
        <td><?= fdate($m['refill_date']) ?>
          <?php if ($left !== null && $left <= REFILL_WARN_DAYS): ?><br><span class="chip chip-overdue"><?= $left < 0 ? 'Refill overdue' : "Refill in $left d" ?></span><?php endif; ?></td>
        <td><?= e($m['prescribing_doctor'] ?: '—') ?></td>
        <?php if (can('medicine.manage')): ?>
        <td class="actions">
          <a class="btn btn-sm btn-ghost" href="medicines.php?edit=<?= (int)$m['id'] ?>">Edit</a>
          <form method="post" class="inline-form" onsubmit="return confirm('Mark this medicine as discontinued?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="discontinue"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <button class="btn btn-sm btn-danger">Discontinue</button></form>
        </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>

<?php if ($stopped): ?>
<section class="card">
  <h2>Discontinued</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>Medicine</th><th>Dose</th><th>Stopped</th><?php if (can('medicine.manage')): ?><th></th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($stopped as $m): ?>
      <tr><td><?= e($m['name']) ?></td><td><?= e($m['dose']) ?></td><td><?= fdate($m['end_date']) ?></td>
        <?php if (can('medicine.manage')): ?>
        <td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="reactivate"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn btn-sm btn-ghost">Reactivate</button></form></td>
        <?php endif; ?></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
