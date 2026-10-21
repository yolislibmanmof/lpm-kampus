<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// 1. Standar belum tercapai (menggunakan subquery untuk kompatibilitas MySQL)
$belum = $db->prepare("SELECT * FROM (
    SELECT s.kode_standar, s.nama_standar, ROUND(AVG(e.capaian), 1) AS c, ROUND(AVG(e.target), 1) AS t
    FROM standar_mutu s 
    JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.tahun = ?
    GROUP BY s.id_standar, s.kode_standar, s.nama_standar
) AS subquery
WHERE c < t
ORDER BY (t - c) DESC");
$belum->execute([$tahun]);
$belumList = $belum->fetchAll();

// 2. Temuan mayor terbuka
$mayor = $db->prepare("SELECT t.*, p.nama_prodi, s.kode_standar 
    FROM temuan_audit t
    JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
    LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
    LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar
    WHERE t.kategori = 'Mayor' AND t.status_verifikasi != 'Diterima' 
    ORDER BY t.id_temuan DESC");
$mayor->execute();
$mayorList = $mayor->fetchAll();

// 3. Rekomendasi AI Tahun Ini (Prioritas Tinggi)
$reks = $db->prepare("SELECT * FROM rekomendasi_ai WHERE tahun = ? AND prioritas = 'Tinggi' ORDER BY id_rekomendasi LIMIT 5");
$reks->execute([$tahun]);
$reksList = $reks->fetchAll();

$logo = Site::setting('logo_path');
$brand = Site::setting('brand_utama', 'LPM') . ' ' . Site::setting('brand_aksen', 'Kampus');

// Statistik untuk rekap
$totalStandar = count($belumList);
$totalMayor = count($mayorList);
$totalReks = count($reksList);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan RTM <?= $tahun ?> — <?= Security::e($brand) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0F3D5C;
            --accent: #C9A227;
            --danger: #EF4444;
            --warning: #F59E0B;
            --success: #10B981;
            --text-dark: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
        }
        body { 
            font-family: 'Plus Jakarta Sans', Arial, sans-serif; 
            color: var(--text-dark); 
            padding: 40px; 
            font-size: 12px; 
            line-height: 1.6;
            background: #fff;
        }
        
        /* KOP SURAT */
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
        
        /* JUDUL DOKUMEN */
        h3.judul { 
            text-align: center; color: var(--primary); margin: 0 0 4px; 
            font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
        }
        p.sub { text-align: center; margin: 0 0 28px; font-size: 13px; color: var(--text-muted); font-weight: 600; }
        
        /* REKAPITULASI */
        .rekap-box {
            background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px;
            padding: 14px 18px; margin: 20px 0 28px; font-size: 12px; font-weight: 600;
            color: var(--primary); display: flex; gap: 24px; flex-wrap: wrap;
        }
        .rekap-item { display: flex; align-items: center; gap: 6px; }
        .rekap-item strong { color: var(--accent); font-size: 14px; }
        
        /* SECTION */
        h4.section-title { 
            color: var(--primary); margin: 24px 0 12px; font-size: 14px; 
            font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
            padding: 8px 12px; background: rgba(15,61,92,.04); 
            border-left: 4px solid var(--accent); border-radius: 0 6px 6px 0;
        }
        
        /* TABEL */
        table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 11px; }
        th, td { border: 1px solid var(--border); padding: 8px 10px; text-align: left; vertical-align: top; }
        th { 
            background: #F8FAFC; color: var(--primary); font-weight: 700; 
            text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;
        }
        td { color: var(--text-dark); }
        
        /* KOLOM ANGKA */
        .col-center { text-align: center; }
        .col-cap { font-weight: 700; color: var(--danger); }
        .col-target { font-weight: 600; }
        
        /* BADGE KATEGORI */
        .kat-mayor { color: #DC2626; font-weight: 700; }
        .kat-minor { color: #D97706; font-weight: 700; }
        .kat-obs { color: #2563EB; font-weight: 700; }
        .kat-baik { color: #059669; font-weight: 700; }
        
        /* ALERT BOX */
        .alert-box {
            padding: 12px 16px; border-radius: 8px; margin-bottom: 10px;
            border-left: 4px solid var(--danger); background: #FEF2F2; color: #991B1B; font-size: 12px;
        }
        .alert-box strong { display: block; margin-bottom: 4px; color: #7F1D1D; font-size: 13px; }
        
        /* REKOMENDASI */
        .rek-item {
            padding: 12px 16px; background: rgba(201,162,39,.06); border-radius: 8px;
            margin-bottom: 10px; font-size: 12px; border-left: 3px solid var(--accent);
            line-height: 1.5;
        }
        .rek-item strong { color: var(--primary); }
        
        /* KEPUTUSAN RAPAT */
        .keputusan-table td { 
            height: 50px; font-size: 12px; color: var(--text-muted); 
            font-style: italic; border: 1px dashed var(--border);
        }
        .keputusan-table td:first-child { 
            width: 40px; text-align: center; font-weight: 700; 
            color: var(--primary); font-style: normal;
        }
        
        /* TANDA TANGAN */
        .sig { display: flex; justify-content: space-between; margin-top: 60px; page-break-inside: avoid; }
        .sig div { text-align: center; width: 45%; }
        .sig .space { height: 80px; }
        .sig strong { 
            border-bottom: 1px solid var(--text-dark); padding-bottom: 4px; 
            display: inline-block; min-width: 200px; font-weight: 700;
        }
        .sig small { display: block; margin-top: 4px; font-size: 11px; color: var(--text-muted); }
        
        /* FOOTER */
        .doc-footer {
            margin-top: 40px; padding-top: 12px; border-top: 1px solid var(--border);
            font-size: 10px; color: var(--text-muted); text-align: center;
        }
        
        /* TOMBOL CETAK */
        .noprint { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .noprint button { 
            padding: 12px 24px; background: var(--primary); color: #fff; border: none; 
            border-radius: 8px; cursor: pointer; font-weight: 700; font-family: inherit;
            font-size: 13px; box-shadow: 0 4px 12px rgba(15, 61, 92, 0.3); transition: all 0.2s;
        }
        .noprint button:hover { background: var(--accent); color: var(--primary); transform: translateY(-2px); }

        /* PRINT CSS */
        @media print { 
            @page { margin: 20mm 20mm 25mm 20mm; size: A4; }
            .noprint { display: none !important; } 
            body { padding: 0; font-size: 11px; }
            .kop { border-bottom-color: #000; }
            .kop .t h2 { color: #000; }
            th { background: #E2E8F0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .rekap-box { background: #F8FAFC !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .alert-box { background: #FEF2F2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .rek-item { background: rgba(201,162,39,.06) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            h4.section-title { background: rgba(15,61,92,.04) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .section-title { page-break-after: avoid; }
            table { page-break-inside: avoid; }
            .sig { page-break-inside: avoid; margin-top: 40px; }
        }
    </style>
</head>
<body>
<div class="noprint"><button onclick="window.print()">🖨️ Cetak / Simpan PDF</button></div>

<!-- KOP SURAT -->
<div class="kop">
    <?php if ($logo): ?><img src="/uploads/<?= Security::e($logo) ?>" alt="Logo"><?php endif; ?>
    <div class="t">
        <h2><?= Security::e($brand) ?></h2>
        <p>Lembaga Penjaminan Mutu — Gedung Rektorat Lt. 3 • lpm@kampus.ac.id</p>
    </div>
</div>

<!-- JUDUL -->
<h3 class="judul">Bahan Rapat Tinjauan Manajemen (RTM)</h3>
<p class="sub">Siklus PPEPP Tahun Akademik <?= $tahun ?> — Dokumen Internal</p>

<!-- REKAPITULASI -->
<div class="rekap-box">
    <span class="rekap-item">📊 Standar Belum Tercapai: <strong><?= $totalStandar ?></strong></span>
    <span class="rekap-item">🔴 Temuan Mayor Terbuka: <strong><?= $totalMayor ?></strong></span>
    <span class="rekap-item">🤖 Rekomendasi Prioritas: <strong><?= $totalReks ?></strong></span>
</div>

<!-- BAGIAN A: STANDAR BELUM TERCAPAI -->
<h4 class="section-title">A. Standar Mutu yang Belum Tercapai (Gap Analysis)</h4>
<?php if (empty($belumList)): ?>
    <p style="color:var(--success);font-weight:600;padding:12px;background:#D1FAE5;border-radius:6px;">
        🎉 Alhamdulillah, seluruh standar mutu telah mencapai atau melampaui target pada tahun <?= $tahun ?>.
    </p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width:10%">Kode</th>
                <th style="width:30%">Nama Standar</th>
                <th style="width:12%" class="col-center">Capaian</th>
                <th style="width:12%" class="col-center">Target</th>
                <th style="width:12%" class="col-center">Gap</th>
                <th style="width:24%">Rekomendasi Perbaikan</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($belumList as $b): 
            $gap = $b['t'] - $b['c'];
            $status = $gap >= 20 ? '<span class="kat-mayor">Kritis</span>' : '<span class="kat-minor">Perlu Perhatian</span>';
        ?>
            <tr>
                <td style="font-weight:700;"><?= Security::e($b['kode_standar']) ?></td>
                <td><?= Security::e($b['nama_standar']) ?></td>
                <td class="col-center col-cap"><?= $b['c'] ?>%</td>
                <td class="col-center col-target"><?= $b['t'] ?>%</td>
                <td class="col-center" style="font-weight:700;color:var(--danger);"><?= $gap ?>%</td>
                <td>
                    <?= $gap >= 20 ? 'Perlu analisis akar masalah mendalam, alokasi anggaran khusus, dan monitoring bulanan.' : 'Perlu tindakan perbaikan terstruktur dan evaluasi berkala.' ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- BAGIAN B: TEMUAN MAYOR -->
<h4 class="section-title">B. Temuan Audit Mutu Internal (AMI) Kategori Mayor</h4>
<?php if (empty($mayorList)): ?>
    <p style="color:var(--success);font-weight:600;padding:12px;background:#D1FAE5;border-radius:6px;">
        ✅ Tidak ada temuan kategori Mayor yang terbuka / belum ditutup.
    </p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width:20%">Program Studi</th>
                <th style="width:12%">Standar</th>
                <th style="width:45%">Deskripsi Temuan</th>
                <th style="width:23%">Status Koreksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($mayorList as $m): ?>
            <tr>
                <td><strong><?= Security::e($m['nama_prodi'] ?: 'Institusi') ?></strong></td>
                <td style="font-weight:700;"><?= Security::e($m['kode_standar']) ?></td>
                <td><?= Security::e($m['deskripsi_temuan']) ?></td>
                <td>
                    <span class="kat-mayor"><?= Security::e($m['status_verifikasi']) ?></span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- BAGIAN C: REKOMENDASI STRATEGIS -->
<h4 class="section-title">C. Rekomendasi Strategis Prioritas Tinggi</h4>
<?php if (empty($reksList)): ?>
    <p style="color:var(--text-muted);font-style:italic;">Belum ada rekomendasi prioritas tinggi yang di-generate untuk tahun ini. Silakan jalankan fitur "Generate Rekomendasi AI" di menu Rekomendasi RTM.</p>
<?php else: ?>
    <?php foreach ($reksList as $rek): ?>
    <div class="rek-item">
        <strong>[<?= Security::e($rek['sumber']) ?>]</strong> <?= Security::e($rek['teks']) ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- BAGIAN D: KEPUTUSAN RAPAT -->
<h4 class="section-title">D. Keputusan Rapat</h4>
<table class="keputusan-table">
    <tr><td>1.</td><td></td></tr>
    <tr><td>2.</td><td></td></tr>
    <tr><td>3.</td><td></td></tr>
    <tr><td>4.</td><td></td></tr>
    <tr><td>5.</td><td></td></tr>
</table>

<!-- TANDA TANGAN -->
<div class="sig">
    <div>
        Mengetahui,<br>
        Rektor / Wakil Rektor Bidang Akademik<br>
        <div class="space"></div>
        <strong>( ........................................... )</strong>
        <small>NIP. ..........................</small>
    </div>
    <div>
        Maumere, <?= date('d F Y') ?><br>
        Kepala Lembaga Penjaminan Mutu<br>
        <div class="space"></div>
        <?php
        $kaprodi = $db->query("SELECT nama_lengkap FROM users WHERE id_role = 2 LIMIT 1")->fetch();
        $namaKaprodi = $kaprodi ? Security::e($kaprodi['nama_lengkap']) : '( ........................................... )';
        ?>
        <strong><?= $namaKaprodi ?></strong>
        <small>NIP/NIDN. ..........................</small>
    </div>
</div>

<!-- FOOTER -->
<div class="doc-footer">
    Dokumen ini dicetak secara otomatis dari Sistem Informasi Penjaminan Mutu (SIM-Mutu) pada <?= date('d F Y, H:i') ?> WIB.<br>
    Keaslian dokumen dapat diverifikasi melalui portal LPM.
</div>

<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>
</body>
</html>