<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function start_secure_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('eia_admin');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Strict']);
    session_start();
}
function auth_data(): array {
    if (!is_file(AUTH_FILE)) return [];
    $j = json_decode((string)file_get_contents(AUTH_FILE), true);
    return is_array($j) ? $j : [];
}
function has_password(): bool { return !empty(auth_data()['hash']); }
function set_password(string $pass): bool {
    ensure_dirs();
    $a = auth_data(); $a['hash'] = password_hash($pass, PASSWORD_DEFAULT); $a['changed'] = date('c');
    return file_put_contents(AUTH_FILE, json_encode($a), LOCK_EX) !== false;
}
function check_password(string $pass): bool {
    $hash = auth_data()['hash'] ?? '';
    return $hash !== '' && password_verify($pass, $hash);
}
function is_logged(): bool { start_secure_session(); return !empty($_SESSION['eia_ok']); }
function require_login(): void { if (!is_logged()) { header('Location: login.php'); exit; } }
function csrf_token(): string {
    start_secure_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_check(): void {
    start_secure_session();
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) { http_response_code(400); exit('Sesión vencida. Volvé atrás y recargá la página.'); }
}
/** Bloqueo simple: 5 intentos fallidos → 10 minutos de espera (por IP, guardado en data/) */
function login_blocked(): int {
    $f = DATA_DIR . '/login-attempts.json';
    $all = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'x'; $r = $all[$ip] ?? ['n'=>0,'t'=>0];
    if ($r['n'] >= 5 && time() - $r['t'] < 600) return 600 - (time() - $r['t']);
    return 0;
}
function login_attempt(bool $ok): void {
    ensure_dirs();
    $f = DATA_DIR . '/login-attempts.json';
    $all = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'x';
    if ($ok) unset($all[$ip]);
    else { $r = $all[$ip] ?? ['n'=>0,'t'=>0]; if (time() - $r['t'] > 600) $r['n'] = 0; $r['n']++; $r['t'] = time(); $all[$ip] = $r; }
    file_put_contents($f, json_encode($all), LOCK_EX);
}
