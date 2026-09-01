<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $tahun = (int)($_POST['tahun'] ?? date('Y'));
        $nama = trim($_POST['nama_siklus'] ?? '') ?: ('AMI ' . $tahun);
        $chk = $db->prepare("SELECT COUNT(*) c FROM instrumen_tahun WHERE tahun = ?");
        $chk->execute([$tahun]);
        if ($chk->fetch()['c']) { $error = 'Instrumen tahun ' . $tahun . ' sudah ada.'; }
        else {
            $db->prepare("INSERT INTO instrumen_tahun (tahun, nama_siklus) VALUES (?,?)")->execute([$tahun, $nama]);
            Logger::log('UPDATE', 'Membuat instrumen AMI tahun ' . $tahun);
            $pesan = '✅ Instrumen dibuat. Isi butir atau generate template.';
        }
    }

    if ($action === 'template') {
        $idT = (int)$_POST['id_instrumen_tahun'];
        $stds = $db->query("SELECT id_standar FROM standar_mutu ORDER BY kode_standar")->fetchAll();
        $n = 0;
        foreach ($stds as $s) {
            $defaults = [
                ['Apakah kebijakan/dokumen standar telah ditetapkan dan disosialisasikan?', 'Telaah Dokumen', 'SK, kebijakan, bukti sosialisasi'],
                ['Apakah pelaksanaan kegiatan sesuai standar? Sebutkan bukti pelaksanaannya.', 'Observasi & Wawancara', 'Laporan kegiatan, notulen'],
                ['Apakah evaluasi, analisis capaian, dan tindakan perbaikan terdokumentasi?', 'Telaah Dokumen', 'Laporan evaluasi, notulen RTM'],
            ];
            foreach ($defaults as $d) {
                $db->prepare("INSERT INTO instrumen_butir (id_instrumen_tahun, id_standar, butir, metode, dokumen_diperlukan, urutan) VALUES (?,?,?,?,?,?)")
                   ->execute([$idT, $s['id_standar'], $d[0], $d[1], $d[2], ++$n]);
            }
        }
        $pesan = '✅ Template 3 butir per standar berhasil digenerate.';
    }

    if ($action === 'copy') {
        $src = (int)$_POST['id_sumber'];
        $tahunBaru = (int)$_POST['tahun_baru'];
        $namaBaru = trim($_POST['nama_baru'] ?? '') ?: ('AMI ' . $tahunBaru);
        $chk = $db->prepare("SELECT COUNT(*) c FROM instrumen_tahun WHERE tahun = ?");
        $chk->execute([$tahunBaru]);
        if ($chk->fetch()['c']) { $error = 'Instrumen tahun ' . $tahunBaru . ' sudah ada.'; }
        else {
            $db->prepare("INSERT INTO instrumen_tahun (tahun, nama_siklus, status) VALUES (?,'Draft','Draft')")->execute([$tahunBaru]);
            $newId = (int)$db->lastInsertId();
            $db->prepare("UPDATE instrumen_tahun SET nama_siklus = ? WHERE id_instrumen_tahun = ?")->execute([$namaBaru, $newId]);
            $db->prepare("INSERT INTO instrumen_butir (id_instrumen_tahun, id_standar, butir, metode, dokumen_diperlukan, urutan)
                          SELECT ?, id_standar, butir, metode, dokumen_diperlukan, urutan FROM instrumen_butir WHERE id_instrumen_tahun = ?")
               ->execute([$newId, $src]);
            Logger::log('UPDATE', "Menyalin instrumen ke tahun $tahunBaru");
            $pesan = "✅ Instrumen disalin ke tahun $tahunBaru (status Draft).";
        }
    }

    if ($action === 'add_butir') {
        $db->prepare("INSERT INTO instrumen_butir (id_instrumen_tahun, id_standar, butir, metode, dokumen_diperlukan, urutan) VALUES (?,?,?,?,?,?)")
           ->execute([(int)$_POST['id_instrumen_tahun'], (int)$_POST['id_standar'], trim($_POST['butir']), trim($_POST['metode']), trim($_POST['dokumen_diperlukan']), (int)($_POST['urutan'] ?? 0)]);
        $pesan = '✅ Butir ditambahkan.';
    }

    if ($action === 'del_butir') {
        $db->prepare("DELETE FROM instrumen_butir WHERE id_butir = ?")->execute([(int)$_POST['id_butir']]);
        $pesan = '✅ Butir dihapus.';
    }

    if ($action === 'status') {
        $db->prepare("UPDATE instrumen_tahun SET status = CASE status WHEN 'Aktif' THEN 'Arsip' WHEN 'Arsip' THEN 'Draft' ELSE 'Aktif' END WHERE id_instrumen_tahun = ?")
           ->execute([(int)$_POST['id_instrumen_tahun']]);
        $pesan = '✅ Status diubah (Draft → Aktif → Arsip).';
    }

    if ($action === 'delete') {
        $db->prepare("DELETE FROM instrumen_tahun WHERE id_instrumen_tahun = ?")->execute([(int)$_POST['id_instrumen_tahun']]);
        Logger::log('DELETE', 'Menghapus instrumen #' . (int)$_POST['id_instrumen_tahun']);
        $pesan = '✅ Instrumen dihapus.';
    }
}

