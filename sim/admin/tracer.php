<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

/* ===== Export CSV ===== */
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tracer_study_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBFnama;nim;prodi;tahun_lulus;email;wa;status;instansi;jabatan;masa_tunggu;kesesuaian;gaji;siap_wawancara\n";
    foreach ($db->query("SELECT t.*, p.nama_prodi FROM tracer_alumni t LEFT JOIN prodi p ON t.id_prodi = p.id_prodi ORDER BY t.created_at DESC")->fetchAll() as $r) {
        echo implode(';', array_map(fn($v) => str_replace(';', ',', (string)$v), [
            $r['nama'], $r['nim'], $r['nama_prodi'], $r['tahun_lulus'], $r['email'], $r['no_wa'],
            $r['status_kerja'], $r['nama_instansi'], $r['jabatan'], $r['masa_tunggu_bulan'],
            $r['kesesuaian_bidang'], $r['kisaran_gaji'], $r['siap_wawancara'] ? 'Ya' : 'Tidak'
        ])) . "\n";
    }
    exit;
}

$total = (int)$db->query("SELECT COUNT(*) t FROM tracer_alumni")->fetch()['t'];
$worked = (int)$db->query("SELECT COUNT(*) t FROM tracer_alumni WHERE status_kerja IN ('Bekerja','Wirausaha')")->fetch()['t'];
$avgT = $db->query("SELECT ROUND(AVG(masa_tunggu_bulan),1) a FROM tracer_alumni WHERE status_kerja IN ('Bekerja','Wirausaha')")->fetch()['a'] ?? 0;
$sesuai = (int)$db->query("SELECT COUNT(*) t FROM tracer_alumni WHERE kesesuaian_bidang IN ('Sangat Sesuai','Sesuai')")->fetch()['t'];
$siap = (int)$db->query("SELECT COUNT(*) t FROM tracer_alumni WHERE siap_wawancara = 1")->fetch()['t'];

$tkr = $total > 0 ? round($worked / $total * 100) : 0;
$skr = $worked > 0 ? round($sesuai / $worked * 100) : 0;

