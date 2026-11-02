<?php
declare(strict_types=1);

final class GamificationEngine
{
    // Kode aksi dan poin default
    public const AKSI = [
        'LOGIN' => 1,
        'UPLOAD_DOKUMEN' => 10,
        'REVIEW_EDP' => 20,
        'SUBMIT_EDOM' => 5,
        'COMPLETE_AUDIT' => 30,
        'RESOLVE_TEMUAN' => 15,
        'ADD_RISK' => 5,
        'MOCK_AUDIT' => 25,
        'PUBLISH_BERITA' => 8,
        'FILL_TRACER' => 15,
    ];

    private static ?PDO $db = null;

    private static function db(): PDO
    {
        if (self::$db === null) self::$db = Database::getInstance();
        return self::$db;
    }

    /**
     * Tambahkan poin ke user. Otomatis cek dan unlock badge baru.
     */
    public static function addPoints(int $idUser, string $aksi, ?string $keterangan = null, ?string $refTable = null, ?int $refId = null): array
    {
        if (!isset(self::AKSI[$aksi])) {
            return ['success' => false, 'message' => 'Unknown action code'];
        }

        $poin = self::AKSI[$aksi];
        $newBadges = [];

        try {
            $db = self::db();
            $db->beginTransaction();

            // 1. Insert poin
            $db->prepare("INSERT INTO gamification_points (id_user, aksi, poin, keterangan, ref_table, ref_id)
                          VALUES (?, ?, ?, ?, ?, ?)")
               ->execute([$idUser, $aksi, $poin, $keterangan, $refTable, $refId]);

            // 2. Hitung total poin user
            $q = $db->prepare("SELECT COALESCE(SUM(poin), 0) FROM gamification_points WHERE id_user = ?");
            $q->execute([$idUser]);
            $totalPoin = (int)$q->fetchColumn();

            // 3. Cek badge yang belum di-unlock
            $q = $db->prepare("SELECT b.* FROM gamification_badges b
                              WHERE b.is_active = 1 AND b.threshold <= ?
                              AND (b.role_required IS NULL OR b.role_required = (SELECT id_role FROM users WHERE id_user = ?))
                              AND NOT EXISTS (
                                  SELECT 1 FROM gamification_user_badges ub 
                                  WHERE ub.id_user = ? AND ub.id_badge = b.id_badge
                              )");
            $q->execute([$totalPoin, $idUser, $idUser]);
            $newBadgesRows = $q->fetchAll();

            // 4. Unlock badge baru
            foreach ($newBadgesRows as $badge) {
                $db->prepare("INSERT INTO gamification_user_badges (id_user, id_badge) VALUES (?, ?)")
                   ->execute([$idUser, $badge['id_badge']]);
                $newBadges[] = $badge;
            }

            $db->commit();

            if (!empty($newBadges)) {
                Logger::log('GAMIFICATION', "User $idUser unlocked " . count($newBadges) . " badge(s)");
            }

            return [
                'success' => true,
                'poin_added' => $poin,
                'total_poin' => $totalPoin,
                'new_badges' => $newBadges
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('GamificationEngine::addPoints: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    /**
     * Ambil profil gamifikasi user
     */
    public static function getUserProfile(int $idUser): array
    {
        try {
            $db = self::db();
            
            $q = $db->prepare("SELECT COALESCE(SUM(poin), 0) FROM gamification_points WHERE id_user = ?");
            $q->execute([$idUser]);
            $totalPoin = (int)$q->fetchColumn();

            $q = $db->prepare("SELECT b.*, ub.unlocked_at 
                              FROM gamification_user_badges ub
                              JOIN gamification_badges b ON ub.id_badge = b.id_badge
                              WHERE ub.id_user = ?
                              ORDER BY ub.unlocked_at DESC");
            $q->execute([$idUser]);
            $badges = $q->fetchAll();

            $q = $db->prepare("SELECT aksi, COUNT(*) cnt, SUM(poin) total_poin
                              FROM gamification_points WHERE id_user = ?
                              GROUP BY aksi ORDER BY total_poin DESC");
            $q->execute([$idUser]);
            $breakdown = $q->fetchAll();

            $q = $db->prepare("SELECT COUNT(*) FROM gamification_points 
                              WHERE id_user = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $q->execute([$idUser]);
            $poinBulanIni = (int)$q->fetchColumn();

            // Hitung rank global
            $q = $db->prepare("SELECT COUNT(DISTINCT id_user) + 1 FROM gamification_points 
                              WHERE (SELECT COALESCE(SUM(poin), 0) FROM gamification_points g2 
                                     WHERE g2.id_user = gamification_points.id_user) > ?");
            $q->execute([$totalPoin]);
            $rank = (int)$q->fetchColumn();

            // Badge selanjutnya
            $q = $db->prepare("SELECT * FROM gamification_badges 
                              WHERE is_active = 1 AND threshold > ?
                              ORDER BY threshold ASC LIMIT 1");
            $q->execute([$totalPoin]);
            $nextBadge = $q->fetch();

            return [
                'total_poin' => $totalPoin,
                'poin_bulan_ini' => $poinBulanIni,
                'rank' => $rank,
                'badges' => $badges,
                'breakdown' => $breakdown,
                'next_badge' => $nextBadge,
                'progress_to_next' => $nextBadge ? min(100, round(($totalPoin / $nextBadge['threshold']) * 100)) : 100
            ];
        } catch (Throwable $e) {
            error_log('GamificationEngine::getUserProfile: ' . $e->getMessage());
            return ['total_poin' => 0, 'badges' => [], 'rank' => 0];
        }
    }

    /**
     * Leaderboard global (top N user)
     */
    public static function getGlobalLeaderboard(int $limit = 20): array
    {
        try {
            $db = self::db();
            $q = $db->prepare("SELECT u.id_user, u.nama_lengkap, u.id_role, 
                                      COALESCE(SUM(p.poin), 0) AS total_poin,
                                      COUNT(DISTINCT ub.id_badge) AS total_badges
                              FROM users u
                              LEFT JOIN gamification_points p ON u.id_user = p.id_user
                              LEFT JOIN gamification_user_badges ub ON u.id_user = ub.id_user
                              GROUP BY u.id_user
                              ORDER BY total_poin DESC, total_badges DESC
                              LIMIT ?");
            $q->execute([$limit]);
            return $q->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}