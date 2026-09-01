<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([4]);
$db = Database::getInstance();
$auditorId = Auth::id();
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $chk = $db->prepare("SELECT pa.id_tugas FROM temuan_audit t JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas WHERE t.id_temuan = ? AND pa.id_auditor = ?");
    $chk->execute([(int)$_POST['id_temuan'], $auditorId]);
    if ($chk->fetch()) {
        $db->prepare("UPDATE temuan_audit SET status_verifikasi = ?, catatan_verifikasi = ? WHERE id_temuan = ?")
           ->execute([$_POST['keputusan'], trim($_POST['catatan_verifikasi'] ?? ''), (int)$_POST['id_temuan']]);
        Logger::log('UPDATE', 'Verifikasi temuan #' . (int)$_POST['id_temuan'] . ': ' . $_POST['keputusan']);
        // 🔔 v3: beri tahu Kaprodi
        $pp = $db->prepare("SELECT pa.id_prodi FROM temuan_audit t JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas WHERE t.id_temuan = ?");
        $pp->execute([(int)$_POST['id_temuan']]);
        $prodiId = $pp->fetch()['id_prodi'] ?? null;
        if ($prodiId) {
            $kk = $db->prepare("SELECT id_user FROM users WHERE id_role = 3 AND id_prodi = ? LIMIT 1");
            $kk->execute([$prodiId]);
            $k = $kk->fetch();
            if ($k) {
                $ok = $_POST['keputusan'] === 'Diterima';
                Notifier::send((int)$k['id_user'],
                    $ok ? '✅ Koreksi Diterima' : '❌ Koreksi Ditolak',
                    $ok ? 'Tindakan koreksi Anda diterima auditor. Temuan ditutup.' : 'Koreksi Anda ditolak. Mohon perbaiki dan kirim ulang.',
                    '/sim/prodi/riwayat.php', $ok ? '✅' : '❌');
            }
        }
        $pesan = '✅ Verifikasi tersimpan.';
    }
}

$stmt = $db->prepare("
    SELECT t.*, p.nama_prodi, s.kode_standar, s.nama_standar
    FROM temuan_audit t
    JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
    LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
    LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar
    WHERE pa.id_auditor = ? AND t.tindakan_koreksi IS NOT NULL AND t.tindakan_koreksi != ''
    ORDER BY (t.status_verifikasi = 'Menunggu') DESC, t.id_temuan DESC
");
$stmt->execute([$auditorId]);
$list = $stmt->fetchAll();

$simTitle = 'Verifikasi Koreksi';
$activeMenu = 'verifikasi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<?php if (empty($list)): ?>
    <div class="card" style="text-align:center;">
        <div style="font-size:48px;margin-bottom:12px;">✅</div>
        <p class="text-muted">Tidak ada tindakan koreksi yang menunggu verifikasi.</p>
    </div>
<?php endif; ?>

<?php foreach ($list as $t): ?>
<div class="card" style="margin-bottom:20px;border-left:4px solid <?= $t['status_verifikasi'] === 'Menunggu' ? 'var(--warning)' : ($t['status_verifikasi'] === 'Diterima' ? 'var(--success)' : 'var(--danger)') ?>;">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
        <strong><?= Security::e($t['nama_prodi']) ?> · <?= Security::e($t['kode_standar']) ?></strong>
        <span class="badge" style="<?= $t['kategori'] === 'Mayor' ? 'background:#FEE2E2;color:#991B1B' : 'background:#FEF3C7;color:#92400E' ?>"><?= Security::e($t['kategori']) ?></span>
    </div>
    <p style="font-size:14px;margin-bottom:8px;"><strong>Temuan:</strong> <?= Security::e($t['deskripsi_temuan']) ?></p>
    <p style="font-size:14px;color:var(--info);margin-bottom:16px;"><strong>🛠️ Koreksi dari Prodi:</strong> <?= Security::e($t['tindakan_koreksi']) ?></p>

    <?php if ($t['status_verifikasi'] === 'Menunggu'): ?>
    <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="id_temuan" value="<?= $t['id_temuan'] ?>">
        <input type="text" name="catatan_verifikasi" class="form-control" style="flex:1;min-width:250px;" placeholder="Catatan verifikasi (opsional)...">
        <button name="keputusan" value="Diterima" class="btn btn-primary" style="background:var(--success);">✅ Terima</button>
        <button name="keputusan" value="Ditolak" class="btn btn-primary" style="background:var(--danger);" onclick="return confirm('Tolak koreksi ini?');">❌ Tolak</button>
    </form>
    <?php else: ?>
        <p style="font-size:13px;"><strong>Hasil:</strong> <span class="badge" style="<?= $t['status_verifikasi'] === 'Diterima' ? 'background:#D1FAE5;color:#065F46' : 'background:#FEE2E2;color:#991B1B' ?>"><?= Security::e($t['status_verifikasi']) ?></span>
        <?php if ($t['catatan_verifikasi']): ?> — <?= Security::e($t['catatan_verifikasi']) ?><?php endif; ?></p>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>