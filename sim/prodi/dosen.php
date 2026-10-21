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
            $vals = [$prodiId, trim($_POST['nama'])];
            if (in_array('nidn', $cols)) { $fields[] = 'nidn'; $vals[] = trim($_POST['nidn']); }
            if ($jabCol) { $fields[] = $jabCol; $vals[] = trim($_POST['jabatan']); }
            if (in_array('status', $cols)) { $fields[] = 'status'; $vals[] = $_POST['status']; }
            $sql = "INSERT INTO dosen (" . implode(',', $fields) . ") VALUES (" . trim(str_repeat('?,', count($fields)), ',') . ")";
            $db->prepare($sql)->execute($vals);
            Logger::log('CREATE', 'Kaprodi menambah dosen: ' . trim($_POST['nama']));
            $pesan = '✅ Dosen berhasil ditambahkan ke prodi Anda.';
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
            $pesan = '✅ Data dosen berhasil diperbarui.';
        }
    }
    if (($_POST['action'] ?? '') === 'del') {
        $db->prepare("DELETE FROM dosen WHERE id_dosen = ? AND id_prodi = ?")->execute([(int)$_POST['id_dosen'], $prodiId]);
        $pesan = '✅ Dosen berhasil dihapus.';
    }
}

$list = $db->query("SELECT d.* FROM dosen d WHERE d.id_prodi = $prodiId ORDER BY d.id_dosen DESC LIMIT 200")->fetchAll();

// Stats
$totalDosen = count($list);
$aktifDosen = count(array_filter($list, fn($d) => ($d['status'] ?? 'Aktif') === 'Aktif'));
$nonaktifDosen = $totalDosen - $aktifDosen;

$edit = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM dosen WHERE id_dosen = ? AND id_prodi = ?");
    $st->execute([(int)$_GET['edit'], $prodiId]);
    $edit = $st->fetch();
}
?>

