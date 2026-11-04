<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

/** Guarda una imagen subida (jpg/png/webp), la achica a 900px de ancho y la convierte a WebP si se puede. Devuelve el nombre de archivo o lanza excepción. */
function store_upload(array $f, string $prefix): string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Error al subir la imagen.');
    if (($f['size'] ?? 0) > MAX_UPLOAD_BYTES) throw new RuntimeException('La imagen supera los 8 MB.');
    $info = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG=>'jpg', IMAGETYPE_PNG=>'png', IMAGETYPE_WEBP=>'webp'];
    if (!$info || !isset($types[$info[2]])) throw new RuntimeException('Formato no válido: subí JPG, PNG o WebP.');
    ensure_dirs();
    $name = $prefix . '-' . bin2hex(random_bytes(5));
    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $src = @imagecreatefromstring((string)file_get_contents($f['tmp_name']));
        if ($src) {
            [$w, $hh] = [imagesx($src), imagesy($src)];
            $tw = min(900, $w); $th = (int)round($hh * $tw / $w);
            $dst = imagecreatetruecolor($tw, $th);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $hh);
            $out = UPLOAD_DIR . "/$name.webp";
            imagewebp($dst, $out, 82);
            imagedestroy($src); imagedestroy($dst);
            return "$name.webp";
        }
    }
    $ext = $types[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . "/$name.$ext")) throw new RuntimeException('No se pudo guardar la imagen.');
    return "$name.$ext";
}
function image_ratio(string $file): string {
    $i = @getimagesize(UPLOAD_DIR . '/' . basename($file));
    return $i ? $i[0] . '/' . $i[1] : '3/4';
}
function delete_upload(string $file): void {
    $p = UPLOAD_DIR . '/' . basename($file);
    if (is_file($p)) @unlink($p);
}
