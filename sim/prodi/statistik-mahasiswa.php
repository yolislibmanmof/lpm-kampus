<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = ''; $error = '';
$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'Statistik Mahasiswa';
$activeMenu = 'statmhs';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun belum terhubung ke prodi.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $db->prepare("REPLACE INTO statistik_mahasiswa (id_prodi, tahun_akademik, peminat, daya_tampung, maba, jumlah_mahasiswa, lulus) VALUES (?,?,?,?,?,?,?)")
           ->execute([$prodiId, trim($_POST['tahun_akademik']), (int)$_POST['peminat'], (int)$_POST['daya_tampung'], (int)$_POST['maba'], (int)$_POST['jumlah_mahasiswa'], (int)$_POST['lulus']]);
        $pesan = '✅ Statistik berhasil disimpan.';
    }
    if ($action === 'csv') {
        if (!empty($_FILES['csv']['name'])) {
            $h = fopen($_FILES['csv']['tmp_name'], 'r'); $n = 0;
            while (($row = fgetcsv($h, 2000, ';')) !== false) {
                if (count($row) < 6 || !preg_match('/\d{4}/', $row[0])) continue;
                $db->prepare("REPLACE INTO statistik_mahasiswa (id_prodi, tahun_akademik, peminat, daya_tampung, maba, jumlah_mahasiswa, lulus) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$prodiId, trim($row[0]), (int)$row[1], (int)$row[2], (int)$row[3], (int)$row[4], (int)$row[5]]);
                $n++;
            }
            fclose($h);
            $pesan = "✅ $n baris CSV berhasil diimpor.";
        }
    }
    if ($action === 'matriks') {
        $st = $db->prepare("SELECT * FROM statistik_mahasiswa WHERE id_stat = ? AND id_prodi = ?");
        $st->execute([(int)$_POST['id_stat'], $prodiId]); $s = $st->fetch();
        if ($s) {
            $kr = $db->prepare("SELECT id_kriteria FROM kriteria_akreditasi WHERE nomor = 3"); $kr->execute();
            $kid = $kr->fetch()['id_kriteria'] ?? null;
            if ($kid) {
                $db->prepare("INSERT INTO bukti_kriteria (id_prodi, id_kriteria, indikator, nama_bukti, file_path, status, tahun) VALUES (?,?,?,?,NULL,'Lengkap',?)")
                   ->execute([$prodiId, $kid, 'Statistik mahasiswa ' . $s['tahun_akademik'], 'Peminat ' . $s['peminat'] . ', daya tampung ' . $s['daya_tampung'] . ', maba ' . $s['maba'] . ', aktif ' . $s['jumlah_mahasiswa'] . ', lulus ' . $s['lulus'], date('Y')]);
                $pesan = '📌 Statistik berhasil ditambahkan ke Matriks Bukti Kriteria 3.';
            }
        }
    }
    if ($action === 'del') {
        $db->prepare("DELETE FROM statistik_mahasiswa WHERE id_stat = ? AND id_prodi = ?")->execute([(int)$_POST['id_stat'], $prodiId]);
        $pesan = '✅ Data berhasil dihapus.';
    }
}

$list = $db->prepare("SELECT * FROM statistik_mahasiswa WHERE id_prodi = ? ORDER BY tahun_akademik ASC");
$list->execute([$prodiId]); $list = $list->fetchAll();

// Stats summary
$totalMaba = array_sum(array_column($list, 'maba'));
$totalLulus = array_sum(array_column($list, 'lulus'));
$avgKeterisian = 0;
if ($list) {
    $sum = 0; $count = 0;
    foreach ($list as $s) {
        if ($s['daya_tampung'] > 0) {
            $sum += ($s['maba'] / $s['daya_tampung'] * 100);
            $count++;
        }
    }
    $avgKeterisian = $count > 0 ? round($sum / $count) : 0;
}
?>

