<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';
$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+90 days'));

/* Konstanta validasi */
define('ALUR_STATUS', ['Draf', 'Telaah', 'Disetujui', 'Perlu Revisi']);
define('PERIODE_VALID', [6, 12, 24, 36, 48]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id_dokumen'] ?? 0);

    if ($id <= 0) { $error = '❌ ID dokumen tidak valid.'; }
    else {
        // Ambil data periode untuk kalkulasi tanggal
        $docStmt = $db->prepare("SELECT periode_review_bulan FROM dokumen_mutu WHERE id_dokumen = ?");
        $docStmt->execute([$id]);
        $per = (int)($docStmt->fetch()['periode_review_bulan'] ?? 24);

        // ================= SET STATUS ALUR =================
        if ($action === 'set_status') {
            $st = $_POST['alur'] ?? '';
            $catatan = mb_substr(trim($_POST['catatan'] ?? ''), 0, 255);

            if (!in_array($st, ALUR_STATUS, true)) {
                $error = '❌ Status alur tidak valid.';
            } elseif ($st === 'Perlu Revisi' && $catatan === '') {
                $error = '❌ Catatan revisi wajib diisi agar pemilik dokumen mengetahui apa yang harus diperbaiki.';
            } else {
                try {
                    $rev = null;
                    if ($st === 'Disetujui') $rev = date('Y-m-d', strtotime("+$per months"));
                    
                    $db->prepare("UPDATE dokumen_mutu SET alur_status = ?, catatan_review = ?, review_berikutnya = COALESCE(?, review_berikutnya) WHERE id_dokumen = ?")
                       ->execute([$st, $catatan, $rev, $id]);
                    
                    Logger::log('UPDATE', "Kendali dokumen #$id → $st" . ($catatan ? " (Catatan: $catatan)" : ""));
                    
                    if ($st === 'Disetujui') {
                        Notifier::sendRole(1, '📑 Dokumen Disetujui', 'Dokumen telah disetujui & dijadwalkan review berkala.', '/sim/admin/kendali-dokumen.php', '');
                    } elseif ($st === 'Perlu Revisi') {
                        Notifier::sendRole(1, '↩️ Dokumen Perlu Revisi', 'Dokumen dikembalikan untuk diperbaiki. Silakan cek catatan.', '/sim/admin/dokumen.php', '️');
                    }
                    $pesan = '✅ Status dokumen diperbarui.';
                } catch (PDOException $e) { $error = '❌ Gagal memperbarui status: ' . $e->getMessage(); }
            }
        }

        // ================= REVIEW DONE (Tutup Siklus) =================
        if ($action === 'review_done') {
            try {
                $db->prepare("UPDATE dokumen_mutu SET alur_status = 'Disetujui', review_berikutnya = ?, catatan_review = NULL WHERE id_dokumen = ?")
                   ->execute([date('Y-m-d', strtotime("+$per months")), $id]);
                Logger::log('UPDATE', "Review berkala dokumen #$id selesai, jadwal berikutnya +$per bulan");
                $pesan = "✅ Review ditutup — jadwal review berikutnya $per bulan lagi.";
            } catch (PDOException $e) { $error = '❌ Gagal menutup review: ' . $e->getMessage(); }
        }

        // ================= SET PERIODE =================
        if ($action === 'set_periode') {
            $p = (int)($_POST['periode'] ?? 0);
            if (!in_array($p, PERIODE_VALID, true)) {
                $error = '❌ Periode review tidak valid.';
            } else {
                try {
                    $db->prepare("UPDATE dokumen_mutu SET periode_review_bulan = ? WHERE id_dokumen = ?")
                       ->execute([$p, $id]);
                    Logger::log('UPDATE', "Periode review dokumen #$id diubah menjadi $p bulan");
                    $pesan = '✅ Periode review diubah.';
                } catch (PDOException $e) { $error = '❌ Gagal mengubah periode: ' . $e->getMessage(); }
            }
        }
    }
}

