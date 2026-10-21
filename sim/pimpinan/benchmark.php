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
    elseif ($d !== null && $d < 0) $warnings[] = "📉 {$f['nama_fakultas']} menurun {$d} poin dibanding " . ($tahun-1) . " — investigasi penyebab.";
}

// Statistik Ringkas
$avgCapaian = $fakNow ? round(array_sum(array_column($fakNow, 'a')) / count($fakNow), 1) : 0;
$topFakultas = $fakNow[0]['nama_fakultas'] ?? '—';
$topNilai = $fakNow[0]['a'] ?? 0;

$simTitle = 'Benchmark & Analitik';
$activeMenu = 'bench';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    /* ===== BENCHMARK DASHBOARD — PREMIUM UI ===== */
    .bench-hero {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
        border-radius: var(--radius-lg); padding: 40px; color: #fff;
        margin-bottom: 32px; position: relative; overflow: hidden;
        box-shadow: var(--shadow-lg);
    }
    .bench-hero::before {
        content: ''; position: absolute; width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -100px; top: -150px; animation: benchPulse 5s ease-in-out infinite;
    }
    @keyframes benchPulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 0.8; } }
    
    .bench-hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; }
    .bench-hero-text h2 { font-size: 28px; margin: 0 0 10px; font-weight: 800; letter-spacing: -0.02em; }
    .bench-hero-text p { color: rgba(255,255,255,.85); font-size: 15px; margin: 0; }
    
    .bench-hero-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 50px;
        background: rgba(201,162,39,.2); border: 1px solid rgba(201,162,39,.4);
        font-size: 11px; font-weight: 700; color: #E8C55A;
        margin-bottom: 16px; letter-spacing: 1px; text-transform: uppercase;
    }

    .bench-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .bench-stat {
        background: var(--bg-card); border-radius: 16px; padding: 24px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
        display: flex; align-items: center; gap: 16px; transition: all 0.3s var(--ease-out);
    }
    .bench-stat:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: rgba(201,162,39,.3); }
    .bench-stat-ic {
        width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center; font-size: 24px; flex-shrink: 0;
    }
    .bench-stat-ic.gold { background: linear-gradient(135deg, rgba(201,162,39,.15), rgba(201,162,39,.05)); color: #C9A227; }
    .bench-stat-ic.blue { background: linear-gradient(135deg, rgba(59,130,246,.15), rgba(59,130,246,.05)); color: #3B82F6; }
    .bench-stat-ic.green { background: linear-gradient(135deg, rgba(16,185,129,.15), rgba(16,185,129,.05)); color: #10B981; }
    .bench-stat h4 { font-size: 28px; margin: 0; color: var(--text-dark); font-weight: 800; line-height: 1; }
    .bench-stat small { font-size: 12px; color: var(--text-muted); font-weight: 600; margin-top: 4px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }

    .chart-card {
        background: var(--bg-card); border-radius: 16px; padding: 28px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
    }
    .chart-card h3 { font-size: 18px; margin: 0 0 20px; color: var(--text-dark); font-weight: 700; display: flex; align-items: center; gap: 10px; }

    .rank-table th { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; }
    .rank-table td { vertical-align: middle; padding: 14px 16px; }
    .trend-up { color: #10B981; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; }
    .trend-down { color: #EF4444; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; }
    .trend-stable { color: #64748B; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }

    .warning-card {
        background: var(--bg-card); border-radius: 16px; padding: 28px;
        border: 2px solid var(--border); box-shadow: var(--shadow-sm);
    }
    .warning-card.danger { border-color: #FCA5A5; background: rgba(239,68,68,.03); }
    .warning-card.success { border-color: #6EE7B7; background: rgba(16,185,129,.03); }
    .warning-card h3 { font-size: 18px; margin: 0 0 16px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .warning-item {
        padding: 14px 16px; background: var(--bg-light); border-radius: 10px;
        margin-bottom: 10px; font-size: 14px; color: var(--text-dark); border-left: 4px solid #EF4444;
    }
    .warning-item:last-child { margin-bottom: 0; }

    @media (max-width: 992px) {
        .bench-hero-content { flex-direction: column; text-align: center; }
    }
</style>

<!-- HERO SECTION -->
<div class="bench-hero">
    <div class="bench-hero-content">
        <div class="bench-hero-text">
            <div class="bench-hero-badge">
                <span>🌐</span>
                <span>Benchmark & Analitik</span>
            </div>
            <h2>Analisis Lintas Tahun & Fakultas</h2>
            <p>Pandangan komprehensif tentang tren capaian mutu universitas dan perbandingan kinerja antar fakultas.</p>
        </div>
        <form method="GET">
            <select name="tahun" class="form-control" style="width:auto; background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.3);" onchange="this.form.submit()">
                <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                    <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?> style="color: #000;"><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>
</div>

<!-- STATISTICS -->
<div class="bench-stats">
    <div class="bench-stat">
        <div class="bench-stat-ic gold">📊</div>
        <div>
            <h4><?= $avgCapaian ?>%</h4>
            <small>Rata-rata Capaian <?= $tahun ?></small>
        </div>
    </div>
    <div class="bench-stat">
        <div class="bench-stat-ic blue">🏫</div>
        <div>
            <h4><?= count($fakNow) ?></h4>
            <small>Total Fakultas</small>
        </div>
    </div>
    <div class="bench-stat">
        <div class="bench-stat-ic green">🏆</div>
        <div>
            <h4><?= $topNilai ?>%</h4>
            <small>Terbaik: <?= Security::e($topFakultas) ?></small>
        </div>
    </div>
</div>

<!-- CHARTS -->
<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:24px;margin-bottom:32px;">
    <div class="chart-card">
        <h3>📈 Tren Capaian Universitas (5 Tahun Terakhir)</h3>
        <div style="height:320px;"><canvas id="cTren"></canvas></div>
    </div>
    <div class="chart-card">
        <h3>🏫 Perbandingan Capaian Fakultas (<?= $tahun ?>)</h3>
        <div style="height:320px;"><canvas id="cFak"></canvas></div>
    </div>
</div>

<!-- TABLE & WARNINGS -->
<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;">
    <div class="chart-card">
        <h3>🏆 Papan Peringkat Fakultas</h3>
        <div class="table-wrapper">
            <table class="rank-table">
                <thead>
                    <tr>
                        <th style="width:60px;text-align:center;">#</th>
                        <th>Fakultas</th>
                        <th style="width:120px;text-align:center;">Capaian</th>
                        <th style="width:120px;text-align:center;">Tren</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($fakNow as $i => $f):
                    $d = isset($fakPrev[$f['nama_fakultas']]) ? round((float)$f['a'] - $fakPrev[$f['nama_fakultas']], 1) : null;
                    $badgeClass = $f['a'] >= 100 ? 'badge-unggul' : ($f['a'] >= 70 ? 'badge-a' : 'badge-baik');
                    $badgeStyle = $f['a'] < 70 ? 'background:#FEE2E2;color:#991B1B' : '';
                ?>
                    <tr>
                        <td style="text-align:center;font-weight:800;color:var(--text-muted);"><?= $i + 1 ?></td>
                        <td><strong style="color:var(--text-dark);"><?= Security::e($f['nama_fakultas']) ?></strong></td>
                        <td style="text-align:center;">
                            <span class="badge" style="<?= $badgeStyle ?>"><?= $f['a'] ?>%</span>
                        </td>
                        <td style="text-align:center;">
                            <?php if ($d === null): ?>
                                <span class="trend-stable">● Stabil</span>
                            <?php elseif ($d > 0): ?>
                                <span class="trend-up">▲ +<?= $d ?></span>
                            <?php elseif ($d < 0): ?>
                                <span class="trend-down">▼ <?= $d ?></span>
                            <?php else: ?>
                                <span class="trend-stable">● Stabil</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="warning-card <?= $warnings ? 'danger' : 'success' ?>">
        <h3 style="color: <?= $warnings ? '#991B1B' : '#065F46' ?>">
            <?= $warnings ? '🚨 Peringatan Dini' : '✅ Semua Aman' ?>
        </h3>
        <?php if (!$warnings): ?>
            <p class="text-muted" style="margin:0;">Tidak ada fakultas yang menurun atau di bawah 70%. Pertahankan kinerja excellent!</p>
        <?php else: ?>
            <?php foreach ($warnings as $w): ?>
                <div class="warning-item"><?= $w ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Chart 1: Tren Capaian
const ctxTren = document.getElementById('cTren').getContext('2d');
new Chart(ctxTren, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($years, 'tahun')) ?: '["-"]' ?>,
        datasets: [{
            label: 'Rata-rata Capaian (%)',
            data: <?= json_encode(array_column($years, 'a')) ?: '[0]' ?>,
            borderColor: '#C9A227',
            backgroundColor: 'rgba(201,162,39,0.15)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 6,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#C9A227',
            pointBorderWidth: 3,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(15, 61, 92, 0.95)',
                titleFont: { size: 13, weight: 'bold' },
                bodyFont: { size: 13 },
                padding: 12,
                cornerRadius: 8,
                callbacks: { label: function(context) { return 'Capaian: ' + context.parsed.y + '%'; } }
            }
        },
        scales: {
            y: { beginAtZero: true, max: 110, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Chart 2: Perbandingan Fakultas
const ctxFak = document.getElementById('cFak').getContext('2d');
const fakLabels = <?= json_encode(array_column($fakNow, 'nama_fakultas')) ?: '["-"]' ?>;
const fakData = <?= json_encode(array_column($fakNow, 'a')) ?: '[0]' ?>;

// Warna dinamis berdasarkan nilai
const barColors = fakData.map(v => v >= 100 ? '#10B981' : (v >= 70 ? '#C9A227' : '#EF4444'));

new Chart(ctxFak, {
    type: 'bar',
    data: {
        labels: fakLabels,
        datasets: [{
            label: 'Capaian (%)',
            data: fakData,
            backgroundColor: barColors,
            borderRadius: 8,
            borderSkipped: false,
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
            y: { beginAtZero: true, max: 110, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } }
        }
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>