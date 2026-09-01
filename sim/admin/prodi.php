<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_fakultas') {
        $nama = trim($_POST['nama_fakultas'] ?? '');
        if ($nama) {
            $db->prepare("INSERT INTO fakultas (nama_fakultas) VALUES (?)")->execute([$nama]);
            $pesan = '✅ Fakultas ditambahkan.';
        } else { $error = 'Nama fakultas wajib diisi.'; }
    }

    if ($action === 'add_prodi') {
        $kode = trim($_POST['kode_prodi'] ?? '');
        $nama = trim($_POST['nama_prodi'] ?? '');
        $fid  = (int)($_POST['id_fakultas'] ?? 0);
        if ($kode && $nama && $fid) {
            try {
                $db->prepare("INSERT INTO prodi (id_fakultas, kode_prodi, nama_prodi) VALUES (?,?,?)")
                   ->execute([$fid, $kode, $nama]);
                $pesan = '✅ Program studi ditambahkan.';
            } catch (PDOException $e) { $error = 'Kode prodi sudah digunakan.'; }
        } else { $error = 'Lengkapi semua kolom prodi.'; }
    }

    if ($action === 'edit_prodi') {
        $db->prepare("UPDATE prodi SET kode_prodi=?, nama_prodi=?, id_fakultas=? WHERE id_prodi=?")
           ->execute([trim($_POST['kode_prodi']), trim($_POST['nama_prodi']), (int)$_POST['id_fakultas'], (int)$_POST['id_prodi']]);
        $pesan = '✅ Prodi diperbarui.';
    }

    if ($action === 'delete_prodi') {
        try {
            $db->prepare("DELETE FROM prodi WHERE id_prodi=?")->execute([(int)$_POST['id_prodi']]);
            $pesan = '✅ Prodi dihapus.';
        } catch (PDOException $e) {
            $error = 'Tidak bisa menghapus: prodi masih dipakai data lain (akreditasi/audit/user).';
        }
    }

    if ($action === 'delete_fakultas') {
        try {
            $db->prepare("DELETE FROM fakultas WHERE id_fakultas=?")->execute([(int)$_POST['id_fakultas']]);
            $pesan = '✅ Fakultas dihapus.';
        } catch (PDOException $e) {
            $error = 'Tidak bisa menghapus: fakultas masih memiliki prodi.';
        }
    }
}

// Mode edit (jika ada ?edit=ID)
$editProdi = null;
if (!empty($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM prodi WHERE id_prodi = ?");
    $st->execute([(int)$_GET['edit']]);
    $editProdi = $st->fetch();
}

$fakultas = $db->query("SELECT f.*, (SELECT COUNT(*) FROM prodi p WHERE p.id_fakultas = f.id_fakultas) AS jml_prodi FROM fakultas f ORDER BY f.nama_fakultas")->fetchAll();
$prodi = $db->query("SELECT p.*, f.nama_fakultas FROM prodi p LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas ORDER BY f.nama_fakultas, p.nama_prodi")->fetchAll();

$simTitle = 'Data Prodi & Fakultas';
$activeMenu = 'prodi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<!-- Form Tambah / Edit -->
<div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;margin-bottom:28px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">🏛️ Tambah Fakultas</h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="add_fakultas">
            <div class="form-group"><label class="form-label">Nama Fakultas</label>
                <input type="text" name="nama_fakultas" class="form-control" placeholder="cth: Fakultas Teknik" required></div>
            <button type="submit" class="btn btn-primary">💾 Simpan</button>
        </form>
    </div>

    <div class="card" style="<?= $editProdi ? 'border:2px solid var(--accent);' : '' ?>">
        <h3 style="margin-bottom:16px;"><?= $editProdi ? '✏️ Edit' : '🎓 Tambah' ?> Program Studi</h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="<?= $editProdi ? 'edit_prodi' : 'add_prodi' ?>">
            <?php if ($editProdi): ?><input type="hidden" name="id_prodi" value="<?= $editProdi['id_prodi'] ?>"><?php endif; ?>
            <div style="display:grid;grid-template-columns:1fr 2fr 1fr;gap:12px;">
                <div class="form-group"><label class="form-label">Kode</label>
                    <input type="text" name="kode_prodi" class="form-control" value="<?= Security::e($editProdi['kode_prodi'] ?? '') ?>" placeholder="IF" required></div>
                <div class="form-group"><label class="form-label">Nama Prodi</label>
                    <input type="text" name="nama_prodi" class="form-control" value="<?= Security::e($editProdi['nama_prodi'] ?? '') ?>" placeholder="Informatika" required></div>
                <div class="form-group"><label class="form-label">Fakultas</label>
                    <select name="id_fakultas" class="form-control" required>
                        <?php foreach ($fakultas as $f): ?>
                            <option value="<?= $f['id_fakultas'] ?>" <?= ($editProdi['id_fakultas'] ?? '') == $f['id_fakultas'] ? 'selected' : '' ?>><?= Security::e($f['nama_fakultas']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
            <button type="submit" class="btn <?= $editProdi ? 'btn-gold' : 'btn-primary' ?>">💾 <?= $editProdi ? 'Perbarui' : 'Simpan Prodi' ?></button>
            <?php if ($editProdi): ?><a href="/sim/admin/prodi.php" class="btn btn-outline">Batal</a><?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabel Fakultas -->
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:16px;">🏛️ Daftar Fakultas</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Nama Fakultas</th><th>Jumlah Prodi</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($fakultas)): ?><tr><td colspan="3" style="text-align:center;padding:30px;" class="text-muted">Belum ada fakultas.</td></tr><?php endif; ?>
            <?php foreach ($fakultas as $f): ?>
                <tr>
                    <td><strong><?= Security::e($f['nama_fakultas']) ?></strong></td>
                    <td><span class="badge badge-a"><?= $f['jml_prodi'] ?> prodi</span></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus fakultas?');">
                            <input type="hidden" name="id_fakultas" value="<?= $f['id_fakultas'] ?>">
                            <?= Security::csrfField() ?>
                            <button name="action" value="delete_fakultas" class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tabel Prodi -->
<div class="card">
    <h3 style="margin-bottom:16px;">🎓 Daftar Program Studi</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Kode</th><th>Nama Prodi</th><th>Fakultas</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($prodi)): ?><tr><td colspan="4" style="text-align:center;padding:30px;" class="text-muted">Belum ada prodi.</td></tr><?php endif; ?>
            <?php foreach ($prodi as $p): ?>
                <tr>
                    <td><strong><?= Security::e($p['kode_prodi']) ?></strong></td>
                    <td><?= Security::e($p['nama_prodi']) ?></td>
                    <td><?= Security::e($p['nama_fakultas']) ?></td>
                    <td>
                        <a href="?edit=<?= $p['id_prodi'] ?>" class="btn btn-outline" style="padding:5px 12px;font-size:12px;">✏️</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus prodi?');">
                            <input type="hidden" name="id_prodi" value="<?= $p['id_prodi'] ?>">
                            <?= Security::csrfField() ?>
                            <button name="action" value="delete_prodi" class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>