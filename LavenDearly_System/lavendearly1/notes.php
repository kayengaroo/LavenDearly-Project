<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
$pdo = db();
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'note') {
        require_perm('note.add');
        $date = post('note_date');
        if (!valid_date($date) || $date > $today) { flash('Enter a valid date that is not in the future.', 'error'); redirect('notes.php'); }
        if (post('mood') === '' && post('meals') === '' && post('sleep') === '' && post('concerns') === '') {
            flash('Write at least one detail before saving the note.', 'error'); redirect('notes.php');
        }
        $pdo->prepare('INSERT INTO care_notes (note_date, mood, meals, sleep, concerns, created_by, created_at) VALUES (?,?,?,?,?,?,?)')
            ->execute([$date, post('mood') ?: null, post('meals') ?: null, post('sleep') ?: null, post('concerns') ?: null, $me['id'], date('Y-m-d H:i:s')]);
        audit('create', 'care note #' . $pdo->lastInsertId(), "Note for $date");
        flash('Care note saved.');
    } elseif ($action === 'comment') {
        require_perm('note.comment');
        $nid = (int)post('note_id'); $c = mb_substr(post('comment'), 0, 500);
        $ex = $pdo->prepare('SELECT COUNT(*) FROM care_notes WHERE id = ?'); $ex->execute([$nid]);
        if ($c === '' || !$ex->fetchColumn()) { flash('Write a comment first.', 'error'); redirect('notes.php'); }
        $pdo->prepare('INSERT INTO note_comments (note_id, user_id, comment, created_at) VALUES (?,?,?,?)')
            ->execute([$nid, $me['id'], $c, date('Y-m-d H:i:s')]);
        audit('create', "comment on note #$nid", '');
        flash('Comment added.');
    }
    redirect('notes.php');
}

$notes = $pdo->query('SELECT n.*, u.full_name FROM care_notes n JOIN users u ON u.id = n.created_by ORDER BY n.note_date DESC, n.id DESC LIMIT 30')->fetchAll();
$comments = [];
if ($notes) {
    $ids = implode(',', array_map('intval', array_column($notes, 'id')));
    foreach ($pdo->query("SELECT c.*, u.full_name FROM note_comments c JOIN users u ON u.id = c.user_id WHERE c.note_id IN ($ids) ORDER BY c.id")->fetchAll() as $c)
        $comments[$c['note_id']][] = $c;
}
$pageTitle = 'Daily Care Notes'; $active = 'notes';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>Daily care notes</h1><p class="muted">Mood, meals, sleep and concerns, so the whole family sees how the day went.</p></div></div>

<?php if (can('note.add')): ?>
<details class="card" open>
  <summary>Write today's note</summary>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="note">
    <div class="form-grid">
      <div class="field"><label for="note_date">Date</label><input type="date" id="note_date" name="note_date" max="<?= $today ?>" value="<?= $today ?>" required></div>
      <div class="field"><label for="mood">Mood</label><input type="text" id="mood" name="mood" maxlength="80" placeholder="e.g. cheerful, tired"></div>
      <div class="field"><label for="meals">Meals</label><input type="text" id="meals" name="meals" maxlength="255" placeholder="e.g. ate most of lunch"></div>
      <div class="field"><label for="sleep">Sleep</label><input type="text" id="sleep" name="sleep" maxlength="120" placeholder="e.g. slept 7 hours"></div>
    </div>
    <div class="field"><label for="concerns">Concerns</label><textarea id="concerns" name="concerns" placeholder="Anything the family or doctor should know"></textarea></div>
    <button class="btn" type="submit">Save note</button>
  </form>
</details>
<?php endif; ?>

<section class="card">
  <?php if (!$notes): ?><div class="empty">No care notes yet.</div><?php endif; ?>
  <?php foreach ($notes as $n): ?>
  <article class="note-item">
    <h3><?= fdate($n['note_date']) ?></h3>
    <p class="muted small">Written by <?= e($n['full_name']) ?></p>
    <p><strong>Mood:</strong> <?= e($n['mood'] ?: '—') ?> &nbsp;·&nbsp; <strong>Meals:</strong> <?= e($n['meals'] ?: '—') ?> &nbsp;·&nbsp; <strong>Sleep:</strong> <?= e($n['sleep'] ?: '—') ?></p>
    <?php if ($n['concerns']): ?><p><strong>Concerns:</strong> <?= e($n['concerns']) ?></p><?php endif; ?>
    <?php foreach ($comments[$n['id']] ?? [] as $c): ?>
      <div class="comment"><strong><?= e($c['full_name']) ?></strong> <span class="muted small"><?= date('M j, g:i A', strtotime($c['created_at'])) ?></span><br><?= e($c['comment']) ?></div>
    <?php endforeach; ?>
    <?php if (can('note.comment')): ?>
    <form method="post" class="actions" style="margin-top:10px">
      <?= csrf_field() ?><input type="hidden" name="action" value="comment"><input type="hidden" name="note_id" value="<?= (int)$n['id'] ?>">
      <input type="text" name="comment" maxlength="500" placeholder="Add a comment" aria-label="Add a comment" style="flex:1;min-width:200px">
      <button class="btn btn-sm btn-ghost">Comment</button>
    </form>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
