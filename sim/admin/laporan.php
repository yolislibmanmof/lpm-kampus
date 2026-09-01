<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]); // Admin & Pimpinan
$db = Database::getInstance();

$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Capaian per standar (tahun terpilih)
$eval = $db->prepare("
    SELECT s.kode_standar, s.nama_standar,
           AVG(e.capaian) AS capaian, AVG(e.target) AS target
    FROM standar_mutu s
    LEFT JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.tahun = ?
    GROUP BY s.id_standar, s.kode_standar, s.nama_standar
    ORDER BY s.kode_standar
");
$eval->execute([$tahun]);
$evaluasiList = $eval->fetchAll();

// Temuan per kategori
$temuan = $db->query("SELECT kategori, COUNT(*) jumlah FROM temuan_audit GROUP BY kategori")->fetchAll();

// Distribusi akreditasi
$akreditasi = $db->query("SELECT peringkat, COUNT(*) jumlah FROM akreditasi WHERE tingkat = 'Prodi' GROUP BY peringkat")->fetchAll();

// Status verifikasi koreksi
$koreksi = $db->query("SELECT status_verifikasi, COUNT(*) jumlah FROM temuan_audit GROUP BY status_verifikasi")->fetchAll();

// Daftar tahun untuk filter
$tahunList = $db->query("SELECT DISTINCT tahun FROM evaluasi_diri ORDER BY tahun DESC")->fetchAll();

// Ringkasan RTM
$totalTemuan   = array_sum(array_column($temuan, 'jumlah'));
$temuanMayor   = 0;
foreach ($temuan as $t) { if ($t['kategori'] === 'Mayor') $temuanMayor = (int)$t['jumlah']; }
$avgCapaian = 0;
$adaData = false;
foreach ($evaluasiList as $e) { if ($e['capaian'] !== null) { $avgCapaian += (float)$e['capaian']; $adaData = true; } }
$avgCapaian = $adaData ? round($avgCapaian / count(array_filter($evaluasiList, fn($x) => $x['capaian'] !== null)), 1) : 0;

// Data untuk chart
$labelStandar = array_map(fn($r) => $r['kode_standar'], $evaluasiList);
$arrCapaian   = array_map(fn($r) => round((float)$r['capaian'], 1), $evaluasiList);
$arrTarget    = array_map(fn($r) => round((float)$r['target'], 1), $evaluasiList);

$simTitle = 'Laporan Mutu & RTM';
$activeMenu = 'laporan';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
@media print {
    .sim-sidebar, .sim-topbar, .no-print { display: none !important; }
    .sim-content { margin-left: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd; }
}
</style>

<!-- Kop Laporan (muncul saat dicetak) -->
<div class="no-print" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;">
        <label class="form-label" style="margin:0;">Tahun:</label>
        <select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="<?= date('Y') ?>" <?= $tahun == date('Y') ? 'selected' : '' ?>><?= date('Y') ?></option>
            <?php foreach ($tahunList as $tl): ?>
                <option value="<?= $tl['tahun'] ?>" <?= $tahun == $tl['tahun'] ? 'selected' : '' ?>><?= $tl['tahun'] ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <button onclick="window.print()" class="btn btn-gold">🖨️ Cetak / Simpan PDF</button>
</div>

<!-- Kop resmi untuk cetakan -->
<div style="display:none;" class="print-only">
    <h2 style="text-align:center;">LAPORAN CAPAIAN MUTU — TAHUN <?= $tahun ?></h2>
    <p style="text-align:center;">Lembaga Penjaminan Mutu Kampus</p>
    <hr>
</div>

<!-- Ringkasan RTM -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px;margin-bottom:32px;">
    <div class="stat-card">
        <div class="stat-icon blue">📊</div>
        <div><h3 style="font-size:28px;"><?= $avgCapaian ?>%</h3><p class="text-muted">Rata-rata Capaian Standar</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">⚠️</div>
        <div><h3 style="font-size:28px;"><?= $temuanMayor ?></h3><p class="text-muted">Temuan Mayor</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold">🔍</div>
        <div><h3 style="font-size:28px;"><?= $totalTemuan ?></h3><p class="text-muted">Total Temuan Audit</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">🏆</div>
        <div><h3 style="font-size:28px;"><?= array_sum(array_column($akreditasi, 'jumlah')) ?></h3><p class="text-muted">Prodi Terakreditasi</p></div>
    </div>
</div>

<!-- Grafik Capaian Standar -->
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:20px;">📈 Capaian vs Target per Standar (<?= $tahun ?>)</h3>
    <div style="height:320px;"><canvas id="chartCapaian"></canvas></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card">
        <h3 style="margin-bottom:20px;">🔍 Kategori Temuan Audit</h3>
        <canvas id="chartTemuan"></canvas>
    </div>
    <div class="card">
        <h3 style="margin-bottom:20px;">🏆 Distribusi Akreditasi Prodi</h3>
        <canvas id="chartAkreditasi"></canvas>
    </div>
</div>

<!-- Tabel Rincian per Standar -->
<div class="card">
    <h3 style="margin-bottom:20px;">📋 Rincian Capaian per Standar — Bahan Rapat Tinjauan Manajemen (RTM)</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Standar</th><th>Capaian</th><th>Target</th><th>Progres</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($evaluasiList as $e): ?>
                <?php
                $cap = $e['capaian'] !== null ? round((float)$e['capaian'], 1) : null;
                $tgt = $e['target'] !== null ? round((float)$e['target'], 1) : 100;
                $status = $cap === null ? 'Belum Dinilai' : ($cap >= $tgt ? 'Tercapai' : 'Belum Tercapai');
                ?>
                <tr>
                    <td><strong><?= Security::e($e['kode_standar']) ?></strong><br><small class="text-muted"><?= Security::e($e['nama_standar']) ?></small></td>
                    <td><?= $cap === null ? '—' : $cap . '%' ?></td>
                    <td><?= $tgt ?>%</td>
                    <td style="min-width:150px;">
                        <div style="background:var(--border);border-radius:50px;height:8px;overflow:hidden;">
                            <div style="width:<?= $cap ?? 0 ?>%;height:100%;background:<?= ($cap ?? 0) >= $tgt ? 'var(--success)' : 'var(--warning)' ?>;"></div>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?= $status === 'Tercapai' ? 'badge-unggul' : ($status === 'Belum Tercapai' ? 'badge-b' : 'badge-baik') ?>">
                            <?= $status ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="margin-top:16px;font-size:13px;">
        💡 <strong>Rekomendasi RTM:</strong> Standar berstatus "Belum Tercapai" wajib disusun tindakan perbaikan
        dan menjadi prioritas pengawasan pada siklus PPEPP tahun berikutnya.
    </p>
</div>

<script>
// Chart Capaian vs Target
new Chart(document.getElementById('chartCapaian'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelStandar) ?>,
        datasets: [
            { label: 'Capaian (%)', data: <?= json_encode($arrCapaian) ?>, backgroundColor: '#0F3D5C', borderRadius: 6 },
            { label: 'Target (%)',  data: <?= json_encode($arrTarget) ?>,  backgroundColor: '#C9A227', borderRadius: 6 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});

// Chart Temuan
new Chart(document.getElementById('chartTemuan'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($temuan, 'kategori')) ?: '["Belum ada data"]' ?>,
        datasets: [{ data: <?= json_encode(array_column($temuan, 'jumlah')) ?: '[1]' ?>,
                     backgroundColor: ['#EF4444','#F59E0B','#3B82F6','#10B981'] }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

// Chart Akreditasi
new Chart(document.getElementById('chartAkreditasi'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($akreditasi, 'peringkat')) ?: '["Belum ada data"]' ?>,
        datasets: [{ data: <?= json_encode(array_column($akreditasi, 'jumlah')) ?: '[1]' ?>,
                     backgroundColor: ['#10B981','#3B82F6','#F59E0B','#64748B'] }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>