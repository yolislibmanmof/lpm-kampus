<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$pesan = ''; $error = '';

// Konstanta untuk validasi ENUM status
define('STATUS_TL', ['Belum Mulai', 'Berjalan', 'Selesai', 'Tertunda']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= TAMBAH KEPUTUSAN BARU =================
    if ($action === 'add') {
        $tahun = (int)($_POST['tahun'] ?? date('Y'));
        $keputusan = mb_substr(trim($_POST['keputusan'] ?? ''), 0, 500);
        $pic = mb_substr(trim($_POST['pic'] ?? ''), 0, 100);
        $tenggat = trim($_POST['tenggat'] ?? '') ?: null;

        if ($tahun < 2000 || $tahun > (date('Y') + 5)) {
            $error = '❌ Tahun tidak valid.';
        } elseif ($keputusan === '') {
            $error = '❌ Keputusan / Tindak Lanjut wajib diisi.';
        } else {
            try {
                // Default status 'Belum Mulai' saat pertama kali dibuat
                $db->prepare("INSERT INTO rtm_tindak_lanjut (tahun, keputusan, pic, tenggat, status) VALUES (?,?,?,?, 'Belum Mulai')")
                   ->execute([$tahun, $keputusan, $pic, $tenggat]);
                
                Logger::log('CREATE', 'Menambahkan tindak lanjut RTM tahun ' . $tahun . ': ' . mb_substr($keputusan, 0, 50));
                $pesan = '✅ Keputusan RTM berhasil dicatat.';
            } catch (PDOException $e) {
                $error = '❌ Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }

    // ================= UPDATE STATUS & BUKTI =================
    if ($action === 'update') {
        $id = (int)($_POST['id_tl'] ?? 0);
        $status = $_POST['status'] ?? '';
        $bukti = mb_substr(trim($_POST['bukti'] ?? ''), 0, 255);

        if ($id <= 0) {
            $error = '❌ ID tindak lanjut tidak valid.';
        } elseif (!in_array($status, STATUS_TL, true)) {
            $error = '❌ Status tidak valid.';
        } else {
            try {
                $db->prepare("UPDATE rtm_tindak_lanjut SET status=?, bukti=? WHERE id_tl=?")
                   ->execute([$status, $bukti, $id]);
                
                Logger::log('UPDATE', 'Memperbarui status tindak lanjut RTM #' . $id . ' menjadi ' . $status);
                $pesan = '✅ Status tindak lanjut diperbarui.';
            } catch (PDOException $e) {
                $error = '❌ Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }

    // ================= HAPUS =================
    if ($action === 'delete') {
        $id = (int)($_POST['id_tl'] ?? 0);
        if ($id <= 0) {
            $error = '❌ ID tidak valid.';
        } else {
            try {
                // Ambil data dulu untuk dicatat di log
                $stmt = $db->prepare("SELECT keputusan FROM rtm_tindak_lanjut WHERE id_tl=?");
                $stmt->execute([$id]);
                $keputusan = $stmt->fetchColumn() ?: 'Unknown';
                
                $db->prepare("DELETE FROM rtm_tindak_lanjut WHERE id_tl=?")->execute([$id]);
                Logger::log('DELETE', 'Menghapus tindak lanjut RTM: ' . mb_substr($keputusan, 0, 50));
                $pesan = '✅ Baris berhasil dihapus.';
            } catch (PDOException $e) {
                $error = '❌ Gagal menghapus: ' . $e->getMessage();
            }
        }
    }
}

// ================= MUAT DATA & STATISTIK =================
try {
    $list = $db->query("SELECT * FROM rtm_tindak_lanjut ORDER BY tahun DESC, tenggat ASC")->fetchAll();
    
    $stats = ['Belum Mulai' => 0, 'Berjalan' => 0, 'Selesai' => 0, 'Tertunda' => 0];
    foreach ($db->query("SELECT status, COUNT(*) c FROM rtm_tindak_lanjut GROUP BY status")->fetchAll() as $r) {
        if (isset($stats[$r['status']])) {
            $stats[$r['status']] = (int)$r['c'];
        }
    }
    
    $late = 0;
    $now = time();
    foreach ($list as $r) {
        if ($r['status'] !== 'Selesai' && $r['tenggat'] && strtotime($r['tenggat']) < $now) {
            $late++;
        }
    }
} catch (PDOException $e) {
    $error = '❌ Gagal memuat data: ' . $e->getMessage();
    $list = [];
    $stats = ['Belum Mulai' => 0, 'Berjalan' => 0, 'Selesai' => 0, 'Tertunda' => 0];
    $late = 0;
}

$simTitle = 'Tindak Lanjut RTM';
$activeMenu = 'rtmtl';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<!-- Statistik Ringkas -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">📑</div><div><h3 style="font-size:28px;"><?= count($list) ?></h3><p class="text-muted">Total Keputusan</p></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><h3 style="font-size:28px;"><?= $stats['Selesai'] ?></h3><p class="text-muted">Selesai</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">🔄</div><div><h3 style="font-size:28px;"><?= $stats['Berjalan'] ?></h3><p class="text-muted">Berjalan</p></div></div>
    <div class="stat-card"><div class="stat-icon red">⏰</div><div><h3 style="font-size:28px;"><?= $late ?></h3><p class="text-muted">Lewat Tenggat</p></div></div>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Catat Keputusan RTM Baru</h3>
    <form method="POST" style="display:grid;grid-template-columns:.6fr 3fr 1fr 1fr auto;gap:14px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Tahun</label>
            <input type="number" name="tahun" class="form-control" min="2000" max="<?= date('Y') + 5 ?>" value="<?= date('Y') ?>"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Keputusan / Tindak Lanjut *</label>
            <input type="text" name="keputusan" class="form-control" maxlength="500" placeholder="cth: Revisi manual mutu S3 sesuai SN-Dikti baru" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">PIC</label>
            <input type="text" name="pic" class="form-control" maxlength="100" placeholder="cth: Kabid Standar"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tenggat</label>
            <input type="date" name="tenggat" class="form-control"></div>
        <button class="btn btn-primary">💾 Simpan</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Monitoring Tindak Lanjut</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Tahun</th><th>Keputusan & PIC</th><th>Tenggat</th><th>Status</th><th>Bukti</th><th style="width:60px;"></th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada tindak lanjut dicatat.</td></tr>
            <?php else: ?>
                <?php foreach ($list as $r):
                    $overdue = $r['status'] !== 'Selesai' && $r['tenggat'] && strtotime($r['tenggat']) < time();
                ?>
                    <tr>
                        <td><strong><?= (int)$r['tahun'] ?></strong></td>
                        <td>
                            <strong style="font-size:13.5px;"><?= Security::e($r['keputusan']) ?></strong><br>
                            <small class="text-muted">PIC: <?= Security::e($r['pic'] ?: '—') ?></small>
                        </td>
                        <td>
                            <?= $r['tenggat'] ? date('d M Y', strtotime($r['tenggat'])) : '—' ?>
                            <?php if ($overdue): ?><br><span class="badge" style="background:#FEE2E2;color:#991B1B;margin-top:4px;display:inline-block;">⏰ Terlambat</span><?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:flex;gap:8px;align-items:center;">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id_tl" value="<?= $r['id_tl'] ?>">
                                <select name="status" class="form-control" style="width:auto;padding:7px 10px;font-size:12.5px;font-weight:600;
                                    <?= $r['status'] === 'Selesai' ? 'background:#D1FAE5;color:#065F46;' : ($r['status'] === 'Berjalan' ? 'background:#FEF3C7;color:#92400E;' : '') ?>" 
                                    onchange="this.form.submit()">
                                    <?php foreach (STATUS_TL as $s): ?>
                                        <option <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td style="min-width:180px;">
                            <form method="POST" style="display:flex;gap:6px;">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id_tl" value="<?= $r['id_tl'] ?>">
                                <input type="hidden" name="status" value="<?= Security::e($r['status']) ?>">
                                <input type="text" name="bukti" class="form-control" maxlength="255" style="padding:7px 10px;font-size:12.5px;" placeholder="tautan/dokumen bukti" value="<?= Security::e($r['bukti']) ?>">
                                <button class="btn btn-outline" style="padding:6px 12px;font-size:12px;" title="Simpan Bukti">💾</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Hapus catatan tindak lanjut ini? Tindakan tidak dapat dibatalkan.');">
                                <input type="hidden" name="id_tl" value="<?= $r['id_tl'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);" title="Hapus">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="font-size:12.5px;margin-top:14px;">💡 Bukti tindak lanjut yang rapi = poin besar saat asesmen eksternal (siklus PPEPP tertutup sempurna).</p>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>