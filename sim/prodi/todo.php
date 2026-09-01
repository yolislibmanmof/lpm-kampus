<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = '';
$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'To-Do Akreditasi';
$activeMenu = 'todo';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun belum terhubung ke prodi.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO tugas_akreditasi (id_prodi, judul, kategori, pic, tenggat) VALUES (?,?,?,?,?)")
           ->execute([$prodiId, trim($_POST['judul']), trim($_POST['kategori']), trim($_POST['pic']), $_POST['tenggat'] ?: null]);
        $pesan = '✅ Tugas ditambahkan.';
    }
    if ($action === 'status') {
        $db->prepare("UPDATE tugas_akreditasi SET status = ? WHERE id_tugas = ? AND id_prodi = ?")
           ->execute([$_POST['status'], (int)$_POST['id_tugas'], $prodiId]);
        $pesan = '✅ Status diperbarui.';
    }
    if ($action === 'del') {
        $db->prepare("DELETE FROM tugas_akreditasi WHERE id_tugas = ? AND id_prodi = ?")->execute([(int)$_POST['id_tugas'], $prodiId]);
        $pesan = '✅ Tugas dihapus.';
    }
}

$list = $db->prepare("SELECT * FROM tugas_akreditasi WHERE id_prodi = ? ORDER BY (status='Selesai') ASC, tenggat ASC");
$list->execute([$prodiId]);
$list = $list->fetchAll();
$total = count($list);
$selesai = count(array_filter($list, fn($t) => $t['status'] === 'Selesai'));
$berjalan = count(array_filter($list, fn($t) => $t['status'] === 'Berjalan'));
$telat = count(array_filter($list, fn($t) => $t['status'] !== 'Selesai' && $t['tenggat'] && $t['tenggat'] < $today));
$progres = $total > 0 ? round($selesai / $total * 100) : 0;
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:var(--radius-lg);padding:32px 36px;color:#fff;margin-bottom:28px;">
    <h2 style="font-size:24px;">✅ Papan Tugas Persiapan Akreditasi</h2>
    <div style="margin-top:16px;background:rgba(255,255,255,.2);border-radius:50px;height:14px;overflow:hidden;">
        <div style="height:100%;width:<?= $progres ?>%;background:linear-gradient(90deg,var(--accent),var(--accent-light));border-radius:50px;transition:width .8s;"></div>
    </div>
    <p style="margin-top:10px;opacity:.85;">Progres keseluruhan: <strong><?= $progres ?>%</strong> • <?= $selesai ?> selesai / <?= $total ?> tugas • <strong><?= $telat ?> terlambat</strong></p>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Tambah Tugas</h3>
    <form method="POST" style="display:grid;grid-template-columns:2.4fr 1fr 1fr .9fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Uraian Tugas</label>
            <input type="text" name="judul" class="form-control" placeholder="cth: Finalisasi LED Kriteria 6" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Kategori</label>
            <select name="kategori" class="form-control">
                <option>LED</option><option>LKPS</option><option>Borang K1</option><option>Borang K2</option><option>Borang K3</option>
                <option>Borang K4</option><option>Borang K5</option><option>Borang K6</option><option>Borang K7</option>
                <option>Borang K8</option><option>Borang K9</option><option>SK & Dokumen</option><option>Lainnya</option>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">PIC</label>
            <input type="text" name="pic" class="form-control" placeholder="cth: Bu Ana"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tenggat</label>
            <input type="date" name="tenggat" class="form-control"></div>
        <button class="btn btn-gold">💾</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Daftar Tugas</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Tugas</th><th>Kategori</th><th>PIC</th><th>Tenggat</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada tugas. Mulai petakan pekerjaan borang Anda!</td></tr><?php endif; ?>
            <?php foreach ($list as $t):
                $overdue = $t['status'] !== 'Selesai' && $t['tenggat'] && $t['tenggat'] < $today;
            ?>
                <tr>
                    <td><strong style="font-size:13.5px;"><?= Security::e($t['judul']) ?></strong></td>
                    <td><span class="badge badge-baik"><?= Security::e($t['kategori']) ?></span></td>
                    <td><?= Security::e($t['pic']) ?></td>
                    <td><?= $t['tenggat'] ? date('d M Y', strtotime($t['tenggat'])) : '—' ?>
                        <?php if ($overdue): ?><br><span class="badge" style="background:#FEE2E2;color:#991B1B;">⏰ Terlambat</span><?php endif; ?></td>
                    <td>
                        <form method="POST"><input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="status">
                            <select name="status" class="form-control" style="width:auto;padding:7px 10px;font-size:12.5px;" onchange="this.form.submit()">
                                <?php foreach (['Belum Mulai','Berjalan','Selesai'] as $s): ?><option <?= $t['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td><form method="POST" onsubmit="return confirm('Hapus tugas?');"><input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>