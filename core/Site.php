<?php
final class Site
{
    private static array $cache = [];

    public static function setting(string $key, string $default = ''): string
    {
        if (empty(self::$cache)) {
            try {
                foreach (Database::getInstance()->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll() as $r) {
                    self::$cache[$r['setting_key']] = $r['setting_value'];
                }
            } catch (Throwable $e) {
                return $default;
            }
        }
        return self::$cache[$key] ?? $default;
    }

    public static function flush(): void { self::$cache = []; }

    // URL publik untuk file di folder uploads
    public static function fileUrl(?string $path): string
    {
        return '/uploads/' . Security::e($path ?? '');
    }

    public static function brand(string $mode = 'publik'): string
    {
        $utama = self::setting('brand_utama', 'LPM');
        $aksen = self::setting('brand_aksen', 'Kampus');
        $logo  = self::setting('logo_path');
        $inisial = Security::e(mb_substr($utama, 0, 1));

        $imgPublik = $logo
            ? '<img src="' . self::fileUrl($logo) . '" alt="Logo" style="width:44px;height:44px;border-radius:10px;object-fit:cover;">'
            : '<div class="brand-logo">' . $inisial . '</div>';

        $imgSim = $logo
            ? '<img src="' . self::fileUrl($logo) . '" alt="Logo" style="width:36px;height:36px;border-radius:8px;object-fit:cover;">'
            : '<div class="brand-logo" style="width:36px;height:36px;font-size:16px;">' . $inisial . '</div>';

        switch ($mode) {
            case 'publik':
                return '<a href="/index.php" class="brand">' . $imgPublik .
                       '<div class="brand-text">' . Security::e($utama) . ' <span>' . Security::e($aksen) . '</span></div></a>';
            case 'sim':
                return '<a href="/sim/index.php" class="brand" style="margin-bottom:8px;">' . $imgSim .
                       '<div class="brand-text" style="color:#fff;font-size:18px;">' . Security::e($utama) . ' <span style="color:var(--accent-light);">' . Security::e($aksen) . '</span></div></a>';
            case 'footer':
                return '<div class="brand-text" style="color:#fff;">' . Security::e($utama) . ' <span>' . Security::e($aksen) . '</span></div>';
            case 'login':
                return '<div class="brand-text" style="color:#fff;font-size:28px;margin-bottom:40px;">' . Security::e($utama) . ' <span style="color:var(--accent-light);">' . Security::e($aksen) . '</span></div>';
        }
        return '';
    }
}