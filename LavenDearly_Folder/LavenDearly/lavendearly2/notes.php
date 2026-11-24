<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = require_login();
$pdo = get_db();
$canPostNote = in_array($currentUser['role'], ['administrator', 'caregiver'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['act'] ?? 'add_note';

    if ($act === 'add_note') {
        require_role(['administrator', 'caregiver'], $currentUser);
        $noteDate = trim((string)($_POST['note_date'] ?? date('Y-m-d')));
        $mood = trim((string)($_POST['mood'] ?? ''));
        $meals = trim((string)($_POST['meals_summary'] ?? ''));
        $sleep = trim((string)($_POST['sleep_summary'] ?? ''));
        $concerns = trim((string)($_POST['concerns'] ?? ''));

        $dObj = DateTime::createFromFormat('Y-m-d', $noteDate);
        if (!$dObj || $dObj->format('Y-m-d') !== $noteDate || $mood === '' || $concerns === '') {
            set_flash('error', 'Please enter a valid note date, mood, and care observation.');
        } else {
            $ins = $pdo->prepare('INSERT INTO care_notes (patient_id, author_id, note_date, mood, meals_summary, sleep_summary, concerns) VALUES (1, :aid, :nd, :md, :ml, :sl, :cn)');
            $ins->execute([
                ':aid' => (int)$currentUser['id'], ':nd' => $noteDate, ':md' => $mood,
                ':ml' => $meals, ':sl' => $sleep, ':cn' => $concerns,
            ]);
            $newId = (int)$pdo->lastInsertId();
            log_audit((int)$currentUser['id'], 'ADD_NOTE', 'Daily Care Notes', 'NOTE-' . $newId, 'Posted daily care note for ' . $noteDate);
            set_flash('success', 'Daily care note posted.');
        }
    } elseif ($act === 'add_comment') {
        $noteId = (int)($_POST['note_id'] ?? 0);
        $commentText = trim((string)($_POST['comment_text'] ?? ''));
        if ($noteId <= 0 || $commentText === '') {
            set_flash('error', 'Comment cannot be empty.');
        } else {
            $ins = $pdo->prepare('INSERT INTO care_note_comments (note_id, author_id, comment_text) VALUES (:nid, :aid, :txt)');
            $ins->execute([':nid' => $noteId, ':aid' => (int)$currentUser['id'], ':txt' => $commentText]);
            log_audit((int)$currentUser['id'], 'COMMENT_NOTE', 'Daily Care Notes', 'NOTE-' . $noteId, 'Added comment on care note #' . $noteId);
            set_flash('success', 'Comment added.');
        }
    }
    header('Location: notes.php');
    exit;
}

$notes = $pdo->query('SELECT cn.*, u.full_name AS author_name, u.role AS author_role FROM care_notes cn JOIN users u ON u.id = cn.author_id ORDER BY cn.note_date DESC, cn.id DESC')->fetchAll();
$commentsRows = $pdo->query('SELECT cnc.*, u.full_name AS commenter_name, u.role AS commenter_role FROM care_note_comments cnc JOIN users u ON u.id = cnc.author_id ORDER BY cnc.created_at ASC, cnc.id ASC')->fetchAll();
$commentsByNote = [];
foreach ($commentsRows as $c) {
    $commentsByNote[(int)$c['note_id']][] = $c;
}

$pageTitle = 'Daily Care Notes';
$activePage = 'notes.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>Daily Care Notes</h1>
    <p>Observations on mood, meals, sleep, and daily concerns—with comments from family members.</p>
  </div>
</div>

<div class="grid-2">
  <div>
    <?php if (!$canPostNote): ?>
      <div class="card">
        <h2>Family Check-In Note</h2>
        <div class="flash flash-warning">As a Contributor, you can read all daily care notes and post comments or questions on any note.</div>
      </div>
    <?php else: ?>
      <div class="card">
        <h2>Post Daily Care Note</h2>
        <form method="post" action="notes.php">
          <?= csrf_field() ?>
          <input type="hidden" name="act" value="add_note">
          <div class="form-grid">
            <div class="form-group">
              <label for="note_date">Date *</label>
              <input type="date" id="note_date" name="note_date" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
              <label for="mood">Mood *</label>
              <input type="text" id="mood" name="mood" required placeholder="e.g., Cheerful, Tired, Calm">
            </div>
          </div>
          <div class="form-group">
            <label for="meals_summary">Meals &amp; Hydration</label>
            <input type="text" id="meals_summary" name="meals_summary" placeholder="e.g., Finished breakfast & lunch; 6 glasses water">
          </div>
          <div class="form-group">
            <label for="sleep_summary">Sleep Summary</label>
            <input type="text" id="sleep_summary" name="sleep_summary" placeholder="e.g., 7 hours overnight, rested afternoon nap">
          </div>
          <div class="form-group">
            <label for="concerns">Daily Notes / Concerns *</label>
            <textarea id="concerns" name="concerns" required placeholder="Any symptoms, vitals, activities, or reminders for the family"></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Publish Care Note</button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <?php foreach ($notes as $n): ?>
      <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
          <h3 style="margin:0;"><?= e($n['note_date']) ?> — <?= e($n['mood']) ?></h3>
          <span class="kpi-sub">By <strong><?= e($n['author_name']) ?></strong> (<?= e($n['author_role']) ?>)</span>
        </div>
        <p style="margin:10px 0 6px; font-size:13px;"><strong>Meals:</strong> <?= e($n['meals_summary'] ?: '—') ?></p>
        <p style="margin:0 0 8px; font-size:13px;"><strong>Sleep:</strong> <?= e($n['sleep_summary'] ?: '—') ?></p>
        <p style="margin:0 0 12px;"><?= e($n['concerns']) ?></p>

        <?php $nComments = $commentsByNote[(int)$n['id']] ?? []; ?>
        <div class="comment-list">
          <?php foreach ($nComments as $cm): ?>
            <div class="comment-item">
              <strong><?= e($cm['commenter_name']) ?></strong>: <?= e($cm['comment_text']) ?>
              <div class="comment-meta"><?= e($cm['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <form method="post" action="notes.php" class="btn-row" style="margin-top:10px;">
          <?= csrf_field() ?>
          <input type="hidden" name="act" value="add_comment">
          <input type="hidden" name="note_id" value="<?= (int)$n['id'] ?>">
          <input type="text" name="comment_text" required placeholder="Add a family comment or question..." style="flex:1; min-width:180px;">
          <button type="submit" class="btn btn-sm btn-outline">Comment</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
