<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([5]);
$db = Database::getInstance();

$simTitle = 'Simulasi Akreditasi Fakultas';
$activeMenu = 'mockfak';

$fakId = null;
try {
    $me = $db->prepare("SELECT id_fakultas FROM users WHERE id_user = ?");
    $me->execute([Auth::id()]);
    $v = $me->fetchColumn();
    $fakId = ($v !== false && $v !== null) ? (int)$v : null;
} catch (Throwable $e) {}

$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$detailId = isset($_GET['detail']) ? (int)$_GET['detail'] : 0;
$tahunAjaran = $tahun . '/' . ($tahun + 1);

$list = [];
$detailHeader = null;
$detailRows = [];
$namaProdiDetail = '';

try {
    if ($fakId !== null) {
        $q = $db->prepare("SELECT p.id_prodi, p.nama_prodi, m.id_mock, m.total_skor, m.prediksi_peringkat, m.catatan_umum
                           FROM prodi p
                           LEFT JOIN mock_audit_header m ON m.id_prodi = p.id_prodi AND m.tahun_ajaran = ?
                           WHERE p.id_fakultas = ?
                           ORDER BY m.total_skor DESC, p.nama_prodi ASC");
        $q->execute([$tahunAjaran, $fakId]);
        $list = $q->fetchAll();

        if ($detailId > 0) {
            $chk = $db->prepare("SELECT nama_prodi FROM prodi WHERE id_prodi = ? AND id_fakultas = ?");
            $chk->execute([$detailId, $fakId]);
            $namaProdiDetail = (string)$chk->fetchColumn();
            if ($namaProdiDetail !== '') {
                $qh = $db->prepare("SELECT * FROM mock_audit_header WHERE id_prodi = ? AND tahun_ajaran = ? ORDER BY created_at DESC LIMIT 1");
                $qh->execute([$detailId, $tahunAjaran]);
                $detailHeader = $qh->fetch() ?: null;
                if ($detailHeader) {
                    $qd = $db->prepare("SELECT d.*, k.nomor, k.nama_kriteria
                                        FROM mock_audit_detail d
                                        JOIN kriteria_akreditasi k ON d.id_kriteria = k.id_kriteria
                                        WHERE d.id_mock = ? ORDER BY k.nomor ASC");
                    $qd->execute([$detailHeader['id_mock']]);
                    $detailRows = $qd->fetchAll();
                }
            } else {
                $detailId = 0;
            }
        }
    }
} catch (Throwable $e) {
    error_log('GPM mock-audit: ' . $e->getMessage());
}

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if ($fakId === null) {
    echo '<div class="alert alert-danger no-toast">⚠️ Akun Anda belum ditugaskan ke fakultas mana pun. Hubungi Admin LPM via menu 🏢 Penugasan GPM.</div>';
    require_once dirname(__DIR__, 2) . '/includes/footer-sim.php';
    exit;
}

function gpmBadgePeringkat(string $p): string {
    if ($p === 'Unggul') return 'badge-unggul';
    if ($p === 'Baik Sekali') return 'badge-a';
    if ($p === 'Baik') return 'badge-baik';
    return 'badge-b';
}

$skorSum = 0.0; $skorN = 0; $nUnggul = 0; $nBelum = 0;
foreach ($list as $m) {
    if ($m['total_skor'] !== null) {
        $skorSum += (float)$m['total_skor']; $skorN++;
        if ($m['prediksi_peringkat'] === 'Unggul') $nUnggul++;
    } else { $nBelum++; }
}
$rataSkor = $skorN > 0 ? round($skorSum / $skorN, 2) : 0;
?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
        <h2 style="margin:0;">🎯 Simulasi Akreditasi Fakultas</h2>
        <p class="text-muted" style="margin:4px 0 0;font-size:13px;">Hasil mock audit seluruh prodi binaan — tahun ajaran <?= Security::e($tahunAjaran) ?></p>
    </div>
    <form method="GET">
        <select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <?php for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>><?= $y ?>/<?= $y + 1 ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:24px;">
    <div class="stat-card"><div class="stat-icon gold">📊</div><div><h3 style="font-size:26px;"><?= number_format($rataSkor, 2) ?></h3><p class="text-muted">Rata-rata Skor</p></div></div>
    <div class="stat-card"><div class="stat-icon green">🏆</div><div><h3 style="font-size:26px;" data-count="<?= $nUnggul ?>"><?= $nUnggul ?></h3><p class="text-muted">Prediksi Unggul</p></div></div>
    <div class="stat-card"><div class="stat-icon blue">⏳</div><div><h3 style="font-size:26px;" data-count="<?= $nBelum ?>"><?= $nBelum ?></h3><p class="text-muted">Belum Dinilai</p></div></div>
</div>

<?php if ($detailId > 0 && $detailHeader): ?>
<!-- ===== DETAIL PER KRITERIA ===== -->
<a href="/sim/gpm/mock-audit.php?tahun=<?= $tahun ?>" class="btn btn-outline" style="margin-bottom:20px;padding:8px 18px;font-size:13px;">← Kembali ke Daftar</a>
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:4px;">📋 <?= Security::e($namaProdiDetail) ?></h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:20px;">
        Skor total <strong><?= number_format((float)$detailHeader['total_skor'], 2) ?>/4.00</strong> —
        <span class="badge <?= gpmBadgePeringkat((string)$detailHeader['prediksi_peringkat']) ?>"><?= Security::e((string)$detailHeader['prediksi_peringkat']) ?></span>
    </p>
    <?php foreach ($detailRows as $d):
        $pct = min(100, (int)round(((float)$d['skor_capaian'] / 4) * 100));
        $warnaBar = $d['skor_capaian'] >= 3.5 ? 'var(--success)' : ($d['skor_capaian'] >= 3.0 ? 'var(--accent)' : ($d['skor_capaian'] >= 2.5 ? 'var(--warning)' : 'var(--danger)'));
    ?>
        <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                <strong>K<?= (int)$d['nomor'] ?> — <?= Security::e($d['nama_kriteria']) ?></strong>
                <span><strong><?= number_format((float)$d['skor_capaian'], 2) ?></strong> • bukti <?= (int)$d['kelengkapan_bukti'] ?>%</span>
            </div>
            <div style="background:var(--border);border-radius:10px;height:8px;overflow:hidden;">
                <div style="width:<?= $pct ?>%;height:100%;background:<?= $warnaBar ?>;border-radius:10px;"></div>
            </div>
            <?php if (!empty($d['catatan_gap'])): ?>
                <small class="text-muted" style="display:block;margin-top:4px;">⚠️ <?= Security::e($d['catatan_gap']) ?></small>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<!-- ===== DAFTAR PRODI ===== -->
<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrapper" style="box-shadow:none;border:none;border-radius:0;">
        <table>
            <thead><tr><th>Prodi</th><th style="text-align:center;">Skor</th><th style="width:220px;">Progres ke 4.00</th><th>Prediksi</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr><td colspan="5" style="text-align:center;padding:36px;" class="text-muted">Belum ada data simulasi di fakultas Anda.</td></tr>
            <?php else: foreach ($list as $m):
                $skor = $m['total_skor'] !== null ? (float)$m['total_skor'] : null;
                $pct = $skor !== null ? min(100, (int)round(($skor / 4) * 100)) : 0;
            ?>
                <tr>
                    <td><strong><?= Security::e($m['nama_prodi']) ?></strong></td>
                    <td style="text-align:center;"><?= $skor !== null ? '<strong style="font-size:16px;">' . number_format($skor, 2) . '</strong>' : '<span class="text-muted">—</span>' ?></td>
                    <td>
                        <div style="background:var(--border);border-radius:10px;height:8px;overflow:hidden;">
                            <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,var(--primary),var(--accent));border-radius:10px;"></div>
                        </div>
                    </td>
                    <td>
                        <?php if ($m['prediksi_peringkat']): ?>
                            <span class="badge <?= gpmBadgePeringkat((string)$m['prediksi_peringkat']) ?>"><?= Security::e((string)$m['prediksi_peringkat']) ?></span>
                        <?php else: ?><span class="text-muted" style="font-size:12px;">Belum dinilai</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($m['id_mock']): ?>
                            <a href="?detail=<?= (int)$m['id_prodi'] ?>&tahun=<?= $tahun ?>" class="btn btn-outline" style="padding:6px 14px;font-size:12px;">🔍 Detail</a>
                        <?php else: ?><span class="text-muted" style="font-size:12px;">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>