<?php
require_once __DIR__ . '/../../config/config.php';
Auth::requireRole([1, 3, 5]); // Admin, Kaprodi, GPM
require_once __DIR__ . '/../../core/MockAuditEngine.php';

$db = Database::getInstance();
$user = Auth::user();

// Sumber kebenaran ID kriteria (tidak lagi mengasumsikan 1..9)
$kriteriaList = $db->query("SELECT id_kriteria, nomor, nama_kriteria FROM kriteria_akreditasi ORDER BY nomor ASC")->fetchAll();

// Tentukan prodi
if ((int)$user['role'] === 1) {
    $idProdi = (int)($_GET['prodi'] ?? 0);
    if ($idProdi <= 0) {
        $idProdi = (int)$db->query("SELECT id_prodi FROM prodi ORDER BY id_prodi ASC LIMIT 1")->fetchColumn();
    }
} else {
    $stmt = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
    $stmt->execute([$user['id']]);
    $idProdi = (int)($stmt->fetchColumn() ?: 0);
}

// Validasi prodi benar-benar ada
$cek = $db->prepare("SELECT COUNT(*) FROM prodi WHERE id_prodi = ?");
$cek->execute([$idProdi]);
if ($idProdi <= 0 || !(int)$cek->fetchColumn()) {
    die("<div style='padding:40px;text-align:center;'><h2>⛔ Akses Ditolak</h2><p>Anda tidak terikat pada Program Studi manapun. Hubungi Admin LPM.</p><a href='/sim/index.php' class='btn btn-primary'>Kembali ke Dashboard</a></div>");
}

$tahunAjaran = date('Y') . '/' . (date('Y') + 1);
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mock'])) {
    Security::verifyCsrf();
    $details = [];
    foreach ($kriteriaList as $k) {
        $kid = (int)$k['id_kriteria'];
        $skor = max(1.0, min(4.0, (float)($_POST['skor_' . $kid] ?? 1.00)));
        $bukti = max(0, min(100, (int)($_POST['bukti_' . $kid] ?? 0)));
        $details[$kid] = ['skor' => $skor, 'bukti' => $bukti, 'gap' => trim((string)($_POST['gap_' . $kid] ?? ''))];
    }
    $success = MockAuditEngine::saveSimulation($idProdi, $tahunAjaran, $details, (int)$user['id']);
    $msg = $success ? "✅ Simulasi berhasil disimpan dan peringkat diperbarui!" : "❌ Gagal menyimpan simulasi.";
}

$simData = MockAuditEngine::getLatestSimulation($idProdi);
$prediksi = $simData ? MockAuditEngine::predictRank((float)$simData['total_skor']) : null;

// Peta data lama per kriteria (untuk nilai form & radar yang benar)
$oldMap = [];
if ($simData) { foreach ($simData['details'] as $d) { $oldMap[(int)$d['id_kriteria']] = $d; } }
$chartLabels = array_map(fn($k) => $k['nama_kriteria'], $kriteriaList);
$chartData = array_map(fn($k) => isset($oldMap[(int)$k['id_kriteria']]) ? (float)$oldMap[(int)$k['id_kriteria']]['skor_capaian'] : 1.0, $kriteriaList);

$simTitle = 'Simulasi Pre-Akreditasi';
$activeMenu = 'mockaudit';
require_once __DIR__ . '/../../includes/header-sim.php';
?>
<style>
    .mock-hero { padding: 24px; border-radius: 16px; text-align: center; margin-bottom: 24px; }
    .mock-score { font-size: 48px; font-weight: 900; line-height: 1; margin: 8px 0; }
    .mock-badge { display: inline-block; padding: 6px 16px; border-radius: 50px; font-size: 14px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
    .kriteria-row { display: grid; grid-template-columns: 40px 2fr 1fr 1fr 2fr; gap: 12px; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border); }
    .kriteria-row:last-child { border-bottom: none; }
    .kriteria-num { font-weight: 800; color: var(--text-muted); font-size: 14px; }
    .input-score { width: 70px; padding: 6px; text-align: center; border-radius: 8px; border: 1px solid var(--border); font-weight: 700; }
    .input-bukti { width: 80px; padding: 6px; text-align: center; border-radius: 8px; border: 1px solid var(--border); }
    .input-gap { width: 100%; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; }
</style>

