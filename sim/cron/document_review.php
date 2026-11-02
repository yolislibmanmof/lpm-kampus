<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/DocumentLifecycle.php';

// Security: hanya bisa dijalankan via CLI atau dengan token rahasia
$token = $_GET['token'] ?? '';
$isCli = php_sapi_name() === 'cli';
if (!$isCli && $token !== env('CRON_TOKEN', '')) {
    http_response_code(403);
    exit('Forbidden');
}

$result = DocumentLifecycle::runDailyCheck();
echo json_encode(['success' => true, 'result' => $result]);