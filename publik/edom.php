<?php
$pageTitle = 'EDOM — Evaluasi Dosen oleh Mahasiswa';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();
$db = Database::getInstance();

$EDOM_Q = [
    1  => ['q' => 'Kejelasan penyampaian tujuan & materi pembelajaran', 'asp' => 'Metode Pembelajaran'],
    2  => ['q' => 'Penguasaan materi dan kemampuan menjawab pertanyaan', 'asp' => 'Metode Pembelajaran'],
    3  => ['q' => 'Penggunaan metode mengajar yang bervariasi & menarik', 'asp' => 'Metode Pembelajaran'],
    4  => ['q' => 'Pemanfaatan media & teknologi pembelajaran', 'asp' => 'Metode Pembelajaran'],
    5  => ['q' => 'Ketepatan waktu & konsistensi kehadiran mengajar', 'asp' => 'Profesionalisme'],
    6  => ['q' => 'Interaksi & kepedulian terhadap kesulitan mahasiswa', 'asp' => 'Profesionalisme'],
    7  => ['q' => 'Keadilan & transparansi penilaian/evaluasi', 'asp' => 'Profesionalisme'],
    8  => ['q' => 'Pemberian umpan balik (feedback) atas tugas', 'asp' => 'Umpan Balik & Keteladanan'],
    9  => ['q' => 'Sikap, etika, dan keteladanan di dalam kelas', 'asp' => 'Umpan Balik & Keteladanan'],
    10 => ['q' => 'Kemampuan memotivasi & menginspirasi mahasiswa', 'asp' => 'Umpan Balik & Keteladanan'],
];

