<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([2]);
$db = Database::getInstance();
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$ikus = $db->query("SELECT * FROM iku_indikator ORDER BY id_iku")->fetchAll();
$dm = $db->prepare("SELECT id_iku, nilai FROM iku_data WHERE tahun = ?");
$dm->execute([$tahun]);
$data = [];
foreach ($dm->fetchAll() as $d) $data[$d['id_iku']] = $d;

$pcts = [];
foreach ($ikus as $k) {
    $pcts[] = (isset($data[$k['id_iku']]) && $k['target'] > 0) ? min(round($data[$k['id_iku']]['nilai'] / $k['target'] * 100, 1), 150) : 0;
}
$filled = array_filter($pcts);
$overall = $filled ? round(array_sum($filled) / count($filled)) : 0;

$simTitle = 'Dasbor IKU';
$activeMenu = 'iku';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:var(--radius-lg);padding:32px 36px;color:#fff;margin-bottom:28px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
    <div>
        <h2 style="font-size:24px;">📊 Capaian IKU <?= $tahun ?></h2>
        <p style="opacity:.8;">Indikator Kinerja Utama — pandangan eksekutif satu layar.</p>
    </div>
    <div style="text-align:right;">
        <div style="font-size:44px;font-weight:800;color:var(--accent-light);"><?= $overall ?>%</div>
        <p style="opacity:.8;font-size:13px;">Rata-rata capaian terhadap target</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:20px;margin-bottom:28px;">
    <?php foreach ($ikus as $i => $k): $p = $pcts[$i]; ?>
    <div class="stat-card" style="border-left:4px solid <?= $p >= 100 ? 'var(--success)' : ($p >= 60 ? 'var(--accent)' : ($p > 0 ? 'var(--danger)' : 'var(--border)')) ?>;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <span class="badge badge-a"><?= Security::e($k['kode']) ?></span>
            <strong style="font-size:20px;color:<?= $p >= 100 ? 'var(--success)' : 'var(--text-dark)' ?>;"><?= $p > 0 ? $p . '%' : '—' ?></strong>
        </div>
        <p class="text-muted" style="font-size:12.5px;margin-top:8px;"><?= Security::e($k['nama']) ?></p>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📈 Capaian vs Target</h3>
    <div style="height:340px;"><canvas id="cIku"></canvas></div>
    <p class="text-muted" style="font-size:12.5px;margin-top:12px;">💡 IKU 1 & 8 dapat dihitung otomatis oleh Admin dari Tracer Study dan Survei Pengguna — angka selalu berbasis data hidup.</p>
</div>

<script>
new Chart(document.getElementById('cIku'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($ikus, 'kode')) ?>,
        datasets: [
            { label: 'Capaian (%)', data: <?= json_encode($pcts) ?>, backgroundColor: '#C9A227', borderRadius: 8 },
            { label: 'Target (100%)', data: <?= json_encode(array_fill(0, count($ikus), 100)) ?>, type: 'line', borderColor: '#0F3D5C', borderDash: [6, 4], pointRadius: 0, fill: false }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 150 } } }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>