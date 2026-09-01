<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $judul = trim($_POST['judul'] ?? '');
        $isi   = trim($_POST['konten'] ?? '');
        if (!$judul || !$isi) {
            $error = 'Judul dan konten wajib diisi.';
        } else {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $judul)) . '-' . time();
            $db->prepare("INSERT INTO berita (id_user, judul, slug, kategori, konten, is_published, published_at) VALUES (?,?,?,?,?,?,NOW())")
               ->execute([Auth::id(), $judul, $slug, $_POST['kategori'], $isi, isset($_POST['publish']) ? 1 : 0]);
            $pesan = '✅ Berita berhasil disimpan.';
        }
    }

    if ($action === 'toggle') {
        $db->prepare("UPDATE berita SET is_published = 1 - is_published WHERE id_berita = ?")->execute([(int)$_POST['id_berita']]);
        $pesan = '✅ Status publikasi diubah.';
    }

    if ($action === 'delete') {
        $db->prepare("DELETE FROM berita WHERE id_berita = ?")->execute([(int)$_POST['id_berita']]);
        $pesan = '✅ Berita dihapus.';
    }
}

$berita = $db->query("SELECT b.*, u.nama_lengkap FROM berita b LEFT JOIN users u ON b.id_user = u.id_user ORDER BY b.created_at DESC")->fetchAll();

$simTitle = 'Berita & Agenda';
$activeMenu = 'berita';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">✍️ Tulis Berita / Agenda</h3>
    <form method="POST">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="create">
        <div style="display:grid;grid-template-columns:3fr 1fr;gap:16px;">
            <div class="form-group"><label class="form-label">Judul *</label>
                <input type="text" name="judul" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Kategori</label>
                <select name="kategori" class="form-control">
                    <option>Berita</option><option>Agenda</option><option>Pengumuman</option>
                </select></div>
        </div>
        <div class="form-group"><label class="form-label">Konten *</label>
            <textarea name="konten" class="form-control" rows="5" required></textarea></div>
        <label style="display:flex;gap:8px;align-items:center;margin-bottom:16px;">
            <input type="checkbox" name="publish" checked> Langsung publikasikan
        </label>
        <button type="submit" class="btn btn-primary">💾 Simpan Berita</button>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead><tr><th>Judul</th><th>Kategori</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($berita as $b): ?>
            <tr>
                <td><strong><?= Security::e($b['judul']) ?></strong></td>
                <td><span class="badge badge-a"><?= Security::e($b['kategori']) ?></span></td>
                <td><?= $b['is_published'] ? '🟢 Terbit' : '🟡 Draf' ?></td>
                <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline;"><input type="hidden" name="id_berita" value="<?= $b['id_berita'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-outline" style="padding:5px 12px;font-size:12px;"><?= $b['is_published'] ? 'Tarik' : 'Terbitkan' ?></button></form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus berita?');"><input type="hidden" name="id_berita" value="<?= $b['id_berita'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><button class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);">🗑️</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>