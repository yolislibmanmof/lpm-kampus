<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

$tugas = $db->query("SELECT pa.*, p.nama_prodi, f.nama_fakultas, u.nama_lengkap AS auditor, j.tahun_ami
    FROM penugasan_audit pa
    LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
    LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
    LEFT JOIN users u ON pa.id_auditor = u.id_user
    LEFT JOIN jadwal_ami j ON pa.id_jadwal = j.id_jadwal
    ORDER BY pa.tanggal_audit DESC")->fetchAll();

$tahunList = $db->query("SELECT DISTINCT tahun FROM evaluasi_diri ORDER BY tahun DESC")->fetchAll();
if (empty($tahunList)) {
    $tahunList = [['tahun' => date('Y')]];
}

$simTitle = 'Pusat Laporan PDF';
$activeMenu = 'puslap';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    .puslap-hero {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
        border-radius: var(--radius-lg); padding: 32px 36px; color: #fff;
        margin-bottom: 32px; position: relative; overflow: hidden;
        box-shadow: var(--shadow-lg);
    }
    .puslap-hero::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -80px; top: -100px; animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .puslap-hero h2 { font-size: 24px; margin: 0 0 8px; position: relative; font-weight: 800; }
    .puslap-hero p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; margin: 0; }

    .report-card {
        background: var(--bg-card); border-radius: 16px; padding: 28px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
        transition: all 0.3s var(--ease-out);
    }
    .report-card:hover { box-shadow: var(--shadow-md); border-color: rgba(201,162,39,.3); }
    .report-card h3 { font-size: 18px; margin: 0 0 20px; color: var(--text-dark); font-weight: 700; display: flex; align-items: center; gap: 10px; }

    .rtm-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;
    }
    .rtm-btn {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        padding: 16px; border-radius: 12px; text-decoration: none;
        font-weight: 700; font-size: 14px; transition: all 0.3s;
        border: 1.5px solid var(--border); background: var(--bg-light); color: var(--text-dark);
    }
    .rtm-btn:hover {
        border-color: var(--accent); background: rgba(201,162,39,.05); color: var(--accent);
        transform: translateY(-3px); box-shadow: 0 8px 20px rgba(201,162,39,.15);
    }
    .rtm-btn.active {
        background: linear-gradient(135deg, var(--accent), var(--accent-light));
        color: var(--primary-dark); border-color: transparent;
        box-shadow: 0 8px 20px rgba(201,162,39,.3);
    }
    .rtm-btn.active:hover {
        transform: translateY(-3px); box-shadow: 0 12px 24px rgba(201,162,39,.4);
    }

    .info-box {
        margin-top: 20px; padding: 16px;
        background: rgba(201,162,39,.06); border-left: 3px solid var(--accent);
        border-radius: 8px; font-size: 13px; color: var(--text-muted); line-height: 1.5;
    }
    
    @media (max-width: 992px) {
        .puslap-grid { grid-template-columns: 1fr !important; }
    }
</style>

<div class="puslap-hero">
    <h2>📑 Pusat Laporan & Ekspor PDF</h2>
    <p>Akses cepat ke dokumen resmi Audit Mutu Internal (AMI) dan Rapat Tinjauan Manajemen (RTM) yang siap cetak.</p>
</div>

<div class="puslap-grid" style="display:grid;grid-template-columns:1.6fr 1fr;gap:24px;">
    <!-- Laporan AMI per penugasan -->
    <div class="report-card">
        <h3>🔍 Laporan Audit Mutu Internal (AMI)</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:30%">Program Studi</th>
                        <th style="width:25%">Auditor</th>
                        <th style="width:15%;text-align:center;">Tahun</th>
                        <th style="width:15%;text-align:center;">Status</th>
                        <th style="width:15%;text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($tugas)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:40px;">
                            <div style="font-size:48px;margin-bottom:12px;opacity:0.4;">📂</div>
                            <p class="text-muted">Belum ada penugasan audit yang tercatat.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tugas as $t): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--text-dark);"><?= Security::e($t['nama_prodi']) ?></strong><br>
                                <small class="text-muted"><?= Security::e($t['nama_fakultas']) ?></small>
                            </td>
                            <td><?= Security::e($t['auditor']) ?></td>
                            <td style="text-align:center;font-weight:600;"><?= $t['tahun_ami'] ?></td>
                            <td style="text-align:center;">
                                <span class="badge <?= $t['status'] === 'Selesai' ? 'badge-unggul' : 'badge-b' ?>">
                                    <?= Security::e($t['status']) ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <a href="/sim/laporan/ami-print.php?tugas=<?= $t['id_tugas'] ?>" target="_blank" class="btn btn-gold" style="padding:8px 16px;font-size:12px;">
                                    📄 Export PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Laporan RTM per tahun -->
    <div class="report-card">
        <h3>📑 Laporan Rapat Tinjauan Manajemen (RTM)</h3>
        <div class="rtm-grid">
            <?php foreach ($tahunList as $tl): 
                $isCurrent = $tl['tahun'] == date('Y');
            ?>
                <a href="/sim/laporan/rtm-print.php?tahun=<?= $tl['tahun'] ?>" target="_blank" class="rtm-btn <?= $isCurrent ? 'active' : '' ?>">
                    <span>📄</span>
                    <span>RTM <?= $tl['tahun'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="info-box">
            💡 <strong>Tips:</strong> Dokumen akan terbuka di tab baru dengan format resmi. Gunakan fitur <strong>"Save as PDF"</strong> pada dialog cetak browser Anda untuk menyimpan salinan digital.
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>