$detail = null;
$detailId = (int)($_GET['detail'] ?? 0);
if ($detailId) {
    $st = $db->prepare("SELECT * FROM instrumen_tahun WHERE id_instrumen_tahun = ?");
    $st->execute([$detailId]);
    $detail = $st->fetch();
}

$simTitle = 'Instrumen AMI';
$activeMenu = 'instrumen';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<?php if (!$detail):
    $list = $db->query("SELECT t.*, (SELECT COUNT(*) FROM instrumen_butir b WHERE b.id_instrumen_tahun = t.id_instrumen_tahun) jml FROM instrumen_tahun t ORDER BY t.tahun DESC")->fetchAll();
?>
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Buat Instrumen Tahun Baru</h3>
    <form method="POST" style="display:grid;grid-template-columns:1fr 2fr auto;gap:16px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="create">
        <div class="form-group" style="margin:0;"><label class="form-label">Tahun</label>
            <input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Nama Siklus</label>
            <input type="text" name="nama_siklus" class="form-control" placeholder="cth: AMI 2026/2027"></div>
        <button class="btn btn-primary">💾 Buat</button>
    </form>
</div>

<div style="display:flex;flex-direction:column;gap:16px;">
    <?php if (empty($list)): ?>
        <div class="card" style="text-align:center;"><div style="font-size:52px;">🧾</div><p class="text-muted">Belum ada instrumen. Buat tahun pertama di atas.</p></div>
    <?php endif; ?>
    <?php foreach ($list as $t): ?>
    <div class="card" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
        <div>
            <h3 style="font-size:17px;">🧾 <?= Security::e($t['nama_siklus']) ?> <span class="text-muted" style="font-size:13px;">(<?= $t['tahun'] ?>)</span></h3>
            <p class="text-muted" style="font-size:13px;">
                <span class="badge <?= $t['status'] === 'Aktif' ? 'badge-unggul' : ($t['status'] === 'Draft' ? 'badge-b' : 'badge-baik') ?>"><?= $t['status'] ?></span>
                • <?= $t['jml'] ?> butir pertanyaan
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="?detail=<?= $t['id_instrumen_tahun'] ?>" class="btn btn-primary" style="padding:8px 18px;font-size:13px;">📝 Kelola Butir</a>
            <?php if ($t['jml'] == 0): ?>
            <form method="POST"><input type="hidden" name="id_instrumen_tahun" value="<?= $t['id_instrumen_tahun'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="template"><button class="btn btn-gold" style="padding:8px 18px;font-size:13px;">⚡ Generate Template</button></form>
            <?php endif; ?>
            <form method="POST"><input type="hidden" name="id_instrumen_tahun" value="<?= $t['id_instrumen_tahun'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="status"><button class="btn btn-outline" style="padding:8px 18px;font-size:13px;">🔁 Status</button></form>
            <form method="POST" onsubmit="return confirm('Hapus instrumen beserta seluruh butirnya?');"><input type="hidden" name="id_instrumen_tahun" value="<?= $t['id_instrumen_tahun'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><button class="btn btn-outline" style="padding:8px 18px;font-size:13px;color:var(--danger);">🗑️</button></form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else:
    $butir = $db->prepare("SELECT b.*, s.kode_standar, s.nama_standar FROM instrumen_butir b JOIN standar_mutu s ON b.id_standar = s.id_standar WHERE b.id_instrumen_tahun = ? ORDER BY s.kode_standar, b.urutan, b.id_butir");
    $butir->execute([$detailId]);
    $butirList = $butir->fetchAll();
    $grup = [];
    foreach ($butirList as $b) $grup[$b['kode_standar']][] = $b;
    $standar = $db->query("SELECT * FROM standar_mutu ORDER BY kode_standar")->fetchAll();
