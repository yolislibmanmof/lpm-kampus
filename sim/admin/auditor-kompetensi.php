<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    if (($_POST['action'] ?? '') === 'add') {
        $sert = null;
        if (!empty($_FILES['sertifikat']['name'])) {
            $up = Security::secureUpload($_FILES['sertifikat'], 'sertifikat');
            if ($up['success']) $sert = $up['path']; else $error = $up['message'];
        }
        if (!$error) {
            $db->prepare("INSERT INTO auditor_kompetensi (id_user, jenis_pelatihan, penyelenggara, tahun, sertifikat) VALUES (?,?,?,?,?)")
               ->execute([(int)$_POST['id_user'], trim($_POST['jenis_pelatihan']), trim($_POST['penyelenggara']), (int)$_POST['tahun'], $sert]);
            Logger::log('UPDATE', 'Menambah rekam kompetensi auditor');
            $pesan = '✅ Kompetensi tercatat.';
        }
    }
    if (($_POST['action'] ?? '') === 'delete') {
        $db->prepare("DELETE FROM auditor_kompetensi WHERE id_kompetensi = ?")->execute([(int)$_POST['id_kompetensi']]);
        $pesan = '✅ Rekaman dihapus.';
    }
}

$auditors = $db->query("SELECT u.id_user, u.nama_lengkap,
    (SELECT COUNT(*) FROM auditor_kompetensi k WHERE k.id_user = u.id_user) jml,
    (SELECT MAX(tahun) FROM auditor_kompetensi k WHERE k.id_user = u.id_user) terakhir
    FROM users u WHERE u.id_role = 4 ORDER BY u.nama_lengkap")->fetchAll();

$riwayat = $db->query("SELECT k.*, u.nama_lengkap FROM auditor_kompetensi k JOIN users u ON k.id_user = u.id_user ORDER BY k.created_at DESC LIMIT 100")->fetchAll();

$simTitle = 'Kompetensi Auditor';
$activeMenu = 'komp';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">🎖️ Catat Pelatihan / Sertifikasi Auditor</h3>
    <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1.4fr 2fr 1.4fr .7fr 1fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Auditor</label>
            <select name="id_user" class="form-control" required>
                <?php foreach ($auditors as $a): ?><option value="<?= $a['id_user'] ?>"><?= Security::e($a['nama_lengkap']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Jenis Pelatihan</label>
            <input type="text" name="jenis_pelatihan" class="form-control" placeholder="cth: Pelatihan Auditor AMI Tingkat Nasional" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Penyelenggara</label>
            <input type="text" name="penyelenggara" class="form-control" placeholder="cth: LLDikti / Kemdikbud"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tahun</label>
            <input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Sertifikat (PDF)</label>
            <input type="file" name="sertifikat" class="form-control" accept="application/pdf"></div>
        <button class="btn btn-primary">➕</button>
    </form>
</div>

<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:24px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">👥 Peta Kompetensi Auditor</h3>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($auditors as $a): ?>
            <div style="padding:14px;background:var(--bg-light);border-radius:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;">
                <div><strong style="font-size:14px;"><?= Security::e($a['nama_lengkap']) ?></strong>
                    <p class="text-muted" style="font-size:12.5px;"><?= $a['jml'] ?> pelatihan • terakhir <?= $a['terakhir'] ?? '—' ?></p></div>
                <span class="badge <?= $a['jml'] > 0 ? 'badge-unggul' : 'badge-baik' ?>"><?= $a['jml'] > 0 ? '✅ Kompeten' : '⚠️ Perlu Pelatihan' ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-muted" style="font-size:12.5px;margin-top:14px;">💡 Saat menjadwalkan AMI, prioritaskan auditor bersertifikat & hindari konflik kepentingan (jangan mengaudit prodi asalnya).</p>
    </div>

    <div class="card">
        <h3 style="margin-bottom:16px;">📜 Riwayat Pelatihan</h3>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Auditor</th><th>Pelatihan</th><th>Tahun</th><th>Sertifikat</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($riwayat)): ?><tr><td colspan="5" style="text-align:center;padding:30px;" class="text-muted">Belum ada rekaman.</td></tr><?php endif; ?>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td><strong><?= Security::e($r['nama_lengkap']) ?></strong></td>
                        <td><?= Security::e($r['jenis_pelatihan']) ?><br><small class="text-muted"><?= Security::e($r['penyelenggara']) ?></small></td>
                        <td><?= $r['tahun'] ?></td>
                        <td><?= $r['sertifikat'] ? '<a href="/download.php?file=' . urlencode($r['sertifikat']) . '&type=internal" target="_blank" style="color:var(--primary);font-weight:700;">📄 Lihat</a>' : '—' ?></td>
                        <td><form method="POST" onsubmit="return confirm('Hapus?');"><input type="hidden" name="id_kompetensi" value="<?= $r['id_kompetensi'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>