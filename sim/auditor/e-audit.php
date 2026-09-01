<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([4]);
$db = Database::getInstance();
$auditorId = Auth::id();
$pesan = ''; $error = '';

function badgeTemuan($k) {
    return match($k) {
        'Mayor' => 'background:#FEE2E2;color:#991B1B',
        'Minor' => 'background:#FEF3C7;color:#92400E',
        'Observasi' => 'background:#DBEAFE;color:#1E40AF',
        default => 'background:#D1FAE5;color:#065F46'
    };
}

$stmt = $db->prepare("SELECT pa.*, p.nama_prodi, j.tahun_ami FROM penugasan_audit pa LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi LEFT JOIN jadwal_ami j ON pa.id_jadwal = j.id_jadwal WHERE pa.id_auditor = ? ORDER BY pa.tanggal_audit DESC");
$stmt->execute([$auditorId]);
$myTasks = $stmt->fetchAll();
$myIds = array_column($myTasks, 'id_tugas');

$tugasId = (int)($_GET['tugas'] ?? 0);
$current = null;
if ($tugasId && in_array($tugasId, $myIds)) {
    foreach ($myTasks as $t) if ($t['id_tugas'] == $tugasId) $current = $t;
}

if ($current && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_temuan' && $current['status'] !== 'Selesai') {
        foreach ($_POST['kategori'] ?? [] as $idStandar => $kat) {
            $idStandar = (int)$idStandar;
            $desk = trim($_POST['deskripsi'][$idStandar] ?? '');
            $chk = $db->prepare("SELECT id_temuan FROM temuan_audit WHERE id_tugas=? AND id_standar=?");
            $chk->execute([$tugasId, $idStandar]);
            $ex = $chk->fetch();
            if ($ex) {
                $db->prepare("UPDATE temuan_audit SET kategori=?, deskripsi_temuan=? WHERE id_temuan=?")->execute([$kat, $desk, $ex['id_temuan']]);
            } else {
                $db->prepare("INSERT INTO temuan_audit (id_tugas, id_standar, kategori, deskripsi_temuan) VALUES (?,?,?,?)")->execute([$tugasId, $idStandar, $kat, $desk]);
            }
        }
        $db->prepare("UPDATE penugasan_audit SET status='Dikerjakan' WHERE id_tugas=? AND status='Ditugaskan'")->execute([$tugasId]);
        Logger::log('AUDIT', 'Menyimpan lembar kerja audit (tugas #' . $tugasId . ')');
        $pesan = '✅ Lembar kerja audit tersimpan.';
    }

    if ($action === 'selesai' && $current['status'] !== 'Selesai') {
        $db->prepare("UPDATE penugasan_audit SET status='Selesai' WHERE id_tugas=?")->execute([$tugasId]);
        Logger::log('AUDIT', 'Menandai audit SELESAI (tugas #' . $tugasId . ')');
        // 🔔 v3: beri tahu Kaprodi prodi yang diaudit
        $pp = $db->prepare("SELECT id_prodi FROM penugasan_audit WHERE id_tugas = ?");
        $pp->execute([$tugasId]);
        $prodiOfTask = $pp->fetch()['id_prodi'] ?? null;
        if ($prodiOfTask) {
            $kk = $db->prepare("SELECT id_user FROM users WHERE id_role = 3 AND id_prodi = ? LIMIT 1");
            $kk->execute([$prodiOfTask]);
            $kaprodi = $kk->fetch();
            if ($kaprodi) {
                Notifier::send((int)$kaprodi['id_user'], '🔍 Audit Prodi Selesai', 'Audit Mutu Internal prodi Anda telah selesai. Silakan tinjau temuan dan kirim tindakan koreksi.', '/sim/prodi/riwayat.php', '🔍');
            }
        }
        $pesan = '✅ Audit ditandai SELESAI. Lembar kerja terkunci.';
    }
}

$temuanMap = [];
if ($current) {
    $tm = $db->prepare("SELECT * FROM temuan_audit WHERE id_tugas=?");
    $tm->execute([$tugasId]);
    foreach ($tm->fetchAll() as $r) { $temuanMap[$r['id_standar']] = $r; }
}
$standar = $db->query("SELECT * FROM standar_mutu ORDER BY kode_standar")->fetchAll();
$locked = ($current['status'] ?? '') === 'Selesai';

$simTitle = 'E-Audit';
$activeMenu = 'eaudit';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<?php if (!$current): ?>
    <div class="card">
        <h3 style="margin-bottom:20px;">📋 Pilih Penugasan Audit</h3>
        <?php if (empty($myTasks)): ?>
            <p class="text-muted">Belum ada penugasan untuk Anda.</p>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($myTasks as $t): ?>
                <a href="?tugas=<?= $t['id_tugas'] ?>" style="padding:16px;background:var(--bg-light);border-radius:10px;text-decoration:none;color:inherit;display:flex;justify-content:space-between;align-items:center;">
                    <div><strong><?= Security::e($t['nama_prodi']) ?></strong><br><small class="text-muted">AMI <?= $t['tahun_ami'] ?> · <?= date('d M Y', strtotime($t['tanggal_audit'])) ?></small></div>
                    <span class="badge <?= $t['status'] === 'Selesai' ? 'badge-unggul' : 'badge-b' ?>"><?= Security::e($t['status']) ?> →</span>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;align-items:center;">
            <div>
                <h2 style="font-size:22px;">🔍 Audit: <?= Security::e($current['nama_prodi']) ?></h2>
                <p style="opacity:.8;">AMI <?= $current['tahun_ami'] ?> · <?= date('d M Y', strtotime($current['tanggal_audit'])) ?></p>
            </div>
            <span class="badge" style="background:rgba(255,255,255,.2);color:#fff;"><?= $locked ? '🔒 Terkunci' : '✏️ ' . Security::e($current['status']) ?></span>
        </div>
    </div>

    <form method="POST">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="save_temuan">
        <?php foreach ($standar as $s): $tm = $temuanMap[$s['id_standar']] ?? null; ?>
        <div class="card" style="margin-bottom:16px;<?= $tm && $tm['kategori'] === 'Mayor' ? 'border-left:4px solid var(--danger);' : '' ?>">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
                <h3 style="font-size:16px;"><?= Security::e($s['kode_standar']) ?> — <?= Security::e($s['nama_standar']) ?></h3>
                <select name="kategori[<?= $s['id_standar'] ?>]" class="form-control" style="width:auto;" <?= $locked ? 'disabled' : '' ?>>
                    <?php foreach (['Kondisi Baik','Observasi','Minor','Mayor'] as $opt): ?>
                        <option <?= ($tm['kategori'] ?? 'Kondisi Baik') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <textarea name="deskripsi[<?= $s['id_standar'] ?>]" class="form-control" rows="2" placeholder="Catatan observasi / deskripsi temuan..." <?= $locked ? 'disabled' : '' ?>><?= Security::e($tm['deskripsi_temuan'] ?? '') ?></textarea>
        </div>
        <?php endforeach; ?>

        <?php if (!$locked): ?>
        <div style="display:flex;gap:12px;margin-bottom:24px;">
            <button type="submit" class="btn btn-primary">💾 Simpan Lembar Kerja</button>
            <button type="submit" name="action" value="selesai" class="btn btn-gold" onclick="return confirm('Tandai audit selesai? Lembar akan terkunci & Kaprodi dinotifikasi.');">✅ Tandai Selesai</button>
        </div>
        <?php endif; ?>
    </form>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>