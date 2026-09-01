<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$kriteria = $db->query("SELECT * FROM kriteria_akreditasi ORDER BY nomor")->fetchAll();

$b = $db->prepare("SELECT id_prodi, id_kriteria, status FROM bukti_kriteria WHERE tahun = ?");
$b->execute([$tahun]);
$map = [];
foreach ($b->fetchAll() as $r) {
    $map[$r['id_prodi']][$r['id_kriteria']]['tot'] = ($map[$r['id_prodi']][$r['id_kriteria']]['tot'] ?? 0) + 1;
    if ($r['status'] === 'Lengkap') $map[$r['id_prodi']][$r['id_kriteria']]['l'] = ($map[$r['id_prodi']][$r['id_kriteria']]['l'] ?? 0) + 1;
}

function cellPct($map, $pid, $kid) {
    $d = $map[$pid][$kid] ?? null;
    if (!$d) return null;
    return round(($d['l'] ?? 0) / $d['tot'] * 100);
}
function cellColor($p) {
    if ($p === null) return 'background:var(--bg-light);color:var(--text-muted)';
    if ($p >= 80) return 'background:#D1FAE5;color:#065F46';
    if ($p >= 50) return 'background:#FEF3C7;color:#92400E';
    return 'background:#FEE2E2;color:#991B1B';
}

$simTitle = 'Monitor Kesiapan Akreditasi';
$activeMenu = 'monmatriks';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
        <h2 style="font-size:22px;color:var(--primary-dark);">🗂️ Peta Kesiapan Bukti <?= $tahun ?></h2>
        <p class="text-muted" style="font-size:13px;">Hijau ≥80% • Kuning ≥50% • Merah &lt;50% • Abu = belum dimulai</p>
    </div>
    <form method="GET"><select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <?php for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--): ?><option <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?>
    </select></form>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Program Studi</th><?php foreach ($kriteria as $k): ?><th style="text-align:center;">K<?= $k['nomor'] ?></th><?php endforeach; ?><th style="text-align:center;">Rata-rata</th></tr></thead>
            <tbody>
            <?php foreach ($prodiList as $p):
                $sums = [];
            ?>
                <tr>
                    <td><strong><?= Security::e($p['nama_prodi']) ?></strong></td>
                    <?php foreach ($kriteria as $k): $pc = cellPct($map, $p['id_prodi'], $k['id_kriteria']); if ($pc !== null) $sums[] = $pc; ?>
                        <td style="text-align:center;"><span class="badge" style="<?= cellColor($pc) ?>;min-width:48px;"><?= $pc === null ? '—' : $pc . '%' ?></span></td>
                    <?php endforeach; ?>
                    <td style="text-align:center;">
                        <?php $avg = $sums ? round(array_sum($sums) / count($sums)) : null; ?>
                        <strong style="font-size:16px;color:<?= $avg === null ? 'var(--text-muted)' : ($avg >= 80 ? 'var(--success)' : ($avg >= 50 ? 'var(--warning)' : 'var(--danger)')) ?>;"><?= $avg === null ? '—' : $avg . '%' ?></strong>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="font-size:12.5px;margin-top:14px;">💡 Gunakan tabel ini saat rapat LPM: prodi merah = jadwalkan pendampingan borang lebih awal.</p>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>