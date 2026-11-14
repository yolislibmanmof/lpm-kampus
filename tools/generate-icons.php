<?php
/**
 * GENERATOR IKON PWA — LPM Kampus
 * Cara pakai (pilih salah satu):
 *   CLI : php tools/generate-icons.php
 *   Web : login sebagai Admin LPM, lalu buka /tools/generate-icons.php
 * Menghasilkan assets/icons/icon-72..512.png memakai logo yang diupload
 * (site_settings logo_path). Jika tidak ada logo, dibuat monogram premium.
 */
require_once dirname(__DIR__) . '/config/config.php';

if (PHP_SAPI !== 'cli') {
    Auth::requireRole([1]); // hanya Admin LPM
    header('Content-Type: text/plain; charset=utf-8');
}
if (!extension_loaded('gd')) { exit("Ekstensi GD tidak tersedia di server.\n"); }

$root = dirname(__DIR__);
$dir  = $root . '/assets/icons';
if (!is_dir($dir)) { mkdir($dir, 0755, true); }

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// ---- Coba ambil logo yang diupload (aman: harus di dalam PATH_UPLOAD) ----
$logo = null;
try {
    $p = Site::setting('logo_path');
    if ($p) {
        $real = realpath(PATH_UPLOAD . $p);
        $up   = realpath(PATH_UPLOAD);
        if ($real && $up && str_starts_with($real, $up . DIRECTORY_SEPARATOR) && is_file($real)) {
            $mime = (string)@mime_content_type($real);
            $img  = false;
            if ($mime === 'image/png')  $img = @imagecreatefrompng($real);
            elseif ($mime === 'image/jpeg') $img = @imagecreatefromjpeg($real);
            elseif ($mime === 'image/gif')  $img = @imagecreatefromgif($real);
            elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) $img = @imagecreatefromwebp($real);
            if ($img) { $logo = $img; echo "Memakai logo: $p\n"; }
        }
    }
} catch (Throwable $e) { /* fallback monogram */ }
if (!$logo) echo "Logo tidak ditemukan — memakai monogram premium.\n";

// ---- Master 512px: gradient navy + aksen emas ----
$M = 512;
$master = imagecreatetruecolor($M, $M);
for ($y = 0; $y < $M; $y++) {
    $t = $y / $M;
    $col = imagecolorallocate($master,
        (int)round(15 + (6 - 15) * $t),
        (int)round(61 + (29 - 61) * $t),
        (int)round(92 + (46 - 92) * $t)
    );
    imageline($master, 0, $y, $M, $y, $col);
}
$gold  = imagecolorallocate($master, 201, 162, 39);
$white = imagecolorallocate($master, 255, 255, 255);
imagefilledrectangle($master, 0, $M - 26, $M, $M, $gold);

if ($logo) {
    $lw = imagesx($logo); $lh = imagesy($logo);
    $box = (int)($M * 0.66);
    $scale = min($box / $lw, $box / $lh);
    $nw = max(1, (int)($lw * $scale)); $nh = max(1, (int)($lh * $scale));
    $nx = (int)(($M - $nw) / 2); $ny = (int)(($M - $nh) / 2) - 8;
    imagealphablending($master, true);
    imagecopyresampled($master, $logo, $nx, $ny, 0, 0, $nw, $nh, $lw, $lh);
} else {
    $bx = (int)($M * 0.33); $by = (int)($M * 0.26);
    $bw = (int)($M * 0.085); $bh = (int)($M * 0.42);
    imagefilledrectangle($master, $bx, $by, $bx + $bw, $by + $bh, $white);
    imagefilledrectangle($master, $bx, $by + $bh - $bw, $bx + (int)($M * 0.28), $by + $bh, $white);
    $d = (int)($M * 0.10);
    imagefilledellipse($master, $bx + (int)($M * 0.34), $by + (int)($M * 0.09), $d, $d, $gold);
}

foreach ($sizes as $s) {
    $im = imagecreatetruecolor($s, $s);
    imagecopyresampled($im, $master, 0, 0, 0, 0, $s, $s, $M, $M);
    imagepng($im, $dir . '/icon-' . $s . '.png', 9);
    imagedestroy($im);
    echo "OK assets/icons/icon-$s.png\n";
}
imagedestroy($master);
if ($logo) imagedestroy($logo);
echo "\nSelesai! Ikon PWA siap. Silakan hapus file ini dari server bila diinginkan.\n";