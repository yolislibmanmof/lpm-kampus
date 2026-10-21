<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$pesan = ''; $error = '';

define('JENIS_TENGGAT', ['Akreditasi', 'AMI', 'Laporan', 'Lainnya']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= ADD TENGGAT =================
    if ($action === 'add') {
        $judul = mb_substr(trim($_POST['judul'] ?? ''), 0, 150);
        $jenis = $_POST['jenis'] ?? 'Lainnya';
        $prodi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;
        $tanggal = trim($_POST['tanggal'] ?? '');

        if ($judul === '') {
            $error = '❌ Judul tenggat wajib diisi.';
        } elseif (!in_array($jenis, JENIS_TENGGAT, true)) {
            $error = '❌ Jenis tenggat tidak valid.';
        } elseif ($tanggal === '' || !DateTime::createFromFormat('Y-m-d', $tanggal)) {
            $error = '❌ Tanggal tidak valid (format: YYYY-MM-DD).';
        } else {
            try {
                $db->prepare("INSERT INTO pengingat_tenggat (judul, jenis, id_prodi, tanggal) VALUES (?,?,?,?)")
                   ->execute([$judul, $jenis, $prodi, $tanggal]);
                Logger::log('CREATE', 'Menambah tenggat: ' . $judul . ' (' . $tanggal . ')');
                $pesan = '✅ Tenggat dicatat — reminder otomatis aktif (H-90/60/30/7/1/0).';
            } catch (PDOException $e) {
                $error = '❌ Gagal menyimpan tenggat: ' . $e->getMessage();
            }
        }
    }

    // ================= EDIT TENGGAT =================
    if ($action === 'edit') {
        $id = (int)($_POST['id_tenggat'] ?? 0);
        $judul = mb_substr(trim($_POST['judul'] ?? ''), 0, 150);
        $jenis = $_POST['jenis'] ?? 'Lainnya';
        $prodi = !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null;
        $tanggal = trim($_POST['tanggal'] ?? '');

        if ($id <= 0 || $judul === '') {
            $error = ' Data tidak lengkap.';
        } elseif (!in_array($jenis, JENIS_TENGGAT, true)) {
            $error = '❌ Jenis tenggat tidak valid.';
        } elseif ($tanggal === '' || !DateTime::createFromFormat('Y-m-d', $tanggal)) {
            $error = '❌ Tanggal tidak valid.';
        } else {
            try {
                $db->prepare("UPDATE pengingat_tenggat SET judul=?, jenis=?, id_prodi=?, tanggal=? WHERE id_tenggat=?")
                   ->execute([$judul, $jenis, $prodi, $tanggal, $id]);
                Logger::log('UPDATE', 'Memperbarui tenggat #' . $id . ': ' . $judul);
                $pesan = '✅ Tenggat diperbarui.';
            } catch (PDOException $e) {
                $error = '❌ Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }

    // ================= TOGGLE STATUS =================
    if ($action === 'toggle') {
        try {
            $id = (int)$_POST['id_tenggat'];
            $db->prepare("UPDATE pengingat_tenggat SET selesai = 1 - selesai WHERE id_tenggat = ?")->execute([$id]);
            Logger::log('UPDATE', 'Mengubah status tenggat #' . $id);
            $pesan = '✅ Status tenggat diperbarui.';
        } catch (PDOException $e) { $error = '❌ Gagal mengubah status: ' . $e->getMessage(); }
    }

    // ================= DELETE TENGGAT =================
    if ($action === 'del') {
        try {
            $id = (int)$_POST['id_tenggat'];
            $stmt = $db->prepare("SELECT judul FROM pengingat_tenggat WHERE id_tenggat = ?");
            $stmt->execute([$id]);
            $judul = $stmt->fetchColumn() ?: 'Unknown';
            
            $db->prepare("DELETE FROM pengingat_tenggat WHERE id_tenggat = ?")->execute([$id]);
            Logger::log('DELETE', 'Menghapus tenggat: ' . $judul);
            $pesan = '✅ Tenggat dihapus.';
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }
}

$list = $db->query("SELECT t.*, p.nama_prodi FROM pengingat_tenggat t LEFT JOIN prodi p ON t.id_prodi = p.id_prodi ORDER BY t.selesai ASC, t.tanggal ASC")->fetchAll();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$today = strtotime(date('Y-m-d'));

// Statistik
$nTotal = count($list);
$nSelesai = count(array_filter($list, fn($t) => $t['selesai']));
$nAktif = $nTotal - $nSelesai;
$nTerlambat = count(array_filter($list, function($t) use ($today) {
    return !$t['selesai'] && $t['tanggal'] && strtotime($t['tanggal']) < $today;
}));

$simTitle = 'Tenggat & Reminder';
$activeMenu = 'tenggat';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= $pesan ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">📅</div><div><h3 style="font-size:26px;"><?= $nTotal ?></h3><p class="text-muted">Total Tenggat</p></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><h3 style="font-size:26px;"><?= $nSelesai ?></h3><p class="text-muted">Selesai</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">⏳</div><div><h3 style="font-size:26px;"><?= $nAktif ?></h3><p class="text-muted">Aktif</p></div></div>
    <div class="stat-card"><div class="stat-icon red">⚠️</div><div><h3 style="font-size:26px;"><?= $nTerlambat ?></h3><p class="text-muted">Terlambat</p></div></div>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Catat Tenggat (Akreditasi / AMI / Laporan)</h3>
    <form method="POST" style="display:grid;grid-template-columns:2.2fr 1fr 1.2fr .9fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Judul Tenggat *</label>
            <input type="text" name="judul" class="form-control" maxlength="150" placeholder="cth: Submit LED-LKPS Bisnis Digital (BAN-PT)" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Jenis *</label>
            <select name="jenis" class="form-control">
                <?php foreach (JENIS_TENGGAT as $j): ?><option><?= $j ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Prodi (opsional)</label>
            <select name="id_prodi" class="form-control"><option value="0">— Umum —</option>
                <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tanggal *</label>
            <input type="date" name="tanggal" class="form-control" required></div>
        <button class="btn btn-gold">💾</button>
    </form>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3 style="margin:0;">📋 Daftar Tenggat (<?= $nTotal ?>)</h3>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <select id="fJenis" class="form-control" style="width:auto;">
                <option value="">Semua Jenis</option>
                <?php foreach (JENIS_TENGGAT as $j): ?><option><?= $j ?></option><?php endforeach; ?>
            </select>
            <input type="text" id="cariTenggat" class="form-control" placeholder="🔍 Cari judul..." style="max-width:220px;">
        </div>
    </div>
    <div class="table-wrapper">
        <table id="tabelTenggat">
            <thead><tr><th>Tenggat</th><th>Jenis</th><th>Prodi</th><th>Tanggal</th><th>Status</th><th style="width:150px;">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada tenggat. Catat tanggal akreditasi prodi di sini!</td></tr>
            <?php else: ?>
                <?php foreach ($list as $t):
                    $h = $t['tanggal'] ? (int)round((strtotime($t['tanggal']) - $today) / 86400) : null;
                    if ($t['selesai']) {
                        $badge = '<span class="badge badge-baik">✅ Selesai</span>';
                    } elseif ($h === null) {
                        $badge = '<span class="badge badge-b">Tanpa Tanggal</span>';
                    } elseif ($h < 0) {
                        $badge = '<span class="badge" style="background:#FEE2E2;color:#991B1B;">⚠️ Lewat ' . abs($h) . ' hari</span>';
                    } elseif ($h <= 7) {
                        $badge = '<span class="badge" style="background:#FEE2E2;color:#991B1B;">🔴 H-' . $h . '</span>';
                    } elseif ($h <= 30) {
                        $badge = '<span class="badge" style="background:#FEF3C7;color:#92400E;">🟡 H-' . $h . '</span>';
                    } else {
                        $badge = '<span class="badge badge-baik">🟢 H-' . $h . '</span>';
                    }
                ?>
                    <tr class="tenggat-row" data-jenis="<?= Security::e($t['jenis']) ?>">
                        <td><strong><?= Security::e($t['judul']) ?></strong></td>
                        <td><span class="badge badge-a"><?= Security::e($t['jenis']) ?></span></td>
                        <td><?= Security::e($t['nama_prodi'] ?? 'Umum') ?></td>
                        <td><?= $t['tanggal'] ? date('d M Y', strtotime($t['tanggal'])) : '—' ?></td>
                        <td><?= $badge ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <button class="btn btn-outline btn-edit-tenggat" style="padding:5px 10px;font-size:11px;"
                                    data-id="<?= $t['id_tenggat'] ?>"
                                    data-judul="<?= Security::e($t['judul']) ?>"
                                    data-jenis="<?= Security::e($t['jenis']) ?>"
                                    data-prodi="<?= (int)($t['id_prodi'] ?? 0) ?>"
                                    data-tanggal="<?= Security::e($t['tanggal'] ?? '') ?>">✏️</button>
                                <form method="POST" style="display:inline;"><input type="hidden" name="id_tenggat" value="<?= $t['id_tenggat'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-outline" style="padding:5px 10px;font-size:11px;"><?= $t['selesai'] ? '↩️ Buka' : '✅' ?></button></form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus tenggat ini?');"><input type="hidden" name="id_tenggat" value="<?= $t['id_tenggat'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del"><button class="btn btn-outline" style="padding:5px 10px;font-size:11px;color:var(--danger);">🗑️</button></form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit Tenggat -->
<div id="modalEditTenggat" style="display:none;position:fixed;inset:0;background:rgba(4,10,20,.62);backdrop-filter:blur(5px);z-index:4000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:var(--bg-card);border-radius:18px;box-shadow:var(--shadow-lg);border:1px solid var(--border);width:min(560px,100%);overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;">✏️ Edit Tenggat</h3>
            <button onclick="document.getElementById('modalEditTenggat').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);">×</button>
        </div>
        <form method="POST" style="padding:22px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_tenggat" id="et_id">
            <div class="form-group" style="margin:0;"><label class="form-label">Judul Tenggat *</label>
                <input type="text" name="judul" id="et_judul" class="form-control" maxlength="150" required></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Jenis *</label>
                    <select name="jenis" id="et_jenis" class="form-control">
                        <?php foreach (JENIS_TENGGAT as $j): ?><option><?= $j ?></option><?php endforeach; ?>
                    </select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Prodi</label>
                    <select name="id_prodi" id="et_prodi" class="form-control">
                        <option value="0">— Umum —</option>
                        <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Tanggal *</label>
                    <input type="date" name="tanggal" id="et_tanggal" class="form-control" required></div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalEditTenggat').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Filter & pencarian
(function() {
    var q = document.getElementById('cariTenggat'), fj = document.getElementById('fJenis');
    if (!q) return;
    function apply() {
        var term = q.value.toLowerCase(), jenis = fj.value;
        document.querySelectorAll('#tabelTenggat .tenggat-row').forEach(function(tr) {
            var okT = tr.textContent.toLowerCase().indexOf(term) > -1;
            var okJ = !jenis || tr.dataset.jenis === jenis;
            tr.style.display = (okT && okJ) ? '' : 'none';
        });
    }
    q.addEventListener('input', apply);
    fj.addEventListener('change', apply);
})();

// Modal edit tenggat
(function() {
    var modal = document.getElementById('modalEditTenggat');
    if (!modal) return;
    document.querySelectorAll('.btn-edit-tenggat').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('et_id').value = btn.dataset.id;
            document.getElementById('et_judul').value = btn.dataset.judul;
            document.getElementById('et_jenis').value = btn.dataset.jenis;
            document.getElementById('et_prodi').value = btn.dataset.prodi;
            document.getElementById('et_tanggal').value = btn.dataset.tanggal;
            modal.style.display = 'flex';
        });
    });
    modal.addEventListener('click', function(e) { if (e.target === modal) modal.style.display = 'none'; });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>