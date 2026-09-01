<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = ''; $error = '';

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

/* Deteksi kolom tabel dosen (aman untuk semua varian skema) */
$cols = array_column($db->query("SHOW COLUMNS FROM dosen")->fetchAll(), 'Field');
$namaCol = in_array('nama_dosen', $cols) ? 'nama_dosen' : (in_array('nama', $cols) ? 'nama' : null);
$jabCol = in_array('jabatan_fungsional', $cols) ? 'jabatan_fungsional' : (in_array('jabatan', $cols) ? 'jabatan' : null);

$simTitle = 'Kelola Dosen Prodi';
$activeMenu = 'dosenprodi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun belum terhubung ke prodi. Hubungi Admin LPM.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

$prodiNama = $db->prepare("SELECT nama_prodi FROM prodi WHERE id_prodi = ?");
$prodiNama->execute([$prodiId]);
$prodiNama = $prodiNama->fetch()['nama_prodi'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    if (($_POST['action'] ?? '') === 'add') {
        if (!$namaCol) { $error = 'Kolom nama tidak ditemukan di tabel dosen.'; }
        else {
            $fields = ['id_prodi', $namaCol];
            $vals = [$prodiId, trim($_POST['nama'])]; // ⚠️ DIPAKSA ke prodi Kaprodi
            if (in_array('nidn', $cols)) { $fields[] = 'nidn'; $vals[] = trim($_POST['nidn']); }
            if ($jabCol) { $fields[] = $jabCol; $vals[] = trim($_POST['jabatan']); }
            if (in_array('status', $cols)) { $fields[] = 'status'; $vals[] = $_POST['status']; }
            $sql = "INSERT INTO dosen (" . implode(',', $fields) . ") VALUES (" . trim(str_repeat('?,', count($fields)), ',') . ")";
            $db->prepare($sql)->execute($vals);
            Logger::log('CREATE', 'Kaprodi menambah dosen: ' . trim($_POST['nama']));
            $pesan = '✅ Dosen ditambahkan ke prodi Anda.';
        }
    }
    if (($_POST['action'] ?? '') === 'edit') {
        if (!$namaCol) { $error = 'Kolom nama tidak ditemukan.'; }
        else {
            $sql = "UPDATE dosen SET $namaCol = ?";
            $vals = [trim($_POST['nama'])];
            if (in_array('nidn', $cols)) { $sql .= ", nidn = ?"; $vals[] = trim($_POST['nidn']); }
            if ($jabCol) { $sql .= ", $jabCol = ?"; $vals[] = trim($_POST['jabatan']); }
            if (in_array('status', $cols)) { $sql .= ", status = ?"; $vals[] = $_POST['status']; }
            $sql .= " WHERE id_dosen = ? AND id_prodi = ?";
            $vals[] = (int)$_POST['id_dosen'];
            $vals[] = $prodiId;
            $db->prepare($sql)->execute($vals);
            $pesan = '✅ Data dosen diperbarui.';
        }
    }
    if (($_POST['action'] ?? '') === 'del') {
        $db->prepare("DELETE FROM dosen WHERE id_dosen = ? AND id_prodi = ?")->execute([(int)$_POST['id_dosen'], $prodiId]);
        $pesan = '✅ Dosen dihapus.';
    }
}

$list = $db->query("SELECT d.* FROM dosen d WHERE d.id_prodi = $prodiId ORDER BY d.id_dosen DESC LIMIT 200")->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM dosen WHERE id_dosen = ? AND id_prodi = ?");
    $st->execute([(int)$_GET['edit'], $prodiId]);
    $edit = $st->fetch();
}
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:var(--radius-lg);padding:26px 32px;color:#fff;margin-bottom:26px;">
    <h2 style="font-size:22px;">📇 Kelola Dosen Prodi</h2>
    <p style="opacity:.85;font-size:13.5px;">Data dosen ini otomatis tersedia di <strong>EDOM</strong>, <strong>penugasan audit</strong>, dan <strong>matriks bukti akreditasi</strong>. Hanya dosen prodi <strong><?= Security::e($prodiNama) ?></strong> yang dapat dikelola.</p>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:16px;"><?= $edit ? '✏️ Edit Dosen' : '➕ Tambah Dosen Baru' ?></h3>
    <?php if ($edit): ?>
        <a href="/sim/prodi/dosen.php" class="btn btn-outline" style="padding:5px 14px;font-size:12px;margin-bottom:14px;">✕ Batal Edit</a>
    <?php endif; ?>
    <form method="POST" style="display:grid;grid-template-columns:1fr 1.6fr 1.2fr .9fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?><input type="hidden" name="id_dosen" value="<?= $edit['id_dosen'] ?>"><?php endif; ?>
        <div class="form-group" style="margin:0;"><label class="form-label">NIDN</label>
            <input type="text" name="nidn" class="form-control" value="<?= Security::e($edit['nidn'] ?? '') ?>" placeholder="0012058801"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Nama Lengkap & Gelar *</label>
            <input type="text" name="nama" class="form-control" value="<?= Security::e($edit[$namaCol] ?? '') ?>" placeholder="cth: Dr. Maria Bata, M.M." required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Jabatan Fungsional</label>
            <select name="jabatan" class="form-control">
                <?php foreach (['Tenaga Pengajar','Asisten Ahli','Lektor','Lektor Kepala','Guru Besar'] as $j): ?>
                    <option <?= ($edit[$jabCol] ?? '') === $j ? 'selected' : '' ?>><?= $j ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Status</label>
            <select name="status" class="form-control">
                <?php foreach (['Aktif','Nonaktif'] as $s): ?>
                    <option <?= ($edit['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select></div>
        <button class="btn btn-gold">💾 <?= $edit ? 'Perbarui' : 'Simpan' ?></button>
    </form>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3>📋 Dosen <?= Security::e($prodiNama) ?> (<?= count($list) ?>)</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>NIDN</th><th>Nama</th><th>Jabatan</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="5" style="text-align:center;padding:30px;" class="text-muted">Belum ada dosen di prodi ini.</td></tr><?php endif; ?>
            <?php foreach ($list as $d): ?>
                <tr>
                    <td><?= Security::e($d['nidn'] ?? '—') ?></td>
                    <td><strong><?= Security::e($d[$namaCol] ?? '') ?></strong></td>
                    <td><?= Security::e($d[$jabCol] ?? '—') ?></td>
                    <td><span class="badge <?= ($d['status'] ?? 'Aktif') === 'Aktif' ? 'badge-unggul' : 'badge-baik' ?>"><?= Security::e($d['status'] ?? '—') ?></span></td>
                    <td style="display:flex;gap:6px;">
                        <a href="?edit=<?= $d['id_dosen'] ?>" class="btn btn-outline" style="padding:5px 12px;font-size:12px;">✏️</a>
                        <form method="POST" onsubmit="return confirm('Hapus dosen ini? Semua EDOM terkait juga akan hilang.');"><input type="hidden" name="id_dosen" value="<?= $d['id_dosen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del"><button class="btn btn-outline" style="padding:5px 10px;font-size:12px;color:var(--danger);">🗑️</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="font-size:12.5px;margin-top:14px;">💡 Dosen yang Anda tambahkan di sini langsung terlihat di <strong>📝 EDOM</strong> untuk ditugaskan ke kelas/mata kuliah.</p>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>