<style>
    .stat-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 28px 32px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .stat-header::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -80px; top: -100px;
    }
    .stat-header h2 { font-size: 22px; margin: 0 0 8px; position: relative; font-weight: 800; }
    .stat-header p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; margin: 0; }
    
    .stat-summary {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-top: 20px;
    }
    .stat-sum-item {
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px); border-radius: 12px;
        padding: 16px; text-align: center; border: 1px solid rgba(255,255,255,.15);
    }
    .stat-sum-item h4 { font-size: 28px; margin: 0 0 4px; font-weight: 800; color: var(--accent-light); }
    .stat-sum-item small { font-size: 12px; opacity: .8; text-transform: uppercase; letter-spacing: 1px; }

    .input-card {
        background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 28px;
        box-shadow: 0 4px 12px rgba(0,0,0,.05); border: 2px solid var(--border);
    }
    .csv-drop {
        border: 2px dashed var(--border); border-radius: 12px; padding: 20px;
        text-align: center; background: var(--bg-light); transition: all 0.3s;
        display: flex; align-items: center; justify-content: center; gap: 12px;
    }
    .csv-drop:hover { border-color: var(--accent); background: rgba(201,162,39,.05); }
    
    .badge-kinerja {
        display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 800;
    }
    .kinerja-tinggi { background: #D1FAE5; color: #065F46; }
    .kinerja-sedang { background: #FEF3C7; color: #92400E; }
    .kinerja-rendah { background: #FEE2E2; color: #991B1B; }
</style>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="stat-header">
    <h2>📊 Statistik & Tren Mahasiswa</h2>
    <p>Pantau daya tarik prodi melalui rasio peminat, keterisian, dan kelulusan.</p>
    <div class="stat-summary">
        <div class="stat-sum-item"><h4><?= count($list) ?></h4><small>Tahun Tercatat</small></div>
        <div class="stat-sum-item"><h4><?= $totalMaba ?></h4><small>Total Maba</small></div>
        <div class="stat-sum-item"><h4><?= $totalLulus ?></h4><small>Total Lulus</small></div>
        <div class="stat-sum-item"><h4><?= $avgKeterisian ?>%</h4><small>Rata-rata Keterisian</small></div>
    </div>
</div>

<div class="input-card">
    <h3 style="margin:0 0 16px;font-size:18px;">➕ Input Data Statistik Tahunan</h3>
    <form method="POST" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:16px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="save">
        <div class="form-group" style="margin:0;"><label class="form-label">Tahun Akademik</label>
            <input type="text" name="tahun_akademik" class="form-control" placeholder="2025/2026" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Peminat</label><input type="number" name="peminat" class="form-control" value="0"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Daya Tampung</label><input type="number" name="daya_tampung" class="form-control" value="0"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Maba</label><input type="number" name="maba" class="form-control" value="0"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Mhs Aktif</label><input type="number" name="jumlah_mahasiswa" class="form-control" value="0"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Lulus</label><input type="number" name="lulus" class="form-control" value="0"></div>
        <button class="btn btn-gold" style="height:42px;">💾 Simpan</button>
    </form>
    
    <form method="POST" enctype="multipart/form-data" style="margin-top:20px;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="csv">
        <div class="csv-drop">
            <span style="font-size:24px;">📥</span>
            <div style="text-align:left;">
                <input type="file" name="csv" accept=".csv,.txt" style="font-size:13px;">
                <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
                    Format CSV: <code>tahun_akademik;peminat;daya_tampung;maba;jumlah_mahasiswa;lulus</code>
                </div>
            </div>
            <button class="btn btn-outline" style="margin-left:auto;">Import</button>
        </div>
    </form>
</div>

<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">📈 Tren Peminat vs Maba</h3>
        <div style="height:320px;"><canvas id="cStat"></canvas></div>
    </div>
    <div class="card">
        <h3 style="margin-bottom:16px;">📋 Riwayat & Rasio Kinerja</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tahun Akademik</th>
                        <th style="text-align:center;">Peminat</th>
                        <th style="text-align:center;">Maba</th>
                        <th style="text-align:center;">Ketetatan</th>
                        <th style="text-align:center;">Keterisian</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;" class="text-muted">Belum ada data statistik.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $s): 
                        $ketetatan = $s['maba'] > 0 ? round($s['peminat'] / $s['maba'], 1) : 0;
                        $keterisian = $s['daya_tampung'] > 0 ? round($s['maba'] / $s['daya_tampung'] * 100) : 0;
                        $kinerjaClass = $keterisian >= 100 ? 'kinerja-tinggi' : ($keterisian >= 80 ? 'kinerja-sedang' : 'kinerja-rendah');
                        $kinerjaText = $keterisian >= 100 ? '🟢 Unggul' : ($keterisian >= 80 ? '🟡 Baik' : '🔴 Perhatian');
                    ?>
                        <tr>
                            <td><strong><?= Security::e($s['tahun_akademik']) ?></strong></td>
                            <td style="text-align:center;"><?= number_format($s['peminat']) ?></td>
                            <td style="text-align:center;font-weight:700;"><?= number_format($s['maba']) ?></td>
                            <td style="text-align:center;"><?= $ketetatan > 0 ? $ketetatan . ' : 1' : '—' ?></td>
                            <td style="text-align:center;">
                                <span class="badge-kinerja <?= $kinerjaClass ?>"><?= $keterisian ?>% <?= $kinerjaText ?></span>
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex;gap:6px;justify-content:center;">
                                    <form method="POST"><input type="hidden" name="id_stat" value="<?= $s['id_stat'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="matriks"><button class="btn btn-primary" style="padding:4px 10px;font-size:11px;" title="Tambah ke Matriks Bukti K3">📌 K3</button></form>
                                    <form method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');"><input type="hidden" name="id_stat" value="<?= $s['id_stat'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);" title="Hapus">🗑️</button></form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('cStat').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: { 
        labels: <?= json_encode(array_column($list, 'tahun_akademik')) ?: '["-"]' ?>,
        datasets: [
            { 
                label: 'Peminat', 
                data: <?= json_encode(array_map(fn($s) => (int)$s['peminat'], $list)) ?: '[0]' ?>, 
                backgroundColor: 'rgba(15, 61, 92, 0.8)',
                borderColor: '#0F3D5C',
                borderWidth: 2,
                borderRadius: 6,
                order: 2
            },
            { 
                label: 'Maba', 
                data: <?= json_encode(array_map(fn($s) => (int)$s['maba'], $list)) ?: '[0]' ?>, 
                backgroundColor: 'rgba(201, 162, 39, 0.8)',
                borderColor: '#C9A227',
                borderWidth: 2,
                borderRadius: 6,
                order: 1
            },
            {
                label: 'Lulus',
                data: <?= json_encode(array_map(fn($s) => (int)$s['lulus'], $list)) ?: '[0]' ?>,
                type: 'line',
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#10B981',
                pointRadius: 4,
                fill: true,
                order: 0
            }
        ] 
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { usePointStyle: true, padding: 16, font: { weight: '600' } } },
            tooltip: { backgroundColor: 'rgba(15, 61, 92, 0.95)', padding: 12, cornerRadius: 8 }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>