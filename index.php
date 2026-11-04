<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
$products = array_values(array_filter(load_products(), fn($p) => !empty($p['visible']) && !empty($p['images'])));
$themes = themes();
$totalPages = array_sum(array_map(fn($p) => (int)($p['pages'] ?? 0), $products));
$heroImgs = [];
foreach ($products as $p) foreach ($p['images'] as $im) $heroImgs[] = 'uploads/' . rawurlencode($im);
$v = '1';
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>EstrategIA · Descargables — agendas, planners e imprimibles</title>
<meta name="description" content="Agendas imprimibles, planners digitales para tablet y plantillas descargables. Miralas por dentro y elegí la tuya.">
<meta property="og:title" content="EstrategIA · Descargables">
<meta property="og:description" content="Agendas imprimibles y planners digitales listos para descargar.">
<?php if ($heroImgs): ?><meta property="og:image" content="<?= h($heroImgs[0]) ?>"><?php endif; ?>
<link rel="preload" href="assets/fonts/bricolage-grotesque-latin-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/site.css?v=<?= $v ?>">
</head>
<body>
<header class="pad" id="hdr"><a class="logo" href="#top"><i>e</i>EstrategIA<small>Descargables</small></a>
<nav class="nav"><a class="t" href="#catalogo">Catálogo</a><a class="t" href="#como">Cómo funciona</a><a class="btn" href="#catalogo">Ver agendas</a></nav></header>

<main id="top">
<section id="desk" aria-label="Presentación">
  <div class="dstage" id="dstage" data-sheets="<?= h(json_encode($heroImgs, JSON_UNESCAPED_SLASHES)) ?>">
    <div class="htxt" id="htxt"><div class="k" style="color:var(--acc)">Agendas · planners · imprimibles</div>
      <h1>Papel bonito para <span class="serif">organizar tu vida.</span></h1>
      <p>Descargables listos para imprimir en casa o usar en tu tablet. Elegí el que va con vos, miralo por dentro y llevátelo.</p>
      <div class="row"><a class="btn" href="#catalogo">Ver el catálogo</a><a class="btn o" href="#como">Cómo funciona</a></div></div>
    <?php if ($totalPages > 0): ?><div class="count" id="cnt" data-total="<?= $totalPages ?>"><b><span id="pn" style="font:inherit;color:inherit">0</span>+</b><span>páginas para llenar</span></div><?php endif; ?>
    <div class="stamp" id="stamp"><i></i>Bajá para ordenar la mesa</div>
  </div>
</section>

<div id="catalogo">
<div class="bar"><div class="chips" role="tablist" aria-label="Filtrar">
  <button class="on" data-f="all">Todos</button><button data-f="print">Imprimibles</button><button data-f="digital">Para tablet</button></div></div>
<div id="prods">
<?php if (!$products): ?><p class="empty">Pronto vas a encontrar acá nuestros descargables.</p><?php endif; ?>
<?php foreach ($products as $p):
  $t = $themes[$p['theme'] ?? ''] ?? reset($themes);
  $style = "--bg:{$t['bg']};--bgsolid:{$t['bg']};--glow:{$t['glow']};--fg:{$t['fg']};--fg2:{$t['fg2']};--tag:{$t['tag']};--chip:{$t['chip']}";
  $ar = preg_match('~^\d+/\d+$~', (string)($p['ar'] ?? '')) ? $p['ar'] : '3/4';
  [$aw, $ah] = array_map('intval', explode('/', $ar));
  $wide = $ah > 0 && $aw / $ah > 0.74;
  $ext = safe_url((string)($p['landing'] ?? ''));
  $detail = $ext ?: product_url((string)$p['id']);
  $plain = strip_tags(str_replace('*', '', (string)$p['title']));
  $imgs = array_map(fn($im) => 'uploads/' . rawurlencode($im), $p['images']);
