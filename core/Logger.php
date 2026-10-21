<?php
final class Logger
{
    public static function log(string $aksi, string $detail): void
    {
        try {
            $user = Auth::user();
            $namaUser = 'Sistem';
            
            // Ambil nama dengan aman, mendukung Array maupun Object
            if (is_array($user)) {
                $namaUser = $user['nama_lengkap'] ?? $user['nama'] ?? 'Sistem';
            } elseif (is_object($user)) {
                $namaUser = $user->nama_lengkap ?? $user->nama ?? 'Sistem';
            }

            Database::getInstance()->prepare(
                "INSERT INTO activity_log (id_user, nama_user, aksi, detail, ip_address) VALUES (?,?,?,?,?)"
            )->execute([
                Auth::id(),
                $namaUser,
                $aksi,
                $detail,
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (Throwable $e) {
            // Gagal logging tidak boleh mengganggu proses utama (silent fail)
        }
    }
}