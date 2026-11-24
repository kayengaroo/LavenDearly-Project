<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

function e(?string $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Invalid or expired CSRF token.');
    }
}

function check_session_timeout(): bool {
    if (empty($_SESSION['user_id'])) {
        return false;
    }
    $last = (int)($_SESSION['last_activity'] ?? 0);
    if ($last > 0 && (time() - $last) > SESSION_TIMEOUT_SECONDS) {
        $_SESSION = [];
        session_regenerate_id(true);
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function require_login(): array {
    if (!check_session_timeout()) {
        header('Location: login.php?timeout=1');
        exit;
    }
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, full_name, email, relationship_label, role, agreed_share_pct, is_active, last_login_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !(int)$user['is_active']) {
        $_SESSION = [];
        header('Location: login.php?inactive=1');
        exit;
    }
    return $user;
}

function require_role(array $allowedRoles, array $user): void {
    if (!in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        include __DIR__ . '/header.php';
        echo '<div class="card alert-card danger"><h2>Access Denied (403)</h2><p>Your current role (<strong>' . e($user['role']) . '</strong>) does not have permission to perform this action.</p></div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

function log_audit(int $userId, string $action, string $module, string $recordRef, string $details = ''): void {
    $pdo = get_db();
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action_type, module_name, record_ref, details, ip_address) VALUES (:uid, :act, :mod, :ref, :det, :ip)');
    $stmt->execute([
        ':uid' => $userId,
        ':act' => $action,
        ':mod' => $module,
        ':ref' => $recordRef,
        ':det' => $details,
        ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    ]);
}

function get_patient(): array {
    $pdo = get_db();
    $stmt = $pdo->query('SELECT * FROM patients ORDER BY id ASC LIMIT 1');
    $p = $stmt->fetch();
    return $p ?: [
        'id' => 1,
        'full_name' => 'Remedios L. Santos',
        'birthdate' => '1945-04-12',
        'blood_type' => 'O+',
        'allergies' => 'Penicillin, Sulfa drugs, Shellfish',
        'conditions' => 'Type 2 Diabetes Mellitus, Hypertension, Dyslipidemia',
        'primary_doctor' => 'Dr. Ramon Cruz, MD (Internal Medicine / Geriatrics)',
        'primary_clinic' => 'Philippine General Hospital / St. Jude Medical Clinic, Manila',
        'address' => 'Pandacan, Manila',
        'care_notes_summary' => 'Requires morning and evening blood pressure/glucose checks and pillbox verification.'
    ];
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array {
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}