$token = $_GET['token'] ?? '';
$kelas = null;
if ($token) {
    $st = $db->prepare("SELECT k.*, d.nama_dosen, per.nama periode_nama, per.status status_periode, p.nama_prodi
        FROM edom_kelas k
        JOIN edom_periode per ON k.id_periode = per.id_periode
        JOIN dosen d ON k.id_dosen = d.id_dosen
        LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
        WHERE k.token = ?");
    $st->execute([$token]);
    $kelas = $st->fetch();
}

$success = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kelas) {
    Security::verifyCsrf();
    if ($kelas['status_periode'] !== 'Terbuka') $error = 'Periode evaluasi ini sudah ditutup.';
    else {
        $nim = trim($_POST['nim'] ?? '');
        $chk = $db->prepare("SELECT id_jawaban FROM edom_jawaban WHERE id_kelas = ? AND nim = ?");
        $chk->execute([$kelas['id_kelas'], $nim]);
        if (!$nim) $error = 'NIM wajib diisi.';
        elseif ($chk->fetch()) $error = 'NIM ini sudah mengisi untuk kelas ini. Terima kasih atas partisipasi Anda sebelumnya!';
        else {
            $ok = true; $vals = [];
            for ($i = 1; $i <= 10; $i++) { $v = (int)($_POST['q' . $i] ?? 0); if ($v < 1 || $v > 5) $ok = false; $vals[] = $v; }
            if (!$ok) $error = 'Beri penilaian bintang 1–5 untuk semua aspek.';
            else {
                try {
                    $db->prepare("INSERT INTO edom_jawaban (id_kelas, nim, q1,q2,q3,q4,q5,q6,q7,q8,q9,q10, komentar) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                       ->execute(array_merge([$kelas['id_kelas'], $nim], $vals, [trim($_POST['komentar'] ?? '')]));
                    $success = true;
                } catch (PDOException $e) {
                    $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
                }
            }
        }
    }
}

// Statistik EDOM untuk kelas ini
$edomStats = ['responden' => 0, 'avg' => 0, 'puas' => 0, 'days_left' => 0];
if ($kelas) {
    try {
        $edomStats['responden'] = (int)$db->query("SELECT COUNT(*) FROM edom_jawaban WHERE id_kelas = " . (int)$kelas['id_kelas'])->fetchColumn();
        $avgCols = array_map(fn($i) => "AVG(q$i)", range(1, 10));
        $avg = $db->query("SELECT (" . implode('+', $avgCols) . ")/10 FROM edom_jawaban WHERE id_kelas = " . (int)$kelas['id_kelas'])->fetchAll(PDO::FETCH_COLUMN);
        if ($avg) {
            $edomStats['avg'] = round(array_sum($avg) / count($avg), 2);
        }
        // Hitung responden yang rata-rata >= 4 (puas)
        $puasRows = $db->query("SELECT nim, (" . implode('+', array_map(fn($i) => "q$i", range(1,10))) . ")/10 AS avg_s FROM edom_jawaban WHERE id_kelas = " . (int)$kelas['id_kelas'])->fetchAll();
        $edomStats['puas'] = count(array_filter($puasRows, fn($r) => $r['avg_s'] >= 4));
    } catch (Throwable $e) {}
}

// ===== KUMPULAN ICON SVG =====
$ic = [
    'lock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'zap' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    'target' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    'trending_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
    'link' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
    'check' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    'check_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
    'star' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'star_filled' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'alert_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>',
    'loader' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:edSpin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
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
    'book_open' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'heart_handshake' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 14 9.828 12.828a4 4 0 1 0-5.656 5.656l6.368 6.368a2 2 0 0 0 2.828 0L21 17.284a2 2 0 0 0 0-2.828L16.232 9.687a4 4 0 0 0-5.656 0L9 11.263"/><path d="M21.346 11.291a4 4 0 0 0-5.656-5.656L13.462 7.862l.707.707"/></svg>',
    'graduation' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
    'message_square' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'calendar_days' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>',
    'ruler' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>',
    'id_card' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>',
    'briefcase' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    'newspaper' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>',
];

// Helper: ikon per aspek
function edAspectIcon($asp, $ic) {
    return match ($asp) {
        'Metode Pembelajaran' => $ic['book_open'],
        'Profesionalisme' => $ic['award'],
        'Umpan Balik & Keteladanan' => $ic['heart_handshake'],
        default => $ic['target'],
    };
}

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
@keyframes edSpin { to { transform: rotate(360deg); } }

/* ===== SCROLL PROGRESS ===== */
.ed-progress-global { position: fixed; top: 0; left: 0; width: 100%; height: 4px; z-index: 1100; background: rgba(15,61,92,.06); }
.ed-progress-global-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #C9A227, #E8C55A); box-shadow: 0 0 12px rgba(201,162,39,.5); transition: width .1s linear; }

/* ===== DARK MODE ===== */
.ed-dark-toggle {
    position: fixed; bottom: 24px; left: 24px; z-index: 1000;
    width: 52px; height: 52px; border-radius: 50%; border: none; cursor: pointer;
    background: #fff; color: #0F3D5C;
    display: grid; place-items: center; font-size: 22px;
    box-shadow: 0 8px 24px rgba(15,61,92,.15); transition: .3s;
}
.ed-dark-toggle:hover { transform: scale(1.08) rotate(15deg); }
body.ed-dark { background: #0F172A; color: #E2E8F0; }
body.ed-dark .ed-body { background: linear-gradient(180deg, #0F172A, #1E293B); }
body.ed-dark .ed-card { background: #1E293B; border-color: #334155; }
body.ed-dark .ed-form-body { background: #1E293B; }
body.ed-dark .ed-progress-wrap { background: rgba(15,23,42,.95); border-bottom-color: #334155; }
body.ed-dark .ed-q { border-bottom-color: #334155; }
body.ed-dark .ed-q.answered { background: linear-gradient(90deg, rgba(16,185,129,.08), transparent); }
body.ed-dark .ed-q.shake { background: rgba(239,68,68,.08); }
body.ed-dark .ed-q-text,
body.ed-dark .ed-state h3,
body.ed-dark .ed-faq h4,
body.ed-dark .ed-faq-q,
body.ed-dark .ed-nim-field input { color: #E2E8F0; }
body.ed-dark .ed-state p,
body.ed-dark .ed-progress-label,
body.ed-dark .ed-faq-a p,
body.ed-dark .ed-komentar-label { color: #94A3B8; }
body.ed-dark .ed-nim-field input { background: #0F172A; border-color: #334155; }
body.ed-dark .ed-nim-field label { background: #1E293B; color: #94A3B8; }
body.ed-dark .ed-nim-field input:focus ~ label,
body.ed-dark .ed-nim-field input:not(:placeholder-shown) ~ label { background: #1E293B; }
body.ed-dark .ed-komentar textarea { background: #0F172A; border-color: #334155; color: #E2E8F0; }
body.ed-dark .ed-stars label { color: #334155; }
body.ed-dark .ed-stars label:hover,
body.ed-dark .ed-stars label:hover ~ label,
body.ed-dark .ed-stars input:checked ~ label { color: #E8C55A; }
body.ed-dark .ed-progress-bar { background: #334155; }
body.ed-dark .ed-legend-wrap { background: linear-gradient(135deg, rgba(201,162,39,.1), rgba(15,61,92,.2)); border-color: rgba(201,162,39,.3); }
body.ed-dark .ed-lg-item { background: #0F172A; border-color: #334155; }
body.ed-dark .ed-faq-item { background: #0F172A; border-color: #334155; }
body.ed-dark .ed-faq-q:hover { background: #1E293B; }
body.ed-dark .ed-alert.danger { background: rgba(239,68,68,.15); color: #FCA5A5; }
body.ed-dark .ed-stats-card { background: #1E293B; border-color: #334155; }
body.ed-dark .ed-stats-num { color: #E2E8F0; }
body.ed-dark .ed-link-item { background: #0F172A; color: #E2E8F0; border-color: #334155; }
body.ed-dark .ed-aspect-sep { border-bottom-color: #334155; }

/* ===== HERO ===== */
.ed-hero {
    min-height: 60vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 120px 0 60px;
    display: flex; align-items: center;
}
.ed-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: edShift 16s ease-in-out infinite;
}
@keyframes edShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.ed-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.ed-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; padding: 0 24px; }

.ed-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.ed-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: edShimmer 3.5s ease-in-out infinite;
}
@keyframes edShimmer { to { left: 200%; } }
.ed-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: edPulse 2s ease infinite;
}
@keyframes edPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.ed-hero h1 { font-size: clamp(34px, 5vw, 62px); font-weight: 800; line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em; color: #fff; }
.ed-hero h1 .ed-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: edTextShine 4s linear infinite; font-style: italic;
}
@keyframes edTextShine { to { background-position: 200% center; } }
.ed-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

.ed-trust { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
.ed-trust-item {
    padding: 7px 14px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.ed-trust-item span { color: #10B981; display: inline-flex; }

/* ===== STATS ===== */
.ed-stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px; max-width: 820px; margin: -40px auto 0; position: relative; z-index: 5;
    padding: 0 24px;
}
.ed-stats-card {
    background: #fff; border-radius: 18px; padding: 22px 20px;
    border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 14px;
    box-shadow: 0 10px 30px rgba(15,61,92,.1); transition: .3s;
}
.ed-stats-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(15,61,92,.15); }
.ed-stats-ic {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 22px; color: #fff;
}
.ed-stats-card:nth-child(1) .ed-stats-ic { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; }
.ed-stats-card:nth-child(2) .ed-stats-ic { background: linear-gradient(135deg, #10B981, #34D399); }
.ed-stats-card:nth-child(3) .ed-stats-ic { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
.ed-stats-card:nth-child(4) .ed-stats-ic { background: linear-gradient(135deg, #8B5CF6, #A78BFA); }
.ed-stats-num { font-size: 24px; font-weight: 900; color: #0F3D5C; line-height: 1; letter-spacing: -.02em; }
.ed-stats-lbl { font-size: 11px; color: #64748B; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; margin-top: 3px; }

/* ===== BODY ===== */
.ed-body { padding: 60px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }

.ed-card {
    max-width: 820px; margin: 40px auto 0;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid #E2E8F0; position: relative;
    box-shadow: 0 24px 70px rgba(15,61,92,.1);
}
.ed-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #C9A227, #0F3D5C, #C9A227);
    background-size: 200% 100%; animation: edGradLine 4s linear infinite;
}
@keyframes edGradLine { to { background-position: 200% 0; } }

.ed-dosen {
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 32px 36px;
    display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
    position: relative; overflow: hidden;
}
.ed-dosen::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.ed-dosen-ava {
    width: 68px; height: 68px; border-radius: 20px; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; display: grid; place-items: center;
    font-size: 28px; font-weight: 900;
    box-shadow: 0 10px 30px rgba(201,162,39,.4);
    position: relative; z-index: 1;
}
.ed-dosen-info { flex: 1; min-width: 220px; position: relative; z-index: 1; }
.ed-dosen-info small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.ed-dosen-info h3 { font-size: 22px; font-weight: 800; margin-bottom: 4px; letter-spacing: -.02em; }
.ed-dosen-info p { color: rgba(255,255,255,.85); font-size: 14px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.ed-dosen-per {
    padding: 6px 16px; border-radius: 50px;
    background: rgba(16,185,129,.18); border: 1px solid rgba(16,185,129,.35);
    color: #6EE7B7; font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase;
    position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 6px;
}
.ed-dosen-per::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #6EE7B7; animation: edPulse 2s ease infinite; }

/* ===== STEP INDICATOR ===== */
.ed-steps-nav {
    display: flex; gap: 6px; padding: 16px 36px 0;
    background: rgba(15,61,92,.02); border-bottom: 1px solid #E2E8F0; overflow-x: auto;
}
body.ed-dark .ed-steps-nav { background: rgba(0,0,0,.2); border-bottom-color: #334155; }
.ed-step-ind {
    display: flex; align-items: center; gap: 6px;
    padding: 10px 4px; position: relative; flex: 1; min-width: 120px;
    transition: .3s;
}
.ed-step-ind::after {
    content: ''; flex: 1; height: 2px; background: #E2E8F0;
    margin: 0 4px; border-radius: 2px; transition: background .4s;
}
body.ed-dark .ed-step-ind::after { background: #334155; }
.ed-step-ind:last-child::after { display: none; }
.ed-step-ind.done::after { background: linear-gradient(90deg, #10B981, #34D399); }
.ed-step-ind.active::after { background: linear-gradient(90deg, #C9A227, #E8C55A); }
.ed-step-num-i {
    width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
    background: #E2E8F0; color: #64748B; font-size: 11px; font-weight: 800;
    display: grid; place-items: center; transition: .3s;
}
body.ed-dark .ed-step-num-i { background: #334155; color: #94A3B8; }
.ed-step-ind.done .ed-step-num-i { background: linear-gradient(135deg, #10B981, #34D399); color: #fff; }
.ed-step-ind.active .ed-step-num-i { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; transform: scale(1.15); box-shadow: 0 4px 14px rgba(201,162,39,.4); }
.ed-step-lbl-i { font-size: 10.5px; font-weight: 700; color: #64748B; letter-spacing: .4px; white-space: nowrap; }
body.ed-dark .ed-step-lbl-i { color: #94A3B8; }
.ed-step-ind.active .ed-step-lbl-i { color: #0F3D5C; }
body.ed-dark .ed-step-ind.active .ed-step-lbl-i { color: #E8C55A; }
@media (max-width: 640px) { .ed-steps-nav { padding: 12px 16px 0; } .ed-step-lbl-i { display: none; } }

.ed-progress-wrap {
    padding: 18px 36px; background: rgba(247,249,252,.95); backdrop-filter: blur(12px);
    border-bottom: 1px solid #E2E8F0;
    display: flex; justify-content: space-between; align-items: center; gap: 14px;
    position: sticky; top: 0; z-index: 10;
}
body.ed-dark .ed-progress-wrap { background: rgba(15,23,42,.95); border-bottom-color: #334155; }
.ed-progress-label { font-size: 13px; font-weight: 700; color: #0F3D5C; }
.ed-progress-label span { color: #C9A227; }
.ed-progress-bar {
    flex: 1; max-width: 420px; height: 8px; background: #E2E8F0;
    border-radius: 8px; overflow: hidden;
}
body.ed-dark .ed-progress-bar { background: #334155; }
.ed-progress-fill {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, #C9A227, #E8C55A);
    border-radius: 8px; transition: width .4s cubic-bezier(.16,1,.3,1);
    box-shadow: 0 0 14px rgba(201,162,39,.4);
}
.ed-progress-count {
    padding: 4px 12px; border-radius: 50px;
    background: rgba(201,162,39,.12); color: #C9A227;
    font-size: 12px; font-weight: 800; letter-spacing: .5px;
}

.ed-form-body { padding: 36px; }

/* Aspect separator */
.ed-aspect-sep {
    display: flex; align-items: center; gap: 14px;
    margin: 36px 0 24px; padding-bottom: 12px;
    border-bottom: 2px dashed #E2E8F0;
}
body.ed-dark .ed-aspect-sep { border-bottom-color: #334155; }
.ed-aspect-sep:first-child { margin-top: 0; }
.ed-aspect-sep-ic {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 18px; color: #fff;
    box-shadow: 0 6px 18px rgba(15,61,92,.15);
}
.ed-aspect-seh4 { font-size: 16px; color: #0F3D5C; margin: 0; letter-spacing: -.01em; font-weight: 800; }
.ed-aspect-sep small { color: #64748B; font-size: 12px; display: block; margin-top: 2px; }

.ed-legend-wrap {
    padding: 18px; border-radius: 16px;
    background: linear-gradient(135deg, rgba(201,162,39,.06), rgba(15,61,92,.03));
    border: 1px solid rgba(201,162,39,.2);
    margin-bottom: 24px;
}
.ed-legend-title { font-size: 12px; font-weight: 800; color: #0F3D5C; margin-bottom: 12px; letter-spacing: .5px; display: flex; align-items: center; gap: 8px; }
.ed-legend { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
.ed-lg-item {
    padding: 10px 6px; border-radius: 10px;
    background: #fff; border: 1px solid #E2E8F0; text-align: center; transition: .3s;
}
.ed-lg-item:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,61,92,.08); }
.ed-lg-stars { font-size: 14px; color: #C9A227; margin-bottom: 4px; display: flex; justify-content: center; gap: 2px; }
.ed-lg-label { font-size: 10px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; }
.ed-lg-item.s1 .ed-lg-label { color: #EF4444; }
.ed-lg-item.s2 .ed-lg-label { color: #F59E0B; }
.ed-lg-item.s3 .ed-lg-label { color: #64748B; }
.ed-lg-item.s4 .ed-lg-label { color: #10B981; }
.ed-lg-item.s5 .ed-lg-label { color: #C9A227; }
@media (max-width: 640px) { .ed-legend { gap: 4px; } .ed-lg-item { padding: 8px 4px; } .ed-lg-label { font-size: 8.5px; } }

.ed-nim-field { position: relative; margin-bottom: 24px; }
.ed-nim-field input {
    width: 100%; padding: 18px 18px 18px 48px;
    border: 2px solid #E2E8F0; border-radius: 14px;
    font-size: 15px; font-family: inherit; outline: none;
    background: #fff; color: #1E293B; transition: .3s;
}
.ed-nim-field input:focus { border-color: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.15); }
.ed-nim-field label {
    position: absolute; left: 48px; top: 18px;
    font-size: 14px; color: #64748B;
    pointer-events: none; transition: .25s cubic-bezier(.16,1,.3,1);
    background: #fff; padding: 0 6px; font-weight: 600;
}
.ed-nim-field input:focus ~ label,
.ed-nim-field input:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 11px; color: #C9A227;
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
}
.ed-nim-field-ic {
    position: absolute; left: 16px; top: 18px; font-size: 18px; color: #64748B; display: inline-flex;
}

.ed-q {
    padding: 22px 0; border-bottom: 1px dashed #E2E8F0;
    transition: .3s;
}
body.ed-dark .ed-q { border-bottom-color: #334155; }
.ed-q:last-of-type { border-bottom: none; }
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
}
.ed-q.shake {
    animation: shake 0.4s ease-in-out;
    background: rgba(239, 68, 68, 0.05);
    border-radius: 12px;
}
.ed-q.answered { background: linear-gradient(90deg, rgba(16,185,129,.03), transparent); padding-left: 12px; margin-left: -12px; margin-right: -12px; padding-right: 12px; border-radius: 12px; }
.ed-q-num {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 3px 10px; border-radius: 8px;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    color: #fff; font-size: 11px; font-weight: 800; letter-spacing: 1px;
    margin-bottom: 8px; min-width: 32px; text-align: center;
}
.ed-q.answered .ed-q-num { background: linear-gradient(135deg, #10B981, #34D399); }
.ed-q-text { font-weight: 600; font-size: 15px; color: #0F3D5C; line-height: 1.5; margin: 0; }

.ed-stars {
    display: flex; flex-direction: row-reverse; justify-content: flex-end;
    gap: 8px; margin-top: 14px; align-items: center;
}
.ed-stars input { display: none; }
.ed-stars label {
    font-size: 36px; color: #D8DEE8; cursor: pointer;
    transition: .2s cubic-bezier(.34,1.56,.64,1); line-height: 1;
    position: relative; display: inline-flex;
}
.ed-stars label:hover,
.ed-stars label:hover ~ label,
.ed-stars input:checked ~ label {
    color: #C9A227;
    text-shadow: 0 0 20px rgba(201,162,39,.6);
}
.ed-stars label:hover { transform: scale(1.25) rotate(-5deg); }
.ed-stars input:checked ~ label { animation: edStarPop .5s cubic-bezier(.34,1.56,.64,1); }
@keyframes edStarPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

.ed-emoji-feedback {
    display: inline-flex; align-items: center; margin-left: 12px; font-size: 24px;
    opacity: 0; transform: scale(0);
    transition: .3s cubic-bezier(.34,1.56,.64,1);
    color: #C9A227;
}
.ed-emoji-feedback.show { opacity: 1; transform: scale(1); }

.ed-komentar { margin-top: 32px; position: relative; }
.ed-komentar textarea {
    width: 100%; padding: 18px; border: 2px solid #E2E8F0; border-radius: 14px;
    font-size: 14px; font-family: inherit; outline: none; resize: vertical;
    min-height: 100px; transition: .3s;
}
.ed-komentar textarea:focus { border-color: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.15); }
.ed-komentar-label {
    font-size: 11px; font-weight: 800; letter-spacing: 1px;
    text-transform: uppercase; color: #C9A227;
    margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
}
.ed-char-count {
    position: absolute; right: 16px; bottom: 12px;
    font-size: 11px; color: #64748B; font-weight: 600;
    background: rgba(255,255,255,.85); padding: 2px 6px; border-radius: 4px;
}
body.ed-dark .ed-char-count { background: rgba(15,23,42,.85); }

.ed-submit {
    margin-top: 28px; width: 100%; padding: 18px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; box-shadow: 0 12px 36px rgba(201,162,39,.45);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.ed-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.ed-submit:hover::before { left: 150%; }
.ed-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(201,162,39,.65); }
.ed-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.ed-kbd {
    font-size: 10.5px; padding: 3px 8px; border-radius: 5px;
    background: rgba(15,61,92,.12); color: #0F3D5C; font-weight: 800;
    letter-spacing: .4px; margin-left: auto;
}

.ed-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600;
    display: flex; gap: 12px; align-items: center;
    animation: edAlertIn .45s cubic-bezier(.16,1,.3,1);
}
@keyframes edAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.ed-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444; }

.ed-state { text-align: center; padding: 60px 40px; position: relative; overflow: hidden; }
.ed-state-ic {
    width: 100px; height: 100px; border-radius: 50%; margin: 0 auto 24px;
    display: grid; place-items: center; font-size: 48px;
    box-shadow: 0 18px 50px rgba(0,0,0,.15);
    position: relative; z-index: 1;
}
.ed-state-ic.error { background: linear-gradient(135deg, #EF4444, #F87171); color: #fff; }
.ed-state-ic.success {
    background: linear-gradient(135deg, #10B981, #34D399); color: #fff;
    animation: edSuccessPop .7s cubic-bezier(.34,1.56,.64,1);
}
@keyframes edSuccessPop {
    0% { transform: scale(0) rotate(-180deg); }
    60% { transform: scale(1.15) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}
.ed-state h3 { font-size: 24px; color: #0F3D5C; margin-bottom: 10px; letter-spacing: -.02em; position: relative; z-index: 1; }
.ed-state p { color: #64748B; font-size: 15px; max-width: 440px; margin: 0 auto; line-height: 1.6; position: relative; z-index: 1; }

.ed-confetti {
    position: absolute; width: 10px; height: 10px; opacity: 0;
    animation: edConfettiFall 3s ease-out forwards; pointer-events: none;
}
@keyframes edConfettiFall {
    0% { transform: translateY(-100px) rotate(0); opacity: 1; }
    100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
}

/* FAQ */
.ed-faq { max-width: 820px; margin: 40px auto 0; padding: 0 24px; }
.ed-faq h4 { font-size: 18px; color: #0F3D5C; margin-bottom: 16px; font-weight: 800; text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px; }
body.ed-dark .ed-faq h4 { color: #E2E8F0; }
.ed-faq-item {
    background: #fff; border: 1px solid #E2E8F0; border-radius: 12px;
    margin-bottom: 10px; overflow: hidden; transition: .3s;
}
.ed-faq-item:hover { border-color: rgba(201,162,39,.4); }
.ed-faq-q {
    padding: 16px 20px; cursor: pointer; font-size: 14px; font-weight: 700;
    color: #0F3D5C; display: flex; justify-content: space-between; align-items: center;
    transition: .3s;
}
.ed-faq-q:hover { background: #F7F9FC; }
.ed-faq-q svg { font-size: 16px; color: #C9A227; transition: transform .3s; flex-shrink: 0; }
.ed-faq-item.open .ed-faq-q svg { transform: rotate(180deg); }
.ed-faq-a { max-height: 0; overflow: hidden; transition: max-height .3s ease; padding: 0 20px; }
.ed-faq-item.open .ed-faq-a { max-height: 320px; padding: 0 20px 16px; }
.ed-faq-a p { font-size: 13px; color: #64748B; line-height: 1.6; margin: 0; }

/* Related Links */
.ed-links {
    max-width: 820px; margin: 30px auto 0; padding: 0 24px;
    display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;
}
.ed-link-item {
    display: flex; align-items: center; gap: 12px; padding: 14px 16px;
    border-radius: 14px; background: #fff; text-decoration: none;
    color: #0F3D5C; font-size: 13.5px; font-weight: 600;
    transition: .25s; border: 1px solid #E2E8F0;
    box-shadow: 0 4px 14px rgba(15,61,92,.05);
}
.ed-link-item:hover { background: rgba(201,162,39,.08); border-color: rgba(201,162,39,.4); transform: translateX(4px); }
.ed-link-ic {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82); color: #fff;
    display: grid; place-items: center; font-size: 16px;
}
.ed-link-item span { flex: 1; }
.ed-link-arrow { color: #C9A227; display: inline-flex; flex-shrink: 0; }

/* Back to Top */
.ed-back-top {
    position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px;
    border-radius: 50%; background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; display: grid; place-items: center;
    font-size: 22px; box-shadow: 0 10px 30px rgba(201,162,39,.4);
    cursor: pointer; border: none; z-index: 999;
    opacity: 0; pointer-events: none; transform: translateY(20px);
    transition: .4s cubic-bezier(.16,1,.3,1);
}
.ed-back-top.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.ed-back-top:hover { transform: translateY(-4px) scale(1.05); }

@media (max-width: 640px) {
    .ed-form-body { padding: 24px; }
    .ed-dosen { padding: 24px; }
    .ed-stars label { font-size: 30px; }
    .ed-progress-wrap { padding: 14px 20px; }
}

@media print {
    .ed-hero, .ed-dark-toggle, .ed-back-top, .ed-progress-global, .ed-faq, .ed-links, .ed-stats-row { display: none !important; }
    .ed-card { box-shadow: none; border: 1px solid #000; margin-top: 0; }
}
</style>

<div class="ed-progress-global"><div class="ed-progress-global-bar" id="edProg"></div></div>

<section class="ed-hero">
    <div class="ed-hero-aurora"></div>
    <div class="ed-hero-grid"></div>
    <div class="container">
        <div class="ed-hero-inner">
            <span class="ed-hero-badge">
                <span class="ed-hero-badge-dot"></span>
                Evaluasi Dosen oleh Mahasiswa
            </span>
            <h1>Suara Anda, <span class="ed-grad">Peningkatan Mutu</span><br>Pembelajaran Kami</h1>
            <p class="ed-hero-lead">
                Penilaian Anda bersifat rahasia, objektif, dan berdampak langsung pada
                peningkatan kualitas pembelajaran di kampus.
            </p>
            <div class="ed-trust">
                <div class="ed-trust-item"><span><?= $ic['lock'] ?></span> 100% Rahasia</div>
                <div class="ed-trust-item"><span><?= $ic['zap'] ?></span> ±3 Menit</div>
                <div class="ed-trust-item"><span><?= $ic['target'] ?></span> Objektif</div>
                <div class="ed-trust-item"><span><?= $ic['trending_up'] ?></span> Berdampak</div>
            </div>
        </div>
    </div>
</section>

<?php if ($kelas && $kelas['status_periode'] === 'Terbuka'): ?>
<div class="ed-stats-row">
    <div class="ed-stats-card">
        <div class="ed-stats-ic"><?= $ic['users'] ?></div>
        <div>
            <div class="ed-stats-num" data-count="<?= $edomStats['responden'] ?>"><?= number_format($edomStats['responden']) ?></div>
            <div class="ed-stats-lbl">Mahasiswa Menilai</div>
        </div>
    </div>
    <div class="ed-stats-card">
        <div class="ed-stats-ic"><?= $ic['star'] ?></div>
        <div>
            <div class="ed-stats-num"><?= $edomStats['avg'] ?> <span style="font-size:.5em;">/5</span></div>
            <div class="ed-stats-lbl">Rata² Skor</div>
        </div>
    </div>
    <div class="ed-stats-card">
        <div class="ed-stats-ic"><?= $ic['smile'] ?></div>
        <div>
            <div class="ed-stats-num" data-count="<?= $edomStats['puas'] ?>"><?= number_format($edomStats['puas']) ?></div>
            <div class="ed-stats-lbl">Sangat Puas</div>
        </div>
    </div>
    <div class="ed-stats-card">
        <div class="ed-stats-ic"><?= $ic['calendar_days'] ?></div>
        <div>
            <div class="ed-stats-num">10 <span style="font-size:.5em;">Aspek</span></div>
            <div class="ed-stats-lbl">Dinilai</div>
        </div>
    </div>
</div>
<?php endif; ?>

<section class="ed-body">
    <div class="container">
        <div class="ed-card">
            <?php if (!$kelas): ?>
                <div class="ed-state">
                    <div class="ed-state-ic error"><?= $ic['link'] ?></div>
                    <h3>Tautan Tidak Valid</h3>
                    <p>Pastikan Anda membuka tautan atau QR code resmi dari Kaprodi atau LPM. Tautan ini mungkin sudah kadaluarsa atau salah.</p>
                </div>
            <?php elseif ($success): ?>
                <div class="ed-state" id="edSuccessState">
                    <div class="ed-state-ic success"><?= $ic['check_circle'] ?></div>
                    <h3>Terima Kasih atas Penilaian Anda!</h3>
                    <p>Masukan Anda telah tercatat dengan aman dan akan digunakan oleh dosen serta LPM untuk peningkatan mutu pembelajaran secara berkelanjutan.</p>
                    <a href="/publik/berita.php" class="btn btn-primary" style="margin-top:24px;padding:10px 22px;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#0F3D5C;border-radius:50px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:8px;box-shadow:0 8px 22px rgba(201,162,39,.4);">Baca Berita Terbaru <?= $ic['arrow_right'] ?></a>
                </div>
            <?php else: ?>
                <div class="ed-dosen">
                    <div class="ed-dosen-ava"><?= strtoupper(mb_substr($kelas['nama_dosen'], 0, 1)) ?></div>
                    <div class="ed-dosen-info">
                        <small><?= $ic['graduation'] ?> Dosen Pengampu</small>
                        <h3><?= Security::e($kelas['nama_dosen']) ?></h3>
                        <p><?= Security::e($kelas['mata_kuliah']) ?> &nbsp;•&nbsp; <?= Security::e($kelas['nama_prodi'] ?? '—') ?></p>
                    </div>
                    <span class="ed-dosen-per">Periode Terbuka</span>
                </div>

                <?php
                $aspectGroups = ['Metode Pembelajaran', 'Profesionalisme', 'Umpan Balik & Keteladanan'];
                ?>
                <div class="ed-steps-nav" id="edStepsNav">
                    <?php foreach ($aspectGroups as $ai => $asp): ?>
                    <div class="ed-step-ind <?= $ai === 0 ? 'active' : '' ?>" data-aspect="<?= Security::e($asp) ?>">
                        <span class="ed-step-num-i"><?= $ai + 1 ?></span>
                        <span class="ed-step-lbl-i"><?= Security::e($asp) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="ed-progress-wrap">
                    <span class="ed-progress-label">Progres: <span id="edCount">0</span> / 10 aspek</span>
                    <div class="ed-progress-bar"><div class="ed-progress-fill" id="edFill"></div></div>
                    <span class="ed-progress-count" id="edPercent">0%</span>
                </div>

                <div class="ed-form-body">
                    <div class="ed-legend-wrap">
                        <div class="ed-legend-title"><?= $ic['ruler'] ?> Panduan Penilaian Bintang</div>
                        <div class="ed-legend">
                            <div class="ed-lg-item s1"><div class="ed-lg-stars"><?= $ic['star_filled'] ?></div><div class="ed-lg-label">Sangat Kurang</div></div>
                            <div class="ed-lg-item s2"><div class="ed-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="ed-lg-label">Kurang</div></div>
                            <div class="ed-lg-item s3"><div class="ed-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="ed-lg-label">Cukup</div></div>
                            <div class="ed-lg-item s4"><div class="ed-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="ed-lg-label">Baik</div></div>
                            <div class="ed-lg-item s5"><div class="ed-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="ed-lg-label">Sangat Baik</div></div>
                        </div>
                    </div>

                    <?php if ($error): ?><div class="ed-alert danger"><?= $ic['alert_circle'] ?> <?= Security::e($error) ?></div><?php endif; ?>

                    <form method="POST" id="edForm">
                        <?= Security::csrfField() ?>
                        <div class="ed-nim-field">
                            <span class="ed-nim-field-ic"><?= $ic['id_card'] ?></span>
                            <input type="text" name="nim" id="edNim" placeholder=" " required autocomplete="off">
                            <label for="edNim">NIM Anda *</label>
                        </div>

                        <?php
                        $lastAsp = '';
                        foreach ($EDOM_Q as $i => $row):
                            $label = $row['q'];
                            $asp = $row['asp'];
                            if ($asp !== $lastAsp):
                                $lastAsp = $asp;
                                $aspIcon = edAspectIcon($asp, $ic);
                                $aspColor = match($asp) {
                                    'Metode Pembelajaran' => 'linear-gradient(135deg,#3B82F6,#60A5FA)',
                                    'Profesionalisme' => 'linear-gradient(135deg,#8B5CF6,#A78BFA)',
                                    'Umpan Balik & Keteladanan' => 'linear-gradient(135deg,#EC4899,#F472B6)',
                                    default => 'linear-gradient(135deg,#0F3D5C,#1A5A82)'
                                };
                        ?>
                            <div class="ed-aspect-sep" data-aspect-sep="<?= Security::e($asp) ?>">
                                <div class="ed-aspect-sep-ic" style="background: <?= $aspColor ?>;"><?= $aspIcon ?></div>
                                <div>
                                    <h4 class="ed-aspect-seh4">Aspek: <?= Security::e($asp) ?></h4>
                                    <small>Berikan penilaian Anda untuk aspek ini</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="ed-q" data-q="<?= $i ?>" data-aspect="<?= Security::e($asp) ?>">
                            <span class="ed-q-num"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></span>
                            <p class="ed-q-text">
                                <?= Security::e($label) ?>
                                <span class="ed-emoji-feedback" id="edEmoji<?= $i ?>"></span>
                            </p>
                            <div class="ed-stars">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                    <input type="radio" id="q<?= $i ?>_<?= $s ?>" name="q<?= $i ?>" value="<?= $s ?>" required>
                                    <label for="q<?= $i ?>_<?= $s ?>" title="<?= $s ?> bintang" tabindex="0"><?= $ic['star_filled'] ?></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="ed-komentar">
                            <label class="ed-komentar-label"><?= $ic['message_square'] ?> Komentar & Saran (Opsional)</label>
                            <textarea name="komentar" placeholder="Tuliskan masukan membangun untuk dosen Anda..." maxlength="500"></textarea>
                            <span class="ed-char-count" id="edCharCount">0/500</span>
                        </div>

                        <button type="submit" class="ed-submit" id="edSubmit">
                            <?= $ic['send'] ?> Kirim Evaluasi Sekarang
                            <span class="ed-kbd">Ctrl + ↵</span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- FAQ -->
        <div class="ed-faq">
            <h4><?= $ic['help_circle'] ?> Pertanyaan Umum</h4>
            <div class="ed-faq-item">
                <div class="ed-faq-q">Apakah identitas saya dirahasiakan?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="ed-faq-a"><p>Ya, 100% dirahasiakan. Hasil evaluasi hanya dilaporkan dalam bentuk agregat ke dosen dan LPM. NIM hanya digunakan untuk mencegah pengisian ganda dan tidak akan dibagikan.</p></div>
            </div>
            <div class="ed-faq-item">
                <div class="ed-faq-q">Berapa lama waktu yang dibutuhkan?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="ed-faq-a"><p>Sekitar 3 menit. Anda cukup memberikan penilaian bintang (1–5) untuk 10 aspek dan menuliskan saran singkat (opsional).</p></div>
            </div>
            <div class="ed-faq-item">
                <div class="ed-faq-q">Bagaimana hasil evaluasi digunakan?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="ed-faq-a"><p>Hasil digunakan sebagai bahan refleksi dosen, pengembangan profesional, serta pertimbangan dalam pembinaan akademik oleh Kaprodi dan LPM. Tidak digunakan untuk sanksi individual.</p></div>
            </div>
            <div class="ed-faq-item">
                <div class="ed-faq-q">Mengapa saya tidak bisa mengisi ulang?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="ed-faq-a"><p>Untuk menjaga validitas statistik, setiap NIM hanya diperkenankan mengisi satu kali per kelas per periode. Hal ini memastikan satu suara = satu mahasiswa.</p></div>
            </div>
        </div>

        <!-- Related Links -->
        <div class="ed-links">
            <a href="/publik/tracer.php" class="ed-link-item">
                <span class="ed-link-ic"><?= $ic['graduation'] ?></span>
                <span>Tracer Study Alumni</span>
                <span class="ed-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
            <a href="/publik/survei-pengguna.php" class="ed-link-item">
                <span class="ed-link-ic"><?= $ic['briefcase'] ?></span>
                <span>Survei Pengguna</span>
                <span class="ed-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
            <a href="/publik/berita.php" class="ed-link-item">
                <span class="ed-link-ic"><?= $ic['newspaper'] ?></span>
                <span>Berita Kampus</span>
                <span class="ed-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
        </div>
    </div>
</section>

<button class="ed-dark-toggle" id="edDarkToggle" aria-label="Toggle dark mode"><?= $ic['moon'] ?></button>
<button class="ed-back-top" id="edBackTop" aria-label="Kembali ke atas"><?= $ic['arrow_up'] ?></button>

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

    /* ===== FAQ ACCORDION (harus sebelum return) ===== */
    document.querySelectorAll('.ed-faq-item').forEach(function (item) {
        var q = item.querySelector('.ed-faq-q');
        if (!q) return;
        q.setAttribute('role', 'button');
        q.setAttribute('tabindex', '0');
        q.setAttribute('aria-expanded', 'false');
        function toggle() {
            var open = item.classList.toggle('open');
            q.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        q.addEventListener('click', toggle);
        q.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
        });
    });

    /* ===== SCROLL PROGRESS & BACK TO TOP ===== */
    var prog = document.getElementById('edProg');
    var backTop = document.getElementById('edBackTop');
    window.addEventListener('scroll', function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        var p = max > 0 ? (window.scrollY / max) * 100 : 0;
        if (prog) prog.style.width = p + '%';
        if (backTop) backTop.classList.toggle('visible', window.scrollY > 500);
    }, { passive: true });
    if (backTop) backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ===== DARK MODE ===== */
    var darkBtn = document.getElementById('edDarkToggle');
    var savedDark = localStorage.getItem('edDark') === '1';
    if (savedDark) {
        document.body.classList.add('ed-dark');
        if (darkBtn) darkBtn.innerHTML = ICON.sun;
    }
    if (darkBtn) darkBtn.addEventListener('click', function () {
        var on = document.body.classList.toggle('ed-dark');
        localStorage.setItem('edDark', on ? '1' : '0');
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
    var successState = document.getElementById('edSuccessState');
    if (successState) {
        var colors = ['#C9A227', '#E8C55A', '#10B981', '#3B82F6', '#F59E0B'];
        for (var i = 0; i < 60; i++) {
            var conf = document.createElement('div');
            conf.className = 'ed-confetti';
            conf.style.left = Math.random() * 100 + '%';
            conf.style.top = '-20px';
            conf.style.background = colors[Math.floor(Math.random() * colors.length)];
            conf.style.borderRadius = Math.random() > .5 ? '50%' : '2px';
            conf.style.animationDelay = (Math.random() * 1.5) + 's';
            conf.style.animationDuration = (2 + Math.random() * 2) + 's';
            successState.appendChild(conf);
        }
    }

    var form = document.getElementById('edForm');
    if (!form) return;

    var fill = document.getElementById('edFill');
    var count = document.getElementById('edCount');
    var percent = document.getElementById('edPercent');
    var total = 10;
    var token = <?= json_encode($token) ?>;
    var STORAGE_KEY = 'edom_draft_' + token;

    // 1. Restore Draft
    try {
        var draft = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        if (draft.nim) {
            var nimEl = document.getElementById('edNim');
            if (nimEl) nimEl.value = draft.nim;
        }
        if (draft.komentar) {
            var komEl = form.querySelector('[name="komentar"]');
            if (komEl) komEl.value = draft.komentar;
        }
        for (var i = 1; i <= 10; i++) {
            if (draft['q' + i]) {
                var radio = form.querySelector('input[name="q' + i + '"][value="' + draft['q' + i] + '"]');
                if (radio) {
                    radio.checked = true;
                    var emojiEl = document.getElementById('edEmoji' + i);
                    if (emojiEl) { emojiEl.innerHTML = emojiMap[parseInt(draft['q' + i])]; emojiEl.classList.add('show'); }
                    var qEl = radio.closest('.ed-q');
                    if (qEl) qEl.classList.add('answered');
                }
            }
        }
    } catch (e) {}

    function updateProgress() {
        var answered = 0;
        for (var i = 1; i <= total; i++) {
            var checked = form.querySelector('input[name="q' + i + '"]:checked');
            var q = form.querySelector('.ed-q[data-q="' + i + '"]');
            if (checked) {
                answered++;
                if (q) q.classList.add('answered');
                var emojiEl = document.getElementById('edEmoji' + i);
                if (emojiEl) { emojiEl.innerHTML = emojiMap[parseInt(checked.value)]; emojiEl.classList.add('show'); }
            } else {
                if (q) q.classList.remove('answered');
                var emojiEl2 = document.getElementById('edEmoji' + i);
                if (emojiEl2) { emojiEl2.innerHTML = ''; emojiEl2.classList.remove('show'); }
            }
        }
        var p = Math.round((answered / total) * 100);
        if (fill) fill.style.width = p + '%';
        if (count) count.textContent = answered;
        if (percent) percent.textContent = p + '%';
    }

    // 2. Auto-Save on Change
    form.addEventListener('change', function () {
        var draft = { nim: document.getElementById('edNim').value };
        var komEl = form.querySelector('[name="komentar"]');
        if (komEl) draft.komentar = komEl.value;
        for (var i = 1; i <= 10; i++) {
            var checked = form.querySelector('input[name="q' + i + '"]:checked');
            if (checked) draft['q' + i] = checked.value;
        }
        localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
        updateProgress();
    });

    // Character counter
    var komEl = form.querySelector('[name="komentar"]');
    var charCount = document.getElementById('edCharCount');
    if (komEl && charCount) {
        komEl.addEventListener('input', function () {
            charCount.textContent = this.value.length + '/500';
            if (this.value.length > 450) charCount.style.color = '#F59E0B';
            else charCount.style.color = '';
            var draft = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
            draft.komentar = this.value;
            localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
        });
        charCount.textContent = komEl.value.length + '/500';
    }

    updateProgress();

    // 3. Step Indicator (aspect-based)
    var steps = document.querySelectorAll('.ed-step-ind');
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
        
        for (var i = 1; i <= total; i++) {
            var checked = form.querySelector('input[name="q' + i + '"]:checked');
            var q = form.querySelector('.ed-q[data-q="' + i + '"]');
            if (!checked) {
                allAnswered = false;
                if (q) q.classList.add('shake');
                if (!firstUnanswered) firstUnanswered = q;
            } else {
                if (q) q.classList.remove('shake');
            }
        }
        
        var nimEl = document.getElementById('edNim');
        if (!nimEl.value.trim()) {
            allAnswered = false;
            nimEl.parentElement.classList.add('shake');
            if (!firstUnanswered) firstUnanswered = nimEl;
        } else {
            nimEl.parentElement.classList.remove('shake');
        }
        
        if (!allAnswered) {
            e.preventDefault();
            if (firstUnanswered) {
                firstUnanswered.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            var btn = document.getElementById('edSubmit');
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
            var btn = document.getElementById('edSubmit');
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

    // 6. Keyboard Navigation for Stars
    form.querySelectorAll('.ed-stars label').forEach(function (label) {
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