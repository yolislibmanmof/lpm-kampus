<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();

$simTitle   = 'Akreditasi Internasional';
$activeMenu = 'akrintl';

$msg = ''; $err = '';

// ===== ACTIONS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // === UPLOAD SERTIFIKAT ===
    $filePath = null;
    if (isset($_FILES['sertifikat']) && $_FILES['sertifikat']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['sertifikat']['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $dir = PATH_UPLOAD . 'akreditasi-intl/';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
            if (move_uploaded_file($_FILES['sertifikat']['tmp_name'], $dir . $name)) {
                $filePath = 'akreditasi-intl/' . $name;
            }
        } else {
            $err = 'Hanya file PDF yang diterima.';
        }
    }

    // === ADD / UPDATE ===
    if (in_array($action, ['add', 'update']) && !$err) {
        $lembaga = trim($_POST['lembaga'] ?? '');
        if ($lembaga === '__custom') {
            $lembaga = trim($_POST['custom_lembaga'] ?? '');
            if (!$lembaga) $err = 'Nama lembaga custom harus diisi.';
        }

        $data = [
            ':id_prodi'           => $_POST['id_prodi'] ?: null,
            ':lembaga'            => $lembaga,
            ':tingkat'            => $_POST['tingkat'] ?? 'Prodi',
            ':peringkat'          => trim($_POST['peringkat'] ?? ''),
            ':tanggal_sertifikat' => $_POST['tanggal_sertifikat'] ?: null,
            ':masa_berlaku'       => $_POST['masa_berlaku'] ?: null,
            ':nomor_sertifikat'   => trim($_POST['nomor_sertifikat'] ?? ''),
            ':negara_lembaga'     => trim($_POST['negara_lembaga'] ?? ''),
            ':logo_url'           => trim($_POST['logo_url'] ?? ''),
            ':catatan'            => trim($_POST['catatan'] ?? ''),
        ];

        if ($action === 'add' && !$err) {
            $sql = "INSERT INTO akreditasi_internasional 
                    (id_prodi, lembaga, tingkat, peringkat, tanggal_sertifikat, masa_berlaku, 
                     nomor_sertifikat, file_path, negara_lembaga, logo_url, catatan)
                    VALUES (:id_prodi, :lembaga, :tingkat, :peringkat, :tanggal_sertifikat, 
                            :masa_berlaku, :nomor_sertifikat, :file_path, :negara_lembaga, :logo_url, :catatan)";
            $data[':file_path'] = $filePath;
            $db->prepare($sql)->execute($data);
            $msg = '✅ Akreditasi internasional berhasil ditambahkan.';
        } elseif ($action === 'update' && !$err) {
            $sql = "UPDATE akreditasi_internasional SET
                    id_prodi = :id_prodi, lembaga = :lembaga, tingkat = :tingkat,
                    peringkat = :peringkat, tanggal_sertifikat = :tanggal_sertifikat,
                    masa_berlaku = :masa_berlaku, nomor_sertifikat = :nomor_sertifikat,
                    negara_lembaga = :negara_lembaga, logo_url = :logo_url, catatan = :catatan";
            if ($filePath) {
                $old = $db->prepare("SELECT file_path FROM akreditasi_internasional WHERE id_intl = ?");
                $old->execute([$_POST['id_intl']]);
                $oldFile = $old->fetch()['file_path'] ?? null;
                if ($oldFile && file_exists(PATH_UPLOAD . $oldFile)) @unlink(PATH_UPLOAD . $oldFile);
                $sql .= ", file_path = :file_path";
                $data[':file_path'] = $filePath;
            }
            $sql .= " WHERE id_intl = :id";
            $data[':id'] = (int)$_POST['id_intl'];
            $db->prepare($sql)->execute($data);
            $msg = '✅ Data akreditasi internasional diperbarui.';
        }
    }

    // === DELETE ===
    if ($action === 'delete') {
        $id = (int)($_POST['id_intl'] ?? 0);
        $old = $db->prepare("SELECT file_path FROM akreditasi_internasional WHERE id_intl = ?");
        $old->execute([$id]);
        $oldFile = $old->fetch()['file_path'] ?? null;
        if ($oldFile && file_exists(PATH_UPLOAD . $oldFile)) @unlink(PATH_UPLOAD . $oldFile);
        $db->prepare("DELETE FROM akreditasi_internasional WHERE id_intl = ?")->execute([$id]);
        $msg = '🗑️ Data dihapus.';
    }
}

