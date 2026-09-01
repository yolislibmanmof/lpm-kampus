<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = '';

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

if ($prodiId && ($_POST['action'] ?? '') === 'koreksi') {
    Security::verifyCsrf();
    $chk = $db->prepare("SELECT pa.id_prodi FROM temuan_audit t JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas WHERE t.id_temuan = ?");
    $chk->execute([(int)$_POST['id_temuan']]);
    $row = $chk->fetch();
    if ($row && (int)$row['id_prodi'] === (int)$prodiId) {
        $db->prepare("UPDATE temuan_audit SET tindakan_koreksi = ?, tanggal_koreksi = NOW(), status_verifikasi = 'Menunggu' WHERE id_temuan = ?")
           ->execute([trim($_POST['tindakan_koreksi']), (int)$_POST['id_temuan']]);
        Logger::log('KOREKSI', 'Mengirim tindakan koreksi untuk temuan #' . (int)$_POST['id_temuan']);
        // 🔔 v3: beri tahu auditor
        $au = $db->prepare("SELECT pa.id_auditor FROM temuan_audit t JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas WHERE t.id_temuan = ?");
        $au->execute([(int)$_POST['id_temuan']]);
        $auditor = $au->fetch();
        if ($auditor) {
            Notifier::send((int)$auditor['id_auditor'], '🛠️ Tindakan Koreksi Dikirim', 'Prodi binaan Anda mengirimkan tindakan koreksi. Mohon lakukan verifikasi.', '/sim/auditor/verifikasi.php', '🛠️');
        }
        $pesan = '✅ Tindakan koreksi dikirim. Menunggu verifikasi auditor.';
    }
}

$tugas = [];
if ($prodiId) {
    $stmt = $db->prepare("SELECT pa.*, u.nama_lengkap AS auditor, j.tahun_ami FROM penugasan_audit pa LEFT JOIN users u ON pa.id_auditor = u.id_user LEFT JOIN jadwal_ami j ON pa.id_jadwal = j.id_jadwal WHERE pa.id_prodi = ? ORDER BY pa.tanggal_audit DESC");
    $stmt->execute([$prodiId]);
    $tugas = $stmt->fetchAll();
}

$simTitle = 'Riwayat Audit';
$activeMenu = 'riwayat';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun Anda belum terhubung ke Program Studi.</div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<?php if (empty($tugas)): ?>
    <div class="card" style="text-align:center;"><p class="text-muted">Belum ada riwayat audit untuk prodi Anda.</p></div>
<?php endif; ?>

<?php foreach ($tugas as $t):
    $stmt = $db->prepare("SELECT t.*, s.kode_standar, s.nama_standar FROM temuan_audit t LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar WHERE t.id_tugas = ? ORDER BY t.kategori");
    $stmt->execute([$t['id_tugas']]);
    $temuanList = $stmt->fetchAll();
?>
<div class="card" style="margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
        <h3>🔍 AMI <?= $t['tahun_ami'] ?> — Auditor: <?= Security::e($t['auditor']) ?></h3>
        <span class="badge badge-b"><?= Security::e($t['status']) ?> · <?= date('d M Y', strtotime($t['tanggal_audit'])) ?></span>
    </div>

    <?php if (empty($temuanList)): ?>
        <p class="text-muted">Tidak ada temuan pada audit ini. 🎉</p>
    <?php else: ?>
        <?php foreach ($temuanList as $tm): ?>
        <div style="padding:16px;background:var(--bg-light);border-radius:10px;margin-bottom:12px;border-left:4px solid <?= $tm['kategori']==='Mayor' ? 'var(--danger)' : ($tm['kategori']==='Minor' ? 'var(--warning)' : 'var(--success)') ?>;">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <strong><?= Security::e($tm['kode_standar']) ?> · <?= Security::e($tm['kategori']) ?></strong>
                <span class="badge <?= $tm['status_verifikasi']==='Diterima' ? 'badge-unggul' : 'badge-b' ?>">Verifikasi: <?= Security::e($tm['status_verifikasi']) ?></span>
            </div>
            <p style="font-size:14px;margin:8px 0;"><?= Security::e($tm['deskripsi_temuan']) ?></p>
            <?php if (!empty($tm['tindakan_koreksi'])): ?>
                <p style="font-size:13px;color:var(--info);">🛠️ Koreksi Anda: <?= Security::e($tm['tindakan_koreksi']) ?></p>
            <?php endif; ?>
            <?php if ($tm['status_verifikasi'] !== 'Diterima'): ?>
            <form method="POST" style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="koreksi">
                <input type="hidden" name="id_temuan" value="<?= $tm['id_temuan'] ?>">
                <input type="text" name="tindakan_koreksi" class="form-control" style="flex:1;min-width:250px;" placeholder="Jelaskan tindakan koreksi yang dilakukan..." value="<?= Security::e($tm['tindakan_koreksi']) ?>" required>
                <button class="btn btn-gold" style="padding:10px 20px;">📤 Kirim Koreksi</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>