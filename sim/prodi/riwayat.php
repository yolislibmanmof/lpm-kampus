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
        
        // 🔔 Notifikasi ke Auditor
        $au = $db->prepare("SELECT pa.id_auditor FROM temuan_audit t JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas WHERE t.id_temuan = ?");
        $au->execute([(int)$_POST['id_temuan']]);
        $auditor = $au->fetch();
        if ($auditor) {
            Notifier::send((int)$auditor['id_auditor'], '🛠️ Tindakan Koreksi Dikirim', 'Prodi binaan Anda mengirimkan tindakan koreksi. Mohon lakukan verifikasi.', '/sim/auditor/verifikasi.php', '🛠️');
        }
        $pesan = '✅ Tindakan koreksi berhasil dikirim. Menunggu verifikasi dari auditor.';
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
    <div class="alert alert-danger">⚠️ Akun Anda belum terhubung ke Program Studi. Hubungi Admin LPM.</div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;
?>

<style>
    .audit-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 28px 32px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .audit-header::before {
        content: ''; position: absolute; width: 250px; height: 250px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -60px; top: -80px;
    }
    .audit-header h2 { font-size: 22px; margin: 0; position: relative; font-weight: 800; }
    
    .audit-card {
        background: #fff; border-radius: 16px; border: 2px solid var(--border);
        margin-bottom: 24px; overflow: hidden; transition: all 0.3s;
    }
    .audit-card:hover { border-color: rgba(201,162,39,.4); box-shadow: 0 8px 24px rgba(0,0,0,.06); }
    
    .audit-head {
        padding: 20px 24px; background: var(--bg-light);
        border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    }
    .audit-head h3 { font-size: 16px; margin: 0; color: var(--primary-dark); display: flex; align-items: center; gap: 10px; }
    
    .finding-item {
        padding: 20px 24px; border-bottom: 1px solid var(--border);
        transition: background 0.3s;
    }
    .finding-item:last-child { border-bottom: none; }
    .finding-item:hover { background: rgba(15,61,92,.02); }
    
    .finding-header {
        display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;
    }
    .finding-title {
        font-size: 15px; font-weight: 700; color: var(--text-dark);
        display: flex; align-items: center; gap: 10px;
    }
    .finding-desc {
        font-size: 14px; color: var(--text-muted); line-height: 1.6; margin: 0 0 16px 0;
        background: rgba(15,61,92,.03); padding: 12px 16px; border-radius: 8px; border-left: 3px solid var(--border);
    }
    
    .koreksi-box {
        background: linear-gradient(135deg, rgba(201,162,39,.05), rgba(15,61,92,.03));
        border: 1px solid rgba(201,162,39,.2); border-radius: 12px; padding: 16px; margin-top: 16px;
    }
    .koreksi-box label {
        display: block; font-size: 13px; font-weight: 700; color: var(--primary-dark); margin-bottom: 8px;
    }
    .koreksi-box textarea {
        width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px;
        font-size: 14px; font-family: inherit; resize: vertical; min-height: 80px;
        transition: all 0.3s; margin-bottom: 12px;
    }
    .koreksi-box textarea:focus {
        outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(201,162,39,.15);
    }
    
    .status-badge {
        padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-mayor { background: #FEE2E2; color: #991B1B; }
    .status-minor { background: #FEF3C7; color: #92400E; }
    .status-observasi { background: #DBEAFE; color: #1E40AF; }
    .status-baik { background: #D1FAE5; color: #065F46; }
    
    .status-verifikasi {
        padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;
    }
    .ver-menunggu { background: #FEF3C7; color: #92400E; }
    .ver-diterima { background: #D1FAE5; color: #065F46; }
    .ver-ditolak { background: #FEE2E2; color: #991B1B; }
</style>

<div class="audit-header">
    <h2>📜 Riwayat & Tindak Lanjut Audit Mutu Internal</h2>
</div>

<?php if ($pesan): ?>
    <div class="alert alert-success" style="margin-bottom:24px;"><?= Security::e($pesan) ?></div>
<?php endif; ?>

<?php if (empty($tugas)): ?>
    <div class="card" style="text-align:center;padding:60px 20px;">
        <div style="font-size:64px;margin-bottom:16px;opacity:0.5;">📋</div>
        <h3 style="color:var(--text-dark);margin-bottom:8px;">Belum Ada Riwayat Audit</h3>
        <p class="text-muted">Program Studi Anda belum pernah diaudit atau data belum tersedia.</p>
    </div>
<?php else: ?>
    <?php foreach ($tugas as $t):
        $stmt = $db->prepare("SELECT t.*, s.kode_standar, s.nama_standar FROM temuan_audit t LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar WHERE t.id_tugas = ? ORDER BY FIELD(t.kategori, 'Mayor', 'Minor', 'Observasi', 'Kondisi Baik')");
        $stmt->execute([$t['id_tugas']]);
        $temuanList = $stmt->fetchAll();
    ?>
    <div class="audit-card">
        <div class="audit-head">
            <h3>🔍 AMI Tahun <?= Security::e($t['tahun_ami']) ?></h3>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <span style="font-size:13px;color:var(--text-muted);">👤 <?= Security::e($t['auditor']) ?></span>
                <span class="badge badge-b"><?= Security::e($t['status']) ?></span>
                <span style="font-size:13px;color:var(--text-muted);">📅 <?= date('d M Y', strtotime($t['tanggal_audit'])) ?></span>
            </div>
        </div>

        <?php if (empty($temuanList)): ?>
            <div style="padding:32px;text-align:center;">
                <span style="font-size:32px;">🎉</span>
                <p class="text-muted" style="margin-top:12px;">Tidak ada temuan pada audit ini. Pertahankan kinerja excellent!</p>
            </div>
        <?php else: ?>
            <?php foreach ($temuanList as $tm): 
                $katClass = match($tm['kategori']) {
                    'Mayor' => 'status-mayor',
                    'Minor' => 'status-minor',
                    'Observasi' => 'status-observasi',
                    default => 'status-baik'
                };
                $verClass = match($tm['status_verifikasi']) {
                    'Menunggu' => 'ver-menunggu',
                    'Diterima' => 'ver-diterima',
                    'Ditolak' => 'ver-ditolak',
                    default => 'ver-menunggu'
                };
            ?>
            <div class="finding-item">
                <div class="finding-header">
                    <div class="finding-title">
                        <span class="status-badge <?= $katClass ?>"><?= Security::e($tm['kategori']) ?></span>
                        <span><?= Security::e($tm['kode_standar']) ?> · <?= Security::e($tm['nama_standar']) ?></span>
                    </div>
                    <span class="status-verifikasi <?= $verClass ?>">
                        <?= $tm['status_verifikasi'] === 'Diterima' ? '✅' : ($tm['status_verifikasi'] === 'Ditolak' ? '❌' : '⏳') ?> 
                        <?= Security::e($tm['status_verifikasi']) ?>
                    </span>
                </div>
                
                <p class="finding-desc"><?= nl2br(Security::e($tm['deskripsi_temuan'])) ?></p>
                
                <?php if (!empty($tm['tindakan_koreksi'])): ?>
                    <div style="background:rgba(16,185,129,.08);border-left:3px solid #10B981;padding:12px 16px;border-radius:8px;margin-bottom:12px;">
                        <strong style="font-size:13px;color:#065F46;display:block;margin-bottom:4px;">🛠️ Tindakan Koreksi Anda:</strong>
                        <p style="font-size:14px;color:#047857;margin:0;"><?= nl2br(Security::e($tm['tindakan_koreksi'])) ?></p>
                        <?php if ($tm['tanggal_koreksi']): ?>
                        <small style="color:#059669;font-size:11px;">Dikirim: <?= date('d M Y H:i', strtotime($tm['tanggal_koreksi'])) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($tm['status_verifikasi'] !== 'Diterima'): ?>
                <form method="POST" class="koreksi-box">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="koreksi">
                    <input type="hidden" name="id_temuan" value="<?= $tm['id_temuan'] ?>">
                    <label>📝 Form Tindakan Koreksi:</label>
                    <textarea name="tindakan_koreksi" placeholder="Jelaskan secara detail tindakan perbaikan yang telah atau akan dilakukan..." required><?= Security::e($tm['tindakan_koreksi']) ?></textarea>
                    <div style="display:flex;justify-content:flex-end;">
                        <button class="btn btn-gold" style="padding:10px 24px;font-weight:700;">📤 Kirim Koreksi</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>