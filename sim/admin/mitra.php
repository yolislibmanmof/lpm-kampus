<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$simTitle = 'Mitra & Pengakuan';
$activeMenu = 'mitra';

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png','jpg','jpeg','svg','webp'])) {
            $dir = PATH_UPLOAD . 'mitra/';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $name)) $logoPath = 'mitra/' . $name;
        } else { $err = 'Format logo harus PNG/JPG/SVG/WEBP.'; }
    }

    if (in_array($action, ['add','update']) && !$err) {
        $d = [
            ':nama'    => trim($_POST['nama'] ?? ''),
            ':kode'    => trim($_POST['kode'] ?? ''),
            ':url'     => trim($_POST['url'] ?? ''),
            ':warna'   => preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['warna'] ?? '') ? $_POST['warna'] : '#0F3D5C',
            ':urutan'  => (int)($_POST['urutan'] ?? 0),
            ':aktif'   => isset($_POST['aktif']) ? 1 : 0,
        ];
        if ($action === 'add') {
            $d[':logo'] = $logoPath;
            $db->prepare("INSERT INTO mitra (nama,kode,logo_path,url,warna,urutan,aktif) VALUES (:nama,:kode,:logo,:url,:warna,:urutan,:aktif)")->execute($d);
            $msg = '✅ Mitra ditambahkan.';
        } else {
            $sql = "UPDATE mitra SET nama=:nama,kode=:kode,url=:url,warna=:warna,urutan=:urutan,aktif=:aktif";
            if ($logoPath) { $sql .= ",logo_path=:logo"; $d[':logo'] = $logoPath; }
            $sql .= " WHERE id_mitra=:id";
            $d[':id'] = (int)$_POST['id_mitra'];
            $db->prepare($sql)->execute($d);
            $msg = '✅ Mitra diperbarui.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id_mitra'] ?? 0);
        $st = $db->prepare("SELECT logo_path FROM mitra WHERE id_mitra=?"); $st->execute([$id]);
        $f = $st->fetch()['logo_path'] ?? null;
        if ($f && file_exists(PATH_UPLOAD . $f)) @unlink(PATH_UPLOAD . $f);
        $db->prepare("DELETE FROM mitra WHERE id_mitra=?")->execute([$id]);
        $msg = '🗑️ Mitra dihapus.';
    }
}

$list = $db->query("SELECT * FROM mitra ORDER BY urutan, nama")->fetchAll();
$edit = null;
if (!empty($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM mitra WHERE id_mitra=?"); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch();
}
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>
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
            <div class="form-group"><label class="form-label">Nama Lembaga *</label><input class="form-control" name="nama" required value="<?= Security::e($edit['nama'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Kode / Monogram *</label><input class="form-control" name="kode" required maxlength="12" value="<?= Security::e($edit['kode'] ?? '') ?>" placeholder="BAN-PT"></div>
            <div class="form-group"><label class="form-label">Logo (opsional, PNG/SVG)</label><input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg,.webp" class="form-control"></div>
            <div class="form-group"><label class="form-label">Website (opsional)</label><input class="form-control" name="url" value="<?= Security::e($edit['url'] ?? '') ?>" placeholder="https://..."></div>
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
            <?php foreach ($list as $m): ?>
                <tr>
                    <td><?php if ($m['logo_path']): ?><img class="mt-logo" src="/uploads/<?= Security::e($m['logo_path']) ?>" alt=""><?php else: ?><span class="mt-mono" style="background:<?= Security::e($m['warna']) ?>"><?= Security::e(mb_substr($m['kode'], 0, 4)) ?></span><?php endif; ?></td>
                    <td><?= Security::e($m['nama']) ?></td>
                    <td><?= Security::e($m['kode']) ?></td>
                    <td><?= (int)$m['urutan'] ?></td>
                    <td><?= $m['aktif'] ? '✅ Aktif' : '⏸️ Nonaktif' ?></td>
                    <td>
                        <a class="mt-btn mt-edit" href="?edit=<?= $m['id_mitra'] ?>">✏️</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus mitra ini?')"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_mitra" value="<?= $m['id_mitra'] ?>"><button class="mt-btn mt-del">🗑️</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>