/* Query dengan JOIN users untuk menampilkan nama pengunggah */
$antrean = $db->query("SELECT d.*, k.nama_kategori, u.nama_lengkap 
    FROM dokumen_mutu d 
    LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori 
    LEFT JOIN users u ON d.id_user = u.id_user 
    WHERE d.alur_status IN ('Draf','Telaah','Perlu Revisi') 
    ORDER BY d.judul_dokumen")->fetchAll();

$review = $db->query("SELECT d.*, k.nama_kategori, u.nama_lengkap 
    FROM dokumen_mutu d 
    LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori 
    LEFT JOIN users u ON d.id_user = u.id_user 
    WHERE d.alur_status = 'Disetujui' 
    ORDER BY d.review_berikutnya ASC")->fetchAll();

$nAntre = count($antrean);
$nTelat = 0; $nSegera = 0;
foreach ($review as $r) {
    if ($r['review_berikutnya'] && $r['review_berikutnya'] < $today) $nTelat++;
    elseif ($r['review_berikutnya'] && $r['review_berikutnya'] <= $soon) $nSegera++;
}

function badgeReview($tgl, $today, $soon) {
    if (!$tgl) return '<span class="badge badge-baik">Tanpa Jadwal</span>';
    if ($tgl < $today) return '<span class="badge" style="background:#FEE2E2;color:#991B1B;">⏰ Jatuh Tempo</span>';
    if ($tgl <= $soon) return '<span class="badge" style="background:#FEF3C7;color:#92400E;">️ Review &lt; 90 hari</span>';
    return '<span class="badge badge-unggul">✅ Aman</span>';
}

$simTitle = 'Kendali Dokumen';
$activeMenu = 'kendok';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">📥</div><div><h3 style="font-size:28px;"><?= $nAntre ?></h3><p class="text-muted">Antrean Persetujuan</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">⚠️</div><div><h3 style="font-size:28px;"><?= $nSegera ?></h3><p class="text-muted">Review &lt; 90 Hari</p></div></div>
    <div class="stat-card"><div class="stat-icon red">⏰</div><div><h3 style="font-size:28px;"><?= $nTelat ?></h3><p class="text-muted">Jatuh Tempo</p></div></div>
</div>

<!-- ===== ANTREAN PERSETUJUAN ===== -->
<div class="card" style="margin-bottom:28px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;">🖊️ Antrean Persetujuan Dokumen</h3>
        <input type="text" id="cariAntrean" class="form-control" placeholder="🔍 Cari judul..." style="max-width:240px;">
    </div>
    <?php if (empty($antrean)): ?><p class="text-muted" style="text-align:center;padding:20px;">Tidak ada dokumen menunggu persetujuan. ✅</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table id="tabelAntrean">
            <thead><tr><th>Dokumen</th><th>Kategori</th><th>Status Alur</th><th>Catatan</th><th style="width:280px;">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($antrean as $d): ?>
                <tr class="row-antrean">
                    <td>
                        <strong><?= Security::e($d['judul_dokumen']) ?></strong><br>
                        <small class="text-muted"><?= Security::e($d['kode_dokumen'] ?? '—') ?> • v<?= (int)$d['versi'] ?> • Oleh: <?= Security::e($d['nama_lengkap'] ?? 'Sistem') ?></small>
                    </td>
                    <td><?= Security::e($d['nama_kategori'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $d['alur_status'] === 'Perlu Revisi' ? 'badge-unggul' : 'badge-b' ?>" 
                              style="<?= $d['alur_status'] === 'Perlu Revisi' ? 'background:#FEE2E2;color:#991B1B' : '' ?>">
                            <?= Security::e($d['alur_status']) ?>
                        </span>
                    </td>
                    <td style="font-size:13px;max-width:200px;"><?= Security::e($d['catatan_review'] ?? '—') ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="/download.php?id=<?= $d['id_dokumen'] ?>" class="btn btn-outline" style="padding:6px 10px;font-size:12px;" title="Unduh">📥</a>
                            <?php if ($d['alur_status'] === 'Draf' || $d['alur_status'] === 'Perlu Revisi'): ?>
                            <form method="POST" style="display:inline;"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="alur" value="Telaah"><button class="btn btn-outline" style="padding:6px 10px;font-size:12px;">🔍 Telaah</button></form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="alur" value="Disetujui"><button class="btn btn-primary" style="padding:6px 10px;font-size:12px;background:var(--success);">✅ Setujui</button></form>
                            <form method="POST" style="display:inline-flex;gap:4px;"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="alur" value="Perlu Revisi"><input type="text" name="catatan" class="form-control" style="padding:6px 8px;font-size:12px;width:120px;" placeholder="Alasan revisi..." required><button class="btn btn-outline" style="padding:6px 10px;font-size:12px;color:var(--danger);">↩️</button></form>
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
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;">🔁 Review Berkala (Siklus Kendali Dokumen)</h3>
            <p class="text-muted" style="font-size:13px;margin:4px 0 0;">Dokumen mutu wajib ditinjau ulang secara berkala — bukti kendali dokumen bagi asesor.</p>
        </div>
        <input type="text" id="cariReview" class="form-control" placeholder="🔍 Cari judul..." style="max-width:240px;">
    </div>
    <?php if (empty($review)): ?><p class="text-muted" style="text-align:center;padding:20px;">Belum ada dokumen yang disetujui untuk dijadwalkan review.</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table id="tabelReview">
            <thead><tr><th>Dokumen</th><th>Review Berikutnya</th><th>Status</th><th>Periode</th><th style="width:220px;">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($review as $d): ?>
                <tr class="row-review">
                    <td>
                        <strong><?= Security::e($d['judul_dokumen']) ?></strong><br>
                        <small class="text-muted"><?= Security::e($d['kode_dokumen'] ?? '—') ?> • v<?= (int)$d['versi'] ?> • Oleh: <?= Security::e($d['nama_lengkap'] ?? 'Sistem') ?></small>
                    </td>
                    <td><?= $d['review_berikutnya'] ? date('d M Y', strtotime($d['review_berikutnya'])) : '—' ?></td>
                    <td><?= badgeReview($d['review_berikutnya'], $today, $soon) ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:6px;align-items:center;"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="set_periode">
                            <select name="periode" class="form-control" style="width:auto;padding:6px 8px;font-size:12px;" onchange="this.form.submit()">
                                <?php foreach (PERIODE_VALID as $p): ?><option value="<?= $p ?>" <?= $d['periode_review_bulan'] == $p ? 'selected' : '' ?>><?= $p ?> bln</option><?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="/download.php?id=<?= $d['id_dokumen'] ?>" class="btn btn-outline" style="padding:6px 10px;font-size:12px;" title="Unduh">📥</a>
                            <form method="POST" style="display:inline;"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="review_done"><button class="btn btn-primary" style="padding:6px 10px;font-size:12px;" title="Tandai review selesai & perpanjang jadwal">🔄</button></form>
                            <form method="POST" style="display:inline;"><input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="alur" value="Draf"><button class="btn btn-outline" style="padding:6px 10px;font-size:12px;" title="Kembalikan ke Draf untuk revisi mayor">✏️</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
// Pencarian real-time untuk kedua tabel
(function() {
    var q1 = document.getElementById('cariAntrean');
    if (q1) q1.addEventListener('input', function() {
        var term = q1.value.toLowerCase();
        document.querySelectorAll('#tabelAntrean .row-antrean').forEach(function(tr) {
            tr.style.display = tr.textContent.toLowerCase().indexOf(term) > -1 ? '' : 'none';
        });
    });

    var q2 = document.getElementById('cariReview');
    if (q2) q2.addEventListener('input', function() {
        var term = q2.value.toLowerCase();
        document.querySelectorAll('#tabelReview .row-review').forEach(function(tr) {
            tr.style.display = tr.textContent.toLowerCase().indexOf(term) > -1 ? '' : 'none';
        });
    });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>