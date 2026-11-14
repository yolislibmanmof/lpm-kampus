<?php
declare(strict_types=1);

final class Auth
{
    public static function attempt(string $email, string $password): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Throttle berbasis database (tidak bisa dijauhi dengan buang cookie)
        if (!Security::checkLoginThrottleDb($email, $ip)) {
            return ['success' => false, 'message' => 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.'];
        }

        $stmt = Database::getInstance()->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Dummy verify agar waktu respons sama baik user ada maupun tidak (anti user-enumeration)
        $dummyHash = '$2y$10$/yXYNn3Udrd/FvaDsSYqa.ahrXV2e8Bg3ZKQKN/23PWML84G0tW9K';
        $valid = password_verify($password, $user['password_hash'] ?? $dummyHash);

        if (!$user || !$valid) {
            Security::recordLoginAttempt(isset($user['id_user']) ? (int)$user['id_user'] : null, $email, $ip, false, 'kredensial_salah');
            Logger::log('LOGIN_GAGAL', 'Percobaan login gagal untuk: ' . $email);
            return ['success' => false, 'message' => 'Email atau password salah.'];
        }

        if ($user['blokir_sampai'] && strtotime($user['blokir_sampai']) > time()) {
            Security::recordLoginAttempt((int)$user['id_user'], $email, $ip, false, 'diblokir');
            return ['success' => false, 'message' => 'Akun sedang diblokir sementara.'];
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'    => $user['id_user'],
            'nama'  => $user['nama_lengkap'],
            'email' => $user['email'],
            'role'  => $user['id_role'],
            'ip'    => $ip,
            'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'time'  => time()
        ];

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $upd = Database::getInstance()->prepare('UPDATE users SET password_hash = :p WHERE id_user = :id');
            $upd->execute([':p' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user['id_user']]);
        }

        Database::getInstance()->prepare('UPDATE users SET last_login = NOW() WHERE id_user = :id')
            ->execute([':id' => $user['id_user']]);

        Security::recordLoginAttempt((int)$user['id_user'], $email, $ip, true);
        Logger::log('LOGIN', 'Login berhasil ke SIM-Mutu');

        // 🎮 Gamifikasi: poin login harian (sekali/hari, anti-farming). Tidak pernah melempar exception.
        require_once __DIR__ . '/GamificationEngine.php';
        GamificationEngine::addPoints((int)$user['id_user'], 'LOGIN', 'Login harian', null, null, true);

        return ['success' => true, 'role' => $user['id_role']];
    }

    public static function check(): bool { return isset($_SESSION['user']); }
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function id(): ?int { return $_SESSION['user']['id'] ?? null; }
    public static function role(): ?int { return $_SESSION['user']['role'] ?? null; }

    public static function requireRole(array $allowedRoles): void
    {
        if (!self::check()) { header('Location: /login.php'); exit; }

        if ($_SESSION['user']['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '') ||
            $_SESSION['user']['agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            self::logout();
            header('Location: /login.php?expired=1');
            exit;
        }

        if (time() - ($_SESSION['user']['time'] ?? 0) > 7200) {
            self::logout();
            header('Location: /login.php?expired=1');
            exit;
        }

        if (!in_array(self::role(), $allowedRoles, true)) {
            header('Location: /sim/403.php');
            exit;
        }
    }

    public static function logout(): void
    {
        if (self::check()) Logger::log('LOGOUT', 'Keluar dari SIM-Mutu');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}