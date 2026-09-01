<?php
/**
 * TEMPLATE KONFIGURASI — SALIN MENJADI config.php
 * 
 * Cara pakai:
 * 1. Copy file ini menjadi: config/config.php
 * 2. Isi sesuai database & email Anda
 * 3. Jangan commit config.php ke Git!
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'sim_mutu');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'SIM-Mutu LPM');
define('APP_URL', 'http://localhost/lpm-kampus');
define('APP_ENV', 'development'); // production | development

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PATH_UPLOAD', __DIR__ . '/../uploads/');

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'email-anda@gmail.com');
define('MAIL_PASS', 'app-password');
define('MAIL_FROM', 'noreply@lpm-kampus.local');

// Session & Security
define('SESSION_NAME', 'lpm_session');
define('CSRF_TOKEN_NAME', '_csrf_token');