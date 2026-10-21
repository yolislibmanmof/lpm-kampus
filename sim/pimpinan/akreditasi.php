<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

$data = $db->query("SELECT a.*, p.nama_prodi, f.nama_fakultas FROM akreditasi a
    LEFT JOIN prodi p ON a.id_prodi = p.id_prodi
    LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
    ORDER BY FIELD(a.peringkat, 'Unggul', 'A', 'Baik Sekali', 'B', 'Baik', 'C'), a.masa_berlaku ASC")->fetchAll();

// Statistik (DIPERBAIKI: Menggunakan function() standar untuk blok kode)
$totalAkred = count($data);
$unggul = count(array_filter($data, fn($a) => in_array($a['peringkat'], ['Unggul', 'A'])));
$baikSekali = count(array_filter($data, fn($a) => in_array($a['peringkat'], ['Baik Sekali', 'B'])));
$expiring = count(array_filter($data, function($a) {
    $sisa = strtotime($a['masa_berlaku']) - time();
    return $sisa >= 0 && $sisa < 365*86400;
}));
$expired = count(array_filter($data, fn($a) => strtotime($a['masa_berlaku']) < time()));

$simTitle = 'Status Akreditasi';
$activeMenu = 'akreditasi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    /* ===== AKREDITASI DASHBOARD — PREMIUM UI ===== */
    .akr-hero {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
        border-radius: var(--radius-lg); padding: 40px; color: #fff;
        margin-bottom: 32px; position: relative; overflow: hidden;
        box-shadow: var(--shadow-lg);
    }
    .akr-hero::before {
        content: ''; position: absolute; width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -100px; top: -150px; animation: akrPulse 5s ease-in-out infinite;
    }
    @keyframes akrPulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 0.8; } }
    
    .akr-hero-content { position: relative; z-index: 1; }
    .akr-hero h2 { font-size: 28px; margin: 0 0 10px; font-weight: 800; letter-spacing: -0.02em; }
    .akr-hero p { color: rgba(255,255,255,.85); font-size: 15px; margin: 0; max-width: 600px; }
    
    .akr-hero-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 50px;
        background: rgba(201,162,39,.2); border: 1px solid rgba(201,162,39,.4);
        font-size: 11px; font-weight: 700; color: #E8C55A;
        margin-bottom: 16px; letter-spacing: 1px; text-transform: uppercase;
    }

    .akr-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .akr-stat {
        background: var(--bg-card); border-radius: 16px; padding: 24px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
        display: flex; align-items: center; gap: 16px; transition: all 0.3s var(--ease-out);
    }
    .akr-stat:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: rgba(201,162,39,.3); }
    .akr-stat-ic {
        width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center; font-size: 24px; flex-shrink: 0;
    }
    .akr-stat-ic.gold { background: linear-gradient(135deg, rgba(201,162,39,.15), rgba(201,162,39,.05)); color: #C9A227; }
    .akr-stat-ic.blue { background: linear-gradient(135deg, rgba(59,130,246,.15), rgba(59,130,246,.05)); color: #3B82F6; }
    .akr-stat-ic.yellow { background: linear-gradient(135deg, rgba(245,158,11,.15), rgba(245,158,11,.05)); color: #F59E0B; }
    .akr-stat-ic.red { background: linear-gradient(135deg, rgba(239,68,68,.15), rgba(239,68,68,.05)); color: #EF4444; }
    .akr-stat h4 { font-size: 28px; margin: 0; color: var(--text-dark); font-weight: 800; line-height: 1; }
    .akr-stat small { font-size: 12px; color: var(--text-muted); font-weight: 600; margin-top: 4px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }

    .akr-legend {
        background: var(--bg-card); border-radius: 12px; padding: 16px 20px;
        border: 1px solid var(--border); margin-bottom: 24px;
        display: flex; gap: 20px; flex-wrap: wrap; align-items: center;
    }
    .akr-legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted); }
    .akr-legend-dot { width: 12px; height: 12px; border-radius: 50%; }
    .akr-legend-dot.green { background: #10B981; }
    .akr-legend-dot.yellow { background: #F59E0B; }
    .akr-legend-dot.red { background: #EF4444; }

    .akr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; }
    .akr-card {
        background: var(--bg-card); border-radius: 16px; padding: 24px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
        transition: all 0.3s var(--ease-out); position: relative; overflow: hidden;
    }
    .akr-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: rgba(201,162,39,.3); }
    .akr-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    }
    .akr-card.status-aman::before { background: linear-gradient(90deg, #10B981, #059669); }
    .akr-card.status-segera::before { background: linear-gradient(90deg, #F59E0B, #D97706); }
    .akr-card.status-expired::before { background: linear-gradient(90deg, #EF4444, #DC2626); }

    .akr-card-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    .akr-card-prodi { flex: 1; }
    .akr-card-prodi h4 { font-size: 16px; font-weight: 800; color: var(--text-dark); margin: 0 0 4px; }
    .akr-card-prodi small { font-size: 12px; color: var(--text-muted); }
    
    .akr-card-peringkat {
        padding: 8px 16px; border-radius: 10px; font-size: 14px; font-weight: 800;
        text-align: center; min-width: 70px;
    }
    .peringkat-unggul { background: linear-gradient(135deg, #10B981, #059669); color: #fff; }
    .peringkat-baik { background: linear-gradient(135deg, #3B82F6, #2563EB); color: #fff; }
    .peringkat-cukup { background: linear-gradient(135deg, #F59E0B, #D97706); color: #fff; }

    .akr-card-info { margin-bottom: 16px; }
    .akr-info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed var(--border); font-size: 13px; }
    .akr-info-row:last-child { border-bottom: none; }
    .akr-info-label { color: var(--text-muted); }
    .akr-info-value { font-weight: 700; color: var(--text-dark); }

    .akr-card-status {
        padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 700;
        display: flex; align-items: center; justify-content: space-between;
    }
    .status-aman { background: rgba(16,185,129,.1); color: #065F46; border: 1px solid rgba(16,185,129,.2); }
    .status-segera { background: rgba(245,158,11,.1); color: #92400E; border: 1px solid rgba(245,158,11,.2); }
    .status-expired { background: rgba(239,68,68,.1); color: #991B1B; border: 1px solid rgba(239,68,68,.2); }

    .akr-empty {
        text-align: center; padding: 80px 40px;
        background: var(--bg-card); border-radius: 20px;
        border: 2px dashed var(--border);
    }
    .akr-empty-ic { font-size: 80px; margin-bottom: 20px; opacity: 0.4; }
    .akr-empty h3 { font-size: 20px; color: var(--text-dark); margin: 0 0 10px; }
    .akr-empty p { color: var(--text-muted); font-size: 14px; margin: 0; }

    @media (max-width: 768px) {
        .akr-hero { padding: 28px 20px; }
        .akr-hero h2 { font-size: 22px; }
        .akr-stats { grid-template-columns: 1fr 1fr; }
        .akr-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- HERO SECTION -->
<div class="akr-hero">
    <div class="akr-hero-content">
        <div class="akr-hero-badge">
            <span>🏆</span>
            <span>Status Akreditasi Institusi</span>
        </div>
        <h2>Monitoring Akreditasi Program Studi</h2>
        <p>Pantau masa berlaku dan peringkat akreditasi seluruh program studi secara real-time. Sistem otomatis menandai akreditasi yang akan segera berakhir.</p>
    </div>
</div>

<!-- STATISTICS -->
<div class="akr-stats">
    <div class="akr-stat">
        <div class="akr-stat-ic gold">📊</div>
        <div>
            <h4><?= $totalAkred ?></h4>
            <small>Total Terakreditasi</small>
        </div>
    </div>
    <div class="akr-stat">
        <div class="akr-stat-ic blue">🥇</div>
        <div>
            <h4><?= $unggul ?></h4>
            <small>Unggul / A</small>
        </div>
    </div>
    <div class="akr-stat">
        <div class="akr-stat-ic yellow">⏰</div>
        <div>
            <h4><?= $expiring ?></h4>
            <small>Segera Berakhir</small>
        </div>
    </div>
    <div class="akr-stat">
        <div class="akr-stat-ic red">⚠️</div>
        <div>
            <h4><?= $expired ?></h4>
            <small>Kedaluwarsa</small>
        </div>
    </div>
</div>

<!-- LEGEND -->
<div class="akr-legend">
    <span style="font-weight:700;color:var(--text-dark);margin-right:8px;">Keterangan Status:</span>
    <div class="akr-legend-item">
        <div class="akr-legend-dot green"></div>
        <span>Aman (> 1 tahun)</span>
    </div>
    <div class="akr-legend-item">
        <div class="akr-legend-dot yellow"></div>
        <span>Segera Berakhir (< 1 tahun)</span>
    </div>
    <div class="akr-legend-item">
        <div class="akr-legend-dot red"></div>
        <span>Kedaluwarsa</span>
    </div>
</div>

<!-- AKREDITASI CARDS -->
<?php if (empty($data)): ?>
    <div class="akr-empty">
        <div class="akr-empty-ic">🏆</div>
        <h3>Belum Ada Data Akreditasi</h3>
        <p>Silakan tambahkan data akreditasi melalui menu Administrasi → Akreditasi.</p>
    </div>
<?php else: ?>
    <div class="akr-grid">
        <?php foreach ($data as $a):
            $sisa = strtotime($a['masa_berlaku']) - time();
            $statusClass = $sisa < 0 ? 'status-expired' : ($sisa < 365*86400 ? 'status-segera' : 'status-aman');
            $statusText = $sisa < 0 ? 'Kedaluwarsa' : ($sisa < 365*86400 ? 'Segera Berakhir' : 'Aman');
            $statusIcon = $sisa < 0 ? '⚠️' : ($sisa < 365*86400 ? '⏰' : '✅');
            
            $hariSisa = ceil($sisa / 86400);
            $hariText = $sisa < 0 ? abs($hariSisa) . ' hari lalu' : $hariSisa . ' hari lagi';
            
            $peringkatClass = in_array($a['peringkat'], ['Unggul', 'A']) ? 'peringkat-unggul' : 
                             (in_array($a['peringkat'], ['Baik Sekali', 'B']) ? 'peringkat-baik' : 'peringkat-cukup');
        ?>
        <div class="akr-card <?= $statusClass ?>">
            <div class="akr-card-head">
                <div class="akr-card-prodi">
                    <h4><?= Security::e($a['nama_prodi'] ?? 'INSTITUSI') ?></h4>
                    <small><?= Security::e($a['nama_fakultas'] ?? '') ?></small>
                </div>
                <div class="akr-card-peringkat <?= $peringkatClass ?>">
                    <?= Security::e($a['peringkat']) ?>
                </div>
            </div>
            
            <div class="akr-card-info">
                <div class="akr-info-row">
                    <span class="akr-info-label">Lembaga</span>
                    <span class="akr-info-value"><?= Security::e($a['lembaga']) ?></span>
                </div>
                <div class="akr-info-row">
                    <span class="akr-info-label">No. SK</span>
                    <span class="akr-info-value"><?= Security::e($a['no_sk'] ?: '—') ?></span>
                </div>
                <div class="akr-info-row">
                    <span class="akr-info-label">Berlaku Hingga</span>
                    <span class="akr-info-value"><?= date('d M Y', strtotime($a['masa_berlaku'])) ?></span>
                </div>
            </div>
            
            <div class="akr-card-status <?= $statusClass ?>">
                <span><?= $statusIcon ?> <?= $statusText ?></span>
                <span style="font-size:12px;"><?= $hariText ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>