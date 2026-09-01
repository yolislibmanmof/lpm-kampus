<?php
declare(strict_types=1);

final class Security
{
    private static array $allowedExt = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'ico', 'svg'
    ];

    public static function e(?string $v): string
    {
        return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
    }

    /* ---------- HEADERS KEAMANAN (v3.1 final) ---------- */
    public static function sendHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header("Content-Security-Policy: default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: blob:; " .
               "connect-src 'self' https://cdn.jsdelivr.net; " .
               "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'lifetime' => 0, 'path' => '/', 'httponly' => true,
                'samesite' => 'Lax', 'secure' => isset($_SERVER['HTTPS'])
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
        if ($file['size'] > (defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : 10 * 1024 * 1024)) return $fail('Ukuran file melebihi batas.');

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExt, true)) return $fail('Tipe file tidak diizinkan.');

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $safeMime = [
            'application/pdf', 'application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg', 'image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'
        ];
        if (!in_array($mime, $safeMime, true)) return $fail('Isi file tidak sesuai tipenya.');

        $base = defined('PATH_UPLOAD') ? PATH_UPLOAD : dirname(__DIR__) . '/uploads/';
        $dir = rtrim($base, '/\\') . '/' . preg_replace('/[^a-z0-9_-]/', '', strtolower($subdir));
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $name = date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return $fail('Gagal menyimpan file.');

        return ['success' => true, 'message' => '', 'path' => basename($dir) . '/' . $name];
    }

    /* ---------- RATE LIMIT LOGIN (berbasis sesi) ---------- */
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
}