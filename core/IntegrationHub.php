<?php
declare(strict_types=1);

final class IntegrationHub
{
    private static array $adapters = [];

    /**
     * Daftar adapter yang tersedia
     */
    public static function getAvailableAdapters(): array
    {
        return [
            'PDDIKTI' => 'PddiktiAdapter',
            'SISTER'  => 'SisterAdapter',
            'SEVIMA'  => 'SevimaAdapter',
            'SINTA'   => 'SintaAdapter',
        ];
    }

    /**
     * Ambil adapter berdasarkan kode
     */
    public static function getAdapter(string $kode): ?ApiAdapter
    {
        if (isset(self::$adapters[$kode])) {
            return self::$adapters[$kode];
        }

        $adapters = self::getAvailableAdapters();
        if (!isset($adapters[$kode])) return null;

        $class = $adapters[$kode];
        if (!class_exists($class)) return null;

        self::$adapters[$kode] = new $class();
        return self::$adapters[$kode];
    }

    /**
     * Jalankan sinkronisasi (manual atau otomatis)
     */
    public static function runSync(string $kodeApi, string $jenisSync, array $options = []): array
    {
        $adapter = self::getAdapter($kodeApi);
        if (!$adapter) {
            return ['success' => false, 'message' => "Adapter $kodeApi tidak ditemukan"];
        }

        $db = Database::getInstance();
        $apiConfig = $db->prepare("SELECT * FROM api_integrasi WHERE kode = :kode");
        $apiConfig->execute([':kode' => $kodeApi]);
        $config = $apiConfig->fetch();

        if (!$config || !$config['is_active']) {
            return ['success' => false, 'message' => "API $kodeApi tidak aktif"];
        }

        // Buat log sinkronisasi
        $logStmt = $db->prepare("INSERT INTO sync_log (id_api, jenis_sync, mode, status, waktu_mulai, id_user, ip_address) 
                                 VALUES (:api, :jenis, :mode, 'Running', NOW(), :user, :ip)");
        $logStmt->execute([
            ':api'   => $config['id_api'],
            ':jenis' => $jenisSync,
            ':mode'  => $options['mode'] ?? 'full',
            ':user'  => Auth::id(),
            ':ip'    => $_SERVER['REMOTE_ADDR'] ?? 'cli'
        ]);
        $idLog = (int)$db->lastInsertId();

        $startTime = microtime(true);
        $result = ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];

        try {
            $adapter->setConfig($config);
            $result = $adapter->sync($jenisSync, $options);
            $status = $result['failed'] > 0 ? 'Partial' : 'Success';
        } catch (Exception $e) {
            $status = 'Failed';
            $result['errors'][] = $e->getMessage();
            error_log("Sync Error [$kodeApi/$jenisSync]: " . $e->getMessage());
        }

        $duration = (int)(microtime(true) - $startTime);

        // Update log
        $updateLog = $db->prepare("UPDATE sync_log SET 
            status = :status, total_data = :total, data_berhasil = :success, 
            data_gagal = :failed, data_skip = :skipped, waktu_selesai = NOW(), 
            durasi_detik = :durasi, error_message = :error, detail_json = :detail
            WHERE id_log = :id");
        $updateLog->execute([
            ':status'  => $status,
            ':total'   => $result['total'],
            ':success' => $result['success'],
            ':failed'  => $result['failed'],
            ':skipped' => $result['skipped'],
            ':durasi'  => $duration,
            ':error'   => !empty($result['errors']) ? implode("\n", $result['errors']) : null,
            ':detail'  => json_encode($result['detail'] ?? []),
            ':id'      => $idLog
        ]);

        Logger::log('SYNC', "Sinkronisasi $kodeApi/$jenisSync: $status ({$result['success']}/{$result['total']})");

        return [
            'success' => $status !== 'Failed',
            'status'  => $status,
            'id_log'  => $idLog,
            'result'  => $result,
            'duration' => $duration
        ];
    }

    /**
     * Test koneksi ke API
     */
    public static function testConnection(string $kodeApi): array
    {
        $adapter = self::getAdapter($kodeApi);
        if (!$adapter) return ['success' => false, 'message' => 'Adapter tidak ditemukan'];

        $db = Database::getInstance();
        $config = $db->prepare("SELECT * FROM api_integrasi WHERE kode = :kode");
        $config->execute([':kode' => $kodeApi]);
        $apiConfig = $config->fetch();

        if (!$apiConfig) return ['success' => false, 'message' => 'Konfigurasi tidak ditemukan'];

        try {
            $adapter->setConfig($apiConfig);
            $testResult = $adapter->testConnection();
            
            $update = $db->prepare("UPDATE api_integrasi SET last_test = NOW(), last_test_status = :status WHERE id_api = :id");
            $update->execute([
                ':status' => $testResult['success'] ? 'Sukses' : 'Gagal',
                ':id'     => $apiConfig['id_api']
            ]);

            return $testResult;
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Statistik integrasi untuk dashboard
     */
    public static function getStats(): array
    {
        $db = Database::getInstance();
        
        $apis = $db->query("SELECT kode, nama, is_active, last_test, last_test_status FROM api_integrasi ORDER BY kode")->fetchAll();
        
        $last24h = $db->query("SELECT id_api, COUNT(*) total, 
            SUM(CASE WHEN status='Success' THEN 1 ELSE 0 END) sukses,
            SUM(data_berhasil) total_data
            FROM sync_log 
            WHERE waktu_mulai >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY id_api")->fetchAll();

        $pending = (int)$db->query("SELECT COUNT(*) FROM sync_queue WHERE status = 'Pending'")->fetchColumn();
        
        $conflicts = (int)$db->query("SELECT COUNT(*) FROM sync_conflict WHERE resolved_by IS NULL")->fetchColumn();

        return [
            'apis' => $apis,
            'last24h' => $last24h,
            'pending_queue' => $pending,
            'unresolved_conflicts' => $conflicts
        ];
    }
}