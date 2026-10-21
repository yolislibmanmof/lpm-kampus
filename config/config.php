<?php
declare(strict_types=1);

// 1. Muat Autoloader Composer (untuk vlucas/phpdotenv)
$rootDir = dirname(__DIR__);
if (file_exists($rootDir . '/vendor/autoload.php')) {
    require_once $rootDir . '/vendor/autoload.php';
    
    // Muat file .env secara aman
    if (file_exists($rootDir . '/.env')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable($rootDir);
            $dotenv->safeLoad();
        } catch (Exception $e) {
            error_log('Gagal memuat .env: ' . $e->getMessage());
        }
    }
}

// 2. Helper function untuk mengambil nilai ENV
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false) {
            return $default;
        }
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

// Direktori upload
define('PATH_UPLOAD', rtrim(dirname(__DIR__), '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'webp', 'ico', 'svg']);

// ==================== SESSION AMAN ====================
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || $_SERVER['SERVER_PORT'] == 443
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_name('LPM_SID');
    session_start();
}

// Regenerasi session berkala (anti session fixation)
if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - $_SESSION['_created'] > 1800) { // 30 menit
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}

// ==================== AUTOLOAD CLASS (DIPERBAIKI TOTAL) ====================
spl_autoload_register(function (string $class): void {
    // PERBAIKAN: Gunakan dirname(__DIR__) secara langsung di dalam closure 
    // agar tidak terjadi error "Undefined variable"
    $coreDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core';
    $file = $coreDir . DIRECTORY_SEPARATOR . $class . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Fallback ke folder includes jika diperlukan
    $incDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes';
    $fileInc = $incDir . DIRECTORY_SEPARATOR . $class . '.php';
    if (file_exists($fileInc)) {
        require_once $fileInc;
    }
});

// ==================== ERROR HANDLING ====================
if (APP_ENV === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    $logPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'php_errors.log';
    ini_set('error_log', $logPath);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

date_default_timezone_set('Asia/Jakarta');