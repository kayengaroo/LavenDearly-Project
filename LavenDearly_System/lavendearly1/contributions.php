<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
$pdo = db();
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_perm('contribution.add');
    $amt = post('amount'); $date = post('contrib_date');
    $uid = $me['role'] === 'admin' ? (int)post('user_id') : (int)$me['id'];   // contributors record only their own
    $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ? AND is_active = 1'); $chk->execute([$uid]);
    if (!valid_amount($amt)) flash('The amount must be a positive number.', 'error');
    elseif (!valid_date($date) || $date > $today) flash('Enter a valid date that is not in the future.', 'error');
    elseif (!$chk->fetchColumn()) flash('Choose a family member.', 'error');
    else {
        $pdo->prepare('INSERT INTO contributions (user_id, amount, contrib_date, note, created_at) VALUES (?,?,?,?,?)')
            ->execute([$uid, number_format((float)$amt, 2, '.', ''), $date, mb_substr(post('note'), 0, 255) ?: null, date('Y-m-d H:i:s')]);
        audit('create', 'contribution', "member #$uid " . peso($amt));
        flash('Contribution recorded.');
    }
    redirect('contributions.php');
}

$bal = balances();
$users = $pdo->query('SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name')->fetchAll();
$hist = $pdo->query('SELECT c.*, u.full_name FROM contributions c JOIN users u ON u.id = c.user_id ORDER BY c.contrib_date DESC, c.id DESC LIMIT 40')->fetchAll();
$sharePct = array_sum(array_column($bal['rows'], 'share_percent'));

$pageTitle = 'Contributions & Balances'; $active = 'contributions';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>Contributions &amp; balances</h1>
  <p class="muted">Each member’s agreed share of all expenses, compared with what they have paid.</p></div></div>

<div class="grid g3" style="margin-bottom:22px">
  <div class="stat s-plum"><div class="num"><?= peso($bal['total']) ?></div><div class="lbl">Total care expenses</div><div class="sub">All recorded purchases</div></div>
  <div class="stat s-dusty"><div class="num"><?= peso($bal['fund']) ?></div><div class="lbl">Care fund balance</div><div class="sub">Contributions − expenses paid from the fund</div></div>
  <div class="stat s-beige"><div class="num"><?= rtrim(rtrim(number_format($sharePct, 2), '0'), '.') ?>%</div><div class="lbl">Shares assigned</div><div class="sub"><?= abs($sharePct - 100) < 0.01 ? 'Shares add up to 100%' : 'Shares should add up to 100% (Family Members page)' ?></div></div>
</div>

<section class="card">
  <h2>Who has paid what</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>Member</th><th class="num">Agreed share</th><th class="num">Share amount</th><th class="num">Paid personally</th><th class="num">Contributed</th><th class="num">Total paid</th><th class="num">Balance</th></tr></thead>
    <tbody>
    <?php foreach ($bal['rows'] as $r): ?>
      <tr>
        <td><strong><?= e($r['full_name']) ?></strong><br><span class="muted small"><?= e($r['relationship']) ?></span></td>
        <td class="num"><?= rtrim(rtrim(number_format($r['share_percent'], 2), '0'), '.') ?>%</td>
        <td class="num"><?= peso($r['share']) ?></td>
        <td class="num"><?= peso($r['personal']) ?></td>
        <td class="num"><?= peso($r['contrib']) ?></td>
        <td class="num"><?= peso($r['paid']) ?></td>
        <td class="num <?= $r['balance'] >= 0 ? 'pos' : 'neg' ?>"><?= $r['balance'] >= 0 ? '+' : '−' ?><?= peso(abs($r['balance'])) ?>
          <br><span class="muted small" style="font-weight:500"><?= abs($r['balance']) < 0.005 ? 'settled' : ($r['balance'] > 0 ? 'is owed' : 'still owes') ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="hint" style="margin-top:10px">Balance = (purchases paid personally + contributions sent) − agreed share of all expenses. Expenses paid from the shared care fund are covered by contributions.</p>
</section>

<?php if (can('contribution.add')): ?>
<details class="card">
  <summary>Record a contribution</summary>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-grid">
      <?php if ($me['role'] === 'admin'): ?>
      <div class="field"><label for="user_id">Member</label><select id="user_id" name="user_id"><?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (int)$u['id'] === (int)$me['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option><?php endforeach; ?></select></div>
      <?php endif; ?>
      <div class="field"><label for="amount">Amount (₱)</label><input type="number" id="amount" name="amount" step="0.01" min="0.01" required></div>
      <div class="field"><label for="contrib_date">Date sent</label><input type="date" id="contrib_date" name="contrib_date" max="<?= $today ?>" value="<?= $today ?>" required></div>
      <div class="field"><label for="note">Note</label><input type="text" id="note" name="note" maxlength="255" placeholder="e.g. sent via GCash"></div>
    </div>
    <button class="btn" type="submit">Save contribution</button>
  </form>
</details>
<?php endif; ?>

<section class="card">
  <h2>Recent contributions</h2>
  <?php if (!$hist): ?><div class="empty">No contributions recorded yet.</div><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Member</th><th class="num">Amount</th><th>Note</th></tr></thead>
    <tbody><?php foreach ($hist as $h): ?>
      <tr><td><?= fdate($h['contrib_date']) ?></td><td><?= e($h['full_name']) ?></td><td class="num"><?= peso($h['amount']) ?></td><td><?= e($h['note'] ?: '') ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