<div class="container" style="padding: 24px 20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h2 style="margin:0;">🎯 Simulasi Pre-Akreditasi (Mock Audit)</h2>
            <p class="text-muted" style="margin:4px 0 0;">Evaluasi kesiapan akreditasi berdasarkan 9 kriteria.</p>
        </div>
        <a href="#mockForm" class="btn btn-primary">💾 Isi & Simpan Simulasi</a>
    </div>

    <?php if ($msg): ?>
        <div class="card" style="padding:16px;margin-bottom:20px;background:<?= strpos($msg,'✅')!==false?'rgba(16,185,129,0.1)':'rgba(239,68,68,0.1)' ?>;border:1px solid <?= strpos($msg,'✅')!==false?'#10B981':'#EF4444' ?>;border-radius:12px;">
            <?= Security::e($msg) ?>
        </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px;">
        <div>
            <div class="mock-hero" style="background:<?= $prediksi['bg'] ?? 'var(--bg-light)' ?>;border:1px solid <?= $prediksi['color'] ?? 'var(--border)' ?>40;">
                <small style="text-transform:uppercase;letter-spacing:1px;font-weight:700;color:var(--text-muted);">Prediksi Peringkat Saat Ini</small>
                <div class="mock-score" style="color:<?= $prediksi['color'] ?? 'var(--text-muted)' ?>;"><?= $simData ? number_format((float)$simData['total_skor'],2) : '0.00' ?></div>
                <?php if ($prediksi): ?>
                    <span class="mock-badge" style="background:<?= $prediksi['color'] ?>;color:#fff;"><?= Security::e($prediksi['peringkat']) ?></span>
                    <p style="margin-top:16px;font-size:14px;"><?= Security::e($prediksi['pesan']) ?></p>
                <?php else: ?>
                    <p style="margin-top:16px;font-size:14px;color:var(--text-muted);">Belum ada data simulasi. Silakan isi penilaian di sebelah kanan.</p>
                <?php endif; ?>
            </div>
            <div class="card" style="padding:20px;">
                <h4 style="margin:0 0 16px;">📊 Radar 9 Kriteria</h4>
                <canvas id="radarChart" height="250"></canvas>
            </div>
        </div>

        <div class="card" style="padding:24px;" id="mockForm">
            <h3 style="margin:0 0 20px;">📝 Input Penilaian Mandiri</h3>
            <form method="POST">
                <?= Security::csrfField() ?>
                <div style="display:grid;grid-template-columns:40px 2fr 1fr 1fr 2fr;gap:12px;padding-bottom:8px;border-bottom:2px solid var(--border);font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;">
                    <div>#</div><div>Kriteria</div><div style="text-align:center;">Skor (1-4)</div><div style="text-align:center;">Bukti (%)</div><div>Gap / Kekurangan</div>
                </div>
                <?php foreach ($kriteriaList as $k): $kid=(int)$k['id_kriteria']; $old=$oldMap[$kid] ?? null; ?>
                <div class="kriteria-row">
                    <div class="kriteria-num"><?= (int)$k['nomor'] ?></div>
                    <div style="font-size:14px;font-weight:600;"><?= Security::e($k['nama_kriteria']) ?></div>
                    <div style="text-align:center;"><input type="number" step="0.01" min="1" max="4" name="skor_<?= $kid ?>" class="input-score" value="<?= $old ? Security::e((string)$old['skor_capaian']) : '1.00' ?>" required></div>
                    <div style="text-align:center;"><input type="number" min="0" max="100" name="bukti_<?= $kid ?>" class="input-bukti" value="<?= $old ? (int)$old['kelengkapan_bukti'] : 0 ?>"></div>
                    <div><input type="text" name="gap_<?= $kid ?>" class="input-gap" placeholder="Contoh: Kurang dokumen SOP..." value="<?= Security::e($old['catatan_gap'] ?? '') ?>"></div>
                </div>
                <?php endforeach; ?>
                <div style="margin-top:24px;text-align:right;">
                    <button type="submit" name="save_mock" class="btn btn-primary" style="padding:12px 32px;">🚀 Jalankan Simulasi & Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('radarChart');
    if (!ctx || typeof Chart === 'undefined') return;
    const labels = <?= json_encode($chartLabels) ?>;
    const dataSkor = <?= json_encode($chartData) ?>;
    const target = labels.map(() => 2.5);
    new Chart(ctx, {
        type: 'radar',
        data: { labels: labels, datasets: [
            { label: 'Capaian Skor Saat Ini', data: dataSkor, backgroundColor: 'rgba(201,162,39,0.2)', borderColor: '#C9A227', pointBackgroundColor: '#C9A227', pointBorderColor: '#fff', borderWidth: 2 },
            { label: 'Target Minimal (Baik)', data: target, backgroundColor: 'rgba(16,185,129,0.1)', borderColor: '#10B981', borderDash: [5,5], pointRadius: 0, borderWidth: 2 }
        ]},
        options: { scales: { r: { angleLines: { color: 'rgba(0,0,0,0.1)' }, grid: { color: 'rgba(0,0,0,0.05)' }, pointLabels: { font: { size: 11, weight: '600' }, color: '#334155' }, suggestedMin: 0, suggestedMax: 4, ticks: { stepSize: 1, backdropColor: 'transparent' } } }, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } } }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer-sim.php'; ?>