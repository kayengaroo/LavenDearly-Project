<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
require_role(['administrator'], $currentUser);
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['act'] ?? 'add';

    if ($act === 'add') {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $rel = trim((string)($_POST['relationship_label'] ?? ''));
        $phone = trim((string)($_POST['contact_number'] ?? ''));
        $role = $_POST['role'] ?? 'contributor';
        $isEmerg = !empty($_POST['is_emergency_contact']) ? 1 : 0;
        $loc = trim((string)($_POST['location_note'] ?? ''));

        if ($fullName === '' || $rel === '' || $phone === '') {
            set_flash('error', 'Please complete name, relationship, and contact number.');
        } else {
            $ins = $pdo->prepare('INSERT INTO family_members (patient_id, full_name, relationship_label, contact_number, role, is_emergency_contact, location_note) VALUES (1, :fn, :rel, :ph, :rl, :em, :lc)');
            $ins->execute([
                ':fn' => $fullName, ':rel' => $rel, ':ph' => $phone,
                ':rl' => $role, ':em' => $isEmerg, ':lc' => $loc,
            ]);
            $newId = (int)$pdo->lastInsertId();
            log_audit((int)$currentUser['id'], 'ADD_MEMBER', 'Family Members', 'MEM-' . $newId, 'Added family member ' . $fullName);
            set_flash('success', 'Family member added.');
        }
    } elseif ($act === 'delete') {
        $mid = (int)($_POST['member_id'] ?? 0);
        if ($mid > 0) {
            $pdo->prepare('DELETE FROM family_members WHERE id = :id')->execute([':id' => $mid]);
            log_audit((int)$currentUser['id'], 'DELETE_MEMBER', 'Family Members', 'MEM-' . $mid, 'Removed family member #' . $mid);
            set_flash('success', 'Family member removed.');
        }
    }
    header('Location: members.php');
    exit;
}

$members = $pdo->query('SELECT * FROM family_members ORDER BY is_emergency_contact DESC, id ASC')->fetchAll();

$pageTitle = 'Family Members';
$activePage = 'members.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Family Member Management</h1>
    <p>Maintain family circle members, relationships, roles, and emergency contact flags (Administrator only).</p>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Family &amp; Care Circle Directory</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name &amp; Relationship</th><th>Contact</th><th>Role</th><th>Emergency</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($members as $m): ?>
            <tr>
              <td>
                <strong><?= e($m['full_name']) ?></strong><br>
                <span class="kpi-sub"><?= e($m['relationship_label']) ?><?= $m['location_note'] ? ' • ' . e($m['location_note']) : '' ?></span>
              </td>
              <td><?= e($m['contact_number']) ?></td>
              <td><?= e($m['role']) ?></td>
              <td><?= (int)$m['is_emergency_contact'] === 1 ? '<span class="badge badge-overdue">Yes</span>' : 'No' ?></td>
              <td>
                <form method="post" action="members.php" data-confirm="Remove this member from the family directory?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>Add Family Member</h2>
    <form method="post" action="members.php">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="add">
      <div class="form-group">
        <label for="full_name">Full Name *</label>
        <input type="text" id="full_name" name="full_name" required>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label for="relationship_label">Relationship *</label>
          <input type="text" id="relationship_label" name="relationship_label" required placeholder="e.g., Younger Sister">
        </div>
        <div class="form-group">
          <label for="contact_number">Contact Number *</label>
          <input type="text" id="contact_number" name="contact_number" required placeholder="+63 917 ...">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label for="role">Role</label>
          <select id="role" name="role">
            <option value="contributor">Contributor</option>
            <option value="caregiver">Caregiver</option>
            <option value="administrator">Administrator</option>
            <option value="emergency_only">Emergency Only</option>
          </select>
        </div>
        <div class="form-group">
          <label for="location_note">Location / Notes</label>
          <input type="text" id="location_note" name="location_note" placeholder="e.g., Quezon City">
        </div>
      </div>
      <div class="form-group">
        <label><input type="checkbox" name="is_emergency_contact" value="1" style="width:auto;"> Mark as Emergency Contact</label>
      </div>
      <button type="submit" class="btn btn-primary">Add Member</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
