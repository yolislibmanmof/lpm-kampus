<?php
declare(strict_types=1);

/**
 * Menghitung Health Score per prodi berdasarkan 5 pilar:
 * 1. EDP (Evaluasi Diri) - 25%
 * 2. Mock Audit (Simulasi Akreditasi) - 25%
 * 3. Risk Score (invers: sedikit risiko = tinggi) - 20%
 * 4. Temuan Audit (invers: sedikit temuan = tinggi) - 15%
 * 5. IKU (Indikator Kinerja Utama) - 15%
 * 
 * Output: 0 - 100
 */
final class HealthScoreEngine
{
    private static ?PDO $db = null;

    private static function db(): PDO
    {
        if (self::$db === null) self::$db = Database::getInstance();
        return self::$db;
    }

    /**
     * Hitung semua komponen skor untuk 1 prodi
     */
    public static function calculate(int $idProdi, int $tahun): array
    {
        $db = self::db();
        
        // 1. Skor EDP (0-100): rata-rata capaian evaluasi_diri
        $skorEdp = 0.0;
        try {
            $q = $db->prepare("SELECT AVG(capaian) FROM evaluasi_diri WHERE id_prodi = ? AND tahun = ?");
            $q->execute([$idProdi, $tahun]);
            $skorEdp = (float)($q->fetchColumn() ?: 0);
        } catch (Throwable $e) {}

        // 2. Skor Mock Audit (0-100): konversi skor 0-4 ke 0-100
        $skorMock = 0.0;
        try {
            $q = $db->prepare("SELECT total_skor FROM mock_audit_header 
                              WHERE id_prodi = ? AND tahun_ajaran LIKE ?
                              ORDER BY created_at DESC LIMIT 1");
            $q->execute([$idProdi, $tahun . '%']);
            $mockSkor = $q->fetchColumn();
            if ($mockSkor !== false && $mockSkor !== null) {
                $skorMock = min(100, ((float)$mockSkor / 4.0) * 100);
            }
        } catch (Throwable $e) {}

        // 3. Skor Risiko (0-100): invers dari jumlah risiko ekstrem + tinggi
        $skorRisiko = 100.0;
        try {
            $q = $db->prepare("SELECT 
                    SUM(CASE WHEN level_risiko = 'Ekstrem' THEN 20 ELSE 0 END) +
                    SUM(CASE WHEN level_risiko = 'Tinggi' THEN 10 ELSE 0 END) +
                    SUM(CASE WHEN level_risiko = 'Sedang' THEN 3 ELSE 0 END) AS penalty
                FROM risk_register WHERE id_prodi = ? AND status IN ('Teridentifikasi','Dalam Mitigasi')");
            $q->execute([$idProdi]);
            $penalty = (float)($q->fetchColumn() ?: 0);
            $skorRisiko = max(0, 100 - $penalty);
        } catch (Throwable $e) {}

        // 4. Skor Temuan (0-100): invers dari jumlah temuan mayor/minor belum selesai
        $skorTemuan = 100.0;
        try {
            $q = $db->prepare("SELECT 
                    SUM(CASE WHEN t.kategori = 'Mayor' THEN 15 ELSE 0 END) +
                    SUM(CASE WHEN t.kategori = 'Minor' THEN 5 ELSE 0 END) AS penalty
                FROM temuan_audit t
                JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
                WHERE pa.id_prodi = ? AND t.status_verifikasi != 'Diterima'");
            $q->execute([$idProdi]);
            $penalty = (float)($q->fetchColumn() ?: 0);
            $skorTemuan = max(0, 100 - $penalty);
        } catch (Throwable $e) {}

        // 5. Skor IKU (0-100): rata-rata persentase pencapaian IKU
        $skorIku = 0.0;
        try {
            $q = $db->prepare("SELECT AVG(CASE WHEN i.target > 0 THEN (d.nilai / i.target) * 100 ELSE 0 END)
                              FROM iku_data d
                              JOIN iku_indikator i ON d.id_iku = i.id_iku
                              WHERE d.tahun = ?");
            $q->execute([$tahun]);
            $skorIku = min(100, (float)($q->fetchColumn() ?: 0));
        } catch (Throwable $e) {}

        // Total skor (bobot)
        $total = ($skorEdp * 0.25) + ($skorMock * 0.25) + ($skorRisiko * 0.20) +
                 ($skorTemuan * 0.15) + ($skorIku * 0.15);
        $total = round(max(0, min(100, $total)), 2);

        // Tentukan peringkat
        if ($total >= 85) $peringkat = 'Excellent';
        elseif ($total >= 70) $peringkat = 'Good';
        elseif ($total >= 55) $peringkat = 'Fair';
        elseif ($total >= 40) $peringkat = 'Poor';
        else $peringkat = 'Critical';

        return [
            'skor_edp' => round($skorEdp, 2),
            'skor_mock' => round($skorMock, 2),
            'skor_risiko' => round($skorRisiko, 2),
            'skor_temuan' => round($skorTemuan, 2),
            'skor_iku' => round($skorIku, 2),
            'total_skor' => $total,
            'peringkat' => $peringkat
        ];
    }

    /**
     * Simpan hasil ke tabel prodi_health_score
     */
    public static function saveScore(int $idProdi, int $tahun, array $scores): bool
    {
        try {
            $db = self::db();
            $db->prepare("INSERT INTO prodi_health_score 
                (id_prodi, tahun, skor_edp, skor_mock, skor_risiko, skor_temuan, skor_iku, total_skor, peringkat)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    skor_edp = VALUES(skor_edp), skor_mock = VALUES(skor_mock),
                    skor_risiko = VALUES(skor_risiko), skor_temuan = VALUES(skor_temuan),
                    skor_iku = VALUES(skor_iku), total_skor = VALUES(total_skor), peringkat = VALUES(peringkat)")
               ->execute([
                   $idProdi, $tahun,
                   $scores['skor_edp'], $scores['skor_mock'], $scores['skor_risiko'],
                   $scores['skor_temuan'], $scores['skor_iku'],
                   $scores['total_skor'], $scores['peringkat']
               ]);
            return true;
        } catch (Throwable $e) {
            error_log('HealthScoreEngine::save: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hitung semua prodi untuk tahun tertentu (batch)
     */
    public static function calculateAll(int $tahun): int
    {
        try {
            $db = self::db();
            $q = $db->query("SELECT id_prodi FROM prodi ORDER BY id_prodi");
            $count = 0;
            foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $idProdi) {
                $scores = self::calculate((int)$idProdi, $tahun);
                self::saveScore((int)$idProdi, $tahun, $scores);
                $count++;
            }
            Logger::log('HEALTH_SCORE', "Calculated $count prodi scores for year $tahun");
            return $count;
        } catch (Throwable $e) {
            error_log('HealthScoreEngine::calculateAll: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ambil leaderboard (top N prodi)
     */
    public static function getLeaderboard(int $tahun, int $limit = 10): array
    {
        try {
            $db = self::db();
            $q = $db->prepare("SELECT h.*, p.nama_prodi, f.nama_fakultas
                              FROM prodi_health_score h
                              JOIN prodi p ON h.id_prodi = p.id_prodi
                              LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
                              WHERE h.tahun = ?
                              ORDER BY h.total_skor DESC, p.nama_prodi ASC
                              LIMIT ?");
            $q->execute([$tahun, $limit]);
            return $q->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}