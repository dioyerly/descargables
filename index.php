<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
$products = array_values(array_filter(load_products(), fn($p) => !empty($p['visible']) && !empty($p['images'])));
$themes = themes();
$totalPages = array_sum(array_map(fn($p) => (int)($p['pages'] ?? 0), $products));
$heroImgs = [];
foreach ($products as $p) foreach ($p['images'] as $im) $heroImgs[] = 'uploads/' . rawurlencode($im);
$v = (string)@filemtime(__DIR__ . '/assets/js/site.js');
$apps = showcase_load('apps', true); $landings = showcase_load('landings', true); $micro = showcase_load('micro', true);
$wa = 'https://wa.me/5491126425994';
$waMsg = fn(string $t) => $wa . '?text=' . rawurlencode($t);
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>EstrategIA · Productos digitales — agendas, apps web, landings y micro apps</title>
<meta name="description" content="Productos digitales que organizan, solucionan y venden: agendas e imprimibles, apps web, landings y micro apps a medida.">
<meta property="og:title" content="EstrategIA · Productos digitales">
<meta property="og:description" content="Agendas e imprimibles, apps web, landings y micro apps a medida.">
<?php if ($heroImgs): ?><meta property="og:image" content="<?= h($heroImgs[0]) ?>"><?php endif; ?>
<link rel="preload" href="assets/fonts/bricolage-grotesque-latin-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/site.css?v=<?= $v ?>">
</head>
<body>
<header class="pad" id="hdr"><a class="logo" href="#top"><i>e</i>EstrategIA<small>Productos digitales</small></a>
<nav class="nav"><a class="t" href="#agendas">Agendas</a><a class="t" href="#apps">Apps web</a><a class="t" href="#landings">Landings</a><a class="t" href="#micro">Micro apps</a><a class="btn" href="<?= h($waMsg('Hola! Quiero consultar por un producto a medida.')) ?>" target="_blank" rel="noopener">Pedí el tuyo</a></nav></header>

<main id="top">
<section id="desk" aria-label="Presentación">
  <div class="dstage" id="dstage" data-cover="assets/img/portada.webp" data-sheets="<?= h(json_encode($heroImgs, JSON_UNESCAPED_SLASHES)) ?>">
    <div class="htxt" id="htxt"><div class="k" style="color:var(--acc)">EstrategIA · Productos digitales</div>
      <h1>Productos digitales que <span class="rot serif" id="rot" aria-live="polite"><b class="on">organizan.</b><b>solucionan.</b><b>venden.</b></span></h1>
      <p>Agendas e imprimibles, apps web, landings y micro apps a medida. Todo listo para usar, mostrar y vender.</p>
      <div class="row"><a class="btn" href="#agendas">Ver los productos</a><a class="btn o" href="<?= h($waMsg('Hola! Quiero pedir algo a medida.')) ?>" target="_blank" rel="noopener">Pedí lo tuyo</a></div></div>
    <div class="cats" id="cats">
      <a href="#agendas"><b>01</b><span>Agendas e imprimibles</span><small><?= count($products) ?> productos</small></a>
      <a href="#apps"><b>02</b><span>Apps web</span><small><?= count($apps) ?: 'Muy pronto' ?></small></a>
      <a href="#landings"><b>03</b><span>Landings a medida</span><small><?= count($landings) ?: 'Muy pronto' ?></small></a>
      <a href="#micro"><b>04</b><span>Micro apps a medida</span><small><?= count($micro) ?: 'Muy pronto' ?></small></a>
    </div>
    <div class="stamp" id="stamp"><i></i>Bajá y mirá todo</div>
  </div>
</section>

<nav class="tabs" id="tabs" aria-label="Categorías"><a href="#agendas" class="on">Agendas e imprimibles</a><a href="#apps">Apps web</a><a href="#landings">Landings a medida</a><a href="#micro">Micro apps a medida</a></nav>

<section id="agendas" aria-label="Agendas e imprimibles">
<div class="sechead pad"><div class="wrap"><div class="k" style="color:var(--acc)">01 · Agendas e imprimibles</div><h2>Papel bonito, <span class="serif">listo para usar.</span></h2><p>Agendas y planners para imprimir en casa o usar en tu tablet. Mirá las páginas por dentro y elegí la tuya.</p></div></div>
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
</section>

