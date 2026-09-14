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

/* ============================================================
   DATA AKREDITASI INTERNASIONAL (1x query saja)
============================================================ */
$intlList = [];
$intlByLembaga = [];
$intlLembagaList = [];
$intlStats = ['total' => 0, 'lembaga' => 0, 'aktif' => 0];

try {
    $intlList = $db->query("
        SELECT a.*, p.nama_prodi, f.nama_fakultas,
               l.warna, l.nama AS nama_lembaga, l.fokus, l.negara
        FROM akreditasi_internasional a
        LEFT JOIN prodi p ON a.id_prodi = p.id_prodi
        LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
        LEFT JOIN lembaga_internasional l ON a.lembaga = l.kode
        ORDER BY a.masa_berlaku DESC
    ")->fetchAll();

    foreach ($intlList as $i) {
        $intlByLembaga[$i['lembaga']][] = $i;
    }

    $intlLembagaList = $db->query("SELECT * FROM lembaga_internasional ORDER BY urutan")->fetchAll();

    $intlStats = [
        'total'   => count($intlList),
        'lembaga' => count(array_unique(array_column($intlList, 'lembaga'))),
        'aktif'   => array_reduce($intlList, function($c, $i) {
            return $c + ($i['masa_berlaku'] && strtotime($i['masa_berlaku']) > time() ? 1 : 0);
        }, 0),
    ];
} catch (Throwable $e) {
    // tabel belum ada — skip
}

$akzLang = (function_exists('lang') && lang() === 'en') ? 'en' : 'id';
$intlRotWords = $akzLang === 'en'
    ? ['International', 'Global', 'Worldwide', 'Renowned']
    : ['Internasional', 'Global', 'Dunia', 'Mendunia'];

/* ============================================================
   STATISTIK AKREDITASI NASIONAL
============================================================ */
if (!function_exists('akzNormalizeStatus')) {
    function akzNormalizeStatus($raw): string {
        $s = trim((string)$raw);
        if ($s === '') return 'Belum Diisi';
        $u = mb_strtoupper($s, 'UTF-8');
        if (strpos($u, 'UNGGUL') !== false || $u === 'A') return 'Unggul';
        if (strpos($u, 'BAIK SEKALI') !== false || $u === 'B') return 'Baik Sekali';
        if (strpos($u, 'BAIK') !== false || $u === 'C') return 'Baik';
        if (strpos($u, 'TERAKREDITASI') !== false) return 'Terakreditasi';
        if (strpos($u,'BELUM')!==false || strpos($u,'TIDAK')!==false
            || strpos($u,'KADALUARSA')!==false || strpos($u,'EXPIRED')!==false) return 'Belum Diisi';
        return 'Lainnya';
    }
}

$akzTotalProdi = 0;
$akzRows = [];
try {
    $akzTotalProdi = (int)$db->query("SELECT COUNT(*) FROM prodi")->fetchColumn();
    $akzRows = $db->query("SELECT id_prodi, peringkat AS status_akreditasi
                           FROM akreditasi WHERE tingkat = 'Prodi'")->fetchAll();
} catch (Throwable $e) { $akzRows = []; }

$perProdi = [];
foreach ($akzRows as $r) {
    if ($r['id_prodi'] !== null && !isset($perProdi[$r['id_prodi']])) {
        $perProdi[$r['id_prodi']] = akzNormalizeStatus($r['status_akreditasi'] ?? '');
    }
}

$akzCounts = ['Unggul'=>0,'Baik Sekali'=>0,'Baik'=>0,'Terakreditasi'=>0,'Belum Diisi'=>0,'Lainnya'=>0];
foreach ($perProdi as $st) {
    if (!isset($akzCounts[$st])) $st = 'Lainnya';
    $akzCounts[$st]++;
}
$akzCounts['Belum Diisi'] += max(0, $akzTotalProdi - count($perProdi));

$akzTotal = max($akzTotalProdi, count($perProdi));
$akzUnggul = $akzCounts['Unggul'];
$akzTerakreditasiTotal = $akzUnggul + $akzCounts['Baik Sekali'] + $akzCounts['Baik'] + $akzCounts['Terakreditasi'];
$akzBelum = $akzCounts['Belum Diisi'] + $akzCounts['Lainnya'];

$akzPctUnggul        = $akzTotal ? round(($akzUnggul / $akzTotal) * 100) : 0;
$akzPctTerakreditasi = $akzTotal ? round(($akzTerakreditasiTotal / $akzTotal) * 100) : 0;
$akzPctBelum         = $akzTotal ? round(($akzBelum / $akzTotal) * 100) : 0;

$akzSegments = [
    ['label' => 'Unggul',        'count' => $akzCounts['Unggul'],        'color' => '#C9A227'],
    ['label' => 'Baik Sekali',   'count' => $akzCounts['Baik Sekali'],   'color' => '#10B981'],
    ['label' => 'Baik',          'count' => $akzCounts['Baik'],          'color' => '#3B82F6'],
    ['label' => 'Terakreditasi', 'count' => $akzCounts['Terakreditasi'], 'color' => '#8B5CF6'],
    ['label' => 'Belum Diisi',   'count' => $akzCounts['Belum Diisi'],   'color' => '#F59E0B'],
    ['label' => 'Lainnya',       'count' => $akzCounts['Lainnya'],       'color' => '#64748B'],
];

$akzConicParts = []; $akzStart = 0;
if ($akzTotal > 0) {
    foreach ($akzSegments as $seg) {
        if ($seg['count'] <= 0) continue;
        $p = ($seg['count'] / $akzTotal) * 100;
        $akzConicParts[] = $seg['color'] . ' ' . $akzStart . '% ' . ($akzStart + $p) . '%';
        $akzStart += $p;
    }
}
$akzConic = !empty($akzConicParts) ? implode(', ', $akzConicParts) : '#E5E7EB 0% 100%';

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   AKREDITASI v14 — CLEAN, FAIL-SAFE
============================================================ */
.akz-wrap { --eo: cubic-bezier(.22, 1, .36, 1); --es: cubic-bezier(.34, 1.56, .64, 1); }

/* ===== HERO ===== */
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

.akz-p { position: absolute; inset: 0; z-index: 1; pointer-events: none; }
.akz-p span {
    position: absolute; width: 6px; height: 6px; border-radius: 50%;
    background: #E8C55A; box-shadow: 0 0 12px #E8C55A;
    animation: akzFloat 9s ease-in-out infinite;
}
.akz-p span:nth-child(1) { left: 8%; top: 24%; }
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

/* ===== MARQUEE ===== */
.akz-marquee { overflow: hidden; background: #092A40; padding: 13px 0; border-top: 1px solid rgba(201,162,39,.25); border-bottom: 1px solid rgba(201,162,39,.25); }
.akz-track { display: flex; gap: 56px; width: max-content; animation: akzScroll 30s linear infinite; }
.akz-marquee:hover .akz-track { animation-play-state: paused; }
.akz-track b { color: rgba(255,255,255,.8); font-size: 12.5px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; white-space: nowrap; }
.akz-track b i { color: #E8C55A; font-style: normal; margin-right: 10px; }
@keyframes akzScroll { to { transform: translateX(-50%); } }

/* ===== STATS ===== */
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

.akz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 20px; }
.akz-card {
    position: relative; overflow: hidden;
    background: #fff; border: 1px solid #E2E8F0; border-left: 5px solid #94A3B8;
    border-radius: 16px; padding: 22px; box-shadow: 0 4px 20px rgba(15,61,92,.05);
    transition: transform .4s var(--eo), box-shadow .4s;
    animation: akzUp .7s var(--eo) backwards;
}
.akz-grid .akz-card:nth-child(n+1):nth-child(-n+8) { animation-delay: calc(.05s + .07s * (var(--i,0))); }
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

/* ===== STATISTIK NASIONAL ===== */
.akz-stat-sec {
    background:
        radial-gradient(ellipse 60% 40% at 10% 10%, rgba(201,162,39,.12), transparent 55%),
        linear-gradient(180deg, #FFFFFF 0%, #F7F9FC 100%);
    position: relative; overflow: hidden;
}
.akz-stat-head { text-align: center; max-width: 760px; margin: 0 auto 44px; }
.akz-stat-head h2 { font-size: clamp(30px, 4vw, 52px); font-weight: 800; line-height: 1.08; margin: 18px 0 14px; letter-spacing: -.03em; color: var(--primary-dark); }
.akz-stat-head p { color: var(--text-muted); font-size: clamp(15px, 1.3vw, 17px); line-height: 1.65; }

.akz-stat-grid { display: grid; grid-template-columns: 340px 1fr 1.1fr; gap: 22px; align-items: stretch; }
@media (max-width: 1100px) { .akz-stat-grid { grid-template-columns: 1fr 1fr; } .akz-donut-card { grid-row: span 2; } }
@media (max-width: 760px) { .akz-stat-grid { grid-template-columns: 1fr; } .akz-donut-card { grid-row: auto; } }

.akz-donut-card, .akz-rank-card, .akz-summary-card {
    background: rgba(255,255,255,.86); border: 1px solid rgba(226,232,240,.9);
    box-shadow: 0 20px 50px rgba(15,61,92,.08); backdrop-filter: blur(12px);
}
.akz-donut-card { border-radius: 26px; padding: 30px 24px; display: flex; flex-direction: column; justify-content: center; text-align: center; position: relative; overflow: hidden; }
.akz-donut-card::before {
    content: ''; position: absolute; inset: -1px; border-radius: inherit; padding: 1px;
    background: linear-gradient(135deg, rgba(201,162,39,.45), rgba(15,61,92,.08), rgba(201,162,39,.25));
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none;
}
.akz-donut-wrap { display: grid; place-items: center; margin-bottom: 22px; }
.akz-donut {
    width: 220px; height: 220px; border-radius: 50%; background: conic-gradient(var(--akz-conic));
    display: grid; place-items: center; position: relative;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.35), 0 18px 45px rgba(15,61,92,.16);
    animation: akzDonutPop .8s cubic-bezier(.34,1.56,.64,1);
}
@keyframes akzDonutPop { from { transform: scale(.75) rotate(-24deg); opacity: 0; } to { transform: scale(1) rotate(0); opacity: 1; } }
.akz-donut::after {
    content: ''; position: absolute; inset: 16px; border-radius: 50%;
    background: radial-gradient(circle at 35% 25%, rgba(255,255,255,.95), rgba(255,255,255,.82)), #fff;
    box-shadow: inset 0 2px 18px rgba(15,61,92,.08);
}
.akz-donut-inner { position: relative; z-index: 2; }
.akz-donut-inner strong { display: block; font-size: 48px; font-weight: 900; line-height: 1; color: var(--primary-dark); letter-spacing: -.04em; }
.akz-donut-inner span { display: block; margin-top: 6px; font-size: 11px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: var(--text-muted); }

.akz-donut-caption h3, .akz-rank-card h3 { font-size: 18px; color: var(--primary-dark); margin: 0 0 6px; }
.akz-donut-caption p { color: var(--text-muted); font-size: 13px; line-height: 1.5; margin: 0; }

.akz-summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 520px) { .akz-summary-grid { grid-template-columns: 1fr; } }
.akz-summary-card { border-radius: 20px; padding: 20px; display: flex; align-items: center; gap: 14px; transition: .3s; }
.akz-summary-card:hover { transform: translateY(-4px); box-shadow: 0 24px 60px rgba(15,61,92,.13); }
.akz-summary-ic { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; font-size: 22px; background: rgba(15,61,92,.08); }
.akz-summary-card.gold .akz-summary-ic { background: rgba(201,162,39,.16); }
.akz-summary-card.green .akz-summary-ic { background: rgba(16,185,129,.14); }
.akz-summary-card.warn .akz-summary-ic { background: rgba(245,158,11,.15); }
.akz-summary-card strong { display: block; font-size: 30px; font-weight: 900; color: var(--primary-dark); line-height: 1; }
.akz-summary-card span { display: block; margin-top: 5px; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: var(--text-muted); }

.akz-rank-card { border-radius: 26px; padding: 24px; }
.akz-rank-card h3 { margin-bottom: 18px; }
.akz-rank-row { margin-bottom: 15px; }
.akz-rank-row:last-child { margin-bottom: 0; }
.akz-rank-top { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 7px; font-size: 13px; }
.akz-rank-top span { color: var(--text-dark); font-weight: 700; display: inline-flex; align-items: center; gap: 8px; }
.akz-rank-top span i { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.akz-rank-top b { color: var(--primary-dark); font-size: 13px; }
.akz-rank-top small { color: var(--text-muted); font-weight: 700; }
.akz-rank-bar { height: 9px; border-radius: 50px; background: #E5E7EB; overflow: hidden; }
.akz-rank-bar em { display: block; height: 100%; border-radius: inherit; width: 0; animation: akzBarIn 1s ease forwards; }
@keyframes akzBarIn { from { width: 0; } }

/* ===== AKREDITASI INTERNASIONAL ===== */
.intl-stamp:hover { transform: translateY(-8px); box-shadow: 0 30px 60px rgba(0,0,0,.4); }
.intl-stamp.hidden { display: none; }

/* ===== RESPONSIVE ===== */
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

<!-- ===== STATS AKREDITASI ===== -->
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

<!-- ===== STATISTIK AKREDITASI NASIONAL ===== -->
<section class="akz-sec akz-stat-sec" id="statistik-akreditasi">
    <div class="akz-container">
        <div class="akz-stat-head">
            <span class="akz-badge"><span class="akz-badge-dot"></span> 📊 Accreditation Overview</span>
            <h2>Statistik <span class="gr">Akreditasi Nasional</span></h2>
            <p>Ringkasan capaian akreditasi program studi sebagai gambaran mutu akademik dan komitmen peningkatan berkelanjutan.</p>
        </div>

        <div class="akz-stat-grid">
            <div class="akz-donut-card">
                <div class="akz-donut-wrap">
                    <div class="akz-donut" style="--akz-conic: <?= Security::e($akzConic) ?>;">
                        <div class="akz-donut-inner">
                            <strong data-count="<?= $akzPctUnggul ?>"><?= $akzPctUnggul ?></strong>
                            <span>% Unggul</span>
                        </div>
                    </div>
                </div>
                <div class="akz-donut-caption">
                    <h3>Distribusi Peringkat</h3>
                    <p><?= $akzTotal ?> program studi dalam pemantauan mutu berkelanjutan.</p>
                </div>
            </div>

            <div class="akz-summary-grid">
                <div class="akz-summary-card"><div class="akz-summary-ic">🏫</div><div><strong data-count="<?= $akzTotal ?>"><?= $akzTotal ?></strong><span>Total Prodi</span></div></div>
                <div class="akz-summary-card gold"><div class="akz-summary-ic">🏆</div><div><strong data-count="<?= $akzPctUnggul ?>"><?= $akzPctUnggul ?></strong><span>% Prodi Unggul</span></div></div>
                <div class="akz-summary-card green"><div class="akz-summary-ic">✅</div><div><strong data-count="<?= $akzPctTerakreditasi ?>"><?= $akzPctTerakreditasi ?></strong><span>% Terakreditasi</span></div></div>
                <div class="akz-summary-card warn"><div class="akz-summary-ic">⏳</div><div><strong data-count="<?= $akzPctBelum ?>"><?= $akzPctBelum ?></strong><span>% Perlu Update</span></div></div>
            </div>

            <div class="akz-rank-card">
                <h3>Komposisi Peringkat</h3>
                <?php foreach ($akzSegments as $seg):
                    $pct = $akzTotal ? round(($seg['count'] / $akzTotal) * 100) : 0;
                ?>
                    <div class="akz-rank-row">
                        <div class="akz-rank-top">
                            <span><i style="background:<?= $seg['color'] ?>;"></i> <?= Security::e($seg['label']) ?></span>
                            <b><?= $seg['count'] ?> <small>(<?= $pct ?>%)</small></b>
                        </div>
                        <div class="akz-rank-bar"><em style="width:<?= $pct ?>%;background:<?= $seg['color'] ?>;"></em></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ===== AKREDITASI INTERNASIONAL ===== -->
<section class="akz-sec" style="background:linear-gradient(180deg,#061D2E 0%, #0F3D5C 50%, #092A40 100%);color:#fff;position:relative;overflow:hidden;">
    <div style="position:absolute;inset:0;pointer-events:none;opacity:.5;filter:blur(60px);background:
        radial-gradient(ellipse 60% 50% at 15% 30%, rgba(201,162,39,.3), transparent 55%),
        radial-gradient(ellipse 50% 60% at 85% 70%, rgba(26,90,130,.4), transparent 55%);"></div>

    <div style="position:absolute;inset:0;pointer-events:none;opacity:.035;">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="passportPat" x="0" y="0" width="180" height="180" patternUnits="userSpaceOnUse">
                    <circle cx="90" cy="90" r="70" fill="none" stroke="#fff" stroke-width="1" stroke-dasharray="4 4"/>
                    <circle cx="90" cy="90" r="58" fill="none" stroke="#fff" stroke-width="1"/>
                    <text x="90" y="94" font-family="Arial Black" font-size="18" fill="#fff" text-anchor="middle" font-weight="900">PASSPORT</text>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#passportPat)"/>
        </svg>
    </div>

    <div class="akz-container" style="position:relative;z-index:1;">
        <div style="text-align:center;max-width:720px;margin:0 auto 48px;">
            <span class="akz-badge" style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.18);">
                <span class="akz-badge-dot" style="background:#E8C55A;box-shadow:0 0 14px #E8C55A;"></span>
                🌍 Global Recognition
            </span>
            <h2 style="font-size:clamp(32px,4.5vw,56px);font-weight:800;line-height:1.08;margin:20px 0 16px;letter-spacing:-.03em;color:#fff;">
                <?= $akzLang === 'en' ? 'Accreditation' : 'Akreditasi' ?> <span class="gr" id="akzIntlWord"><?= $intlRotWords[0] ?></span>
            </h2>
            <p style="color:rgba(255,255,255,.75);font-size:clamp(15px,1.4vw,17px);line-height:1.65;">
                Pengakuan lembaga akreditasi internasional terhadap kualitas program studi dan institusi kami —
                bukti komitmen menuju standar pendidikan global.
            </p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px;margin-bottom:44px;">
            <div style="background:rgba(255,255,255,.06);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:22px;text-align:center;">
                <div style="font-size:36px;font-weight:800;background:linear-gradient(180deg,#F7E491,#C9A227);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;line-height:1;"><?= $intlStats['total'] ?></div>
                <div style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.6);font-weight:700;margin-top:6px;">Total Sertifikat</div>
            </div>
            <div style="background:rgba(255,255,255,.06);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:22px;text-align:center;">
                <div style="font-size:36px;font-weight:800;background:linear-gradient(180deg,#F7E491,#C9A227);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;line-height:1;"><?= $intlStats['lembaga'] ?></div>
                <div style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.6);font-weight:700;margin-top:6px;">Lembaga Akreditasi</div>
            </div>
            <div style="background:rgba(255,255,255,.06);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:22px;text-align:center;">
                <div style="font-size:36px;font-weight:800;background:linear-gradient(180deg,#34D399,#10B981);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;line-height:1;"><?= $intlStats['aktif'] ?></div>
                <div style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.6);font-weight:700;margin-top:6px;">Status Aktif</div>
            </div>
        </div>

        <?php if (!empty($intlByLembaga)): ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-bottom:36px;">
            <button type="button" class="intl-filter-btn active" data-filter="all" style="padding:9px 18px;border-radius:50px;font-size:12px;font-weight:700;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#092A40;border:none;cursor:pointer;">🌍 Semua</button>
            <?php foreach ($intlLembagaList as $l): if (empty($intlByLembaga[$l['kode']])) continue; ?>
                <button type="button" class="intl-filter-btn" data-filter="<?= $l['kode'] ?>" style="padding:9px 18px;border-radius:50px;font-size:12px;font-weight:700;background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.2);cursor:pointer;">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $l['warna'] ?>;margin-right:6px;"></span><?= $l['kode'] ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:22px;" id="intlGrid">
            <?php if (empty($intlList)): ?>
                <div style="grid-column:1/-1;text-align:center;padding:60px 20px;background:rgba(255,255,255,.04);border:2px dashed rgba(255,255,255,.2);border-radius:20px;color:rgba(255,255,255,.6);">
                    <div style="font-size:64px;margin-bottom:16px;opacity:.4;">🌍</div>
                    <h3 style="font-size:20px;margin-bottom:8px;">Belum ada akreditasi internasional</h3>
                    <p style="font-size:14px;margin:0;">Kami sedang mempersiapkan perjalanan akreditasi internasional.</p>
                </div>
            <?php else: foreach ($intlList as $i):
                $days = $i['masa_berlaku'] ? (int)((strtotime($i['masa_berlaku']) - time()) / 86400) : null;
                $warna = $i['warna'] ?: '#0F3D5C';
            ?>
            <div class="intl-stamp" data-lembaga="<?= Security::e($i['lembaga']) ?>" style="position:relative;background:rgba(255,255,255,.04);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:26px;transition:transform .5s cubic-bezier(.22,1,.36,1), box-shadow .4s;overflow:hidden;">
                <div style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:conic-gradient(from 0deg,<?= $warna ?>33,transparent 30%,<?= $warna ?>22 60%,transparent);animation:akzSpin 20s linear infinite;pointer-events:none;opacity:.4;"></div>
                <div style="position:relative;z-index:1;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:16px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:52px;height:52px;border-radius:14px;background:<?= $warna ?>;display:grid;place-items:center;color:#fff;font-weight:900;font-size:12px;box-shadow:0 6px 18px <?= $warna ?>66;"><?= mb_substr($i['lembaga'], 0, 4) ?></div>
                            <div>
                                <div style="font-weight:800;font-size:16px;color:#fff;"><?= Security::e($i['lembaga']) ?></div>
                                <div style="font-size:11px;color:rgba(255,255,255,.55);"><?= Security::e($i['negara'] ?: $i['negara_lembaga'] ?: 'International') ?></div>
                            </div>
                        </div>
                    </div>
                    <div style="padding:14px;background:rgba(255,255,255,.04);border-radius:12px;border:1px solid rgba(255,255,255,.08);margin-bottom:14px;">
                        <div style="font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.5);font-weight:700;margin-bottom:4px;"><?= $i['tingkat'] === 'Prodi' ? 'Program Studi' : 'Institutional' ?></div>
                        <div style="font-size:14px;font-weight:700;color:#fff;"><?= Security::e($i['nama_prodi'] ?: 'Kampus') ?></div>
                        <?php if ($i['nama_fakultas']): ?><div style="font-size:11px;color:rgba(255,255,255,.55);margin-top:2px;"><?= Security::e($i['nama_fakultas']) ?></div><?php endif; ?>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:11px;">
                        <div><div style="color:rgba(255,255,255,.45);text-transform:uppercase;font-weight:700;">Peringkat</div><div style="color:#fff;font-weight:700;margin-top:2px;"><?= Security::e($i['peringkat'] ?: '—') ?></div></div>
                        <div><div style="color:rgba(255,255,255,.45);text-transform:uppercase;font-weight:700;">No. Sertifikat</div><div style="color:#fff;font-weight:700;margin-top:2px;font-size:10px;"><?= Security::e($i['nomor_sertifikat'] ?: '—') ?></div></div>
                    </div>
                    <div style="margin-top:14px;padding-top:14px;border-top:1px dashed rgba(255,255,255,.15);display:flex;justify-content:space-between;align-items:center;">
                        <div style="font-size:11px;color:rgba(255,255,255,.6);">📅 <?= $i['masa_berlaku'] ? 's/d ' . date('d M Y', strtotime($i['masa_berlaku'])) : '—' ?></div>
                        <?php if ($days === null): ?><span style="padding:3px 8px;border-radius:50px;font-size:10px;font-weight:800;background:rgba(255,255,255,.08);color:rgba(255,255,255,.6);">—</span>
                        <?php elseif ($days < 0): ?><span style="padding:3px 8px;border-radius:50px;font-size:10px;font-weight:800;background:rgba(239,68,68,.2);color:#FCA5A5;">Expired</span>
                        <?php elseif ($days < 180): ?><span style="padding:3px 8px;border-radius:50px;font-size:10px;font-weight:800;background:rgba(245,158,11,.2);color:#FCD34D;">⏰ <?= $days ?>d</span>
                        <?php else: ?><span style="padding:3px 8px;border-radius:50px;font-size:10px;font-weight:800;background:rgba(16,185,129,.2);color:#6EE7B7;">✓ Active</span><?php endif; ?>
                    </div>
                    <?php if ($i['file_path']): ?>
                        <a href="/uploads/<?= Security::e($i['file_path']) ?>" target="_blank" style="margin-top:12px;display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border-radius:10px;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#092A40;font-size:12px;font-weight:800;text-decoration:none;">📥 Unduh Sertifikat</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="akz-note" style="margin-top:48px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.75);">
            💡 <strong style="color:#E8C55A;">Akreditasi internasional</strong> menunjukkan pengakuan lembaga akreditasi luar negeri terhadap kualitas kurikulum, pengajaran, dan lulusan kami — memperluas peluang kolaborasi global dan mobilitas mahasiswa.
        </div>
    </div>
