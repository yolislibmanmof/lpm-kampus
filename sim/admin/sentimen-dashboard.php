<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2, 3]); // Admin, Pimpinan, Kaprodi
require_once dirname(__DIR__, 2) . '/core/SentimentAnalyzer.php';
$db = Database::getInstance();

$simTitle = 'Analisis Sentimen EDOM';
$activeMenu = 'sentimen';

$user = Auth::user();

// Filter prodi berdasarkan role
$idProdiFilter = $user['role'] == 3 ? (int)$user['prodi_id'] : (int)($_GET['prodi'] ?? 0);
$idKelas = (int)($_GET['kelas'] ?? 0);

// Handle action: trigger batch analyze
if (isset($_GET['analyze']) && $user['role'] == 1) {
    $count = SentimentAnalyzer::batchAnalyze(200);
    $pesan = "✅ $count komentar dianalisis.";
}

// Ambil daftar prodi (admin/pimpinan lihat semua, kaprodi hanya sendiri)
$prodiOptions = [];
if ($user['role'] == 3) {
    $q = $db->prepare("SELECT id_prodi, nama_prodi FROM prodi WHERE id_prodi = ?");
    $q->execute([$idProdiFilter]);
} else {
    $q = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi");
}
$prodiOptions = $q->fetchAll();

// Statistik global
$whereKelas = '';
$params = [];
if ($idProdiFilter > 0) {
    $whereKelas = " JOIN edom_kelas k ON s.id_kelas = k.id_kelas JOIN dosen d ON k.id_dosen = d.id_dosen WHERE d.id_prodi = ? ";
    $params[] = $idProdiFilter;
}
$statsQ = $db->prepare("SELECT kategori, COUNT(*) cnt, AVG(skor_sentimen) avg FROM edom_sentimen s $whereKelas GROUP BY kategori");
$statsQ->execute($params);
$globalStats = ['Positif' => 0, 'Netral' => 0, 'Negatif' => 0, 'avg' => 0, 'total' => 0];
foreach ($statsQ->fetchAll() as $r) {
    $globalStats[$r['kategori']] = (int)$r['cnt'];
    $globalStats['total'] += (int)$r['cnt'];
}
$avgQ = $db->prepare("SELECT AVG(s.skor_sentimen) FROM edom_sentimen s $whereKelas");
$avgQ->execute($params);
$globalStats['avg'] = round((float)$avgQ->fetchColumn(), 2);

