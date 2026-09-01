<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$pesan = '';
$tahun = (int)($_GET['tahun'] ?? date('Y'));

if (($_POST['action'] ?? '') === 'generate') {
    Security::verifyCsrf();
    $rec = [];

    /* 1) Celah EDP per standar */
    $gap = $db->prepare("SELECT * FROM (
        SELECT s.kode_standar, s.nama_standar, ROUND(AVG(e.capaian),1) c, ROUND(AVG(e.target),1) t
        FROM standar_mutu s JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.tahun = ?
        GROUP BY s.id_standar
    ) g WHERE g.c < g.t ORDER BY (g.t - g.c) DESC");
    $gap->execute([$tahun]);
    foreach ($gap->fetchAll() as $g) {
        $delta = $g['t'] - $g['c'];
        $rec[] = ['EDP', $delta >= 20 ? 'Tinggi' : 'Sedang',
            "Standar {$g['kode_standar']} ({$g['nama_standar']}) baru mencapai {$g['c']}% dari target {$g['t']}% (celah {$delta} poin). Rekomendasi: lakukan analisis akar masalah, tetapkan rencana perbaikan berjangka 6 bulan, dan alokasikan anggaran khusus pada siklus PPEPP berikutnya."];
    }

    /* 2) Temuan mayor AMI */
    $mayor = $db->query("SELECT p.nama_prodi, s.kode_standar, t.deskripsi_temuan, t.status_verifikasi
        FROM temuan_audit t
        JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
        LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
        LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar
        WHERE t.kategori = 'Mayor' AND t.status_verifikasi != 'Diterima'");
    foreach ($mayor->fetchAll() as $m) {
        $rec[] = ['AMI', 'Tinggi',
            "Temuan MAYOR pada {$m['nama_prodi']} (Standar {$m['kode_standar']}) belum tertutup: \"{$m['deskripsi_temuan']}\". Rekomendasi: wajibkan tindakan koreksi dalam 30 hari dan verifikasi ulang oleh auditor sebelum RTM berikutnya."];
    }

    /* 3) Tracer study */
    $tr = $db->query("SELECT COUNT(*) t, SUM(status_kerja IN ('Bekerja','Wirausaha')) w, AVG(masa_tunggu_bulan) mt FROM tracer_alumni")->fetch();
    if ($tr['t'] > 0) {
        $tkr = round($tr['w'] / $tr['t'] * 100, 1);
        if ($tkr < 60) $rec[] = ['Tracer', 'Tinggi', "Tingkat serapan lulusan hanya {$tkr}% (< 60%). Rekomendasi: aktifkan career center, perluas kemitraan industri/magang, dan gelar job fair tahunan."];
        $rel = $db->query("SELECT COUNT(*) c, SUM(kesesuaian_bidang IN ('Sangat Sesuai','Sesuai')) s FROM tracer_alumni WHERE status_kerja IN ('Bekerja','Wirausaha')")->fetch();
        if ($rel['c'] > 0 && round($rel['s'] / $rel['c'] * 100) < 60)
            $rec[] = ['Tracer', 'Sedang', 'Kesesuaian kerja dengan bidang studi di bawah 60%. Rekomendasi: tinjau keselarasan kurikulum (OBE) bersama dunia usaha.'];
        if ($tr['mt'] > 6) $rec[] = ['Tracer', 'Sedang', "Rata-rata masa tunggu kerja {$tr['mt']} bulan (> 6). Rekomendasi: program sertifikasi kompetensi & pembekalan karier sebelum wisuda."];
    }

    /* 4) Survei pengguna — aspek terlemah */
    $sp = $db->query("SELECT ROUND(AVG(aspek_etika),2) a1, ROUND(AVG(aspek_bahasa),2) a3, ROUND(AVG(aspek_teknologi),2) a4, ROUND(AVG(aspek_komunikasi),2) a5 FROM survei_pengguna")->fetch();
    if ($sp['a1'] !== null) {
        $aspek = ['Etika' => $sp['a1'], 'Bahasa Asing' => $sp['a3'], 'Teknologi' => $sp['a4'], 'Komunikasi' => $sp['a5']];
        $worst = array_keys($aspek, min($aspek))[0];
        if (min($aspek) < 3.5) $rec[] = ['Survei', 'Sedang', "Pengguna lulusan menilai aspek {$worst} paling rendah ({$aspek[$worst]}/5). Rekomendasi: sisipkan penguatan {$worst} ke dalam MBKM / mata kuliah terkait."];
    }

    /* 5) IKU di bawah target (FIX: prepare, bukan query) */
    $iku = $db->prepare("SELECT i.kode, i.nama, i.target, d.nilai FROM iku_indikator i JOIN iku_data d ON d.id_iku = i.id_iku AND d.tahun = ? WHERE d.nilai < i.target");
    $iku->execute([$tahun]);
    foreach ($iku->fetchAll() as $k) {
        $rec[] = ['IKU', $k['nilai'] < $k['target'] / 2 ? 'Tinggi' : 'Rendah',
            "{$k['kode']} ({$k['nama']}) baru {$k['nilai']} dari target {$k['target']}. Rekomendasi: tetapkan PIC khusus dan milestones triwulan."];
    }

    $db->prepare("DELETE FROM rekomendasi_ai WHERE tahun = ?")->execute([$tahun]);
    foreach ($rec as $r) {
        $db->prepare("INSERT INTO rekomendasi_ai (tahun, sumber, prioritas, teks) VALUES (?,?,?,?)")->execute([$tahun, $r[0], $r[1], $r[2]]);
    }
    /* FIX baris 66: pakai concatenation, bukan ternary di dalam string */
    Logger::log('UPDATE', 'Generate ' . count($rec) . ' rekomendasi AI tahun ' . $tahun);
    Notifier::sendRole(2, '🤖 Rekomendasi AI Siap', count($rec) . ' draf rekomendasi dihasilkan untuk RTM ' . $tahun . '.', '/sim/pimpinan/rekomendasi.php', '🤖');
    $pesan = '🤖 ' . count($rec) . ' rekomendasi dihasilkan dari 5 sumber data.';
}

if (($_POST['action'] ?? '') === 'delete') {
    Security::verifyCsrf();
    $db->prepare("DELETE FROM rekomendasi_ai WHERE id_rekomendasi = ?")->execute([(int)$_POST['id_rekomendasi']]);
    $pesan = '✅ Rekomendasi dihapus.';
}

$list = $db->prepare("SELECT * FROM rekomendasi_ai WHERE tahun = ? ORDER BY FIELD(prioritas,'Tinggi','Sedang','Rendah'), id_rekomendasi");
$list->execute([$tahun]);
$reks = $list->fetchAll();

$simTitle = 'Rekomendasi AI';
$activeMenu = 'rekom';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
        <h2 style="font-size:22px;color:var(--primary-dark);">🤖 Rekomendasi Berbantuan AI — <?= $tahun ?></h2>
        <p class="text-muted" style="font-size:13px;">Mesin membaca celah EDP, temuan AMI, tracer, survei pengguna & IKU — lalu menyusun draf siap RTM.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <form method="GET"><select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <?php for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--): ?><option <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?>
        </select></form>
        <form method="POST" onsubmit="return confirm('Generate ulang? Rekomendasi lama tahun ini akan diganti.');"><input type="hidden" name="action" value="generate"><?= Security::csrfField() ?><button class="btn btn-gold">🤖 Generate Rekomendasi</button></form>
    </div>
