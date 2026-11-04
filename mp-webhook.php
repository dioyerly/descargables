<?php
declare(strict_types=1);
// Aviso automático (webhook) de Mercado Pago
require __DIR__ . '/lib/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
if (!db_ready() || !mp_configured()) { http_response_code(503); exit('no configurado'); }
$raw = (string)file_get_contents('php://input');
$j = json_decode($raw, true) ?: [];
$type = (string)($j['type'] ?? $_GET['type'] ?? $_GET['topic'] ?? '');
$id = (string)($j['data']['id'] ?? $_GET['data_id'] ?? $_GET['data.id'] ?? $_GET['id'] ?? '');
if ($type !== 'payment' || $id === '') { http_response_code(200); exit('ignorado'); }
if (!mp_signature_ok($id)) { http_response_code(401); exit('firma inválida'); }
try { mp_sync_payment_id($id); http_response_code(200); echo 'ok'; }
catch (Throwable $e) { error_log('webhook: ' . $e->getMessage()); http_response_code(500); echo 'reintentar'; }
