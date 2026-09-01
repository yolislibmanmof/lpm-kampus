<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO rtm_tindak_lanjut (tahun, keputusan, pic, tenggat) VALUES (?,?,?,?)")
           ->execute([(int)$_POST['tahun'], trim($_POST['keputusan']), trim($_POST['pic']), $_POST['tenggat'] ?: null]);
        Logger::log('UPDATE', 'Menambahkan tindak lanjut RTM');
        $pesan = '✅ Keputusan RTM dicatat.';
    }
    if ($action === 'update') {
        $db->prepare("UPDATE rtm_tindak_lanjut SET status=?, bukti=? WHERE id_tl=?")
           ->execute([$_POST['status'], trim($_POST['bukti']), (int)$_POST['id_tl']]);
        $pesan = '✅ Status tindak lanjut diperbarui.';
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM rtm_tindak_lanjut WHERE id_tl=?")->execute([(int)$_POST['id_tl']]);
        $pesan = '✅ Baris dihapus.';
    }
}

$list = $db->query("SELECT * FROM rtm_tindak_lanjut ORDER BY tahun DESC, tenggat ASC")->fetchAll();
$st = $db->query("SELECT status, COUNT(*) c FROM rtm_tindak_lanjut GROUP BY status")->fetchAll();
$late = 0;
foreach ($list as $r) if ($r['status'] !== 'Selesai' && $r['tenggat'] && strtotime($r['tenggat']) < time()) $late++;

$simTitle = 'Tindak Lanjut RTM';
$activeMenu = 'rtmtl';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">📑</div><div><h3 style="font-size:28px;"><?= count($list) ?></h3><p class="text-muted">Total Keputusan</p></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><h3 style="font-size:28px;"><?= (int)($db->query("SELECT COUNT(*) t FROM rtm_tindak_lanjut WHERE status='Selesai'")->fetch()['t']) ?></h3><p class="text-muted">Selesai</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">🔄</div><div><h3 style="font-size:28px;"><?= (int)($db->query("SELECT COUNT(*) t FROM rtm_tindak_lanjut WHERE status='Berjalan'")->fetch()['t']) ?></h3><p class="text-muted">Berjalan</p></div></div>
    <div class="stat-card"><div class="stat-icon red">⏰</div><div><h3 style="font-size:28px;"><?= $late ?></h3><p class="text-muted">Lewat Tenggat</p></div></div>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Catat Keputusan RTM Baru</h3>
    <form method="POST" style="display:grid;grid-template-columns:.6fr 3fr 1fr 1fr auto;gap:14px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Tahun</label>
            <input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Keputusan / Tindak Lanjut</label>
            <input type="text" name="keputusan" class="form-control" placeholder="cth: Revisi manual mutu S3 sesuai SN-Dikti baru" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">PIC</label>
            <input type="text" name="pic" class="form-control" placeholder="cth: Kabid Standar"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tenggat</label>
            <input type="date" name="tenggat" class="form-control"></div>
        <button class="btn btn-primary">💾 Simpan</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Monitoring Tindak Lanjut</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Tahun</th><th>Keputusan & PIC</th><th>Tenggat</th><th>Status</th><th>Bukti</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada tindak lanjut dicatat.</td></tr><?php endif; ?>
            <?php foreach ($list as $r):
                $overdue = $r['status'] !== 'Selesai' && $r['tenggat'] && strtotime($r['tenggat']) < time();
            ?>
                <tr>
                    <td><?= $r['tahun'] ?></td>
                    <td><strong style="font-size:13.5px;"><?= Security::e($r['keputusan']) ?></strong><br><small class="text-muted">PIC: <?= Security::e($r['pic']) ?></small></td>
                    <td><?= $r['tenggat'] ? date('d M Y', strtotime($r['tenggat'])) : '—' ?>
                        <?php if ($overdue): ?><br><span class="badge" style="background:#FEE2E2;color:#991B1B;">⏰ Terlambat</span><?php endif; ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;align-items:center;">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_tl" value="<?= $r['id_tl'] ?>">
                            <select name="status" class="form-control" style="width:auto;padding:7px 10px;font-size:12.5px;" onchange="this.form.submit()">
                                <?php foreach (['Belum Mulai','Berjalan','Selesai','Tertunda'] as $s): ?><option <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td style="min-width:160px;">
                        <form method="POST" style="display:flex;gap:6px;">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_tl" value="<?= $r['id_tl'] ?>">
                            <input type="hidden" name="status" value="<?= Security::e($r['status']) ?>">
                            <input type="text" name="bukti" class="form-control" style="padding:7px 10px;font-size:12.5px;" placeholder="tautan/dokumen bukti" value="<?= Security::e($r['bukti']) ?>">
                            <button class="btn btn-outline" style="padding:6px 12px;font-size:12px;">💾</button>
                        </form>
                    </td>
                    <td><form method="POST" onsubmit="return confirm('Hapus?');"><input type="hidden" name="id_tl" value="<?= $r['id_tl'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="font-size:12.5px;margin-top:14px;">💡 Bukti tindak lanjut yang rapi = poin besar saat asesmen eksternal (siklus PPEPP tertutup sempurna).</p>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>