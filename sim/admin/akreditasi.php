<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

/* ===== KONSTANTA & HELPER ===== */
define('LEMBAGA_NASIONAL', ['BAN-PT', 'LAMDIK', 'LAMSAMA', 'Internasional']);
define('PERINGKAT_NASIONAL', ['Unggul', 'Baik Sekali', 'Baik', 'Cukup']);
define('TINGKAT_AKRED', ['Prodi', 'Institusi']);

function safeUnlinkSertifikat(?string $rel): void {
    if (!$rel) return;
    $root = realpath(PATH_UPLOAD);
    $f = realpath(PATH_UPLOAD . $rel);
    if ($root && $f && str_starts_with($f, $root . DIRECTORY_SEPARATOR) && is_file($f)) @unlink($f);
}

function handleUploadSertifikat(array $file, string $folder): array {
    global $error;
    if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'message' => ''];
    }
    // Validasi MIME type yang sebenarnya (bukan hanya ekstensi)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMime = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMime, true)) {
        return ['success' => false, 'path' => null, 'message' => '❌ Format file tidak didukung. Gunakan PDF/JPG/PNG/WEBP.'];
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'path' => null, 'message' => '❌ Ukuran file melebihi 10 MB.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $dir = PATH_UPLOAD . $folder . '/';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        return ['success' => false, 'path' => null, 'message' => '❌ Gagal menyimpan file.'];
    }
    return ['success' => true, 'path' => $folder . '/' . $name, 'message' => ''];
}

function badgeMasaBerlaku(?string $tgl): string {
    if (!$tgl) return '<span class="badge" style="background:#F1F5F9;color:#64748B;">—</span>';
    $days = (int)floor((strtotime($tgl) - time()) / 86400);
    if ($days < 0) return '<span class="badge" style="background:#FEE2E2;color:#991B1B;">⏰ Expired ' . abs($days) . ' hari</span>';
    if ($days <= 180) return '<span class="badge" style="background:#FEF3C7;color:#92400E;">️ H-' . $days . ' hari</span>';
    return '<span class="badge" style="background:#D1FAE5;color:#065F46;">✅ ' . $days . ' hari lagi</span>';
}

