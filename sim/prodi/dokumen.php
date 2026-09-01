<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();

$dokumen = $db->query("SELECT d.*, k.nama_kategori FROM dokumen_mutu d LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori WHERE d.status = 'Aktif' ORDER BY d.kode_dokumen")->fetchAll();

$simTitle = 'Dokumen Mutu';
$activeMenu = 'dokumen';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div class="card" style="margin-bottom:24px;">
    <p class="text-muted">📚 Unduh kebijakan, manual mutu, standar, dan formulir resmi LPM di bawah ini sebagai referensi penyusunan EDP & borang.</p>
</div>

<div class="table-wrapper">
    <table>
        <thead><tr><th>Kode</th><th>Judul Dokumen</th><th>Kategori</th><th>Versi</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (empty($dokumen)): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;" class="text-muted">Belum ada dokumen mutu.</td></tr>
        <?php endif; ?>
        <?php foreach ($dokumen as $d): ?>
            <tr>
                <td><?= Security::e($d['kode_dokumen']) ?></td>
                <td><strong><?= Security::e($d['judul_dokumen']) ?></strong></td>
                <td><?= Security::e($d['nama_kategori']) ?></td>
                <td>v<?= $d['versi'] ?></td>
                <td><a href="/download.php?id=<?= $d['id_dokumen'] ?>" class="btn btn-primary" style="padding:6px 16px;font-size:12px;">📥 Unduh</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>