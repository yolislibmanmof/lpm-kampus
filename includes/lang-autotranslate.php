<?php
/**
 * Auto-translate site-wide untuk halaman publik.
 * Aktif hanya saat lang = en, via output buffering bertingkat.
 */
if (!function_exists('lpm_autotranslate_start')) {
    function lpm_autotranslate_start() {
        if (lang() === 'en') {
            ob_start('lpm_autotranslate_page');   // tanpa cek ob_get_level()
        }
    }
}

if (!function_exists('lpm_autotranslate_page')) {
    function lpm_autotranslate_page($html) {
        $map = require __DIR__ . '/lang-page-map.php';
        return strtr($html, $map);
    }
}