/* ===== PROSES POST ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    $tab = $_POST['tab'] ?? 'nasional';

    // ================= TAMBAH AKREDITASI NASIONAL =================
    if ($action === 'add_nasional') {
        $idProdi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;
        $tingkat = $_POST['tingkat'] ?? 'Prodi';
        $peringkat = $_POST['peringkat'] ?? '';
        $lembaga = $_POST['lembaga'] ?? '';
        $noSk = mb_substr(trim($_POST['no_sk'] ?? ''), 0, 100);
        $tglSk = trim($_POST['tanggal_sk'] ?? '') ?: null;
        $masaBerlaku = trim($_POST['masa_berlaku'] ?? '') ?: null;

        if (!$peringkat || !$lembaga) {
            $error = '❌ Peringkat dan Lembaga wajib diisi.';
        } elseif (!in_array($tingkat, TINGKAT_AKRED, true) || !in_array($lembaga, LEMBAGA_NASIONAL, true)) {
            $error = '❌ Nilai tidak valid.';
        } else {
            $up = handleUploadSertifikat($_FILES['file_sertifikat'] ?? [], 'akreditasi');
            if (!$up['success'] && $up['message']) { $error = $up['message']; }
            else {
                try {
                    $db->prepare("INSERT INTO akreditasi (id_prodi, tingkat, peringkat, lembaga, no_sk, tanggal_sk, masa_berlaku, file_sertifikat) VALUES (?,?,?,?,?,?,?,?)")
                       ->execute([$idProdi, $tingkat, $peringkat, $lembaga, $noSk, $tglSk, $masaBerlaku, $up['path']]);
                    Logger::log('CREATE', 'Menambah akreditasi nasional: ' . $peringkat . ' - ' . $lembaga);
                    $pesan = '✅ Akreditasi nasional berhasil ditambahkan.';
                } catch (PDOException $e) { $error = '❌ Gagal menyimpan: ' . $e->getMessage(); }
            }
        }
    }

    // ================= EDIT AKREDITASI NASIONAL =================
    if ($action === 'edit_nasional') {
        $id = (int)($_POST['id_akreditasi'] ?? 0);
        $idProdi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;
        $tingkat = $_POST['tingkat'] ?? 'Prodi';
        $peringkat = $_POST['peringkat'] ?? '';
        $lembaga = $_POST['lembaga'] ?? '';
        $noSk = mb_substr(trim($_POST['no_sk'] ?? ''), 0, 100);
        $tglSk = trim($_POST['tanggal_sk'] ?? '') ?: null;
        $masaBerlaku = trim($_POST['masa_berlaku'] ?? '') ?: null;

        if ($id <= 0 || !$peringkat || !$lembaga) { $error = ' Data tidak lengkap.'; }
        else {
            try {
                $st = $db->prepare("SELECT file_sertifikat FROM akreditasi WHERE id_akreditasi = ?");
                $st->execute([$id]);
                $oldFile = $st->fetchColumn();
                
                $up = handleUploadSertifikat($_FILES['file_sertifikat'] ?? [], 'akreditasi');
                if (!$up['success'] && $up['message']) { $error = $up['message']; }
                else {
                    $newFile = $up['path'] ?: $oldFile;
                    if ($up['path'] && $oldFile) safeUnlinkSertifikat($oldFile);
                    
                    $db->prepare("UPDATE akreditasi SET id_prodi=?, tingkat=?, peringkat=?, lembaga=?, no_sk=?, tanggal_sk=?, masa_berlaku=?, file_sertifikat=? WHERE id_akreditasi=?")
                       ->execute([$idProdi, $tingkat, $peringkat, $lembaga, $noSk, $tglSk, $masaBerlaku, $newFile, $id]);
                    Logger::log('UPDATE', 'Memperbarui akreditasi nasional #' . $id);
                    $pesan = '✅ Akreditasi nasional berhasil diperbarui.';
                }
            } catch (PDOException $e) { $error = '❌ Gagal memperbarui: ' . $e->getMessage(); }
        }
    }

    // ================= HAPUS AKREDITASI NASIONAL =================
    if ($action === 'delete_nasional') {
        $id = (int)($_POST['id_akreditasi'] ?? 0);
        try {
            $st = $db->prepare("SELECT file_sertifikat, peringkat, lembaga FROM akreditasi WHERE id_akreditasi = ?");
            $st->execute([$id]);
            $row = $st->fetch();
            if ($row) {
                safeUnlinkSertifikat($row['file_sertifikat']);
                $db->prepare("DELETE FROM akreditasi WHERE id_akreditasi = ?")->execute([$id]);
                Logger::log('DELETE', 'Menghapus akreditasi: ' . $row['peringkat'] . ' - ' . $row['lembaga']);
                $pesan = '✅ Akreditasi dihapus.';
            }
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }

    // ================= TAMBAH AKREDITASI INTERNASIONAL =================
    if ($action === 'add_intl') {
        $idProdi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;
        $lembaga = mb_substr(trim($_POST['lembaga_intl'] ?? ''), 0, 50);
        $tingkat = $_POST['tingkat_intl'] ?? 'Prodi';
        $peringkat = mb_substr(trim($_POST['peringkat_intl'] ?? ''), 0, 50);
        $tglSert = trim($_POST['tanggal_sertifikat'] ?? '') ?: null;
        $masaBerlaku = trim($_POST['masa_berlaku_intl'] ?? '') ?: null;
        $noSert = mb_substr(trim($_POST['nomor_sertifikat'] ?? ''), 0, 100);
        $negara = mb_substr(trim($_POST['negara_lembaga'] ?? ''), 0, 50);
        $catatan = mb_substr(trim($_POST['catatan_intl'] ?? ''), 0, 500);

        if (!$lembaga || !$peringkat) { $error = '❌ Lembaga dan Peringkat wajib diisi.'; }
        else {
            $up = handleUploadSertifikat($_FILES['file_path_intl'] ?? [], 'akreditasi-intl');
            if (!$up['success'] && $up['message']) { $error = $up['message']; }
            else {
                try {
                    $db->prepare("INSERT INTO akreditasi_internasional (id_prodi, lembaga, tingkat, peringkat, tanggal_sertifikat, masa_berlaku, nomor_sertifikat, file_path, negara_lembaga, catatan) VALUES (?,?,?,?,?,?,?,?,?,?)")
                       ->execute([$idProdi, $lembaga, $tingkat, $peringkat, $tglSert, $masaBerlaku, $noSert, $up['path'], $negara, $catatan]);
                    Logger::log('CREATE', 'Menambah akreditasi internasional: ' . $lembaga);
                    $pesan = '✅ Akreditasi internasional berhasil ditambahkan.';
                } catch (PDOException $e) { $error = '❌ Gagal menyimpan: ' . $e->getMessage(); }
            }
        }
    }

    // ================= HAPUS AKREDITASI INTERNASIONAL =================
    if ($action === 'delete_intl') {
        $id = (int)($_POST['id_intl'] ?? 0);
        try {
            $st = $db->prepare("SELECT file_path, lembaga FROM akreditasi_internasional WHERE id_intl = ?");
            $st->execute([$id]);
            $row = $st->fetch();
            if ($row) {
                safeUnlinkSertifikat($row['file_path']);
                $db->prepare("DELETE FROM akreditasi_internasional WHERE id_intl = ?")->execute([$id]);
                Logger::log('DELETE', 'Menghapus akreditasi internasional: ' . $row['lembaga']);
                $pesan = '✅ Akreditasi internasional dihapus.';
            }
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }
}

/* ===== MUAT DATA ===== */
try {
    $prodiList = $db->query("SELECT * FROM prodi ORDER BY nama_prodi")->fetchAll();
    $lembagaIntlList = $db->query("SELECT * FROM lembaga_internasional ORDER BY urutan")->fetchAll();
    
    // Akreditasi Nasional dengan join prodi
    $akredNasional = $db->query("SELECT a.*, p.nama_prodi FROM akreditasi a LEFT JOIN prodi p ON a.id_prodi = p.id_prodi ORDER BY a.tanggal_sk DESC")->fetchAll();
    
    // Akreditasi Internasional dengan join prodi
    $akredIntl = $db->query("SELECT ai.*, p.nama_prodi FROM akreditasi_internasional ai LEFT JOIN prodi p ON ai.id_prodi = p.id_prodi ORDER BY ai.tanggal_sertifikat DESC")->fetchAll();
    
    // Statistik
    $now = date('Y-m-d');
    $statNasional = [
        'total' => count($akredNasional),
        'aktif' => 0, 'akan_expired' => 0, 'expired' => 0
    ];
    foreach ($akredNasional as $a) {
        if (!$a['masa_berlaku']) { $statNasional['aktif']++; continue; }
        $days = (int)floor((strtotime($a['masa_berlaku']) - time()) / 86400);
        if ($days < 0) $statNasional['expired']++;
        elseif ($days <= 180) $statNasional['akan_expired']++;
        else $statNasional['aktif']++;
    }
    
    $statIntl = ['total' => count($akredIntl), 'aktif' => 0, 'akan_expired' => 0, 'expired' => 0];
    foreach ($akredIntl as $a) {
        if (!$a['masa_berlaku']) { $statIntl['aktif']++; continue; }
        $days = (int)floor((strtotime($a['masa_berlaku']) - time()) / 86400);
        if ($days < 0) $statIntl['expired']++;
        elseif ($days <= 180) $statIntl['akan_expired']++;
        else $statIntl['aktif']++;
    }
} catch (PDOException $e) {
    $error = '❌ Gagal memuat data: ' . $e->getMessage();
    $prodiList = []; $lembagaIntlList = []; $akredNasional = []; $akredIntl = [];
}

