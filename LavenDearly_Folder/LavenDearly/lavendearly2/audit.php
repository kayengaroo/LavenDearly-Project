<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
require_role(['administrator'], $currentUser);
$pdo = get_db();

$logs = $pdo->query('SELECT al.*, u.full_name AS actor_name FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC, al.id DESC LIMIT 150')->fetchAll();

$pageTitle = 'Audit Log';
$activePage = 'audit.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>System Audit Log</h1>
    <p>Chronological record of logins, dose logs, schedule changes, expense entries, exports, and user updates.</p>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Timestamp</th><th>User</th><th>Action</th><th>Module</th><th>Record</th><th>Details</th></tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td><?= e($l['created_at']) ?></td>
            <td><?= e($l['actor_name'] ?: 'System') ?></td>
            <td><span class="badge badge-pending"><?= e($l['action_type']) ?></span></td>
            <td><?= e($l['module_name']) ?></td>
            <td><code><?= e($l['record_ref']) ?></code></td>
            <td><?= e($l['details']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
