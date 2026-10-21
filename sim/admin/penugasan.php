<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

// Muat data master
try {
    $auditors = $db->query("SELECT u.id_user, u.nama_lengkap, p.nama_prodi as prodi_asal 
        FROM users u 
        LEFT JOIN prodi p ON u.id_prodi = p.id_prodi 
        WHERE u.id_role = 4 AND u.is_active = 1 
        ORDER BY u.nama_lengkap")->fetchAll();
    
    $prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
    
    $jadwalList = $db->query("SELECT id_jadwal, tahun_ami, tanggal_mulai, tanggal_selesai 
        FROM jadwal_ami 
        WHERE status IN ('Draf', 'Berjalan') 
        ORDER BY tahun_ami DESC")->fetchAll();
} catch (PDOException $e) {
    $error = '❌ Gagal memuat data master: ' . $e->getMessage();
    $auditors = [];
    $prodiList = [];
    $jadwalList = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= TAMBAH PENUGASAN =================
    if ($action === 'add') {
        $idJadwal = (int)($_POST['id_jadwal'] ?? 0);
        $idAuditor = (int)($_POST['id_auditor'] ?? 0);
        $idProdi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;
        $tanggal = trim($_POST['tanggal_audit'] ?? '');
        $status = $_POST['status'] ?? 'Ditugaskan';

        if ($idJadwal <= 0 || $idAuditor <= 0 || $tanggal === '') {
            $error = '❌ Jadwal, Auditor, dan Tanggal Audit wajib diisi.';
        } else {
            try {
                $db->prepare("INSERT INTO penugasan_audit (id_jadwal, id_auditor, id_prodi, tanggal_audit, status) VALUES (?,?,?,?,?)")
                   ->execute([$idJadwal, $idAuditor, $idProdi, $tanggal, $status]);
                
                // Ambil nama untuk log
                $stmt = $db->prepare("SELECT nama_lengkap FROM users WHERE id_user = ?");
                $stmt->execute([$idAuditor]);
                $namaAuditor = $stmt->fetchColumn() ?: 'Unknown';
                
                Logger::log('CREATE', 'Menugaskan auditor ' . $namaAuditor . ' untuk AMI');
                $pesan = '✅ Penugasan audit berhasil ditambahkan.';
            } catch (PDOException $e) {
                $error = '❌ Gagal menyimpan penugasan: ' . $e->getMessage();
            }
        }
    }

    // ================= EDIT PENUGASAN =================
    if ($action === 'edit') {
        $idTugas = (int)($_POST['id_tugas'] ?? 0);
        $idJadwal = (int)($_POST['id_jadwal'] ?? 0);
        $idAuditor = (int)($_POST['id_auditor'] ?? 0);
        $idProdi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;
        $tanggal = trim($_POST['tanggal_audit'] ?? '');
        $status = $_POST['status'] ?? 'Ditugaskan';

        if ($idTugas <= 0 || $idJadwal <= 0 || $idAuditor <= 0 || $tanggal === '') {
            $error = '❌ Data tidak lengkap.';
        } else {
            try {
                $db->prepare("UPDATE penugasan_audit SET id_jadwal=?, id_auditor=?, id_prodi=?, tanggal_audit=?, status=? WHERE id_tugas=?")
                   ->execute([$idJadwal, $idAuditor, $idProdi, $tanggal, $status, $idTugas]);
                
                Logger::log('UPDATE', 'Memperbarui penugasan audit #' . $idTugas);
                $pesan = '✅ Penugasan audit berhasil diperbarui.';
            } catch (PDOException $e) {
                $error = '❌ Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }

    // ================= HAPUS PENUGASAN =================
    if ($action === 'delete') {
        $idTugas = (int)($_POST['id_tugas'] ?? 0);
        if ($idTugas <= 0) {
            $error = '❌ ID penugasan tidak valid.';
        } else {
            try {
                $db->prepare("DELETE FROM penugasan_audit WHERE id_tugas=?")->execute([$idTugas]);
                Logger::log('DELETE', 'Menghapus penugasan audit #' . $idTugas);
                $pesan = '✅ Penugasan audit dihapus.';
            } catch (PDOException $e) {
                $error = '❌ Gagal menghapus: ' . $e->getMessage();
            }
        }
    }
}

// Muat daftar penugasan
try {
    $tugasList = $db->query("SELECT pa.*, 
        u.nama_lengkap as nama_auditor, 
        p.nama_prodi as nama_prodi_audit,
        j.tahun_ami
        FROM penugasan_audit pa
        LEFT JOIN users u ON pa.id_auditor = u.id_user
        LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
        LEFT JOIN jadwal_ami j ON pa.id_jadwal = j.id_jadwal
        ORDER BY pa.tanggal_audit DESC, j.tahun_ami DESC
        LIMIT 200")->fetchAll();
} catch (PDOException $e) {
    $tugasList = [];
}

// Mode edit
$editTugas = null;
if (!empty($_GET['edit'])) {
    try {
        $st = $db->prepare("SELECT * FROM penugasan_audit WHERE id_tugas = ?");
        $st->execute([(int)$_GET['edit']]);
        $editTugas = $st->fetch();
    } catch (PDOException $e) {}
}

$simTitle = 'Penugasan Audit AMI';
$activeMenu = 'penugasan';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= $pesan ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:<?= $editTugas ? '1fr 1fr' : '1fr' ?>;gap:24px;margin-bottom:28px;">
    <!-- Form Tambah/Edit -->
    <div class="card">
        <h3 style="margin-bottom:20px;"><?= $editTugas ? '️ Edit' : '➕ Tambah' ?> Penugasan Audit</h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="<?= $editTugas ? 'edit' : 'add' ?>">
            <?php if ($editTugas): ?><input type="hidden" name="id_tugas" value="<?= $editTugas['id_tugas'] ?>"><?php endif; ?>
            
            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Jadwal AMI *</label>
                <select name="id_jadwal" class="form-control" required>
                    <option value="">— Pilih Jadwal —</option>
                    <?php foreach ($jadwalList as $j): ?>
                        <option value="<?= $j['id_jadwal'] ?>" <?= ($editTugas['id_jadwal'] ?? '') == $j['id_jadwal'] ? 'selected' : '' ?>>
                            AMI Tahun <?= Security::e($j['tahun_ami']) ?> (<?= date('d M', strtotime($j['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($j['tanggal_selesai'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Auditor *</label>
                <select name="id_auditor" class="form-control" required>
                    <option value="">— Pilih Auditor —</option>
                    <?php foreach ($auditors as $a): ?>
                        <option value="<?= $a['id_user'] ?>" <?= ($editTugas['id_auditor'] ?? '') == $a['id_user'] ? 'selected' : '' ?>>
                            <?= Security::e($a['nama_lengkap']) ?> <?= $a['prodi_asal'] ? '(' . Security::e($a['prodi_asal']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($auditors)): ?>
                    <small class="text-muted">⚠️ Belum ada auditor. <a href="/sim/admin/gpm.php" style="color:var(--primary);">Kelola GPM/Auditor</a></small>
                <?php endif; ?>
            </div>
            
            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Prodi yang Diaudit <small class="text-muted">(kosongkan = audit lintas prodi/umum)</small></label>
                <select name="id_prodi" class="form-control">
                    <option value="">— Audit Umum/Lintas Prodi —</option>
                    <?php foreach ($prodiList as $p): ?>
                        <option value="<?= $p['id_prodi'] ?>" <?= ($editTugas['id_prodi'] ?? '') == $p['id_prodi'] ? 'selected' : '' ?>>
                            <?= Security::e($p['nama_prodi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Tanggal Audit *</label>
                <input type="date" name="tanggal_audit" class="form-control" value="<?= Security::e($editTugas['tanggal_audit'] ?? '') ?>" required>
            </div>
            
            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="Ditugaskan" <?= ($editTugas['status'] ?? '') === 'Ditugaskan' ? 'selected' : '' ?>>Ditugaskan</option>
                    <option value="Dikerjakan" <?= ($editTugas['status'] ?? '') === 'Dikerjakan' ? 'selected' : '' ?>>Dikerjakan</option>
                    <option value="Selesai" <?= ($editTugas['status'] ?? '') === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width:100%;"><?= $editTugas ? '💾 Perbarui' : '➕ Tambah Penugasan' ?></button>
            <?php if ($editTugas): ?>
                <a href="/sim/admin/penugasan.php" class="btn btn-outline" style="width:100%;margin-top:8px;">Batal</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Info Panel -->
    <?php if (!$editTugas): ?>
    <div class="card" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;">
        <h3 style="color:#fff;margin-bottom:12px;">💡 Panduan Penugasan</h3>
        <div style="font-size:14px;opacity:.95;line-height:1.7;">
            <p><strong>Langkah Penugasan:</strong></p>
            <ol style="padding-left:20px;margin-bottom:16px;">
                <li>Pilih jadwal AMI yang sudah dibuat</li>
                <li>Pilih auditor (pastikan prodi asal sudah diatur)</li>
                <li>Pilih prodi yang akan diaudit (atau kosongkan untuk audit umum)</li>
                <li>Tentukan tanggal pelaksanaan audit</li>
                <li>Sistem akan otomatis mendeteksi <strong>konflik kepentingan</strong></li>
            </ol>
            <p style="border-top:1px solid rgba(255,255,255,.3);padding-top:12px;margin-top:12px;">
                ⚠️ <strong>Penting:</strong> Auditor tidak boleh mengaudit prodi asalnya sendiri. Cek halaman <a href="/sim/admin/konflik.php" style="color:#FBBF24;text-decoration:underline;"><strong>Cek Konflik Kepentingan</strong></a> setelah penugasan.
            </p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Daftar Penugasan -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;">📋 Daftar Penugasan Audit (<?= count($tugasList) ?>)</h3>
        <a href="/sim/admin/konflik.php" class="btn btn-outline" style="padding:6px 14px;font-size:12px;">⚖️ Cek Konflik</a>
    </div>
    
    <?php if (empty($tugasList)): ?>
        <div style="text-align:center;padding:40px;">
            <div style="font-size:48px;margin-bottom:12px;"></div>
            <p class="text-muted">Belum ada penugasan audit. Tambahkan penugasan pertama Anda di form sebelah kiri.</p>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Tahun AMI</th>
                    <th>Auditor</th>
                    <th>Prodi Asal</th>
                    <th>Prodi Diaudit</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th style="width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tugasList as $t): 
                    // Cek konflik
                    $stmt = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
                    $stmt->execute([$t['id_auditor']]);
                    $prodiAuditor = $stmt->fetchColumn();
                    $isKonflik = $prodiAuditor && $prodiAuditor == $t['id_prodi'];
                ?>
                <tr style="<?= $isKonflik ? 'background:rgba(239,68,68,.05);' : '' ?>">
                    <td><strong><?= Security::e($t['tahun_ami'] ?? '—') ?></strong></td>
                    <td><?= Security::e($t['nama_auditor']) ?></td>
                    <td><?= $prodiAuditor ? Security::e($db->query("SELECT nama_prodi FROM prodi WHERE id_prodi=$prodiAuditor")->fetchColumn() ?: '—') : '<span class="text-muted">Belum diatur</span>' ?></td>
                    <td><?= Security::e($t['nama_prodi_audit'] ?? 'Umum/Lintas Prodi') ?></td>
                    <td><?= $t['tanggal_audit'] ? date('d M Y', strtotime($t['tanggal_audit'])) : '—' ?></td>
                    <td>
                        <span class="badge" style="<?= 
                            $t['status'] === 'Selesai' ? 'background:#D1FAE5;color:#065F46;' : 
                            ($t['status'] === 'Dikerjakan' ? 'background:#FEF3C7;color:#92400E;' : 
                            'background:#DBEAFE;color:#1E40AF;') 
                        ?>">
                            <?= Security::e($t['status']) ?>
                        </span>
                        <?php if ($isKonflik): ?>
                            <br><span class="badge" style="background:#FEE2E2;color:#991B1B;margin-top:4px;">️ Konflik</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="?edit=<?= $t['id_tugas'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:11px;" title="Edit">✏️</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus penugasan ini?');">
                                <input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);" title="Hapus">🗑️</button>
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

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>