<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
if (!db_ready()) { header('Location: setup.php'); exit; }
extras_ready();
mark_owner_device(true); // quien mira el panel no se cuenta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = (string)($_POST['act'] ?? '');
    if ($act === 'mine') {
        $vid = (string)($_POST['vid'] ?? '');
        if (preg_match('/^[a-z0-9]{6,16}$/', $vid)) { add_owner_vid($vid); $n = db()->prepare('DELETE FROM events WHERE vid=?'); $n->execute([$vid]); flash('Listo: borré esas visitas y ese aparato ya no se cuenta.'); }
    } elseif ($act === 'reset') {
        $n = (int)db()->exec('DELETE FROM events'); flash('Estadísticas en cero (' . $n . ' registros borrados). Empiezan a contar desde ahora.');
    }
    header('Location: estadisticas.php?d=' . (int)($_GET['d'] ?? 30)); exit;
}

$ranges = [1 => 'Hoy', 7 => '7 días', 30 => '30 días', 90 => '90 días', 365 => '1 año'];
$d = (int)($_GET['d'] ?? 30); if (!isset($ranges[$d])) $d = 30;
$from = date('Y-m-d 00:00:00', strtotime('-' . ($d - 1) . ' days'));
$prevFrom = date('Y-m-d 00:00:00', strtotime('-' . (2 * $d - 1) . ' days'));
$pdo = db();
$q = function (string $sql, array $a = []) use ($pdo) { $st = $pdo->prepare($sql); $st->execute($a); return $st; };
$one = fn(string $sql, array $a = []) => (float)$q($sql, $a)->fetchColumn();

$visitors = $one('SELECT COUNT(DISTINCT vid) FROM events WHERE ts >= ?', [$from]);
$visitorsPrev = $one('SELECT COUNT(DISTINCT vid) FROM events WHERE ts >= ? AND ts < ?', [$prevFrom, $from]);
$sessions = $one('SELECT COUNT(DISTINCT sid) FROM events WHERE ts >= ?', [$from]);
$sessionsPrev = $one('SELECT COUNT(DISTINCT sid) FROM events WHERE ts >= ? AND ts < ?', [$prevFrom, $from]);
$pageviews = $one("SELECT COUNT(*) FROM events WHERE t='pv' AND ts >= ?", [$from]);
$clicks = $one("SELECT COUNT(*) FROM events WHERE t IN ('tab','click','demo','wa','photos','buy') AND ts >= ?", [$from]);
$sSeen = $one("SELECT COUNT(DISTINCT sid) FROM events WHERE ts >= ? AND (t='seen' OR page='detalle')", [$from]);
$sDetail = $one("SELECT COUNT(DISTINCT sid) FROM events WHERE ts >= ? AND page='detalle' AND t='pv'", [$from]);
$sBuy = $one("SELECT COUNT(DISTINCT sid) FROM events WHERE ts >= ? AND t='buy'", [$from]);
$sales = $q("SELECT COUNT(*) n, COALESCE(SUM(amount),0) s FROM orders WHERE status='paid' AND paid_at >= ?", [$from])->fetch();
$salesPrev = $q("SELECT COUNT(*) n, COALESCE(SUM(amount),0) s FROM orders WHERE status='paid' AND paid_at >= ? AND paid_at < ?", [$prevFrom, $from])->fetch();
$waClicks = $one("SELECT COUNT(*) FROM events WHERE t='wa' AND ts >= ?", [$from]);
$conv = $sessions > 0 ? (float)$sales['n'] / $sessions * 100 : 0;

// serie por día (o por hora si es "Hoy")
$series = [];
if ($d === 1) {
    for ($h = 0; $h < 24; $h++) $series[sprintf('%02d', $h)] = ['v' => 0, 'p' => 0];
    foreach ($q("SELECT DATE_FORMAT(ts,'%H') k, COUNT(DISTINCT vid) v, SUM(t='pv') p FROM events WHERE ts >= ? GROUP BY k", [$from]) as $r) $series[$r['k']] = ['v' => (int)$r['v'], 'p' => (int)$r['p']];
} else {
    for ($i = $d - 1; $i >= 0; $i--) $series[date('Y-m-d', strtotime("-$i days"))] = ['v' => 0, 'p' => 0];
    foreach ($q("SELECT DATE(ts) k, COUNT(DISTINCT vid) v, SUM(t='pv') p FROM events WHERE ts >= ? GROUP BY k", [$from]) as $r) if (isset($series[$r['k']])) $series[$r['k']] = ['v' => (int)$r['v'], 'p' => (int)$r['p']];
}

