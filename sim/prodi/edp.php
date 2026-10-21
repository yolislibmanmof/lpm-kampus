<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = ''; $error = '';

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$tahun = (int)($_GET['tahun'] ?? date('Y'));

if ($prodiId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        foreach ($_POST['capaian'] ?? [] as $idStandar => $cap) {
            $idStandar = (int)$idStandar;
            $tgt = (float)($_POST['target'][$idStandar] ?? 100);
            $cat = trim($_POST['catatan'][$idStandar] ?? '');
            $buk = trim($_POST['bukti'][$idStandar] ?? '');
            $cap = ($cap === '') ? null : (float)$cap;

            $chk = $db->prepare("SELECT id_evaluasi, status FROM evaluasi_diri WHERE id_prodi=? AND id_standar=? AND tahun=?");
            $chk->execute([$prodiId, $idStandar, $tahun]);
            $row = $chk->fetch();

            if ($row) {
                if ($row['status'] !== 'Terkunci') {
                    $db->prepare("UPDATE evaluasi_diri SET capaian=?, target=?, catatan=?, bukti_fisik=? WHERE id_evaluasi=?")
                       ->execute([$cap, $tgt, $cat, $buk, $row['id_evaluasi']]);
                }
            } else {
                $db->prepare("INSERT INTO evaluasi_diri (id_prodi, id_standar, tahun, capaian, target, catatan, bukti_fisik) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$prodiId, $idStandar, $tahun, $cap, $tgt, $cat, $buk]);
            }
        }
        $pesan = '✅ Draft evaluasi berhasil disimpan.';
    }

    if ($action === 'lock') {
        $db->prepare("UPDATE evaluasi_diri SET status=? WHERE id_prodi=? AND id_standar=? AND tahun=?")
           ->execute([$_POST['new_status'], $prodiId, (int)$_POST['id_standar'], $tahun]);
        $pesan = '✅ Status standar diperbarui.';
    }
}

$standar = $db->query("SELECT * FROM standar_mutu ORDER BY kode_standar")->fetchAll();
$evalMap = [];
if ($prodiId) {
    $ev = $db->prepare("SELECT * FROM evaluasi_diri WHERE id_prodi=? AND tahun=?");
    $ev->execute([$prodiId, $tahun]);
    foreach ($ev->fetchAll() as $r) { $evalMap[$r['id_standar']] = $r; }
}

$simTitle = 'Evaluasi Diri (EDP)';
$activeMenu = 'edp';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">️ Akun Anda belum terhubung ke Program Studi. Hubungi Admin LPM.</div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

// Hitung progres
$totalStandar = count($standar);
$lockedCount = 0; $draftCount = 0;
foreach ($standar as $s) {
    $e = $evalMap[$s['id_standar']] ?? null;
    if ($e && $e['status'] === 'Terkunci') $lockedCount++;
    elseif ($e) $draftCount++;
}
$progress = $totalStandar > 0 ? round(($lockedCount / $totalStandar) * 100) : 0;
?>

