<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
$pdo = db();
$today = date('Y-m-d');
$types = ['visit' => 'Doctor visit', 'lab' => 'Lab result', 'prescription' => 'Prescription'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'add') {
        require_perm('appointment.add');
        $type = post('type'); $title = post('title'); $date = post('appt_date');
        $err = null;
        if (!isset($types[$type])) $err = 'Choose a record type.';
        elseif ($title === '') $err = 'A title is required.';
        elseif (!valid_date($date)) $err = 'Enter a valid date.';
        $path = null;
        if (!$err) {
            try { $path = save_upload('file', 'appointments'); } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
        }
        if ($err) { flash($err, 'error'); redirect('appointments.php'); }
        $pdo->prepare('INSERT INTO appointments (type, title, doctor, appt_date, notes, file_path, created_by, created_at) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$type, $title, post('doctor') ?: null, $date, post('notes') ?: null, $path, $me['id'], date('Y-m-d H:i:s')]);
        audit('create', 'appointment #' . $pdo->lastInsertId(), "$type: $title");
        flash('Record saved.');
    } elseif ($action === 'delete') {
        require_perm('appointment.delete');
        $id = (int)post('id');
        $st = $pdo->prepare('SELECT file_path, title FROM appointments WHERE id = ?'); $st->execute([$id]);
        if ($row = $st->fetch()) {
            if ($row['file_path']) @unlink(__DIR__ . '/uploads/' . $row['file_path']);
            $pdo->prepare('DELETE FROM appointments WHERE id = ?')->execute([$id]);
            audit('delete', "appointment #$id", $row['title']);
            flash('Record deleted.');
        }
    }
    redirect('appointments.php');
}

$filter = $_GET['type'] ?? '';
$sql = 'SELECT a.*, u.full_name AS creator FROM appointments a JOIN users u ON u.id = a.created_by';
$args = [];
if (isset($types[$filter])) { $sql .= ' WHERE a.type = ?'; $args[] = $filter; }
$st = $pdo->prepare($sql . ' ORDER BY a.appt_date DESC, a.id DESC');
$st->execute($args);
$list = $st->fetchAll();

$pageTitle = 'Appointments & Documents'; $active = 'appointments';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div><h1>Appointments &amp; documents</h1><p class="muted">Doctor visits, lab results and prescriptions, with their files.</p></div>
  <form method="get" class="actions"><select name="type" aria-label="Filter by type" style="width:auto" onchange="this.form.submit()">
    <option value="">All records</option>
    <?php foreach ($types as $k => $v): ?><option value="<?= $k ?>" <?= $filter === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
  </select></form>
</div>

<?php if (can('appointment.add')): ?>
<details class="card">
  <summary>Add a record</summary>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="field"><label for="type">Type</label><select id="type" name="type"><?php foreach ($types as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="title">Title</label><input type="text" id="title" name="title" required maxlength="150" placeholder="e.g. Cardiology follow-up"></div>
      <div class="field"><label for="appt_date">Date</label><input type="date" id="appt_date" name="appt_date" required value="<?= $today ?>"></div>
      <div class="field"><label for="doctor">Doctor</label><input type="text" id="doctor" name="doctor" maxlength="120"></div>
    </div>
    <div class="field"><label for="notes">Notes</label><textarea id="notes" name="notes"></textarea></div>
    <div class="field"><label for="file">File (JPG, PNG or PDF, up to 5 MB)</label><input type="file" id="file" name="file" accept=".jpg,.jpeg,.png,.pdf"></div>
    <button class="btn" type="submit">Save record</button>
  </form>
</details>
<?php endif; ?>

<section class="card">
  <?php if (!$list): ?><div class="empty">No records found.</div><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Type</th><th>Title</th><th>Doctor</th><th>Notes</th><th>File</th><?php if (can('appointment.delete')): ?><th></th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($list as $a): ?>
      <tr>
        <td><?= fdate($a['appt_date']) ?><?= $a['appt_date'] >= $today ? '<br><span class="chip chip-pending">Upcoming</span>' : '' ?></td>
        <td><?= e($types[$a['type']]) ?></td>
        <td><strong><?= e($a['title']) ?></strong><br><span class="muted small">added by <?= e($a['creator']) ?></span></td>
        <td><?= e($a['doctor'] ?: '—') ?></td>
        <td><?= e($a['notes'] ?: '') ?></td>
        <td><?= $a['file_path'] ? '<a href="file.php?t=appointment&id=' . (int)$a['id'] . '" target="_blank" rel="noopener">View file</a>' : '—' ?></td>
        <?php if (can('appointment.delete')): ?>
        <td><form method="post" onsubmit="return confirm('Delete this record and its file?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
