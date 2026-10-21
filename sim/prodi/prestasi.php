<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = ''; $error = '';
$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'Bank Prestasi';
$activeMenu = 'prestasi';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun belum terhubung ke prodi.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $file = null;
        if (!empty($_FILES['file']['name'])) {
            $up = Security::secureUpload($_FILES['file'], 'prestasi');
            if ($up['success']) $file = $up['path']; else $error = $up['message'];
        }
        if (!$error) {
            $db->prepare("INSERT INTO prestasi_prodi (id_prodi, jenis, judul, tingkat, tahun, penyelenggara, file_path) VALUES (?,?,?,?,?,?,?)")
               ->execute([$prodiId, $_POST['jenis'], trim($_POST['judul']), $_POST['tingkat'], (int)$_POST['tahun'], trim($_POST['penyelenggara']), $file]);
            $pesan = '✅ Prestasi berhasil dicatat.';
        }
    }

    if ($action === 'matriks') {
        $pr = $db->prepare("SELECT * FROM prestasi_prodi WHERE id_prestasi = ? AND id_prodi = ?");
        $pr->execute([(int)$_POST['id_prestasi'], $prodiId]);
        $p = $pr->fetch();
        if ($p) {
            $nomor = $p['jenis'] === 'Mahasiswa' ? 9 : 7; // Mahasiswa → K9, Dosen → K7
            $kr = $db->prepare("SELECT id_kriteria FROM kriteria_akreditasi WHERE nomor = ?");
            $kr->execute([$nomor]);
            $kid = $kr->fetch()['id_kriteria'] ?? null;
            if (!$kid) { $error = 'Kriteria akreditasi belum ada. Jalankan SQL seed 9 kriteria terlebih dahulu.'; }
            else {
                $db->prepare("INSERT INTO bukti_kriteria (id_prodi, id_kriteria, indikator, nama_bukti, file_path, status, tahun) VALUES (?,?,?,?,?,'Lengkap',?)")
                   ->execute([$prodiId, $kid, 'Prestasi ' . strtolower($p['jenis']) . ' tingkat ' . $p['tingkat'], 'Prestasi: ' . $p['judul'] . ' (' . $p['tahun'] . ')', $p['file_path'], $p['tahun']]);
                $pesan = "📌 Prestasi otomatis masuk Matriks Bukti Kriteria $nomor.";
            }
        }
    }

    if ($action === 'del') {
        $db->prepare("DELETE FROM prestasi_prodi WHERE id_prestasi = ? AND id_prodi = ?")->execute([(int)$_POST['id_prestasi'], $prodiId]);
        $pesan = '✅ Prestasi berhasil dihapus.';
    }
}

$jenisF = $_GET['jenis'] ?? 'all';
$q = "SELECT * FROM prestasi_prodi WHERE id_prodi = ?";
$params = [$prodiId];
if ($jenisF !== 'all') { $q .= " AND jenis = ?"; $params[] = $jenisF; }
$q .= " ORDER BY tahun DESC, id_prestasi DESC";
$stmt = $db->prepare($q); $stmt->execute($params);
$list = $stmt->fetchAll();

$stM = $db->prepare("SELECT COUNT(*) t FROM prestasi_prodi WHERE id_prodi = ? AND jenis = 'Mahasiswa'"); $stM->execute([$prodiId]); $nMhs = (int)$stM->fetch()['t'];
$stD = $db->prepare("SELECT COUNT(*) t FROM prestasi_prodi WHERE id_prodi = ? AND jenis = 'Dosen'"); $stD->execute([$prodiId]); $nDsn = (int)$stD->fetch()['t'];
$stI = $db->prepare("SELECT COUNT(*) t FROM prestasi_prodi WHERE id_prodi = ? AND tingkat = 'Internasional'"); $stI->execute([$prodiId]); $nIntl = (int)$stI->fetch()['t'];
?>

