<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['act'] ?? 'add_contrib';

    if ($act === 'add_contrib') {
        $cDate = trim((string)($_POST['contribution_date'] ?? date('Y-m-d')));
        $amtRaw = (string)($_POST['amount'] ?? '0');
        $channel = trim((string)($_POST['channel'] ?? 'Transfer'));
        $notes = trim((string)($_POST['notes'] ?? ''));
        $targetUserId = ($currentUser['role'] === 'administrator' && !empty($_POST['user_id']))
            ? (int)$_POST['user_id']
            : (int)$currentUser['id'];

        $dObj = DateTime::createFromFormat('Y-m-d', $cDate);
        $amount = is_numeric($amtRaw) ? (float)$amtRaw : 0.0;

        if (!$dObj || $dObj->format('Y-m-d') !== $cDate || $amount <= 0) {
            set_flash('error', 'Please provide a valid date and a positive contribution amount.');
        } else {
            $ins = $pdo->prepare('INSERT INTO contributions (patient_id, user_id, contribution_date, amount, channel, notes) VALUES (1, :uid, :cd, :amt, :ch, :nt)');
            $ins->execute([
                ':uid' => $targetUserId, ':cd' => $cDate, ':amt' => round($amount, 2), ':ch' => $channel, ':nt' => $notes,
            ]);
            $newId = (int)$pdo->lastInsertId();
            log_audit((int)$currentUser['id'], 'ADD_CONTRIBUTION', 'Contributions', 'CONTRIB-' . $newId, sprintf('Recorded contribution PHP %.2f for user #%d', $amount, $targetUserId));
            set_flash('success', 'Contribution recorded.');
        }
    } elseif ($act === 'update_shares') {
        require_role(['administrator'], $currentUser);
        $shares = $_POST['shares'] ?? [];
        $sumPct = 0.0;
        foreach ($shares as $pct) {
            $sumPct += is_numeric($pct) ? (float)$pct : 0.0;
        }
        if (abs($sumPct - 100.0) > 0.05) {
            set_flash('error', sprintf('Agreed expense shares must sum to 100%% (current total: %.2f%%).', $sumPct));
        } else {
            $upd = $pdo->prepare('UPDATE users SET agreed_share_pct = :pct WHERE id = :id');
            foreach ($shares as $uid => $pct) {
                $upd->execute([':pct' => round((float)$pct, 2), ':id' => (int)$uid]);
            }
            log_audit((int)$currentUser['id'], 'UPDATE_SHARES', 'Contributions', 'SHARES-ALL', 'Updated family agreed expense share percentages.');
            set_flash('success', 'Agreed share percentages updated.');
        }
    }
    header('Location: contributions.php');
    exit;
}

$users = $pdo->query('SELECT id, full_name, relationship_label, role, agreed_share_pct FROM users WHERE is_active = 1 ORDER BY id ASC')->fetchAll();
$totalExpenses = (float)$pdo->query('SELECT COALESCE(SUM(amount), 0) FROM expenses')->fetchColumn();

$paidByUserStmt = $pdo->query('SELECT paid_by, COALESCE(SUM(amount), 0) AS total_paid FROM expenses GROUP BY paid_by');
$directPaid = [];
foreach ($paidByUserStmt->fetchAll() as $r) {
    $directPaid[(int)$r['paid_by']] = (float)$r['total_paid'];
}

$contribByUserStmt = $pdo->query('SELECT user_id, COALESCE(SUM(amount), 0) AS total_contrib FROM contributions GROUP BY user_id');
$contributed = [];
foreach ($contribByUserStmt->fetchAll() as $r) {
    $contributed[(int)$r['user_id']] = (float)$r['total_contrib'];
}

$recentContribs = $pdo->query('SELECT c.*, u.full_name FROM contributions c JOIN users u ON u.id = c.user_id ORDER BY c.contribution_date DESC, c.id DESC LIMIT 25')->fetchAll();

