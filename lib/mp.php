<?php
declare(strict_types=1);
// Mercado Pago (Checkout Pro) + pedidos + entrega

function mp_api(string $method, string $path, ?array $body = null, ?string $idem = null): array {
    $ch = curl_init(rtrim((string)cfg('mp.api_base', 'https://api.mercadopago.com'), '/') . $path);
    $hd = ['Authorization: Bearer ' . cfg('mp.access_token', ''), 'Content-Type: application/json'];
    if ($idem) $hd[] = 'X-Idempotency-Key: ' . $idem;
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $hd, CURLOPT_TIMEOUT => 25]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($res === false) throw new RuntimeException('Mercado Pago no respondió: ' . $err);
    $j = json_decode((string)$res, true);
    if ($code >= 400) { error_log("MP $method $path -> $code $res"); throw new RuntimeException('Mercado Pago devolvió un error (' . $code . ').'); }
    return is_array($j) ? $j : [];
}

/** Crea el pedido pendiente y la preferencia de pago. Devuelve la URL de pago de Mercado Pago. */
function mp_start_checkout(array $p, string $email): string {
    $pdo = db();
    $code = bin2hex(random_bytes(16));
    $st = $pdo->prepare('INSERT INTO orders (code, product_id, product_title, amount, currency, buyer_email, status, ip) VALUES (?,?,?,?,?,?,?,?)');
    $st->execute([$code, $p['pk'], strip_tags(str_replace('*', '', $p['title'])), $p['price'], 'ARS', $email, 'pending', substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45)]);
    $back = site_url('gracias.php?o=' . $code);
    $pref = mp_api('POST', '/checkout/preferences', [
        'items' => [[
            'id' => $p['id'], 'title' => strip_tags(str_replace('*', '', $p['title'])), 'description' => 'Producto digital descargable',
            'quantity' => 1, 'currency_id' => 'ARS', 'unit_price' => (float)$p['price'],
        ]],
        'payer' => ['email' => $email],
        'external_reference' => $code,
        'back_urls' => ['success' => $back, 'pending' => $back, 'failure' => $back],
        'auto_return' => 'approved',
        'notification_url' => site_url('mp-webhook.php'),
        'statement_descriptor' => 'ESTRATEGIA',
    ], $code);
    if (empty($pref['init_point'])) throw new RuntimeException('No se pudo iniciar el pago.');
    $pdo->prepare('UPDATE orders SET mp_preference_id=? WHERE code=?')->execute([(string)($pref['id'] ?? ''), $code]);
    return (string)$pref['init_point'];
}

function order_by_code(string $code): ?array {
    if (!preg_match('/^[a-f0-9]{32}$/', $code)) return null;
    $st = db()->prepare('SELECT * FROM orders WHERE code=?'); $st->execute([$code]);
    return $st->fetch() ?: null;
}

/** Marca el pedido como pagado si el pago de MP es válido (referencia, estado y monto). Idempotente. */
function mp_apply_payment(array $pay): ?array {
    $code = (string)($pay['external_reference'] ?? '');
    $order = order_by_code($code);
    if (!$order) return null;
    $status = (string)($pay['status'] ?? '');
    $pid = (string)($pay['id'] ?? '');
    if ($status === 'approved') {
        if ((string)($pay['currency_id'] ?? '') !== $order['currency'] || (float)($pay['transaction_amount'] ?? 0) + 0.001 < (float)$order['amount']) {
            error_log("MP pago $pid: monto/moneda no coincide con pedido $code"); return $order;
        }
        if ($order['status'] !== 'paid') {
            $days = max(1, (int)cfg('download.days', 7));
            $tok = bin2hex(random_bytes(24));
            $up = db()->prepare("UPDATE orders SET status='paid', mp_payment_id=?, token=?, expires_at=DATE_ADD(NOW(), INTERVAL $days DAY), paid_at=NOW() WHERE code=? AND status<>'paid'");
            $up->execute([$pid, $tok, $code]);
            $order = order_by_code($code);
            if ($up->rowCount() > 0) order_send_mail($order);
        }
    } elseif (in_array($status, ['rejected', 'cancelled'], true) && $order['status'] === 'pending') {
        db()->prepare("UPDATE orders SET status='rejected', mp_payment_id=? WHERE code=?")->execute([$pid, $code]);
        $order = order_by_code($code);
    } elseif (in_array($status, ['refunded', 'charged_back'], true)) {
        db()->prepare("UPDATE orders SET status='refunded', token=NULL WHERE code=?")->execute([$code]);
        $order = order_by_code($code);
    }
    return $order;
}

function mp_sync_payment_id(string $paymentId): ?array {
    if (!preg_match('/^\d+$/', $paymentId)) return null;
    return mp_apply_payment(mp_api('GET', '/v1/payments/' . $paymentId));
}

/** Busca pagos por referencia (por si el aviso automático tarda) */
function mp_sync_by_reference(string $code): ?array {
    $r = mp_api('GET', '/v1/payments/search?sort=date_created&criteria=desc&external_reference=' . urlencode($code));
    foreach (($r['results'] ?? []) as $pay) if (($pay['status'] ?? '') === 'approved') return mp_apply_payment($pay);
    return order_by_code($code);
}

function download_link(array $order): string { return site_url('descargar.php?t=' . $order['token']); }

function order_send_mail(array $order): void {
    if (empty($order['token'])) return;
    $from = (string)cfg('mail_from', 'no-responder@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $days = (int)cfg('download.days', 7);
    $body = "¡Gracias por tu compra!\r\n\r\nProducto: {$order['product_title']}\r\n\r\nDescargalo acá (el enlace dura $days días):\r\n" . download_link($order) . "\r\n\r\nSi tenés algún problema, respondé por WhatsApp o Instagram @estrategia.datos.\r\n\r\nEstrategIA · Descargables";
    $headers = "From: EstrategIA Descargables <$from>\r\nContent-Type: text/plain; charset=UTF-8\r\nMIME-Version: 1.0";
    @mail($order['buyer_email'], '=?UTF-8?B?' . base64_encode('Tu descarga: ' . $order['product_title']) . '?=', $body, $headers);
}

/** Verifica la firma de los avisos de Mercado Pago (solo si hay clave secreta configurada) */
function mp_signature_ok(string $dataId): bool {
    $secret = (string)cfg('mp.webhook_secret', '');
    if ($secret === '') return true;
    $sig = $_SERVER['HTTP_X_SIGNATURE'] ?? ''; $rid = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
    $ts = ''; $v1 = '';
    foreach (explode(',', $sig) as $part) { [$k, $v] = array_pad(explode('=', trim($part), 2), 2, ''); if ($k === 'ts') $ts = $v; if ($k === 'v1') $v1 = $v; }
    if ($ts === '' || $v1 === '') return false;
    $id = ctype_alnum($dataId) ? strtolower($dataId) : $dataId;
    return hash_equals(hash_hmac('sha256', "id:$id;request-id:$rid;ts:$ts;", $secret), $v1);
}
