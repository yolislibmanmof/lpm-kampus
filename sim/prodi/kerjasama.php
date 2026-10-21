<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = ''; $error = '';

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'Kerja Sama / MoU';
$activeMenu = 'kerjasama';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun belum terhubung ke prodi.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $file = null;
        if (!empty($_FILES['file']['name'])) {
            $up = Security::secureUpload($_FILES['file'], 'kerjasama');
            if ($up['success']) $file = $up['path']; else $error = $up['message'];
        }
        if (!$error) {
            if ($action === 'add') {
                $db->prepare("INSERT INTO kerjasama_prodi (id_prodi, mitra, jenis, tingkat, nomor_mou, tanggal_mulai, tanggal_berakhir, tindak_lanjut, file_path) VALUES (?,?,?,?,?,?,?,?,?)")
                   ->execute([$prodiId, trim($_POST['mitra']), trim($_POST['jenis']), $_POST['tingkat'], trim($_POST['nomor_mou']), $_POST['tanggal_mulai'] ?: null, $_POST['tanggal_berakhir'] ?: null, trim($_POST['tindak_lanjut']), $file]);
                $pesan = '✅ Kerja sama berhasil dicatat.';
            } else {
                if (!$file) { 
                    $old = $db->prepare("SELECT file_path FROM kerjasama_prodi WHERE id_kerjasama=?"); 
                    $old->execute([(int)$_POST['id_kerjasama']]); 
                    $file = $old->fetch()['file_path']; 
                }
                $db->prepare("UPDATE kerjasama_prodi SET mitra=?, jenis=?, tingkat=?, nomor_mou=?, tanggal_mulai=?, tanggal_berakhir=?, tindak_lanjut=?, file_path=? WHERE id_kerjasama=? AND id_prodi=?")
                   ->execute([trim($_POST['mitra']), trim($_POST['jenis']), $_POST['tingkat'], trim($_POST['nomor_mou']), $_POST['tanggal_mulai'] ?: null, $_POST['tanggal_berakhir'] ?: null, trim($_POST['tindak_lanjut']), $file, (int)$_POST['id_kerjasama'], $prodiId]);
                $pesan = '✅ Data kerja sama berhasil diperbarui.';
            }
        }
    }
    if ($action === 'matriks') {
        $ks = $db->prepare("SELECT * FROM kerjasama_prodi WHERE id_kerjasama = ? AND id_prodi = ?");
        $ks->execute([(int)$_POST['id_kerjasama'], $prodiId]);
        $k = $ks->fetch();
        if ($k) {
            $kr = $db->prepare("SELECT id_kriteria FROM kriteria_akreditasi WHERE nomor = 6"); 
            $kr->execute([]);
            $kid = $kr->fetch()['id_kriteria'] ?? null;
            if ($kid) {
                $db->prepare("INSERT INTO bukti_kriteria (id_prodi, id_kriteria, indikator, nama_bukti, file_path, status, tahun) VALUES (?,?,?,?,?,'Lengkap',?)")
                   ->execute([$prodiId, $kid, 'Kerja sama tingkat ' . $k['tingkat'], 'MoU: ' . $k['mitra'] . ' (' . ($k['nomor_mou'] ?: '-') . ')', $k['file_path'], date('Y')]);
                $pesan = '📌 MoU berhasil ditambahkan ke Matriks Bukti Kriteria 6.';
            } else {
                $error = 'Kriteria akreditasi K6 belum ditemukan di database.';
            }
        }
    }
    if ($action === 'del') {
        $db->prepare("DELETE FROM kerjasama_prodi WHERE id_kerjasama = ? AND id_prodi = ?")->execute([(int)$_POST['id_kerjasama'], $prodiId]);
        $pesan = '✅ Data kerja sama berhasil dihapus.';
    }
}

$list = $db->prepare("SELECT * FROM kerjasama_prodi WHERE id_prodi = ? ORDER BY tanggal_berakhir ASC");
$list->execute([$prodiId]); 
$list = $list->fetchAll();

$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+90 days'));
$nAktif = count(array_filter($list, fn($k) => !$k['tanggal_berakhir'] || $k['tanggal_berakhir'] >= $today));
$nIntl = count(array_filter($list, fn($k) => $k['tingkat'] === 'Internasional'));
$nSoon = count(array_filter($list, fn($k) => $k['tanggal_berakhir'] && $k['tanggal_berakhir'] <= $soon && $k['tanggal_berakhir'] >= $today));

$edit = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM kerjasama_prodi WHERE id_kerjasama = ? AND id_prodi = ?");
    $st->execute([(int)$_GET['edit'], $prodiId]); 
    $edit = $st->fetch();
}
?>

