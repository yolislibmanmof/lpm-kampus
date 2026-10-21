<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();

$me = $db->prepare("SELECT u.id_prodi, u.nama_lengkap FROM users u WHERE u.id_user = ?");
$me->execute([Auth::id()]);
$u = $me->fetch();
$prodiId = $u['id_prodi'] ?? null;
if (!$prodiId) { die('<h1 style="text-align:center;margin-top:50px;font-family:sans-serif;color:#EF4444;">⚠️ Akun belum terhubung ke Program Studi.</h1>'); }

$tahun = (int)($_GET['tahun'] ?? date('Y'));
$prodi = $db->prepare("SELECT p.nama_prodi, f.nama_fakultas FROM prodi p LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas WHERE p.id_prodi = ?");
$prodi->execute([$prodiId]); 
$P = $prodi->fetch();

$edp = $db->prepare("SELECT e.*, s.kode_standar, s.nama_standar FROM evaluasi_diri e LEFT JOIN standar_mutu s ON e.id_standar = s.id_standar WHERE e.id_prodi = ? AND e.tahun = ? ORDER BY s.kode_standar");
$edp->execute([$prodiId, $tahun]); 
$edp = $edp->fetchAll();

$tr = $db->prepare("SELECT COUNT(*) t, SUM(status_kerja IN ('Bekerja','Wirausaha')) w, ROUND(AVG(masa_tunggu_bulan),1) mt FROM tracer_alumni WHERE id_prodi = ?");
$tr->execute([$prodiId]); 
$TR = $tr->fetch();
$tkr = $TR['t'] > 0 ? round($TR['w'] / $TR['t'] * 100) : 0;

$prestasi = $db->prepare("SELECT * FROM prestasi_prodi WHERE id_prodi = ? AND tahun = ? ORDER BY FIELD(tingkat, 'Internasional', 'Nasional', 'Regional', 'Universitas', 'Fakultas', 'Prodi') DESC, jenis");
$prestasi->execute([$prodiId, $tahun]); 
$prestasi = $prestasi->fetchAll();

