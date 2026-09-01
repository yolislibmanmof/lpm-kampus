<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([4]);
$db = Database::getInstance();

$h = (int)date('H');
$sapa = $h < 10 ? 'Selamat Pagi' : ($h < 15 ? 'Selamat Siang' : ($h < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$stmt = $db->prepare("SELECT pa.*, p.nama_prodi, j.tahun_ami FROM penugasan_audit pa LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi LEFT JOIN jadwal_ami j ON pa.id_jadwal = j.id_jadwal WHERE pa.id_auditor = ? ORDER BY pa.tanggal_audit DESC");
$stmt->execute([Auth::id()]);
$tugasList = $stmt->fetchAll();

$stDist = $db->prepare("SELECT status, COUNT(*) c FROM penugasan_audit WHERE id_auditor = ? GROUP BY status");
$stDist->execute([Auth::id()]);
$stDist = $stDist->fetchAll();

$v = $db->prepare("SELECT COUNT(*) t FROM temuan_audit t JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas WHERE pa.id_auditor = ? AND t.status_verifikasi = 'Menunggu' AND t.tindakan_koreksi != ''");
$v->execute([Auth::id()]);
$verifCount = $v->fetch()['t'];

$simTitle = 'Dashboard Auditor';
$activeMenu = 'dashboard';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    .db-banner { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); border-radius: var(--radius-lg); padding: 36px 40px; color: #fff; position: relative; overflow: hidden; margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; box-shadow: var(--shadow-lg); }
    .db-banner::before { content: ''; position: absolute; width: 340px; height: 340px; border-radius: 50%; background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%); right: -80px; top: -120px; }
    .db-banner h2 { font-size: 26px; margin-bottom: 6px; position: relative; }
    .db-banner p { color: rgba(255,255,255,.78); position: relative; }
</style>

<div class="db-banner">
    <div>
        <h2><?= $sapa ?>, <?= Security::e(Auth::user()['nama']) ?> 👋</h2>
        <p><?= $verifCount > 0 ? "⏳ Ada $verifCount tindakan koreksi menunggu verifikasi Anda." : '✅ Tidak ada antrean verifikasi. Kerja bagus!' ?></p>
    </div>
    <a href="/sim/auditor/e-audit.php" class="btn btn-gold" style="position:relative;">🔍 Buka E-Audit</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin-bottom:32px;">
    <div class="stat-card"><div class="stat-icon blue">📋</div><div><h3 style="font-size:28px;"><?= count($tugasList) ?></h3><p class="text-muted">Total Penugasan</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">🔄</div><div><h3 style="font-size:28px;"><?= count(array_filter($tugasList, fn($t) => $t['status'] === 'Dikerjakan')) ?></h3><p class="text-muted">Dikerjakan</p></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><h3 style="font-size:28px;"><?= count(array_filter($tugasList, fn($t) => $t['status'] === 'Selesai')) ?></h3><p class="text-muted">Selesai</p></div></div>
    <div class="stat-card"><div class="stat-icon red">⏳</div><div><h3 style="font-size:28px;"><?= $verifCount ?></h3><p class="text-muted">Menunggu Verifikasi</p></div></div>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px;">
    <div class="card">
        <h3 style="margin-bottom:20px;">📋 Penugasan Audit Saya</h3>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Prodi</th><th>Tahun</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($tugasList)): ?><tr><td colspan="5" style="text-align:center;padding:30px;" class="text-muted">Belum ada penugasan.</td></tr><?php endif; ?>
                <?php foreach (array_slice($tugasList, 0, 6) as $t): ?>
                    <tr>
                        <td><strong><?= Security::e($t['nama_prodi']) ?></strong></td>
                        <td><?= $t['tahun_ami'] ?></td>
                        <td><?= date('d M Y', strtotime($t['tanggal_audit'])) ?></td>
                        <td><span class="badge <?= $t['status'] === 'Selesai' ? 'badge-unggul' : 'badge-b' ?>"><?= Security::e($t['status']) ?></span></td>
                        <td><a href="/sim/auditor/e-audit.php?tugas=<?= $t['id_tugas'] ?>" class="btn btn-outline" style="padding:5px 14px;font-size:12px;">🔍</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card"><h3 style="margin-bottom:20px;">📊 Status Penugasan</h3><canvas id="cSt"></canvas></div>
</div>

<script>
new Chart(document.getElementById('cSt'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_column($stDist, 'status')) ?: '["Belum ada"]' ?>,
            datasets: [{ data: <?= json_encode(array_column($stDist, 'c')) ?: '[1]' ?>, backgroundColor: ['#F59E0B','#3B82F6','#10B981'], borderWidth: 4, borderColor: '#fff' }] },
    options: { plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>