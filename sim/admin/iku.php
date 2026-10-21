<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);
$db = Database::getInstance();
$pesan = ''; $error = '';
$tahun = (int)($_GET['tahun'] ?? date('Y'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= UPDATE TARGET & NAMA =================
    if ($action === 'target') {
        try {
            $id = (int)$_POST['id_iku'];
            $target = (float)$_POST['target'];
            $nama = mb_substr(trim($_POST['nama'] ?? ''), 0, 200);
            
            $db->prepare("UPDATE iku_indikator SET target = ?, nama = ? WHERE id_iku = ?")
               ->execute([$target, $nama, $id]);
            Logger::log('UPDATE', "Memperbarui target IKU #$id menjadi $target");
            $pesan = '✅ Target dan nama indikator diperbarui.';
        } catch (PDOException $e) {
            $error = '❌ Gagal memperbarui: ' . $e->getMessage();
        }
    }

    // ================= INPUT NILAI CAPAIAN =================
    if ($action === 'nilai') {
        try {
            $id = (int)$_POST['id_iku'];
            $nilai = (float)($_POST['nilai'] ?? 0);
            $catatan = mb_substr(trim($_POST['catatan'] ?? ''), 0, 255);
            
            $db->prepare("REPLACE INTO iku_data (id_iku, tahun, nilai, catatan) VALUES (?,?,?,?)")
               ->execute([$id, $tahun, $nilai, $catatan]);
            Logger::log('UPDATE', "Input nilai IKU #$id tahun $tahun: $nilai");
            $pesan = '✅ Nilai capaian tersimpan.';
        } catch (PDOException $e) {
            $error = '❌ Gagal menyimpan nilai: ' . $e->getMessage();
        }
    }

    // ================= HITUNG OTOMATIS IKU 1 & 8 =================
    if ($action === 'auto') {
        try {
            // IKU 1: Persentase lulusan bekerja/wirausaha (handle NULL/0)
            $tr = $db->query("SELECT COUNT(*) as t, SUM(CASE WHEN status_kerja IN ('Bekerja','Wirausaha') THEN 1 ELSE 0 END) as w FROM tracer_alumni")->fetch();
            $iku1 = ($tr['t'] > 0) ? round(($tr['w'] / $tr['t']) * 100, 1) : null;

            // IKU 8: Rata-rata 7 aspek survei pengguna (gunakan COALESCE untuk handle NULL)
            $sp = $db->query("
                SELECT ROUND(AVG(
                    (COALESCE(aspek_etika,0) + COALESCE(aspek_keahlian,0) + COALESCE(aspek_bahasa,0) + 
                     COALESCE(aspek_teknologi,0) + COALESCE(aspek_komunikasi,0) + COALESCE(aspek_kerjasama,0) + 
                     COALESCE(aspek_pengembangan,0)) / 7.0
                ) * 20, 1) as r 
                FROM survei_pengguna
            ")->fetch();
            $iku8 = (!empty($sp['r'])) ? (float)$sp['r'] : null;

            $updates = [
                ['kode' => 'IKU 1', 'nilai' => $iku1, 'catatan' => 'Otomatis: Tracer Study'],
                ['kode' => 'IKU 8', 'nilai' => $iku8, 'catatan' => 'Otomatis: Survei Pengguna']
            ];

            foreach ($updates as $u) {
                if ($u['nilai'] !== null) {
                    // Cari berdasarkan kode, fallback ke id_iku spesifik jika perlu
                    $stmt = $db->prepare("SELECT id_iku FROM iku_indikator WHERE kode = ?");
                    $stmt->execute([$u['kode']]);
                    $iid = $stmt->fetchColumn();
                    
                    if ($iid) {
                        $db->prepare("REPLACE INTO iku_data (id_iku, tahun, nilai, catatan) VALUES (?,?,?,?)")
                           ->execute([$iid, $tahun, $u['nilai'], $u['catatan']]);
                    }
                }
            }
            Logger::log('CREATE', "Kalkulasi otomatis IKU 1 & 8 untuk tahun $tahun");
            $pesan = '⚡ IKU 1 & IKU 8 berhasil dihitung dan disimpan dari data Tracer Study & Survei.';
        } catch (PDOException $e) {
            $error = '❌ Gagal menghitung otomatis: ' . $e->getMessage();
        }
    }
}

$ikus = $db->query("SELECT * FROM iku_indikator ORDER BY id_iku")->fetchAll();
$dm = $db->prepare("SELECT id_iku, nilai, catatan FROM iku_data WHERE tahun = ?");
$dm->execute([$tahun]);
$data = [];
foreach ($dm->fetchAll() as $d) {
    $data[$d['id_iku']] = $d;
}

$simTitle = 'IKU — Indikator Kinerja Utama';
$activeMenu = 'iku';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
        <h2 style="font-size:22px;color:var(--primary-dark);">📊 Dasbor IKU <?= $tahun ?></h2>
        <p class="text-muted" style="font-size:13px;">Target & nama indikator dapat disesuaikan dengan kebijakan kampus Anda.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <form method="GET">
            <select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
                <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                    <option <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="auto">
            <button class="btn btn-gold">⚡ Hitung Otomatis IKU 1 & 8</button>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
    <?php foreach ($ikus as $k):
        $d = $data[$k['id_iku']] ?? null;
        $pct = ($d && $k['target'] > 0) ? min(round($d['nilai'] / $k['target'] * 100, 1), 150) : 0;
    ?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:8px;">
            <span class="badge badge-a"><?= Security::e($k['kode']) ?></span>
            <?php if ($d): ?>
                <span class="badge <?= $pct >= 100 ? 'badge-unggul' : ($pct >= 60 ? 'badge-b' : '') ?>" 
                      style="<?= $pct < 60 ? 'background:#FEE2E2;color:#991B1B' : '' ?>">
                    <?= $pct >= 100 ? '🏆 Tercapai' : $pct . '%' ?>
                </span>
            <?php else: ?>
                <span class="badge badge-baik">Belum diinput</span>
            <?php endif; ?>
        </div>
        
        <!-- Form Edit Nama & Target (Inline, lebih robust daripada hidden form) -->
        <form method="POST" style="display:flex;gap:8px;align-items:end;margin-bottom:12px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="target">
            <input type="hidden" name="id_iku" value="<?= $k['id_iku'] ?>">
            <div class="form-group" style="margin:0;flex:2;">
                <label class="form-label" style="font-size:11px;">Nama Indikator</label>
                <input type="text" name="nama" class="form-control" style="padding:6px 8px;font-size:13px;" value="<?= Security::e($k['nama']) ?>" required>
            </div>
            <div class="form-group" style="margin:0;flex:1;">
                <label class="form-label" style="font-size:11px;">Target</label>
                <input type="number" step="0.1" name="target" class="form-control" style="padding:6px 8px;font-size:13px;" value="<?= $k['target'] ?>" required>
            </div>
            <button class="btn btn-outline" style="padding:6px 10px;font-size:12px;" title="Simpan Nama & Target">💾</button>
        </form>

        <div style="background:var(--bg-light);border-radius:50px;height:10px;overflow:hidden;margin-bottom:14px;">
            <div style="height:100%;width:<?= min($pct, 100) ?>%;background:<?= $pct >= 100 ? 'var(--success)' : ($pct >= 60 ? 'var(--accent)' : 'var(--danger)') ?>;border-radius:50px;transition:width .8s;"></div>
        </div>

        <form method="POST" style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="nilai">
            <input type="hidden" name="id_iku" value="<?= $k['id_iku'] ?>">
            <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:11px;">Nilai Capaian (<?= Security::e($k['satuan']) ?>)</label>
                <input type="number" step="0.1" name="nilai" class="form-control" style="padding:6px 8px;" value="<?= $d['nilai'] ?? '' ?>" placeholder="0">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:11px;">Catatan</label>
                <input type="text" name="catatan" class="form-control" style="padding:6px 8px;" value="<?= Security::e($d['catatan'] ?? '') ?>">
            </div>
            <button class="btn btn-primary" style="padding:8px 16px;font-size:13px;">💾 Simpan Nilai</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📈 Capaian vs Target (<?= $tahun ?>)</h3>
    <div style="height:320px;"><canvas id="cIku"></canvas></div>
</div>

<script>
new Chart(document.getElementById('cIku'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($ikus, 'kode')) ?>,
        datasets: [
            { 
                label: 'Capaian (%)', 
                data: <?= json_encode(array_map(fn($k) => isset($data[$k['id_iku']], $k['target']) && $k['target'] > 0 ? min(round($data[$k['id_iku']]['nilai'] / $k['target'] * 100, 1), 150) : 0, $ikus)) ?>, 
                backgroundColor: '#C9A227', 
                borderRadius: 8 
            },
            { 
                label: 'Target (100%)', 
                data: <?= json_encode(array_fill(0, count($ikus), 100)) ?>, 
                type: 'line', 
                borderColor: '#0F3D5C', 
                borderDash: [6, 4], 
                pointRadius: 0, 
                fill: false 
            }
        ]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false, 
        scales: { y: { beginAtZero: true, max: 150, title: { display: true, text: 'Persentase (%)' } } } 
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>