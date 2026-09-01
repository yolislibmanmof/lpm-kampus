<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$me = $db->prepare("SELECT u.id_prodi, u.nama_lengkap FROM users u WHERE u.id_user = ?");
$me->execute([Auth::id()]);
$u = $me->fetch();
$prodiId = $u['id_prodi'] ?? null;
if (!$prodiId) { die('Akun belum terhubung ke prodi.'); }

$tahun = (int)($_GET['tahun'] ?? date('Y'));
$prodi = $db->prepare("SELECT p.nama_prodi, f.nama_fakultas FROM prodi p LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas WHERE p.id_prodi = ?");
$prodi->execute([$prodiId]); $P = $prodi->fetch();

$edp = $db->prepare("SELECT e.*, s.kode_standar, s.nama_standar FROM evaluasi_diri e LEFT JOIN standar_mutu s ON e.id_standar = s.id_standar WHERE e.id_prodi = ? AND e.tahun = ? ORDER BY s.kode_standar");
$edp->execute([$prodiId, $tahun]); $edp = $edp->fetchAll();

$tr = $db->prepare("SELECT COUNT(*) t, SUM(status_kerja IN ('Bekerja','Wirausaha')) w, ROUND(AVG(masa_tunggu_bulan),1) mt FROM tracer_alumni WHERE id_prodi = ?");
$tr->execute([$prodiId]); $TR = $tr->fetch();
$tkr = $TR['t'] > 0 ? round($TR['w'] / $TR['t'] * 100) : 0;

$prestasi = $db->prepare("SELECT * FROM prestasi_prodi WHERE id_prodi = ? AND tahun = ? ORDER BY jenis, tingkat DESC");
$prestasi->execute([$prodiId, $tahun]); $prestasi = $prestasi->fetchAll();

