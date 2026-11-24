<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();
$canManage = in_array($currentUser['role'], ['administrator', 'caregiver'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_role(['administrator', 'caregiver'], $currentUser);

    $apptDate = trim((string)($_POST['appointment_date'] ?? ''));
    $apptTime = trim((string)($_POST['appointment_time'] ?? '09:00 AM'));
    $doctorName = trim((string)($_POST['doctor_name'] ?? ''));
    $clinic = trim((string)($_POST['clinic_or_hospital'] ?? ''));
    $purpose = trim((string)($_POST['purpose_or_notes'] ?? ''));
    $docLabel = trim((string)($_POST['attachment_label'] ?? ''));

    $dObj = DateTime::createFromFormat('Y-m-d', $apptDate);
    if (!$dObj || $dObj->format('Y-m-d') !== $apptDate || $doctorName === '' || $clinic === '' || $purpose === '') {
        set_flash('error', 'Please fill in a valid appointment date, doctor, clinic/hospital, and visit notes.');
    } else {
        $relPath = null;
        $uploadError = false;

        if (!empty($_FILES['document_file']['name'])) {
            $file = $_FILES['document_file'];
            if ((int)$file['error'] !== UPLOAD_ERR_OK) {
                set_flash('error', 'File upload failed (error code ' . (int)$file['error'] . ').');
                $uploadError = true;
            } elseif ((int)$file['size'] > 5 * 1024 * 1024) {
                set_flash('error', 'Uploaded file exceeds the 5 MB size limit.');
                $uploadError = true;
            } else {
                $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
                $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];

                if (!in_array($ext, $allowedExts, true) || !in_array($mime, $allowedMimes, true)) {
                    set_flash('error', 'Invalid file type. Only PDF, JPG, JPEG, and PNG files are allowed.');
                    $uploadError = true;
                } else {
                    $safeName = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetDir = __DIR__ . '/uploads/documents';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    $dest = $targetDir . '/' . $safeName;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $relPath = 'uploads/documents/' . $safeName;
                        if ($docLabel === '') {
                            $docLabel = basename((string)$file['name']);
                        }
                    } else {
                        set_flash('error', 'Could not move uploaded file to storage directory.');
                        $uploadError = true;
                    }
                }
            }
        }

        if (!$uploadError) {
            $stmt = $pdo->prepare('INSERT INTO appointments (patient_id, appointment_date, appointment_time, doctor_name, clinic_or_hospital, purpose_or_notes, attachment_path, attachment_label, created_by) VALUES (1, :ad, :at, :dn, :cl, :pu, :ap, :al, :uid)');
            $stmt->execute([
                ':ad' => $apptDate, ':at' => $apptTime, ':dn' => $doctorName,
                ':cl' => $clinic, ':pu' => $purpose, ':ap' => $relPath,
                ':al' => $docLabel ?: null, ':uid' => (int)$currentUser['id'],
            ]);
            $newId = (int)$pdo->lastInsertId();
            log_audit((int)$currentUser['id'], 'CREATE', 'Appointments & Docs', 'APPT-' . $newId, 'Recorded appointment with ' . $doctorName . ' on ' . $apptDate);
            set_flash('success', 'Appointment and medical record saved.');
        }
    }
    header('Location: appointments.php');
    exit;
}

$appointments = $pdo->query('SELECT a.*, u.full_name AS creator_name FROM appointments a JOIN users u ON u.id = a.created_by ORDER BY a.appointment_date DESC, a.id DESC')->fetchAll();

$pageTitle = 'Appointments & Documents';
$activePage = 'appointments.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Appointments &amp; Medical Documents</h1>
    <p>Doctor visits, prescriptions, and lab results (PDF, JPG, PNG up to 5 MB).</p>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Recorded Visits &amp; Documents</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Date</th><th>Doctor &amp; Clinic</th><th>Notes &amp; File</th></tr>
        </thead>
        <tbody>
          <?php foreach ($appointments as $a): ?>
            <tr>
              <td>
                <strong><?= e($a['appointment_date']) ?></strong><br>
                <span class="kpi-sub"><?= e($a['appointment_time']) ?></span>
              </td>
              <td>
                <strong><?= e($a['doctor_name']) ?></strong><br>
                <span class="kpi-sub"><?= e($a['clinic_or_hospital']) ?></span>
              </td>
              <td>
                <div><?= e($a['purpose_or_notes']) ?></div>
                <?php if (!empty($a['attachment_path'])): ?>
                  <div style="margin-top:6px;">
                    <a href="file.php?type=doc&amp;id=<?= (int)$a['id'] ?>" target="_blank" class="btn btn-sm btn-outline">
                      View File: <?= e($a['attachment_label'] ?: 'Attachment') ?>
                    </a>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>Add Appointment / Medical Document</h2>
    <?php if (!$canManage): ?>
      <div class="flash flash-warning">Contributors can view appointments and attached medical files.</div>
    <?php else: ?>
      <form method="post" action="appointments.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="form-group">
            <label for="appointment_date">Date *</label>
            <input type="date" id="appointment_date" name="appointment_date" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
          </div>
          <div class="form-group">
            <label for="appointment_time">Time</label>
            <input type="text" id="appointment_time" name="appointment_time" value="10:00 AM">
          </div>
        </div>
        <div class="form-group">
          <label for="doctor_name">Doctor Name *</label>
          <input type="text" id="doctor_name" name="doctor_name" required placeholder="Dr. Ramon Cruz, MD">
        </div>
        <div class="form-group">
          <label for="clinic_or_hospital">Clinic or Hospital *</label>
          <input type="text" id="clinic_or_hospital" name="clinic_or_hospital" required placeholder="St. Jude Medical Clinic">
        </div>
        <div class="form-group">
          <label for="purpose_or_notes">Purpose / Prescription / Lab Notes *</label>
          <textarea id="purpose_or_notes" name="purpose_or_notes" required placeholder="Check-up notes, lab results, or updated prescription details"></textarea>
        </div>
        <div class="form-group">
          <label for="attachment_label">Document Title (Optional)</label>
          <input type="text" id="attachment_label" name="attachment_label" placeholder="e.g., HbA1c Lab Result Oct 2026">
        </div>
        <div class="form-group">
          <label for="document_file">Attach Prescription / Lab / Visit File (PDF, JPG, PNG — max 5 MB)</label>
          <input type="file" id="document_file" name="document_file" accept=".pdf,.jpg,.jpeg,.png">
        </div>
        <button type="submit" class="btn btn-primary">Save Appointment</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
