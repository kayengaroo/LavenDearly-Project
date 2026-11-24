<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
require_perm('users.manage');
$pdo = db();

function active_admins(PDO $pdo, int $except = 0): int
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1 AND id <> ?");
    $st->execute([$except]);
    return (int)$st->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)post('id'); $action = post('action');
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?'); $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) { flash('Member not found.', 'error'); redirect('users.php'); }

    if ($action === 'reset') {
        $pw = (string)($_POST['new_password'] ?? '');
        if (strlen($pw) < 8) flash('The new password needs at least 8 characters.', 'error');
        else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
            audit('password_reset', "user #$id", $u['username']);
            flash('Password reset for ' . $u['full_name'] . '. Share it with them privately.');
        }
    } elseif ($action === 'toggle') {
        if ($id === (int)$me['id']) flash('You cannot deactivate your own account.', 'error');
        elseif ($u['is_active'] && $u['role'] === 'admin' && active_admins($pdo, $id) === 0) flash('At least one active administrator is required.', 'error');
        else {
            $new = $u['is_active'] ? 0 : 1;
            $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$new, $id]);
            audit($new ? 'activate' : 'deactivate', "user #$id", $u['username']);
            flash($u['full_name'] . ($new ? ' can sign in again.' : ' no longer has access.'));
        }
    } elseif ($action === 'role') {
        $role = post('role');
        if (!in_array($role, ['admin', 'caregiver', 'contributor'], true)) flash('Invalid role.', 'error');
        elseif ($id === (int)$me['id'] && $role !== 'admin') flash('You cannot remove your own administrator role.', 'error');
        elseif ($u['role'] === 'admin' && $role !== 'admin' && active_admins($pdo, $id) === 0) flash('At least one active administrator is required.', 'error');
        else {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
            audit('role_change', "user #$id", $u['username'] . ': ' . $u['role'] . ' → ' . $role);
            flash('Role updated.');
        }
    }
    redirect('users.php');
}

$users = $pdo->query('SELECT * FROM users ORDER BY is_active DESC, full_name')->fetchAll();
$pageTitle = 'User Management'; $active = 'users';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>User management</h1>
  <p class="muted">Reset passwords, change roles, and remove access when a family member leaves. Every change is written to the audit log.</p></div></div>

<section class="card">
  <div class="table-wrap"><table>
    <thead><tr><th>Member</th><th>Role</th><th>Status</th><th>Reset password</th><th>Access</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><strong><?= e($u['full_name']) ?></strong><br><span class="muted small"><?= e($u['username']) ?> · <?= e($u['email']) ?></span></td>
        <td>
          <form method="post" class="actions"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <select name="role" aria-label="Role for <?= e($u['full_name']) ?>" style="width:auto">
              <?php foreach (['admin' => 'Family Administrator', 'caregiver' => 'Caregiver', 'contributor' => 'Contributor'] as $k => $v): ?><option value="<?= $k ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
            </select><button class="btn btn-sm btn-ghost">Change</button></form>
        </td>
        <td><?= $u['is_active'] ? '<span class="chip chip-given">Active</span>' : '<span class="chip chip-off">Deactivated</span>' ?></td>
        <td>
          <form method="post" class="actions" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <input type="password" name="new_password" minlength="8" placeholder="New password" aria-label="New password for <?= e($u['full_name']) ?>" style="width:160px" required>
            <button class="btn btn-sm btn-ghost">Reset</button></form>
        </td>
        <td>
          <form method="post" onsubmit="return confirm('<?= $u['is_active'] ? 'Remove this member’s access?' : 'Restore this member’s access?' ?>')"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="btn btn-sm <?= $u['is_active'] ? 'btn-danger' : 'btn-dusty' ?>"><?= $u['is_active'] ? 'Deactivate' : 'Reactivate' ?></button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
