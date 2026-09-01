<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();

$h = (int)date('H');
$sapa = $h < 10 ? 'Selamat Pagi' : ($h < 15 ? 'Selamat Siang' : ($h < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'Dashboard Kaprodi';
$activeMenu = 'dashboard';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun Anda belum terhubung ke Program Studi. Hubungi Admin LPM.</div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

$namaProdi = $db->prepare("SELECT nama_prodi FROM prodi WHERE id_prodi = ?");
$namaProdi->execute([$prodiId]);
$namaProdi = $namaProdi->fetch()['nama_prodi'];

$edp = $db->prepare("SELECT COUNT(*) total, SUM(CASE WHEN status='Terkunci' THEN 1 ELSE 0 END) selesai FROM evaluasi_diri WHERE id_prodi=? AND tahun=?");
$edp->execute([$prodiId, date('Y')]);
$edp = $edp->fetch();
$progres = $edp['total'] > 0 ? round($edp['selesai'] / $edp['total'] * 100) : 0;

$borangCount = $db->prepare("SELECT COUNT(*) t FROM borang_akreditasi WHERE id_prodi=?");
$borangCount->execute([$prodiId]);
$borangCount = $borangCount->fetch()['t'];

$capaian = $db->prepare("SELECT s.kode_standar, e.capaian, e.target FROM standar_mutu s LEFT JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.id_prodi = ? AND e.tahun = ? ORDER BY s.kode_standar");
$capaian->execute([$prodiId, date('Y')]);
$capaianList = $capaian->fetchAll();

$temuan = $db->prepare("SELECT t.*, s.nama_standar FROM temuan_audit t LEFT JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar WHERE pa.id_prodi = ? ORDER BY t.id_temuan DESC LIMIT 4");
$temuan->execute([$prodiId]);
$temuanList = $temuan->fetchAll();
?>

<style>
    .db-banner { background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: var(--radius-lg); padding: 36px 40px; color: #fff; position: relative; overflow: hidden; margin-bottom: 28px; box-shadow: var(--shadow-lg); }
    .db-banner::before { content: ''; position: absolute; width: 340px; height: 340px; border-radius: 50%; background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%); right: -80px; top: -120px; }
    .db-banner h2 { font-size: 26px; margin-bottom: 6px; position: relative; }
    .db-banner p { color: rgba(255,255,255,.78); position: relative; }
    .db-prog { margin-top: 22px; background: rgba(255,255,255,.2); border-radius: 50px; height: 12px; overflow: hidden; position: relative; }
    .db-prog > div { height: 100%; background: linear-gradient(90deg, var(--accent), var(--accent-light)); border-radius: 50px; transition: width 1s var(--ease-out); }
</style>

<div class="db-banner">
    <h2><?= $sapa ?>, <?= Security::e(Auth::user()['nama']) ?> 👋</h2>
    <p>Program Studi: <strong style="color:var(--accent-light);"><?= Security::e($namaProdi) ?></strong> — Progres EDP <?= date('Y') ?>: <?= $progres ?>%</p>
    <div class="db-prog"><div style="width:<?= $progres ?>%;"></div></div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin-bottom:32px;">
    <div class="stat-card"><div class="stat-icon blue">📊</div><div><h3 style="font-size:28px;"><?= $edp['total'] ?? 0 ?></h3><p class="text-muted">Standar Dievaluasi</p></div></div>
    <div class="stat-card"><div class="stat-icon green">☁️</div><div><h3 style="font-size:28px;"><?= $borangCount ?></h3><p class="text-muted">File Borang</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">🔍</div><div><h3 style="font-size:28px;"><?= count($temuanList) ?></h3><p class="text-muted">Temuan Audit</p></div></div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;">
    <div class="card"><h3 style="margin-bottom:20px;">📊 Capaian per Standar (<?= date('Y') ?>)</h3><div style="height:300px;"><canvas id="cCap"></canvas></div></div>
    <div class="card">
        <h3 style="margin-bottom:20px;">⚠️ Temuan Terbaru</h3>
        <?php if (empty($temuanList)): ?><p class="text-muted">Belum ada temuan. Pertahankan! 🎉</p>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($temuanList as $t): ?>
                <div style="padding:14px;background:var(--bg-light);border-radius:12px;border-left:4px solid <?= $t['kategori']==='Mayor' ? 'var(--danger)' : 'var(--warning)' ?>;">
                    <span class="badge <?= $t['kategori']==='Mayor' ? 'badge-unggul' : 'badge-b' ?>" style="<?= $t['kategori']==='Mayor' ? 'background:#FEE2E2;color:#991B1B' : '' ?>"><?= Security::e($t['kategori']) ?></span>
                    <p style="font-size:13px;margin-top:8px;color:var(--text-muted);"><?= Security::e(mb_strimwidth($t['deskripsi_temuan'], 0, 90, '...')) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="/sim/prodi/riwayat.php" class="btn btn-outline" style="margin-top:16px;">Riwayat Lengkap →</a>
        <?php endif; ?>
    </div>
</div>

<script>
new Chart(document.getElementById('cCap'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($capaianList, 'kode_standar')) ?>,
            datasets: [
                { label: 'Capaian', data: <?= json_encode(array_map(fn($r) => $r['capaian'] !== null ? round((float)$r['capaian'],1) : 0, $capaianList)) ?>, backgroundColor: '#0F3D5C', borderRadius: 8 },
                { label: 'Target', data: <?= json_encode(array_map(fn($r) => round((float)($r['target'] ?? 100),1), $capaianList)) ?>, backgroundColor: '#C9A227', borderRadius: 8 }
            ] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>