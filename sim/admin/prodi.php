<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= TAMBAH FAKULTAS =================
    if ($action === 'add_fakultas') {
        $nama = mb_substr(trim($_POST['nama_fakultas'] ?? ''), 0, 150);
        if ($nama !== '') {
            try {
                $db->prepare("INSERT INTO fakultas (nama_fakultas) VALUES (?)")->execute([$nama]);
                Logger::log('CREATE', 'Menambah fakultas: ' . $nama);
                $pesan = '✅ Fakultas berhasil ditambahkan.';
            } catch (PDOException $e) {
                $error = '❌ Gagal menambahkan fakultas: ' . $e->getMessage();
            }
        } else { 
            $error = '❌ Nama fakultas wajib diisi.'; 
        }
    }

    // ================= TAMBAH PRODI =================
    if ($action === 'add_prodi') {
        $kode = mb_substr(trim($_POST['kode_prodi'] ?? ''), 0, 20);
        $nama = mb_substr(trim($_POST['nama_prodi'] ?? ''), 0, 150);
        $fid  = (int)($_POST['id_fakultas'] ?? 0);
        
        if ($kode !== '' && $nama !== '' && $fid > 0) {
            try {
                $db->prepare("INSERT INTO prodi (id_fakultas, kode_prodi, nama_prodi) VALUES (?,?,?)")
                   ->execute([$fid, $kode, $nama]);
                Logger::log('CREATE', 'Menambah prodi: ' . $nama . ' (' . $kode . ')');
                $pesan = '✅ Program studi berhasil ditambahkan.';
            } catch (PDOException $e) { 
                if ($e->getCode() == 23000 || stripos($e->getMessage(), 'Duplicate') !== false) {
                    $error = '❌ Kode prodi sudah digunakan oleh prodi lain.';
                } else {
                    $error = '❌ Gagal menambahkan prodi: ' . $e->getMessage();
                }
            }
        } else { 
            $error = '❌ Lengkapi semua kolom program studi.'; 
        }
    }

    // ================= EDIT PRODI =================
    if ($action === 'edit_prodi') {
        $id   = (int)($_POST['id_prodi'] ?? 0);
        $kode = mb_substr(trim($_POST['kode_prodi'] ?? ''), 0, 20);
        $nama = mb_substr(trim($_POST['nama_prodi'] ?? ''), 0, 150);
        $fid  = (int)($_POST['id_fakultas'] ?? 0);

        if ($id > 0 && $kode !== '' && $nama !== '' && $fid > 0) {
            try {
                $db->prepare("UPDATE prodi SET kode_prodi=?, nama_prodi=?, id_fakultas=? WHERE id_prodi=?")
                   ->execute([$kode, $nama, $fid, $id]);
                Logger::log('UPDATE', "Memperbarui prodi #$id: $nama ($kode)");
                $pesan = '✅ Program studi berhasil diperbarui.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || stripos($e->getMessage(), 'Duplicate') !== false) {
                    $error = '❌ Kode prodi sudah digunakan oleh prodi lain.';
                } else {
                    $error = '❌ Gagal memperbarui: ' . $e->getMessage();
                }
            }
        } else {
            $error = '❌ Lengkapi semua kolom program studi dengan benar.';
        }
    }

    // ================= HAPUS PRODI =================
    if ($action === 'delete_prodi') {
        $id = (int)($_POST['id_prodi'] ?? 0);
        try {
            // Ambil nama dulu untuk log
            $stmt = $db->prepare("SELECT nama_prodi FROM prodi WHERE id_prodi=?");
            $stmt->execute([$id]);
            $nama = $stmt->fetchColumn() ?: 'Unknown';
            
            $db->prepare("DELETE FROM prodi WHERE id_prodi=?")->execute([$id]);
            Logger::log('DELETE', 'Menghapus prodi: ' . $nama);
            $pesan = '✅ Program studi dihapus.';
        } catch (PDOException $e) {
            $error = '❌ Tidak bisa menghapus: prodi masih digunakan oleh data lain (akreditasi, audit, atau pengguna).';
        }
    }

    // ================= HAPUS FAKULTAS =================
    if ($action === 'delete_fakultas') {
        $id = (int)($_POST['id_fakultas'] ?? 0);
        try {
            $stmt = $db->prepare("SELECT nama_fakultas FROM fakultas WHERE id_fakultas=?");
            $stmt->execute([$id]);
            $nama = $stmt->fetchColumn() ?: 'Unknown';
            
            $db->prepare("DELETE FROM fakultas WHERE id_fakultas=?")->execute([$id]);
            Logger::log('DELETE', 'Menghapus fakultas: ' . $nama);
            $pesan = '✅ Fakultas dihapus.';
        } catch (PDOException $e) {
            $error = '❌ Tidak bisa menghapus: fakultas masih memiliki program studi aktif.';
        }
    }
}

