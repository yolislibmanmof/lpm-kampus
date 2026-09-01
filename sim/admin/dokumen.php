<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $judul = trim($_POST['judul_dokumen'] ?? '');
        if (!$judul || !isset($_FILES['file'])) {
            $error = 'Judul dan file wajib diisi.';
        } else {
            $up = Security::secureUpload($_FILES['file'], 'dokumen');
            if (!$up['success']) {
                $error = $up['message'];
            } else {
                $db->prepare("INSERT INTO dokumen_mutu (id_kategori, id_user, kode_dokumen, judul_dokumen, deskripsi, file_path, tipe_akses) VALUES (?,?,?,?,?,?,?)")
                   ->execute([
                       (int)$_POST['id_kategori'], Auth::id(), trim($_POST['kode_dokumen'] ?? ''),
                       $judul, trim($_POST['deskripsi'] ?? ''), $up['path'], $_POST['tipe_akses']
                   ]);
                $pesan = '✅ Dokumen berhasil diunggah.';
            }
        }
    }

    if ($action === 'status') {
        $db->prepare("UPDATE dokumen_mutu SET status = ? WHERE id_dokumen = ?")
           ->execute([$_POST['status'], (int)$_POST['id_dokumen']]);
        $pesan = '✅ Status dokumen diperbarui.';
    }

    if ($action === 'delete') {
        $stmt = $db->prepare("SELECT file_path FROM dokumen_mutu WHERE id_dokumen = ?");
        $stmt->execute([(int)$_POST['id_dokumen']]);
        $doc = $stmt->fetch();
        if ($doc && file_exists(PATH_UPLOAD . $doc['file_path'])) {
            unlink(PATH_UPLOAD . $doc['file_path']);
        }
        $db->prepare("DELETE FROM dokumen_mutu WHERE id_dokumen = ?")->execute([(int)$_POST['id_dokumen']]);
        $pesan = '✅ Dokumen dihapus.';
    }
}

$dokumen = $db->query("SELECT d.*, k.nama_kategori, u.nama_lengkap FROM dokumen_mutu d LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori LEFT JOIN users u ON d.id_user = u.id_user ORDER BY d.uploaded_at DESC")->fetchAll();
$kategori = $db->query("SELECT * FROM kategori_dokumen")->fetchAll();

$simTitle = 'Dokumen PPEPP';
$activeMenu = 'dokumen';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">📤 Upload Dokumen Mutu</h3>
    <form method="POST" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="upload">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            <div class="form-group"><label class="form-label">Kode Dokumen</label>
                <input type="text" name="kode_dokumen" class="form-control" placeholder="MM-01-2026"></div>
            <div class="form-group"><label class="form-label">Judul Dokumen *</label>
                <input type="text" name="judul_dokumen" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Kategori</label>
                <select name="id_kategori" class="form-control">
                    <?php foreach ($kategori as $k): ?><option value="<?= $k['id_kategori'] ?>"><?= Security::e($k['nama_kategori']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Akses</label>
                <select name="tipe_akses" class="form-control">
                    <option value="internal">Internal (login)</option>
                    <option value="publik">Publik</option>
                </select></div>
            <div class="form-group"><label class="form-label">File (PDF/Doc/Xls, max 10MB)</label>
                <input type="file" name="file" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="2"></textarea></div>
        <button type="submit" class="btn btn-primary">📤 Unggah Dokumen</button>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead><tr><th>Kode</th><th>Judul</th><th>Kategori</th><th>Akses</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($dokumen as $d): ?>
            <tr>
                <td><?= Security::e($d['kode_dokumen']) ?></td>
                <td><strong><?= Security::e($d['judul_dokumen']) ?></strong><br><small class="text-muted">oleh <?= Security::e($d['nama_lengkap']) ?></small></td>
                <td><?= Security::e($d['nama_kategori']) ?></td>
                <td><span class="badge <?= $d['tipe_akses'] === 'publik' ? 'badge-unggul' : 'badge-b' ?>"><?= $d['tipe_akses'] ?></span></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>">
                        <?= Security::csrfField() ?><input type="hidden" name="action" value="status">
                        <select name="status" class="form-control" style="padding:5px;width:auto;display:inline-block;" onchange="this.form.submit()">
                            <option <?= $d['status']==='Aktif'?'selected':'' ?>>Aktif</option>
                            <option <?= $d['status']==='Revisi'?'selected':'' ?>>Revisi</option>
                            <option <?= $d['status']==='Nonaktif'?'selected':'' ?>>Nonaktif</option>
                        </select>
                    </form>
                </td>
                <td>
                    <a href="/download.php?id=<?= $d['id_dokumen'] ?>" class="btn btn-outline" style="padding:5px 12px;font-size:12px;">📥</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus dokumen?');">
                        <input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>">
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