// Mode edit
$editNasional = null; $editIntl = null;
if (!empty($_GET['edit_nasional'])) {
    $st = $db->prepare("SELECT * FROM akreditasi WHERE id_akreditasi = ?");
    $st->execute([(int)$_GET['edit_nasional']]);
    $editNasional = $st->fetch();
}
if (!empty($_GET['edit_intl'])) {
    $st = $db->prepare("SELECT * FROM akreditasi_internasional WHERE id_intl = ?");
    $st->execute([(int)$_GET['edit_intl']]);
    $editIntl = $st->fetch();
}

$simTitle = 'Manajemen Akreditasi';
$activeMenu = 'akreditasi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= $pesan ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<!-- Statistik Ringkas -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">🏆</div><div><h3 style="font-size:26px;"><?= $statNasional['total'] ?></h3><p class="text-muted">Akreditasi Nasional</p></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><h3 style="font-size:26px;"><?= $statNasional['aktif'] ?></h3><p class="text-muted">Status Aktif</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">️</div><div><h3 style="font-size:26px;"><?= $statNasional['akan_expired'] ?></h3><p class="text-muted">Akan Expired</p></div></div>
    <div class="stat-card"><div class="stat-icon red">🌍</div><div><h3 style="font-size:26px;"><?= $statIntl['total'] ?></h3><p class="text-muted">Akreditasi Internasional</p></div></div>
