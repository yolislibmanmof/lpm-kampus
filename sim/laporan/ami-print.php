<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2, 4]);
$db = Database::getInstance();

$stmt = $db->prepare("SELECT pa.*, p.nama_prodi, f.nama_fakultas, u.nama_lengkap AS auditor, j.tahun_ami
    FROM penugasan_audit pa
    LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
    LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
    LEFT JOIN users u ON pa.id_auditor = u.id_user
    LEFT JOIN jadwal_ami j ON pa.id_jadwal = j.id_jadwal
    WHERE pa.id_tugas = ?");
$stmt->execute([(int)($_GET['tugas'] ?? 0)]);
$t = $stmt->fetch();
if (!$t) { exit('Laporan tidak ditemukan.'); }

$temuan = $db->prepare("SELECT t.*, s.kode_standar, s.nama_standar FROM temuan_audit t LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar WHERE t.id_tugas = ? ORDER BY s.kode_standar");
$temuan->execute([$t['id_tugas']]);
$temuanList = $temuan->fetchAll();
$rekap = ['Mayor' => 0, 'Minor' => 0, 'Observasi' => 0, 'Kondisi Baik' => 0];
foreach ($temuanList as $x) $rekap[$x['kategori']] = ($rekap[$x['kategori']] ?? 0) + 1;

$logo = Site::setting('logo_path');
$brand = Site::setting('brand_utama', 'LPM') . ' ' . Site::setting('brand_aksen', 'Kampus');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan AMI — <?= Security::e($t['nama_prodi']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; padding: 40px; font-size: 13px; }
        .kop { display: flex; gap: 16px; align-items: center; border-bottom: 4px double #0F3D5C; padding-bottom: 14px; margin-bottom: 24px; }
        .kop img { width: 62px; height: 62px; object-fit: cover; border-radius: 8px; }
        .kop .t h2 { margin: 0; color: #0F3D5C; font-size: 19px; }
        .kop .t p { margin: 2px 0 0; font-size: 11px; color: #444; }
        h3.judul { text-align: center; text-decoration: underline; color: #0F3D5C; margin: 20px 0 4px; font-size: 15px; }
        p.sub { text-align: center; margin: 0 0 22px; font-size: 12px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 14px 0; }
        th, td { border: 1px solid #444; padding: 7px 9px; text-align: left; vertical-align: top; }
        th { background: #E8EEF4; }
        .info td { border: none; padding: 2px 4px; }
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

<h3 class="judul">LAPORAN AUDIT MUTU INTERNAL (AMI)</h3>
<p class="sub">Tahun Akademik <?= $t['tahun_ami'] ?></p>

<table class="info">
    <tr><td style="width:160px;">Program Studi</td><td>: <strong><?= Security::e($t['nama_prodi']) ?></strong></td></tr>
    <tr><td>Fakultas</td><td>: <?= Security::e($t['nama_fakultas']) ?></td></tr>
    <tr><td>Tanggal Audit</td><td>: <?= date('d F Y', strtotime($t['tanggal_audit'])) ?></td></tr>
    <tr><td>Auditor</td><td>: <?= Security::e($t['auditor']) ?></td></tr>
    <tr><td>Status Audit</td><td>: <?= Security::e($t['status']) ?></td></tr>
</table>

<table>
    <thead><tr><th style="width:70px;">Standar</th><th>Nama Standar</th><th style="width:90px;">Kategori</th><th>Deskripsi / Temuan</th></tr></thead>
    <tbody>
    <?php if (empty($temuanList)): ?><tr><td colspan="4" style="text-align:center;">Tidak ada temuan tercatat.</td></tr><?php endif; ?>
    <?php foreach ($temuanList as $x): ?>
        <tr>
            <td><?= Security::e($x['kode_standar']) ?></td>
            <td><?= Security::e($x['nama_standar']) ?></td>
            <td><strong><?= Security::e($x['kategori']) ?></strong></td>
            <td><?= Security::e($x['deskripsi_temuan'] ?: '-') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<p><strong>Rekapitulasi:</strong>
    Mayor: <?= $rekap['Mayor'] ?> • Minor: <?= $rekap['Minor'] ?> •
    Observasi: <?= $rekap['Observasi'] ?> • Kondisi Baik: <?= $rekap['Kondisi Baik'] ?>
</p>

<div class="sig">
    <div>Mengetahui,<br>Ketua Program Studi<br><div class="space"></div><strong>( ................................ )</strong></div>
    <div>Maumere, <?= date('d F Y') ?><br>Auditor,<br><div class="space"></div><strong><?= Security::e($t['auditor']) ?></strong></div>
</div>

<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>
</body>
</html>