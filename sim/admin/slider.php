<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();

$simTitle   = 'Slider Beranda';
$activeMenu = 'slider';

/* ===================================================================
   HELPER: Get/Save slider data (JSON format dengan backward compat)
   =================================================================== */
function sliderGet($db) {
    $st = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'hero_slider'");
    $st->execute();
    $r = $st->fetch();
    $raw = $r ? (string)$r['setting_value'] : '';
    if (empty($raw)) return [];
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) return $decoded;
    $lines = array_values(array_filter(array_map('trim', explode("\n", $raw))));
    $migrated = [];
    foreach ($lines as $path) {
        $migrated[] = ['path' => $path, 'caption' => '', 'link' => '', 'active' => true];
    }
    sliderSave($db, $migrated);
    return $migrated;
}

function sliderSave($db, array $data) {
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $st = $db->prepare("SELECT setting_key FROM site_settings WHERE setting_key = 'hero_slider'");
    $st->execute();
    if ($st->fetch()) {
        $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'hero_slider'")->execute([$json]);
    } else {
        $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('hero_slider', ?)")->execute([$json]);
    }
}

function compressImage($source, $dest, $quality = 80) {
    $info = @getimagesize($source);
    if (!$info) return false;
    switch ($info[2]) {
        case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($source); break;
        case IMAGETYPE_PNG:  $img = imagecreatefrompng($source); imagepalettetotruecolor($img); break;
        case IMAGETYPE_WEBP: $img = imagecreatefromwebp($source); break;
        default: return false;
    }
    $result = imagejpeg($img, $dest, $quality);
    imagedestroy($img);
    return $result;
}

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    $images = sliderGet($db);

    if ($action === 'upload') {
        if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            $err = 'Pilih file foto terlebih dahulu.';
        } else {
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $err = 'Format harus JPG / PNG / WEBP.';
            } else {
                $dir = PATH_UPLOAD . 'slider/';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';
                $target = $dir . $name;
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) {
                    if (filesize($target) > 2 * 1024 * 1024 || $ext !== 'jpg') compressImage($target, $target, 82);
                    $images[] = [
                        'path' => 'slider/' . $name,
                        'caption' => trim($_POST['caption'] ?? ''),
                        'link' => trim($_POST['link'] ?? ''),
                        'active' => true,
                    ];
                    sliderSave($db, $images);
                    $msg = '✅ Foto slider berhasil ditambahkan.';
                } else {
                    $err = 'Gagal menyimpan file. Pastikan folder uploads dapat ditulis.';
                }
            }
        }
    }

    if ($action === 'delete') {
        $path = trim($_POST['path'] ?? '');
        $realBase = realpath(PATH_UPLOAD . 'slider/');
        $realTarget = realpath(PATH_UPLOAD . $path);
        if ($realTarget && $realBase && strpos($realTarget, $realBase) === 0 && is_file($realTarget)) {
            @unlink($realTarget);
            $images = array_values(array_filter($images, fn($img) => $img['path'] !== $path));
            sliderSave($db, $images);
            $msg = '🗑️ Foto slider dihapus.';
        } else { $err = 'Path tidak valid atau tidak aman.'; }
    }

    if ($action === 'toggle') {
        $path = trim($_POST['path'] ?? '');
        foreach ($images as &$img) if ($img['path'] === $path) { $img['active'] = !($img['active'] ?? true); break; }
        unset($img);
        sliderSave($db, $images);
        $msg = '✓ Status foto diperbarui.';
    }

    if ($action === 'update') {
        $path = trim($_POST['path'] ?? '');
        foreach ($images as &$img) {
            if ($img['path'] === $path) {
                $img['caption'] = trim($_POST['caption'] ?? '');
                $img['link'] = trim($_POST['link'] ?? '');
                break;
            }
        }
        unset($img);
        sliderSave($db, $images);
        $msg = '✓ Metadata foto diperbarui.';
    }

    if ($action === 'reorder') {
        $order = $_POST['order'] ?? [];
        if (is_array($order)) {
            $map = []; foreach ($images as $img) $map[$img['path']] = $img;
            $new = [];
            foreach ($order as $p) if (isset($map[$p])) $new[] = $map[$p];
            foreach ($images as $img) if (!in_array($img['path'], $order)) $new[] = $img;
            sliderSave($db, $new);
            $msg = '✓ Urutan foto diperbarui.';
        }
    }
}