// productos: vistos en el inicio, detalle, comprar, WhatsApp, demos y ventas
$prod = [];
$add = function (string $name, string $col, float $v) use (&$prod) { $name = trim($name); if ($name === '') return; $prod[$name] = ($prod[$name] ?? ['seen' => 0, 'det' => 0, 'buy' => 0, 'wa' => 0, 'demo' => 0, 'sold' => 0, 'rev' => 0]); $prod[$name][$col] += $v; };
foreach ($q("SELECT t, page, n, COUNT(*) c FROM events WHERE ts >= ? AND n <> '' AND (t IN ('seen','buy','wa','demo') OR (t='pv' AND page='detalle')) GROUP BY t, page, n", [$from]) as $r) {
    $col = ['seen' => 'seen', 'buy' => 'buy', 'wa' => 'wa', 'demo' => 'demo', 'pv' => 'det'][$r['t']];
    if ($r['t'] === 'wa' && in_array($r['n'], ['inicio', 'detalle', 'legales', 'otra'], true)) continue;
    $add((string)$r['n'], $col, (float)$r['c']);
}
foreach ($q("SELECT product_title n, COUNT(*) c, SUM(amount) s FROM orders WHERE status='paid' AND paid_at >= ? GROUP BY product_title", [$from]) as $r) { $add((string)$r['n'], 'sold', (float)$r['c']); $add((string)$r['n'], 'rev', (float)$r['s']); }
uasort($prod, fn($a, $b) => ($b['det'] * 3 + $b['seen'] + $b['wa'] * 4 + $b['demo'] * 2 + $b['sold'] * 10) <=> ($a['det'] * 3 + $a['seen'] + $a['wa'] * 4 + $a['demo'] * 2 + $a['sold'] * 10));
$prod = array_slice($prod, 0, 15, true);

$tabNames = ['agendas' => 'Agendas e imprimibles', 'apps' => 'Apps web', 'landings' => 'Landings', 'micro' => 'Micro apps'];
$tabs = []; foreach ($q("SELECT n, COUNT(*) c FROM events WHERE t='tab' AND ts >= ? GROUP BY n ORDER BY c DESC", [$from]) as $r) if (isset($tabNames[$r['n']])) $tabs[$tabNames[$r['n']]] = (int)$r['c'];
foreach ($tabNames as $v) $tabs[$v] = $tabs[$v] ?? 0; arsort($tabs);

$src = []; foreach ($q("SELECT src, COUNT(DISTINCT sid) c FROM events WHERE ts >= ? GROUP BY src", [$from]) as $r) { $l = source_label((string)$r['src']); $src[$l] = ($src[$l] ?? 0) + (int)$r['c']; }
arsort($src); $src = array_slice($src, 0, 10, true);

$dev = ['m' => 0, 'd' => 0]; foreach ($q("SELECT dev, COUNT(DISTINCT sid) c FROM events WHERE ts >= ? GROUP BY dev", [$from]) as $r) $dev[$r['dev'] === 'm' ? 'm' : 'd'] += (int)$r['c'];

$tl = ['wa' => 'WhatsApp', 'demo' => 'Abrió demo', 'photos' => 'Vio fotos', 'click' => 'Botón', 'buy' => 'Comprar', 'tab' => 'Pestaña'];
$topClicks = []; foreach ($q("SELECT t, n, COUNT(*) c FROM events WHERE ts >= ? AND t IN ('wa','demo','photos','click','buy') GROUP BY t, n ORDER BY c DESC LIMIT 12", [$from]) as $r) $topClicks[] = $r;

