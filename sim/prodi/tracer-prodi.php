<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'Tracer Study Prodi';
$activeMenu = 'tracerprodi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">️ Akun Anda belum terhubung ke Program Studi. Hubungi Admin LPM.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tracer_prodi_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBFNama;NIM;Tahun Lulus;Status;Instansi;Jabatan;Masa Tunggu (bln);Kesesuaian;Kisaran Gaji;No WA;Siap Wawancara\n";
    $st = $db->prepare("SELECT * FROM tracer_alumni WHERE id_prodi = ? ORDER BY created_at DESC");
    $st->execute([$prodiId]);
    foreach ($st->fetchAll() as $r) {
        echo implode(';', array_map(fn($v) => str_replace([';', "\n", "\r"], [',', ' ', ''], (string)$v), [
            $r['nama'], $r['nim'], $r['tahun_lulus'], $r['status_kerja'], $r['nama_instansi'], $r['jabatan'],
            $r['masa_tunggu_bulan'], $r['kesesuaian_bidang'], $r['kisaran_gaji'], $r['no_wa'], $r['siap_wawancara'] ? 'Ya' : 'Tidak'
        ])) . "\n";
    }
    exit;
}

// Statistik
$s1 = $db->prepare("SELECT COUNT(*) t FROM tracer_alumni WHERE id_prodi = ?"); $s1->execute([$prodiId]); $total = (int)$s1->fetch()['t'];
$s2 = $db->prepare("SELECT COUNT(*) t FROM tracer_alumni WHERE id_prodi = ? AND status_kerja IN ('Bekerja','Wirausaha')"); $s2->execute([$prodiId]); $worked = (int)$s2->fetch()['t'];
$s3 = $db->prepare("SELECT ROUND(AVG(masa_tunggu_bulan),1) a FROM tracer_alumni WHERE id_prodi = ? AND status_kerja IN ('Bekerja','Wirausaha')"); $s3->execute([$prodiId]); $avgT = $s3->fetch()['a'] ?? 0;
$tkr = $total > 0 ? round($worked / $total * 100) : 0;

// Chart Data
$chartStmt = $db->prepare("SELECT status_kerja, COUNT(*) as c FROM tracer_alumni WHERE id_prodi = ? GROUP BY status_kerja");
$chartStmt->execute([$prodiId]);
$chartData = $chartStmt->fetchAll();
$chartLabels = array_map(fn($r) => $r['status_kerja'] ?: 'Belum Diisi', $chartData);
$chartValues = array_map('intval', array_column($chartData, 'c'));

// Lists
$list = $db->prepare("SELECT * FROM tracer_alumni WHERE id_prodi = ? ORDER BY created_at DESC LIMIT 200");
$list->execute([$prodiId]); $list = $list->fetchAll();

$siap = $db->prepare("SELECT * FROM responden_akreditasi WHERE id_prodi = ? ORDER BY tipe, nama");
$siap->execute([$prodiId]); $siap = $siap->fetchAll();
?>