$edom = $db->prepare("SELECT d.nama_dosen, ROUND(AVG((j.q1+j.q2+j.q3+j.q4+j.q5+j.q6+j.q7+j.q8+j.q9+j.q10)/10),2) rata, COUNT(j.id_jawaban) n
    FROM edom_jawaban j JOIN edom_kelas k ON j.id_kelas = k.id_kelas JOIN dosen d ON k.id_dosen = d.id_dosen
    WHERE d.id_prodi = ? GROUP BY k.id_dosen ORDER BY rata DESC LIMIT 10");
$edom->execute([$prodiId]); 
$edom = $edom->fetchAll();

$tugas = $db->prepare("SELECT COUNT(*) t, SUM(status='Selesai') s FROM tugas_akreditasi WHERE id_prodi = ?");
$tugas->execute([$prodiId]); 
$TG = $tugas->fetch();

$kepalaLpm = $db->query("SELECT nama_lengkap FROM users WHERE id_role = 2 LIMIT 1")->fetch()['nama_lengkap'] ?? 'Kepala LPM';
$brand = Site::setting('brand_utama', 'LPM') . ' ' . Site::setting('brand_aksen', 'Kampus');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Kinerja Prodi <?= $tahun ?> — <?= Security::e($P['nama_prodi']) ?></title>
<style>
    :root {
        --primary: #0F3D5C;
        --accent: #C9A227;
        --success: #10B981;
        --warning: #F59E0B;
    }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { 
        font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif; 
        font-size: 11pt; 
        color: #1a1a1a; 
        margin: 0; 
        padding: 0; 
        background: #f4f6f8; 
        line-height: 1.5;
    }
    .toolbar { 
        position: fixed; top: 0; left: 0; right: 0; 
        background: var(--primary); color: #fff; 
        padding: 12px 24px; display: flex; gap: 12px; justify-content: flex-end; 
        z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,.15);
    }
    .toolbar a, .toolbar button { 
        background: var(--accent); color: var(--primary); 
        border: none; padding: 8px 16px; border-radius: 6px; 
        font-weight: 700; cursor: pointer; text-decoration: none; 
        font-size: 11pt; display: inline-flex; align-items: center; gap: 6px;
        transition: transform 0.2s;
    }
    .toolbar a:hover, .toolbar button:hover { transform: translateY(-2px); filter: brightness(1.1); }
    
    .page { 
        max-width: 210mm; margin: 80px auto 40px; 
        background: #fff; padding: 40px; 
        box-shadow: 0 8px 24px rgba(0,0,0,.08);
        min-height: 297mm;
    }
    
    .kop { 
        text-align: center; border-bottom: 3px double var(--primary); 
        padding-bottom: 16px; margin-bottom: 24px; 
    }
    .kop h1 { margin: 0; font-size: 16pt; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; }
    .kop h2 { margin: 4px 0 0; font-size: 14pt; color: #333; }
    .kop p { margin: 4px 0 0; font-size: 10pt; color: #666; }
    
    h3 { 
        font-size: 12pt; margin: 24px 0 12px; 
        color: var(--primary); border-left: 4px solid var(--accent); 
        padding-left: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        background: linear-gradient(90deg, rgba(201,162,39,.08), transparent);
        padding-top: 6px; padding-bottom: 6px;
    }
    
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 10pt; }
    th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; vertical-align: middle; }
    th { background: #f1f5f9; color: var(--primary); font-weight: 700; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5px; }
    tr:nth-child(even) { background: #f8fafc; }
    
    .status-ok { color: var(--success); font-weight: 700; display: flex; align-items: center; gap: 6px; }
    .status-warn { color: var(--warning); font-weight: 700; display: flex; align-items: center; gap: 6px; }
    
    .sig { 
        margin-top: 48px; display: flex; justify-content: space-between; 
        page-break-inside: avoid;
    }
    .sig div { text-align: center; width: 45%; }
    .sig .space { height: 80px; }
    .sig strong { font-size: 11pt; border-bottom: 1px solid #333; padding-bottom: 4px; display: inline-block; min-width: 200px; }
    
    .footer-note {
        margin-top: 40px; padding-top: 16px; border-top: 1px solid #e2e8f0;
        font-size: 9pt; color: #64748b; text-align: center;
    }

    @media print { 
        .toolbar { display: none !important; } 
        body { background: #fff; }
        .page { margin: 0; padding: 20mm; box-shadow: none; min-height: auto; }
        h3 { break-after: avoid; }
        table { break-inside: avoid; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <a href="/sim/prodi/laporan-kinerja.php?tahun=<?= $tahun - 1 ?>">◀ Tahun <?= $tahun - 1 ?></a>
    <a href="/sim/prodi/laporan-kinerja.php?tahun=<?= $tahun + 1 ?>">Tahun <?= $tahun + 1 ?> ▶</a>
    <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    <a href="/sim/index.php" style="background:#fff;color:var(--primary);">✕ Tutup</a>
</div>

<div class="page">
    <div class="kop">
        <h1><?= Security::e($brand) ?></h1>
        <h2>LAPORAN KINERJA PROGRAM STUDI</h2>
        <h2><?= strtoupper(Security::e($P['nama_prodi'])) ?></h2>
        <p>Tahun Akademik <?= $tahun ?> • Fakultas <?= Security::e($P['nama_fakultas'] ?? '—') ?></p>
    </div>

    <h3>A. Capaian Standar Mutu (Evaluasi Diri)</h3>
    <table>
        <tr>
            <th style="width:10%">Kode</th>
            <th style="width:40%">Nama Standar</th>
            <th style="width:15%">Capaian</th>
            <th style="width:15%">Target</th>
            <th style="width:20%">Status</th>
        </tr>
        <?php foreach ($edp as $e): 
            $ok = $e['capaian'] !== null && $e['target'] !== null && (float)$e['capaian'] >= (float)$e['target']; 
        ?>
            <tr>
                <td style="font-weight:700;"><?= Security::e($e['kode_standar']) ?></td>
                <td><?= Security::e($e['nama_standar']) ?></td>
                <td style="text-align:center;"><?= $e['capaian'] !== null ? '<strong>' . $e['capaian'] . '%</strong>' : '—' ?></td>
                <td style="text-align:center;"><?= $e['target'] !== null ? $e['target'] . '%' : '—' ?></td>
                <td>
                    <?php if ($ok): ?>
                        <span class="status-ok">✅ Tercapai</span>
                    <?php else: ?>
                        <span class="status-warn">⚠️ Perlu Perbaikan</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h3>B. Kinerja Lulusan (Tracer Study)</h3>
    <table>
        <tr>
            <th>Total Responden</th>
            <th>Terserap Kerja / Wirausaha</th>
            <th>Rata-rata Masa Tunggu</th>
        </tr>
        <tr style="text-align:center; font-size:11pt;">
            <td><strong><?= (int)$TR['t'] ?> Alumni</strong></td>
            <td><strong style="color:var(--success);"><?= $tkr ?>%</strong></td>
            <td><strong><?= $TR['mt'] ? $TR['mt'] . ' Bulan' : '—' ?></strong></td>
        </tr>
    </table>

    <h3>C. Evaluasi Dosen oleh Mahasiswa (EDOM)</h3>
    <table>
        <tr>
            <th style="width:5%">No</th>
            <th>Nama Dosen</th>
            <th style="width:15%;text-align:center;">Rata-rata (Skala 5)</th>
            <th style="width:15%;text-align:center;">Jumlah Responden</th>
        </tr>
        <?php if (empty($edom)): ?>
            <tr><td colspan="4" style="text-align:center;color:#666;">Belum ada data EDOM untuk tahun ini.</td></tr>
        <?php else: ?>
            <?php $no = 1; foreach ($edom as $d): ?>
                <tr>
                    <td style="text-align:center;"><?= $no++ ?></td>
                    <td><?= Security::e($d['nama_dosen']) ?></td>
                    <td style="text-align:center; font-weight:700; color: <?= (float)$d['rata'] >= 4 ? 'var(--success)' : 'var(--warning)' ?>;"><?= $d['rata'] ?></td>
                    <td style="text-align:center;"><?= (int)$d['n'] ?> Mhs</td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <h3>D. Prestasi Mahasiswa & Dosen Tahun <?= $tahun ?></h3>
    <table>
        <tr>
            <th style="width:15%">Jenis</th>
            <th>Judul Prestasi</th>
            <th style="width:15%">Tingkat</th>
            <th style="width:25%">Penyelenggara</th>
        </tr>
        <?php if (empty($prestasi)): ?>
            <tr><td colspan="4" style="text-align:center;color:#666;">Tidak ada prestasi tercatat pada tahun ini.</td></tr>
        <?php else: ?>
            <?php foreach ($prestasi as $p): ?>
                <tr>
                    <td><?= $p['jenis'] === 'Mahasiswa' ? '🎓 Mahasiswa' : '👨‍🏫 Dosen' ?></td>
                    <td><?= Security::e($p['judul']) ?></td>
                    <td><strong><?= Security::e($p['tingkat']) ?></strong></td>
                    <td><?= Security::e($p['penyelenggara']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <h3>E. Progres Persiapan Akreditasi</h3>
    <table>
        <tr>
            <th style="width:33%">Total Tugas</th>
            <th style="width:33%">Tugas Selesai</th>
            <th style="width:34%">Progres Keseluruhan</th>
        </tr>
        <tr style="text-align:center; font-size:11pt;">
            <td><strong><?= (int)$TG['t'] ?> Tugas</strong></td>
            <td><strong style="color:var(--success);"><?= (int)$TG['s'] ?> Selesai</strong></td>
            <td>
                <div style="background:#e2e8f0;border-radius:50px;height:12px;overflow:hidden;margin:4px 20px 0;">
                    <div style="height:100%;width:<?= $TG['t'] > 0 ? round($TG['s'] / $TG['t'] * 100) : 0 ?>%;background:var(--accent);border-radius:50px;"></div>
                </div>
                <strong style="font-size:10pt;"><?= $TG['t'] > 0 ? round($TG['s'] / $TG['t'] * 100) : 0 ?>%</strong>
            </td>
        </tr>
    </table>

    <div class="sig">
        <div>
            Mengetahui,<br>
            Kepala Lembaga Penjaminan Mutu
            <div class="space"></div>
            <strong><?= Security::e($kepalaLpm) ?></strong>
        </div>
        <div>
            <?= Security::e($P['nama_fakultas'] ?? 'Fakultas') ?>,<br>
            Ketua Program Studi
            <div class="space"></div>
            <strong><?= Security::e($u['nama_lengkap']) ?></strong>
        </div>
    </div>
    
    <div class="footer-note">
        Dokumen ini dicetak secara otomatis dari Sistem Informasi Penjaminan Mutu (SIM-Mutu) pada <?= date('d F Y, H:i') ?> WITA.<br>
        Keaslian dokumen dapat diverifikasi melalui portal LPM.
    </div>
</div>

</body>
</html>