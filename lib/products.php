<?php
declare(strict_types=1);
// Productos: MySQL si está listo; si no, el seed (así el sitio no se rompe antes de configurar).

function row_to_product(array $r): array {
    $j = fn($v) => ($d = json_decode((string)$v, true)) && is_array($d) ? $d : [];
    return [
        'id' => $r['slug'], 'order' => (int)$r['sort_order'], 'visible' => (bool)$r['visible'], 'type' => $r['type'],
        'kicker' => $r['kicker'], 'title' => $r['title'], 'theme' => $r['theme'], 'pages' => (int)$r['pages'], 'ar' => $r['ar'],
        'desc' => $r['short_desc'], 'long' => $r['long_desc'], 'meta' => $j($r['meta']), 'feats' => $j($r['feats']), 'images' => $j($r['images']),
        'price' => (float)$r['price'], 'file' => $r['file_name'], 'file_orig' => $r['file_orig'], 'ext_url' => $r['ext_url'], 'landing' => $r['landing_url'] ?? '', 'pk' => (int)$r['id'],
    ];
}

function load_products(): array {
    if (db_ready()) {
        $rows = db()->query('SELECT * FROM products ORDER BY sort_order, id')->fetchAll();
        return array_map('row_to_product', $rows);
    }
    $src = is_file(DATA_DIR . '/products.json') ? DATA_DIR . '/products.json' : SEED_FILE;
    if (!is_file($src)) return [];
    $l = json_decode((string)file_get_contents($src), true);
    $l = is_array($l) ? $l : [];
    usort($l, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    return array_map(fn($p) => $p + ['price' => 0.0, 'long' => '', 'file' => '', 'file_orig' => '', 'ext_url' => '', 'landing' => safe_url((string)($p['url'] ?? '')), 'pk' => 0], $l);
}

function find_product(array $list, string $slug): ?array {
    foreach ($list as $p) if (($p['id'] ?? '') === $slug) return $p;
    return null;
}

function product_params(array $p): array {
    $js = fn($v) => json_encode(array_values($v), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return [
        ':visible' => !empty($p['visible']) ? 1 : 0, ':type' => $p['type'], ':kicker' => $p['kicker'], ':title' => $p['title'],
        ':theme' => $p['theme'], ':pages' => (int)$p['pages'], ':ar' => $p['ar'], ':short_desc' => $p['desc'], ':long_desc' => $p['long'],
        ':meta' => $js($p['meta']), ':feats' => $js($p['feats']), ':images' => $js($p['images']),
        ':price' => round((float)$p['price'], 2), ':file_name' => $p['file'], ':file_orig' => $p['file_orig'], ':ext_url' => $p['ext_url'], ':landing_url' => $p['landing'] ?? '',
    ];
}

function product_insert(array $p): void {
    $st = db()->prepare('INSERT INTO products (slug, sort_order, visible, type, kicker, title, theme, pages, ar, short_desc, long_desc, meta, feats, images, price, file_name, file_orig, ext_url, landing_url)
        VALUES (:slug, :sort_order, :visible, :type, :kicker, :title, :theme, :pages, :ar, :short_desc, :long_desc, :meta, :feats, :images, :price, :file_name, :file_orig, :ext_url, :landing_url)');
    $st->execute(product_params($p) + [':slug' => $p['id'], ':sort_order' => (int)($p['order'] ?? 0)]);
}

function product_update(array $p): void {
    $st = db()->prepare('UPDATE products SET visible=:visible, type=:type, kicker=:kicker, title=:title, theme=:theme, pages=:pages, ar=:ar, short_desc=:short_desc, long_desc=:long_desc,
        meta=:meta, feats=:feats, images=:images, price=:price, file_name=:file_name, file_orig=:file_orig, ext_url=:ext_url, landing_url=:landing_url WHERE slug=:slug');
    $st->execute(product_params($p) + [':slug' => $p['id']]);
}

function product_delete(string $slug): void { $st = db()->prepare('DELETE FROM products WHERE slug=?'); $st->execute([$slug]); }

function products_resequence(array $slugs): void {
    $st = db()->prepare('UPDATE products SET sort_order=? WHERE slug=?');
    foreach (array_values($slugs) as $i => $s) $st->execute([($i + 1) * 10, $s]);
}

function product_next_order(): int { return (int)db()->query('SELECT COALESCE(MAX(sort_order),0)+10 FROM products')->fetchColumn(); }

/** ¿Se puede vender? (precio, archivo o link, y Mercado Pago configurado) */
function product_for_sale(array $p): bool {
    return $p['price'] > 0 && ($p['file'] !== '' || $p['ext_url'] !== '') && db_ready() && mp_configured();
}
function mp_configured(): bool {
    $t = (string)cfg('mp.access_token', '');
    return $t !== '' && $t !== 'PONER_AQUI';
}
function format_price(float $v): string {
    return '$ ' . number_format($v, fmod($v, 1) == 0.0 ? 0 : 2, ',', '.');
}
