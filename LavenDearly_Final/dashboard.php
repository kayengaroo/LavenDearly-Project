<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
$pdo = db();
$today = date('Y-m-d');

$sched    = scheduled_doses($today);
$counts   = ['given' => 0, 'missed' => 0, 'skipped' => 0, 'overdue' => 0, 'pending' => 0];
foreach ($sched as $s) $counts[$s['state']]++;
$overdue  = overdue_doses(1);
$refills  = refills_due();

$st = $pdo->prepare('SELECT * FROM appointments WHERE appt_date >= ? ORDER BY appt_date, id LIMIT 5');
$st->execute([$today]);
$appts = $st->fetchAll();

$bal = balances();
$mine = null;
foreach ($bal['rows'] as $r) if ((int)$r['id'] === (int)$me['id']) $mine = $r;

$note = $pdo->query('SELECT n.*, u.full_name FROM care_notes n JOIN users u ON u.id = n.created_by ORDER BY n.note_date DESC, n.id DESC LIMIT 1')->fetch();
$patient = $pdo->query('SELECT full_name FROM patient WHERE id = 1')->fetch();

$hour = (int)date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$pageTitle = 'Dashboard'; $active = 'dashboard';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1><?= $greet ?>, <?= e(explode(' ', $me['full_name'])[0]) ?></h1>
    <p class="muted"><?= date('l, F j, Y') ?> · Caring for <strong><?= e($patient['full_name'] ?? 'the patient') ?></strong></p>
  </div>
</div>

<?php if ($overdue || $refills): ?>
<div class="alert-band" role="alert">
  <strong>Needs attention</strong>
  <ul>
    <?php foreach ($overdue as $o): ?>
      <li>Overdue: <?= e($o['name']) ?> <?= e($o['dose']) ?> was due <?= $o['date'] === $today ? 'today' : fdate($o['date']) ?> at <?= ftime($o['time']) ?> and has not been logged.</li>
    <?php endforeach; ?>
    <?php foreach ($refills as $r): $left = (int)floor((strtotime($r['refill_date']) - strtotime($today)) / 86400); ?>
      <li>Refill: <?= e($r['name']) ?> —
        <?= $left < 0 ? 'refill date passed ' . abs($left) . ' day(s) ago' : ($left === 0 ? 'refill due today' : "refill due in $left day(s)") ?> (<?= fdate($r['refill_date']) ?>).</li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="grid g4" style="margin-bottom:22px">
  <div class="stat s-plum"><div class="num"><?= $counts['given'] ?>/<?= count($sched) ?></div><div class="lbl">Doses given today</div><div class="sub"><?= $counts['pending'] ?> still pending</div></div>
  <div class="stat <?= $counts['overdue'] ? 's-berry' : 's-dusty' ?>"><div class="num"><?= count($overdue) ?></div><div class="lbl">Overdue doses</div><div class="sub">Not logged on time</div></div>
  <div class="stat s-beige"><div class="num"><?= count($refills) ?></div><div class="lbl">Refills due within <?= REFILL_WARN_DAYS ?> days</div><div class="sub">Includes past-due refills</div></div>
  <div class="stat s-dusty"><div class="num"><?= peso($bal['fund']) ?></div><div class="lbl">Care fund balance</div><div class="sub">Contributions minus fund-paid expenses</div></div>
</div>

<div class="split">
  <section class="card">
    <h2>Today's medicine schedule</h2>
    <?php if (!$sched): ?><div class="empty">No medicines are scheduled today.</div><?php else: ?>
    <div class="table-wrap"><table>
      <thead><tr><th>Time</th><th>Medicine</th><th>Status</th><?php if (can('dose.log')): ?><th>Log</th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach ($sched as $s): ?>
        <tr>
          <td><?= ftime($s['time']) ?></td>
          <td><strong><?= e($s['name']) ?></strong><br><span class="muted small"><?= e($s['dose']) ?></span></td>
          <td><?= chip($s['state']) ?>
              <?php if ($s['log']): ?><br><span class="muted small">by <?= e($s['log']['logger']) ?><?= $s['log']['actual_time'] ? ' at ' . ftime($s['log']['actual_time']) : '' ?></span><?php endif; ?></td>
          <?php if (can('dose.log')): ?>
          <td>
            <?php if (!$s['log']): ?>
            <form method="post" action="doselog.php" class="actions">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="log">
              <input type="hidden" name="return" value="dashboard.php">
              <input type="hidden" name="medicine_id" value="<?= (int)$s['med_id'] ?>">
              <input type="hidden" name="scheduled_date" value="<?= e($s['date']) ?>">
              <input type="hidden" name="scheduled_time" value="<?= e($s['time']) ?>">
              <button class="btn btn-sm btn-dusty" name="status" value="given">Given</button>
              <button class="btn btn-sm btn-ghost" name="status" value="missed">Missed</button>
              <button class="btn btn-sm btn-ghost" name="status" value="skipped">Skipped</button>
            </form>
            <?php else: ?><span class="muted small">Logged</span><?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>

  <div>
    <section class="card">
      <h2>Upcoming appointments</h2>
      <?php if (!$appts): ?><p class="muted">Nothing scheduled.</p><?php endif; ?>
      <?php foreach ($appts as $a): ?>
        <p><strong><?= e($a['title']) ?></strong><br><span class="muted small"><?= fdate($a['appt_date']) ?><?= $a['doctor'] ? ' · ' . e($a['doctor']) : '' ?></span></p>
      <?php endforeach; ?>
      <a class="btn btn-sm btn-ghost" href="appointments.php">All appointments</a>
    </section>
    <?php if ($mine && (float)$mine['share_percent'] > 0): ?>
    <section class="card">
      <h2>Your balance</h2>
      <p style="font-size:1.6rem;margin:0" class="<?= $mine['balance'] >= 0 ? 'pos' : 'neg' ?>"><?= peso(abs($mine['balance'])) ?></p>
      <p class="muted"><?= $mine['balance'] >= 0 ? 'You have paid more than your agreed share.' : 'Still to contribute toward your agreed share.' ?></p>
      <a class="btn btn-sm btn-ghost" href="contributions.php">See all balances</a>
    </section>
    <?php endif; ?>
  </div>
</div>

<section class="card">
  <h2>Latest care note</h2>
  <?php if ($note): ?>
    <p><strong><?= fdate($note['note_date']) ?></strong> · <span class="muted">by <?= e($note['full_name']) ?></span></p>
    <p>Mood: <?= e($note['mood'] ?: '—') ?> · Meals: <?= e($note['meals'] ?: '—') ?> · Sleep: <?= e($note['sleep'] ?: '—') ?></p>
    <?php if ($note['concerns']): ?><p>Concerns: <?= e($note['concerns']) ?></p><?php endif; ?>
    <a class="btn btn-sm btn-ghost" href="notes.php">Open care notes</a>
  <?php else: ?><p class="muted">No care notes yet.</p><?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
