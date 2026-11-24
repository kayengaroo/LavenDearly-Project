<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
require_role(['administrator'], $currentUser);
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['act'] ?? '';

    if ($act === 'create_user') {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $rel = trim((string)($_POST['relationship_label'] ?? 'Family Member'));
        $role = $_POST['role'] ?? 'contributor';
        $pass = (string)($_POST['password'] ?? '');

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8 || !in_array($role, ['administrator', 'caregiver', 'contributor'], true)) {
            set_flash('error', 'Provide valid name, email, role, and a password of at least 8 characters.');
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $ins = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, relationship_label, role, agreed_share_pct, is_active) VALUES (:fn, :em, :ph, :rl, :ro, 0.00, 1)');
            $ins->execute([':fn' => $fullName, ':em' => $email, ':ph' => $hash, ':rl' => $rel, ':ro' => $role]);
            $newId = (int)$pdo->lastInsertId();
            log_audit((int)$currentUser['id'], 'CREATE_USER', 'User Management', 'USER-' . $newId, 'Created account ' . $email . ' (' . $role . ')');
            set_flash('success', 'User account created.');
        }
    } elseif ($act === 'update_role_status') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'contributor';
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($uid === (int)$currentUser['id'] && $active === 0) {
            set_flash('error', 'You cannot deactivate your own active administrator account.');
        } elseif ($uid > 0 && in_array($role, ['administrator', 'caregiver', 'contributor'], true)) {
            $stmt = $pdo->prepare('UPDATE users SET role = :ro, is_active = :ac WHERE id = :id');
            $stmt->execute([':ro' => $role, ':ac' => $active, ':id' => $uid]);
            log_audit((int)$currentUser['id'], 'UPDATE_USER', 'User Management', 'USER-' . $uid, "Set role={$role}, active={$active} for user #{$uid}");
            set_flash('success', 'User role and access status updated.');
        }
    } elseif ($act === 'reset_password') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $newPass = (string)($_POST['new_password'] ?? '');
        if ($uid > 0 && strlen($newPass) >= 8) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password_hash = :ph WHERE id = :id')->execute([':ph' => $hash, ':id' => $uid]);
            log_audit((int)$currentUser['id'], 'RESET_PASSWORD', 'User Management', 'USER-' . $uid, 'Reset password for user #' . $uid);
            set_flash('success', 'Password reset successfully.');
        } else {
            set_flash('error', 'New password must be at least 8 characters.');
        }
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query('SELECT id, full_name, email, relationship_label, role, is_active, last_login_at FROM users ORDER BY id ASC')->fetchAll();

$pageTitle = 'User Management';
$activePage = 'users.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>User Management</h1>
    <p>Create accounts, change roles, deactivate access when members leave, and reset passwords (Administrator only).</p>
  </div>
</div>

<div class="card">
  <h2>System Accounts</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>User</th><th>Relationship</th><th>Last Login</th><th>Role &amp; Active</th><th>Password Reset</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><strong><?= e($u['full_name']) ?></strong><br><span class="kpi-sub"><?= e($u['email']) ?></span></td>
            <td><?= e($u['relationship_label']) ?></td>
            <td><?= e($u['last_login_at'] ?: 'Never') ?></td>
            <td>
              <form method="post" action="users.php" class="btn-row">
                <?= csrf_field() ?>
                <input type="hidden" name="act" value="update_role_status">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <select name="role" style="width:auto;">
                  <option value="administrator" <?= $u['role'] === 'administrator' ? 'selected' : '' ?>>Administrator</option>
                  <option value="caregiver" <?= $u['role'] === 'caregiver' ? 'selected' : '' ?>>Caregiver</option>
                  <option value="contributor" <?= $u['role'] === 'contributor' ? 'selected' : '' ?>>Contributor</option>
                </select>
                <label style="font-size:12px;"><input type="checkbox" name="is_active" value="1" <?= (int)$u['is_active'] === 1 ? 'checked' : '' ?> style="width:auto;"> Active</label>
                <button type="submit" class="btn btn-sm btn-outline">Save</button>
              </form>
            </td>
            <td>
              <form method="post" action="users.php" class="btn-row">
                <?= csrf_field() ?>
                <input type="hidden" name="act" value="reset_password">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <input type="password" name="new_password" minlength="8" required placeholder="New pass (8+)" style="width:140px;">
                <button type="submit" class="btn btn-sm btn-outline">Reset</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card" style="max-width:600px;">
  <h2>Create Family Account</h2>
  <form method="post" action="users.php">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="create_user">
    <div class="form-grid">
      <div class="form-group">
        <label for="full_name">Full Name *</label>
        <input type="text" id="full_name" name="full_name" required>
      </div>
      <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" required>
      </div>
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label for="relationship_label">Relationship</label>
        <input type="text" id="relationship_label" name="relationship_label" placeholder="e.g., Brother">
      </div>
      <div class="form-group">
        <label for="role">Role *</label>
        <select id="role" name="role">
          <option value="contributor">Contributor</option>
          <option value="caregiver">Caregiver</option>
          <option value="administrator">Administrator</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label for="password">Initial Password (min 8 characters) *</label>
      <input type="password" id="password" name="password" minlength="8" required>
    </div>
    <button type="submit" class="btn btn-primary">Create Account</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
