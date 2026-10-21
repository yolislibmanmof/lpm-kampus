<?php
$pageTitle = 'Kuesioner Monev';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();

$db = Database::getInstance();
$token = $_GET['token'] ?? '';
$pesan = ''; $error = '';

$stmt = $db->prepare("SELECT * FROM kuesioner_periode WHERE token = ?");
$stmt->execute([$token]);
$periode = $stmt->fetch();

$sidik = sha1(($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
$sudahIsi = false;

if ($periode) {
    $c = $db->prepare("SELECT COUNT(*) n FROM kuesioner_jawaban WHERE id_periode = ? AND sidik_responden = ?");
    $c->execute([$periode['id_periode'], $sidik]);
    $sudahIsi = $c->fetch()['n'] > 0;
}

if ($periode && $periode['status'] === 'Aktif' && !$sudahIsi && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $skor = $_POST['skor'] ?? [];
    
    $qStmt = $db->prepare("SELECT id_pertanyaan FROM kuesioner_pertanyaan WHERE id_periode = ?");
    $qStmt->execute([$periode['id_periode']]);
    $allQids = $qStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $valid = true;
    $missing = false;
    
    foreach ($allQids as $qid) {
        if (!isset($skor[$qid]) || !in_array((int)$skor[$qid], [1, 2, 3, 4, 5], true)) {
            $valid = false;
            $missing = true;
            break;
        }
    }
    
    if (!$valid) {
        $error = $missing ? 'Mohon beri penilaian (1–5 bintang) pada *semua* pertanyaan yang belum diisi.' : 'Terjadi kesalahan validasi. Silakan coba lagi.';
    } else {
        try {
            $insertStmt = $db->prepare("INSERT INTO kuesioner_jawaban (id_periode, id_pertanyaan, sidik_responden, skor) VALUES (?,?,?,?)");
            foreach ($skor as $qid => $v) {
                $insertStmt->execute([$periode['id_periode'], (int)$qid, $sidik, (int)$v]);
            }
            $sudahIsi = true;
            $pesan = 'Terima kasih! Masukan Anda sangat berarti bagi peningkatan mutu kampus.';
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
        }
    }
}

$questions = [];
$aspects = [];
if ($periode) {
    $q = $db->prepare("SELECT * FROM kuesioner_pertanyaan WHERE id_periode = ? ORDER BY id_pertanyaan");
    $q->execute([$periode['id_periode']]);
    $questions = $q->fetchAll();
    foreach ($questions as $qq) {
        $asp = $qq['aspek'] ?? 'Lainnya';
        if (!in_array($asp, $aspects)) $aspects[] = $asp;
    }
}

// Statistik periode
$kqStats = ['responden' => 0, 'avg' => 0, 'high' => 0, 'days_left' => 0];
if ($periode) {
    try {
        $kqStats['responden'] = (int)$db->prepare("SELECT COUNT(DISTINCT sidik_responden) FROM kuesioner_jawaban WHERE id_periode = ?")->execute([$periode['id_periode']]) ? 
            (int)$db->query("SELECT COUNT(DISTINCT sidik_responden) FROM kuesioner_jawaban WHERE id_periode = " . (int)$periode['id_periode'])->fetchColumn() : 0;
        $avgCols = $db->query("SELECT AVG(skor) FROM kuesioner_jawaban WHERE id_periode = " . (int)$periode['id_periode'])->fetchColumn();
        $kqStats['avg'] = $avgCols ? round((float)$avgCols, 2) : 0;
        // Hitung responden dengan rata-rata >= 4
        $sub = $db->query("SELECT sidik_responden, AVG(skor) avg_s FROM kuesioner_jawaban WHERE id_periode = " . (int)$periode['id_periode'] . " GROUP BY sidik_responden HAVING avg_s >= 4")->fetchAll();
        $kqStats['high'] = count($sub);
        if (!empty($periode['tanggal_selesai'])) {
            $end = strtotime($periode['tanggal_selesai']);
            $now = time();
            if ($end > $now) $kqStats['days_left'] = (int)ceil(($end - $now) / 86400);
        }
    } catch (Throwable $e) {}
}

// ===== KUMPULAN ICON SVG =====
$ic = [
    'lock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'zap' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    'bar_chart' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/></svg>',
    'check' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    'check_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
    'star' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'star_filled' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'alert_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>',
    'loader' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:kqSpin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
    'send' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
    'arrow_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>',
    'arrow_right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
    'moon' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>',
    'sun' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>',
    'help_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
    'chevron_down' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
    'info' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
    'users' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'smile' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>',
    'meh' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" x2="16" y1="15" y2="15"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>',
    'frown' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 16s-1.5-2-4-2-4 2-4 2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>',
    'graduation' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
    'microscope' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18h8"/><path d="M3 22h18"/><path d="M14 22a7 7 0 1 0 0-14h-1"/><path d="M9 14h2"/><path d="M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2Z"/><path d="M12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3"/></svg>',
    'heart_handshake' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 14 9.828 12.828a4 4 0 1 0-5.656 5.656l6.368 6.368a2 2 0 0 0 2.828 0L21 17.284a2 2 0 0 0 0-2.828L16.232 9.687a4 4 0 0 0-5.656 0L9 11.263"/><path d="M21.346 11.291a4 4 0 0 0-5.656-5.656L13.462 7.862l.707.707"/></svg>',
    'building_2' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>',
    'wrench' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    'target' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'calendar_days' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>',
    'ruler' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>',
];

// Helper: Ikon per aspek
function kqAspectIcon($asp, $ic) {
    return match ($asp) {
        'Pendidikan' => $ic['graduation'],
        'Penelitian' => $ic['microscope'],
        'Pengabdian' => $ic['heart_handshake'],
        'Layanan' => $ic['building_2'],
        'Sarana' => $ic['wrench'],
        default => $ic['target'],
    };
}

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
@keyframes kqSpin { to { transform: rotate(360deg); } }

/* ===== SCROLL PROGRESS ===== */
.kq-progress-global { position: fixed; top: 0; left: 0; width: 100%; height: 4px; z-index: 1100; background: rgba(15,61,92,.06); }
.kq-progress-global-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #C9A227, #E8C55A); box-shadow: 0 0 12px rgba(201,162,39,.5); transition: width .1s linear; }

/* ===== DARK MODE ===== */
.kq-dark-toggle {
    position: fixed; bottom: 24px; left: 24px; z-index: 1000;
    width: 52px; height: 52px; border-radius: 50%; border: none; cursor: pointer;
    background: #fff; color: #0F3D5C;
    display: grid; place-items: center; font-size: 22px;
    box-shadow: 0 8px 24px rgba(15,61,92,.15); transition: .3s;
}
.kq-dark-toggle:hover { transform: scale(1.08) rotate(15deg); }
body.kq-dark { background: #0F172A; color: #E2E8F0; }
body.kq-dark .kq-body { background: linear-gradient(180deg, #0F172A, #1E293B); }
body.kq-dark .kq-card { background: #1E293B; border-color: #334155; }
body.kq-dark .kq-form-body { background: #1E293B; }
body.kq-dark .kq-progress-wrap { background: rgba(15,23,42,.95); border-bottom-color: #334155; }
body.kq-dark .kq-q { border-bottom-color: #334155; }
body.kq-dark .kq-q.answered { background: linear-gradient(90deg, rgba(16,185,129,.08), transparent); }
body.kq-dark .kq-q.shake { background: rgba(239,68,68,.08); }
body.kq-dark .kq-q-text,
body.kq-dark .kq-state h3,
body.kq-dark .kq-faq h4,
body.kq-dark .kq-faq-q { color: #E2E8F0; }
body.kq-dark .kq-state p,
body.kq-dark .kq-progress-label,
body.kq-dark .kq-secure,
body.kq-dark .kq-faq-a p { color: #94A3B8; }
body.kq-dark .kq-stars label { color: #334155; }
body.kq-dark .kq-stars label:hover,
body.kq-dark .kq-stars label:hover ~ label,
body.kq-dark .kq-stars input:checked ~ label { color: #E8C55A; }
body.kq-dark .kq-progress-bar { background: #334155; }
body.kq-dark .kq-faq-item { background: #0F172A; border-color: #334155; }
body.kq-dark .kq-faq-q:hover { background: #1E293B; }
body.kq-dark .kq-alert.danger { background: rgba(239,68,68,.15); color: #FCA5A5; }
body.kq-dark .kq-stats-card { background: #1E293B; border-color: #334155; }
body.kq-dark .kq-stats-num { color: #E2E8F0; }
body.kq-dark .kq-link-item { background: #0F172A; color: #E2E8F0; border-color: #334155; }
body.kq-dark .kq-legend { background: linear-gradient(135deg, rgba(201,162,39,.12), rgba(15,61,92,.2)); border-color: rgba(201,162,39,.3); }
body.kq-dark .kq-lg-item { background: #0F172A; border-color: #334155; }

/* ===== HERO ===== */
.kq-hero {
    min-height: 60vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 120px 0 60px;
    display: flex; align-items: center;
}
.kq-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 15% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 85% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: kqShift 16s ease-in-out infinite;
}
@keyframes kqShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.kq-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.kq-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; padding: 0 24px; }

.kq-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.kq-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: kqShimmer 3.5s ease-in-out infinite;
}
@keyframes kqShimmer { to { left: 200%; } }
.kq-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: kqPulse 2s ease infinite;
}
@keyframes kqPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.kq-hero h1 { font-size: clamp(34px, 5vw, 62px); font-weight: 800; line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em; color: #fff; }
.kq-hero h1 .kq-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: kqTextShine 4s linear infinite; font-style: italic;
}
@keyframes kqTextShine { to { background-position: 200% center; } }
.kq-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

.kq-trust { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
.kq-trust-item {
    padding: 7px 14px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.kq-trust-item span { color: #10B981; display: inline-flex; }

/* ===== STATS ===== */
.kq-stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px; max-width: 820px; margin: -40px auto 0; position: relative; z-index: 5;
    padding: 0 24px;
}
.kq-stats-card {
    background: #fff; border-radius: 18px; padding: 22px 20px;
    border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 14px;
    box-shadow: 0 10px 30px rgba(15,61,92,.1); transition: .3s;
}
.kq-stats-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(15,61,92,.15); }
.kq-stats-ic {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 22px; color: #fff;
}
.kq-stats-card:nth-child(1) .kq-stats-ic { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; }
.kq-stats-card:nth-child(2) .kq-stats-ic { background: linear-gradient(135deg, #10B981, #34D399); }
.kq-stats-card:nth-child(3) .kq-stats-ic { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
.kq-stats-card:nth-child(4) .kq-stats-ic { background: linear-gradient(135deg, #F59E0B, #FBBF24); }
.kq-stats-num { font-size: 24px; font-weight: 900; color: #0F3D5C; line-height: 1; letter-spacing: -.02em; }
.kq-stats-lbl { font-size: 11px; color: #64748B; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; margin-top: 3px; }

/* ===== BODY ===== */
.kq-body { padding: 60px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }

.kq-card {
    max-width: 820px; margin: 40px auto 0;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid #E2E8F0; position: relative;
    box-shadow: 0 24px 70px rgba(15,61,92,.1);
}
.kq-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #C9A227, #0F3D5C, #C9A227);
    background-size: 200% 100%; animation: kqGradLine 4s linear infinite;
}
@keyframes kqGradLine { to { background-position: 200% 0; } }

.kq-per {
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 28px 36px;
    display: flex; justify-content: space-between; align-items: center;
    gap: 16px; flex-wrap: wrap; position: relative; overflow: hidden;
}
.kq-per::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.kq-per-info { position: relative; z-index: 1; }
.kq-per-info small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.kq-per-info h3 { font-size: 22px; font-weight: 800; margin-bottom: 4px; letter-spacing: -.02em; }
.kq-per-info p { color: rgba(255,255,255,.85); font-size: 13.5px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.kq-per-badge {
    padding: 7px 16px; border-radius: 50px;
    background: rgba(16,185,129,.18); border: 1px solid rgba(16,185,129,.35);
    color: #6EE7B7; font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase;
    position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 6px;
}
.kq-per-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #6EE7B7; animation: kqPulse 2s ease infinite; }
.kq-countdown {
    padding: 4px 12px; border-radius: 50px; background: rgba(201,162,39,.18);
    color: #E8C55A; font-size: 11px; font-weight: 800; letter-spacing: .8px;
    display: inline-flex; align-items: center; gap: 5px;
}

/* ===== STEP INDICATOR ===== */
.kq-steps-nav {
    display: flex; gap: 6px; padding: 16px 36px 0;
    background: rgba(15,61,92,.02); border-bottom: 1px solid #E2E8F0; overflow-x: auto;
}
body.kq-dark .kq-steps-nav { background: rgba(0,0,0,.2); border-bottom-color: #334155; }
.kq-step-ind {
    display: flex; align-items: center; gap: 6px;
    padding: 10px 4px; position: relative; flex: 1; min-width: 80px;
    transition: .3s;
}
.kq-step-ind::after {
    content: ''; flex: 1; height: 2px; background: #E2E8F0;
    margin: 0 4px; border-radius: 2px; transition: background .4s;
}
body.kq-dark .kq-step-ind::after { background: #334155; }
.kq-step-ind:last-child::after { display: none; }
.kq-step-ind.done::after { background: linear-gradient(90deg, #10B981, #34D399); }
.kq-step-ind.active::after { background: linear-gradient(90deg, #C9A227, #E8C55A); }
.kq-step-num-i {
    width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
    background: #E2E8F0; color: #64748B; font-size: 11px; font-weight: 800;
    display: grid; place-items: center; transition: .3s;
}
body.kq-dark .kq-step-num-i { background: #334155; color: #94A3B8; }
.kq-step-ind.done .kq-step-num-i { background: linear-gradient(135deg, #10B981, #34D399); color: #fff; }
.kq-step-ind.active .kq-step-num-i { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; transform: scale(1.15); box-shadow: 0 4px 14px rgba(201,162,39,.4); }
.kq-step-lbl-i { font-size: 10.5px; font-weight: 700; color: #64748B; letter-spacing: .4px; white-space: nowrap; }
body.kq-dark .kq-step-lbl-i { color: #94A3B8; }
.kq-step-ind.active .kq-step-lbl-i { color: #0F3D5C; }
body.kq-dark .kq-step-ind.active .kq-step-lbl-i { color: #E8C55A; }
@media (max-width: 640px) { .kq-steps-nav { padding: 12px 16px 0; } .kq-step-lbl-i { display: none; } }

.kq-progress-wrap {
    padding: 18px 36px; background: rgba(247,249,252,.95); backdrop-filter: blur(12px);
    border-bottom: 1px solid #E2E8F0;
    display: flex; justify-content: space-between; align-items: center; gap: 14px;
    position: sticky; top: 0; z-index: 10;
}
body.kq-dark .kq-progress-wrap { background: rgba(15,23,42,.95); border-bottom-color: #334155; }
.kq-progress-label { font-size: 13px; font-weight: 700; color: #0F3D5C; }
.kq-progress-label span { color: #C9A227; }
.kq-progress-bar {
    flex: 1; max-width: 420px; height: 8px; background: #E2E8F0;
    border-radius: 8px; overflow: hidden;
}
.kq-progress-fill {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, #C9A227, #E8C55A);
    border-radius: 8px; transition: width .4s cubic-bezier(.16,1,.3,1);
    box-shadow: 0 0 14px rgba(201,162,39,.4);
}
.kq-progress-count {
    padding: 4px 12px; border-radius: 50px;
    background: rgba(201,162,39,.12); color: #C9A227;
    font-size: 12px; font-weight: 800; letter-spacing: .5px;
}

.kq-form-body { padding: 36px; }

/* Section Aspect Separator */
.kq-aspect-sep {
    display: flex; align-items: center; gap: 14px;
    margin: 36px 0 24px; padding-bottom: 12px;
    border-bottom: 2px dashed #E2E8F0;
}
body.kq-dark .kq-aspect-sep { border-bottom-color: #334155; }
.kq-aspect-sep-ic {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 18px; color: #fff;
    box-shadow: 0 6px 18px rgba(15,61,92,.15);
}
.kq-aspect-seh4 { font-size: 16px; color: #0F3D5C; margin: 0; letter-spacing: -.01em; font-weight: 800; }
.kq-aspect-sep small { color: #64748B; font-size: 12px; display: block; margin-top: 2px; }

.kq-q {
    padding: 22px 0; border-bottom: 1px dashed #E2E8F0;
    transition: .3s;
}
body.kq-dark .kq-q { border-bottom-color: #334155; }
.kq-q:last-of-type { border-bottom: none; }
.kq-q.answered {
    background: linear-gradient(90deg, rgba(16,185,129,.04), transparent);
    padding-left: 12px; margin-left: -12px; margin-right: -12px;
    padding-right: 12px; border-radius: 12px;
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
}
.kq-q.shake {
    animation: shake 0.4s ease-in-out;
    background: rgba(239, 68, 68, 0.05);
    border-radius: 12px;
}

.kq-q-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 10px; flex-wrap: wrap; }
.kq-q-num {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 3px 10px; border-radius: 8px;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    color: #fff; font-size: 11px; font-weight: 800; letter-spacing: 1px;
    min-width: 32px; text-align: center;
}
.kq-q.answered .kq-q-num { background: linear-gradient(135deg, #10B981, #34D399); }
.kq-aspek {
    padding: 4px 12px; border-radius: 50px;
    font-size: 10.5px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
    border: 1px solid transparent; display: inline-flex; align-items: center; gap: 5px;
}
.kq-aspek.Pendidikan { background: rgba(16,185,129,.1); color: #059669; border-color: rgba(16,185,129,.25); }
.kq-aspek.Penelitian { background: rgba(59,130,246,.1); color: #1D4ED8; border-color: rgba(59,130,246,.25); }
.kq-aspek.Pengabdian { background: rgba(245,158,11,.1); color: #B45309; border-color: rgba(245,158,11,.25); }
.kq-aspek.Layanan { background: rgba(139,92,246,.1); color: #6D28D9; border-color: rgba(139,92,246,.25); }
.kq-aspek.Sarana { background: rgba(236,72,153,.1); color: #BE185D; border-color: rgba(236,72,153,.25); }
.kq-aspek.default { background: rgba(15,61,92,.06); color: #0F3D5C; }

.kq-q-text { font-weight: 600; font-size: 15px; color: #0F3D5C; line-height: 1.5; }
.kq-q-feedback {
    display: inline-flex; align-items: center; gap: 6px;
    margin-left: 8px; font-size: 20px; opacity: 0; transform: scale(0);
    transition: .3s cubic-bezier(.34,1.56,.64,1);
}
.kq-q-feedback.show { opacity: 1; transform: scale(1); }

/* Legend */
.kq-legend {
    padding: 16px; border-radius: 14px;
    background: linear-gradient(135deg, rgba(201,162,39,.06), rgba(15,61,92,.03));
    border: 1px solid rgba(201,162,39,.2); margin-bottom: 20px;
}
.kq-legend-title { font-size: 12px; font-weight: 800; color: #0F3D5C; margin-bottom: 10px; letter-spacing: .5px; display: flex; align-items: center; gap: 8px; }
.kq-legend-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
.kq-lg-item {
    padding: 8px 4px; border-radius: 8px; background: #fff;
    border: 1px solid #E2E8F0; text-align: center;
}
.kq-lg-stars { font-size: 14px; color: #C9A227; margin-bottom: 2px; display: flex; justify-content: center; gap: 2px; }
.kq-lg-label { font-size: 9.5px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; }
.kq-lg-item.s1 .kq-lg-label { color: #EF4444; }
.kq-lg-item.s2 .kq-lg-label { color: #F59E0B; }
.kq-lg-item.s3 .kq-lg-label { color: #64748B; }
.kq-lg-item.s4 .kq-lg-label { color: #10B981; }
.kq-lg-item.s5 .kq-lg-label { color: #C9A227; }

.kq-stars {
    display: flex; flex-direction: row-reverse; justify-content: flex-end;
    gap: 8px; margin-top: 14px;
}
.kq-stars input { display: none; }
.kq-stars label {
    font-size: 36px; color: #D8DEE8; cursor: pointer;
    transition: .2s cubic-bezier(.34,1.56,.64,1); line-height: 1;
    display: inline-flex;
}
.kq-stars label:hover,
.kq-stars label:hover ~ label,
.kq-stars input:checked ~ label {
    color: #C9A227;
    text-shadow: 0 0 20px rgba(201,162,39,.6);
}
.kq-stars label:hover { transform: scale(1.25) rotate(-5deg); }
.kq-stars input:checked ~ label { animation: kqStarPop .5s cubic-bezier(.34,1.56,.64,1); }
@keyframes kqStarPop { 0% { transform: scale(1); } 50% { transform: scale(1.3); } 100% { transform: scale(1); } }

.kq-submit {
    margin-top: 28px; width: 100%; padding: 18px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; box-shadow: 0 12px 36px rgba(201,162,39,.45);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.kq-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.kq-submit:hover::before { left: 150%; }
.kq-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(201,162,39,.65); }
.kq-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }
.kq-kbd {
    font-size: 10.5px; padding: 3px 8px; border-radius: 5px;
    background: rgba(15,61,92,.12); color: #0F3D5C; font-weight: 800;
    letter-spacing: .4px; margin-left: auto;
}

.kq-secure {
    display: flex; align-items: center; gap: 10px; margin-top: 16px;
    font-size: 12px; color: #64748B;
}
.kq-secure-ic {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(16,185,129,.1); color: #10B981;
    display: grid; place-items: center; font-size: 12px;
}

.kq-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600;
    display: flex; gap: 12px; align-items: center;
    animation: kqAlertIn .45s cubic-bezier(.16,1,.3,1);
}
@keyframes kqAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.kq-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444; }

.kq-state { text-align: center; padding: 60px 40px; position: relative; overflow: hidden; }
.kq-state-ic {
    width: 100px; height: 100px; border-radius: 50%; margin: 0 auto 24px;
    display: grid; place-items: center; font-size: 48px;
    box-shadow: 0 18px 50px rgba(0,0,0,.15); position: relative; z-index: 1;
}
.kq-state-ic.error { background: linear-gradient(135deg, #64748B, #94A3B8); color: #fff; }
.kq-state-ic.success {
    background: linear-gradient(135deg, #10B981, #34D399); color: #fff;
    animation: kqSuccessPop .7s cubic-bezier(.34,1.56,.64,1);
}
@keyframes kqSuccessPop {
    0% { transform: scale(0) rotate(-180deg); }
    60% { transform: scale(1.15) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}
.kq-state h3 { font-size: 24px; color: #0F3D5C; margin-bottom: 10px; letter-spacing: -.02em; position: relative; z-index: 1; }
.kq-state p { color: #64748B; font-size: 15px; max-width: 440px; margin: 0 auto; line-height: 1.6; position: relative; z-index: 1; }

.kq-confetti {
    position: absolute; width: 10px; height: 10px; opacity: 0;
    animation: kqConfettiFall 3s ease-out forwards; pointer-events: none;
}
@keyframes kqConfettiFall {
    0% { transform: translateY(-100px) rotate(0); opacity: 1; }
    100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
}

/* FAQ */
.kq-faq { max-width: 820px; margin: 40px auto 0; padding: 0 24px; }
.kq-faq h4 { font-size: 18px; color: #0F3D5C; margin-bottom: 16px; font-weight: 800; text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px; }
body.kq-dark .kq-faq h4 { color: #E2E8F0; }
.kq-faq-item {
    background: #fff; border: 1px solid #E2E8F0; border-radius: 12px;
    margin-bottom: 10px; overflow: hidden; transition: .3s;
}
.kq-faq-item:hover { border-color: rgba(201,162,39,.4); }
.kq-faq-q {
    padding: 16px 20px; cursor: pointer; font-size: 14px; font-weight: 700;
    color: #0F3D5C; display: flex; justify-content: space-between; align-items: center;
    transition: .3s;
}
.kq-faq-q:hover { background: #F7F9FC; }
.kq-faq-q svg { font-size: 16px; color: #C9A227; transition: transform .3s; flex-shrink: 0; }
.kq-faq-item.open .kq-faq-q svg { transform: rotate(180deg); }
.kq-faq-a {
    max-height: 0; overflow: hidden; transition: max-height .3s ease;
    padding: 0 20px;
}
.kq-faq-item.open .kq-faq-a { max-height: 320px; padding: 0 20px 16px; }
.kq-faq-a p { font-size: 13px; color: #64748B; line-height: 1.6; margin: 0; }

/* Related Links */
.kq-links {
    max-width: 820px; margin: 30px auto 0; padding: 0 24px;
    display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;
}
.kq-link-item {
    display: flex; align-items: center; gap: 12px; padding: 14px 16px;
    border-radius: 14px; background: #fff; text-decoration: none;
    color: #0F3D5C; font-size: 13.5px; font-weight: 600;
    transition: .25s; border: 1px solid #E2E8F0;
    box-shadow: 0 4px 14px rgba(15,61,92,.05);
}
.kq-link-item:hover { background: rgba(201,162,39,.08); border-color: rgba(201,162,39,.4); transform: translateX(4px); }
.kq-link-ic {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82); color: #fff;
    display: grid; place-items: center; font-size: 16px;
}
.kq-link-item span { flex: 1; }
.kq-link-arrow { color: #C9A227; display: inline-flex; flex-shrink: 0; }

@media (max-width: 640px) {
    .kq-form-body { padding: 24px; }
    .kq-per { padding: 24px; }
    .kq-stars label { font-size: 30px; }
    .kq-progress-wrap { padding: 14px 20px; }
    .kq-legend-grid { gap: 3px; }
    .kq-lg-label { font-size: 8px; }
}

@media print {
    .kq-hero, .kq-dark-toggle, .kq-back-top, .kq-progress-global, .kq-faq, .kq-links, .kq-stats-row { display: none !important; }
    .kq-card { box-shadow: none; border: 1px solid #000; margin-top: 0; }
}
</style>

<div class="kq-progress-global"><div class="kq-progress-global-bar" id="kqProg"></div></div>

<!-- ===== HERO ===== -->
<section class="kq-hero">
    <div class="kq-hero-aurora"></div>
    <div class="kq-hero-grid"></div>
    <div class="container">
        <div class="kq-hero-inner">
            <span class="kq-hero-badge">
                <span class="kq-hero-badge-dot"></span>
                Kuesioner Monitoring & Evaluasi
            </span>
            <h1>Suara Anda, <span class="kq-grad">Mutu Kami</span></h1>
            <p class="kq-hero-lead">
                Penilaian singkat (±2 menit), anonim, dan berdampak langsung pada
                peningkatan layanan akademik kampus.
            </p>
            <div class="kq-trust">
                <div class="kq-trust-item"><span><?= $ic['lock'] ?></span> Anonim</div>
                <div class="kq-trust-item"><span><?= $ic['zap'] ?></span> ±2 Menit</div>
                <div class="kq-trust-item"><span><?= $ic['bar_chart'] ?></span> Berdampak</div>
                <div class="kq-trust-item"><span><?= $ic['check'] ?></span> Gratis</div>
            </div>
        </div>
    </div>
</section>

<?php if ($periode && $periode['status'] === 'Aktif'): ?>
<div class="kq-stats-row">
    <div class="kq-stats-card">
        <div class="kq-stats-ic"><?= $ic['users'] ?></div>
        <div>
            <div class="kq-stats-num" data-count="<?= $kqStats['responden'] ?>"><?= number_format($kqStats['responden']) ?></div>
            <div class="kq-stats-lbl">Responden</div>
        </div>
    </div>
    <div class="kq-stats-card">
        <div class="kq-stats-ic"><?= $ic['star'] ?></div>
        <div>
            <div class="kq-stats-num"><?= $kqStats['avg'] ?> <span style="font-size:.5em;">/5</span></div>
            <div class="kq-stats-lbl">Rata² Skor</div>
        </div>
    </div>
    <div class="kq-stats-card">
        <div class="kq-stats-ic"><?= $ic['smile'] ?></div>
        <div>
            <div class="kq-stats-num" data-count="<?= $kqStats['high'] ?>"><?= number_format($kqStats['high']) ?></div>
            <div class="kq-stats-lbl">Sangat Puas</div>
        </div>
    </div>
    <div class="kq-stats-card">
        <div class="kq-stats-ic"><?= $ic['calendar_days'] ?></div>
        <div>
            <div class="kq-stats-num" data-count="<?= $kqStats['days_left'] ?>"><?= $kqStats['days_left'] ?> <span style="font-size:.5em;">hari</span></div>
            <div class="kq-stats-lbl">Sisa Waktu</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== BODY ===== -->
<section class="kq-body">
    <div class="container">
        <div class="kq-card">
            <?php if (!$periode || $periode['status'] !== 'Aktif'): ?>
                <div class="kq-state">
                    <div class="kq-state-ic error"><?= $ic['lock'] ?></div>
                    <h3>Kuesioner Tidak Tersedia</h3>
                    <p>Tautan tidak valid atau periode kuesioner telah ditutup. Silakan hubungi pihak LPM untuk informasi lebih lanjut.</p>
                </div>
            <?php elseif ($sudahIsi): ?>
                <div class="kq-state" id="kqSuccessState">
                    <div class="kq-state-ic success"><?= $ic['check_circle'] ?></div>
                    <h3><?= $pesan ? Security::e($pesan) : 'Anda Sudah Mengisi' ?></h3>
                    <p>Setiap responden hanya dapat mengisi satu kali demi kevalidan data. Terima kasih atas partisipasi Anda!</p>
                    <a href="/publik/berita.php" class="btn btn-primary" style="margin-top:24px;padding:10px 22px;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#0F3D5C;border-radius:50px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:8px;box-shadow:0 8px 22px rgba(201,162,39,.4);">Baca Berita Terbaru <?= $ic['arrow_right'] ?></a>
                </div>
            <?php else: ?>
                <div class="kq-per">
                    <div class="kq-per-info">
                        <small><?= $ic['info'] ?> Periode Aktif</small>
                        <h3><?= Security::e($periode['nama_periode']) ?></h3>
                        <p>
                            <strong><?= Security::e($periode['tipe_responden']) ?></strong>
                            &nbsp;•&nbsp; <?= count($questions) ?> pernyataan
                            <?php if ($kqStats['days_left'] > 0): ?>
                                <span class="kq-countdown"><?= $ic['clock'] ?? '' ?> Sisa <?= $kqStats['days_left'] ?> hari</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="kq-per-badge">Aktif</span>
                </div>

                <?php if (!empty($aspects)): ?>
                <div class="kq-steps-nav" id="kqStepsNav">
                    <?php foreach ($aspects as $ai => $asp): ?>
                    <div class="kq-step-ind <?= $ai === 0 ? 'active' : '' ?>" data-aspect="<?= Security::e($asp) ?>">
                        <span class="kq-step-num-i"><?= $ai + 1 ?></span>
                        <span class="kq-step-lbl-i"><?= Security::e($asp) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="kq-progress-wrap">
                    <span class="kq-progress-label">Progres: <span id="kqCount">0</span> / <?= count($questions) ?></span>
                    <div class="kq-progress-bar"><div class="kq-progress-fill" id="kqFill"></div></div>
                    <span class="kq-progress-count" id="kqPercent">0%</span>
                </div>

                <div class="kq-form-body">
                    <?php if ($error): ?><div class="kq-alert danger"><?= $ic['alert_circle'] ?> <?= Security::e($error) ?></div><?php endif; ?>

                    <div class="kq-legend">
                        <div class="kq-legend-title"><?= $ic['ruler'] ?> Panduan Penilaian</div>
                        <div class="kq-legend-grid">
                            <div class="kq-lg-item s1"><div class="kq-lg-stars"><?= $ic['star_filled'] ?></div><div class="kq-lg-label">Sangat Kurang</div></div>
                            <div class="kq-lg-item s2"><div class="kq-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="kq-lg-label">Kurang</div></div>
                            <div class="kq-lg-item s3"><div class="kq-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="kq-lg-label">Cukup</div></div>
                            <div class="kq-lg-item s4"><div class="kq-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="kq-lg-label">Baik</div></div>
                            <div class="kq-lg-item s5"><div class="kq-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="kq-lg-label">Sangat Baik</div></div>
                        </div>
                    </div>

                    <form method="POST" id="kqForm">
                        <?= Security::csrfField() ?>
                        <?php
                        $lastAspect = '';
                        $qi = 0;
                        foreach ($questions as $q):
                            $aspek = $q['aspek'] ?? 'Lainnya';
                            $aspekClass = in_array($aspek, ['Pendidikan','Penelitian','Pengabdian','Layanan','Sarana']) ? $aspek : 'default';
                            
                            // Render aspect separator saat aspek berubah
                            if ($aspek !== $lastAspect):
                                $lastAspect = $aspek;
                                $aspIcon = kqAspectIcon($aspek, $ic);
                                $aspColor = match($aspekClass) {
                                    'Pendidikan' => 'linear-gradient(135deg,#10B981,#34D399)',
                                    'Penelitian' => 'linear-gradient(135deg,#3B82F6,#60A5FA)',
                                    'Pengabdian' => 'linear-gradient(135deg,#F59E0B,#FBBF24)',
                                    'Layanan' => 'linear-gradient(135deg,#8B5CF6,#A78BFA)',
                                    'Sarana' => 'linear-gradient(135deg,#EC4899,#F472B6)',
                                    default => 'linear-gradient(135deg,#0F3D5C,#1A5A82)'
                                };
                        ?>
                            <div class="kq-aspect-sep" data-aspect-sep="<?= Security::e($aspek) ?>">
                                <div class="kq-aspect-sep-ic" style="background: <?= $aspColor ?>;"><?= $aspIcon ?></div>
                                <div>
                                    <h4 class="kq-aspect-seh4">Aspek: <?= Security::e($aspek) ?></h4>
                                    <small>Berikan penilaian Anda untuk aspek ini</small>
                                </div>
                            </div>
                        <?php endif;
                            $qi++;
                        ?>
                        <div class="kq-q" data-q="<?= $q['id_pertanyaan'] ?>" data-aspect="<?= Security::e($aspek) ?>">
                            <div class="kq-q-head">
                                <span class="kq-q-num"><?= str_pad($qi, 2, '0', STR_PAD_LEFT) ?></span>
                                <span class="kq-aspek kq-aspek-<?= $aspekClass ?>"><?= $aspIcon ?> <?= Security::e($aspek) ?></span>
                            </div>
                            <p class="kq-q-text">
                                <?= Security::e($q['pertanyaan']) ?>
                                <span class="kq-q-feedback" id="fb_<?= $q['id_pertanyaan'] ?>"></span>
                            </p>
                            <div class="kq-stars">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                    <input type="radio" id="q<?= $q['id_pertanyaan'] ?>_<?= $s ?>" name="skor[<?= $q['id_pertanyaan'] ?>]" value="<?= $s ?>" data-qid="<?= $q['id_pertanyaan'] ?>" required>
                                    <label for="q<?= $q['id_pertanyaan'] ?>_<?= $s ?>" title="<?= $s ?> bintang"><?= $ic['star_filled'] ?></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <button type="submit" class="kq-submit" id="kqSubmit">
                            <?= $ic['send'] ?> Kirim Penilaian Sekarang
                            <span class="kq-kbd">Ctrl + ↵</span>
                        </button>

                        <div class="kq-secure">
                            <span class="kq-secure-ic"><?= $ic['lock'] ?></span>
                            <span>Data Anda anonim dan hanya digunakan untuk penjaminan mutu internal.</span>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- FAQ -->
        <div class="kq-faq">
            <h4><?= $ic['help_circle'] ?> Pertanyaan Umum</h4>
            <div class="kq-faq-item">
                <div class="kq-faq-q">Apakah identitas saya dirahasiakan?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="kq-faq-a"><p>Ya, 100% anonim. Sistem hanya menyimpan sidik jari perangkat untuk mencegah pengisian ganda. Nama atau identitas pribadi Anda tidak tercatat.</p></div>
            </div>
            <div class="kq-faq-item">
                <div class="kq-faq-q">Berapa lama waktu yang dibutuhkan?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="kq-faq-a"><p>Sekitar 2 menit. Anda cukup memberikan penilaian bintang (1–5) untuk setiap pernyataan yang ditampilkan.</p></div>
            </div>
            <div class="kq-faq-item">
                <div class="kq-faq-q">Mengapa saya tidak bisa mengisi ulang?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="kq-faq-a"><p>Untuk menjaga validitas data statistik, setiap perangkat hanya diperkenankan mengisi satu kali per periode kuesioner.</p></div>
            </div>
            <div class="kq-faq-item">
                <div class="kq-faq-q">Bagaimana hasil kuesioner digunakan?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="kq-faq-a"><p>Hasil dianalisis agregat dan menjadi bahan evaluasi kebijakan akademik, penyempurnaan layanan, serta pemenuhan standar akreditasi BAN-PT/LAM.</p></div>
            </div>
        </div>

        <!-- Related Links -->
        <div class="kq-links">
            <a href="/publik/tracer.php" class="kq-link-item">
                <span class="kq-link-ic"><?= $ic['graduation'] ?></span>
                <span>Tracer Study Alumni</span>
                <span class="kq-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
            <a href="/publik/survei-pengguna.php" class="kq-link-item">
                <span class="kq-link-ic"><?= $ic['award'] ?></span>
                <span>Survei Pengguna</span>
                <span class="kq-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
            <a href="/publik/berita.php" class="kq-link-item">
                <span class="kq-link-ic"><?= $ic['info'] ?></span>
                <span>Berita Kampus</span>
                <span class="kq-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
        </div>
    </div>
</section>

<button class="kq-dark-toggle" id="kqDarkToggle" aria-label="Toggle dark mode"><?= $ic['moon'] ?></button>

<script>
(function () {
    'use strict';

    var ICON = {
        check: <?= json_encode($ic['check']) ?>,
        loader: <?= json_encode($ic['loader']) ?>,
        send: <?= json_encode($ic['send']) ?>,
        moon: <?= json_encode($ic['moon']) ?>,
        sun: <?= json_encode($ic['sun']) ?>,
        alert: <?= json_encode($ic['alert_circle']) ?>,
        smile: <?= json_encode($ic['smile']) ?>,
        meh: <?= json_encode($ic['meh']) ?>,
        frown: <?= json_encode($ic['frown']) ?>
    };
    var emojiMap = { 1: ICON.frown, 2: ICON.meh, 3: ICON.smile, 4: ICON.smile, 5: ICON.smile };

    /* ===== SCROLL PROGRESS & BACK TO TOP ===== */
    var prog = document.getElementById('kqProg');
    var backTop = document.getElementById('kqBackTop');
    window.addEventListener('scroll', function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        var p = max > 0 ? (window.scrollY / max) * 100 : 0;
        if (prog) prog.style.width = p + '%';
        if (backTop) backTop.classList.toggle('visible', window.scrollY > 500);
    }, { passive: true });
    if (backTop) backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ===== DARK MODE ===== */
    var darkBtn = document.getElementById('kqDarkToggle');
    var savedDark = localStorage.getItem('kqDark') === '1';
    if (savedDark) {
        document.body.classList.add('kq-dark');
        if (darkBtn) darkBtn.innerHTML = ICON.sun;
    }
    if (darkBtn) darkBtn.addEventListener('click', function () {
        var on = document.body.classList.toggle('kq-dark');
        localStorage.setItem('kqDark', on ? '1' : '0');
        this.innerHTML = on ? ICON.sun : ICON.moon;
    });

    /* ===== COUNTER ANIMATION ===== */
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseFloat(el.getAttribute('data-count')) || 0;
        var isInt = target === Math.floor(target);
        var start = null;
        function tick(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / 1300, 1);
            var val = target * (1 - Math.pow(1 - p, 3));
            el.textContent = isInt ? Math.round(val).toLocaleString('id-ID') : val.toFixed(2);
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });

    /* ===== SUCCESS CONFETTI ===== */
    var successState = document.getElementById('kqSuccessState');
    if (successState) {
        var colors = ['#C9A227', '#E8C55A', '#10B981', '#3B82F6', '#F59E0B'];
        for (var i = 0; i < 60; i++) {
            var conf = document.createElement('div');
            conf.className = 'kq-confetti';
            conf.style.left = Math.random() * 100 + '%';
            conf.style.top = '-20px';
            conf.style.background = colors[Math.floor(Math.random() * colors.length)];
            conf.style.borderRadius = Math.random() > .5 ? '50%' : '2px';
            conf.style.animationDelay = (Math.random() * 1.5) + 's';
            conf.style.animationDuration = (2 + Math.random() * 2) + 's';
            successState.appendChild(conf);
        }
    }

    /* ===== FAQ ACCORDION ===== */
    document.querySelectorAll('.kq-faq-item').forEach(function (item) {
        var q = item.querySelector('.kq-faq-q');
        if (!q) return;
        q.setAttribute('role', 'button');
        q.setAttribute('tabindex', '0');
        q.setAttribute('aria-expanded', 'false');
        function toggleFaq() {
            var open = item.classList.toggle('open');
            q.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        q.addEventListener('click', toggleFaq);
        q.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleFaq(); }
        });
    });

    var form = document.getElementById('kqForm');
    if (!form) return;

    var fill = document.getElementById('kqFill');
    var count = document.getElementById('kqCount');
    var percent = document.getElementById('kqPercent');
    var total = form.querySelectorAll('.kq-q').length;
    var token = <?= json_encode($token) ?>;
    var STORAGE_KEY = 'kq_draft_' + token;

    // 1. Restore Draft
    try {
        var draft = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        for (var qid in draft) {
            var radio = form.querySelector('input[name="skor[' + qid + '"][value="' + draft[qid] + '"]');
            if (radio) {
                radio.checked = true;
                var fb = document.getElementById('fb_' + qid);
                if (fb) { fb.innerHTML = emojiMap[parseInt(draft[qid])]; fb.classList.add('show'); }
                var qEl = radio.closest('.kq-q');
                if (qEl) qEl.classList.add('answered');
            }
        }
    } catch (e) {}

    function updateProgress() {
        var answered = 0;
        form.querySelectorAll('.kq-q').forEach(function (q) {
            var name = q.querySelector('input[type="radio"]').name;
            var checked = form.querySelector('input[name="' + name + '"]:checked');
            if (checked) {
                answered++;
                q.classList.add('answered');
                var qid = checked.dataset.qid;
                var fb = document.getElementById('fb_' + qid);
                if (fb) { fb.innerHTML = emojiMap[parseInt(checked.value)]; fb.classList.add('show'); }
            } else {
                q.classList.remove('answered');
                var qid2 = q.dataset.q;
                var fb2 = document.getElementById('fb_' + qid2);
                if (fb2) { fb2.innerHTML = ''; fb2.classList.remove('show'); }
            }
        });
        var p = total > 0 ? Math.round((answered / total) * 100) : 0;
        if (fill) fill.style.width = p + '%';
        if (count) count.textContent = answered;
        if (percent) percent.textContent = p + '%';
    }

    // 2. Auto-Save on Change
    form.addEventListener('change', function (e) {
        if (e.target.name && e.target.name.startsWith('skor[')) {
            var draft = {};
            form.querySelectorAll('input[type="radio"]:checked').forEach(function (r) {
                var match = r.name.match(/skor\[(\d+)\]/);
                if (match) draft[match[1]] = r.value;
            });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
        }
        updateProgress();
    });

    updateProgress();

    // 3. Step Indicator (aspect-based)
    var steps = document.querySelectorAll('.kq-step-ind');
    var sepSections = document.querySelectorAll('[data-aspect-sep]');
    if (steps.length && sepSections.length) {
        var stepObs = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    var activeAsp = en.target.getAttribute('data-aspect-sep');
                    steps.forEach(function (s) {
                        var asp = s.getAttribute('data-aspect');
                        s.classList.remove('active', 'done');
                        if (asp === activeAsp) s.classList.add('active');
                    });
                }
            });
        }, { rootMargin: '-30% 0px -60% 0px' });
        sepSections.forEach(function (s) { stepObs.observe(s); });
    }

    // 4. Smart Validation & Scroll
    form.addEventListener('submit', function (e) {
        var firstUnanswered = null;
        var allAnswered = true;
        
        form.querySelectorAll('.kq-q').forEach(function (q) {
            var name = q.querySelector('input[type="radio"]').name;
            var checked = form.querySelector('input[name="' + name + '"]:checked');
            if (!checked) {
                allAnswered = false;
                q.classList.add('shake');
                if (!firstUnanswered) firstUnanswered = q;
            } else {
                q.classList.remove('shake');
            }
        });
        
        if (!allAnswered) {
            e.preventDefault();
            if (firstUnanswered) {
                firstUnanswered.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            var btn = document.getElementById('kqSubmit');
            var originalText = btn.innerHTML;
            btn.innerHTML = ICON.alert + ' Mohon lengkapi semua penilaian';
            btn.style.background = '#EF4444';
            btn.style.color = '#fff';
            setTimeout(function () {
                btn.innerHTML = originalText;
                btn.style.background = '';
                btn.style.color = '';
            }, 3000);
        } else {
            localStorage.removeItem(STORAGE_KEY);
            var btn = document.getElementById('kqSubmit');
            if (btn && !btn.dataset.busy) {
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.innerHTML = ICON.loader + ' Mengirim...';
            }
        }
    });

    // 5. Keyboard Shortcut: Ctrl/Cmd + Enter
    form.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }
    });

    // 6. Keyboard navigation for stars
    form.querySelectorAll('.kq-stars label').forEach(function (label) {
        label.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>