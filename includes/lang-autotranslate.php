<?php
/**
 * Auto-translate site-wide untuk halaman publik.
 * Aktif hanya saat lang = en, via output buffering.
 * PENTING: blok <script>, <style>, dan komentar HTML dilindungi agar
 * JavaScript/CSS tidak rusak oleh terjemahan (mis. apostrof Inggris).
 */
if (!function_exists('lpm_autotranslate_start')) {
    function lpm_autotranslate_start() {
        if (lang() === 'en') {
            ob_start('lpm_autotranslate_page');
        }
    }
}

if (!function_exists('lpm_autotranslate_page')) {
    function lpm_autotranslate_page($html) {
        $map = require __DIR__ . '/lang-page-map.php';

        // 1) Amankan blok script/style/komentar dengan placeholder sementara
        $protected = [];
        $html = preg_replace_callback(
            '/(<script\b[^>]*>.*?<\/script>)|(<style\b[^>]*>.*?<\/style>)|(<!--.*?-->)/is',
            function ($m) use (&$protected) {
                $token = "\x1ALPM_PROTECT_" . count($protected) . "\x1A";
                $protected[$token] = $m[0];
                return $token;
            },
            $html
        );

        // 2) Terjemahkan hanya konten HTML yang terlihat
        $html = strtr($html, $map);

        // 3) Kembalikan blok yang diamankan (tidak ikut diterjemahkan)
        if ($protected) {
            $html = strtr($html, $protected);
        }
        return $html;
    }
}