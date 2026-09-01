<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO pengingat_tenggat (judul, jenis, id_prodi, tanggal) VALUES (?,?,?,?)")
           ->execute([trim($_POST['judul']), $_POST['jenis'], (int)($_POST['id_prodi'] ?? 0) ?: null, $_POST['tanggal']]);
        $pesan = '✅ Tenggat dicatat — reminder otomatis aktif (H-90/60/30/7/1/0).';
    }
    if ($action === 'toggle') {
        $db->prepare("UPDATE pengingat_tenggat SET selesai = 1 - selesai WHERE id_tenggat = ?")->execute([(int)$_POST['id_tenggat']]);
        $pesan = '✅ Status diperbarui.';
    }
    if ($action === 'del') {
        $db->prepare("DELETE FROM pengingat_tenggat WHERE id_tenggat = ?")->execute([(int)$_POST['id_tenggat']]);
        $pesan = '✅ Tenggat dihapus.';
    }
}

$list = $db->query("SELECT t.*, p.nama_prodi FROM pengingat_tenggat t LEFT JOIN prodi p ON t.id_prodi = p.id_prodi ORDER BY t.tanggal ASC")->fetchAll();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$today = strtotime(date('Y-m-d'));

$simTitle = 'Tenggat & Reminder';
$activeMenu = 'tenggat';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Catat Tenggat (Akreditasi / AMI / Laporan)</h3>
    <form method="POST" style="display:grid;grid-template-columns:2.2fr 1fr 1.2fr .9fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Judul Tenggat</label>
            <input type="text" name="judul" class="form-control" placeholder="cth: Submit LED-LKPS Bisnis Digital (BAN-PT)" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Jenis</label>
            <select name="jenis" class="form-control"><option>Akreditasi</option><option>AMI</option><option>Laporan</option><option>Lainnya</option></select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Prodi (opsional)</label>
            <select name="id_prodi" class="form-control"><option value="0">— Umum —</option>
                <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tanggal</label>
            <input type="date" name="tanggal" class="form-control" required></div>
        <button class="btn btn-gold">💾</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Daftar Tenggat Aktif</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Tenggat</th><th>Jenis</th><th>Prodi</th><th>Tanggal</th><th>Sisa</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada tenggat. Catat tanggal akreditasi prodi di sini!</td></tr><?php endif; ?>
            <?php foreach ($list as $t):
                $h = (int)round((strtotime($t['tanggal']) - $today) / 86400);
                $badge = $t['selesai'] ? '<span class="badge badge-baik">✅ Selesai</span>'
                    : ($h < 0 ? '<span class="badge" style="background:#FEE2E2;color:#991B1B;">Lewat ' . abs($h) . ' hari</span>'
                    : ($h <= 7 ? '<span class="badge" style="background:#FEE2E2;color:#991B1B;">🔴 H-' . $h . '</span>'
                    : ($h <= 30 ? '<span class="badge" style="background:#FEF3C7;color:#92400E;">🟡 H-' . $h . '</span>'
                    : '<span class="badge badge-baik">🟢 H-' . $h . '</span>')));
            ?>
                <tr>
                    <td><strong><?= Security::e($t['judul']) ?></strong></td>
                    <td><?= Security::e($t['jenis']) ?></td>
                    <td><?= Security::e($t['nama_prodi'] ?? 'Umum') ?></td>
                    <td><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                    <td><?= $badge ?></td>
                    <td style="display:flex;gap:6px;">
                        <form method="POST"><input type="hidden" name="id_tenggat" value="<?= $t['id_tenggat'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-outline" style="padding:5px 12px;font-size:11px;"><?= $t['selesai'] ? '↩️ Buka' : '✅ Selesai' ?></button></form>
                        <form method="POST" onsubmit="return confirm('Hapus?');"><input type="hidden" name="id_tenggat" value="<?= $t['id_tenggat'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del"><button class="btn btn-outline" style="padding:5px 10px;font-size:11px;color:var(--danger);">🗑️</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>