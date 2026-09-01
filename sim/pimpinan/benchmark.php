<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$years = $db->query("SELECT tahun, ROUND(AVG(capaian),1) a FROM evaluasi_diri GROUP BY tahun ORDER BY tahun ASC")->fetchAll();
$years = array_slice($years, -5);

$fq = "SELECT f.nama_fakultas, ROUND(AVG(e.capaian),1) a FROM evaluasi_diri e
       JOIN prodi p ON e.id_prodi = p.id_prodi JOIN fakultas f ON p.id_fakultas = f.id_fakultas
       WHERE e.tahun = ? GROUP BY f.id_fakultas ORDER BY a DESC";
$now = $db->prepare($fq); $now->execute([$tahun]); $fakNow = $now->fetchAll();
$prev = $db->prepare($fq); $prev->execute([$tahun - 1]);
$fakPrev = [];
foreach ($prev->fetchAll() as $f) $fakPrev[$f['nama_fakultas']] = (float)$f['a'];

$warnings = [];
foreach ($fakNow as $f) {
    $d = isset($fakPrev[$f['nama_fakultas']]) ? round((float)$f['a'] - $fakPrev[$f['nama_fakultas']], 1) : null;
    if ((float)$f['a'] < 70) $warnings[] = "🔴 {$f['nama_fakultas']} rata-rata {$f['a']}% (< 70%) — perlu pendampingan intensif.";
    elseif ($d !== null && $d < 0) $warnings[] = "📉 {$f['nama_fakultas']} menurun {$d} poin dibanding {$tahun}-1 — investigasi penyebab.";
}

$simTitle = 'Benchmark & Analitik';
$activeMenu = 'bench';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <h2 style="font-size:22px;color:var(--primary-dark);">🌐 Benchmark & Analitik Lintas Tahun</h2>
    <form method="GET"><select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?><option <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?>
    </select></form>
</div>

<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card"><h3 style="margin-bottom:16px;">📈 Tren Capaian Universitas</h3>
        <div style="height:300px;"><canvas id="cTren"></canvas></div></div>
    <div class="card"><h3 style="margin-bottom:16px;">🏫 Perbandingan Fakultas (<?= $tahun ?>)</h3>
        <div style="height:300px;"><canvas id="cFak"></canvas></div></div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">🏆 Papan Peringkat Fakultas</h3>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Fakultas</th><th>Capaian <?= $tahun ?></th><th>Tren</th></tr></thead>
                <tbody>
                <?php foreach ($fakNow as $i => $f):
                    $d = isset($fakPrev[$f['nama_fakultas']]) ? round((float)$f['a'] - $fakPrev[$f['nama_fakultas']], 1) : null;
                ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= Security::e($f['nama_fakultas']) ?></strong></td>
                        <td><span class="badge <?= $f['a'] >= 100 ? 'badge-unggul' : ($f['a'] >= 70 ? 'badge-a' : 'badge-unggul') ?>" style="<?= $f['a'] < 70 ? 'background:#FEE2E2;color:#991B1B' : '' ?>"><?= $f['a'] ?>%</span></td>
                        <td><?= $d === null ? '—' : ($d > 0 ? "<span style='color:var(--success);font-weight:800;'>▲ +$d</span>" : ($d < 0 ? "<span style='color:var(--danger);font-weight:800;'>▼ $d</span>" : '● stabil')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card" style="border:2px solid <?= $warnings ? 'var(--danger)' : 'var(--success)' ?>;">
        <h3 style="margin-bottom:16px;"><?= $warnings ? '🚨 Peringatan Dini' : '✅ Semua Aman' ?></h3>
        <?php if (!$warnings): ?><p class="text-muted">Tidak ada fakultas menurun atau di bawah 70%. Pertahankan!</p>
        <?php else: foreach ($warnings as $w): ?>
            <p style="font-size:14px;padding:12px 14px;background:var(--bg-light);border-radius:10px;margin-bottom:10px;"><?= $w ?></p>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
new Chart(document.getElementById('cTren'), {
    type: 'line',
    data: { labels: <?= json_encode(array_column($years, 'tahun')) ?>,
        datasets: [{ label: 'Rata-rata Capaian (%)', data: <?= json_encode(array_column($years, 'a')) ?>,
            borderColor: '#C9A227', backgroundColor: 'rgba(201,162,39,.15)', fill: true, tension: .35, pointRadius: 5, pointBackgroundColor: '#0F3D5C' }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 110 } } }
});
new Chart(document.getElementById('cFak'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($fakNow, 'nama_fakultas')) ?: '["-"]' ?>,
        datasets: [{ label: 'Capaian (%)', data: <?= json_encode(array_column($fakNow, 'a')) ?: '[0]' ?>, backgroundColor: '#0F3D5C', borderRadius: 8 }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 110 } } }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>