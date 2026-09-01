<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

/* Deteksi kolom tabel dosen (aman untuk semua varian skema) */
$cols = array_column($db->query("SHOW COLUMNS FROM dosen")->fetchAll(), 'Field');
$namaCol = in_array('nama_dosen', $cols) ? 'nama_dosen' : (in_array('nama', $cols) ? 'nama' : null);
$jabCol = in_array('jabatan_fungsional', $cols) ? 'jabatan_fungsional' : (in_array('jabatan', $cols) ? 'jabatan' : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    if (($_POST['action'] ?? '') === 'add') {
        if (!$namaCol) { $error = 'Kolom nama tidak ditemukan di tabel dosen.'; }
        else {
            $fields = ['id_prodi', $namaCol];
            $vals = [(int)$_POST['id_prodi'], trim($_POST['nama'])];
            if (in_array('nidn', $cols)) { $fields[] = 'nidn'; $vals[] = trim($_POST['nidn']); }
            if ($jabCol) { $fields[] = $jabCol; $vals[] = trim($_POST['jabatan']); }
            if (in_array('status', $cols)) { $fields[] = 'status'; $vals[] = $_POST['status']; }
            $sql = "INSERT INTO dosen (" . implode(',', $fields) . ") VALUES (" . trim(str_repeat('?,', count($fields)), ',') . ")";
            $db->prepare($sql)->execute($vals);
            Logger::log('CREATE', 'Menambah dosen manual: ' . trim($_POST['nama']));
            $pesan = '✅ Dosen ditambahkan — langsung tersedia di EDOM & sistem lain.';
        }
    }
    if (($_POST['action'] ?? '') === 'del') {
        $db->prepare("DELETE FROM dosen WHERE id_dosen = ?")->execute([(int)$_POST['id_dosen']]);
        $pesan = '✅ Dosen dihapus.';
    }
}

$list = $db->query("SELECT d.*, p.nama_prodi FROM dosen d LEFT JOIN prodi p ON d.id_prodi = p.id_prodi ORDER BY d.id_dosen DESC LIMIT 200")->fetchAll();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();

$simTitle = 'Kelola Dosen';
$activeMenu = 'dosenman';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:6px;">➕ Tambah Dosen Manual</h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:20px;">Untuk kampus tanpa file Feeder — dosen langsung masuk ke EDOM, matriks bukti, dan penugasan audit.</p>
    <form method="POST" style="display:grid;grid-template-columns:1.4fr 1fr 1.6fr 1.2fr .9fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Program Studi *</label>
            <select name="id_prodi" class="form-control" required>
                <option value="">— Pilih Prodi —</option>
                <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">NIDN</label>
            <input type="text" name="nidn" class="form-control" placeholder="0012058801"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Nama Lengkap & Gelar *</label>
            <input type="text" name="nama" class="form-control" placeholder="cth: Dr. Maria Bata, M.M." required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Jabatan Fungsional</label>
            <select name="jabatan" class="form-control">
                <option>Tenaga Pengajar</option><option>Asisten Ahli</option><option>Lektor</option>
                <option>Lektor Kepala</option><option>Guru Besar</option>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Status</label>
            <select name="status" class="form-control"><option>Aktif</option><option>Nonaktif</option></select></div>
        <button class="btn btn-gold">💾 Simpan</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📇 Daftar Dosen (<?= count($list) ?>)</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>NIDN</th><th>Nama</th><th>Prodi</th><th>Jabatan</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada dosen.</td></tr><?php endif; ?>
            <?php foreach ($list as $d): ?>
                <tr>
                    <td><?= Security::e($d['nidn'] ?? '—') ?></td>
                    <td><strong><?= Security::e($d[$namaCol] ?? '') ?></strong></td>
                    <td><?= Security::e($d['nama_prodi'] ?? '—') ?></td>
                    <td><?= Security::e($d[$jabCol] ?? '—') ?></td>
                    <td><?= Security::e($d['status'] ?? '—') ?></td>
                    <td><form method="POST" onsubmit="return confirm('Hapus dosen ini?');"><input type="hidden" name="id_dosen" value="<?= $d['id_dosen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>