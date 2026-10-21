<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

$kategori = $_GET['kategori'] ?? 'all';
$verif = $_GET['verif'] ?? 'all';

/* ===== HEATMAP DATA ===== */
$prodiHm = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$hmRows = $db->query("SELECT pa.id_prodi, t.kategori, COUNT(*) n FROM temuan_audit t JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas GROUP BY pa.id_prodi, t.kategori")->fetchAll();
$hmMap = [];
foreach ($hmRows as $r) $hmMap[$r['id_prodi']][$r['kategori']] = (int)$r['n'];

function heatColor($kat, $n) {
    if ($n === 0) return 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0';
    if ($kat === 'Mayor')  return $n >= 3 ? 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA' : 'background:#FEF3C7;color:#92400E;border:1px solid #FDE68A';
    if ($kat === 'Minor')  return $n >= 3 ? 'background:#FEF3C7;color:#92400E;border:1px solid #FDE68A' : 'background:#DBEAFE;color:#1E40AF;border:1px solid #BFDBFE';
    if ($kat === 'Observasi') return 'background:#F3F4F6;color:#374151;border:1px solid #E5E7EB';
    return 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0'; // Kondisi Baik
}

/* ===== Tabel filter ===== */
$q = "SELECT t.*, p.nama_prodi, s.kode_standar, u.nama_lengkap auditor FROM temuan_audit t
      JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
      LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
      LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar
      LEFT JOIN users u ON pa.id_auditor = u.id_user WHERE 1=1";
$params = [];
if ($kategori !== 'all') { $q .= " AND t.kategori = :k"; $params[':k'] = $kategori; }
if ($verif !== 'all') { $q .= " AND t.status_verifikasi = :v"; $params[':v'] = $verif; }
$q .= " ORDER BY FIELD(t.kategori, 'Mayor', 'Minor', 'Observasi', 'Kondisi Baik'), t.id_temuan DESC";
$stmt = $db->prepare($q);
$stmt->execute($params);
$list = $stmt->fetchAll();

