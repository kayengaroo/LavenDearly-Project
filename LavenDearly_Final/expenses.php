<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
$pdo = db();
$today = date('Y-m-d');
$cats = ['Medicines', 'Groceries', 'Check-up fees', 'Supplies', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'add') {
        require_perm('expense.add');
        $date = post('expense_date'); $item = post('item'); $cat = post('category'); $amt = post('amount'); $payer = post('paid_by');
        $err = null;
        if (!valid_date($date) || $date > $today) $err = 'Enter a valid date that is not in the future.';
        elseif ($item === '') $err = 'Describe what was bought.';
        elseif (!in_array($cat, $cats, true)) $err = 'Choose a category.';
        elseif (!valid_amount($amt)) $err = 'The amount must be a positive number.';
        $paidBy = null;
        if (!$err) {
            if ($me['role'] === 'contributor') {
                $paidBy = (int)$me['id'];               // contributors can only record purchases they paid themselves
            } elseif ($payer !== 'fund') {
                $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ?'); $chk->execute([(int)$payer]);
                if (!$chk->fetchColumn()) $err = 'Choose who paid.'; else $paidBy = (int)$payer;
            }
        }
        $path = null;
        if (!$err) { try { $path = save_upload('receipt', 'receipts'); } catch (RuntimeException $ex) { $err = $ex->getMessage(); } }
        if ($err) { flash($err, 'error'); redirect('expenses.php'); }
        $pdo->prepare('INSERT INTO expenses (expense_date, item, category, amount, paid_by, receipt_path, created_by, created_at) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$date, $item, $cat, number_format((float)$amt, 2, '.', ''), $paidBy, $path, $me['id'], date('Y-m-d H:i:s')]);
        audit('create', 'expense #' . $pdo->lastInsertId(), "$item " . peso($amt));
        flash('Expense recorded.');
    } elseif ($action === 'delete') {
        require_perm('expense.delete');
        $id = (int)post('id');
        $st = $pdo->prepare('SELECT receipt_path, item FROM expenses WHERE id = ?'); $st->execute([$id]);
        if ($row = $st->fetch()) {
            if ($row['receipt_path']) @unlink(__DIR__ . '/uploads/' . $row['receipt_path']);
            $pdo->prepare('DELETE FROM expenses WHERE id = ?')->execute([$id]);
            audit('delete', "expense #$id", $row['item']);
            flash('Expense deleted.');
        }
    }
    redirect('expenses.php');
}

$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$cat = in_array($_GET['category'] ?? '', $cats, true) ? $_GET['category'] : '';
$sql = 'SELECT x.*, u.full_name AS payer FROM expenses x LEFT JOIN users u ON u.id = x.paid_by WHERE x.expense_date BETWEEN ? AND ?';
$args = ["$month-01", date('Y-m-t', strtotime("$month-01"))];
if ($cat) { $sql .= ' AND x.category = ?'; $args[] = $cat; }
$st = $pdo->prepare($sql . ' ORDER BY x.expense_date DESC, x.id DESC');
$st->execute($args);
$list = $st->fetchAll();
$total = array_sum(array_column($list, 'amount'));
$users = $pdo->query('SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name')->fetchAll();

$pageTitle = 'Expenses'; $active = 'expenses';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div><h1>Expenses</h1><p class="muted">Record purchases for medicines, groceries and check-ups, with the receipt.</p></div>
  <form method="get" class="actions">
    <input type="month" name="month" value="<?= e($month) ?>" aria-label="Month" style="width:auto">
    <select name="category" aria-label="Category" style="width:auto"><option value="">All categories</option>
      <?php foreach ($cats as $c): ?><option <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
    <button class="btn btn-sm">Filter</button>
  </form>
</div>

<?php if (can('expense.add')): ?>
<details class="card">
  <summary>Record an expense</summary>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="field"><label for="expense_date">Date</label><input type="date" id="expense_date" name="expense_date" max="<?= $today ?>" value="<?= $today ?>" required></div>
      <div class="field"><label for="item">Item</label><input type="text" id="item" name="item" maxlength="150" required placeholder="e.g. Losartan 50 mg, 30 tablets"></div>
      <div class="field"><label for="category">Category</label><select id="category" name="category"><?php foreach ($cats as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="amount">Amount (₱)</label><input type="number" id="amount" name="amount" step="0.01" min="0.01" required></div>
      <?php if ($me['role'] !== 'contributor'): ?>
      <div class="field"><label for="paid_by">Paid by</label>
        <select id="paid_by" name="paid_by">
          <option value="fund">Shared care fund</option>
          <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?> (own money)</option><?php endforeach; ?>
        </select>
        <div class="hint">Choose “Shared care fund” when the money came from contributions sent to the household.</div></div>
      <?php endif; ?>
    </div>
    <div class="field"><label for="receipt">Receipt (JPG, PNG or PDF, up to 5 MB)</label><input type="file" id="receipt" name="receipt" accept=".jpg,.jpeg,.png,.pdf"></div>
    <button class="btn" type="submit">Save expense</button>
  </form>
</details>
<?php endif; ?>

<section class="card">
  <h2>Expenses for <?= date('F Y', strtotime("$month-01")) ?> <span class="muted" style="font-family:inherit;font-style:normal;font-size:.95rem">· total <?= peso($total) ?></span></h2>
  <?php if (!$list): ?><div class="empty">No expenses match this filter.</div><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Item</th><th>Category</th><th class="num">Amount</th><th>Paid by</th><th>Receipt</th><?php if (can('expense.delete')): ?><th></th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($list as $x): ?>
      <tr><td><?= fdate($x['expense_date']) ?></td><td><?= e($x['item']) ?></td><td><?= e($x['category']) ?></td>
        <td class="num"><?= peso($x['amount']) ?></td><td><?= $x['payer'] ? e($x['payer']) : 'Shared care fund' ?></td>
        <td><?= $x['receipt_path'] ? '<a href="file.php?t=expense&id=' . (int)$x['id'] . '" target="_blank" rel="noopener">View receipt</a>' : '—' ?></td>
        <?php if (can('expense.delete')): ?><td><form method="post" onsubmit="return confirm('Delete this expense?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></td><?php endif; ?></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
