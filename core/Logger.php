<?php
final class Logger
{
    public static function log(string $aksi, string $detail): void
    {
        try {
            Database::getInstance()->prepare(
                "INSERT INTO activity_log (id_user, nama_user, aksi, detail, ip_address) VALUES (?,?,?,?,?)"
            )->execute([
                Auth::id(),
                Auth::user()['nama'] ?? 'Sistem',
                $aksi,
                $detail,
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (Throwable $e) {}
    }
}