<section id="apps" class="msec apps pad" aria-label="Apps web"><div class="wrap">
  <div class="k">02 · Apps web</div><h2>Apps que <span class="serif">resuelven</span> tu día a día.</h2>
  <p class="lead">Sistemas web hechos para que un negocio se ordene: gestión, stock, turnos, cuentas. Se usan desde cualquier dispositivo.</p>
  <?php if (!$apps): ?><div class="soon"><b>Muy pronto</b><span>Acá vas a ver las apps web, con fotos y el link para probarlas.</span></div><?php endif; ?>
  <div class="agrid">
  <?php foreach ($apps as $a): $im = array_map(fn($x) => 'uploads/' . rawurlencode($x), $a['images']); ?>
    <article class="acard" data-imgs="<?= h(json_encode($im, JSON_UNESCAPED_SLASHES)) ?>" data-title="<?= h($a['title']) ?>">
      <button class="amain" type="button" data-open="0" aria-label="Ampliar foto de <?= h($a['title']) ?>" style="background-image:url('<?= h($im[0] ?? '') ?>')"></button>
      <?php if (count($im) > 1): ?><div class="athumbs"><?php foreach (array_slice($im, 1, 4) as $i => $src): ?><button type="button" data-open="<?= $i + 1 ?>" aria-label="Ampliar foto <?= $i + 2 ?>" style="background-image:url('<?= h($src) ?>')"></button><?php endforeach; ?></div><?php endif; ?>
      <div class="abody"><h3><?= h($a['title']) ?></h3><p><?= h($a['desc']) ?></p>
        <?php if ($a['tags']): ?><div class="atags"><?php foreach ($a['tags'] as $t): ?><span><?= h($t) ?></span><?php endforeach; ?></div><?php endif; ?>
        <?php if ($a['link'] !== ''): ?><a class="btn" href="<?= h($a['link']) ?>" target="_blank" rel="noopener">Ver app <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a><?php else: ?><span class="btn dis">Próximamente</span><?php endif; ?></div>
    </article>
  <?php endforeach; ?>
  </div>
</div></section>

<section id="landings" class="msec lands pad" aria-label="Landings a medida"><div class="wrap">
  <div class="k" style="color:var(--acc)">03 · Landings a medida</div><h2>Landings que <span class="serif">convierten.</span></h2>
  <p class="lead">Páginas de una sola pantalla pensadas para vender o presentar tu negocio. Abajo las ves funcionando, tal cual quedan.</p>
  <?php if (!$landings): ?><div class="soon"><b>Muy pronto</b><span>Acá vas a ver ejemplos de landings funcionando, dentro de un visor.</span></div><?php endif; ?>
  <?php if ($landings): ?>
  <div class="viewer" id="viewer">
    <div class="vtabs" role="tablist"><?php foreach ($landings as $i => $l): $src = $l['html'] !== '' ? 'muestras/' . rawurlencode($l['html']) : ''; ?>
      <button type="button" role="tab" class="<?= $i === 0 ? 'on' : '' ?>" data-src="<?= h($src) ?>" data-img="<?= h(!empty($l['images'][0]) ? 'uploads/' . rawurlencode($l['images'][0]) : '') ?>" data-title="<?= h($l['title']) ?>" data-desc="<?= h($l['desc']) ?>" data-link="<?= h($l['link'] !== '' ? $l['link'] : $waMsg('Hola! Me gustó la landing "' . $l['title'] . '". Quiero una para mi negocio.')) ?>"><?= h($l['title']) ?></button>
    <?php endforeach; ?></div>
    <div class="vtools"><span class="vname" id="vname"></span><div class="vdev"><button type="button" class="on" data-dev="d" aria-label="Ver en computadora">Computadora</button><button type="button" data-dev="m" aria-label="Ver en celular">Celular</button></div><a id="vopen" class="btn o sm" href="#" target="_blank" rel="noopener">Abrir completa ↗</a></div>
    <div class="browser"><div class="bbar"><i></i><i></i><i></i><b id="vurl">tu-negocio.com</b></div>
      <div class="vp" id="vp"><div class="vfr" id="vfr"><div class="vph" id="vph"></div></div></div></div>
    <div class="vfoot"><p id="vdesc"></p><a id="vask" class="btn" href="#" target="_blank" rel="noopener">Quiero una igual</a></div>
  </div>
  <?php endif; ?>
