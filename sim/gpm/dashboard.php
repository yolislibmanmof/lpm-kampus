<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([5]);
$db = Database::getInstance();
$pesan = '';

$me = $db->prepare("SELECT id_fakultas FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$fakId = $me->fetch()['id_fakultas'] ?? null;

$simTitle = 'Dashboard GPM Fakultas';
$activeMenu = 'dashboard';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$fakId): ?>
    <div class="alert alert-danger">⚠️ Akun Anda belum ditugaskan ke fakultas. Hubungi Admin LPM (menu 🏢 Penugasan GPM).</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

$fn = $db->prepare("SELECT nama_fakultas FROM fakultas WHERE id_fakultas = ?");
$fn->execute([$fakId]);
$namaFak = $fn->fetch()['nama_fakultas'];

$tahun = (int)($_GET['tahun'] ?? date('Y'));
$prodiList = $db->prepare("SELECT id_prodi, nama_prodi FROM prodi WHERE id_fakultas = ? ORDER BY nama_prodi");
$prodiList->execute([$fakId]);
$prodiList = $prodiList->fetchAll();

/* ===== Keputusan review ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $pid = (int)$_POST['id_prodi'];
    $st = $_POST['keputusan'];
    $cat = trim($_POST['catatan'] ?? '');
    $db->prepare("REPLACE INTO review_gpm (id_prodi, tahun, status, catatan) VALUES (?,?,?,?)")
       ->execute([$pid, $tahun, $st, $cat]);
    Logger::log('UPDATE', "Review GPM prodi #$pid ($tahun): $st");
    $k = $db->prepare("SELECT id_user FROM users WHERE id_role = 3 AND id_prodi = ? LIMIT 1");
    $k->execute([$pid]);
    $kap = $k->fetch();
    if ($kap) Notifier::send((int)$kap['id_user'],
        $st === 'Disetujui' ? '✅ EDP Disetujui GPM' : '↩️ EDP Dikembalikan GPM',
        $cat ?: ('EDP tahun ' . $tahun . ' telah direview GPM fakultas.'), '/sim/prodi/edp.php', $st === 'Disetujui' ? '✅' : '↩️');
    $pesan = '✅ Keputusan review tersimpan & kaprodi dinotifikasi.';
}

/* ===== Data antrean ===== */
$antrean = [];
foreach ($prodiList as $p) {
    $e = $db->prepare("SELECT COUNT(*) total, SUM(status = 'Terkunci') terkunci FROM evaluasi_diri WHERE id_prodi = ? AND tahun = ?");
    $e->execute([$p['id_prodi'], $tahun]);
    $ev = $e->fetch();
    $r = $db->prepare("SELECT status, catatan FROM review_gpm WHERE id_prodi = ? AND tahun = ?");
    $r->execute([$p['id_prodi'], $tahun]);
    $rv = $r->fetch();
    $antrean[] = ['prodi' => $p, 'total' => (int)$ev['total'], 'terkunci' => (int)$ev['terkunci'], 'review' => $rv];
}
$nMenunggu = count(array_filter($antrean, fn($a) => $a['terkunci'] > 0 && !$a['review']));
$nDisetujui = count(array_filter($antrean, fn($a) => $a['review'] && $a['review']['status'] === 'Disetujui'));

/* ===== Detail EDP prodi ===== */
$detail = null; $edpRows = [];
$detailId = (int)($_GET['detail'] ?? 0);
if ($detailId) {
    $chk = $db->prepare("SELECT id_prodi FROM prodi WHERE id_prodi = ? AND id_fakultas = ?");
    $chk->execute([$detailId, $fakId]);
    if ($chk->fetch()) {
        $q = $db->prepare("SELECT e.*, s.kode_standar, s.nama_standar FROM evaluasi_diri e LEFT JOIN standar_mutu s ON e.id_standar = s.id_standar WHERE e.id_prodi = ? AND e.tahun = ? ORDER BY s.kode_standar");
        $q->execute([$detailId, $tahun]);
        $edpRows = $q->fetchAll();
        $detail = $detailId;
    }
}
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:var(--radius-lg);padding:32px 36px;color:#fff;margin-bottom:28px;position:relative;overflow:hidden;">
    <h2 style="font-size:24px;position:relative;">🏢 GPM <?= Security::e($namaFak) ?></h2>
    <p style="opacity:.8;position:relative;">Alur berjenjang: Prodi mengunci EDP → Anda mereview → LPM melihat hasil final. Tahun: <strong><?= $tahun ?></strong></p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">🏫</div><div><h3 style="font-size:28px;"><?= count($prodiList) ?></h3><p class="text-muted">Prodi Dibina</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">⏳</div><div><h3 style="font-size:28px;"><?= $nMenunggu ?></h3><p class="text-muted">Menunggu Review Anda</p></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><h3 style="font-size:28px;"><?= $nDisetujui ?></h3><p class="text-muted">Disetujui</p></div></div>
