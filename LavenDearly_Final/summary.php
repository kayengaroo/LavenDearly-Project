<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
require_perm('summary.print');
$pdo = db();
$today = date('Y-m-d');

$p = $pdo->query('SELECT * FROM patient WHERE id = 1')->fetch();
$age = $p['birthdate'] ? (int)date_diff(date_create($p['birthdate']), date_create('today'))->y : null;
$meds = $pdo->query("SELECT * FROM medicines WHERE status = 'active' ORDER BY name")->fetchAll();
$appts = $pdo->prepare('SELECT * FROM appointments WHERE appt_date <= ? ORDER BY appt_date DESC, id DESC LIMIT 5');
$appts->execute([$today]); $appts = $appts->fetchAll();
$types = ['visit' => 'Doctor visit', 'lab' => 'Lab result', 'prescription' => 'Prescription'];

if (isset($_GET['logprint'])) {           // tiny background call made when the Print button is pressed
    audit('print', 'doctor summary', 'Summary printed');
    http_response_code(204);
    exit;
}

$pageTitle = 'Doctor Summary'; $active = 'summary';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head no-print">
  <div><h1>Doctor summary</h1><p class="muted">A one-page summary to hand to a doctor or emergency room.</p></div>
  <button class="btn" id="printBtn" type="button">Print summary</button>
</div>
<script>
  document.getElementById('printBtn').addEventListener('click', function () {
    fetch('summary.php?logprint=1', {credentials: 'same-origin'}).finally(function () { window.print(); });
  });
</script>

<article class="card" style="border-radius:28px">
  <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;border-bottom:3px solid var(--dusty);padding-bottom:12px;margin-bottom:16px">
    <div><h2 style="font-size:1.8rem;margin:0"><?= e($p['full_name']) ?></h2>
      <div class="muted"><?= $p['birthdate'] ? 'Born ' . fdate($p['birthdate']) . " · $age years old" : '' ?></div></div>
    <div style="text-align:right" class="small">LavenDearly patient summary<br>Printed <?= date('M j, Y g:i A') ?></div>
  </div>

  <div style="background:var(--beige);border-left:10px solid var(--berry);border-radius:18px;padding:10px 18px;margin-bottom:16px">
    <strong>ALLERGIES:</strong> <?= e($p['allergies'] ?: 'None recorded') ?>
  </div>

  <div class="grid g2">
    <div><h3>Medical conditions</h3><p><?= nl2br(e($p['conditions'] ?: '—')) ?></p></div>
    <div><h3>Primary doctor</h3><p><?= e($p['primary_doctor'] ?: '—') ?><?= $p['doctor_contact'] ? '<br>' . e($p['doctor_contact']) : '' ?></p></div>
  </div>

  <h3>Active medicines</h3>
  <?php if (!$meds): ?><p class="muted">No active medicines recorded.</p><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Medicine</th><th>Dose</th><th>Times</th><th>Prescribed by</th><th>Since</th></tr></thead><tbody>
    <?php foreach ($meds as $m): ?><tr><td><strong><?= e($m['name']) ?></strong></td><td><?= e($m['dose']) ?></td>
      <td><?= e(implode(', ', array_map('ftime', explode(',', $m['times_of_day'])))) ?></td><td><?= e($m['prescribing_doctor'] ?: '—') ?></td><td><?= fdate($m['start_date']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>

  <div class="grid g2" style="margin-top:16px">
    <div><h3>Emergency contacts</h3><p><?= nl2br(e($p['emergency_contacts'] ?: '—')) ?></p></div>
    <div><h3>Recent visits &amp; records</h3>
      <?php if (!$appts): ?><p class="muted">None recorded.</p><?php endif; ?>
      <?php foreach ($appts as $a): ?><p style="margin-bottom:6px"><strong><?= fdate($a['appt_date']) ?></strong> — <?= e($types[$a['type']]) ?>: <?= e($a['title']) ?><?= $a['doctor'] ? ' (' . e($a['doctor']) . ')' : '' ?></p><?php endforeach; ?>
    </div>
  </div>
  <p class="muted small" style="margin:14px 0 0;border-top:1px solid var(--taupe);padding-top:8px">Prepared from the family’s LavenDearly record. It supports record-keeping and does not replace the doctor’s own assessment or prescription.</p>
</article>
<?php include __DIR__ . '/includes/footer.php'; ?>
