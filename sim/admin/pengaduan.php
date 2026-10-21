<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

define('STATUS_PENGADUAN', ['Baru', 'Diproses', 'Selesai']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id_pengaduan'] ?? 0);

    // ================= UBAH STATUS =================
    if ($action === 'status') {
        $status = $_POST['status'] ?? '';
        if (!in_array($status, STATUS_PENGADUAN, true)) {
            $error = '❌ Status tidak valid.';
        } else {
            try {
                $db->prepare("UPDATE pengaduan SET status = ? WHERE id_pengaduan = ?")
                   ->execute([$status, $id]);
                Logger::log('UPDATE', 'Mengubah status pengaduan #' . $id . ' menjadi ' . $status);
                $pesan = '✅ Status pengaduan diperbarui.';
            } catch (PDOException $e) { $error = '❌ Gagal memperbarui status: ' . $e->getMessage(); }
        }
    }

    // ================= HAPUS =================
    if ($action === 'delete') {
        try {
            $db->prepare("DELETE FROM pengaduan WHERE id_pengaduan = ?")->execute([$id]);
            Logger::log('DELETE', 'Menghapus pengaduan #' . $id);
            $pesan = '✅ Pengaduan dihapus.';
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }
}

// ================= MUAT DATA & STATISTIK =================
try {
    $stats = ['Baru' => 0, 'Diproses' => 0, 'Selesai' => 0];
    foreach ($db->query("SELECT status, COUNT(*) c FROM pengaduan GROUP BY status")->fetchAll() as $s) {
        if (isset($stats[$s['status']])) $stats[$s['status']] = (int)$s['c'];
    }
    
    $pengaduan = $db->query("SELECT * FROM pengaduan ORDER BY created_at DESC LIMIT 300")->fetchAll();
} catch (PDOException $e) {
    $error = '❌ Gagal memuat data pengaduan: ' . $e->getMessage();
    $pengaduan = [];
}

$simTitle = 'Pengaduan Publik';
$activeMenu = 'pengaduan';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<!-- Statistik Singkat -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon red">📩</div><div><h3 style="font-size:26px;"><?= $stats['Baru'] ?></h3><p class="text-muted">Pengaduan Baru</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">⏳</div><div><h3 style="font-size:26px;"><?= $stats['Diproses'] ?></h3><p class="text-muted">Sedang Diproses</p></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><h3 style="font-size:26px;"><?= $stats['Selesai'] ?></h3><p class="text-muted">Telah Selesai</p></div></div>
    <div class="stat-card"><div class="stat-icon blue"></div><div><h3 style="font-size:26px;"><?= count($pengaduan) ?></h3><p class="text-muted">Total Masuk</p></div></div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3 style="margin:0;">📋 Daftar Pengaduan Masuk</h3>
        <input type="text" id="cariPengaduan" class="form-control" placeholder="🔍 Cari nama, email, atau subjek..." style="max-width:300px;">
    </div>
    
    <div class="table-wrapper">
        <table id="tabelPengaduan">
            <thead>
                <tr>
                    <th style="width:20%;">Pelapor</th>
                    <th style="width:40%;">Subjek & Isi</th>
                    <th style="width:12%;">Tanggal</th>
                    <th style="width:13%;">Status</th>
                    <th style="width:15%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pengaduan)): ?>
                <tr><td colspan="5" style="text-align:center;padding:40px;" class="text-muted">Belum ada pengaduan masuk. ✅</td></tr>
            <?php else: ?>
                <?php foreach ($pengaduan as $p): ?>
                    <tr class="row-pengaduan">
                        <td>
                            <strong><?= Security::e($p['nama']) ?></strong><br>
                            <small class="text-muted"><?= Security::e($p['email']) ?></small>
                            <?php if (!empty($p['instansi'])): ?><br><small class="text-muted"><?= Security::e($p['instansi']) ?></small><?php endif; ?>
                        </td>
                        <td>
                            <strong style="font-size:14px;"><?= Security::e($p['subjek']) ?></strong><br>
                            <small class="text-muted" style="line-height:1.5;"><?= Security::e(mb_strimwidth($p['isi_pesan'], 0, 150, '...')) ?></small>
                        </td>
                        <td><small><?= date('d M Y', strtotime($p['created_at'])) ?><br><span class="text-muted" style="font-size:11px;"><?= date('H:i', strtotime($p['created_at'])) ?></span></small></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="id_pengaduan" value="<?= $p['id_pengaduan'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="status">
                                <select name="status" class="form-control" style="padding:5px;width:auto;font-size:12px;font-weight:700;
                                    <?= $p['status'] === 'Baru' ? 'background:#FEE2E2;color:#991B1B;' : ($p['status'] === 'Diproses' ? 'background:#FEF3C7;color:#92400E;' : 'background:#D1FAE5;color:#065F46;') ?>" 
                                    onchange="this.form.submit()">
                                    <?php foreach (STATUS_PENGADUAN as $s): ?>
                                        <option <?= $p['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Hapus pengaduan ini secara permanen?');">
                                <input type="hidden" name="id_pengaduan" value="<?= $p['id_pengaduan'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);" title="Hapus">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Pencarian real-time
(function() {
    var q = document.getElementById('cariPengaduan');
    if (!q) return;
    q.addEventListener('input', function() {
        var term = q.value.toLowerCase();
        document.querySelectorAll('#tabelPengaduan .row-pengaduan').forEach(function(tr) {
            tr.style.display = tr.textContent.toLowerCase().indexOf(term) > -1 ? '' : 'none';
        });
    });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>