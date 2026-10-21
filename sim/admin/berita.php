<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

/* Konstanta lokal untuk modul berita */
define('BERITA_KATEGORI', ['Berita', 'Agenda', 'Pengumuman']);
define('COVER_EXTS', ['jpg', 'jpeg', 'png', 'webp']);

/* Helper: hapus file sampul lama dengan aman (anti path traversal) */
function unlinkCover(?string $rel): void {
    if (!$rel) return;
    $root = realpath(PATH_UPLOAD);
    $f    = realpath(PATH_UPLOAD . $rel);
    if ($root && $f && str_starts_with($f, $root . DIRECTORY_SEPARATOR) && is_file($f)) @unlink($f);
}

/* Helper: proses unggahan sampul, kembalikan path relatif atau null */
function handleCoverUpload(): ?string {
    global $error;
    if (empty($_FILES['cover']['name']) || ($_FILES['cover']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    if ($_FILES['cover']['size'] > MAX_UPLOAD_SIZE) { $error = '❌ Ukuran gambar sampul melebihi batas ' . round(MAX_UPLOAD_SIZE / 1048576) . ' MB.'; return null; }
    $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, COVER_EXTS, true)) { $error = '❌ Format sampul tidak didukung. Gunakan: ' . implode(', ', COVER_EXTS) . '.'; return null; }
    if (@getimagesize($_FILES['cover']['tmp_name']) === false) { $error = '❌ File yang diunggah bukan gambar yang valid.'; return null; }
    $dir = PATH_UPLOAD . 'berita/';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($_FILES['cover']['tmp_name'], $dir . $name)) { $error = '❌ Gagal menyimpan file sampul ke server.'; return null; }
    return 'berita/' . $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= CREATE =================
    if ($action === 'create') {
        $judul = trim($_POST['judul'] ?? '');
        $isi   = trim($_POST['konten'] ?? '');
        $kat   = $_POST['kategori'] ?? 'Berita';
        if (!$judul || !$isi) {
            $error = 'Judul dan konten wajib diisi.';
        } elseif (!in_array($kat, BERITA_KATEGORI, true)) {
            $error = '❌ Kategori tidak valid.';
        } else {
            try {
                $slugBase = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $judul));
                $slug     = substr(trim($slugBase, '-'), 0, 200) . '-' . time();
                $publish  = isset($_POST['publish']) ? 1 : 0;
                $cover    = handleCoverUpload();
                if ($error) { /* error unggah sampul — batalkan insert */ }
                else {
                    $db->prepare("INSERT INTO berita (id_user, judul, slug, kategori, konten, cover, is_published, published_at) VALUES (?,?,?,?,?,?,?,?)")
                       ->execute([Auth::id(), mb_substr($judul, 0, 200), $slug, $kat, $isi, $cover, $publish, $publish ? date('Y-m-d H:i:s') : null]);
                    Logger::log('CREATE', 'Menambah ' . $kat . ': ' . mb_substr($judul, 0, 60));
                    $pesan = '✅ Berita berhasil disimpan' . ($publish ? ' dan diterbitkan.' : ' sebagai draf.');
                }
            } catch (PDOException $e) {
                $error = '❌ Gagal menyimpan berita: ' . $e->getMessage();
            }
        }
    }

    // ================= EDIT =================
    if ($action === 'edit') {
        $id  = (int)($_POST['id_berita'] ?? 0);
        $judul = trim($_POST['judul'] ?? '');
        $isi   = trim($_POST['konten'] ?? '');
        $kat   = $_POST['kategori'] ?? 'Berita';
        if ($id <= 0 || !$judul || !$isi) {
            $error = 'Data berita tidak lengkap.';
        } elseif (!in_array($kat, BERITA_KATEGORI, true)) {
            $error = '❌ Kategori tidak valid.';
        } else {
            try {
                $old = $db->prepare("SELECT cover, slug FROM berita WHERE id_berita = ?");
                $old->execute([$id]);
                $row = $old->fetch();
                if (!$row) { $error = '❌ Berita tidak ditemukan.'; }
                else {
                    $publish = isset($_POST['publish']) ? 1 : 0;
                    $cover   = $row['cover'];
                    $newCover = handleCoverUpload();
                    if ($error) { /* error unggah — batalkan update */ }
                    else {
                        if ($newCover) { unlinkCover($cover); $cover = $newCover; }
                        // Slug TIDAK diubah agar tautan publik lama tidak rusak
                        $db->prepare("UPDATE berita SET judul = ?, kategori = ?, konten = ?, cover = ?, is_published = ?, published_at = CASE WHEN is_published = 0 AND ? = 1 THEN COALESCE(published_at, NOW()) ELSE published_at END WHERE id_berita = ?")
                           ->execute([mb_substr($judul, 0, 200), $kat, $isi, $cover, $publish, $publish, $id]);
                        Logger::log('UPDATE', 'Memperbarui berita #' . $id . ': ' . mb_substr($judul, 0, 60));
                        $pesan = '✅ Perubahan berita berhasil disimpan.';
                    }
                }
            } catch (PDOException $e) {
                $error = '❌ Gagal memperbarui berita: ' . $e->getMessage();
            }
        }
    }

    // ================= TOGGLE PUBLIKASI =================
    if ($action === 'toggle') {
        try {
            $db->prepare("UPDATE berita SET is_published = 1 - is_published, published_at = CASE WHEN is_published = 0 THEN COALESCE(published_at, NOW()) ELSE published_at END WHERE id_berita = ?")
               ->execute([(int)($_POST['id_berita'] ?? 0)]);
            Logger::log('UPDATE', 'Mengubah status publikasi berita #' . (int)($_POST['id_berita'] ?? 0));
            $pesan = '✅ Status publikasi diubah.';
        } catch (PDOException $e) { $error = '❌ Gagal mengubah status: ' . $e->getMessage(); }
    }

    // ================= DELETE =================
    if ($action === 'delete') {
        try {
            $id = (int)($_POST['id_berita'] ?? 0);
            $st = $db->prepare("SELECT cover FROM berita WHERE id_berita = ?");
            $st->execute([$id]);
            $row = $st->fetch();
            if ($row) {
                unlinkCover($row['cover']); // bersihkan file sampul dari server
                $db->prepare("DELETE FROM berita WHERE id_berita = ?")->execute([$id]);
                Logger::log('DELETE', 'Menghapus berita #' . $id);
                $pesan = '✅ Berita beserta file sampulnya telah dihapus.';
            } else { $error = '❌ Berita tidak ditemukan.'; }
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }
}