?>
<section class="prod" data-type="<?= h($p['type'] ?? 'print') ?>" id="p-<?= h($p['id']) ?>" aria-label="<?= h($plain) ?>" data-title="<?= h($plain) ?>" data-imgs="<?= h(json_encode($imgs, JSON_UNESCAPED_SLASHES)) ?>">
 <div class="pst" style="<?= h($style) ?>"><div class="pbg"></div>
  <div class="info"><div class="k"><?= h($p['kicker'] ?? '') ?></div><h2><?= title_html((string)$p['title']) ?></h2><p><?= h($p['desc'] ?? '') ?></p>
   <?php if ($p['price'] > 0): ?><div class="pprice"><?= h(format_price($p['price'])) ?></div><?php endif; ?>
   <?php if (!empty($p['meta'])): ?><div class="meta"><?php foreach ($p['meta'] as $m): ?><span><?= h($m) ?></span><?php endforeach; ?></div><?php endif; ?>
   <?php if (!empty($p['feats'])): ?><ul class="feats"><?php foreach ($p['feats'] as $f): ?><li><?= h($f) ?></li><?php endforeach; ?></ul><?php endif; ?>
   <div class="row">
    <a class="btn" href="<?= h($detail) ?>"<?= $ext ? ' target="_blank" rel="noopener"' : '' ?>>Ver detalle <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    <button class="btn o" data-zoom="<?= h($p['id']) ?>">Ver páginas</button></div></div>
  <div class="fan">
   <?php foreach ($imgs as $i => $src): ?><div class="pg<?= $wide ? ' land' : '' ?>" style="--ar:<?= h($ar) ?>;background-image:url('<?= h($src) ?>')" data-i="<?= $i ?>" data-p="<?= h($p['id']) ?>" role="button" tabindex="0" aria-label="Ampliar página <?= $i + 1 ?>"></div><?php endforeach; ?>
   <div class="dots"><?php foreach ($imgs as $_): ?><i></i><?php endforeach; ?></div></div>
 </div>
</section>
<?php endforeach; ?>
</div>
</div>

<section class="how top pad" id="como" aria-label="Cómo funciona">
  <div class="wrap"><div class="k" style="color:var(--acc)">Cómo funciona</div><h2>De la pantalla <span class="serif">a tu escritorio.</span></h2>
  <div class="steps">
    <div class="st"><b>01</b><h3>Elegí</h3><p>Mirá las páginas por dentro y entrá a la agenda que más te guste para ver todos los detalles.</p></div>
    <div class="st"><b>02</b><h3>Descargá</h3><p>Al comprar recibís el archivo al instante: PDF o HTML, según el producto.</p></div>
    <div class="st"><b>03</b><h3>Usalo a tu manera</h3><p>Imprimí solo las páginas que necesites o usalo en tu tablet con una app de notas.</p></div>
  </div></div>
</section>

<section class="end top pad" aria-label="Contacto">
  <div class="wrap"><div class="k" style="color:var(--acc)">¿Buscás algo especial?</div><h2>Nuevas agendas <span class="serif">todo el tiempo.</span></h2>
    <p>Seguinos para enterarte de los próximos lanzamientos o escribinos si tenés alguna duda antes de comprar.</p>
    <div class="row"><a class="btn" href="https://www.instagram.com/estrategia.datos/" target="_blank" rel="noopener">Instagram @estrategia.datos</a><a class="btn o" href="https://wa.me/5491126425994" target="_blank" rel="noopener">Escribinos por WhatsApp</a></div>
    <footer><span>© <?= date('Y') ?> EstrategIA · Descargables</span><span>Productos digitales: no se envía nada físico.</span></footer></div>
</section>
</main>

<div class="lb" id="lb" role="dialog" aria-modal="true" aria-label="Vista ampliada"><img id="lbi" alt=""><button class="x" id="lbx" aria-label="Cerrar">✕</button><button class="pv" id="lbp" aria-label="Anterior">‹</button><button class="nx" id="lbn" aria-label="Siguiente">›</button><div class="cap" id="lbc"></div></div>
<script src="assets/js/site.js?v=<?= $v ?>" defer></script>
</body>
</html>
