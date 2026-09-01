<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([2]);
$db = Database::getInstance();

$kategori = $_GET['kategori'] ?? 'all';
$verif = $_GET['verif'] ?? 'all';

/* ===== HEATMAP (BARU v3) ===== */
$prodiHm = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$hmRows = $db->query("SELECT pa.id_prodi, t.kategori, COUNT(*) n FROM temuan_audit t JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas GROUP BY pa.id_prodi, t.kategori")->fetchAll();
$hmMap = [];
foreach ($hmRows as $r) $hmMap[$r['id_prodi']][$r['kategori']] = (int)$r['n'];

function heatColor($kat, $n) {
    if ($n === 0) return 'background:#ECFDF5;color:#065F46';
    if ($kat === 'Mayor')  return $n >= 3 ? 'background:#DC2626;color:#fff' : 'background:#F87171;color:#fff';
    if ($kat === 'Minor')  return $n >= 3 ? 'background:#D97706;color:#fff' : 'background:#FCD34D;color:#78350F';
    if ($kat === 'Observasi') return 'background:#DBEAFE;color:#1E40AF';
    return 'background:#A7F3D0;color:#065F46'; // Kondisi Baik
}

/* ===== Tabel filter (LAMA) ===== */
$q = "SELECT t.*, p.nama_prodi, s.kode_standar, u.nama_lengkap auditor FROM temuan_audit t
      JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
      LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
      LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar
      LEFT JOIN users u ON pa.id_auditor = u.id_user WHERE 1=1";
$params = [];
if ($kategori !== 'all') { $q .= " AND t.kategori = :k"; $params[':k'] = $kategori; }
if ($verif !== 'all') { $q .= " AND t.status_verifikasi = :v"; $params[':v'] = $verif; }
$q .= " ORDER BY t.id_temuan DESC";
$stmt = $db->prepare($q);
$stmt->execute($params);
$list = $stmt->fetchAll();

$simTitle = 'Temuan Audit';
$activeMenu = 'temuan';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<!-- 🗺️ HEATMAP (BARU) -->
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:6px;">🗺️ Peta Panas Temuan per Prodi</h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:16px;">Satu pandangan untuk membaca "prodi mana yang paling perlu perhatian" — merah = prioritas pembinaan.</p>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Program Studi</th><th style="text-align:center;">🔴 Mayor</th><th style="text-align:center;">🟡 Minor</th><th style="text-align:center;">🔵 Observasi</th><th style="text-align:center;">🟢 Kondisi Baik</th></tr></thead>
            <tbody>
            <?php foreach ($prodiHm as $p): ?>
                <tr>
                    <td><strong><?= Security::e($p['nama_prodi']) ?></strong></td>
                    <?php foreach (['Mayor','Minor','Observasi','Kondisi Baik'] as $kat): $n = $hmMap[$p['id_prodi']][$kat] ?? 0; ?>
                        <td style="text-align:center;"><span class="badge" style="<?= heatColor($kat, $n) ?>;min-width:44px;"><?= $n ?></span></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="font-size:12px;margin-top:12px;">Legenda: <span class="badge" style="background:#ECFDF5;color:#065F46">0 = bersih</span> <span class="badge" style="background:#F87171;color:#fff">1–2 mayor</span> <span class="badge" style="background:#DC2626;color:#fff">≥3 mayor = prioritas</span></p>
</div>

<!-- Filter + tabel (LAMA) -->
<form method="GET" style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
    <select name="kategori" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="all">Semua Kategori</option>
        <?php foreach (['Mayor','Minor','Observasi','Kondisi Baik'] as $k): ?><option <?= $kategori === $k ? 'selected' : '' ?>><?= $k ?></option><?php endforeach; ?>
    </select>
    <select name="verif" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="all">Semua Status</option>
        <?php foreach (['Menunggu','Diterima','Ditolak'] as $v): ?><option <?= $verif === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
    </select>
</form>

<div class="table-wrapper">
    <table>
        <thead><tr><th>Prodi</th><th>Standar</th><th>Kategori</th><th>Temuan</th><th>Koreksi</th><th>Verifikasi</th></tr></thead>
        <tbody>
        <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:40px;" class="text-muted">Tidak ada temuan.</td></tr><?php endif; ?>
        <?php foreach ($list as $t): ?>
            <tr>
                <td><strong><?= Security::e($t['nama_prodi']) ?></strong><br><small class="text-muted">Auditor: <?= Security::e($t['auditor']) ?></small></td>
                <td><?= Security::e($t['kode_standar']) ?></td>
                <td><span class="badge" style="<?= match($t['kategori']) { 'Mayor' => 'background:#FEE2E2;color:#991B1B', 'Minor' => 'background:#FEF3C7;color:#92400E', 'Observasi' => 'background:#DBEAFE;color:#1E40AF', default => 'background:#D1FAE5;color:#065F46' } ?>"><?= Security::e($t['kategori']) ?></span></td>
                <td style="max-width:250px;font-size:13px;"><?= Security::e(mb_strimwidth($t['deskripsi_temuan'], 0, 100, '...')) ?></td>
                <td style="max-width:200px;font-size:13px;"><?= Security::e(mb_strimwidth($t['tindakan_koreksi'] ?? '—', 0, 80, '...')) ?></td>
                <td><span class="badge <?= $t['status_verifikasi'] === 'Diterima' ? 'badge-unggul' : 'badge-b' ?>"><?= Security::e($t['status_verifikasi']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>