</div></section>

<section id="micro" class="msec micro pad" aria-label="Micro apps a medida"><div class="wrap">
  <div class="k">04 · Micro apps a medida</div><h2>Herramientas chicas, <span class="serif">hechas para vos.</span></h2>
  <p class="lead">Calculadoras, paneles, formularios y utilidades pensadas para una tarea puntual de tu negocio.</p>
  <?php if (!$micro): ?><div class="soon"><b>Muy pronto</b><span>Acá vas a ver capturas de las micro apps que ya hicimos.</span></div><?php endif; ?>
  <?php $mimgs = []; $mcap = []; foreach ($micro as $m) foreach ($m['images'] as $im) { $mimgs[] = 'uploads/' . rawurlencode($im); $mcap[] = $m['title']; } ?>
  <div class="mgrid" id="mgrid" data-imgs="<?= h(json_encode($mimgs, JSON_UNESCAPED_SLASHES)) ?>" data-caps="<?= h(json_encode($mcap, JSON_UNESCAPED_UNICODE)) ?>">
  <?php foreach ($mimgs as $i => $src): ?><button type="button" class="mtile" data-open="<?= $i ?>" aria-label="Ampliar: <?= h($mcap[$i]) ?>"><img src="<?= h($src) ?>" alt="<?= h($mcap[$i]) ?>" loading="lazy"><span><?= h($mcap[$i]) ?></span></button><?php endforeach; ?>
  </div>
</div></section>

<section class="how top pad" id="como" aria-label="Cómo funciona">
  <div class="wrap"><div class="k" style="color:var(--acc)">Cómo funciona</div><h2>De la pantalla <span class="serif">a tu escritorio.</span></h2>
  <div class="steps">
    <div class="st"><b>01</b><h3>Elegí</h3><p>Mirá los productos por dentro, probá las apps y las landings de ejemplo, y quedate con lo que va con vos.</p></div>
    <div class="st"><b>02</b><h3>Descargá o pedí</h3><p>Las agendas las recibís al instante al comprar (PDF o HTML). Lo a medida lo charlamos por WhatsApp.</p></div>
    <div class="st"><b>03</b><h3>Usalo a tu manera</h3><p>Imprimí, escribí en tu tablet o poné a trabajar tu app y tu landing. Es tuyo.</p></div>
  </div></div>
</section>

<section class="end top pad" aria-label="Contacto">
  <div class="wrap"><div class="k" style="color:var(--acc)">¿Necesitás algo a medida?</div><h2>Lo pensamos <span class="serif">juntos.</span></h2>
    <p>Landings, apps web y micro apps hechas para tu negocio. Contanos qué necesitás y te respondemos por WhatsApp.</p>
    <div class="row"><a class="btn" href="https://www.instagram.com/estrategia.datos/" target="_blank" rel="noopener">Instagram @estrategia.datos</a><a class="btn o" href="https://wa.me/5491126425994" target="_blank" rel="noopener">Escribinos por WhatsApp</a></div>
    <footer><span>© <?= date('Y') ?> EstrategIA · Productos digitales</span><span>Productos digitales: no se envía nada físico.</span></footer></div>
</section>
</main>

<div class="lb" id="lb" role="dialog" aria-modal="true" aria-label="Vista ampliada"><img id="lbi" alt=""><button class="x" id="lbx" aria-label="Cerrar">✕</button><button class="pv" id="lbp" aria-label="Anterior">‹</button><button class="nx" id="lbn" aria-label="Siguiente">›</button><div class="cap" id="lbc"></div></div>
<script src="assets/js/site.js?v=<?= $v ?>" defer></script>
</body>
</html>
