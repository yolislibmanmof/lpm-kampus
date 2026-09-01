<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2, 3, 4]);
$db = Database::getInstance();

$list = $db->query("SELECT * FROM instrumen_tahun ORDER BY tahun DESC")->fetchAll();
$selected = (int)($_GET['id'] ?? 0);
if (!$selected) {
    foreach ($list as $l) { if ($l['status'] === 'Aktif') { $selected = $l['id_instrumen_tahun']; break; } }
    if (!$selected && $list) $selected = $list[0]['id_instrumen_tahun'];
}
$current = null;
foreach ($list as $l) if ($l['id_instrumen_tahun'] == $selected) $current = $l;

$grup = [];
if ($current) {
    $b = $db->prepare("SELECT b.*, s.kode_standar, s.nama_standar FROM instrumen_butir b JOIN standar_mutu s ON b.id_standar = s.id_standar WHERE b.id_instrumen_tahun = ? ORDER BY s.kode_standar, b.urutan, b.id_butir");
    $b->execute([$selected]);
    foreach ($b->fetchAll() as $x) $grup[$x['kode_standar']][] = $x;
}

$simTitle = 'Instrumen AMI';
$activeMenu = 'instrumen';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div class="card" style="margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
    <div>
        <h3>🧾 Instrumen Audit Mutu Internal</h3>
        <p class="text-muted" style="font-size:13px;">Acuan resmi butir penilaian untuk siklus terpilih.</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" style="display:flex;gap:10px;align-items:center;">
            <select name="id" class="form-control" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ($list as $l): ?>
                    <option value="<?= $l['id_instrumen_tahun'] ?>" <?= $selected == $l['id_instrumen_tahun'] ? 'selected' : '' ?>><?= Security::e($l['nama_siklus']) ?> (<?= $l['status'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </form>
        <button class="btn btn-gold" onclick="window.print()">🖨️ Cetak</button>
    </div>
</div>

<?php if (!$current): ?>
    <div class="card" style="text-align:center;"><p class="text-muted">Belum ada instrumen tersedia.</p></div>
<?php endif; ?>

<?php foreach ($grup as $kode => $items): ?>
<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-bottom:14px;">📌 Standar <?= Security::e($kode) ?> — <?= Security::e($items[0]['nama_standar']) ?></h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th style="width:40px;">No</th><th>Butir Penilaian</th><th>Metode</th><th>Dokumen yang Diperlukan</th></tr></thead>
            <tbody>
            <?php foreach ($items as $i => $b): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= Security::e($b['butir']) ?></td>
                    <td><?= Security::e($b['metode']) ?></td>
                    <td><?= Security::e($b['dokumen_diperlukan']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>