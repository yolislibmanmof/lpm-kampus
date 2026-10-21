<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2, 3, 4]);
$db = Database::getInstance();

$list = $db->query("SELECT * FROM instrumen_tahun ORDER BY tahun DESC")->fetchAll();
$selected = (int)($_GET['id'] ?? 0);
if (!$selected) {
    foreach ($list as $l) { if ($l['status'] === 'Aktif') { $selected = $l['id_instrumen_tahun']; break; } }
    if (!$selected && $list) $selected = $list[0]['id_instrumen_tahun'];
}
$current = null;
foreach ($list as $l) if ($l['id_instrumen_tahun'] == $selected) $current = $l;

$grup = [];
$totalButir = 0;
if ($current) {
    $b = $db->prepare("SELECT b.*, s.kode_standar, s.nama_standar FROM instrumen_butir b JOIN standar_mutu s ON b.id_standar = s.id_standar WHERE b.id_instrumen_tahun = ? ORDER BY s.kode_standar, b.urutan, b.id_butir");
    $b->execute([$selected]);
    foreach ($b->fetchAll() as $x) {
        $grup[$x['kode_standar']][] = $x;
        $totalButir++;
    }
}

$simTitle = 'Instrumen AMI';
$activeMenu = 'instrumen';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    .instrumen-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 32px 36px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .instrumen-header::before {
        content: ''; position: absolute; width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -100px; top: -150px; animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .instrumen-header h2 { font-size: 24px; margin: 0 0 8px; position: relative; font-weight: 800; }
    .instrumen-header p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; margin: 0 0 20px 0; }
    
    .instrumen-stats {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 20px;
    }
    .instrumen-stat {
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px); border-radius: 12px;
        padding: 16px; text-align: center; border: 1px solid rgba(255,255,255,.15);
    }
    .instrumen-stat h4 { font-size: 28px; margin: 0 0 4px; font-weight: 800; color: var(--accent-light); }
    .instrumen-stat small { font-size: 12px; opacity: .8; text-transform: uppercase; letter-spacing: 1px; }

    .status-badge {
        display: inline-block; padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-aktif { background: rgba(16,185,129,.2); color: #6EE7B7; border: 1px solid #10B981; }
    .status-draft { background: rgba(245,158,11,.2); color: #FBBF24; border: 1px solid #F59E0B; }
    .status-arsip { background: rgba(107,114,128,.2); color: #9CA3AF; border: 1px solid #6B7280; }

    /* DIPERBAIKI: Gunakan CSS variables */
    .control-bar {
        background: var(--bg-card); border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,.05); border: 2px solid var(--border);
        display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
    }
    .control-left { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .control-right { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    
    .search-box {
        position: relative; flex: 1; min-width: 250px; max-width: 400px;
    }
    .search-box input {
        width: 100%; padding: 10px 16px 10px 40px; border: 2px solid var(--border);
        border-radius: 10px; font-size: 14px; transition: all 0.3s;
        background: var(--bg-card);
        color: var(--text-dark);
    }
    .search-box input:focus {
        outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(201,162,39,.15);
    }
    .search-box .ic {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        font-size: 16px; color: var(--text-muted);
    }

    /* DIPERBAIKI: Gunakan CSS variables */
    .quick-nav {
        position: sticky; top: 80px; z-index: 100;
        background: var(--bg-card); border-radius: 12px; padding: 16px; margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,.05); border: 2px solid var(--border);
        max-height: 300px; overflow-y: auto;
    }
    .quick-nav h4 {
        font-size: 13px; margin: 0 0 12px; color: var(--text-muted);
        text-transform: uppercase; letter-spacing: 1px; font-weight: 700;
    }
    .quick-nav-list {
        display: flex; flex-direction: column; gap: 8px;
    }
    .quick-nav-item {
        padding: 10px 14px; border-radius: 8px; background: var(--bg-light);
        text-decoration: none; color: var(--text-dark); font-size: 13px; font-weight: 600;
        transition: all 0.3s; display: flex; justify-content: space-between; align-items: center;
        border: 1px solid transparent;
    }
    .quick-nav-item:hover {
        background: rgba(201,162,39,.1); border-color: var(--accent); color: var(--accent);
        transform: translateX(4px);
    }
    .quick-nav-item .count {
        background: var(--primary); color: #fff; padding: 2px 8px; border-radius: 50px;
        font-size: 11px; font-weight: 800;
    }

    /* DIPERBAIKI: Gunakan CSS variables */
    .standar-card {
        background: var(--bg-card); border-radius: 16px; border: 2px solid var(--border);
        margin-bottom: 24px; overflow: hidden; transition: all 0.3s;
        scroll-margin-top: 100px;
    }
    .standar-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.08); }
    
    .standar-head {
        padding: 20px 24px; border-bottom: 2px solid var(--border);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    }
    .standar-head h3 {
        font-size: 18px; margin: 0; color: var(--text-dark);
        display: flex; align-items: center; gap: 12px;
    }
    .standar-kode {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff; padding: 6px 14px; border-radius: 8px; font-size: 14px; font-weight: 800;
    }
    .standar-count {
        background: var(--bg-light); padding: 6px 14px; border-radius: 50px;
        font-size: 13px; font-weight: 700; color: var(--text-muted);
    }

    .butir-table th {
        font-size: 12px; text-transform: uppercase; letter-spacing: 1px;
        color: var(--text-muted); font-weight: 700; background: var(--bg-light);
    }
    .butir-table td { vertical-align: top; padding: 16px 12px; border-color: var(--border); }
    .butir-nomor {
        width: 50px; text-align: center; font-weight: 800; color: var(--accent);
        font-size: 16px; vertical-align: middle;
    }
    .butir-text { font-size: 14px; line-height: 1.6; color: var(--text-dark); }
    .butir-meta { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
    .metode-badge {
        display: inline-block; padding: 4px 10px; border-radius: 6px;
        background: rgba(59,130,246,.1); color: #3B82F6; font-size: 11px; font-weight: 700;
    }

    /* DIPERBAIKI: Gunakan CSS variables */
    .empty-state {
        text-align: center; padding: 60px 20px; background: var(--bg-card);
        border-radius: 16px; border: 2px dashed var(--border);
    }
    .empty-state .ic { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
    .empty-state h3 { font-size: 20px; color: var(--text-dark); margin-bottom: 8px; }
    .empty-state p { color: var(--text-muted); font-size: 14px; }

    /* DARK MODE OVERRIDES - PASTIKAN SEMUA TERLINDUNGI */
    body.dark .control-bar {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .search-box input {
        background: #0D1526;
        border-color: var(--border);
        color: var(--text-dark);
    }
    body.dark .quick-nav {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .quick-nav-item {
        background: var(--bg-light);
        color: var(--text-dark);
    }
    body.dark .standar-card {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .standar-head {
        border-color: var(--border);
    }
    body.dark .standar-head h3 {
        color: var(--text-dark);
    }
    body.dark .butir-table th {
        background: rgba(255,255,255,0.05);
    }
    body.dark .butir-table td {
        border-color: var(--border);
    }
    body.dark .empty-state {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .empty-state h3 {
        color: var(--text-dark);
    }

    @media print {
        .no-print { display: none !important; }
        .standar-card { break-inside: avoid; page-break-inside: avoid; margin-bottom: 20px; }
        body { background: #fff; }
        .instrumen-header { background: #0F3D5C !important; -webkit-print-color-adjust: exact; }
    }
    @media (max-width: 992px) {
        .quick-nav { position: static; max-height: none; }
    }
</style>

<!-- Breadcrumb -->
<nav class="no-print" style="margin-bottom:20px;font-size:13px;color:var(--text-muted);">
    <a href="/sim/index.php" style="color:var(--primary);text-decoration:none;">🏠 Dashboard</a>
    <span style="margin:0 8px;">/</span>
    <span>Instrumen AMI</span>
</nav>

<div class="instrumen-header">
    <h2> Instrumen Audit Mutu Internal</h2>
    <p>Acuan resmi butir penilaian untuk siklus Audit Mutu Internal (AMI) yang terpilih.</p>
    
    <?php if ($current): ?>
    <div style="margin-bottom:20px;">
        <span class="status-badge <?= $current['status'] === 'Aktif' ? 'status-aktif' : ($current['status'] === 'Draft' ? 'status-draft' : 'status-arsip') ?>">
            <?= $current['status'] === 'Aktif' ? '🟢' : ($current['status'] === 'Draft' ? '🟡' : '⚪') ?>
            <?= Security::e($current['status']) ?>
        </span>
        <span style="margin-left:12px;font-size:14px;opacity:.9;">
            📅 Tahun <?= Security::e($current['tahun']) ?>
        </span>
    </div>
    
    <div class="instrumen-stats">
        <div class="instrumen-stat">
            <h4><?= count($grup) ?></h4>
            <small>Total Standar</small>
        </div>
        <div class="instrumen-stat">
            <h4><?= $totalButir ?></h4>
            <small>Total Butir</small>
        </div>
        <div class="instrumen-stat">
            <h4><?= Security::e($current['nama_siklus']) ?></h4>
            <small>Nama Siklus</small>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$current): ?>
    <div class="empty-state">
        <div class="ic">📋</div>
        <h3>Belum Ada Instrumen Tersedia</h3>
        <p>Silakan hubungi Admin LPM untuk membuat siklus instrumen AMI terlebih dahulu.</p>
    </div>
<?php else: ?>

<!-- Control Bar -->
<div class="control-bar no-print">
    <div class="control-left">
        <form method="GET" style="display:flex;gap:10px;align-items:center;">
            <label style="font-size:13px;font-weight:700;color:var(--text-dark);">Pilih Siklus:</label>
            <select name="id" class="form-control" style="width:auto;min-width:200px;" onchange="this.form.submit()">
                <?php foreach ($list as $l): ?>
                    <option value="<?= $l['id_instrumen_tahun'] ?>" <?= $selected == $l['id_instrumen_tahun'] ? 'selected' : '' ?>>
                        <?= Security::e($l['nama_siklus']) ?> (<?= $l['status'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="control-right">
        <div class="search-box">
            <span class="ic">🔍</span>
            <input type="text" id="searchButir" placeholder="Cari butir penilaian..." autocomplete="off">
        </div>
        <button class="btn btn-outline" onclick="window.print()" style="padding:10px 18px;font-size:13px;">🖨️ Cetak</button>
    </div>
</div>

<!-- Layout dengan Quick Nav -->
<div style="display:grid;grid-template-columns:280px 1fr;gap:24px;">
    <!-- Quick Navigation (Sticky) -->
    <div class="no-print">
        <div class="quick-nav">
            <h4>📑 Navigasi Cepat</h4>
            <div class="quick-nav-list">
                <?php foreach ($grup as $kode => $items): ?>
                <a href="#standar-<?= urlencode($kode) ?>" class="quick-nav-item">
                    <span>📌 <?= Security::e($kode) ?></span>
                    <span class="count"><?= count($items) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div>
        <?php foreach ($grup as $kode => $items): ?>
        <div class="standar-card" id="standar-<?= urlencode($kode) ?>">
            <div class="standar-head">
                <h3>
                    <span class="standar-kode"><?= Security::e($kode) ?></span>
                    <?= Security::e($items[0]['nama_standar']) ?>
                </h3>
                <span class="standar-count"><?= count($items) ?> Butir</span>
            </div>
            <div class="table-wrapper">
                <table class="butir-table">
                    <thead>
                        <tr>
                            <th style="width:60px;text-align:center;">No</th>
                            <th style="width:50%;">Butir Penilaian</th>
                            <th style="width:20%;">Metode Verifikasi</th>
                            <th style="width:30%;">Dokumen yang Diperlukan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $i => $b): ?>
                        <tr class="butir-row" data-search="<?= strtolower(Security::e($b['butir'] . ' ' . $b['metode'] . ' ' . $b['dokumen_diperlukan'])) ?>">
                            <td class="butir-nomor"><?= $i + 1 ?></td>
                            <td>
                                <div class="butir-text"><?= Security::e($b['butir']) ?></div>
                            </td>
                            <td>
                                <span class="metode-badge"><?= Security::e($b['metode'] ?: '—') ?></span>
                            </td>
                            <td>
                                <div class="butir-meta"><?= Security::e($b['dokumen_diperlukan'] ?: '—') ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    var searchInput = document.getElementById('searchButir');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var term = this.value.toLowerCase();
            var rows = document.querySelectorAll('.butir-row');
            var cards = document.querySelectorAll('.standar-card');
            
            rows.forEach(function(row) {
                var searchData = row.getAttribute('data-search');
                row.style.display = searchData.indexOf(term) > -1 ? '' : 'none';
            });
            
            // Hide cards that have no visible rows
            cards.forEach(function(card) {
                var visibleRows = card.querySelectorAll('.butir-row:not([style*="display: none"])');
                card.style.display = visibleRows.length > 0 ? '' : 'none';
            });
        });
    }

    // Smooth scroll for quick nav
    document.querySelectorAll('.quick-nav-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('href').substring(1);
            var target = document.getElementById(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>

<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>