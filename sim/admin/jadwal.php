<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= BUAT JADWAL =================
    if ($action === 'create_jadwal') {
        $tahun = (int)($_POST['tahun_ami'] ?? date('Y'));
        $mulai = trim($_POST['tanggal_mulai'] ?? '');
        $selesai = trim($_POST['tanggal_selesai'] ?? '');

        if ($tahun < 2000 || $tahun > (date('Y') + 5)) {
            $error = '❌ Tahun AMI tidak valid.';
        } elseif ($mulai === '' || $selesai === '') {
            $error = '❌ Tanggal mulai dan selesai wajib diisi.';
        } elseif (strtotime($selesai) < strtotime($mulai)) {
            $error = '❌ Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
        } else {
            try {
                $db->prepare("INSERT INTO jadwal_ami (tahun_ami, tanggal_mulai, tanggal_selesai, status) VALUES (?,?,?,'Draf')")
                   ->execute([$tahun, $mulai, $selesai]);
                Logger::log('CREATE', 'Membuat jadwal AMI tahun ' . $tahun);
                $pesan = '✅ Jadwal AMI berhasil dibuat.';
            } catch (PDOException $e) {
                $error = '❌ Gagal menyimpan jadwal: ' . $e->getMessage();
            }
        }
    }

    // ================= PLOTING AUDITOR =================
    if ($action === 'create_tugas') {
        $idJadwal = (int)($_POST['id_jadwal'] ?? 0);
        $idProdi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;
        $idAuditor = (int)($_POST['id_auditor'] ?? 0);
        $tanggal = trim($_POST['tanggal_audit'] ?? '');

        if ($idJadwal <= 0 || $idAuditor <= 0 || $tanggal === '') {
            $error = '❌ Jadwal, Auditor, dan Tanggal Audit wajib diisi.';
        } else {
            try {
                // Cek duplikasi: auditor yang sama di prodi yang sama pada jadwal yang sama
                $cek = $db->prepare("SELECT COUNT(*) FROM penugasan_audit WHERE id_jadwal = ? AND id_auditor = ? AND id_prodi = ?");
                $cek->execute([$idJadwal, $idAuditor, $idProdi]);
                if ($cek->fetchColumn() > 0) {
                    $error = '❌ Auditor ini sudah ditugaskan untuk prodi tersebut pada jadwal ini.';
                } else {
                    $db->prepare("INSERT INTO penugasan_audit (id_jadwal, id_prodi, id_auditor, tanggal_audit, status) VALUES (?,?,?,?, 'Ditugaskan')")
                       ->execute([$idJadwal, $idProdi, $idAuditor, $tanggal]);
                    
                    $stmt = $db->prepare("SELECT nama_lengkap FROM users WHERE id_user = ?");
                    $stmt->execute([$idAuditor]);
                    $namaAuditor = $stmt->fetchColumn() ?: 'Unknown';
                    
                    Logger::log('CREATE', 'Menugaskan auditor ' . $namaAuditor . ' untuk AMI');
                    $pesan = '✅ Auditor berhasil diplot ke prodi.';
                }
            } catch (PDOException $e) {
                $error = '❌ Gagal menyimpan penugasan: ' . $e->getMessage();
            }
        }
    }

    // ================= HAPUS PENUGASAN =================
    if ($action === 'delete_tugas') {
        $idTugas = (int)($_POST['id_tugas'] ?? 0);
        if ($idTugas <= 0) {
            $error = '❌ ID penugasan tidak valid.';
        } else {
            try {
                $db->prepare("DELETE FROM penugasan_audit WHERE id_tugas = ?")->execute([$idTugas]);
                Logger::log('DELETE', 'Menghapus penugasan audit #' . $idTugas);
                $pesan = '✅ Penugasan dihapus.';
            } catch (PDOException $e) {
                $error = '❌ Gagal menghapus: ' . $e->getMessage();
            }
        }
    }
}

