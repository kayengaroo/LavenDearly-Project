<?php
require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Manila');

const SESSION_TIMEOUT  = 1200; // 20 minutes of inactivity
const DOSE_GRACE_MIN   = 60;   // a dose is "overdue" 60 minutes after its scheduled time
const REFILL_WARN_DAYS = 7;
const MAX_UPLOAD_BYTES = 5242880; // 5 MB

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* ---------- basic helpers ---------- */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }
function post(string $k, string $default = ''): string { return trim((string)($_POST[$k] ?? $default)); }
function peso($n): string { return '₱' . number_format((float)$n, 2); }
function fdate(?string $d): string { return $d ? date('M j, Y', strtotime($d)) : '—'; }
function ftime(?string $t): string { return $t ? date('g:i A', strtotime(substr($t, 0, 5))) : '—'; }
function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}
function valid_amount(string $a): bool { return is_numeric($a) && (float)$a > 0 && (float)$a < 10000000; }

function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'][] = [$type, $msg]; }
function show_flash(): void
{
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        echo '<div class="flash flash-' . e($type) . '" role="status">' . e($msg) . '</div>';
    }
    unset($_SESSION['flash']);
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
            http_response_code(400);
            exit('Invalid or expired form. Please go back, refresh the page and try again.');
        }
    }
}

/* ---------- roles & permissions ---------- */
const PERMS = [
    'patient.edit'      => ['admin', 'caregiver'],
    'members.manage'    => ['admin'],
    'users.manage'      => ['admin'],
    'medicine.manage'   => ['admin', 'caregiver'],
    'dose.log'          => ['admin', 'caregiver'],
    'dose.delete'       => ['admin'],
    'appointment.add'   => ['admin', 'caregiver'],
    'appointment.delete'=> ['admin'],
    'expense.add'       => ['admin', 'caregiver', 'contributor'],
    'expense.delete'    => ['admin'],
    'contribution.add'  => ['admin', 'contributor'],
    'note.add'          => ['admin', 'caregiver'],
    'note.comment'      => ['admin', 'caregiver', 'contributor'],
    'reports.view'      => ['admin'],
    'audit.view'        => ['admin'],
    'summary.print'     => ['admin', 'caregiver'],
    'doses.view'        => ['admin', 'caregiver'],
];

function me(): ?array { return $GLOBALS['me'] ?? null; }
function can(string $perm): bool
{
    $u = me();
    return $u && in_array($u['role'], PERMS[$perm] ?? [], true);
}
function require_perm(string $perm): void
{
    if (!can($perm)) {
        flash('Your role does not have access to that page or action.', 'error');
        redirect('dashboard.php');
    }
}
function role_label(string $r): string
{
    return ['admin' => 'Family Administrator', 'caregiver' => 'Caregiver', 'contributor' => 'Contributor'][$r] ?? $r;
}

function require_login(): array
{
    if (empty($_SESSION['uid'])) redirect('login.php');
    if (time() - ($_SESSION['last'] ?? 0) > SESSION_TIMEOUT) {
        $_SESSION = [];
        flash('Your session timed out after a period of inactivity. Please sign in again.', 'warn');
        redirect('login.php');
    }
    $_SESSION['last'] = time();
    $st = db()->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u) {
        $_SESSION = [];
        flash('Your account is no longer active.', 'error');
        redirect('login.php');
    }
    $GLOBALS['me'] = $u;
    csrf_check();
    return $u;
}

/* ---------- audit trail ---------- */
function audit(string $action, string $record = '', string $details = '', ?int $uid = null): void
{
    $uid = $uid ?? ($_SESSION['uid'] ?? null);
    $st = db()->prepare('INSERT INTO audit_log (user_id, action, record, details, created_at) VALUES (?,?,?,?,?)');
    $st->execute([$uid, $action, $record, mb_substr($details, 0, 500), date('Y-m-d H:i:s')]);
}