<style>
    .tracer-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 32px 36px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .tracer-header::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -80px; top: -100px; animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .tracer-header h2 { font-size: 24px; margin-bottom: 8px; position: relative; font-weight: 800; }
    .tracer-header p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; }
    
    .stat-card { transition: transform 0.3s, box-shadow 0.3s; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,.1); }
    
    /* DIPERBAIKI: Gunakan CSS variables */
    .search-box {
        background: var(--bg-card);
        border-radius: 12px; padding: 16px 20px;
        margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,.05);
        display: flex; gap: 12px; align-items: center;
        border: 2px solid var(--border);
    }
    .search-box input {
        flex: 1; padding: 10px 16px; border: 2px solid var(--border);
        border-radius: 8px; font-size: 14px; transition: all 0.3s;
        background: var(--bg-card);
        color: var(--text-dark);
    }
    .search-box input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(201,162,39,.15); }
    
    /* DIPERBAIKI: Gunakan CSS variables */
    .contact-card {
        padding: 16px; background: var(--bg-card); border-radius: 12px;
        display: flex; justify-content: space-between; align-items: center;
        gap: 12px; transition: all 0.3s; border: 1px solid var(--border);
    }
    .contact-card:hover { border-color: var(--accent); box-shadow: 0 4px 12px rgba(0,0,0,.05); }
    .contact-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg, var(--accent), var(--accent-light));
        color: var(--primary-dark); display: grid; place-items: center;
        font-weight: 800; font-size: 14px; flex-shrink: 0;
    }
    .contact-info strong { display: block; font-size: 14px; color: var(--text-dark); }
    .contact-info small { color: var(--text-muted); font-size: 12px; }
    
    .wa-btn {
        padding: 8px 14px; border-radius: 8px; background: #25D366; color: #fff;
        text-decoration: none; font-size: 12px; font-weight: 700;
        display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s;
    }
    .wa-btn:hover { background: #128C7E; transform: translateY(-2px); }

    /* DARK MODE OVERRIDES */
    body.dark .search-box {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .search-box input {
        background: #0D1526;
        border-color: var(--border);
        color: var(--text-dark);
    }
    body.dark .contact-card {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .contact-card:hover {
        background: #0D1526;
    }
</style>

<div class="tracer-header">
    <h2>🎓 Tracer Study Program Studi</h2>
    <p>Pantau keterlacakan, penyerapan kerja, dan kesiapan alumni untuk wawancara asesor akreditasi.</p>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <h3 style="font-size:18px;color:var(--text-dark);margin:0;"> Dashboard Kinerja Alumni</h3>
    <a href="?export=1" class="btn btn-outline" style="padding:8px 16px;font-size:13px;">📤 Export Data CSV</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:24px;margin-bottom:32px;">
    <div class="stat-card"><div class="stat-icon blue">🎓</div><div><h3 style="font-size:28px;"><?= $total ?></h3><p class="text-muted">Total Responden</p></div></div>
    <div class="stat-card"><div class="stat-icon green">💼</div><div><h3 style="font-size:28px;"><?= $tkr ?>%</h3><p class="text-muted">Terserap Kerja</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">⏱️</div><div><h3 style="font-size:28px;"><?= $avgT ?: '—' ?></h3><p class="text-muted">Rata-rata Tunggu (bln)</p></div></div>
    <div class="stat-card"><div class="stat-icon red"></div><div><h3 style="font-size:28px;"><?= count($siap) ?></h3><p class="text-muted">Siap Wawancara</p></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">📈 Distribusi Status Kerja</h3>
        <div style="height:280px;display:flex;justify-content:center;">
            <canvas id="cStatus"></canvas>
        </div>
    </div>
    <div class="card">
        <h3 style="margin-bottom:16px;">🙋 Siap Wawancara Asesor</h3>
        <div style="display:flex;flex-direction:column;gap:10px;max-height:320px;overflow-y:auto;padding-right:8px;">
            <?php if (empty($siap)): ?>
                <p class="text-muted" style="text-align:center;padding:20px;">Belum ada responden terdaftar dari prodi Anda.</p>
            <?php else: ?>
                <?php foreach ($siap as $s): 
                    $initials = strtoupper(substr($s['nama'], 0, 1) . (strpos($s['nama'], ' ') ? substr($s['nama'], strpos($s['nama'], ' ')+1, 1) : ''));
                ?>
                <div class="contact-card">
                    <div style="display:flex;gap:12px;align-items:center;flex:1;min-width:0;">
                        <div class="contact-avatar"><?= Security::e($initials) ?></div>
                        <div class="contact-info" style="min-width:0;">
                            <strong style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= Security::e($s['nama']) ?></strong>
                            <small><?= Security::e($s['tipe']) ?> <?= $s['unit_kerja'] ? '• ' . Security::e($s['unit_kerja']) : '' ?></small>
                        </div>
                    </div>
                    <?php if ($s['no_wa']): ?>
                    <a class="wa-btn" target="_blank" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $s['no_wa']) ?>">💬 Chat</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;">📋 Daftar Alumni Responden</h3>
        <div class="search-box" style="margin:0;flex:1;max-width:400px;">
            <span>🔍</span>
            <input type="text" id="searchAlumni" placeholder="Cari nama atau NIM..." autocomplete="off">
        </div>
    </div>
    <div class="table-wrapper">
        <table id="alumniTable">
            <thead><tr><th>Nama & NIM</th><th>Lulus</th><th>Status</th><th>Masa Tunggu</th><th>Kontak</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr><td colspan="5" style="text-align:center;padding:40px;" class="text-muted">Belum ada alumni mengisi tracer. Sebarkan link tracer ke grup alumni!</td></tr>
            <?php else: ?>
                <?php foreach ($list as $r): 
                    $statusClass = match($r['status_kerja']) {
                        'Bekerja' => 'badge-unggul',
                        'Wirausaha' => 'badge-a',
                        'Melanjutkan Studi' => 'badge-b',
                        default => 'badge-baik'
                    };
                ?>
                <tr class="alumni-row" data-search="<?= strtolower(Security::e($r['nama'] . ' ' . $r['nim'])) ?>">
                    <td>
                        <strong><?= Security::e($r['nama']) ?></strong><br>
                        <small class="text-muted"><?= Security::e($r['nim']) ?></small>
                    </td>
                    <td><?= Security::e($r['tahun_lulus']) ?></td>
                    <td><span class="badge <?= $statusClass ?>"><?= Security::e($r['status_kerja'] ?: 'Belum Diisi') ?></span></td>
                    <td><?= $r['masa_tunggu_bulan'] ? $r['masa_tunggu_bulan'] . ' bln' : '—' ?></td>
                    <td>
                        <?php if ($r['no_wa']): ?>
                        <a class="wa-btn" target="_blank" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $r['no_wa']) ?>">💬 WA</a>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Chart Distribusi Status
new Chart(document.getElementById('cStatus'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            data: <?= json_encode($chartValues) ?>,
            backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#EF4444', '#8B5CF6', '#64748B'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right', labels: { usePointStyle: true, padding: 16, font: { size: 12, weight: '600' } } }
        }
    }
});

// Live Search
const searchInput = document.getElementById('searchAlumni');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.alumni-row').forEach(row => {
            const searchData = row.getAttribute('data-search');
            row.style.display = searchData.includes(query) ? '' : 'none';
        });
    });
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>