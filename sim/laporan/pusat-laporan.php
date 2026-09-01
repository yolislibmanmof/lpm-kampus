<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

$tugas = $db->query("SELECT pa.*, p.nama_prodi, u.nama_lengkap AS auditor, j.tahun_ami
    FROM penugasan_audit pa
    LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
    LEFT JOIN users u ON pa.id_auditor = u.id_user
    LEFT JOIN jadwal_ami j ON pa.id_jadwal = j.id_jadwal
    ORDER BY pa.tanggal_audit DESC")->fetchAll();

$tahunList = $db->query("SELECT DISTINCT tahun FROM evaluasi_diri ORDER BY tahun DESC")->fetchAll();

$simTitle = 'Pusat Laporan PDF';
$activeMenu = 'puslap';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:24px;">
    <!-- Laporan AMI per penugasan -->
    <div class="card">
        <h3 style="margin-bottom:20px;">🔍 Laporan Audit Mutu Internal (per Penugasan)</h3>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Prodi</th><th>Auditor</th><th>Tahun</th><th>Status</th><th>Export</th></tr></thead>
                <tbody>
                <?php if (empty($tugas)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;" class="text-muted">Belum ada penugasan audit.</td></tr>
                <?php endif; ?>
                <?php foreach ($tugas as $t): ?>
                    <tr>
                        <td><strong><?= Security::e($t['nama_prodi']) ?></strong></td>
                        <td><?= Security::e($t['auditor']) ?></td>
                        <td><?= $t['tahun_ami'] ?></td>
                        <td><span class="badge <?= $t['status'] === 'Selesai' ? 'badge-unggul' : 'badge-b' ?>"><?= Security::e($t['status']) ?></span></td>
                        <td><a href="/sim/laporan/ami-print.php?tugas=<?= $t['id_tugas'] ?>" target="_blank" class="btn btn-gold" style="padding:6px 16px;font-size:12px;">📄 Export PDF</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Laporan RTM per tahun -->
    <div class="card">
        <h3 style="margin-bottom:20px;">📑 Laporan RTM (per Tahun)</h3>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <a href="/sim/laporan/rtm-print.php?tahun=<?= date('Y') ?>" target="_blank" class="btn btn-primary" style="justify-content:center;">📄 RTM <?= date('Y') ?></a>
            <?php foreach ($tahunList as $tl): if ($tl['tahun'] == date('Y')) continue; ?>
                <a href="/sim/laporan/rtm-print.php?tahun=<?= $tl['tahun'] ?>" target="_blank" class="btn btn-outline" style="justify-content:center;">📄 RTM <?= $tl['tahun'] ?></a>
            <?php endforeach; ?>
        </div>
        <p class="text-muted" style="font-size:13px;margin-top:16px;">💡 Dokumen terbuka di tab baru dengan format resmi — klik <strong>Save as PDF</strong> pada dialog cetak.</p>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>