// últimas visitas: cada sesión con lo que hizo
$recent = [];
$sids = $q("SELECT sid, MIN(ts) ini, MAX(ts) fin, MAX(dev) dev, MAX(src) src, MAX(vid) vid FROM events WHERE ts >= ? AND sid <> '' GROUP BY sid ORDER BY fin DESC LIMIT 40", [$from])->fetchAll();
if ($sids) {
    $in = implode(',', array_fill(0, count($sids), '?'));
    $ev = []; foreach ($q("SELECT sid, t, n, page FROM events WHERE sid IN ($in) ORDER BY id", array_column($sids, 'sid')) as $r) $ev[$r['sid']][] = $r;
    $vc = []; foreach ($q("SELECT vid, COUNT(DISTINCT sid) c FROM events WHERE vid IN (" . implode(',', array_fill(0, count($sids), '?')) . ") GROUP BY vid", array_column($sids, 'vid')) as $r) $vc[$r['vid']] = (int)$r['c'];
    $tabN = ['agendas' => 'Agendas', 'apps' => 'Apps web', 'landings' => 'Landings', 'micro' => 'Micro apps'];
    foreach ($sids as $s) {
        $acts = []; $pv = 0;
        foreach ($ev[$s['sid']] ?? [] as $e) {
            if ($e['t'] === 'pv') { $pv++; $x = $e['page'] === 'detalle' ? 'Abrió el detalle de ' . $e['n'] : ($e['page'] === 'legales' ? 'Leyó los legales' : ($e['page'] === 'inicio' ? 'Entró al inicio' : '')); }
            elseif ($e['t'] === 'tab') $x = 'Pestaña ' . ($tabN[$e['n']] ?? $e['n']);
            elseif ($e['t'] === 'seen') $x = 'Vio ' . $e['n'];
            elseif ($e['t'] === 'wa') $x = 'Tocó WhatsApp' . ($e['n'] !== '' && !in_array($e['n'], ['inicio', 'detalle'], true) ? ' (' . $e['n'] . ')' : '');
            elseif ($e['t'] === 'demo') $x = 'Abrió la demo de ' . $e['n'];
            elseif ($e['t'] === 'photos') $x = 'Miró fotos' . ($e['n'] !== '' ? ' de ' . $e['n'] : '');
            elseif ($e['t'] === 'buy') $x = '🛒 Tocó Comprar en ' . $e['n'];
            else $x = 'Tocó «' . $e['n'] . '»';
            if ($x !== '' && end($acts) !== $x) $acts[] = $x;
        }
        $min = max(0, (int)round((strtotime($s['fin']) - strtotime($s['ini'])) / 60));
        $recent[] = ['vid' => (string)$s['vid'], 'ini' => $s['ini'], 'dev' => $s['dev'] === 'm' ? 'Celular' : 'Computadora', 'src' => source_label((string)$s['src']), 'acts' => $acts, 'min' => $min, 'ret' => ($vc[$s['vid']] ?? 1) > 1];
    }
}
$fmt = fn(float $n) => number_format($n, 0, ',', '.');
$delta = function (float $now, float $prev): string {
    if ($prev <= 0) return $now > 0 ? '<em class="up">nuevo</em>' : '';
    $p = ($now - $prev) / $prev * 100;
    return '<em class="' . ($p >= 0 ? 'up' : 'down') . '">' . ($p >= 0 ? '▲ ' : '▼ ') . number_format(abs($p), 0) . '%</em>';
};
$bars = function (array $rows, string $color = 'var(--acc)') use ($fmt): string {
    if (!$rows || max($rows) <= 0) return '<p class="muted sm">Todavía no hay datos en este período.</p>';
    $mx = max($rows); $o = '<div class="hbars">';
    foreach ($rows as $k => $v) $o .= '<div class="hb"><span>' . h((string)$k) . '</span><i style="--w:' . round($v / $mx * 100, 1) . '%;--c:' . $color . '"></i><b>' . $fmt((float)$v) . '</b></div>';
    return $o . '</div>';
};
$hasData = $sessions > 0;
admin_head('Estadísticas'); ?>
<div class="head"><div><h1>Estadísticas</h1><p class="muted">Visitas reales de la tienda. No se cuentan tus visitas ni los robots.</p></div>
  <nav class="rng"><?php foreach ($ranges as $k => $l): ?><a href="?d=<?= $k ?>"<?= $k === $d ? ' class="on"' : '' ?>><?= h($l) ?></a><?php endforeach; ?></nav></div>
<?php show_flash(); ?>
<?php if (!$hasData): ?><div class="flash warn">Todavía no hay visitas registradas en este período. Apenas alguien entre a la tienda, los números aparecen acá (podés probar desde un celular que no sea el tuyo o en una ventana de incógnito).</div><?php endif; ?>

<div class="kpis">
  <div class="kpi"><small>Personas</small><b><?= $fmt($visitors) ?></b><span>visitantes únicos <?= $delta($visitors, $visitorsPrev) ?></span></div>
  <div class="kpi"><small>Visitas</small><b><?= $fmt($sessions) ?></b><span>veces que entraron <?= $delta($sessions, $sessionsPrev) ?></span></div>
  <div class="kpi"><small>Páginas vistas</small><b><?= $fmt($pageviews) ?></b><span><?= $sessions > 0 ? number_format($pageviews / $sessions, 1, ',', '.') . ' por visita' : '—' ?></span></div>
  <div class="kpi"><small>Clics</small><b><?= $fmt($clicks) ?></b><span><?= $fmt($waClicks) ?> a WhatsApp</span></div>
  <div class="kpi hi"><small>Compras</small><b><?= $fmt((float)$sales['n']) ?></b><span><?= h(format_price((float)$sales['s'])) ?> <?= $delta((float)$sales['s'], (float)$salesPrev['s']) ?></span></div>
  <div class="kpi"><small>Conversión</small><b><?= number_format($conv, 1, ',', '.') ?>%</b><span>de las visitas compran</span></div>
