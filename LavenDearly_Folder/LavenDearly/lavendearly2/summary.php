<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();
$patient = get_patient();

$activeMeds = $pdo->query("SELECT name, dosage, schedule_times, instructions, prescribing_doctor FROM medicines WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$emergencyContacts = $pdo->query("SELECT full_name, relationship_label, contact_number, location_note FROM family_members WHERE is_emergency_contact = 1 ORDER BY id ASC")->fetchAll();
$recentAppts = $pdo->query("SELECT appointment_date, appointment_time, doctor_name, clinic_or_hospital, purpose_or_notes FROM appointments ORDER BY appointment_date DESC LIMIT 5")->fetchAll();

$pageTitle = 'Doctor Summary';
$activePage = 'summary.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>One-Page Doctor &amp; Emergency Summary</h1>
    <p>Prepared for clinic check-ups, specialist visits, or urgent care reference.</p>
  </div>
  <button type="button" class="btn btn-primary no-print" onclick="window.print()">Print Summary</button>
</div>

<div class="card">
  <div style="display:flex; justify-content:space-between; border-bottom:2px solid var(--border); padding-bottom:12px; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
    <div>
      <h2 style="margin:0;">Patient: <?= e($patient['full_name']) ?></h2>
      <div class="kpi-sub">Birthdate: <?= e($patient['birthdate']) ?> • Blood Type: <?= e($patient['blood_type']) ?> • Address: <?= e($patient['address']) ?></div>
    </div>
    <div class="kpi-sub">Generated: <?= date('Y-m-d H:i') ?> by <?= e($currentUser['full_name']) ?></div>
  </div>

  <div class="grid-2" style="margin-bottom:16px;">
    <div>
      <h3>Allergies &amp; Conditions</h3>
      <p><strong>Known Allergies:</strong> <?= e($patient['allergies'] ?: 'None recorded') ?></p>
      <p><strong>Chronic Conditions:</strong> <?= e($patient['conditions'] ?: 'None recorded') ?></p>
      <p><strong>Primary Physician:</strong> <?= e($patient['primary_doctor']) ?> (<?= e($patient['primary_clinic']) ?>)</p>
    </div>
    <div>
      <h3>Emergency Contacts</h3>
      <table>
        <thead><tr><th>Contact</th><th>Role</th><th>Phone</th></tr></thead>
        <tbody>
          <?php foreach ($emergencyContacts as $ec): ?>
            <tr>
              <td><strong><?= e($ec['full_name']) ?></strong></td>
              <td><?= e($ec['relationship_label']) ?></td>
              <td><?= e($ec['contact_number']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <h3>Active Medications</h3>
  <div class="table-wrap" style="margin-bottom:16px;">
    <table>
      <thead><tr><th>Medicine</th><th>Dosage</th><th>Schedule Times</th><th>Instructions</th></tr></thead>
      <tbody>
        <?php foreach ($activeMeds as $m): ?>
          <tr>
            <td><strong><?= e($m['name']) ?></strong></td>
            <td><?= e($m['dosage']) ?></td>
            <td><?= e($m['schedule_times']) ?></td>
            <td><?= e($m['instructions']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h3>Recent &amp; Upcoming Appointments</h3>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Doctor / Clinic</th><th>Visit Notes</th></tr></thead>
      <tbody>
        <?php foreach ($recentAppts as $a): ?>
          <tr>
            <td><?= e($a['appointment_date']) ?> (<?= e($a['appointment_time']) ?>)</td>
            <td><strong><?= e($a['doctor_name']) ?></strong> — <?= e($a['clinic_or_hospital']) ?></td>
            <td><?= e($a['purpose_or_notes']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