$statusDist = $db->query("SELECT status_kerja, COUNT(*) c FROM tracer_alumni GROUP BY status_kerja")->fetchAll();
$tungguDist = $db->query("SELECT
    SUM(masa_tunggu_bulan <= 3) s1, SUM(masa_tunggu_bulan BETWEEN 4 AND 6) s2,
    SUM(masa_tunggu_bulan BETWEEN 7 AND 12) s3, SUM(masa_tunggu_bulan > 12) s4
    FROM tracer_alumni WHERE status_kerja IN ('Bekerja','Wirausaha')")->fetch();

$prodiFilter = (int)($_GET['prodi'] ?? 0);
$q = "SELECT t.*, p.nama_prodi FROM tracer_alumni t LEFT JOIN prodi p ON t.id_prodi = p.id_prodi";
$params = [];
if ($prodiFilter) { $q .= " WHERE t.id_prodi = ?"; $params[] = $prodiFilter; }
$q .= " ORDER BY t.created_at DESC LIMIT 200";
$stmt = $db->prepare($q); $stmt->execute($params);
$list = $stmt->fetchAll();

$siapList = $db->query("SELECT t.*, p.nama_prodi FROM tracer_alumni t LEFT JOIN prodi p ON t.id_prodi = p.id_prodi WHERE t.siap_wawancara = 1 ORDER BY p.nama_prodi, t.tahun_lulus DESC")->fetchAll();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$formUrl = APP_URL . '/publik/tracer.php';

$simTitle = 'Tracer Study';
$activeMenu = 'tracer';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
        <h2 style="font-size:22px;color:var(--primary-dark);">🎓 Dasbor Tracer Study</h2>
        <p class="text-muted" style="font-size:13px;">Tingkat kerja <?= $tkr ?>% • Kesesuaian bidang <?= $skr ?>% • Rata-rata masa tunggu <?= $avgT ?> bulan</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="?export=1" class="btn btn-outline">📤 Export CSV</a>
        <button class="btn btn-gold" onclick="navigator.clipboard.writeText('<?= Security::e($formUrl) ?>');this.textContent='✅ Tersalin';">📋 Salin Tautan Form</button>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">🎓</div><div><h3 style="font-size:28px;"><?= $total ?></h3><p class="text-muted">Responden</p></div></div>
    <div class="stat-card"><div class="stat-icon green">💼</div><div><h3 style="font-size:28px;"><?= $tkr ?>%</h3><p class="text-muted">Terserap Kerja</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">🎯</div><div><h3 style="font-size:28px;"><?= $skr ?>%</h3><p class="text-muted">Sesuai Bidang</p></div></div>
    <div class="stat-card"><div class="stat-icon red">🙋</div><div><h3 style="font-size:28px;"><?= $siap ?></h3><p class="text-muted">Siap Wawancara Asesor</p></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:24px;margin-bottom:28px;">
    <div class="card"><h3 style="margin-bottom:16px;">Status Alumni</h3><canvas id="cStatus"></canvas></div>
    <div class="card"><h3 style="margin-bottom:16px;">Masa Tunggu Kerja</h3><canvas id="cTunggu"></canvas></div>
    <div class="card" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;">
        <h3 style="margin-bottom:12px;color:#fff;">QR Form Alumni</h3>
        <div id="qrTr" style="background:#fff;border-radius:12px;padding:10px;display:inline-block;line-height:0;"></div>
        <p style="font-size:12px;opacity:.8;margin-top:10px;">Sebarkan ke grup alumni / wisuda.</p>
    </div>
</div>

<div class="card" style="margin-bottom:28px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3>🙋 Alumni Siap Wawancara Asesor (<?= count($siapList) ?>)</h3>
    </div>
    <?php if (empty($siapList)): ?><p class="text-muted">Belum ada alumni yang bersedia diwawancara.</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Nama</th><th>Prodi</th><th>Lulus</th><th>Pekerjaan</th><th>Kontak</th></tr></thead>
            <tbody>
            <?php foreach ($siapList as $a): ?>
                <tr>
                    <td><strong><?= Security::e($a['nama']) ?></strong></td>
                    <td><?= Security::e($a['nama_prodi']) ?></td>
                    <td><?= Security::e($a['tahun_lulus']) ?></td>
                    <td><?= Security::e($a['jabatan']) ?> <?= $a['nama_instansi'] ? '— ' . Security::e($a['nama_instansi']) : '' ?></td>
                    <td><a class="btn btn-primary" style="padding:5px 14px;font-size:12px;background:var(--success);" target="_blank" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $a['no_wa']) ?>">💬 WA</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3>📋 Seluruh Responden</h3>
        <form method="GET"><select name="prodi" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="0">Semua Prodi</option>
            <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>" <?= $prodiFilter == $p['id_prodi'] ? 'selected' : '' ?>><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
        </select></form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Nama</th><th>Prodi</th><th>Lulus</th><th>Status</th><th>Masa Tunggu</th><th>Kesesuaian</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada responden.</td></tr><?php endif; ?>
            <?php foreach ($list as $r): ?>
                <tr>
                    <td><strong><?= Security::e($r['nama']) ?></strong><br><small class="text-muted"><?= Security::e($r['nim']) ?></small></td>
                    <td><?= Security::e($r['nama_prodi']) ?></td>
                    <td><?= Security::e($r['tahun_lulus']) ?></td>
                    <td><span class="badge <?= $r['status_kerja'] === 'Bekerja' ? 'badge-unggul' : ($r['status_kerja'] === 'Wirausaha' ? 'badge-a' : 'badge-b') ?>"><?= Security::e($r['status_kerja']) ?></span></td>
                    <td><?= $r['masa_tunggu_bulan'] ?> bln</td>
                    <td><?= Security::e($r['kesesuaian_bidang']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
<script>
(function () {
    if (typeof qrcode === 'function') {
        var qr = qrcode(0, 'M'); qr.addData(<?= json_encode($formUrl) ?>); qr.make();
        var box = document.getElementById('qrTr');
        box.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
        var s = box.querySelector('svg'); if (s) { s.style.width = '150px'; s.style.height = '150px'; }
    }
})();
new Chart(document.getElementById('cStatus'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_column($statusDist, 'status_kerja')) ?: '["-"]' ?>,
            datasets: [{ data: <?= json_encode(array_column($statusDist, 'c')) ?: '[0]' ?>, backgroundColor: ['#10B981','#C9A227','#3B82F6','#64748B'], borderWidth: 3, borderColor: '#fff' }] },
    options: { plugins: { legend: { position: 'bottom' } } }
});
new Chart(document.getElementById('cTunggu'), {
    type: 'bar',
    data: { labels: ['≤3 bln','4–6','7–12','>12'],
            datasets: [{ label: 'Alumni', data: [<?= (int)$tungguDist['s1'] ?>, <?= (int)$tungguDist['s2'] ?>, <?= (int)$tungguDist['s3'] ?>, <?= (int)$tungguDist['s4'] ?>], backgroundColor: '#1A5A82', borderRadius: 8 }] }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>