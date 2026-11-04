<?php
declare(strict_types=1);
/** Lee config.php (que NO está en GitHub). Uso: cfg('db'), cfg('mp.access_token', '') */
function cfg(string $path, $default = null) {
    static $c = null;
    if ($c === null) {
        $f = __DIR__ . '/../config.php';
        $c = is_file($f) ? (require $f) : [];
        if (!is_array($c)) $c = [];
    }
    $v = $c;
    foreach (explode('.', $path) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) return $default;
        $v = $v[$k];
    }
    return $v;
}
function config_exists(): bool { return is_file(__DIR__ . '/../config.php'); }
