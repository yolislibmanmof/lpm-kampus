<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]); // Admin & Pimpinan
$db = Database::getInstance();
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$pid = (int)($_GET['prodi'] ?? 0);

$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$standarList = $db->query("SELECT id_standar, kode_standar FROM standar_mutu ORDER BY kode_standar")->fetchAll();

/* ===== Matriks capaian ===== */
$ev = $db->prepare("SELECT id_prodi, id_standar, capaian FROM evaluasi_diri WHERE tahun = ?");
$ev->execute([$tahun]);
$map = [];
foreach ($ev->fetchAll() as $r) { $map[$r['id_prodi']][$r['id_standar']] = $r['capaian']; }

/* ===== Rata-rata per standar ===== */
$avgPer = $db->prepare("SELECT s.kode_standar, ROUND(AVG(e.capaian),1) a FROM standar_mutu s LEFT JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.tahun = ? GROUP BY s.id_standar ORDER BY s.kode_standar");
$avgPer->execute([$tahun]);
$avgList = $avgPer->fetchAll();

/* ===== Kuesioner ===== */
$kue = $db->query("SELECT tipe_responden, aspek, ROUND(AVG(skor),2) rata FROM kuesioner GROUP BY tipe_responden, aspek")->fetchAll();
$aspekLabels = array_values(array_unique(array_column($kue, 'aspek')));
$dsMhs = array_fill(0, count($aspekLabels), null);
$dsDosen = array_fill(0, count($aspekLabels), null);
foreach ($kue as $row) {
    $i = array_search($row['aspek'], $aspekLabels);
    if ($row['tipe_responden'] === 'Mahasiswa') $dsMhs[$i] = (float)$row['rata']; else $dsDosen[$i] = (float)$row['rata'];
}

/* ===== RADAR ===== */
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

