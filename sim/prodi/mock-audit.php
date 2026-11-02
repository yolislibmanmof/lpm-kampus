<?php
require_once __DIR__ . '/../../config/config.php';
Auth::requireRole([1, 3, 5]); // Admin, Kaprodi, GPM
require_once __DIR__ . '/../../core/MockAuditEngine.php';

// 🛠️ PERBAIKAN 1: Inisialisasi koneksi database
$db = Database::getInstance();

$user = Auth::user();

// 🛠️ PERBAIKAN 2: Ambil id_prodi dengan benar (karena tidak ada di session Auth)
if ($user['role'] == 1) {
    // Admin: ambil dari URL atau default ke 4
    $idProdi = (int)($_GET['prodi'] ?? 4);
} else {
    // Kaprodi/GPM: ambil dari tabel users berdasarkan ID user yang login
    $stmt = $db->prepare("SELECT id_prodi FROM users WHERE id_user = :id");
    $stmt->execute([':id' => $user['id']]);
    $userData = $stmt->fetch();
    $idProdi = $userData['id_prodi'] ?? null;
}

// Validasi: Pastikan user memiliki prodi yang valid
if (!$idProdi) {
    die("<div style='padding:40px;text-align:center;'><h2>⛔ Akses Ditolak</h2><p>Anda tidak terikat pada Program Studi manapun. Hubungi Admin LPM.</p><a href='/sim/index.php' class='btn btn-primary'>Kembali ke Dashboard</a></div>");
}

$tahunAjaran = date('Y') . '/' . (date('Y') + 1);

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mock'])) {
    $details = [];
    for ($i = 1; $i <= 9; $i++) {
        $details[$i] = [
            'skor' => $_POST["skor_$i"] ?? 1.00,
            'bukti' => $_POST["bukti_$i"] ?? 0,
            'gap' => $_POST["gap_$i"] ?? ''
        ];
    }
    $success = MockAuditEngine::saveSimulation((int)$idProdi, $tahunAjaran, $details, $user['id']);
    $msg = $success ? "✅ Simulasi berhasil disimpan dan peringkat diperbarui!" : "❌ Gagal menyimpan simulasi.";
}

