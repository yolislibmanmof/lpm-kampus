<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([5]);
$db = Database::getInstance();

$simTitle = 'Risiko Fakultas';
$activeMenu = 'riskfak';

/* ===== Identitas fakultas ===== */
$fakId = null;
try {
    $me = $db->prepare("SELECT id_fakultas FROM users WHERE id_user = ?");
    $me->execute([Auth::id()]);
    $v = $me->fetchColumn();
    $fakId = ($v !== false && $v !== null) ? (int)$v : null;
} catch (Throwable $e) {}

/* ===== Filter ===== */
$fProdi  = isset($_GET['prodi']) ? (int)$_GET['prodi'] : 0;
$fStatus = (string)($_GET['status'] ?? '');

/* ===== Ambil data (sebelum header, agar export CSV bisa kirim header HTTP) ===== */
$risks = [];
$prodiOptions = [];
$error = '';
try {
    $prodiOptions = $db->prepare("SELECT id_prodi, nama_prodi FROM prodi WHERE id_fakultas = ? ORDER BY nama_prodi");
    $prodiOptions->execute([$fakId === null ? -1 : $fakId]);
    $prodiOptions = $prodiOptions->fetchAll();

    if ($fakId !== null) {
        $sql = "SELECT r.*, p.nama_prodi, u.nama_lengkap AS nama_pic
                FROM risk_register r
                JOIN prodi p ON r.id_prodi = p.id_prodi
                LEFT JOIN users u ON r.id_user_pic = u.id_user
                WHERE p.id_fakultas = ?";
        $params = [$fakId];
        if ($fProdi > 0) { $sql .= " AND r.id_prodi = ?"; $params[] = $fProdi; }
        if ($fStatus !== '') { $sql .= " AND r.status = ?"; $params[] = $fStatus; }
        $sql .= " ORDER BY r.skor_risiko DESC, r.id_risk DESC";
        $q = $db->prepare($sql);
        $q->execute($params);
        $risks = $q->fetchAll();
    }
} catch (Throwable $e) {
    $error = 'Gagal memuat data risiko: ' . $e->getMessage();
    error_log('GPM risk-register: ' . $e->getMessage());
}

