<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

// Muat daftar fakultas terlebih dahulu untuk validasi
$fakList = $db->query("SELECT * FROM fakultas ORDER BY nama_fakultas")->fetchAll();
$fakIds = array_map('intval', array_column($fakList, 'id_fakultas'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    
    $idUser = (int)($_POST['id_user'] ?? 0);
    $idFak  = !empty($_POST['id_fakultas']) ? (int)$_POST['id_fakultas'] : null;

    // Validasi: Pastikan user ada dan benar-benar ber-role GPM (5)
    $stmt = $db->prepare("SELECT nama_lengkap FROM users WHERE id_user = ? AND id_role = 5");
    $stmt->execute([$idUser]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = '❌ Pengguna tidak valid atau bukan ber-peran GPM Fakultas.';
    } elseif ($idFak !== null && !in_array($idFak, $fakIds, true)) {
        $error = ' Fakultas yang dipilih tidak valid.';
    } else {
        try {
            $db->prepare("UPDATE users SET id_fakultas = ? WHERE id_user = ? AND id_role = 5")
               ->execute([$idFak, $idUser]);
            Logger::log('UPDATE', 'Menugaskan GPM ' . $user['nama_lengkap'] . ' ke fakultas ID ' . ($idFak ?? 'NULL (Umum)'));
            $pesan = '✅ Penugasan fakultas berhasil disimpan.';
        } catch (PDOException $e) {
            $error = '❌ Gagal menyimpan penugasan: ' . $e->getMessage();
        }
    }
}

$gpm = $db->query("SELECT u.*, f.nama_fakultas FROM users u LEFT JOIN fakultas f ON u.id_fakultas = f.id_fakultas WHERE u.id_role = 5 ORDER BY u.nama_lengkap")->fetchAll();

$simTitle = 'Penugasan GPM';
$activeMenu = 'gpm';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;">🏢 Penugasan GPM Fakultas</h3>
        <a href="/sim/admin/users.php" class="btn btn-outline" style="padding:6px 14px;font-size:12px;">➕ Tambah User GPM</a>
    </div>
    <p class="text-muted" style="font-size:13px;margin-bottom:20px;">Buat akun baru dengan role <strong>GPM Fakultas</strong> di menu Manajemen Pengguna, lalu ikatkan ke fakultas di sini.</p>
    
    <?php if (empty($gpm)): ?>
        <div style="text-align:center;padding:40px;">
            <div style="font-size:48px;margin-bottom:12px;">👥</div>
            <p class="text-muted">Belum ada pengguna dengan peran GPM Fakultas.</p>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Nama</th><th>Email</th><th>Fakultas Saat Ini</th><th style="width:300px;">Tugaskan Ke</th></tr></thead>
            <tbody>
            <?php foreach ($gpm as $u): ?>
                <tr>
                    <td><strong><?= Security::e($u['nama_lengkap']) ?></strong></td>
                    <td><small class="text-muted"><?= Security::e($u['email']) ?></small></td>
                    <td>
                        <?php if (!empty($u['nama_fakultas'])): ?>
                            <span class="badge badge-unggul"><?= Security::e($u['nama_fakultas']) ?></span>
                        <?php else: ?>
                            <span class="badge badge-baik">— Belum ditugaskan —</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                            <select name="id_fakultas" class="form-control" style="width:auto;flex:1;">
                                <option value="0">— Pilih Fakultas —</option>
                                <?php foreach ($fakList as $f): ?>
                                    <option value="<?= $f['id_fakultas'] ?>" <?= $u['id_fakultas'] == $f['id_fakultas'] ? 'selected' : '' ?>>
                                        <?= Security::e($f['nama_fakultas']) ?>
                                    </option>
                                <?php endforeach; ?>
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