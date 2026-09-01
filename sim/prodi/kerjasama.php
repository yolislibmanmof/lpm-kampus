<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = ''; $error = '';
$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'Kerja Sama / MoU';
$activeMenu = 'kerjasama';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun belum terhubung ke prodi.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $file = null;
        if (!empty($_FILES['file']['name'])) {
            $up = Security::secureUpload($_FILES['file'], 'kerjasama');
            if ($up['success']) $file = $up['path']; else $error = $up['message'];
        }
        if (!$error) {
            if ($action === 'add') {
                $db->prepare("INSERT INTO kerjasama_prodi (id_prodi, mitra, jenis, tingkat, nomor_mou, tanggal_mulai, tanggal_berakhir, tindak_lanjut, file_path) VALUES (?,?,?,?,?,?,?,?,?)")
                   ->execute([$prodiId, trim($_POST['mitra']), trim($_POST['jenis']), $_POST['tingkat'], trim($_POST['nomor_mou']), $_POST['tanggal_mulai'] ?: null, $_POST['tanggal_berakhir'] ?: null, trim($_POST['tindak_lanjut']), $file]);
                $pesan = '✅ Kerja sama tercatat.';
            } else {
                if (!$file) { $old = $db->prepare("SELECT file_path FROM kerjasama_prodi WHERE id_kerjasama=?"); $old->execute([(int)$_POST['id_kerjasama']]); $file = $old->fetch()['file_path']; }
                $db->prepare("UPDATE kerjasama_prodi SET mitra=?, jenis=?, tingkat=?, nomor_mou=?, tanggal_mulai=?, tanggal_berakhir=?, tindak_lanjut=?, file_path=? WHERE id_kerjasama=? AND id_prodi=?")
                   ->execute([trim($_POST['mitra']), trim($_POST['jenis']), $_POST['tingkat'], trim($_POST['nomor_mou']), $_POST['tanggal_mulai'] ?: null, $_POST['tanggal_berakhir'] ?: null, trim($_POST['tindak_lanjut']), $file, (int)$_POST['id_kerjasama'], $prodiId]);
                $pesan = '✅ Kerja sama diperbarui.';
            }
        }
    }
    if ($action === 'matriks') {
        $ks = $db->prepare("SELECT * FROM kerjasama_prodi WHERE id_kerjasama = ? AND id_prodi = ?");
        $ks->execute([(int)$_POST['id_kerjasama'], $prodiId]);
        $k = $ks->fetch();
        if ($k) {
            $kr = $db->prepare("SELECT id_kriteria FROM kriteria_akreditasi WHERE nomor = 6"); $kr->execute([]);
            $kid = $kr->fetch()['id_kriteria'] ?? null;
            if ($kid) {
                $db->prepare("INSERT INTO bukti_kriteria (id_prodi, id_kriteria, indikator, nama_bukti, file_path, status, tahun) VALUES (?,6,'Kerja sama mitra " . " ','MoU: ' . ?, ?, 'Lengkap', ?)") ;
                // (dibangun ulang di bawah agar aman)
            }
            $db->prepare("INSERT INTO bukti_kriteria (id_prodi, id_kriteria, indikator, nama_bukti, file_path, status, tahun) VALUES (?,?,?,?,?,'Lengkap',?)")
               ->execute([$prodiId, $kid, 'Kerja sama tingkat ' . $k['tingkat'], 'MoU: ' . $k['mitra'] . ' (' . ($k['nomor_mou'] ?: '-') . ')', $k['file_path'], date('Y')]);
            $pesan = '📌 MoU masuk Matriks Bukti K6.';
        }
    }
    if ($action === 'del') {
        $db->prepare("DELETE FROM kerjasama_prodi WHERE id_kerjasama = ? AND id_prodi = ?")->execute([(int)$_POST['id_kerjasama'], $prodiId]);
        $pesan = '✅ Kerja sama dihapus.';
    }
}

$list = $db->prepare("SELECT * FROM kerjasama_prodi WHERE id_prodi = ? ORDER BY tanggal_berakhir ASC");
$list->execute([$prodiId]); $list = $list->fetchAll();
$today = time(); $soon = date('Y-m-d', strtotime('+90 days'));
$nAktif = count(array_filter($list, fn($k) => !$k['tanggal_berakhir'] || $k['tanggal_berakhir'] >= date('Y-m-d')));
$nIntl = count(array_filter($list, fn($k) => $k['tingkat'] === 'Internasional'));
$nSoon = count(array_filter($list, fn($k) => $k['tanggal_berakhir'] && $k['tanggal_berakhir'] <= $soon));

