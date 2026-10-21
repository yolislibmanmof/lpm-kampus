<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$simTitle = 'Mitra & Pengakuan';
$activeMenu = 'mitra';

$msg = ''; $err = '';

/* Helper: Hapus file dengan aman (mencegah path traversal) */
function safeUnlinkMitra(?string $rel): void {
    if (!$rel) return;
    $root = realpath(PATH_UPLOAD);
    $f    = realpath(PATH_UPLOAD . $rel);
    if ($root && $f && str_starts_with($f, $root . DIRECTORY_SEPARATOR) && is_file($f)) {
        @unlink($f);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Validasi ketat: pastikan file benar-benar gambar
        if (@getimagesize($_FILES['logo']['tmp_name']) === false) {
            $err = '❌ File yang diunggah bukan gambar yang valid.';
        } else {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png','jpg','jpeg','svg','webp'])) {
                $dir = PATH_UPLOAD . 'mitra/';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $name)) {
                    $logoPath = 'mitra/' . $name;
                } else {
                    $err = '❌ Gagal menyimpan file logo ke server.';
                }
            } else { 
                $err = '❌ Format logo harus PNG/JPG/SVG/WEBP.'; 
            }
        }
    }

    if (in_array($action, ['add','update']) && !$err) {
        $nama   = mb_substr(trim($_POST['nama'] ?? ''), 0, 150);
        $kode   = mb_substr(trim($_POST['kode'] ?? ''), 0, 20);
        $url    = trim($_POST['url'] ?? '');
        $warna  = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['warna'] ?? '') ? $_POST['warna'] : '#0F3D5C';
        $urutan = (int)($_POST['urutan'] ?? 0);
        $aktif  = isset($_POST['aktif']) ? 1 : 0;

        if ($nama === '' || $kode === '') {
            $err = '❌ Nama dan Kode mitra wajib diisi.';
        } else {
            try {
                if ($action === 'add') {
                    $db->prepare("INSERT INTO mitra (nama,kode,logo_path,url,warna,urutan,aktif) VALUES (?,?,?,?,?,?,?)")
                       ->execute([$nama, $kode, $logoPath, $url, $warna, $urutan, $aktif]);
                    Logger::log('CREATE', 'Menambah mitra: ' . $nama);
                    $msg = '✅ Mitra berhasil ditambahkan.';
                } else {
                    $id = (int)$_POST['id_mitra'];
                    $old = $db->prepare("SELECT logo_path FROM mitra WHERE id_mitra=?");
                    $old->execute([$id]);
                    $oldLogo = $old->fetchColumn();

                    $sql = "UPDATE mitra SET nama=?, kode=?, url=?, warna=?, urutan=?, aktif=?";
                    $params = [$nama, $kode, $url, $warna, $urutan, $aktif];
                    
                    if ($logoPath) {
                        $sql .= ", logo_path=?";
                        $params[] = $logoPath;
                        safeUnlinkMitra($oldLogo); // Hapus logo lama jika ada yang baru
                    }
                    $sql .= " WHERE id_mitra=?";
                    $params[] = $id;
                    
                    $db->prepare($sql)->execute($params);
                    Logger::log('UPDATE', 'Memperbarui mitra #' . $id . ': ' . $nama);
                    $msg = '✅ Mitra berhasil diperbarui.';
                }
            } catch (PDOException $e) {
                $err = '❌ Gagal menyimpan: ' . $e->getMessage();
                if ($logoPath) safeUnlinkMitra($logoPath); // Bersihkan file yatim jika DB gagal
            }
        }
    }

    if ($action === 'delete' && !$err) {
        $id = (int)($_POST['id_mitra'] ?? 0);
        try {
            $st = $db->prepare("SELECT nama, logo_path FROM mitra WHERE id_mitra=?");
            $st->execute([$id]);
            $f = $st->fetch();
            if ($f) {
                safeUnlinkMitra($f['logo_path']);
                $db->prepare("DELETE FROM mitra WHERE id_mitra=?")->execute([$id]);
                Logger::log('DELETE', 'Menghapus mitra: ' . $f['nama']);
                $msg = '✅ Mitra berhasil dihapus.';
            } else {
                $err = '❌ Mitra tidak ditemukan.';
            }
        } catch (PDOException $e) {
            $err = '❌ Gagal menghapus: ' . $e->getMessage();
        }
    }
}

