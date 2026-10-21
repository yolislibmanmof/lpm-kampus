<?php
declare(strict_types=1);

final class NotificationManager
{
    /**
     * Ambil jumlah notifikasi yang belum dibaca
     */
    public static function getUnreadCount(int $idUser): int
    {
        try {
            $stmt = Database::getInstance()->prepare("SELECT COUNT(*) as c FROM notifications WHERE id_user = ? AND is_read = 0");
            $stmt->execute([$idUser]);
            return (int)($stmt->fetch()['c'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Ambil daftar notifikasi terbaru
     */
    public static function getRecent(int $idUser, int $limit = 8): array
    {
        try {
            $stmt = Database::getInstance()->prepare("SELECT * FROM notifications WHERE id_user = ? ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([$idUser, $limit]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Tandai satu notifikasi sebagai dibaca
     */
    public static function markAsRead(int $notifId, int $idUser): bool
    {
        try {
            $stmt = Database::getInstance()->prepare("UPDATE notifications SET is_read = 1 WHERE id_notif = ? AND id_user = ?");
            $stmt->execute([$notifId, $idUser]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Tandai SEMUA notifikasi pengguna sebagai dibaca
     */
    public static function markAllAsRead(int $idUser): bool
    {
        try {
            $stmt = Database::getInstance()->prepare("UPDATE notifications SET is_read = 1 WHERE id_user = ? AND is_read = 0");
            $stmt->execute([$idUser]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}