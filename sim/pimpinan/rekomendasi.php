<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

$simTitle = 'Rekomendasi RTM';
$activeMenu = 'rekomendasi';
$pesan = '';
$error = '';
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// ================= PROSES GENERATE REKOMENDASI =================
if (($_POST['action'] ?? '') === 'generate') {
    Security::verifyCsrf();
    $rec = [];

    /* 1) Celah EDP per standar */
    $gap = $db->prepare("SELECT * FROM (
        SELECT s.kode_standar, s.nama_standar, ROUND(AVG(e.capaian),1) c, ROUND(AVG(e.target),1) t
        FROM standar_mutu s JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.tahun = ?
        GROUP BY s.id_standar
    ) g WHERE g.c < g.t ORDER BY (g.t - g.c) DESC");
    $gap->execute([$tahun]);
    foreach ($gap->fetchAll() as $g) {
        $delta = $g['t'] - $g['c'];
        $rec[] = ['EDP', $delta >= 20 ? 'Tinggi' : 'Sedang',
            "Standar {$g['kode_standar']} ({$g['nama_standar']}) baru mencapai {$g['c']}% dari target {$g['t']}% (celah {$delta} poin). Rekomendasi: lakukan analisis akar masalah, tetapkan rencana perbaikan berjangka 6 bulan, dan alokasikan anggaran khusus pada siklus PPEPP berikutnya."];
    }

    /* 2) Temuan mayor AMI */
    $mayor = $db->query("SELECT p.nama_prodi, s.kode_standar, t.deskripsi_temuan, t.status_verifikasi
        FROM temuan_audit t
        JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
        LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
        LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar
        WHERE t.kategori = 'Mayor' AND t.status_verifikasi != 'Diterima'");
    foreach ($mayor->fetchAll() as $m) {
        $rec[] = ['AMI', 'Tinggi',
            "Temuan MAYOR pada {$m['nama_prodi']} (Standar {$m['kode_standar']}) belum tertutup: \"{$m['deskripsi_temuan']}\". Rekomendasi: wajibkan tindakan koreksi dalam 30 hari dan verifikasi ulang oleh auditor sebelum RTM berikutnya."];
    }

    /* 3) Tracer study */
    $tr = $db->query("SELECT COUNT(*) t, SUM(status_kerja IN ('Bekerja','Wirausaha')) w, AVG(masa_tunggu_bulan) mt FROM tracer_alumni")->fetch();
    if ($tr['t'] > 0) {
        $tkr = round($tr['w'] / $tr['t'] * 100, 1);
        if ($tkr < 60) $rec[] = ['Tracer', 'Tinggi', "Tingkat serapan lulusan hanya {$tkr}% (< 60%). Rekomendasi: aktifkan career center, perluas kemitraan industri/magang, dan gelar job fair tahunan."];
        $rel = $db->query("SELECT COUNT(*) c, SUM(kesesuaian_bidang IN ('Sangat Sesuai','Sesuai')) s FROM tracer_alumni WHERE status_kerja IN ('Bekerja','Wirausaha')")->fetch();
        if ($rel['c'] > 0 && round($rel['s'] / $rel['c'] * 100) < 60)
            $rec[] = ['Tracer', 'Sedang', 'Kesesuaian kerja dengan bidang studi di bawah 60%. Rekomendasi: tinjau keselarasan kurikulum (OBE) bersama dunia usaha.'];
        if ($tr['mt'] > 6) $rec[] = ['Tracer', 'Sedang', "Rata-rata masa tunggu kerja {$tr['mt']} bulan (> 6). Rekomendasi: program sertifikasi kompetensi & pembekalan karier sebelum wisuda."];
    }

    /* 4) Survei pengguna */
    $sp = $db->query("SELECT ROUND(AVG(aspek_etika),2) a1, ROUND(AVG(aspek_bahasa),2) a3, ROUND(AVG(aspek_teknologi),2) a4, ROUND(AVG(aspek_komunikasi),2) a5 FROM survei_pengguna")->fetch();
    if ($sp['a1'] !== null) {
        $aspek = ['Etika' => $sp['a1'], 'Bahasa Asing' => $sp['a3'], 'Teknologi' => $sp['a4'], 'Komunikasi' => $sp['a5']];
        $worst = array_keys($aspek, min($aspek))[0];
        if (min($aspek) < 3.5) $rec[] = ['Survei', 'Sedang', "Pengguna lulusan menilai aspek {$worst} paling rendah ({$aspek[$worst]}/5). Rekomendasi: sisipkan penguatan {$worst} ke dalam MBKM / mata kuliah terkait."];
    }

    /* 5) IKU di bawah target */
    $iku = $db->prepare("SELECT i.kode, i.nama, i.target, d.nilai FROM iku_indikator i JOIN iku_data d ON d.id_iku = i.id_iku AND d.tahun = ? WHERE d.nilai < i.target");
    $iku->execute([$tahun]);
    foreach ($iku->fetchAll() as $k) {
        $rec[] = ['IKU', $k['nilai'] < $k['target'] / 2 ? 'Tinggi' : 'Rendah',
            "{$k['kode']} ({$k['nama']}) baru {$k['nilai']} dari target {$k['target']}. Rekomendasi: tetapkan PIC khusus dan milestones triwulan."];
    }

    $db->prepare("DELETE FROM rekomendasi_ai WHERE tahun = ?")->execute([$tahun]);
    foreach ($rec as $r) {
        $db->prepare("INSERT INTO rekomendasi_ai (tahun, sumber, prioritas, teks) VALUES (?,?,?,?)")->execute([$tahun, $r[0], $r[1], $r[2]]);
    }
    Logger::log('UPDATE', 'Generate ' . count($rec) . ' rekomendasi AI tahun ' . $tahun);
    Notifier::sendRole(2, ' Rekomendasi AI Siap', count($rec) . ' draf rekomendasi dihasilkan untuk RTM ' . $tahun . '.', '/sim/pimpinan/rekomendasi.php', '🤖');
    $pesan = ' ' . count($rec) . ' rekomendasi dihasilkan dari 5 sumber data.';
}

// ================= PROSES HAPUS =================
if (($_POST['action'] ?? '') === 'delete') {
    Security::verifyCsrf();
    $db->prepare("DELETE FROM rekomendasi_ai WHERE id_rekomendasi = ?")->execute([(int)$_POST['id_rekomendasi']]);
    $pesan = '✅ Rekomendasi dihapus.';
}

// ================= LOAD DATA =================
$list = $db->prepare("SELECT * FROM rekomendasi_ai WHERE tahun = ? ORDER BY FIELD(prioritas,'Tinggi','Sedang','Rendah'), id_rekomendasi");
$list->execute([$tahun]);
$reks = $list->fetchAll();

// Statistik
$totalRec = count($reks);
$highPriority = count(array_filter($reks, fn($r) => $r['prioritas'] === 'Tinggi'));
$medPriority = count(array_filter($reks, fn($r) => $r['prioritas'] === 'Sedang'));
$lowPriority = count(array_filter($reks, fn($r) => $r['prioritas'] === 'Rendah'));

// Group by sumber
$groupedBySumber = [];
foreach ($reks as $r) {
    $groupedBySumber[$r['sumber']][] = $r;
}

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    /* ===== REKOMENDASI RTM — PREMIUM UI ===== */
    .rtm-hero {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
        border-radius: var(--radius-lg); padding: 40px; color: #fff;
        margin-bottom: 32px; position: relative; overflow: hidden;
        box-shadow: var(--shadow-lg);
    }
    .rtm-hero::before {
        content: ''; position: absolute; width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -100px; top: -150px; animation: rtmPulse 5s ease-in-out infinite;
    }
    .rtm-hero::after {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(26,90,130,.4), transparent 70%);
        left: -80px; bottom: -100px; animation: rtmPulse 7s ease-in-out infinite reverse;
    }
    @keyframes rtmPulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 0.8; } }
    
    .rtm-hero-content { position: relative; z-index: 1; }
    .rtm-hero h2 { font-size: 28px; margin: 0 0 10px; font-weight: 800; letter-spacing: -0.02em; }
    .rtm-hero p { color: rgba(255,255,255,.85); font-size: 15px; margin: 0; max-width: 600px; }
    .rtm-hero-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 50px;
        background: rgba(201,162,39,.2); border: 1px solid rgba(201,162,39,.4);
        font-size: 11px; font-weight: 700; color: #E8C55A;
        margin-bottom: 16px; letter-spacing: 1px; text-transform: uppercase;
    }

    .rtm-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .rtm-stat {
        background: var(--bg-card); border-radius: 16px; padding: 24px;
        border: 1px solid var(--border); box-shadow: var(--shadow-sm);
        display: flex; align-items: center; gap: 18px;
        transition: all 0.3s var(--ease-out);
    }
    .rtm-stat:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: rgba(201,162,39,.3); }
    .rtm-stat-ic {
        width: 56px; height: 56px; border-radius: 14px; display: grid; place-items: center; font-size: 24px;
        flex-shrink: 0;
    }
    .rtm-stat-ic.red { background: linear-gradient(135deg, rgba(239,68,68,.15), rgba(239,68,68,.05)); color: #EF4444; }
    .rtm-stat-ic.yellow { background: linear-gradient(135deg, rgba(245,158,11,.15), rgba(245,158,11,.05)); color: #F59E0B; }
    .rtm-stat-ic.green { background: linear-gradient(135deg, rgba(16,185,129,.15), rgba(16,185,129,.05)); color: #10B981; }
    .rtm-stat-ic.blue { background: linear-gradient(135deg, rgba(59,130,246,.15), rgba(59,130,246,.05)); color: #3B82F6; }
    .rtm-stat h4 { font-size: 28px; margin: 0; color: var(--text-dark); font-weight: 800; line-height: 1; }
    .rtm-stat small { font-size: 12px; color: var(--text-muted); font-weight: 600; margin-top: 4px; display: block; }

    .rtm-controls {
        background: var(--bg-card); border-radius: 16px; padding: 20px 24px;
        border: 1px solid var(--border); margin-bottom: 24px;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;
    }
    .rtm-controls-left { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .rtm-controls-right { display: flex; gap: 12px; flex-wrap: wrap; }

    .rtm-filter-group { display: flex; align-items: center; gap: 10px; }
    .rtm-filter-label { font-size: 13px; font-weight: 700; color: var(--text-dark); margin: 0; }
    .rtm-filter-select {
        padding: 8px 14px; border-radius: 10px; border: 1.5px solid var(--border);
        background: var(--bg-light); color: var(--text-dark); font-size: 13px; font-weight: 600;
        cursor: pointer; transition: all 0.2s;
    }
    .rtm-filter-select:hover { border-color: var(--accent); }
    .rtm-filter-select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(201,162,39,.15); }

    .rtm-btn {
        padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 700;
        border: none; cursor: pointer; transition: all 0.3s;
        display: inline-flex; align-items: center; gap: 8px;
    }
    .rtm-btn-gold {
        background: linear-gradient(135deg, var(--accent), var(--accent-light));
        color: var(--primary-dark); box-shadow: 0 4px 16px rgba(201,162,39,.3);
    }
    .rtm-btn-gold:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(201,162,39,.45); }
    .rtm-btn-outline {
        background: transparent; border: 1.5px solid var(--border); color: var(--text-dark);
    }
    .rtm-btn-outline:hover { border-color: var(--accent); color: var(--accent); }

    .rtm-source-group { margin-bottom: 28px; }
    .rtm-source-header {
        display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
        padding-bottom: 12px; border-bottom: 2px solid var(--border);
    }
    .rtm-source-ic {
        width: 40px; height: 40px; border-radius: 10px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff; display: grid; place-items: center; font-size: 18px;
    }
    .rtm-source-title { font-size: 16px; font-weight: 800; color: var(--text-dark); margin: 0; }
    .rtm-source-count {
        padding: 4px 12px; border-radius: 50px;
        background: rgba(201,162,39,.1); color: var(--accent);
        font-size: 12px; font-weight: 700;
    }

    .rec-card {
        background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border);
        margin-bottom: 12px; padding: 20px 24px; transition: all 0.3s var(--ease-out);
        display: flex; gap: 16px; align-items: flex-start;
        position: relative; overflow: hidden;
    }
    .rec-card::before {
        content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
        background: linear-gradient(180deg, var(--accent), var(--primary));
    }
    .rec-card:hover { box-shadow: var(--shadow-md); border-color: rgba(201,162,39,.3); transform: translateX(4px); }
    .rec-card.priority-tinggi::before { background: linear-gradient(180deg, #EF4444, #DC2626); }
    .rec-card.priority-sedang::before { background: linear-gradient(180deg, #F59E0B, #D97706); }
    .rec-card.priority-rendah::before { background: linear-gradient(180deg, #10B981, #059669); }

    .rec-content { flex: 1; }
    .rec-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px; }
    .rec-title { font-size: 15px; font-weight: 700; color: var(--text-dark); margin: 0; }
    .rec-badge {
        padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-tinggi { background: #FEE2E2; color: #991B1B; }
    .badge-sedang { background: #FEF3C7; color: #92400E; }
    .badge-rendah { background: #D1FAE5; color: #065F46; }
    
    .rec-source { font-size: 12px; color: var(--primary); font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .rec-detail { font-size: 14px; color: var(--text-muted); margin: 0 0 12px 0; line-height: 1.6; }
    .rec-usul {
        background: rgba(201,162,39,.05); border-left: 3px solid var(--accent);
        padding: 12px 16px; border-radius: 8px; font-size: 13.5px; color: var(--text-dark);
        line-height: 1.5;
    }
    .rec-usul strong { color: var(--primary); font-weight: 700; }

    .rec-actions { display: flex; gap: 8px; flex-shrink: 0; }
    .rec-action-btn {
        padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
        border: 1.5px solid var(--border); background: var(--bg-light);
        color: var(--text-dark); cursor: pointer; transition: all 0.2s;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .rec-action-btn:hover { border-color: var(--accent); color: var(--accent); }
    .rec-action-btn.danger:hover { border-color: var(--danger); color: var(--danger); }

    .rtm-empty {
        text-align: center; padding: 80px 40px;
        background: var(--bg-card); border-radius: 20px;
        border: 2px dashed var(--border);
    }
    .rtm-empty-ic { font-size: 80px; margin-bottom: 20px; opacity: 0.4; }
    .rtm-empty h3 { font-size: 20px; color: var(--text-dark); margin: 0 0 10px; }
    .rtm-empty p { color: var(--text-muted); font-size: 14px; margin: 0; }

    @media (max-width: 768px) {
        .rtm-hero { padding: 28px 20px; }
        .rtm-hero h2 { font-size: 22px; }
        .rtm-stats { grid-template-columns: 1fr 1fr; }
        .rtm-controls { flex-direction: column; align-items: stretch; }
        .rec-card { flex-direction: column; }
        .rec-actions { width: 100%; justify-content: flex-end; }
    }
</style>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<!-- HERO SECTION -->
<div class="rtm-hero">
    <div class="rtm-hero-content">
        <div class="rtm-hero-badge">
            <span>🤖</span>
            <span>AI-Powered Analysis</span>
        </div>
        <h2>Rekomendasi Strategis RTM <?= $tahun ?></h2>
        <p>Sistem menganalisis 5 sumber data (EDP, AMI, Tracer, Survei, IKU) untuk menghasilkan rekomendasi berbasis data yang siap dibahas dalam Rapat Tinjauan Manajemen.</p>
    </div>
</div>

<!-- STATISTICS -->
<div class="rtm-stats">
    <div class="rtm-stat">
        <div class="rtm-stat-ic blue">📊</div>
        <div>
            <h4><?= $totalRec ?></h4>
            <small>Total Rekomendasi</small>
        </div>
    </div>
    <div class="rtm-stat">
        <div class="rtm-stat-ic red">🔴</div>
        <div>
            <h4><?= $highPriority ?></h4>
            <small>Prioritas Tinggi</small>
        </div>
    </div>
    <div class="rtm-stat">
        <div class="rtm-stat-ic yellow"></div>
        <div>
            <h4><?= $medPriority ?></h4>
            <small>Prioritas Sedang</small>
        </div>
    </div>
    <div class="rtm-stat">
        <div class="rtm-stat-ic green"></div>
        <div>
            <h4><?= $lowPriority ?></h4>
            <small>Prioritas Rendah</small>
        </div>
    </div>
</div>

<!-- CONTROLS -->
<div class="rtm-controls">
    <div class="rtm-controls-left">
        <div class="rtm-filter-group">
            <label class="rtm-filter-label">Tahun RTM:</label>
            <form method="GET" style="display: inline;">
                <select name="tahun" class="rtm-filter-select" onchange="this.form.submit()">
                    <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
    </div>
    <div class="rtm-controls-right">
        <form method="POST" onsubmit="return confirm('Generate ulang? Rekomendasi lama tahun ini akan diganti.');">
            <input type="hidden" name="action" value="generate">
            <?= Security::csrfField() ?>
            <button type="submit" class="rtm-btn rtm-btn-gold">
                <span></span>
                <span>Generate Rekomendasi</span>
            </button>
        </form>
    </div>
</div>

<!-- CONTENT -->
<?php if (empty($reks)): ?>
    <div class="rtm-empty">
        <div class="rtm-empty-ic">🤖</div>
        <h3>Belum Ada Rekomendasi untuk <?= $tahun ?></h3>
        <p>Klik tombol <strong>"Generate Rekomendasi"</strong> di atas. Sistem akan menganalisis 5 sumber data secara instan dan menghasilkan draf rekomendasi strategis.</p>
    </div>
<?php else: ?>
    <?php foreach ($groupedBySumber as $sumber => $items): ?>
        <div class="rtm-source-group">
            <div class="rtm-source-header">
                <div class="rtm-source-ic">
                    <?php
                    $icons = ['EDP' => '', 'AMI' => '🔍', 'Tracer' => '🎓', 'Survei' => '💼', 'IKU' => '📈'];
                    echo $icons[$sumber] ?? '📊';
                    ?>
                </div>
                <h3 class="rtm-source-title"><?= Security::e($sumber) ?></h3>
                <span class="rtm-source-count"><?= count($items) ?> rekomendasi</span>
            </div>
            
            <?php foreach ($items as $r): ?>
                <div class="rec-card priority-<?= strtolower($r['prioritas']) ?>">
                    <div class="rec-content">
                        <div class="rec-head">
                            <span class="rec-badge badge-<?= strtolower($r['prioritas']) ?>">Prioritas <?= $r['prioritas'] ?></span>
                        </div>
                        <p class="rec-detail"><?= Security::e($r['teks']) ?></p>
                    </div>
                    <div class="rec-actions">
                        <button class="rec-action-btn" onclick="navigator.clipboard.writeText(<?= json_encode($r['teks']) ?>); this.innerHTML='✅ Tersalin'; setTimeout(() => this.innerHTML='📋 Salin', 2000);">
                            📋 Salin
                        </button>
                        <form method="POST" onsubmit="return confirm('Hapus rekomendasi ini?');" style="display: inline;">
                            <input type="hidden" name="id_rekomendasi" value="<?= $r['id_rekomendasi'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <?= Security::csrfField() ?>
                            <button type="submit" class="rec-action-btn danger">🗑️</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
// Auto-hide alert after 5 seconds
document.querySelectorAll('.alert').forEach(function(alert) {
    setTimeout(function() {
        alert.style.transition = 'opacity 0.5s, transform 0.5s';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(function() { alert.remove(); }, 500);
    }, 5000);
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>