// ===== FETCH DATA =====
$list = $db->query("
    SELECT a.*, p.nama_prodi, f.nama_fakultas
    FROM akreditasi_internasional a
    LEFT JOIN prodi p ON a.id_prodi = p.id_prodi
    LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
    ORDER BY a.masa_berlaku DESC, a.lembaga ASC
")->fetchAll();

$prodiList = $db->query("SELECT p.id_prodi, p.nama_prodi, f.nama_fakultas
    FROM prodi p LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
    ORDER BY f.nama_fakultas, p.nama_prodi")->fetchAll();
$lembagaList = $db->query("SELECT * FROM lembaga_internasional ORDER BY urutan, nama")->fetchAll();

// Edit mode
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editItem = null;
if ($editId) {
    $st = $db->prepare("SELECT * FROM akreditasi_internasional WHERE id_intl = ?");
    $st->execute([$editId]);
    $editItem = $st->fetch();
}

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
/* ============================================================
   AKREDITASI INTERNASIONAL — FINAL STYLES
   Dark mode ready • Responsive • Clean layout
============================================================ */

/* ===== STATS ===== */
.int-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px; }
.int-stat { background: var(--bg-card); border-radius: 14px; padding: 18px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); }
.int-stat-n { font-size: 28px; font-weight: 800; color: var(--text-dark); line-height: 1; }
.int-stat-l { font-size: 11px; color: var(--text-muted); letter-spacing: 1px; text-transform: uppercase; font-weight: 700; margin-top: 4px; }

/* ===== LAYOUT ===== */
.int-grid { display: grid; grid-template-columns: 380px 1fr; gap: 24px; margin-top: 24px; }
@media (max-width: 992px) { .int-grid { grid-template-columns: 1fr; } }

/* ===== FORM ===== */
.int-form {
    background: var(--bg-card); border-radius: 20px; padding: 28px;
    border: 1px solid var(--border); position: sticky; top: 96px;
    align-self: start; box-shadow: var(--shadow-sm);
    max-height: calc(100vh - 120px); overflow-y: auto;
}
.int-form::-webkit-scrollbar { width: 5px; }
.int-form::-webkit-scrollbar-thumb { background: var(--border); border-radius: 5px; }
.int-form h3 { margin: 0 0 18px; font-size: 18px; color: var(--text-dark); display: flex; align-items: center; gap: 8px; }

.int-form .form-group { min-width: 0; margin-bottom: 14px; }
.int-form .form-label {
    font-size: 11px; letter-spacing: .8px; text-transform: uppercase;
    font-weight: 800; color: var(--text-muted); margin-bottom: 6px; display: block;
}
.int-form .form-control {
    width: 100%; min-width: 0; box-sizing: border-box;
    background: var(--bg-light); color: var(--text-dark);
    border: 1.5px solid var(--border); border-radius: 10px;
    padding: 10px 12px; font-size: 13px; font-family: inherit;
    transition: border-color .2s, box-shadow .2s;
}
.int-form .form-control:focus {
    outline: none; border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(201,162,39,.15);
}
.int-form textarea.form-control { min-height: 70px; resize: vertical; }
.int-form select.form-control { cursor: pointer; }

/* Grid 2 kolom tanggal */
.int-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
.int-2col > .form-group { margin-bottom: 0; }

/* Input tanggal + file: ikut dark mode */
.int-form input[type="date"].form-control,
.int-form input[type="file"].form-control {
    color-scheme: light; padding: 8px 10px;
}
body.dark .int-form input[type="date"].form-control,
body.dark .int-form input[type="file"].form-control { color-scheme: dark; }

