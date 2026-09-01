<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="survei_pengguna_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBFinstansi;responden;jabatan;alumni;prodi;tahun;etika;keahlian;bahasa;teknologi;komunikasi;kerjasama;pengembangan;komentar\n";
    foreach ($db->query("SELECT s.*, p.nama_prodi FROM survei_pengguna s LEFT JOIN prodi p ON s.id_prodi = p.id_prodi ORDER BY s.created_at DESC")->fetchAll() as $r) {
        echo implode(';', array_map(fn($v) => str_replace(';', ',', (string)$v), [
            $r['nama_instansi'], $r['nama_responden'], $r['jabatan'], $r['nama_alumni'], $r['nama_prodi'], $r['tahun_lulus'],
            $r['aspek_etika'], $r['aspek_keahlian'], $r['aspek_bahasa'], $r['aspek_teknologi'], $r['aspek_komunikasi'], $r['aspek_kerjasama'], $r['aspek_pengembangan'], $r['komentar']
        ])) . "\n";
    }
    exit;
}

$total = (int)$db->query("SELECT COUNT(*) t FROM survei_pengguna")->fetch()['t'];
$avg = $db->query("SELECT ROUND(AVG(aspek_etika),2) etika, ROUND(AVG(aspek_keahlian),2) keahlian, ROUND(AVG(aspek_bahasa),2) bahasa,
    ROUND(AVG(aspek_teknologi),2) teknologi, ROUND(AVG(aspek_komunikasi),2) komunikasi, ROUND(AVG(aspek_kerjasama),2) kerjasama,
    ROUND(AVG(aspek_pengembangan),2) pengembangan FROM survei_pengguna")->fetch();
$list = $db->query("SELECT s.*, p.nama_prodi FROM survei_pengguna s LEFT JOIN prodi p ON s.id_prodi = p.id_prodi ORDER BY s.created_at DESC LIMIT 200")->fetchAll();

$simTitle = 'Survei Pengguna Lulusan';
$activeMenu = 'surveipg';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <h2 style="font-size:22px;color:var(--primary-dark);">💼 Survei Pengguna Lulusan — <?= $total ?> responden</h2>
    <div style="display:flex;gap:10px;">
        <a href="?export=1" class="btn btn-outline">📤 Export CSV</a>
        <button class="btn btn-gold" onclick="navigator.clipboard.writeText('<?= APP_URL ?>/publik/survei-pengguna.php');this.textContent='✅ Tersalin';">📋 Salin Tautan Form</button>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:24px;margin-bottom:28px;">
    <div class="card"><h3 style="margin-bottom:16px;">📡 Radar Kompetensi Lulusan (skala 5)</h3>
        <div style="height:320px;"><canvas id="cRadar"></canvas></div></div>
    <div class="card" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;">
        <h3 style="color:#fff;margin-bottom:12px;">💡 Cara Membaca</h3>
        <p style="font-size:14px;opacity:.85;">Radar ini adalah <strong>bukti akreditasi</strong> tentang persepsi pengguna lulusan. Sisi yang cekung = kompetensi yang perlu diperkuat kurikulum. Tempel grafik ini ke LED/LKPS kriteria lulusan.</p>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Riwayat Penilaian</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Instansi</th><th>Lulusan Dinilai</th><th>Rata-rata</th><th>Komentar</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="4" style="text-align:center;padding:30px;" class="text-muted">Belum ada penilaian masuk.</td></tr><?php endif; ?>
            <?php foreach ($list as $r):
                $rata = round(((float)$r['aspek_etika'] + $r['aspek_keahlian'] + $r['aspek_bahasa'] + $r['aspek_teknologi'] + $r['aspek_komunikasi'] + $r['aspek_kerjasama'] + $r['aspek_pengembangan']) / 7, 1);
            ?>
                <tr>
                    <td><strong><?= Security::e($r['nama_instansi']) ?></strong><br><small class="text-muted"><?= Security::e($r['nama_responden']) ?> <?= $r['jabatan'] ? '— ' . Security::e($r['jabatan']) : '' ?></small></td>
                    <td><?= Security::e($r['nama_alumni']) ?><br><small class="text-muted"><?= Security::e($r['nama_prodi']) ?> · <?= Security::e($r['tahun_lulus']) ?></small></td>
                    <td><span class="badge <?= $rata >= 4 ? 'badge-unggul' : ($rata >= 3 ? 'badge-a' : 'badge-b') ?>"><?= $rata ?> / 5</span></td>
                    <td style="max-width:280px;font-size:13px;"><?= Security::e(mb_strimwidth($r['komentar'] ?: '—', 0, 100, '...')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('cRadar'), {
    type: 'radar',
    data: { labels: ['Etika','Keahlian','Bahasa','Teknologi','Komunikasi','Kerjasama','Pengembangan'],
        datasets: [{ label: 'Rata-rata', data: [<?= (float)($avg['etika'] ?? 0) ?>, <?= (float)($avg['keahlian'] ?? 0) ?>, <?= (float)($avg['bahasa'] ?? 0) ?>, <?= (float)($avg['teknologi'] ?? 0) ?>, <?= (float)($avg['komunikasi'] ?? 0) ?>, <?= (float)($avg['kerjasama'] ?? 0) ?>, <?= (float)($avg['pengembangan'] ?? 0) ?>],
        backgroundColor: 'rgba(201,162,39,.25)', borderColor: '#C9A227', borderWidth: 2, pointBackgroundColor: '#0F3D5C' }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { r: { min: 0, max: 5, ticks: { display: false } } } }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>