</section>

</div>

<script>
(function () {
    'use strict';

    /* Counter: fail-safe — nilai server sudah benar, animasi hanya hiasan */
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        var start = null;
        function tick(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / 1200, 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(tick);
            else el.textContent = target;
        }
        requestAnimationFrame(tick);
    });

    /* Tilt 3D desktop */
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

        var hero = document.getElementById('akzHero');
        if (hero) {
            hero.addEventListener('mousemove', function (e) {
                var r = hero.getBoundingClientRect();
                hero.style.background = 'radial-gradient(600px circle at ' + (e.clientX - r.left) + 'px ' + (e.clientY - r.top) + 'px, rgba(201,162,39,.15), transparent 45%), #061D2E';
            });
            hero.addEventListener('mouseleave', function () { hero.style.background = '#061D2E'; });
        }
    }

    /* Rotasi kata judul akreditasi nasional */
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

    /* Rotasi kata judul akreditasi internasional (bilingual) */
    var w2 = document.getElementById('akzIntlWord');
    if (w2) {
        var words2 = <?= json_encode($intlRotWords) ?>;
        var i2 = 0;
        setInterval(function () {
            i2 = (i2 + 1) % words2.length;
            w2.style.transition = 'opacity .25s';
            w2.style.opacity = '0';
            setTimeout(function () { w2.textContent = words2[i2]; w2.style.opacity = '1'; }, 250);
        }, 4000);
    }

    /* Filter internasional */
    var filters = document.querySelectorAll('.intl-filter-btn');
    var cards = document.querySelectorAll('.intl-stamp');
    filters.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filters.forEach(function (b) {
                b.style.background = 'rgba(255,255,255,.08)';
                b.style.color = '#fff';
                b.style.borderColor = 'rgba(255,255,255,.2)';
            });
            this.style.background = 'linear-gradient(135deg,#C9A227,#E8C55A)';
            this.style.color = '#092A40';
            this.style.borderColor = 'transparent';
            var f = this.dataset.filter;
            cards.forEach(function (c) { c.classList.toggle('hidden', !(f === 'all' || c.dataset.lembaga === f)); });
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>