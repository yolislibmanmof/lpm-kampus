<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();

function sliderGet($db) {
    $st = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'hero_slider'");
    $st->execute();
    $r = $st->fetch();
    return $r ? (string)$r['setting_value'] : '';
}
function sliderSave($db, $val) {
    $st = $db->prepare("SELECT setting_key FROM site_settings WHERE setting_key = 'hero_slider'");
    $st->execute();
    if ($st->fetch()) {
        $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'hero_slider'")->execute([$val]);
    } else {
        $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('hero_slider', ?)")->execute([$val]);
    }
}

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            $err = 'Pilih file foto terlebih dahulu.';
        } else {
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $err = 'Format harus JPG / PNG / WEBP.';
            } else {
                $dir = PATH_UPLOAD . 'slider/';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $dir . $name)) {
                    $lines = array_values(array_filter(array_map('trim', explode("\n", sliderGet($db)))));
                    $lines[] = 'slider/' . $name;
                    sliderSave($db, implode("\n", $lines));
                    $msg = '✅ Foto slider berhasil ditambahkan.';
                } else {
                    $err = 'Gagal menyimpan file. Pastikan folder uploads dapat ditulis.';
                }
            }
        }
    }

    if ($action === 'delete') {
        $path = trim($_POST['path'] ?? '');
        if (strpos($path, 'slider/') === 0) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", sliderGet($db)))));
            $lines = array_values(array_diff($lines, [$path]));
            sliderSave($db, implode("\n", $lines));
            @unlink(PATH_UPLOAD . $path);
            $msg = '🗑️ Foto slider dihapus.';
        } else {
            $err = 'Path tidak valid.';
        }
    }
}

$images = array_values(array_filter(array_map('trim', explode("\n", sliderGet($db)))));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Slider Beranda | SIM-Mutu</title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
    .sl-top { background: linear-gradient(90deg, #092A40, #0F3D5C); color: #fff; padding: 18px 0; }
    .sl-top .container { display: flex; justify-content: space-between; align-items: center; }
    .sl-top a { color: #E8C55A; text-decoration: none; font-weight: 700; font-size: 14px; }
    .sl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 18px; margin-top: 20px; }
    .sl-item { border-radius: 16px; overflow: hidden; border: 1px solid #E2E8F0; background: #fff; box-shadow: 0 1px 3px rgba(15,61,92,.08); }
    .sl-item img { width: 100%; height: 140px; object-fit: cover; display: block; }
    .sl-item form { padding: 10px; }
    .sl-empty { padding: 40px; text-align: center; color: #64748B; border: 2px dashed #E2E8F0; border-radius: 16px; margin-top: 18px; }
</style>
</head>
<body style="background:#F7F9FC;">

<div class="sl-top">
    <div class="container">
        <strong>🖼️ Manajemen Slider Beranda</strong>
        <a href="/sim/index.php">← Kembali ke Dasbor</a>
    </div>
</div>

<div class="container" style="padding:32px 24px 64px;">
    <?php if ($msg): ?><div class="alert alert-success"><?= Security::e($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-danger"><?= Security::e($err) ?></div><?php endif; ?>

    <div class="card" style="max-width:560px;">
        <h3 style="margin-bottom:6px;">Unggah Foto Slider</h3>
        <p class="text-muted" style="font-size:13.5px;margin-bottom:18px;">
            Foto tampil bergantian (crossfade ±5 detik) sebagai latar 4 kartu di beranda.
            Disarankan ukuran <strong>1600×900 px</strong>.
        </p>
        <form method="POST" enctype="multipart/form-data">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="upload">
            <div class="form-group">
                <label class="form-label">Pilih Foto (JPG/PNG/WEBP)</label>
                <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp" class="form-control" required>
            </div>
            <button class="btn btn-gold" type="submit">📤 Unggah & Tambahkan</button>
        </form>
    </div>

    <h3 style="margin:36px 0 4px;">Foto Saat Ini (<?= count($images) ?>)</h3>
    <p class="text-muted" style="font-size:13.5px;">Urutan daftar = urutan tayang slider.</p>

    <?php if (empty($images)): ?>
        <div class="sl-empty">Belum ada foto slider. Beranda memakai latar gradien bawaan.</div>
    <?php else: ?>
        <div class="sl-grid">
            <?php foreach ($images as $img): ?>
            <div class="sl-item">
                <img src="/uploads/<?= Security::e($img) ?>" alt="">
                <form method="POST" onsubmit="return confirm('Hapus foto ini?');">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="path" value="<?= Security::e($img) ?>">
                    <button class="btn btn-danger" type="submit" style="width:100%;padding:9px 0;font-size:13px;">🗑️ Hapus</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>