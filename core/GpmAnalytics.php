<?php
declare(strict_types=1);

/**
 * GpmAnalytics - Class helper untuk logika analitik GPM Fakultas
 * Menyediakan method reusable untuk dashboard dan modul GPM lainnya
 */
final class GpmAnalytics
{
    private static ?PDO $db = null;

    private static function db(): PDO
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    /**
     * Ambil statistik ringkas untuk fakultas tertentu
     */
    public static function getFakultasStats(int $idFakultas, int $tahun): array
    {
        try {
            $db = self::db();
            
            // Hitung total prodi di fakultas
            $totalProdi = $db->prepare("SELECT COUNT(*) FROM prodi WHERE id_fakultas = ?");
            $totalProdi->execute([$idFakultas]);
            $nProdi = (int)$totalProdi->fetchColumn();

            // Hitung EDP yang sudah dikunci (progres)
            $edpLocked = $db->prepare("
                SELECT COUNT(DISTINCT e.id_prodi) 
                FROM evaluasi_diri e
                JOIN prodi p ON e.id_prodi = p.id_prodi
                WHERE p.id_fakultas = ? AND e.tahun = ? AND e.status = 'Terkunci'
            ");
            $edpLocked->execute([$idFakultas, $tahun]);
            $nEdpLocked = (int)$edpLocked->fetchColumn();

            // Hitung EDP yang menunggu review GPM (sudah terkunci tapi belum direview)
            $edpMenunggu = $db->prepare("
                SELECT COUNT(DISTINCT e.id_prodi)
                FROM evaluasi_diri e
                JOIN prodi p ON e.id_prodi = p.id_prodi
                LEFT JOIN review_gpm r ON e.id_prodi = r.id_prodi AND e.tahun = r.tahun
                WHERE p.id_fakultas = ? AND e.tahun = ? AND e.status = 'Terkunci' AND r.id_review IS NULL
            ");
            $edpMenunggu->execute([$idFakultas, $tahun]);
            $nEdpMenunggu = (int)$edpMenunggu->fetchColumn();

            // Hitung EDP yang sudah disetujui
            $edpDisetujui = $db->prepare("
                SELECT COUNT(DISTINCT r.id_prodi)
                FROM review_gpm r
                JOIN prodi p ON r.id_prodi = p.id_prodi
                WHERE p.id_fakultas = ? AND r.tahun = ? AND r.status = 'Disetujui'
            ");
            $edpDisetujui->execute([$idFakultas, $tahun]);
            $nEdpDisetujui = (int)$edpDisetujui->fetchColumn();

            // Hitung temuan audit yang belum diverifikasi
            $temuanPending = $db->prepare("
                SELECT COUNT(*)
                FROM temuan_audit t
                JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
                JOIN prodi p ON pa.id_prodi = p.id_prodi
                WHERE p.id_fakultas = ? AND t.status_verifikasi = 'Menunggu'
            ");
            $temuanPending->execute([$idFakultas]);
            $nTemuanPending = (int)$temuanPending->fetchColumn();

            return [
                'total_prodi' => $nProdi,
                'edp_locked' => $nEdpLocked,
                'edp_menunggu' => $nEdpMenunggu,
                'edp_disetujui' => $nEdpDisetujui,
                'temuan_pending' => $nTemuanPending,
                'progress_edp' => $nProdi > 0 ? round(($nEdpLocked / $nProdi) * 100) : 0
            ];
        } catch (PDOException $e) {
            error_log("GpmAnalytics::getFakultasStats error: " . $e->getMessage());
            return [
                'total_prodi' => 0,
                'edp_locked' => 0,
                'edp_menunggu' => 0,
                'edp_disetujui' => 0,
                'temuan_pending' => 0,
                'progress_edp' => 0
            ];
        }
    }

    /**
     * Ambil data risiko per prodi di fakultas
     */
    public static function getFakultasRisiko(int $idFakultas): array
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("
                SELECT 
                    p.id_prodi,
                    p.nama_prodi,
                    COUNT(r.id_risk) as total_risiko,
                    SUM(CASE WHEN r.level_risiko = 'Ekstrem' THEN 1 ELSE 0 END) as risiko_ekstrem,
                    SUM(CASE WHEN r.level_risiko = 'Tinggi' THEN 1 ELSE 0 END) as risiko_tinggi,
                    SUM(CASE WHEN r.status IN ('Teridentifikasi', 'Dalam Mitigasi') THEN 1 ELSE 0 END) as risiko_aktif
                FROM prodi p
                LEFT JOIN risk_register r ON p.id_prodi = r.id_prodi
                WHERE p.id_fakultas = ?
                GROUP BY p.id_prodi, p.nama_prodi
                ORDER BY risiko_ekstrem DESC, risiko_tinggi DESC, total_risiko DESC
            ");
            $stmt->execute([$idFakultas]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("GpmAnalytics::getFakultasRisiko error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil data mock audit per prodi di fakultas
     */
    public static function getFakultasMockAudit(int $idFakultas, int $tahun): array
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("
                SELECT 
                    p.id_prodi,
                    p.nama_prodi,
                    m.total_skor,
                    m.prediksi_peringkat,
                    m.created_at
                FROM prodi p
                LEFT JOIN mock_audit_header m ON p.id_prodi = m.id_prodi AND m.tahun_ajaran = ?
                WHERE p.id_fakultas = ?
                ORDER BY m.total_skor DESC
            ");
            $stmt->execute([$tahun . '/' . ($tahun + 1), $idFakultas]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("GpmAnalytics::getFakultasMockAudit error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil temuan audit per prodi di fakultas
     */
    public static function getFakultasTemuan(int $idFakultas): array
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("
                SELECT 
                    p.id_prodi,
                    p.nama_prodi,
                    t.id_temuan,
                    t.kategori,
                    t.deskripsi_temuan,
                    t.status_verifikasi,
                    t.created_at
                FROM temuan_audit t
                JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
                JOIN prodi p ON pa.id_prodi = p.id_prodi
                WHERE p.id_fakultas = ?
                ORDER BY 
                    CASE t.kategori 
                        WHEN 'Mayor' THEN 1 
                        WHEN 'Minor' THEN 2 
                        WHEN 'Observasi' THEN 3 
                        ELSE 4 
                    END,
                    t.created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$idFakultas]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("GpmAnalytics::getFakultasTemuan error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil antrean review EDP per prodi
     */
    public static function getEdpAntrean(int $idFakultas, int $tahun): array
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("
                SELECT 
                    p.id_prodi,
                    p.nama_prodi,
                    COUNT(e.id_evaluasi) as total_standar,
                    SUM(CASE WHEN e.status = 'Terkunci' THEN 1 ELSE 0 END) as terkunci,
                    r.status as review_status,
                    r.catatan as review_catatan,
                    r.updated_at as review_tanggal
                FROM prodi p
                LEFT JOIN evaluasi_diri e ON p.id_prodi = e.id_prodi AND e.tahun = ?
                LEFT JOIN review_gpm r ON p.id_prodi = r.id_prodi AND r.tahun = ?
                WHERE p.id_fakultas = ?
                GROUP BY p.id_prodi, p.nama_prodi, r.status, r.catatan, r.updated_at
                ORDER BY 
                    (r.status IS NULL AND SUM(CASE WHEN e.status = 'Terkunci' THEN 1 ELSE 0 END) > 0) DESC,
                    p.nama_prodi
            ");
            $stmt->execute([$tahun, $tahun, $idFakultas]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("GpmAnalytics::getEdpAntrean error: " . $e->getMessage());
            return [];
        }
    }
}