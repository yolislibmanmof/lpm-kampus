<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/core/ApiAuth.php';
require_once dirname(__DIR__, 2) . '/core/IntegrationHub.php';

$startMs = (int)(microtime(true) * 1000);
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') ApiAuth::respond([]);

$token = ApiAuth::getTokenFromHeader();
$auth = ApiAuth::validate($token);

if (!$auth) {
    ApiAuth::log('/api/sync-status', $_SERVER['REQUEST_METHOD'], null, 401, 'Invalid token');
    ApiAuth::respond(['success' => false, 'message' => 'Unauthorized'], 401);
}

if (!ApiAuth::isIpAllowed($auth)) {
    ApiAuth::log('/api/sync-status', $_SERVER['REQUEST_METHOD'], $auth['id_token'], 403, 'IP not allowed');
    ApiAuth::respond(['success' => false, 'message' => 'Forbidden: IP not allowed'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => true];

try {
    if ($method === 'GET') {
        if (!ApiAuth::hasPermission($auth, 'sync.read')) {
            ApiAuth::log('/api/sync-status', $method, $auth['id_token'], 403, 'Missing sync.read');
            ApiAuth::respond(['success' => false, 'message' => 'Forbidden: sync.read required'], 403);
        }

        $apis = $db->query("SELECT id_api, kode, nama, is_active, last_test, last_test_status FROM api_integrasi ORDER BY kode")->fetchAll();
        $last24h = $db->query("SELECT id_api, COUNT(*) total, SUM(CASE WHEN status='Success' THEN 1 ELSE 0 END) sukses
                               FROM sync_log WHERE waktu_mulai >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                               GROUP BY id_api")->fetchAll();
        $pending = (int)$db->query("SELECT COUNT(*) FROM sync_queue WHERE status = 'Pending'")->fetchColumn();

        $response['data'] = [
            'apis' => $apis,
            'last_24h' => $last24h,
            'pending_queue' => $pending
        ];
    } elseif ($method === 'POST') {
        if (!ApiAuth::hasPermission($auth, 'sync.trigger')) {
            ApiAuth::log('/api/sync-status', $method, $auth['id_token'], 403, 'Missing sync.trigger');
            ApiAuth::respond(['success' => false, 'message' => 'Forbidden: sync.trigger required'], 403);
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $kode = $body['kode'] ?? '';
        $jenis = $body['jenis'] ?? '';

        if (!$kode || !$jenis) {
            ApiAuth::respond(['success' => false, 'message' => 'Missing kode or jenis'], 400);
        }

        $result = IntegrationHub::runSync($kode, $jenis, ['mode' => $body['mode'] ?? 'full']);
        $response['result'] = $result;
        Logger::log('API', "API sync triggered by token #{$auth['id_token']}: $kode/$jenis");
    } else {
        ApiAuth::respond(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (Throwable $e) {
    error_log('API sync error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => 'Internal error'];
}

$msTime = (int)(microtime(true) * 1000) - $startMs;
ApiAuth::log('/api/sync-status', $method, $auth['id_token'], $response['success'] ? 200 : 500, null, null, $msTime);
ApiAuth::respond($response);