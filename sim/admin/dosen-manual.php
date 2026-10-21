<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

/* Deteksi kolom tabel dosen (aman untuk semua varian skema) */
$cols = array_column($db->query("SHOW COLUMNS FROM dosen")->fetchAll(), 'Field');
$namaCol = in_array('nama_dosen', $cols) ? 'nama_dosen' : (in_array('nama', $cols) ? 'nama' : null);
$jabCol  = in_array('jabatan_fungsional', $cols) ? 'jabatan_fungsional' : (in_array('jabatan', $cols) ? 'jabatan' : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= ADD DOSEN =================
    if ($action === 'add') {
        if (!$namaCol) { $error = 'Kolom nama tidak ditemukan di tabel dosen.'; }
        else {
            $nama   = trim($_POST['nama'] ?? '');
            $nidn   = trim($_POST['nidn'] ?? '');
            $idProdi = (int)($_POST['id_prodi'] ?? 0);

            // Validasi
            if ($nama === '' || $idProdi === 0) {
                $error = 'Nama lengkap dan Program Studi wajib diisi.';
            } elseif ($nidn !== '' && !preg_match('/^\d{10}$/', $nidn)) {
                $error = 'Format NIDN tidak valid. NIDN harus terdiri dari 10 digit angka.';
            } else {
                try {
                    $fields = ['id_prodi', $namaCol];
                    $vals   = [$idProdi, $nama];
                    if (in_array('nidn', $cols) && $nidn !== '') { $fields[] = 'nidn'; $vals[] = $nidn; }
                    if ($jabCol) { $fields[] = $jabCol; $vals[] = trim($_POST['jabatan'] ?? ''); }
                    if (in_array('status', $cols)) { $fields[] = 'status'; $vals[] = $_POST['status'] ?? 'Aktif'; }

                    $placeholders = implode(',', array_fill(0, count($fields), '?'));
                    $sql = "INSERT INTO dosen (" . implode(',', $fields) . ") VALUES ($placeholders)";
                    $db->prepare($sql)->execute($vals);

                    Logger::log('CREATE', 'Menambah dosen manual: ' . $nama);
                    $pesan = '✅ Dosen berhasil ditambahkan — langsung tersedia di EDOM & sistem lain.';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000 || stripos($e->getMessage(), 'Duplicate') !== false) {
                        $error = '❌ NIDN tersebut sudah terdaftar. Gunakan NIDN lain atau cari dosen di daftar.';
                    } else {
                        $error = '❌ Gagal menambahkan dosen: ' . $e->getMessage();
                    }
                }
            }
        }
    }

    // ================= EDIT DOSEN =================
    if ($action === 'edit') {
        $id = (int)($_POST['id_dosen'] ?? 0);
        if ($id > 0 && $namaCol) {
            try {
                $nama   = trim($_POST['nama'] ?? '');
                $nidn   = trim($_POST['nidn'] ?? '');
                $idProdi = (int)($_POST['id_prodi'] ?? 0);

                if ($nama === '' || $idProdi === 0) {
                    $error = 'Nama lengkap dan Program Studi wajib diisi.';
                } elseif ($nidn !== '' && !preg_match('/^\d{10}$/', $nidn)) {
                    $error = 'Format NIDN tidak valid. NIDN harus terdiri dari 10 digit angka.';
                } else {
                    $fields = ['id_prodi' => $idProdi, $namaCol => $nama];
                    if (in_array('nidn', $cols)) $fields['nidn'] = ($nidn !== '') ? $nidn : null;
                    if ($jabCol) $fields[$jabCol] = trim($_POST['jabatan'] ?? '');
                    if (in_array('status', $cols)) $fields['status'] = $_POST['status'] ?? 'Aktif';

                    $set  = implode(', ', array_map(function($k) { return "$k = ?"; }, array_keys($fields)));
                    $vals = array_values($fields);
                    $vals[] = $id;

                    $db->prepare("UPDATE dosen SET $set WHERE id_dosen = ?")->execute($vals);
                    Logger::log('UPDATE', "Memperbarui data dosen #$id: $nama");
                    $pesan = '✅ Data dosen berhasil diperbarui.';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || stripos($e->getMessage(), 'Duplicate') !== false) {
                    $error = '❌ NIDN tersebut sudah digunakan oleh dosen lain.';
                } else {
                    $error = '❌ Gagal memperbarui: ' . $e->getMessage();
                }
            }
        }
    }

    // ================= DELETE DOSEN (dengan proteksi) =================
    if ($action === 'del') {
        $id = (int)($_POST['id_dosen'] ?? 0);
        if ($id > 0) {
            try {
                // Cek apakah dosen memiliki kelas EDOM aktif
                $jmlKelas = (int)$db->query("SELECT COUNT(*) FROM edom_kelas WHERE id_dosen = $id")->fetchColumn();
                if ($jmlKelas > 0) {
                    $error = "❌ Dosen tidak dapat dihapus karena masih memiliki $jmlKelas kelas EDOM aktif. Nonaktifkan kelas terlebih dahulu.";
                } else {
                    $stmt = $db->prepare("DELETE FROM dosen WHERE id_dosen = ?");
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        Logger::log('DELETE', "Menghapus dosen #$id");
                        $pesan = '✅ Dosen berhasil dihapus.';
                    } else {
                        $error = '❌ Dosen tidak ditemukan.';
                    }
                }
            } catch (PDOException $e) {
                $error = '❌ Gagal menghapus: ' . $e->getMessage();
            }
        }
    }
}

