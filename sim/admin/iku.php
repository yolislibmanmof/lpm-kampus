<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$pesan = '';
$tahun = (int)($_GET['tahun'] ?? date('Y'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'target') {
        $db->prepare("UPDATE iku_indikator SET target = ?, nama = ? WHERE id_iku = ?")
           ->execute([(float)$_POST['target'], trim($_POST['nama']), (int)$_POST['id_iku']]);
        $pesan = '✅ Target diperbarui.';
    }

    if ($action === 'nilai') {
        $db->prepare("REPLACE INTO iku_data (id_iku, tahun, nilai, catatan) VALUES (?,?,?,?)")
           ->execute([(int)$_POST['id_iku'], $tahun, (float)$_POST['nilai'], trim($_POST['catatan'] ?? '')]);
        Logger::log('UPDATE', "Input nilai IKU tahun $tahun");
        $pesan = '✅ Nilai tersimpan.';
    }

    if ($action === 'auto') {
        // IKU 1 dari Tracer Study
        $tr = $db->query("SELECT COUNT(*) t, SUM(status_kerja IN ('Bekerja','Wirausaha')) w FROM tracer_alumni")->fetch();
        $iku1 = $tr['t'] > 0 ? round($tr['w'] / $tr['t'] * 100, 1) : null;
        // IKU 8 dari Survei Pengguna (rata-rata 7 aspek → skala 100)
        $sp = $db->query("SELECT AVG((aspek_etika+aspek_keahlian+aspek_bahasa+aspek_teknologi+aspek_komunikasi+aspek_kerjasama+aspek_pengembangan)/7)*20 r FROM survei_pengguna")->fetch();
        $iku8 = $sp['r'] ? round((float)$sp['r'], 1) : null;
        foreach ([['IKU 1', $iku1, 'Otomatis: Tracer Study'], ['IKU 8', $iku8, 'Otomatis: Survei Pengguna']] as $a) {
            if ($a[1] !== null) {
                $id = $db->prepare("SELECT id_iku FROM iku_indikator WHERE kode = ?"); $id->execute([$a[0]]);
                $iid = $id->fetch()['id_iku'] ?? null;
                if ($iid) $db->prepare("REPLACE INTO iku_data (id_iku, tahun, nilai, catatan) VALUES (?,?,?,?)")->execute([$iid, $tahun, $a[1], $a[2]]);
            }
        }
        $pesan = '⚡ IKU 1 & IKU 8 dihitung otomatis dari Tracer Study dan Survei Pengguna.';
    }
}

$ikus = $db->query("SELECT * FROM iku_indikator ORDER BY id_iku")->fetchAll();
$dm = $db->prepare("SELECT id_iku, nilai, catatan FROM iku_data WHERE tahun = ?");
$dm->execute([$tahun]);
$data = [];
foreach ($dm->fetchAll() as $d) $data[$d['id_iku']] = $d;

$simTitle = 'IKU — Indikator Kinerja Utama';
$activeMenu = 'iku';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
        <h2 style="font-size:22px;color:var(--primary-dark);">📊 Dasbor IKU <?= $tahun ?></h2>
        <p class="text-muted" style="font-size:13px;">Target & nama indikator dapat disesuaikan dengan kebijakan kampus Anda.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <form method="GET"><select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?><option <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?>
        </select></form>
        <form method="POST"><input type="hidden" name="action" value="auto"><?= Security::csrfField() ?><button class="btn btn-gold">⚡ Hitung Otomatis IKU 1 & 8</button></form>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
    <?php foreach ($ikus as $k):
        $d = $data[$k['id_iku']] ?? null;
        $pct = ($d && $k['target'] > 0) ? min(round($d['nilai'] / $k['target'] * 100), 150) : 0;
    ?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:8px;">
            <span class="badge badge-a"><?= Security::e($k['kode']) ?></span>
            <?php if ($d): ?>
                <span class="badge <?= $pct >= 100 ? 'badge-unggul' : ($pct >= 60 ? 'badge-b' : 'badge-unggul') ?>" style="<?= $pct < 60 ? 'background:#FEE2E2;color:#991B1B' : '' ?>"><?= $pct >= 100 ? '🏆 Tercapai' : $pct . '%' ?></span>
            <?php else: ?><span class="badge badge-baik">Belum diinput</span><?php endif; ?>
        </div>
        <p style="font-weight:700;font-size:14.5px;margin-bottom:12px;"><?= Security::e($k['nama']) ?></p>
        <div style="background:var(--bg-light);border-radius:50px;height:10px;overflow:hidden;margin-bottom:14px;">
            <div style="height:100%;width:<?= min($pct, 100) ?>%;background:<?= $pct >= 100 ? 'var(--success)' : ($pct >= 60 ? 'var(--accent)' : 'var(--danger)') ?>;border-radius:50px;transition:width .8s;"></div>
        </div>
        <form method="POST" style="display:grid;grid-template-columns:1.6fr 1fr 1fr auto;gap:8px;align-items:end;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="nilai">
            <input type="hidden" name="id_iku" value="<?= $k['id_iku'] ?>">
            <div class="form-group" style="margin:0;"><label class="form-label">Nilai (<?= Security::e($k['satuan']) ?>)</label>
                <input type="number" step="0.1" name="nilai" class="form-control" value="<?= $d['nilai'] ?? '' ?>" placeholder="target: <?= $k['target'] ?>"></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Catatan</label>
                <input type="text" name="catatan" class="form-control" value="<?= Security::e($d['catatan'] ?? '') ?>"></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Target</label>
                <input type="number" step="0.1" name="target" form="tgt<?= $k['id_iku'] ?>" class="form-control" value="<?= $k['target'] ?>"></div>
            <button class="btn btn-primary" style="padding:10px 16px;">💾</button>
        </form>
        <form method="POST" id="tgt<?= $k['id_iku'] ?>" style="display:none;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="target">
            <input type="hidden" name="id_iku" value="<?= $k['id_iku'] ?>">
            <input type="hidden" name="nama" value="<?= Security::e($k['nama']) ?>">
        </form>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📈 Capaian vs Target (<?= $tahun ?>)</h3>
    <div style="height:320px;"><canvas id="cIku"></canvas></div>
</div>

<script>
new Chart(document.getElementById('cIku'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($ikus, 'kode')) ?>,
        datasets: [
            { label: 'Capaian (%)', data: <?= json_encode(array_map(fn($k) => isset($data[$k['id_iku']], $k['target']) && $k['target'] > 0 ? min(round($data[$k['id_iku']]['nilai'] / $k['target'] * 100, 1), 150) : 0, $ikus)) ?>, backgroundColor: '#C9A227', borderRadius: 8 },
            { label: 'Target (100%)', data: <?= json_encode(array_fill(0, count($ikus), 100)) ?>, type: 'line', borderColor: '#0F3D5C', borderDash: [6, 4], pointRadius: 0, fill: false }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 150 } } }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>