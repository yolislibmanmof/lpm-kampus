<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = ''; $error = '';

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'Matriks Bukti Akreditasi';
$activeMenu = 'matriks';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun belum terhubung ke prodi.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

$tahun = (int)($_GET['tahun'] ?? date('Y'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $file = null;
        if (!empty($_FILES['file']['name'])) {
            $up = Security::secureUpload($_FILES['file'], 'bukti');
            if ($up['success']) $file = $up['path']; else $error = $up['message'];
        }
        if (!$error) {
            $db->prepare("INSERT INTO bukti_kriteria (id_prodi, id_kriteria, indikator, nama_bukti, file_path, status, tahun) VALUES (?,?,?,?,?,?,?)")
               ->execute([$prodiId, (int)$_POST['id_kriteria'], trim($_POST['indikator']), trim($_POST['nama_bukti']), $file, $_POST['status'], $tahun]);
            Logger::log('UPLOAD', 'Menambah bukti kriteria');
            $pesan = '✅ Bukti tercatat.';
        }
    }
    if ($action === 'status') {
        $db->prepare("UPDATE bukti_kriteria SET status = ? WHERE id_bukti = ? AND id_prodi = ?")
           ->execute([$_POST['status'], (int)$_POST['id_bukti'], $prodiId]);
        $pesan = '✅ Status diperbarui.';
    }
    if ($action === 'del') {
        $db->prepare("DELETE FROM bukti_kriteria WHERE id_bukti = ? AND id_prodi = ?")->execute([(int)$_POST['id_bukti'], $prodiId]);
        $pesan = '✅ Bukti dihapus.';
    }
}

$kriteria = $db->query("SELECT * FROM kriteria_akreditasi ORDER BY nomor")->fetchAll();
$all = $db->prepare("SELECT * FROM bukti_kriteria WHERE id_prodi = ? AND tahun = ? ORDER BY id_kriteria, id_bukti");
$all->execute([$prodiId, $tahun]);
$grouped = [];
$totAll = 0; $lAll = 0;
foreach ($all->fetchAll() as $b) {
    $grouped[$b['id_kriteria']][] = $b;
    $totAll++; if ($b['status'] === 'Lengkap') $lAll++;
}
$overall = $totAll > 0 ? round($lAll / $totAll * 100) : 0;
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:var(--radius-lg);padding:32px 36px;color:#fff;margin-bottom:28px;">
    <h2 style="font-size:24px;">🗂️ Kesiapan Borang <?= $tahun ?></h2>
    <div style="margin-top:16px;background:rgba(255,255,255,.2);border-radius:50px;height:14px;overflow:hidden;">
        <div style="height:100%;width:<?= $overall ?>%;background:linear-gradient(90deg,var(--accent),var(--accent-light));border-radius:50px;transition:width .8s;"></div>
    </div>
    <p style="margin-top:10px;opacity:.85;">Kelengkapan bukti: <strong><?= $overall ?>%</strong> (<?= $lAll ?>/<?= $totAll ?> bukti lengkap)</p>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Tambah Bukti</h3>
    <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1.2fr 1.6fr 1.6fr .9fr .9fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Kriteria</label>
            <select name="id_kriteria" class="form-control" required>
                <?php foreach ($kriteria as $k): ?><option value="<?= $k['id_kriteria'] ?>">K<?= $k['nomor'] ?> — <?= Security::e($k['nama_kriteria']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Indikator</label>
            <input type="text" name="indikator" class="form-control" placeholder="cth: Rasio dosen-mahasiswa" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Nama Bukti</label>
            <input type="text" name="nama_bukti" class="form-control" placeholder="cth: SK Dosen 2025" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">File</label>
            <input type="file" name="file" class="form-control"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Status</label>
            <select name="status" class="form-control"><option>Belum</option><option>Sebagian</option><option>Lengkap</option></select></div>
        <button class="btn btn-gold">💾</button>
    </form>
</div>

<?php foreach ($kriteria as $k):
    $rows = $grouped[$k['id_kriteria']] ?? [];
    $tot = count($rows);
    $l = count(array_filter($rows, fn($r) => $r['status'] === 'Lengkap'));
    $pct = $tot > 0 ? round($l / $tot * 100) : 0;
?>
<div class="card" style="margin-bottom:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="font-size:16px;">📌 K<?= $k['nomor'] ?> — <?= Security::e($k['nama_kriteria']) ?></h3>
        <span class="badge <?= $pct >= 80 ? 'badge-unggul' : ($pct >= 50 ? 'badge-a' : 'badge-b') ?>"><?= $pct ?>% lengkap</span>
    </div>
    <div style="background:var(--bg-light);border-radius:50px;height:8px;overflow:hidden;margin-bottom:14px;">
        <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') ?>;border-radius:50px;"></div>
    </div>
    <?php if (empty($rows)): ?><p class="text-muted" style="font-size:13px;">Belum ada bukti pada kriteria ini.</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Indikator</th><th>Bukti</th><th>File</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= Security::e($r['indikator']) ?></td>
                    <td><strong><?= Security::e($r['nama_bukti']) ?></strong></td>
                    <td><?= $r['file_path'] ? '<a href="/download.php?file=' . urlencode($r['file_path']) . '&type=internal" target="_blank" style="color:var(--primary);font-weight:700;">📄</a>' : '—' ?></td>
                    <td>
                        <form method="POST"><input type="hidden" name="id_bukti" value="<?= $r['id_bukti'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="status">
                            <select name="status" class="form-control" style="width:auto;padding:6px 8px;font-size:12px;" onchange="this.form.submit()">
                                <?php foreach (['Belum','Sebagian','Lengkap'] as $s): ?><option <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td><form method="POST" onsubmit="return confirm('Hapus bukti?');"><input type="hidden" name="id_bukti" value="<?= $r['id_bukti'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>