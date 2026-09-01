<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    if (($_POST['action'] ?? '') === 'status') {
        $db->prepare("UPDATE pengaduan SET status = ? WHERE id_pengaduan = ?")
           ->execute([$_POST['status'], (int)$_POST['id_pengaduan']]);
        $pesan = '✅ Status pengaduan diperbarui.';
    }
    if (($_POST['action'] ?? '') === 'delete') {
        $db->prepare("DELETE FROM pengaduan WHERE id_pengaduan = ?")->execute([(int)$_POST['id_pengaduan']]);
        $pesan = '✅ Pengaduan dihapus.';
    }
}

$pengaduan = $db->query("SELECT * FROM pengaduan ORDER BY created_at DESC")->fetchAll();

$simTitle = 'Pengaduan Publik';
$activeMenu = 'pengaduan';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div class="table-wrapper">
    <table>
        <thead><tr><th>Pelapor</th><th>Subjek & Isi</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (empty($pengaduan)): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;" class="text-muted">Belum ada pengaduan masuk. ✅</td></tr>
        <?php endif; ?>
        <?php foreach ($pengaduan as $p): ?>
            <tr>
                <td><strong><?= Security::e($p['nama']) ?></strong><br><small class="text-muted"><?= Security::e($p['email']) ?></small></td>
                <td><strong><?= Security::e($p['subjek']) ?></strong><br><small class="text-muted"><?= Security::e(mb_strimwidth($p['isi_pesan'], 0, 120, '...')) ?></small></td>
                <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="id_pengaduan" value="<?= $p['id_pengaduan'] ?>">
                        <?= Security::csrfField() ?><input type="hidden" name="action" value="status">
                        <select name="status" class="form-control" style="padding:5px;width:auto;" onchange="this.form.submit()">
                            <option <?= $p['status']==='Baru'?'selected':'' ?>>Baru</option>
                            <option <?= $p['status']==='Diproses'?'selected':'' ?>>Diproses</option>
                            <option <?= $p['status']==='Selesai'?'selected':'' ?>>Selesai</option>
                        </select>
                    </form>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('Hapus pengaduan?');">
                        <input type="hidden" name="id_pengaduan" value="<?= $p['id_pengaduan'] ?>">
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