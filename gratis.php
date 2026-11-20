<?php
declare(strict_types=1);
// Recibe código + email de la pestaña GRATIS → entrega el enlace de descarga (una vez por email)
require __DIR__ . '/lib/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . base_path() . '/#excels'); exit; }
if (!empty($_POST['web'])) exit; // trampa para bots

$B = base_path();
$v = (string)@filemtime(__DIR__ . '/assets/js/site.js');
$page = function (string $kicker, string $title, string $body) use ($B, $v): void { ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Descarga gratis · EstrategIA Descargables</title>
<link rel="stylesheet" href="<?= h($B) ?>/assets/css/site.css?v=<?= $v ?>"><link rel="stylesheet" href="<?= h($B) ?>/assets/css/detalle.css?v=<?= $v ?>"></head>
<body class="solo"><header class="pad solid"><a class="logo" href="<?= h($B) ?>/"><i>e</i>EstrategIA<small>Descargables</small></a></header>
<main class="thanks"><div class="card"><div class="k" style="color:var(--acc)"><?= h($kicker) ?></div><h1><?= $title ?></h1><?= $body ?></div></main></body></html><?php exit; };

$back = '<p class="row"><a class="btn o" href="' . h($B) . '/#excels">Volver a Excels</a></p>';

if (!gratis_ready()) $page('Gratis', 'Por ahora <span class="serif">no está disponible.</span>', '<p>Probá de nuevo más tarde.</p>' . $back);

$wait = gratis_blocked();
if ($wait > 0) $page('Gratis', 'Demasiados <span class="serif">intentos.</span>', '<p>Esperá unos minutos y volvé a probar.</p>' . $back);

$codigo = trim((string)($_POST['codigo'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
gratis_attempt();

if (!preg_match('/^[A-Za-z0-9_-]{4,40}$/', $codigo)) $page('Gratis', 'Revisá el <span class="serif">código.</span>', '<p>El código tiene letras, números, guion o guion bajo.</p>' . $back);
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $page('Gratis', 'Revisá el <span class="serif">email.</span>', '<p>Escribí un email válido: ahí te mandamos el enlace.</p>' . $back);

$r = gratis_redeem($codigo, $email, (string)($_SERVER['REMOTE_ADDR'] ?? ''));
if (!$r['ok']) $page('Gratis', 'No pudimos <span class="serif">entregarlo.</span>', '<p>' . h($r['msg']) . '</p>' . $back);

$o = $r['order'];
$body = '<p><b>' . h($o['product_title']) . '</b></p>'
    . '<p class="row"><a class="btn" href="' . h($B) . '/descargar.php?t=' . h($o['token']) . '">Descargar ahora</a></p>'
    . '<p>También te mandamos el enlace a <b>' . h($o['buyer_email']) . '</b> (revisá spam si no llega).</p>'
    . '<small>El enlace dura ' . (int)cfg('download.days', 7) . ' días. Cada email puede descargar este producto una sola vez: guardá el archivo.</small>';
$page('¡Listo!', 'Es <span class="serif">tuyo.</span>', $body);
