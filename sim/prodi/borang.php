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
        $path = PATH_UPLOAD . $b['file_path'];
        if (file_exists($path)) {
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
        $stmt = $db->prepare("SELECT file_path FROM borang_akreditasi WHERE id_borang=? AND id_prodi=?");
        $stmt->execute([(int)$_POST['id_borang'], $prodiId]);
        $b = $stmt->fetch();
        if ($b && file_exists(PATH_UPLOAD . $b['file_path'])) unlink(PATH_UPLOAD . $b['file_path']);
        $db->prepare("DELETE FROM borang_akreditasi WHERE id_borang=?")->execute([(int)$_POST['id_borang']]);
        $pesan = '✅ File borang dihapus.';
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

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">📤 Unggah Dokumen Borang / LED</h3>
    <form method="POST" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="upload">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            <div class="form-group"><label class="form-label">Kriteria</label>
                <select name="kriteria" class="form-control">
                    <option>LED</option><option>LKPS</option>
                    <?php for ($k = 1; $k <= 9; $k++): ?><option>Kriteria <?= $k ?></option><?php endfor; ?>
                </select></div>
            <div class="form-group"><label class="form-label">File (max 10MB)</label>
                <input type="file" name="file" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Catatan</label>
                <input type="text" name="catatan" class="form-control" placeholder="cth: DK 1.1 - SK Dosen"></div>
        </div>
        <button type="submit" class="btn btn-primary">📤 Unggah</button>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead><tr><th>Kriteria</th><th>Nama File</th><th>Catatan</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (empty($borang)): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;" class="text-muted">Belum ada file borang.</td></tr>
        <?php endif; ?>
        <?php foreach ($borang as $b): ?>
            <tr>
                <td><span class="badge badge-a"><?= Security::e($b['kriteria']) ?></span></td>
                <td><strong><?= Security::e($b['nama_file']) ?></strong><br><small class="text-muted">oleh <?= Security::e($b['nama_lengkap']) ?> · <?= date('d M Y', strtotime($b['uploaded_at'])) ?></small></td>
                <td><?= Security::e($b['catatan']) ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="id_borang" value="<?= $b['id_borang'] ?>">
                        <?= Security::csrfField() ?><input type="hidden" name="action" value="status">
                        <select name="status" class="form-control" style="padding:5px;width:auto;" onchange="this.form.submit()">
                            <option <?= $b['status']==='Draf'?'selected':'' ?>>Draf</option>
                            <option <?= $b['status']==='Final'?'selected':'' ?>>Final</option>
                            <option <?= $b['status']==='Terkirim'?'selected':'' ?>>Terkirim</option>
                        </select>
                    </form>
                </td>
                <td>
                    <a href="?download=<?= $b['id_borang'] ?>" class="btn btn-outline" style="padding:5px 12px;font-size:12px;">📥</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus file?');">
                        <input type="hidden" name="id_borang" value="<?= $b['id_borang'] ?>">
                        <?= Security::csrfField() ?><input type="hidden" name="action" value="delete">
                        <button class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>