$list = $db->query("SELECT * FROM mitra ORDER BY urutan, nama")->fetchAll();
$edit = null;
if (!empty($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM mitra WHERE id_mitra=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= Security::e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= Security::e($err) ?></div><?php endif; ?>

<style>
.mt-grid{display:grid;grid-template-columns:360px 1fr;gap:22px}
@media(max-width:992px){.mt-grid{grid-template-columns:1fr}}
.mt-form{background:var(--bg-card);border:1px solid var(--border);border-radius:18px;padding:24px;position:sticky;top:96px;align-self:start;max-height:calc(100vh - 120px);overflow:auto}
.mt-form h3{margin:0 0 14px;color:var(--text-dark)}
.mt-tablewrap{background:var(--bg-card);border:1px solid var(--border);border-radius:18px;overflow:auto}
.mt-table{width:100%;border-collapse:collapse;font-size:13.5px;min-width:640px}
.mt-table th{background:var(--bg-light);color:var(--text-muted);text-transform:uppercase;font-size:11px;letter-spacing:1px;padding:12px 14px;text-align:left}
.mt-table td{padding:12px 14px;border-bottom:1px solid var(--border);color:var(--text-dark)}
.mt-logo{width:44px;height:44px;border-radius:10px;object-fit:contain;background:var(--bg-light);padding:4px}
.mt-mono{width:44px;height:44px;border-radius:10px;display:grid;place-items:center;color:#fff;font-weight:900;font-size:10px}
.mt-btn{padding:6px 10px;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer}
.mt-edit{background:rgba(59,130,246,.12);color:#2563EB}
.mt-del{background:rgba(239,68,68,.12);color:#DC2626}
</style>

<div class="mt-grid">
    <div class="mt-form">
        <h3><?= $edit ? '✏️ Edit Mitra' : '➕ Tambah Mitra' ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>">
            <?php if ($edit): ?><input type="hidden" name="id_mitra" value="<?= $edit['id_mitra'] ?>"><?php endif; ?>
            <div class="form-group"><label class="form-label">Nama Lembaga *</label><input class="form-control" name="nama" required maxlength="150" value="<?= Security::e($edit['nama'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Kode / Monogram *</label><input class="form-control" name="kode" required maxlength="20" value="<?= Security::e($edit['kode'] ?? '') ?>" placeholder="BAN-PT"></div>
            <div class="form-group"><label class="form-label">Logo (opsional, PNG/SVG)</label><input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg,.webp" class="form-control"></div>
            <div class="form-group"><label class="form-label">Website (opsional)</label><input type="url" class="form-control" name="url" value="<?= Security::e($edit['url'] ?? '') ?>" placeholder="https://..."></div>
            <div class="form-group"><label class="form-label">Warna Monogram</label><input type="color" name="warna" class="form-control" style="padding:6px;height:44px;" value="<?= Security::e($edit['warna'] ?? '#0F3D5C') ?>"></div>
            <div class="form-group"><label class="form-label">Urutan</label><input type="number" name="urutan" class="form-control" value="<?= $edit['urutan'] ?? 0 ?>"></div>
            <div class="form-group"><label style="display:flex;gap:8px;align-items:center;color:var(--text-dark);font-weight:600;"><input type="checkbox" name="aktif" value="1" <?= (!$edit || $edit['aktif']) ? 'checked' : '' ?>> Tampilkan di website</label></div>
            <button class="btn btn-gold" style="width:100%;justify-content:center;" type="submit"><?= $edit ? '💾 Perbarui' : '➕ Tambah' ?></button>
            <?php if ($edit): ?><a class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:8px;" href="/sim/admin/mitra.php">Batal</a><?php endif; ?>
        </form>
    </div>
    <div class="mt-tablewrap">
        <table class="mt-table">
            <thead><tr><th>Logo</th><th>Nama</th><th>Kode</th><th>Urutan</th><th>Status</th><th style="width:90px">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada mitra terdaftar.</td></tr><?php endif; ?>
            <?php foreach ($list as $m): ?>
                <tr>
                    <td><?php if ($m['logo_path']): ?><img class="mt-logo" src="/uploads/<?= Security::e($m['logo_path']) ?>" alt=""><?php else: ?><span class="mt-mono" style="background:<?= Security::e($m['warna']) ?>"><?= Security::e(mb_substr($m['kode'], 0, 4)) ?></span><?php endif; ?></td>
                    <td><strong><?= Security::e($m['nama']) ?></strong></td>
                    <td><code><?= Security::e($m['kode']) ?></code></td>
                    <td><?= (int)$m['urutan'] ?></td>
                    <td><?= $m['aktif'] ? '<span style="color:var(--success);font-weight:700;">✅ Aktif</span>' : '<span class="text-muted">⏸️ Nonaktif</span>' ?></td>
                    <td>
                        <a class="mt-btn mt-edit" href="?edit=<?= $m['id_mitra'] ?>">✏️</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus mitra ini beserta logonya?')"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_mitra" value="<?= $m['id_mitra'] ?>"><button class="mt-btn mt-del">🗑️</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>