<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $db->prepare("UPDATE users SET id_fakultas = ? WHERE id_user = ? AND id_role = 5")
       ->execute([(int)$_POST['id_fakultas'] ?: null, (int)$_POST['id_user']]);
    $pesan = '✅ Penugasan fakultas disimpan.';
}

$gpm = $db->query("SELECT u.*, f.nama_fakultas FROM users u LEFT JOIN fakultas f ON u.id_fakultas = f.id_fakultas WHERE u.id_role = 5 ORDER BY u.nama_lengkap")->fetchAll();
$fakList = $db->query("SELECT * FROM fakultas ORDER BY nama_fakultas")->fetchAll();

$simTitle = 'Penugasan GPM';
$activeMenu = 'gpm';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div class="card">
    <h3 style="margin-bottom:6px;">🏢 Penugasan GPM Fakultas</h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:20px;">Buat akun baru dengan role <strong>GPM Fakultas</strong> di menu 👥 Manajemen Pengguna, lalu ikatkan ke fakultas di sini.</p>
    <?php if (empty($gpm)): ?><p class="text-muted">Belum ada user GPM.</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Nama</th><th>Email</th><th>Fakultas Saat Ini</th><th>Tugaskan Ke</th></tr></thead>
            <tbody>
            <?php foreach ($gpm as $u): ?>
                <tr>
                    <td><strong><?= Security::e($u['nama_lengkap']) ?></strong></td>
                    <td><?= Security::e($u['email']) ?></td>
                    <td><?= Security::e($u['nama_fakultas'] ?? '— belum ditugaskan —') ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                            <select name="id_fakultas" class="form-control" style="width:auto;">
                                <option value="0">— Pilih Fakultas —</option>
                                <?php foreach ($fakList as $f): ?><option value="<?= $f['id_fakultas'] ?>" <?= $u['id_fakultas'] == $f['id_fakultas'] ? 'selected' : '' ?>><?= Security::e($f['nama_fakultas']) ?></option><?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary" style="padding:8px 16px;font-size:12px;">💾</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>