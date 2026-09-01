<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nip   = trim($_POST['nidn_nip'] ?? '');
        $nama  = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = (int)($_POST['id_role'] ?? 3);
        $prodi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;
        $pass  = $_POST['password'] ?? '';

        if (!$nip || !$nama || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
            $error = 'Data tidak valid. Password minimal 8 karakter.';
        } else {
            try {
                $db->prepare("INSERT INTO users (id_role, id_prodi, nidn_nip, nama_lengkap, email, password_hash) VALUES (?,?,?,?,?,?)")
                   ->execute([$role, $prodi, $nip, $nama, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                $pesan = '✅ Pengguna baru berhasil ditambahkan.';
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan. NIP/NIDN atau email mungkin sudah terdaftar.';
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id_user'];
        if ($id !== Auth::id()) {
            $db->prepare("UPDATE users SET is_active = 1 - is_active WHERE id_user = ?")->execute([$id]);
            $pesan = '✅ Status pengguna diperbarui.';
        } else { $error = 'Tidak bisa mengubah akun sendiri.'; }
    }

    if ($action === 'reset') {
        $db->prepare("UPDATE users SET password_hash = ? WHERE id_user = ?")
           ->execute([password_hash('Mutu2026!', PASSWORD_DEFAULT), (int)$_POST['id_user']]);
        $pesan = '✅ Password direset menjadi: Mutu2026!';
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id_user'];
        if ($id !== Auth::id()) {
            $db->prepare("DELETE FROM users WHERE id_user = ?")->execute([$id]);
            $pesan = '✅ Pengguna dihapus.';
        } else { $error = 'Tidak bisa menghapus akun sendiri.'; }
    }
}

$users = $db->query("SELECT u.*, r.nama_role, p.nama_prodi FROM users u JOIN roles r ON u.id_role = r.id_role LEFT JOIN prodi p ON u.id_prodi = p.id_prodi ORDER BY u.id_user")->fetchAll();
$roles = $db->query("SELECT * FROM roles ORDER BY id_role")->fetchAll();
$prodiList = $db->query("SELECT * FROM prodi ORDER BY nama_prodi")->fetchAll();

$simTitle = 'Manajemen Pengguna';
$activeMenu = 'users';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Tambah Pengguna Baru</h3>
    <form method="POST">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="create">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            <div class="form-group"><label class="form-label">NIDN / NIP</label>
                <input type="text" name="nidn_nip" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Role / Hak Akses</label>
                <select name="id_role" class="form-control">
                    <?php foreach ($roles as $r): ?><option value="<?= $r['id_role'] ?>"><?= Security::e($r['nama_role']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Prodi (untuk Kaprodi)</label>
                <select name="id_prodi" class="form-control">
                    <option value="">— Tidak terikat prodi —</option>
                    <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Password (min. 8)</label>
                <input type="password" name="password" class="form-control" minlength="8" required></div>
        </div>
        <button type="submit" class="btn btn-primary">💾 Simpan Pengguna</button>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead><tr><th>NIDN/NIP</th><th>Nama</th><th>Role</th><th>Prodi</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= Security::e($u['nidn_nip']) ?></td>
                <td><strong><?= Security::e($u['nama_lengkap']) ?></strong><br><small class="text-muted"><?= Security::e($u['email']) ?></small></td>
                <td><span class="badge badge-a"><?= Security::e($u['nama_role']) ?></span></td>
                <td><?= Security::e($u['nama_prodi'] ?? '—') ?></td>
                <td><?= $u['is_active'] ? '🟢 Aktif' : '🔴 Nonaktif' ?></td>
                <td style="display:flex;gap:6px;">
                    <form method="POST"><input type="hidden" name="id_user" value="<?= $u['id_user'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-outline" style="padding:5px 12px;font-size:12px;"><?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                    <form method="POST"><input type="hidden" name="id_user" value="<?= $u['id_user'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="reset"><button class="btn btn-outline" style="padding:5px 12px;font-size:12px;">🔑 Reset</button></form>
                    <form method="POST" onsubmit="return confirm('Hapus pengguna ini?');"><input type="hidden" name="id_user" value="<?= $u['id_user'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><button class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);">🗑️</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>