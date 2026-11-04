<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
$slug = (string)($_GET['s'] ?? '');
$p = find_product(load_products(), $slug);
if (!$p || !$p['visible'] || !$p['images']) { http_response_code(404); $nf = true; }
if ($p && !empty($p['landing']) && safe_url((string)$p['landing'])) { header('Location: ' . $p['landing'], true, 302); exit; }
$v = '1'; $B = base_path();
if (!empty($nf)) { ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>No encontrado · EstrategIA Descargables</title>
<link rel="stylesheet" href="<?= h($B) ?>/assets/css/site.css?v=<?= $v ?>"><link rel="stylesheet" href="<?= h($B) ?>/assets/css/detalle.css?v=<?= $v ?>"></head><body class="solo">
<header class="pad solid"><a class="logo" href="<?= h($B) ?>/"><i>e</i>EstrategIA<small>Descargables</small></a></header>
<main class="thanks"><div class="card"><div class="k" style="color:var(--acc)">Error 404</div><h1>No encontramos <span class="serif">ese producto.</span></h1><p class="row"><a class="btn" href="<?= h($B) ?>/">Ver el catálogo</a></p></div></main></body></html><?php exit; }
$plain = strip_tags(str_replace('*', '', (string)$p['title']));
$imgs = array_map(fn($im) => $B . '/uploads/' . rawurlencode($im), $p['images']);
$sale = product_for_sale($p);
$err = isset($_GET['err']) ? mb_substr((string)$_GET['err'], 0, 160) : '';
$paras = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', (string)$p['long']) ?: [])));
$ar = preg_match('~^\d+/\d+$~', (string)$p['ar']) ? $p['ar'] : '3/4';
$typeLbl = types()[$p['type']] ?? '';
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= h($plain) ?> · EstrategIA Descargables</title>
<meta name="description" content="<?= h(mb_substr((string)$p['desc'], 0, 160)) ?>">
<meta property="og:title" content="<?= h($plain) ?>"><meta property="og:description" content="<?= h(mb_substr((string)$p['desc'], 0, 200)) ?>">
<meta property="og:image" content="<?= h(site_url('uploads/' . rawurlencode($p['images'][0]))) ?>">
<link rel="preload" href="<?= h($B) ?>/assets/fonts/bricolage-grotesque-latin-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= h($B) ?>/assets/css/site.css?v=<?= $v ?>"><link rel="stylesheet" href="<?= h($B) ?>/assets/css/detalle.css?v=<?= $v ?>">
</head>
<body class="det" style="<?= h(theme_style((string)$p['theme'])) ?>">
<header class="pad solid" id="hdr"><a class="logo" href="<?= h($B) ?>/"><i>e</i>EstrategIA<small>Descargables</small></a>
<nav class="nav"><a class="t" href="<?= h($B) ?>/#catalogo">← Catálogo</a><a class="btn" href="#comprar">Comprar</a></nav></header>

<main class="dwrap">
  <section class="gal" aria-label="Fotos del producto">
    <button class="main" id="gmain" style="--ar:<?= h($ar) ?>" aria-label="Ampliar foto"><img id="gimg" src="<?= h($imgs[0]) ?>" alt="<?= h($plain) ?> — página 1"></button>
    <?php if (count($imgs) > 1): ?><div class="thumbs"><?php foreach ($imgs as $i => $src): ?><button type="button" class="th<?= $i === 0 ? ' on' : '' ?>" data-i="<?= $i ?>" aria-label="Ver foto <?= $i + 1 ?>"><img src="<?= h($src) ?>" alt="" loading="lazy"></button><?php endforeach; ?></div><?php endif; ?>
  </section>

  <section class="dinfo">
    <div class="k" style="color:var(--tag)"><?= h($p['kicker'] ?: $typeLbl) ?></div>
    <h1><?= title_html((string)$p['title']) ?></h1>
    <?php if ($p['price'] > 0): ?><div class="price"><?= h(format_price($p['price'])) ?><small>ARS · pago único</small></div><?php endif; ?>
    <?php if ($p['meta']): ?><div class="meta"><?php foreach ($p['meta'] as $m): ?><span><?= h($m) ?></span><?php endforeach; ?></div><?php endif; ?>
    <p class="lead"><?= h($p['desc']) ?></p>
    <?php foreach ($paras as $para): ?><p class="body"><?= nl2br(h($para)) ?></p><?php endforeach; ?>
    <?php if ($p['feats']): ?><h2>Qué incluye</h2><ul class="feats"><?php foreach ($p['feats'] as $f): ?><li><?= h($f) ?></li><?php endforeach; ?></ul><?php endif; ?>

    <div class="buy" id="comprar">
    <?php if ($sale): ?>
      <?php if ($err): ?><div class="alert"><?= h($err) ?></div><?php endif; ?>
      <form method="post" action="<?= h($B) ?>/comprar.php">
        <input type="hidden" name="slug" value="<?= h($p['id']) ?>">
        <input type="text" name="web" value="" tabindex="-1" autocomplete="off" class="hp" aria-hidden="true">
        <label>Tu email<input type="email" name="email" required maxlength="190" placeholder="nombre@correo.com" autocomplete="email"><small>Ahí te mandamos el enlace de descarga.</small></label>
        <button class="btn big" type="submit">Comprar · <?= h(format_price($p['price'])) ?></button>
      </form>
      <ul class="trust"><li>Pago seguro con Mercado Pago</li><li>Descarga al instante</li><li>Producto digital: no se envía nada físico</li></ul>
    <?php else: ?>
      <button class="btn big" type="button" disabled aria-disabled="true"><?= $p['price'] > 0 ? 'Comprar · ' . h(format_price($p['price'])) : 'Comprar' ?></button>
      <p class="soon">No disponible por ahora. Si lo querés ya, escribinos y te lo pasamos.</p>
      <p class="row"><a class="btn o" href="https://wa.me/5491126425994" target="_blank" rel="noopener">Escribir por WhatsApp</a></p>
    <?php endif; ?>
    </div>
  </section>
</main>

<footer class="pad dfoot"><span>© <?= date('Y') ?> EstrategIA · Descargables</span><a href="<?= h($B) ?>/#catalogo">Ver más productos</a></footer>

<div class="lb" id="lb" role="dialog" aria-modal="true" aria-label="Vista ampliada"><img id="lbi" alt=""><button class="x" id="lbx" aria-label="Cerrar">✕</button><button class="pv" id="lbp" aria-label="Anterior">‹</button><button class="nx" id="lbn" aria-label="Siguiente">›</button><div class="cap" id="lbc"></div></div>
<script>window.__IMGS=<?= json_encode($imgs, JSON_UNESCAPED_SLASHES) ?>;window.__TITLE=<?= json_encode($plain, JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= h($B) ?>/assets/js/detalle.js?v=<?= $v ?>" defer></script>
</body></html>