<style>
    /* ===== MONEV DASHBOARD — PREMIUM UI ===== */
    .monev-hero {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
        border-radius: var(--radius-lg); padding: 40px; color: #fff;
        margin-bottom: 32px; position: relative; overflow: hidden;
        box-shadow: var(--shadow-lg);
    }
    .monev-hero::before {
        content: ''; position: absolute; width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -100px; top: -150px; animation: monevPulse 5s ease-in-out infinite;
    }
    @keyframes monevPulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 0.8; } }
    
    .monev-hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; }
    .monev-hero-text h2 { font-size: 28px; margin: 0 0 10px; font-weight: 800; letter-spacing: -0.02em; }
    .monev-hero-text p { color: rgba(255,255,255,.85); font-size: 15px; margin: 0; }
    
    .monev-hero-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 50px;
        background: rgba(201,162,39,.2); border: 1px solid rgba(201,162,39,.4);
        font-size: 11px; font-weight: 700; color: #E8C55A;
        margin-bottom: 16px; letter-spacing: 1px; text-transform: uppercase;
    }

    .filter-bar {
        background: var(--bg-card); border-radius: 12px; padding: 16px 20px;
        border: 1px solid var(--border); margin-bottom: 24px;
        display: flex; gap: 16px; flex-wrap: wrap; align-items: center;
        box-shadow: var(--shadow-sm);
    }
    .filter-label { font-size: 13px; font-weight: 700; color: var(--text-dark); margin-right: 8px; }
    .filter-select {
        padding: 8px 14px; border-radius: 8px; border: 1.5px solid var(--border);
        background: var(--bg-light); color: var(--text-dark); font-size: 13px; font-weight: 600;
        cursor: pointer; transition: all 0.2s;
    }
    .filter-select:hover { border-color: var(--accent); }
    .filter-select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(201,162,39,.15); }

    .chart-card {
        background: var(--bg-card); border-radius: 16px; padding: 28px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
        transition: all 0.3s var(--ease-out);
    }
    .chart-card:hover { box-shadow: var(--shadow-md); border-color: rgba(201,162,39,.3); }
    .chart-card h3 { font-size: 18px; margin: 0 0 20px; color: var(--text-dark); font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .chart-info {
        margin-top: 16px; padding: 12px 16px;
        background: rgba(201,162,39,.06); border-left: 3px solid var(--accent);
        border-radius: 8px; font-size: 13px; color: var(--text-muted); line-height: 1.5;
    }

    .monev-table th { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; text-align: center; }
    .monev-table td { vertical-align: middle; padding: 14px 16px; text-align: center; }
    .monev-table td:first-child { text-align: left; font-weight: 600; color: var(--text-dark); }
    
    .val-high { color: #10B981; font-weight: 800; }
    .val-mid { color: #F59E0B; font-weight: 700; }
    .val-low { color: #94A3B8; font-weight: 600; }

    @media (max-width: 992px) {
        .monev-hero-content { flex-direction: column; text-align: center; }
    }
    @media (max-width: 768px) {
        .filter-bar { flex-direction: column; align-items: stretch; }
    }
</style>

<!-- HERO SECTION -->
<div class="monev-hero">
    <div class="monev-hero-content">
        <div class="monev-hero-text">
            <div class="monev-hero-badge">
                <span>📊</span>
                <span>Monitoring & Evaluasi</span>
            </div>
            <h2>Dashboard Analitik Mutu Institusi</h2>
            <p>Pantau capaian standar mutu, perbandingan antar prodi, dan umpan balik kuesioner secara real-time untuk pengambilan keputusan berbasis data.</p>
        </div>
    </div>
</div>

<!-- FILTER BAR -->
<form method="GET" class="filter-bar">
    <span class="filter-label">Filter Data:</span>
    <select name="tahun" class="filter-select" onchange="this.form.submit()">
        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
            <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>>Tahun <?= $y ?></option>
        <?php endfor; ?>
    </select>
    <select name="prodi" class="filter-select" onchange="this.form.submit()">
        <option value="0" <?= $pid == 0 ? 'selected' : '' ?>>🏫 Seluruh Universitas</option>
        <?php foreach ($prodiList as $p): ?>
            <option value="<?= $p['id_prodi'] ?>" <?= $pid == $p['id_prodi'] ? 'selected' : '' ?>><?= Security::e($p['nama_prodi']) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<!-- CHARTS ROW 1 -->
<div style="display:grid;grid-template-columns:1fr 1.3fr;gap:24px;margin-bottom:24px;">
    <!-- RADAR -->
    <div class="chart-card">
        <h3>📡 Radar Capaian Standar</h3>
        <div style="height:340px;"><canvas id="cRadar"></canvas></div>
        <div class="chart-info">
            💡 <strong>Area Navy</strong> = Capaian riil • <strong>Garis Emas</strong> = Target. Semakin penuh area navy menutupi garis emas, semakin sehat mutu unit tersebut.
        </div>
    </div>
    <!-- BAR RATA-RATA -->
    <div class="chart-card">
        <h3>📈 Rata-rata Capaian per Standar (<?= $tahun ?>)</h3>
        <div style="height:340px;"><canvas id="cCap"></canvas></div>
    </div>
</div>

<!-- CHART ROW 2: KUESIONER -->
<div class="chart-card" style="margin-bottom:24px;">
    <h3>💬 Umpan Balik Kuesioner (Skala 1 - 5)</h3>
    <div style="height:320px;"><canvas id="cKue"></canvas></div>
</div>

<!-- MATRIKS -->
<div class="chart-card">
    <h3>🗂️ Matriks Capaian per Program Studi</h3>
    <div class="table-wrapper">
        <table class="monev-table">
            <thead>
                <tr>
                    <th style="text-align:left;">Program Studi</th>
                    <?php foreach ($standarList as $s): ?>
                        <th title="<?= Security::e($s['kode_standar']) ?>"><?= str_replace('S', '', $s['kode_standar']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($prodiList as $p): ?>
                <tr>
                    <td><?= Security::e($p['nama_prodi']) ?></td>
                    <?php foreach ($standarList as $s): 
                        $c = $map[$p['id_prodi']][$s['id_standar']] ?? null;
                        $class = $c === null ? 'val-low' : ($c >= 100 ? 'val-high' : ($c >= 70 ? 'val-mid' : 'val-low'));
                    ?>
                        <td class="<?= $class ?>">
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
/* 📡 RADAR CHART */
const ctxRadar = document.getElementById('cRadar').getContext('2d');
new Chart(ctxRadar, {
    type: 'radar',
    data: {
        labels: <?= json_encode(array_column($radar, 'kode_standar')) ?>,
        datasets: [
            {
                label: 'Capaian (%)',
                data: <?= json_encode(array_map(fn($r) => $r['c'] !== null ? (float)$r['c'] : 0, $radar)) ?>,
                backgroundColor: 'rgba(15, 61, 92, 0.25)',
                borderColor: '#0F3D5C',
                borderWidth: 2,
                pointBackgroundColor: '#0F3D5C',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            },
            {
                label: 'Target (%)',
                data: <?= json_encode(array_map(fn($r) => (float)($r['t'] ?? 100), $radar)) ?>,
                backgroundColor: 'transparent',
                borderColor: '#C9A227',
                borderWidth: 2,
                borderDash: [6, 4],
                pointBackgroundColor: '#C9A227',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 12, weight: '600' } } },
            tooltip: {
                backgroundColor: 'rgba(15, 61, 92, 0.95)',
                titleFont: { size: 13, weight: 'bold' },
                bodyFont: { size: 13 },
                padding: 12,
                cornerRadius: 8
            }
        },
        scales: {
            r: {
                min: 0,
                max: 100,
                ticks: { display: false, stepSize: 20 },
                grid: { color: 'rgba(120,140,170,.2)' },
                angleLines: { color: 'rgba(120,140,170,.2)' },
                pointLabels: { font: { weight: 700, size: 12 }, color: 'var(--text-dark)' }
            }
        }
    }
});

/* 📈 BAR CHART: RATA-RATA CAPAIAN */
const ctxCap = document.getElementById('cCap').getContext('2d');
const capData = <?= json_encode(array_column($avgList, 'a')) ?>;
const capColors = capData.map(v => v >= 100 ? '#10B981' : (v >= 70 ? '#C9A227' : '#EF4444'));

new Chart(ctxCap, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($avgList, 'kode_standar')) ?>,
        datasets: [{
            label: 'Rata-rata Capaian (%)',
            data: capData,
            backgroundColor: capColors,
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(15, 61, 92, 0.95)',
                padding: 12,
                cornerRadius: 8,
                callbacks: { label: function(context) { return 'Capaian: ' + context.parsed.y + '%'; } }
            }
        },
        scales: {
            y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } }
        }
    }
});

/* 💬 BAR CHART: KUESIONER */
const ctxKue = document.getElementById('cKue').getContext('2d');
new Chart(ctxKue, {
    type: 'bar',
    data: {
        labels: <?= json_encode($aspekLabels) ?>,
        datasets: [
            {
                label: 'Mahasiswa',
                data: <?= json_encode($dsMhs) ?>,
                backgroundColor: '#C9A227',
                borderRadius: 6,
                borderSkipped: false
            },
            {
                label: 'Dosen',
                data: <?= json_encode($dsDosen) ?>,
                backgroundColor: '#0F3D5C',
                borderRadius: 6,
                borderSkipped: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { usePointStyle: true, padding: 20, font: { size: 12, weight: '600' } } },
            tooltip: {
                backgroundColor: 'rgba(15, 61, 92, 0.95)',
                padding: 12,
                cornerRadius: 8
            }
        },
        scales: {
            y: { beginAtZero: true, max: 5, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } }
        }
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>