/* ---------- file uploads (type + size checked, stored outside public access) ---------- */
function save_upload(string $field, string $subdir): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('The file could not be uploaded. Please try again.');
    if ($f['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('The file is larger than 5 MB.');
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) throw new RuntimeException('Only JPG, PNG or PDF files are allowed.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if ($mime !== $allowed[$ext]) throw new RuntimeException('The file content does not match its type.');
    $dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('The file could not be saved.');
    return $subdir . '/' . $name;
}

/* ---------- medicines & doses ---------- */
function parse_times(string $raw): ?array
{
    $parts = preg_split('/[\s,;]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
    $out = [];
    foreach ($parts as $p) {
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $p, $m)) return null;
        $out[] = sprintf('%02d:%s', (int)$m[1], $m[2]);
    }
    $out = array_values(array_unique($out));
    sort($out);
    return $out ?: null;
}

/** Every scheduled dose for a date, with its logged status (or overdue / pending / upcoming). */
function scheduled_doses(string $date): array
{
    $pdo = db();
    $st = $pdo->prepare("SELECT * FROM medicines
        WHERE start_date <= ? AND (status = 'active' OR (end_date IS NOT NULL AND end_date >= ?))
        ORDER BY name");
    $st->execute([$date, $date]);
    $meds = $st->fetchAll();

    $lg = $pdo->prepare('SELECT d.*, u.full_name AS logger FROM dose_log d JOIN users u ON u.id = d.logged_by WHERE d.scheduled_date = ?');
    $lg->execute([$date]);
    $logs = [];
    foreach ($lg->fetchAll() as $row) $logs[$row['medicine_id'] . '|' . substr($row['scheduled_time'], 0, 5)] = $row;

    $today = date('Y-m-d');
    $rows = [];
    foreach ($meds as $m) {
        foreach (explode(',', $m['times_of_day']) as $t) {
            $t = trim($t);
            if ($t === '') continue;
            $log = $logs[$m['id'] . '|' . $t] ?? null;
            if ($log) $state = $log['status'];
            elseif ($date > $today) $state = 'upcoming';
            elseif (strtotime("$date $t") + DOSE_GRACE_MIN * 60 < time()) $state = 'overdue';
            else $state = 'pending';
            $rows[] = ['med_id' => $m['id'], 'name' => $m['name'], 'dose' => $m['dose'], 'time' => $t,
                       'date' => $date, 'log' => $log, 'state' => $state, 'med_status' => $m['status']];
        }
    }
    usort($rows, fn($a, $b) => [$a['time'], $a['name']] <=> [$b['time'], $b['name']]);
    return $rows;
}

function overdue_doses(int $daysBack = 1): array
{
    $out = [];
    for ($i = $daysBack; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        foreach (scheduled_doses($d) as $r) if ($r['state'] === 'overdue' && $r['med_status'] === 'active') $out[] = $r;
    }
    return $out;
}

function refills_due(int $days = REFILL_WARN_DAYS): array
{
    $st = db()->prepare("SELECT * FROM medicines WHERE status = 'active' AND refill_date IS NOT NULL AND refill_date <= ? ORDER BY refill_date");
    $st->execute([date('Y-m-d', strtotime("+$days day"))]);
    return $st->fetchAll();
}

function chip(string $state): string
{
    $labels = ['given' => 'Given', 'missed' => 'Missed', 'skipped' => 'Skipped', 'overdue' => 'Overdue',
               'pending' => 'Pending', 'upcoming' => 'Upcoming'];
    return '<span class="chip chip-' . e($state) . '">' . e($labels[$state] ?? ucfirst($state)) . '</span>';
}

/** Adherence for each medicine between two dates (given vs. scheduled doses that have already come due). */
function adherence(string $from, string $to): array
{
    $pdo = db();
    $meds = $pdo->query('SELECT * FROM medicines ORDER BY name')->fetchAll();
    $now = time();
    $out = [];
    foreach ($meds as $m) {
        $start = max($from, $m['start_date']);
        $end = $to;
        if ($m['end_date'] && $m['end_date'] < $end) $end = $m['end_date'];
        $scheduled = 0;
        if ($start <= $end) {
            for ($d = strtotime($start); $d <= strtotime($end); $d += 86400) {
                $ds = date('Y-m-d', $d);
                foreach (explode(',', $m['times_of_day']) as $t) {
                    if (trim($t) !== '' && strtotime("$ds " . trim($t)) <= $now) $scheduled++;
                }
            }
        }
        $st = $pdo->prepare('SELECT status, COUNT(*) c FROM dose_log WHERE medicine_id = ? AND scheduled_date BETWEEN ? AND ? GROUP BY status');
        $st->execute([$m['id'], $from, $to]);
        $c = ['given' => 0, 'missed' => 0, 'skipped' => 0];
        foreach ($st->fetchAll() as $r) $c[$r['status']] = (int)$r['c'];
        if ($scheduled === 0 && array_sum($c) === 0) continue;
        $out[] = ['name' => $m['name'], 'dose' => $m['dose'], 'scheduled' => $scheduled,
                  'given' => $c['given'], 'missed' => $c['missed'], 'skipped' => $c['skipped'],
                  'rate' => $scheduled ? round($c['given'] / $scheduled * 100) : null];
    }
    return $out;
}

/* ---------- money: shares, contributions, balances ---------- */
/**
 * Balance = (expenses the member paid personally + contributions they sent) - (their agreed share of ALL expenses).
 * Positive = overpaid (is owed); negative = still owes. Expenses with no payer are paid from the shared care fund.
 */
function balances(): array
{
    $pdo = db();
    $total = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses')->fetchColumn();
    $fundExp = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE paid_by IS NULL')->fetchColumn();
    $contribTotal = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM contributions')->fetchColumn();
    $personal = $pdo->query('SELECT paid_by, SUM(amount) s FROM expenses WHERE paid_by IS NOT NULL GROUP BY paid_by')->fetchAll(PDO::FETCH_KEY_PAIR);
    $contrib = $pdo->query('SELECT user_id, SUM(amount) s FROM contributions GROUP BY user_id')->fetchAll(PDO::FETCH_KEY_PAIR);
    $rows = [];
    foreach ($pdo->query('SELECT id, full_name, relationship, role, share_percent, is_active FROM users ORDER BY full_name')->fetchAll() as $u) {
        $p = (float)($personal[$u['id']] ?? 0);
        $c = (float)($contrib[$u['id']] ?? 0);
        if (!$u['is_active'] && $p + $c == 0) continue;
        $share = $total * (float)$u['share_percent'] / 100;
        $rows[] = $u + ['share' => $share, 'personal' => $p, 'contrib' => $c, 'paid' => $p + $c, 'balance' => $p + $c - $share];
    }
    return ['total' => $total, 'fund' => $contribTotal - $fundExp, 'rows' => $rows];
}

/* ---------- navigation ---------- */
function nav_items(): array
{
    $items = [
        ['dashboard.php',    'dashboard',    'Dashboard',          null],
        ['patient.php',      'patient',      'Patient Profile',    null],
        ['medicines.php',    'medicines',    'Medicine Schedule',  null],
        ['doselog.php',      'doselog',      'Dose Log',           'doses.view'],
        ['appointments.php', 'appointments', 'Appointments & Docs', null],
        ['notes.php',        'notes',        'Daily Care Notes',   null],
        ['expenses.php',     'expenses',     'Expenses',           null],
        ['contributions.php','contributions','Contributions & Balances', null],
        ['search.php',       'search',       'Search & Filter',    null],
        ['reports.php',      'reports',      'Reports',            'reports.view'],
        ['summary.php',      'summary',      'Doctor Summary',     'summary.print'],
        ['members.php',      'members',      'Family Members',     null],
        ['users.php',        'users',        'User Management',    'users.manage'],
        ['audit.php',        'audit',        'Audit Log',          'audit.view'],
        ['about.php',        'about',        'About the Project',  null],
    ];
    return array_values(array_filter($items, fn($i) => $i[3] === null || can($i[3])));
}
