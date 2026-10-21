<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

$h = (int)date('H');
$sapa = $h < 10 ? 'Selamat Pagi' : ($h < 15 ? 'Selamat Siang' : ($h < 18 ? 'Selamat Sore' : 'Selamat Malam'));
$tahun = date('Y');

// Statistik Utama
$totalProdi = $db->query("SELECT COUNT(*) t FROM prodi")->fetch()['t'] ?? 0;
$unggul = $db->query("SELECT COUNT(*) t FROM akreditasi WHERE tingkat='Prodi' AND peringkat IN ('Unggul','A')")->fetch()['t'] ?? 0;
$avgCapaian = $db->query("SELECT ROUND(AVG(capaian),1) a FROM evaluasi_diri WHERE tahun = $tahun")->fetch()['a'] ?? 0;
$mayorOpen = $db->query("SELECT COUNT(*) t FROM temuan_audit WHERE kategori='Mayor' AND status_verifikasi != 'Diterima'")->fetch()['t'] ?? 0;

// Data Chart 1: Tren Capaian
$capTahun = $db->query("SELECT tahun, ROUND(AVG(capaian),1) a FROM evaluasi_diri GROUP BY tahun ORDER BY tahun ASC")->fetchAll();
$chartTahun = array_column($capTahun, 'tahun');
$chartNilai = array_column($capTahun, 'a');

// Data Chart 2: Distribusi Akreditasi
$akr = $db->query("SELECT peringkat, COUNT(*) j FROM akreditasi WHERE tingkat='Prodi' GROUP BY peringkat ORDER BY FIELD(peringkat, 'Unggul', 'A', 'Baik Sekali', 'B', 'Baik', 'C')")->fetchAll();
$chartAkrLabel = array_column($akr, 'peringkat');
$chartAkrData = array_column($akr, 'j');