$simData = MockAuditEngine::getLatestSimulation((int)$idProdi);
$prediksi = $simData ? MockAuditEngine::predictRank((float)$simData['total_skor']) : null;

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
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <div>
            <h2 style="margin:0;">🎯 Simulasi Pre-Akreditasi (Mock Audit)</h2>
            <p class="text-muted" style="margin:4px 0 0;">Evaluasi kesiapan akreditasi berdasarkan data real-time Sistem.</p>
        </div>
        <form method="POST">
            <button type="submit" name="save_mock" class="btn btn-primary">💾 Simpan Hasil Simulasi</button>
        </form>
    </div>

    <?php if (isset($msg)): ?>
        <div class="card" style="padding:16px;margin-bottom:20px;background:<?= strpos($msg, '✅') !== false ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)' ?>;border:1px solid <?= strpos($msg, '✅') !== false ? '#10B981' : '#EF4444' ?>;border-radius:12px;">
            <?= $msg ?>
        </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px;">
        <!-- PANEL KIRI: HASIL PREDIKSI & RADAR CHART -->
        <div>
            <div class="mock-hero" style="background:<?= $prediksi['bg'] ?? 'var(--bg-light)' ?>;border:1px solid <?= $prediksi['color'] ?? 'var(--border)' ?>40;">
                <small style="text-transform:uppercase;letter-spacing:1px;font-weight:700;color:var(--text-muted);">Prediksi Peringkat Saat Ini</small>
                <div class="mock-score" style="color:<?= $prediksi['color'] ?? 'var(--text-muted)' ?>;">
                    <?= $simData ? number_format((float)$simData['total_skor'], 2) : '0.00' ?>
                </div>
                <?php if ($prediksi): ?>
                    <span class="mock-badge" style="background:<?= $prediksi['color'] ?>;color:#fff;"><?= $prediksi['peringkat'] ?></span>
                    <p style="margin-top:16px;font-size:14px;color:var(--text-dark);"><?= $prediksi['pesan'] ?></p>
                <?php else: ?>
                    <p style="margin-top:16px;font-size:14px;color:var(--text-muted);">Belum ada data simulasi. Silakan isi penilaian di sebelah kanan.</p>
                <?php endif; ?>
            </div>

            <div class="card" style="padding:20px;">
                <h4 style="margin:0 0 16px;">📊 Radar 9 Kriteria</h4>
                <canvas id="radarChart" height="250"></canvas>
            </div>
        </div>

        <!-- PANEL KANAN: FORM INPUT PENILAIAN -->
        <div class="card" style="padding:24px;">
            <h3 style="margin:0 0 20px;">📝 Input Penilaian Mandiri</h3>
            <form method="POST">
                <div style="display:grid;grid-template-columns:40px 2fr 1fr 1fr 2fr;gap:12px;padding-bottom:8px;border-bottom:2px solid var(--border);font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;">
                    <div>#</div>
                    <div>Kriteria</div>
                    <div style="text-align:center;">Skor (1-4)</div>
                    <div style="text-align:center;">Bukti (%)</div>
                    <div>Gap / Kekurangan</div>
                </div>

                <?php 
                // Ambil nama kriteria dari DB
                $kriteriaList = $db->query("SELECT id_kriteria, nomor, nama_kriteria FROM kriteria_akreditasi ORDER BY nomor ASC")->fetchAll();
                foreach ($kriteriaList as $k): 
                    // Cari data lama jika ada
                    $old = null;
                    if ($simData) {
                        foreach ($simData['details'] as $d) {
                            if ($d['id_kriteria'] == $k['id_kriteria']) $old = $d;
                        }
                    }
                ?>
                <div class="kriteria-row">
                    <div class="kriteria-num"><?= $k['nomor'] ?></div>
                    <div style="font-size:14px;font-weight:600;"><?= Security::e($k['nama_kriteria']) ?></div>
                    <div style="text-align:center;">
                        <input type="number" step="0.01" min="1" max="4" name="skor_<?= $k['id_kriteria'] ?>" class="input-score" value="<?= $old ? $old['skor_capaian'] : '1.00' ?>" required>
                    </div>
                    <div style="text-align:center;">
                        <input type="number" min="0" max="100" name="bukti_<?= $k['id_kriteria'] ?>" class="input-bukti" value="<?= $old ? $old['kelengkapan_bukti'] : '0' ?>">
                    </div>
                    <div>
                        <input type="text" name="gap_<?= $k['id_kriteria'] ?>" class="input-gap" placeholder="Contoh: Kurang dokumen SOP..." value="<?= Security::e($old['catatan_gap'] ?? '') ?>">
                    </div>
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
    if (ctx) {
        // Siapkan data untuk chart
        const labels = [<?php foreach($kriteriaList as $k) { echo "'" . Security::e($k['nama_kriteria']) . "',"; } ?>];
        const dataSkor = [<?php foreach($kriteriaList as $k) { echo $old ? $old['skor_capaian'] : '1.00'; echo ","; } ?>]; // Fallback ke data terakhir atau 1.00
        
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Capaian Skor Saat Ini',
                    data: dataSkor,
                    backgroundColor: 'rgba(201, 162, 39, 0.2)',
                    borderColor: '#C9A227',
                    pointBackgroundColor: '#C9A227',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#C9A227',
                    borderWidth: 2
                }, {
                    label: 'Target Minimal (Baik)',
                    data: [2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5],
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderColor: '#10B981',
                    borderDash: [5, 5],
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: {
                scales: {
                    r: {
                        angleLines: { color: 'rgba(0,0,0,0.1)' },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        pointLabels: { font: { size: 11, weight: '600' }, color: 'var(--text-dark)' },
                        suggestedMin: 0,
                        suggestedMax: 4,
                        ticks: { stepSize: 1, backdropColor: 'transparent' }
                    }
                },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer-sim.php'; ?>