</div>

<?php if (empty($reks)): ?>
    <div class="card" style="text-align:center;"><div style="font-size:52px;">🤖</div>
        <p class="text-muted">Belum ada rekomendasi untuk <?= $tahun ?>. Klik <strong>Generate</strong> — sistem akan menganalisis 5 sumber data secara instan.</p></div>
<?php endif; ?>

<div style="display:flex;flex-direction:column;gap:16px;">
<?php foreach ($reks as $r): ?>
    <div class="card" style="border-left:4px solid <?= $r['prioritas'] === 'Tinggi' ? 'var(--danger)' : ($r['prioritas'] === 'Sedang' ? 'var(--warning)' : 'var(--success)') ?>;">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap;">
            <div style="flex:1;min-width:260px;">
                <p style="margin-bottom:8px;"><span class="badge <?= $r['prioritas'] === 'Tinggi' ? 'badge-unggul' : 'badge-a' ?>" style="<?= $r['prioritas'] === 'Tinggi' ? 'background:#FEE2E2;color:#991B1B' : '' ?>">Prioritas <?= $r['prioritas'] ?></span>
                <span class="badge badge-baik" style="margin-left:6px;">Sumber: <?= Security::e($r['sumber']) ?></span></p>
                <p style="font-size:14.5px;"><?= Security::e($r['teks']) ?></p>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-outline" style="padding:6px 14px;font-size:12px;" onclick="navigator.clipboard.writeText(<?= json_encode($r['teks']) ?>);this.textContent='✅ Tersalin';">📋 Salin</button>
                <form method="POST" onsubmit="return confirm('Hapus?');"><input type="hidden" name="id_rekomendasi" value="<?= $r['id_rekomendasi'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><button class="btn btn-outline" style="padding:6px 12px;font-size:12px;color:var(--danger);">🗑️</button></form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>