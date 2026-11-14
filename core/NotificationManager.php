<?php
declare(strict_types=1);

/**
 * NotificationManager
 * Mengelola notifikasi in-app pengguna SIM-Mutu.
 *
 * Catatan teknis:
 * PDO berjalan dengan ATTR_EMULATE_PREPARES = false (native prepares).
 * Nilai LIMIT tidak boleh di-bind sebagai string lewat execute() karena
 * MySQL menolaknya (error 1210) dan membuat hasil kosong senyap.
 * Karena itu LIMIT ditanam langsung sebagai integer yang di-cast & dibatasi.
 */
final class NotificationManager
{
    /** Batas atas jumlah notifikasi yang boleh diambil sekali panggil. */
    private const MAX_LIMIT = 50;

    /**
     * Ambil jumlah notifikasi yang belum dibaca.
     */
    public static function getUnreadCount(int $idUser): int
    {
        try {
            $stmt = Database::getInstance()->prepare(
                "SELECT COUNT(*) AS c FROM notifications WHERE id_user = ? AND is_read = 0"
            );
            $stmt->execute([$idUser]);
            return (int)($stmt->fetch()['c'] ?? 0);
        } catch (Throwable $e) {
            error_log('[NotificationManager] getUnreadCount: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ambil daftar notifikasi terbaru milik pengguna.
     */
    public static function getRecent(int $idUser, int $limit = 8): array
    {
        try {
            $limit = max(1, min(self::MAX_LIMIT, $limit));
            $stmt = Database::getInstance()->prepare(
                "SELECT * FROM notifications WHERE id_user = ? ORDER BY created_at DESC LIMIT " . $limit
            );
            $stmt->execute([$idUser]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('[NotificationManager] getRecent: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Tandai satu notifikasi sebagai dibaca (hanya milik pengguna ybs).
     */
    public static function markAsRead(int $notifId, int $idUser): bool
    {
        try {
            $stmt = Database::getInstance()->prepare(
                "UPDATE notifications SET is_read = 1 WHERE id_notif = ? AND id_user = ?"
            );
            $stmt->execute([$notifId, $idUser]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('[NotificationManager] markAsRead: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Tandai SEMUA notifikasi pengguna sebagai dibaca.
     */
    public static function markAllAsRead(int $idUser): bool
    {
        try {
            $stmt = Database::getInstance()->prepare(
                "UPDATE notifications SET is_read = 1 WHERE id_user = ? AND is_read = 0"
            );
            $stmt->execute([$idUser]);
            return true;
        } catch (Throwable $e) {
            error_log('[NotificationManager] markAllAsRead: ' . $e->getMessage());
            return false;
        }
    }
}