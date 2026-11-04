<?php
declare(strict_types=1);
// Entrega de lo comprado (enlace privado con vencimiento y límite). Si el producto tiene varios archivos, muestra la lista.
require __DIR__ . '/lib/bootstrap.php';
function stop(string $msg, int $code = 404): void {
    http_response_code($code);
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Descarga</title><body style="font:18px system-ui;max-width:560px;margin:15vh auto;padding:0 22px;color:#1c1a17"><h1 style="font-size:28px">' . h($msg) . '</h1><p><a href="' . h(base_path()) . '/">Volver al catálogo</a></p>';
    exit;
}
if (!db_ready()) stop('Servicio no disponible.', 503);
$t = (string)($_GET['t'] ?? '');
if (!preg_match('/^[a-f0-9]{48}$/', $t)) stop('Enlace no válido.');
$st = db()->prepare("SELECT o.*, p.slug, p.file_name, p.file_orig, p.files, p.ext_url FROM orders o JOIN products p ON p.id=o.product_id WHERE o.token=? AND o.status='paid'");
$st->execute([$t]);
$o = $st->fetch();
if (!$o) stop('Enlace no válido.');
if ($o['expires_at'] && strtotime((string)$o['expires_at']) < time()) stop('Este enlace venció. Escribinos por WhatsApp y te lo renovamos.', 410);

$files = ($d = json_decode((string)$o['files'], true)) && is_array($d) ? $d : [];
if (!$files && $o['file_name'] !== '') $files = [['stored' => $o['file_name'], 'orig' => $o['file_orig'], 'size' => 0]];
$items = [];   // lo que se puede descargar: archivos + link externo
foreach ($files as $i => $fl) $items[] = ['k' => 'f' . $i, 'orig' => $fl['orig'] ?: 'descarga', 'stored' => $fl['stored'], 'size' => (int)($fl['size'] ?? 0)];
if ($o['ext_url'] !== '' && safe_url($o['ext_url'])) $items[] = ['k' => 'u', 'orig' => 'Enlace de descarga', 'stored' => '', 'size' => 0, 'url' => $o['ext_url']];
if (!$items) stop('El archivo no está disponible. Escribinos y lo resolvemos.', 500);

$limit = max(1, (int)cfg('download.max', 5)) * count($items);
$pick = (string)($_GET['f'] ?? '');
if ($pick === '' && count($items) === 1) $pick = $items[0]['k'];

if ($pick === '') {   // varios archivos: lista de descarga
    $v = (string)@filemtime(__DIR__ . '/assets/js/site.js'); $B = base_path(); ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Tus descargas · EstrategIA Descargables</title>
<link rel="stylesheet" href="<?= h($B) ?>/assets/css/site.css?v=<?= $v ?>"><link rel="stylesheet" href="<?= h($B) ?>/assets/css/detalle.css?v=<?= $v ?>"></head>
<body class="solo"><header class="pad solid"><a class="logo" href="<?= h($B) ?>/"><i>e</i>EstrategIA<small>Descargables</small></a></header>
<main class="thanks"><div class="card"><div class="k" style="color:var(--acc)">Tus archivos</div><h1>Descargá <span class="serif">cada uno.</span></h1>
<p><b><?= h($o['product_title']) ?></b></p>
<?php foreach ($items as $it): ?><p class="row" style="margin-top:10px"><a class="btn" href="<?= h($B) ?>/descargar.php?t=<?= h($t) ?>&f=<?= h($it['k']) ?>">Descargar · <?= h($it['k'] === 'u' ? $it['orig'] : file_kind($it['orig'])) ?></a></p>
<p style="margin:2px 0 0"><small><?= h($it['orig']) ?><?= $it['size'] ? ' · ' . h(human_size($it['size'])) : '' ?></small></p><?php endforeach; ?>
<small>Son archivos distintos: descargá cada uno una vez. El enlace dura hasta <?= h(date('d/m/Y', strtotime((string)$o['expires_at']))) ?>.</small>
</div></main></body></html><?php exit; }

$sel = null; foreach ($items as $it) if ($it['k'] === $pick) $sel = $it;
if (!$sel) stop('Enlace no válido.');
if ((int)$o['downloads'] >= $limit) stop('Llegaste al máximo de descargas. Escribinos y te ayudamos.', 410);
db()->prepare('UPDATE orders SET downloads=downloads+1 WHERE id=?')->execute([$o['id']]);
if ($sel['k'] === 'u') { header('Location: ' . $sel['url']); exit; }
$path = PRIVATE_DIR . '/' . basename($sel['stored']);
if (!is_file($path)) stop('El archivo no está disponible. Escribinos y lo resolvemos.', 500);
$orig = $sel['orig'];
$ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
$mime = ['pdf'=>'application/pdf','zip'=>'application/zip','html'=>'text/html; charset=utf-8','epub'=>'application/epub+zip','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'][$ext] ?? 'application/octet-stream';
while (ob_get_level()) ob_end_clean();
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $orig) . '"');
header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
set_time_limit(0); readfile($path); exit;
