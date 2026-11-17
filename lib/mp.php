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
        if ($order['status'] !== 'paid') $order = order_mark_paid($code, $pid) ?? $order;
    } elseif (in_array($status, ['rejected', 'cancelled'], true)) {
        $order = order_fail($code, mp_reason($pay), $pid) ?? $order;
    } elseif (in_array($status, ['refunded', 'charged_back'], true)) {
        $order = order_refund($code, $status === 'charged_back' ? 'Contracargo' : 'Devuelto') ?? $order;
    }
    return $order;
}

/** Marca el pedido como pagado, genera el enlace y manda el mail. Idempotente. */
function order_mark_paid(string $code, string $payId): ?array {
    $days = max(1, (int)cfg('download.days', 7));
    $tok = bin2hex(random_bytes(24));
    $up = db()->prepare("UPDATE orders SET status='paid', fail_reason='', token=?, expires_at=DATE_ADD(NOW(), INTERVAL $days DAY), paid_at=NOW(), mp_payment_id=IF(method='mp', ?, mp_payment_id) WHERE code=? AND status<>'paid'");
    $up->execute([$tok, $payId, $code]);
    $order = order_by_code($code);
    if ($order && $up->rowCount() > 0) order_send_mail($order);
    return $order;
}

/** Registra un pago fallido y avisa a la dueña (una vez por intento). Nunca pisa un pedido ya pagado. */
function order_fail(string $code, string $reason, string $payId): ?array {
    $reason = mb_substr($reason, 0, 190);
    $up = db()->prepare("UPDATE orders SET status='rejected', fail_reason=?, mp_payment_id=IF(method='mp', ?, mp_payment_id) WHERE code=? AND status NOT IN ('paid','refunded') AND (status<>'rejected' OR fail_reason<>? OR (method='mp' AND mp_payment_id<>?))");
    $up->execute([$reason, $payId, $code, $reason, $payId]);
    $order = order_by_code($code);
    if ($order && $up->rowCount() > 0) order_notify_failure($order, $reason);
    return $order;
}

function order_refund(string $code, string $why): ?array {
    $up = db()->prepare("UPDATE orders SET status='refunded', token=NULL, fail_reason=? WHERE code=? AND status<>'refunded'");
    $up->execute([mb_substr($why, 0, 190), $code]);
    $order = order_by_code($code);
    if ($order && $up->rowCount() > 0) notify_owner('Pago devuelto o disputado: ' . $order['product_title'], "Un pago quedó como: $why\r\n\r\n" . order_summary($order) . "\r\nSe anuló el enlace de descarga.");
    return $order;
}

/** Motivo legible del rechazo de Mercado Pago */
function mp_reason(array $pay): string {
    $d = (string)($pay['status_detail'] ?? '');
    $map = [
        'cc_rejected_insufficient_amount' => 'Fondos insuficientes', 'cc_rejected_bad_filled_security_code' => 'Código de seguridad incorrecto',
        'cc_rejected_bad_filled_date' => 'Fecha de vencimiento incorrecta', 'cc_rejected_bad_filled_other' => 'Datos de la tarjeta incorrectos',
        'cc_rejected_high_risk' => 'Rechazado por riesgo de fraude', 'cc_rejected_blacklist' => 'Tarjeta en lista de bloqueo',
        'cc_rejected_call_for_authorize' => 'El banco pide autorizar el pago', 'cc_rejected_card_disabled' => 'Tarjeta deshabilitada',
        'cc_rejected_max_attempts' => 'Demasiados intentos', 'cc_rejected_duplicated_payment' => 'Pago duplicado',
        'cc_rejected_invalid_installments' => 'Cuotas no válidas', 'cc_rejected_other_reason' => 'El banco rechazó la tarjeta (posible tarjeta del exterior)',
        'cc_rejected_card_type_not_allowed' => 'Tipo de tarjeta no permitido', 'rejected_by_bank' => 'Rechazado por el banco',
        'cancelled_by_user' => 'Cancelado por el comprador', 'expired' => 'Venció sin pagar',
    ];
    $m = $pay['payment_method_id'] ?? '';
    return 'Mercado Pago: ' . ($map[$d] ?? ($d !== '' ? $d : 'rechazado')) . ($m !== '' ? " ($m)" : '');
}

function order_summary(array $o): string {
    return "Producto: {$o['product_title']}\r\nMonto: " . ($o['currency'] === 'USD' ? 'USD ' . number_format((float)$o['amount'], 2) : format_price((float)$o['amount'])) .
        "\r\nMedio: " . ($o['method'] === 'pp' ? 'PayPal' : 'Mercado Pago') . "\r\nEmail del comprador: {$o['buyer_email']}\r\nFecha: {$o['created_at']}\r\n";
}

/** Mail a la dueña (config: notify_email). Si no está configurado, no hace nada: igual queda en el panel. */
function notify_owner(string $subject, string $body): void {
    $to = trim((string)cfg('notify_email', ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return;
    $from = (string)cfg('mail_from', 'no-responder@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $headers = "From: Avisos Descargables <$from>\r\nContent-Type: text/plain; charset=UTF-8\r\nMIME-Version: 1.0";
    @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body . "\r\nVer pedidos: " . site_url('admin/pedidos.php') . "\r\n", $headers);
}

/** Avisa de un pago fallido y, si PayPal está disponible, le manda al comprador cómo pagar desde el exterior. */
function order_notify_failure(array $o, string $reason): void {
    notify_owner('Pago NO completado: ' . $o['product_title'], "Alguien intentó comprar y el pago falló.\r\nMotivo: $reason\r\n\r\n" . order_summary($o) . "\r\nSi querés, escribile al comprador y ofrecele pagar con PayPal.");
    if (pp_configured() && $o['method'] === 'mp' && !empty($o['buyer_email'])) {
        $st = db()->prepare('SELECT slug FROM products WHERE id=?'); $st->execute([$o['product_id']]);
        $slug = (string)$st->fetchColumn();
        if ($slug === '') return;
        $from = (string)cfg('mail_from', 'no-responder@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $body = "Hola, vimos que tu pago de \"{$o['product_title']}\" no se pudo completar.\r\n\r\nSi tu tarjeta es de otro país, podés pagar con PayPal (tarjeta internacional o cuenta PayPal) desde acá:\r\n" .
            site_url('producto/' . rawurlencode($slug)) . "#comprar\r\n\r\nSi seguís con problemas, respondé este mail o escribinos por WhatsApp.\r\n\r\nEstrategIA · Descargables";
        @mail($o['buyer_email'], '=?UTF-8?B?' . base64_encode('Tu pago no se completó: ' . $o['product_title']) . '?=', $body, "From: EstrategIA Descargables <$from>\r\nContent-Type: text/plain; charset=UTF-8\r\nMIME-Version: 1.0");
    }
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