/* ===== Export CSV ===== */
if (isset($_GET['export']) && $fakId !== null) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="risk_register_fakultas_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['Prodi', 'Judul Risiko', 'Dampak', 'Probabilitas', 'Skor', 'Level', 'PIC', 'Status', 'Rencana Mitigasi'], ';');
    foreach ($risks as $r) {
        fputcsv($out, [
            $r['nama_prodi'], $r['judul_risiko'], $r['dampak'], $r['probabilitas'],
            $r['skor_risiko'], $r['level_risiko'], $r['nama_pic'], $r['status'], $r['rencana_mitigasi']
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

/* ===== Statistik & heatmap dari data yang sudah diambil ===== */
$total = count($risks);
$nEkstrem = 0; $nTinggi = 0; $nAktif = 0;
$heatmap = [];
for ($d = 5; $d >= 1; $d--) { for ($p = 1; $p <= 5; $p++) { $heatmap[$d][$p] = 0; } }

foreach ($risks as $r) {
    if ($r['level_risiko'] === 'Ekstrem') $nEkstrem++;
    if ($r['level_risiko'] === 'Tinggi') $nTinggi++;
    if (in_array($r['status'], ['Teridentifikasi', 'Dalam Mitigasi'], true)) $nAktif++;
    $d = (int)$r['dampak']; $p = (int)$r['probabilitas'];
    if ($r['status'] !== 'Termitigasi' && isset($heatmap[$d][$p])) $heatmap[$d][$p]++;
}

function gpmRiskColor(int $skor): array {
    if ($skor >= 20) return ['#EF4444', 'rgba(239,68,68,0.12)'];
    if ($skor >= 12) return ['#F59E0B', 'rgba(245,158,11,0.12)'];
    if ($skor >= 5)  return ['#3B82F6', 'rgba(59,130,246,0.12)'];
    return ['#10B981', 'rgba(16,185,129,0.12)'];
}
?>

<?php if ($error !== ''): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
        <h2 style="margin:0;">⚠️ Register Risiko Fakultas</h2>
        <p class="text-muted" style="margin:4px 0 0;font-size:13px;">Monitor seluruh risiko prodi di bawah binaan fakultas Anda.</p>
    </div>
    <a href="?<?= Security::e(http_build_query(array_merge($_GET, ['export' => 1]))) ?>" class="btn btn-outline" style="padding:8px 18px;font-size:13px;">📤 Export CSV</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:24px;">
    <div class="stat-card"><div class="stat-icon blue">📋</div><div><h3 style="font-size:26px;" data-count="<?= $total ?>"><?= $total ?></h3><p class="text-muted">Total Risiko</p></div></div>
    <div class="stat-card"><div class="stat-icon red">🔴</div><div><h3 style="font-size:26px;" data-count="<?= $nEkstrem ?>"><?= $nEkstrem ?></h3><p class="text-muted">Level Ekstrem</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">🟠</div><div><h3 style="font-size:26px;" data-count="<?= $nTinggi ?>"><?= $nTinggi ?></h3><p class="text-muted">Level Tinggi</p></div></div>
    <div class="stat-card"><div class="stat-icon green">⏳</div><div><h3 style="font-size:26px;" data-count="<?= $nAktif ?>"><?= $nAktif ?></h3><p class="text-muted">Belum Termitigasi</p></div></div>
</div>

<!-- FILTER -->
<div class="card" style="margin-bottom:24px;">
    <form method="GET" style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Filter Prodi</label>
            <select name="prodi" class="form-control" onchange="this.form.submit()">
                <option value="0">Semua Prodi</option>
                <?php foreach ($prodiOptions as $p): ?>
                    <option value="<?= (int)$p['id_prodi'] ?>" <?= $fProdi === (int)$p['id_prodi'] ? 'selected' : '' ?>><?= Security::e($p['nama_prodi']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Filter Status</label>
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <?php foreach (['Teridentifikasi', 'Dalam Mitigasi', 'Termitigasi', 'Diterima (Risk Accepted)'] as $s): ?>
                    <option value="<?= Security::e($s) ?>" <?= $fStatus === $s ? 'selected' : '' ?>><?= Security::e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height:46px;">🔍 Terapkan</button>
    </form>
</div>

<div style="display:grid;grid-template-columns:340px 1fr;gap:24px;">
    <!-- HEATMAP -->
    <div class="card">
        <h3 style="margin-bottom:6px;">🗺️ Heatmap Fakultas</h3>
        <p class="text-muted" style="font-size:12px;margin-bottom:14px;">Dampak (Y) × Probabilitas (X) — risiko aktif</p>
        <div style="display:grid;grid-template-columns:26px repeat(5,1fr);gap:5px;">
            <div></div>
            <?php for ($p = 1; $p <= 5; $p++): ?><div class="text-muted" style="text-align:center;font-size:11px;font-weight:700;">P<?= $p ?></div><?php endfor; ?>
            <?php for ($d = 5; $d >= 1; $d--): ?>
                <div class="text-muted" style="display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;">D<?= $d ?></div>
                <?php for ($p = 1; $p <= 5; $p++):
                    $skor = $d * $p;
                    list($warna, $bg) = gpmRiskColor($skor);
                    $cnt = $heatmap[$d][$p];
                ?>
                <div style="min-height:44px;border-radius:8px;background:<?= $bg ?>;border:1px solid <?= $warna ?>40;color:<?= $warna ?>;display:grid;place-items:center;font-weight:800;font-size:15px;">
                    <?= $cnt > 0 ? $cnt : '' ?>
                </div>
                <?php endfor; ?>
            <?php endfor; ?>
        </div>
    </div>

    <!-- TABEL -->
    <div class="card" style="padding:0;overflow:hidden;">
        <div class="table-wrapper" style="box-shadow:none;border:none;border-radius:0;">
            <table>
                <thead><tr><th>Risiko / Prodi</th><th style="text-align:center;">Skor</th><th>Level</th><th>PIC</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($risks)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:36px;" class="text-muted">Belum ada risiko terdaftar di fakultas Anda.</td></tr>
                <?php else: foreach ($risks as $r):
                    list($warna, $bg) = gpmRiskColor((int)$r['skor_risiko']);
                ?>
                    <tr>
                        <td>
                            <strong><?= Security::e($r['judul_risiko']) ?></strong><br>
                            <small class="text-muted"><?= Security::e($r['nama_prodi']) ?></small>
                            <div class="text-muted" style="font-size:12px;margin-top:4px;" title="<?= Security::e($r['rencana_mitigasi']) ?>">
                                ️ <?= Security::e(mb_strimwidth((string)$r['rencana_mitigasi'], 0, 70, '...')) ?>
                            </div>
                        </td>
                        <td style="text-align:center;"><strong style="font-size:16px;"><?= (int)$r['skor_risiko'] ?></strong><br><small class="text-muted"><?= (int)$r['dampak'] ?>×<?= (int)$r['probabilitas'] ?></small></td>
                        <td><span class="badge" style="background:<?= $bg ?>;color:<?= $warna ?>;font-weight:800;"><?= Security::e($r['level_risiko']) ?></span></td>
                        <td><small><?= Security::e((string)($r['nama_pic'] ?? '—')) ?></small></td>
                        <td><span class="badge badge-b"><?= Security::e($r['status']) ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>