<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
$products = array_values(array_filter(load_products(), fn($p) => !empty($p['visible']) && !empty($p['images'])));
$themes = themes();
$totalPages = array_sum(array_map(fn($p) => (int)($p['pages'] ?? 0), $products));
$heroImgs = [];
foreach ($products as $p) foreach ($p['images'] as $im) $heroImgs[] = 'uploads/' . rawurlencode($im);
$v = (string)@filemtime(__DIR__ . '/assets/js/site.js');
$apps = showcase_load('apps', true); $landings = showcase_load('landings', true); $micro = showcase_load('micro', true); $soon = showcase_load('soon', true);
/* imágenes del inicio: una de cada producto primero (no falta ninguno), después segundas fotos hasta el tope */
$pick = function (array $items, int $cap): array {
    $o = []; $max = 0; foreach ($items as $it) $max = max($max, count($it['images'] ?? []));
    for ($k = 0; $k < min($max, 3) && count($o) < $cap; $k++) foreach ($items as $it) {
        if (count($o) >= $cap) break; $im = $it['images'][$k] ?? null; if ($im === null) continue;
        $o[] = ['u' => 'uploads/' . rawurlencode($im), 'g' => (string)($it['id'] ?? $it['slug'] ?? md5(json_encode($it)))];
    }
    return $o;
};
$heroSets = ['agendas' => $pick($products, max(8, count($products))), 'apps' => $pick($apps, max(6, count($apps))), 'landings' => $pick($landings, max(3, count($landings))), 'micro' => $pick($micro, max(3, count($micro)))];
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
<?php require __DIR__ . "/lib/tiktok.php"; ?></head>
<body data-page="inicio" data-base="<?= h(base_path()) ?>">
<header class="pad" id="hdr"><a class="logo" href="#top"><img class="mark" src="assets/img/logo-emblema.png" alt="EstrategIA" width="34" height="34">EstrategIA<small>Productos digitales</small></a>
<nav class="nav"><a class="t" href="#apps">Apps web</a><a class="t" href="#landings">Landings</a><a class="t" href="#agendas">Agendas</a><a class="t" href="#micro">Micro apps</a><a class="t" href="#proximamente">Próximamente</a><a class="btn" href="<?= h($waMsg('Hola! Quiero consultar por un producto a medida.')) ?>" target="_blank" rel="noopener">Pedí el tuyo</a></nav></header>

<main id="top">
<section id="desk" aria-label="Presentación">
  <div class="dstage" id="dstage" data-vland="assets/img/hero/landing-ember.mp4" data-vmicro="assets/img/hero/micro-nimm.mp4" data-sets="<?= h(json_encode($heroSets, JSON_UNESCAPED_SLASHES)) ?>">
    <div class="glowbg" id="glowbg"></div>
    <div class="htxt" id="htxt"><div class="k" style="color:var(--acc)">EstrategIA · Productos digitales</div>
      <h1>Productos digitales que <span class="rot serif" id="rot" aria-live="polite"><b class="on">organizan.</b><b>solucionan.</b><b>venden.</b></span></h1>
      <p>Agendas e imprimibles, apps web, landings y micro apps a medida. Todo listo para usar, mostrar y vender.</p>
      <div class="row"><a class="btn" href="#agendas">Ver los productos</a><a class="btn o" href="<?= h($waMsg('Hola! Quiero pedir algo a medida.')) ?>" target="_blank" rel="noopener">Pedí lo tuyo</a></div></div>
    <div class="dtitle" id="dtitle"><div class="k">Cuatro formas de trabajar con EstrategIA</div>
      <h2>Para <span class="rot2 serif" id="rot2"><b class="on">organizarte.</b><b>gestionar tu negocio.</b><b>vender más.</b><b>resolverlo en un toque.</b><b>hacer crecer tu idea.</b></span></h2></div>
    <div class="devs" id="devs">
      <a class="dev d-tab" href="#agendas" style="--c:#6a4fc4"><i class="halo"></i><div class="frame"><div class="scr"></div></div><div class="cap"><b>01</b><span>Agendas e imprimibles</span><small><?= count($products) ? count($products) . ' productos' : 'Muy pronto' ?></small></div></a>
      <a class="dev d-lap" href="#apps" style="--c:#2f8f83"><i class="halo"></i><div class="frame"><div class="scr"></div></div><div class="cap"><b>02</b><span>Apps web</span><small><?= count($apps) ? count($apps) . ' apps' : 'Muy pronto' ?></small></div></a>
      <a class="dev d-pho" href="#landings" style="--c:#e0714f"><i class="halo"></i><div class="frame"><div class="scr"></div></div><div class="cap"><b>03</b><span>Landings a medida</span><small><?= count($landings) ? count($landings) . ' ejemplos' : 'Muy pronto' ?></small></div></a>
      <a class="dev d-web" href="#micro" style="--c:#c9962b"><i class="halo"></i><div class="frame"><div class="scr"></div></div><div class="cap"><b>04</b><span>Micro apps a medida</span><small><?= count($micro) ? count($micro) . ' ejemplos' : 'Muy pronto' ?></small></div></a>
    </div>
    <div class="pieces" id="pieces"></div>
    <div class="stamp" id="stamp"><i></i>Bajá y mirá todo</div>
  </div>
