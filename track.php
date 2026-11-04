<?php
declare(strict_types=1);
// Recibe eventos anónimos de la página (sin IP, sin datos personales). Ignora al dueño y a los robots.
require __DIR__ . '/lib/bootstrap.php';
http_response_code(204);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || is_bot_ua((string)($_SERVER['HTTP_USER_AGENT'] ?? ''))) exit;
$raw = (string)file_get_contents('php://input', false, null, 0, 2048);
$j = json_decode($raw, true);
if (!is_array($j) || !extras_ready()) exit;
if (is_owner_request((string)($j['v'] ?? ''))) exit; // el dueño no se cuenta
$t = (string)($j['t'] ?? '');
if (!in_array($t, ['pv', 'tab', 'seen', 'buy', 'click', 'demo', 'wa', 'photos'], true)) exit;
$clean = fn($v, int $n) => mb_substr(trim(preg_replace('/[\x00-\x1F]+/u', ' ', (string)$v) ?? ''), 0, $n);
$id = fn($v) => preg_match('/^[a-z0-9]{6,16}$/', (string)$v) ? (string)$v : '';
$page = in_array($j['p'] ?? '', ['inicio', 'detalle', 'gracias', 'legales'], true) ? $j['p'] : 'otra';
try {
    db()->prepare('INSERT INTO events (vid, sid, t, n, page, src, dev) VALUES (?,?,?,?,?,?,?)')
        ->execute([$id($j['v'] ?? ''), $id($j['i'] ?? ''), $t, $clean($j['n'] ?? '', 140), $page, $clean(strtolower((string)($j['s'] ?? '')), 60), ($j['d'] ?? '') === 'm' ? 'm' : 'd']);
    if (random_int(1, 500) === 1) db()->exec('DELETE FROM events WHERE ts < (NOW() - INTERVAL 18 MONTH)');
} catch (Throwable $e) { error_log('track: ' . $e->getMessage()); }
