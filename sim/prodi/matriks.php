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
            $pesan = '✅ Bukti berhasil tercatat.';
        }
    }
    if ($action === 'status') {
        $db->prepare("UPDATE bukti_kriteria SET status = ? WHERE id_bukti = ? AND id_prodi = ?")
           ->execute([$_POST['status'], (int)$_POST['id_bukti'], $prodiId]);
        $pesan = '✅ Status bukti diperbarui.';
    }
    if ($action === 'del') {
        $db->prepare("DELETE FROM bukti_kriteria WHERE id_bukti = ? AND id_prodi = ?")->execute([(int)$_POST['id_bukti'], $prodiId]);
        $pesan = '✅ Bukti berhasil dihapus.';
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

<style>
    .matriks-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 32px 36px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .matriks-header::before {
        content: ''; position: absolute; width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -100px; top: -150px; animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .matriks-header h2 { font-size: 24px; margin: 0 0 8px; position: relative; font-weight: 800; }
    .matriks-header p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; margin: 0 0 20px 0; }
    
    .matriks-stats {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;
    }
    .matriks-stat {
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px); border-radius: 12px;
        padding: 16px; text-align: center; border: 1px solid rgba(255,255,255,.15);
    }
    .matriks-stat h4 { font-size: 28px; margin: 0 0 4px; font-weight: 800; color: var(--accent-light); }
    .matriks-stat small { font-size: 12px; opacity: .8; text-transform: uppercase; letter-spacing: 1px; }

    .prog-bar-main {
        background: rgba(255,255,255,.2); border-radius: 50px; height: 16px; overflow: hidden; position: relative;
    }
    .prog-fill-main {
        height: 100%; background: linear-gradient(90deg, var(--accent), var(--accent-light));
        border-radius: 50px; transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .prog-fill-main::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent);
        animation: shimmer 2s infinite;
    }
    @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

    .add-form-card {
        background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 28px;
        box-shadow: 0 4px 12px rgba(0,0,0,.05); border: 2px solid var(--border);
    }
    .add-form-card h3 { margin: 0 0 16px; font-size: 18px; display: flex; align-items: center; gap: 10px; }
    
    .kriteria-card {
        background: #fff; border-radius: 16px; border: 2px solid var(--border);
        margin-bottom: 20px; overflow: hidden; transition: all 0.3s;
    }
    .kriteria-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.06); }
    
    .kriteria-head {
        padding: 16px 24px; background: var(--bg-light); border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    }
    .kriteria-head h3 { font-size: 16px; margin: 0; color: var(--primary-dark); display: flex; align-items: center; gap: 10px; }
    
    .kriteria-prog {
        height: 6px; background: #e2e8f0; border-radius: 50px; overflow: hidden;
    }
    .kriteria-prog-fill {
        height: 100%; border-radius: 50px; transition: width 0.8s ease;
    }

    .status-select {
        padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); font-size: 12px; font-weight: 600;
        background: #fff; cursor: pointer; transition: all 0.3s;
    }
    .status-select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(201,162,39,.15); }

    @media (max-width: 992px) { .matriks-stats { grid-template-columns: 1fr; } }
</style>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="matriks-header">
    <h2>🗂️ Kesiapan Borang Akreditasi Tahun <?= $tahun ?></h2>
    <p>Pantau kelengkapan bukti fisik untuk setiap kriteria akreditasi program studi.</p>
    
    <div class="matriks-stats">
        <div class="matriks-stat"><h4><?= count($kriteria) ?></h4><small>Total Kriteria</small></div>
        <div class="matriks-stat"><h4><?= $totAll ?></h4><small>Total Bukti</small></div>
        <div class="matriks-stat"><h4><?= $lAll ?></h4><small>Bukti Lengkap</small></div>
    </div>
    
    <div class="prog-bar-main">
        <div class="prog-fill-main" style="width: <?= $overall ?>%;"></div>
    </div>
    <p style="margin-top:10px;opacity:.9;font-size:14px;">
        <strong>Kelengkapan Keseluruhan: <?= $overall ?>%</strong> (<?= $lAll ?> dari <?= $totAll ?> bukti berstatus Lengkap)
    </p>
</div>