.int-file-note { display: block; color: #059669; font-size: 12px; margin-top: 6px; }
@media (max-width: 520px) { .int-2col { grid-template-columns: 1fr; } }

/* ===== TABLE ===== */
.int-table-wrap { background: var(--bg-card); border-radius: 20px; overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow-x: auto; }
.int-table { width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 720px; }
.int-table th { background: var(--bg-light); text-align: left; padding: 14px 16px; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid var(--border); }
.int-table td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.int-table tr:last-child td { border-bottom: none; }
.int-table tr:hover { background: var(--bg-light); }

.int-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700; letter-spacing: .5px; }
.int-prodi-badge { background: rgba(16,185,129,.1); color: #059669; }
.int-inst-badge { background: rgba(59,130,246,.1); color: #2563EB; }

.int-lembaga-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 8px; background: var(--bg-light); font-size: 11px; font-weight: 700; color: var(--text-dark); }
.int-lembaga-pill .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

.int-actions { display: flex; gap: 6px; }
.int-actions form { display: inline; }
.int-btn-sm { padding: 6px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; cursor: pointer; border: none; transition: .2s; font-family: inherit; }
.int-btn-edit { background: rgba(59,130,246,.1); color: #2563EB; }
.int-btn-edit:hover { background: #2563EB; color: #fff; }
.int-btn-del { background: rgba(239,68,68,.1); color: #DC2626; }
.int-btn-del:hover { background: #DC2626; color: #fff; }

.int-chip-ok { background: #D1FAE5; color: #065F46; padding: 3px 8px; border-radius: 50px; font-size: 10.5px; font-weight: 800; }
.int-chip-warn { background: #FEF3C7; color: #92400E; padding: 3px 8px; border-radius: 50px; font-size: 10.5px; font-weight: 800; }
.int-chip-exp { background: #FEE2E2; color: #991B1B; padding: 3px 8px; border-radius: 50px; font-size: 10.5px; font-weight: 800; }

.int-empty { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.int-empty-ic { font-size: 56px; opacity: .4; margin-bottom: 10px; }
.int-empty h3 { color: var(--text-dark); }

/* ===== BUTTONS ===== */
.int-form .btn-gold, .int-form .btn-ghost {
    padding: 12px 16px; border-radius: 12px; font-size: 14px; font-weight: 700;
    font-family: inherit; cursor: pointer; text-decoration: none;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    border: 1.5px solid transparent; transition: .25s;
}
.int-form .btn-gold {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); box-shadow: 0 6px 18px rgba(201,162,39,.3);
    border: none;
}
.int-form .btn-gold:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(201,162,39,.4); }
.int-form .btn-ghost { background: transparent; color: var(--text-muted); border-color: var(--border); }
.int-form .btn-ghost:hover { background: var(--bg-light); color: var(--text-dark); }
</style>

<!-- ===== STATS ===== -->
<div class="int-stats">
    <?php
    $total = count($list);
    $prodiCount = count(array_filter($list, fn($i) => $i['tingkat'] === 'Prodi'));
    $instCount = count(array_filter($list, fn($i) => $i['tingkat'] === 'Institusi'));
    $lembagaUnik = count(array_unique(array_column($list, 'lembaga')));
    $activeCount = 0;
    foreach ($list as $i) {
        if ($i['masa_berlaku'] && strtotime($i['masa_berlaku']) > time()) $activeCount++;
    }
    ?>
    <div class="int-stat"><div class="int-stat-n"><?= $total ?></div><div class="int-stat-l">Total</div></div>
    <div class="int-stat"><div class="int-stat-n" style="color:#059669;"><?= $activeCount ?></div><div class="int-stat-l">Aktif</div></div>
    <div class="int-stat"><div class="int-stat-n"><?= $prodiCount ?></div><div class="int-stat-l">Prodi</div></div>
    <div class="int-stat"><div class="int-stat-n"><?= $instCount ?></div><div class="int-stat-l">Institusi</div></div>
    <div class="int-stat"><div class="int-stat-n"><?= $lembagaUnik ?></div><div class="int-stat-l">Lembaga</div></div>
</div>

<div class="int-grid">
    <!-- ===== FORM ===== -->
    <div class="int-form">
        <h3><?= $editItem ? '✏️ Edit Data' : '➕ Tambah Akreditasi Internasional' ?></h3>
        <form method="POST" enctype="multipart/form-data" id="intForm">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'add' ?>">
            <?php if ($editItem): ?>
                <input type="hidden" name="id_intl" value="<?= $editItem['id_intl'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Lembaga Akreditasi *</label>
                <select name="lembaga" class="form-control" required>
                    <option value="">— Pilih Lembaga —</option>
                    <?php foreach ($lembagaList as $l): ?>
                        <option value="<?= $l['kode'] ?>" data-negara="<?= $l['negara'] ?>" <?= ($editItem['lembaga'] ?? '') === $l['kode'] ? 'selected' : '' ?>>
                            <?= $l['kode'] ?> — <?= Security::e($l['nama']) ?> (<?= $l['negara'] ?>)
                        </option>
                    <?php endforeach; ?>
                    <option value="__custom" <?= ($editItem && !in_array($editItem['lembaga'], array_column($lembagaList, 'kode'))) ? 'selected' : '' ?>>➕ Lembaga Lain (Custom)</option>
                </select>
            </div>

            <div class="form-group" id="customLembaga" style="display:none;">
                <label class="form-label">Nama Lembaga Custom</label>
                <input type="text" name="custom_lembaga" class="form-control" placeholder="Contoh: AACSB">
            </div>

            <div class="form-group">
                <label class="form-label">Negara Lembaga</label>
                <input type="text" name="negara_lembaga" class="form-control" value="<?= Security::e($editItem['negara_lembaga'] ?? '') ?>" placeholder="Germany, USA, ASEAN...">
            </div>

            <div class="form-group">
                <label class="form-label">Tingkat *</label>
                <select name="tingkat" id="intTingkat" class="form-control" required>
                    <option value="Prodi" <?= ($editItem['tingkat'] ?? '') === 'Prodi' ? 'selected' : '' ?>>Program Studi</option>
                    <option value="Institusi" <?= ($editItem['tingkat'] ?? '') === 'Institusi' ? 'selected' : '' ?>>Institusi (Kampus)</option>
                </select>
            </div>

            <div class="form-group" id="prodiWrap">
                <label class="form-label">Program Studi</label>
                <select name="id_prodi" class="form-control">
                    <option value="">— Institusi (tidak ada prodi) —</option>
                    <?php foreach ($prodiList as $p): ?>
                        <option value="<?= $p['id_prodi'] ?>" <?= ($editItem['id_prodi'] ?? '') == $p['id_prodi'] ? 'selected' : '' ?>>
                            <?= Security::e($p['nama_fakultas']) ?> — <?= Security::e($p['nama_prodi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Peringkat / Status</label>
                <input type="text" name="peringkat" class="form-control" value="<?= Security::e($editItem['peringkat'] ?? '') ?>" placeholder="Accredited, Certified, Gold, dll">
            </div>

            <div class="form-group">
                <label class="form-label">Nomor Sertifikat</label>
                <input type="text" name="nomor_sertifikat" class="form-control" value="<?= Security::e($editItem['nomor_sertifikat'] ?? '') ?>">
            </div>

            <!-- Grid tanggal: tidak luber -->
            <div class="int-2col">
                <div class="form-group">
                    <label class="form-label">Tanggal Sertifikat</label>
                    <input type="date" name="tanggal_sertifikat" class="form-control" value="<?= $editItem['tanggal_sertifikat'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Masa Berlaku</label>
                    <input type="date" name="masa_berlaku" class="form-control" value="<?= $editItem['masa_berlaku'] ?? '' ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Upload Sertifikat (PDF)</label>
                <input type="file" name="sertifikat" accept="application/pdf" class="form-control">
                <?php if ($editItem && $editItem['file_path']): ?>
                    <small class="int-file-note">✓ Ada file: <?= Security::e($editItem['file_path']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">URL Logo Lembaga (opsional)</label>
                <input type="url" name="logo_url" class="form-control" value="<?= Security::e($editItem['logo_url'] ?? '') ?>" placeholder="https://...">
            </div>

            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)"><?= Security::e($editItem['catatan'] ?? '') ?></textarea>
            </div>

            <button class="btn btn-gold" type="submit">
                <?= $editItem ? '💾 Perbarui Data' : '➕ Tambah Akreditasi' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="/sim/admin/akreditasi-intl.php" class="btn btn-ghost" style="margin-top:8px;">Batal Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ===== LIST ===== -->
    <div class="int-table-wrap">
        <?php if (empty($list)): ?>
            <div class="int-empty">
                <div class="int-empty-ic">🌍</div>
                <h3>Belum ada akreditasi internasional</h3>
                <p>Tambahkan data akreditasi internasional prodi atau institusi.</p>
            </div>
        <?php else: ?>
            <table class="int-table">
                <thead>
                    <tr>
                        <th>Lembaga</th>
                        <th>Tingkat</th>
                        <th>Program / Unit</th>
                        <th>Peringkat</th>
                        <th>Masa Berlaku</th>
                        <th style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $i):
                        $days = $i['masa_berlaku'] ? (int)((strtotime($i['masa_berlaku']) - time()) / 86400) : null;
                        $lembagaMeta = null;
                        foreach ($lembagaList as $l) {
                            if ($l['kode'] === $i['lembaga']) { $lembagaMeta = $l; break; }
                        }
                    ?>
                    <tr>
                        <td>
                            <span class="int-lembaga-pill">
                                <span class="dot" style="background:<?= $lembagaMeta['warna'] ?? '#0F3D5C' ?>;"></span>
                                <?= Security::e($i['lembaga']) ?>
                            </span>
                            <small style="display:block;color:var(--text-muted);font-size:11px;margin-top:3px;">
                                <?= Security::e($i['negara_lembaga'] ?: '—') ?>
                            </small>
                        </td>
                        <td>
                            <span class="int-badge <?= $i['tingkat'] === 'Prodi' ? 'int-prodi-badge' : 'int-inst-badge' ?>">
                                <?= $i['tingkat'] ?>
                            </span>
                        </td>
                        <td>
                            <strong style="color:var(--text-dark);"><?= Security::e($i['nama_prodi'] ?? 'Institusi') ?></strong>
                            <?php if ($i['nama_fakultas']): ?>
                                <small style="display:block;color:var(--text-muted);font-size:11px;"><?= Security::e($i['nama_fakultas']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-dark);"><?= Security::e($i['peringkat'] ?: '—') ?></td>
                        <td>
                            <?php if (!$i['masa_berlaku']): ?>
                                <span class="int-chip-warn">— TBA</span>
                            <?php elseif ($days < 0): ?>
                                <span class="int-chip-exp">⚠️ Expired</span>
                            <?php elseif ($days < 180): ?>
                                <span class="int-chip-warn">⏰ <?= $days ?> hari</span>
                            <?php else: ?>
                                <span class="int-chip-ok">✓ <?= $days ?> hari</span>
                            <?php endif; ?>
                            <small style="display:block;color:var(--text-muted);font-size:11px;margin-top:2px;"><?= $i['masa_berlaku'] ? date('d M Y', strtotime($i['masa_berlaku'])) : '—' ?></small>
                        </td>
                        <td>
                            <div class="int-actions">
                                <a href="?edit=<?= $i['id_intl'] ?>" class="int-btn-sm int-btn-edit" title="Edit">✏️</a>
                                <form method="POST" onsubmit="return confirm('Hapus data akreditasi ini?');">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_intl" value="<?= $i['id_intl'] ?>">
                                    <button type="submit" class="int-btn-sm int-btn-del" title="Hapus">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    // Custom lembaga toggle
    var lembaga = document.querySelector('[name="lembaga"]');
    var customWrap = document.getElementById('customLembaga');
    var negara = document.querySelector('[name="negara_lembaga"]');
    lembaga.addEventListener('change', function () {
        if (this.value === '__custom') {
            customWrap.style.display = 'block';
            negara.value = '';
        } else {
            customWrap.style.display = 'none';
            var opt = this.options[this.selectedIndex];
            if (opt.dataset.negara) negara.value = opt.dataset.negara;
        }
    });
    if (lembaga.value === '__custom') customWrap.style.display = 'block';

    // Tingkat toggle (Prodi vs Institusi)
    var tingkat = document.getElementById('intTingkat');
    var prodiWrap = document.getElementById('prodiWrap');
    tingkat.addEventListener('change', function () {
        prodiWrap.style.display = this.value === 'Prodi' ? 'block' : 'none';
    });
    if (tingkat.value === 'Institusi') prodiWrap.style.display = 'none';

    // Validasi form: custom lembaga wajib diisi
    var form = document.getElementById('intForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (lembaga.value === '__custom' && !customWrap.querySelector('input').value.trim()) {
                e.preventDefault();
                alert('Nama lembaga custom harus diisi.');
                customWrap.querySelector('input').focus();
            }
        });
    }
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>