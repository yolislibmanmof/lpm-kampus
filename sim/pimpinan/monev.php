<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([2]);
$db = Database::getInstance();
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$pid = (int)($_GET['prodi'] ?? 0);

$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$standarList = $db->query("SELECT id_standar, kode_standar FROM standar_mutu ORDER BY kode_standar")->fetchAll();

/* ===== Matriks capaian (LAMA) ===== */
$ev = $db->prepare("SELECT id_prodi, id_standar, capaian FROM evaluasi_diri WHERE tahun = ?");
$ev->execute([$tahun]);
$map = [];
foreach ($ev->fetchAll() as $r) { $map[$r['id_prodi']][$r['id_standar']] = $r['capaian']; }

/* ===== Rata-rata per standar (LAMA) ===== */
$avgPer = $db->prepare("SELECT s.kode_standar, ROUND(AVG(e.capaian),1) a FROM standar_mutu s LEFT JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.tahun = ? GROUP BY s.id_standar ORDER BY s.kode_standar");
$avgPer->execute([$tahun]);
$avgList = $avgPer->fetchAll();

/* ===== Kuesioner (LAMA) ===== */
$kue = $db->query("SELECT tipe_responden, aspek, ROUND(AVG(skor),2) rata FROM kuesioner GROUP BY tipe_responden, aspek")->fetchAll();
$aspekLabels = array_values(array_unique(array_column($kue, 'aspek')));
$dsMhs = array_fill(0, count($aspekLabels), null);
$dsDosen = array_fill(0, count($aspekLabels), null);
foreach ($kue as $row) {
    $i = array_search($row['aspek'], $aspekLabels);
    if ($row['tipe_responden'] === 'Mahasiswa') $dsMhs[$i] = (float)$row['rata']; else $dsDosen[$i] = (float)$row['rata'];
}

/* ===== RADAR (BARU v3) ===== */
$rq = "SELECT s.kode_standar, ROUND(AVG(e.capaian),1) c, ROUND(AVG(e.target),1) t
       FROM standar_mutu s LEFT JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.tahun = ?";
$rParams = [$tahun];
if ($pid) { $rq .= " AND e.id_prodi = ?"; $rParams[] = $pid; }
$rq .= " GROUP BY s.id_standar ORDER BY s.kode_standar";
$rs = $db->prepare($rq);
$rs->execute($rParams);
$radar = $rs->fetchAll();

$simTitle = 'Monitoring & Evaluasi';
$activeMenu = 'monev';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<!-- Filter (LAMA tahun + BARU prodi) -->
<form method="GET" style="display:flex;gap:12px;align-items:center;margin-bottom:24px;flex-wrap:wrap;">
    <label class="form-label" style="margin:0;">Tahun:</label>
    <select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?><option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?>
    </select>
    <label class="form-label" style="margin:0;">Fokus Radar:</label>
    <select name="prodi" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="0">🏫 Seluruh Universitas</option>
        <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>" <?= $pid == $p['id_prodi'] ? 'selected' : '' ?>><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
    </select>
</form>

<div style="display:grid;grid-template-columns:1fr 1.3fr;gap:24px;margin-bottom:24px;">
    <!-- 📡 RADAR (BARU) -->
    <div class="card">
        <h3 style="margin-bottom:16px;">📡 Radar Capaian Standar <?= $pid ? '(Prodi Terpilih)' : '(Universitas)' ?> — <?= $tahun ?></h3>
        <div style="height:340px;"><canvas id="cRadar"></canvas></div>
        <p class="text-muted" style="font-size:12.5px;margin-top:12px;">💡 Area <strong style="color:var(--primary);">navy</strong> = capaian riil • Garis <strong style="color:var(--accent);">emas</strong> = target. Semakin penuh area navy menutupi emas, semakin sehat mutu prodi.</p>
    </div>
    <!-- Bar rata-rata (LAMA) -->
    <div class="card">
        <h3 style="margin-bottom:16px;">📈 Rata-rata Capaian per Standar (<?= $tahun ?>)</h3>
        <div style="height:340px;"><canvas id="cCap"></canvas></div>
    </div>
</div>

<!-- Kuesioner (LAMA) -->
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:20px;">💬 Umpan Balik Kuesioner (skala 5)</h3>
    <div style="height:300px;"><canvas id="cKue"></canvas></div>
</div>

<!-- Matriks (LAMA) -->
<div class="card">
    <h3 style="margin-bottom:16px;">🗂️ Matriks Capaian per Prodi</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Program Studi</th><?php foreach ($standarList as $s): ?><th style="text-align:center;"><?= Security::e($s['kode_standar']) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($prodiList as $p): ?>
                <tr>
                    <td><strong><?= Security::e($p['nama_prodi']) ?></strong></td>
                    <?php foreach ($standarList as $s): $c = $map[$p['id_prodi']][$s['id_standar']] ?? null; ?>
                        <td style="text-align:center;font-weight:700;color:<?= $c === null ? 'var(--text-muted)' : ($c >= 100 ? 'var(--success)' : 'var(--warning)') ?>;">
                            <?= $c === null ? '—' : $c . '%' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
/* 📡 RADAR (BARU) */
new Chart(document.getElementById('cRadar'), {
    type: 'radar',
    data: {
        labels: <?= json_encode(array_column($radar, 'kode_standar')) ?>,
        datasets: [
            { label: 'Capaian', data: <?= json_encode(array_map(fn($r) => $r['c'] !== null ? (float)$r['c'] : 0, $radar)) ?>,
              backgroundColor: 'rgba(15,61,92,.28)', borderColor: '#0F3D5C', borderWidth: 2, pointBackgroundColor: '#0F3D5C' },
            { label: 'Target', data: <?= json_encode(array_map(fn($r) => (float)($r['t'] ?? 100), $radar)) ?>,
              backgroundColor: 'transparent', borderColor: '#C9A227', borderWidth: 2, borderDash: [6, 4], pointBackgroundColor: '#C9A227' }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false,
        scales: { r: { min: 0, max: 100, ticks: { display: false }, grid: { color: 'rgba(120,140,170,.25)' }, angleLines: { color: 'rgba(120,140,170,.25)' }, pointLabels: { font: { weight: 700, size: 12 } } } } }
});
/* LAMA */
new Chart(document.getElementById('cCap'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($avgList, 'kode_standar')) ?>,
            datasets: [{ label: 'Capaian (%)', data: <?= json_encode(array_column($avgList, 'a')) ?>, backgroundColor: '#0F3D5C', borderRadius: 6 }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } }
});
new Chart(document.getElementById('cKue'), {
    type: 'bar',
    data: { labels: <?= json_encode($aspekLabels) ?>,
            datasets: [
                { label: 'Mahasiswa', data: <?= json_encode($dsMhs) ?>, backgroundColor: '#C9A227', borderRadius: 6 },
                { label: 'Dosen', data: <?= json_encode($dsDosen) ?>, backgroundColor: '#0F3D5C', borderRadius: 6 }
            ] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 5 } } }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>