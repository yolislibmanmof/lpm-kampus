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
    <div class="alert alert-danger">⚠️ Akun Anda belum terhubung ke Program Studi. Hubungi Admin LPM.</div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div class="no-print" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;">
        <label class="form-label" style="margin:0;">Tahun:</label>
        <select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
    <p class="text-muted" style="font-size:13px;">💡 Isi capaian & target, simpan draft, lalu <strong>Kunci</strong> jika sudah final.</p>
</div>

<form method="POST">
    <?= Security::csrfField() ?>
    <input type="hidden" name="action" value="save">

    <?php foreach ($standar as $s): $e = $evalMap[$s['id_standar']] ?? null; $locked = ($e['status'] ?? '') === 'Terkunci'; ?>
    <div class="card" style="margin-bottom:20px;<?= $locked ? 'border-left:4px solid var(--success);' : '' ?>">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
            <h3 style="font-size:17px;"><?= Security::e($s['kode_standar']) ?> — <?= Security::e($s['nama_standar']) ?></h3>
            <?php if ($e): ?>
                <span class="badge <?= $locked ? 'badge-unggul' : 'badge-b' ?>"><?= $locked ? '🔒 Terkunci' : '✏️ Draf' ?></span>
            <?php else: ?>
                <span class="badge badge-baik">Belum Dinilai</span>
            <?php endif; ?>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 2fr;gap:16px;">
            <div class="form-group"><label class="form-label">Capaian (%)</label>
                <input type="number" min="0" max="100" step="0.01" name="capaian[<?= $s['id_standar'] ?>]" class="form-control" value="<?= Security::e($e['capaian'] ?? '') ?>" <?= $locked ? 'disabled' : '' ?>></div>
            <div class="form-group"><label class="form-label">Target (%)</label>
                <input type="number" min="0" max="100" step="0.01" name="target[<?= $s['id_standar'] ?>]" class="form-control" value="<?= Security::e($e['target'] ?? '100') ?>" <?= $locked ? 'disabled' : '' ?>></div>
            <div class="form-group"><label class="form-label">Bukti Fisik (kode/nama dokumen)</label>
                <input type="text" name="bukti[<?= $s['id_standar'] ?>]" class="form-control" value="<?= Security::e($e['bukti_fisik'] ?? '') ?>" placeholder="cth: LKD-2026-01" <?= $locked ? 'disabled' : '' ?>></div>
        </div>
        <div class="form-group"><label class="form-label">Catatan / Analisis</label>
            <textarea name="catatan[<?= $s['id_standar'] ?>]" class="form-control" rows="2" <?= $locked ? 'disabled' : '' ?>><?= Security::e($e['catatan'] ?? '') ?></textarea></div>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary" style="margin-bottom:24px;">💾 Simpan Semua Draft</button>
</form>

<!-- Tombol Kunci/Buka per standar -->
<div class="card">
    <h3 style="margin-bottom:16px;">🔒 Kunci Standar (Finalisasi)</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php foreach ($standar as $s): $e = $evalMap[$s['id_standar']] ?? null; if (!$e) continue; $locked = $e['status'] === 'Terkunci'; ?>
            <form method="POST" style="display:inline;">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="lock">
                <input type="hidden" name="id_standar" value="<?= $s['id_standar'] ?>">
                <input type="hidden" name="new_status" value="<?= $locked ? 'Draf' : 'Terkunci' ?>">
                <button class="btn <?= $locked ? 'btn-outline' : 'btn-gold' ?>" style="padding:8px 16px;font-size:13px;">
                    <?= $locked ? ' Buka' : ' Kunci' ?> <?= Security::e($s['kode_standar']) ?>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>