// Mode edit (jika ada ?edit=ID)
$editProdi = null;
if (!empty($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM prodi WHERE id_prodi = ?");
    $st->execute([(int)$_GET['edit']]);
    $editProdi = $st->fetch();
}

$fakultas = $db->query("SELECT f.*, (SELECT COUNT(*) FROM prodi p WHERE p.id_fakultas = f.id_fakultas) AS jml_prodi FROM fakultas f ORDER BY f.nama_fakultas")->fetchAll();
$prodi = $db->query("SELECT p.*, f.nama_fakultas FROM prodi p LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas ORDER BY f.nama_fakultas, p.nama_prodi")->fetchAll();

$simTitle = 'Data Prodi & Fakultas';
$activeMenu = 'prodi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= $pesan ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<!-- Form Tambah / Edit -->
<div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;margin-bottom:28px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">🏛️ Tambah Fakultas</h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="add_fakultas">
            <div class="form-group"><label class="form-label">Nama Fakultas *</label>
                <input type="text" name="nama_fakultas" class="form-control" maxlength="150" placeholder="cth: Fakultas Teknik" required></div>
            <button type="submit" class="btn btn-primary" style="width:100%;">💾 Simpan Fakultas</button>
        </form>
    </div>

    <div class="card" style="<?= $editProdi ? 'border:2px solid var(--accent);' : '' ?>">
        <h3 style="margin-bottom:16px;"><?= $editProdi ? '✏️ Edit' : '🎓 Tambah' ?> Program Studi</h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="<?= $editProdi ? 'edit_prodi' : 'add_prodi' ?>">
            <?php if ($editProdi): ?><input type="hidden" name="id_prodi" value="<?= $editProdi['id_prodi'] ?>"><?php endif; ?>
            <div style="display:grid;grid-template-columns:1fr 2fr 1fr;gap:12px;">
                <div class="form-group"><label class="form-label">Kode *</label>
                    <input type="text" name="kode_prodi" class="form-control" maxlength="20" value="<?= Security::e($editProdi['kode_prodi'] ?? '') ?>" placeholder="IF" required></div>
                <div class="form-group"><label class="form-label">Nama Prodi *</label>
                    <input type="text" name="nama_prodi" class="form-control" maxlength="150" value="<?= Security::e($editProdi['nama_prodi'] ?? '') ?>" placeholder="Informatika" required></div>
                <div class="form-group"><label class="form-label">Fakultas *</label>
                    <select name="id_fakultas" class="form-control" required>
                        <option value="">— Pilih Fakultas —</option>
                        <?php foreach ($fakultas as $f): ?>
                            <option value="<?= $f['id_fakultas'] ?>" <?= ($editProdi['id_fakultas'] ?? '') == $f['id_fakultas'] ? 'selected' : '' ?>><?= Security::e($f['nama_fakultas']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="submit" class="btn <?= $editProdi ? 'btn-gold' : 'btn-primary' ?>">💾 <?= $editProdi ? 'Perbarui' : 'Simpan Prodi' ?></button>
                <?php if ($editProdi): ?><a href="/sim/admin/prodi.php" class="btn btn-outline">Batal</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Fakultas -->
<div class="card" style="margin-bottom:28px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;">🏛️ Daftar Fakultas</h3>
        <input type="text" id="cariFakultas" class="form-control" placeholder="🔍 Cari fakultas..." style="max-width:240px;">
    </div>
    <div class="table-wrapper">
        <table id="tabelFakultas">
            <thead><tr><th>Nama Fakultas</th><th>Jumlah Prodi</th><th style="width:80px;">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($fakultas)): ?><tr><td colspan="3" style="text-align:center;padding:30px;" class="text-muted">Belum ada fakultas.</td></tr><?php endif; ?>
            <?php foreach ($fakultas as $f): ?>
                <tr class="row-fakultas">
                    <td><strong><?= Security::e($f['nama_fakultas']) ?></strong></td>
                    <td><span class="badge badge-a"><?= (int)$f['jml_prodi'] ?> prodi</span></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus fakultas ini? Pastikan tidak ada prodi di dalamnya.');">
                            <input type="hidden" name="id_fakultas" value="<?= $f['id_fakultas'] ?>">
                            <?= Security::csrfField() ?>
                            <button name="action" value="delete_fakultas" class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);" title="Hapus">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tabel Prodi -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;">🎓 Daftar Program Studi</h3>
        <input type="text" id="cariProdi" class="form-control" placeholder="🔍 Cari kode/nama prodi..." style="max-width:280px;">
    </div>
    <div class="table-wrapper">
        <table id="tabelProdi">
            <thead><tr><th>Kode</th><th>Nama Prodi</th><th>Fakultas</th><th style="width:120px;">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($prodi)): ?><tr><td colspan="4" style="text-align:center;padding:30px;" class="text-muted">Belum ada program studi.</td></tr><?php endif; ?>
            <?php foreach ($prodi as $p): ?>
                <tr class="row-prodi">
                    <td><code style="font-size:12px;"><?= Security::e($p['kode_prodi']) ?></code></td>
                    <td><strong><?= Security::e($p['nama_prodi']) ?></strong></td>
                    <td><?= Security::e($p['nama_fakultas'] ?? '—') ?></td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="?edit=<?= $p['id_prodi'] ?>" class="btn btn-outline" style="padding:5px 10px;font-size:12px;" title="Edit">✏️</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus program studi ini? Tindakan tidak dapat dibatalkan jika masih ada data terkait.');">
                                <input type="hidden" name="id_prodi" value="<?= $p['id_prodi'] ?>">
                                <?= Security::csrfField() ?>
                                <button name="action" value="delete_prodi" class="btn btn-outline" style="padding:5px 10px;font-size:12px;color:var(--danger);" title="Hapus">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Pencarian real-time untuk Fakultas
(function() {
    var q = document.getElementById('cariFakultas');
    if (!q) return;
    q.addEventListener('input', function() {
        var term = q.value.toLowerCase();
        document.querySelectorAll('#tabelFakultas .row-fakultas').forEach(function(tr) {
            tr.style.display = tr.textContent.toLowerCase().indexOf(term) > -1 ? '' : 'none';
        });
    });
})();

// Pencarian real-time untuk Prodi
(function() {
    var q = document.getElementById('cariProdi');
    if (!q) return;
    q.addEventListener('input', function() {
        var term = q.value.toLowerCase();
        document.querySelectorAll('#tabelProdi .row-prodi').forEach(function(tr) {
            tr.style.display = tr.textContent.toLowerCase().indexOf(term) > -1 ? '' : 'none';
        });
    });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>