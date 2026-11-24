<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();
$patient = get_patient();

$canEdit = in_array($currentUser['role'], ['administrator', 'caregiver'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_role(['administrator', 'caregiver'], $currentUser);

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $birthdate = trim((string)($_POST['birthdate'] ?? ''));
    $bloodType = trim((string)($_POST['blood_type'] ?? ''));
    $allergies = trim((string)($_POST['allergies'] ?? ''));
    $conditions = trim((string)($_POST['conditions'] ?? ''));
    $primaryDoctor = trim((string)($_POST['primary_doctor'] ?? ''));
    $primaryClinic = trim((string)($_POST['primary_clinic'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $careNotesSummary = trim((string)($_POST['care_notes_summary'] ?? ''));

    $dateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
    if ($fullName === '' || !$dateObj || $dateObj->format('Y-m-d') !== $birthdate || $address === '') {
        set_flash('error', 'Please provide a valid patient name, valid birthdate (YYYY-MM-DD), and home address.');
    } else {
        $stmt = $pdo->prepare('UPDATE patients SET full_name = :fn, birthdate = :bd, blood_type = :bt, allergies = :al, conditions = :co, primary_doctor = :doc, primary_clinic = :cl, address = :addr, care_notes_summary = :sum WHERE id = :id');
        $stmt->execute([
            ':fn' => $fullName,
            ':bd' => $birthdate,
            ':bt' => $bloodType,
            ':al' => $allergies,
            ':co' => $conditions,
            ':doc' => $primaryDoctor,
            ':cl' => $primaryClinic,
            ':addr' => $address,
            ':sum' => $careNotesSummary,
            ':id' => (int)$patient['id'],
        ]);
        log_audit((int)$currentUser['id'], 'UPDATE', 'Patient Profile', 'PATIENT-' . $patient['id'], 'Updated patient profile for ' . $fullName);
        set_flash('success', 'Patient profile updated.');
        header('Location: patient.php');
        exit;
    }
}

$emergencyContacts = $pdo->query('SELECT * FROM family_members WHERE is_emergency_contact = 1 ORDER BY id ASC')->fetchAll();
$patient = get_patient();

$pageTitle = 'Patient Profile';
$activePage = 'patient.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Patient Profile</h1>
    <p>Core medical background, allergies, conditions, and emergency contacts for <?= e($patient['full_name']) ?>.</p>
  </div>
  <a href="summary.php" class="btn btn-primary">View Printable Doctor Summary</a>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Elderly Patient Details</h2>
    <p><strong>Full Name:</strong> <?= e($patient['full_name']) ?></p>
    <p><strong>Birthdate:</strong> <?= e($patient['birthdate']) ?></p>
    <p><strong>Blood Type:</strong> <?= e($patient['blood_type']) ?></p>
    <p><strong>Home Address:</strong> <?= e($patient['address']) ?></p>
    <p><strong>Known Allergies:</strong> <span class="badge badge-missed"><?= e($patient['allergies'] ?: 'None recorded') ?></span></p>
    <p><strong>Medical Conditions:</strong> <?= e($patient['conditions'] ?: 'None recorded') ?></p>
    <p><strong>Primary Doctor:</strong> <?= e($patient['primary_doctor']) ?> (<?= e($patient['primary_clinic']) ?>)</p>
    <p><strong>Care Guidance:</strong> <?= e($patient['care_notes_summary']) ?></p>

    <h3>Emergency Contacts</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Relationship</th><th>Phone</th><th>Note</th></tr></thead>
        <tbody>
          <?php foreach ($emergencyContacts as $ec): ?>
            <tr>
              <td><strong><?= e($ec['full_name']) ?></strong></td>
              <td><?= e($ec['relationship_label']) ?></td>
              <td><?= e($ec['contact_number']) ?></td>
              <td><?= e($ec['location_note']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>Edit Patient Profile</h2>
    <?php if (!$canEdit): ?>
      <div class="flash flash-warning">Contributors have read-only access to the Patient Profile. Contact an Administrator or Caregiver for updates.</div>
    <?php else: ?>
      <form method="post" action="patient.php">
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="full_name">Full Name *</label>
          <input type="text" id="full_name" name="full_name" required value="<?= e($patient['full_name']) ?>">
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="birthdate">Birthdate *</label>
            <input type="date" id="birthdate" name="birthdate" required value="<?= e($patient['birthdate']) ?>">
          </div>
          <div class="form-group">
            <label for="blood_type">Blood Type</label>
            <input type="text" id="blood_type" name="blood_type" value="<?= e($patient['blood_type']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="address">Address *</label>
          <input type="text" id="address" name="address" required value="<?= e($patient['address']) ?>">
        </div>
        <div class="form-group">
          <label for="allergies">Allergies</label>
          <input type="text" id="allergies" name="allergies" value="<?= e($patient['allergies']) ?>">
        </div>
        <div class="form-group">
          <label for="conditions">Medical Conditions</label>
          <textarea id="conditions" name="conditions"><?= e($patient['conditions']) ?></textarea>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="primary_doctor">Primary Doctor</label>
            <input type="text" id="primary_doctor" name="primary_doctor" value="<?= e($patient['primary_doctor']) ?>">
          </div>
          <div class="form-group">
            <label for="primary_clinic">Clinic / Hospital</label>
            <input type="text" id="primary_clinic" name="primary_clinic" value="<?= e($patient['primary_clinic']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="care_notes_summary">Daily Care Guidance</label>
          <textarea id="care_notes_summary" name="care_notes_summary"><?= e($patient['care_notes_summary']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Patient Profile</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
