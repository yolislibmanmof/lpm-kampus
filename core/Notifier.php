<?php
final class Notifier
{
    public static function send(int $idUser, string $judul, string $isi, string $url = '/sim/index.php', string $icon = '🔔'): void
    {
        try {
            Database::getInstance()->prepare(
                "INSERT INTO notifications (id_user, judul, isi, url, icon) VALUES (?,?,?,?,?)"
            )->execute([$idUser, $judul, $isi, $url, $icon]);
        } catch (Throwable $e) {}
    }

    // Kirim ke semua user dengan role tertentu (1=Admin, 2=Pimpinan, 3=Kaprodi, 4=Auditor)
    public static function sendRole(int $role, string $judul, string $isi, string $url = '/sim/index.php', string $icon = '🔔'): void
    {
        try {
            $s = Database::getInstance()->prepare("SELECT id_user FROM users WHERE id_role = ? AND is_active = 1");
            $s->execute([$role]);
            foreach ($s->fetchAll() as $u) {
                self::send((int)$u['id_user'], $judul, $isi, $url, $icon);
            }
        } catch (Throwable $e) {}
    }

    public static function unread(int $idUser): int
    {
        try {
            $s = Database::getInstance()->prepare("SELECT COUNT(*) c FROM notifications WHERE id_user = ? AND is_read = 0");
            $s->execute([$idUser]);
            return (int)$s->fetch()['c'];
        } catch (Throwable $e) { return 0; }
    }
}