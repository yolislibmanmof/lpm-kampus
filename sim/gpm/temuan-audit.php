<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([5]);
$db = Database::getInstance();

$simTitle = 'Temuan Audit Fakultas';
$activeMenu = 'temuanfak';

$fakId = null;
try {
    $me = $db->prepare("SELECT id_fakultas FROM users WHERE id_user = ?");
    $me->execute([Auth::id()]);
    $v = $me->fetchColumn();
    $fakId = ($v !== false && $v !== null) ? (int)$v : null;
} catch (Throwable $e) {}

$fProdi = isset($_GET['prodi']) ? (int)$_GET['prodi'] : 0;
$fKat   = (string)($_GET['kategori'] ?? '');
$fVerif = (string)($_GET['verif'] ?? '');

$temuan = [];
$prodiOptions = [];
try {
    if ($fakId !== null) {
        $po = $db->prepare("SELECT id_prodi, nama_prodi FROM prodi WHERE id_fakultas = ? ORDER BY nama_prodi");
        $po->execute([$fakId]);
        $prodiOptions = $po->fetchAll();

        $sql = "SELECT t.id_temuan, t.kategori, t.deskripsi_temuan, t.tindakan_koreksi, t.tanggal_koreksi,
                       t.status_verifikasi, t.catatan_verifikasi, pa.tanggal_audit,
                       p.id_prodi, p.nama_prodi, u.nama_lengkap AS auditor
                FROM temuan_audit t
                JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
                JOIN prodi p ON pa.id_prodi = p.id_prodi
                LEFT JOIN users u ON pa.id_auditor = u.id_user
                WHERE p.id_fakultas = ?";
        $params = [$fakId];
        if ($fProdi > 0) { $sql .= " AND pa.id_prodi = ?"; $params[] = $fProdi; }
        if ($fKat !== '') { $sql .= " AND t.kategori = ?"; $params[] = $fKat; }
        if ($fVerif !== '') { $sql .= " AND t.status_verifikasi = ?"; $params[] = $fVerif; }
        $sql .= " ORDER BY CASE t.kategori WHEN 'Mayor' THEN 1 WHEN 'Minor' THEN 2 WHEN 'Observasi' THEN 3 ELSE 4 END, t.id_temuan DESC";
        $q = $db->prepare($sql);
        $q->execute($params);
        $temuan = $q->fetchAll();
    }
} catch (Throwable $e) {
    error_log('GPM temuan-audit: ' . $e->getMessage());
}

/* ===== Export CSV ===== */
if (isset($_GET['export']) && $fakId !== null) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="temuan_audit_fakultas_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['Prodi', 'Kategori', 'Deskripsi Temuan', 'Tindakan Koreksi', 'Status Verifikasi', 'Tanggal Audit', 'Auditor'], ';');
    foreach ($temuan as $t) {
        fputcsv($out, [
            $t['nama_prodi'], $t['kategori'], $t['deskripsi_temuan'], $t['tindakan_koreksi'],
            $t['status_verifikasi'], $t['tanggal_audit'], $t['auditor']
        ], ';');
    }
    fclose($out);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if ($fakId === null) {
    echo '<div class="alert alert-danger no-toast">⚠️ Akun Anda belum ditugaskan ke fakultas mana pun. Hubungi Admin LPM via menu 🏢 Penugasan GPM.</div>';
    require_once dirname(__DIR__, 2) . '/includes/footer-sim.php';
    exit;
}