</div>

<div class="card st-card">
  <div class="st-h"><h2><?= $d === 1 ? 'Hoy, hora por hora' : 'Visitantes por día' ?></h2><div class="leg"><span><i style="background:var(--acc)"></i>Personas</span><span><i style="background:#e0714f"></i>Páginas vistas</span></div></div>
  <?php $mx = max(1, ...array_values(array_map(fn($x) => max($x['v'], $x['p']), $series))); $n = count($series); $W = 1000; $H = 220; $bw = $W / $n;
  $pts = []; $i = 0; foreach ($series as $k => $s) { $pts[] = round($i * $bw + $bw / 2, 1) . ',' . round($H - $s['p'] / $mx * ($H - 14), 1); $i++; } ?>
  <div class="chart"><svg viewBox="0 0 <?= $W ?> <?= $H + 26 ?>" preserveAspectRatio="none" role="img" aria-label="Gráfico de visitas">
    <?php for ($g = 1; $g <= 3; $g++): $y = round($H - $g / 3 * ($H - 14), 1); ?><line x1="0" x2="<?= $W ?>" y1="<?= $y ?>" y2="<?= $y ?>" class="gl"/><?php endfor; ?>
    <?php $i = 0; foreach ($series as $k => $s): $bh = $s['v'] / $mx * ($H - 14); ?><rect x="<?= round($i * $bw + $bw * .18, 1) ?>" y="<?= round($H - $bh, 1) ?>" width="<?= round($bw * .64, 1) ?>" height="<?= round(max($bh, $s['v'] ? 2 : 0), 1) ?>" rx="<?= min(6, round($bw * .2)) ?>" class="bar"><title><?= h($d === 1 ? $k . ' h' : date('d/m', strtotime($k))) ?>: <?= $s['v'] ?> personas · <?= $s['p'] ?> páginas</title></rect><?php $i++; endforeach; ?>
    <polyline points="<?= implode(' ', $pts) ?>" class="ln"/>
    <?php $step = max(1, (int)ceil($n / 10)); $i = 0; foreach ($series as $k => $s): if ($i % $step === 0): ?><text x="<?= round($i * $bw + $bw / 2, 1) ?>" y="<?= $H + 20 ?>" class="ax"><?= h($d === 1 ? $k . 'h' : date('d/m', strtotime($k))) ?></text><?php endif; $i++; endforeach; ?>
  </svg><div class="ymax"><?= $fmt((float)$mx) ?></div></div>
</div>

<div class="st-grid">
  <div class="card st-card">
    <h2>Recorrido de compra</h2><p class="muted sm">Cuántas visitas llegan a cada paso.</p>
    <?php $steps = ['Entraron a la página' => $sessions, 'Vieron productos' => $sSeen, 'Abrieron el detalle' => $sDetail, 'Tocaron “Comprar”' => $sBuy, 'Compraron' => (float)$sales['n']]; $top = max(1, $sessions); $prev = null; ?>
    <div class="funnel"><?php foreach ($steps as $l => $v): ?>
      <div class="fs"><div class="fl"><span><?= h($l) ?></span><b><?= $fmt((float)$v) ?></b></div><div class="ft"><i style="--w:<?= round(max(0, min(100, $v / $top * 100)), 1) ?>%"></i></div>
      <?php if ($prev !== null): ?><small><?= $prev > 0 ? number_format(min(100, $v / $prev * 100), 0) . '% del paso anterior' : '—' ?></small><?php endif; ?></div>
    <?php $prev = (float)$v; endforeach; ?></div>
  </div>
  <div class="card st-card">
    <h2>De dónde vienen</h2><p class="muted sm">Visitas según el sitio desde el que llegaron.</p>
    <?= $bars($src, '#2f8f83') ?>
    <div class="devs-split"><?php $dt = max(1, $dev['m'] + $dev['d']); ?>
      <div><small>Celular</small><b><?= round($dev['m'] / $dt * 100) ?>%</b></div><div class="dbar"><i style="--w:<?= round($dev['m'] / $dt * 100, 1) ?>%"></i></div><div><small>Computadora</small><b><?= round($dev['d'] / $dt * 100) ?>%</b></div></div>
    <p class="muted sm tip">Tip: en tus links de Instagram o WhatsApp agregá <code>?ref=instagram</code> o <code>?ref=whatsapp</code> al final y acá vas a ver exactamente de dónde vino cada visita.</p>
  </div>
