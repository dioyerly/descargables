<?php
declare(strict_types=1);
// Solo lectura para el Centro de operaciones (panel.estrategia.site). Sin datos personales de visitantes.
// Se abre con la clave compartida: 'ecosystem_key' en config.php (32+ caracteres), enviada como "Authorization: Bearer ...".
require __DIR__ . '/lib/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
function out(int $c, array $b): never { http_response_code($c); echo json_encode($b, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') out(405, ['ok' => false, 'code' => 'method_not_allowed']);
// Conexión por código: el panel manda el código de 8 dígitos (un solo uso, 10 min) y una clave nueva.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $inP = json_decode((string)file_get_contents('php://input', false, null, 0, 1025), true);
    if (is_array($inP) && ($inP['accion'] ?? '') === 'emparejar') {
        if (!db_ready()) out(503, ['ok' => false, 'code' => 'db_unavailable']);
        extras_ready();
        $fl = explode('|', setting('emparejar_fallos', '0|0')) + [0, 0];
        if ((int)$fl[1] > time() - 900 && (int)$fl[0] >= 5) out(429, ['ok' => false, 'code' => 'rate']);
        $cod = preg_replace('/\D+/', '', (string)($inP['codigo'] ?? '')); $clave = (string)($inP['clave'] ?? '');
        $p = explode('|', setting('centro_codigo', ''), 2);
        $okP = count($p) === 2 && (int)$p[1] >= time() && hash_equals($p[0], hash('sha256', $cod)) && strlen($clave) >= 32 && strlen($clave) <= 200 && preg_match('/^[A-Za-z0-9_-]+$/', $clave);
        if (!$okP) { setting_set('emparejar_fallos', ((int)$fl[1] > time() - 900 ? (int)$fl[0] + 1 : 1) . '|' . time()); usleep(400000); out(401, ['ok' => false, 'code' => 'codigo_incorrecto']); }
        setting_set('centro_token_hash', hash('sha256', $clave)); setting_set('centro_codigo', ''); setting_set('emparejar_fallos', '0|0');
        out(200, ['ok' => true]);
    }
}

$key = (string)cfg('ecosystem_key', '');
$alt = (string)cfg('export_token', '');
$hashSet = db_ready() && extras_ready() ? setting('centro_token_hash', '') : '';
if (strlen($key) < 32 && strlen($alt) < 32 && $hashSet === '') out(503, ['ok' => false, 'code' => 'export_not_configured']);
$auth = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if ($auth === '' && function_exists('getallheaders')) foreach (getallheaders() as $k => $v) if (strtolower($k) === 'authorization') $auth = (string)$v;
$tok = preg_match('/^Bearer\s+(\S+)$/i', $auth, $m) ? $m[1] : '';
$ok = ($tok !== '') && ((strlen($key) >= 32 && hash_equals($key, $tok)) || (strlen($alt) >= 32 && hash_equals($alt, $tok)) || ($hashSet !== '' && hash_equals($hashSet, hash('sha256', $tok))));
if (!$ok) { usleep(400000); out(401, ['ok' => false, 'code' => 'unauthorized']); }

$view = (string)($_GET['view'] ?? 'summary');
if (!in_array($view, ['summary', 'sistema'], true)) out(404, ['ok' => false, 'code' => 'view_not_found']);
if (!db_ready()) out(503, ['ok' => false, 'code' => 'db_unavailable']);
extras_ready();
$pdo = db();
$q = function (string $sql, array $a = []) use ($pdo) { $st = $pdo->prepare($sql); $st->execute($a); return $st; };
$one = fn(string $sql, array $a = []) => (float)$q($sql, $a)->fetchColumn();

if ($view === 'sistema') {
    $al = [];
    $mpTok = (string)cfg('mp.access_token', '');
    $mpOk = $mpTok !== '' && $mpTok !== 'PONER_AQUI';
    $ppOk = (string)cfg('paypal.client_id', '') !== '' && cfg('paypal.client_id') !== 'PONER_AQUI';
    if (!$mpOk) $al[] = ['nivel' => 'rojo', 'texto' => 'Falta el Access Token de Mercado Pago: la tienda no puede cobrar'];
    if (!$ppOk) $al[] = ['nivel' => 'amarillo', 'texto' => 'PayPal no esta configurado (no se pueden cobrar tarjetas del exterior)'];
    $failed = (int)$one("SELECT COUNT(*) FROM orders WHERE status NOT IN ('paid','pending') AND created_at >= (NOW() - INTERVAL 7 DAY)");
    if ($failed > 0) $al[] = ['nivel' => 'amarillo', 'texto' => $failed . ($failed === 1 ? ' pedido fallido o devuelto' : ' pedidos fallidos o devueltos') . ' en 7 dias'];
    $stuck = (int)$one("SELECT COUNT(*) FROM orders WHERE status='pending' AND created_at < (NOW() - INTERVAL 1 DAY) AND created_at >= (NOW() - INTERVAL 7 DAY)");
    if ($stuck > 0) $al[] = ['nivel' => 'info', 'texto' => $stuck . ($stuck === 1 ? ' pedido quedo' : ' pedidos quedaron') . ' sin pagar (quizas abandonaron el pago)'];
    $paidNoTok = (int)$one("SELECT COUNT(*) FROM orders WHERE status='paid' AND (token IS NULL OR token='')");
    if ($paidNoTok > 0) $al[] = ['nivel' => 'rojo', 'texto' => $paidNoTok . ' pago(s) sin enlace de descarga entregado'];
    $last = $q("SELECT MAX(ts) FROM events")->fetchColumn();
    if ($last && strtotime((string)$last) < time() - 3 * 86400) $al[] = ['nivel' => 'amarillo', 'texto' => 'La tienda no registra visitas hace mas de 3 dias'];
    out(200, ['ok' => true, 'alertas' => $al, 'diagnostico' => ['mp' => $mpOk, 'paypal' => $ppOk, 'https' => str_starts_with((string)cfg('site_url', ''), 'https://')], 'ultimo_evento' => $last ?: null]);
}

$d = (int)($_GET['d'] ?? 30); if (!in_array($d, [7, 30, 90], true)) $d = 30;
$from = date('Y-m-d 00:00:00', strtotime('-' . ($d - 1) . ' days'));
$prevFrom = date('Y-m-d 00:00:00', strtotime('-' . (2 * $d - 1) . ' days'));
$today = date('Y-m-d 00:00:00');
$visitors = $one('SELECT COUNT(DISTINCT vid) FROM events WHERE ts >= ?', [$from]);
$visitorsPrev = $one('SELECT COUNT(DISTINCT vid) FROM events WHERE ts >= ? AND ts < ?', [$prevFrom, $from]);
$visitorsHoy = $one('SELECT COUNT(DISTINCT vid) FROM events WHERE ts >= ?', [$today]);
$sessions = $one('SELECT COUNT(DISTINCT sid) FROM events WHERE ts >= ?', [$from]);
$pageviews = $one("SELECT COUNT(*) FROM events WHERE t='pv' AND ts >= ?", [$from]);
$clicks = $one("SELECT COUNT(*) FROM events WHERE t IN ('tab','click','demo','wa','photos','buy','aviso') AND ts >= ?", [$from]);
$wa = $one("SELECT COUNT(*) FROM events WHERE t='wa' AND ts >= ?", [$from]);
$sSeen = $one("SELECT COUNT(DISTINCT sid) FROM events WHERE ts >= ? AND (t='seen' OR page='detalle')", [$from]);
$sDet = $one("SELECT COUNT(DISTINCT sid) FROM events WHERE ts >= ? AND page='detalle' AND t='pv'", [$from]);
$sBuy = $one("SELECT COUNT(DISTINCT sid) FROM events WHERE ts >= ? AND t='buy'", [$from]);
$sales = $q("SELECT COUNT(*) n, COALESCE(SUM(amount),0) s FROM orders WHERE status='paid' AND paid_at >= ?", [$from])->fetch();
$salesPrev = $q("SELECT COUNT(*) n FROM orders WHERE status='paid' AND paid_at >= ? AND paid_at < ?", [$prevFrom, $from])->fetch();
$byCur = [];
foreach ($q("SELECT currency c, COUNT(*) n, SUM(amount) s FROM orders WHERE status='paid' AND paid_at >= ? GROUP BY currency", [$from]) as $r) $byCur[] = ['moneda' => (string)$r['c'], 'n' => (int)$r['n'], 'monto' => (float)$r['s']];
$orders = ['pagados' => (int)$sales['n'], 'pendientes' => (int)$one("SELECT COUNT(*) FROM orders WHERE status='pending' AND created_at >= ?", [$from]),
    'fallidos' => (int)$one("SELECT COUNT(*) FROM orders WHERE status NOT IN ('paid','pending') AND created_at >= ?", [$from])];
$lastOrders = [];
foreach ($q("SELECT product_title t, amount a, currency c, status s, method m, created_at ts FROM orders ORDER BY id DESC LIMIT 8") as $r) $lastOrders[] = ['producto' => (string)$r['t'], 'monto' => (float)$r['a'], 'moneda' => (string)$r['c'], 'estado' => (string)$r['s'], 'metodo' => (string)$r['m'], 'fecha' => (string)$r['ts']];

$prod = [];
$add = function (string $name, string $col, float $v) use (&$prod) { $name = trim($name); if ($name === '') return; $prod[$name] ??= ['seen' => 0, 'det' => 0, 'buy' => 0, 'wa' => 0, 'demo' => 0, 'sold' => 0, 'rev' => 0]; $prod[$name][$col] += $v; };
foreach ($q("SELECT t, page, n, COUNT(*) c FROM events WHERE ts >= ? AND n <> '' AND (t IN ('seen','buy','wa','demo') OR (t='pv' AND page='detalle')) GROUP BY t, page, n", [$from]) as $r) {
    if ($r['t'] === 'wa' && in_array($r['n'], ['inicio', 'detalle', 'legales', 'otra'], true)) continue;
    $add((string)$r['n'], ['seen' => 'seen', 'buy' => 'buy', 'wa' => 'wa', 'demo' => 'demo', 'pv' => 'det'][$r['t']], (float)$r['c']);
}
foreach ($q("SELECT product_title n, COUNT(*) c, SUM(amount) s FROM orders WHERE status='paid' AND paid_at >= ? GROUP BY product_title", [$from]) as $r) { $add((string)$r['n'], 'sold', (float)$r['c']); $add((string)$r['n'], 'rev', (float)$r['s']); }
$score = fn($p) => $p['det'] * 3 + $p['seen'] + $p['wa'] * 4 + $p['demo'] * 2 + $p['buy'] * 6 + $p['sold'] * 10;
uasort($prod, fn($a, $b) => $score($b) <=> $score($a));
$productos = [];
foreach (array_slice($prod, 0, 15, true) as $n => $p) $productos[] = ['nombre' => $n] + array_map(fn($x) => is_float($x) && floor($x) == $x ? (int)$x : $x, $p);

$tabNames = ['agendas' => 'Agendas e imprimibles', 'apps' => 'Apps web', 'landings' => 'Landings', 'micro' => 'Micro apps'];
$tabs = array_fill_keys(array_values($tabNames), 0);
foreach ($q("SELECT n, COUNT(*) c FROM events WHERE t='tab' AND ts >= ? GROUP BY n", [$from]) as $r) if (isset($tabNames[$r['n']])) $tabs[$tabNames[$r['n']]] = (int)$r['c'];
arsort($tabs);
$src = [];
foreach ($q("SELECT src, COUNT(DISTINCT sid) c FROM events WHERE ts >= ? GROUP BY src", [$from]) as $r) { $l = source_label((string)$r['src']); $src[$l] = ($src[$l] ?? 0) + (int)$r['c']; }
arsort($src);
$origen = []; foreach (array_slice($src, 0, 8, true) as $k => $v) $origen[] = ['origen' => $k, 'sesiones' => $v];
$dev = ['celular' => 0, 'compu' => 0];
foreach ($q("SELECT dev, COUNT(DISTINCT sid) c FROM events WHERE ts >= ? GROUP BY dev", [$from]) as $r) $dev[$r['dev'] === 'm' ? 'celular' : 'compu'] += (int)$r['c'];
$serie = [];
for ($i = $d - 1; $i >= 0; $i--) $serie[date('Y-m-d', strtotime("-$i days"))] = 0;
foreach ($q("SELECT DATE(ts) k, COUNT(DISTINCT vid) v FROM events WHERE ts >= ? GROUP BY k", [$from]) as $r) if (isset($serie[$r['k']])) $serie[$r['k']] = (int)$r['v'];
$serieOut = []; foreach ($serie as $k => $v) $serieOut[] = ['dia' => $k, 'visitantes' => $v];
$hasData = $sessions > 0;
$prevN = (int)$salesPrev['n'];
out(200, [
    'ok' => true, 'generado' => date('c'), 'dias' => $d, 'tiene_datos' => $hasData,
    'visitantes' => (int)$visitors, 'visitantes_prev' => (int)$visitorsPrev, 'visitantes_hoy' => (int)$visitorsHoy, 'sesiones' => (int)$sessions,
    'paginas_vistas' => (int)$pageviews, 'clicks' => (int)$clicks, 'clicks_whatsapp' => (int)$wa,
    'embudo' => ['vieron_producto' => (int)$sSeen, 'abrieron_detalle' => (int)$sDet, 'tocaron_comprar' => (int)$sBuy, 'compraron' => (int)$sales['n']],
    'ventas' => ['n' => (int)$sales['n'], 'n_prev' => $prevN, 'monto' => (float)$sales['s'], 'por_moneda' => $byCur],
    'conv_pct' => $sessions > 0 ? round((int)$sales['n'] / $sessions * 100, 2) : null,
    'pedidos' => $orders, 'ultimos_pedidos' => $lastOrders,
    'productos' => $productos, 'pestanas' => $tabs, 'origen' => $origen, 'dispositivos' => $dev, 'por_dia' => $serieOut,
]);