$nMayor = 0; $nMenunggu = 0; $nDiterima = 0;
foreach ($temuan as $t) {
    if ($t['kategori'] === 'Mayor') $nMayor++;
    if ($t['status_verifikasi'] === 'Menunggu') $nMenunggu++;
    if ($t['status_verifikasi'] === 'Diterima') $nDiterima++;
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
        <h2 style="margin:0;">🔍 Temuan Audit Fakultas</h2>
        <p class="text-muted" style="margin:4px 0 0;font-size:13px;">Pantau temuan AMI dan tindak lanjut koreksi di prodi binaan Anda.</p>
    </div>
    <a href="?<?= Security::e(http_build_query(array_merge($_GET, ['export' => 1]))) ?>" class="btn btn-outline" style="padding:8px 18px;font-size:13px;">📤 Export CSV</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:24px;">
    <div class="stat-card"><div class="stat-icon blue">📋</div><div><h3 style="font-size:26px;" data-count="<?= count($temuan) ?>"><?= count($temuan) ?></h3><p class="text-muted">Total Temuan</p></div></div>
    <div class="stat-card"><div class="stat-icon red">🔴</div><div><h3 style="font-size:26px;" data-count="<?= $nMayor ?>"><?= $nMayor ?></h3><p class="text-muted">Kategori Mayor</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">⏳</div><div><h3 style="font-size:26px;" data-count="<?= $nMenunggu ?>"><?= $nMenunggu ?></h3><p class="text-muted">Menunggu Verifikasi</p></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><h3 style="font-size:26px;" data-count="<?= $nDiterima ?>"><?= $nDiterima ?></h3><p class="text-muted">Koreksi Diterima</p></div></div>
</div>

<!-- FILTER -->
<div class="card" style="margin-bottom:24px;">
    <form method="GET" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Prodi</label>
            <select name="prodi" class="form-control" onchange="this.form.submit()">
                <option value="0">Semua Prodi</option>
                <?php foreach ($prodiOptions as $p): ?>
                    <option value="<?= (int)$p['id_prodi'] ?>" <?= $fProdi === (int)$p['id_prodi'] ? 'selected' : '' ?>><?= Security::e($p['nama_prodi']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Kategori</label>
            <select name="kategori" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                <?php foreach (['Mayor', 'Minor', 'Observasi', 'Kondisi Baik'] as $k): ?>
                    <option value="<?= $k ?>" <?= $fKat === $k ? 'selected' : '' ?>><?= $k ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Status Verifikasi</label>
            <select name="verif" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <?php foreach (['Menunggu', 'Diterima', 'Ditolak'] as $s): ?>
                    <option value="<?= $s ?>" <?= $fVerif === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height:46px;">🔍 Terapkan</button>
    </form>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrapper" style="box-shadow:none;border:none;border-radius:0;">
        <table>
            <thead><tr><th>Prodi / Auditor</th><th>Kategori</th><th>Temuan & Koreksi</th><th>Tanggal Audit</th><th>Verifikasi</th></tr></thead>
            <tbody>
            <?php if (empty($temuan)): ?>
                <tr><td colspan="5" style="text-align:center;padding:36px;" class="text-muted">Tidak ada temuan yang cocok dengan filter.</td></tr>
            <?php else: foreach ($temuan as $t):
                $badgeK = $t['kategori'] === 'Mayor' ? 'badge-danger' : ($t['kategori'] === 'Minor' ? 'badge-warning' : ($t['kategori'] === 'Observasi' ? 'badge-b' : 'badge-success'));
                $badgeV = $t['status_verifikasi'] === 'Menunggu' ? 'badge-warning' : ($t['status_verifikasi'] === 'Diterima' ? 'badge-success' : 'badge-danger');
            ?>
                <tr>
                    <td>
                        <strong><?= Security::e($t['nama_prodi']) ?></strong><br>
                        <small class="text-muted">Auditor: <?= Security::e((string)($t['auditor'] ?? '—')) ?></small>
                    </td>
                    <td><span class="badge <?= $badgeK ?>"><?= Security::e($t['kategori']) ?></span></td>
                    <td style="max-width:380px;">
                        <div style="font-size:13px;"><?= Security::e(mb_strimwidth((string)$t['deskripsi_temuan'], 0, 140, '...')) ?></div>
                        <?php if (!empty($t['tindakan_koreksi'])): ?>
                            <small class="text-muted" style="display:block;margin-top:4px;">🛠️ <?= Security::e(mb_strimwidth((string)$t['tindakan_koreksi'], 0, 90, '...')) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><small><?= $t['tanggal_audit'] ? date('d M Y', strtotime($t['tanggal_audit'])) : '—' ?></small></td>
                    <td><span class="badge <?= $badgeV ?>"><?= Security::e($t['status_verifikasi']) ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>