</section>

<nav class="tabs" id="tabs" aria-label="Categorías"><a href="#apps" class="on">Apps web</a><a href="#landings">Landings</a><a href="#agendas"><span class="tl">Agendas e imprimibles</span><span class="ts">Agendas</span></a><a href="#micro">Micro apps</a><a href="#proximamente">Próximamente</a></nav>
<div id="panels">

<section id="agendas" class="panel" hidden aria-label="Agendas e imprimibles">
<div class="sechead pad"><div class="wrap"><div class="k" style="color:var(--acc)">01 · Agendas e imprimibles</div><h2>Papel bonito, <span class="serif">listo para usar.</span></h2><p>Agendas y planners para imprimir en casa o usar en tu tablet. Mirá las páginas por dentro y elegí la tuya.</p></div></div>
<div id="catalogo">
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

<?php
function app_band(array $a, int $n, string $kind, callable $ask): void {
  $im = array_map(fn($x) => 'uploads/' . rawurlencode($x), $a['images']);
  $src = ($a['html'] !== '' && is_file(SHOWCASE_DIR . '/' . basename($a['html']))) ? 'muestras/' . rawurlencode($a['html']) : '';
  [$askUrl, $askLabel] = $ask($a);
  $label = ['micro' => 'Micro app', 'landings' => 'Landing', 'soon' => 'Próximamente'][$kind] ?? 'App web'; ?>
  <article class="app pad" data-app data-src="<?= h($src) ?>" data-imgs="<?= h(json_encode($im, JSON_UNESCAPED_SLASHES)) ?>" data-title="<?= h($a['title']) ?>">
    <div class="app-info"><div class="k"><?= $label ?> · <?= str_pad((string)($n + 1), 2, '0', STR_PAD_LEFT) ?></div>
      <h3><?= h($a['title']) ?></h3><p><?= h($a['desc']) ?></p>
      <?php if (($a['price'] ?? '') !== ''): $uv = usd_amount((string)$a['price']); $ars = $uv > 0 ? usd_to_ars($uv) : 0; ?><div class="aprice"><small>Desde aprox.</small><b><?= h($uv > 0 ? usd_label($uv) . ($ars > 0 ? ' - ARS ' . number_format($ars, 0, ',', '.') : '') : $a['price']) ?></b><?php if ($ars > 0): ?><span class="ars">Pesos al dólar <?= h(strtolower(explode(' (', dolar_types()[dolar_type()] ?? 'oficial')[0])) ?> de hoy</span><?php endif; ?><em>Es una aproximación: depende de qué tan a medida sea el pedido.</em></div><?php endif; ?>
      <?php if ($a['tags']): ?><div class="atags"><?php foreach ($a['tags'] as $t): ?><span><?= h($t) ?></span><?php endforeach; ?></div><?php endif; ?>
      <?php if ($kind === 'soon'): ?><form class="aviso-form" data-aviso="<?= h($a['id']) ?>" action="aviso.php" method="post" novalidate>
          <input type="email" name="email" required maxlength="190" placeholder="Tu mail" autocomplete="email" aria-label="Tu mail">
          <input type="text" name="web" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
          <input type="hidden" name="slug" value="<?= h($a['id']) ?>">
          <button class="btn" type="submit" data-trk="aviso:<?= h($a['id']) ?>">Avisame cuando esté</button>
          <p class="msg" role="status" aria-live="polite"></p>
        </form>
      <?php endif; ?>
      <div class="row"><?php if ($kind !== 'soon'): ?><a class="btn" href="<?= h($askUrl) ?>" target="_blank" rel="noopener"><?= h($askLabel) ?></a><?php endif; ?>
        <?php if ($src !== ''): ?><a class="btn o vopen" href="demo.php?f=<?= h(rawurlencode(basename($a['html']))) ?>" target="_blank" rel="noopener">Abrir demo completa ↗</a><?php endif; ?>
        <?php if ($kind !== 'soon' && (count($im) > 1 || ($src !== '' && $im))): ?><button type="button" class="btn o" data-open="0">Ver fotos</button><?php endif; ?></div></div>
    <div class="app-demo">
      <div class="vtools"><div class="vdev"><button type="button" class="on" data-dev="d">Computadora</button><button type="button" data-dev="m">Celular</button></div></div>
      <div class="browser"><div class="bbar"><i></i><i></i><i></i><b class="vurl"><?= h(trim(preg_replace('~[^a-z0-9]+~', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $a['title']) ?: 'app')), '-')) ?>.com</b></div>
        <div class="vp"><div class="vfr"><div class="vph"<?= $src === '' && $im ? ' style="background-image:url(\'' . h($im[0]) . '\')"' . ($kind === 'soon' ? '' : ' data-open="0"') : '' ?>></div></div></div>
        <?php if ($kind === 'soon' && count($im) > 1): ?><button type="button" class="sarr sprev" aria-label="Foto anterior">&#8249;</button><button type="button" class="sarr snext" aria-label="Foto siguiente">&#8250;</button><div class="sdots"><?php foreach ($im as $_): ?><i></i><?php endforeach; ?></div><?php endif; ?></div>
      <?php if ($kind !== 'soon' && count($im) > 1): ?><div class="athumbs"><?php foreach (array_slice($im, 0, 6) as $i => $u): ?><button type="button" data-open="<?= $i ?>" aria-label="Ampliar foto <?= $i + 1 ?>" style="background-image:url('<?= h($u) ?>')"></button><?php endforeach; ?></div><?php endif; ?>
    </div>
  </article>
