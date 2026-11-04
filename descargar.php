<?php
declare(strict_types=1);
// Entrega del archivo comprado (enlace privado con vencimiento y límite)
require __DIR__ . '/lib/bootstrap.php';
function stop(string $msg, int $code = 404): void {
    http_response_code($code);
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Descarga</title><body style="font:18px system-ui;max-width:560px;margin:15vh auto;padding:0 22px;color:#1c1a17"><h1 style="font-size:28px">' . h($msg) . '</h1><p><a href="' . h(base_path()) . '/">Volver al catálogo</a></p>';
    exit;
}
if (!db_ready()) stop('Servicio no disponible.', 503);
$t = (string)($_GET['t'] ?? '');
if (!preg_match('/^[a-f0-9]{48}$/', $t)) stop('Enlace no válido.');
$st = db()->prepare("SELECT o.*, p.file_name, p.file_orig, p.ext_url FROM orders o JOIN products p ON p.id=o.product_id WHERE o.token=? AND o.status='paid'");
$st->execute([$t]);
$o = $st->fetch();
if (!$o) stop('Enlace no válido.');
if ($o['expires_at'] && strtotime((string)$o['expires_at']) < time()) stop('Este enlace venció. Escribinos por WhatsApp y te lo renovamos.', 410);
if ((int)$o['downloads'] >= (int)cfg('download.max', 5)) stop('Llegaste al máximo de descargas. Escribinos y te ayudamos.', 410);
db()->prepare('UPDATE orders SET downloads=downloads+1 WHERE id=?')->execute([$o['id']]);
if ($o['file_name'] !== '') {
    $path = PRIVATE_DIR . '/' . basename($o['file_name']);
    if (!is_file($path)) stop('El archivo no está disponible. Escribinos y lo resolvemos.', 500);
    $orig = $o['file_orig'] ?: 'descarga';
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $mime = ['pdf'=>'application/pdf','zip'=>'application/zip','html'=>'text/html; charset=utf-8','epub'=>'application/epub+zip','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'][$ext] ?? 'application/octet-stream';
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $orig) . '"');
    header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
    set_time_limit(0); readfile($path); exit;
}
if ($o['ext_url'] !== '' && safe_url($o['ext_url'])) { header('Location: ' . $o['ext_url']); exit; }
stop('El archivo no está disponible. Escribinos y lo resolvemos.', 500);
