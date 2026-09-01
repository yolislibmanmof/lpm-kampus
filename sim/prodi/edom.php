<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = ''; $error = '';

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$EDOM_Q = ['Kejelasan materi','Penguasaan materi','Metode mengajar','Media & teknologi','Ketepatan waktu','Interaksi & kepedulian','Keadilan penilaian','Feedback tugas','Etika & keteladanan','Motivasi & inspirasi'];

$simTitle = 'EDOM';
$activeMenu = 'edom';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun belum terhubung ke prodi.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

$dosenList = $db->prepare("SELECT id_dosen, nama_dosen FROM dosen WHERE id_prodi = ? ORDER BY nama_dosen");
$dosenList->execute([$prodiId]);
$dosenList = $dosenList->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create_periode') {
        $db->prepare("INSERT INTO edom_periode (id_prodi, nama, semester) VALUES (?,?,?)")
           ->execute([$prodiId, trim($_POST['nama']), trim($_POST['semester'])]);
        $pesan = '✅ Periode EDOM dibuat.';
    }
    if ($action === 'add_kelas') {
        $db->prepare("INSERT INTO edom_kelas (id_periode, id_dosen, mata_kuliah, token) VALUES (?,?,?,?)")
           ->execute([(int)$_POST['id_periode'], (int)$_POST['id_dosen'], trim($_POST['mata_kuliah']), bin2hex(random_bytes(8))]);
        $pesan = '✅ Kelas ditambahkan — QR & tautan siap dibagikan.';
    }
    if ($action === 'toggle') {
        $db->prepare("UPDATE edom_periode SET status = IF(status='Terbuka','Tertutup','Terbuka') WHERE id_periode = ? AND id_prodi = ?")
           ->execute([(int)$_POST['id_periode'], $prodiId]);
        $pesan = '✅ Status periode diubah.';
    }
    if ($action === 'del_kelas') {
        $db->prepare("DELETE FROM edom_kelas WHERE id_kelas = ?")->execute([(int)$_POST['id_kelas']]);
        $pesan = '✅ Kelas dihapus.';
    }
}

$detail = (int)($_GET['detail'] ?? 0);
$hasil = (int)($_GET['hasil'] ?? 0);

