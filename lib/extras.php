<?php
declare(strict_types=1);
// Ajustes, cotización del dólar y estadísticas propias (sin servicios externos de analítica).

const OWNER_COOKIE = 'est_owner';

/** Crea lo que falte (tabla de ajustes, de eventos y la columna price_usd). Barato: una consulta por pedido. */
function extras_ready(): bool {
    static $ok = null;
    if ($ok !== null) return $ok;
    if (!db_ready()) return $ok = false;
    $pdo = db();
    try { $pdo->query('SELECT 1 FROM events LIMIT 1'); $pdo->query('SELECT price_usd FROM products LIMIT 1'); return $ok = true; }
    catch (Throwable $e) {}
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (k VARCHAR(60) PRIMARY KEY, v MEDIUMTEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            vid VARCHAR(16) NOT NULL DEFAULT '', sid VARCHAR(16) NOT NULL DEFAULT '',
            t VARCHAR(12) NOT NULL, n VARCHAR(140) NOT NULL DEFAULT '', page VARCHAR(16) NOT NULL DEFAULT '',
            src VARCHAR(60) NOT NULL DEFAULT '', dev CHAR(1) NOT NULL DEFAULT 'd',
            INDEX (ts), INDEX (t, ts), INDEX (sid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        try { $pdo->query('SELECT price_usd FROM products LIMIT 1'); }
        catch (Throwable $e) { $pdo->exec("ALTER TABLE products ADD price_usd DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER price"); }
        return $ok = true;
    } catch (Throwable $e) { error_log('extras_ready: ' . $e->getMessage()); return $ok = false; }
}

function setting(string $k, string $def = ''): string {
    if (!extras_ready()) return $def;
    static $cache = [];
    if (!array_key_exists($k, $cache)) { $st = db()->prepare('SELECT v FROM settings WHERE k=?'); $st->execute([$k]); $v = $st->fetchColumn(); $cache[$k] = $v === false ? null : (string)$v; }
    return $cache[$k] ?? $def;
}
function setting_set(string $k, string $v): void {
    if (!extras_ready()) return;
    db()->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v=VALUES(v)')->execute([$k, $v]);
}

/* ---------- Datos del negocio (legales y contacto) ---------- */
function biz(): array {
    return ['name' => 'EstrategIA', 'email' => 'estrategia.datos@estrategia.site', 'whatsapp' => '+54 9 11 2642-5994', 'wa' => 'https://wa.me/5491126425994', 'country' => 'Argentina', 'site' => 'descargar.estrategia.site'];
}

/* ---------- Dólar ---------- */
function dolar_types(): array { return ['oficial' => 'Oficial (Banco Nación, venta)', 'blue' => 'Blue (venta)', 'tarjeta' => 'Tarjeta (oficial + impuestos)']; }
function dolar_type(): string { $t = setting('dolar_tipo', 'oficial'); return isset(dolar_types()[$t]) ? $t : 'oficial'; }

function http_json(string $url): ?array {
    if (function_exists('curl_init')) {
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_USERAGENT => 'EstrategIA-tienda/1.0', CURLOPT_FOLLOWLOCATION => true]);
        $b = curl_exec($c); $code = (int)curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
        if ($b === false || $code >= 400) return null;
    } else {
        $b = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5, 'header' => "User-Agent: EstrategIA-tienda/1.0\r\n"]]));
        if ($b === false) return null;
    }
    $j = json_decode((string)$b, true);
    return is_array($j) ? $j : null;
}

/** Cotización (venta) del tipo elegido. Se consulta como mucho cada 3 horas; si la consulta falla se usa la última guardada. */
function dolar_rate(bool $force = false): array {
    $tipo = dolar_type();
    $c = json_decode(setting('dolar_cache', '{}'), true) ?: [];
    $fresh = ($c['tipo'] ?? '') === $tipo && !empty($c['valor']) && time() - (int)($c['checked'] ?? 0) < 3 * 3600;
    if ($fresh && !$force) return $c + ['ok' => true];
    $valor = 0.0; $fecha = '';
    $j = http_json('https://dolarapi.com/v1/dolares/' . $tipo);
    if ($j && (float)($j['venta'] ?? 0) > 0) { $valor = (float)$j['venta']; $fecha = (string)($j['fechaActualizacion'] ?? ''); }
    elseif ($tipo !== 'tarjeta' && ($b = http_json('https://api.bluelytics.com.ar/v2/latest')) && (float)($b[$tipo]['value_sell'] ?? 0) > 0) { $valor = (float)$b[$tipo]['value_sell']; $fecha = (string)($b['last_update'] ?? ''); }
    if ($valor > 0) {
        $c = ['tipo' => $tipo, 'valor' => $valor, 'fecha' => $fecha ?: date('c'), 'checked' => time()];
        setting_set('dolar_cache', json_encode($c));
        return $c + ['ok' => true];
    }
    // sin conexión: la última que tengamos (aunque sea de otro tipo, se avisa)
    return $c ? $c + ['ok' => false] : ['tipo' => $tipo, 'valor' => 0.0, 'fecha' => '', 'checked' => 0, 'ok' => false];
}

