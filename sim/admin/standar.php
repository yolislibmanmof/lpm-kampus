<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

define('STATUS_INSTRUMEN', ['Draft', 'Aktif', 'Arsip']);
define('KATEGORI_STANDAR', ['Pendidikan', 'Penelitian', 'Pengabdian', 'Tata Pamong', 'Kemahasiswaan', 'SDM', 'Keuangan', 'Sarana', 'Sistem Informasi', 'Lainnya']);

/* ===== PROSES POST ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= TAMBAH STANDAR =================
    if ($action === 'add_standar') {
        $kode = mb_substr(trim($_POST['kode_standar'] ?? ''), 0, 30);
        $nama = mb_substr(trim($_POST['nama_standar'] ?? ''), 0, 255);
        $kategori = $_POST['kategori_standar'] ?? 'Lainnya';
        
        if (!$kode || !$nama) { $error = '❌ Kode dan Nama Standar wajib diisi.'; }
        elseif (!in_array($kategori, KATEGORI_STANDAR, true)) { $error = '❌ Kategori tidak valid.'; }
        else {
            try {
                $db->prepare("INSERT INTO standar_mutu (kode_standar, nama_standar, kategori_standar) VALUES (?,?,?)")
                   ->execute([$kode, $nama, $kategori]);
                Logger::log('CREATE', 'Menambah standar mutu: ' . $kode . ' - ' . $nama);
                $pesan = '✅ Standar mutu berhasil ditambahkan.';
            } catch (PDOException $e) { $error = '❌ Gagal menyimpan: ' . $e->getMessage(); }
        }
    }

    // ================= EDIT STANDAR =================
    if ($action === 'edit_standar') {
        $id = (int)($_POST['id_standar'] ?? 0);
        $kode = mb_substr(trim($_POST['kode_standar'] ?? ''), 0, 30);
        $nama = mb_substr(trim($_POST['nama_standar'] ?? ''), 0, 255);
        $kategori = $_POST['kategori_standar'] ?? 'Lainnya';
        
        if ($id <= 0 || !$kode || !$nama) { $error = ' Data tidak lengkap.'; }
        else {
            try {
                $db->prepare("UPDATE standar_mutu SET kode_standar=?, nama_standar=?, kategori_standar=? WHERE id_standar=?")
                   ->execute([$kode, $nama, $kategori, $id]);
                Logger::log('UPDATE', 'Memperbarui standar #' . $id);
                $pesan = '✅ Standar mutu berhasil diperbarui.';
            } catch (PDOException $e) { $error = '❌ Gagal memperbarui: ' . $e->getMessage(); }
        }
    }

    // ================= HAPUS STANDAR =================
    if ($action === 'delete_standar') {
        $id = (int)($_POST['id_standar'] ?? 0);
        try {
            $st = $db->prepare("SELECT kode_standar FROM standar_mutu WHERE id_standar = ?");
            $st->execute([$id]);
            $kode = $st->fetchColumn() ?: '';
            $db->prepare("DELETE FROM standar_mutu WHERE id_standar = ?")->execute([$id]);
            Logger::log('DELETE', 'Menghapus standar: ' . $kode);
            $pesan = '✅ Standar mutu dihapus.';
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }

    // ================= TAMBAH SIKLUS INSTRUMEN =================
    if ($action === 'add_siklus') {
        $tahun = (int)($_POST['tahun'] ?? date('Y'));
        $nama = mb_substr(trim($_POST['nama_siklus'] ?? ''), 0, 100);
        $status = $_POST['status'] ?? 'Draft';
        
        if (!$nama) { $error = '❌ Nama siklus wajib diisi.'; }
        elseif (!in_array($status, STATUS_INSTRUMEN, true)) { $error = '❌ Status tidak valid.'; }
        else {
            try {
                $db->prepare("INSERT INTO instrumen_tahun (tahun, nama_siklus, status) VALUES (?,?,?)")
                   ->execute([$tahun, $nama, $status]);
                Logger::log('CREATE', 'Membuat siklus AMI: ' . $nama . ' (' . $tahun . ')');
                $pesan = '✅ Siklus AMI berhasil dibuat.';
            } catch (PDOException $e) { $error = '❌ Gagal menyimpan: ' . $e->getMessage(); }
        }
    }

    // ================= UPDATE STATUS SIKLUS =================
    if ($action === 'update_status_siklus') {
        $id = (int)($_POST['id_instrumen_tahun'] ?? 0);
        $status = $_POST['status'] ?? 'Draft';
        if (!in_array($status, STATUS_INSTRUMEN, true)) { $error = '❌ Status tidak valid.'; }
        else {
            try {
                $db->prepare("UPDATE instrumen_tahun SET status = ? WHERE id_instrumen_tahun = ?")->execute([$status, $id]);
                Logger::log('UPDATE', 'Mengubah status siklus #' . $id . ' menjadi ' . $status);
                $pesan = '✅ Status siklus diperbarui.';
            } catch (PDOException $e) { $error = '❌ Gagal memperbarui: ' . $e->getMessage(); }
        }
    }

    // ================= HAPUS SIKLUS =================
    if ($action === 'delete_siklus') {
        $id = (int)($_POST['id_instrumen_tahun'] ?? 0);
        try {
            $db->prepare("DELETE FROM instrumen_tahun WHERE id_instrumen_tahun = ?")->execute([$id]);
            Logger::log('DELETE', 'Menghapus siklus AMI #' . $id);
            $pesan = '✅ Siklus AMI dihapus beserta butirnya.';
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }

    // ================= TAMBAH BUTIR =================
    if ($action === 'add_butir') {
        $idSiklus = (int)($_POST['id_instrumen_tahun'] ?? 0);
        $idStandar = (int)($_POST['id_standar'] ?? 0);
        $butir = trim($_POST['butir'] ?? '');
        $metode = mb_substr(trim($_POST['metode'] ?? ''), 0, 100);
        $dokumen = mb_substr(trim($_POST['dokumen_diperlukan'] ?? ''), 0, 255);
        $urutan = (int)($_POST['urutan'] ?? 0);
        
        if ($idSiklus <= 0 || $idStandar <= 0 || !$butir) { $error = '❌ Siklus, Standar, dan Butir wajib diisi.'; }
        else {
            try {
                $db->prepare("INSERT INTO instrumen_butir (id_instrumen_tahun, id_standar, butir, metode, dokumen_diperlukan, urutan) VALUES (?,?,?,?,?,?)")
                   ->execute([$idSiklus, $idStandar, $butir, $metode, $dokumen, $urutan]);
                Logger::log('CREATE', 'Menambah butir instrumen untuk siklus #' . $idSiklus);
                $pesan = '✅ Butir instrumen berhasil ditambahkan.';
            } catch (PDOException $e) { $error = '❌ Gagal menyimpan: ' . $e->getMessage(); }
        }
    }

    // ================= EDIT BUTIR =================
    if ($action === 'edit_butir') {
        $id = (int)($_POST['id_butir'] ?? 0);
        $idStandar = (int)($_POST['id_standar'] ?? 0);
        $butir = trim($_POST['butir'] ?? '');
        $metode = mb_substr(trim($_POST['metode'] ?? ''), 0, 100);
        $dokumen = mb_substr(trim($_POST['dokumen_diperlukan'] ?? ''), 0, 255);
        $urutan = (int)($_POST['urutan'] ?? 0);
        
        if ($id <= 0 || $idStandar <= 0 || !$butir) { $error = '❌ Data tidak lengkap.'; }
        else {
            try {
                $db->prepare("UPDATE instrumen_butir SET id_standar=?, butir=?, metode=?, dokumen_diperlukan=?, urutan=? WHERE id_butir=?")
                   ->execute([$idStandar, $butir, $metode, $dokumen, $urutan, $id]);
                Logger::log('UPDATE', 'Memperbarui butir #' . $id);
                $pesan = '✅ Butir instrumen berhasil diperbarui.';
            } catch (PDOException $e) { $error = '❌ Gagal memperbarui: ' . $e->getMessage(); }
        }
    }

    // ================= HAPUS BUTIR =================
    if ($action === 'delete_butir') {
        $id = (int)($_POST['id_butir'] ?? 0);
        try {
            $db->prepare("DELETE FROM instrumen_butir WHERE id_butir = ?")->execute([$id]);
            Logger::log('DELETE', 'Menghapus butir #' . $id);
            $pesan = '✅ Butir instrumen dihapus.';
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }
}

/* ===== MUAT DATA ===== */
try {
    $standarList = $db->query("SELECT * FROM standar_mutu ORDER BY kode_standar")->fetchAll();
    $siklusList = $db->query("SELECT * FROM instrumen_tahun ORDER BY tahun DESC, id_instrumen_tahun DESC")->fetchAll();
    
    // Muat butir untuk setiap siklus
    $butirPerSiklus = [];
    foreach ($siklusList as $s) {
        $stmt = $db->prepare("SELECT b.*, s.kode_standar, s.nama_standar FROM instrumen_butir b LEFT JOIN standar_mutu s ON b.id_standar = s.id_standar WHERE b.id_instrumen_tahun = ? ORDER BY b.urutan, b.id_butir");
        $stmt->execute([$s['id_instrumen_tahun']]);
        $butirPerSiklus[$s['id_instrumen_tahun']] = $stmt->fetchAll();
    }
    
    $statStandar = count($standarList);
    $statSiklusAktif = count(array_filter($siklusList, fn($s) => $s['status'] === 'Aktif'));
    $statTotalButir = array_sum(array_map('count', $butirPerSiklus));
} catch (PDOException $e) {
    $error = '❌ Gagal memuat data: ' . $e->getMessage();
    $standarList = []; $siklusList = []; $butirPerSiklus = [];
}

