<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
require_perm('doses.view');
$pdo = db();
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return = in_array(post('return'), ['dashboard.php', 'doselog.php'], true) ? post('return') : 'doselog.php';
    $action = post('action');

    if ($action === 'log') {
        require_perm('dose.log');
        $mid = (int)post('medicine_id'); $d = post('scheduled_date'); $t = post('scheduled_time');
        $status = post('status'); $note = mb_substr(post('note'), 0, 255); $actual = post('actual_time');
        $err = null;
        if (!in_array($status, ['given', 'missed', 'skipped'], true)) $err = 'Choose given, missed or skipped.';
        elseif (!valid_date($d)) $err = 'Invalid date.';
        elseif ($d > $today) $err = 'You cannot log a dose for a future date.';
        elseif ($actual !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $actual)) $err = 'Invalid actual time.';
        if (!$err) {
            // the dose must really be on the schedule for that date
            $found = false;
            foreach (scheduled_doses($d) as $r) if ((int)$r['med_id'] === $mid && $r['time'] === $t) $found = true;
            if (!$found) $err = 'That dose is not on the schedule.';
        }
        if (!$err) {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM dose_log WHERE medicine_id=? AND scheduled_date=? AND scheduled_time=?');
            $dup->execute([$mid, $d, $t . ':00']);
            if ($dup->fetchColumn() > 0) $err = 'That dose has already been logged.';
        }
        if ($err) { flash($err, 'error'); redirect($return); }
        $actualTime = $status === 'given' ? ($actual !== '' ? $actual . ':00' : date('H:i:s')) : null;
        try {
            $pdo->prepare('INSERT INTO dose_log (medicine_id, scheduled_date, scheduled_time, status, actual_time, logged_by, note, created_at) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$mid, $d, $t . ':00', $status, $actualTime, $me['id'], $note ?: null, date('Y-m-d H:i:s')]);
            $mn = $pdo->prepare('SELECT name FROM medicines WHERE id=?'); $mn->execute([$mid]);
            audit('dose_' . $status, 'medicine #' . $mid, $mn->fetchColumn() . " scheduled $d $t");
            flash('Dose logged as ' . $status . '.');
        } catch (PDOException $ex) {
            flash('That dose has already been logged.', 'error');
        }
        redirect($return);
    }

    if ($action === 'delete') {
        require_perm('dose.delete');
        $id = (int)post('id');
        $pdo->prepare('DELETE FROM dose_log WHERE id = ?')->execute([$id]);
        audit('delete', "dose_log #$id", 'Dose entry removed by administrator');
        flash('Dose entry removed so it can be logged again.');
        redirect('doselog.php?date=' . urlencode(post('date', $today)));
    }
    redirect('doselog.php');
}

$date = valid_date($_GET['date'] ?? '') ? $_GET['date'] : $today;
$rows = scheduled_doses($date);
$history = $pdo->prepare('SELECT d.*, m.name, m.dose, u.full_name AS logger FROM dose_log d
    JOIN medicines m ON m.id = d.medicine_id JOIN users u ON u.id = d.logged_by
    ORDER BY d.scheduled_date DESC, d.scheduled_time DESC LIMIT 40');
$history->execute();
$history = $history->fetchAll();

$pageTitle = 'Dose Log'; $active = 'doselog';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div><h1>Dose log</h1><p class="muted">Record each scheduled dose as given, missed or skipped. Each dose can be logged only once.</p></div>
  <form method="get" class="actions"><label for="date" style="margin:0">Date</label>
    <input type="date" id="date" name="date" max="<?= $today ?>" value="<?= e($date) ?>" style="width:auto">
    <button class="btn btn-sm">Show</button></form>
</div>

<section class="card">
  <h2>Schedule for <?= fdate($date) ?></h2>
  <?php if (!$rows): ?><div class="empty">No medicines were scheduled on this date.</div><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Time</th><th>Medicine</th><th>Status</th><th>Log this dose</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= ftime($r['time']) ?></td>
        <td><strong><?= e($r['name']) ?></strong><br><span class="muted small"><?= e($r['dose']) ?></span></td>
        <td><?= chip($r['state']) ?>
          <?php if ($r['log']): ?><br><span class="muted small">by <?= e($r['log']['logger']) ?><?= $r['log']['actual_time'] ? ' at ' . ftime($r['log']['actual_time']) : '' ?><?= $r['log']['note'] ? '<br>“' . e($r['log']['note']) . '”' : '' ?></span><?php endif; ?></td>
        <td>
          <?php if (!$r['log'] && $r['state'] !== 'upcoming' && can('dose.log')): ?>
          <form method="post" class="actions">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="log"><input type="hidden" name="return" value="doselog.php">
            <input type="hidden" name="medicine_id" value="<?= (int)$r['med_id'] ?>">
            <input type="hidden" name="scheduled_date" value="<?= e($r['date']) ?>">
            <input type="hidden" name="scheduled_time" value="<?= e($r['time']) ?>">
            <input type="time" name="actual_time" aria-label="Actual time given" style="width:130px" title="Actual time given (optional)">
            <input type="text" name="note" maxlength="255" placeholder="Note (optional)" aria-label="Note" style="width:190px">
            <button class="btn btn-sm btn-dusty" name="status" value="given">Given</button>
            <button class="btn btn-sm btn-ghost" name="status" value="missed">Missed</button>
            <button class="btn btn-sm btn-ghost" name="status" value="skipped">Skipped</button>
          </form>
          <?php elseif ($r['log'] && can('dose.delete')): ?>
          <form method="post" onsubmit="return confirm('Remove this entry so the dose can be logged again?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['log']['id'] ?>"><input type="hidden" name="date" value="<?= e($date) ?>">
            <button class="btn btn-sm btn-danger">Correct entry</button></form>
          <?php else: ?><span class="muted small">—</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Recent log entries</h2>
  <?php if (!$history): ?><div class="empty">Nothing has been logged yet.</div><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Scheduled</th><th>Medicine</th><th>Status</th><th>Actual time</th><th>Logged by</th><th>Note</th></tr></thead>
    <tbody>
    <?php foreach ($history as $h): ?>
      <tr><td><?= fdate($h['scheduled_date']) ?> · <?= ftime($h['scheduled_time']) ?></td>
        <td><?= e($h['name']) ?> <span class="muted small"><?= e($h['dose']) ?></span></td>
        <td><?= chip($h['status']) ?></td><td><?= ftime($h['actual_time']) ?></td>
        <td><?= e($h['logger']) ?></td><td><?= e($h['note'] ?: '') ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