<style>
    .ks-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 28px 32px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .ks-header::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -80px; top: -100px; animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .ks-header h2 { font-size: 22px; margin: 0 0 8px; position: relative; font-weight: 800; }
    .ks-header p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; margin: 0; }
    
    .stat-card {
        background: #fff; border-radius: 12px; padding: 20px; text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,.05); border: 1px solid var(--border);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,.08); }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 12px; margin: 0 auto 12px;
        display: grid; place-items: center; font-size: 24px;
    }
    .stat-icon.green { background: linear-gradient(135deg, #10B981, #34D399); color: #fff; }
    .stat-icon.blue { background: linear-gradient(135deg, #3B82F6, #60A5FA); color: #fff; }
    .stat-icon.gold { background: linear-gradient(135deg, #F59E0B, #FBBF24); color: #fff; }
    .stat-icon.red { background: linear-gradient(135deg, #EF4444, #F87171); color: #fff; }
    .stat-card h3 { font-size: 28px; margin: 0 0 4px; font-weight: 800; color: var(--text-dark); }
    .stat-card p { font-size: 12px; color: var(--text-muted); margin: 0; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

    .form-card {
        background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 28px;
        box-shadow: 0 4px 12px rgba(0,0,0,.05); border: 2px solid var(--border);
    }
    .form-card h3 { margin: 0 0 16px; font-size: 18px; display: flex; align-items: center; gap: 10px; }

    .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; }
    .file-input-wrapper input[type=file] {
        position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer;
    }
    .file-input-label {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 10px; border: 2px dashed var(--border); border-radius: 8px;
        color: var(--text-muted); font-size: 13px; transition: all 0.3s; background: var(--bg-light);
    }
    .file-input-wrapper:hover .file-input-label {
        border-color: var(--accent); color: var(--accent); background: rgba(201,162,39,.05);
    }

    .badge-tingkat {
        display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700;
    }
    .tingkat-intl { background: #8B5CF6; color: #fff; }
    .tingkat-nas { background: #F59E0B; color: #fff; }
    .tingkat-reg { background: #06B6D4; color: #fff; }
    .tingkat-lok { background: #64748B; color: #fff; }
</style>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="ks-header">
    <h2>🤝 Manajemen Kerja Sama & MoU</h2>
    <p>Kelola dan pantau masa berlaku perjanjian kerja sama program studi dengan mitra internal maupun eksternal.</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:28px;">
    <div class="stat-card">
        <div class="stat-icon green">🤝</div>
        <h3><?= $nAktif ?></h3>
        <p>MoU Aktif</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">🌍</div>
        <h3><?= $nIntl ?></h3>
        <p>Tingkat Internasional</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $nSoon > 0 ? 'red' : 'gold' ?>">⏰</div>
        <h3><?= $nSoon ?></h3>
        <p>Perlu Perpanjangan (&lt;90 hari)</p>
    </div>
</div>

<div class="form-card">
    <h3><?= $edit ? '✏️ Edit Data Kerja Sama' : '➕ Catat Kerja Sama / MoU Baru' ?></h3>
    <?php if ($edit): ?><a href="/sim/prodi/kerjasama.php" class="btn btn-outline" style="padding:6px 14px;font-size:12px;margin-bottom:16px;display:inline-flex;align-items:center;gap:6px;">✕ Batal Edit</a><?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:16px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?><input type="hidden" name="id_kerjasama" value="<?= $edit['id_kerjasama'] ?>"><?php endif; ?>
        
        <div class="form-group" style="margin:0;">
            <label class="form-label">Nama Mitra *</label>
            <input type="text" name="mitra" class="form-control" value="<?= Security::e($edit['mitra'] ?? '') ?>" placeholder="cth: PT Telkom Indonesia" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Jenis Mitra</label>
            <input type="text" name="jenis" class="form-control" value="<?= Security::e($edit['jenis'] ?? '') ?>" placeholder="Industri / Pemda / PT">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tingkat Kerja Sama</label>
            <select name="tingkat" class="form-control">
                <?php foreach (['Lokal','Regional','Nasional','Internasional'] as $t): ?>
                    <option <?= ($edit['tingkat'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Nomor MoU / PKS</label>
            <input type="text" name="nomor_mou" class="form-control" value="<?= Security::e($edit['nomor_mou'] ?? '') ?>" placeholder="cth: 123/UN.X/2024">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" class="form-control" value="<?= Security::e($edit['tanggal_mulai'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tanggal Berakhir</label>
            <input type="date" name="tanggal_berakhir" class="form-control" value="<?= Security::e($edit['tanggal_berakhir'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Bentuk Tindak Lanjut</label>
            <input type="text" name="tindak_lanjut" class="form-control" value="<?= Security::e($edit['tindak_lanjut'] ?? '') ?>" placeholder="Magang / Dosen Tamu / Riset">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">File Dokumen (PDF)</label>
            <div class="file-input-wrapper">
                <input type="file" name="file" accept="application/pdf">
                <div class="file-input-label"><span>📁</span> <?= $edit && $edit['file_path'] ? 'Ganti File' : 'Pilih File' ?></div>
            </div>
        </div>
        <button class="btn btn-gold" style="height:42px;font-weight:700;">💾 <?= $edit ? 'Perbarui' : 'Simpan' ?></button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Daftar Kerja Sama Program Studi</h3>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width:25%">Mitra & Jenis</th>
                    <th style="width:12%;text-align:center;">Tingkat</th>
                    <th style="width:15%">No. MoU / PKS</th>
                    <th style="width:15%;text-align:center;">Masa Berlaku</th>
                    <th style="width:20%">Tindak Lanjut</th>
                    <th style="width:13%;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;">
                        <div style="font-size:48px;margin-bottom:12px;opacity:0.5;">🤝</div>
                        <p class="text-muted">Belum ada data kerja sama yang tercatat.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($list as $k):
                    $exp = $k['tanggal_berakhir'] && $k['tanggal_berakhir'] <= $soon;
                    $expired = $k['tanggal_berakhir'] && $k['tanggal_berakhir'] < $today;
                    
                    $tingkatClass = match($k['tingkat']) {
                        'Internasional' => 'tingkat-intl',
                        'Nasional' => 'tingkat-nas',
                        'Regional' => 'tingkat-reg',
                        default => 'tingkat-lok'
                    };
                ?>
                <tr style="<?= $expired ? 'opacity:0.6;background:#FEF2F2;' : '' ?>">
                    <td style="vertical-align:middle;">
                        <strong style="font-size:14px;color:var(--text-dark);"><?= Security::e($k['mitra']) ?></strong><br>
                        <small class="text-muted"><?= Security::e($k['jenis']) ?: 'Umum' ?></small>
                    </td>
                    <td style="vertical-align:middle;text-align:center;">
                        <span class="badge-tingkat <?= $tingkatClass ?>"><?= $k['tingkat'] ?></span>
                    </td>
                    <td style="vertical-align:middle;"><?= Security::e($k['nomor_mou']) ?: '<span class="text-muted">—</span>' ?></td>
                    <td style="vertical-align:middle;text-align:center;">
                        <?php if ($k['tanggal_berakhir']): ?>
                            <?= date('d M Y', strtotime($k['tanggal_berakhir'])) ?>
                            <?php if ($expired): ?>
                                <br><span class="badge" style="background:#FEE2E2;color:#991B1B;font-size:10px;">⛔ Kadaluarsa</span>
                            <?php elseif ($exp): ?>
                                <br><span class="badge" style="background:#FEF3C7;color:#92400E;font-size:10px;">⏰ Segera perpanjang</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">Tanpa batas</span>
                        <?php endif; ?>
                    </td>
                    <td style="vertical-align:middle;font-size:13px;"><?= Security::e($k['tindak_lanjut']) ?: '<span class="text-muted">—</span>' ?></td>
                    <td style="vertical-align:middle;text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="id_kerjasama" value="<?= $k['id_kerjasama'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="matriks">
                                <button class="btn btn-primary" style="padding:6px 10px;font-size:11px;" title="Tambah ke Matriks Bukti Kriteria 6">📌 K6</button>
                            </form>
                            <a href="?edit=<?= $k['id_kerjasama'] ?>" class="btn btn-outline" style="padding:6px 10px;font-size:11px;" title="Edit">✏️</a>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Yakin ingin menghapus data kerja sama ini?');">
                                <input type="hidden" name="id_kerjasama" value="<?= $k['id_kerjasama'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="del">
                                <button class="btn btn-outline" style="padding:6px 10px;font-size:11px;color:var(--danger);border-color:rgba(239,68,68,.3);" title="Hapus">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="font-size:12.5px;margin-top:16px;padding:12px;background:var(--bg-light);border-radius:8px;border-left:3px solid var(--accent);">
        💡 <strong>Tips:</strong> Tombol <strong>📌 K6</strong> akan otomatis menyalin data MoU ini ke modul Matriks Bukti Akreditasi (Kriteria 6: Kerja Sama) dengan status <em>Lengkap</em>.
    </p>
</div>

<script>
document.querySelectorAll('.file-input-wrapper input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        const label = this.nextElementSibling;
        if (this.files.length > 0) {
            label.innerHTML = `<span>📄</span> ${this.files[0].name}`;
            label.style.borderColor = 'var(--success)';
            label.style.color = 'var(--success)';
            label.style.background = 'rgba(16,185,129,.05)';
        } else {
            label.innerHTML = `<span>📁</span> <?= $edit && $edit['file_path'] ? 'Ganti File' : 'Pilih File' ?>`;
            label.style.borderColor = ''; label.style.color = ''; label.style.background = '';
        }
    });
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>