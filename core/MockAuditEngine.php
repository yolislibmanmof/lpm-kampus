<?php
declare(strict_types=1);

final class MockAuditEngine
{
    // Bobot kriteria (Contoh pendekatan LAM: K3, K4, K6 biasanya paling berat)
    private const BOBOT_KRITERIA = [
        1 => 0.10, // Visi, Misi, Tujuan, dan Strategi
        2 => 0.10, // Tata Pamong, Kepemimpinan, dan Sistem Manajemen
        3 => 0.15, // Mahasiswa
        4 => 0.15, // Sumber Daya Manusia
        5 => 0.10, // Keuangan, Sarana, dan Prasarana
        6 => 0.15, // Pendidikan
        7 => 0.10, // Penelitian
        8 => 0.05, // Pengabdian kepada Masyarakat
        9 => 0.10  // Luaran dan Capaian Tridharma
    ];

    /**
     * Hitung prediksi peringkat berdasarkan skor rata-rata tertimbang
     */
    public static function predictRank(float $totalSkor): array
    {
        if ($totalSkor >= 3.50) {
            return ['peringkat' => 'Unggul', 'color' => '#D97706', 'bg' => 'rgba(217, 119, 6, 0.1)', 'pesan' => '🏆 Luar biasa! Prodi siap mengajukan akreditasi Unggul.'];
        } elseif ($totalSkor >= 3.00) {
            return ['peringkat' => 'Baik Sekali', 'color' => '#059669', 'bg' => 'rgba(5, 150, 105, 0.1)', 'pesan' => '✅ Sangat baik. Tingkatkan sedikit lagi untuk meraih Unggul.'];
        } elseif ($totalSkor >= 2.50) {
            return ['peringkat' => 'Baik', 'color' => '#2563EB', 'bg' => 'rgba(37, 99, 235, 0.1)', 'pesan' => '⚠️ Memenuhi standar minimal, namun perlu perbaikan signifikan di beberapa kriteria.'];
        } else {
            return ['peringkat' => 'Belum Terakreditasi / Perlu Perbaikan Besar', 'color' => '#EF4444', 'bg' => 'rgba(239, 68, 68, 0.1)', 'pesan' => '🚨 KRITIS: Banyak kriteria yang belum memenuhi standar minimal. Segera lakukan tindakan koreksi.'];
        }
    }

    /**
     * Simpan atau Update hasil simulasi mock audit
     */
    public static function saveSimulation(int $idProdi, string $tahunAjaran, array $detailScores, int $userId): bool
    {
        $db = Database::getInstance();
        $totalSkor = 0.0;

        // Hitung total skor tertimbang
        foreach ($detailScores as $idKriteria => $data) {
            $bobot = self::BOBOT_KRITERIA[$idKriteria] ?? 0.10;
            $totalSkor += ((float)$data['skor']) * $bobot;
        }

        $prediksi = self::predictRank($totalSkor);

        $db->beginTransaction();
        try {
            // 1. Simpan Header
            $stmtHeader = $db->prepare("INSERT INTO mock_audit_header (id_prodi, tahun_ajaran, total_skor, prediksi_peringkat, catatan_umum, created_by) 
                                        VALUES (:prodi, :tahun, :skor, :peringkat, :catatan, :user)");
            $stmtHeader->execute([
                ':prodi' => $idProdi,
                ':tahun' => $tahunAjaran,
                ':skor' => round($totalSkor, 2),
                ':peringkat' => $prediksi['peringkat'],
                ':catatan' => $prediksi['pesan'],
                ':user' => $userId
            ]);
            $idMock = (int)$db->lastInsertId();

            // 2. Simpan Detail per Kriteria
            $stmtDetail = $db->prepare("INSERT INTO mock_audit_detail (id_mock, id_kriteria, skor_capaian, kelengkapan_bukti, catatan_gap) 
                                        VALUES (:mock, :kriteria, :skor, :bukti, :gap)");
            foreach ($detailScores as $idKriteria => $data) {
                $stmtDetail->execute([
                    ':mock' => $idMock,
                    ':kriteria' => $idKriteria,
                    ':skor' => (float)$data['skor'],
                    ':bukti' => (int)$data['bukti'],
                    ':gap' => $data['gap'] ?? ''
                ]);
            }

            $db->commit();
            Logger::log('MOCK_AUDIT', "Simulasi akreditasi disimpan untuk Prodi ID: $idProdi, Skor: $totalSkor");
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Mock Audit Save Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil data simulasi terbaru untuk sebuah Prodi
     */
    public static function getLatestSimulation(int $idProdi): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM mock_audit_header WHERE id_prodi = :prodi ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([':prodi' => $idProdi]);
        $header = $stmt->fetch();

        if (!$header) return null;

        $stmtDetail = $db->prepare("SELECT d.*, k.nama_kriteria 
                                    FROM mock_audit_detail d 
                                    JOIN kriteria_akreditasi k ON d.id_kriteria = k.id_kriteria 
                                    WHERE d.id_mock = :mock 
                                    ORDER BY d.id_kriteria ASC");
        $stmtDetail->execute([':mock' => $header['id_mock']]);
        $header['details'] = $stmtDetail->fetchAll();

        return $header;
    }
}