$berita = $db->query("SELECT b.*, u.nama_lengkap FROM berita b LEFT JOIN users u ON b.id_user = u.id_user ORDER BY b.created_at DESC LIMIT 300")->fetchAll();

$simTitle = 'Berita & Agenda';
$activeMenu = 'berita';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    .b-thumb { width: 52px; height: 38px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); display: block; }
    .b-thumb-empty { width: 52px; height: 38px; border-radius: 8px; background: var(--bg-light); border: 1px dashed var(--border); display: grid; place-items: center; font-size: 14px; color: var(--text-muted); }
    .b-judul { max-width: 340px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .b-penulis { font-size: 11.5px; color: var(--text-muted); font-weight: 500; }
</style>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">✍️ Tulis Berita / Agenda</h3>
    <form method="POST" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="create">
        <div style="display:grid;grid-template-columns:3fr 1fr;gap:16px;">
            <div class="form-group"><label class="form-label">Judul *</label>
                <input type="text" name="judul" class="form-control" maxlength="200" required></div>
            <div class="form-group"><label class="form-label">Kategori</label>
                <select name="kategori" class="form-control">
                    <?php foreach (BERITA_KATEGORI as $k): ?><option><?= $k ?></option><?php endforeach; ?>
                </select></div>
        </div>
        <div style="display:grid;grid-template-columns:3fr 1fr;gap:16px;">
            <div class="form-group"><label class="form-label">Konten *</label>
                <textarea name="konten" class="form-control" rows="5" required></textarea></div>
            <div class="form-group"><label class="form-label">Gambar Sampul <small class="text-muted">(opsional)</small></label>
                <input type="file" name="cover" class="form-control" accept="image/jpeg,image/png,image/webp"></div>
        </div>
        <label style="display:flex;gap:8px;align-items:center;margin-bottom:16px;">
            <input type="checkbox" name="publish" checked> Langsung publikasikan
        </label>
        <button type="submit" class="btn btn-primary">💾 Simpan Berita</button>
    </form>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
        <h3 style="margin:0;">📰 Arsip Berita (<?= count($berita) ?>)</h3>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <select id="filterKat" class="form-control" style="width:auto;">
                <option value="">Semua Kategori</option>
                <?php foreach (BERITA_KATEGORI as $k): ?><option value="<?= $k ?>"><?= $k ?></option><?php endforeach; ?>
            </select>
            <input type="text" id="cariBerita" class="form-control" placeholder="🔍 Cari judul..." style="max-width:240px;">
        </div>
    </div>
    <div class="table-wrapper">
        <table id="tabelBerita">
            <thead><tr><th style="width:64px;">Sampul</th><th>Judul</th><th>Kategori</th><th>Status</th><th>Tanggal</th><th style="width:170px;">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($berita)): ?><tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada berita. Mulai tulis publikasi pertama Anda ✍️</td></tr><?php endif; ?>
            <?php foreach ($berita as $b): ?>
                <tr class="berita-row" data-kategori="<?= Security::e($b['kategori']) ?>">
                    <td>
                        <?php if (!empty($b['cover']) && file_exists(PATH_UPLOAD . $b['cover'])): ?>
                            <img class="b-thumb" src="/uploads/<?= Security::e($b['cover']) ?>" alt="">
                        <?php else: ?><div class="b-thumb-empty">🖼️</div><?php endif; ?>
                    </td>
                    <td>
                        <strong class="b-judul"><?= Security::e($b['judul']) ?></strong>
                        <span class="b-penulis">oleh <?= Security::e($b['nama_lengkap'] ?? 'Sistem') ?></span>
                    </td>
                    <td><span class="badge badge-a"><?= Security::e($b['kategori']) ?></span></td>
                    <td><?= $b['is_published'] ? '🟢 Terbit' : '🟡 Draf' ?></td>
                    <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button class="btn btn-outline btn-edit-berita" style="padding:5px 12px;font-size:12px;"
                                data-id="<?= $b['id_berita'] ?>"
                                data-judul="<?= Security::e($b['judul']) ?>"
                                data-kategori="<?= Security::e($b['kategori']) ?>"
                                data-konten="<?= Security::e($b['konten']) ?>"
                                data-published="<?= (int)$b['is_published'] ?>"
                                data-cover="<?= Security::e($b['cover'] ?? '') ?>">✏️</button>
                            <form method="POST" style="display:inline;"><input type="hidden" name="id_berita" value="<?= $b['id_berita'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-outline" style="padding:5px 12px;font-size:12px;"><?= $b['is_published'] ? 'Tarik' : 'Terbitkan' ?></button></form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus berita ini beserta file sampulnya?');"><input type="hidden" name="id_berita" value="<?= $b['id_berita'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><button class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);">🗑️</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== MODAL EDIT BERITA ===== -->
