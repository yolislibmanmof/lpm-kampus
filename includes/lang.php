<?php
/**
 * Multi-language helper untuk LPM Kampus
 * Support: ID (default) & EN
 */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!function_exists('lang')) {
    function lang() {
        if (!isset($_SESSION['lang'])) $_SESSION['lang'] = 'id';
        $allowed = ['id', 'en'];
        $requested = $_GET['lang'] ?? $_SESSION['lang'];
        if (in_array($requested, $allowed)) {
            $_SESSION['lang'] = $requested;
            return $requested;
        }
        return 'id';
    }
}

if (!function_exists('t')) {
    function t($key, $default = null) {
        static $translations = null;
        if ($translations === null) {
            $file = __DIR__ . '/lang-data.php';
            $translations = file_exists($file) ? require $file : [];
        }
        $lang = lang();
        return $translations[$key][$lang] ?? $default ?? $translations[$key]['id'] ?? $key;
    }
}

if (!function_exists('currentLangUrl')) {
    function currentLangUrl($targetLang) {
        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        $params = $_GET;
        $params['lang'] = $targetLang;
        return $uri . '?' . http_build_query($params);
    }
}