<style>
    .edp-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 28px 32px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .edp-header::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -80px; top: -100px; animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .edp-header h2 { font-size: 22px; margin: 0 0 8px; position: relative; font-weight: 800; }
    .edp-header p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; margin-bottom: 16px; }
    
    .edp-stats {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px;
    }
    .edp-stat {
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px); border-radius: 12px;
        padding: 16px; text-align: center; border: 1px solid rgba(255,255,255,.15);
    }
    .edp-stat h4 { font-size: 28px; margin: 0 0 4px; font-weight: 800; }
    .edp-stat small { font-size: 12px; opacity: .8; text-transform: uppercase; letter-spacing: 1px; }

    /* DIPERBAIKI: Gunakan CSS variables */
    .edp-card {
        background: var(--bg-card); border-radius: 16px; border: 2px solid var(--border);
        margin-bottom: 20px; overflow: hidden; transition: all 0.3s;
    }
    .edp-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.06); }
    .edp-card.locked { border-left: 5px solid var(--success); }
    .edp-card.draft { border-left: 5px solid var(--warning); }
    .edp-card.empty { border-left: 5px solid var(--text-muted); opacity: 0.85; }

    .edp-head {
        padding: 16px 24px; background: var(--bg-light); border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    }
    .edp-head h3 { font-size: 16px; margin: 0; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
    
    .edp-body { padding: 24px; }
    .edp-grid { display: grid; grid-template-columns: 1fr 1fr 1.5fr; gap: 20px; margin-bottom: 20px; }
    
    .capaian-bar {
        height: 8px; background: #e2e8f0; border-radius: 50px; overflow: hidden; margin-top: 8px;
    }
    .capaian-fill {
        height: 100%; border-radius: 50px; transition: width 0.8s ease;
    }
    
    .edp-actions {
        display: flex; justify-content: flex-end; padding-top: 16px; border-top: 1px dashed var(--border);
    }

    /* DIPERBAIKI: Form selector tahun */
    .year-selector {
        background: var(--bg-card);
        border-color: var(--border);
    }
    .year-selector select {
        background: var(--bg-card);
        color: var(--text-dark);
        border-color: var(--border);
    }

    /* DARK MODE OVERRIDES */
    body.dark .edp-card {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .edp-head h3 {
        color: var(--text-dark);
    }
    body.dark .year-selector {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .year-selector select {
        background: #0D1526;
        color: var(--text-dark);
        border-color: var(--border);
    }
    body.dark .capaian-bar {
        background: rgba(255,255,255,0.1);
    }

    @media (max-width: 992px) { .edp-grid { grid-template-columns: 1fr; } .edp-stats { grid-template-columns: 1fr; } }
</style>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div class="edp-header">
    <h2>📝 Evaluasi Diri Program Studi (EDP)</h2>
    <p>Isi capaian & target, simpan draft, lalu <strong>Kunci</strong> standar yang sudah final.</p>
    <div style="background:rgba(255,255,255,.2);border-radius:50px;height:12px;overflow:hidden;">
        <div style="height:100%;width:<?= $progress ?>%;background:linear-gradient(90deg,var(--accent),var(--accent-light));border-radius:50px;transition:width 1s;"></div>
    </div>
    <div class="edp-stats">
        <div class="edp-stat"><h4><?= $totalStandar ?></h4><small>Total Standar</small></div>
        <div class="edp-stat"><h4><?= $lockedCount ?></h4><small>Terkunci</small></div>
        <div class="edp-stat"><h4><?= $draftCount ?></h4><small>Draf / Belum Dinilai</small></div>
    </div>
</div>

<div class="no-print" style="display:flex;justify-content:flex-end;margin-bottom:24px;">
    <form method="GET" class="year-selector" style="display:flex;gap:12px;align-items:center;padding:12px 16px;border-radius:12px;border:1px solid var(--border);">
        <label class="form-label" style="margin:0;font-weight:700;color:var(--text-dark);">Tahun Evaluasi:</label>
        <select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<form method="POST">
    <?= Security::csrfField() ?>
    <input type="hidden" name="action" value="save">

    <?php foreach ($standar as $s): 
        $e = $evalMap[$s['id_standar']] ?? null; 
        $locked = ($e['status'] ?? '') === 'Terkunci';
        $statusClass = $locked ? 'locked' : ($e ? 'draft' : 'empty');
        $cap = $e['capaian'] ?? null;
        $tgt = $e['target'] ?? 100;
        $pct = ($cap !== null && $tgt > 0) ? min(100, max(0, ($cap / $tgt) * 100)) : 0;
        $barColor = $cap !== null ? ($cap >= $tgt ? '#10B981' : '#F59E0B') : '#CBD5E1';
    ?>
    <div class="edp-card <?= $statusClass ?>">
        <div class="edp-head">
            <h3>
                <span style="background:var(--primary);color:#fff;padding:4px 10px;border-radius:6px;font-size:13px;"><?= Security::e($s['kode_standar']) ?></span>
                <?= Security::e($s['nama_standar']) ?>
            </h3>
            <?php if ($e): ?>
                <span class="badge <?= $locked ? 'badge-unggul' : 'badge-b' ?>" style="font-size:12px;padding:6px 12px;">
                    <?= $locked ? '🔒 Terkunci' : '✏️ Draf' ?>
                </span>
            <?php else: ?>
                <span class="badge badge-baik" style="font-size:12px;padding:6px 12px;">Belum Dinilai</span>
            <?php endif; ?>
        </div>
        <div class="edp-body">
            <div class="edp-grid">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Capaian (%)</label>
                    <input type="number" min="0" max="100" step="0.01" name="capaian[<?= $s['id_standar'] ?>]" class="form-control" value="<?= Security::e($e['capaian'] ?? '') ?>" <?= $locked ? 'disabled' : '' ?> placeholder="0.00">
                    <?php if ($cap !== null): ?>
                    <div class="capaian-bar">
                        <div class="capaian-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div>
                    </div>
                    <small style="color:<?= $barColor ?>;font-size:11px;font-weight:700;"><?= $pct ?>% dari target</small>
                    <?php endif; ?>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Target (%)</label>
                    <input type="number" min="0" max="100" step="0.01" name="target[<?= $s['id_standar'] ?>]" class="form-control" value="<?= Security::e($e['target'] ?? '100') ?>" <?= $locked ? 'disabled' : '' ?>>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Bukti Fisik (Kode/Nama Dokumen)</label>
                    <input type="text" name="bukti[<?= $s['id_standar'] ?>]" class="form-control" value="<?= Security::e($e['bukti_fisik'] ?? '') ?>" placeholder="cth: LKD-2026-01" <?= $locked ? 'disabled' : '' ?>>
                </div>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Catatan / Analisis Gap</label>
                <textarea name="catatan[<?= $s['id_standar'] ?>]" class="form-control" rows="2" <?= $locked ? 'disabled' : '' ?> placeholder="Jelaskan analisis capaian terhadap target..."><?= Security::e($e['catatan'] ?? '') ?></textarea>
            </div>
            
            <?php if ($e): ?>
            <div class="edp-actions">
                <form method="POST" style="margin:0;">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="lock">
                    <input type="hidden" name="id_standar" value="<?= $s['id_standar'] ?>">
                    <input type="hidden" name="new_status" value="<?= $locked ? 'Draf' : 'Terkunci' ?>">
                    <button class="btn <?= $locked ? 'btn-outline' : 'btn-gold' ?>" style="padding:8px 20px;font-size:13px;font-weight:700;">
                        <?= $locked ? ' Buka Kunci' : '🔒 Kunci Standar' ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary" style="margin-bottom:24px;padding:14px 32px;font-size:15px;font-weight:700;box-shadow:0 4px 12px rgba(15,61,92,.2);">
        💾 Simpan Semua Perubahan
    </button>
</form>

<script>
// Animate progress bar on load
window.addEventListener('load', function() {
    const progBar = document.querySelector('.edp-header > div > div');
    if (progBar) {
        const width = progBar.style.width;
        progBar.style.width = '0%';
        setTimeout(() => {
            progBar.style.width = width;
        }, 300);
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>