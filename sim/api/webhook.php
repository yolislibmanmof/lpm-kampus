<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/core/ApiAuth.php';

$startMs = (int)(microtime(true) * 1000);
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Webhook-Source, X-Webhook-Signature');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiAuth::log('/api/webhook', 'GET', null, 405, 'Method not allowed');
    ApiAuth::respond(['success' => false, 'message' => 'Only POST allowed'], 405);
}

$source = $_SERVER['HTTP_X_WEBHOOK_SOURCE'] ?? '';
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$rawBody = file_get_contents('php://input');

if (!$source || !$signature) {
    ApiAuth::log('/api/webhook', 'POST', null, 400, 'Missing source or signature');
    ApiAuth::respond(['success' => false, 'message' => 'Missing X-Webhook-Source or X-Webhook-Signature'], 400);
}

if (!ApiAuth::verifyWebhookSignature($rawBody, $signature, $source)) {
    ApiAuth::log('/api/webhook', 'POST', null, 401, 'Invalid signature', substr($rawBody, 0, 500));
    ApiAuth::respond(['success' => false, 'message' => 'Invalid signature'], 401);
}

$body = json_decode($rawBody, true);
if (!$body) {
    ApiAuth::log('/api/webhook', 'POST', null, 400, 'Invalid JSON');
    ApiAuth::respond(['success' => false, 'message' => 'Invalid JSON body'], 400);
}

try {
    $db->prepare("UPDATE webhook_secrets SET last_triggered = NOW() WHERE nama = ?")
       ->execute([$source]);

    $event = $body['event'] ?? 'unknown';
    $data = $body['data'] ?? [];

    // Routing berdasarkan source + event
    $handled = false;
    $result = ['received' => true, 'source' => $source, 'event' => $event];

    if ($source === 'SISTER' && $event === 'dosen.updated') {
        // Contoh: sync data dosen baru dari SISTER
        Logger::log('WEBHOOK', "SISTER dosen.updated: " . json_encode($data));
        $handled = true;
    } elseif ($source === 'PDDIKTI' && $event === 'mahasiswa.registered') {
        Logger::log('WEBHOOK', "PDDIKTI mahasiswa.registered: " . json_encode($data));
        $handled = true;
    } elseif ($source === 'SEVIMA' && $event === 'nilai.published') {
        Logger::log('WEBHOOK', "SEVIMA nilai.published: " . json_encode($data));
        $handled = true;
    }

    if (!$handled) {
        Logger::log('WEBHOOK', "Unhandled event: $source/$event");
        $result['handled'] = false;
    } else {
        $result['handled'] = true;
    }
} catch (Throwable $e) {
    error_log('Webhook processing error: ' . $e->getMessage());
    ApiAuth::log('/api/webhook', 'POST', null, 500, $e->getMessage());
    ApiAuth::respond(['success' => false, 'message' => 'Processing error'], 500);
}

$msTime = (int)(microtime(true) * 1000) - $startMs;
ApiAuth::log('/api/webhook', 'POST', null, 200, null, substr($rawBody, 0, 2000), $msTime);
ApiAuth::respond($result);