</div>

<?php if (!$detail): ?>
<div class="card">
    <h3 style="margin-bottom:16px;">📝 Antrean Review EDP <?= $tahun ?></h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Prodi</th><th>Progres EDP</th><th>Status Review GPM</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($antrean as $a): ?>
                <tr>
                    <td><strong><?= Security::e($a['prodi']['nama_prodi']) ?></strong></td>
                    <td><?= $a['terkunci'] ?>/<?= $a['total'] ?> standar terkunci</td>
                    <td>
                        <?php if (!$a['review']): ?>
                            <?= $a['terkunci'] > 0 ? '<span class="badge badge-b">⏳ Menunggu Review</span>' : '<span class="badge badge-baik">Belum dikunci prodi</span>' ?>
                        <?php else: ?>
                            <span class="badge <?= $a['review']['status'] === 'Disetujui' ? 'badge-unggul' : 'badge-unggul' ?>" style="<?= $a['review']['status'] === 'Dikembalikan' ? 'background:#FEE2E2;color:#991B1B' : '' ?>"><?= $a['review']['status'] === 'Disetujui' ? '✅ Disetujui' : '↩️ Dikembalikan' ?></span>
                        <?php endif; ?>
                    </td>
                    <td><a href="?detail=<?= $a['prodi']['id_prodi'] ?>&tahun=<?= $tahun ?>" class="btn btn-primary" style="padding:6px 16px;font-size:12px;">🔍 Review</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else:
    $rvNow = null;
    foreach ($antrean as $a) if ($a['prodi']['id_prodi'] == $detail) $rvNow = $a['review'];
?>
<a href="/sim/index.php" class="btn btn-outline" style="margin-bottom:20px;padding:8px 20px;font-size:13px;">← Antrean Review</a>
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">📋 EDP <?= $tahun ?> — baca dulu, lalu putuskan</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Standar</th><th>Capaian</th><th>Target</th><th>Status</th><th>Bukti / Analisis</th></tr></thead>
            <tbody>
            <?php foreach ($edpRows as $e): ?>
                <tr>
                    <td><strong><?= Security::e($e['kode_standar']) ?></strong><br><small class="text-muted"><?= Security::e($e['nama_standar']) ?></small></td>
                    <td><?= $e['capaian'] !== null ? $e['capaian'] . '%' : '—' ?></td>
                    <td><?= $e['target'] !== null ? $e['target'] . '%' : '—' ?></td>
                    <td><span class="badge <?= $e['status'] === 'Terkunci' ? 'badge-unggul' : 'badge-b' ?>"><?= Security::e($e['status']) ?></span></td>
                    <td style="font-size:13px;max-width:300px;"><?= Security::e(mb_strimwidth($e['bukti_fisik'] ?? '', 0, 120, '...')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">⚖️ Keputusan GPM</h3>
    <?php if ($rvNow): ?><p class="text-muted" style="margin-bottom:12px;">Keputusan terakhir: <strong><?= Security::e($rvNow['status']) ?></strong> — <?= Security::e($rvNow['catatan'] ?? '') ?></p><?php endif; ?>
    <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="id_prodi" value="<?= $detail ?>">
        <input type="text" name="catatan" class="form-control" style="flex:1;min-width:250px;" placeholder="Catatan untuk kaprodi (opsional)...">
        <button name="keputusan" value="Disetujui" class="btn btn-primary" style="background:var(--success);">✅ Setujui ke LPM</button>
        <button name="keputusan" value="Dikembalikan" class="btn btn-primary" style="background:var(--danger);" onclick="return confirm('Kembalikan EDP ke prodi?');">↩️ Kembalikan</button>
    </form>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>