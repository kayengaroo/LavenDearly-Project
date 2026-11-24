<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_perm('members.manage');
    $id = (int)post('id');
    $name = post('full_name'); $username = post('username'); $email = post('email');
    $rel = post('relationship'); $role = post('role'); $share = post('share_percent');
    $pass = (string)($_POST['password'] ?? '');
    $err = null;
    if ($name === '' || $username === '') $err = 'Name and username are required.';
    elseif (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $username)) $err = 'Username: 3–40 letters, numbers, dots, dashes or underscores.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email address.';
    elseif (!in_array($role, ['admin', 'caregiver', 'contributor'], true)) $err = 'Choose a role.';
    elseif (!is_numeric($share) || $share < 0 || $share > 100) $err = 'The share must be between 0 and 100.';
    elseif (!$id && strlen($pass) < 8) $err = 'A new member needs a temporary password of at least 8 characters.';
    if (!$err) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?'); $dup->execute([$username, $id]);
        if ($dup->fetchColumn()) $err = 'That username is already taken.';
    }
    if (!$err && $id && $id === (int)$me['id'] && $role !== 'admin') $err = 'You cannot remove your own administrator role.';
    if ($err) { flash($err, 'error'); redirect('members.php' . ($id ? "?edit=$id" : '')); }

    if ($id) {
        $pdo->prepare('UPDATE users SET full_name=?, username=?, email=?, relationship=?, role=?, share_percent=? WHERE id=?')
            ->execute([$name, $username, $email, $rel, $role, $share, $id]);
        audit('update', "user #$id", "$name ($role, $share%)");
        flash('Member updated.');
    } else {
        $pdo->prepare('INSERT INTO users (full_name, username, email, password_hash, role, relationship, share_percent, is_active, created_at) VALUES (?,?,?,?,?,?,?,1,?)')
            ->execute([$name, $username, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $rel, $share, date('Y-m-d H:i:s')]);
        audit('create', 'user #' . $pdo->lastInsertId(), "$name ($role)");
        flash('Member added. Share the temporary password with them privately.');
    }
    redirect('members.php');
}

$editRow = null;
if (can('members.manage') && isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?'); $st->execute([(int)$_GET['edit']]);
    $editRow = $st->fetch() ?: null;
}
$members = $pdo->query('SELECT * FROM users ORDER BY is_active DESC, role, full_name')->fetchAll();
$pageTitle = 'Family Members'; $active = 'members';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>Family members</h1><p class="muted">Everyone who can see this care record, with their relationship, role and agreed share of expenses.</p></div></div>

<?php if (can('members.manage')): ?>
<details class="card" <?= $editRow ? 'open' : '' ?>>
  <summary><?= $editRow ? 'Edit member' : 'Add a family member' ?></summary>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $editRow ? (int)$editRow['id'] : 0 ?>">
    <div class="form-grid">
      <div class="field"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" required value="<?= e($editRow['full_name'] ?? '') ?>"></div>
      <div class="field"><label for="username">Username</label><input type="text" id="username" name="username" required value="<?= e($editRow['username'] ?? '') ?>"></div>
      <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" required value="<?= e($editRow['email'] ?? '') ?>"></div>
      <div class="field"><label for="relationship">Relationship to patient</label><input type="text" id="relationship" name="relationship" placeholder="e.g. Son (abroad)" value="<?= e($editRow['relationship'] ?? '') ?>"></div>
      <div class="field"><label for="role">Role</label><select id="role" name="role"><?php foreach (['admin' => 'Family Administrator', 'caregiver' => 'Caregiver', 'contributor' => 'Contributor'] as $k => $v): ?><option value="<?= $k ?>" <?= ($editRow['role'] ?? 'contributor') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="share_percent">Agreed share of expenses (%)</label><input type="number" id="share_percent" name="share_percent" min="0" max="100" step="0.01" required value="<?= e($editRow['share_percent'] ?? '0') ?>"></div>
      <?php if (!$editRow): ?>
      <div class="field"><label for="password">Temporary password</label><input type="password" id="password" name="password" minlength="8" required><div class="hint">At least 8 characters.</div></div>
      <?php endif; ?>
    </div>
    <div class="actions"><button class="btn" type="submit"><?= $editRow ? 'Save changes' : 'Add member' ?></button><?php if ($editRow): ?><a class="btn btn-ghost" href="members.php">Cancel</a><?php endif; ?></div>
  </form>
</details>
<?php endif; ?>

<section class="card">
  <div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Relationship</th><th>Role</th><th class="num">Share</th><th>Status</th><?php if (can('members.manage')): ?><th></th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($members as $m): ?>
      <tr><td><strong><?= e($m['full_name']) ?></strong><br><span class="muted small"><?= e($m['username']) ?></span></td>
        <td><?= e($m['relationship'] ?: '—') ?></td>
        <td><span class="chip chip-role"><?= e(role_label($m['role'])) ?></span></td>
        <td class="num"><?= rtrim(rtrim(number_format($m['share_percent'], 2), '0'), '.') ?>%</td>
        <td><?= $m['is_active'] ? '<span class="chip chip-given">Active</span>' : '<span class="chip chip-off">Deactivated</span>' ?></td>
        <?php if (can('members.manage')): ?><td><a class="btn btn-sm btn-ghost" href="members.php?edit=<?= (int)$m['id'] ?>">Edit</a></td><?php endif; ?></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