try {
    $jadwal  = $db->query("SELECT * FROM jadwal_ami ORDER BY tahun_ami DESC")->fetchAll();
    $tugas   = $db->query("SELECT t.*, p.nama_prodi, u.nama_lengkap AS auditor, j.tahun_ami 
        FROM penugasan_audit t 
        LEFT JOIN prodi p ON t.id_prodi = p.id_prodi 
        LEFT JOIN users u ON t.id_auditor = u.id_user 
        LEFT JOIN jadwal_ami j ON t.id_jadwal = j.id_jadwal 
        ORDER BY j.tahun_ami DESC, t.tanggal_audit DESC")->fetchAll();
    $prodi   = $db->query("SELECT * FROM prodi ORDER BY nama_prodi")->fetchAll();
    $auditor = $db->query("SELECT id_user, nama_lengkap FROM users WHERE id_role = 4 AND is_active = 1 ORDER BY nama_lengkap")->fetchAll();
} catch (PDOException $e) {
    $error = '❌ Gagal memuat data: ' . $e->getMessage();
    $jadwal = []; $tugas = []; $prodi = []; $auditor = [];
}

$simTitle = 'Penjadwalan AMI';
$activeMenu = 'jadwal';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= $pesan ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
    <!-- Buat Jadwal -->
    <div class="card">
        <h3 style="margin-bottom:20px;">📅 Buat Jadwal AMI</h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="create_jadwal">
            <div class="form-group"><label class="form-label">Tahun AMI *</label>
                <input type="number" name="tahun_ami" class="form-control" min="2000" max="<?= date('Y') + 5 ?>" value="<?= date('Y') ?>" required></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group"><label class="form-label">Tanggal Mulai *</label>
                    <input type="date" name="tanggal_mulai" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Tanggal Selesai *</label>
                    <input type="date" name="tanggal_selesai" class="form-control" required></div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">💾 Simpan Jadwal</button>
        </form>
    </div>

    <!-- Ploting Auditor -->
    <div class="card">
        <h3 style="margin-bottom:20px;">🎯 Ploting Auditor ke Prodi</h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="create_tugas">
            <div class="form-group"><label class="form-label">Jadwal AMI *</label>
                <select name="id_jadwal" class="form-control" required>
                    <option value="">— Pilih Jadwal —</option>
                    <?php foreach ($jadwal as $j): ?><option value="<?= $j['id_jadwal'] ?>">AMI <?= $j['tahun_ami'] ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Program Studi <small class="text-muted">(opsional)</small></label>
                <select name="id_prodi" class="form-control">
                    <option value="">— Audit Umum/Lintas Prodi —</option>
                    <?php foreach ($prodi as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">Auditor *</label>
                <select name="id_auditor" class="form-control" required>
                    <option value="">— Pilih Auditor —</option>
                    <?php foreach ($auditor as $a): ?><option value="<?= $a['id_user'] ?>"><?= Security::e($a['nama_lengkap']) ?></option><?php endforeach; ?>
                </select>
                <?php if (empty($auditor)): ?><small class="text-muted">⚠️ Belum ada auditor. <a href="/sim/admin/gpm.php" style="color:var(--primary);">Kelola GPM/Auditor</a></small><?php endif; ?>
            </div>
            <div class="form-group"><label class="form-label">Tanggal Audit *</label>
                <input type="date" name="tanggal_audit" class="form-control" required></div>
            <button type="submit" class="btn btn-gold" style="width:100%;">🎯 Tugaskan</button>
        </form>
    </div>
</div>

<!-- Daftar Penugasan -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;">📋 Daftar Penugasan Audit (<?= count($tugas) ?>)</h3>
        <a href="/sim/admin/konflik.php" class="btn btn-outline" style="padding:6px 14px;font-size:12px;">⚖️ Cek Konflik Kepentingan</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Tahun AMI</th><th>Program Studi</th><th>Auditor</th><th>Tanggal Audit</th><th>Status</th><th style="width:80px;">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($tugas)): ?>
                <tr><td colspan="6" style="text-align:center;padding:40px;" class="text-muted">Belum ada penugasan audit.</td></tr>
            <?php else: ?>
                <?php foreach ($tugas as $t): ?>
                    <tr>
                        <td><strong><?= Security::e($t['tahun_ami'] ?? '—') ?></strong></td>
                        <td><?= Security::e($t['nama_prodi'] ?? 'Umum/Lintas Prodi') ?></td>
                        <td><?= Security::e($t['auditor'] ?? '—') ?></td>
                        <td><?= $t['tanggal_audit'] ? date('d M Y', strtotime($t['tanggal_audit'])) : '—' ?></td>
                        <td><span class="badge badge-b"><?= Security::e($t['status'] ?? 'Ditugaskan') ?></span></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Hapus penugasan ini?');">
                                <input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete_tugas">
                                <button class="btn btn-outline" style="padding:5px 10px;font-size:11px;color:var(--danger);" title="Hapus">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>