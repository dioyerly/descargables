<?php
declare(strict_types=1);
// Productos gratis con código: la persona pone el código y su email, y recibe el mismo enlace de descarga que una compra.
// Cada email puede canjear cada código una sola vez. Todo se guarda en la base (nada de códigos en GitHub).

/** Crea las tablas de códigos y la marca «gratis» en productos (solo agrega; no borra nada). */
function gratis_ready(): bool {
    static $ok = null;
    if ($ok !== null) return $ok;
    if (!db_ready()) return $ok = false;
    $pdo = db();
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS codigos_gratis (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(40) NOT NULL UNIQUE,
            product_id INT UNSIGNED NOT NULL,
            max_usos INT UNSIGNED NOT NULL DEFAULT 100,
            usos INT UNSIGNED NOT NULL DEFAULT 0,
            vence DATETIME NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS codigos_usos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            codigo_id INT UNSIGNED NOT NULL,
            email VARCHAR(190) NOT NULL,
            order_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_codigo_email (codigo_id, email),
            INDEX (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $has = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='gratis'")->fetchColumn();
        if (!$has) $pdo->exec("ALTER TABLE products ADD gratis TINYINT(1) NOT NULL DEFAULT 0");
        return $ok = true;
    } catch (Throwable $e) {
        error_log('gratis_ready: ' . $e->getMessage());
        return $ok = false;
    }
}

/** Productos marcados como gratis, visibles y con foto (para la pestaña GRATIS). */
function gratis_products(): array {
    if (!gratis_ready()) return [];
    $rows = db()->query('SELECT * FROM products WHERE gratis=1 AND visible=1 ORDER BY sort_order, id')->fetchAll();
    return array_values(array_filter(array_map('row_to_product', $rows), fn($p) => !empty($p['images'])));
}

/** Límite de intentos por IP (contra adivinar códigos): 15 cada 15 minutos. Devuelve segundos de espera o 0. */
function gratis_blocked(): int {
    $f = DATA_DIR . '/gratis-intentos.json';
    $all = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'x';
    $r = $all[$ip] ?? ['n' => 0, 't' => 0];
    if ($r['n'] >= 15 && time() - $r['t'] < 900) return 900 - (time() - $r['t']);
    return 0;
}
function gratis_attempt(): void {
    ensure_dirs();
    $f = DATA_DIR . '/gratis-intentos.json';
    $all = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'x';
    $r = $all[$ip] ?? ['n' => 0, 't' => 0];
    if (time() - $r['t'] > 900) $r['n'] = 0;
    $r['n']++; $r['t'] = time();
    $all[$ip] = $r;
    file_put_contents($f, json_encode($all), LOCK_EX);
}

/**
 * Canjea un código por un email: crea el pedido (pagado, sin monto), guarda el uso y manda el mail con el enlace.
 * Devuelve ['ok' => bool, 'msg' => string, 'order' => array|null].
 */
function gratis_redeem(string $codigo, string $email, string $ip): array {
    $fail = fn(string $m) => ['ok' => false, 'msg' => $m, 'order' => null];
    if (!gratis_ready()) return $fail('La descarga gratis no está disponible por ahora.');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // FOR UPDATE: dos personas no pueden pasar el último uso a la vez
        $st = $pdo->prepare('SELECT c.*, p.title AS ptitle, p.visible AS pvisible FROM codigos_gratis c JOIN products p ON p.id=c.product_id WHERE c.codigo=? FOR UPDATE');
        $st->execute([$codigo]);
        $c = $st->fetch();
        if (!$c || !(int)$c['activo'] || !(int)$c['pvisible']) { $pdo->rollBack(); return $fail('Ese código no es válido.'); }
        if ($c['vence'] !== null && strtotime((string)$c['vence']) < time()) { $pdo->rollBack(); return $fail('Este código ya venció.'); }
        if ((int)$c['usos'] >= (int)$c['max_usos']) { $pdo->rollBack(); return $fail('Se agotaron las descargas de este código.'); }
        $st = $pdo->prepare('SELECT id FROM codigos_usos WHERE codigo_id=? AND email=?');
        $st->execute([$c['id'], $email]);
        if ($st->fetch()) { $pdo->rollBack(); return $fail('Este email ya descargó este producto. Cada email puede descargarlo una sola vez.'); }

        $orderCode = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(24));
        $days = max(1, (int)cfg('download.days', 7));
        $pdo->prepare("INSERT INTO orders (code, product_id, product_title, amount, currency, buyer_email, status, token, expires_at, ip, method, paid_at)
            VALUES (?, ?, ?, 0, 'ARS', ?, 'paid', ?, DATE_ADD(NOW(), INTERVAL $days DAY), ?, 'gratis', NOW())")
            ->execute([$orderCode, (int)$c['product_id'], (string)$c['ptitle'], $email, $token, substr($ip, 0, 45)]);
        $orderId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO codigos_usos (codigo_id, email, order_id) VALUES (?, ?, ?)')->execute([$c['id'], $email, $orderId]);
        $pdo->prepare('UPDATE codigos_gratis SET usos=usos+1 WHERE id=?')->execute([$c['id']]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ((string)$e->getCode() === '23000') return $fail('Este email ya descargó este producto. Cada email puede descargarlo una sola vez.');
        error_log('gratis_redeem: ' . $e->getMessage());
        return $fail('No pudimos procesar el código. Probá de nuevo en unos minutos.');
    }
    $order = order_by_code($orderCode);
    if ($order) order_send_mail($order);
    return ['ok' => true, 'msg' => '', 'order' => $order];
}
