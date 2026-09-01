<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([2]);
$db = Database::getInstance();

$data = $db->query("SELECT a.*, p.nama_prodi, f.nama_fakultas FROM akreditasi a
    LEFT JOIN prodi p ON a.id_prodi = p.id_prodi
    LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
    ORDER BY a.masa_berlaku ASC")->fetchAll();

$simTitle = 'Status Akreditasi';
$activeMenu = 'akreditasi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div class="card" style="margin-bottom:24px;">
    <p class="text-muted">🔔 Sistem menandai otomatis: <span class="badge badge-unggul">Aman</span> <span class="badge badge-b">Segera Berakhir (&lt; 1 tahun)</span> <span class="badge badge-unggul" style="background:#FEE2E2;color:#991B1B;">Kedaluwarsa</span></p>
</div>

<div class="table-wrapper">
    <table>
        <thead><tr><th>Prodi / Institusi</th><th>Peringkat</th><th>Lembaga</th><th>No. SK</th><th>Berlaku Hingga</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($data as $a):
            $sisa = strtotime($a['masa_berlaku']) - time();
            $status = $sisa < 0 ? ['Kedaluwarsa','background:#FEE2E2;color:#991B1B'] : ($sisa < 365*86400 ? ['Segera Berakhir','background:#FEF3C7;color:#92400E'] : ['Aman','background:#D1FAE5;color:#065F46']);
        ?>
            <tr>
                <td><strong><?= Security::e($a['nama_prodi'] ?? 'INSTITUSI') ?></strong><br><small class="text-muted"><?= Security::e($a['nama_fakultas'] ?? '') ?></small></td>
                <td><span class="badge badge-a"><?= Security::e($a['peringkat']) ?></span></td>
                <td><?= Security::e($a['lembaga']) ?></td>
                <td><?= Security::e($a['no_sk']) ?></td>
                <td><?= date('d M Y', strtotime($a['masa_berlaku'])) ?></td>
                <td><span class="badge" style="<?= $status[1] ?>"><?= $status[0] ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>