$simTitle = 'Temuan Audit';
$activeMenu = 'temuan';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    /* ===== TEMUAN AUDIT DASHBOARD — PREMIUM UI ===== */
    .temuan-hero {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
        border-radius: var(--radius-lg); padding: 40px; color: #fff;
        margin-bottom: 32px; position: relative; overflow: hidden;
        box-shadow: var(--shadow-lg);
    }
    .temuan-hero::before {
        content: ''; position: absolute; width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -100px; top: -150px; animation: temuanPulse 5s ease-in-out infinite;
    }
    @keyframes temuanPulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 0.8; } }
    
    .temuan-hero-content { position: relative; z-index: 1; }
    .temuan-hero h2 { font-size: 28px; margin: 0 0 10px; font-weight: 800; letter-spacing: -0.02em; }
    .temuan-hero p { color: rgba(255,255,255,.85); font-size: 15px; margin: 0; max-width: 600px; }
    
    .temuan-hero-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 50px;
        background: rgba(201,162,39,.2); border: 1px solid rgba(201,162,39,.4);
        font-size: 11px; font-weight: 700; color: #E8C55A;
        margin-bottom: 16px; letter-spacing: 1px; text-transform: uppercase;
    }

    .heatmap-card {
        background: var(--bg-card); border-radius: 16px; padding: 28px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm); margin-bottom: 32px;
    }
    .heatmap-card h3 { font-size: 18px; margin: 0 0 8px; color: var(--text-dark); font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .heatmap-card > p { font-size: 13px; color: var(--text-muted); margin: 0 0 20px; }
    
    .heatmap-table th { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; text-align: center; }
    .heatmap-table td { vertical-align: middle; padding: 12px 16px; }
    .heatmap-table td:first-child { text-align: left; font-weight: 600; color: var(--text-dark); }
    .heatmap-table td:not(:first-child) { text-align: center; }
    
    .heat-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 48px; height: 32px; border-radius: 8px;
        font-size: 14px; font-weight: 800; transition: all 0.2s;
    }
    .heat-badge:hover { transform: scale(1.1); }

    .filter-bar {
        background: var(--bg-card); border-radius: 12px; padding: 16px 20px;
        border: 1px solid var(--border); margin-bottom: 20px;
        display: flex; gap: 16px; flex-wrap: wrap; align-items: center;
    }
    .filter-label { font-size: 13px; font-weight: 700; color: var(--text-dark); margin-right: 8px; }
    .filter-select {
        padding: 8px 14px; border-radius: 8px; border: 1.5px solid var(--border);
        background: var(--bg-light); color: var(--text-dark); font-size: 13px; font-weight: 600;
        cursor: pointer; transition: all 0.2s;
    }
    .filter-select:hover { border-color: var(--accent); }
    .filter-select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(201,162,39,.15); }

    .findings-table th { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; }
    .findings-table td { vertical-align: top; padding: 16px; }
    .badge-mayor { background: #FEE2E2; color: #991B1B; }
    .badge-minor { background: #FEF3C7; color: #92400E; }
    .badge-obs { background: #DBEAFE; color: #1E40AF; }
    .badge-baik { background: #D1FAE5; color: #065F46; }
    
    .status-diterima { background: #D1FAE5; color: #065F46; }
    .status-menunggu { background: #FEF3C7; color: #92400E; }
    .status-ditolak { background: #FEE2E2; color: #991B1B; }

    @media (max-width: 768px) {
        .temuan-hero { padding: 28px 20px; }
        .temuan-hero h2 { font-size: 22px; }
        .filter-bar { flex-direction: column; align-items: stretch; }
    }
</style>

<!-- HERO SECTION -->
<div class="temuan-hero">
    <div class="temuan-hero-content">
        <div class="temuan-hero-badge">
            <span>🗺️</span>
            <span>Peta Panas & Temuan Audit</span>
        </div>
        <h2>Monitoring Temuan Audit Mutu Internal</h2>
        <p>Satu pandangan komprehensif untuk membaca "prodi mana yang paling perlu perhatian" dan melacak status tindak lanjut temuan.</p>
    </div>
</div>

<!-- HEATMAP -->
<div class="heatmap-card">
    <h3>🗺️ Peta Panas Temuan per Program Studi</h3>
    <p>Merah = Prioritas pembinaan intensif. Hijau = Bersih dari temuan.</p>
    <div class="table-wrapper">
        <table class="heatmap-table">
            <thead>
                <tr>
                    <th style="text-align:left;">Program Studi</th>
                    <th>🔴 Mayor</th>
                    <th>🟡 Minor</th>
                    <th>🔵 Observasi</th>
                    <th>🟢 Kondisi Baik</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($prodiHm as $p): ?>
                <tr>
                    <td><?= Security::e($p['nama_prodi']) ?></td>
                    <?php foreach (['Mayor','Minor','Observasi','Kondisi Baik'] as $kat): 
                        $n = $hmMap[$p['id_prodi']][$kat] ?? 0; 
                    ?>
                        <td>
                            <span class="heat-badge" style="<?= heatColor($kat, $n) ?>"><?= $n ?></span>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:var(--text-muted);">
        <span>Legenda:</span>
        <span class="heat-badge" style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;min-width:32px;height:24px;font-size:11px;">0</span> Bersih
        <span class="heat-badge" style="background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;min-width:32px;height:24px;font-size:11px;">1-2</span> Minor
        <span class="heat-badge" style="background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;min-width:32px;height:24px;font-size:11px;">≥3</span> Mayor (Prioritas)
    </div>
</div>

<!-- FILTERS -->
<form method="GET" class="filter-bar">
    <span class="filter-label">Filter:</span>
    <select name="kategori" class="filter-select" onchange="this.form.submit()">
        <option value="all">Semua Kategori</option>
        <?php foreach (['Mayor','Minor','Observasi','Kondisi Baik'] as $k): ?>
            <option value="<?= $k ?>" <?= $kategori === $k ? 'selected' : '' ?>><?= $k ?></option>
        <?php endforeach; ?>
    </select>
    <select name="verif" class="filter-select" onchange="this.form.submit()">
        <option value="all">Semua Status Verifikasi</option>
        <?php foreach (['Menunggu','Diterima','Ditolak'] as $v): ?>
            <option value="<?= $v ?>" <?= $verif === $v ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($kategori !== 'all' || $verif !== 'all'): ?>
        <a href="?" class="btn btn-outline" style="padding:8px 16px;font-size:13px;margin-left:auto;">✕ Reset Filter</a>
    <?php endif; ?>
</form>

<!-- FINDINGS TABLE -->
<div class="chart-card">
    <h3>📋 Detail Temuan Audit</h3>
    <div class="table-wrapper">
        <table class="findings-table">
            <thead>
                <tr>
                    <th style="width:20%">Prodi & Auditor</th>
                    <th style="width:10%">Standar</th>
                    <th style="width:12%;text-align:center;">Kategori</th>
                    <th style="width:25%">Deskripsi Temuan</th>
                    <th style="width:25%">Tindakan Koreksi</th>
                    <th style="width:8%;text-align:center;">Verifikasi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;">
                        <div style="font-size:48px;margin-bottom:12px;opacity:0.4;">🔍</div>
                        <p class="text-muted">Tidak ada temuan yang sesuai dengan filter.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($list as $t): 
                    $katClass = match($t['kategori']) {
                        'Mayor' => 'badge-mayor',
                        'Minor' => 'badge-minor',
                        'Observasi' => 'badge-obs',
                        default => 'badge-baik'
                    };
                    $verifClass = match($t['status_verifikasi']) {
                        'Diterima' => 'status-diterima',
                        'Menunggu' => 'status-menunggu',
                        'Ditolak' => 'status-ditolak',
                        default => 'badge-baik'
                    };
                ?>
                <tr>
                    <td>
                        <strong style="color:var(--text-dark);font-size:14px;"><?= Security::e($t['nama_prodi']) ?></strong><br>
                        <small class="text-muted">Auditor: <?= Security::e($t['auditor']) ?></small>
                    </td>
                    <td>
                        <span style="font-weight:700;color:var(--primary);"><?= Security::e($t['kode_standar']) ?></span>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge <?= $katClass ?>" style="font-size:11px;padding:4px 10px;"><?= Security::e($t['kategori']) ?></span>
                    </td>
                    <td style="font-size:13px;color:var(--text-dark);line-height:1.5;">
                        <?= Security::e(mb_strimwidth($t['deskripsi_temuan'], 0, 120, '...')) ?>
                    </td>
                    <td style="font-size:13px;color:var(--text-muted);line-height:1.5;">
                        <?= $t['tindakan_koreksi'] ? Security::e(mb_strimwidth($t['tindakan_koreksi'], 0, 100, '...')) : '<span style="font-style:italic;">Belum ada koreksi</span>' ?>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge <?= $verifClass ?>" style="font-size:11px;padding:4px 10px;"><?= Security::e($t['status_verifikasi']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>