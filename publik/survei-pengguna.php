<?php
$pageTitle = 'Survei Pengguna Lulusan';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();
$db = Database::getInstance();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$success = false; $error = '';

$ASPEK = [
    'etika' => 'Etika & integritas lulusan',
    'keahlian' => 'Keahlian sesuai bidang kerja',
    'bahasa' => 'Kemampuan bahasa asing',
    'teknologi' => 'Penguasaan teknologi / informatika',
    'komunikasi' => 'Kemampuan komunikasi',
    'kerjasama' => 'Kerja sama dalam tim',
    'pengembangan' => 'Potensi pengembangan diri',
];

// Statistik survei
$spStats = ['total' => 0, 'avg_score' => 0, 'high_satisfaction' => 0];
try {
    $spStats['total'] = (int)$db->query("SELECT COUNT(*) FROM survei_pengguna")->fetchColumn();
    $avgCols = ['aspek_etika','aspek_keahlian','aspek_bahasa','aspek_teknologi','aspek_komunikasi','aspek_kerjasama','aspek_pengembangan'];
    $avg = $db->query("SELECT (" . implode('+', $avgCols) . ")/7 FROM survei_pengguna")->fetchAll();
    if ($avg) {
        $spStats['avg_score'] = round(array_sum(array_column($avg, 0)) / count($avg), 2);
        $spStats['high_satisfaction'] = (int)$db->query("SELECT COUNT(*) FROM survei_pengguna WHERE (" . implode('+', $avgCols) . ")/7 >= 4")->fetchColumn();
    }
} catch (Throwable $e) {}

// ===== KUMPULAN ICON SVG (GAYA MODERN & PROFESSIONAL) =====
$ic = [
    'briefcase' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    'building' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>',
    'user' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'phone' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    'graduation' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
    'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>',
    'star' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'star_filled' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'message_square' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'send' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
    'check' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    'lock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'zap' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    'target' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    'bar_chart' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/></svg>',
    'shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>',
    'handshake' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/></svg>',
    'alert_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>',
    'loader' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spSpin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
    'arrow_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>',
    'moon' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>',
    'sun' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>',
    'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'users' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'trending_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
    'help_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
    'arrow_right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
    'newspaper' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>',
    'info' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
    'smile' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>',
    'meh' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" x2="16" y1="15" y2="15"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>',
    'frown' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 16s-1.5-2-4-2-4 2-4 2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>',
    'ruler' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $ok = true; $skor = [];
    foreach ($ASPEK as $k => $l) {
        $v = (int)($_POST['aspek_' . $k] ?? 0);
        if ($v < 1 || $v > 5) $ok = false;
        $skor[$k] = $v;
    }
    
    $namaInstansi = trim($_POST['nama_instansi'] ?? '');
    $namaAlumni = trim($_POST['nama_alumni'] ?? '');
    
    if (!$namaInstansi || !$namaAlumni) {
        $error = 'Mohon lengkapi Nama Instansi dan Nama Lulusan.';
    } elseif (!$ok) {
        $error = 'Mohon beri penilaian bintang (1–5) pada semua aspek kompetensi.';
    } else {
        try {
            $db->prepare("INSERT INTO survei_pengguna (nama_instansi, nama_responden, jabatan, kontak, nama_alumni, id_prodi, tahun_lulus, aspek_etika, aspek_keahlian, aspek_bahasa, aspek_teknologi, aspek_komunikasi, aspek_kerjasama, aspek_pengembangan, komentar) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([
                   $namaInstansi, trim($_POST['nama_responden'] ?? ''), trim($_POST['jabatan'] ?? ''), trim($_POST['kontak'] ?? ''),
                   $namaAlumni, (int)($_POST['id_prodi'] ?? 0), trim($_POST['tahun_lulus'] ?? ''),
                   $skor['etika'], $skor['keahlian'], $skor['bahasa'], $skor['teknologi'], $skor['komunikasi'], $skor['kerjasama'], $skor['pengembangan'],
                   trim($_POST['komentar'] ?? '')
               ]);
            Notifier::sendRole(1, '💼 Survei Pengguna Masuk', $namaInstansi . ' menilai lulusan kami.', '/sim/admin/survei-pengguna.php', '💼');
            $success = true;
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
        }
    }
}
require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
@keyframes spSpin { to { transform: rotate(360deg); } }

/* ===== SCROLL PROGRESS ===== */
.sp-progress-global { position: fixed; top: 0; left: 0; width: 100%; height: 4px; z-index: 1100; background: rgba(15,61,92,.06); }
.sp-progress-global-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #C9A227, #E8C55A); box-shadow: 0 0 12px rgba(201,162,39,.5); transition: width .1s linear; }

