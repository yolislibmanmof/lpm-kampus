<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

/* ===== Export CSV (Robust & Excel-Friendly) ===== */
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="survei_pengguna_' . date('Ymd') . '.csv"');
    
    $output = fopen('php://output', 'w');
    // UTF-8 BOM agar Excel membaca karakter Indonesia dengan benar
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header kolom
    fputcsv($output, [
        'Instansi', 'Responden', 'Jabatan', 'Nama Alumni', 'Prodi', 'Tahun Lulus', 
        'Etika', 'Keahlian', 'Bahasa', 'Teknologi', 'Komunikasi', 'Kerjasama', 'Pengembangan', 'Komentar'
    ], ';');
    
    $stmt = $db->query("SELECT s.*, p.nama_prodi FROM survei_pengguna s LEFT JOIN prodi p ON s.id_prodi = p.id_prodi ORDER BY s.created_at DESC");
    while ($r = $stmt->fetch()) {
        fputcsv($output, [
            $r['nama_instansi'] ?? '',
            $r['nama_responden'] ?? '',
            $r['jabatan'] ?? '',
            $r['nama_alumni'] ?? '',
            $r['nama_prodi'] ?? '',
            $r['tahun_lulus'] ?? '',
            (float)($r['aspek_etika'] ?? 0),
            (float)($r['aspek_keahlian'] ?? 0),
            (float)($r['aspek_bahasa'] ?? 0),
            (float)($r['aspek_teknologi'] ?? 0),
            (float)($r['aspek_komunikasi'] ?? 0),
            (float)($r['aspek_kerjasama'] ?? 0),
            (float)($r['aspek_pengembangan'] ?? 0),
            $r['komentar'] ?? ''
        ], ';');
    }
    fclose($output);
    exit;
}