// Daftar kelas
$kelasList = [];
if ($idProdiFilter > 0) {
    $q = $db->prepare("SELECT k.id_kelas, k.mata_kuliah, d.nama_dosen, per.nama AS periode,
                              (SELECT COUNT(*) FROM edom_sentimen s WHERE s.id_kelas = k.id_kelas) AS total_sentimen
                       FROM edom_kelas k
                       JOIN dosen d ON k.id_dosen = d.id_dosen
                       JOIN edom_periode per ON k.id_periode = per.id_periode
                       WHERE d.id_prodi = ?
                       ORDER BY per.tanggal_selesai DESC");
    $q->execute([$idProdiFilter]);
    $kelasList = $q->fetchAll();
}

// Detail kelas
$kelasDetail = null;
$wordCloud = [];
$kelasStats = null;
if ($idKelas > 0) {
    $q = $db->prepare("SELECT k.*, d.nama_dosen, per.nama AS periode FROM edom_kelas k
                       JOIN dosen d ON k.id_dosen = d.id_dosen
                       JOIN edom_periode per ON k.id_periode = per.id_periode
                       WHERE k.id_kelas = ?");
    $q->execute([$idKelas]);
    $kelasDetail = $q->fetch();
    $kelasStats = SentimentAnalyzer::getClassStats($idKelas);
    $wordCloud = SentimentAnalyzer::getWordCloud($idKelas, 30);
}

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if (!empty($pesan)): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="margin:0;">🧠 Analisis Sentimen EDOM</h2>
        <p class="text-muted" style="margin:4px 0 0;font-size:13px;">Analisis otomatis komentar mahasiswa menggunakan NLP ringan.</p>
    </div>
    <?php if ($user['role'] == 1): ?>
        <a href="?analyze=1<?= $idProdiFilter ? '&prodi='.$idProdiFilter : '' ?>" class="btn btn-primary" style="padding:8px 16px;font-size:13px;">🔄 Analisis Batch</a>
    <?php endif; ?>
</div>

<!-- FILTER -->
<div class="card" style="margin-bottom:24px;">
    <form method="GET" style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end;">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Filter Program Studi</label>
            <select name="prodi" class="form-control" onchange="this.form.submit()">
                <option value="0">— Pilih Prodi —</option>
                <?php foreach ($prodiOptions as $p): ?>
                    <option value="<?= (int)$p['id_prodi'] ?>" <?= $idProdiFilter === (int)$p['id_prodi'] ? 'selected' : '' ?>><?= Security::e($p['nama_prodi']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-outline" style="height:42px;">Terapkan</button>
    </form>
</div>

<?php if ($idProdiFilter > 0): ?>

<!-- GLOBAL STATS -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
    <div class="stat-card"><div class="stat-icon blue">💬</div><div><h3 style="font-size:24px;" data-count="<?= $globalStats['total'] ?>"><?= $globalStats['total'] ?></h3><p class="text-muted">Total Komentar Dianalisis</p></div></div>
    <div class="stat-card"><div class="stat-icon green">😊</div><div><h3 style="font-size:24px;" data-count="<?= $globalStats['Positif'] ?>"><?= $globalStats['Positif'] ?></h3><p class="text-muted">Sentimen Positif</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">😐</div><div><h3 style="font-size:24px;" data-count="<?= $globalStats['Netral'] ?>"><?= $globalStats['Netral'] ?></h3><p class="text-muted">Sentimen Netral</p></div></div>
    <div class="stat-card"><div class="stat-icon red">😞</div><div><h3 style="font-size:24px;" data-count="<?= $globalStats['Negatif'] ?>"><?= $globalStats['Negatif'] ?></h3><p class="text-muted">Sentimen Negatif</p></div></div>
</div>

<!-- CHART -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">📊 Distribusi Sentimen</h3>
        <div style="height:280px;"><canvas id="pieChart"></canvas></div>
    </div>
    <div class="card">
        <h3 style="margin-bottom:16px;">📈 Tren Skor Rata-rata</h3>
        <div style="padding:20px;">
            <div style="text-align:center;margin-bottom:20px;">
                <div style="font-size:48px;font-weight:800;color:<?= $globalStats['avg'] > 0.2 ? 'var(--success)' : ($globalStats['avg'] < -0.2 ? 'var(--danger)' : 'var(--text-muted)') ?>;">
                    <?= number_format($globalStats['avg'], 2) ?>
                </div>
                <p class="text-muted" style="font-size:13px;">Skor Sentimen Rata-rata</p>
            </div>
            <div style="background:var(--bg-light);border-radius:12px;padding:16px;font-size:12px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span>Positif: <strong><?= $globalStats['Positif'] ?></strong></span>
                    <span><?= $globalStats['total'] > 0 ? round(($globalStats['Positif']/$globalStats['total'])*100) : 0 ?>%</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span>Netral: <strong><?= $globalStats['Netral'] ?></strong></span>
                    <span><?= $globalStats['total'] > 0 ? round(($globalStats['Netral']/$globalStats['total'])*100) : 0 ?>%</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span>Negatif: <strong><?= $globalStats['Negatif'] ?></strong></span>
                    <span><?= $globalStats['total'] > 0 ? round(($globalStats['Negatif']/$globalStats['total'])*100) : 0 ?>%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DAFTAR KELAS -->
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">📚 Daftar Kelas</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Mata Kuliah</th><th>Dosen</th><th>Periode</th><th>Komentar Dianalisis</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($kelasList)): ?>
                <tr><td colspan="5" style="text-align:center;padding:30px;" class="text-muted">Belum ada data kelas.</td></tr>
            <?php else: foreach ($kelasList as $k): ?>
                <tr>
                    <td><strong><?= Security::e($k['mata_kuliah']) ?></strong></td>
                    <td><?= Security::e($k['nama_dosen']) ?></td>
                    <td><?= Security::e($k['periode']) ?></td>
                    <td><?= $k['total_sentimen'] > 0 ? '<span class="badge badge-success">' . $k['total_sentimen'] . '</span>' : '<span class="text-muted">0</span>' ?></td>
                    <td><a href="?prodi=<?= $idProdiFilter ?>&kelas=<?= (int)$k['id_kelas'] ?>" class="btn btn-outline" style="padding:4px 12px;font-size:12px;">🔍 Detail</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($kelasDetail): ?>
<!-- DETAIL KELAS -->
<a href="?prodi=<?= $idProdiFilter ?>" class="btn btn-outline" style="margin-bottom:20px;padding:8px 18px;font-size:13px;">← Kembali</a>

<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:4px;"><?= Security::e($kelasDetail['mata_kuliah']) ?></h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:20px;">
        Dosen: <strong><?= Security::e($kelasDetail['nama_dosen']) ?></strong> • 
        Periode: <strong><?= Security::e($kelasDetail['periode']) ?></strong>
    </p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <!-- Stats -->
        <div>
            <h4 style="margin-bottom:12px;">📊 Statistik</h4>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;padding:12px;background:rgba(16,185,129,.08);border-radius:10px;border-left:4px solid var(--success);">
                    <span>😊 Positif</span><strong><?= $kelasStats['Positif'] ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px;background:rgba(107,114,128,.08);border-radius:10px;border-left:4px solid #6B7280;">
                    <span>😐 Netral</span><strong><?= $kelasStats['Netral'] ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px;background:rgba(239,68,68,.08);border-radius:10px;border-left:4px solid var(--danger);">
                    <span>😞 Negatif</span><strong><?= $kelasStats['Negatif'] ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px;background:var(--bg-light);border-radius:10px;">
                    <span>Rata-rata skor</span><strong style="color:<?= $kelasStats['avg_skor'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;"><?= number_format($kelasStats['avg_skor'], 2) ?></strong>
                </div>
            </div>
        </div>

        <!-- Word Cloud -->
        <div>
            <h4 style="margin-bottom:12px;">☁️ Kata Kunci Teratas</h4>
            <?php if (empty($wordCloud)): ?>
                <p class="text-muted" style="text-align:center;padding:20px;">Belum ada data.</p>
            <?php else: ?>
                <div style="padding:16px;background:var(--bg-light);border-radius:12px;line-height:2.2;text-align:center;">
                    <?php foreach ($wordCloud as $w): ?>
                        <span style="display:inline-block;padding:4px 8px;font-size:<?= $w['size'] ?>px;font-weight:<?= $w['weight'] >= 0 ? '700' : '600' ?>;color:<?= $w['weight'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;margin:2px;">
                            <?= Security::e($w['text']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Positif', 'Netral', 'Negatif'],
        datasets: [{
            data: [<?= $globalStats['Positif'] ?>, <?= $globalStats['Netral'] ?>, <?= $globalStats['Negatif'] ?>],
            backgroundColor: ['#10B981', '#94A3B8', '#EF4444'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

<?php else: ?>
<div class="card" style="text-align:center;padding:60px 20px;">
    <div style="font-size:64px;margin-bottom:16px;">🎓</div>
    <h3>Pilih Program Studi</h3>
    <p class="text-muted">Pilih prodi dari dropdown di atas untuk melihat analisis sentimen.</p>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>