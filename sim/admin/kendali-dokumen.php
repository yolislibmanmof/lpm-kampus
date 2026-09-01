<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = '';
$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+90 days'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id_dokumen'] ?? 0);

    $doc = $db->prepare("SELECT periode_review_bulan FROM dokumen_mutu WHERE id_dokumen = ?");
    $doc->execute([$id]);
    $per = (int)($doc->fetch()['periode_review_bulan'] ?? 24);

    if ($action === 'set_status') {
        $st = $_POST['alur'];
        $rev = null;
        if ($st === 'Disetujui') $rev = date('Y-m-d', strtotime("+$per months"));
        $db->prepare("UPDATE dokumen_mutu SET alur_status = ?, catatan_review = ?, review_berikutnya = COALESCE(?, review_berikutnya) WHERE id_dokumen = ?")
           ->execute([$st, trim($_POST['catatan'] ?? ''), $rev, $id]);
        Logger::log('UPDATE', "Kendali dokumen #$id → $st");
        if ($st === 'Disetujui') Notifier::sendRole(1, '📑 Dokumen Disetujui', 'Dokumen telah disetujui & dijadwalkan review berkala.', '/sim/admin/kendali-dokumen.php', '📑');
        $pesan = '✅ Status dokumen diperbarui.';
    }

    if ($action === 'review_done') {
        $db->prepare("UPDATE dokumen_mutu SET alur_status = 'Disetujui', review_berikutnya = ?, catatan_review = NULL WHERE id_dokumen = ?")
           ->execute([date('Y-m-d', strtotime("+$per months")), $id]);
        Logger::log('UPDATE', "Review dokumen #$id selesai, jadwal berikutnya +$per bulan");
        $pesan = "✅ Review ditutup — jadwal review berikutnya $per bulan lagi.";
    }

    if ($action === 'set_periode') {
        $db->prepare("UPDATE dokumen_mutu SET periode_review_bulan = ? WHERE id_dokumen = ?")
           ->execute([(int)$_POST['periode'], $id]);
        $pesan = '✅ Periode review diubah.';
    }
}

$antrean = $db->query("SELECT d.*, k.nama_kategori FROM dokumen_mutu d LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori WHERE d.alur_status IN ('Draf','Telaah','Perlu Revisi') ORDER BY d.judul_dokumen")->fetchAll();
$review = $db->query("SELECT d.*, k.nama_kategori FROM dokumen_mutu d LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori WHERE d.alur_status = 'Disetujui' ORDER BY d.review_berikutnya ASC")->fetchAll();

$nAntre = count($antrean);
$nTelat = 0; $nSegera = 0;
foreach ($review as $r) {
    if ($r['review_berikutnya'] && $r['review_berikutnya'] < $today) $nTelat++;
    elseif ($r['review_berikutnya'] && $r['review_berikutnya'] <= $soon) $nSegera++;
}

function badgeReview($tgl, $today, $soon) {
    if (!$tgl) return '<span class="badge badge-baik">Tanpa Jadwal</span>';
    if ($tgl < $today) return '<span class="badge" style="background:#FEE2E2;color:#991B1B;">⏰ Jatuh Tempo</span>';
    if ($tgl <= $soon) return '<span class="badge" style="background:#FEF3C7;color:#92400E;">⚠️ Review &lt; 90 hari</span>';
    return '<span class="badge badge-unggul">✅ Aman</span>';
}

$simTitle = 'Kendali Dokumen';
$activeMenu = 'kendok';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">📥</div><div><h3 style="font-size:28px;"><?= $nAntre ?></h3><p class="text-muted">Antrean Persetujuan</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">⚠️</div><div><h3 style="font-size:28px;"><?= $nSegera ?></h3><p class="text-muted">Review &lt; 90 Hari</p></div></div>
    <div class="stat-card"><div class="stat-icon red">⏰</div><div><h3 style="font-size:28px;"><?= $nTelat ?></h3><p class="text-muted">Jatuh Tempo</p></div></div>
</div>

<!-- ===== ANTREAN PERSETUJUAN ===== -->
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:16px;">🖊️ Antrean Persetujuan Dokumen</h3>
    <?php if (empty($antrean)): ?><p class="text-muted">Tidak ada dokumen menunggu persetujuan. ✅</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Dokumen</th><th>Kategori</th><th>Status Alur</th><th>Catatan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($antrean as $d): ?>
                <tr>
                    <td><strong><?= Security::e($d['judul_dokumen']) ?></strong><br><small class="text-muted"><?= Security::e($d['kode_dokumen']) ?> • v<?= Security::e($d['versi']) ?></small></td>
                    <td><?= Security::e($d['nama_kategori']) ?></td>
                    <td><span class="badge <?= $d['alur_status'] === 'Perlu Revisi' ? 'badge-unggul' : 'badge-b' ?>" style="<?= $d['alur_status'] === 'Perlu Revisi' ? 'background:#FEE2E2;color:#991B1B' : '' ?>"><?= Security::e($d['alur_status']) ?></span></td>
                    <td style="font-size:13px;"><?= Security::e($d['catatan_review'] ?? '—') ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <?php if ($d['alur_status'] === 'Draf'): ?>
                            <form method="POST"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="alur" value="Telaah"><button class="btn btn-outline" style="padding:6px 12px;font-size:12px;">🔍 Ke Telaah</button></form>
                            <?php endif; ?>
                            <form method="POST"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="alur" value="Disetujui"><button class="btn btn-primary" style="padding:6px 12px;font-size:12px;background:var(--success);">✅ Setujui</button></form>
                            <form method="POST" style="display:flex;gap:6px;"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="alur" value="Perlu Revisi"><input type="text" name="catatan" class="form-control" style="padding:6px 10px;font-size:12px;width:150px;" placeholder="catatan revisi..."><button class="btn btn-outline" style="padding:6px 12px;font-size:12px;color:var(--danger);">↩️ Revisi</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ===== JADWAL REVIEW BERKALA ===== -->
<div class="card">
    <h3 style="margin-bottom:6px;">🔁 Review Berkala (Siklus Kendali Dokumen)</h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:16px;">Dokumen mutu wajib ditinjau ulang secara berkala (default 24 bulan) — bukti kendali dokumen bagi asesor.</p>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Dokumen</th><th>Review Berikutnya</th><th>Status</th><th>Periode</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($review as $d): ?>
                <tr>
                    <td><strong><?= Security::e($d['judul_dokumen']) ?></strong><br><small class="text-muted"><?= Security::e($d['kode_dokumen']) ?></small></td>
                    <td><?= $d['review_berikutnya'] ? date('d M Y', strtotime($d['review_berikutnya'])) : '—' ?></td>
                    <td><?= badgeReview($d['review_berikutnya'], $today, $soon) ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:6px;align-items:center;"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="set_periode">
                            <select name="periode" class="form-control" style="width:auto;padding:6px 8px;font-size:12px;" onchange="this.form.submit()">
                                <?php foreach ([12, 24, 36] as $p): ?><option value="<?= $p ?>" <?= $d['periode_review_bulan'] == $p ? 'selected' : '' ?>><?= $p ?> bln</option><?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <form method="POST"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="review_done"><button class="btn btn-primary" style="padding:6px 12px;font-size:12px;">🔄 Review Selesai</button></form>
                            <form method="POST"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="alur" value="Draf"><button class="btn btn-outline" style="padding:6px 12px;font-size:12px;">✏️ Ke Draf</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>