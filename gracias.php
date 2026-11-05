<?php
declare(strict_types=1);
// Vuelta desde Mercado Pago: confirma el pago y muestra la descarga
require __DIR__ . '/lib/bootstrap.php';
$order = (db_ready() && mp_configured()) ? order_by_code((string)($_GET['o'] ?? '')) : null;
if ($order && $order['status'] !== 'paid') {
    try {
        $pid = (string)($_GET['payment_id'] ?? $_GET['collection_id'] ?? '');
        $o2 = $pid !== '' ? mp_sync_payment_id($pid) : null;
        if (!$o2 || $o2['status'] !== 'paid') $o2 = mp_sync_by_reference($order['code']);
        if ($o2) $order = $o2;
    } catch (Throwable $e) { error_log('gracias: ' . $e->getMessage()); }
}
$state = !$order ? 'none' : ($order['status'] === 'paid' ? 'paid' : ($order['status'] === 'pending' ? 'pending' : 'fail'));
$v = (string)@filemtime(__DIR__ . '/assets/js/site.js');
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Tu compra · EstrategIA Descargables</title>
<link rel="stylesheet" href="<?= h(base_path()) ?>/assets/css/site.css?v=<?= $v ?>"><link rel="stylesheet" href="<?= h(base_path()) ?>/assets/css/detalle.css?v=<?= $v ?>"><?php require __DIR__ . "/lib/tiktok.php"; ?></head>
<body class="solo">
<header class="pad solid" id="hdr"><a class="logo" href="<?= h(base_path()) ?>/"><i>e</i>EstrategIA<small>Descargables</small></a></header>
<main class="thanks"><div class="card">
<?php if ($state === 'paid'): ?>
  <div class="k" style="color:var(--acc)">Pago aprobado</div><h1>¡Listo! <span class="serif">Es tuyo.</span></h1>
  <p><b><?= h($order['product_title']) ?></b></p>
  <p>Descargalo con este botón. También te mandamos el enlace a <b><?= h($order['buyer_email']) ?></b> (revisá la carpeta de spam si no llega).</p>
  <p class="row"><a class="btn" href="<?= h(base_path()) ?>/descargar.php?t=<?= h($order['token']) ?>">Descargar ahora</a></p>
  <small>El enlace dura <?= (int)cfg('download.days', 7) ?> días y permite hasta <?= (int)cfg('download.max', 5) ?> descargas. Guardá el archivo en un lugar seguro.</small>
<?php elseif ($state === 'pending'): ?>
  <div class="k" style="color:var(--acc)">Pago en proceso</div><h1>Estamos <span class="serif">esperando</span> la confirmación.</h1>
  <p>Mercado Pago todavía no confirmó el pago (con algunos medios tarda unos minutos). Apenas se apruebe te mandamos el enlace a <b><?= h($order['buyer_email']) ?></b>.</p>
  <p class="row"><a class="btn" href="">Actualizar</a><a class="btn o" href="<?= h(base_path()) ?>/">Volver al catálogo</a></p>
<?php elseif ($state === 'fail'): ?>
  <div class="k" style="color:var(--acc)">No se completó</div><h1>El pago <span class="serif">no se aprobó.</span></h1>
  <p>No se te cobró nada. Podés intentar de nuevo con otro medio de pago.</p>
  <p class="row"><a class="btn" href="<?= h(base_path()) ?>/">Volver al catálogo</a></p>
<?php else: ?>
  <div class="k" style="color:var(--acc)">Compra</div><h1>No encontramos <span class="serif">ese pedido.</span></h1>
  <p class="row"><a class="btn" href="<?= h(base_path()) ?>/">Volver al catálogo</a></p>
<?php endif; ?>
</div></main>
<?php include __DIR__ . '/lib/footer.php'; ?>
</body></html>
