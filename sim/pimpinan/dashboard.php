<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([2]);
$db = Database::getInstance();

$h = (int)date('H');
$sapa = $h < 10 ? 'Selamat Pagi' : ($h < 15 ? 'Selamat Siang' : ($h < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$totalProdi = $db->query("SELECT COUNT(*) t FROM prodi")->fetch()['t'];
$unggul = $db->query("SELECT COUNT(*) t FROM akreditasi WHERE tingkat='Prodi' AND peringkat IN ('Unggul','A')")->fetch()['t'];
$avg = $db->query("SELECT ROUND(AVG(capaian),1) a FROM evaluasi_diri WHERE tahun = YEAR(NOW())")->fetch()['a'] ?? 0;
$mayorOpen = $db->query("SELECT COUNT(*) t FROM temuan_audit WHERE kategori='Mayor' AND status_verifikasi != 'Diterima'")->fetch()['t'];

$akr = $db->query("SELECT peringkat, COUNT(*) j FROM akreditasi WHERE tingkat='Prodi' GROUP BY peringkat")->fetchAll();
$capTahun = $db->query("SELECT tahun, ROUND(AVG(capaian),1) a FROM evaluasi_diri GROUP BY tahun ORDER BY tahun")->fetchAll();

$simTitle = 'Dashboard Pimpinan';
$activeMenu = 'dashboard';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    .db-banner { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); border-radius: var(--radius-lg); padding: 36px 40px; color: #fff; position: relative; overflow: hidden; margin-bottom: 28px; box-shadow: var(--shadow-lg); }
    .db-banner::before { content: ''; position: absolute; width: 340px; height: 340px; border-radius: 50%; background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%); right: -80px; top: -120px; }
    .db-banner h2 { font-size: 26px; margin-bottom: 6px; position: relative; }
    .db-banner p { color: rgba(255,255,255,.78); position: relative; max-width: 640px; }
</style>

<div class="db-banner">
    <h2><?= $sapa ?>, <?= Security::e(Auth::user()['nama']) ?> 👋</h2>
    <p>Ringkasan eksekutif mutu institusi tersaji real-time untuk mendukung pengambilan keputusan strategis Anda.</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin-bottom:32px;">
    <div class="stat-card"><div class="stat-icon blue">🏫</div><div><h3 style="font-size:28px;"><?= $totalProdi ?></h3><p class="text-muted">Total Prodi</p></div></div>
    <div class="stat-card"><div class="stat-icon green">🏆</div><div><h3 style="font-size:28px;"><?= $unggul ?></h3><p class="text-muted">Unggul / A</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">📊</div><div><h3 style="font-size:28px;"><?= $avg ?? 0 ?>%</h3><p class="text-muted">Capaian <?= date('Y') ?></p></div></div>
    <div class="stat-card"><div class="stat-icon red">⚠️</div><div><h3 style="font-size:28px;"><?= $mayorOpen ?></h3><p class="text-muted">Mayor Terbuka</p></div></div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card"><h3 style="margin-bottom:20px;">📈 Tren Capaian Mutu per Tahun</h3><div style="height:300px;"><canvas id="cTren"></canvas></div></div>
    <div class="card"><h3 style="margin-bottom:20px;">🏆 Distribusi Akreditasi</h3><canvas id="cAkr"></canvas></div>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📑 Akses Cepat Kebijakan</h3>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="/sim/pimpinan/rtm.php" class="btn btn-primary">📑 Laporan RTM</a>
        <a href="/sim/pimpinan/monev.php" class="btn btn-primary">📈 Monitoring Capaian</a>
        <a href="/sim/pimpinan/akreditasi.php" class="btn btn-outline">🏆 Akreditasi</a>
        <a href="/sim/pimpinan/temuan.php" class="btn btn-outline">⚠️ Temuan Audit</a>
    </div>
</div>

<script>
new Chart(document.getElementById('cTren'), {
    type: 'line',
    data: { labels: <?= json_encode(array_column($capTahun, 'tahun')) ?: '["-"]' ?>,
            datasets: [{ label: 'Rata-rata Capaian (%)', data: <?= json_encode(array_column($capTahun, 'a')) ?: '[0]' ?>,
                         borderColor: '#C9A227', backgroundColor: 'rgba(201,162,39,.15)', fill: true, tension: .4, pointRadius: 5, pointBackgroundColor: '#0F3D5C' }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } }
});
new Chart(document.getElementById('cAkr'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_column($akr, 'peringkat')) ?: '["Belum ada"]' ?>,
            datasets: [{ data: <?= json_encode(array_column($akr, 'j')) ?: '[1]' ?>, backgroundColor: ['#10B981','#3B82F6','#F59E0B','#64748B'], borderWidth: 4, borderColor: '#fff' }] },
    options: { plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>