</div>

<div class="card st-card">
  <h2>Productos más visitados</h2><p class="muted sm">Vistos = lo vieron en el inicio · Detalle = abrieron su página · Comprar = tocaron el botón · WhatsApp y demos para lo que es a medida.</p>
  <?php if (!$prod): ?><p class="muted sm">Todavía no hay datos en este período.</p><?php else: $pm = max(1, ...array_values(array_map(fn($x) => $x["seen"] + $x["det"], $prod))); ?>
  <div class="ptable"><div class="pr ph"><span>Producto</span><span>Vistos</span><span>Detalle</span><span>Comprar</span><span>WhatsApp</span><span>Demos</span><span>Ventas</span></div>
  <?php foreach ($prod as $name => $r): ?><div class="pr"><span class="pn"><b><?= h($name) ?></b><i style="--w:<?= round(($r['seen'] + $r['det']) / $pm * 100, 1) ?>%"></i></span><span data-l="Vistos"><?= $fmt($r['seen']) ?></span><span data-l="Detalle"><?= $fmt($r['det']) ?></span><span data-l="Comprar"><?= $fmt($r['buy']) ?></span><span data-l="WhatsApp"><?= $fmt($r['wa']) ?></span><span data-l="Demos"><?= $fmt($r['demo']) ?></span><span data-l="Ventas"><?= $r['sold'] ? '<b>' . $fmt($r['sold']) . '</b> · ' . h(format_price($r['rev'])) : '0' ?></span></div><?php endforeach; ?>
  </div><?php endif; ?>
</div>

<div class="st-grid">
  <div class="card st-card"><h2>Pestañas más vistas</h2><p class="muted sm">Veces que tocaron cada categoría.</p><?= $bars($tabs) ?></div>
  <div class="card st-card"><h2>Clics más tocados</h2><p class="muted sm">Botones, WhatsApp, demos y fotos.</p>
    <?php if (!$topClicks): ?><p class="muted sm">Todavía no hay datos en este período.</p><?php else: $rows = []; foreach ($topClicks as $r) $rows[($tl[$r['t']] ?? $r['t']) . ($r['n'] !== '' ? ' · ' . $r['n'] : '')] = (int)$r['c']; echo $bars($rows, '#e0714f'); endif; ?></div>
</div>
<div class="card st-card" style="margin-top:16px">
  <h2>Últimas visitas</h2><p class="muted sm">Cada visita con la hora, el aparato, de dónde vino y qué hizo. Si reconocés una tuya, entrá al admin desde ese aparato para que no se cuente más.</p>
  <?php if (!$recent): ?><p class="muted sm">Todavía no hay visitas en este período.</p><?php else: ?><div class="rv">
  <?php foreach ($recent as $r): ?><details class="rvi"><summary><span class="rt"><?= h(date('d/m H:i', strtotime($r['ini']))) ?></span><span class="rd"><?= $r['dev'] === 'Celular' ? '📱' : '💻' ?> <?= h($r['dev']) ?></span><span class="rs"><?= h($r['src']) ?></span><span class="rx"><?= count($r['acts']) ?> acciones · <?= $r['min'] < 1 ? 'menos de 1 min' : $r['min'] . ' min' ?><?= $r['ret'] ? ' · <b>volvió</b>' : '' ?></span></summary>
    <ol><?php foreach ($r['acts'] as $x): ?><li><?= h($x) ?></li><?php endforeach; ?></ol>
    <?php if ($r['vid'] !== ''): ?><form method="post" class="mine" onsubmit="return confirm('¿Borrar las visitas de este aparato y no contarlo más?')"><?= csrf_field() ?><input type="hidden" name="act" value="mine"><input type="hidden" name="vid" value="<?= h($r['vid']) ?>"><button class="btn sm o" type="submit">Era yo · borrar y no contar este aparato</button></form><?php endif; ?></details><?php endforeach; ?>
  </div><?php endif; ?>
</div>
<form method="post" class="reset" onsubmit="return confirm('¿Borrar TODAS las visitas registradas hasta ahora? Las ventas no se tocan.')"><?= csrf_field() ?><input type="hidden" name="act" value="reset"><button class="btn sm o" type="submit">Poner las estadísticas en cero</button><span class="muted sm">Útil antes de lanzar: borra las visitas de prueba. Las ventas y pedidos no se tocan.</span></form>
<p class="muted sm" style="margin-top:18px">Las estadísticas son anónimas: no se guarda la IP ni datos personales. Los datos de más de 18 meses se borran solos.</p>
<?php admin_foot();