$list = $db->query("SELECT d.*, p.nama_prodi FROM dosen d LEFT JOIN prodi p ON d.id_prodi = p.id_prodi ORDER BY d.id_dosen DESC LIMIT 500")->fetchAll();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();

$simTitle = 'Kelola Dosen';
$activeMenu = 'dosenman';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:6px;">➕ Tambah Dosen Manual</h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:20px;">Untuk kampus tanpa file Feeder — dosen langsung masuk ke EDOM, matriks bukti, dan penugasan audit.</p>
    <form method="POST" style="display:grid;grid-template-columns:1.4fr 1fr 1.6fr 1.2fr .9fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Program Studi *</label>
            <select name="id_prodi" class="form-control" required>
                <option value="">— Pilih Prodi —</option>
                <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">NIDN <small class="text-muted">(10 digit)</small></label>
            <input type="text" name="nidn" class="form-control" placeholder="0012058801" maxlength="10" pattern="\d{10}" title="NIDN harus 10 digit angka"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Nama Lengkap & Gelar *</label>
            <input type="text" name="nama" class="form-control" placeholder="cth: Dr. Maria Bata, M.M." required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Jabatan Fungsional</label>
            <select name="jabatan" class="form-control">
                <option>Tenaga Pengajar</option><option>Asisten Ahli</option><option>Lektor</option>
                <option>Lektor Kepala</option><option>Guru Besar</option>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Status</label>
            <select name="status" class="form-control"><option>Aktif</option><option>Nonaktif</option></select></div>
        <button class="btn btn-gold">💾 Simpan</button>
    </form>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:16px;flex-wrap:wrap;">
        <h3 style="margin:0;">📇 Daftar Dosen (<?= count($list) ?>)</h3>
        <input type="text" id="dosenSearch" class="form-control" placeholder="🔍 Cari nama / NIDN / prodi..." style="max-width:300px;">
    </div>
    <div class="table-wrapper">
        <table id="tabelDosen">
            <thead><tr><th>NIDN</th><th>Nama</th><th>Prodi</th><th>Jabatan</th><th>Status</th><th style="width:130px;">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?><tr class="dosen-row"><td colspan="6" style="text-align:center;padding:30px;" class="text-muted">Belum ada dosen.</td></tr><?php endif; ?>
            <?php foreach ($list as $d): ?>
                <tr class="dosen-row">
                    <td><?= Security::e($d['nidn'] ?? '—') ?></td>
                    <td><strong><?= Security::e($d[$namaCol] ?? '') ?></strong></td>
                    <td><?= Security::e($d['nama_prodi'] ?? '—') ?></td>
                    <td><?= Security::e($d[$jabCol] ?? '—') ?></td>
                    <td>
                        <span style="padding:3px 10px;border-radius:50px;font-size:11px;font-weight:700;background:<?= ($d['status'] ?? '') === 'Aktif' ? 'rgba(16,185,129,.15);color:#059669;' : 'rgba(107,114,128,.15);color:#6B7280;' ?>">
                            <?= Security::e($d['status'] ?? '—') ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-outline btn-edit-dosen" style="padding:4px 10px;font-size:11px;"
                                data-id="<?= $d['id_dosen'] ?>"
                                data-nidn="<?= Security::e($d['nidn'] ?? '') ?>"
                                data-nama="<?= Security::e($d[$namaCol] ?? '') ?>"
                                data-prodi="<?= (int)$d['id_prodi'] ?>"
                                data-jabatan="<?= Security::e($d[$jabCol] ?? '') ?>"
                                data-status="<?= Security::e($d['status'] ?? 'Aktif') ?>">✏️</button>
                            <form method="POST" onsubmit="return confirm('Hapus dosen ini? Tindakan tidak dapat dibatalkan.');">
                                <input type="hidden" name="id_dosen" value="<?= $d['id_dosen'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="del">
                                <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit Dosen -->
