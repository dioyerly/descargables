<?php
declare(strict_types=1);
// EstrategIA Descargables — núcleo compartido
const APP_ROOT = __DIR__ . '/..';
const DATA_DIR = APP_ROOT . '/data';
const UPLOAD_DIR = APP_ROOT . '/uploads';
const PRIVATE_DIR = APP_ROOT . '/private/files';
const SEED_FILE = APP_ROOT . '/seed/products.json';
const AUTH_FILE = DATA_DIR . '/auth.json';
const MAX_IMAGES = 6;
const MAX_UPLOAD_BYTES = 8 * 1024 * 1024;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/mp.php';

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** *palabra* → cursiva serif; el resto escapado */
function title_html(string $t): string {
    $e = h($t);
    return preg_replace('/\*(.+?)\*/u', '<span class="serif">$1</span>', $e) ?? $e;
}

function themes(): array {
    return [
        'noche'   => ['label'=>'Noche dorada','bg'=>'#1d1b45','glow'=>'rgba(214,175,92,.28)','fg'=>'#f5ecd8','fg2'=>'#cfc6e8','tag'=>'#e2b85c','chip'=>'rgba(255,255,255,.1)'],
        'rosa'    => ['label'=>'Rosa','bg'=>'#fde7ee','glow'=>'rgba(255,117,151,.32)','fg'=>'#3b1d2a','fg2'=>'#7a4a5e','tag'=>'#e75a88','chip'=>'rgba(231,90,136,.12)'],
        'lavanda' => ['label'=>'Lavanda','bg'=>'#ece6f4','glow'=>'rgba(160,120,200,.32)','fg'=>'#2d2240','fg2'=>'#5f5272','tag'=>'#8a5fb0','chip'=>'rgba(138,95,176,.12)'],
        'crema'   => ['label'=>'Crema','bg'=>'#f6efe2','glow'=>'rgba(240,180,140,.35)','fg'=>'#2b2620','fg2'=>'#6d6457','tag'=>'#b4704f','chip'=>'rgba(180,112,79,.12)'],
        'salvia'  => ['label'=>'Salvia','bg'=>'#e6efe6','glow'=>'rgba(120,170,130,.32)','fg'=>'#1f2d22','fg2'=>'#566a5a','tag'=>'#4f8a5f','chip'=>'rgba(79,138,95,.12)'],
        'cielo'   => ['label'=>'Cielo','bg'=>'#e3eef7','glow'=>'rgba(110,160,210,.32)','fg'=>'#1b2a3a','fg2'=>'#526679','tag'=>'#3f7fbf','chip'=>'rgba(63,127,191,.12)'],
        'grafito' => ['label'=>'Grafito','bg'=>'#1f2226','glow'=>'rgba(150,170,190,.22)','fg'=>'#eef1f4','fg2'=>'#b5bcc4','tag'=>'#a7c4e0','chip'=>'rgba(255,255,255,.1)'],
    ];
}
function theme_style(string $key): string {
    $all = themes(); $t = $all[$key] ?? reset($all);
    return "--bg:{$t['bg']};--bgsolid:{$t['bg']};--glow:{$t['glow']};--fg:{$t['fg']};--fg2:{$t['fg2']};--tag:{$t['tag']};--chip:{$t['chip']}";
}
function types(): array { return ['print'=>'Imprimible','digital'=>'Digital (tablet)','both'=>'Imprimible y digital']; }

function ensure_dirs(): void {
    foreach ([DATA_DIR, UPLOAD_DIR, PRIVATE_DIR] as $d) if (!is_dir($d)) @mkdir($d, 0755, true);
}

function slugify(string $s): string {
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $s) ?? '');
    return trim($s, '-') ?: 'producto';
}

function safe_url(string $u): string {
    $u = trim($u);
    if ($u === '') return '';
    if (!preg_match('~^https?://~i', $u)) return '';
    return filter_var($u, FILTER_VALIDATE_URL) ? $u : '';
}

/** Carpeta base de la web (por si se instala en una subcarpeta) */
function base_path(): string {
    $d = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    foreach (['/admin'] as $sub) if (substr($d, -strlen($sub)) === $sub) $d = substr($d, 0, -strlen($sub));
    return $d;
}
function site_url(string $path = ''): string {
    $u = rtrim((string)cfg('site_url', ''), '/');
    if ($u === '') {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $u = ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
    }
    return $u . '/' . ltrim($path, '/');
}
function product_url(string $slug): string { return base_path() . '/producto/' . rawurlencode($slug); }
