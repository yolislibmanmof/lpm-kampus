<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([4]);
$db = Database::getInstance();

$stmt = $db->prepare("SELECT pa.*, p.nama_prodi, j.tahun_ami FROM penugasan_audit pa LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi LEFT JOIN jadwal_ami j ON pa.id_jadwal = j.id_jadwal WHERE pa.id_auditor = ? ORDER BY pa.tanggal_audit DESC");
$stmt->execute([Auth::id()]);
$tugas = $stmt->fetchAll();

$simTitle = 'Riwayat Audit';
$activeMenu = 'riwayat';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if (empty($tugas)): ?>
    <div class="card" style="text-align:center;"><p class="text-muted">Belum ada riwayat audit.</p></div>
<?php endif; ?>

<?php foreach ($tugas as $t):
    $c = $db->prepare("SELECT kategori, COUNT(*) jml FROM temuan_audit WHERE id_tugas = ? GROUP BY kategori");
    $c->execute([$t['id_tugas']]);
    $rekap = $c->fetchAll();
?>
<div class="card" style="margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
        <h3>🔍 <?= Security::e($t['nama_prodi']) ?> — AMI <?= $t['tahun_ami'] ?></h3>
        <span class="badge <?= $t['status'] === 'Selesai' ? 'badge-unggul' : 'badge-b' ?>"><?= Security::e($t['status']) ?></span>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
        <?php if (empty($rekap)): ?><span class="text-muted" style="font-size:13px;">Belum ada temuan dicatat.</span><?php endif; ?>
        <?php foreach ($rekap as $r): ?>
            <span class="badge" style="<?= match($r['kategori']) { 'Mayor' => 'background:#FEE2E2;color:#991B1B', 'Minor' => 'background:#FEF3C7;color:#92400E', 'Observasi' => 'background:#DBEAFE;color:#1E40AF', default => 'background:#D1FAE5;color:#065F46' } ?>"><?= $r['kategori'] ?>: <?= $r['jml'] ?></span>
        <?php endforeach; ?>
    </div>
    <a href="/sim/auditor/e-audit.php?tugas=<?= $t['id_tugas'] ?>" class="btn btn-outline" style="padding:6px 16px;font-size:12px;">📂 Buka Lembar Kerja</a>
</div>
<?php endforeach; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>