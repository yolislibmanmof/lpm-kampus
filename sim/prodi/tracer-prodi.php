<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'Tracer Prodi';
$activeMenu = 'tracerprodi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun belum terhubung ke prodi.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tracer_prodi_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBFnama;nim;tahun_lulus;status;instansi;jabatan;masa_tunggu;kesesuaian;gaji;wa;wawancara\n";
    $st = $db->prepare("SELECT * FROM tracer_alumni WHERE id_prodi = ? ORDER BY created_at DESC");
    $st->execute([$prodiId]);
    foreach ($st->fetchAll() as $r) {
        echo implode(';', array_map(fn($v) => str_replace(';', ',', (string)$v), [
            $r['nama'], $r['nim'], $r['tahun_lulus'], $r['status_kerja'], $r['nama_instansi'], $r['jabatan'],
            $r['masa_tunggu_bulan'], $r['kesesuaian_bidang'], $r['kisaran_gaji'], $r['no_wa'], $r['siap_wawancara'] ? 'Ya' : 'Tidak'
        ])) . "\n";
    }
    exit;
}

$s1 = $db->prepare("SELECT COUNT(*) t FROM tracer_alumni WHERE id_prodi = ?"); $s1->execute([$prodiId]); $total = (int)$s1->fetch()['t'];
$s2 = $db->prepare("SELECT COUNT(*) t FROM tracer_alumni WHERE id_prodi = ? AND status_kerja IN ('Bekerja','Wirausaha')"); $s2->execute([$prodiId]); $worked = (int)$s2->fetch()['t'];
$s3 = $db->prepare("SELECT ROUND(AVG(masa_tunggu_bulan),1) a FROM tracer_alumni WHERE id_prodi = ? AND status_kerja IN ('Bekerja','Wirausaha')"); $s3->execute([$prodiId]); $avgT = $s3->fetch()['a'] ?? 0;
$tkr = $total > 0 ? round($worked / $total * 100) : 0;

$list = $db->prepare("SELECT * FROM tracer_alumni WHERE id_prodi = ? ORDER BY created_at DESC LIMIT 200");
$list->execute([$prodiId]); $list = $list->fetchAll();

$siap = $db->prepare("SELECT * FROM responden_akreditasi WHERE id_prodi = ? ORDER BY tipe, nama");
$siap->execute([$prodiId]); $siap = $siap->fetchAll();
?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <h2 style="font-size:22px;color:var(--primary-dark);">🎓 Tracer Study Prodi Saya</h2>
    <a href="?export=1" class="btn btn-outline">📤 Export CSV</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">🎓</div><div><h3 style="font-size:28px;"><?= $total ?></h3><p class="text-muted">Responden</p></div></div>
    <div class="stat-card"><div class="stat-icon green">💼</div><div><h3 style="font-size:28px;"><?= $tkr ?>%</h3><p class="text-muted">Terserap Kerja</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">⏱️</div><div><h3 style="font-size:28px;"><?= $avgT ?></h3><p class="text-muted">Rata-rata Tunggu (bln)</p></div></div>
    <div class="stat-card"><div class="stat-icon red">🙋</div><div><h3 style="font-size:28px;"><?= count($siap) ?></h3><p class="text-muted">Responden Wawancara</p></div></div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">📋 Alumni Responden</h3>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Nama</th><th>Lulus</th><th>Status</th><th>Masa Tunggu</th><th>Kontak</th></tr></thead>
                <tbody>
                <?php if (empty($list)): ?><tr><td colspan="5" style="text-align:center;padding:30px;" class="text-muted">Belum ada alumni mengisi tracer. Sebarkan link tracer ke grup alumni!</td></tr><?php endif; ?>
                <?php foreach ($list as $r): ?>
                    <tr>
                        <td><strong><?= Security::e($r['nama']) ?></strong><br><small class="text-muted"><?= Security::e($r['nim']) ?></small></td>
                        <td><?= Security::e($r['tahun_lulus']) ?></td>
                        <td><span class="badge <?= $r['status_kerja'] === 'Bekerja' ? 'badge-unggul' : 'badge-a' ?>"><?= Security::e($r['status_kerja']) ?></span></td>
                        <td><?= $r['masa_tunggu_bulan'] ?> bln</td>
                        <td><a class="btn btn-primary" style="padding:4px 12px;font-size:11px;background:var(--success);" target="_blank" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $r['no_wa']) ?>">💬 WA</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <h3 style="margin-bottom:16px;">🙋 Siap Wawancara Asesor</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php if (empty($siap)): ?><p class="text-muted">Belum ada responden terdaftar dari prodi Anda.</p><?php endif; ?>
            <?php foreach ($siap as $s): ?>
            <div style="padding:12px 14px;background:var(--bg-light);border-radius:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;">
                <div><strong style="font-size:13.5px;"><?= Security::e($s['nama']) ?></strong>
                    <p class="text-muted" style="font-size:12px;"><?= Security::e($s['tipe']) ?> <?= $s['unit_kerja'] ? '• ' . Security::e($s['unit_kerja']) : '' ?></p></div>
                <a class="btn btn-primary" style="padding:4px 12px;font-size:11px;background:var(--success);" target="_blank" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $s['no_wa']) ?>">💬</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>