/** USD → ARS, redondeado hacia arriba a la centena (precios prolijos). */
function usd_to_ars(float $usd, ?float $rate = null): float {
    $rate = $rate ?? (float)(dolar_rate()['valor'] ?? 0);
    if ($usd <= 0 || $rate <= 0) return 0.0;
    return (float)(ceil($usd * $rate / 100) * 100);
}

/* ---------- Estadísticas ---------- */
function is_owner_device(): bool { return ($_COOKIE[OWNER_COOKIE] ?? '') === '1'; }
/** Huella de la conexión (IP con sal, nunca la IP en claro) */
function ip_hash(): string { return substr(hash('sha256', 'est|' . ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . (string)cfg('db.name', '')), 0, 20); }
function owner_ips(): array { $a = json_decode(setting('owner_ips', '{}'), true); return is_array($a) ? $a : []; }
function owner_vids(): array { $a = json_decode(setting('owner_vids', '[]'), true); return is_array($a) ? $a : []; }
/** Recuerda la conexión del dueño 45 días (así tampoco cuentan el celular o el navegador de Instagram en el mismo wifi) */
function remember_owner_ip(): void {
    if (!extras_ready()) return;
    $a = array_filter(owner_ips(), fn($t) => time() - (int)$t < 45 * 86400);
    $a[ip_hash()] = time(); arsort($a); setting_set('owner_ips', json_encode(array_slice($a, 0, 30, true)));
}
function add_owner_vid(string $vid): void {
    if (!extras_ready() || !preg_match('/^[a-z0-9]{6,16}$/', $vid)) return;
    $a = owner_vids(); if (!in_array($vid, $a, true)) $a[] = $vid; setting_set('owner_vids', json_encode(array_slice($a, -200)));
}
/** ¿Este pedido viene del dueño? (cookie, conexión reciente o aparato marcado como suyo) */
function is_owner_request(string $vid = ''): bool {
    if (is_owner_device()) return true;
    $t = owner_ips()[ip_hash()] ?? 0; if ($t && time() - (int)$t < 45 * 86400) return true;
    return $vid !== '' && in_array($vid, owner_vids(), true);
}
function mark_owner_device(bool $on): void {
    if ($on) remember_owner_ip();
    elseif (extras_ready()) { $a = owner_ips(); unset($a[ip_hash()]); setting_set('owner_ips', json_encode($a)); }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(OWNER_COOKIE, $on ? '1' : '', ['expires' => $on ? time() + 3 * 365 * 86400 : time() - 3600, 'path' => '/', 'secure' => $https, 'httponly' => false, 'samesite' => 'Lax']);
    $_COOKIE[OWNER_COOKIE] = $on ? '1' : '';
}

/** Agrupa el origen en nombres entendibles */
function source_label(string $s): string {
    $s = strtolower(trim($s));
    if ($s === '' || $s === 'directo') return 'Directo / WhatsApp / favoritos';
    $map = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'fb' => 'Facebook', 'google' => 'Google', 'whatsapp' => 'WhatsApp', 'wa.me' => 'WhatsApp', 'tiktok' => 'TikTok',
        'youtube' => 'YouTube', 'youtu.be' => 'YouTube', 'linkedin' => 'LinkedIn', 'lnkd' => 'LinkedIn', 'pinterest' => 'Pinterest', 'pin.it' => 'Pinterest', 't.co' => 'X / Twitter', 'twitter' => 'X / Twitter',
        'x.com' => 'X / Twitter', 'bing' => 'Bing', 'duckduckgo' => 'DuckDuckGo', 'yahoo' => 'Yahoo', 'etsy' => 'Etsy', 'gumroad' => 'Gumroad', 'mercadopago' => 'Mercado Pago', 'estrategia.site' => 'Tus otras páginas (estrategia.site)', 'threads' => 'Threads'];
    foreach ($map as $k => $v) if (str_contains($s, $k)) return $v;
    return $s;
}

function is_bot_ua(string $ua): bool {
    return $ua === '' || (bool)preg_match('~bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|headless|lighthouse|pingdom|uptime|curl|wget|python|monitor~i', $ua);
}