$edom = $db->prepare("SELECT d.nama_dosen, ROUND(AVG((j.q1+j.q2+j.q3+j.q4+j.q5+j.q6+j.q7+j.q8+j.q9+j.q10)/10),2) rata, COUNT(j.id_jawaban) n
    FROM edom_jawaban j JOIN edom_kelas k ON j.id_kelas = k.id_kelas JOIN dosen d ON k.id_dosen = d.id_dosen
    WHERE d.id_prodi = ? GROUP BY k.id_dosen ORDER BY rata DESC");
$edom->execute([$prodiId]); $edom = $edom->fetchAll();

$tugas = $db->prepare("SELECT COUNT(*) t, SUM(status='Selesai') s FROM tugas_akreditasi WHERE id_prodi = ?");
$tugas->execute([$prodiId]); $TG = $tugas->fetch();

$kepalaLpm = $db->query("SELECT nama_lengkap FROM users WHERE id_role = 2 LIMIT 1")->fetch()['nama_lengkap'] ?? 'Kepala LPM';
$brand = Site::setting('brand_utama', 'LPM') . ' ' . Site::setting('brand_aksen', 'Kampus');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Kinerja Prodi <?= $tahun ?> — <?= Security::e($P['nama_prodi']) ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #111; margin: 0; padding: 28px; background: #fff; }
    .tb { position: fixed; top: 0; left: 0; right: 0; background: #0F3D5C; color: #fff; padding: 10px 20px; display: flex; gap: 10px; justify-content: flex-end; z-index: 9; }
    .tb a, .tb button { background: #C9A227; color: #0F3D5C; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 12px; }
    .kop { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 18px; }
    .kop h2 { margin: 2px 0; font-size: 16px; }
    .kop p { margin: 0; font-size: 11px; }
    h3 { font-size: 13px; margin: 18px 0 6px; border-left: 4px solid #C9A227; padding-left: 8px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    th, td { border: 1px solid #888; padding: 5px 8px; text-align: left; vertical-align: top; }
    th { background: #EEF3F8; }
    .sig { margin-top: 36px; display: flex; justify-content: space-between; }
    .sig div { text-align: center; width: 45%; }
    .sig .space { height: 70px; }
    @media print { .tb { display: none; } body { padding: 10mm; } }
</style>
</head>
<body>
<div class="tb">
    <a href="/sim/prodi/laporan-kinerja.php?tahun=<?= $tahun - 1 ?>">◀ <?= $tahun - 1 ?></a>
    <a href="/sim/prodi/laporan-kinerja.php?tahun=<?= $tahun + 1 ?>"><?= $tahun + 1 ?> ▶</a>
    <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    <a href="/sim/index.php">✕ Tutup</a>
</div>

<div class="kop">
    <p style="font-weight:800;"><?= Security::e($brand) ?></p>
    <h2>LAPORAN KINERJA PROGRAM STUDI <?= strtoupper(Security::e($P['nama_prodi'])) ?></h2>
    <p>Tahun Akademik <?= $tahun ?> • Fakultas <?= Security::e($P['nama_fakultas'] ?? '—') ?></p>
</div>

<h3>A. Capaian Standar Mutu (EDP)</h3>
<table>
    <tr><th style="width:8%">Standar</th><th style="width:34%">Nama</th><th style="width:12%">Capaian</th><th style="width:12%">Target</th><th style="width:12%">Status</th></tr>
    <?php foreach ($edp as $e): $ok = $e['capaian'] !== null && $e['target'] !== null && $e['capaian'] >= $e['target']; ?>
        <tr><td><?= Security::e($e['kode_standar']) ?></td><td><?= Security::e($e['nama_standar']) ?></td>
        <td><?= $e['capaian'] !== null ? $e['capaian'] . '%' : '—' ?></td><td><?= $e['target'] !== null ? $e['target'] . '%' : '—' ?></td>
        <td><?= $ok ? '✅ Tercapai' : '⚠️ Perlu Perbaikan' ?></td></tr>
    <?php endforeach; ?>
</table>

<h3>B. Kinerja Lulusan (Tracer Study)</h3>
<table>
    <tr><th>Responden</th><th>Terserap Kerja</th><th>Rata-rata Masa Tunggu</th></tr>
    <tr><td><?= (int)$TR['t'] ?></td><td><?= $tkr ?>%</td><td><?= $TR['mt'] ?? '—' ?> bulan</td></tr>
</table>

<h3>C. Evaluasi Pembelajaran (EDOM)</h3>
<table>
    <tr><th>Dosen</th><th>Rata-rata (skala 5)</th><th>Responden</th></tr>
    <?php foreach ($edom as $d): ?><tr><td><?= Security::e($d['nama_dosen']) ?></td><td><?= $d['rata'] ?></td><td><?= (int)$d['n'] ?></td></tr><?php endforeach; ?>
</table>

<h3>D. Prestasi Tahun <?= $tahun ?></h3>
<table>
    <tr><th>Jenis</th><th>Prestasi</th><th>Tingkat</th><th>Penyelenggara</th></tr>
    <?php if (empty($prestasi)): ?><tr><td colspan="4" style="text-align:center;">Tidak ada prestasi tercatat pada tahun ini.</td></tr><?php endif; ?>
    <?php foreach ($prestasi as $p): ?><tr><td><?= $p['jenis'] ?></td><td><?= Security::e($p['judul']) ?></td><td><?= Security::e($p['tingkat']) ?></td><td><?= Security::e($p['penyelenggara']) ?></td></tr><?php endforeach; ?>
</table>

<h3>E. Progres Persiapan Akreditasi</h3>
<table>
    <tr><th>Total Tugas</th><th>Selesai</th><th>Progres</th></tr>
    <tr><td><?= (int)$TG['t'] ?></td><td><?= (int)$TG['s'] ?></td><td><?= $TG['t'] > 0 ? round($TG['s'] / $TG['t'] * 100) : 0 ?>%</td></tr>
</table>

<div class="sig">
    <div>Mengetahui,<br>Kepala LPM<div class="space"></div><strong><?= Security::e($kepalaLpm) ?></strong></div>
    <div>Kaprodi,<div class="space"></div><strong><?= Security::e($u['nama_lengkap']) ?></strong></div>
</div>
</body>
</html>