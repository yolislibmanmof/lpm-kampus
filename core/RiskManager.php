<?php
declare(strict_types=1);

final class RiskManager
{
    /**
     * Hitung level risiko berdasarkan skor (Dampak x Probabilitas)
     */
    public static function calculateLevel(int $dampak, int $probabilitas): array
    {
        $skor = $dampak * $probabilitas;
        
        if ($skor >= 20) {
            return ['skor' => $skor, 'level' => 'Ekstrem', 'color' => '#EF4444', 'bg' => 'rgba(239,68,68,0.1)'];
        } elseif ($skor >= 12) {
            return ['skor' => $skor, 'level' => 'Tinggi', 'color' => '#F59E0B', 'bg' => 'rgba(245,158,11,0.1)'];
        } elseif ($skor >= 5) {
            return ['skor' => $skor, 'level' => 'Sedang', 'color' => '#3B82F6', 'bg' => 'rgba(59,130,246,0.1)'];
        } else {
            return ['skor' => $skor, 'level' => 'Rendah', 'color' => '#10B981', 'bg' => 'rgba(16,185,129,0.1)'];
        }
    }

    /**
     * Ambil daftar risiko (bisa filter per prodi atau semua)
     */
    public static function getRisks(?int $idProdi = null, string $statusFilter = ''): array
    {
        $db = Database::getInstance();
        $sql = "SELECT r.*, p.nama_prodi, u.nama_lengkap AS nama_pic 
                FROM risk_register r 
                LEFT JOIN prodi p ON r.id_prodi = p.id_prodi 
                LEFT JOIN users u ON r.id_user_pic = u.id_user 
                WHERE 1=1";
        $params = [];

        if ($idProdi !== null) {
            $sql .= " AND r.id_prodi = :prodi";
            $params[':prodi'] = $idProdi;
        } else {
            // Jika null, ambil yang institusi (NULL) ATAU semua (opsional, di sini kita ambil semua untuk Admin)
        }

        if ($statusFilter !== '') {
            $sql .= " AND r.status = :status";
            $params[':status'] = $statusFilter;
        }

        $sql .= " ORDER BY r.skor_risiko DESC, r.tanggal_identifikasi DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Tambah risiko baru
     */
    public static function addRisk(array $data): bool
    {
        $db = Database::getInstance();
        $level = self::calculateLevel((int)$data['dampak'], (int)$data['probabilitas']);
        
        $sql = "INSERT INTO risk_register 
                (id_prodi, id_user_pic, judul_risiko, deskripsi, dampak, probabilitas, level_risiko, rencana_mitigasi, status, tanggal_identifikasi, tanggal_review) 
                VALUES (:prodi, :pic, :judul, :desk, :dampak, :prob, :level, :mitigasi, :status, :tgl_id, :tgl_rev)";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':prodi'    => $data['id_prodi'] ?: null,
            ':pic'      => $data['id_user_pic'],
            ':judul'    => $data['judul_risiko'],
            ':desk'     => $data['deskripsi'],
            ':dampak'   => $data['dampak'],
            ':prob'     => $data['probabilitas'],
            ':level'    => $level['level'],
            ':mitigasi' => $data['rencana_mitigasi'],
            ':status'   => $data['status'] ?? 'Teridentifikasi',
            ':tgl_id'   => $data['tanggal_identifikasi'],
            ':tgl_rev'  => $data['tanggal_review'] ?: null
        ]);
    }

    /**
     * Update status risiko (untuk aksi cepat)
     */
    public static function updateStatus(int $idRisk, string $newStatus): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE risk_register SET status = :status, updated_at = NOW() WHERE id_risk = :id");
        return $stmt->execute([':status' => $newStatus, ':id' => $idRisk]);
    }

    /**
     * Statistik untuk Dashboard
     */
    public static function getStats(?int $idProdi = null): array
    {
        $db = Database::getInstance();
        
        // Gunakan WHERE 1=1 sebagai basis agar aman saat di-append dengan AND
        $where = "WHERE 1=1";
        $params = [];

        if ($idProdi !== null) {
            $where .= " AND id_prodi = :prodi";
            $params[':prodi'] = $idProdi;
        }

        $totalStmt = $db->prepare("SELECT COUNT(*) FROM risk_register $where");
        $totalStmt->execute($params);
        
        $ekstremStmt = $db->prepare("SELECT COUNT(*) FROM risk_register $where AND level_risiko = 'Ekstrem'");
        $ekstremStmt->execute($params);

        $aktifStmt = $db->prepare("SELECT COUNT(*) FROM risk_register $where AND status IN ('Teridentifikasi', 'Dalam Mitigasi')");
        $aktifStmt->execute($params);

        return [
            'total' => (int)$totalStmt->fetchColumn(),
            'ekstrem' => (int)$ekstremStmt->fetchColumn(),
            'aktif' => (int)$aktifStmt->fetchColumn()
        ];
    }
}