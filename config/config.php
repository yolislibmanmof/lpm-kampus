<?php
declare(strict_types=1);

// ==================== KONFIGURASI SISTEM ====================
define('APP_NAME', 'LPM Kampus');
define('APP_URL', 'http://lpm-kampus');             // URL lokal Laragon
define('APP_ENV', 'development');                  // ganti 'production' saat online

define('DB_HOST', 'localhost');
define('DB_NAME', 'db_lpm_kampus');
define('DB_USER', 'root');                         // default Laragon
define('DB_PASS', '');                             // default Laragon kosong

// Direktori upload
define('PATH_UPLOAD', dirname(__DIR__) . '/uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','png']);

// ==================== SESSION AMAN ====================
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_name('LPM_SID');
    session_start();
}

// Regenerasi session berkala (anti session fixation)
if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - $_SESSION['_created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}

// ==================== AUTOLOAD & ERROR HANDLING ====================
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../core/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

if (APP_ENV === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');   // tampilkan error saat development
}

date_default_timezone_set('Asia/Jakarta');