if ($hasil) {
    $k = $db->prepare("SELECT k.*, d.nama_dosen FROM edom_kelas k JOIN dosen d ON k.id_dosen = d.id_dosen WHERE k.id_kelas = ?");
    $k->execute([$hasil]);
    $kelas = $k->fetch();
    $avg = $db->prepare("SELECT ROUND(AVG(q1),2) a1, ROUND(AVG(q2),2) a2, ROUND(AVG(q3),2) a3, ROUND(AVG(q4),2) a4, ROUND(AVG(q5),2) a5,
        ROUND(AVG(q6),2) a6, ROUND(AVG(q7),2) a7, ROUND(AVG(q8),2) a8, ROUND(AVG(q9),2) a9, ROUND(AVG(q10),2) a10, COUNT(*) n
        FROM edom_jawaban WHERE id_kelas = ?");
    $avg->execute([$hasil]);
    $A = $avg->fetch();
    $komentar = $db->prepare("SELECT nim, komentar, created_at FROM edom_jawaban WHERE id_kelas = ? AND komentar != '' ORDER BY created_at DESC");
    $komentar->execute([$hasil]);
    $komentar = $komentar->fetchAll();
} elseif ($detail) {
    $per = $db->prepare("SELECT * FROM edom_periode WHERE id_periode = ? AND id_prodi = ?");
    $per->execute([$detail, $prodiId]);
    $periode = $per->fetch();
    $kelasList = $db->prepare("SELECT k.*, d.nama_dosen,
        (SELECT COUNT(*) FROM edom_jawaban j WHERE j.id_kelas = k.id_kelas) n,
        (SELECT ROUND(AVG((q1+q2+q3+q4+q5+q6+q7+q8+q9+q10)/10),2) FROM edom_jawaban j WHERE j.id_kelas = k.id_kelas) rata
        FROM edom_kelas k JOIN dosen d ON k.id_dosen = d.id_dosen WHERE k.id_periode = ? ORDER BY d.nama_dosen");
    $kelasList->execute([$detail]);
    $kelasList = $kelasList->fetchAll();
} else {
    $periods = $db->prepare("SELECT p.*, (SELECT COUNT(*) FROM edom_kelas k WHERE k.id_periode = p.id_periode) jml_kelas FROM edom_periode p WHERE p.id_prodi = ? ORDER BY p.id_periode DESC");
    $periods->execute([$prodiId]);
    $periods = $periods->fetchAll();
}
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<?php if (isset($kelas) && $kelas): ?>
<a href="?detail=<?= $kelas['id_periode'] ?>" class="btn btn-outline" style="margin-bottom:20px;padding:8px 20px;font-size:13px;">← Daftar Kelas</a>
<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card"><h3 style="margin-bottom:16px;">📡 Radar Kompetensi Mengajar (10 Aspek)</h3>
        <div style="height:320px;"><canvas id="cEdom"></canvas></div></div>
    <div class="card" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;">
        <h3 style="color:#fff;"><?= Security::e($kelas['nama_dosen']) ?></h3>
        <p style="opacity:.85;font-size:13.5px;"><?= Security::e($kelas['mata_kuliah']) ?></p>
        <div style="font-size:52px;font-weight:800;color:var(--accent-light);margin:16px 0;"><?= $A['n'] ? round((($A['a1']+$A['a2']+$A['a3']+$A['a4']+$A['a5']+$A['a6']+$A['a7']+$A['a8']+$A['a9']+$A['a10'])/10),1) : '—' ?><span style="font-size:20px;">/5</span></div>
        <p style="opacity:.85;font-size:13px;">Rata-rata keseluruhan dari <?= (int)$A['n'] ?> responden</p>
    </div>
</div>
<div class="card">
    <h3 style="margin-bottom:16px;">💬 Komentar Mahasiswa (<?= count($komentar) ?>)</h3>
    <?php if (empty($komentar)): ?><p class="text-muted">Belum ada komentar.</p><?php endif; ?>
    <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($komentar as $c): ?>
        <div style="padding:14px;background:var(--bg-light);border-radius:12px;">
            <p style="font-size:14px;"><?= Security::e($c['komentar']) ?></p>
            <small class="text-muted">— <?= Security::e($c['nim']) ?> • <?= date('d M Y', strtotime($c['created_at'])) ?></small>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
new Chart(document.getElementById('cEdom'), {
    type: 'radar',
    data: { labels: <?= json_encode($EDOM_Q) ?>,
        datasets: [{ label: 'Rata-rata', data: [<?= (float)($A['a1']??0) ?>,<?= (float)($A['a2']??0) ?>,<?= (float)($A['a3']??0) ?>,<?= (float)($A['a4']??0) ?>,<?= (float)($A['a5']??0) ?>,<?= (float)($A['a6']??0) ?>,<?= (float)($A['a7']??0) ?>,<?= (float)($A['a8']??0) ?>,<?= (float)($A['a9']??0) ?>,<?= (float)($A['a10']??0) ?>],
        backgroundColor: 'rgba(201,162,39,.25)', borderColor: '#C9A227', borderWidth: 2, pointBackgroundColor: '#0F3D5C' }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { r: { min: 0, max: 5, ticks: { display: false } } } }
});
</script>

<?php elseif (isset($periode) && $periode): ?>
<a href="/sim/prodi/edom.php" class="btn btn-outline" style="margin-bottom:20px;padding:8px 20px;font-size:13px;">← Semua Periode</a>
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
    <h2 style="font-size:22px;color:var(--primary-dark);">📝 <?= Security::e($periode['nama']) ?> <span class="badge <?= $periode['status']=='Terbuka'?'badge-unggul':'badge-baik' ?>"><?= $periode['status'] ?></span></h2>
    <form method="POST"><input type="hidden" name="id_periode" value="<?= $periode['id_periode'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-gold"><?= $periode['status']=='Terbuka' ? '🔒 Tutup Periode' : '🔓 Buka Periode' ?></button></form>
</div>

<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">➕ Tambah Kelas / Mata Kuliah</h3>
    <?php if (empty($dosenList)): ?><p class="text-muted">Belum ada dosen. Tambahkan via 📇 Kelola Dosen (Admin) atau 📥 Import PDDikti.</p>
    <?php else: ?>
    <form method="POST" style="display:grid;grid-template-columns:1.4fr 1.6fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add_kelas">
        <input type="hidden" name="id_periode" value="<?= $periode['id_periode'] ?>">
        <div class="form-group" style="margin:0;"><label class="form-label">Dosen</label>
            <select name="id_dosen" class="form-control" required>
                <?php foreach ($dosenList as $d): ?><option value="<?= $d['id_dosen'] ?>"><?= Security::e($d['nama_dosen']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Mata Kuliah</label>
            <input type="text" name="mata_kuliah" class="form-control" placeholder="cth: Pemrograman Web" required></div>
        <button class="btn btn-primary">➕ Tambah</button>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Kelas dalam Periode Ini</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Dosen</th><th>Mata Kuliah</th><th>Responden</th><th>Rata-rata</th><th>QR / Tautan</th><th>Hasil</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($kelasList)): ?><tr><td colspan="7" style="text-align:center;padding:30px;" class="text-muted">Belum ada kelas.</td></tr><?php endif; ?>
            <?php foreach ($kelasList as $k): $url = APP_URL . '/publik/edom.php?token=' . $k['token']; ?>
                <tr>
                    <td><strong><?= Security::e($k['nama_dosen']) ?></strong></td>
                    <td><?= Security::e($k['mata_kuliah']) ?></td>
                    <td><?= (int)$k['n'] ?></td>
                    <td><?= $k['rata'] ? '<span class="badge badge-unggul">' . $k['rata'] . ' / 5</span>' : '—' ?></td>
                    <td><button class="btn btn-outline" style="padding:5px 12px;font-size:12px;" onclick="navigator.clipboard.writeText('<?= Security::e($url) ?>');this.textContent='✅ Tersalin';">📋 Salin Link</button></td>
                    <td><a href="?hasil=<?= $k['id_kelas'] ?>" class="btn btn-primary" style="padding:5px 14px;font-size:12px;">📊 Lihat</a></td>
                    <td><form method="POST" onsubmit="return confirm('Hapus kelas & semua jawabannya?');"><input type="hidden" name="id_kelas" value="<?= $k['id_kelas'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del_kelas"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">➕ Buat Periode EDOM</h3>
    <form method="POST" style="display:grid;grid-template-columns:2fr 1fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="create_periode">
        <div class="form-group" style="margin:0;"><label class="form-label">Nama Periode</label>
            <input type="text" name="nama" class="form-control" placeholder="cth: EDOM Semester Ganjil 2026/2027" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Semester</label>
            <input type="text" name="semester" class="form-control" placeholder="Ganjil 2026/27"></div>
        <button class="btn btn-gold">💾 Buat</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📅 Riwayat Periode</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Periode</th><th>Semester</th><th>Kelas</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($periods)): ?><tr><td colspan="5" style="text-align:center;padding:30px;" class="text-muted">Belum ada periode. Buat yang pertama di atas.</td></tr><?php endif; ?>
            <?php foreach ($periods as $p): ?>
                <tr>
                    <td><strong><?= Security::e($p['nama']) ?></strong></td>
                    <td><?= Security::e($p['semester']) ?></td>
                    <td><?= (int)$p['jml_kelas'] ?></td>
                    <td><span class="badge <?= $p['status']=='Terbuka'?'badge-unggul':'badge-baik' ?>"><?= $p['status'] ?></span></td>
                    <td><a href="?detail=<?= $p['id_periode'] ?>" class="btn btn-primary" style="padding:5px 14px;font-size:12px;">📋 Kelola</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>