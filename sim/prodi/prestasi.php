<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = ''; $error = '';
$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'Bank Prestasi';
$activeMenu = 'prestasi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun belum terhubung ke prodi.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $file = null;
        if (!empty($_FILES['file']['name'])) {
            $up = Security::secureUpload($_FILES['file'], 'prestasi');
            if ($up['success']) $file = $up['path']; else $error = $up['message'];
        }
        if (!$error) {
            $db->prepare("INSERT INTO prestasi_prodi (id_prodi, jenis, judul, tingkat, tahun, penyelenggara, file_path) VALUES (?,?,?,?,?,?,?)")
               ->execute([$prodiId, $_POST['jenis'], trim($_POST['judul']), $_POST['tingkat'], (int)$_POST['tahun'], trim($_POST['penyelenggara']), $file]);
            $pesan = '✅ Prestasi tercatat.';
        }
    }

    if ($action === 'matriks') {
        $pr = $db->prepare("SELECT * FROM prestasi_prodi WHERE id_prestasi = ? AND id_prodi = ?");
        $pr->execute([(int)$_POST['id_prestasi'], $prodiId]);
        $p = $pr->fetch();
        if ($p) {
            $nomor = $p['jenis'] === 'Mahasiswa' ? 9 : 7; // Mahasiswa → K9, Dosen → K7
            $kr = $db->prepare("SELECT id_kriteria FROM kriteria_akreditasi WHERE nomor = ?");
            $kr->execute([$nomor]);
            $kid = $kr->fetch()['id_kriteria'] ?? null;
            if (!$kid) { $error = 'Kriteria akreditasi belum ada. Jalankan SQL seed 9 kriteria terlebih dahulu.'; }
            else {
                $db->prepare("INSERT INTO bukti_kriteria (id_prodi, id_kriteria, indikator, nama_bukti, file_path, status, tahun) VALUES (?,?,?,?,?,'Lengkap',?)")
                   ->execute([$prodiId, $kid, 'Prestasi ' . strtolower($p['jenis']) . ' tingkat ' . $p['tingkat'], 'Prestasi: ' . $p['judul'] . ' (' . $p['tahun'] . ')', $p['file_path'], $p['tahun']]);
                $pesan = "📌 Prestasi otomatis masuk Matriks Bukti Kriteria $nomor.";
            }
        }
    }

    if ($action === 'del') {
        $db->prepare("DELETE FROM prestasi_prodi WHERE id_prestasi = ? AND id_prodi = ?")->execute([(int)$_POST['id_prestasi'], $prodiId]);
        $pesan = '✅ Prestasi dihapus.';
    }
}

$jenisF = $_GET['jenis'] ?? 'all';
$q = "SELECT * FROM prestasi_prodi WHERE id_prodi = ?";
$params = [$prodiId];
if ($jenisF !== 'all') { $q .= " AND jenis = ?"; $params[] = $jenisF; }
$q .= " ORDER BY tahun DESC, id_prestasi DESC";
$stmt = $db->prepare($q); $stmt->execute($params);
$list = $stmt->fetchAll();

$nMhs = (int)$db->prepare("SELECT COUNT(*) FROM prestasi_prodi WHERE id_prodi = ? AND jenis='Mahasiswa'")->execute([$prodiId]) ? 0 : 0;
$stM = $db->prepare("SELECT COUNT(*) t FROM prestasi_prodi WHERE id_prodi = ? AND jenis = 'Mahasiswa'"); $stM->execute([$prodiId]); $nMhs = (int)$stM->fetch()['t'];
$stD = $db->prepare("SELECT COUNT(*) t FROM prestasi_prodi WHERE id_prodi = ? AND jenis = 'Dosen'"); $stD->execute([$prodiId]); $nDsn = (int)$stD->fetch()['t'];
$stI = $db->prepare("SELECT COUNT(*) t FROM prestasi_prodi WHERE id_prodi = ? AND tingkat = 'Internasional'"); $stI->execute([$prodiId]); $nIntl = (int)$stI->fetch()['t'];
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon gold">🏅</div><div><h3 style="font-size:28px;"><?= count($list) ?></h3><p class="text-muted">Ditampilkan</p></div></div>
    <div class="stat-card"><div class="stat-icon blue">🧑</div><div><h3 style="font-size:28px;"><?= $nMhs ?></h3><p class="text-muted">Prestasi Mahasiswa</p></div></div>
    <div class="stat-card"><div class="stat-icon green">🧑</div><div><h3 style="font-size:28px;"><?= $nDsn ?></h3><p class="text-muted">Prestasi Dosen</p></div></div>
    <div class="stat-card"><div class="stat-icon red">🌍</div><div><h3 style="font-size:28px;"><?= $nIntl ?></h3><p class="text-muted">Tingkat Internasional</p></div></div>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Catat Prestasi</h3>
    <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 2.2fr .9fr .7fr 1.2fr 1fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Jenis</label>
            <select name="jenis" class="form-control"><option>Mahasiswa</option><option>Dosen</option></select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Judul Prestasi</label>
            <input type="text" name="judul" class="form-control" placeholder="cth: Juara 1 Hackathon Nasional" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tingkat</label>
            <select name="tingkat" class="form-control">
                <option>Prodi</option><option>Fakultas</option><option>Universitas</option>
                <option>Regional</option><option selected>Nasional</option><option>Internasional</option>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tahun</label>
            <input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Penyelenggara</label>
            <input type="text" name="penyelenggara" class="form-control" placeholder="cth: Kemdikbud"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Bukti (PDF)</label>
            <input type="file" name="file" class="form-control" accept="application/pdf"></div>
        <button class="btn btn-gold">💾</button>
    </form>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3>🏆 Daftar Prestasi</h3>
        <form method="GET"><select name="jenis" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="all">Semua Jenis</option><option value="Mahasiswa" <?= $jenisF === 'Mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
            <option value="Dosen" <?= $jenisF === 'Dosen' ? 'selected' : '' ?>>Dosen</option>
        </select></form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Prestasi</th><th>Jenis</th><th>Tingkat</th><th>Tahun</th><th>Bukti</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada prestasi tercatat.</td></tr><?php endif; ?>
            <?php foreach ($list as $p): ?>
                <tr>
                    <td><strong><?= Security::e($p['judul']) ?></strong><br><small class="text-muted"><?= Security::e($p['penyelenggara']) ?></small></td>
                    <td><span class="badge <?= $p['jenis'] === 'Mahasiswa' ? 'badge-a' : 'badge-b' ?>"><?= $p['jenis'] ?></span></td>
                    <td><?= Security::e($p['tingkat']) ?></td>
                    <td><?= $p['tahun'] ?></td>
                    <td><?= $p['file_path'] ? '<a href="/download.php?file=' . urlencode($p['file_path']) . '&type=internal" target="_blank" style="color:var(--primary);font-weight:700;">📄</a>' : '—' ?></td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap;">
                        <form method="POST"><input type="hidden" name="id_prestasi" value="<?= $p['id_prestasi'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="matriks"><button class="btn btn-primary" style="padding:5px 12px;font-size:11px;" title="Masukkan ke Matriks Bukti (Mahasiswa→K9, Dosen→K7)">📌 Matriks</button></form>
                        <form method="POST" onsubmit="return confirm('Hapus prestasi?');"><input type="hidden" name="id_prestasi" value="<?= $p['id_prestasi'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="font-size:12.5px;margin-top:14px;">💡 Tombol <strong>📌 Matriks</strong> otomatis menyalin prestasi ke Matriks Bukti (Mahasiswa → K9, Dosen → K7) berstatus <em>Lengkap</em>.</p>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>