</div>

<!-- Tab Navigation -->
<div class="card" style="margin-bottom:24px;padding:0;overflow:hidden;">
    <div style="display:flex;border-bottom:2px solid var(--border);background:var(--bg-light);">
        <button class="tab-btn active" data-tab="nasional" style="flex:1;padding:16px;border:none;background:transparent;cursor:pointer;font-weight:700;font-size:14px;color:var(--primary-dark);border-bottom:3px solid var(--accent);">🏆 Akreditasi Nasional</button>
        <button class="tab-btn" data-tab="internasional" style="flex:1;padding:16px;border:none;background:transparent;cursor:pointer;font-weight:700;font-size:14px;color:var(--text-muted);">🌍 Akreditasi Internasional</button>
    </div>

    <!-- TAB NASIONAL -->
    <div class="tab-content" id="tab-nasional" style="padding:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
            <h3 style="margin:0;">📋 Daftar Akreditasi Nasional</h3>
            <button class="btn btn-primary" onclick="openModal('modalAddNasional')">➕ Tambah Akreditasi</button>
        </div>

        <?php if (empty($akredNasional)): ?>
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
                <div style="font-size:48px;margin-bottom:12px;">🏆</div>
                <p>Belum ada data akreditasi nasional.</p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Prodi / Institusi</th>
                        <th>Peringkat</th>
                        <th>Lembaga</th>
                        <th>No. SK</th>
                        <th>Masa Berlaku</th>
                        <th>Sertifikat</th>
                        <th style="width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($akredNasional as $a): ?>
                    <tr>
                        <td>
                            <strong><?= Security::e($a['nama_prodi'] ?? ($a['tingkat'] === 'Institusi' ? 'Tingkat Institusi' : '—')) ?></strong><br>
                            <small class="text-muted"><?= Security::e($a['tingkat']) ?></small>
                        </td>
                        <td>
                            <span class="badge" style="background:<?= $a['peringkat'] === 'Unggul' ? '#D1FAE5;color:#065F46;' : ($a['peringkat'] === 'Baik Sekali' ? '#DBEAFE;color:#1E40AF;' : '#FEF3C7;color:#92400E;'); ?>font-weight:700;">
                                <?= Security::e($a['peringkat']) ?>
                            </span>
                        </td>
                        <td><span class="badge badge-a"><?= Security::e($a['lembaga']) ?></span></td>
                        <td><small><?= Security::e($a['no_sk'] ?? '—') ?></small></td>
                        <td>
                            <?= $a['masa_berlaku'] ? date('d M Y', strtotime($a['masa_berlaku'])) : '—' ?><br>
                            <?= badgeMasaBerlaku($a['masa_berlaku']) ?>
                        </td>
                        <td>
                            <?php if (!empty($a['file_sertifikat']) && file_exists(PATH_UPLOAD . $a['file_sertifikat'])): ?>
                                <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;" onclick="viewSertifikat('/uploads/<?= Security::e($a['file_sertifikat']) ?>', '<?= Security::e($a['peringkat']) ?> - <?= Security::e($a['lembaga']) ?>')">👁️ Lihat</button>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:11px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="?edit_nasional=<?= $a['id_akreditasi'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:11px;">✏️</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus akreditasi ini?');">
                                    <input type="hidden" name="tab" value="nasional">
                                    <input type="hidden" name="id_akreditasi" value="<?= $a['id_akreditasi'] ?>">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="delete_nasional">
                                    <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- TAB INTERNASIONAL -->
    <div class="tab-content" id="tab-internasional" style="padding:24px;display:none;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
            <h3 style="margin:0;"> Daftar Akreditasi Internasional</h3>
            <button class="btn btn-primary" onclick="openModal('modalAddIntl')">➕ Tambah Akreditasi</button>
        </div>

        <?php if (empty($akredIntl)): ?>
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
                <div style="font-size:48px;margin-bottom:12px;">🌍</div>
                <p>Belum ada data akreditasi internasional.</p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Prodi / Institusi</th>
                        <th>Lembaga</th>
                        <th>Negara</th>
                        <th>Peringkat</th>
                        <th>No. Sertifikat</th>
                        <th>Masa Berlaku</th>
                        <th style="width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($akredIntl as $a): ?>
                    <tr>
                        <td>
                            <strong><?= Security::e($a['nama_prodi'] ?? ($a['tingkat'] === 'Institusi' ? 'Tingkat Institusi' : '—')) ?></strong><br>
                            <small class="text-muted"><?= Security::e($a['tingkat']) ?></small>
                        </td>
                        <td><span class="badge badge-a"><?= Security::e($a['lembaga']) ?></span></td>
                        <td><small><?= Security::e($a['negara_lembaga'] ?? '—') ?></small></td>
                        <td><strong style="color:var(--accent);"><?= Security::e($a['peringkat']) ?></strong></td>
                        <td><small><?= Security::e($a['nomor_sertifikat'] ?? '—') ?></small></td>
                        <td>
                            <?= $a['masa_berlaku'] ? date('d M Y', strtotime($a['masa_berlaku'])) : '—' ?><br>
                            <?= badgeMasaBerlaku($a['masa_berlaku']) ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <?php if (!empty($a['file_path']) && file_exists(PATH_UPLOAD . $a['file_path'])): ?>
                                    <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;" onclick="viewSertifikat('/uploads/<?= Security::e($a['file_path']) ?>', '<?= Security::e($a['lembaga']) ?>')">👁️</button>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus akreditasi ini?');">
                                    <input type="hidden" name="tab" value="internasional">
                                    <input type="hidden" name="id_intl" value="<?= $a['id_intl'] ?>">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="delete_intl">
                                    <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== MODAL TAMBAH AKREDITASI NASIONAL ===== -->
<div id="modalAddNasional" class="modal-overlay">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3>➕ Tambah Akreditasi Nasional</h3>
            <button onclick="closeModal('modalAddNasional')" class="modal-close">×</button>
        </div>
        <form method="POST" enctype="multipart/form-data" style="padding:24px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="tab" value="nasional">
            <input type="hidden" name="action" value="add_nasional">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Tingkat *</label>
                    <select name="tingkat" class="form-control" required><?php foreach (TINGKAT_AKRED as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Program Studi</label>
                    <select name="id_prodi" class="form-control"><option value="">— Institusi / Umum —</option><?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Lembaga *</label>
                    <select name="lembaga" class="form-control" required><?php foreach (LEMBAGA_NASIONAL as $l): ?><option><?= $l ?></option><?php endforeach; ?></select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Peringkat *</label>
                    <select name="peringkat" class="form-control" required><?php foreach (PERINGKAT_NASIONAL as $p): ?><option><?= $p ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Nomor SK</label><input type="text" name="no_sk" class="form-control" maxlength="100"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Tanggal SK</label><input type="date" name="tanggal_sk" class="form-control"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Masa Berlaku Hingga</label><input type="date" name="masa_berlaku" class="form-control"></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">File Sertifikat (PDF/Gambar, maks 10MB)</label><input type="file" name="file_sertifikat" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAddNasional')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL TAMBAH AKREDITASI INTERNASIONAL ===== -->
<div id="modalAddIntl" class="modal-overlay">
    <div class="modal-content" style="max-width:650px;">
        <div class="modal-header">
            <h3>🌍 Tambah Akreditasi Internasional</h3>
            <button onclick="closeModal('modalAddIntl')" class="modal-close">×</button>
        </div>
        <form method="POST" enctype="multipart/form-data" style="padding:24px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="tab" value="internasional">
            <input type="hidden" name="action" value="add_intl">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Tingkat *</label>
                    <select name="tingkat_intl" class="form-control" required><?php foreach (TINGKAT_AKRED as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Program Studi</label>
                    <select name="id_prodi" class="form-control"><option value="">— Institusi / Umum —</option><?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Lembaga Akreditasi *</label>
                    <select name="lembaga_intl" class="form-control" required>
                        <option value="">— Pilih Lembaga —</option>
                        <?php foreach ($lembagaIntlList as $l): ?><option value="<?= Security::e($l['kode']) ?>"><?= Security::e($l['nama']) ?> (<?= Security::e($l['negara']) ?>)</option><?php endforeach; ?>
                        <option value="Lainnya">Lainnya (manual)</option>
                    </select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Negara</label><input type="text" name="negara_lembaga" class="form-control" maxlength="50"></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Peringkat / Status *</label><input type="text" name="peringkat_intl" class="form-control" required placeholder="cth: Accredited, Gold, Certified"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Tanggal Sertifikat</label><input type="date" name="tanggal_sertifikat" class="form-control"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Masa Berlaku Hingga</label><input type="date" name="masa_berlaku_intl" class="form-control"></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Nomor Sertifikat</label><input type="text" name="nomor_sertifikat" class="form-control" maxlength="100"></div>
            <div class="form-group" style="margin:0;"><label class="form-label">File Sertifikat (PDF/Gambar, maks 10MB)</label><input type="file" name="file_path_intl" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Catatan</label><textarea name="catatan_intl" class="form-control" rows="2" maxlength="500"></textarea></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAddIntl')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL EDIT AKREDITASI NASIONAL ===== -->
<?php if ($editNasional): ?>
<div id="modalEditNasional" class="modal-overlay" style="display:flex;">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3>✏️ Edit Akreditasi Nasional</h3>
            <a href="/sim/admin/akreditasi.php" class="modal-close">×</a>
        </div>
        <form method="POST" enctype="multipart/form-data" style="padding:24px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="tab" value="nasional">
            <input type="hidden" name="action" value="edit_nasional">
            <input type="hidden" name="id_akreditasi" value="<?= $editNasional['id_akreditasi'] ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Tingkat *</label>
                    <select name="tingkat" class="form-control" required><?php foreach (TINGKAT_AKRED as $t): ?><option <?= $editNasional['tingkat']===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?></select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Program Studi</label>
                    <select name="id_prodi" class="form-control"><option value="">— Institusi / Umum —</option><?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>" <?= $editNasional['id_prodi']==$p['id_prodi']?'selected':'' ?>><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Lembaga *</label>
                    <select name="lembaga" class="form-control" required><?php foreach (LEMBAGA_NASIONAL as $l): ?><option <?= $editNasional['lembaga']===$l?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Peringkat *</label>
                    <select name="peringkat" class="form-control" required><?php foreach (PERINGKAT_NASIONAL as $p): ?><option <?= $editNasional['peringkat']===$p?'selected':'' ?>><?= $p ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Nomor SK</label><input type="text" name="no_sk" class="form-control" maxlength="100" value="<?= Security::e($editNasional['no_sk']) ?>"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Tanggal SK</label><input type="date" name="tanggal_sk" class="form-control" value="<?= Security::e($editNasional['tanggal_sk']) ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Masa Berlaku Hingga</label><input type="date" name="masa_berlaku" class="form-control" value="<?= Security::e($editNasional['masa_berlaku']) ?>"></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Ganti File Sertifikat (kosongkan jika tetap)</label><input type="file" name="file_sertifikat" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="/sim/admin/akreditasi.php" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">💾 Perbarui</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ===== MODAL PREVIEW SERTIFIKAT ===== -->
<div id="modalPreview" class="modal-overlay">
    <div class="modal-content" style="max-width:900px;max-height:90vh;">
        <div class="modal-header">
            <h3 id="previewTitle">Preview Sertifikat</h3>
            <button onclick="closeModal('modalPreview')" class="modal-close">×</button>
        </div>
        <div style="padding:24px;text-align:center;">
            <iframe id="previewFrame" src="" style="width:100%;height:70vh;border:1px solid var(--border);border-radius:12px;"></iframe>
            <div style="margin-top:16px;">
                <a id="previewDownload" href="" download class="btn btn-primary"> Download Sertifikat</a>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(4,10,20,.7); backdrop-filter:blur(6px); z-index:5000; align-items:center; justify-content:center; padding:20px; }
.modal-content { background:var(--bg-card); border-radius:20px; box-shadow:0 25px 60px rgba(0,0,0,.3); border:1px solid var(--border); max-height:90vh; overflow-y:auto; }
.modal-header { display:flex; justify-content:space-between; align-items:center; padding:20px 24px; border-bottom:1px solid var(--border); position:sticky; top:0; background:var(--bg-card); border-radius:20px 20px 0 0; }
.modal-header h3 { margin:0; font-size:18px; color:var(--primary-dark); }
.modal-close { background:none; border:none; font-size:28px; cursor:pointer; color:var(--text-muted); line-height:1; }
.modal-close:hover { color:var(--danger); }
.tab-btn { transition:all .3s; }
.tab-btn:hover { color:var(--primary-dark) !important; }
</style>

<script>
// Tab switching
document.querySelectorAll('.tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(function(b) { 
            b.classList.remove('active'); 
            b.style.borderBottomColor = 'transparent';
            b.style.color = 'var(--text-muted)';
        });
        btn.classList.add('active');
        btn.style.borderBottomColor = 'var(--accent)';
        btn.style.color = 'var(--primary-dark)';
        document.querySelectorAll('.tab-content').forEach(function(c) { c.style.display = 'none'; });
        document.getElementById('tab-' + btn.dataset.tab).style.display = 'block';
    });
});

// Modal functions
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Preview sertifikat
function viewSertifikat(url, title) {
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewFrame').src = url;
    document.getElementById('previewDownload').href = url;
    openModal('modalPreview');
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.style.display = 'none';
    });
});

// Auto-open edit modal if exists
<?php if ($editNasional): ?>openModal('modalEditNasional');<?php endif; ?>
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>