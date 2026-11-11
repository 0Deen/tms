<?php
// Pure helper / validation functions (no database access) - covered by unit tests.
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function normalize_reg_number(string $r): string {
    $c = strtoupper(preg_replace('/\s+/', '', $r));
    return strlen($c) > 3 ? substr($c, 0, 3) . ' ' . substr($c, 3) : $c;
}
function validate_reg_number(string $r): bool {
    // Kenyan plate format, e.g. KDA 123A
    return (bool)preg_match('/^K[A-Z]{2} \d{3}[A-Z]$/', normalize_reg_number($r));
}
function validate_phone(string $p): bool {
    // 07XXXXXXXX, 01XXXXXXXX or +2547XXXXXXXX
    return (bool)preg_match('/^(\+254|0)[17]\d{8}$/', preg_replace('/\s+/', '', $p));
}
function validate_national_id(string $id): bool { return (bool)preg_match('/^\d{7,8}$/', trim($id)); }
function validate_seats($n): bool {
    return filter_var($n, FILTER_VALIDATE_INT) !== false && (int)$n >= 1 && (int)$n <= 70;
}
function validate_email_optional(string $e): bool { return $e === '' || filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
function validate_time_order(string $dep, string $arr): bool { return $dep !== '' && $arr !== '' && strcmp($arr, $dep) > 0; }
function calc_revenue(int $passengers, float $fare): float { return round($passengers * $fare, 2); }
function passengers_within_capacity(int $count, int $capacity): bool { return $count >= 0 && $count <= $capacity; }
function flash(?string $msg = null) {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return null; }
    $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m;
}

function audit_log(PDO $pdo, string $action, string $entity = '', ?int $entityId = null, string $description = ''): void {
    try {
        $u = current_user();
        $pdo->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,description,ip_address) VALUES (?,?,?,?,?,?)')
            ->execute([$u['id'] ?? null, $action, $entity ?: null, $entityId, substr($description,0,500), $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) { error_log('Audit log write failed: '.$e->getMessage()); }
}
function money_kes($amount): string { return 'KSh '.number_format((float)$amount,2); }
