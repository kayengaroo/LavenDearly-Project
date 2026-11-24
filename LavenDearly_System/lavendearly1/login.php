<?php
require __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['uid'])) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $locked = ($_SESSION['lock_until'] ?? 0) > time();
    if ($locked) {
        $error = 'Too many failed attempts. Please wait a few minutes and try again.';
    } else {
        $username = post('username');
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        if ($u && $u['is_active'] && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION = ['uid' => (int)$u['id'], 'last' => time()];
            audit('login', 'user #' . $u['id'], 'Signed in', (int)$u['id']);
            redirect('dashboard.php');
        }
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['fails'] = 0; }
        audit('login_failed', 'username', mb_substr($username, 0, 40), null);
        $error = 'Incorrect username or password, or the account is inactive.';
    }
}
$pageTitle = 'Sign in';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · LavenDearly</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:ital,wght@1,600;1,700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth">
  <section class="auth-art">
    <div class="art-copy">
      <div class="brand" style="padding:0;margin-bottom:36px">
        <img src="assets/img/logo.svg" width="56" height="56" alt="">
        <span>LavenDearly</span>
      </div>
      <h1>One shared record for the whole family.</h1>
      <p>Medicines, appointments, expenses and daily notes for an elderly loved one, in one place everyone can trust.</p>
      <p><a href="about.php" style="color:var(--ink)">Read about the project →</a></p>
    </div>
    <!-- scattered lavender sprigs, fading in toward the lower right -->
    <svg class="sprigs" aria-hidden="true" focusable="false" width="100%" height="100%">
      <defs>
      <g id="sp">
        <path d="M20 118C20 92 21 60 20 18" fill="none" style="stroke:var(--leaf)" stroke-width="1.7" stroke-linecap="round"/>
        <path d="M20 113C9 103 5 92 6 82C14 90 19 100 20 113Z" style="fill:var(--leaf)"/>
        <path d="M20 111C31 102 35 92 34 84C26 91 21 100 20 111Z" style="fill:var(--leaf)"/>
        <ellipse cx="16.9" cy="84.0" rx="2.10" ry="4.30" transform="rotate(-30 16.9 84.0)" style="fill:var(--bud)"/>
        <ellipse cx="23.1" cy="81.8" rx="2.10" ry="4.30" transform="rotate(30 23.1 81.8)" style="fill:var(--bud)"/>
        <ellipse cx="17.1" cy="76.6" rx="1.99" ry="4.08" transform="rotate(-30 17.1 76.6)" style="fill:var(--bud)"/>
        <ellipse cx="22.9" cy="74.4" rx="1.99" ry="4.08" transform="rotate(30 22.9 74.4)" style="fill:var(--bud)"/>
        <ellipse cx="17.2" cy="69.2" rx="1.89" ry="3.87" transform="rotate(-30 17.2 69.2)" style="fill:var(--bud)"/>
        <ellipse cx="22.8" cy="67.0" rx="1.89" ry="3.87" transform="rotate(30 22.8 67.0)" style="fill:var(--bud)"/>
        <ellipse cx="17.4" cy="61.8" rx="1.78" ry="3.65" transform="rotate(-30 17.4 61.8)" style="fill:var(--bud)"/>
        <ellipse cx="22.6" cy="59.6" rx="1.78" ry="3.65" transform="rotate(30 22.6 59.6)" style="fill:var(--bud)"/>
        <ellipse cx="17.5" cy="54.4" rx="1.68" ry="3.44" transform="rotate(-30 17.5 54.4)" style="fill:var(--bud)"/>
        <ellipse cx="22.5" cy="52.2" rx="1.68" ry="3.44" transform="rotate(30 22.5 52.2)" style="fill:var(--bud)"/>
        <ellipse cx="17.7" cy="47.0" rx="1.58" ry="3.22" transform="rotate(-30 17.7 47.0)" style="fill:var(--bud)"/>
        <ellipse cx="22.3" cy="44.8" rx="1.58" ry="3.22" transform="rotate(30 22.3 44.8)" style="fill:var(--bud)"/>
        <ellipse cx="17.8" cy="39.6" rx="1.47" ry="3.01" transform="rotate(-30 17.8 39.6)" style="fill:var(--bud)"/>
        <ellipse cx="22.2" cy="37.4" rx="1.47" ry="3.01" transform="rotate(30 22.2 37.4)" style="fill:var(--bud)"/>
        <ellipse cx="18.0" cy="32.2" rx="1.36" ry="2.79" transform="rotate(-30 18.0 32.2)" style="fill:var(--bud)"/>
        <ellipse cx="22.0" cy="30.0" rx="1.36" ry="2.79" transform="rotate(30 22.0 30.0)" style="fill:var(--bud)"/>
        <ellipse cx="18.1" cy="24.8" rx="1.26" ry="2.58" transform="rotate(-30 18.1 24.8)" style="fill:var(--bud)"/>
        <ellipse cx="21.9" cy="22.6" rx="1.26" ry="2.58" transform="rotate(30 21.9 22.6)" style="fill:var(--bud)"/>
        <ellipse cx="20" cy="12" rx="1.9" ry="4.2" style="fill:var(--bud)"/>
      </g>
        <pattern id="lavpat" width="260" height="320" patternUnits="userSpaceOnUse">
      <use href="#sp" transform="translate(34 34) rotate(-8) scale(0.95) translate(-20 -60)" style="--bud:#5B4A78;--leaf:#DDE5D0"/>
      <use href="#sp" transform="translate(168 70) rotate(9) scale(0.72) translate(-20 -60)" style="--bud:#F5F0E6;--leaf:#D5DFC6"/>
      <use href="#sp" transform="translate(100 168) rotate(-16) scale(0.6) translate(-20 -60)" style="--bud:#DCCFEC;--leaf:#E3E8D6"/>
      <use href="#sp" transform="translate(222 186) rotate(6) scale(0.95) translate(-20 -60)" style="--bud:#5B4A78;--leaf:#DDE5D0"/>
      <use href="#sp" transform="translate(26 214) rotate(12) scale(0.5) translate(-20 -60)" style="--bud:#F5F0E6;--leaf:#D5DFC6"/>
      <use href="#sp" transform="translate(150 262) rotate(-6) scale(0.78) translate(-20 -60)" style="--bud:#DCCFEC;--leaf:#E3E8D6"/>
      <use href="#sp" transform="translate(236 40) rotate(20) scale(0.36) translate(-20 -60)" style="--bud:#DCCFEC;--leaf:#E3E8D6"/>
      <use href="#sp" transform="translate(84 100) rotate(-24) scale(0.32) translate(-20 -60)" style="--bud:#F5F0E6;--leaf:#D5DFC6"/>
      <use href="#sp" transform="translate(196 128) rotate(14) scale(0.34) translate(-20 -60)" style="--bud:#5B4A78;--leaf:#DDE5D0"/>
      <use href="#sp" transform="translate(60 290) rotate(-12) scale(0.38) translate(-20 -60)" style="--bud:#5B4A78;--leaf:#DDE5D0"/>
      <use href="#sp" transform="translate(112 40) rotate(18) scale(0.3) translate(-20 -60)" style="--bud:#DCCFEC;--leaf:#E3E8D6"/>
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#lavpat)"/>
    </svg>
  </section>
  <section class="auth-form">
    <div class="auth-box">
      <h2>Welcome back</h2>
      <p class="muted">Sign in with your family account.</p>
      <?php show_flash(); ?>
      <?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <div class="field"><label for="username">Username</label>
          <input type="text" id="username" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>"></div>
        <div class="field"><label for="password">Password</label>
          <input type="password" id="password" name="password" required></div>
        <button class="btn" type="submit" style="width:100%">Sign in</button>
      </form>
      <div class="demo">
        <strong>Starter accounts</strong> (password <code>LavenDearly@2026</code>):<br>
        <code>maria</code> administrator · <code>rosa</code> caregiver · <code>paolo</code>, <code>liza</code> contributors.<br>
        Change these passwords before real use.
      </div>
    </div>
  </section>
</div>
</body>
</html>