?>
<a href="/sim/admin/instrumen.php" class="btn btn-outline" style="margin-bottom:20px;padding:8px 20px;font-size:13px;">← Semua Instrumen</a>

<div class="card" style="margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:16px;align-items:center;">
        <div>
            <h3>🧾 <?= Security::e($detail['nama_siklus']) ?> — <?= count($butirList) ?> butir</h3>
            <p class="text-muted" style="font-size:13px;">Status: <strong><?= $detail['status'] ?></strong></p>
        </div>
        <form method="POST" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="copy">
            <input type="hidden" name="id_sumber" value="<?= $detailId ?>">
            <div class="form-group" style="margin:0;"><label class="form-label">Salin ke Tahun</label>
                <input type="number" name="tahun_baru" class="form-control" style="width:120px;" value="<?= $detail['tahun'] + 1 ?>" required></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Nama Baru (opsional)</label>
                <input type="text" name="nama_baru" class="form-control" placeholder="AMI <?= $detail['tahun'] + 1 ?>"></div>
            <button class="btn btn-gold">📑 Salin Instrumen</button>
        </form>
    </div>
</div>

<?php foreach ($standar as $s): $items = $grup[$s['kode_standar']] ?? []; ?>
<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-bottom:14px;"><?= Security::e($s['kode_standar']) ?> — <?= Security::e($s['nama_standar']) ?> <span class="badge badge-a" style="margin-left:8px;"><?= count($items) ?> butir</span></h3>
    <?php foreach ($items as $b): ?>
        <div style="padding:12px 14px;background:var(--bg-light);border-radius:10px;margin-bottom:10px;display:flex;justify-content:space-between;gap:10px;align-items:center;">
            <div>
                <p style="font-size:14px;font-weight:600;"><?= $b['urutan'] ?>. <?= Security::e($b['butir']) ?></p>
                <small class="text-muted">🔍 <?= Security::e($b['metode']) ?> • 📎 <?= Security::e($b['dokumen_diperlukan']) ?></small>
            </div>
            <form method="POST"><input type="hidden" name="id_butir" value="<?= $b['id_butir'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del_butir"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form>
        </div>
    <?php endforeach; ?>
    <form method="POST" style="padding:14px;background:var(--bg-light);border-radius:10px;border:1px dashed var(--border);">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add_butir">
        <input type="hidden" name="id_instrumen_tahun" value="<?= $detailId ?>">
        <input type="hidden" name="id_standar" value="<?= $s['id_standar'] ?>">
        <div style="display:grid;grid-template-columns:3fr 1fr 1fr auto;gap:10px;align-items:end;">
            <div class="form-group" style="margin:0;"><label class="form-label">Butir Pertanyaan</label>
                <input type="text" name="butir" class="form-control" required></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Metode</label>
                <input type="text" name="metode" class="form-control" placeholder="Telaah Dokumen"></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Dokumen Dibutuhkan</label>
                <input type="text" name="dokumen_diperlukan" class="form-control" placeholder="SK, laporan..."></div>
            <button class="btn btn-primary" style="padding:10px 18px;">➕</button>
        </div>
    </form>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>