// Mode edit
$editStandar = null; $editButir = null;
if (!empty($_GET['edit_standar'])) {
    $st = $db->prepare("SELECT * FROM standar_mutu WHERE id_standar = ?");
    $st->execute([(int)$_GET['edit_standar']]);
    $editStandar = $st->fetch();
}
if (!empty($_GET['edit_butir'])) {
    $st = $db->prepare("SELECT * FROM instrumen_butir WHERE id_butir = ?");
    $st->execute([(int)$_GET['edit_butir']]);
    $editButir = $st->fetch();
}

$simTitle = 'Standar Mutu & Instrumen AMI';
$activeMenu = 'standar';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= $pesan ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<!-- Statistik -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">📋</div><div><h3 style="font-size:26px;"><?= $statStandar ?></h3><p class="text-muted">Standar Mutu</p></div></div>
    <div class="stat-card"><div class="stat-icon green">🔄</div><div><h3 style="font-size:26px;"><?= $statSiklusAktif ?></h3><p class="text-muted">Siklus AMI Aktif</p></div></div>
    <div class="stat-card"><div class="stat-icon gold"></div><div><h3 style="font-size:26px;"><?= $statTotalButir ?></h3><p class="text-muted">Total Butir Instrumen</p></div></div>
</div>

<!-- SECTION 1: STANDAR MUTU -->
<div class="card" style="margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;">📋 Standar Mutu</h3>
        <button class="btn btn-primary" onclick="openModal('modalAddStandar')">➕ Tambah Standar</button>
    </div>

    <?php if (empty($standarList)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">
            <div style="font-size:48px;margin-bottom:12px;">📋</div>
            <p>Belum ada standar mutu. Tambahkan standar pertama Anda.</p>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Kode</th><th>Nama Standar</th><th>Kategori</th><th style="width:120px;">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($standarList as $s): ?>
                <tr>
                    <td><code style="font-size:12px;font-weight:700;background:var(--bg-light);padding:4px 8px;border-radius:6px;"><?= Security::e($s['kode_standar']) ?></code></td>
                    <td><strong><?= Security::e($s['nama_standar']) ?></strong></td>
                    <td><span class="badge badge-a"><?= Security::e($s['kategori_standar']) ?></span></td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="?edit_standar=<?= $s['id_standar'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:11px;">️</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus standar ini?');">
                                <input type="hidden" name="id_standar" value="<?= $s['id_standar'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete_standar">
                                <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">️</button>
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

<!-- SECTION 2: SIKLUS INSTRUMEN AMI -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;">🔄 Siklus Instrumen AMI</h3>
        <button class="btn btn-primary" onclick="openModal('modalAddSiklus')">➕ Buat Siklus Baru</button>
    </div>

    <?php if (empty($siklusList)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">
            <div style="font-size:48px;margin-bottom:12px;">🔄</div>
            <p>Belum ada siklus AMI. Buat siklus pertama untuk mulai menyusun instrumen audit.</p>
        </div>
    <?php else: ?>
        <?php foreach ($siklusList as $siklus): 
            $butirList = $butirPerSiklus[$siklus['id_instrumen_tahun']] ?? [];
            $statusColor = $siklus['status'] === 'Aktif' ? '#10B981' : ($siklus['status'] === 'Arsip' ? '#64748B' : '#F59E0B');
        ?>
        <div class="siklus-item" style="border:1px solid var(--border);border-radius:16px;margin-bottom:16px;overflow:hidden;">
            <div class="siklus-header" style="padding:18px 22px;background:var(--bg-light);cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:12px;" onclick="toggleSiklus(<?= $siklus['id_instrumen_tahun'] ?>)">
                <div style="display:flex;align-items:center;gap:14px;flex:1;">
                    <span style="font-size:20px;">📅</span>
                    <div>
                        <strong style="font-size:15px;color:var(--primary-dark);"><?= Security::e($siklus['nama_siklus']) ?></strong>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">Tahun <?= $siklus['tahun'] ?> • <?= count($butirList) ?> butir instrumen</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="badge" style="background:<?= $statusColor ?>20;color:<?= $statusColor ?>;font-weight:700;"><?= Security::e($siklus['status']) ?></span>
                    <span class="toggle-icon" id="icon-<?= $siklus['id_instrumen_tahun'] ?>" style="font-size:18px;transition:transform .3s;">▼</span>
                </div>
            </div>
            
            <div class="siklus-body" id="body-<?= $siklus['id_instrumen_tahun'] ?>" style="display:none;padding:22px;">
                <!-- Action Bar -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_instrumen_tahun" value="<?= $siklus['id_instrumen_tahun'] ?>">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="update_status_siklus">
                            <select name="status" class="form-control" style="width:auto;padding:6px 10px;font-size:12px;" onchange="this.form.submit()">
                                <?php foreach (STATUS_INSTRUMEN as $st): ?><option <?= $siklus['status']===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary" style="padding:6px 14px;font-size:12px;" onclick="openModal('modalAddButir-<?= $siklus['id_instrumen_tahun'] ?>')">➕ Tambah Butir</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus siklus ini beserta semua butirnya?');">
                            <input type="hidden" name="id_instrumen_tahun" value="<?= $siklus['id_instrumen_tahun'] ?>">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="delete_siklus">
                            <button class="btn btn-outline" style="padding:6px 14px;font-size:12px;color:var(--danger);">🗑️ Hapus Siklus</button>
                        </form>
                    </div>
                </div>

                <!-- Daftar Butir -->
                <?php if (empty($butirList)): ?>
                    <div style="text-align:center;padding:30px;color:var(--text-muted);background:var(--bg-light);border-radius:12px;">
                        <p>Belum ada butir instrumen. Klik "Tambah Butir" untuk memulai.</p>
                    </div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th style="width:50px;">#</th><th>Standar</th><th>Butir Pertanyaan</th><th>Metode</th><th style="width:120px;">Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($butirList as $idx => $b): ?>
                            <tr>
                                <td><strong style="color:var(--accent);"><?= $idx + 1 ?></strong></td>
                                <td><code style="font-size:11px;background:var(--bg-light);padding:3px 8px;border-radius:4px;"><?= Security::e($b['kode_standar'] ?? '—') ?></code><br><small class="text-muted"><?= Security::e(mb_strimwidth($b['nama_standar'] ?? '', 0, 30, '...')) ?></small></td>
                                <td style="font-size:13px;line-height:1.5;"><?= Security::e($b['butir']) ?><?php if ($b['dokumen_diperlukan']): ?><br><small class="text-muted">📎 <?= Security::e($b['dokumen_diperlukan']) ?></small><?php endif; ?></td>
                                <td><small><?= Security::e($b['metode'] ?? '—') ?></small></td>
                                <td>
                                    <div style="display:flex;gap:4px;">
                                        <a href="?edit_butir=<?= $b['id_butir'] ?>" class="btn btn-outline" style="padding:3px 8px;font-size:10px;">✏️</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus butir ini?');">
                                            <input type="hidden" name="id_butir" value="<?= $b['id_butir'] ?>">
                                            <?= Security::csrfField() ?>
                                            <input type="hidden" name="action" value="delete_butir">
                                            <button class="btn btn-outline" style="padding:3px 8px;font-size:10px;color:var(--danger);">🗑️</button>
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

        <!-- Modal Tambah Butir (per siklus) -->
        <div id="modalAddButir-<?= $siklus['id_instrumen_tahun'] ?>" class="modal-overlay">
            <div class="modal-content" style="max-width:700px;">
                <div class="modal-header">
                    <h3>➕ Tambah Butir Instrumen - <?= Security::e($siklus['nama_siklus']) ?></h3>
                    <button onclick="closeModal('modalAddButir-<?= $siklus['id_instrumen_tahun'] ?>')" class="modal-close">×</button>
                </div>
                <form method="POST" style="padding:24px;display:grid;gap:14px;">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="add_butir">
                    <input type="hidden" name="id_instrumen_tahun" value="<?= $siklus['id_instrumen_tahun'] ?>">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group" style="margin:0;"><label class="form-label">Standar Mutu *</label>
                            <select name="id_standar" class="form-control" required>
                                <option value="">— Pilih Standar —</option>
                                <?php foreach ($standarList as $s): ?><option value="<?= $s['id_standar'] ?>"><?= Security::e($s['kode_standar']) ?> - <?= Security::e($s['nama_standar']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="form-group" style="margin:0;"><label class="form-label">Urutan</label><input type="number" name="urutan" class="form-control" value="<?= count($butirList) + 1 ?>" min="0"></div>
                    </div>
                    <div class="form-group" style="margin:0;"><label class="form-label">Butir Pertanyaan *</label><textarea name="butir" class="form-control" rows="3" required></textarea></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group" style="margin:0;"><label class="form-label">Metode Verifikasi</label><input type="text" name="metode" class="form-control" maxlength="100" placeholder="cth: Telaah Dokumen, Wawancara"></div>
                        <div class="form-group" style="margin:0;"><label class="form-label">Dokumen Diperlukan</label><input type="text" name="dokumen_diperlukan" class="form-control" maxlength="255" placeholder="cth: SK, Laporan, Notulen"></div>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('modalAddButir-<?= $siklus['id_instrumen_tahun'] ?>')">Batal</button>
                        <button type="submit" class="btn btn-primary">💾 Simpan Butir</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ===== MODAL TAMBAH STANDAR ===== -->
<div id="modalAddStandar" class="modal-overlay">
    <div class="modal-content" style="max-width:550px;">
        <div class="modal-header">
            <h3>➕ Tambah Standar Mutu</h3>
            <button onclick="closeModal('modalAddStandar')" class="modal-close">×</button>
        </div>
        <form method="POST" style="padding:24px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="add_standar">
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Kode *</label><input type="text" name="kode_standar" class="form-control" required maxlength="30" placeholder="S1"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Kategori *</label>
                    <select name="kategori_standar" class="form-control" required><?php foreach (KATEGORI_STANDAR as $k): ?><option><?= $k ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Nama Standar *</label><input type="text" name="nama_standar" class="form-control" required maxlength="255" placeholder="cth: Standar Kompetensi Lulusan"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAddStandar')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL TAMBAH SIKLUS ===== -->
<div id="modalAddSiklus" class="modal-overlay">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h3>🔄 Buat Siklus AMI Baru</h3>
            <button onclick="closeModal('modalAddSiklus')" class="modal-close">×</button>
        </div>
        <form method="POST" style="padding:24px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="add_siklus">
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Tahun *</label><input type="number" name="tahun" class="form-control" required value="<?= date('Y') ?>" min="2000" max="<?= date('Y') + 5 ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Status *</label>
                    <select name="status" class="form-control" required><?php foreach (STATUS_INSTRUMEN as $s): ?><option><?= $s ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Nama Siklus *</label><input type="text" name="nama_siklus" class="form-control" required maxlength="100" placeholder="cth: AMI 2026 - Siklus PPEPP"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAddSiklus')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Buat Siklus</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL EDIT STANDAR ===== -->
<?php if ($editStandar): ?>
<div id="modalEditStandar" class="modal-overlay" style="display:flex;">
    <div class="modal-content" style="max-width:550px;">
        <div class="modal-header">
            <h3>✏️ Edit Standar Mutu</h3>
            <a href="/sim/admin/standar.php" class="modal-close">×</a>
        </div>
        <form method="POST" style="padding:24px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="edit_standar">
            <input type="hidden" name="id_standar" value="<?= $editStandar['id_standar'] ?>">
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Kode *</label><input type="text" name="kode_standar" class="form-control" required maxlength="30" value="<?= Security::e($editStandar['kode_standar']) ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Kategori *</label>
                    <select name="kategori_standar" class="form-control" required><?php foreach (KATEGORI_STANDAR as $k): ?><option <?= $editStandar['kategori_standar']===$k?'selected':'' ?>><?= $k ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Nama Standar *</label><input type="text" name="nama_standar" class="form-control" required maxlength="255" value="<?= Security::e($editStandar['nama_standar']) ?>"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="/sim/admin/standar.php" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">💾 Perbarui</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ===== MODAL EDIT BUTIR ===== -->
<?php if ($editButir): ?>
<div id="modalEditButir" class="modal-overlay" style="display:flex;">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h3>✏️ Edit Butir Instrumen</h3>
            <a href="/sim/admin/standar.php" class="modal-close">×</a>
        </div>
        <form method="POST" style="padding:24px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="edit_butir">
            <input type="hidden" name="id_butir" value="<?= $editButir['id_butir'] ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Standar Mutu *</label>
                    <select name="id_standar" class="form-control" required>
                        <?php foreach ($standarList as $s): ?><option value="<?= $s['id_standar'] ?>" <?= $editButir['id_standar']==$s['id_standar']?'selected':'' ?>><?= Security::e($s['kode_standar']) ?> - <?= Security::e($s['nama_standar']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Urutan</label><input type="number" name="urutan" class="form-control" value="<?= $editButir['urutan'] ?>" min="0"></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Butir Pertanyaan *</label><textarea name="butir" class="form-control" rows="3" required><?= Security::e($editButir['butir']) ?></textarea></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Metode Verifikasi</label><input type="text" name="metode" class="form-control" maxlength="100" value="<?= Security::e($editButir['metode']) ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Dokumen Diperlukan</label><input type="text" name="dokumen_diperlukan" class="form-control" maxlength="255" value="<?= Security::e($editButir['dokumen_diperlukan']) ?>"></div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="/sim/admin/standar.php" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">💾 Perbarui</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(4,10,20,.7); backdrop-filter:blur(6px); z-index:5000; align-items:center; justify-content:center; padding:20px; }
.modal-content { background:var(--bg-card); border-radius:20px; box-shadow:0 25px 60px rgba(0,0,0,.3); border:1px solid var(--border); max-height:90vh; overflow-y:auto; }
.modal-header { display:flex; justify-content:space-between; align-items:center; padding:20px 24px; border-bottom:1px solid var(--border); position:sticky; top:0; background:var(--bg-card); border-radius:20px 20px 0 0; }
.modal-header h3 { margin:0; font-size:18px; color:var(--primary-dark); }
.modal-close { background:none; border:none; font-size:28px; cursor:pointer; color:var(--text-muted); line-height:1; text-decoration:none; }
.modal-close:hover { color:var(--danger); }
.siklus-item { transition:box-shadow .3s; }
.siklus-item:hover { box-shadow:0 4px 12px rgba(15,61,92,.08); }
.siklus-header:hover { background:var(--border) !important; }
.toggle-icon.rotated { transform:rotate(180deg); }
</style>

<script>
// Modal functions
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Toggle siklus
function toggleSiklus(id) {
    var body = document.getElementById('body-' + id);
    var icon = document.getElementById('icon-' + id);
    if (body.style.display === 'none') {
        body.style.display = 'block';
        icon.classList.add('rotated');
    } else {
        body.style.display = 'none';
        icon.classList.remove('rotated');
    }
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.style.display = 'none';
    });
});

// Auto-open edit modals
<?php if ($editStandar): ?>openModal('modalEditStandar');<?php endif; ?>
<?php if ($editButir): ?>openModal('modalEditButir');<?php endif; ?>
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>