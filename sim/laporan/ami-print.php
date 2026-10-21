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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0F3D5C;
            --accent: #C9A227;
            --text-dark: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
        }
        body { 
            font-family: 'Plus Jakarta Sans', Arial, sans-serif; 
            color: var(--text-dark); 
            padding: 40px; 
            font-size: 12px; 
            line-height: 1.5;
            background: #fff;
        }
        .kop { 
            display: flex; gap: 20px; align-items: center; 
            border-bottom: 3px double var(--primary); 
            padding-bottom: 16px; margin-bottom: 32px; 
        }
        .kop img { width: 70px; height: 70px; object-fit: contain; }
        .kop .t h2 { 
            margin: 0; color: var(--primary); font-size: 20px; 
            font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase;
        }
        .kop .t p { margin: 4px 0 0; font-size: 12px; color: var(--text-muted); font-weight: 500; }
        
        h3.judul { 
            text-align: center; color: var(--primary); margin: 0 0 4px; 
            font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
        }
        p.sub { text-align: center; margin: 0 0 28px; font-size: 13px; color: var(--text-muted); font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 12px; }
        th, td { border: 1px solid var(--border); padding: 8px 12px; text-align: left; vertical-align: top; }
        th { 
            background: #F8FAFC; color: var(--primary); font-weight: 700; 
            text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;
        }
        .info-table td { border: none; padding: 4px 8px; font-size: 12px; }
        .info-table td:first-child { font-weight: 600; color: var(--text-muted); width: 160px; }
        
        .rekap-box {
            background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px;
            padding: 12px 16px; margin: 20px 0; font-size: 12px; font-weight: 600;
            color: var(--primary); display: flex; gap: 24px; flex-wrap: wrap;
        }
        .rekap-item strong { color: var(--accent); }
        
        .sig { display: flex; justify-content: space-between; margin-top: 60px; page-break-inside: avoid; }
        .sig div { text-align: center; width: 40%; }
        .sig .space { height: 80px; }
        .sig strong { border-bottom: 1px solid var(--text-dark); padding-bottom: 4px; display: inline-block; min-width: 200px; }
        
        .kat-mayor { color: #DC2626; font-weight: 700; }
        .kat-minor { color: #D97706; font-weight: 700; }
        .kat-obs { color: #2563EB; font-weight: 700; }
        .kat-baik { color: #059669; font-weight: 700; }

        .noprint { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .noprint button { 
            padding: 12px 24px; background: var(--primary); color: #fff; border: none; 
            border-radius: 8px; cursor: pointer; font-weight: 700; font-family: inherit;
            font-size: 13px; box-shadow: 0 4px 12px rgba(15, 61, 92, 0.3); transition: all 0.2s;
        }
        .noprint button:hover { background: var(--accent); color: var(--primary); transform: translateY(-2px); }

        @media print { 
            .noprint { display: none !important; } 
            body { padding: 0; font-size: 11px; }
            .kop { border-bottom-color: #000; }
            .kop .t h2 { color: #000; }
            th { background: #eee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .rekap-box { background: #f9f9f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 20mm 20mm 25mm 20mm; size: A4; }
        }
    </style>
</head>
<body>
<div class="noprint"><button onclick="window.print()">🖨️ Cetak / Simpan PDF</button></div>

<div class="kop">
    <?php if ($logo): ?><img src="/uploads/<?= Security::e($logo) ?>" alt="Logo"><?php endif; ?>
    <div class="t">
        <h2><?= Security::e($brand) ?></h2>
        <p>Lembaga Penjaminan Mutu — Gedung Rektorat Lt. 3 • lpm@kampus.ac.id</p>
    </div>
</div>

<h3 class="judul">Laporan Audit Mutu Internal (AMI)</h3>
<p class="sub">Tahun Akademik <?= Security::e($t['tahun_ami']) ?></p>

<table class="info-table">
    <tr><td>Program Studi</td><td>: <strong><?= Security::e($t['nama_prodi']) ?></strong></td></tr>
    <tr><td>Fakultas</td><td>: <?= Security::e($t['nama_fakultas']) ?></td></tr>
    <tr><td>Tanggal Audit</td><td>: <?= date('d F Y', strtotime($t['tanggal_audit'])) ?></td></tr>
    <tr><td>Auditor</td><td>: <?= Security::e($t['auditor']) ?></td></tr>
    <tr><td>Status Audit</td><td>: <?= Security::e($t['status']) ?></td></tr>
</table>

<table>
    <thead>
        <tr>
            <th style="width:80px;">Standar</th>
            <th>Nama Standar</th>
            <th style="width:100px;">Kategori</th>
            <th>Deskripsi / Temuan</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($temuanList)): ?>
        <tr><td colspan="4" style="text-align:center; padding: 20px; color: var(--text-muted);">Tidak ada temuan tercatat. Kinerja sangat baik!</td></tr>
    <?php else: ?>
        <?php foreach ($temuanList as $x): 
            $katClass = match($x['kategori']) {
                'Mayor' => 'kat-mayor',
                'Minor' => 'kat-minor',
                'Observasi' => 'kat-obs',
                default => 'kat-baik'
            };
        ?>
            <tr>
                <td style="font-weight:700;"><?= Security::e($x['kode_standar']) ?></td>
                <td><?= Security::e($x['nama_standar']) ?></td>
                <td class="<?= $katClass ?>"><?= Security::e($x['kategori']) ?></td>
                <td><?= nl2br(Security::e($x['deskripsi_temuan'] ?: '-')) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<div class="rekap-box">
    <span class="rekap-item">🔴 Mayor: <strong><?= $rekap['Mayor'] ?></strong></span>
    <span class="rekap-item">🟡 Minor: <strong><?= $rekap['Minor'] ?></strong></span>
    <span class="rekap-item">🔵 Observasi: <strong><?= $rekap['Observasi'] ?></strong></span>
    <span class="rekap-item">🟢 Kondisi Baik: <strong><?= $rekap['Kondisi Baik'] ?></strong></span>
</div>

<div class="sig">
    <div>
        Mengetahui,<br>
        Ketua Program Studi<br>
        <div class="space"></div>
        <strong>( ........................................... )</strong><br>
        <span style="font-size:11px; color:var(--text-muted);">NIP/NIDN. ..........................</span>
    </div>
    <div>
        Maumere, <?= date('d F Y') ?><br>
        Auditor,<br>
        <div class="space"></div>
        <strong><?= Security::e($t['auditor']) ?></strong><br>
        <span style="font-size:11px; color:var(--text-muted);">NIP/NIDN. ..........................</span>
    </div>
</div>
</body>
</html>