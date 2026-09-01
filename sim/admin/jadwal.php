<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_jadwal') {
        $db->prepare("INSERT INTO jadwal_ami (tahun_ami, tanggal_mulai, tanggal_selesai, status) VALUES (?,?,?,'Draf')")
           ->execute([(int)$_POST['tahun_ami'], $_POST['tanggal_mulai'], $_POST['tanggal_selesai']]);
        $pesan = '✅ Jadwal AMI dibuat.';
    }

    if ($action === 'create_tugas') {
        $db->prepare("INSERT INTO penugasan_audit (id_jadwal, id_prodi, id_auditor, tanggal_audit) VALUES (?,?,?,?)")
           ->execute([(int)$_POST['id_jadwal'], (int)$_POST['id_prodi'], (int)$_POST['id_auditor'], $_POST['tanggal_audit']]);
        $pesan = '✅ Auditor berhasil diplot ke prodi.';
    }

    if ($action === 'delete_tugas') {
        $db->prepare("DELETE FROM penugasan_audit WHERE id_tugas = ?")->execute([(int)$_POST['id_tugas']]);
        $pesan = '✅ Penugasan dihapus.';
    }
}

$jadwal  = $db->query("SELECT * FROM jadwal_ami ORDER BY tahun_ami DESC")->fetchAll();
$tugas   = $db->query("SELECT t.*, p.nama_prodi, u.nama_lengkap AS auditor, j.tahun_ami FROM penugasan_audit t LEFT JOIN prodi p ON t.id_prodi = p.id_prodi LEFT JOIN users u ON t.id_auditor = u.id_user LEFT JOIN jadwal_ami j ON t.id_jadwal = j.id_jadwal ORDER BY t.tanggal_audit DESC")->fetchAll();
$prodi   = $db->query("SELECT * FROM prodi ORDER BY nama_prodi")->fetchAll();
$auditor = $db->query("SELECT id_user, nama_lengkap FROM users WHERE id_role = 4 AND is_active = 1")->fetchAll();

$simTitle = 'Penjadwalan AMI';
$activeMenu = 'jadwal';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
    <!-- Buat Jadwal -->
    <div class="card">
        <h3 style="margin-bottom:20px;">📅 Buat Jadwal AMI</h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="create_jadwal">
            <div class="form-group"><label class="form-label">Tahun AMI</label>
                <input type="number" name="tahun_ami" class="form-control" value="<?= date('Y') ?>" required></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group"><label class="form-label">Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control" required></div>
            </div>
            <button type="submit" class="btn btn-primary">💾 Simpan Jadwal</button>
        </form>
    </div>

    <!-- Ploting Auditor -->
    <div class="card">
        <h3 style="margin-bottom:20px;">🎯 Ploting Auditor ke Prodi</h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="create_tugas">
            <div class="form-group"><label class="form-label">Jadwal AMI</label>
                <select name="id_jadwal" class="form-control">
                    <?php foreach ($jadwal as $j): ?><option value="<?= $j['id_jadwal'] ?>">AMI <?= $j['tahun_ami'] ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Program Studi</label>
                <select name="id_prodi" class="form-control">
                    <?php foreach ($prodi as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Auditor</label>
                <select name="id_auditor" class="form-control">
                    <?php foreach ($auditor as $a): ?><option value="<?= $a['id_user'] ?>"><?= Security::e($a['nama_lengkap']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Tanggal Audit</label>
                <input type="date" name="tanggal_audit" class="form-control" required></div>
            <button type="submit" class="btn btn-gold">🎯 Tugaskan</button>
        </form>
    </div>
</div>

<!-- Daftar Penugasan -->
<div class="table-wrapper">
    <table>
        <thead><tr><th>Tahun AMI</th><th>Program Studi</th><th>Auditor</th><th>Tanggal Audit</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (empty($tugas)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;" class="text-muted">Belum ada penugasan audit.</td></tr>
        <?php endif; ?>
        <?php foreach ($tugas as $t): ?>
            <tr>
                <td><?= $t['tahun_ami'] ?></td>
                <td><strong><?= Security::e($t['nama_prodi']) ?></strong></td>
                <td><?= Security::e($t['auditor']) ?></td>
                <td><?= date('d M Y', strtotime($t['tanggal_audit'])) ?></td>
                <td><span class="badge badge-b"><?= Security::e($t['status']) ?></span></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Hapus penugasan?');">
                        <input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
                        <?= Security::csrfField() ?><input type="hidden" name="action" value="delete_tugas">
                        <button class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>