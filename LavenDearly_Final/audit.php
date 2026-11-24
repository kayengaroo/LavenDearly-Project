<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
require_perm('audit.view');
$pdo = db();

$user = (int)($_GET['user'] ?? 0);
$action = trim($_GET['action'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 50;

$w = []; $a = [];
if ($user) { $w[] = 'l.user_id = ?'; $a[] = $user; }
if ($action !== '') { $w[] = 'l.action = ?'; $a[] = $action; }
$where = $w ? ' WHERE ' . implode(' AND ', $w) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM audit_log l $where"); $cnt->execute($a);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);
$st = $pdo->prepare("SELECT l.*, u.full_name FROM audit_log l LEFT JOIN users u ON u.id = l.user_id $where ORDER BY l.id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($a);
$rows = $st->fetchAll();

$users = $pdo->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll();
$actions = $pdo->query('SELECT DISTINCT action FROM audit_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
$qs = http_build_query(['user' => $user ?: '', 'action' => $action]);

$pageTitle = 'Audit Log'; $active = 'audit';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>Audit log</h1><p class="muted">Who did what, and when. Entries cannot be edited or deleted from the system.</p></div>
  <form method="get" class="actions">
    <select name="user" aria-label="User" style="width:auto"><option value="0">All users</option><?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= $user === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option><?php endforeach; ?></select>
    <select name="action" aria-label="Action" style="width:auto"><option value="">All actions</option><?php foreach ($actions as $x): ?><option <?= $action === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?></select>
    <button class="btn btn-sm">Filter</button></form></div>

<section class="card">
  <?php if (!$rows): ?><div class="empty">No entries.</div><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Details</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td style="white-space:nowrap"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></td><td><?= e($r['full_name'] ?? '—') ?></td><td><span class="chip chip-pending"><?= e($r['action']) ?></span></td><td><?= e($r['record']) ?></td><td><?= e($r['details']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <div class="pagination">
    <?php if ($page > 1): ?><a class="btn btn-sm btn-ghost" href="audit.php?<?= $qs ?>&page=<?= $page - 1 ?>">← Newer</a><?php endif; ?>
    <span class="muted small" style="align-self:center">Page <?= $page ?> of <?= $pages ?> · <?= $total ?> entries</span>
    <?php if ($page < $pages): ?><a class="btn btn-sm btn-ghost" href="audit.php?<?= $qs ?>&page=<?= $page + 1 ?>">Older →</a><?php endif; ?>
  </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
