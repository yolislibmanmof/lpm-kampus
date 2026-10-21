<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();

$h = (int)date('H');
$sapa = $h < 10 ? 'Selamat Pagi' : ($h < 15 ? 'Selamat Siang' : ($h < 18 ? 'Selamat Sore' : 'Selamat Malam'));

// ================= DATA ASLI (TETAP DIPERTAHANKAN) =================
$totalUsers     = $db->query("SELECT COUNT(*) t FROM users")->fetch()['t'];
$totalDokumen   = $db->query("SELECT COUNT(*) t FROM dokumen_mutu")->fetch()['t'];
$totalPengaduan = $db->query("SELECT COUNT(*) t FROM pengaduan WHERE status = 'Baru'")->fetch()['t'];
$totalBerita    = $db->query("SELECT COUNT(*) t FROM berita WHERE is_published = 1")->fetch()['t'];

$roleDist = $db->query("SELECT r.nama_role, COUNT(*) c FROM users u JOIN roles r ON u.id_role = r.id_role GROUP BY r.id_role")->fetchAll();
$katDist  = $db->query("SELECT k.nama_kategori, COUNT(*) c FROM dokumen_mutu d LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori GROUP BY d.id_kategori")->fetchAll();
$pengaduanBaru = $db->query("SELECT * FROM pengaduan WHERE status = 'Baru' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$roleLabels = array_map('strval', array_column($roleDist, 'nama_role'));
$roleData   = array_map('intval', array_column($roleDist, 'c'));
$katLabels  = array_map('strval', array_column($katDist, 'nama_kategori'));
$katData    = array_map('intval', array_column($katDist, 'c'));

// ================= DATA BARU (SINKRON SKEMA ASLI) =================

// 1) Tren Pengaduan 6 bulan (isi bulan kosong agar garis kontinyu)
$trenMap = [];
for ($i = 5; $i >= 0; $i--) { $trenMap[date('Y-m', strtotime("-$i month"))] = 0; }
try {
    $rows = $db->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') bulan, COUNT(*) total
        FROM pengaduan
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY bulan
    ")->fetchAll();
    foreach ($rows as $r) { if (isset($trenMap[$r['bulan']])) $trenMap[$r['bulan']] = (int)$r['total']; }
} catch (Throwable $e) {}
$trenLabels = array_map(function ($k) { return date('M Y', strtotime($k . '-01')); }, array_keys($trenMap));
$trenTotal  = array_values($trenMap);

// 2) Statistik Akreditasi Prodi (kolom: tingkat, peringkat) + Akreditasi Internasional
$statsAkreditasi = ['Unggul' => 0, 'Baik Sekali' => 0, 'Baik' => 0, 'Proses' => 0];
try {
    $akred = $db->query("SELECT peringkat, COUNT(*) jumlah FROM akreditasi WHERE tingkat = 'Prodi' GROUP BY peringkat")->fetchAll();
    foreach ($akred as $a) {
        $p = (string)$a['peringkat'];
        if (in_array($p, ['Unggul', 'A'], true))            $statsAkreditasi['Unggul'] += (int)$a['jumlah'];
        elseif (in_array($p, ['Baik Sekali', 'B'], true))   $statsAkreditasi['Baik Sekali'] += (int)$a['jumlah'];
        elseif (in_array($p, ['Baik', 'C'], true))          $statsAkreditasi['Baik'] += (int)$a['jumlah'];
        else                                                $statsAkreditasi['Proses'] += (int)$a['jumlah'];
    }
} catch (Throwable $e) {}
$akredIntl = 0;
try { $akredIntl = (int)$db->query("SELECT COUNT(*) t FROM akreditasi_internasional")->fetch()['t']; } catch (Throwable $e) {}

// 3) Jadwal AMI + progres penugasan (relasi via penugasan_audit — BUKAN kolom di jadwal_ami)
$jadwalAmi = [];
try {
    $jadwalAmi = $db->query("
        SELECT j.id_jadwal, j.tahun_ami, j.tanggal_mulai, j.tanggal_selesai, j.status,
               (SELECT COUNT(*) FROM penugasan_audit pa WHERE pa.id_jadwal = j.id_jadwal) AS jml_tugas,
               (SELECT COUNT(*) FROM penugasan_audit pa WHERE pa.id_jadwal = j.id_jadwal AND pa.status = 'Selesai') AS jml_selesai,
               (SELECT GROUP_CONCAT(DISTINCT p.nama_prodi SEPARATOR ', ')
                  FROM penugasan_audit pa
                  JOIN prodi p ON p.id_prodi = pa.id_prodi
                 WHERE pa.id_jadwal = j.id_jadwal) AS prodi_list
        FROM jadwal_ami j
        WHERE j.status IN ('Draf', 'Berjalan')
        ORDER BY j.tahun_ami DESC, j.id_jadwal DESC
        LIMIT 5
    ")->fetchAll();
} catch (Throwable $e) {}

// 4) Reminder Review Dokumen (kolom asli: review_berikutnya, alur_status, judul_dokumen)
$reviewDokumen = [];
try {
    $reviewDokumen = $db->query("
        SELECT d.judul_dokumen, d.kode_dokumen, d.review_berikutnya, d.alur_status, k.nama_kategori
        FROM dokumen_mutu d
        LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori
        WHERE d.status = 'Aktif'
          AND ( (d.review_berikutnya IS NOT NULL AND d.review_berikutnya <= DATE_ADD(CURDATE(), INTERVAL 90 DAY))
                OR d.alur_status = 'Perlu Revisi' )
        ORDER BY (d.alur_status = 'Perlu Revisi') DESC, d.review_berikutnya ASC
        LIMIT 6
    ")->fetchAll();
} catch (Throwable $e) {}

// 5) Pengingat Tenggat (tabel pengingat_tenggat, dipakai juga oleh core/Reminder.php)
$tenggatList = [];
try {
    $tenggatList = $db->query("
        SELECT t.judul, t.jenis, t.tanggal, p.nama_prodi
        FROM pengingat_tenggat t
        LEFT JOIN prodi p ON p.id_prodi = t.id_prodi
        WHERE t.selesai = 0 AND t.tanggal IS NOT NULL
          AND t.tanggal <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        ORDER BY t.tanggal ASC
        LIMIT 5
    ")->fetchAll();
} catch (Throwable $e) {}

// 6) Temuan Audit per Kategori + verifikasi menunggu (enum asli: Mayor/Minor/Observasi/Kondisi Baik)
$temuanDist = [];
try {
    $temuanDist = $db->query("SELECT kategori, COUNT(*) c FROM temuan_audit WHERE kategori IS NOT NULL GROUP BY kategori")->fetchAll();
} catch (Throwable $e) {}
$temuanLabels = array_map('strval', array_column($temuanDist, 'kategori'));
$temuanData   = array_map('intval', array_column($temuanDist, 'c'));
$temuanMenunggu = 0;
try { $temuanMenunggu = (int)$db->query("SELECT COUNT(*) t FROM temuan_audit WHERE status_verifikasi = 'Menunggu'")->fetch()['t']; } catch (Throwable $e) {}

// 7) Tindak Lanjut RTM yang belum selesai (+ penghitungan terlambat)
$rtmList = [];
try {
    $rtmList = $db->query("
        SELECT tahun, keputusan, pic, tenggat, status
        FROM rtm_tindak_lanjut
        WHERE status <> 'Selesai'
        ORDER BY (tenggat IS NULL), tenggat ASC
        LIMIT 5
    ")->fetchAll();
} catch (Throwable $e) {}
$rtmOverdue = 0;
try { $rtmOverdue = (int)$db->query("SELECT COUNT(*) t FROM rtm_tindak_lanjut WHERE status <> 'Selesai' AND tenggat IS NOT NULL AND tenggat < CURDATE()")->fetch()['t']; } catch (Throwable $e) {}

// 8) Rata-rata Kepuasan per Aspek (tabel kuesioner, skor decimal)
$kuesDist = [];
try {
    $kuesDist = $db->query("SELECT aspek, ROUND(AVG(skor), 2) rata FROM kuesioner GROUP BY aspek ORDER BY rata DESC")->fetchAll();
} catch (Throwable $e) {}
$kuesLabels = array_map('strval', array_column($kuesDist, 'aspek'));
$kuesData   = array_map('floatval', array_column($kuesDist, 'rata'));

// 9) Log Aktivitas (tabel asli: activity_log — kolom nama_user, aksi, detail)
$logAktivitas = [];
try {
    $logAktivitas = $db->query("SELECT nama_user, aksi, detail, ip_address, created_at FROM activity_log ORDER BY created_at DESC LIMIT 8")->fetchAll();
} catch (Throwable $e) {}

// 10) Status Mail & Kesehatan Server
$mailAktif = 0;
try { $mailAktif = (int)$db->query("SELECT aktif FROM mail_settings WHERE id = 1")->fetch()['aktif']; } catch (Throwable $e) {}
$phpVersion  = PHP_VERSION;
$diskFree    = @disk_free_space(dirname(__DIR__, 2)) ? round(@disk_free_space(dirname(__DIR__, 2)) / 1073741824, 2) : 0;
$diskTotal   = @disk_total_space(dirname(__DIR__, 2)) ? round(@disk_total_space(dirname(__DIR__, 2)) / 1073741824, 2) : 0;
$diskPercent = ($diskTotal > 0) ? (int)round((($diskTotal - $diskFree) / $diskTotal) * 100) : 0;
$memoryUsage = round(memory_get_usage(true) / 1048576, 2);

$simTitle = 'Dashboard Admin';
$activeMenu = 'dashboard';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    .db-banner { background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: var(--radius-lg); padding: 36px 40px; color: #fff; position: relative; overflow: hidden; margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; box-shadow: var(--shadow-lg); }
    .db-banner::before { content: ''; position: absolute; width: 340px; height: 340px; border-radius: 50%; background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%); right: -80px; top: -120px; }
    .db-banner h2 { font-size: 26px; margin-bottom: 6px; position: relative; }
    .db-banner p { color: rgba(255,255,255,.78); position: relative; }

    /* ===== Reminder & Tenggat ===== */
    .reminder-item { padding: 14px 16px; background: var(--bg-light); border-radius: 10px; border-left: 4px solid var(--warning); display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 10px; }
    .reminder-item:last-child { margin-bottom: 0; }
    .reminder-item.critical { border-left-color: var(--danger); background: rgba(239,68,68,.06); }
    .reminder-info { flex: 1; min-width: 0; }
    .reminder-info strong { font-size: 14px; display: block; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .reminder-info small { color: var(--text-muted); font-size: 12px; }
    .reminder-badge { padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700; background: var(--warning); color: #fff; white-space: nowrap; }
    .reminder-badge.critical { background: var(--danger); }
    .reminder-badge.soon { background: var(--info); }

    /* ===== Timeline AMI ===== */
    .ami-timeline { position: relative; padding-left: 24px; }
    .ami-timeline::before { content: ''; position: absolute; left: 8px; top: 6px; bottom: 6px; width: 2px; background: var(--border); }
    .ami-item { position: relative; padding-bottom: 18px; }
    .ami-item:last-child { padding-bottom: 0; }
    .ami-item::before { content: ''; position: absolute; left: -20px; top: 6px; width: 12px; height: 12px; border-radius: 50%; background: var(--accent); border: 3px solid var(--bg-card); box-shadow: 0 0 0 2px var(--accent); }
    .ami-item.active::before { background: var(--success); box-shadow: 0 0 0 2px var(--success), 0 0 12px var(--success); animation: amiPulse 2s infinite; }
    @keyframes amiPulse { 0%,100% { opacity: 1; } 50% { opacity: .5; } }
    .ami-info strong { font-size: 14px; display: block; margin-bottom: 2px; }
    .ami-info small { color: var(--text-muted); font-size: 12px; display: block; }
    .ami-status { font-size: 11px; padding: 2px 8px; border-radius: 50px; background: var(--bg-light); color: var(--text-muted); font-weight: 700; white-space: nowrap; }
    .ami-status.active { background: rgba(16,185,129,.15); color: var(--success); }
    .ami-progress { height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; margin-top: 8px; }
    .ami-progress-fill { height: 100%; background: linear-gradient(90deg, var(--primary), var(--accent)); border-radius: 3px; }

    /* ===== Akreditasi ===== */
    .akred-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .akred-box { padding: 16px; background: var(--bg-light); border-radius: 12px; text-align: center; border: 1px solid var(--border); transition: .3s; }
    .akred-box:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .akred-box .num { font-size: 32px; font-weight: 800; line-height: 1; margin-bottom: 4px; }
    .akred-box .lbl { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }
    .akred-box.unggul .num { color: #D97706; }
    .akred-box.baik-sekali .num { color: #059669; }
    .akred-box.baik .num { color: #2563EB; }
    .akred-box.proses .num { color: var(--text-muted); }
    body.dark .akred-box.unggul .num { color: #FBBF24; }
    body.dark .akred-box.baik-sekali .num { color: #6EE7B7; }
    body.dark .akred-box.baik .num { color: #93C5FD; }

    /* ===== RTM ===== */
    .rtm-item { padding: 12px 14px; background: var(--bg-light); border-radius: 10px; margin-bottom: 10px; border-left: 4px solid var(--info); }
    .rtm-item:last-child { margin-bottom: 0; }
    .rtm-item.overdue { border-left-color: var(--danger); background: rgba(239,68,68,.06); }
    .rtm-item strong { font-size: 13.5px; display: block; margin-bottom: 3px; }
    .rtm-item small { color: var(--text-muted); font-size: 12px; }
    .rtm-badge { display: inline-block; margin-top: 6px; font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 50px; background: rgba(59,130,246,.12); color: #2563EB; }
    .rtm-badge.berjalan { background: rgba(16,185,129,.12); color: #059669; }
    .rtm-badge.tertunda { background: rgba(245,158,11,.15); color: #B45309; }
    body.dark .rtm-badge { color: #93C5FD; }
    body.dark .rtm-badge.berjalan { color: #6EE7B7; }
    body.dark .rtm-badge.tertunda { color: #FBBF24; }

    /* ===== Log Aktivitas ===== */
    .log-item { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); align-items: flex-start; }
    .log-item:last-child { border-bottom: none; }
    .log-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--bg-light); display: grid; place-items: center; font-size: 14px; flex-shrink: 0; }
    .log-info { flex: 1; min-width: 0; }
    .log-info strong { font-size: 13px; display: block; }
    .log-info small { color: var(--text-muted); font-size: 12px; }
    .log-time { font-size: 11px; color: var(--text-muted); white-space: nowrap; }

    /* ===== Kesehatan Sistem ===== */
    .health-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; }
    .health-item { padding: 12px; background: var(--bg-light); border-radius: 10px; }
    .health-item small { color: var(--text-muted); font-size: 11px; display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }
    .health-item strong { font-size: 14px; display: block; }
    .health-bar { height: 6px; background: var(--border); border-radius: 3px; overflow: hidden; margin-top: 6px; }
    .health-bar-fill { height: 100%; background: linear-gradient(90deg, var(--success), #6EE7B7); border-radius: 3px; }
    .health-bar-fill.warning { background: linear-gradient(90deg, var(--warning), #FBBF24); }
    .health-bar-fill.danger { background: linear-gradient(90deg, var(--danger), #FCA5A5); }
</style>

<!-- ================= BANNER (ASLI) ================= -->
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

<!-- ================= STAT CARDS (ASLI) ================= -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin-bottom:32px;">
    <div class="stat-card"><div class="stat-icon blue">👥</div><div><h3 style="font-size:28px;"><?= $totalUsers ?></h3><p class="text-muted">Total Pengguna</p></div></div>
    <div class="stat-card"><div class="stat-icon green">📁</div><div><h3 style="font-size:28px;"><?= $totalDokumen ?></h3><p class="text-muted">Dokumen Mutu</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">📩</div><div><h3 style="font-size:28px;"><?= $totalPengaduan ?></h3><p class="text-muted">Pengaduan Baru</p></div></div>
    <div class="stat-card"><div class="stat-icon red">📰</div><div><h3 style="font-size:28px;"><?= $totalBerita ?></h3><p class="text-muted">Berita Terbit</p></div></div>
</div>

<!-- ================= GRAFIK DISTRIBUSI (ASLI) ================= -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card"><h3 style="margin-bottom:20px;">👥 Distribusi Pengguna per Role</h3><canvas id="cRole"></canvas></div>
    <div class="card"><h3 style="margin-bottom:20px;">📁 Dokumen per Kategori</h3><canvas id="cKat"></canvas></div>
</div>

<!-- ================= 🆕 TREN PENGADUAN 6 BULAN ================= -->
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:20px;">📈 Tren Pengaduan Masyarakat (6 Bulan Terakhir)</h3>
    <canvas id="cTren" height="70"></canvas>
</div>

<!-- ================= PENGADUAN (ASLI) + 🆕 AKREDITASI ================= -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px;">
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

    <div class="card">
        <h3 style="margin-bottom:16px;">🏆 Akreditasi Prodi</h3>
        <div class="akred-grid">
            <div class="akred-box unggul"><div class="num"><?= $statsAkreditasi['Unggul'] ?></div><div class="lbl">Unggul / A</div></div>
            <div class="akred-box baik-sekali"><div class="num"><?= $statsAkreditasi['Baik Sekali'] ?></div><div class="lbl">Baik Sekali</div></div>
            <div class="akred-box baik"><div class="num"><?= $statsAkreditasi['Baik'] ?></div><div class="lbl">Baik / C</div></div>
            <div class="akred-box proses"><div class="num"><?= $statsAkreditasi['Proses'] ?></div><div class="lbl">Proses / Lain</div></div>
        </div>
        <div style="margin-top:12px;padding:12px 14px;background:var(--bg-light);border-radius:10px;display:flex;justify-content:space-between;align-items:center;">
            <small class="text-muted" style="font-weight:700;">🌍 Akreditasi Internasional</small>
            <strong style="font-size:18px;color:var(--accent);"><?= $akredIntl ?></strong>
        </div>
    </div>
</div>

<!-- ================= 🆕 JADWAL AMI + 🆕 REMINDER DOKUMEN/TENGGAT ================= -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3>📅 Siklus AMI Berjalan</h3>
            <a href="/sim/admin/jadwal.php" class="btn btn-outline" style="padding:6px 12px;font-size:12px;">Kelola</a>
        </div>
        <?php if (empty($jadwalAmi)): ?>
            <p class="text-muted" style="text-align:center;padding:24px 0;">Belum ada siklus AMI berstatus Draf/Berjalan.</p>
        <?php else: ?>
            <div class="ami-timeline">
                <?php foreach ($jadwalAmi as $j):
                    $isAktif = ($j['status'] === 'Berjalan');
                    $jmlTugas = (int)$j['jml_tugas'];
                    $jmlSelesai = (int)$j['jml_selesai'];
                    $prog = $jmlTugas > 0 ? (int)round(($jmlSelesai / $jmlTugas) * 100) : 0;
                ?>
                <div class="ami-item <?= $isAktif ? 'active' : '' ?>">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                        <div class="ami-info" style="flex:1;min-width:0;">
                            <strong>AMI Tahun <?= Security::e((string)$j['tahun_ami']) ?></strong>
                            <small><?= $j['tanggal_mulai'] ? date('d M Y', strtotime($j['tanggal_mulai'])) : 'Tanggal belum diatur' ?><?= $j['prodi_list'] ? ' • ' . Security::e(mb_strimwidth($j['prodi_list'], 0, 60, '…')) : '' ?></small>
                            <small>Penugasan: <?= $jmlSelesai ?>/<?= $jmlTugas ?> selesai</small>
                            <?php if ($jmlTugas > 0): ?>
                            <div class="ami-progress"><div class="ami-progress-fill" style="width:<?= $prog ?>%"></div></div>
                            <?php endif; ?>
                        </div>
                        <span class="ami-status <?= $isAktif ? 'active' : '' ?>"><?= $isAktif ? '● Berjalan' : 'Draf' ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3>🔔 Reminder Review & Tenggat</h3>
            <a href="/sim/admin/dokumen.php" class="btn btn-outline" style="padding:6px 12px;font-size:12px;">Kelola</a>
        </div>
        <?php if (empty($reviewDokumen) && empty($tenggatList)): ?>
            <p class="text-muted" style="text-align:center;padding:24px 0;">✅ Tidak ada dokumen atau tenggat yang memerlukan tindakan.</p>
        <?php else: ?>
            <?php foreach ($reviewDokumen as $d):
                $perluRevisi = ($d['alur_status'] === 'Perlu Revisi');
                $days = $d['review_berikutnya'] ? (int)floor((strtotime($d['review_berikutnya']) - time()) / 86400) : null;
                $critical = $perluRevisi || ($days !== null && $days <= 30);
                $soon = !$critical && $days !== null && $days <= 60;
            ?>
            <div class="reminder-item <?= $critical ? 'critical' : '' ?>">
                <div class="reminder-info">
                    <strong><?= Security::e($d['judul_dokumen']) ?></strong>
                    <small><?= Security::e($d['nama_kategori'] ?? '—') ?><?= $d['review_berikutnya'] ? ' • Review: ' . date('d M Y', strtotime($d['review_berikutnya'])) : '' ?></small>
                </div>
                <span class="reminder-badge <?= $critical ? 'critical' : ($soon ? 'soon' : '') ?>">
                    <?= $perluRevisi ? '⚠️ Perlu Revisi' : (($days !== null && $days < 0) ? 'Lewat ' . abs($days) . ' hari' : $days . ' hari') ?>
                </span>
            </div>
            <?php endforeach; ?>
            <?php foreach ($tenggatList as $t):
                $daysT = (int)floor((strtotime($t['tanggal']) - time()) / 86400);
            ?>
            <div class="reminder-item <?= $daysT <= 14 ? 'critical' : '' ?>">
                <div class="reminder-info">
                    <strong><?= Security::e($t['judul']) ?></strong>
                    <small><?= Security::e($t['jenis'] ?? 'Tenggat') ?><?= $t['nama_prodi'] ? ' • ' . Security::e($t['nama_prodi']) : '' ?> • <?= date('d M Y', strtotime($t['tanggal'])) ?></small>
                </div>
                <span class="reminder-badge <?= $daysT <= 14 ? 'critical' : '' ?>"><?= $daysT < 0 ? 'Lewat ' . abs($daysT) . ' hari' : 'H-' . $daysT ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ================= 🆕 TEMUAN AUDIT + 🆕 TINDAK LANJUT RTM ================= -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">🔍 Temuan Audit per Kategori</h3>
        <?php if (empty($temuanDist)): ?>
            <p class="text-muted" style="text-align:center;padding:24px 0;">Belum ada temuan audit tercatat.</p>
        <?php else: ?>
            <canvas id="cTemuan" height="180"></canvas>
        <?php endif; ?>
        <div style="margin-top:14px;padding:12px 14px;background:var(--bg-light);border-radius:10px;display:flex;justify-content:space-between;align-items:center;">
            <small class="text-muted" style="font-weight:700;">⏳ Menunggu Verifikasi Koreksi</small>
            <strong style="font-size:18px;<?= $temuanMenunggu > 0 ? 'color:var(--warning);' : 'color:var(--success);' ?>"><?= $temuanMenunggu ?></strong>
        </div>
    </div>

        <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3>📋 Tindak Lanjut RTM</h3>
            <?php if ($rtmOverdue > 0): ?><span class="reminder-badge critical"><?= $rtmOverdue ?> Terlambat</span><?php endif; ?>
        </div>
        <?php if (empty($rtmList)): ?>
            <p class="text-muted" style="text-align:center;padding:24px 0;">Semua tindak lanjut RTM telah selesai ✅</p>
        <?php else: ?>
            <?php foreach ($rtmList as $r):
                $overdue = $r['tenggat'] && strtotime($r['tenggat']) < time() && $r['status'] !== 'Selesai';
                $badgeCls = strtolower(str_replace(' ', '-', $r['status']));
            ?>
            <div class="rtm-item <?= $overdue ? 'overdue' : '' ?>">
                <strong><?= Security::e(mb_strimwidth($r['keputusan'], 0, 90, '…')) ?></strong>
                <small>PIC: <?= Security::e($r['pic'] ?? '—') ?> • Tenggat: <?= $r['tenggat'] ? date('d M Y', strtotime($r['tenggat'])) : '—' ?><?= $overdue ? ' • ⚠️ Terlambat' : '' ?></small><br>
                <span class="rtm-badge <?= $badgeCls ?>"><?= Security::e($r['status']) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ================= 🆕 KEPUASAN + 🆕 LOG AKTIVITAS ================= -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">😊 Rata-rata Kepuasan per Aspek</h3>
        <?php if (empty($kuesDist)): ?>
            <p class="text-muted" style="text-align:center;padding:24px 0;">Belum ada data kuesioner.</p>
        <?php else: ?>
            <canvas id="cKues" height="180"></canvas>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3 style="margin-bottom:16px;">📜 Log Aktivitas Sistem</h3>
        <?php if (empty($logAktivitas)): ?>
            <p class="text-muted" style="text-align:center;padding:24px 0;">Belum ada aktivitas tercatat.</p>
        <?php else: ?>
            <?php
            $iconMap = ['LOGIN'=>'🔐','LOGOUT'=>'🚪','LOGIN_GAGAL'=>'⛔','CREATE'=>'➕','UPDATE'=>'✏️','DELETE'=>'🗑️','UPLOAD'=>'📤','DOWNLOAD'=>'📥'];
            foreach ($logAktivitas as $log):
                $aksi = strtoupper((string)($log['aksi'] ?? ''));
                $icon = $iconMap[$aksi] ?? '📋';
            ?>
            <div class="log-item">
                <div class="log-icon"><?= $icon ?></div>
                <div class="log-info">
                    <strong><?= Security::e($log['nama_user'] ?? 'Sistem') ?> — <?= Security::e($log['aksi'] ?? '') ?></strong>
                    <small><?= Security::e(mb_strimwidth($log['detail'] ?? '—', 0, 70, '…')) ?></small>
                </div>
                <span class="log-time"><?= date('d/m H:i', strtotime($log['created_at'])) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ================= AKSI CEPAT (ASLI, DIPERKAYA) + INFO SISTEM (ASLI, DIPERKAYA) ================= -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">⚡ Aksi Cepat</h3>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;">
            <a href="/sim/admin/users.php" class="btn btn-primary" style="justify-content:center;">➕ Tambah User</a>
            <a href="/sim/admin/dokumen.php" class="btn btn-primary" style="justify-content:center;">📤 Upload Dokumen</a>
            <a href="/sim/admin/jadwal.php" class="btn btn-primary" style="justify-content:center;">📅 Buat Jadwal AMI</a>
            <a href="/sim/admin/berita.php" class="btn btn-primary" style="justify-content:center;">✍️ Tulis Berita</a>
            <a href="/sim/admin/pengaduan.php" class="btn btn-outline" style="justify-content:center;">📩 Kelola Pengaduan</a>
            <a href="/sim/index.php" class="btn btn-outline" style="justify-content:center;">🏠 Ke Beranda SIM</a>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-bottom:16px;">ℹ️ Informasi Sistem</h3>
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:14px;">
            <div style="padding:14px;background:var(--bg-light);border-radius:10px;"><small class="text-muted">Versi Sistem</small><p style="font-weight:700;">SIM-Mutu v4.0</p></div>
            <div style="padding:14px;background:var(--bg-light);border-radius:10px;"><small class="text-muted">Tanggal Hari Ini</small><p style="font-weight:700;"><?= date('d F Y') ?></p></div>
            <div style="padding:14px;background:var(--bg-light);border-radius:10px;"><small class="text-muted">Status Server</small><p style="font-weight:700;color:var(--success);">● Online</p></div>
        </div>
        <div class="health-grid">
            <div class="health-item"><small>PHP</small><strong><?= Security::e($phpVersion) ?></strong></div>
            <div class="health-item"><small>Notifikasi Mail</small><strong style="color:<?= $mailAktif ? 'var(--success)' : 'var(--text-muted)' ?>;"><?= $mailAktif ? '● Aktif' : '○ Nonaktif' ?></strong></div>
            <div class="health-item" style="grid-column:1/-1;">
                <small>Disk Server <?= $diskFree ?> / <?= $diskTotal ?> GB</small>
                <div class="health-bar"><div class="health-bar-fill <?= $diskPercent > 85 ? 'danger' : ($diskPercent > 70 ? 'warning' : '') ?>" style="width:<?= $diskPercent ?>%"></div></div>
            </div>
            <div class="health-item" style="grid-column:1/-1;"><small>Memori Proses</small><strong><?= $memoryUsage ?> MB</strong></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Chart Distribusi Role
    var cRole = document.getElementById('cRole');
    if (cRole) {
        try {
            new Chart(cRole, {
                type: 'doughnut',
                data: { labels: <?= json_encode($roleLabels) ?>,
                        datasets: [{ data: <?= json_encode($roleData) ?>, backgroundColor: ['#0F3D5C','#C9A227','#3B82F6','#10B981','#8B5CF6'], borderWidth: 4, borderColor: '#fff' }] },
                options: { plugins: { legend: { position: 'bottom' } } }
            });
        } catch (e) { console.error('Chart Role Error:', e); }
    }

    // 2. Chart Dokumen per Kategori
    var cKat = document.getElementById('cKat');
    if (cKat) {
        try {
            new Chart(cKat, {
                type: 'bar',
                data: { labels: <?= json_encode($katLabels) ?>,
                        datasets: [{ label: 'Dokumen', data: <?= json_encode($katData) ?>, backgroundColor: '#1A5A82', borderRadius: 8 }] }
            });
        } catch (e) { console.error('Chart Kat Error:', e); }
    }

    // 3. Chart Tren Pengaduan
    var cTren = document.getElementById('cTren');
    if (cTren) {
        try {
            new Chart(cTren, {
                type: 'line',
                data: { labels: <?= json_encode($trenLabels) ?>,
                        datasets: [{ label: 'Pengaduan', data: <?= json_encode($trenTotal) ?>, borderColor: '#C9A227', backgroundColor: 'rgba(201,162,39,.15)', fill: true, tension: .4, pointBackgroundColor: '#C9A227', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 5 }] },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
        } catch (e) { console.error('Chart Tren Error:', e); }
    }

    // 4. Chart Temuan Audit (Hanya jalan jika canvas ada)
    var cTemuan = document.getElementById('cTemuan');
    if (cTemuan) {
        try {
            new Chart(cTemuan, {
                type: 'doughnut',
                data: { labels: <?= json_encode($temuanLabels) ?>,
                        datasets: [{ data: <?= json_encode($temuanData) ?>, backgroundColor: ['#EF4444','#F59E0B','#3B82F6','#10B981'], borderWidth: 4, borderColor: '#fff' }] },
                options: { plugins: { legend: { position: 'bottom' } } }
            });
        } catch (e) { console.error('Chart Temuan Error:', e); }
    }

    // 5. Chart Kepuasan Kuesioner (Hanya jalan jika canvas ada)
    var cKues = document.getElementById('cKues');
    if (cKues) {
        try {
            new Chart(cKues, {
                type: 'bar',
                data: { labels: <?= json_encode($kuesLabels) ?>,
                        datasets: [{ label: 'Skor (maks 5)', data: <?= json_encode($kuesData) ?>, backgroundColor: '#10B981', borderRadius: 8 }] },
                options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { min: 0, max: 5 } } }
            });
        } catch (e) { console.error('Chart Kues Error:', e); }
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>