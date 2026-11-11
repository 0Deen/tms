<?php
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) { session_start(); }

function login(PDO $pdo, string $username, string $password): bool {
    $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([trim($username)]);
    $u = $st->fetch();
    if (!$u || (isset($u['status']) && $u['status'] !== 'Active') || !password_verify($password, $u['password'])) { return false; }
    if (session_status() === PHP_SESSION_ACTIVE) { session_regenerate_id(true); }
    $_SESSION['user'] = ['id' => $u['user_id'], 'name' => $u['full_name'], 'role' => $u['role']];
    $_SESSION['last'] = time();
    if (function_exists('audit_log')) audit_log($pdo, 'auth.login', 'user', (int)$u['user_id'], 'Successful sign-in.');
    return true;
}
function logout(): void {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) { session_destroy(); }
}
function current_user(): ?array {
    if (isset($_SESSION['user'])) {
        if (time() - ($_SESSION['last'] ?? 0) > SESSION_TIMEOUT) { logout(); return null; }
        $_SESSION['last'] = time();
        return $_SESSION['user'];
    }
    return null;
}
function require_login(): void {
    if (!current_user()) { header('Location: login.php'); exit; }
}
function has_role(array $roles): bool { $u = current_user(); return $u && in_array($u['role'], $roles, true); }
function require_role(array $roles): void {
    require_login();
    if (!has_role($roles)) { http_response_code(403); exit('403 - You do not have permission to open this page.'); }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid security token.'); }
}
