<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$simTitle = 'Milestone Lembaga';
$activeMenu = 'milestone';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    $d = [
        ':tahun' => trim($_POST['tahun'] ?? ''),
        ':judul' => trim($_POST['judul'] ?? ''),
        ':desk'  => trim($_POST['deskripsi'] ?? ''),
        ':icon'  => trim($_POST['icon'] ?? '🎯'),
        ':kat'   => trim($_POST['kategori'] ?? 'Pengembangan'),
        ':urut'  => (int)($_POST['urutan'] ?? 0),
    ];
    if ($action === 'add') {
        $db->prepare("INSERT INTO milestone (tahun,judul,deskripsi,icon,kategori,urutan) VALUES (:tahun,:judul,:desk,:icon,:kat,:urut)")->execute($d);
        $msg = '✅ Milestone ditambahkan.';
    } elseif ($action === 'update') {
        $d[':id'] = (int)$_POST['id_milestone'];
        $db->prepare("UPDATE milestone SET tahun=:tahun,judul=:judul,deskripsi=:desk,icon=:icon,kategori=:kat,urutan=:urut WHERE id_milestone=:id")->execute($d);
        $msg = '✅ Milestone diperbarui.';
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM milestone WHERE id_milestone=?")->execute([(int)($_POST['id_milestone'] ?? 0)]);
        $msg = '🗑️ Milestone dihapus.';
    }
}

$list = $db->query("SELECT * FROM milestone ORDER BY urutan, tahun")->fetchAll();
$edit = null;
if (!empty($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM milestone WHERE id_milestone=?"); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch();
}
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>
<style>
.ms-grid{display:grid;grid-template-columns:360px 1fr;gap:22px}
@media(max-width:992px){.ms-grid{grid-template-columns:1fr}}
.ms-form{background:var(--bg-card);border:1px solid var(--border);border-radius:18px;padding:24px;position:sticky;top:96px;align-self:start}
.ms-form h3{margin:0 0 14px;color:var(--text-dark)}
.ms-tablewrap{background:var(--bg-card);border:1px solid var(--border);border-radius:18px;overflow:auto}
.ms-table{width:100%;border-collapse:collapse;font-size:13.5px;min-width:600px}
.ms-table th{background:var(--bg-light);color:var(--text-muted);text-transform:uppercase;font-size:11px;letter-spacing:1px;padding:12px 14px;text-align:left}
.ms-table td{padding:12px 14px;border-bottom:1px solid var(--border);color:var(--text-dark)}
.ms-year{display:inline-block;padding:3px 10px;border-radius:50px;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#092A40;font-weight:900;font-size:11px}
.ms-btn{padding:6px 10px;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer}
.ms-edit{background:rgba(59,130,246,.12);color:#2563EB}
.ms-del{background:rgba(239,68,68,.12);color:#DC2626}
</style>

<div class="ms-grid">
    <div class="ms-form">
        <h3><?= $edit ? '✏️ Edit Milestone' : '➕ Tambah Milestone' ?></h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>">
            <?php if ($edit): ?><input type="hidden" name="id_milestone" value="<?= $edit['id_milestone'] ?>"><?php endif; ?>
            <div class="form-group"><label class="form-label">Tahun *</label><input class="form-control" name="tahun" required maxlength="10" value="<?= Security::e($edit['tahun'] ?? '') ?>" placeholder="2026"></div>
            <div class="form-group"><label class="form-label">Judul *</label><input class="form-control" name="judul" required value="<?= Security::e($edit['judul'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Deskripsi</label><textarea class="form-control" name="deskripsi" rows="3"><?= Security::e($edit['deskripsi'] ?? '') ?></textarea></div>
            <div class="form-group"><label class="form-label">Ikon (emoji)</label><input class="form-control" name="icon" maxlength="8" value="<?= Security::e($edit['icon'] ?? '🎯') ?>"></div>
            <div class="form-group"><label class="form-label">Kategori</label><input class="form-control" name="kategori" value="<?= Security::e($edit['kategori'] ?? 'Pengembangan') ?>" placeholder="Pendirian / Audit / Akreditasi..."></div>
            <div class="form-group"><label class="form-label">Urutan</label><input type="number" name="urutan" class="form-control" value="<?= $edit['urutan'] ?? 0 ?>"></div>
            <button class="btn btn-gold" style="width:100%;justify-content:center;" type="submit"><?= $edit ? '💾 Perbarui' : '➕ Tambah' ?></button>
            <?php if ($edit): ?><a class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:8px;" href="/sim/admin/milestone.php">Batal</a><?php endif; ?>
        </form>
    </div>
    <div class="ms-tablewrap">
        <table class="ms-table">
            <thead><tr><th>Tahun</th><th>Judul</th><th>Kategori</th><th>Urutan</th><th style="width:90px">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($list as $m): ?>
                <tr>
                    <td><span class="ms-year"><?= Security::e($m['tahun']) ?></span></td>
                    <td><?= $m['icon'] ?> <?= Security::e($m['judul']) ?></td>
                    <td><?= Security::e($m['kategori']) ?></td>
                    <td><?= (int)$m['urutan'] ?></td>
                    <td>
                        <a class="ms-btn ms-edit" href="?edit=<?= $m['id_milestone'] ?>">✏️</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus milestone ini?')"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_milestone" value="<?= $m['id_milestone'] ?>"><button class="ms-btn ms-del">🗑️</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>