<?php } ?>
<?php
$askApp = fn($a) => $a['link'] !== '' ? [$a['link'], 'Ver app ↗'] : [$waMsg('Hola! Me interesa la app "' . $a['title'] . '".'), 'Me interesa'];
$askMicro = fn($m) => [$waMsg('Hola! Me gustó la micro app "' . $m['title'] . '". Quiero algo así para mi negocio.'), 'Quiero algo así'];
$askSoon = fn($x) => ['#', ''];
$askLand = fn($l) => [$l['link'] !== '' ? $l['link'] : $waMsg('Hola! Me gustó la landing "' . $l['title'] . '". Quiero una para mi negocio.'), 'Quiero una igual'];
?>
<section id="apps" class="panel apps" aria-label="Apps web">
  <div class="sechead pad"><div class="k">02 · Apps web</div><h2>Apps que <span class="serif">resuelven</span> tu día a día.</h2><p>Sistemas web hechos para que un negocio se ordene: gestión, stock, turnos, cuentas. Probá la demo, se usa desde cualquier dispositivo.</p></div>
  <?php if (!$apps): ?><div class="soon pad"><b>Muy pronto</b><span>Acá vas a poder probar las demos de las apps web.</span></div><?php endif; ?>
  <?php foreach ($apps as $n => $a) app_band($a, $n, 'apps', $askApp); ?>
</section>

