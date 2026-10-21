<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();

$h = (int)date('H');
$sapa = $h < 10 ? 'Selamat Pagi' : ($h < 15 ? 'Selamat Siang' : ($h < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'Dashboard Kaprodi';
$activeMenu = 'dashboard';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">⚠️ Akun Anda belum terhubung ke Program Studi. Hubungi Admin LPM.</div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

$namaProdi = $db->prepare("SELECT nama_prodi FROM prodi WHERE id_prodi = ?");
$namaProdi->execute([$prodiId]);
$namaProdi = $namaProdi->fetch()['nama_prodi'];

// EDP Progress
$edp = $db->prepare("SELECT COUNT(*) total, SUM(CASE WHEN status='Terkunci' THEN 1 ELSE 0 END) selesai FROM evaluasi_diri WHERE id_prodi=? AND tahun=?");
$edp->execute([$prodiId, date('Y')]);
$edp = $edp->fetch();
$progres = $edp['total'] > 0 ? round($edp['selesai'] / $edp['total'] * 100) : 0;

// Borang Count
$borangCount = $db->prepare("SELECT COUNT(*) t FROM borang_akreditasi WHERE id_prodi=?");
$borangCount->execute([$prodiId]);
$borangCount = $borangCount->fetch()['t'];

// Capaian per Standar
$capaian = $db->prepare("SELECT s.kode_standar, s.nama_standar, e.capaian, e.target FROM standar_mutu s LEFT JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.id_prodi = ? AND e.tahun = ? ORDER BY s.kode_standar");
$capaian->execute([$prodiId, date('Y')]);
$capaianList = $capaian->fetchAll();

// Temuan Audit Terbaru
$temuan = $db->prepare("SELECT t.*, s.nama_standar FROM temuan_audit t LEFT JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar WHERE pa.id_prodi = ? ORDER BY t.id_temuan DESC LIMIT 4");
$temuan->execute([$prodiId]);
$temuanList = $temuan->fetchAll();

// Statistik Mahasiswa
$statMhs = $db->prepare("SELECT tahun_akademik, peminat, daya_tampung, maba, jumlah_mahasiswa, lulus FROM statistik_mahasiswa WHERE id_prodi = ? ORDER BY tahun_akademik DESC LIMIT 1");
$statMhs->execute([$prodiId]);
$statMhs = $statMhs->fetch();

// Notifikasi Pending
$notifPending = $db->prepare("SELECT COUNT(*) t FROM pengingat_tenggat WHERE id_prodi = ? AND selesai = 0 AND tanggal <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
$notifPending->execute([$prodiId]);
$notifPending = $notifPending->fetch()['t'];
?>

<style>
    .db-banner { 
        background: linear-gradient(135deg, var(--primary), var(--primary-light)); 
        border-radius: var(--radius-lg); 
        padding: 40px 44px; 
        color: #fff; 
        position: relative; 
        overflow: hidden; 
        margin-bottom: 32px; 
        box-shadow: var(--shadow-lg);
    }
    .db-banner::before { 
        content: ''; 
        position: absolute; 
        width: 400px; 
        height: 400px; 
        border-radius: 50%; 
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%); 
        right: -100px; 
        top: -150px;
        animation: dbPulse 4s ease-in-out infinite;
    }
    @keyframes dbPulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.7; }
    }
    .db-banner h2 { 
        font-size: 28px; 
        margin-bottom: 8px; 
        position: relative;
        font-weight: 800;
    }
    .db-banner p { 
        color: rgba(255,255,255,.85); 
        position: relative;
        font-size: 15px;
    }
    .db-prog { 
        margin-top: 24px; 
        background: rgba(255,255,255,.2); 
        border-radius: 50px; 
        height: 14px; 
        overflow: hidden; 
        position: relative;
    }
    .db-prog > div { 
        height: 100%; 
        background: linear-gradient(90deg, var(--accent), var(--accent-light)); 
        border-radius: 50px; 
        transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .db-prog > div::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent);
        animation: dbShimmer 2s infinite;
    }
    @keyframes dbShimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .db-prog-text {
        margin-top: 8px;
        font-size: 13px;
        color: rgba(255,255,255,.9);
        font-weight: 600;
        position: relative;
    }
    
    .stat-card {
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,.1);
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }
    .qa-btn {
        padding: 20px;
        background: #fff;
        border: 2px solid var(--border);
        border-radius: 16px;
        text-decoration: none;
        color: var(--text-dark);
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .qa-btn:hover {
        border-color: var(--accent);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(201,162,39,.15);
    }
    .qa-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .qa-btn:nth-child(2) .qa-icon { background: linear-gradient(135deg, #10B981, #34D399); }
    .qa-btn:nth-child(3) .qa-icon { background: linear-gradient(135deg, #F59E0B, #FBBF24); }
    .qa-btn:nth-child(4) .qa-icon { background: linear-gradient(135deg, #EF4444, #F87171); }
    .qa-text strong { display: block; font-size: 14px; margin-bottom: 2px; }
    .qa-text small { color: var(--text-muted); font-size: 12px; }
    
    .alert-banner {
        padding: 16px 20px;
        background: linear-gradient(135deg, rgba(245,158,11,.1), rgba(245,158,11,.05));
        border-left: 4px solid var(--warning);
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.5s ease-out;
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .alert-banner .ic { font-size: 24px; }
    .alert-banner strong { display: block; font-size: 14px; color: var(--text-dark); }
    .alert-banner small { color: var(--text-muted); font-size: 12px; }
</style>

<div class="db-banner">
    <h2><?= $sapa ?>, <?= Security::e(Auth::user()['nama']) ?> 👋</h2>
    <p>Program Studi: <strong style="color:var(--accent-light);"><?= Security::e($namaProdi) ?></strong></p>
    <div class="db-prog">
        <div style="width:<?= $progres ?>%;"></div>
    </div>
    <div class="db-prog-text">
        📊 Progres Evaluasi Diri <?= date('Y') ?>: <strong><?= $progres ?>%</strong> 
        (<?= $edp['selesai'] ?> dari <?= $edp['total'] ?> standar selesai)
    </div>
</div>

<?php if ($notifPending > 0): ?>
<div class="alert-banner">
    <span class="ic"></span>
    <div>
        <strong><?= $notifPending ?> Tenggat Mendekati!</strong>
        <small>Anda memiliki <?= $notifPending ?> tugas yang harus diselesaikan dalam 7 hari ke depan.</small>
    </div>
</div>
<?php endif; ?>

<div class="quick-actions">
    <a href="/sim/prodi/edp.php" class="qa-btn">
        <div class="qa-icon">📝</div>
        <div class="qa-text">
            <strong>Evaluasi Diri</strong>
            <small>Isi EDP Tahun Ini</small>
        </div>
    </a>
    <a href="/sim/prodi/borang.php" class="qa-btn">
        <div class="qa-icon">☁️</div>
        <div class="qa-text">
            <strong>Cloud Borang</strong>
            <small><?= $borangCount ?> File Terupload</small>
        </div>
    </a>
    <a href="/sim/prodi/matriks.php" class="qa-btn">
        <div class="qa-icon">🗂️</div>
        <div class="qa-text">
            <strong>Matriks Bukti</strong>
            <small>Kelola Dokumen</small>
        </div>
    </a>
    <a href="/sim/prodi/riwayat.php" class="qa-btn">
        <div class="qa-icon">📜</div>
        <div class="qa-text">
            <strong>Riwayat Audit</strong>
            <small><?= count($temuanList) ?> Temuan</small>
        </div>
    </a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin-bottom:32px;">
    <div class="stat-card">
        <div class="stat-icon blue">📊</div>
        <div>
            <h3 style="font-size:28px;"><?= $edp['total'] ?? 0 ?></h3>
            <p class="text-muted">Standar Dievaluasi</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">☁️</div>
        <div>
            <h3 style="font-size:28px;"><?= $borangCount ?></h3>
            <p class="text-muted">File Borang</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold"></div>
        <div>
            <h3 style="font-size:28px;"><?= count($temuanList) ?></h3>
            <p class="text-muted">Temuan Audit</p>
        </div>
    </div>
    <?php if ($statMhs): ?>
    <div class="stat-card">
        <div class="stat-icon red">👥</div>
        <div>
            <h3 style="font-size:28px;"><?= $statMhs['jumlah_mahasiswa'] ?></h3>
            <p class="text-muted">Total Mahasiswa</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;">
    <div class="card">
        <h3 style="margin-bottom:20px;">📊 Capaian vs Target per Standar (<?= date('Y') ?>)</h3>
        <div style="height:320px;">
            <canvas id="cCap"></canvas>
        </div>
    </div>
    <div class="card">
        <h3 style="margin-bottom:20px;">⚠️ Temuan Audit Terbaru</h3>
        <?php if (empty($temuanList)): ?>
            <div style="text-align:center;padding:40px;">
                <div style="font-size:48px;margin-bottom:12px;"></div>
                <p class="text-muted">Belum ada temuan. Pertahankan kinerja excellent!</p>
            </div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($temuanList as $t): ?>
                <div style="padding:16px;background:var(--bg-light);border-radius:12px;border-left:4px solid <?= $t['kategori']==='Mayor' ? 'var(--danger)' : ($t['kategori']==='Minor' ? 'var(--warning)' : 'var(--info)') ?>;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <span class="badge" style="<?= $t['kategori']==='Mayor' ? 'background:#FEE2E2;color:#991B1B' : ($t['kategori']==='Minor' ? 'background:#FEF3C7;color:#92400E' : 'background:#DBEAFE;color:#1E40AF') ?>;font-weight:700;">
                            <?= Security::e($t['kategori']) ?>
                        </span>
                        <small style="color:var(--text-muted);font-size:11px;">
                            <?= Security::e($t['nama_standar'] ?? '—') ?>
                        </small>
                    </div>
                    <p style="font-size:13px;color:var(--text-muted);margin:0;line-height:1.5;">
                        <?= Security::e(mb_strimwidth($t['deskripsi_temuan'], 0, 100, '...')) ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="/sim/prodi/riwayat.php" class="btn btn-outline" style="margin-top:16px;width:100%;text-align:center;">
                Lihat Riwayat Lengkap →
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
// Chart Capaian dengan animasi
const ctx = document.getElementById('cCap').getContext('2d');
const capaianData = <?= json_encode(array_map(fn($r) => $r['capaian'] !== null ? round((float)$r['capaian'],1) : 0, $capaianList)) ?>;
const targetData = <?= json_encode(array_map(fn($r) => round((float)($r['target'] ?? 100),1), $capaianList)) ?>;
const labels = <?= json_encode(array_column($capaianList, 'kode_standar')) ?>;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Capaian (%)',
                data: capaianData,
                backgroundColor: 'rgba(15, 61, 92, 0.8)',
                borderColor: '#0F3D5C',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            },
            {
                label: 'Target (%)',
                data: targetData,
                backgroundColor: 'rgba(201, 162, 39, 0.3)',
                borderColor: '#C9A227',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                borderDash: [5, 5],
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1500,
            easing: 'easeOutQuart'
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 12, weight: '600' }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 61, 92, 0.95)',
                titleFont: { size: 13, weight: '700' },
                bodyFont: { size: 12 },
                padding: 12,
                cornerRadius: 8,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + '%';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) { return value + '%'; },
                    font: { size: 11 }
                },
                grid: {
                    color: 'rgba(0,0,0,.05)'
                }
            },
            x: {
                ticks: {
                    font: { size: 11, weight: '600' }
                },
                grid: {
                    display: false
                }
            }
        }
    }
});

// Animate progress bar on load
window.addEventListener('load', function() {
    const progBar = document.querySelector('.db-prog > div');
    if (progBar) {
        const width = progBar.style.width;
        progBar.style.width = '0%';
        setTimeout(() => {
            progBar.style.width = width;
        }, 300);
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>