<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();
$canAdd = in_array($currentUser['role'], ['administrator', 'caregiver'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_role(['administrator', 'caregiver'], $currentUser);

    $expDate = trim((string)($_POST['expense_date'] ?? date('Y-m-d')));
    $itemName = trim((string)($_POST['item_name'] ?? ''));
    $category = $_POST['category'] ?? 'medicine';
    $amountRaw = (string)($_POST['amount'] ?? '0');
    $paidBy = (int)($_POST['paid_by'] ?? $currentUser['id']);
    $notes = trim((string)($_POST['notes'] ?? ''));

    $dObj = DateTime::createFromFormat('Y-m-d', $expDate);
    $amount = is_numeric($amountRaw) ? (float)$amountRaw : 0.0;

    if (!$dObj || $dObj->format('Y-m-d') !== $expDate || $itemName === '' || $amount <= 0 || !in_array($category, ['medicine', 'groceries', 'checkup', 'supplies', 'other'], true)) {
        set_flash('error', 'Please enter a valid date, item name, category, and a positive amount greater than 0.');
    } else {
        $receiptPath = null;
        $uploadFailed = false;

        if (!empty($_FILES['receipt_file']['name'])) {
            $file = $_FILES['receipt_file'];
            if ((int)$file['error'] !== UPLOAD_ERR_OK) {
                set_flash('error', 'Receipt upload error.');
                $uploadFailed = true;
            } elseif ((int)$file['size'] > 5 * 1024 * 1024) {
                set_flash('error', 'Receipt file must be 5 MB or smaller.');
                $uploadFailed = true;
            } else {
                $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true) || !in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
                    set_flash('error', 'Receipt must be PDF, JPG, JPEG, or PNG.');
                    $uploadFailed = true;
                } else {
                    $fname = 'rcpt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $dir = __DIR__ . '/uploads/receipts';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    if (move_uploaded_file($file['tmp_name'], $dir . '/' . $fname)) {
                        $receiptPath = 'uploads/receipts/' . $fname;
                    } else {
                        set_flash('error', 'Failed to save receipt file.');
                        $uploadFailed = true;
                    }
                }
            }
        }

        if (!$uploadFailed) {
            $stmt = $pdo->prepare('INSERT INTO expenses (patient_id, expense_date, item_name, category, amount, paid_by, receipt_path, notes) VALUES (1, :ed, :item, :cat, :amt, :pb, :rp, :nt)');
            $stmt->execute([
                ':ed' => $expDate, ':item' => $itemName, ':cat' => $category,
                ':amt' => round($amount, 2), ':pb' => $paidBy, ':rp' => $receiptPath, ':nt' => $notes,
            ]);
            $newId = (int)$pdo->lastInsertId();
            log_audit((int)$currentUser['id'], 'CREATE', 'Expenses', 'EXP-' . $newId, sprintf('Added expense %s (PHP %.2f)', $itemName, $amount));
            set_flash('success', 'Expense recorded.');
        }
    }
    header('Location: expenses.php');
    exit;
}

$activeUsers = $pdo->query('SELECT id, full_name, role FROM users WHERE is_active = 1 ORDER BY full_name ASC')->fetchAll();
$expenses = $pdo->query('SELECT e.*, u.full_name AS payer_name FROM expenses e JOIN users u ON u.id = e.paid_by ORDER BY e.expense_date DESC, e.id DESC')->fetchAll();
$sumExpenses = (float)$pdo->query('SELECT COALESCE(SUM(amount), 0) FROM expenses')->fetchColumn();

$pageTitle = 'Expenses';
$activePage = 'expenses.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Expense Recording</h1>
    <p>Track purchases for medicines, groceries, check-ups, and care supplies with receipts.</p>
  </div>
  <a href="contributions.php" class="btn btn-outline">View Contribution Balances</a>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Logged Expenses (Total: &#8369;<?= number_format($sumExpenses, 2) ?>)</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Date</th><th>Item &amp; Category</th><th>Amount</th><th>Paid By</th><th>Receipt</th></tr>
        </thead>
        <tbody>
          <?php foreach ($expenses as $ex): ?>
            <tr>
              <td><?= e($ex['expense_date']) ?></td>
              <td>
                <strong><?= e($ex['item_name']) ?></strong><br>
                <span class="badge badge-pending"><?= e(ucfirst($ex['category'])) ?></span>
                <?php if ($ex['notes']): ?><div class="kpi-sub"><?= e($ex['notes']) ?></div><?php endif; ?>
              </td>
              <td><strong>&#8369;<?= number_format((float)$ex['amount'], 2) ?></strong></td>
              <td><?= e($ex['payer_name']) ?></td>
              <td>
                <?php if (!empty($ex['receipt_path'])): ?>
                  <a href="file.php?type=receipt&amp;id=<?= (int)$ex['id'] ?>" target="_blank" class="btn btn-sm btn-outline">Receipt</a>
                <?php else: ?>
                  <span class="kpi-sub">None</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>Record New Expense</h2>
    <?php if (!$canAdd): ?>
      <div class="flash flash-warning">Contributors can view expenses and record their own contributions on the Contributions page.</div>
    <?php else: ?>
      <form method="post" action="expenses.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="form-group">
            <label for="expense_date">Date *</label>
            <input type="date" id="expense_date" name="expense_date" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label for="category">Category *</label>
            <select id="category" name="category" required>
              <option value="medicine">Medicine</option>
              <option value="groceries">Groceries</option>
              <option value="checkup">Check-up</option>
              <option value="supplies">Supplies</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label for="item_name">Item / Description *</label>
          <input type="text" id="item_name" name="item_name" required placeholder="e.g., Losartan 50mg 30 tabs">
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="amount">Amount (PHP) *</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required placeholder="0.00">
          </div>
          <div class="form-group">
            <label for="paid_by">Paid By *</label>
            <select id="paid_by" name="paid_by" required>
              <?php foreach ($activeUsers as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= (int)$u['id'] === (int)$currentUser['id'] ? 'selected' : '' ?>>
                  <?= e($u['full_name']) ?> (<?= e($u['role']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label for="receipt_file">Receipt File (PDF, JPG, PNG — max 5 MB)</label>
          <input type="file" id="receipt_file" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png">
        </div>
        <div class="form-group">
          <label for="notes">Notes</label>
          <input type="text" id="notes" name="notes" placeholder="Pharmacy name or extra details">
        </div>
        <button type="submit" class="btn btn-primary">Save Expense</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
