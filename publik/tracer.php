<?php
$pageTitle = 'Tracer Study Alumni';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();
$db = Database::getInstance();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$success = false; $error = '';
$tahunIni = (int)date('Y');

// Statistik tracer
$tracerStats = ['total' => 0, 'responden' => 0, 'rata_tunggu' => 0];
try {
    $tracerStats['total'] = (int)$db->query("SELECT COUNT(*) FROM prodi")->fetchColumn();
    $tracerStats['responden'] = (int)$db->query("SELECT COUNT(*) FROM tracer_alumni")->fetchColumn();
    $avg = $db->query("SELECT AVG(masa_tunggu_bulan) FROM tracer_alumni WHERE masa_tunggu_bulan > 0")->fetchColumn();
    $tracerStats['rata_tunggu'] = $avg ? round($avg, 1) : 0;
} catch (Throwable $e) {}

// ===== KUMPULAN ICON SVG (GAYA MODERN & PROFESSIONAL) =====
$ic = [
    'graduation' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
    'user' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'id_card' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>',
    'mail' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    'phone' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    'briefcase' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    'building' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>',
    'clock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'message_square' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'mic' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>',
    'send' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
    'check' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    'check_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
    'shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>',
    'lock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'sparkles' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>',
    'heart' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>',
    'zap' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    'party' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5.8 11.3 2 22l10.7-3.79"/><path d="M4 3h.01"/><path d="M22 8h.01"/><path d="M15 2h.01"/><path d="M22 20h.01"/><path d="m22 2-2.24.75a2.9 2.9 0 0 0-1.96 3.12v0c.1.86-.57 1.63-1.45 1.63h-.38c-.86 0-1.6.6-1.76 1.44L14 10"/><path d="m22 13-.82-.33c-.86-.34-1.82.2-1.98 1.11c-.11.7-.72 1.22-1.43 1.22H17"/><path d="m11 2 .33.82c.34.86-.2 1.82-1.11 1.98C9.52 4.9 9 5.52 9 6.23V7"/><path d="M11 13c1.93 1.93 2.83 4.17 2 5-.83.83-3.07-.07-5-2-1.93-1.93-2.83-4.17-2-5 .83-.83 3.07.07 5 2Z"/></svg>',
    'alert_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>',
    'loader' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:trSpin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
    'arrow_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>',
    'moon' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>',
    'sun' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>',
    'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'users' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'bar_chart' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/></svg>',
    'trending_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
    'help_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
    'arrow_right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
    'newspaper' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>',
    'link_2' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/></svg>',
    'info' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
    'hand_waving' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 19h4a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-1a2 2 0 0 0-2 2"/><path d="M18 11h.5a1.5 1.5 0 0 0 0-3h-.5"/><path d="M6 19h.5a1.5 1.5 0 0 0 0-3H6"/><path d="M7 13.5V11a2 2 0 0 0-4 0v2.5"/><path d="M17 13.5V6a2 2 0 0 0-4 0v9"/></svg>',
    'chevron_down' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $nim = trim($_POST['nim'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $id_prodi = (int)($_POST['id_prodi'] ?? 0);
    $tahun_lulus = trim($_POST['tahun_lulus'] ?? '');
    $no_wa = trim($_POST['no_wa'] ?? '');
    
    $chk = $db->prepare("SELECT id_alumni FROM tracer_alumni WHERE nim = ?");
    $chk->execute([$nim]);
    
    if (!$nim || !$nama || !$id_prodi || !$tahun_lulus || !$no_wa) {
        $error = 'Mohon lengkapi semua kolom wajib (*) dengan benar.';
    } elseif ($chk->fetch()) {
        $error = 'NIM ini sudah pernah mengisi tracer study. Terima kasih atas partisipasi Anda sebelumnya!';
    } else {
        try {
            $db->prepare("INSERT INTO tracer_alumni (nama, nim, id_prodi, tahun_lulus, email, no_wa, status_kerja, nama_instansi, jabatan, masa_tunggu_bulan, kesesuaian_bidang, kisaran_gaji, testimoni, siap_wawancara) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([
                   $nama, $nim, $id_prodi, $tahun_lulus,
                   trim($_POST['email'] ?? ''), $no_wa, trim($_POST['status_kerja'] ?? ''),
                   trim($_POST['nama_instansi'] ?? ''), trim($_POST['jabatan'] ?? ''), (int)($_POST['masa_tunggu_bulan'] ?? 0),
                   trim($_POST['kesesuaian_bidang'] ?? ''), trim($_POST['kisaran_gaji'] ?? ''), trim($_POST['testimoni'] ?? ''),
                   isset($_POST['siap_wawancara']) ? 1 : 0
               ]);
            if (isset($_POST['siap_wawancara'])) {
                $db->prepare("INSERT INTO responden_akreditasi (tipe, nama, identitas, id_prodi, no_wa, email, ketersediaan, catatan) VALUES ('Alumni',?,?,?,?,?,'Siap',?)")
                   ->execute([$nama, $nim, $id_prodi, $no_wa, trim($_POST['email'] ?? ''), 'Otomatis dari Tracer Study ' . $tahun_lulus]);
            }
            Notifier::sendRole(1, '🎓 Tracer Study Masuk', $nama . ' (' . $nim . ') baru saja mengisi tracer study.', '/sim/admin/tracer.php', '🎓');
            $success = true;
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
        }
    }
}
require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
@keyframes trSpin { to { transform: rotate(360deg); } }

/* ===== SCROLL PROGRESS ===== */
.tr-progress { position: fixed; top: 0; left: 0; width: 100%; height: 4px; z-index: 1100; background: rgba(15,61,92,.06); }
.tr-progress-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #C9A227, #E8C55A); box-shadow: 0 0 12px rgba(201,162,39,.5); transition: width .1s linear; }

/* ===== DARK MODE ===== */
.tr-dark-toggle {
    position: fixed; bottom: 24px; left: 24px; z-index: 1000;
    width: 52px; height: 52px; border-radius: 50%; border: none; cursor: pointer;
    background: #fff; color: #0F3D5C;
    display: grid; place-items: center; font-size: 22px;
    box-shadow: 0 8px 24px rgba(15,61,92,.15);
    transition: .3s;
}
.tr-dark-toggle:hover { transform: scale(1.08) rotate(15deg); }
body.tr-dark { background: #0F172A; color: #E2E8F0; }
body.tr-dark .tr-body { background: linear-gradient(180deg, #0F172A, #1E293B); }
body.tr-dark .tr-card { background: #1E293B; border-color: #334155; }
body.tr-dark .tr-form-body { background: #1E293B; }
body.tr-dark .tr-sec-title h4,
body.tr-dark .tr-state h3,
body.tr-dark .tr-field input,
body.tr-dark .tr-field select,
body.tr-dark .tr-field textarea,
body.tr-dark .tr-toggle-text h5 { color: #E2E8F0; }
body.tr-dark .tr-sec-title small,
body.tr-dark .tr-state p,
body.tr-dark .tr-field label,
body.tr-dark .tr-toggle-text p,
body.tr-dark .tr-secure,
body.tr-dark .tr-char-count { color: #94A3B8; }
body.tr-dark .tr-field input,
body.tr-dark .tr-field select,
body.tr-dark .tr-field textarea { background: #0F172A; border-color: #334155; color: #E2E8F0; }
body.tr-dark .tr-field input:focus ~ label,
body.tr-dark .tr-field input:not(:placeholder-shown) ~ label,
body.tr-dark .tr-field select:focus ~ label,
body.tr-dark .tr-field.has-value label,
body.tr-dark .tr-field textarea:focus ~ label,
body.tr-dark .tr-field textarea:not(:placeholder-shown) ~ label { background: #1E293B; }
body.tr-dark .tr-field label { background: #1E293B; }
body.tr-dark .tr-toggle { background: linear-gradient(135deg, rgba(201,162,39,.1), rgba(15,61,92,.15)); border-color: rgba(201,162,39,.3); }
body.tr-dark .tr-toggle.active { background: linear-gradient(135deg, rgba(16,185,129,.12), rgba(16,185,129,.05)); border-color: rgba(16,185,129,.4); }
body.tr-dark .tr-alert.danger { background: rgba(239,68,68,.15); color: #FCA5A5; }
body.tr-dark .tr-stats-card { background: #1E293B; border-color: #334155; }
body.tr-dark .tr-faq-item { background: #0F172A; border-color: #334155; }
body.tr-dark .tr-faq-item summary { color: #E2E8F0; }
body.tr-dark .tr-link-item { background: #0F172A; color: #E2E8F0; border-color: #334155; }

/* ===== HERO ===== */
.tr-hero {
    min-height: 60vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 120px 0 60px;
    display: flex; align-items: center;
}
.tr-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: trShift 16s ease-in-out infinite;
}
@keyframes trShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.tr-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.tr-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; padding: 0 24px; }

.tr-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.tr-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: trShimmer 3.5s ease-in-out infinite;
}
@keyframes trShimmer { to { left: 200%; } }
.tr-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: trPulse 2s ease infinite;
}
@keyframes trPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.tr-hero h1 { font-size: clamp(34px, 5vw, 62px); font-weight: 800; line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em; }
.tr-hero h1 .tr-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: trTextShine 4s linear infinite; font-style: italic;
}
@keyframes trTextShine { to { background-position: 200% center; } }
.tr-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

.tr-trust { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
.tr-trust-item {
    padding: 7px 14px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.tr-trust-item span { color: #10B981; display: inline-flex; }

/* ===== STATS CARDS ===== */
.tr-stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px; max-width: 880px; margin: -40px auto 0; position: relative; z-index: 5;
    padding: 0 24px;
}
.tr-stats-card {
    background: #fff; border-radius: 18px; padding: 22px 20px;
    border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 14px;
    box-shadow: 0 10px 30px rgba(15,61,92,.1);
    transition: .3s;
}
.tr-stats-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(15,61,92,.15); }
.tr-stats-ic {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 22px; color: #fff;
}
.tr-stats-card:nth-child(1) .tr-stats-ic { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; }
.tr-stats-card:nth-child(2) .tr-stats-ic { background: linear-gradient(135deg, #10B981, #34D399); }
.tr-stats-card:nth-child(3) .tr-stats-ic { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
.tr-stats-num { font-size: 24px; font-weight: 900; color: #0F3D5C; line-height: 1; letter-spacing: -.02em; }
body.tr-dark .tr-stats-num { color: #E2E8F0; }
.tr-stats-lbl { font-size: 11px; color: #64748B; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; margin-top: 3px; }

/* ===== BODY ===== */
.tr-body { padding: 60px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }

.tr-card {
    max-width: 880px; margin: 40px auto 0;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid #E2E8F0; position: relative;
    box-shadow: 0 24px 70px rgba(15,61,92,.1);
}
.tr-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #C9A227, #0F3D5C, #C9A227);
    background-size: 200% 100%; animation: trGradLine 4s linear infinite;
}
@keyframes trGradLine { to { background-position: 200% 0; } }

.tr-head {
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 32px 36px;
    display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
    position: relative; overflow: hidden;
}
.tr-head::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.tr-head-ic {
    width: 68px; height: 68px; border-radius: 20px; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; display: grid; place-items: center;
    font-size: 28px;
    box-shadow: 0 10px 30px rgba(201,162,39,.4);
    position: relative; z-index: 1;
}
.tr-head-info { flex: 1; min-width: 220px; position: relative; z-index: 1; }
.tr-head-info small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: block; margin-bottom: 4px; }
.tr-head-info h3 { font-size: 22px; font-weight: 800; margin-bottom: 4px; letter-spacing: -.02em; }
.tr-head-info p { color: rgba(255,255,255,.85); font-size: 14px; display: flex; align-items: center; gap: 6px; }
.tr-head-badge {
    padding: 6px 16px; border-radius: 50px;
    background: rgba(16,185,129,.18); border: 1px solid rgba(16,185,129,.35);
    color: #6EE7B7; font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase;
    position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 5px;
}
.tr-head-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #6EE7B7; animation: trPulse 2s ease infinite; }

/* ===== STEP INDICATOR ===== */
.tr-steps {
    display: flex; gap: 8px; padding: 20px 36px 0;
    background: rgba(15,61,92,.02); border-bottom: 1px solid #E2E8F0;
}
body.tr-dark .tr-steps { background: rgba(0,0,0,.2); border-bottom-color: #334155; }
.tr-step-ind {
    flex: 1; display: flex; align-items: center; gap: 8px;
    padding: 10px 0; position: relative;
    transition: .3s;
}
.tr-step-ind::after {
    content: ''; flex: 1; height: 2px; background: #E2E8F0;
    margin: 0 6px; border-radius: 2px; transition: background .4s;
}
body.tr-dark .tr-step-ind::after { background: #334155; }
.tr-step-ind:last-child::after { display: none; }
.tr-step-ind.done::after { background: linear-gradient(90deg, #10B981, #34D399); }
.tr-step-ind.active::after { background: linear-gradient(90deg, #C9A227, #E8C55A); }
.tr-step-num {
    width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
    background: #E2E8F0; color: #64748B; font-size: 12px; font-weight: 800;
    display: grid; place-items: center; transition: .3s;
}
body.tr-dark .tr-step-num { background: #334155; color: #94A3B8; }
.tr-step-ind.done .tr-step-num { background: linear-gradient(135deg, #10B981, #34D399); color: #fff; }
.tr-step-ind.active .tr-step-num { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; transform: scale(1.15); box-shadow: 0 4px 14px rgba(201,162,39,.4); }
.tr-step-lbl { font-size: 11.5px; font-weight: 700; color: #64748B; letter-spacing: .4px; display: none; }
body.tr-dark .tr-step-lbl { color: #94A3B8; }
.tr-step-ind.active .tr-step-lbl { color: #0F3D5C; }
body.tr-dark .tr-step-ind.active .tr-step-lbl { color: #E8C55A; }
@media (min-width: 700px) { .tr-step-lbl { display: block; } }
@media (max-width: 640px) { .tr-steps { padding: 16px 20px 0; } }

.tr-form-body { padding: 36px; }

.tr-sec-title {
    display: flex; align-items: center; gap: 14px;
    margin: 32px 0 20px; padding-bottom: 16px;
    border-bottom: 2px dashed #E2E8F0;
    scroll-margin-top: 80px;
}
body.tr-dark .tr-sec-title { border-bottom-color: #334155; }
.tr-sec-title:first-child { margin-top: 0; }
.tr-sec-ic {
    width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    color: #fff; display: grid; place-items: center; font-size: 18px;
    box-shadow: 0 8px 20px rgba(15,61,92,.2);
}
.tr-sec-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; font-weight: 900; font-size: 13px;
    display: grid; place-items: center; margin-left: auto;
    box-shadow: 0 4px 12px rgba(201,162,39,.4);
}
.tr-sec-title h4 { font-size: 17px; color: #0F3D5C; margin: 0; letter-spacing: -.01em; }
.tr-sec-title small { color: #64748B; font-size: 12px; display: block; margin-top: 2px; }

.tr-field { position: relative; margin-bottom: 16px; }
.tr-field input, .tr-field select, .tr-field textarea {
    width: 100%; padding: 22px 18px 10px;
    border: 2px solid #E2E8F0; border-radius: 14px;
    font-size: 14px; font-family: inherit; outline: none;
    background: #fff; color: #1E293B; transition: .3s;
}
.tr-field textarea { resize: vertical; min-height: 100px; padding-top: 26px; }
.tr-field select { cursor: pointer; appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath fill='%230F3D5C' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 16px center; padding-right: 40px;
}
.tr-field input:focus, .tr-field select:focus, .tr-field textarea:focus {
    border-color: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.15);
}
.tr-field input.valid, .tr-field select.valid, .tr-field textarea.valid { border-color: #10B981; }
.tr-field input.error, .tr-field select.error, .tr-field textarea.error {
    border-color: #EF4444; box-shadow: 0 0 0 4px rgba(239,68,68,.15);
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
}
.tr-field.shake { animation: shake 0.4s ease-in-out; }

.tr-field label {
    position: absolute; left: 18px; top: 18px;
    font-size: 13px; color: #64748B;
    pointer-events: none; transition: .25s cubic-bezier(.16,1,.3,1);
    background: #fff; padding: 0 6px; font-weight: 600;
}
.tr-field input:focus ~ label,
.tr-field input:not(:placeholder-shown) ~ label,
.tr-field select:focus ~ label,
.tr-field.has-value label,
.tr-field textarea:focus ~ label,
.tr-field textarea:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 10.5px; color: #C9A227;
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
}
.tr-field-ic {
    position: absolute; right: 16px; top: 20px; font-size: 16px;
    color: #64748B; pointer-events: none; display: inline-flex;
}
.tr-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .tr-row { grid-template-columns: 1fr; } }

.tr-conditional {
    max-height: 2000px; overflow: hidden; opacity: 1;
    transition: max-height .5s cubic-bezier(.16,1,.3,1), opacity .3s ease;
}
.tr-conditional.hidden { max-height: 0; opacity: 0; pointer-events: none; margin: 0; }

.tr-toggle {
    padding: 20px; border-radius: 16px; margin: 24px 0;
    background: linear-gradient(135deg, rgba(201,162,39,.06), rgba(15,61,92,.03));
    border: 1px solid rgba(201,162,39,.25);
    display: flex; align-items: center; gap: 16px;
    transition: .3s; cursor: pointer;
}
.tr-toggle.active {
    background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(16,185,129,.03));
    border-color: rgba(16,185,129,.4);
}
.tr-toggle-ic {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    color: #fff; display: grid; place-items: center; font-size: 20px;
    box-shadow: 0 6px 18px rgba(15,61,92,.2);
    transition: .3s;
}
.tr-toggle.active .tr-toggle-ic {
    background: linear-gradient(135deg, #10B981, #34D399);
    box-shadow: 0 6px 18px rgba(16,185,129,.35);
}
.tr-toggle-text { flex: 1; }
.tr-toggle-text h5 { font-size: 14px; color: #0F3D5C; margin: 0 0 2px; letter-spacing: -.01em; }
.tr-toggle-text p { font-size: 12.5px; color: #64748B; margin: 0; line-height: 1.5; }

.tr-switch {
    position: relative; width: 54px; height: 30px; flex-shrink: 0;
}
.tr-switch input { opacity: 0; width: 0; height: 0; }
.tr-slider {
    position: absolute; cursor: pointer; inset: 0;
    background: #CBD5E1; border-radius: 30px;
    transition: .3s;
}
.tr-slider::before {
    content: ''; position: absolute;
    height: 22px; width: 22px; left: 4px; top: 4px;
    background: #fff; border-radius: 50%;
    transition: .3s cubic-bezier(.34,1.56,.64,1);
    box-shadow: 0 2px 6px rgba(0,0,0,.2);
}
.tr-switch input:checked + .tr-slider {
    background: linear-gradient(135deg, #10B981, #34D399);
}
.tr-switch input:checked + .tr-slider::before {
    transform: translateX(24px);
}

.tr-char-count {
    position: absolute; right: 16px; bottom: 12px;
    font-size: 11px; color: #64748B; font-weight: 600;
    background: rgba(255,255,255,.85); padding: 2px 6px; border-radius: 4px;
}

.tr-submit {
    margin-top: 28px; width: 100%; padding: 18px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; box-shadow: 0 12px 36px rgba(201,162,39,.45);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.tr-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.tr-submit:hover::before { left: 150%; }
.tr-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(201,162,39,.65); }
.tr-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }
.tr-kbd {
    font-size: 10.5px; padding: 3px 8px; border-radius: 5px;
    background: rgba(15,61,92,.12); color: #0F3D5C; font-weight: 800;
    letter-spacing: .4px; margin-left: auto;
}

.tr-secure {
    display: flex; align-items: center; gap: 10px; margin-top: 16px;
    font-size: 12px; color: #64748B;
}
.tr-secure-ic {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(16,185,129,.1); color: #10B981;
    display: grid; place-items: center; font-size: 12px;
}

.tr-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600;
    display: flex; gap: 12px; align-items: center;
    animation: trAlertIn .45s cubic-bezier(.16,1,.3,1);
}
@keyframes trAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.tr-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444; }

.tr-state { text-align: center; padding: 70px 40px; position: relative; overflow: hidden; }
.tr-state-ic {
    width: 110px; height: 110px; border-radius: 50%; margin: 0 auto 24px;
    display: grid; place-items: center; font-size: 52px;
    background: linear-gradient(135deg, #10B981, #34D399); color: #fff;
    box-shadow: 0 20px 55px rgba(16,185,129,.4);
    animation: trSuccessPop .7s cubic-bezier(.34,1.56,.64,1);
    position: relative; z-index: 1;
}
@keyframes trSuccessPop {
    0% { transform: scale(0) rotate(-180deg); }
    60% { transform: scale(1.15) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}
.tr-state h3 { font-size: 28px; color: #0F3D5C; margin-bottom: 12px; letter-spacing: -.02em; position: relative; z-index: 1; }
.tr-state p { color: #64748B; font-size: 16px; max-width: 480px; margin: 0 auto; line-height: 1.6; position: relative; z-index: 1; }
@keyframes trConfettiFall {
    0% { transform: translateY(-100px) rotate(0); opacity: 1; }
    100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
}

/* ===== FAQ ===== */
.tr-faq {
    max-width: 880px; margin: 40px auto 0; padding: 0 24px;
}
.tr-faq-head {
    display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
}
.tr-faq-head-ic {
    width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C;
    display: grid; place-items: center; font-size: 18px;
}
.tr-faq-head h3 { font-size: 20px; color: #0F3D5C; margin: 0; letter-spacing: -.02em; }
body.tr-dark .tr-faq-head h3 { color: #E2E8F0; }
.tr-faq-list { display: flex; flex-direction: column; gap: 10px; }
.tr-faq-item {
    background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
    overflow: hidden; transition: .3s;
}
.tr-faq-item[open] { border-color: rgba(201,162,39,.4); box-shadow: 0 6px 18px rgba(201,162,39,.1); }
.tr-faq-item summary {
    padding: 16px 20px; cursor: pointer; font-weight: 700; font-size: 14px;
    color: #1E293B; list-style: none;
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    transition: .25s;
}
.tr-faq-item summary::-webkit-details-marker { display: none; }
.tr-faq-item summary:hover { color: #0F3D5C; }
.tr-faq-plus {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
    background: rgba(201,162,39,.12); color: #C9A227;
    display: grid; place-items: center; font-size: 16px; font-weight: 900;
    transition: .3s cubic-bezier(.34,1.56,.64,1);
}
.tr-faq-item[open] .tr-faq-plus {
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; transform: rotate(45deg);
}
.tr-faq-item p {
    padding: 0 20px 18px; font-size: 13.5px; color: #64748B; line-height: 1.65;
    animation: trFaqIn .3s cubic-bezier(.16,1,.3,1);
}
@keyframes trFaqIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }

/* ===== RELATED LINKS ===== */
.tr-links {
    max-width: 880px; margin: 30px auto 0; padding: 0 24px;
    display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px;
}
.tr-link-item {
    display: flex; align-items: center; gap: 12px; padding: 14px 16px;
    border-radius: 14px; background: #fff; text-decoration: none;
    color: #0F3D5C; font-size: 13.5px; font-weight: 600;
    transition: .25s; border: 1px solid #E2E8F0;
    box-shadow: 0 4px 14px rgba(15,61,92,.05);
}
.tr-link-item:hover { background: rgba(201,162,39,.08); border-color: rgba(201,162,39,.4); transform: translateX(4px); }
.tr-link-ic {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82); color: #fff;
    display: grid; place-items: center; font-size: 16px;
}
.tr-link-item span { flex: 1; }
.tr-link-arrow { color: #C9A227; display: inline-flex; flex-shrink: 0; }

/* ===== BACK TO TOP ===== */
.tr-back-top {
    position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px;
    border-radius: 50%; background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; display: grid; place-items: center;
    font-size: 22px; box-shadow: 0 10px 30px rgba(201,162,39,.4);
    cursor: pointer; border: none; z-index: 999;
    opacity: 0; pointer-events: none; transform: translateY(20px);
    transition: .4s cubic-bezier(.16,1,.3,1);
}
.tr-back-top.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.tr-back-top:hover { transform: translateY(-4px) scale(1.05); }

@media (max-width: 640px) {
    .tr-form-body { padding: 24px; }
    .tr-head { padding: 24px; }
}

@media print {
    .tr-hero, .tr-dark-toggle, .tr-back-top, .tr-progress, .tr-faq, .tr-links { display: none !important; }
    .tr-card { box-shadow: none; border: 1px solid #000; margin-top: 0; }
}
</style>

<div class="tr-progress"><div class="tr-progress-bar" id="trProg"></div></div>

<section class="tr-hero">
    <div class="tr-hero-aurora"></div>
    <div class="tr-hero-grid"></div>
    <div class="container">
        <div class="tr-hero-inner">
            <span class="tr-hero-badge">
                <span class="tr-hero-badge-dot"></span>
                Tracer Study Alumni
            </span>
            <h1>Jejak Langkah <span class="tr-grad">Alumni Kami</span></h1>
            <p class="tr-hero-lead">
                Kabar karier Anda adalah bahan berharga untuk peningkatan mutu kurikulum & akreditasi.
                Hanya ±3 menit saja untuk melengkapi.
            </p>
            <div class="tr-trust">
                <div class="tr-trust-item"><span><?= $ic['zap'] ?></span> ±3 Menit</div>
                <div class="tr-trust-item"><span><?= $ic['lock'] ?></span> Rahasia</div>
                <div class="tr-trust-item"><span><?= $ic['graduation'] ?></span> Wajib Akreditasi</div>
                <div class="tr-trust-item"><span><?= $ic['heart'] ?></span> Untuk Almamater</div>
            </div>
        </div>
    </div>
</section>

<div class="tr-stats-row">
    <div class="tr-stats-card">
        <div class="tr-stats-ic"><?= $ic['users'] ?></div>
        <div>
            <div class="tr-stats-num" data-count="<?= $tracerStats['responden'] ?>"><?= number_format($tracerStats['responden']) ?></div>
            <div class="tr-stats-lbl">Alumni Terlacak</div>
        </div>
    </div>
    <div class="tr-stats-card">
        <div class="tr-stats-ic"><?= $ic['trending_up'] ?></div>
        <div>
            <div class="tr-stats-num"><?= $tracerStats['rata_tunggu'] ?> <span style="font-size:.5em;">bln</span></div>
            <div class="tr-stats-lbl">Rata² Masa Tunggu</div>
        </div>
    </div>
    <div class="tr-stats-card">
        <div class="tr-stats-ic"><?= $ic['bar_chart'] ?></div>
        <div>
            <div class="tr-stats-num">100<span style="font-size:.5em;">%</span></div>
            <div class="tr-stats-lbl">Untuk Akreditasi</div>
        </div>
    </div>
</div>

<section class="tr-body">
    <div class="container">
        <div class="tr-card">
            <?php if ($success): ?>
                <div class="tr-state" id="trSuccessState">
                    <div class="tr-state-ic"><?= $ic['check'] ?></div>
                    <h3>Terima Kasih, Alumni Hebat!</h3>
                    <p>Data Anda tercatat dan sangat berarti bagi almamater. Sampai jumpa di kegiatan alumni berikutnya! Sukses selalu untuk karier Anda.</p>
                    <a href="/publik/berita.php" class="btn btn-primary" style="margin-top:24px;padding:10px 22px;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#0F3D5C;border-radius:50px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:8px;box-shadow:0 8px 22px rgba(201,162,39,.4);">Baca Berita Terbaru <?= $ic['arrow_right'] ?></a>
                </div>
            <?php else: ?>
                <div class="tr-head">
                    <div class="tr-head-ic"><?= $ic['graduation'] ?></div>
                    <div class="tr-head-info">
                        <small>Formulir Tracer Study</small>
                        <h3>Jejak Karier Alumni</h3>
                        <p><?= $ic['lock'] ?> Data dijaga kerahasiaannya — hanya untuk penjaminan mutu & akreditasi.</p>
                    </div>
                    <span class="tr-head-badge">Aktif</span>
                </div>

                <div class="tr-steps" id="trSteps">
                    <div class="tr-step-ind active" data-step="1"><span class="tr-step-num">1</span><span class="tr-step-lbl">Data Diri</span></div>
                    <div class="tr-step-ind" data-step="2"><span class="tr-step-num">2</span><span class="tr-step-lbl">Status Karier</span></div>
                    <div class="tr-step-ind" data-step="3"><span class="tr-step-num">3</span><span class="tr-step-lbl">Testimoni</span></div>
                    <div class="tr-step-ind" data-step="4"><span class="tr-step-num">4</span><span class="tr-step-lbl">Wawancara</span></div>
                </div>

                <div class="tr-form-body">
                    <?php if ($error): ?><div class="tr-alert danger"><?= $ic['alert_circle'] ?> <?= Security::e($error) ?></div><?php endif; ?>

                    <form method="POST" id="trForm">
                        <?= Security::csrfField() ?>

                        <div class="tr-sec-title" data-section="1">
                            <div class="tr-sec-ic"><?= $ic['user'] ?></div>
                            <div><h4>Data Diri Alumni</h4><small>Identitas dasar Anda sebagai alumni</small></div>
                            <span class="tr-sec-num">1</span>
                        </div>

                        <div class="tr-row">
                            <div class="tr-field">
                                <input type="text" name="nama" id="trNama" placeholder=" " required autocomplete="name">
                                <label>Nama Lengkap *</label>
                                <span class="tr-field-ic"><?= $ic['user'] ?></span>
                            </div>
                            <div class="tr-field">
                                <input type="text" name="nim" id="trNim" placeholder=" " required>
                                <label>NIM *</label>
                                <span class="tr-field-ic"><?= $ic['id_card'] ?></span>
                            </div>
                        </div>

                        <div class="tr-row">
                            <div class="tr-field has-value">
                                <select name="id_prodi" id="trProdi" required>
                                    <option value="">— Pilih Prodi —</option>
                                    <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
                                </select>
                                <label>Program Studi *</label>
                            </div>
                            <div class="tr-field has-value">
                                <select name="tahun_lulus" id="trTahun" required>
                                    <option value="">— Pilih Tahun —</option>
                                    <?php for ($y = $tahunIni; $y >= $tahunIni - 10; $y--): ?><option value="<?= $y ?>"><?= $y ?></option><?php endfor; ?>
                                </select>
                                <label>Tahun Lulus *</label>
                            </div>
                        </div>

                        <div class="tr-row">
                            <div class="tr-field">
                                <input type="email" name="email" id="trEmail" placeholder=" " autocomplete="email">
                                <label>Email</label>
                                <span class="tr-field-ic"><?= $ic['mail'] ?></span>
                            </div>
                            <div class="tr-field">
                                <input type="text" name="no_wa" id="trWa" placeholder=" " required autocomplete="tel">
                                <label>No. WhatsApp *</label>
                                <span class="tr-field-ic"><?= $ic['phone'] ?></span>
                            </div>
                        </div>

                        <div class="tr-sec-title" data-section="2">
                            <div class="tr-sec-ic"><?= $ic['briefcase'] ?></div>
                            <div><h4>Status Karier Saat Ini</h4><small>Kondisi pekerjaan Anda setelah lulus</small></div>
                            <span class="tr-sec-num">2</span>
                        </div>

                        <div class="tr-field has-value">
                            <select name="status_kerja" id="trStatus" required>
                                <option value="">— Pilih Status —</option>
                                <option>Bekerja</option>
                                <option>Wirausaha</option>
                                <option>Melanjutkan Studi</option>
                                <option>Belum Bekerja</option>
                            </select>
                            <label>Status Saat Ini *</label>
                        </div>

                        <div class="tr-conditional" id="trWorkDetails">
                            <div class="tr-row">
                                <div class="tr-field">
                                    <input type="text" name="nama_instansi" id="trInstansi" placeholder=" ">
                                    <label>Nama Instansi / Usaha</label>
                                    <span class="tr-field-ic"><?= $ic['building'] ?></span>
                                </div>
                                <div class="tr-field">
                                    <input type="text" name="jabatan" id="trJabatan" placeholder=" ">
                                    <label>Jabatan / Posisi</label>
                                    <span class="tr-field-ic"><?= $ic['briefcase'] ?></span>
                                </div>
                            </div>

                            <div class="tr-row">
                                <div class="tr-field">
                                    <input type="number" name="masa_tunggu_bulan" id="trTunggu" value="0" min="0" placeholder=" ">
                                    <label>Masa Tunggu Kerja (bulan)</label>
                                    <span class="tr-field-ic"><?= $ic['clock'] ?></span>
                                </div>
                                <div class="tr-field has-value">
                                    <select name="kesesuaian_bidang" id="trSesuai">
                                        <option value="">— Pilih Kesesuaian —</option>
                                        <option>Sangat Sesuai</option>
                                        <option>Sesuai</option>
                                        <option>Kurang Sesuai</option>
                                        <option>Tidak Sesuai</option>
                                    </select>
                                    <label>Kesesuaian dengan Bidang Studi</label>
                                </div>
                            </div>

                            <div class="tr-field has-value">
                                <select name="kisaran_gaji" id="trGaji">
                                    <option value="">— Pilih Kisaran —</option>
                                    <option>&lt; 3 juta</option>
                                    <option>3–5 juta</option>
                                    <option>5–8 juta</option>
                                    <option>&gt; 8 juta</option>
                                </select>
                                <label>Kisaran Gaji</label>
                            </div>
                        </div>

                        <div class="tr-sec-title" data-section="3">
                            <div class="tr-sec-ic"><?= $ic['message_square'] ?></div>
                            <div><h4>Testimoni untuk Kampus</h4><small>Cerita & saran untuk almamater</small></div>
                            <span class="tr-sec-num">3</span>
                        </div>

                        <div class="tr-field">
                            <textarea name="testimoni" id="trTestimoni" placeholder=" " maxlength="500"></textarea>
                            <label>Ceritakan pengalaman & saran Anda untuk kampus...</label>
                            <span class="tr-char-count" id="trCharCount">0/500</span>
                        </div>

                        <div class="tr-sec-title" data-section="4">
                            <div class="tr-sec-ic"><?= $ic['mic'] ?></div>
                            <div><h4>Kesediaan Wawancara</h4><small>Opsional — dukungan untuk akreditasi</small></div>
                            <span class="tr-sec-num">4</span>
                        </div>

                        <label class="tr-toggle" id="trToggleWrap">
                            <div class="tr-toggle-ic"><?= $ic['hand_waving'] ?></div>
                            <div class="tr-toggle-text">
                                <h5>Bersedia Diwawancara Asesor Akreditasi</h5>
                                <p>Jika dicentang, data Anda akan otomatis didaftarkan sebagai calon responden wawancara akreditasi.</p>
                            </div>
                            <div class="tr-switch">
                                <input type="checkbox" name="siap_wawancara" value="1" id="trWawancara">
                                <span class="tr-slider"></span>
                            </div>
                        </label>

                        <button type="submit" class="tr-submit" id="trSubmit">
                            <?= $ic['send'] ?> Kirim Tracer Study Sekarang
                            <span class="tr-kbd">Ctrl + ↵</span>
                        </button>

                        <div class="tr-secure">
                            <span class="tr-secure-ic"><?= $ic['shield'] ?></span>
                            <span>Data Anda terenkripsi dan hanya digunakan untuk penjaminan mutu serta akreditasi kampus.</span>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- FAQ -->
        <div class="tr-faq">
            <div class="tr-faq-head">
                <div class="tr-faq-head-ic"><?= $ic['help_circle'] ?></div>
                <h3>Pertanyaan Umum</h3>
            </div>
            <div class="tr-faq-list">
                <details class="tr-faq-item">
                    <summary>Apa tujuan tracer study ini?<span class="tr-faq-plus">+</span></summary>
                    <p>Tracer study bertujuan untuk melacak jejak karier alumni, mengukur masa tunggu kerja, kesesuaian bidang, serta mengumpulkan umpan balik untuk peningkatan kurikulum dan pemenuhan standar akreditasi BAN-PT/LAM.</p>
                </details>
                <details class="tr-faq-item">
                    <summary>Apakah data saya akan dipublikasikan?<span class="tr-faq-plus">+</span></summary>
                    <p>Tidak. Data pribadi Anda dijaga kerahasiaannya dan hanya digunakan dalam bentuk agregat untuk kepentingan akreditasi serta penjaminan mutu internal. Identitas tidak akan dipublikasikan tanpa persetujuan.</p>
                </details>
                <details class="tr-faq-item">
                    <summary>Siapa yang wajib mengisi tracer study?<span class="tr-faq-plus">+</span></summary>
                    <p>Seluruh alumni yang telah lulus dalam 10 tahun terakhir sangat diharapkan untuk berpartisipasi. Partisipasi Anda berdampak langsung pada kualitas akreditasi almamater.</p>
                </details>
                <details class="tr-faq-item">
                    <summary>Bagaimana jika saya sudah mengisi sebelumnya?<span class="tr-faq-plus">+</span></summary>
                    <p>Sistem akan otomatis mendeteksi NIM yang sudah pernah mengisi dan mencegah duplikasi. Jika Anda ingin memperbarui data, silakan hubungi admin LPM secara langsung.</p>
                </details>
            </div>
        </div>

        <!-- Related Links -->
        <div class="tr-links">
            <a href="/publik/akreditasi.php" class="tr-link-item">
                <span class="tr-link-ic"><?= $ic['award'] ?></span>
                <span>Status Akreditasi</span>
                <span class="tr-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
            <a href="/publik/berita.php" class="tr-link-item">
                <span class="tr-link-ic"><?= $ic['newspaper'] ?></span>
                <span>Berita Kampus</span>
                <span class="tr-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
            <a href="/publik/profil.php" class="tr-link-item">
                <span class="tr-link-ic"><?= $ic['info'] ?></span>
                <span>Tentang LPM</span>
                <span class="tr-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
        </div>
    </div>
</section>

<button class="tr-dark-toggle" id="trDarkToggle" aria-label="Toggle dark mode"><?= $ic['moon'] ?></button>
<button class="tr-back-top" id="trBackTop" aria-label="Kembali ke atas"><?= $ic['arrow_up'] ?></button>

<script>
(function () {
    'use strict';

    var ICON = {
        check: <?= json_encode($ic['check']) ?>,
        loader: <?= json_encode($ic['loader']) ?>,
        send: <?= json_encode($ic['send']) ?>,
        moon: <?= json_encode($ic['moon']) ?>,
        sun: <?= json_encode($ic['sun']) ?>,
        alert: <?= json_encode($ic['alert_circle']) ?>
    };

    /* ===== REVEAL ===== */
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.tr-reveal').forEach(function (el) { io.observe(el); });

    /* ===== SCROLL PROGRESS & BACK TO TOP ===== */
    var prog = document.getElementById('trProg');
    var backTop = document.getElementById('trBackTop');
    window.addEventListener('scroll', function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        var p = max > 0 ? (window.scrollY / max) * 100 : 0;
        if (prog) prog.style.width = p + '%';
        if (backTop) backTop.classList.toggle('visible', window.scrollY > 500);
    }, { passive: true });
    if (backTop) backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ===== DARK MODE ===== */
    var darkBtn = document.getElementById('trDarkToggle');
    var savedDark = localStorage.getItem('trDark') === '1';
    if (savedDark) {
        document.body.classList.add('tr-dark');
        if (darkBtn) darkBtn.innerHTML = ICON.sun;
    }
    if (darkBtn) darkBtn.addEventListener('click', function () {
        var on = document.body.classList.toggle('tr-dark');
        localStorage.setItem('trDark', on ? '1' : '0');
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
    var successState = document.getElementById('trSuccessState');
    if (successState) {
        var colors = ['#C9A227', '#E8C55A', '#0F3D5C', '#10B981', '#F59E0B'];
        for (var i = 0; i < 60; i++) {
            var conf = document.createElement('div');
            conf.style.cssText = 'position:absolute;width:10px;height:10px;opacity:0;animation:trConfettiFall 3s ease-out forwards;pointer-events:none;';
            conf.style.left = Math.random() * 100 + '%';
            conf.style.top = '-20px';
            conf.style.background = colors[Math.floor(Math.random() * colors.length)];
            conf.style.borderRadius = Math.random() > .5 ? '50%' : '2px';
            conf.style.animationDelay = (Math.random() * 1.5) + 's';
            conf.style.animationDuration = (2 + Math.random() * 2) + 's';
            successState.appendChild(conf);
        }
    }

    var form = document.getElementById('trForm');
    if (!form) return;

    var STORAGE_KEY = 'tracer_form_draft_v2';

    // 1. Restore Draft
    try {
        var draft = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        ['nama', 'nim', 'email', 'no_wa', 'nama_instansi', 'jabatan', 'masa_tunggu_bulan', 'testimoni'].forEach(function (name) {
            var el = document.getElementById('tr' + name.charAt(0).toUpperCase() + name.slice(1));
            if (el && draft[name]) el.value = draft[name];
        });
        ['id_prodi', 'tahun_lulus', 'status_kerja', 'kesesuaian_bidang', 'kisaran_gaji'].forEach(function (name) {
            var el = document.getElementById('tr' + name.charAt(0).toUpperCase() + name.slice(1));
            if (el && draft[name]) el.value = draft[name];
        });
        if (draft.siap_wawancara) {
            document.getElementById('trWawancara').checked = true;
            document.getElementById('trToggleWrap').classList.add('active');
        }
        var statusEl = document.getElementById('trStatus');
        if (statusEl && statusEl.value) {
            statusEl.dispatchEvent(new Event('change'));
        }
    } catch (e) {}

    // 2. Auto-Save & Real-time Validation
    form.addEventListener('input', function (e) {
        var draft = {};
        ['nama', 'nim', 'email', 'no_wa', 'nama_instansi', 'jabatan', 'masa_tunggu_bulan', 'testimoni'].forEach(function (name) {
            var el = document.getElementById('tr' + name.charAt(0).toUpperCase() + name.slice(1));
            if (el) draft[name] = el.value;
        });
        ['id_prodi', 'tahun_lulus', 'status_kerja', 'kesesuaian_bidang', 'kisaran_gaji'].forEach(function (name) {
            var el = document.getElementById('tr' + name.charAt(0).toUpperCase() + name.slice(1));
            if (el) draft[name] = el.value;
        });
        draft.siap_wawancara = document.getElementById('trWawancara').checked;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));

        if (e.target.name === 'testimoni') {
            var len = e.target.value.length;
            var counter = document.getElementById('trCharCount');
            if (counter) {
                counter.textContent = len + '/500';
                if (len > 450) counter.style.color = '#F59E0B';
                else if (len < 20 && len > 0) counter.style.color = '#EF4444';
                else counter.style.color = '#64748B';
            }
        }

        if (e.target.name === 'email') {
            var emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.target.value);
            if (e.target.value.length > 0) {
                e.target.classList.toggle('valid', emailValid);
                e.target.classList.toggle('error', !emailValid);
            } else {
                e.target.classList.remove('valid', 'error');
            }
        }

        if (e.target.name === 'no_wa') {
            var waValid = /^[0-9+\-\s]{10,15}$/.test(e.target.value);
            if (e.target.value.length > 0) {
                e.target.classList.toggle('valid', waValid);
                e.target.classList.toggle('error', !waValid);
            } else {
                e.target.classList.remove('valid', 'error');
            }
        }
    });

    form.addEventListener('change', function () {
        var draft = {};
        ['id_prodi', 'tahun_lulus', 'status_kerja', 'kesesuaian_bidang', 'kisaran_gaji'].forEach(function (name) {
            var el = document.getElementById('tr' + name.charAt(0).toUpperCase() + name.slice(1));
            if (el) draft[name] = el.value;
        });
        draft.siap_wawancara = document.getElementById('trWawancara').checked;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
    });

    // Conditional logic
    var status = document.getElementById('trStatus');
    var workDetails = document.getElementById('trWorkDetails');
    if (status && workDetails) {
        function toggleWork() {
            if (status.value === 'Belum Bekerja' || status.value === 'Melanjutkan Studi') {
                workDetails.classList.add('hidden');
            } else {
                workDetails.classList.remove('hidden');
            }
        }
        status.addEventListener('change', toggleWork);
        toggleWork();
    }

    // Toggle visual feedback
    var toggle = document.getElementById('trWawancara');
    var toggleWrap = document.getElementById('trToggleWrap');
    if (toggle && toggleWrap) {
        toggle.addEventListener('change', function () {
            toggleWrap.classList.toggle('active', toggle.checked);
        });
    }

    // ===== STEP INDICATOR (scroll-based) =====
    var steps = document.querySelectorAll('.tr-step-ind');
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

        form.querySelectorAll('[required]').forEach(function (el) {
            if (el.closest('.tr-conditional.hidden')) return;
            if (!el.value.trim()) {
                isValid = false;
                el.classList.add('error');
                el.closest('.tr-field').classList.add('shake');
                if (!firstInvalid) firstInvalid = el;
            } else {
                el.classList.remove('error');
                el.closest('.tr-field').classList.remove('shake');
            }
        });

        var wa = document.getElementById('trWa');
        if (wa && wa.value && !/^[0-9+\-\s]{10,15}$/.test(wa.value)) {
            isValid = false;
            wa.classList.add('error');
            wa.closest('.tr-field').classList.add('shake');
            if (!firstInvalid) firstInvalid = wa;
        }

        var emailEl = form.querySelector('[name="email"]');
        if (emailEl && emailEl.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value)) {
            isValid = false;
            emailEl.classList.add('error');
            if (!firstInvalid) firstInvalid = emailEl;
        }

        if (!isValid) {
            e.preventDefault();
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
            var btn = document.getElementById('trSubmit');
            var originalText = btn.innerHTML;
            btn.innerHTML = ICON.alert + ' Mohon lengkapi data wajib dengan benar';
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
        
        var btn = document.getElementById('trSubmit');
        if (btn && !btn.dataset.busy) {
            btn.dataset.busy = '1';
            btn.disabled = true;
            btn.innerHTML = ICON.loader + ' Mengirim...';
        }
    });

    // Keyboard Shortcut: Ctrl/Cmd + Enter
    form.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>