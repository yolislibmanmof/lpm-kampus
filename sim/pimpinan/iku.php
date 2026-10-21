<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$ikus = $db->query("SELECT * FROM iku_indikator ORDER BY id_iku")->fetchAll();
$dm = $db->prepare("SELECT id_iku, nilai FROM iku_data WHERE tahun = ?");
$dm->execute([$tahun]);
$data = [];
foreach ($dm->fetchAll() as $d) $data[$d['id_iku']] = $d['nilai'];

$pcts = [];
foreach ($ikus as $k) {
    $pcts[] = (isset($data[$k['id_iku']]) && $k['target'] > 0) ? min(round($data[$k['id_iku']] / $k['target'] * 100, 1), 150) : 0;
}
$filled = array_filter($pcts);
$overall = $filled ? round(array_sum($filled) / count($filled)) : 0;

// Hitung statistik
$onTrack = count(array_filter($pcts, fn($p) => $p >= 100));
$warning = count(array_filter($pcts, fn($p) => $p >= 60 && $p < 100));
$critical = count(array_filter($pcts, fn($p) => $p > 0 && $p < 60));
$noData = count(array_filter($pcts, fn($p) => $p == 0));

$simTitle = 'Dasbor IKU';
$activeMenu = 'iku';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    /* ===== IKU DASHBOARD — PREMIUM UI ===== */
    .iku-hero {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
        border-radius: var(--radius-lg); padding: 40px; color: #fff;
        margin-bottom: 32px; position: relative; overflow: hidden;
        box-shadow: var(--shadow-lg);
    }
    .iku-hero::before {
        content: ''; position: absolute; width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -100px; top: -150px; animation: ikuPulse 5s ease-in-out infinite;
    }
    .iku-hero::after {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(26,90,130,.4), transparent 70%);
        left: -80px; bottom: -100px; animation: ikuPulse 7s ease-in-out infinite reverse;
    }
    @keyframes ikuPulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 0.8; } }
    
    .iku-hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; }
    .iku-hero-text h2 { font-size: 28px; margin: 0 0 10px; font-weight: 800; letter-spacing: -0.02em; }
    .iku-hero-text p { color: rgba(255,255,255,.85); font-size: 15px; margin: 0; }
    
    .iku-hero-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 50px;
        background: rgba(201,162,39,.2); border: 1px solid rgba(201,162,39,.4);
        font-size: 11px; font-weight: 700; color: #E8C55A;
        margin-bottom: 16px; letter-spacing: 1px; text-transform: uppercase;
    }
    
    .iku-overall {
        text-align: center; padding: 24px 32px;
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.2); border-radius: 20px;
    }
    .iku-overall-num {
        font-size: 56px; font-weight: 900; line-height: 1;
        background: linear-gradient(135deg, #F7E491, #C9A227);
        -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        margin-bottom: 8px;
    }
    .iku-overall-label { font-size: 13px; color: rgba(255,255,255,.8); font-weight: 600; }

    .iku-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 32px; }
    .iku-stat {
        background: var(--bg-card); border-radius: 14px; padding: 20px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
        display: flex; align-items: center; gap: 14px; transition: all 0.3s var(--ease-out);
    }
    .iku-stat:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .iku-stat-ic {
        width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; font-size: 22px; flex-shrink: 0;
    }
    .iku-stat-ic.green { background: linear-gradient(135deg, rgba(16,185,129,.15), rgba(16,185,129,.05)); color: #10B981; }
    .iku-stat-ic.yellow { background: linear-gradient(135deg, rgba(245,158,11,.15), rgba(245,158,11,.05)); color: #F59E0B; }
    .iku-stat-ic.red { background: linear-gradient(135deg, rgba(239,68,68,.15), rgba(239,68,68,.05)); color: #EF4444; }
    .iku-stat-ic.gray { background: linear-gradient(135deg, rgba(100,116,139,.15), rgba(100,116,139,.05)); color: #64748B; }
    .iku-stat h4 { font-size: 24px; margin: 0; color: var(--text-dark); font-weight: 800; line-height: 1; }
    .iku-stat small { font-size: 11px; color: var(--text-muted); font-weight: 600; margin-top: 4px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }

    .iku-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .iku-card {
        background: var(--bg-card); border-radius: 16px; padding: 24px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
        transition: all 0.3s var(--ease-out); position: relative; overflow: hidden;
    }
    .iku-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: rgba(201,162,39,.3); }
    .iku-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, var(--accent), var(--primary));
    }
    .iku-card.status-on-track::before { background: linear-gradient(90deg, #10B981, #059669); }
    .iku-card.status-warning::before { background: linear-gradient(90deg, #F59E0B, #D97706); }
    .iku-card.status-critical::before { background: linear-gradient(90deg, #EF4444, #DC2626); }
    .iku-card.status-no-data::before { background: linear-gradient(90deg, #94A3B8, #64748B); }

    .iku-card-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    .iku-card-kode {
        padding: 6px 12px; border-radius: 8px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff; font-size: 12px; font-weight: 800; letter-spacing: 0.5px;
    }
    .iku-card-pct {
        font-size: 32px; font-weight: 900; line-height: 1;
        color: var(--text-dark);
    }
    .iku-card-pct.on-track { color: #10B981; }
    .iku-card-pct.warning { color: #F59E0B; }
    .iku-card-pct.critical { color: #EF4444; }
    .iku-card-pct.no-data { color: #94A3B8; }

    .iku-card-title { font-size: 14px; font-weight: 700; color: var(--text-dark); margin: 0 0 16px; line-height: 1.4; min-height: 40px; }
    
    .iku-progress {
        height: 8px; background: var(--bg-light); border-radius: 50px; overflow: hidden; margin-bottom: 12px;
    }
    .iku-progress-bar {
        height: 100%; border-radius: 50px; transition: width 1s var(--ease-out);
        background: linear-gradient(90deg, var(--accent), var(--accent-light));
    }
    .iku-card.status-on-track .iku-progress-bar { background: linear-gradient(90deg, #10B981, #34D399); }
    .iku-card.status-warning .iku-progress-bar { background: linear-gradient(90deg, #F59E0B, #FBBF24); }
    .iku-card.status-critical .iku-progress-bar { background: linear-gradient(90deg, #EF4444, #F87171); }
    .iku-card.status-no-data .iku-progress-bar { background: linear-gradient(90deg, #94A3B8, #CBD5E1); }

    .iku-card-meta { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); }
    .iku-card-meta strong { color: var(--text-dark); font-weight: 700; }

    .chart-card {
        background: var(--bg-card); border-radius: 16px; padding: 28px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
    }
    .chart-card h3 { font-size: 18px; margin: 0 0 20px; color: var(--text-dark); font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .chart-info {
        margin-top: 16px; padding: 12px 16px;
        background: rgba(201,162,39,.06); border-left: 3px solid var(--accent);
        border-radius: 8px; font-size: 13px; color: var(--text-muted);
    }

    @media (max-width: 768px) {
        .iku-hero { padding: 28px 20px; }
        .iku-hero-content { flex-direction: column; text-align: center; }
        .iku-overall { width: 100%; }
        .iku-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- HERO SECTION -->
<div class="iku-hero">
    <div class="iku-hero-content">
        <div class="iku-hero-text">
            <div class="iku-hero-badge">
                <span>📊</span>
                <span>Indikator Kinerja Utama</span>
            </div>
            <h2>Capaian IKU Tahun <?= $tahun ?></h2>
            <p>Pandangan eksekutif satu layar tentang kinerja institusi terhadap 8 indikator utama Kemendikbudristek.</p>
        </div>
        <div class="iku-overall">
            <div class="iku-overall-num"><?= $overall ?>%</div>
            <div class="iku-overall-label">Rata-rata Capaian</div>
        </div>
    </div>
</div>

<!-- STATISTICS -->
<div class="iku-stats">
    <div class="iku-stat">
        <div class="iku-stat-ic green">✅</div>
        <div>
            <h4><?= $onTrack ?></h4>
            <small>On Track (≥100%)</small>
        </div>
    </div>
    <div class="iku-stat">
        <div class="iku-stat-ic yellow">⚠️</div>
        <div>
            <h4><?= $warning ?></h4>
            <small>Perlu Perhatian</small>
        </div>
    </div>
    <div class="iku-stat">
        <div class="iku-stat-ic red">🔴</div>
        <div>
            <h4><?= $critical ?></h4>
            <small>Kritis (&lt;60%)</small>
        </div>
    </div>
    <div class="iku-stat">
        <div class="iku-stat-ic gray">📭</div>
        <div>
            <h4><?= $noData ?></h4>
            <small>Belum Ada Data</small>
        </div>
    </div>
</div>

<!-- IKU CARDS GRID -->
<div class="iku-grid">
    <?php foreach ($ikus as $i => $k): 
        $p = $pcts[$i];
        $statusClass = $p >= 100 ? 'status-on-track' : ($p >= 60 ? 'status-warning' : ($p > 0 ? 'status-critical' : 'status-no-data'));
        $pctClass = $p >= 100 ? 'on-track' : ($p >= 60 ? 'warning' : ($p > 0 ? 'critical' : 'no-data'));
        $nilai = isset($data[$k['id_iku']]) ? $data[$k['id_iku']] : 0;
    ?>
    <div class="iku-card <?= $statusClass ?>">
        <div class="iku-card-head">
            <span class="iku-card-kode"><?= Security::e($k['kode']) ?></span>
            <span class="iku-card-pct <?= $pctClass ?>"><?= $p > 0 ? $p . '%' : '—' ?></span>
        </div>
        <h4 class="iku-card-title"><?= Security::e($k['nama']) ?></h4>
        <div class="iku-progress">
            <div class="iku-progress-bar" style="width: <?= min($p, 100) ?>%;"></div>
        </div>
        <div class="iku-card-meta">
            <span>Capaian: <strong><?= $nilai ?></strong> <?= Security::e($k['satuan']) ?></span>
            <span>Target: <strong><?= $k['target'] ?></strong> <?= Security::e($k['satuan']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- CHART -->
<div class="chart-card">
    <h3> Visualisasi Capaian vs Target (100%)</h3>
    <div style="height:380px;"><canvas id="cIku"></canvas></div>
    <div class="chart-info">
        💡 <strong>Catatan:</strong> IKU 1 (Lulusan Bekerja) & IKU 8 (Kepuasan Pengguna) dapat dihitung otomatis oleh Admin dari data Tracer Study dan Survei Pengguna — angka selalu berbasis data hidup.
    </div>
</div>

<script>
// Animate progress bars on load
window.addEventListener('load', function() {
    document.querySelectorAll('.iku-progress-bar').forEach(function(bar) {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = width; }, 100);
    });
});

// Chart IKU
const ctxIku = document.getElementById('cIku').getContext('2d');
const pcts = <?= json_encode($pcts) ?>;
const labels = <?= json_encode(array_column($ikus, 'kode')) ?>;

// Warna dinamis berdasarkan status
const bgColors = pcts.map(p => 
    p >= 100 ? 'rgba(16, 185, 129, 0.8)' : 
    (p >= 60 ? 'rgba(245, 158, 11, 0.8)' : 
    (p > 0 ? 'rgba(239, 68, 68, 0.8)' : 'rgba(148, 163, 184, 0.5)'))
);
const borderColors = pcts.map(p => 
    p >= 100 ? '#10B981' : 
    (p >= 60 ? '#F59E0B' : 
    (p > 0 ? '#EF4444' : '#94A3B8'))
);

new Chart(ctxIku, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Capaian (%)',
                data: pcts,
                backgroundColor: bgColors,
                borderColor: borderColors,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            },
            {
                label: 'Target (100%)',
                data: <?= json_encode(array_fill(0, count($ikus), 100)) ?>,
                type: 'line',
                borderColor: '#0F3D5C',
                borderWidth: 2,
                borderDash: [8, 4],
                pointRadius: 0,
                fill: false,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: { usePointStyle: true, padding: 20, font: { size: 12, weight: '600' } }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 61, 92, 0.95)',
                titleFont: { size: 13, weight: 'bold' },
                bodyFont: { size: 13 },
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        if (context.datasetIndex === 0) {
                            const iku = <?= json_encode($ikus) ?>[context.dataIndex];
                            const nilai = <?= json_encode(array_map(fn($k) => isset($data[$k['id_iku']]) ? $data[$k['id_iku']] : 0, $ikus)) ?>[context.dataIndex];
                            return `Capaian: ${context.parsed.y}% (${nilai} ${iku.satuan})`;
                        }
                        return 'Target: 100%';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 150,
                ticks: {
                    callback: function(value) { return value + '%'; },
                    font: { size: 11 }
                },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 11, weight: '600' } }
            }
        }
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>