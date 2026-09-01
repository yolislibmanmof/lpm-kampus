<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();

$h = (int)date('H');
$sapa = $h < 10 ? 'Selamat Pagi' : ($h < 15 ? 'Selamat Siang' : ($h < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$totalUsers = $db->query("SELECT COUNT(*) t FROM users")->fetch()['t'];
$totalDokumen = $db->query("SELECT COUNT(*) t FROM dokumen_mutu")->fetch()['t'];
$totalPengaduan = $db->query("SELECT COUNT(*) t FROM pengaduan WHERE status = 'Baru'")->fetch()['t'];
$totalBerita = $db->query("SELECT COUNT(*) t FROM berita WHERE is_published = 1")->fetch()['t'];

$roleDist = $db->query("SELECT r.nama_role, COUNT(*) c FROM users u JOIN roles r ON u.id_role = r.id_role GROUP BY r.id_role")->fetchAll();
$katDist = $db->query("SELECT k.nama_kategori, COUNT(*) c FROM dokumen_mutu d LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori GROUP BY d.id_kategori")->fetchAll();
$pengaduanBaru = $db->query("SELECT * FROM pengaduan WHERE status = 'Baru' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Cast angka agar Chart.js menerima number (bukan string dari PDO)
$roleLabels = array_map('strval', array_column($roleDist, 'nama_role'));
$roleData = array_map('intval', array_column($roleDist, 'c'));
$katLabels = array_map('strval', array_column($katDist, 'nama_kategori'));
$katData = array_map('intval', array_column($katDist, 'c'));

$simTitle = 'Dashboard Admin';
$activeMenu = 'dashboard';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    .db-banner { background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: var(--radius-lg); padding: 36px 40px; color: #fff; position: relative; overflow: hidden; margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; box-shadow: var(--shadow-lg); }
    .db-banner::before { content: ''; position: absolute; width: 340px; height: 340px; border-radius: 50%; background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%); right: -80px; top: -120px; }
    .db-banner h2 { font-size: 26px; margin-bottom: 6px; position: relative; }
    .db-banner p { color: rgba(255,255,255,.78); position: relative; }
</style>

<div class="db-banner">
    <div>
        <h2><?= $sapa ?>, <?= Security::e(Auth::user()['nama']) ?> 👋</h2>
        <p>Kendali penuh sistem penjaminan mutu ada di tangan Anda. Semua layanan beroperasi normal.</p>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;position:relative;">
        <a href="/sim/admin/users.php" class="btn btn-gold">➕ Tambah User</a>
        <a href="/sim/admin/dokumen.php" class="btn" style="background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.3);">📤 Upload Dokumen</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin-bottom:32px;">
    <div class="stat-card"><div class="stat-icon blue">👥</div><div><h3 style="font-size:28px;"><?= $totalUsers ?></h3><p class="text-muted">Total Pengguna</p></div></div>
    <div class="stat-card"><div class="stat-icon green">📁</div><div><h3 style="font-size:28px;"><?= $totalDokumen ?></h3><p class="text-muted">Dokumen Mutu</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">📩</div><div><h3 style="font-size:28px;"><?= $totalPengaduan ?></h3><p class="text-muted">Pengaduan Baru</p></div></div>
    <div class="stat-card"><div class="stat-icon red">📰</div><div><h3 style="font-size:28px;"><?= $totalBerita ?></h3><p class="text-muted">Berita Terbit</p></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card"><h3 style="margin-bottom:20px;">👥 Distribusi Pengguna per Role</h3><canvas id="cRole"></canvas></div>
    <div class="card"><h3 style="margin-bottom:20px;">📁 Dokumen per Kategori</h3><canvas id="cKat"></canvas></div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
    <div class="card">
        <h3 style="margin-bottom:20px;">📩 Pengaduan Terbaru</h3>
        <?php if (empty($pengaduanBaru)): ?>
            <p class="text-muted">Tidak ada pengaduan baru. Semua terkendali ✅</p>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($pengaduanBaru as $p): ?>
                <div style="padding:16px;background:var(--bg-light);border-radius:12px;border-left:4px solid var(--warning);display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div><strong style="font-size:14px;"><?= Security::e($p['nama']) ?></strong>
                        <p style="font-size:13px;color:var(--text-muted);margin-top:4px;"><?= Security::e($p['subjek']) ?></p></div>
                    <small class="text-muted"><?= date('d M Y', strtotime($p['created_at'])) ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="/sim/admin/pengaduan.php" class="btn btn-outline" style="margin-top:16px;">Kelola Semua →</a>
        <?php endif; ?>
    </div>

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div class="card">
            <h3 style="margin-bottom:16px;">⚡ Aksi Cepat</h3>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <a href="/sim/admin/users.php" class="btn btn-primary" style="justify-content:center;">➕ Tambah User</a>
                <a href="/sim/admin/dokumen.php" class="btn btn-primary" style="justify-content:center;">📤 Upload Dokumen</a>
                <a href="/sim/admin/jadwal.php" class="btn btn-primary" style="justify-content:center;">📅 Buat Jadwal AMI</a>
                <a href="/sim/admin/berita.php" class="btn btn-primary" style="justify-content:center;">✍️ Tulis Berita</a>
            </div>
        </div>
        <div class="card">
            <h3 style="margin-bottom:16px;">ℹ️ Informasi Sistem</h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="padding:14px;background:var(--bg-light);border-radius:10px;"><small class="text-muted">Versi Sistem</small><p style="font-weight:700;">SIM-Mutu v4.0</p></div>
                <div style="padding:14px;background:var(--bg-light);border-radius:10px;"><small class="text-muted">Tanggal Hari Ini</small><p style="font-weight:700;"><?= date('d F Y') ?></p></div>
                <div style="padding:14px;background:var(--bg-light);border-radius:10px;"><small class="text-muted">Status Server</small><p style="font-weight:700;color:var(--success);">● Online</p></div>
            </div>
        </div>
    </div>
</div>

<script>
try {
    new Chart(document.getElementById('cRole'), {
        type: 'doughnut',
        data: { labels: <?= json_encode($roleLabels) ?>,
                datasets: [{ data: <?= json_encode($roleData) ?>, backgroundColor: ['#0F3D5C','#C9A227','#3B82F6','#10B981'], borderWidth: 4, borderColor: '#fff' }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
} catch (e) {}
try {
    new Chart(document.getElementById('cKat'), {
        type: 'bar',
        data: { labels: <?= json_encode($katLabels) ?>,
                datasets: [{ label: 'Dokumen', data: <?= json_encode($katData) ?>, backgroundColor: '#1A5A82', borderRadius: 8 }] }
    });
} catch (e) {}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>