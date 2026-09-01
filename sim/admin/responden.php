<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="responden_akreditasi_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBFtipe;nama;identitas;prodi_unit;wa;email;ketersediaan;catatan\n";
    foreach ($db->query("SELECT r.*, p.nama_prodi FROM responden_akreditasi r LEFT JOIN prodi p ON r.id_prodi = p.id_prodi ORDER BY r.tipe, r.nama")->fetchAll() as $r) {
        echo implode(';', array_map(fn($v) => str_replace(';', ',', (string)$v), [
            $r['tipe'], $r['nama'], $r['identitas'], $r['unit_kerja'] ?? $r['nama_prodi'],
            $r['no_wa'], $r['email'], $r['ketersediaan'], $r['catatan']
        ])) . "\n";
    }
    exit;
}

if (($_POST['action'] ?? '') === 'delete') {
    Security::verifyCsrf();
    $db->prepare("DELETE FROM responden_akreditasi WHERE id_responden=?")->execute([(int)$_POST['id_responden']]);
}

$counts = [];
foreach ($db->query("SELECT tipe, COUNT(*) c FROM responden_akreditasi GROUP BY tipe")->fetchAll() as $r) $counts[$r['tipe']] = (int)$r['c'];
$kesiap = $db->query("SELECT ketersediaan, COUNT(*) c FROM responden_akreditasi GROUP BY ketersediaan")->fetchAll();

$tipeF = $_GET['tipe'] ?? 'all';
$q = "SELECT r.*, p.nama_prodi FROM responden_akreditasi r LEFT JOIN prodi p ON r.id_prodi = p.id_prodi";
$params = [];
if ($tipeF !== 'all') { $q .= " WHERE r.tipe = ?"; $params[] = $tipeF; }
$q .= " ORDER BY r.tipe, r.nama";
$stmt = $db->prepare($q); $stmt->execute($params);
$list = $stmt->fetchAll();

$simTitle = 'Responden Wawancara';
$activeMenu = 'responden';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <h2 style="font-size:22px;color:var(--primary-dark);">🎤 Pusat Responden Akreditasi</h2>
    <a href="?export=1" class="btn btn-outline">📤 Export CSV</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon gold">🎓</div><div><h3 style="font-size:28px;"><?= $counts['Alumni'] ?? 0 ?></h3><p class="text-muted">Alumni</p></div></div>
    <div class="stat-card"><div class="stat-icon blue">🧑🎓</div><div><h3 style="font-size:28px;"><?= $counts['Mahasiswa'] ?? 0 ?></h3><p class="text-muted">Mahasiswa</p></div></div>
    <div class="stat-card"><div class="stat-icon green">🧑🏫</div><div><h3 style="font-size:28px;"><?= $counts['Dosen'] ?? 0 ?></h3><p class="text-muted">Dosen</p></div></div>
    <div class="stat-card"><div class="stat-icon red">🧑💼</div><div><h3 style="font-size:28px;"><?= $counts['Tendik'] ?? 0 ?></h3><p class="text-muted">Tendik</p></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;margin-bottom:28px;">
    <div class="card"><h3 style="margin-bottom:16px;">Kesiapan Responden</h3><canvas id="cKesiap"></canvas></div>
    <div class="card">
        <h3 style="margin-bottom:12px;">💡 Tips Sesi Wawancara</h3>
        <p class="text-muted" style="font-size:14px;">Kelompokkan undangan per kategori saat asesor meminta sesi: <strong>Alumni</strong> (bahasan relevansi kurikulum), <strong>Mahasiswa</strong> (layanan & pembelajaran), <strong>Dosen</strong> (penelitian & PPEPP), <strong>Tendik</strong> (dukungan layanan: pustaka, lab, IT, sarpras). Tombol 💬 WA siap dipakai untuk undangan massal.</p>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3>📋 Daftar Responden (<?= count($list) ?>)</h3>
        <form method="GET"><select name="tipe" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="all">Semua Kategori</option>
            <?php foreach (['Alumni','Mahasiswa','Dosen','Tendik'] as $t): ?><option <?= $tipeF === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
        </select></form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Kategori</th><th>Nama</th><th>Prodi / Unit</th><th>Ketersediaan</th><th>Kontak</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada pendaftar. Sebarkan tautan /publik/registrasi-wawancara.php</td></tr><?php endif; ?>
            <?php foreach ($list as $r): ?>
                <tr>
                    <td><span class="badge <?= ['Alumni'=>'badge-unggul','Mahasiswa'=>'badge-a','Dosen'=>'badge-b','Tendik'=>'badge-baik'][$r['tipe']] ?>"><?= $r['tipe'] ?></span></td>
                    <td><strong><?= Security::e($r['nama']) ?></strong><br><small class="text-muted"><?= Security::e($r['identitas']) ?></small></td>
                    <td><?= Security::e($r['unit_kerja'] ?? $r['nama_prodi'] ?? '—') ?></td>
                    <td><?= Security::e($r['ketersediaan']) ?></td>
                    <td><a class="btn btn-primary" style="padding:5px 14px;font-size:12px;background:var(--success);" target="_blank" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $r['no_wa']) ?>">💬 WA</a></td>
                    <td><form method="POST" onsubmit="return confirm('Hapus responden?');"><input type="hidden" name="id_responden" value="<?= $r['id_responden'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('cKesiap'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_column($kesiap, 'ketersediaan')) ?: '["-"]' ?>,
            datasets: [{ data: <?= json_encode(array_column($kesiap, 'c')) ?: '[0]' ?>, backgroundColor: ['#10B981','#3B82F6','#F59E0B','#64748B'], borderWidth: 3, borderColor: '#fff' }] },
    options: { plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>