<div id="modalEditBerita" style="display:none;position:fixed;inset:0;background:rgba(4,10,20,.62);backdrop-filter:blur(5px);z-index:4000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:var(--bg-card);border-radius:18px;box-shadow:var(--shadow-lg);border:1px solid var(--border);width:min(680px,100%);max-height:90vh;overflow-y:auto;">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:var(--bg-card);z-index:2;">
            <h3 style="margin:0;">✏️ Edit Berita</h3>
            <button type="button" onclick="document.getElementById('modalEditBerita').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);">×</button>
        </div>
        <form method="POST" enctype="multipart/form-data" style="padding:22px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_berita" id="eb_id">
            <div style="display:grid;grid-template-columns:3fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Judul *</label>
                    <input type="text" name="judul" id="eb_judul" class="form-control" maxlength="200" required></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Kategori</label>
                    <select name="kategori" id="eb_kategori" class="form-control">
                        <?php foreach (BERITA_KATEGORI as $k): ?><option><?= $k ?></option><?php endforeach; ?>
                    </select></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Konten *</label>
                <textarea name="konten" id="eb_konten" class="form-control" rows="7" required></textarea></div>
            <div style="display:grid;grid-template-columns:1fr auto;gap:14px;align-items:end;">
                <div class="form-group" style="margin:0;"><label class="form-label">Ganti Sampul <small class="text-muted">(kosongkan jika tetap)</small></label>
                    <input type="file" name="cover" class="form-control" accept="image/jpeg,image/png,image/webp"></div>
                <img id="eb_preview" src="" alt="" style="width:84px;height:60px;object-fit:cover;border-radius:10px;border:1px solid var(--border);display:none;">
            </div>
            <label style="display:flex;gap:8px;align-items:center;">
                <input type="checkbox" name="publish" id="eb_publish"> Status: Terbit
            </label>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalEditBerita').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
// ===== Pencarian & Filter Kategori (client-side) =====
(function () {
    var q = document.getElementById('cariBerita'), f = document.getElementById('filterKat');
    if (!q || !f) return;
    function apply() {
        var term = q.value.toLowerCase(), kat = f.value;
        document.querySelectorAll('#tabelBerita .berita-row').forEach(function (tr) {
            var okText = tr.textContent.toLowerCase().indexOf(term) > -1;
            var okKat  = !kat || tr.dataset.kategori === kat;
            tr.style.display = (okText && okKat) ? '' : 'none';
        });
    }
    q.addEventListener('input', apply);
    f.addEventListener('change', apply);
})();

// ===== Modal Edit =====
(function () {
    var modal = document.getElementById('modalEditBerita');
    if (!modal) return;
    document.querySelectorAll('.btn-edit-berita').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('eb_id').value = btn.dataset.id;
            document.getElementById('eb_judul').value = btn.dataset.judul;
            document.getElementById('eb_kategori').value = btn.dataset.kategori;
            document.getElementById('eb_konten').value = btn.dataset.konten;
            document.getElementById('eb_publish').checked = btn.dataset.published === '1';
            var prev = document.getElementById('eb_preview');
            if (btn.dataset.cover) { prev.src = '/uploads/' + btn.dataset.cover; prev.style.display = 'block'; }
            else { prev.src = ''; prev.style.display = 'none'; }
            modal.style.display = 'flex';
        });
    });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>