$simTitle = 'Dashboard Pimpinan';
$activeMenu = 'dashboard';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    .exec-banner {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        border-radius: var(--radius-lg); padding: 36px 40px; color: #fff;
        position: relative; overflow: hidden; margin-bottom: 32px;
        box-shadow: 0 10px 30px rgba(15,61,92,.2);
    }
    .exec-banner::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
        right: -50px; top: -100px;
    }
    .exec-banner h2 { font-size: 26px; margin: 0 0 8px; position: relative; z-index: 1; font-weight: 800; }
    .exec-banner p { color: rgba(255,255,255,.8); position: relative; z-index: 1; max-width: 600px; font-size: 15px; margin: 0; }

    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .kpi-card {
        background: var(--bg-card); border-radius: 16px; padding: 24px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
        display: flex; align-items: center; gap: 18px; transition: all 0.3s var(--ease-out);
    }
    .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: rgba(201,162,39,.3); }
    .kpi-icon {
        width: 56px; height: 56px; border-radius: 14px; display: grid; place-items: center; font-size: 24px; flex-shrink: 0;
    }
    .kpi-icon.blue { background: linear-gradient(135deg, rgba(59,130,246,.15), rgba(59,130,246,.05)); color: #3B82F6; }
    .kpi-icon.green { background: linear-gradient(135deg, rgba(16,185,129,.15), rgba(16,185,129,.05)); color: #10B981; }
    .kpi-icon.gold { background: linear-gradient(135deg, rgba(201,162,39,.15), rgba(201,162,39,.05)); color: #C9A227; }
    .kpi-icon.red { background: linear-gradient(135deg, rgba(239,68,68,.15), rgba(239,68,68,.05)); color: #EF4444; }
    
    .kpi-info h3 { font-size: 28px; margin: 0; color: var(--text-dark); font-weight: 800; line-height: 1; }
    .kpi-info small { font-size: 12px; color: var(--text-muted); font-weight: 600; margin-top: 6px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }

    .chart-card {
        background: var(--bg-card); border-radius: 16px; padding: 24px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
    }
    .chart-card h3 { font-size: 16px; margin: 0 0 20px; color: var(--text-dark); font-weight: 700; display: flex; align-items: center; gap: 8px; }
    
    .quick-actions {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 32px;
    }
    .qa-btn {
        display: flex; align-items: center; gap: 14px; padding: 20px;
        background: var(--bg-card); border: 1.5px solid var(--border); border-radius: 14px;
        text-decoration: none; color: var(--text-dark); transition: all 0.3s;
    }
    .qa-btn:hover { border-color: var(--accent); transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .qa-ic {
        width: 44px; height: 44px; border-radius: 10px; background: var(--bg-light);
        display: grid; place-items: center; font-size: 20px; flex-shrink: 0;
    }
    .qa-btn:hover .qa-ic { background: linear-gradient(135deg, var(--accent), var(--accent-light)); }
    .qa-text strong { display: block; font-size: 14px; margin-bottom: 2px; }
    .qa-text small { color: var(--text-muted); font-size: 12px; }
</style>

<div class="exec-banner">
    <h2><?= $sapa ?>, <?= Security::e(Auth::user()['nama_lengkap'] ?? Auth::user()['nama'] ?? 'Pimpinan') ?> 👋</h2>
    <p>Ringkasan eksekutif mutu institusi tersaji real-time untuk mendukung pengambilan keputusan strategis berbasis data (Data-Driven Decision Making).</p>
</div>

<!-- KPI CARDS -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue">🏫</div>
        <div class="kpi-info">
            <h3><?= $totalProdi ?></h3>
            <small>Total Program Studi</small>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green">🏆</div>
        <div class="kpi-info">
            <h3><?= $unggul ?></h3>
            <small>Prodi Unggul / A</small>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon gold">📊</div>
        <div class="kpi-info">
            <h3><?= $avgCapaian ?>%</h3>
            <small>Rata-rata Capaian EDP <?= $tahun ?></small>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red">⚠️</div>
        <div class="kpi-info">
            <h3><?= $mayorOpen ?></h3>
            <small>Temuan Mayor Terbuka</small>
        </div>
    </div>
</div>

<!-- CHARTS -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:32px;">
    <div class="chart-card">
        <h3>📈 Tren Rata-rata Capaian Mutu</h3>
        <div style="height:300px;"><canvas id="chartTren"></canvas></div>
    </div>
    <div class="chart-card">
        <h3>🏆 Distribusi Peringkat Akreditasi</h3>
        <div style="height:300px;display:flex;align-items:center;justify-content:center;"><canvas id="chartAkr"></canvas></div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="chart-card">
    <h3 style="margin-bottom:20px;">⚡ Akses Cepat Laporan Strategis</h3>
    <div class="quick-actions">
        <a href="/sim/pimpinan/rtm.php" class="qa-btn">
            <div class="qa-ic">📑</div>
            <div class="qa-text"><strong>Bahan Rapat RTM</strong><small>Cetak laporan tinjauan manajemen</small></div>
        </a>
        <a href="/sim/pimpinan/rekomendasi.php" class="qa-btn">
            <div class="qa-ic">🤖</div>
            <div class="qa-text"><strong>Rekomendasi AI</strong><small>Lihat usulan perbaikan otomatis</small></div>
        </a>
        <a href="/sim/pimpinan/temuan.php" class="qa-btn">
            <div class="qa-ic">🔍</div>
            <div class="qa-text"><strong>Monitoring Temuan</strong><small>Pantau tindak lanjut audit</small></div>
        </a>
        <a href="/sim/admin/users.php" class="qa-btn">
            <div class="qa-ic">⚙️</div>
            <div class="qa-text"><strong>Pengaturan Sistem</strong><small>Kelola pengguna dan akses</small></div>
        </a>
    </div>
</div>

<script>
// Chart 1: Tren Capaian
const ctxTren = document.getElementById('chartTren').getContext('2d');
new Chart(ctxTren, {
    type: 'line',
    data: {
        labels: <?= json_encode($chartTahun ?: ['Belum ada data']) ?>,
        datasets: [{
            label: 'Rata-rata Capaian (%)',
            data: <?= json_encode($chartNilai ?: [0]) ?>,
            borderColor: '#C9A227',
            backgroundColor: 'rgba(201,162,39,0.1)',
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
            y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Chart 2: Distribusi Akreditasi
const ctxAkr = document.getElementById('chartAkr').getContext('2d');
new Chart(ctxAkr, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chartAkrLabel ?: ['Belum ada data']) ?>,
        datasets: [{
            data: <?= json_encode($chartAkrData ?: [1]) ?>,
            backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#64748B', '#EF4444'],
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { 
                position: 'bottom', 
                labels: { usePointStyle: true, padding: 16, font: { size: 12, weight: '600' } } 
            }
        }
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>