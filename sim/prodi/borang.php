<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3, 1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;
if (Auth::role() == 1 && isset($_GET['prodi'])) $prodiId = (int)$_GET['prodi']; // admin bisa lihat semua

// Download proxy (sebelum output HTML)
if (isset($_GET['download'])) {
    $stmt = $db->prepare("SELECT * FROM borang_akreditasi WHERE id_borang = ?");
    $stmt->execute([(int)$_GET['download']]);
    $b = $stmt->fetch();
    if ($b && ($b['id_prodi'] == $prodiId || Auth::role() == 1)) {
        $uploadRoot = realpath(PATH_UPLOAD);
        $path = realpath(PATH_UPLOAD . $b['file_path']);
        if ($uploadRoot !== false && $path !== false && strpos($path, $uploadRoot . DIRECTORY_SEPARATOR) === 0 && is_file($path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
            readfile($path);
            exit;
        }
    }
    http_response_code(404); exit('File tidak ditemukan.');
}

if ($prodiId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        if (!isset($_FILES['file'])) { $error = 'Pilih file terlebih dahulu.'; }
        else {
            $up = Security::secureUpload($_FILES['file'], 'borang');
            if (!$up['success']) { $error = $up['message']; }
            else {
                $displayName = preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['file']['name']);
                $db->prepare("INSERT INTO borang_akreditasi (id_prodi, id_user, kriteria, nama_file, file_path, catatan) VALUES (?,?,?,?,?,?)")
                   ->execute([$prodiId, Auth::id(), $_POST['kriteria'], $displayName, $up['path'], trim($_POST['catatan'] ?? '')]);
                $pesan = '✅ File borang berhasil diunggah.';
            }
        }
    }

    if ($action === 'status') {
        $db->prepare("UPDATE borang_akreditasi SET status=? WHERE id_borang=? AND id_prodi=?")
           ->execute([$_POST['status'], (int)$_POST['id_borang'], $prodiId]);
        $pesan = '✅ Status borang diperbarui.';
    }

    if ($action === 'delete') {
        $idBorang = (int)($_POST['id_borang'] ?? 0);
        $stmt = $db->prepare("SELECT file_path FROM borang_akreditasi WHERE id_borang=? AND id_prodi=?");
        $stmt->execute([$idBorang, $prodiId]);
        $b = $stmt->fetch();
        if (!$b) {
            http_response_code(404);
            $error = 'Borang tidak ditemukan atau Anda tidak memiliki akses untuk menghapusnya.';
        } else {
            $uploadRoot = realpath(PATH_UPLOAD);
            $path = realpath(PATH_UPLOAD . $b['file_path']);
            if ($uploadRoot !== false && $path !== false && strpos($path, $uploadRoot . DIRECTORY_SEPARATOR) === 0 && is_file($path)) {
                if (!unlink($path)) $error = 'Catatan database tidak dihapus karena berkas gagal dihapus.';
            }
            if ($error === '') {
                $del = $db->prepare("DELETE FROM borang_akreditasi WHERE id_borang=? AND id_prodi=?");
                $del->execute([$idBorang, $prodiId]);
                $pesan = $del->rowCount() === 1 ? '✅ File borang dihapus.' : 'Borang tidak ditemukan atau sudah dihapus.';
            }
        }
    }
}

$borang = [];
if ($prodiId) {
    $stmt = $db->prepare("SELECT b.*, u.nama_lengkap FROM borang_akreditasi b LEFT JOIN users u ON b.id_user = u.id_user WHERE b.id_prodi=? ORDER BY b.uploaded_at DESC");
    $stmt->execute([$prodiId]);
    $borang = $stmt->fetchAll();
}

$simTitle = 'Cloud Borang Akreditasi';
$activeMenu = 'borang';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun Anda belum terhubung ke Program Studi. Hubungi Admin LPM.</div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;
?>

<style>
    .borang-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 28px 32px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .borang-header::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -80px; top: -100px; animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .borang-header h2 { font-size: 22px; margin: 0 0 8px; position: relative; font-weight: 800; }
    .borang-header p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; margin: 0; }
    
    .upload-card {
        background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 28px;
        box-shadow: 0 4px 12px rgba(0,0,0,.05); border: 2px solid var(--border);
    }
    .upload-card h3 { margin: 0 0 16px; font-size: 18px; display: flex; align-items: center; gap: 10px; }
    
    .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; }
    .file-input-wrapper input[type=file] {
        position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer;
    }
    .file-input-label {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 10px; border: 2px dashed var(--border); border-radius: 8px;
        color: var(--text-muted); font-size: 13px; transition: all 0.3s; background: var(--bg-light);
    }
    .file-input-wrapper:hover .file-input-label {
        border-color: var(--accent); color: var(--accent); background: rgba(201,162,39,.05);
    }
    
    .status-select {
        padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); font-size: 12px; font-weight: 600;
        background: #fff; cursor: pointer; transition: all 0.3s;
    }
    .status-select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(201,162,39,.15); }
    
    .kriteria-badge {
        display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;
        color: #fff; text-transform: uppercase; letter-spacing: 0.5px;
    }
