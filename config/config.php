<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__);

// 1. Muat .env — via Composer jika ada, fallback parser mandiri jika tidak
if (file_exists($rootDir . '/vendor/autoload.php')) {
    require_once $rootDir . '/vendor/autoload.php';
    if (file_exists($rootDir . '/.env')) {
        try {
            Dotenv\Dotenv::createImmutable($rootDir)->safeLoad();
        } catch (Throwable $e) {
            error_log('Gagal memuat .env: ' . $e->getMessage());
        }
    }
} elseif (file_exists($rootDir . '/.env')) {
    // Fallback tanpa Composer (shared hosting)
    foreach (file($rootDir . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if ($v !== '' && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
            $v = substr($v, 1, -1);
        }
        if ($k !== '' && !array_key_exists($k, $_ENV)) {
            $_ENV[$k] = $v; $_SERVER[$k] = $v; putenv("$k=$v");
        }
    }
}

// 2. Helper ENV
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false) return $default;
        if (is_string($value)) {
            if (strtolower($value) === 'true') return true;
            if (strtolower($value) === 'false') return false;
        }
        return $value;
    }
}

// ==================== KONFIGURASI SISTEM ====================
define('APP_NAME', (string)env('APP_NAME', 'LPM Kampus'));
define('APP_ENV', (string)env('APP_ENV', 'development'));
define('APP_URL', rtrim((string)env('APP_URL', 'http://localhost'), '/'));

define('DB_HOST', (string)env('DB_HOST', 'localhost'));
define('DB_PORT', (int)env('DB_PORT', 3306));
define('DB_NAME', (string)env('DB_DATABASE', 'db_lpm_kampus'));
define('DB_USER', (string)env('DB_USERNAME', 'root'));
define('DB_PASS', (string)env('DB_PASSWORD', ''));

define('PATH_UPLOAD', rtrim(dirname(__DIR__), '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','webp','ico','svg']);

// ==================== SESSION AMAN ====================
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['SERVER_PORT'] ?? 0) == 443)
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'domain' => '',
        'secure' => $isSecure, 'httponly' => true, 'samesite' => 'Lax'
    ]);
    session_name('LPM_SID');
    session_start();
}

if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - $_SESSION['_created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}

// ==================== AUTOLOAD (core + core/adapters + includes) ====================
spl_autoload_register(function (string $class) use ($rootDir): void {
    foreach (['core', 'core/adapters', 'includes'] as $dir) {
        $file = $rootDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $class . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});

// ==================== ERROR HANDLING ====================
if (APP_ENV === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $rootDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
date_default_timezone_set('Asia/Jakarta');

// ==================== 2FA PENDING GATE (anti-loop, subfolder-safe) ====================
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['require_2fa'])) {
    if (isset($_SESSION['require_2fa_expires']) && $_SESSION['require_2fa_expires'] < time()) {
        $_SESSION = [];
        session_regenerate_id(true);
        if (!headers_sent()) { header('Location: /login.php?expired=1'); exit; }
    } else {
        // basename + SCRIPT_NAME: tahan subfolder & PATH_INFO
        $self = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if (!in_array($self, ['2fa-verify.php', 'logout.php'], true) && !headers_sent()) {
            header('Location: /2fa-verify.php');
            exit;
        }
    }
}