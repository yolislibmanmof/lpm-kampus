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
        $pesan = '✅ Statistik tersimpan.';
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
            $pesan = "✅ $n baris CSV terimpor. (Format: tahun_akademik;peminat;daya_tampung;maba;jumlah_mahasiswa;lulus)";
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
                $pesan = '📌 Statistik masuk Matriks Bukti K3.';
            }
        }
    }
    if ($action === 'del') {
        $db->prepare("DELETE FROM statistik_mahasiswa WHERE id_stat = ? AND id_prodi = ?")->execute([(int)$_POST['id_stat'], $prodiId]);
        $pesan = '✅ Baris dihapus.';
    }
}

$list = $db->prepare("SELECT * FROM statistik_mahasiswa WHERE id_prodi = ? ORDER BY tahun_akademik ASC");
$list->execute([$prodiId]); $list = $list->fetchAll();
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:16px;">➕ Input Statistik per Tahun Akademik</h3>
    <form method="POST" style="display:grid;grid-template-columns:.9fr repeat(5,.8fr) auto;gap:10px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="save">
        <div class="form-group" style="margin:0;"><label class="form-label">Tahun Akademik</label>
            <input type="text" name="tahun_akademik" class="form-control" placeholder="2025/2026" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Peminat</label><input type="number" name="peminat" class="form-control" value="0"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Daya Tampung</label><input type="number" name="daya_tampung" class="form-control" value="0"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Maba</label><input type="number" name="maba" class="form-control" value="0"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Mhs Aktif</label><input type="number" name="jumlah_mahasiswa" class="form-control" value="0"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Lulus</label><input type="number" name="lulus" class="form-control" value="0"></div>
        <button class="btn btn-gold">💾</button>
    </form>
    <form method="POST" enctype="multipart/form-data" style="margin-top:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="csv">
        <input type="file" name="csv" class="form-control" style="width:auto;" accept=".csv,.txt">
        <button class="btn btn-outline">📥 Import CSV</button>
        <small class="text-muted">Format: <code>tahun_akademik;peminat;daya_tampung;maba;jumlah_mahasiswa;lulus</code></small>
    </form>
</div>

<div style="display:grid;grid-template-columns:1.2fr 1.6fr;gap:24px;">
    <div class="card"><h3 style="margin-bottom:16px;">📈 Tren Peminat vs Maba</h3>
        <div style="height:300px;"><canvas id="cStat"></canvas></div></div>
    <div class="card">
        <h3 style="margin-bottom:16px;">📋 Riwayat & Rasio</h3>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>TA</th><th>Peminat</th><th>Maba</th><th>Ketetatan</th><th>Keterisian</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada data.</td></tr><?php endif; ?>
                <?php foreach ($list as $s): ?>
                    <tr>
                        <td><strong><?= Security::e($s['tahun_akademik']) ?></strong></td>
                        <td><?= (int)$s['peminat'] ?></td>
                        <td><?= (int)$s['maba'] ?></td>
                        <td><?= $s['maba'] > 0 ? round($s['peminat'] / $s['maba'], 1) . ' : 1' : '—' ?></td>
                        <td><?= $s['daya_tampung'] > 0 ? round($s['maba'] / $s['daya_tampung'] * 100) . '%' : '—' ?></td>
                        <td style="display:flex;gap:6px;">
                            <form method="POST"><input type="hidden" name="id_stat" value="<?= $s['id_stat'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="matriks"><button class="btn btn-primary" style="padding:4px 10px;font-size:11px;">📌 K3</button></form>
                            <form method="POST" onsubmit="return confirm('Hapus?');"><input type="hidden" name="id_stat" value="<?= $s['id_stat'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('cStat'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($list, 'tahun_akademik')) ?: '["-"]' ?>,
        datasets: [
            { label: 'Peminat', data: <?= json_encode(array_map(fn($s) => (int)$s['peminat'], $list)) ?: '[0]' ?>, backgroundColor: '#0F3D5C', borderRadius: 8 },
            { label: 'Maba', data: <?= json_encode(array_map(fn($s) => (int)$s['maba'], $list)) ?: '[0]' ?>, backgroundColor: '#C9A227', borderRadius: 8 }
        ] },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>