<section id="landings" class="panel lands apps" hidden aria-label="Landings a medida">
  <div class="sechead pad"><div class="k">03 · Landings a medida</div><h2>Landings que <span class="serif">convierten.</span></h2><p>Páginas de una sola pantalla pensadas para vender o presentar tu negocio. Mirá cómo quedan funcionando.</p></div>
  <?php if (!$landings): ?><div class="soon pad"><b>Muy pronto</b><span>Acá vas a ver ejemplos de landings funcionando.</span></div><?php endif; ?>
  <?php foreach ($landings as $n => $l) app_band($l, $n, 'landings', $askLand); ?>
  <div class="incl-wrap pad">
  <div class="incl">
    <div class="incl-main"><h3>Qué incluye tu landing</h3>
      <ul>
        <li>Diseño a medida de tu negocio: colores, textos y fotos.</li>
        <li>Se ve bien en celular y en computadora.</li>
        <li>Botones directos a tu WhatsApp e Instagram.</li>
        <li><b>Hosting y dominio incluidos el primer año</b>, con el dominio registrado a tu nombre.</li>
        <li>Una revisión y actualización por mes, sin cargo, durante los 3 primeros meses.</li>
        <li>Panel admin opcional, para que cargues y modifiques tus productos, precios y fotos vos mismo (se cotiza aparte).</li>
      </ul></div>
    <div class="incl-side">
      <div><b>Tu página es tuya.</b> Si no querés seguir trabajando conmigo, te entrego los archivos de la página ya paga y el dominio a tu nombre. Nada más.</div>
      <div><b>Después de los 3 meses</b> podés seguir con un mantenimiento mensual opcional, para modificaciones no complejas (textos, precios, fotos, horarios).</div>
      <div><b>Primero lo ves, después decidís.</b> Con tu permiso te armo una página demo con datos o imágenes de tu negocio para que veas un adelanto, antes de pagar nada.</div>
      <a class="btn" href="<?= h($waMsg('Hola! Quiero que me armen una demo de landing para mi negocio.')) ?>" target="_blank" rel="noopener">Pedí tu demo</a>
    </div>
    <p class="incl-note"><b>Dominio:</b> la dirección de tu página (ej. tunegocio.com). <b>Hosting:</b> el lugar en internet donde vive tu página para que se vea las 24 horas.</p>
  </div>
  </div>
</section>

<section id="micro" class="panel micro apps" hidden aria-label="Micro apps a medida">
  <div class="sechead pad"><div class="k">04 · Micro apps a medida</div><h2>Herramientas chicas, <span class="serif">hechas para vos.</span></h2><p>Calculadoras, paneles, formularios y utilidades pensadas para una tarea puntual de tu negocio. Mirá cómo funcionan.</p></div>
  <?php if (!$micro): ?><div class="soon pad"><b>Muy pronto</b><span>Acá vas a ver las micro apps que ya hicimos.</span></div><?php endif; ?>
  <?php foreach ($micro as $n => $m) app_band($m, $n, 'micro', $askMicro); ?>
</section>

<section id="proximamente" class="panel soonp apps" hidden aria-label="Próximamente">
  <div class="sechead pad"><div class="k">05 · Próximamente</div><h2>Lo que <span class="serif">se viene.</span></h2><p>Productos que todavía estamos terminando. Dejá tu mail y te avisamos apenas estén disponibles, una sola vez.</p></div>
  <?php if (!$soon): ?><div class="soon pad"><b>Muy pronto</b><span>Acá vas a ver lo que estamos preparando.</span></div><?php endif; ?>
  <?php foreach ($soon as $n => $p) app_band($p, $n, 'soon', $askSoon); ?>
</section>


</div>

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
    <?php include __DIR__ . '/lib/footer.php'; ?></div>
</section>
</main>

<div class="lb" id="lb" role="dialog" aria-modal="true" aria-label="Vista ampliada"><img id="lbi" alt=""><button class="x" id="lbx" aria-label="Cerrar">✕</button><button class="pv" id="lbp" aria-label="Anterior">‹</button><button class="nx" id="lbn" aria-label="Siguiente">›</button><div class="cap" id="lbc"></div></div>
<script src="assets/js/site.js?v=<?= $v ?>" defer></script>
<script src="assets/js/track.js?v=<?= $v ?>" defer></script>
</body>
</html>
