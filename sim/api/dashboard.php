<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/core/ApiAuth.php';
require_once dirname(__DIR__, 2) . '/core/GpmAnalytics.php';
require_once dirname(__DIR__, 2) . '/core/RiskManager.php';
require_once dirname(__DIR__, 2) . '/core/MockAuditEngine.php';

$startMs = (int)(microtime(true) * 1000);
$db = Database::getInstance();

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ApiAuth::respond([]);
}

// Autentikasi
$token = ApiAuth::getTokenFromHeader();
$auth = ApiAuth::validate($token);

if (!$auth) {
    ApiAuth::log('/api/dashboard', $_SERVER['REQUEST_METHOD'], null, 401, 'Invalid token');
    ApiAuth::respond(['success' => false, 'message' => 'Unauthorized: Invalid or expired token'], 401);
}

if (!ApiAuth::isIpAllowed($auth)) {
    ApiAuth::log('/api/dashboard', $_SERVER['REQUEST_METHOD'], $auth['id_token'], 403, 'IP not whitelisted');
    ApiAuth::respond(['success' => false, 'message' => 'Forbidden: IP not allowed'], 403);
}

if (!ApiAuth::hasPermission($auth, 'dashboard.read')) {
    ApiAuth::log('/api/dashboard', $_SERVER['REQUEST_METHOD'], $auth['id_token'], 403, 'Missing permission');
    ApiAuth::respond(['success' => false, 'message' => 'Forbidden: dashboard.read permission required'], 403);
}

$role = $auth['id_role'];
$responseData = [
    'success' => true,
    'user' => [
        'id' => $auth['id_user'],
        'nama' => $auth['nama'],
        'role' => $role
    ],
    'dashboard' => []
];

try {
    if ($role === 1) {
        // ADMIN: Statistik global
        $nProdi = (int)$db->query("SELECT COUNT(*) FROM prodi")->fetchColumn();
        $nDosen = (int)$db->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
        $nMahasiswa = (int)$db->query("SELECT COUNT(*) FROM tracer_alumni")->fetchColumn();

        $riskStats = RiskManager::getStats();
        $responseData['dashboard'] = [
            'total_prodi' => $nProdi,
            'total_dosen' => $nDosen,
            'total_mahasiswa' => $nMahasiswa,
            'risiko' => $riskStats
        ];
    } elseif ($role === 2) {
        // PIMPINAN: Benchmark singkat
        $tahun = (int)date('Y');
        $avg = $db->prepare("SELECT ROUND(AVG(capaian),1) FROM evaluasi_diri WHERE tahun = ?");
        $avg->execute([$tahun]);
        $responseData['dashboard'] = [
            'rata_capaian' => (float)($avg->fetchColumn() ?: 0),
            'tahun' => $tahun
        ];
    } elseif ($role === 3) {
        // KAPRODI: Data prodi sendiri
        $prodiId = (int)$db->prepare("SELECT id_prodi FROM users WHERE id_user = ?")->execute([$auth['id_user']]) ? null : null;
        $q = $db->prepare("SELECT id_prodi, nama_prodi FROM users u JOIN prodi p ON u.id_prodi = p.id_prodi WHERE u.id_user = ?");
        $q->execute([$auth['id_user']]);
        $prodi = $q->fetch();
        $responseData['dashboard'] = $prodi ?: ['error' => 'Prodi tidak ditemukan'];
    } elseif ($role === 5) {
        // GPM: Data fakultas
        $fakId = null;
        $q = $db->prepare("SELECT id_fakultas FROM users WHERE id_user = ?");
        $q->execute([$auth['id_user']]);
        $fakId = $q->fetchColumn();
        if ($fakId) {
            $responseData['dashboard'] = GpmAnalytics::getFakultasStats((int)$fakId, (int)date('Y'));
        }
    } else {
        $responseData['dashboard'] = ['message' => 'Role ini belum punya endpoint dashboard khusus'];
    }
} catch (Throwable $e) {
    error_log('API dashboard error: ' . $e->getMessage());
    $responseData['dashboard'] = ['error' => 'Failed to load dashboard data'];
}

$msTime = (int)(microtime(true) * 1000) - $startMs;
ApiAuth::log('/api/dashboard', $_SERVER['REQUEST_METHOD'], $auth['id_token'], 200, null, null, $msTime);
ApiAuth::respond($responseData);