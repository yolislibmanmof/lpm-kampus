<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

$tahun = (int)($_GET['tahun'] ?? date('Y'));

// 1. Standar belum tercapai (DIPERBAIKI: Menggunakan Subquery agar kompatibel dengan semua versi MySQL)
$belum = $db->prepare("SELECT * FROM (
    SELECT s.kode_standar, s.nama_standar, ROUND(AVG(e.capaian), 1) AS c, ROUND(AVG(e.target), 1) AS t
    FROM standar_mutu s 
    JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.tahun = ?
    GROUP BY s.id_standar, s.kode_standar, s.nama_standar
) AS subquery
WHERE c < t
ORDER BY (t - c) DESC");
$belum->execute([$tahun]);
$belumList = $belum->fetchAll();

// 2. Temuan mayor terbuka
$mayor = $db->prepare("SELECT t.*, p.nama_prodi, s.kode_standar 
    FROM temuan_audit t
    JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
    LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
    LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar
    WHERE t.kategori = 'Mayor' AND t.status_verifikasi != 'Diterima' 
    ORDER BY t.id_temuan DESC");
$mayor->execute();
$mayorList = $mayor->fetchAll();

// 3. Rekomendasi AI Tahun Ini (Prioritas Tinggi)
$reks = $db->prepare("SELECT * FROM rekomendasi_ai WHERE tahun = ? AND prioritas = 'Tinggi' ORDER BY id_rekomendasi LIMIT 5");
$reks->execute([$tahun]);
$reksList = $reks->fetchAll();

$simTitle = 'Laporan RTM';
$activeMenu = 'rtm';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
/* ===== TAMPILAN LAYAR (SCREEN) ===== */
.rtm-doc {
    background: var(--bg-card); border-radius: var(--radius-lg); padding: 48px;
    box-shadow: var(--shadow-md); max-width: 900px; margin: 0 auto;
    color: var(--text-dark); line-height: 1.6;
}
.rtm-kop {
    text-align: center; border-bottom: 3px double var(--primary); padding-bottom: 20px; margin-bottom: 32px;
}
.rtm-kop h1 { font-size: 22px; color: var(--primary); margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px; }
.rtm-kop h2 { font-size: 18px; color: var(--text-dark); margin: 0 0 8px; font-weight: 700; }
.rtm-kop p { font-size: 13px; color: var(--text-muted); margin: 0; }

.rtm-section { margin-bottom: 32px; page-break-inside: avoid; }
.rtm-section-title {
    font-size: 16px; font-weight: 800; color: var(--primary);
    border-left: 4px solid var(--accent); padding: 10px 12px; margin-bottom: 16px; 
    background: rgba(15,61,92,.04); border-radius: 0 8px 8px 0;
}

.rtm-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 16px; }
.rtm-table th {
    background: var(--bg-light); color: var(--primary); font-weight: 700; text-align: left;
    padding: 10px 12px; border: 1px solid var(--border); font-size: 12px; text-transform: uppercase;
}
.rtm-table td { padding: 10px 12px; border: 1px solid var(--border); vertical-align: top; }
.rtm-table tr:nth-child(even) { background: rgba(0,0,0,0.02); }