<style>
    .dosen-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 28px 32px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .dosen-header::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -80px; top: -100px; animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .dosen-header h2 { font-size: 22px; margin: 0 0 8px; position: relative; font-weight: 800; }
    .dosen-header p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; margin: 0; }
    
    .dosen-stats {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px;
    }
    .dosen-stat {
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px); border-radius: 12px;
        padding: 16px; text-align: center; border: 1px solid rgba(255,255,255,.15);
    }
    .dosen-stat h4 { font-size: 28px; margin: 0 0 4px; font-weight: 800; color: var(--accent-light); }
    .dosen-stat small { font-size: 12px; opacity: .8; text-transform: uppercase; letter-spacing: 1px; }

    .form-card {
        background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 28px;
        box-shadow: 0 4px 12px rgba(0,0,0,.05); border: 2px solid var(--border);
    }
    .form-card h3 { margin: 0 0 16px; font-size: 18px; display: flex; align-items: center; gap: 10px; }
    
    .dosen-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 14px;
        flex-shrink: 0; margin-right: 12px;
    }
    .dosen-name { font-weight: 700; color: var(--text-dark); font-size: 14px; }
    .dosen-nidn { font-size: 12px; color: var(--text-muted); }
    
    .status-badge {
        padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-aktif { background: #D1FAE5; color: #065F46; }
    .status-nonaktif { background: #F1F5F9; color: #64748B; }

    @media (max-width: 992px) { .dosen-stats { grid-template-columns: 1fr; } }
</style>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="dosen-header">
    <h2>📇 Kelola Dosen Program Studi</h2>
    <p>Data dosen ini otomatis tersedia di <strong>EDOM</strong>, <strong>penugasan audit</strong>, dan <strong>matriks bukti akreditasi</strong>.</p>
    <div class="dosen-stats">
        <div class="dosen-stat"><h4><?= $totalDosen ?></h4><small>Total Dosen</small></div>
        <div class="dosen-stat"><h4><?= $aktifDosen ?></h4><small>Dosen Aktif</small></div>
        <div class="dosen-stat"><h4><?= $nonaktifDosen ?></h4><small>Nonaktif</small></div>
    </div>
</div>

<div class="form-card">
    <h3><?= $edit ? '✏️ Edit Data Dosen' : '➕ Tambah Dosen Baru' ?></h3>
    <?php if ($edit): ?>
        <a href="/sim/prodi/dosen.php" class="btn btn-outline" style="padding:6px 14px;font-size:12px;margin-bottom:16px;display:inline-flex;align-items:center;gap:6px;">✕ Batal Edit</a>
    <?php endif; ?>
    <form method="POST" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:16px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?><input type="hidden" name="id_dosen" value="<?= $edit['id_dosen'] ?>"><?php endif; ?>
        
        <div class="form-group" style="margin:0;">
            <label class="form-label">NIDN</label>
            <input type="text" name="nidn" class="form-control" value="<?= Security::e($edit['nidn'] ?? '') ?>" placeholder="0012058801">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Nama Lengkap & Gelar *</label>
            <input type="text" name="nama" class="form-control" value="<?= Security::e($edit[$namaCol] ?? '') ?>" placeholder="cth: Dr. Maria Bata, M.M." required>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Jabatan Fungsional</label>
            <select name="jabatan" class="form-control">
                <?php foreach (['Tenaga Pengajar','Asisten Ahli','Lektor','Lektor Kepala','Guru Besar'] as $j): ?>
                    <option <?= ($edit[$jabCol] ?? '') === $j ? 'selected' : '' ?>><?= $j ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Status Kepegawaian</label>
            <select name="status" class="form-control">
                <?php foreach (['Aktif','Nonaktif'] as $s): ?>
                    <option <?= ($edit['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-gold" style="height:42px;font-weight:700;">💾 <?= $edit ? 'Perbarui' : 'Simpan' ?></button>
    </form>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3 style="margin:0;font-size:18px;">📋 Daftar Dosen <?= Security::e($prodiNama) ?></h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">Avatar</th>
                    <th>NIDN</th>
                    <th>Nama Lengkap</th>
                    <th>Jabatan Fungsional</th>
                    <th style="width:120px;">Status</th>
                    <th style="width:100px;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;">
                        <div style="font-size:48px;margin-bottom:12px;opacity:0.5;">👥</div>
                        <p class="text-muted">Belum ada dosen di prodi ini. Silakan tambahkan data dosen pertama Anda.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($list as $d): 
                    $nama = $d[$namaCol] ?? '';
                    $initials = strtoupper(substr($nama, 0, 1) . (strpos($nama, ' ') ? substr($nama, strpos($nama, ' ')+1, 1) : ''));
                    $status = $d['status'] ?? 'Aktif';
                ?>
                <tr>
                    <td>
                        <div class="dosen-avatar"><?= Security::e($initials) ?></div>
                    </td>
                    <td style="vertical-align:middle;"><?= Security::e($d['nidn'] ?? '—') ?></td>
                    <td style="vertical-align:middle;">
                        <div class="dosen-name"><?= Security::e($nama) ?></div>
                    </td>
                    <td style="vertical-align:middle;"><?= Security::e($d[$jabCol] ?? '—') ?></td>
                    <td style="vertical-align:middle;">
                        <span class="status-badge <?= $status === 'Aktif' ? 'status-aktif' : 'status-nonaktif' ?>">
                            <?= Security::e($status) ?>
                        </span>
                    </td>
                    <td style="vertical-align:middle;text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <a href="?edit=<?= $d['id_dosen'] ?>" class="btn btn-outline" style="padding:6px 10px;font-size:13px;" title="Edit">✏️</a>
                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus dosen ini? Semua data EDOM terkait juga akan terpengaruh.');">
                                <input type="hidden" name="id_dosen" value="<?= $d['id_dosen'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="del">
                                <button class="btn btn-outline" style="padding:6px 10px;font-size:13px;color:var(--danger);border-color:rgba(239,68,68,.3);" title="Hapus">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="font-size:12.5px;margin-top:16px;padding:12px;background:var(--bg-light);border-radius:8px;border-left:3px solid var(--accent);">
        💡 <strong>Tips:</strong> Dosen yang Anda tambahkan di sini akan otomatis muncul di modul <strong>📝 EDOM</strong> untuk ditugaskan ke kelas atau mata kuliah tertentu.
    </p>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>