try {
    $total = (int)$db->query("SELECT COUNT(*) t FROM survei_pengguna")->fetch()['t'];
    
    $avg = $db->query("SELECT 
        ROUND(AVG(aspek_etika), 2) as etika, 
        ROUND(AVG(aspek_keahlian), 2) as keahlian, 
        ROUND(AVG(aspek_bahasa), 2) as bahasa,
        ROUND(AVG(aspek_teknologi), 2) as teknologi, 
        ROUND(AVG(aspek_komunikasi), 2) as komunikasi, 
        ROUND(AVG(aspek_kerjasama), 2) as kerjasama,
        ROUND(AVG(aspek_pengembangan), 2) as pengembangan 
        FROM survei_pengguna")->fetch();

    $list = $db->query("SELECT s.*, p.nama_prodi FROM survei_pengguna s LEFT JOIN prodi p ON s.id_prodi = p.id_prodi ORDER BY s.created_at DESC LIMIT 300")->fetchAll();
    
    // Perbaikan: Hindari double slash pada URL
    $formUrl = rtrim(APP_URL ?? '', '/') . '/publik/survei-pengguna.php';
    
    // Siapkan data chart agar aman dari error JS
    $chartData = [
        (float)($avg['etika'] ?? 0),
        (float)($avg['keahlian'] ?? 0),
        (float)($avg['bahasa'] ?? 0),
        (float)($avg['teknologi'] ?? 0),
        (float)($avg['komunikasi'] ?? 0),
        (float)($avg['kerjasama'] ?? 0),
        (float)($avg['pengembangan'] ?? 0)
    ];
} catch (PDOException $e) {
    die("❌ Gagal memuat data Survei Pengguna: " . $e->getMessage());
}

$simTitle = 'Survei Pengguna Lulusan';
$activeMenu = 'surveipg';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
        <h2 style="font-size:22px;color:var(--primary-dark);">💼 Survei Pengguna Lulusan</h2>
        <p class="text-muted" style="font-size:13px;">Total <?= $total ?> responden terdata dalam sistem.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="?export=1" class="btn btn-outline">📤 Export CSV</a>
        <button class="btn btn-gold" onclick="navigator.clipboard.writeText('<?= Security::e($formUrl) ?>').then(()=>{let b=this;b.textContent='✅ Tersalin';setTimeout(()=>b.textContent='📋 Salin Tautan Form',2000)});">📋 Salin Tautan Form</button>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:24px;margin-bottom:28px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">📡 Radar Kompetensi Lulusan (Skala 1-5)</h3>
        <div style="height:320px;"><canvas id="cRadar"></canvas></div>
    </div>
    <div class="card" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;">
        <h3 style="color:#fff;margin-bottom:12px;">💡 Cara Membaca Grafik</h3>
        <p style="font-size:14px;opacity:.9;line-height:1.7;">
            Radar ini adalah <strong>bukti akreditasi</strong> tentang persepsi pengguna lulusan terhadap kompetensi alumni. 
            <br><br>
            Sisi grafik yang <strong>cekung</strong> (mendekati pusat) menandakan kompetensi yang perlu diperkuat dalam kurikulum atau kegiatan mahasiswa. Grafik ini sangat direkomendasikan untuk ditempel pada LED (Laporan Evaluasi Diri) atau LKPS kriteria lulusan.
        </p>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Riwayat Penilaian Detail</h3>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Instansi & Responden</th>
                    <th>Lulusan yang Dinilai</th>
                    <th>Rata-rata Skor</th>
                    <th>Komentar / Saran</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr><td colspan="4" style="text-align:center;padding:30px;" class="text-muted">Belum ada penilaian yang masuk.</td></tr>
            <?php else: ?>
                <?php foreach ($list as $r): 
                    // Perhitungan rata-rata yang aman dari nilai NULL
                    $rata = round((
                        (float)($r['aspek_etika'] ?? 0) + (float)($r['aspek_keahlian'] ?? 0) +
                        (float)($r['aspek_bahasa'] ?? 0) + (float)($r['aspek_teknologi'] ?? 0) +
                        (float)($r['aspek_komunikasi'] ?? 0) + (float)($r['aspek_kerjasama'] ?? 0) +
                        (float)($r['aspek_pengembangan'] ?? 0)
                    ) / 7, 1);
                ?>
                    <tr>
                        <td>
                            <strong><?= Security::e($r['nama_instansi'] ?? '—') ?></strong><br>
                            <small class="text-muted"><?= Security::e($r['nama_responden'] ?? '—') ?> <?= $r['jabatan'] ? '— ' . Security::e($r['jabatan']) : '' ?></small>
                        </td>
                        <td>
                            <?= Security::e($r['nama_alumni'] ?? '—') ?><br>
                            <small class="text-muted"><?= Security::e($r['nama_prodi'] ?? '—') ?> · <?= Security::e($r['tahun_lulus'] ?? '—') ?></small>
                        </td>
                        <td>
                            <span class="badge <?= $rata >= 4 ? 'badge-unggul' : ($rata >= 3 ? 'badge-a' : 'badge-b') ?>">
                                <?= number_format($rata, 1) ?> / 5
                            </span>
                        </td>
                        <td style="max-width:300px;font-size:13px;line-height:1.5;">
                            <?= Security::e(mb_strimwidth($r['komentar'] ?: '—', 0, 120, '...')) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('cRadar'), {
    type: 'radar',
    data: { 
        labels: ['Etika', 'Keahlian', 'Bahasa', 'Teknologi', 'Komunikasi', 'Kerjasama', 'Pengembangan'],
        datasets: [{ 
            label: 'Rata-rata Skor', 
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(201,162,39,.25)', 
            borderColor: '#C9A227', 
            borderWidth: 2, 
            pointBackgroundColor: '#0F3D5C',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#C9A227'
        }] 
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false, 
        scales: { 
            r: { 
                min: 0, 
                max: 5, 
                ticks: { 
                    stepSize: 1,
                    backdropColor: 'transparent'
                },
                pointLabels: {
                    font: { size: 12, weight: '600' }
                }
            } 
        },
        plugins: {
            legend: { display: false }
        }
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>