$images = sliderGet($db);
$activeCount = count(array_filter($images, fn($i) => ($i['active'] ?? true)));

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    /* ===== SLIDER ADMIN — Dark Mode Ready ===== */
    .sl-stats { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
    .sl-stat { padding: 8px 16px; border-radius: 50px; background: var(--bg-card); border: 1px solid var(--border); font-size: 12.5px; font-weight: 700; color: var(--text-dark); }
    .sl-stat b { color: var(--accent); }

    .sl-info {
        background: linear-gradient(135deg, rgba(201,162,39,.08), rgba(15,61,92,.04));
        border: 1px solid rgba(201,162,39,.25); border-left: 5px solid #C9A227;
        border-radius: 14px; padding: 18px 22px; margin-bottom: 24px;
        display: flex; gap: 14px; align-items: flex-start;
    }
    .sl-info-ic {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        background: linear-gradient(135deg, #C9A227, #E8C55A);
        display: grid; place-items: center; font-size: 18px;
    }
    .sl-info h4 { margin: 0 0 4px; font-size: 14px; color: var(--text-dark); }
    .sl-info p { margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.5; }
    .sl-info strong { color: var(--text-dark); }

    .sl-upload {
        background: var(--bg-card); border: 1px solid var(--border);
        border-radius: 20px; padding: 28px; max-width: 640px; margin-bottom: 32px;
        box-shadow: var(--shadow-sm);
    }
    .sl-upload h3 { margin: 0 0 8px; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
    .sl-upload-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; }
    @media (max-width: 640px) { .sl-upload-row { grid-template-columns: 1fr; } }

    .sl-section-head { display: flex; align-items: center; gap: 10px; margin: 36px 0 4px; }
    .sl-section-head h3 { margin: 0; color: var(--text-dark); }
    .sl-section-head .sl-count {
        background: rgba(201,162,39,.12); color: #C9A227;
        padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 800;
    }

    .sl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
    .sl-item {
        border-radius: 18px; overflow: hidden; border: 2px solid var(--border);
        background: var(--bg-card); box-shadow: var(--shadow-sm);
        transition: all .3s var(--ease-out); cursor: grab; position: relative;
    }
    .sl-item.inactive { opacity: .5; filter: grayscale(.6); }
    .sl-item.dragging { opacity: .4; cursor: grabbing; transform: scale(.98); }
    .sl-item.drag-over { border-color: #C9A227; border-style: dashed; transform: scale(1.02); }
    .sl-item:hover { box-shadow: 0 12px 32px rgba(15,61,92,.15); transform: translateY(-3px); }
    .sl-item-img { width: 100%; height: 180px; object-fit: cover; display: block; }
    .sl-item-badge {
        position: absolute; top: 10px; left: 10px; z-index: 2;
        padding: 4px 10px; border-radius: 50px; font-size: 10px; font-weight: 800;
        letter-spacing: .8px; text-transform: uppercase; color: #fff;
    }
    .sl-item-badge.active { background: #10B981; }
    .sl-item-badge.inactive { background: #64748B; }
    .sl-item-order {
        position: absolute; top: 10px; right: 10px; z-index: 2;
        width: 32px; height: 32px; border-radius: 50%;
        background: #0F3D5C; color: #E8C55A;
        display: grid; place-items: center; font-size: 14px; font-weight: 900;
        box-shadow: 0 4px 12px rgba(0,0,0,.25);
    }
    .sl-item-body { padding: 14px; }
    .sl-item-caption, .sl-item-link {
        width: 100%; padding: 8px 12px; border: 1.5px solid var(--border);
        border-radius: 10px; font-size: 13px; font-family: inherit;
        background: var(--bg-light); color: var(--text-dark);
        margin-bottom: 8px; transition: .2s;
    }
    .sl-item-link { font-size: 12px; margin-bottom: 12px; }
    .sl-item-caption:focus, .sl-item-link:focus {
        outline: none; border-color: #C9A227; box-shadow: 0 0 0 3px rgba(201,162,39,.15);
    }
    .sl-item-link::placeholder { color: var(--text-muted); }

    .sl-item-actions { display: flex; gap: 6px; }
    .sl-item-actions form { flex: 1; }
    .sl-btn-sm {
        width: 100%; padding: 8px 0; border: none; border-radius: 8px;
        font-size: 12px; font-weight: 700; cursor: pointer; transition: .2s;
        font-family: inherit; color: #fff;
    }
    .sl-btn-save { background: #10B981; } .sl-btn-save:hover { background: #059669; }
    .sl-btn-toggle { background: #3B82F6; } .sl-btn-toggle:hover { background: #2563EB; }
    .sl-btn-delete { background: #EF4444; } .sl-btn-delete:hover { background: #DC2626; }

    .sl-empty {
        padding: 60px 40px; text-align: center; color: var(--text-muted);
        border: 2px dashed var(--border); border-radius: 20px; margin-top: 20px;
    }
    .sl-empty-ic { font-size: 64px; margin-bottom: 16px; opacity: .4; }
    .sl-empty h3 { color: var(--text-dark); }

    @keyframes slideIn {
        from { transform: translateX(100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
</style>

<div class="sl-stats">
    <span class="sl-stat">📸 Total: <b><?= count($images) ?></b></span>
    <span class="sl-stat">✓ Aktif: <b><?= $activeCount ?></b></span>
</div>

<div class="sl-info">
    <div class="sl-info-ic">💡</div>
    <div>
        <h4>Tips Slider Beranda</h4>
        <p>
            <strong>Drag & drop</strong> kartu untuk ubah urutan •
            <strong>Caption</strong> tampil sebagai subtitle di hero •
            <strong>Link</strong> membuat foto bisa diklik (kosongkan jika tidak perlu) •
            Foto <strong>&gt;2MB otomatis dikompres</strong> ke 82% kualitas
        </p>
    </div>
</div>

<div class="sl-upload">
    <h3>📤 Tambah Foto Baru</h3>
    <p class="text-muted" style="font-size:13.5px;margin:0 0 14px;">
        Disarankan ukuran <strong>1600×900 px</strong> dengan rasio 16:9.
    </p>
    <form method="POST" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="upload">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Pilih Foto (JPG/PNG/WEBP)</label>
            <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp" class="form-control" required>
        </div>
        <div class="sl-upload-row">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Caption (opsional)</label>
                <input type="text" name="caption" class="form-control" placeholder="cth: Gedung Rektorat">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Link (opsional)</label>
                <input type="text" name="link" class="form-control" placeholder="cth: /publik/profil.php">
            </div>
        </div>
        <button class="btn btn-gold" type="submit" style="margin-top:14px;width:100%;justify-content:center;">
            📤 Unggah & Tambahkan
        </button>
    </form>
</div>

<div class="sl-section-head">
    <h3>🎞️ Foto Saat Ini</h3>
    <span class="sl-count"><?= count($images) ?> foto</span>
</div>
<p class="text-muted" style="font-size:13.5px;">Drag & drop kartu untuk mengubah urutan tayang.</p>

<?php if (empty($images)): ?>
    <div class="sl-empty">
        <div class="sl-empty-ic">🖼️</div>
        <h3>Belum ada foto slider</h3>
        <p>Beranda akan memakai latar gradien bawaan. Unggah foto pertama Anda di atas.</p>
    </div>
<?php else: ?>
    <div class="sl-grid" id="sliderGrid">
        <?php foreach ($images as $idx => $img): $isActive = $img['active'] ?? true; ?>
        <div class="sl-item <?= $isActive ? '' : 'inactive' ?>" draggable="true" data-path="<?= Security::e($img['path']) ?>">
            <span class="sl-item-badge <?= $isActive ? 'active' : 'inactive' ?>"><?= $isActive ? '● Aktif' : '○ Nonaktif' ?></span>
            <span class="sl-item-order"><?= $idx + 1 ?></span>
            <img class="sl-item-img" src="/uploads/<?= Security::e($img['path']) ?>" alt="">
            <div class="sl-item-body">
                <form method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="path" value="<?= Security::e($img['path']) ?>">
                    <input type="text" name="caption" class="sl-item-caption" value="<?= Security::e($img['caption'] ?? '') ?>" placeholder="Caption foto...">
                    <input type="text" name="link" class="sl-item-link" value="<?= Security::e($img['link'] ?? '') ?>" placeholder="Link (cth: /publik/profil.php)">
                    <button type="submit" class="sl-btn-sm sl-btn-save" style="margin-bottom:6px;">💾 Simpan Metadata</button>
                </form>
                <div class="sl-item-actions">
                    <form method="POST">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="path" value="<?= Security::e($img['path']) ?>">
                        <button type="submit" class="sl-btn-sm sl-btn-toggle"><?= $isActive ? '⏸️ Nonaktifkan' : '▶️ Aktifkan' ?></button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Hapus foto ini?');">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="path" value="<?= Security::e($img['path']) ?>">
                        <button type="submit" class="sl-btn-sm sl-btn-delete">🗑️</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
(function() {
    'use strict';
    var grid = document.getElementById('sliderGrid');
    if (!grid) return;
    var draggedItem = null;

    grid.querySelectorAll('.sl-item').forEach(function(item) {
        item.addEventListener('dragstart', function(e) {
            draggedItem = item;
            setTimeout(function() { item.classList.add('dragging'); }, 0);
            e.dataTransfer.effectAllowed = 'move';
        });
        item.addEventListener('dragend', function() {
            item.classList.remove('dragging');
            grid.querySelectorAll('.sl-item').forEach(function(el) { el.classList.remove('drag-over'); });
            draggedItem = null;
        });
        item.addEventListener('dragover', function(e) {
            e.preventDefault(); e.dataTransfer.dropEffect = 'move';
            if (item !== draggedItem) item.classList.add('drag-over');
        });
        item.addEventListener('dragleave', function() { item.classList.remove('drag-over'); });
        item.addEventListener('drop', function(e) {
            e.preventDefault(); item.classList.remove('drag-over');
            if (!draggedItem || draggedItem === item) return;
            var all = Array.from(grid.querySelectorAll('.sl-item'));
            all.indexOf(draggedItem) < all.indexOf(item)
                ? grid.insertBefore(draggedItem, item.nextSibling)
                : grid.insertBefore(draggedItem, item);
            grid.querySelectorAll('.sl-item').forEach(function(el, i) {
                el.querySelector('.sl-item-order').textContent = i + 1;
            });
            var formData = new FormData();
            formData.append('action', 'reorder');
            Array.from(grid.querySelectorAll('.sl-item')).forEach(function(el) {
                formData.append('order[]', el.dataset.path);
            });
            fetch(window.location.href, { method: 'POST', body: formData }).then(function() {
                var toast = document.createElement('div');
                toast.className = 'alert alert-success';
                toast.textContent = '✓ Urutan foto diperbarui.';
                toast.style.cssText = 'position:fixed;top:80px;right:24px;z-index:9999;max-width:320px;animation:slideIn .3s ease;';
                document.body.appendChild(toast);
                setTimeout(function() { toast.remove(); }, 2500);
            });
        });
    });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>