</style>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="borang-header">
    <h2>☁️ Cloud Borang & LED Akreditasi</h2>
    <p>Unggah, kelola, dan pantau status kelengkapan dokumen borang akreditasi program studi Anda di satu tempat.</p>
</div>

<div class="upload-card">
    <h3>📤 Unggah Dokumen Baru</h3>
    <form method="POST" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="upload">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Kriteria / Jenis Dokumen</label>
                <select name="kriteria" class="form-control">
                    <option>LED</option><option>LKPS</option>
                    <?php for ($k = 1; $k <= 9; $k++): ?><option>Kriteria <?= $k ?></option><?php endfor; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">File Dokumen (Max 10MB)</label>
                <div class="file-input-wrapper">
                    <input type="file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
                    <div class="file-input-label"><span>📁</span> Klik atau seret file ke sini</div>
                </div>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Catatan (Opsional)</label>
                <input type="text" name="catatan" class="form-control" placeholder="cth: DK 1.1 - SK Dosen">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:16px;padding:10px 24px;font-weight:700;">📤 Unggah Dokumen</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Riwayat Unggahan</h3>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width:15%">Kriteria</th>
                    <th style="width:30%">Nama File & Pengunggah</th>
                    <th style="width:20%">Catatan</th>
                    <th style="width:15%;text-align:center;">Status</th>
                    <th style="width:20%;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($borang)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;padding:40px;">
                        <div style="font-size:48px;margin-bottom:12px;opacity:0.5;">📂</div>
                        <p class="text-muted">Belum ada file borang yang diunggah.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php 
                $katColors = ['LED' => '#3B82F6', 'LKPS' => '#8B5CF6'];
                foreach (range(1, 9) as $i) $katColors["Kriteria $i"] = '#F59E0B';
                ?>
                <?php foreach ($borang as $b): 
                    $katColor = $katColors[$b['kriteria']] ?? '#64748B';
                    $statusColor = match($b['status']) {
                        'Terkirim' => 'var(--success)',
                        'Final' => 'var(--primary)',
                        default => 'var(--text-muted)'
                    };
                ?>
                <tr>
                    <td style="vertical-align:middle;">
                        <span class="kriteria-badge" style="background:<?= $katColor ?>"><?= Security::e($b['kriteria']) ?></span>
                    </td>
                    <td style="vertical-align:middle;">
                        <strong style="font-size:14px;color:var(--text-dark);"><?= Security::e($b['nama_file']) ?></strong><br>
                        <small class="text-muted">oleh <?= Security::e($b['nama_lengkap']) ?> · <?= date('d M Y', strtotime($b['uploaded_at'])) ?></small>
                    </td>
                    <td style="vertical-align:middle;"><?= Security::e($b['catatan']) ?: '<span class="text-muted">—</span>' ?></td>
                    <td style="vertical-align:middle;text-align:center;">
                        <form method="POST" style="margin:0;display:inline-block;">
                            <input type="hidden" name="id_borang" value="<?= $b['id_borang'] ?>">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="status">
                            <select name="status" class="status-select" style="color:<?= $statusColor ?>" onchange="this.form.submit()">
                                <option <?= $b['status']==='Draf'?'selected':'' ?>>Draf</option>
                                <option <?= $b['status']==='Final'?'selected':'' ?>>Final</option>
                                <option <?= $b['status']==='Terkirim'?'selected':'' ?>>Terkirim</option>
                            </select>
                        </form>
                    </td>
                    <td style="vertical-align:middle;text-align:center;">
                        <div style="display:flex;gap:8px;justify-content:center;">
                            <a href="?download=<?= $b['id_borang'] ?>" class="btn btn-outline" style="padding:6px 12px;font-size:13px;" title="Unduh">📥</a>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Yakin ingin menghapus file ini? Tindakan ini tidak dapat dibatalkan.');">
                                <input type="hidden" name="id_borang" value="<?= $b['id_borang'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-outline" style="padding:6px 12px;font-size:13px;color:var(--danger);border-color:rgba(239,68,68,.3);" title="Hapus">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('.file-input-wrapper input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        const label = this.nextElementSibling;
        if (this.files.length > 0) {
            label.innerHTML = `<span>📄</span> ${this.files[0].name}`;
            label.style.borderColor = 'var(--success)';
            label.style.color = 'var(--success)';
            label.style.background = 'rgba(16,185,129,.05)';
        } else {
            label.innerHTML = `<span>📁</span> Klik atau seret file ke sini`;
            label.style.borderColor = ''; label.style.color = ''; label.style.background = '';
        }
    });
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>