.rtm-alert {
    padding: 16px; border-radius: 8px; margin-bottom: 12px;
    border-left: 4px solid var(--danger); background: rgba(239,68,68,.08); color: #991B1B; font-size: 13.5px;
}
.rtm-alert strong { display: block; margin-bottom: 4px; color: #7F1D1D; }

.rtm-rek-item {
    padding: 12px 16px; background: rgba(201,162,39,.06); border-radius: 8px;
    margin-bottom: 10px; font-size: 13.5px; border-left: 3px solid var(--accent);
}

.rtm-sign {
    margin-top: 60px; display: flex; justify-content: space-between; page-break-inside: avoid;
}
.rtm-sign-box { text-align: center; width: 45%; }
.rtm-sign-box p { margin: 0 0 80px; font-size: 14px; font-weight: 600; }
.rtm-sign-box strong { display: block; border-bottom: 1px solid var(--primary); padding-bottom: 4px; }

/* ===== TAMPILAN CETAK (PRINT) ===== */
@media print {
    @page { margin: 20mm 20mm 25mm 20mm; size: A4; }
    body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sim-sidebar, .sim-topbar, .no-print, footer, script { display: none !important; }
    .sim-content { margin-left: 0 !important; padding: 0 !important; }
    .rtm-doc { box-shadow: none; padding: 0; max-width: 100%; background: #fff !important; color: #000 !important; }
    .rtm-section { page-break-inside: avoid; }
    .rtm-sign { page-break-inside: avoid; margin-top: 40px; }
    .rtm-table th { background: #E2E8F0 !important; color: #000 !important; }
    .rtm-alert { background: #FEF2F2 !important; color: #991B1B !important; border-left-color: #EF4444 !important; }
}

/* Dark Mode Support untuk Layar */
body.dark .rtm-doc { background: var(--bg-card); color: var(--text-dark); }
body.dark .rtm-kop h1 { color: var(--text-dark); }
body.dark .rtm-table tr:nth-child(even) { background: rgba(255,255,255,0.03); }
body.dark .rtm-table th { background: rgba(255,255,255,0.05); color: var(--text-dark); border-color: var(--border); }
body.dark .rtm-table td { border-color: var(--border); }
</style>

<div class="no-print" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap;max-width:900px;margin-left:auto;margin-right:auto;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;">
        <label class="form-label" style="margin:0;font-weight:700;">Tahun Laporan:</label>
        <select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
    <button onclick="window.print()" class="btn btn-gold">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="rtm-doc">
    <div class="rtm-kop">
        <h1>Lembaga Penjaminan Mutu</h1>
        <h2>BAHAN RAPAT TINJAUAN MANAJEMEN (RTM)</h2>
        <p>Siklus PPEPP Tahun Akademik <?= $tahun ?> — Dokumen Internal</p>
    </div>

    <!-- BAGIAN 1: STANDAR BELUM TERCAPAI -->
    <div class="rtm-section">
        <div class="rtm-section-title">1. Standar Mutu yang Belum Tercapai (Gap Analysis)</div>
        <?php if (empty($belumList)): ?>
            <p style="color:var(--success);font-weight:600;">🎉 Alhamdulillah, seluruh standar mutu telah mencapai atau melampaui target pada tahun <?= $tahun ?>.</p>
        <?php else: ?>
            <table class="rtm-table">
                <thead>
                    <tr>
                        <th style="width:15%">Kode</th>
                        <th style="width:35%">Nama Standar</th>
                        <th style="width:15%;text-align:center;">Capaian</th>
                        <th style="width:15%;text-align:center;">Target</th>
                        <th style="width:20%">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($belumList as $b): 
                    $gap = $b['t'] - $b['c'];
                    $status = $gap >= 20 ? '<span style="color:var(--danger);font-weight:700;">Kritis</span>' : '<span style="color:var(--warning);font-weight:700;">Perlu Perhatian</span>';
                ?>
                    <tr>
                        <td><strong><?= Security::e($b['kode_standar']) ?></strong></td>
                        <td><?= Security::e($b['nama_standar']) ?></td>
                        <td style="text-align:center;font-weight:700;color:var(--danger);"><?= $b['c'] ?>%</td>
                        <td style="text-align:center;"><?= $b['t'] ?>%</td>
                        <td><?= $status ?> (Gap: <?= $gap ?>%)</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- BAGIAN 2: TEMUAN MAYOR -->
    <div class="rtm-section">
        <div class="rtm-section-title">2. Temuan Audit Mutu Internal (AMI) Kategori Mayor</div>
        <?php if (empty($mayorList)): ?>
            <p style="color:var(--success);font-weight:600;">✅ Tidak ada temuan kategori Mayor yang terbuka / belum ditutup.</p>
        <?php else: ?>
            <?php foreach ($mayorList as $m): ?>
            <div class="rtm-alert">
                <strong><?= Security::e($m['nama_prodi'] ?: 'Institusi') ?> — Standar <?= Security::e($m['kode_standar']) ?></strong>
                <?= Security::e($m['deskripsi_temuan']) ?>
                <div style="margin-top:6px;font-size:12px;color:#7F1D1D;">Status koreksi: <em><?= Security::e($m['status_verifikasi']) ?></em></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- BAGIAN 3: REKOMENDASI UTAMA -->
    <div class="rtm-section">
        <div class="rtm-section-title">3. Rekomendasi Strategis Prioritas Tinggi</div>
        <?php if (empty($reksList)): ?>
            <p class="text-muted">Belum ada rekomendasi prioritas tinggi yang di-generate untuk tahun ini.</p>
        <?php else: ?>
            <?php foreach ($reksList as $rek): ?>
            <div class="rtm-rek-item">
                <strong>[<?= Security::e($rek['sumber']) ?>]</strong> <?= Security::e($rek['teks']) ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- TANDA TANGAN -->
    <div class="rtm-sign">
        <div class="rtm-sign-box">
            <p>Mengetahui,<br>Kepala Lembaga Penjaminan Mutu</p>
            <?php
            $kaprodi = $db->query("SELECT nama_lengkap FROM users WHERE id_role = 2 LIMIT 1")->fetch();
            $namaKaprodi = $kaprodi ? Security::e($kaprodi['nama_lengkap']) : '(...................................)';
            ?>
            <strong><?= $namaKaprodi ?></strong>
            <span style="font-size:12px;color:var(--text-muted);">NIP/NIDN. ....................</span>
        </div>
        <div class="rtm-sign-box">
            <p>Menyetujui,<br>Rektor / Wakil Rektor Bidang Akademik</p>
            <strong>(...................................)</strong>
            <span style="font-size:12px;color:var(--text-muted);">NIP. ....................</span>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>