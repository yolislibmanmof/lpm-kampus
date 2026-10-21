<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

/* Konstanta enum sesuai skema */
define('DOC_STATUS', ['Aktif', 'Revisi', 'Nonaktif']);
define('DOC_AKSES', ['publik', 'internal']);
define('DOC_ALUR', ['Draf', 'Telaah', 'Disetujui', 'Perlu Revisi']);
define('DOC_PERIODE', [6, 12, 24, 36, 48]);

$kategori = $db->query("SELECT * FROM kategori_dokumen ORDER BY nama_kategori")->fetchAll();
$katIds = array_map('intval', array_column($kategori, 'id_kategori'));

/* Helper: hapus file lama dengan penahanan path traversal */
function unlinkDokumen(?string $rel): void {
    if (!$rel) return;
    $root = realpath(PATH_UPLOAD);
    $f    = realpath(PATH_UPLOAD . $rel);
    if ($root && $f && str_starts_with($f, $root . DIRECTORY_SEPARATOR) && is_file($f)) @unlink($f);
}

/* Helper: validasi tanggal Y-m-d */
function validDateStr(string $s): bool {
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= UPLOAD DOKUMEN BARU =================
    if ($action === 'upload') {
        $judul = mb_substr(trim($_POST['judul_dokumen'] ?? ''), 0, 200);
        $kode  = mb_substr(trim($_POST['kode_dokumen'] ?? ''), 0, 50);
        $idKat = (int)($_POST['id_kategori'] ?? 0);
        $akses = $_POST['tipe_akses'] ?? 'internal';
        $periode = (int)($_POST['periode_review_bulan'] ?? 24);
        $review  = trim($_POST['review_berikutnya'] ?? '');

        if ($judul === '' || !isset($_FILES['file'])) {
            $error = 'Judul dan file wajib diisi.';
        } elseif (!in_array($akses, DOC_AKSES, true)) {
            $error = '❌ Tipe akses tidak valid.';
        } elseif ($idKat !== 0 && !in_array($idKat, $katIds, true)) {
            $error = '❌ Kategori tidak ditemukan.';
        } elseif ($review !== '' && !validDateStr($review)) {
            $error = '❌ Format tanggal review tidak valid (YYYY-MM-DD).';
        } else {
            if (!in_array($periode, DOC_PERIODE, true)) $periode = 24;
            if ($review === '') $review = date('Y-m-d', strtotime("+$periode months"));
            $up = Security::secureUpload($_FILES['file'], 'dokumen');
            if (!$up['success']) {
                $error = $up['message'];
            } else {
                try {
                    $db->prepare("INSERT INTO dokumen_mutu
                        (id_kategori, id_user, kode_dokumen, judul_dokumen, deskripsi, file_path, tipe_akses, versi, review_berikutnya, periode_review_bulan)
                        VALUES (?,?,?,?,?,?,?,1,?,?)")
                       ->execute([
                           $idKat !== 0 ? $idKat : null,
                           Auth::id(),
                           $kode !== '' ? $kode : null,   // WAJIB NULL agar UNIQUE tidak melanggar
                           $judul,
                           trim($_POST['deskripsi'] ?? ''),
                           $up['path'],
                           $akses,
                           $review,
                           $periode
                       ]);
                    Logger::log('CREATE', 'Mengunggah dokumen mutu: ' . $judul . ($kode !== '' ? " ($kode)" : ''));
                    $pesan = '✅ Dokumen berhasil diunggah. Review berikutnya: ' . date('d M Y', strtotime($review)) . '.';
                } catch (PDOException $e) {
                    unlinkDokumen($up['path']); // bersihkan file yatim bila insert gagal
                    if ($e->getCode() == 23000 || stripos($e->getMessage(), 'Duplicate') !== false) {
                        $error = '❌ Kode dokumen tersebut sudah digunakan. Gunakan kode lain.';
                    } else { $error = '❌ Gagal menyimpan: ' . $e->getMessage(); }
                }
            }
        }
    }

    // ================= UNGGAH VERSI BARU =================
    if ($action === 'newversion') {
        $id = (int)($_POST['id_dokumen'] ?? 0);
        if ($id <= 0 || !isset($_FILES['file'])) { $error = 'Data tidak lengkap.'; }
        else {
            try {
                $st = $db->prepare("SELECT file_path, versi, judul_dokumen, periode_review_bulan FROM dokumen_mutu WHERE id_dokumen = ?");
                $st->execute([$id]);
                $old = $st->fetch();
                if (!$old) { $error = '❌ Dokumen tidak ditemukan.'; }
                else {
                    $up = Security::secureUpload($_FILES['file'], 'dokumen');
                    if (!$up['success']) { $error = $up['message']; }
                    else {
                        unlinkDokumen($old['file_path']);
                        $newReview = date('Y-m-d', strtotime('+' . (int)$old['periode_review_bulan'] . ' months'));
                        $db->prepare("UPDATE dokumen_mutu SET file_path = ?, versi = versi + 1, uploaded_at = NOW(), review_berikutnya = ? WHERE id_dokumen = ?")
                           ->execute([$up['path'], $newReview, $id]);
                        Logger::log('UPDATE', 'Mengunggah versi baru dokumen #' . $id . ' (' . $old['judul_dokumen'] . ') ke v' . ((int)$old['versi'] + 1));
                        $pesan = '✅ Versi baru tersimpan (v' . ((int)$old['versi'] + 1) . '). Tanggal review diperbarui otomatis.';
                    }
                }
            } catch (PDOException $e) { $error = '❌ Gagal memperbarui versi: ' . $e->getMessage(); }
        }
    }

    // ================= EDIT METADATA =================
    if ($action === 'edit') {
        $id    = (int)($_POST['id_dokumen'] ?? 0);
        $judul = mb_substr(trim($_POST['judul_dokumen'] ?? ''), 0, 200);
        $kode  = mb_substr(trim($_POST['kode_dokumen'] ?? ''), 0, 50);
        $idKat = (int)($_POST['id_kategori'] ?? 0);
        $akses = $_POST['tipe_akses'] ?? 'internal';
        $alur  = $_POST['alur_status'] ?? 'Disetujui';
        $periode = (int)($_POST['periode_review_bulan'] ?? 24);
        $review  = trim($_POST['review_berikutnya'] ?? '');
        $catatan = mb_substr(trim($_POST['catatan_review'] ?? ''), 0, 255);

        if ($id <= 0 || $judul === '') { $error = 'Judul wajib diisi.'; }
        elseif (!in_array($akses, DOC_AKSES, true) || !in_array($alur, DOC_ALUR, true)) { $error = '❌ Nilai enum tidak valid.'; }
        elseif ($idKat !== 0 && !in_array($idKat, $katIds, true)) { $error = '❌ Kategori tidak ditemukan.'; }
        elseif ($review !== '' && !validDateStr($review)) { $error = '❌ Format tanggal review tidak valid.'; }
        else {
            if (!in_array($periode, DOC_PERIODE, true)) $periode = 24;
            try {
                $db->prepare("UPDATE dokumen_mutu SET id_kategori=?, kode_dokumen=?, judul_dokumen=?, deskripsi=?, tipe_akses=?, alur_status=?, periode_review_bulan=?, review_berikutnya=?, catatan_review=? WHERE id_dokumen=?")
                   ->execute([
                       $idKat !== 0 ? $idKat : null,
                       $kode !== '' ? $kode : null,
                       $judul,
                       trim($_POST['deskripsi'] ?? ''),
                       $akses, $alur, $periode,
                       $review !== '' ? $review : null,
                       $catatan !== '' ? $catatan : null,
                       $id
                   ]);
                Logger::log('UPDATE', 'Memperbarui metadata dokumen #' . $id . ': ' . $judul);
                $pesan = '✅ Metadata dokumen diperbarui.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || stripos($e->getMessage(), 'Duplicate') !== false) {
                    $error = '❌ Kode dokumen tersebut sudah digunakan oleh dokumen lain.';
                } else { $error = '❌ Gagal memperbarui: ' . $e->getMessage(); }
            }
        }
    }

    // ================= UBAH STATUS =================
    if ($action === 'status') {
        $st = $_POST['status'] ?? '';
        if (!in_array($st, DOC_STATUS, true)) { $error = '❌ Status tidak valid.'; }
        else {
            try {
                $db->prepare("UPDATE dokumen_mutu SET status = ? WHERE id_dokumen = ?")->execute([$st, (int)($_POST['id_dokumen'] ?? 0)]);
                Logger::log('UPDATE', 'Mengubah status dokumen #' . (int)($_POST['id_dokumen'] ?? 0) . ' menjadi ' . $st);
                $pesan = '✅ Status dokumen diperbarui.';
            } catch (PDOException $e) { $error = '❌ Gagal mengubah status: ' . $e->getMessage(); }
        }
    }

    // ================= HAPUS =================
    if ($action === 'delete') {
        try {
            $id = (int)($_POST['id_dokumen'] ?? 0);
            $stmt = $db->prepare("SELECT file_path, judul_dokumen FROM dokumen_mutu WHERE id_dokumen = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();
            if ($doc) {
                unlinkDokumen($doc['file_path']);
                $db->prepare("DELETE FROM dokumen_mutu WHERE id_dokumen = ?")->execute([$id]);
                Logger::log('DELETE', 'Menghapus dokumen #' . $id . ': ' . $doc['judul_dokumen']);
                $pesan = '✅ Dokumen beserta file fisiknya dihapus.';
            } else { $error = '❌ Dokumen tidak ditemukan.'; }
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }
}

$dokumen = $db->query("SELECT d.*, k.nama_kategori, u.nama_lengkap FROM dokumen_mutu d LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori LEFT JOIN users u ON d.id_user = u.id_user ORDER BY d.uploaded_at DESC LIMIT 500")->fetchAll();

/* Statistik singkat */
$statAktif = count(array_filter($dokumen, fn($d) => $d['status'] === 'Aktif'));
$statPerluRevisi = count(array_filter($dokumen, fn($d) => $d['alur_status'] === 'Perlu Revisi'));
$statSegera = count(array_filter($dokumen, function ($d) {
    return $d['status'] === 'Aktif' && $d['review_berikutnya'] && (int)floor((strtotime($d['review_berikutnya']) - time()) / 86400) <= 90;
}));

$simTitle = 'Dokumen PPEPP';
$activeMenu = 'dokumen';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">📁</div><div><h3 style="font-size:26px;"><?= count($dokumen) ?></h3><p class="text-muted">Total Dokumen</p></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><h3 style="font-size:26px;"><?= $statAktif ?></h3><p class="text-muted">Status Aktif</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">⏳</div><div><h3 style="font-size:26px;"><?= $statSegera ?></h3><p class="text-muted">Review ≤ 90 Hari</p></div></div>
    <div class="stat-card"><div class="stat-icon red">✏️</div><div><h3 style="font-size:26px;"><?= $statPerluRevisi ?></h3><p class="text-muted">Perlu Revisi</p></div></div>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">📤 Upload Dokumen Mutu</h3>
    <form method="POST" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="upload">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            <div class="form-group"><label class="form-label">Kode Dokumen <small class="text-muted">(opsional, unik)</small></label>
                <input type="text" name="kode_dokumen" class="form-control" maxlength="50" placeholder="MM-01-2026"></div>
            <div class="form-group"><label class="form-label">Judul Dokumen *</label>
                <input type="text" name="judul_dokumen" class="form-control" maxlength="200" required></div>
            <div class="form-group"><label class="form-label">Kategori</label>
                <select name="id_kategori" class="form-control">
                    <option value="0">— Tanpa Kategori —</option>
                    <?php foreach ($kategori as $k): ?><option value="<?= $k['id_kategori'] ?>"><?= Security::e($k['nama_kategori']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Akses</label>
                <select name="tipe_akses" class="form-control">
                    <option value="internal">Internal (login)</option>
                    <option value="publik">Publik</option>
                </select></div>
            <div class="form-group"><label class="form-label">File (max <?= round(MAX_UPLOAD_SIZE / 1048576) ?>MB)</label>
                <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.png" required></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:14px;">
            <div class="form-group"><label class="form-label">Periode Review</label>
                <select name="periode_review_bulan" class="form-control">
                    <option value="6">Setiap 6 bulan</option>
                    <option value="12">Setiap 12 bulan</option>
                    <option value="24" selected>Setiap 24 bulan</option>
                    <option value="36">Setiap 36 bulan</option>
                    <option value="48">Setiap 48 bulan</option>
                </select></div>
            <div class="form-group"><label class="form-label">Tanggal Review Berikutnya <small class="text-muted">(kosongkan = otomatis)</small></label>
                <input type="date" name="review_berikutnya" class="form-control"></div>
            <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="2"></textarea></div>
        </div>
        <button type="submit" class="btn btn-primary">📤 Unggah Dokumen</button>
    </form>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3 style="margin:0;">🗂️ Arsip Dokumen (<?= count($dokumen) ?>)</h3>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <select id="fKat" class="form-control" style="width:auto;">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategori as $k): ?><option value="<?= Security::e($k['nama_kategori']) ?>"><?= Security::e($k['nama_kategori']) ?></option><?php endforeach; ?>
            </select>
            <select id="fStatus" class="form-control" style="width:auto;">
                <option value="">Semua Status</option>
                <?php foreach (DOC_STATUS as $s): ?><option><?= $s ?></option><?php endforeach; ?>
            </select>
            <input type="text" id="cariDok" class="form-control" placeholder="🔍 Cari kode / judul..." style="max-width:220px;">
        </div>
    </div>
    <div class="table-wrapper">
        <table id="tabelDok">
            <thead><tr><th>Kode</th><th>Judul</th><th>Kategori</th><th>Akses</th><th>Status</th><th>Review Berikutnya</th><th style="width:170px;">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($dokumen)): ?><tr><td colspan="7" style="text-align:center;padding:30px;" class="text-muted">Belum ada dokumen mutu terunggah.</td></tr><?php endif; ?>
            <?php foreach ($dokumen as $d):
                $days = $d['review_berikutnya'] ? (int)floor((strtotime($d['review_berikutnya']) - time()) / 86400) : null;
            ?>
                <tr class="dok-row" data-kat="<?= Security::e($d['nama_kategori'] ?? '') ?>" data-status="<?= Security::e($d['status']) ?>">
                    <td><code style="font-size:12px;"><?= Security::e($d['kode_dokumen'] ?? '—') ?></code></td>
                    <td>
                        <strong><?= Security::e($d['judul_dokumen']) ?></strong>
                        <span class="badge badge-b" style="margin-left:6px;">v<?= (int)$d['versi'] ?></span>
                        <?php if ($d['alur_status'] === 'Perlu Revisi'): ?><span class="badge" style="background:rgba(239,68,68,.15);color:#DC2626;margin-left:4px;">Perlu Revisi</span><?php endif; ?>
                        <br><small class="text-muted">oleh <?= Security::e($d['nama_lengkap'] ?? '—') ?></small>
                    </td>
                    <td><?= Security::e($d['nama_kategori'] ?? '—') ?></td>
                    <td><span class="badge <?= $d['tipe_akses'] === 'publik' ? 'badge-unggul' : 'badge-b' ?>"><?= $d['tipe_akses'] ?></span></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>">
                            <?= Security::csrfField() ?><input type="hidden" name="action" value="status">
                            <select name="status" class="form-control" style="padding:5px;width:auto;display:inline-block;" onchange="this.form.submit()">
                                <?php foreach (DOC_STATUS as $s): ?><option <?= $d['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <?php if ($days === null): ?><span class="text-muted">—</span>
                        <?php elseif ($days < 0): ?><span class="badge" style="background:rgba(239,68,68,.15);color:#DC2626;">Lewat <?= abs($days) ?> hari</span>
                        <?php elseif ($days <= 90): ?><span class="badge" style="background:rgba(245,158,11,.15);color:#B45309;">H-<?= $days ?></span>
                        <?php else: ?><span class="text-muted"><?= date('d M Y', strtotime($d['review_berikutnya'])) ?></span><?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap;">
                            <a href="/download.php?id=<?= $d['id_dokumen'] ?>" class="btn btn-outline" style="padding:5px 10px;font-size:12px;" title="Unduh">📥</a>
                            <button class="btn btn-outline btn-newver" style="padding:5px 10px;font-size:12px;" title="Unggah versi baru"
                                data-id="<?= $d['id_dokumen'] ?>" data-judul="<?= Security::e($d['judul_dokumen']) ?>" data-versi="<?= (int)$d['versi'] ?>">🔁</button>
                            <button class="btn btn-outline btn-edit-dok" style="padding:5px 10px;font-size:12px;" title="Edit metadata"
                                data-id="<?= $d['id_dokumen'] ?>"
                                data-kode="<?= Security::e($d['kode_dokumen'] ?? '') ?>"
                                data-judul="<?= Security::e($d['judul_dokumen']) ?>"
                                data-kat="<?= (int)($d['id_kategori'] ?? 0) ?>"
                                data-akses="<?= Security::e($d['tipe_akses']) ?>"
                                data-alur="<?= Security::e($d['alur_status']) ?>"
                                data-periode="<?= (int)$d['periode_review_bulan'] ?>"
                                data-review="<?= Security::e($d['review_berikutnya'] ?? '') ?>"
                                data-catatan="<?= Security::e($d['catatan_review'] ?? '') ?>"
                                data-deskripsi="<?= Security::e($d['deskripsi'] ?? '') ?>">✏️</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus dokumen beserta file fisiknya?');">
                                <input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>">
                                <?= Security::csrfField() ?><input type="hidden" name="action" value="delete">
                                <button class="btn btn-outline" style="padding:5px 10px;font-size:12px;color:var(--danger);" title="Hapus">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== MODAL VERSI BARU ===== -->
<div id="modalNewVer" style="display:none;position:fixed;inset:0;background:rgba(4,10,20,.62);backdrop-filter:blur(5px);z-index:4000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:var(--bg-card);border-radius:18px;box-shadow:var(--shadow-lg);border:1px solid var(--border);width:min(480px,100%);overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);"><h3 style="margin:0;">🔁 Unggah Versi Baru</h3></div>
        <form method="POST" enctype="multipart/form-data" style="padding:22px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="newversion">
            <input type="hidden" name="id_dokumen" id="nv_id">
            <p class="text-muted" style="margin:0;font-size:13px;">Dokumen: <strong id="nv_judul" style="color:var(--text-dark);"></strong><br>Versi saat ini: <strong id="nv_versi" style="color:var(--accent);"></strong> — file lama akan diganti dan tanggal review diperbarui otomatis.</p>
            <div class="form-group" style="margin:0;"><label class="form-label">File Versi Baru *</label>
                <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.png" required></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalNewVer').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">🔁 Simpan Versi Baru</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL EDIT METADATA ===== -->
<div id="modalEditDok" style="display:none;position:fixed;inset:0;background:rgba(4,10,20,.62);backdrop-filter:blur(5px);z-index:4000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:var(--bg-card);border-radius:18px;box-shadow:var(--shadow-lg);border:1px solid var(--border);width:min(640px,100%);max-height:90vh;overflow-y:auto;">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg-card);"><h3 style="margin:0;">✏️ Edit Metadata Dokumen</h3></div>
        <form method="POST" style="padding:22px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_dokumen" id="ed_id">
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Kode</label>
                    <input type="text" name="kode_dokumen" id="ed_kode" class="form-control" maxlength="50"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Judul *</label>
                    <input type="text" name="judul_dokumen" id="ed_judul" class="form-control" maxlength="200" required></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Kategori</label>
                    <select name="id_kategori" id="ed_kat" class="form-control">
                        <option value="0">— Tanpa Kategori —</option>
                        <?php foreach ($kategori as $k): ?><option value="<?= $k['id_kategori'] ?>"><?= Security::e($k['nama_kategori']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Akses</label>
                    <select name="tipe_akses" id="ed_akses" class="form-control"><option value="internal">Internal</option><option value="publik">Publik</option></select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Alur Persetujuan</label>
                    <select name="alur_status" id="ed_alur" class="form-control"><?php foreach (DOC_ALUR as $a): ?><option><?= $a ?></option><?php endforeach; ?></select></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Periode Review</label>
                    <select name="periode_review_bulan" id="ed_periode" class="form-control">
                        <?php foreach (DOC_PERIODE as $p): ?><option value="<?= $p ?>">Setiap <?= $p ?> bulan</option><?php endforeach; ?>
                    </select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Review Berikutnya</label>
                    <input type="date" name="review_berikutnya" id="ed_review" class="form-control"></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" id="ed_deskripsi" class="form-control" rows="2"></textarea></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Catatan Review</label>
                <input type="text" name="catatan_review" id="ed_catatan" class="form-control" maxlength="255"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalEditDok').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Filter & pencarian
(function () {
    var q = document.getElementById('cariDok'), fk = document.getElementById('fKat'), fs = document.getElementById('fStatus');
    if (!q) return;
    function apply() {
        var term = q.value.toLowerCase(), kat = fk.value, st = fs.value;
        document.querySelectorAll('#tabelDok .dok-row').forEach(function (tr) {
            var okT = tr.textContent.toLowerCase().indexOf(term) > -1;
            var okK = !kat || tr.dataset.kat === kat;
            var okS = !st || tr.dataset.status === st;
            tr.style.display = (okT && okK && okS) ? '' : 'none';
        });
    }
    q.addEventListener('input', apply); fk.addEventListener('change', apply); fs.addEventListener('change', apply);
})();

// Modal versi baru
(function () {
    var m = document.getElementById('modalNewVer');
    document.querySelectorAll('.btn-newver').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('nv_id').value = b.dataset.id;
            document.getElementById('nv_judul').textContent = b.dataset.judul;
            document.getElementById('nv_versi').textContent = 'v' + b.dataset.versi;
            m.style.display = 'flex';
        });
    });
    m.addEventListener('click', function (e) { if (e.target === m) m.style.display = 'none'; });
})();

// Modal edit
(function () {
    var m = document.getElementById('modalEditDok');
    document.querySelectorAll('.btn-edit-dok').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('ed_id').value = b.dataset.id;
            document.getElementById('ed_kode').value = b.dataset.kode;
            document.getElementById('ed_judul').value = b.dataset.judul;
            document.getElementById('ed_kat').value = b.dataset.kat;
            document.getElementById('ed_akses').value = b.dataset.akses;
            document.getElementById('ed_alur').value = b.dataset.alur;
            document.getElementById('ed_periode').value = b.dataset.periode;
            document.getElementById('ed_review').value = b.dataset.review;
            document.getElementById('ed_catatan').value = b.dataset.catatan;
            document.getElementById('ed_deskripsi').value = b.dataset.deskripsi;
            m.style.display = 'flex';
        });
    });
    m.addEventListener('click', function (e) { if (e.target === m) m.style.display = 'none'; });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>