/* ===== DARK MODE ===== */
.sp-dark-toggle {
    position: fixed; bottom: 24px; left: 24px; z-index: 1000;
    width: 52px; height: 52px; border-radius: 50%; border: none; cursor: pointer;
    background: #fff; color: #0F3D5C;
    display: grid; place-items: center; font-size: 22px;
    box-shadow: 0 8px 24px rgba(15,61,92,.15);
    transition: .3s;
}
.sp-dark-toggle:hover { transform: scale(1.08) rotate(15deg); }
body.sp-dark { background: #0F172A; color: #E2E8F0; }
body.sp-dark .sp-body { background: linear-gradient(180deg, #0F172A, #1E293B); }
body.sp-dark .sp-card { background: #1E293B; border-color: #334155; }
body.sp-dark .sp-form-body { background: #1E293B; }
body.sp-dark .sp-progress-wrap { background: rgba(15,23,42,.95); border-bottom-color: #334155; }
body.sp-dark .sp-sec-title h4,
body.sp-dark .sp-state h3,
body.sp-dark .sp-field input,
body.sp-dark .sp-field select,
body.sp-dark .sp-field textarea,
body.sp-dark .sp-aspect-title,
body.sp-dark .sp-legend-title { color: #E2E8F0; }
body.sp-dark .sp-sec-title small,
body.sp-dark .sp-state p,
body.sp-dark .sp-field label,
body.sp-dark .sp-secure,
body.sp-dark .sp-char-count,
body.sp-dark .sp-progress-label { color: #94A3B8; }
body.sp-dark .sp-field input,
body.sp-dark .sp-field select,
body.sp-dark .sp-field textarea { background: #0F172A; border-color: #334155; color: #E2E8F0; }
body.sp-dark .sp-field input:focus ~ label,
body.sp-dark .sp-field input:not(:placeholder-shown) ~ label,
body.sp-dark .sp-field select:focus ~ label,
body.sp-dark .sp-field.has-value label,
body.sp-dark .sp-field textarea:focus ~ label,
body.sp-dark .sp-field textarea:not(:placeholder-shown) ~ label { background: #1E293B; }
body.sp-dark .sp-field label { background: #1E293B; }
body.sp-dark .sp-aspect { background: #0F172A; border-color: #334155; }
body.sp-dark .sp-aspect.answered { background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(201,162,39,.05)); border-color: rgba(16,185,129,.3); }
body.sp-dark .sp-legend { background: linear-gradient(135deg, rgba(201,162,39,.1), rgba(15,61,92,.2)); border-color: rgba(201,162,39,.3); }
body.sp-dark .sp-lg-item { background: #1E293B; border-color: #334155; }
body.sp-dark .sp-alert.danger { background: rgba(239,68,68,.15); color: #FCA5A5; }
body.sp-dark .sp-stats-card { background: #1E293B; border-color: #334155; }
body.sp-dark .sp-faq-item { background: #0F172A; border-color: #334155; }
body.sp-dark .sp-faq-item summary { color: #E2E8F0; }
body.sp-dark .sp-link-item { background: #0F172A; color: #E2E8F0; border-color: #334155; }
body.sp-dark .sp-stars label { color: #334155; }
body.sp-dark .sp-stars label:hover,
body.sp-dark .sp-stars label:hover ~ label,
body.sp-dark .sp-stars input:checked ~ label { color: #E8C55A; }

/* ===== HERO ===== */
.sp-hero {
    min-height: 60vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 120px 0 60px;
    display: flex; align-items: center;
}
.sp-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: spShift 16s ease-in-out infinite;
}
@keyframes spShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.sp-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.sp-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; padding: 0 24px; }

.sp-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.sp-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: spShimmer 3.5s ease-in-out infinite;
}
@keyframes spShimmer { to { left: 200%; } }
.sp-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: spPulse 2s ease infinite;
}
@keyframes spPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.sp-hero h1 { font-size: clamp(34px, 5vw, 62px); font-weight: 800; line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em; }
.sp-hero h1 .sp-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: spTextShine 4s linear infinite; font-style: italic;
}
@keyframes spTextShine { to { background-position: 200% center; } }
.sp-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

.sp-trust { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
.sp-trust-item {
    padding: 7px 14px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.sp-trust-item span { color: #10B981; display: inline-flex; }

/* ===== STATS CARDS ===== */
.sp-stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px; max-width: 860px; margin: -40px auto 0; position: relative; z-index: 5;
    padding: 0 24px;
}
.sp-stats-card {
    background: #fff; border-radius: 18px; padding: 22px 20px;
    border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 14px;
    box-shadow: 0 10px 30px rgba(15,61,92,.1);
    transition: .3s;
}
.sp-stats-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(15,61,92,.15); }
.sp-stats-ic {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 22px; color: #fff;
}
.sp-stats-card:nth-child(1) .sp-stats-ic { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; }
.sp-stats-card:nth-child(2) .sp-stats-ic { background: linear-gradient(135deg, #10B981, #34D399); }
.sp-stats-card:nth-child(3) .sp-stats-ic { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
.sp-stats-num { font-size: 24px; font-weight: 900; color: #0F3D5C; line-height: 1; letter-spacing: -.02em; }
body.sp-dark .sp-stats-num { color: #E2E8F0; }
.sp-stats-lbl { font-size: 11px; color: #64748B; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; margin-top: 3px; }

/* ===== BODY ===== */
.sp-body { padding: 60px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }

.sp-card {
    max-width: 860px; margin: 40px auto 0;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid #E2E8F0; position: relative;
    box-shadow: 0 24px 70px rgba(15,61,92,.1);
}
.sp-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #C9A227, #0F3D5C, #C9A227);
    background-size: 200% 100%; animation: spGradLine 4s linear infinite;
}
@keyframes spGradLine { to { background-position: 200% 0; } }

.sp-head {
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 32px 36px;
    display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
    position: relative; overflow: hidden;
}
.sp-head::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.sp-head-ic {
    width: 68px; height: 68px; border-radius: 20px; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; display: grid; place-items: center;
    font-size: 28px;
    box-shadow: 0 10px 30px rgba(201,162,39,.4);
    position: relative; z-index: 1;
}
.sp-head-info { flex: 1; min-width: 220px; position: relative; z-index: 1; }
.sp-head-info small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: block; margin-bottom: 4px; }
.sp-head-info h3 { font-size: 22px; font-weight: 800; margin-bottom: 4px; letter-spacing: -.02em; }
.sp-head-info p { color: rgba(255,255,255,.85); font-size: 14px; display: flex; align-items: center; gap: 6px; }
.sp-head-badge {
    padding: 6px 16px; border-radius: 50px;
    background: rgba(16,185,129,.18); border: 1px solid rgba(16,185,129,.35);
    color: #6EE7B7; font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase;
    position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 5px;
}
.sp-head-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #6EE7B7; animation: spPulse 2s ease infinite; }

/* ===== STEP INDICATOR ===== */
.sp-steps {
    display: flex; gap: 8px; padding: 20px 36px 0;
    background: rgba(15,61,92,.02); border-bottom: 1px solid #E2E8F0;
}
body.sp-dark .sp-steps { background: rgba(0,0,0,.2); border-bottom-color: #334155; }
.sp-step-ind {
    flex: 1; display: flex; align-items: center; gap: 8px;
    padding: 10px 0; position: relative;
    transition: .3s;
}
.sp-step-ind::after {
    content: ''; flex: 1; height: 2px; background: #E2E8F0;
    margin: 0 6px; border-radius: 2px; transition: background .4s;
}
body.sp-dark .sp-step-ind::after { background: #334155; }
.sp-step-ind:last-child::after { display: none; }
.sp-step-ind.done::after { background: linear-gradient(90deg, #10B981, #34D399); }
.sp-step-ind.active::after { background: linear-gradient(90deg, #C9A227, #E8C55A); }
.sp-step-num {
    width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
    background: #E2E8F0; color: #64748B; font-size: 12px; font-weight: 800;
    display: grid; place-items: center; transition: .3s;
}
body.sp-dark .sp-step-num { background: #334155; color: #94A3B8; }
.sp-step-ind.done .sp-step-num { background: linear-gradient(135deg, #10B981, #34D399); color: #fff; }
.sp-step-ind.active .sp-step-num { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; transform: scale(1.15); box-shadow: 0 4px 14px rgba(201,162,39,.4); }
.sp-step-lbl { font-size: 11.5px; font-weight: 700; color: #64748B; letter-spacing: .4px; display: none; }
body.sp-dark .sp-step-lbl { color: #94A3B8; }
.sp-step-ind.active .sp-step-lbl { color: #0F3D5C; }
body.sp-dark .sp-step-ind.active .sp-step-lbl { color: #E8C55A; }
@media (min-width: 700px) { .sp-step-lbl { display: block; } }
@media (max-width: 640px) { .sp-steps { padding: 16px 20px 0; } }

.sp-progress-wrap {
    padding: 18px 36px; background: rgba(247,249,252,.95); backdrop-filter: blur(12px);
    border-bottom: 1px solid #E2E8F0;
    display: flex; justify-content: space-between; align-items: center; gap: 14px;
    position: sticky; top: 0; z-index: 10;
}
body.sp-dark .sp-progress-wrap { background: rgba(15,23,42,.95); border-bottom-color: #334155; }
.sp-progress-label { font-size: 13px; font-weight: 700; color: #0F3D5C; }
.sp-progress-label span { color: #C9A227; }
.sp-progress-bar {
    flex: 1; max-width: 420px; height: 8px; background: #E2E8F0;
    border-radius: 8px; overflow: hidden;
}
body.sp-dark .sp-progress-bar { background: #334155; }
.sp-progress-fill {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, #C9A227, #E8C55A);
    border-radius: 8px; transition: width .4s cubic-bezier(.16,1,.3,1);
    box-shadow: 0 0 14px rgba(201,162,39,.4);
}
.sp-progress-count {
    padding: 4px 12px; border-radius: 50px;
    background: rgba(201,162,39,.12); color: #C9A227;
    font-size: 12px; font-weight: 800; letter-spacing: .5px;
}

.sp-form-body { padding: 36px; }

.sp-sec-title {
    display: flex; align-items: center; gap: 12px;
    margin: 28px 0 18px; padding-bottom: 14px;
    border-bottom: 2px dashed #E2E8F0;
    scroll-margin-top: 80px;
}
body.sp-dark .sp-sec-title { border-bottom-color: #334155; }
.sp-sec-title:first-child { margin-top: 0; }
.sp-sec-ic {
    width: 40px; height: 40px; border-radius: 12px;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    color: #fff; display: grid; place-items: center; font-size: 18px;
    box-shadow: 0 6px 18px rgba(15,61,92,.2);
}
.sp-sec-title h4 { font-size: 16px; color: #0F3D5C; margin: 0; letter-spacing: -.01em; }
.sp-sec-title small { color: #64748B; font-size: 12px; display: block; margin-top: 2px; }

.sp-field { position: relative; margin-bottom: 16px; }
.sp-field input, .sp-field select, .sp-field textarea {
    width: 100%; padding: 22px 18px 10px;
    border: 2px solid #E2E8F0; border-radius: 14px;
    font-size: 14px; font-family: inherit; outline: none;
    background: #fff; color: #1E293B; transition: .3s;
}
.sp-field textarea { resize: vertical; min-height: 100px; padding-top: 26px; }
.sp-field select { cursor: pointer; appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath fill='%230F3D5C' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 16px center; padding-right: 40px;
}
.sp-field input:focus, .sp-field select:focus, .sp-field textarea:focus {
    border-color: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.15);
}
.sp-field input.error, .sp-field select.error, .sp-field textarea.error {
    border-color: #EF4444; box-shadow: 0 0 0 4px rgba(239,68,68,.15);
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
}
.sp-field.shake, .sp-aspect.shake {
    animation: shake 0.4s ease-in-out;
}

.sp-field label {
    position: absolute; left: 18px; top: 18px;
    font-size: 13px; color: #64748B;
    pointer-events: none; transition: .25s cubic-bezier(.16,1,.3,1);
    background: #fff; padding: 0 6px; font-weight: 600;
}
.sp-field input:focus ~ label,
.sp-field input:not(:placeholder-shown) ~ label,
.sp-field select:focus ~ label,
.sp-field.has-value label,
.sp-field textarea:focus ~ label,
.sp-field textarea:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 10.5px; color: #C9A227;
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
}
.sp-field-ic {
    position: absolute; right: 16px; top: 20px; font-size: 16px;
    color: #64748B; pointer-events: none; display: inline-flex;
}
.sp-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .sp-row { grid-template-columns: 1fr; } }

.sp-legend {
    padding: 18px; border-radius: 16px;
    background: linear-gradient(135deg, rgba(201,162,39,.06), rgba(15,61,92,.03));
    border: 1px solid rgba(201,162,39,.2); margin-bottom: 22px;
}
.sp-legend-title { font-size: 12px; font-weight: 800; color: #0F3D5C; margin-bottom: 10px; letter-spacing: .5px; display: flex; align-items: center; gap: 8px; }
.sp-legend-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
.sp-lg-item {
    padding: 10px 6px; border-radius: 10px; background: #fff;
    border: 1px solid #E2E8F0; text-align: center;
}
.sp-lg-stars { font-size: 14px; color: #C9A227; margin-bottom: 3px; display: flex; justify-content: center; gap: 2px; }
.sp-lg-stars.dim { color: #D8DEE8; }
.sp-lg-label { font-size: 10px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; }
.sp-lg-item.s1 .sp-lg-label { color: #EF4444; }
.sp-lg-item.s2 .sp-lg-label { color: #F59E0B; }
.sp-lg-item.s3 .sp-lg-label { color: #64748B; }
.sp-lg-item.s4 .sp-lg-label { color: #10B981; }
.sp-lg-item.s5 .sp-lg-label { color: #C9A227; }

.sp-aspect {
    padding: 20px; border-radius: 16px; margin-bottom: 14px;
    background: #fff; border: 1px solid #E2E8F0;
    transition: .3s cubic-bezier(.16,1,.3,1);
}
.sp-aspect.answered {
    background: linear-gradient(135deg, rgba(16,185,129,.04), rgba(201,162,39,.03));
    border-color: rgba(16,185,129,.3);
}
.sp-aspect:hover { border-color: rgba(201,162,39,.4); box-shadow: 0 6px 20px rgba(15,61,92,.06); }
.sp-aspect-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
.sp-aspect-num {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 3px 10px; border-radius: 8px;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    color: #fff; font-size: 11px; font-weight: 800; letter-spacing: 1px;
    min-width: 32px; text-align: center;
}
.sp-aspect.answered .sp-aspect-num { background: linear-gradient(135deg, #10B981, #34D399); }
.sp-aspect-title { font-weight: 600; font-size: 15px; color: #0F3D5C; line-height: 1.4; flex: 1; }
.sp-aspect-score {
    padding: 4px 10px; border-radius: 50px;
    background: #F7F9FC; color: #64748B;
    font-size: 11px; font-weight: 800; letter-spacing: .5px;
    transition: .3s; display: inline-flex; align-items: center; gap: 5px;
}
body.sp-dark .sp-aspect-score { background: #0F172A; color: #94A3B8; }
.sp-aspect.answered .sp-aspect-score {
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C;
}

.sp-emoji-feedback {
    display: inline-flex; margin-left: 8px; font-size: 22px;
    opacity: 0; transform: scale(0);
    transition: .3s cubic-bezier(.34,1.56,.64,1);
    color: #C9A227;
}
.sp-emoji-feedback.show {
    opacity: 1; transform: scale(1);
}

.sp-stars {
    display: flex; flex-direction: row-reverse; justify-content: flex-end;
    gap: 8px;
}
.sp-stars input { display: none; }
.sp-stars label {
    font-size: 34px; color: #D8DEE8; cursor: pointer;
    transition: .2s cubic-bezier(.34,1.56,.64,1); line-height: 1;
    display: inline-flex;
}
.sp-stars label:hover,
.sp-stars label:hover ~ label,
.sp-stars input:checked ~ label {
    color: #C9A227;
    text-shadow: 0 0 20px rgba(201,162,39,.6);
}
.sp-stars label:hover { transform: scale(1.25) rotate(-5deg); }
.sp-stars input:checked ~ label { animation: spStarPop .5s cubic-bezier(.34,1.56,.64,1); }
@keyframes spStarPop { 0% { transform: scale(1); } 50% { transform: scale(1.3); } 100% { transform: scale(1); } }

.sp-char-count {
    position: absolute; right: 16px; bottom: 12px;
    font-size: 11px; color: #64748B; font-weight: 600;
    background: rgba(255,255,255,.85); padding: 2px 6px; border-radius: 4px;
}

.sp-submit {
    margin-top: 28px; width: 100%; padding: 18px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; box-shadow: 0 12px 36px rgba(201,162,39,.45);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.sp-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.sp-submit:hover::before { left: 150%; }
.sp-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(201,162,39,.65); }
.sp-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }
.sp-kbd {
    font-size: 10.5px; padding: 3px 8px; border-radius: 5px;
    background: rgba(15,61,92,.12); color: #0F3D5C; font-weight: 800;
    letter-spacing: .4px; margin-left: auto;
}

.sp-secure {
    display: flex; align-items: center; gap: 10px; margin-top: 16px;
    font-size: 12px; color: #64748B;
}
.sp-secure-ic {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(16,185,129,.1); color: #10B981;
    display: grid; place-items: center; font-size: 12px;
}

.sp-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600;
    display: flex; gap: 12px; align-items: center;
    animation: spAlertIn .45s cubic-bezier(.16,1,.3,1);
}
@keyframes spAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.sp-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444; }

.sp-state { text-align: center; padding: 70px 40px; position: relative; overflow: hidden; }
.sp-state-ic {
    width: 110px; height: 110px; border-radius: 50%; margin: 0 auto 24px;
    display: grid; place-items: center; font-size: 52px;
    background: linear-gradient(135deg, #10B981, #34D399); color: #fff;
    box-shadow: 0 20px 55px rgba(16,185,129,.4);
    animation: spSuccessPop .7s cubic-bezier(.34,1.56,.64,1);
    position: relative; z-index: 1;
}
@keyframes spSuccessPop {
    0% { transform: scale(0) rotate(-180deg); }
    60% { transform: scale(1.15) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}
.sp-state h3 { font-size: 28px; color: #0F3D5C; margin-bottom: 12px; letter-spacing: -.02em; position: relative; z-index: 1; }
.sp-state p { color: #64748B; font-size: 16px; max-width: 480px; margin: 0 auto; line-height: 1.6; position: relative; z-index: 1; }
@keyframes spConfettiFall {
    0% { transform: translateY(-100px) rotate(0); opacity: 1; }
    100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
}

/* ===== FAQ ===== */
.sp-faq {
    max-width: 860px; margin: 40px auto 0; padding: 0 24px;
}
.sp-faq-head {
    display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
}
.sp-faq-head-ic {
    width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C;
    display: grid; place-items: center; font-size: 18px;
}
.sp-faq-head h3 { font-size: 20px; color: #0F3D5C; margin: 0; letter-spacing: -.02em; }
body.sp-dark .sp-faq-head h3 { color: #E2E8F0; }
.sp-faq-list { display: flex; flex-direction: column; gap: 10px; }
.sp-faq-item {
    background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
    overflow: hidden; transition: .3s;
}
.sp-faq-item[open] { border-color: rgba(201,162,39,.4); box-shadow: 0 6px 18px rgba(201,162,39,.1); }
.sp-faq-item summary {
    padding: 16px 20px; cursor: pointer; font-weight: 700; font-size: 14px;
    color: #1E293B; list-style: none;
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    transition: .25s;
}
.sp-faq-item summary::-webkit-details-marker { display: none; }
.sp-faq-item summary:hover { color: #0F3D5C; }
.sp-faq-plus {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
    background: rgba(201,162,39,.12); color: #C9A227;
    display: grid; place-items: center; font-size: 16px; font-weight: 900;
    transition: .3s cubic-bezier(.34,1.56,.64,1);
}
.sp-faq-item[open] .sp-faq-plus {
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; transform: rotate(45deg);
}
.sp-faq-item p {
    padding: 0 20px 18px; font-size: 13.5px; color: #64748B; line-height: 1.65;
    animation: spFaqIn .3s cubic-bezier(.16,1,.3,1);
}
@keyframes spFaqIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }

/* ===== RELATED LINKS ===== */
.sp-links {
    max-width: 860px; margin: 30px auto 0; padding: 0 24px;
    display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px;
}
.sp-link-item {
    display: flex; align-items: center; gap: 12px; padding: 14px 16px;
    border-radius: 14px; background: #fff; text-decoration: none;
    color: #0F3D5C; font-size: 13.5px; font-weight: 600;
    transition: .25s; border: 1px solid #E2E8F0;
    box-shadow: 0 4px 14px rgba(15,61,92,.05);
}
.sp-link-item:hover { background: rgba(201,162,39,.08); border-color: rgba(201,162,39,.4); transform: translateX(4px); }
.sp-link-ic {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82); color: #fff;
    display: grid; place-items: center; font-size: 16px;
}
.sp-link-item span { flex: 1; }
.sp-link-arrow { color: #C9A227; display: inline-flex; flex-shrink: 0; }

/* ===== BACK TO TOP ===== */
.sp-back-top {
    position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px;
    border-radius: 50%; background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; display: grid; place-items: center;
    font-size: 22px; box-shadow: 0 10px 30px rgba(201,162,39,.4);
    cursor: pointer; border: none; z-index: 999;
    opacity: 0; pointer-events: none; transform: translateY(20px);
    transition: .4s cubic-bezier(.16,1,.3,1);
}
.sp-back-top.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.sp-back-top:hover { transform: translateY(-4px) scale(1.05); }

@media (max-width: 640px) {
    .sp-form-body { padding: 24px; }
    .sp-head { padding: 24px; }
    .sp-stars label { font-size: 28px; }
    .sp-legend-grid { grid-template-columns: repeat(5, 1fr); gap: 3px; }
    .sp-lg-label { font-size: 8.5px; }
    .sp-progress-wrap { padding: 14px 20px; }
}

@media print {
    .sp-hero, .sp-dark-toggle, .sp-back-top, .sp-progress-global, .sp-faq, .sp-links, .sp-stats-row { display: none !important; }
    .sp-card { box-shadow: none; border: 1px solid #000; margin-top: 0; }
}
</style>

<div class="sp-progress-global"><div class="sp-progress-global-bar" id="spProg"></div></div>

<section class="sp-hero">
    <div class="sp-hero-aurora"></div>
    <div class="sp-hero-grid"></div>
    <div class="container">
        <div class="sp-hero-inner">
            <span class="sp-hero-badge">
                <span class="sp-hero-badge-dot"></span>
                Survei Pengguna Lulusan
            </span>
            <h1>Penilaian <span class="sp-grad">Pengguna Lulusan</span></h1>
            <p class="sp-hero-lead">
                Bagi instansi/perusahaan yang mempekerjakan lulusan kami —
                masukan Anda membentuk kurikulum berikutnya yang lebih relevan.
            </p>
            <div class="sp-trust">
                <div class="sp-trust-item"><span><?= $ic['zap'] ?></span> ±2 Menit</div>
                <div class="sp-trust-item"><span><?= $ic['target'] ?></span> 7 Aspek</div>
                <div class="sp-trust-item"><span><?= $ic['bar_chart'] ?></span> Berdampak</div>
                <div class="sp-trust-item"><span><?= $ic['lock'] ?></span> Rahasia</div>
            </div>
        </div>
    </div>
</section>

<div class="sp-stats-row">
    <div class="sp-stats-card">
        <div class="sp-stats-ic"><?= $ic['users'] ?></div>
        <div>
            <div class="sp-stats-num" data-count="<?= $spStats['total'] ?>"><?= number_format($spStats['total']) ?></div>
            <div class="sp-stats-lbl">Total Responden</div>
        </div>
    </div>
    <div class="sp-stats-card">
        <div class="sp-stats-ic"><?= $ic['star'] ?></div>
        <div>
            <div class="sp-stats-num"><?= $spStats['avg_score'] ?> <span style="font-size:.5em;">/5</span></div>
            <div class="sp-stats-lbl">Rata² Skor</div>
        </div>
    </div>
    <div class="sp-stats-card">
        <div class="sp-stats-ic"><?= $ic['smile'] ?></div>
        <div>
            <div class="sp-stats-num" data-count="<?= $spStats['high_satisfaction'] ?>"><?= number_format($spStats['high_satisfaction']) ?></div>
            <div class="sp-stats-lbl">Sangat Puas</div>
        </div>
    </div>
</div>

<section class="sp-body">
    <div class="container">
        <div class="sp-card">
            <?php if ($success): ?>
                <div class="sp-state" id="spSuccessState">
                    <div class="sp-state-ic"><?= $ic['handshake'] ?></div>
                    <h3>Terima Kasih atas Penilaian Anda!</h3>
                    <p>Masukan Anda menjadi bahan berharga untuk peningkatan mutu kurikulum dan layanan kami. Kami berkomitmen menghasilkan lulusan yang siap kerja dan relevan dengan kebutuhan industri.</p>
                    <a href="/publik/berita.php" class="btn btn-primary" style="margin-top:24px;padding:10px 22px;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#0F3D5C;border-radius:50px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:8px;box-shadow:0 8px 22px rgba(201,162,39,.4);">Baca Berita Terbaru <?= $ic['arrow_right'] ?></a>
                </div>
            <?php else: ?>
                <div class="sp-head">
                    <div class="sp-head-ic"><?= $ic['briefcase'] ?></div>
                    <div class="sp-head-info">
                        <small>Formulir Survei</small>
                        <h3>Penilaian Pengguna Lulusan</h3>
                        <p><?= $ic['info'] ?> Isi data instansi & beri penilaian bintang (1–5) untuk 7 aspek kompetensi lulusan.</p>
                    </div>
                    <span class="sp-head-badge">Aktif</span>
                </div>

                <div class="sp-steps" id="spSteps">
                    <div class="sp-step-ind active" data-step="1"><span class="sp-step-num">1</span><span class="sp-step-lbl">Instansi</span></div>
                    <div class="sp-step-ind" data-step="2"><span class="sp-step-num">2</span><span class="sp-step-lbl">Lulusan</span></div>
                    <div class="sp-step-ind" data-step="3"><span class="sp-step-num">3</span><span class="sp-step-lbl">Penilaian</span></div>
                    <div class="sp-step-ind" data-step="4"><span class="sp-step-num">4</span><span class="sp-step-lbl">Komentar</span></div>
                </div>

                <div class="sp-progress-wrap">
                    <span class="sp-progress-label">Aspek dinilai: <span id="spCount">0</span> / 7</span>
                    <div class="sp-progress-bar"><div class="sp-progress-fill" id="spFill"></div></div>
                    <span class="sp-progress-count" id="spPercent">0%</span>
                </div>

                <div class="sp-form-body">
                    <?php if ($error): ?><div class="sp-alert danger"><?= $ic['alert_circle'] ?> <?= Security::e($error) ?></div><?php endif; ?>

                    <form method="POST" id="spForm">
                        <?= Security::csrfField() ?>

                        <div class="sp-sec-title" data-section="1">
                            <div class="sp-sec-ic"><?= $ic['building'] ?></div>
                            <div><h4>Data Instansi & Responden</h4><small>Informasi perusahaan dan Anda sebagai penilai</small></div>
                        </div>

                        <div class="sp-row">
                            <div class="sp-field">
                                <input type="text" name="nama_instansi" id="spInstansi" placeholder=" " required autocomplete="organization">
                                <label>Nama Instansi / Perusahaan *</label>
                                <span class="sp-field-ic"><?= $ic['building'] ?></span>
                            </div>
                            <div class="sp-field">
                                <input type="text" name="nama_responden" id="spResponden" placeholder=" " autocomplete="name">
                                <label>Nama Anda (HRD/Atasan)</label>
                                <span class="sp-field-ic"><?= $ic['user'] ?></span>
                            </div>
                        </div>

                        <div class="sp-row">
                            <div class="sp-field">
                                <input type="text" name="jabatan" id="spJabatan" placeholder=" ">
                                <label>Jabatan Anda</label>
                                <span class="sp-field-ic"><?= $ic['briefcase'] ?></span>
                            </div>
                            <div class="sp-field">
                                <input type="text" name="kontak" id="spKontak" placeholder=" " autocomplete="tel">
                                <label>Email / Telepon</label>
                                <span class="sp-field-ic"><?= $ic['phone'] ?></span>
                            </div>
                        </div>

                        <div class="sp-sec-title" data-section="2">
                            <div class="sp-sec-ic"><?= $ic['graduation'] ?></div>
                            <div><h4>Data Lulusan yang Dinilai</h4><small>Identitas alumni yang Anda pekerjakan</small></div>
                        </div>

                        <div class="sp-row">
                            <div class="sp-field">
                                <input type="text" name="nama_alumni" id="spAlumni" placeholder=" " required>
                                <label>Nama Lulusan *</label>
                                <span class="sp-field-ic"><?= $ic['user'] ?></span>
                            </div>
                            <div class="sp-field has-value">
                                <select name="id_prodi" id="spProdi">
                                    <option value="0">— Pilih Prodi —</option>
                                    <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
                                </select>
                                <label>Prodi Lulusan</label>
                            </div>
                        </div>

                        <div class="sp-field">
                            <input type="text" name="tahun_lulus" id="spTahun" placeholder="cth: 2023">
                            <label>Tahun Lulus</label>
                            <span class="sp-field-ic"><?= $ic['calendar'] ?></span>
                        </div>

                        <div class="sp-sec-title" data-section="3">
                            <div class="sp-sec-ic"><?= $ic['star'] ?></div>
                            <div><h4>Penilaian Kinerja Lulusan</h4><small>Beri bintang 1–5 untuk 7 aspek kompetensi</small></div>
                        </div>

                        <div class="sp-legend">
                            <div class="sp-legend-title"><?= $ic['ruler'] ?> Panduan Penilaian</div>
                            <div class="sp-legend-grid">
                                <div class="sp-lg-item s1"><div class="sp-lg-stars"><?= $ic['star_filled'] ?></div><div class="sp-lg-label">Sangat Kurang</div></div>
                                <div class="sp-lg-item s2"><div class="sp-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="sp-lg-label">Kurang</div></div>
                                <div class="sp-lg-item s3"><div class="sp-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="sp-lg-label">Cukup</div></div>
                                <div class="sp-lg-item s4"><div class="sp-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="sp-lg-label">Baik</div></div>
                                <div class="sp-lg-item s5"><div class="sp-lg-stars"><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?><?= $ic['star_filled'] ?></div><div class="sp-lg-label">Sangat Baik</div></div>
                            </div>
                        </div>

                        <?php $i = 1; foreach ($ASPEK as $k => $label): ?>
                        <div class="sp-aspect" data-aspect="<?= $k ?>">
                            <div class="sp-aspect-head">
                                <span class="sp-aspect-num"><?= str_pad($i++, 2, '0', STR_PAD_LEFT) ?></span>
                                <span class="sp-aspect-title"><?= Security::e($label) ?></span>
                                <span class="sp-aspect-score" id="score_<?= $k ?>"><?= $ic['star'] ?> —</span>
                                <span class="sp-emoji-feedback" id="emoji_<?= $k ?>"></span>
                            </div>
                            <div class="sp-stars">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                    <input type="radio" id="a<?= $k ?><?= $s ?>" name="aspek_<?= $k ?>" value="<?= $s ?>" data-aspect="<?= $k ?>" required>
                                    <label for="a<?= $k ?><?= $s ?>" title="<?= $s ?> bintang" tabindex="0"><?= $ic['star_filled'] ?></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="sp-sec-title" data-section="4">
                            <div class="sp-sec-ic"><?= $ic['message_square'] ?></div>
                            <div><h4>Komentar & Saran</h4><small>Masukan membangun untuk almamater</small></div>
                        </div>

                        <div class="sp-field">
                            <textarea name="komentar" id="spKomentar" placeholder=" " maxlength="500"></textarea>
                            <label>Tuliskan saran untuk peningkatan mutu lulusan...</label>
                            <span class="sp-char-count" id="spCharCount">0/500</span>
                        </div>

                        <button type="submit" class="sp-submit" id="spSubmit">
                            <?= $ic['send'] ?> Kirim Penilaian Sekarang
                            <span class="sp-kbd">Ctrl + ↵</span>
                        </button>

                        <div class="sp-secure">
                            <span class="sp-secure-ic"><?= $ic['shield'] ?></span>
                            <span>Data Anda aman dan hanya digunakan untuk penjaminan mutu internal kampus.</span>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- FAQ -->
        <div class="sp-faq">
            <div class="sp-faq-head">
                <div class="sp-faq-head-ic"><?= $ic['help_circle'] ?></div>
                <h3>Pertanyaan Umum</h3>
            </div>
            <div class="sp-faq-list">
                <details class="sp-faq-item">
                    <summary>Apa tujuan survei pengguna lulusan?<span class="sp-faq-plus">+</span></summary>
                    <p>Survei ini bertujuan untuk mengukur kepuasan pengguna lulusan (perusahaan/instansi) terhadap kompetensi alumni kami. Hasilnya digunakan untuk menyelaraskan kurikulum dengan kebutuhan industri dan memenuhi standar akreditasi BAN-PT/LAM.</p>
                </details>
                <details class="sp-faq-item">
                    <summary>Siapa yang berhak mengisi survei ini?<span class="sp-faq-plus">+</span></summary>
                    <p>Survei ditujukan untuk atasan langsung, HRD, atau pimpinan instansi/perusahaan yang mempekerjakan lulusan kami. Penilaian sebaiknya diberikan oleh pihak yang mengetahui kinerja alumni secara langsung.</p>
                </details>
                <details class="sp-faq-item">
                    <summary>Berapa lama waktu yang dibutuhkan?<span class="sp-faq-plus">+</span></summary>
                    <p>Hanya sekitar 2 menit. Anda cukup mengisi data instansi, memilih lulusan yang dinilai, memberikan rating bintang untuk 7 aspek kompetensi, dan menuliskan saran singkat.</p>
                </details>
                <details class="sp-faq-item">
                    <summary>Apakah data saya akan dirahasiakan?<span class="sp-faq-plus">+</span></summary>
                    <p>Ya, 100% dirahasiakan. Data hanya digunakan dalam bentuk agregat untuk keperluan penjaminan mutu internal dan akreditasi. Identitas responden tidak dipublikasikan.</p>
                </details>
            </div>
        </div>

        <!-- Related Links -->
        <div class="sp-links">
            <a href="/publik/tracer.php" class="sp-link-item">
                <span class="sp-link-ic"><?= $ic['graduation'] ?></span>
                <span>Tracer Study Alumni</span>
                <span class="sp-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
            <a href="/publik/akreditasi.php" class="sp-link-item">
                <span class="sp-link-ic"><?= $ic['award'] ?></span>
                <span>Status Akreditasi</span>
                <span class="sp-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
            <a href="/publik/berita.php" class="sp-link-item">
                <span class="sp-link-ic"><?= $ic['newspaper'] ?></span>
                <span>Berita Kampus</span>
                <span class="sp-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
        </div>
    </div>
</section>

<button class="sp-dark-toggle" id="spDarkToggle" aria-label="Toggle dark mode"><?= $ic['moon'] ?></button>
<button class="sp-back-top" id="spBackTop" aria-label="Kembali ke atas"><?= $ic['arrow_up'] ?></button>

<script>
(function () {
    'use strict';

    var ICON = {
        check: <?= json_encode($ic['check']) ?>,
        loader: <?= json_encode($ic['loader']) ?>,
        send: <?= json_encode($ic['send']) ?>,
        star: <?= json_encode($ic['star']) ?>,
        moon: <?= json_encode($ic['moon']) ?>,
        sun: <?= json_encode($ic['sun']) ?>,
        alert: <?= json_encode($ic['alert_circle']) ?>,
        smile: <?= json_encode($ic['smile']) ?>,
        meh: <?= json_encode($ic['meh']) ?>,
        frown: <?= json_encode($ic['frown']) ?>
    };

    /* ===== SCROLL PROGRESS & BACK TO TOP ===== */
    var prog = document.getElementById('spProg');
    var backTop = document.getElementById('spBackTop');
    window.addEventListener('scroll', function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        var p = max > 0 ? (window.scrollY / max) * 100 : 0;
        if (prog) prog.style.width = p + '%';
        if (backTop) backTop.classList.toggle('visible', window.scrollY > 500);
    }, { passive: true });
    if (backTop) backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ===== DARK MODE ===== */
    var darkBtn = document.getElementById('spDarkToggle');
    var savedDark = localStorage.getItem('spDark') === '1';
    if (savedDark) {
        document.body.classList.add('sp-dark');
        if (darkBtn) darkBtn.innerHTML = ICON.sun;
    }
    if (darkBtn) darkBtn.addEventListener('click', function () {
        var on = document.body.classList.toggle('sp-dark');
        localStorage.setItem('spDark', on ? '1' : '0');
        this.innerHTML = on ? ICON.sun : ICON.moon;
    });

    /* ===== COUNTER ANIMATION ===== */
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        var start = null;
        function tick(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / 1300, 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(tick); else el.textContent = target.toLocaleString('id-ID');
        }
        requestAnimationFrame(tick);
    });

    /* ===== SUCCESS CONFETTI ===== */
    var successState = document.getElementById('spSuccessState');
    if (successState) {
        var colors = ['#C9A227', '#E8C55A', '#0F3D5C', '#10B981', '#F59E0B'];
        for (var i = 0; i < 60; i++) {
            var conf = document.createElement('div');
            conf.style.cssText = 'position:absolute;width:10px;height:10px;opacity:0;animation:spConfettiFall 3s ease-out forwards;pointer-events:none;';
            conf.style.left = Math.random() * 100 + '%';
            conf.style.top = '-20px';
            conf.style.background = colors[Math.floor(Math.random() * colors.length)];
            conf.style.borderRadius = Math.random() > .5 ? '50%' : '2px';
            conf.style.animationDelay = (Math.random() * 1.5) + 's';
            conf.style.animationDuration = (2 + Math.random() * 2) + 's';
            successState.appendChild(conf);
        }
    }

    var form = document.getElementById('spForm');
    if (!form) return;

    var fill = document.getElementById('spFill');
    var count = document.getElementById('spCount');
    var percent = document.getElementById('spPercent');
    var total = 7;
    var aspekKeys = ['etika','keahlian','bahasa','teknologi','komunikasi','kerjasama','pengembangan'];
    var emojiMap = { 1: ICON.frown, 2: ICON.meh, 3: ICON.smile, 4: ICON.smile, 5: ICON.smile };
    var STORAGE_KEY = 'sp_form_draft_v2';

    // 1. Restore Draft
    try {
        var draft = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        ['nama_instansi', 'nama_responden', 'jabatan', 'kontak', 'nama_alumni', 'tahun_lulus', 'komentar'].forEach(function (name) {
            var el = document.getElementById('sp' + name.charAt(0).toUpperCase() + name.slice(1));
            if (el && draft[name]) el.value = draft[name];
        });
        if (draft.id_prodi) {
            var prodiEl = document.getElementById('spProdi');
            if (prodiEl) prodiEl.value = draft.id_prodi;
        }
        aspekKeys.forEach(function (k) {
            if (draft['aspek_' + k]) {
                var radio = form.querySelector('input[name="aspek_' + k + '"][value="' + draft['aspek_' + k] + '"]');
                if (radio) radio.checked = true;
            }
        });
    } catch (e) {}

    function updateProgress() {
        var answered = 0;
        aspekKeys.forEach(function (k) {
            var checked = form.querySelector('input[name="aspek_' + k + '"]:checked');
            var card = form.querySelector('.sp-aspect[data-aspect="' + k + '"]');
            var scoreEl = document.getElementById('score_' + k);
            var emojiEl = document.getElementById('emoji_' + k);
            
            if (checked) {
                answered++;
                if (card) card.classList.add('answered');
                if (scoreEl) scoreEl.innerHTML = ICON.star + ' ' + checked.value;
                if (emojiEl) {
                    emojiEl.innerHTML = emojiMap[parseInt(checked.value)];
                    emojiEl.classList.add('show');
                }
            } else {
                if (card) card.classList.remove('answered');
                if (scoreEl) scoreEl.innerHTML = ICON.star + ' —';
                if (emojiEl) emojiEl.classList.remove('show');
            }
        });
        var p = Math.round((answered / total) * 100);
        if (fill) fill.style.width = p + '%';
        if (count) count.textContent = answered;
        if (percent) percent.textContent = p + '%';
    }

    // 2. Auto-Save & Character Counter
    form.addEventListener('input', function (e) {
        var draft = {};
        ['nama_instansi', 'nama_responden', 'jabatan', 'kontak', 'nama_alumni', 'tahun_lulus', 'komentar'].forEach(function (name) {
            var el = document.getElementById('sp' + name.charAt(0).toUpperCase() + name.slice(1));
            if (el) draft[name] = el.value;
        });
        var prodiEl = document.getElementById('spProdi');
        if (prodiEl) draft.id_prodi = prodiEl.value;
        
        aspekKeys.forEach(function (k) {
            var checked = form.querySelector('input[name="aspek_' + k + '"]:checked');
            if (checked) draft['aspek_' + k] = checked.value;
        });
        localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));

        if (e.target.name === 'komentar') {
            var charCount = document.getElementById('spCharCount');
            if (charCount) {
                charCount.textContent = e.target.value.length + '/500';
                charCount.style.color = e.target.value.length > 450 ? '#F59E0B' : '#64748B';
            }
        }
        updateProgress();
    });

    form.addEventListener('change', updateProgress);
    updateProgress();

    var komentarEl = document.getElementById('spKomentar');
    if (komentarEl) {
        document.getElementById('spCharCount').textContent = komentarEl.value.length + '/500';
    }

    // ===== STEP INDICATOR (scroll-based) =====
    var steps = document.querySelectorAll('.sp-step-ind');
    var sections = document.querySelectorAll('[data-section]');
    var stepObs = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) {
                var activeSec = en.target.getAttribute('data-section');
                steps.forEach(function (s) {
                    var sn = s.getAttribute('data-step');
                    s.classList.remove('active', 'done');
                    if (parseInt(sn) < parseInt(activeSec)) s.classList.add('done');
                    else if (parseInt(sn) === parseInt(activeSec)) s.classList.add('active');
                });
            }
        });
    }, { rootMargin: '-30% 0px -60% 0px' });
    sections.forEach(function (s) { stepObs.observe(s); });

    // 3. Smart Validation & Scroll
    form.addEventListener('submit', function (e) {
        var firstInvalid = null;
        var isValid = true;

        ['spInstansi', 'spAlumni'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && !el.value.trim()) {
                isValid = false;
                el.classList.add('error');
                el.parentElement.classList.add('shake');
                if (!firstInvalid) firstInvalid = el;
            } else if (el) {
                el.classList.remove('error');
                el.parentElement.classList.remove('shake');
            }
        });

        aspekKeys.forEach(function (k) {
            var checked = form.querySelector('input[name="aspek_' + k + '"]:checked');
            var card = form.querySelector('.sp-aspect[data-aspect="' + k + '"]');
            if (!checked) {
                isValid = false;
                if (card) card.classList.add('shake');
                if (!firstInvalid) firstInvalid = card;
            } else {
                if (card) card.classList.remove('shake');
            }
        });

        if (!isValid) {
            e.preventDefault();
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            var btn = document.getElementById('spSubmit');
            var originalText = btn.innerHTML;
            btn.innerHTML = ICON.alert + ' Mohon lengkapi semua data wajib';
            btn.style.background = '#EF4444';
            btn.style.color = '#fff';
            setTimeout(function () {
                btn.innerHTML = originalText;
                btn.style.background = '';
                btn.style.color = '';
            }, 3000);
            return;
        }

        localStorage.removeItem(STORAGE_KEY);
        
        var btn = document.getElementById('spSubmit');
        if (btn && !btn.dataset.busy) {
            btn.dataset.busy = '1';
            btn.disabled = true;
            btn.innerHTML = ICON.loader + ' Mengirim...';
        }
    });

    // 4. Keyboard Navigation for Stars
    form.querySelectorAll('.sp-stars label').forEach(function (label) {
        label.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // 5. Keyboard Shortcut: Ctrl/Cmd + Enter
    form.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>