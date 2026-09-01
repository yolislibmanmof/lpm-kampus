<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$belum = $db->prepare("SELECT s.kode_standar, s.nama_standar, ROUND(AVG(e.capaian),1) c, ROUND(AVG(e.target),1) t
    FROM standar_mutu s JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.tahun = ?
    GROUP BY s.id_standar HAVING c < t ORDER BY c ASC");
$belum->execute([$tahun]);
$belumList = $belum->fetchAll();

$mayor = $db->query("SELECT t.*, p.nama_prodi, s.kode_standar FROM temuan_audit t
    JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
    LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
    LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar
    WHERE t.kategori = 'Mayor' AND t.status_verifikasi != 'Diterima'")->fetchAll();

$logo = Site::setting('logo_path');
$brand = Site::setting('brand_utama', 'LPM') . ' ' . Site::setting('brand_aksen', 'Kampus');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan RTM <?= $tahun ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; padding: 40px; font-size: 13px; }
        .kop { display: flex; gap: 16px; align-items: center; border-bottom: 4px double #0F3D5C; padding-bottom: 14px; margin-bottom: 24px; }
        .kop img { width: 62px; height: 62px; object-fit: cover; border-radius: 8px; }
        .kop .t h2 { margin: 0; color: #0F3D5C; font-size: 19px; }
        .kop .t p { margin: 2px 0 0; font-size: 11px; color: #444; }
        h3.judul { text-align: center; text-decoration: underline; color: #0F3D5C; margin: 20px 0 4px; font-size: 15px; }
        p.sub { text-align: center; margin: 0 0 22px; font-size: 12px; }
        h4 { color: #0F3D5C; margin: 22px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #444; padding: 7px 9px; text-align: left; vertical-align: top; }
        th { background: #E8EEF4; }
        .sig { display: flex; justify-content: space-between; margin-top: 52px; }
        .sig div { text-align: center; width: 40%; }
        .sig .space { height: 70px; }
        .noprint { position: fixed; top: 12px; right: 12px; }
        .noprint button { padding: 10px 20px; background: #0F3D5C; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; }
        @media print { .noprint { display: none; } body { padding: 10px; } }
    </style>
</head>
<body>
<div class="noprint"><button onclick="window.print()">🖨️ Cetak / Simpan PDF</button></div>

<div class="kop">
    <?php if ($logo): ?><img src="/uploads/<?= Security::e($logo) ?>"><?php endif; ?>
    <div class="t">
        <h2><?= Security::e($brand) ?></h2>
        <p>Lembaga Penjaminan Mutu — Gedung Rektorat Lt. 3 • lpm@kampus.ac.id</p>
    </div>
</div>

<h3 class="judul">BAHAN RAPAT TINJAUAN MANAJEMEN (RTM)</h3>
<p class="sub">Siklus PPEPP Tahun <?= $tahun ?></p>

<h4>A. Standar yang Belum Tercapai</h4>
<table>
    <thead><tr><th>Standar</th><th>Nama Standar</th><th>Capaian</th><th>Target</th><th>Rekomendasi Perbaikan</th></tr></thead>
    <tbody>
    <?php if (empty($belumList)): ?><tr><td colspan="5" style="text-align:center;">Seluruh standar tercapai. 🎉</td></tr><?php endif; ?>
    <?php foreach ($belumList as $b): ?>
        <tr>
            <td><?= Security::e($b['kode_standar']) ?></td>
            <td><?= Security::e($b['nama_standar']) ?></td>
            <td><?= $b['c'] ?>%</td>
            <td><?= $b['t'] ?>%</td>
            <td>Perlu tindakan perbaikan terstruktur & alokasi sumber daya.</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h4>B. Temuan Mayor yang Belum Tertutup</h4>
<table>
    <thead><tr><th>Prodi</th><th>Standar</th><th>Deskripsi Temuan</th><th>Status Koreksi</th></tr></thead>
    <tbody>
    <?php if (empty($mayor)): ?><tr><td colspan="4" style="text-align:center;">Tidak ada temuan mayor terbuka.</td></tr><?php endif; ?>
    <?php foreach ($mayor as $m): ?>
        <tr>
            <td><?= Security::e($m['nama_prodi']) ?></td>
            <td><?= Security::e($m['kode_standar']) ?></td>
            <td><?= Security::e($m['deskripsi_temuan']) ?></td>
            <td><?= Security::e($m['status_verifikasi']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h4>C. Keputusan Rapat</h4>
<table>
    <tr><td style="height:60px;">1.</td></tr>
    <tr><td style="height:60px;">2.</td></tr>
    <tr><td style="height:60px;">3.</td></tr>
</table>

<div class="sig">
    <div>Mengetahui,<br>Rektor<br><div class="space"></div><strong>( ................................ )</strong></div>
    <div>Maumere, <?= date('d F Y') ?><br>Ketua LPM,<br><div class="space"></div><strong>( ................................ )</strong></div>
</div>

<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>
</body>
</html>