$pageTitle = 'Contributions & Balances';
$activePage = 'contributions.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Contribution Tracking &amp; Balances</h1>
    <p>Total Care Expenses: <strong>&#8369;<?= number_format($totalExpenses, 2) ?></strong> • Compare each member's total credit (direct payments + contributions) against agreed share.</p>
  </div>
</div>

<div class="card">
  <h2>Family Expense Share &amp; Balance Summary</h2>
  <form method="post" action="contributions.php">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="update_shares">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Family Member</th>
            <th>Role</th>
            <th>Agreed Share (%)</th>
            <th>Target Share (&#8369;)</th>
            <th>Paid + Contributed (&#8369;)</th>
            <th>Net Balance (&#8369;)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <?php
              $uid = (int)$u['id'];
              $pct = (float)$u['agreed_share_pct'];
              $targetAmt = round($totalExpenses * ($pct / 100.0), 2);
              $creditAmt = ($directPaid[$uid] ?? 0.0) + ($contributed[$uid] ?? 0.0);
              $balance = round($creditAmt - $targetAmt, 2);
            ?>
            <tr>
              <td><strong><?= e($u['full_name']) ?></strong> <span class="kpi-sub">(<?= e($u['relationship_label']) ?>)</span></td>
              <td><span class="role-badge role-<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
              <td style="width:140px;">
                <?php if ($currentUser['role'] === 'administrator'): ?>
                  <input type="number" step="0.01" min="0" max="100" name="shares[<?= $uid ?>]" value="<?= number_format($pct, 2, '.', '') ?>">
                <?php else: ?>
                  <?= number_format($pct, 2) ?>%
                <?php endif; ?>
              </td>
              <td>&#8369;<?= number_format($targetAmt, 2) ?></td>
              <td>&#8369;<?= number_format($creditAmt, 2) ?></td>
              <td>
                <?php if ($balance >= 0): ?>
                  <span class="badge badge-paid">+&#8369;<?= number_format($balance, 2) ?> (Over/Credit)</span>
                <?php else: ?>
                  <span class="badge badge-owed">-&#8369;<?= number_format(abs($balance), 2) ?> (Remaining Share)</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($currentUser['role'] === 'administrator'): ?>
      <div style="margin-top:12px;">
        <button type="submit" class="btn btn-primary">Update Share Percentages (Must Total 100%)</button>
      </div>
    <?php endif; ?>
  </form>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Record a Contribution</h2>
    <form method="post" action="contributions.php">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="add_contrib">
      <?php if ($currentUser['role'] === 'administrator'): ?>
        <div class="form-group">
          <label for="user_id">Contributing Member</label>
          <select id="user_id" name="user_id">
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>" <?= (int)$u['id'] === (int)$currentUser['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="form-grid">
        <div class="form-group">
          <label for="contribution_date">Date *</label>
          <input type="date" id="contribution_date" name="contribution_date" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label for="amount">Amount (PHP) *</label>
          <input type="number" id="amount" name="amount" step="0.01" min="0.01" required placeholder="0.00">
        </div>
      </div>
      <div class="form-group">
        <label for="channel">Payment Channel</label>
        <input type="text" id="channel" name="channel" value="Bank Transfer / Remittance">
      </div>
      <div class="form-group">
        <label for="notes">Notes</label>
        <input type="text" id="notes" name="notes" placeholder="e.g., November care allowance">
      </div>
      <button type="submit" class="btn btn-primary">Record Contribution</button>
    </form>
  </div>

  <div class="card">
    <h2>Recent Contributions</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>Member</th><th>Amount</th><th>Channel / Notes</th></tr></thead>
        <tbody>
          <?php foreach ($recentContribs as $c): ?>
            <tr>
              <td><?= e($c['contribution_date']) ?></td>
              <td><strong><?= e($c['full_name']) ?></strong></td>
              <td><strong>&#8369;<?= number_format((float)$c['amount'], 2) ?></strong></td>
              <td><?= e($c['channel']) ?><?= $c['notes'] ? ' — ' . e($c['notes']) : '' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
