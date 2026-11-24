<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
require_perm('reports.view');
$pdo = db();

$month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$from = "$month-01"; $to = date('Y-m-t', strtotime($from));

// expenses by category and by person for the month
$byCat = $pdo->prepare('SELECT category, COUNT(*) n, SUM(amount) total FROM expenses WHERE expense_date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC');
$byCat->execute([$from, $to]); $byCat = $byCat->fetchAll();
$byPerson = $pdo->prepare("SELECT COALESCE(u.full_name, 'Shared care fund') person, COUNT(*) n, SUM(x.amount) total
    FROM expenses x LEFT JOIN users u ON u.id = x.paid_by WHERE x.expense_date BETWEEN ? AND ? GROUP BY person ORDER BY total DESC");
$byPerson->execute([$from, $to]); $byPerson = $byPerson->fetchAll();
$monthTotal = array_sum(array_column($byCat, 'total'));

$adh = adherence($from, $to);
$bal = balances();
$pending = array_values(array_filter($bal['rows'], fn($r) => $r['balance'] < -0.004 && $r['share_percent'] > 0));

/* CSV export — must run before any HTML output */
if (isset($_GET['export'])) {
    $kind = $_GET['export'];
    $rows = []; $head = [];
    if ($kind === 'expenses') {
        $head = ['Date', 'Item', 'Category', 'Amount', 'Paid by'];
        $st = $pdo->prepare("SELECT x.expense_date, x.item, x.category, x.amount, COALESCE(u.full_name,'Shared care fund') FROM expenses x LEFT JOIN users u ON u.id=x.paid_by WHERE x.expense_date BETWEEN ? AND ? ORDER BY x.expense_date");
        $st->execute([$from, $to]); $rows = $st->fetchAll(PDO::FETCH_NUM);
    } elseif ($kind === 'adherence') {
        $head = ['Medicine', 'Dose', 'Scheduled doses', 'Given', 'Missed', 'Skipped', 'Adherence %'];
        foreach ($adh as $a) $rows[] = [$a['name'], $a['dose'], $a['scheduled'], $a['given'], $a['missed'], $a['skipped'], $a['rate'] ?? ''];
    } elseif ($kind === 'pending') {
        $head = ['Member', 'Agreed share %', 'Share amount', 'Total paid', 'Still owes'];
        foreach ($pending as $p) $rows[] = [$p['full_name'], $p['share_percent'], round($p['share'], 2), round($p['paid'], 2), round(-$p['balance'], 2)];
    } else { redirect('reports.php'); }
    audit('export', 'report', "$kind $month");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lavendearly-' . $kind . '-' . $month . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $head);
    foreach ($rows as $r) {
        // guard against spreadsheet formula injection
        $r = array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $r);
        fputcsv($out, $r);
    }
    exit;
}

$pageTitle = 'Reports'; $active = 'reports';
include __DIR__ . '/includes/header.php';
$q = '?month=' . urlencode($month);
?>
<div class="page-head">
  <div><h1>Reports</h1><p class="muted">Monthly expenses, medicine adherence and pending contributions. Export any table to CSV.</p></div>
  <form method="get" class="actions"><input type="month" name="month" value="<?= e($month) ?>" aria-label="Month" style="width:auto"><button class="btn btn-sm">Show</button></form>
</div>

<div class="grid g2">
<section class="card">
  <div class="page-head" style="margin-bottom:10px"><h2 style="margin:0">Expenses by category</h2><a class="btn btn-sm btn-ghost" href="reports.php<?= $q ?>&export=expenses">Export CSV</a></div>
  <?php if (!$byCat): ?><div class="empty">No expenses in <?= date('F Y', strtotime($from)) ?>.</div><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>Category</th><th class="num">Entries</th><th class="num">Total</th></tr></thead><tbody>
    <?php foreach ($byCat as $r): ?><tr><td><?= e($r['category']) ?></td><td class="num"><?= (int)$r['n'] ?></td><td class="num"><?= peso($r['total']) ?></td></tr><?php endforeach; ?>
    <tr><td><strong>Month total</strong></td><td></td><td class="num"><strong><?= peso($monthTotal) ?></strong></td></tr>
  </tbody></table></div><?php endif; ?>
</section>
<section class="card">
  <h2>Expenses by person</h2>
  <?php if (!$byPerson): ?><div class="empty">No expenses in <?= date('F Y', strtotime($from)) ?>.</div><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>Paid by</th><th class="num">Entries</th><th class="num">Total</th></tr></thead><tbody>
    <?php foreach ($byPerson as $r): ?><tr><td><?= e($r['person']) ?></td><td class="num"><?= (int)$r['n'] ?></td><td class="num"><?= peso($r['total']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</section>
</div>

<section class="card">
  <div class="page-head" style="margin-bottom:10px"><h2 style="margin:0">Medicine adherence — <?= date('F Y', strtotime($from)) ?></h2><a class="btn btn-sm btn-ghost" href="reports.php<?= $q ?>&export=adherence">Export CSV</a></div>
  <?php if (!$adh): ?><div class="empty">No doses were scheduled or logged this month.</div><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>Medicine</th><th class="num">Scheduled</th><th class="num">Given</th><th class="num">Missed</th><th class="num">Skipped</th><th class="num">Adherence</th></tr></thead><tbody>
    <?php foreach ($adh as $a): ?><tr><td><strong><?= e($a['name']) ?></strong> <span class="muted small"><?= e($a['dose']) ?></span></td>
      <td class="num"><?= $a['scheduled'] ?></td><td class="num"><?= $a['given'] ?></td><td class="num"><?= $a['missed'] ?></td><td class="num"><?= $a['skipped'] ?></td>
      <td class="num"><?= $a['rate'] === null ? '—' : '<span class="' . ($a['rate'] >= 90 ? 'pos' : 'neg') . '">' . $a['rate'] . '%</span>' ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <p class="hint" style="margin-top:10px">Adherence = doses logged as given ÷ scheduled doses that have already come due. Unlogged doses count against the rate.</p><?php endif; ?>
</section>

<section class="card">
  <div class="page-head" style="margin-bottom:10px"><h2 style="margin:0">Pending contributions</h2><a class="btn btn-sm btn-ghost" href="reports.php<?= $q ?>&export=pending">Export CSV</a></div>
  <?php if (!$pending): ?><div class="empty">Everyone is settled up.</div><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>Member</th><th class="num">Agreed share</th><th class="num">Share amount</th><th class="num">Total paid</th><th class="num">Still owes</th></tr></thead><tbody>
    <?php foreach ($pending as $p): ?><tr><td><?= e($p['full_name']) ?></td><td class="num"><?= rtrim(rtrim(number_format($p['share_percent'], 2), '0'), '.') ?>%</td><td class="num"><?= peso($p['share']) ?></td><td class="num"><?= peso($p['paid']) ?></td><td class="num neg"><?= peso(-$p['balance']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
  <p class="hint" style="margin-top:10px">Pending contributions are cumulative across all recorded expenses, not only this month.</p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
