<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/IntegrationHub.php';

// Security: hanya bisa dijalankan via CLI atau dengan token
$token = $_GET['token'] ?? '';
$isCli = php_sapi_name() === 'cli';
if (!$isCli && $token !== env('CRON_TOKEN', '')) {
    http_response_code(403);
    exit('Forbidden');
}

$db = Database::getInstance();

// Ambil jadwal auto-sync yang aktif
$schedules = $db->query("SELECT s.*, a.kode FROM sync_schedule s 
                         JOIN api_integrasi a ON s.id_api = a.id_api 
                         WHERE s.is_active = 1 AND a.is_active = 1
                         AND (s.next_run IS NULL OR s.next_run <= NOW())")->fetchAll();

$results = [];
foreach ($schedules as $sched) {
    $result = IntegrationHub::runSync($sched['kode'], $sched['jenis_sync'], ['mode' => 'incremental']);
    $results[] = [
        'api' => $sched['kode'],
        'jenis' => $sched['jenis_sync'],
        'status' => $result['status'] ?? 'Unknown'
    ];

    // Update next_run berdasarkan cron expression (simplified: +24 jam)
    $nextRun = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $db->prepare("UPDATE sync_schedule SET last_run = NOW(), next_run = :next WHERE id_schedule = :id")
       ->execute([':next' => $nextRun, ':id' => $sched['id_schedule']]);
}

echo json_encode(['success' => true, 'results' => $results, 'timestamp' => date('c')]);