<div id="modalEditDosen" style="display:none;position:fixed;inset:0;background:rgba(4,10,20,.62);backdrop-filter:blur(5px);z-index:4000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:var(--bg-card);border-radius:18px;box-shadow:var(--shadow-lg);border:1px solid var(--border);width:min(560px,100%);overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;">✏️ Edit Data Dosen</h3>
            <button onclick="document.getElementById('modalEditDosen').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);">×</button>
        </div>
        <form method="POST" style="padding:22px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_dosen" id="edit_id_dosen">
            <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Program Studi *</label>
                <select name="id_prodi" id="edit_id_prodi" class="form-control" required>
                    <option value="">— Pilih Prodi —</option>
                    <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label">NIDN</label>
                <input type="text" name="nidn" id="edit_nidn" class="form-control" maxlength="10" pattern="\d{10}"></div>
            <div class="form-group"><label class="form-label">Status</label>
                <select name="status" id="edit_status" class="form-control"><option>Aktif</option><option>Nonaktif</option></select></div>
            <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Nama Lengkap & Gelar *</label>
                <input type="text" name="nama" id="edit_nama" class="form-control" required></div>
            <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Jabatan Fungsional</label>
                <select name="jabatan" id="edit_jabatan" class="form-control">
                    <option>Tenaga Pengajar</option><option>Asisten Ahli</option><option>Lektor</option>
                    <option>Lektor Kepala</option><option>Guru Besar</option>
                </select></div>
            <div style="grid-column:1/-1;display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalEditDosen').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Pencarian real-time
(function(){
    var search = document.getElementById('dosenSearch');
    if (!search) return;
    search.addEventListener('input', function(){
        var q = search.value.toLowerCase();
        document.querySelectorAll('#tabelDosen .dosen-row').forEach(function(tr){
            if (tr.querySelector('td[colspan]')) return;
            tr.style.display = tr.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
        });
    });
})();

// Modal Edit Dosen
(function(){
    document.querySelectorAll('.btn-edit-dosen').forEach(function(btn){
        btn.addEventListener('click', function(){
            document.getElementById('edit_id_dosen').value = btn.dataset.id;
            document.getElementById('edit_id_prodi').value = btn.dataset.prodi;
            document.getElementById('edit_nidn').value = btn.dataset.nidn;
            document.getElementById('edit_nama').value = btn.dataset.nama;
            document.getElementById('edit_jabatan').value = btn.dataset.jabatan;
            document.getElementById('edit_status').value = btn.dataset.status;
            document.getElementById('modalEditDosen').style.display = 'flex';
        });
    });
    document.getElementById('modalEditDosen').addEventListener('click', function(e){
        if (e.target === this) this.style.display = 'none';
    });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>