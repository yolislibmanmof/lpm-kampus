<?php
declare(strict_types=1);

final class Security
{
    private static array $allowedExt = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'webp', 'ico', 'svg'
    ];

    /** Pasangan ekstensi -> MIME yang diizinkan (cegah file menyamar). */
    private static array $extMime = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt'  => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'ico'  => ['image/x-icon', 'image/vnd.microsoft.icon'],
        'svg'  => ['image/svg+xml', 'text/plain', 'text/xml', 'application/xml'],
    ];

    public static function e(?string $v): string
    {
        return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
    }

    /* ---------- HEADERS KEAMANAN (selaras dengan .htaccess) ---------- */
    public static function sendHeaders(): void
    {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (($_SERVER['SERVER_PORT'] ?? 0) == 443)
                    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 0');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header("Content-Security-Policy: default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: blob: https:; " .
               "media-src 'self' data: blob:; " .
               "connect-src 'self' https://cdn.jsdelivr.net; " .
               "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'lifetime' => 0, 'path' => '/', 'httponly' => true,
                'samesite' => 'Lax', 'secure' => $isSecure
            ]);
        }
    }

    /* ---------- CSRF ---------- */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }

    public static function verifyCsrf(): void
    {
        $t = $_POST['csrf_token'] ?? '';
        if (!$t || !hash_equals($_SESSION['_csrf'] ?? '', $t)) {
            http_response_code(419);
            exit('Sesi tidak valid (CSRF). Silakan muat ulang halaman.');
        }
    }

    /* ---------- UPLOAD AMAN ---------- */
    public static function secureUpload(array $file, string $subdir): array
    {
        $fail = function (string $m) { return ['success' => false, 'message' => $m, 'path' => '']; };

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return $fail('File gagal diunggah.');
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return $fail('Upload tidak valid.');
        if (($file['size'] ?? 0) > (defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : 10 * 1024 * 1024)) return $fail('Ukuran file melebihi batas.');

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExt, true)) return $fail('Tipe file tidak diizinkan.');

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Validasi pasangan ekstensi <-> MIME (bukan sekadar dua whitelist terpisah)
        if (!isset(self::$extMime[$ext]) || !in_array($mime, self::$extMime[$ext], true)) {
            return $fail('Isi file tidak sesuai dengan ekstensinya.');
        }

        // Gambar raster harus benar-benar gambar
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            if (@getimagesize($file['tmp_name']) === false) return $fail('File gambar rusak / tidak valid.');
        }

        // SVG: tolak konten aktif (script / event handler / entity / php)
        if ($ext === 'svg') {
            $svg = (string)file_get_contents($file['tmp_name']);
            if ($svg === '' || preg_match('/<script\b|on\w+\s*=|javascript\s*:|<!ENTITY|<\?php/i', $svg)) {
                return $fail('SVG mengandung konten tidak aman.');
            }
        }

        $base = defined('PATH_UPLOAD') ? PATH_UPLOAD : dirname(__DIR__) . '/uploads/';
        $dir = rtrim($base, '/\\') . '/' . preg_replace('/[^a-z0-9_-]/', '', strtolower($subdir));
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $name = date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return $fail('Gagal menyimpan file.');

        return ['success' => true, 'message' => '', 'path' => basename($dir) . '/' . $name];
    }

    /* ---------- RATE LIMIT LOGIN (berbasis sesi — dipertahankan utk kompatibilitas) ---------- */
    public static function checkLoginThrottle(string $ip): bool
    {
        $t = $_SESSION['_throttle'][$ip] ?? null;
        if ($t && $t['lock_until'] > time()) return false;
        return true;
    }

    public static function registerFailedLogin(string $ip): void
    {
        $t = $_SESSION['_throttle'][$ip] ?? ['count' => 0, 'lock_until' => 0];
        $t['count']++;
        if ($t['count'] >= 5) {
            $t['lock_until'] = time() + 900; // 15 menit
            $t['count'] = 0;
        }
        $_SESSION['_throttle'][$ip] = $t;
    }

    /* ---------- RATE LIMIT LOGIN BERBASIS DATABASE (tabel login_attempts) ---------- */
    public static function checkLoginThrottleDb(string $email, string $ip): bool
    {
        try {
            $db = Database::getInstance();
            $q = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)");
            $q->execute([$email]);
            if ((int)$q->fetchColumn() >= 5) return false;
            $q = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)");
            $q->execute([$ip]);
            if ((int)$q->fetchColumn() >= 20) return false;
            return true;
        } catch (Throwable $e) { error_log('checkLoginThrottleDb: '.$e->getMessage()); return true; }
    }

    public static function recordLoginAttempt(?int $idUser, string $email, string $ip, bool $success, string $reason = ''): void
    {
        try {
            Database::getInstance()->prepare("INSERT INTO login_attempts (id_user, email, ip_address, success, failed_reason, user_agent) VALUES (?, ?, ?, ?, ?, ?)")
               ->execute([$idUser, mb_substr($email,0,100), $ip, $success?1:0, $reason!==''?$reason:null, mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255)]);
        } catch (Throwable $e) { error_log('recordLoginAttempt: '.$e->getMessage()); }
    }

    public static function check2faThrottle(int $idUser): bool
    {
        try {
            $q = Database::getInstance()->prepare("SELECT COUNT(*) FROM login_attempts WHERE id_user = ? AND success = 0 AND failed_reason LIKE '2fa%' AND created_at > (NOW() - INTERVAL 15 MINUTE)");
            $q->execute([$idUser]);
            return (int)$q->fetchColumn() < 5;
        } catch (Throwable $e) { error_log('check2faThrottle: '.$e->getMessage()); return true; }
    }
}