<style>
    .prestasi-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 28px 32px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .prestasi-header::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -80px; top: -100px; animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .prestasi-header h2 { font-size: 22px; margin: 0 0 8px; position: relative; font-weight: 800; }
    .prestasi-header p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; margin: 0; }
    
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
    .stat-icon.gold { background: linear-gradient(135deg, #F59E0B, #FBBF24); color: #fff; }
    .stat-icon.blue { background: linear-gradient(135deg, #3B82F6, #60A5FA); color: #fff; }
    .stat-icon.green { background: linear-gradient(135deg, #10B981, #34D399); color: #fff; }
    .stat-icon.red { background: linear-gradient(135deg, #EF4444, #F87171); color: #fff; }
    .stat-card h3 { font-size: 28px; margin: 0 0 4px; font-weight: 800; color: var(--text-dark); }
    .stat-card p { font-size: 12px; color: var(--text-muted); margin: 0; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
    
    .add-form-card {
        background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 28px;
        box-shadow: 0 4px 12px rgba(0,0,0,.05); border: 2px solid var(--border);
    }
    .add-form-card h3 { margin: 0 0 16px; font-size: 18px; display: flex; align-items: center; gap: 10px; }
    
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
    
    .badge-jenis {
        display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;
        color: #fff; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .badge-tingkat {
        display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700;
    }
</style>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="prestasi-header">
    <h2>🏆 Bank Prestasi Program Studi</h2>
    <p>Dokumentasikan dan kelola seluruh pencapaian mahasiswa dan dosen untuk mendukung akreditasi dan laporan kinerja.</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:28px;">
    <div class="stat-card">
        <div class="stat-icon gold">🏅</div>
        <h3><?= count($list) ?></h3>
        <p>Ditampilkan</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">🧑‍🎓</div>
        <h3><?= $nMhs ?></h3>
        <p>Prestasi Mahasiswa</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">🧑‍🏫</div>
        <h3><?= $nDsn ?></h3>
        <p>Prestasi Dosen</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">🌍</div>
        <h3><?= $nIntl ?></h3>
        <p>Tingkat Internasional</p>
    </div>
</div>

<div class="add-form-card">
    <h3>➕ Catat Prestasi Baru</h3>
    <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:16px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Jenis Prestasi</label>
            <select name="jenis" class="form-control">
                <option>Mahasiswa</option>
                <option>Dosen</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Judul Prestasi</label>
            <input type="text" name="judul" class="form-control" placeholder="cth: Juara 1 Hackathon Nasional" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tingkat</label>
            <select name="tingkat" class="form-control">
                <option>Prodi</option><option>Fakultas</option><option>Universitas</option>
                <option>Regional</option><option selected>Nasional</option><option>Internasional</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tahun</label>
            <input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Penyelenggara</label>
            <input type="text" name="penyelenggara" class="form-control" placeholder="cth: Kemdikbud">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Bukti (PDF/Gambar)</label>
            <div class="file-input-wrapper">
                <input type="file" name="file" accept="application/pdf,.jpg,.jpeg,.png">
                <div class="file-input-label"><span>📁</span> Pilih File</div>
            </div>
        </div>
        <button class="btn btn-gold" style="height:42px;font-weight:700;">💾 Simpan</button>
    </form>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3 style="margin:0;font-size:18px;">📋 Daftar Prestasi</h3>
        <form method="GET">
            <select name="jenis" class="form-control" style="width:auto;" onchange="this.form.submit()">
                <option value="all">Semua Jenis</option>
                <option value="Mahasiswa" <?= $jenisF === 'Mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                <option value="Dosen" <?= $jenisF === 'Dosen' ? 'selected' : '' ?>>Dosen</option>
            </select>
        </form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width:35%">Detail Prestasi</th>
                    <th style="width:15%;text-align:center;">Jenis</th>
                    <th style="width:15%;text-align:center;">Tingkat</th>
                    <th style="width:10%;text-align:center;">Tahun</th>
                    <th style="width:10%;text-align:center;">Bukti</th>
                    <th style="width:15%;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;">
                        <div style="font-size:48px;margin-bottom:12px;opacity:0.5;">🏆</div>
                        <p class="text-muted">Belum ada prestasi tercatat untuk program studi ini.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($list as $p): 
                    $jenisColor = $p['jenis'] === 'Mahasiswa' ? '#3B82F6' : '#10B981';
                    $tingkatColor = match($p['tingkat']) {
                        'Internasional' => '#8B5CF6',
                        'Nasional' => '#F59E0B',
                        'Regional' => '#06B6D4',
                        default => '#64748B'
                    };
                ?>
                <tr>
                    <td style="vertical-align:middle;">
                        <strong style="font-size:14px;color:var(--text-dark);"><?= Security::e($p['judul']) ?></strong><br>
                        <small class="text-muted"><?= Security::e($p['penyelenggara']) ?></small>
                    </td>
                    <td style="vertical-align:middle;text-align:center;">
                        <span class="badge-jenis" style="background:<?= $jenisColor ?>"><?= $p['jenis'] ?></span>
                    </td>
                    <td style="vertical-align:middle;text-align:center;">
                        <span class="badge-tingkat" style="background:<?= $tingkatColor ?>20;color:<?= $tingkatColor ?>"><?= Security::e($p['tingkat']) ?></span>
                    </td>
                    <td style="vertical-align:middle;text-align:center;font-weight:700;"><?= $p['tahun'] ?></td>
                    <td style="vertical-align:middle;text-align:center;">
                        <?php if ($p['file_path']): ?>
                            <a href="/download.php?file=<?= urlencode($p['file_path']) ?>&type=internal" target="_blank" class="btn btn-outline" style="padding:6px 10px;font-size:13px;" title="Unduh Bukti">📄</a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="vertical-align:middle;text-align:center;">
                        <div style="display:flex;gap:8px;justify-content:center;">
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="id_prestasi" value="<?= $p['id_prestasi'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="matriks">
                                <button class="btn btn-primary" style="padding:6px 12px;font-size:12px;" title="Masukkan ke Matriks Bukti (Mahasiswa→K9, Dosen→K7)">📌 Matriks</button>
                            </form>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Yakin ingin menghapus prestasi ini?');">
                                <input type="hidden" name="id_prestasi" value="<?= $p['id_prestasi'] ?>">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="del">
                                <button class="btn btn-outline" style="padding:6px 12px;font-size:12px;color:var(--danger);border-color:rgba(239,68,68,.3);" title="Hapus">🗑️</button>
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
        💡 <strong>Tips:</strong> Tombol <strong>📌 Matriks</strong> akan otomatis menyalin data prestasi ke modul Matriks Bukti Akreditasi (Mahasiswa → Kriteria 9, Dosen → Kriteria 7) dengan status <em>Lengkap</em>.
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
            label.innerHTML = `<span>📁</span> Pilih File`;
            label.style.borderColor = ''; label.style.color = ''; label.style.background = '';
        }
    });
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>