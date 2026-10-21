<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; 
$error = '';

define('VALID_ROLES', [1, 2, 3, 4, 5]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= CREATE USER =================
    if ($action === 'create') {
        $nip   = trim($_POST['nidn_nip'] ?? '');
        $nama  = trim($_POST['nama_lengkap'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role  = (int)($_POST['id_role'] ?? 0);
        $prodi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;
        $pass  = $_POST['password'] ?? '';

        if (!$nip || !$nama || !$email) {
            $error = '❌ NIDN/NIP, Nama, dan Email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '❌ Format email tidak valid.';
        } elseif (strlen($pass) < 8) {
            $error = '❌ Password minimal 8 karakter.';
        } elseif (!in_array($role, VALID_ROLES, true)) {
            $error = '❌ Role tidak valid.';
        } elseif ($prodi !== null) {
            $stmt = $db->prepare("SELECT id_prodi FROM prodi WHERE id_prodi = ?");
            $stmt->execute([$prodi]);
            if (!$stmt->fetch()) {
                $error = '❌ Program studi yang dipilih tidak ditemukan.';
            }
        } elseif ($role === 3 && $prodi === null) {
            $error = '❌ Akun Kaprodi wajib terhubung ke Program Studi.';
        } else {
            try {
                // INSERT dengan kolom eksplisit untuk mencegah error default value
                $stmt = $db->prepare("
                    INSERT INTO users (id_role, id_prodi, nidn_nip, nama_lengkap, email, password_hash, is_active, login_gagal) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, 0)
                ");
                $stmt->execute([$role, $prodi, $nip, $nama, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                
                // Logger yang sudah kita perbaiki sebelumnya
                Logger::log('CREATE', 'Menambah pengguna: ' . $nama . ' (' . $email . ')');
                
                $pesan = '✅ Pengguna baru berhasil ditambahkan dan siap digunakan.';
                
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || stripos($e->getMessage(), 'Duplicate') !== false) {
                    $error = '❌ NIDN/NIP atau email sudah terdaftar di sistem.';
                } else {
                    $error = '❌ Gagal menyimpan: ' . $e->getMessage();
                }
            }
        }
    }

    // ================= EDIT USER =================
    if ($action === 'edit') {
        $id    = (int)($_POST['id_user'] ?? 0);
        $nip   = trim($_POST['nidn_nip'] ?? '');
        $nama  = trim($_POST['nama_lengkap'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role  = (int)($_POST['id_role'] ?? 0);
        $prodi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;

        if ($id <= 0 || !$nip || !$nama || !$email) {
            $error = '❌ Data tidak lengkap.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '❌ Format email tidak valid.';
        } elseif (!in_array($role, VALID_ROLES, true)) {
            $error = '❌ Role tidak valid.';
        } elseif ($role === 3 && $prodi === null) {
            $error = '❌ Akun Kaprodi wajib terhubung ke Program Studi.';
        } else {
            try {
                $check = $db->prepare("SELECT id_user FROM users WHERE (email = ? OR (nidn_nip = ? AND nidn_nip != '')) AND id_user != ?");
                $check->execute([$email, $nip, $id]);
                if ($check->fetch()) {
                    $error = '❌ Email atau NIDN/NIP sudah digunakan oleh pengguna lain.';
                } else {
                    $db->prepare("UPDATE users SET id_role=?, id_prodi=?, nidn_nip=?, nama_lengkap=?, email=? WHERE id_user=?")
                       ->execute([$role, $prodi, $nip, $nama, $email, $id]);
                    Logger::log('UPDATE', 'Memperbarui pengguna #' . $id . ': ' . $nama);
                    $pesan = '✅ Data pengguna diperbarui.';
                }
            } catch (PDOException $e) {
                $error = '❌ Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }

    // ================= TOGGLE STATUS =================
    if ($action === 'toggle') {
        $id = (int)($_POST['id_user'] ?? 0);
        if ($id === Auth::id()) {
            $error = '❌ Tidak bisa mengubah status akun sendiri.';
        } else {
            try {
                $db->prepare("UPDATE users SET is_active = 1 - is_active WHERE id_user = ?")->execute([$id]);
                Logger::log('UPDATE', 'Mengubah status pengguna #' . $id);
                $pesan = '✅ Status pengguna diperbarui.';
            } catch (PDOException $e) { 
                $error = '❌ Gagal mengubah status: ' . $e->getMessage(); 
            }
        }
    }

    // ================= RESET PASSWORD =================
    if ($action === 'reset') {
        $id = (int)($_POST['id_user'] ?? 0);
        if ($id <= 0 || $id === Auth::id()) {
            $error = '❌ Akun tidak valid atau tidak dapat direset.';
        } else {
            try {
                $tempPass = bin2hex(random_bytes(4));
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id_user = ?");
                $stmt->execute([password_hash($tempPass, PASSWORD_DEFAULT), $id]);
                if ($stmt->rowCount() > 0) {
                    Logger::log('UPDATE', 'Reset password pengguna #' . $id);
                    $pesan = '🔑 Password sementara: <strong>' . Security::e($tempPass) . '</strong><br><small class="text-muted">Sampaikan langsung kepada pengguna. Password ini hanya ditampilkan sekali.</small>';
                } else {
                    $error = '❌ Pengguna tidak ditemukan.';
                }
            } catch (PDOException $e) { 
                $error = '❌ Gagal mereset password: ' . $e->getMessage(); 
            }
        }
    }

    // ================= DELETE USER =================
    if ($action === 'delete') {
        $id = (int)($_POST['id_user'] ?? 0);
        if ($id === Auth::id()) {
            $error = '❌ Tidak bisa menghapus akun sendiri.';
        } else {
            try {
                $stmt = $db->prepare("SELECT nama_lengkap FROM users WHERE id_user = ?");
                $stmt->execute([$id]);
                $nama = $stmt->fetchColumn() ?: 'Unknown';
                $db->prepare("DELETE FROM users WHERE id_user = ?")->execute([$id]);
                Logger::log('DELETE', 'Menghapus pengguna: ' . $nama);
                $pesan = '✅ Pengguna berhasil dihapus.';
            } catch (PDOException $e) { 
                $error = '❌ Gagal menghapus: ' . $e->getMessage(); 
            }
        }
    }
}

$users = $db->query("SELECT u.*, r.nama_role, p.nama_prodi FROM users u JOIN roles r ON u.id_role = r.id_role LEFT JOIN prodi p ON u.id_prodi = p.id_prodi ORDER BY u.id_user DESC")->fetchAll();
$roles = $db->query("SELECT * FROM roles ORDER BY id_role")->fetchAll();
$prodiList = $db->query("SELECT * FROM prodi ORDER BY nama_prodi")->fetchAll();

$simTitle = 'Manajemen Pengguna';
$activeMenu = 'users';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= $pesan ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Tambah Pengguna Baru</h3>
    <form method="POST">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="create">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            <div class="form-group"><label class="form-label">NIDN / NIP *</label>
                <input type="text" name="nidn_nip" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Nama Lengkap *</label>
                <input type="text" name="nama_lengkap" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Role / Hak Akses *</label>
                <select name="id_role" class="form-control" required>
                    <?php foreach ($roles as $r): ?><option value="<?= $r['id_role'] ?>"><?= Security::e($r['nama_role']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Prodi (Wajib untuk Kaprodi)</label>
                <select name="id_prodi" class="form-control">
                    <option value="">— Tidak terikat prodi —</option>
                    <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Password * (min. 8 karakter)</label>
                <!-- DIPERBAIKI: Ditambahkan autocomplete="new-password" -->
                <input type="password" name="password" class="form-control" minlength="8" required autocomplete="new-password"></div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:16px;font-weight:700;">💾 Simpan Pengguna</button>
    </form>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;">👥 Daftar Pengguna (<?= count($users) ?>)</h3>
        <input type="text" id="cariUser" class="form-control" placeholder="🔍 Cari nama/email..." style="max-width:240px;" autocomplete="off">
    </div>
    <div class="table-wrapper">
        <table id="tabelUsers">
            <thead>
                <tr>
                    <th>NIDN/NIP</th>
                    <th>Nama & Email</th>
                    <th>Role</th>
                    <th>Prodi</th>
                    <th>Status</th>
                    <th style="width:220px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr class="user-row">
                    <td><code style="font-size:12px;"><?= Security::e($u['nidn_nip']) ?></code></td>
                    <td>
                        <strong><?= Security::e($u['nama_lengkap']) ?></strong><br>
                        <small class="text-muted"><?= Security::e($u['email']) ?></small>
                    </td>
                    <td><span class="badge badge-a"><?= Security::e($u['nama_role']) ?></span></td>
                    <td><?= Security::e($u['nama_prodi'] ?? '—') ?></td>
                    <td>
                        <span style="padding:4px 12px;border-radius:50px;font-size:11px;font-weight:700;background:<?= $u['is_active'] ? 'rgba(16,185,129,.15);color:#059669;' : 'rgba(107,114,128,.15);color:#6B7280;' ?>">
                            <?= $u['is_active'] ? '🟢 Aktif' : '⚪ Nonaktif' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button type="button" class="btn btn-outline btn-edit-user" style="padding:5px 10px;font-size:11px;"
                                data-id="<?= (int)$u['id_user'] ?>"
                                data-nip="<?= htmlspecialchars($u['nidn_nip'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-nama="<?= htmlspecialchars($u['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-email="<?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-role="<?= (int)$u['id_role'] ?>"
                                data-prodi="<?= (int)($u['id_prodi'] ?? 0) ?>" 
                                title="Edit Data">✏️</button>
                            
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="toggle">
                                <button type="submit" class="btn btn-outline" style="padding:5px 10px;font-size:11px;" title="Ubah Status">
                                    <?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                </button>
                            </form>
                            
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Reset password pengguna ini?');">
                                <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="reset">
                                <button type="submit" class="btn btn-outline" style="padding:5px 10px;font-size:11px;" title="Reset Password">🔑</button>
                            </form>
                            
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus pengguna ini?');">
                                <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-outline" style="padding:5px 10px;font-size:11px;color:var(--danger);" title="Hapus">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit User -->
<div id="modalEditUser" style="display:none;position:fixed;inset:0;background:rgba(4,10,20,.62);backdrop-filter:blur(5px);z-index:4000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:var(--bg-card);border-radius:18px;box-shadow:var(--shadow-lg);border:1px solid var(--border);width:min(560px,100%);overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;">✏️ Edit Data Pengguna</h3>
            <button type="button" onclick="document.getElementById('modalEditUser').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);">×</button>
        </div>
        <form method="POST" style="padding:22px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_user" id="eu_id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">NIDN / NIP *</label>
                    <input type="text" name="nidn_nip" id="eu_nip" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Role *</label>
                    <select name="id_role" id="eu_role" class="form-control" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['id_role'] ?>"><?= Security::e($r['nama_role']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="nama_lengkap" id="eu_nama" class="form-control" required>
            </div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="eu_email" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Prodi</label>
                    <select name="id_prodi" id="eu_prodi" class="form-control">
                        <option value="">— Tidak terikat —</option>
                        <?php foreach ($prodiList as $p): ?>
                            <option value="<?= (int)$p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalEditUser').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Pencarian User
    var searchInput = document.getElementById('cariUser');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var term = this.value.toLowerCase();
            var rows = document.querySelectorAll('#tabelUsers .user-row');
            rows.forEach(function(tr) {
                tr.style.display = tr.textContent.toLowerCase().indexOf(term) > -1 ? '' : 'none';
            });
        });
    }

    // 2. Modal Edit User
    var modal = document.getElementById('modalEditUser');
    if (modal) {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-edit-user')) {
                var btn = e.target.closest('.btn-edit-user');
                document.getElementById('eu_id').value = btn.getAttribute('data-id');
                document.getElementById('eu_nip').value = btn.getAttribute('data-nip');
                document.getElementById('eu_nama').value = btn.getAttribute('data-nama');
                document.getElementById('eu_email').value = btn.getAttribute('data-email');
                document.getElementById('eu_role').value = btn.getAttribute('data-role');
                document.getElementById('eu_prodi').value = btn.getAttribute('data-prodi');
                modal.style.display = 'flex';
            }
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>