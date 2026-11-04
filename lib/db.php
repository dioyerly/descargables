<?php
declare(strict_types=1);

/** Conexión PDO a MySQL, o null si todavía no está configurada o falla. */
function db(): ?PDO {
    static $pdo = false;
    if ($pdo !== false) return $pdo;
    $pdo = null;
    $c = cfg('db');
    if (!is_array($c) || empty($c['pass']) || $c['pass'] === 'PONER_AQUI') { $GLOBALS['db_error'] = 'Falta completar config.php (datos de MySQL).'; return null; }
    try {
        $pdo = new PDO(
            'mysql:host=' . $c['host'] . ';dbname=' . $c['name'] . ';charset=utf8mb4',
            (string)$c['user'], (string)$c['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
        );
    } catch (Throwable $e) {
        error_log('DB: ' . $e->getMessage());
        $GLOBALS['db_error'] = $e->getMessage();
        $pdo = null;
    }
    return $pdo;
}

/** true si hay conexión y las tablas ya fueron creadas */
function db_ready(): bool {
    static $ok = null;
    if ($ok !== null) return $ok;
    $pdo = db();
    if (!$pdo) return $ok = false;
    try { $pdo->query('SELECT 1 FROM orders LIMIT 1'); $pdo->query('SELECT 1 FROM products LIMIT 1'); }
    catch (Throwable $e) { return $ok = false; }
    // Actualiza solo la estructura si una versión nueva agregó columnas (no borra nada)
    try { $pdo->query('SELECT landing_url, files FROM products LIMIT 1'); }
    catch (Throwable $e) { try { db_install($pdo); } catch (Throwable $e2) { error_log('DB migrate: ' . $e2->getMessage()); return $ok = false; } }
    return $ok = true;
}

function db_install(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(120) NOT NULL UNIQUE,
        sort_order INT NOT NULL DEFAULT 0,
        visible TINYINT(1) NOT NULL DEFAULT 1,
        type VARCHAR(10) NOT NULL DEFAULT 'print',
        kicker VARCHAR(120) NOT NULL DEFAULT '',
        title VARCHAR(160) NOT NULL,
        theme VARCHAR(20) NOT NULL DEFAULT 'lavanda',
        pages INT NOT NULL DEFAULT 0,
        ar VARCHAR(12) NOT NULL DEFAULT '3/4',
        short_desc TEXT NOT NULL,
        long_desc MEDIUMTEXT NOT NULL,
        meta TEXT NOT NULL,
        feats TEXT NOT NULL,
        images TEXT NOT NULL,
        price DECIMAL(12,2) NOT NULL DEFAULT 0,
        file_name VARCHAR(190) NOT NULL DEFAULT '',
        file_orig VARCHAR(190) NOT NULL DEFAULT '',
        ext_url VARCHAR(500) NOT NULL DEFAULT '',
        landing_url VARCHAR(500) NOT NULL DEFAULT '',
        files MEDIUMTEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $has = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='landing_url'")->fetchColumn();
    if (!(int)$has) $pdo->exec("ALTER TABLE products ADD landing_url VARCHAR(500) NOT NULL DEFAULT '' AFTER ext_url");
    $has = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='files'")->fetchColumn();
    if (!(int)$has) $pdo->exec("ALTER TABLE products ADD files MEDIUMTEXT NULL AFTER landing_url");
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code CHAR(32) NOT NULL UNIQUE,
        product_id INT UNSIGNED NOT NULL,
        product_title VARCHAR(160) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'ARS',
        buyer_email VARCHAR(190) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        mp_preference_id VARCHAR(80) NOT NULL DEFAULT '',
        mp_payment_id VARCHAR(40) NOT NULL DEFAULT '',
        token CHAR(48) NULL UNIQUE,
        downloads INT NOT NULL DEFAULT 0,
        expires_at DATETIME NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        paid_at DATETIME NULL,
        INDEX (status), INDEX (ip, created_at), INDEX (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** Aplica las fichas de seed/fichas/*.json a productos que ya existen (no toca precio, archivo ni visibilidad). Devuelve los títulos actualizados. */
function db_apply_fichas(): array {
    $done = [];
    foreach (glob(APP_ROOT . '/seed/fichas/*.json') ?: [] as $f) {
        $j = json_decode((string)file_get_contents($f), true);
        if (!is_array($j) || empty($j['id'])) continue;
        $p = find_product(load_products(), (string)$j['id']);
        if (!$p) continue;
        foreach (['title','kicker','type','theme','pages','ar','desc','long','meta','feats','images'] as $k) if (array_key_exists($k, $j)) $p[$k] = $j[$k];
        product_update($p);
        $done[] = strip_tags(str_replace('*', '', (string)$p['title']));
    }
    return $done;
}

/** Importa los productos del seed si la tabla está vacía. Devuelve cuántos importó. */
function db_import_seed(PDO $pdo): int {
    if ((int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() > 0) return 0;
    // Si el sitio ya tenía productos cargados (data/products.json del panel anterior), se importan ésos; si no, los de ejemplo.
    $src = is_file(DATA_DIR . '/products.json') ? DATA_DIR . '/products.json' : SEED_FILE;
    if (!is_file($src)) return 0;
    $list = json_decode((string)file_get_contents($src), true);
    if (!is_array($list)) return 0;
    usort($list, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    $n = 0;
    foreach ($list as $i => $p) {
        $p['id'] = (string)($p['id'] ?? slugify((string)($p['title'] ?? 'producto')));
        $p['order'] = ($i + 1) * 10; $p['price'] = 0; $p['long'] = ''; $p['file'] = ''; $p['file_orig'] = ''; $p['ext_url'] = ''; $p['landing'] = safe_url((string)($p['url'] ?? '')); $p['files'] = [];
        product_insert($p); $n++;
    }
    return $n;
}