$edit = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM kerjasama_prodi WHERE id_kerjasama = ? AND id_prodi = ?");
    $st->execute([(int)$_GET['edit'], $prodiId]); $edit = $st->fetch();
}
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon green">🤝</div><div><h3 style="font-size:28px;"><?= $nAktif ?></h3><p class="text-muted">MoU Aktif</p></div></div>
    <div class="stat-card"><div class="stat-icon blue">🌍</div><div><h3 style="font-size:28px;"><?= $nIntl ?></h3><p class="text-muted">Internasional</p></div></div>
    <div class="stat-card"><div class="stat-icon <?= $nSoon ? 'red' : 'gold' ?>">⏰</div><div><h3 style="font-size:28px;"><?= $nSoon ?></h3><p class="text-muted">Perlu Perpanjangan (&lt;90 hari)</p></div></div>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:16px;"><?= $edit ? '✏️ Edit Kerja Sama' : '➕ Catat Kerja Sama / MoU' ?></h3>
    <?php if ($edit): ?><a href="/sim/prodi/kerjasama.php" class="btn btn-outline" style="padding:5px 14px;font-size:12px;margin-bottom:14px;">✕ Batal</a><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1.6fr 1fr .9fr 1fr 1fr 1fr 1.4fr auto;gap:10px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?><input type="hidden" name="id_kerjasama" value="<?= $edit['id_kerjasama'] ?>"><?php endif; ?>
        <div class="form-group" style="margin:0;"><label class="form-label">Nama Mitra *</label>
            <input type="text" name="mitra" class="form-control" value="<?= Security::e($edit['mitra'] ?? '') ?>" placeholder="cth: PT Telkom Indonesia" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Jenis</label>
            <input type="text" name="jenis" class="form-control" value="<?= Security::e($edit['jenis'] ?? '') ?>" placeholder="Industri/Pemda/PT"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tingkat</label>
            <select name="tingkat" class="form-control">
                <?php foreach (['Lokal','Regional','Nasional','Internasional'] as $t): ?><option <?= ($edit['tingkat'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">No. MoU</label>
            <input type="text" name="nomor_mou" class="form-control" value="<?= Security::e($edit['nomor_mou'] ?? '') ?>"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Mulai</label>
            <input type="date" name="tanggal_mulai" class="form-control" value="<?= Security::e($edit['tanggal_mulai'] ?? '') ?>"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Berakhir</label>
            <input type="date" name="tanggal_berakhir" class="form-control" value="<?= Security::e($edit['tanggal_berakhir'] ?? '') ?>"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tindak Lanjut</label>
            <input type="text" name="tindak_lanjut" class="form-control" value="<?= Security::e($edit['tindak_lanjut'] ?? '') ?>" placeholder="Magang/Dosen tamu/Riset"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">File</label>
            <input type="file" name="file" class="form-control" accept="application/pdf"></div>
        <button class="btn btn-gold">💾</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Daftar Kerja Sama</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Mitra</th><th>Tingkat</th><th>No. MoU</th><th>Berakhir</th><th>Tindak Lanjut</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada kerja sama tercatat.</td></tr><?php endif; ?>
            <?php foreach ($list as $k):
                $exp = $k['tanggal_berakhir'] && $k['tanggal_berakhir'] <= $soon;
            ?>
                <tr>
                    <td><strong><?= Security::e($k['mitra']) ?></strong><br><small class="text-muted"><?= Security::e($k['jenis']) ?></small></td>
                    <td><span class="badge <?= $k['tingkat'] === 'Internasional' ? 'badge-unggul' : 'badge-baik' ?>"><?= $k['tingkat'] ?></span></td>
                    <td><?= Security::e($k['nomor_mou'] ?: '—') ?></td>
                    <td><?= $k['tanggal_berakhir'] ? date('d M Y', strtotime($k['tanggal_berakhir'])) : '—' ?>
                        <?php if ($exp): ?><br><span class="badge" style="background:#FEF3C7;color:#92400E;">⏰ Segera perpanjang</span><?php endif; ?></td>
                    <td style="font-size:13px;"><?= Security::e($k['tindak_lanjut']) ?></td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap;">
                        <form method="POST"><input type="hidden" name="id_kerjasama" value="<?= $k['id_kerjasama'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="matriks"><button class="btn btn-primary" style="padding:5px 10px;font-size:11px;">📌 K6</button></form>
                        <a href="?edit=<?= $k['id_kerjasama'] ?>" class="btn btn-outline" style="padding:5px 10px;font-size:11px;">✏️</a>
                        <form method="POST" onsubmit="return confirm('Hapus?');"><input type="hidden" name="id_kerjasama" value="<?= $k['id_kerjasama'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del"><button class="btn btn-outline" style="padding:5px 10px;font-size:11px;color:var(--danger);">🗑️</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>