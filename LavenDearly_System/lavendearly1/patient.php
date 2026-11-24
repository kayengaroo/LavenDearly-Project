<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_perm('patient.edit');
    $name = post('full_name'); $birth = post('birthdate');
    if ($name === '') { flash('The patient name is required.', 'error'); }
    elseif ($birth !== '' && (!valid_date($birth) || $birth > date('Y-m-d'))) { flash('Enter a valid birthdate that is not in the future.', 'error'); }
    else {
        $pdo->prepare('UPDATE patient SET full_name=?, birthdate=?, allergies=?, conditions=?, primary_doctor=?, doctor_contact=?, emergency_contacts=?, updated_at=? WHERE id=1')
            ->execute([$name, $birth ?: null, post('allergies'), post('conditions'), post('primary_doctor'), post('doctor_contact'), post('emergency_contacts'), date('Y-m-d H:i:s')]);
        audit('update', 'patient profile', 'Profile updated');
        flash('Patient profile saved.');
    }
    redirect('patient.php');
}

$p = $pdo->query('SELECT * FROM patient WHERE id = 1')->fetch();
$age = $p['birthdate'] ? (int)date_diff(date_create($p['birthdate']), date_create('today'))->y : null;
$edit = can('patient.edit') && isset($_GET['edit']);
$pageTitle = 'Patient Profile'; $active = 'patient';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div><h1>Patient profile</h1><p class="muted">Basic medical details that doctors and emergency staff will ask for.</p></div>
  <?php if (can('patient.edit') && !$edit): ?><a class="btn" href="patient.php?edit=1">Edit profile</a><?php endif; ?>
</div>

<?php if ($edit): ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="form-grid">
    <div class="field"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" required value="<?= e($p['full_name']) ?>"></div>
    <div class="field"><label for="birthdate">Birthdate</label><input type="date" id="birthdate" name="birthdate" max="<?= date('Y-m-d') ?>" value="<?= e($p['birthdate']) ?>"></div>
    <div class="field"><label for="primary_doctor">Primary doctor</label><input type="text" id="primary_doctor" name="primary_doctor" value="<?= e($p['primary_doctor']) ?>"></div>
    <div class="field"><label for="doctor_contact">Doctor's contact</label><input type="text" id="doctor_contact" name="doctor_contact" value="<?= e($p['doctor_contact']) ?>"></div>
  </div>
  <div class="field"><label for="allergies">Allergies</label><textarea id="allergies" name="allergies"><?= e($p['allergies']) ?></textarea></div>
  <div class="field"><label for="conditions">Medical conditions</label><textarea id="conditions" name="conditions"><?= e($p['conditions']) ?></textarea></div>
  <div class="field"><label for="emergency_contacts">Emergency contacts</label><textarea id="emergency_contacts" name="emergency_contacts"><?= e($p['emergency_contacts']) ?></textarea>
    <div class="hint">One contact per line: name (relationship) - phone number.</div></div>
  <div class="actions"><button class="btn" type="submit">Save profile</button><a class="btn btn-ghost" href="patient.php">Cancel</a></div>
</form>
<?php else: ?>
<div class="card">
  <h2><?= e($p['full_name']) ?></h2>
  <dl class="kv">
    <dt>Birthdate</dt><dd><?= $p['birthdate'] ? fdate($p['birthdate']) . " ($age years old)" : '—' ?></dd>
    <dt>Allergies</dt><dd><?= e($p['allergies'] ?: 'None recorded') ?></dd>
    <dt>Medical conditions</dt><dd><?= e($p['conditions'] ?: '—') ?></dd>
    <dt>Primary doctor</dt><dd><?= e($p['primary_doctor'] ?: '—') ?><?= $p['doctor_contact'] ? ' · ' . e($p['doctor_contact']) : '' ?></dd>
    <dt>Emergency contacts</dt><dd><?= e($p['emergency_contacts'] ?: '—') ?></dd>
    <dt>Last updated</dt><dd><?= $p['updated_at'] ? fdate($p['updated_at']) : '—' ?></dd>
  </dl>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
