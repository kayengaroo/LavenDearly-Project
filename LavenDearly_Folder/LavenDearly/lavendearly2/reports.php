<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();

$exportType = $_GET['export'] ?? '';
if ($exportType === 'expenses') {
    log_audit((int)$currentUser['id'], 'EXPORT_CSV', 'Reports', 'CSV-EXPENSES', 'Exported expenses report to CSV.');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="carecircle_expenses_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Item', 'Category', 'Amount (PHP)', 'Paid By', 'Notes']);
    $rows = $pdo->query('SELECT e.expense_date, e.item_name, e.category, e.amount, u.full_name, e.notes FROM expenses e JOIN users u ON u.id = e.paid_by ORDER BY e.expense_date DESC')->fetchAll();
    foreach ($rows as $r) {
        fputcsv($out, [$r['expense_date'], $r['item_name'], $r['category'], $r['amount'], $r['full_name'], $r['notes']]);
    }
    fclose($out);
    exit;
}

if ($exportType === 'adherence') {
    log_audit((int)$currentUser['id'], 'EXPORT_CSV', 'Reports', 'CSV-ADHERENCE', 'Exported medicine adherence report to CSV.');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="carecircle_adherence_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Medicine', 'Dosage', 'Given Doses', 'Missed Doses', 'Skipped Doses', 'Total Logged', 'Adherence %']);
    $rows = $pdo->query("SELECT m.name, m.dosage,
        SUM(CASE WHEN dl.status = 'given' THEN 1 ELSE 0 END) AS given_cnt,
        SUM(CASE WHEN dl.status = 'missed' THEN 1 ELSE 0 END) AS missed_cnt,
        SUM(CASE WHEN dl.status = 'skipped' THEN 1 ELSE 0 END) AS skipped_cnt,
        COUNT(dl.id) AS total_cnt
        FROM medicines m LEFT JOIN dose_logs dl ON dl.medicine_id = m.id
        GROUP BY m.id, m.name, m.dosage ORDER BY m.name ASC")->fetchAll();
    foreach ($rows as $r) {
        $tot = (int)$r['total_cnt'];
        $pct = $tot > 0 ? round(((int)$r['given_cnt'] / $tot) * 100, 1) : 0.0;
        fputcsv($out, [$r['name'], $r['dosage'], (int)$r['given_cnt'], (int)$r['missed_cnt'], (int)$r['skipped_cnt'], $tot, $pct . '%']);
    }
    fclose($out);
    exit;
}

$expByCat = $pdo->query('SELECT category, SUM(amount) AS total_amt, COUNT(*) AS cnt FROM expenses GROUP BY category ORDER BY total_amt DESC')->fetchAll();
$expByPerson = $pdo->query('SELECT u.full_name, COALESCE(SUM(e.amount), 0) AS total_paid FROM users u LEFT JOIN expenses e ON e.paid_by = u.id GROUP BY u.id, u.full_name ORDER BY total_paid DESC')->fetchAll();
$adherenceRows = $pdo->query("SELECT m.name, m.dosage,
    SUM(CASE WHEN dl.status = 'given' THEN 1 ELSE 0 END) AS given_cnt,
    COUNT(dl.id) AS total_cnt
    FROM medicines m LEFT JOIN dose_logs dl ON dl.medicine_id = m.id
    GROUP BY m.id, m.name, m.dosage ORDER BY m.name ASC")->fetchAll();

$pageTitle = 'Reports & CSV Export';
$activePage = 'reports.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Reports &amp; CSV Export</h1>
    <p>Expense breakdowns by category and member, medicine adherence rates, and downloadable CSV reports.</p>
  </div>
  <div class="btn-row">
    <a href="reports.php?export=expenses" class="btn btn-primary">Export Expenses CSV</a>
    <a href="reports.php?export=adherence" class="btn btn-outline">Export Adherence CSV</a>
  </div>
</div>

<div class="grid-3">
  <div class="card">
    <h2>Expenses by Category</h2>
    <table>
      <thead><tr><th>Category</th><th>Items</th><th>Total (&#8369;)</th></tr></thead>
      <tbody>
        <?php foreach ($expByCat as $c): ?>
          <tr>
            <td><?= e(ucfirst($c['category'])) ?></td>
            <td><?= (int)$c['cnt'] ?></td>
            <td><strong>&#8369;<?= number_format((float)$c['total_amt'], 2) ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h2>Direct Expenses by Member</h2>
    <table>
      <thead><tr><th>Family Member</th><th>Paid (&#8369;)</th></tr></thead>
      <tbody>
        <?php foreach ($expByPerson as $p): ?>
          <tr>
            <td><?= e($p['full_name']) ?></td>
            <td><strong>&#8369;<?= number_format((float)$p['total_paid'], 2) ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h2>Medicine Adherence</h2>
    <table>
      <thead><tr><th>Medicine</th><th>Given / Logged</th><th>Rate</th></tr></thead>
      <tbody>
        <?php foreach ($adherenceRows as $a): ?>
          <?php
            $tot = (int)$a['total_cnt'];
            $giv = (int)$a['given_cnt'];
            $rate = $tot > 0 ? round(($giv / $tot) * 100, 1) : 0.0;
          ?>
          <tr>
            <td><strong><?= e($a['name']) ?></strong></td>
            <td><?= $giv ?> / <?= $tot ?></td>
            <td><span class="badge <?= $rate >= 80 ? 'badge-given' : 'badge-pending' ?>"><?= $rate ?>%</span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