<div class="add-form-card">
    <h3>➕ Tambah Bukti Pendukung</h3>
    <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:16px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Kriteria</label>
            <select name="id_kriteria" class="form-control" required>
                <?php foreach ($kriteria as $k): ?><option value="<?= $k['id_kriteria'] ?>">K<?= $k['nomor'] ?> — <?= Security::e($k['nama_kriteria']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Indikator</label>
            <input type="text" name="indikator" class="form-control" placeholder="cth: Rasio dosen-mahasiswa" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Nama Bukti</label>
            <input type="text" name="nama_bukti" class="form-control" placeholder="cth: SK Dosen 2025" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">File Pendukung</label>
            <input type="file" name="file" class="form-control">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Status Kelengkapan</label>
            <select name="status" class="form-control">
                <option>Belum</option>
                <option>Sebagian</option>
                <option>Lengkap</option>
            </select>
        </div>
        <button class="btn btn-gold" style="height:42px;font-weight:700;">💾 Simpan</button>
    </form>
</div>

<?php foreach ($kriteria as $k):
    $rows = $grouped[$k['id_kriteria']] ?? [];
    $tot = count($rows);
    $l = count(array_filter($rows, fn($r) => $r['status'] === 'Lengkap'));
    $pct = $tot > 0 ? round($l / $tot * 100) : 0;
    $progColor = $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)');
    $badgeClass = $pct >= 80 ? 'badge-unggul' : ($pct >= 50 ? 'badge-a' : 'badge-b');
?>
<div class="kriteria-card">
    <div class="kriteria-head">
        <h3>📌 K<?= $k['nomor'] ?> — <?= Security::e($k['nama_kriteria']) ?></h3>
        <span class="badge <?= $badgeClass ?>" style="font-size:12px;padding:6px 12px;"><?= $pct ?>% Lengkap</span>
    </div>
    <div style="padding:0 24px 16px;">
        <div class="kriteria-prog">
            <div class="kriteria-prog-fill" style="width:<?= $pct ?>%;background:<?= $progColor ?>;"></div>
        </div>
    </div>
    
    <?php if (empty($rows)): ?>
        <div style="padding:24px;text-align:center;border-top:1px solid var(--border);">
            <p class="text-muted" style="font-size:14px;margin:0;">Belum ada bukti yang ditambahkan pada kriteria ini.</p>
        </div>
    <?php else: ?>
    <div class="table-wrapper" style="border-top:1px solid var(--border);">
        <table>
            <thead>
                <tr>
                    <th style="width:30%">Indikator</th>
                    <th style="width:30%">Nama Bukti</th>
                    <th style="width:10%;text-align:center;">File</th>
                    <th style="width:20%;text-align:center;">Status</th>
                    <th style="width:10%;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): 
                $statusColor = match($r['status']) {
                    'Lengkap' => 'var(--success)',
                    'Sebagian' => 'var(--warning)',
                    default => 'var(--text-muted)'
                };
            ?>
                <tr>
                    <td style="vertical-align:middle;"><?= Security::e($r['indikator']) ?></td>
                    <td style="vertical-align:middle;"><strong><?= Security::e($r['nama_bukti']) ?></strong></td>
                    <td style="vertical-align:middle;text-align:center;">
                        <?php if ($r['file_path']): ?>
                            <a href="/download.php?file=<?= urlencode($r['file_path']) ?>&type=internal" target="_blank" class="btn btn-outline" style="padding:6px 10px;font-size:13px;" title="Unduh File">📄</a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="vertical-align:middle;text-align:center;">
                        <form method="POST" style="margin:0;display:inline-block;">
                            <input type="hidden" name="id_bukti" value="<?= $r['id_bukti'] ?>">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="status">
                            <select name="status" class="status-select" style="color:<?= $statusColor ?>" onchange="this.form.submit()">
                                <?php foreach (['Belum','Sebagian','Lengkap'] as $s): ?>
                                    <option <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td style="vertical-align:middle;text-align:center;">
                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus bukti ini?');" style="margin:0;display:inline-block;">
                            <input type="hidden" name="id_bukti" value="<?= $r['id_bukti'] ?>">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="del">
                            <button class="btn btn-outline" style="padding:6px 10px;font-size:13px;color:var(--danger);border-color:rgba(239,68,68,.3);" title="Hapus">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<script>
// Animate main progress bar on load
window.addEventListener('load', function() {
    const progFill = document.querySelector('.prog-fill-main');
    if (progFill) {
        const targetWidth = progFill.style.width;
        progFill.style.width = '0%';
        setTimeout(() => { progFill.style.width = targetWidth; }, 100);
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>