<?php
$pageTitle = 'Status Akreditasi';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();

$db = Database::getInstance();

$tingkat = $_GET['tingkat'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$query = "SELECT a.*, p.nama_prodi, p.kode_prodi, f.nama_fakultas
          FROM akreditasi a
          LEFT JOIN prodi p ON a.id_prodi = p.id_prodi
          LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
          WHERE 1=1";
$params = [];
if ($tingkat !== 'all') { $query .= " AND a.tingkat = :tingkat"; $params[':tingkat'] = $tingkat; }
if ($search) { $query .= " AND (p.nama_prodi LIKE :s1 OR f.nama_fakultas LIKE :s2)"; $params[':s1'] = "%$search%"; $params[':s2'] = "%$search%"; }
$query .= " ORDER BY a.tingkat DESC, p.nama_prodi ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll();

$stats = $db->query("SELECT COUNT(*) total,
    SUM(CASE WHEN peringkat IN ('Unggul','A') THEN 1 ELSE 0 END) unggul,
    SUM(CASE WHEN peringkat IN ('Baik Sekali','B') THEN 1 ELSE 0 END) baik_sekali,
    SUM(CASE WHEN peringkat IN ('Baik','C') THEN 1 ELSE 0 END) baik
    FROM akreditasi WHERE tingkat = 'Prodi'")->fetch();

$dist = $db->query("SELECT peringkat, COUNT(*) j FROM akreditasi WHERE tingkat = 'Prodi' GROUP BY peringkat")->fetchAll();
$totalDist = array_sum(array_column($dist, 'j'));

$marq = [];
foreach (array_slice($data, 0, 8) as $m) {
    $marq[] = ($m['nama_prodi'] ?? 'Institusi') . ' — ' . ($m['peringkat'] ?? '');
}
if (empty($marq)) {
    $marq = ['Penjaminan Mutu', 'Akreditasi Unggul', 'BAN-PT / LAM', 'SN-Dikti', 'PPEPP', 'Budaya Mutu'];
}

function akzColor($p) {
    $p = strtolower($p);
    if (in_array($p, ['unggul', 'a'])) return '#10B981';
    if (in_array($p, ['baik sekali', 'b'])) return '#3B82F6';
    if (in_array($p, ['baik', 'c'])) return '#F59E0B';
    return '#94A3B8';
}
function akzLevel($p) {
    $p = strtolower($p);
    if (in_array($p, ['unggul', 'a'])) return 'unggul';
    if (in_array($p, ['baik sekali', 'b'])) return 'bs';
    if (in_array($p, ['baik', 'c'])) return 'baik';
    return 'def';
}

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   AKREDITASI v13 — CRAZY ANIMATIONS, FAIL-SAFE
   Semua animasi inti = pure CSS (jalan tanpa JS)
============================================================ */
.akz-wrap { --eo: cubic-bezier(.22, 1, .36, 1); --es: cubic-bezier(.34, 1.56, .64, 1); }

/* ===== HERO + AURORA CONIC BERPUTAR ===== */
.akz-hero {
    position: relative; overflow: hidden; min-height: 62vh;
    display: flex; align-items: center; padding: 150px 0 100px;
    background: #061D2E; color: #fff;
}
.akz-aurora {
    position: absolute; inset: -30%; z-index: 0; opacity: .5; filter: blur(70px);
    background: conic-gradient(from 0deg at 50% 50%,
        #061D2E, #C9A227 15%, #0F3D5C 30%, #10B981 45%, #0F3D5C 60%, #E8C55A 75%, #061D2E 90%);
    animation: akzSpin 26s linear infinite;
}
@keyframes akzSpin { to { transform: rotate(360deg); } }
.akz-scan {
    position: absolute; inset: 0; z-index: 1; pointer-events: none; opacity: .25;
    background:
        repeating-linear-gradient(0deg, rgba(255,255,255,.06) 0 1px, transparent 1px 90px),
        repeating-linear-gradient(90deg, rgba(255,255,255,.06) 0 1px, transparent 1px 90px);
    animation: akzScan 8s linear infinite;
}
@keyframes akzScan { to { background-position: 0 90px, 90px 0; } }

/* Partikel emas melayang (CSS murni) */
.akz-p { position: absolute; inset: 0; z-index: 1; pointer-events: none; }
.akz-p span {
    position: absolute; width: 6px; height: 6px; border-radius: 50%;
    background: #E8C55A; box-shadow: 0 0 12px #E8C55A;
    animation: akzFloat 9s ease-in-out infinite;
}
.akz-p span:nth-child(1) { left: 8%; top: 24%; animation-delay: 0s; }
.akz-p span:nth-child(2) { left: 18%; top: 68%; animation-delay: -1s; width: 4px; height: 4px; }
.akz-p span:nth-child(3) { left: 28%; top: 38%; animation-delay: -2s; }
.akz-p span:nth-child(4) { left: 38%; top: 80%; animation-delay: -3s; width: 5px; height: 5px; }
.akz-p span:nth-child(5) { left: 50%; top: 20%; animation-delay: -4s; }
.akz-p span:nth-child(6) { left: 62%; top: 70%; animation-delay: -5s; width: 4px; height: 4px; }
.akz-p span:nth-child(7) { left: 72%; top: 32%; animation-delay: -6s; }
.akz-p span:nth-child(8) { left: 82%; top: 60%; animation-delay: -7s; width: 5px; height: 5px; }
.akz-p span:nth-child(9) { left: 90%; top: 26%; animation-delay: -8s; }
.akz-p span:nth-child(10) { left: 12%; top: 50%; animation-delay: -2.5s; width: 3px; height: 3px; }
.akz-p span:nth-child(11) { left: 56%; top: 46%; animation-delay: -5.5s; width: 3px; height: 3px; }
.akz-p span:nth-child(12) { left: 86%; top: 82%; animation-delay: -7.5s; }
@keyframes akzFloat {
    0%, 100% { transform: translateY(0) translateX(0); opacity: .9; }
    25% { transform: translateY(-26px) translateX(10px); opacity: .5; }
    50% { transform: translateY(-12px) translateX(-12px); opacity: 1; }
    75% { transform: translateY(-30px) translateX(6px); opacity: .6; }
}

.akz-hero-inner { position: relative; z-index: 3; max-width: 900px; margin: 0 auto; text-align: center; padding: 0 24px; }
.akz-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.18);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    margin-bottom: 26px; position: relative; overflow: hidden;
}
.akz-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.5), transparent);
    animation: akzShimmer 3s ease-in-out infinite;
}
@keyframes akzShimmer { to { left: 200%; } }
.akz-badge-dot { width: 8px; height: 8px; border-radius: 50%; background: #10B981; box-shadow: 0 0 14px #10B981; animation: akzPulse 2s ease infinite; }
@keyframes akzPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.akz-hero h1 { font-size: clamp(38px, 6vw, 76px); font-weight: 800; line-height: 1.05; margin: 0 0 20px; letter-spacing: -.04em; color: #fff; }
.akz-hero h1 .gr {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: akzShine 4s linear infinite; font-style: italic;
}
@keyframes akzShine { to { background-position: 200% center; } }
.akz-lead { font-size: clamp(15px, 1.5vw, 18px); color: rgba(255,255,255,.75); max-width: 640px; margin: 0 auto 40px; }

/* Search glass */
.akz-search { display: flex; gap: 10px; max-width: 760px; margin: 0 auto; flex-wrap: wrap; }
.akz-search input, .akz-search select {
    padding: 14px 22px; border-radius: 50px; border: 1px solid rgba(255,255,255,.25);
    background: rgba(255,255,255,.1); backdrop-filter: blur(10px);
    color: #fff; font-family: inherit; font-size: 14px; outline: none; transition: .3s;
}
.akz-search input { flex: 1; min-width: 220px; }
.akz-search input::placeholder { color: rgba(255,255,255,.55); }
.akz-search input:focus, .akz-search select:focus { border-color: #E8C55A; box-shadow: 0 0 0 4px rgba(201,162,39,.2); }
.akz-search select option { color: #0F3D5C; background: #fff; }
.akz-search button {
    padding: 14px 30px; border-radius: 50px; border: none; cursor: pointer;
    background: linear-gradient(135deg, #C9A227, #E8C55A); color: #092A40;
    font-weight: 800; font-size: 14px; font-family: inherit; transition: .3s;
    box-shadow: 0 8px 24px rgba(201,162,39,.4);
}
.akz-search button:hover { transform: translateY(-3px) scale(1.03); box-shadow: 0 14px 34px rgba(201,162,39,.6); }

/* ===== MARQUEE TICKER ===== */
.akz-marquee { overflow: hidden; background: #092A40; padding: 13px 0; border-top: 1px solid rgba(201,162,39,.25); border-bottom: 1px solid rgba(201,162,39,.25); }
.akz-track { display: flex; gap: 56px; width: max-content; animation: akzScroll 30s linear infinite; }
.akz-marquee:hover .akz-track { animation-play-state: paused; }
.akz-track b { color: rgba(255,255,255,.8); font-size: 12.5px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; white-space: nowrap; }
.akz-track b i { color: #E8C55A; font-style: normal; margin-right: 10px; }
@keyframes akzScroll { to { transform: translateX(-50%); } }

/* ===== STATS + AURA CONIC BERPUTAR ===== */
.akz-sec { padding: 70px 0; }
.akz-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.akz-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 22px; }
.akz-stat {
    position: relative; z-index: 0; background: #fff; border-radius: 20px; padding: 28px 24px;
    border: 1px solid #E2E8F0; transition: transform .4s var(--eo), box-shadow .4s;
}
.akz-stat:hover { transform: translateY(-6px) scale(1.02); box-shadow: 0 20px 50px rgba(15,61,92,.15); }
.akz-stat.glow::before {
    content: ''; position: absolute; inset: -4px; border-radius: 24px; z-index: -1;
    background: conic-gradient(#C9A227, #10B981, #3B82F6, #E8C55A, #C9A227);
    animation: akzSpin 5s linear infinite; filter: blur(12px); opacity: .65;
}
.akz-stat-ic {
    width: 52px; height: 52px; border-radius: 14px; margin-bottom: 16px;
    display: grid; place-items: center; font-size: 22px; color: #fff;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    animation: akzBounce 3s ease-in-out infinite;
}
.akz-stat:nth-child(2) .akz-stat-ic { background: linear-gradient(135deg, #10B981, #34D399); animation-delay: -.5s; }
.akz-stat:nth-child(3) .akz-stat-ic { background: linear-gradient(135deg, #3B82F6, #60A5FA); animation-delay: -1s; }
.akz-stat:nth-child(4) .akz-stat-ic { background: linear-gradient(135deg, #F59E0B, #FBBF24); animation-delay: -1.5s; }
@keyframes akzBounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
.akz-num { font-size: clamp(34px, 4vw, 52px); font-weight: 800; color: #092A40; line-height: 1; margin: 0 0 6px; letter-spacing: -.03em; }
.akz-lbl { font-size: 12px; color: #64748B; letter-spacing: 1px; text-transform: uppercase; font-weight: 700; }

/* ===== PANEL + BAR STRIPES BERGERAK ===== */
.akz-panel {
    background: #fff; border-radius: 20px; border: 1px solid #E2E8F0;
    box-shadow: 0 6px 24px rgba(15,61,92,.06); padding: 28px; margin-bottom: 32px;
}
.akz-panel h3 { font-size: 17px; color: #092A40; margin: 0 0 20px; }
.akz-bar-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.akz-bar-row:last-child { margin-bottom: 0; }
.akz-bar-label { width: 110px; font-size: 12.5px; font-weight: 700; color: #1E293B; }
.akz-bar-track { flex: 1; height: 14px; border-radius: 8px; background: #EEF2F7; overflow: hidden; }
.akz-bar-fill {
    height: 100%; border-radius: 8px; position: relative; overflow: hidden;
    transform-origin: left; animation: akzGrow 1.4s var(--eo) backwards;
}
@keyframes akzGrow { from { transform: scaleX(0); } }
.akz-bar-fill::after {
    content: ''; position: absolute; inset: 0;
    background: repeating-linear-gradient(45deg, rgba(255,255,255,.3) 0 8px, transparent 8px 16px);
    animation: akzStripe .8s linear infinite;
}
@keyframes akzStripe { to { background-position: 23px 0; } }
.akz-bar-num { width: 32px; text-align: right; font-weight: 800; font-size: 15px; color: #092A40; }

/* ===== PILLS ===== */
.akz-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
.akz-pill {
    padding: 10px 20px; border-radius: 50px; font-weight: 700; font-size: 13px;
    text-decoration: none; border: 1.5px solid #E2E8F0; color: #1E293B; background: #fff;
    display: inline-flex; align-items: center; gap: 8px; transition: .3s var(--eo);
}
.akz-pill:hover { border-color: rgba(201,162,39,.5); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,61,92,.1); }
.akz-pill.on { background: linear-gradient(135deg, #C9A227, #E8C55A); border-color: transparent; color: #092A40; box-shadow: 0 10px 28px rgba(201,162,39,.4); }
.akz-pill-c { padding: 1px 9px; border-radius: 50px; font-size: 11px; font-weight: 800; background: rgba(15,61,92,.08); color: #0F3D5C; }
.akz-pill.on .akz-pill-c { background: rgba(255,255,255,.25); color: #092A40; }

/* ===== KARTU: stagger masuk + holo shine + tilt ===== */
.akz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 20px; }
.akz-card {
    position: relative; overflow: hidden;
    background: #fff; border: 1px solid #E2E8F0; border-left: 5px solid #94A3B8;
    border-radius: 16px; padding: 22px; box-shadow: 0 4px 20px rgba(15,61,92,.05);
    transition: transform .4s var(--eo), box-shadow .4s;
    animation: akzUp .7s var(--eo) backwards;
}
.akz-grid .akz-card:nth-child(1) { animation-delay: .05s; }
.akz-grid .akz-card:nth-child(2) { animation-delay: .12s; }
.akz-grid .akz-card:nth-child(3) { animation-delay: .19s; }
.akz-grid .akz-card:nth-child(4) { animation-delay: .26s; }
.akz-grid .akz-card:nth-child(5) { animation-delay: .33s; }
.akz-grid .akz-card:nth-child(6) { animation-delay: .40s; }
.akz-grid .akz-card:nth-child(7) { animation-delay: .47s; }
.akz-grid .akz-card:nth-child(8) { animation-delay: .54s; }
.akz-grid .akz-card:nth-child(n+9) { animation-delay: .6s; }
@keyframes akzUp { from { opacity: 0; transform: translateY(34px) scale(.97); } }
.akz-card::after {
    content: ''; position: absolute; top: 0; left: -80%; width: 50%; height: 100%;
    background: linear-gradient(105deg, transparent, rgba(201,162,39,.18), transparent);
    transition: left .7s ease; pointer-events: none;
}
.akz-card:hover::after { left: 130%; }
.akz-card:hover { transform: translateY(-6px); box-shadow: 0 24px 55px rgba(15,61,92,.16); }
.akz-card.unggul { border-left-color: #10B981; }
.akz-card.bs { border-left-color: #3B82F6; }
.akz-card.baik { border-left-color: #F59E0B; }

.akz-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
.akz-card h4 { font-size: 16px; color: #092A40; margin: 0 0 4px; line-height: 1.3; }
.akz-card .fak { color: #64748B; font-size: 12px; font-weight: 600; }
.akz-rank {
    min-width: 46px; height: 46px; border-radius: 12px; display: grid; place-items: center;
    font-weight: 900; font-size: 16px; color: #fff; flex-shrink: 0;
}
.akz-card.unggul .akz-rank { background: linear-gradient(135deg, #10B981, #34D399); box-shadow: 0 6px 18px rgba(16,185,129,.4); }
.akz-card.bs .akz-rank { background: linear-gradient(135deg, #3B82F6, #60A5FA); box-shadow: 0 6px 18px rgba(59,130,246,.4); }
.akz-card.baik .akz-rank { background: linear-gradient(135deg, #F59E0B, #FBBF24); box-shadow: 0 6px 18px rgba(245,158,11,.4); }
.akz-card.def .akz-rank { background: linear-gradient(135deg, #64748B, #94A3B8); }

.akz-card-meta { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding-top: 12px; border-top: 1px dashed #E2E8F0; font-size: 12.5px; color: #64748B; font-weight: 600; }
.akz-days { display: inline-block; margin-top: 12px; padding: 5px 12px; border-radius: 50px; font-size: 11.5px; font-weight: 800; }
.akz-days.ok { background: #D1FAE5; color: #065F46; }
.akz-days.warn { background: #FEF3C7; color: #92400E; animation: akzPulseChip 1.6s ease infinite; }
.akz-days.exp { background: #FEE2E2; color: #991B1B; animation: akzPulseChip 1.2s ease infinite; }
@keyframes akzPulseChip { 0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,.35); } 50% { box-shadow: 0 0 0 8px rgba(239,68,68,0); } }

.akz-empty { grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #fff; border: 2px dashed #E2E8F0; border-radius: 20px; color: #64748B; }

/* ===== NOTE BORDER ANIMASI ===== */
.akz-note {
    margin-top: 28px; padding: 18px 24px; border-radius: 16px; position: relative;
    background: #fff; font-size: 14px; color: #64748B; z-index: 0;
}
.akz-note::before {
    content: ''; position: absolute; inset: -2px; border-radius: 18px; z-index: -1;
    background: linear-gradient(90deg, #C9A227, #0F3D5C, #10B981, #C9A227);
    background-size: 300% 100%; animation: akzBorder 6s linear infinite;
}
@keyframes akzBorder { to { background-position: 300% 0; } }
.akz-note strong { color: #C9A227; }

@media (max-width: 640px) {
    .akz-hero { padding: 120px 0 70px; }
    .akz-bar-label { width: 80px; }
}
</style>

<div class="akz-wrap">

<!-- ===== HERO ===== -->
<section class="akz-hero" id="akzHero">
    <div class="akz-aurora"></div>
    <div class="akz-scan"></div>
    <div class="akz-p">
        <span></span><span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span><span></span>
    </div>
    <div class="akz-hero-inner">
        <span class="akz-badge"><span class="akz-badge-dot"></span> Transparansi Mutu Publik</span>
        <h1>Status <span class="gr" id="akzWord">Akreditasi</span><br>Program Studi</h1>
        <p class="akz-lead">Informasi resmi status akreditasi prodi & institusi — diperbarui berkala oleh LPM.</p>
        <form method="GET" class="akz-search">
            <input type="text" name="search" placeholder="🔍 Cari program studi / fakultas..." value="<?= Security::e($search) ?>">
            <select name="tingkat">
                <option value="all" <?= $tingkat === 'all' ? 'selected' : '' ?>>Semua Tingkat</option>
                <option value="Prodi" <?= $tingkat === 'Prodi' ? 'selected' : '' ?>>Program Studi</option>
                <option value="Institusi" <?= $tingkat === 'Institusi' ? 'selected' : '' ?>>Institusi</option>
            </select>
            <button type="submit">🔍 Cari</button>
        </form>
    </div>
</section>

<!-- ===== MARQUEE ===== -->
<div class="akz-marquee">
    <div class="akz-track">
        <?php for ($i = 0; $i < 2; $i++): foreach ($marq as $mm): ?>
            <b><i>✦</i><?= Security::e($mm) ?></b>
        <?php endforeach; endfor; ?>
    </div>
</div>

<!-- ===== STATS ===== -->
<section class="akz-sec">
    <div class="akz-container">
        <div class="akz-stats">
            <div class="akz-stat glow">
                <div class="akz-stat-ic">🏫</div>
                <div class="akz-num" data-count="<?= (int)($stats['total'] ?? 0) ?>"><?= (int)($stats['total'] ?? 0) ?></div>
                <div class="akz-lbl">Total Terakreditasi</div>
            </div>
            <div class="akz-stat">
                <div class="akz-stat-ic">🥇</div>
                <div class="akz-num" data-count="<?= (int)($stats['unggul'] ?? 0) ?>"><?= (int)($stats['unggul'] ?? 0) ?></div>
                <div class="akz-lbl">Unggul / A</div>
            </div>
            <div class="akz-stat">
                <div class="akz-stat-ic">🥈</div>
                <div class="akz-num" data-count="<?= (int)($stats['baik_sekali'] ?? 0) ?>"><?= (int)($stats['baik_sekali'] ?? 0) ?></div>
                <div class="akz-lbl">Baik Sekali / B</div>
            </div>
            <div class="akz-stat">
                <div class="akz-stat-ic">🥉</div>
                <div class="akz-num" data-count="<?= (int)($stats['baik'] ?? 0) ?>"><?= (int)($stats['baik'] ?? 0) ?></div>
                <div class="akz-lbl">Baik / C</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== DISTRIBUSI + KARTU ===== -->
<section class="akz-sec" style="padding-top:0;">
    <div class="akz-container">

        <div class="akz-panel">
            <h3>📊 Distribusi Peringkat Prodi</h3>
            <?php if (empty($dist)): ?>
                <p style="color:#64748B;margin:0;">Belum ada data distribusi.</p>
            <?php else: foreach ($dist as $d):
                $pct = $totalDist > 0 ? round(((int)$d['j'] / $totalDist) * 100) : 0;
            ?>
                <div class="akz-bar-row">
                    <span class="akz-bar-label"><?= Security::e($d['peringkat']) ?></span>
                    <div class="akz-bar-track"><div class="akz-bar-fill" style="width:<?= $pct ?>%;background:<?= akzColor($d['peringkat']) ?>;"></div></div>
                    <span class="akz-bar-num"><?= (int)$d['j'] ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="akz-pills">
            <a href="?tingkat=all&search=<?= urlencode($search) ?>" class="akz-pill <?= $tingkat === 'all' ? 'on' : '' ?>">Semua <span class="akz-pill-c"><?= count($data) ?></span></a>
            <a href="?tingkat=Prodi&search=<?= urlencode($search) ?>" class="akz-pill <?= $tingkat === 'Prodi' ? 'on' : '' ?>">Program Studi</a>
            <a href="?tingkat=Institusi&search=<?= urlencode($search) ?>" class="akz-pill <?= $tingkat === 'Institusi' ? 'on' : '' ?>">Institusi</a>
        </div>

        <div class="akz-grid">
            <?php if (empty($data)): ?>
                <div class="akz-empty">🔍<br><strong>Tidak ada data ditemukan.</strong><br>Coba ubah kata kunci atau filter.</div>
            <?php endif; ?>
            <?php foreach ($data as $row):
                $masa = strtotime($row['masa_berlaku']);
                $days = $masa ? (int)(($masa - time()) / 86400) : null;
            ?>
            <div class="akz-card <?= akzLevel($row['peringkat']) ?>">
                <div class="akz-card-head">
                    <div>
                        <h4><?= Security::e($row['nama_prodi'] ?? 'Institusi') ?></h4>
                        <span class="fak"><?= Security::e($row['nama_fakultas'] ?? 'Tingkat Institusi') ?></span>
                    </div>
                    <div class="akz-rank"><?= Security::e(mb_substr($row['peringkat'], 0, 1)) ?></div>
                </div>
                <div class="akz-card-meta">
                    <span>🏛️ <?= Security::e($row['lembaga']) ?></span>
                    <span>📅 <?= $masa ? date('d M Y', $masa) : '—' ?></span>
                </div>
                <?php if ($days === null): ?>
                    <span class="akz-days warn">— Masa berlaku tidak tercatat</span>
                <?php elseif ($days < 0): ?>
                    <span class="akz-days exp">⚠️ Kadaluarsa <?= abs($days) ?> hari lalu</span>
                <?php elseif ($days < 180): ?>
                    <span class="akz-days warn">⏰ Sisa <?= $days ?> hari — segera reakreditasi</span>
                <?php else: ?>
                    <span class="akz-days ok">✓ Berlaku <?= $days ?> hari lagi</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="akz-note">
            💡 Data diperbarui berkala oleh LPM. Sertifikat resmi dapat diunduh pada menu <strong>Dokumen Publik</strong> atau diminta langsung ke kantor LPM.
        </div>
    </div>
</section>

</div>

<script>
(function () {
    'use strict';
    /* Semua JS di sini HANYA pemanis tambahan. Jika gagal, halaman tetap tampil penuh. */

    /* Counter: nilai server sudah benar; animasi hanya hiasan, selalu berakhir di nilai asli */
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        var start = null;
        function tick(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / 1400, 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(tick); else el.textContent = target;
        }
        requestAnimationFrame(tick);
    });

    /* Tilt 3D pada kartu & stat (desktop saja) */
    if (window.matchMedia('(pointer: fine)').matches) {
        document.querySelectorAll('.akz-card, .akz-stat').forEach(function (el) {
            el.addEventListener('mousemove', function (e) {
                var r = el.getBoundingClientRect();
                var px = ((e.clientX - r.left) / r.width - .5) * 6;
                var py = ((e.clientY - r.top) / r.height - .5) * -6;
                el.style.transform = 'translateY(-6px) rotateX(' + py + 'deg) rotateY(' + px + 'deg)';
            });
            el.addEventListener('mouseleave', function () { el.style.transform = ''; });
        });

        /* Spotlight hero mengikuti kursor */
        var hero = document.getElementById('akzHero');
        if (hero) {
            hero.addEventListener('mousemove', function (e) {
                var r = hero.getBoundingClientRect();
                hero.style.background = 'radial-gradient(600px circle at ' + (e.clientX - r.left) + 'px ' + (e.clientY - r.top) + 'px, rgba(201,162,39,.15), transparent 45%), #061D2E';
            });
            hero.addEventListener('mouseleave', function () { hero.style.background = '#061D2E'; });
        }
    }

    /* Rotasi kata judul (jika JS mati, kata tetap "Akreditasi") */
    var w = document.getElementById('akzWord');
    if (w) {
        var words = ['Akreditasi', 'Unggul', 'Bermutu', 'Terpercaya'];
        var i = 0;
        setInterval(function () {
            i = (i + 1) % words.length;
            w.style.transition = 'opacity .3s, transform .3s';
            w.style.opacity = '0'; w.style.transform = 'translateY(-14px)';
            setTimeout(function () {
                w.textContent = words[i];
                w.style.opacity = '1'; w.style.transform = 'none';
            }, 300);
        }, 3200);
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>