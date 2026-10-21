<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

define('TIPE_RESPONDEN', ['Mahasiswa', 'Dosen', 'Tendik']);

$TEMPLATE = [
    ['Kualitas Pembelajaran', 'Dosen mengajar dengan jelas dan interaktif.'],
    ['Kualitas Pembelajaran', 'Materi kuliah relevan dengan kebutuhan zaman.'],
    ['Layanan Akademik', 'Layanan administrasi akademik cepat dan ramah.'],
    ['Sarana Prasarana', 'Ruang kelas & fasilitas mendukung pembelajaran.'],
    ['Kompetensi Dosen', 'Dosen menguasai bidang dan terbuka terhadap diskusi.'],
    ['Kepuasan Overall', 'Secara keseluruhan, saya puas terhadap layanan prodi.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= CREATE PERIODE =================
    if ($action === 'create') {
        $nama = mb_substr(trim($_POST['nama_periode'] ?? ''), 0, 150);
        $tipe = $_POST['tipe_responden'] ?? 'Mahasiswa';
        
        if ($nama === '') { 
            $error = '❌ Nama periode wajib diisi.'; 
        } elseif (!in_array($tipe, TIPE_RESPONDEN, true)) {
            $error = '❌ Tipe responden tidak valid.';
        } else {
            try {
                $token = bin2hex(random_bytes(5));
                $db->prepare("INSERT INTO kuesioner_periode (nama_periode, tipe_responden, token) VALUES (?,?,?)")
                   ->execute([$nama, $tipe, $token]);
                $idPer = (int)$db->lastInsertId();
                
                if (isset($_POST['pakai_template'])) {
                    $stmt = $db->prepare("INSERT INTO kuesioner_pertanyaan (id_periode, aspek, pertanyaan) VALUES (?,?,?)");
                    foreach ($TEMPLATE as $t) {
                        $stmt->execute([$idPer, mb_substr($t[0], 0, 100), mb_substr($t[1], 0, 255)]);
                    }
                }
                Logger::log('CREATE', 'Membuat kuesioner: ' . $nama);
                $pesan = '✅ Kuesioner berhasil dibuat. Bagikan tautan/QR ke responden.';
            } catch (PDOException $e) {
                $error = '❌ Gagal membuat kuesioner: ' . $e->getMessage();
            }
        }
    }

    // ================= TOGGLE STATUS =================
    if ($action === 'toggle') {
        try {
            $db->prepare("UPDATE kuesioner_periode SET status = IF(status='Aktif','Tutup','Aktif') WHERE id_periode=?")
               ->execute([(int)$_POST['id_periode']]);
            Logger::log('UPDATE', 'Mengubah status kuesioner #' . (int)$_POST['id_periode']);
            $pesan = '✅ Status kuesioner diubah.';
        } catch (PDOException $e) { $error = '❌ Gagal mengubah status: ' . $e->getMessage(); }
    }

    // ================= TAMBAH PERTANYAAN =================
    if ($action === 'add_q') {
        try {
            $aspek = mb_substr(trim($_POST['aspek'] ?? ''), 0, 100);
            $pertanyaan = trim($_POST['pertanyaan'] ?? '');
            if ($aspek === '' || $pertanyaan === '') {
                $error = '❌ Aspek dan pertanyaan wajib diisi.';
            } else {
                $db->prepare("INSERT INTO kuesioner_pertanyaan (id_periode, aspek, pertanyaan) VALUES (?,?,?)")
                   ->execute([(int)$_POST['id_periode'], $aspek, $pertanyaan]);
                Logger::log('CREATE', 'Menambah pertanyaan kuesioner #' . (int)$_POST['id_periode']);
                $pesan = '✅ Pertanyaan ditambahkan.';
            }
        } catch (PDOException $e) { $error = '❌ Gagal menambah pertanyaan: ' . $e->getMessage(); }
    }

    // ================= HAPUS PERTANYAAN =================
    if ($action === 'del_q') {
        try {
            $db->prepare("DELETE FROM kuesioner_pertanyaan WHERE id_pertanyaan=?")->execute([(int)$_POST['id_pertanyaan']]);
            Logger::log('DELETE', 'Menghapus pertanyaan kuesioner #' . (int)$_POST['id_pertanyaan']);
            $pesan = '✅ Pertanyaan dihapus.';
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }

    // ================= HAPUS PERIODE =================
    if ($action === 'delete') {
        try {
            $id = (int)$_POST['id_periode'];
            // Ambil nama dulu untuk log
            $stmt = $db->prepare("SELECT nama_periode FROM kuesioner_periode WHERE id_periode=?");
            $stmt->execute([$id]);
            $nama = $stmt->fetchColumn() ?: 'Unknown';
            
            $db->prepare("DELETE FROM kuesioner_periode WHERE id_periode=?")->execute([$id]);
            Logger::log('DELETE', 'Menghapus kuesioner periode: ' . $nama);
            $pesan = '✅ Kuesioner beserta seluruh pertanyaan dan jawabannya dihapus.';
            
            // Redirect ke halaman list agar tidak error saat variabel $detail tidak ditemukan
            header('Location: /sim/admin/kuesioner.php?success=1');
            exit;
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }
}

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $pesan = '✅ Kuesioner berhasil dihapus.';
}

$detail = null;
$detailId = (int)($_GET['detail'] ?? 0);
if ($detailId) {
    $st = $db->prepare("SELECT * FROM kuesioner_periode WHERE id_periode=?");
    $st->execute([$detailId]);
    $detail = $st->fetch();
}

$simTitle = 'Kuesioner Monev';
$activeMenu = 'kuesioner';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<?php if (!$detail): /* ============ LIST PERIODE ============ */
    $list = $db->query("SELECT p.*, (SELECT COUNT(DISTINCT sidik_responden) FROM kuesioner_jawaban j WHERE j.id_periode = p.id_periode) responden FROM kuesioner_periode p ORDER BY p.id_periode DESC")->fetchAll();
?>
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">➕ Buat Kuesioner Baru</h3>
    <form method="POST" style="display:grid;grid-template-columns:2fr 1fr auto;gap:16px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="create">
        <div class="form-group" style="margin:0;"><label class="form-label">Nama Periode</label>
            <input type="text" name="nama_periode" class="form-control" maxlength="150" placeholder="cth: Evaluasi Dosen Semester Genap 2025/2026" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Responden</label>
            <select name="tipe_responden" class="form-control">
                <?php foreach (TIPE_RESPONDEN as $t): ?><option><?= $t ?></option><?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" style="height:42px;">💾 Buat</button>
        <label style="grid-column:1/-1;display:flex;gap:8px;align-items:center;font-size:13.5px;cursor:pointer;">
            <input type="checkbox" name="pakai_template" checked> 📥 Sertakan 6 pertanyaan template standar LPM
        </label>
    </form>
</div>

<div style="display:flex;flex-direction:column;gap:16px;">
    <?php if (empty($list)): ?>
        <div class="card" style="text-align:center;padding:40px;"><div style="font-size:52px;margin-bottom:12px;">📭</div><p class="text-muted">Belum ada kuesioner. Buat yang pertama di atas.</p></div>
    <?php endif; ?>
    <?php foreach ($list as $k): ?>
    <div class="card" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
        <div>
            <h3 style="font-size:17px;margin-bottom:6px;"><?= Security::e($k['nama_periode']) ?></h3>
            <p class="text-muted" style="font-size:13px;margin:0;">
                <span class="badge badge-a"><?= Security::e($k['tipe_responden']) ?></span>
                <span class="badge <?= $k['status'] === 'Aktif' ? 'badge-unggul' : 'badge-baik' ?>" style="margin-left:6px;"><?= $k['status'] === 'Aktif' ? '🟢 Aktif' : '🔴 Tutup' ?></span>
                • 👥 <strong><?= (int)$k['responden'] ?></strong> responden
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="?detail=<?= $k['id_periode'] ?>" class="btn btn-primary" style="padding:8px 18px;font-size:13px;">📊 Kelola & Hasil</a>
            <form method="POST"><input type="hidden" name="id_periode" value="<?= $k['id_periode'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-outline" style="padding:8px 18px;font-size:13px;"><?= $k['status'] === 'Aktif' ? '🔒 Tutup' : '🔓 Buka' ?></button></form>
            <form method="POST" onsubmit="return confirm('PERINGATAN: Tindakan ini akan menghapus periode, semua pertanyaan, dan seluruh jawaban yang sudah masuk. Lanjutkan?');"><input type="hidden" name="id_periode" value="<?= $k['id_periode'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><button class="btn btn-outline" style="padding:8px 18px;font-size:13px;color:var(--danger);">🗑️</button></form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: /* ============ DETAIL PERIODE ============ */
    $qs = $db->prepare("SELECT * FROM kuesioner_pertanyaan WHERE id_periode=? ORDER BY id_pertanyaan");
    $qs->execute([$detailId]); $questions = $qs->fetchAll();

    $resp = $db->prepare("SELECT COUNT(DISTINCT sidik_responden) r FROM kuesioner_jawaban WHERE id_periode=?");
    $resp->execute([$detailId]); $respCount = (int)$resp->fetch()['r'];

    $perAspek = $db->prepare("
        SELECT p.aspek, ROUND(AVG(j.skor), 2) AS rata 
        FROM kuesioner_pertanyaan p 
        JOIN kuesioner_jawaban j ON j.id_pertanyaan = p.id_pertanyaan 
        WHERE p.id_periode = ? 
        GROUP BY p.aspek 
        ORDER BY MIN(p.id_pertanyaan) ASC
    ");
    $perAspek->execute([$detailId]); $aspekList = $perAspek->fetchAll();

    // Pastikan URL bersih tanpa double slash
    $baseUrl = rtrim(APP_URL ?? '', '/');
    $url = $baseUrl . '/publik/kuesioner.php?token=' . urlencode($detail['token']);
?>
<a href="/sim/admin/kuesioner.php" class="btn btn-outline" style="margin-bottom:20px;padding:8px 20px;font-size:13px;">← Kembali ke Daftar Kuesioner</a>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;margin-bottom:24px;">
    <div class="card">
        <h3 style="margin-bottom:8px;"><?= Security::e($detail['nama_periode']) ?></h3>
        <p class="text-muted" style="margin:0 0 16px;font-size:13px;">
            Responden: <strong><?= $respCount ?></strong> • Status: <strong><?= $detail['status'] ?></strong>
        </p>
        <label class="form-label" style="font-size:13px;font-weight:600;">Tautan Responden</label>
        <div style="display:flex;gap:10px;">
            <input type="text" class="form-control" value="<?= Security::e($url) ?>" readonly id="kuesUrl" style="font-size:13px;">
            <button type="button" class="btn btn-gold" onclick="navigator.clipboard.writeText(document.getElementById('kuesUrl').value).then(()=>{let b=this;b.textContent='✅ Tersalin';setTimeout(()=>b.textContent='📋 Salin',2000)});">📋 Salin</button>
        </div>
    </div>

    <div class="card" style="text-align:center;">
        <h3 style="justify-content:center;margin-bottom:12px;">QR Code</h3>
        <div id="qrBox" style="margin:0 auto;display:inline-block;padding:12px;background:#ffffff;border-radius:12px;border:1px solid var(--border);line-height:0;"></div>
        <p class="text-muted" style="font-size:12px;margin-top:12px;">Cetak & tempel di kelas / mading</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
<script>
(function () {
    var box = document.getElementById('qrBox');
    if (!box) return;
    if (typeof qrcode === 'function') {
        var qr = qrcode(0, 'M');
        qr.addData(<?= json_encode($url) ?>);
        qr.make();
        box.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
        var svg = box.querySelector('svg');
        if (svg) { svg.style.width = '180px'; svg.style.height = '180px'; }
    } else {
        box.innerHTML = '<p class="text-muted" style="font-size:12px;margin:0;">QR tidak tersedia — salin tautan di samping.</p>';
    }
})();
</script>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">📝 Daftar Pertanyaan (<?= count($questions) ?>)</h3>
        <form method="POST" style="padding:16px;background:var(--bg-light);border-radius:12px;margin-bottom:16px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="add_q">
            <input type="hidden" name="id_periode" value="<?= $detailId ?>">
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:12px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Aspek</label>
                    <input type="text" name="aspek" class="form-control" maxlength="100" placeholder="cth: Layanan" required></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Pertanyaan</label>
                    <input type="text" name="pertanyaan" class="form-control" required></div>
            </div>
            <button class="btn btn-primary" style="padding:9px 20px;font-size:13px;margin-top:12px;">➕ Tambah Pertanyaan</button>
        </form>
        <div style="display:flex;flex-direction:column;gap:10px;max-height:340px;overflow-y:auto;">
            <?php foreach ($questions as $q): ?>
            <div style="padding:12px 14px;background:var(--bg-light);border-radius:10px;display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                <div style="flex:1;min-width:0;">
                    <span class="badge badge-a" style="font-size:11px;"><?= Security::e($q['aspek']) ?></span>
                    <p style="font-size:13.5px;margin-top:6px;word-break:break-word;"><?= Security::e($q['pertanyaan']) ?></p>
                </div>
                <form method="POST" onsubmit="return confirm('Hapus pertanyaan ini?');">
                    <input type="hidden" name="id_pertanyaan" value="<?= $q['id_pertanyaan'] ?>">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="del_q">
                    <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-bottom:16px;">📊 Hasil Live (skala 1-5)</h3>
        <?php if (empty($aspekList)): ?>
            <div style="text-align:center;padding:40px 20px;">
                <div style="font-size:40px;margin-bottom:12px;">📭</div>
                <p class="text-muted">Belum ada jawaban masuk.<br>Bagikan tautan atau QR code terlebih dahulu.</p>
            </div>
        <?php else: ?>
            <div style="height:280px;"><canvas id="cHasil"></canvas></div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($aspekList)): ?>
<script>
new Chart(document.getElementById('cHasil'), {
    type: 'bar',
    data: { 
        labels: <?= json_encode(array_column($aspekList, 'aspek')) ?>,
        datasets: [{ 
            label: 'Rata-rata Skor', 
            data: <?= json_encode(array_column($aspekList, 'rata')) ?>,
            backgroundColor: '#C9A227', 
            borderRadius: 8 
        }] 
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false, 
        scales: { y: { beginAtZero: true, max: 5, title: { display: true, text: 'Skor (1-5)' } } } 
    }
});
</script>
<?php endif; ?>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>