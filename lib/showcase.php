<?php
declare(strict_types=1);
// Muestras del inicio: apps web, landings a medida y micro apps a medida (las agendas siguen siendo "productos").

const SHOWCASE_DIR = APP_ROOT . '/muestras';
const SHOWCASE_MAX_IMAGES = 8;

function showcase_sections(): array {
    return [
        'apps'     => ['label' => 'Apps web', 'single' => 'App web', 'hint' => 'Subís el HTML de la demo y se prueba en vivo en un visor (las fotos son opcionales), con descripción y el link a la app.'],
        'landings' => ['label' => 'Landings a medida', 'single' => 'Landing', 'hint' => 'Subís el HTML de la landing y el sitio la muestra en vivo, dentro de un visor.'],
        'micro'    => ['label' => 'Micro apps a medida', 'single' => 'Micro app', 'hint' => 'Solo fotos (y un título corto).'],
    ];
}

function showcase_install(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS showcase (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(120) NOT NULL UNIQUE,
        section VARCHAR(12) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        visible TINYINT(1) NOT NULL DEFAULT 1,
        title VARCHAR(160) NOT NULL,
        descr TEXT NOT NULL,
        link VARCHAR(500) NOT NULL DEFAULT '',
        tags TEXT NOT NULL,
        images TEXT NOT NULL,
        html_file VARCHAR(160) NOT NULL DEFAULT '',
        INDEX (section, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function showcase_ready(): bool {
    static $ok = null;
    if ($ok !== null) return $ok;
    if (!db_ready()) return $ok = false;
    try { db()->query('SELECT 1 FROM showcase LIMIT 1'); }
    catch (Throwable $e) { try { showcase_install(db()); } catch (Throwable $e2) { error_log('showcase: ' . $e2->getMessage()); return $ok = false; } }
    return $ok = true;
}

function showcase_row(array $r): array {
    $j = fn($v) => ($d = json_decode((string)$v, true)) && is_array($d) ? $d : [];
    return ['id' => $r['slug'], 'section' => $r['section'], 'order' => (int)$r['sort_order'], 'visible' => (bool)$r['visible'], 'title' => $r['title'],
        'desc' => $r['descr'], 'link' => $r['link'], 'tags' => $j($r['tags']), 'images' => $j($r['images']), 'html' => $r['html_file']];
}

function showcase_load(?string $section = null, bool $onlyVisible = false): array {
    if (!showcase_ready()) return [];
    $sql = 'SELECT * FROM showcase'; $w = []; $a = [];
    if ($section) { $w[] = 'section = ?'; $a[] = $section; }
    if ($onlyVisible) $w[] = 'visible = 1';
    if ($w) $sql .= ' WHERE ' . implode(' AND ', $w);
    $st = db()->prepare($sql . ' ORDER BY sort_order, id'); $st->execute($a);
    return array_map('showcase_row', $st->fetchAll());
}

function showcase_find(string $slug): ?array {
    if (!showcase_ready()) return null;
    $st = db()->prepare('SELECT * FROM showcase WHERE slug = ?'); $st->execute([$slug]);
    $r = $st->fetch(); return $r ? showcase_row($r) : null;
}

function showcase_next_order(string $section): int {
    $st = db()->prepare('SELECT COALESCE(MAX(sort_order),0)+10 FROM showcase WHERE section = ?'); $st->execute([$section]);
    return (int)$st->fetchColumn();
}

function showcase_save(array $s, bool $isNew): void {
    $js = fn($v) => json_encode(array_values($v), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $p = [':slug' => $s['id'], ':section' => $s['section'], ':sort_order' => $s['order'], ':visible' => !empty($s['visible']) ? 1 : 0, ':title' => $s['title'],
        ':descr' => $s['desc'], ':link' => $s['link'], ':tags' => $js($s['tags']), ':images' => $js($s['images']), ':html_file' => $s['html']];
    if ($isNew) db()->prepare('INSERT INTO showcase (slug, section, sort_order, visible, title, descr, link, tags, images, html_file) VALUES (:slug, :section, :sort_order, :visible, :title, :descr, :link, :tags, :images, :html_file)')->execute($p);
    else db()->prepare('UPDATE showcase SET section=:section, sort_order=:sort_order, visible=:visible, title=:title, descr=:descr, link=:link, tags=:tags, images=:images, html_file=:html_file WHERE slug=:slug')->execute($p);
}

function showcase_delete(array $s): void {
    foreach ($s['images'] as $im) delete_upload($im);
    if ($s['html'] !== '') @unlink(SHOWCASE_DIR . '/' . basename($s['html']));
    db()->prepare('DELETE FROM showcase WHERE slug = ?')->execute([$s['id']]);
}

/** Guarda el HTML de una landing en /muestras con nombre aleatorio. Devuelve el nombre o lanza excepción. */
function showcase_store_html(array $f, string $prefix): string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('no se pudo subir (supera el límite del hosting o hubo un error).');
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['html', 'htm'], true)) throw new RuntimeException('tiene que ser un archivo .html');
    if (($f['size'] ?? 0) > 12 * 1024 * 1024) throw new RuntimeException('supera los 12 MB.');
    if (!is_dir(SHOWCASE_DIR)) @mkdir(SHOWCASE_DIR, 0755, true);
    $name = $prefix . '-' . bin2hex(random_bytes(4)) . '.html';
    if (!move_uploaded_file($f['tmp_name'], SHOWCASE_DIR . '/' . $name)) throw new RuntimeException('no se pudo guardar en el servidor.');
    return $name;
}
