<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if (check_session_timeout()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both email address and password.';
    } else {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['last_activity'] = time();

            $upd = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
            $upd->execute([':id' => (int)$user['id']]);

            log_audit((int)$user['id'], 'LOGIN', 'Authentication', 'USER-' . $user['id'], 'Signed in as ' . $user['role']);
            set_flash('success', 'Welcome back, ' . $user['full_name'] . ' (' . ucfirst($user['role']) . ').');
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials or inactive account.';
        }
    }
}

$currentUser = null;
$pageTitle = 'Sign In';
$activePage = 'login.php';
include __DIR__ . '/includes/header.php';
?>
<div class="login-wrap">
  <div class="card">
    <div class="login-brand-header">
      <svg class="logo-svg" style="margin:0 auto 6px;" viewBox="0 0 64 52" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <text x="5" y="38" fill="#7E699E" class="logo-d">D</text>
        <path d="M43 44 C43 31, 44 19, 46 10" stroke="#6E7D63" stroke-width="2.2" fill="none" stroke-linecap="round"/>
        <path d="M44 35 C39 33, 38 30, 44 31" fill="#8C9C7E"/>
        <path d="M44 29 C49 27, 50 24, 44 25" fill="#8C9C7E"/>
        <ellipse cx="45" cy="18" rx="3.2" ry="2" fill="#A693C2" transform="rotate(-15 45 18)"/>
        <ellipse cx="48" cy="15" rx="3.2" ry="2" fill="#C6B5D8" transform="rotate(15 48 15)"/>
        <ellipse cx="44" cy="13" rx="3.2" ry="2" fill="#A693C2" transform="rotate(-18 44 13)"/>
        <ellipse cx="48" cy="10" rx="3" ry="1.8" fill="#C6B5D8" transform="rotate(12 48 10)"/>
        <ellipse cx="46" cy="7" rx="2.6" ry="1.8" fill="#7E699E"/>
      </svg>
      <h2 style="margin-bottom:2px;">Sign In to LavenDearly</h2>
      <p class="kpi-sub">Access shared caregiving records for your elderly family member.</p>
    </div>

    <?php if (!empty($_GET['timeout'])): ?>
      <div class="flash flash-warning">Your session timed out due to inactivity. Please sign in again.</div>
    <?php elseif (!empty($_GET['inactive'])): ?>
      <div class="flash flash-error">Your account is inactive. Contact the Family Administrator.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="name@lavendearly.ph">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="Enter password">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
    </form>

    <div class="demo-box">
      <strong>Seeded Demo Accounts (Password: <code>CareCircle2026!</code>)</strong>
      <ul style="margin:6px 0 0; padding-left:18px;">
        <li><strong>Administrator:</strong> maria.santos@carecircle.ph</li>
        <li><strong>Caregiver:</strong> rosa.reyes@carecircle.ph</li>
        <li><strong>Contributor:</strong> marco.santos@carecircle.ph</li>
      </ul>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
