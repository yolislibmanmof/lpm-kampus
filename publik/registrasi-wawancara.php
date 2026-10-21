<?php
$pageTitle = 'Responden Wawancara Akreditasi';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();
$db = Database::getInstance();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$success = false; $error = '';

// Statistik responden per tipe
$rwStats = ['Alumni' => 0, 'Mahasiswa' => 0, 'Dosen' => 0, 'Tendik' => 0, 'total' => 0];
try {
    $rows = $db->query("SELECT tipe, COUNT(*) c FROM responden_akreditasi GROUP BY tipe")->fetchAll();
    foreach ($rows as $r) {
        if (isset($rwStats[$r['tipe']])) $rwStats[$r['tipe']] = (int)$r['c'];
        $rwStats['total'] += (int)$r['c'];
    }
} catch (Throwable $e) {}

// ===== KUMPULAN ICON SVG (GAYA MODERN & PROFESSIONAL) =====
$ic = [
    'graduation' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
    'book_open' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    'user_tie' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M17 14l-5 8-5-8a7 7 0 0 1 10 0z"/><path d="M12 13v4"/></svg>',
    'briefcase' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    'user' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'id_card' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>',
    'phone' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    'mail' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    'mic' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>',
    'check' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    'check_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
    'lock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'party' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5.8 11.3 2 22l10.7-3.79"/><path d="M4 3h.01"/><path d="M22 8h.01"/><path d="M15 2h.01"/><path d="M22 20h.01"/><path d="m22 2-2.24.75a2.9 2.9 0 0 0-1.96 3.12v0c.1.86-.57 1.63-1.45 1.63h-.38c-.86 0-1.6.6-1.76 1.44L14 10"/><path d="m22 13-.82-.33c-.86-.34-1.82.2-1.98 1.11c-.11.7-.72 1.22-1.43 1.22H17"/><path d="m11 2 .33.82c.34.86-.2 1.82-1.11 1.98C9.52 4.9 9 5.52 9 6.23V7"/><path d="M11 13c1.93 1.93 2.83 4.17 2 5-.83.83-3.07-.07-5-2-1.93-1.93-2.83-4.17-2-5 .83-.83 3.07.07 5 2Z"/></svg>',
    'alert_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>',
    'loader' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:rwSpin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
    'arrow_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>',
    'arrow_right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
    'moon' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>',
    'sun' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>',
    'send' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
    'target' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    'clock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>',
    'calendar_days' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>',
    'help_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
    'chevron_down' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
    'info' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
    'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'users' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'newspaper' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>',
    'list_checks' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/></svg>',
    'clipboard_list' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>',
    'save' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $nama = trim($_POST['nama'] ?? '');
    $tipe = $_POST['tipe'] ?? '';
    $no_wa = trim($_POST['no_wa'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (!$nama) { $error = 'Nama wajib diisi.'; }
    elseif (!in_array($tipe, ['Alumni', 'Mahasiswa', 'Dosen', 'Tendik'])) { $error = 'Tipe responden tidak valid.'; }
    elseif (!preg_match('/^[0-9+\-\s]{10,15}$/', $no_wa)) { $error = 'Nomor WhatsApp tidak valid (10-15 digit).'; }
    elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Format email tidak valid.'; }
    else {
        try {
            $db->prepare("INSERT INTO responden_akreditasi (tipe, nama, identitas, id_prodi, unit_kerja, no_wa, email, ketersediaan, catatan) VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([
                   $tipe, $nama, trim($_POST['identitas'] ?? ''),
                   $tipe === 'Tendik' ? null : (int)($_POST['id_prodi'] ?? 0),
                   $tipe === 'Tendik' ? trim($_POST['unit_kerja'] ?? '') : null,
                   $no_wa, $email, trim($_POST['ketersediaan'] ?? ''), trim($_POST['catatan'] ?? '')
               ]);
            Notifier::sendRole(1, '🎤 Responden Terdaftar', $nama . ' (' . $tipe . ') mendaftar sebagai responden wawancara.', '/sim/admin/responden.php', '🎤');
            $success = true;
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
@keyframes rwSpin { to { transform: rotate(360deg); } }

/* ===== SCROLL PROGRESS ===== */
.rw-progress-global { position: fixed; top: 0; left: 0; width: 100%; height: 4px; z-index: 1100; background: rgba(15,61,92,.06); }
.rw-progress-global-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #C9A227, #E8C55A); box-shadow: 0 0 12px rgba(201,162,39,.5); transition: width .1s linear; }

/* ===== DARK MODE ===== */
.rw-dark-toggle {
    position: fixed; bottom: 24px; left: 24px; z-index: 1000;
    width: 52px; height: 52px; border-radius: 50%; border: none; cursor: pointer;
    background: #fff; color: #0F3D5C;
    display: grid; place-items: center; font-size: 22px;
    box-shadow: 0 8px 24px rgba(15,61,92,.15);
    transition: .3s;
}
.rw-dark-toggle:hover { transform: scale(1.08) rotate(15deg); }
body.rw-dark { background: #0F172A; color: #E2E8F0; }
body.rw-dark .rw-body { background: linear-gradient(180deg, #0F172A, #1E293B); }
body.rw-dark .rw-card { background: #1E293B; border-color: #334155; }
body.rw-dark .rw-form-body { background: #1E293B; }
body.rw-dark .rw-field input,
body.rw-dark .rw-field select,
body.rw-dark .rw-field textarea { background: #0F172A; border-color: #334155; color: #E2E8F0; }
body.rw-dark .rw-field label { background: #1E293B; color: #94A3B8; }
body.rw-dark .rw-field input:focus ~ label,
body.rw-dark .rw-field input:not(:placeholder-shown) ~ label,
body.rw-dark .rw-field select:focus ~ label,
body.rw-dark .rw-field.has-value label,
body.rw-dark .rw-field textarea:focus ~ label,
body.rw-dark .rw-field textarea:not(:placeholder-shown) ~ label { background: #1E293B; }
body.rw-dark .rw-segmented { background: #0F172A; border-color: #334155; }
body.rw-dark .rw-seg { color: #94A3B8; }
body.rw-dark .rw-state h3,
body.rw-dark .rw-info h4,
body.rw-dark .rw-faq h4,
body.rw-dark .rw-state-steps h4,
body.rw-dark .rw-info-item h5,
body.rw-dark .rw-faq-q { color: #E2E8F0; }
body.rw-dark .rw-state p,
body.rw-dark .rw-step-text,
body.rw-dark .rw-info-item p,
body.rw-dark .rw-faq-a p,
body.rw-dark .rw-secure,
body.rw-dark .rw-autosave,
body.rw-dark .rw-progress-text,
body.rw-dark .rw-char-count { color: #94A3B8; }
body.rw-dark .rw-state-steps { background: #0F172A; border-color: #334155; }
body.rw-dark .rw-info { background: linear-gradient(135deg, rgba(201,162,39,.12), rgba(15,61,92,.25)); border-color: rgba(201,162,39,.3); }
body.rw-dark .rw-info-item { background: #0F172A; border-color: #334155; }
body.rw-dark .rw-faq-item { background: #1E293B; border-color: #334155; }
body.rw-dark .rw-faq-q:hover { background: #0F172A; }
body.rw-dark .rw-alert.danger { background: rgba(239,68,68,.15); color: #FCA5A5; }
body.rw-dark .rw-progress-bar { background: #334155; }
body.rw-dark .rw-stats-card { background: #1E293B; border-color: #334155; }
body.rw-dark .rw-stats-num { color: #E2E8F0; }
body.rw-dark .rw-link-item { background: #0F172A; color: #E2E8F0; border-color: #334155; }

/* ===== HERO ===== */
.rw-hero {
    min-height: 0; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 130px 0 84px;
    display: flex; align-items: center;
}
.rw-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: rwShift 16s ease-in-out infinite;
}
@keyframes rwShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.rw-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.rw-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; padding: 0 24px; }

.rw-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.rw-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: rwShimmer 3.5s ease-in-out infinite;
}
@keyframes rwShimmer { to { left: 200%; } }
.rw-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: rwPulse 2s ease infinite;
}
@keyframes rwPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.rw-hero h1 { font-size: clamp(34px, 5vw, 62px); font-weight: 800; line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em; color: #fff; }
.rw-hero h1 .rw-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: rwTextShine 4s linear infinite; font-style: italic;
}
@keyframes rwTextShine { to { background-position: 200% center; } }
.rw-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

.rw-roles { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 32px; }
.rw-role-pill {
    padding: 10px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.15);
    color: rgba(255,255,255,.9); font-size: 13px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 8px;
    transition: .3s;
}
.rw-role-pill:hover { transform: translateY(-2px); background: rgba(255,255,255,.12); }
.rw-role-pill span { display: inline-flex; color: #E8C55A; }

/* ===== STATS CARDS ===== */
.rw-stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px; max-width: 820px; margin: -46px auto 0; position: relative; z-index: 5;
    padding: 0 24px;
}
@media (min-width: 900px) { .rw-stats-row { grid-template-columns: repeat(5, 1fr); } }
.rw-stats-card {
    background: #fff; border-radius: 18px; padding: 20px;
    border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 12px;
    box-shadow: 0 10px 30px rgba(15,61,92,.1);
    transition: .3s;
}
.rw-stats-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(15,61,92,.15); }
.rw-stats-ic {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 20px; color: #fff;
}
.rw-stats-card:nth-child(1) .rw-stats-ic { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; }
.rw-stats-card:nth-child(2) .rw-stats-ic { background: linear-gradient(135deg, #10B981, #34D399); }
.rw-stats-card:nth-child(3) .rw-stats-ic { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
.rw-stats-card:nth-child(4) .rw-stats-ic { background: linear-gradient(135deg, #8B5CF6, #A78BFA); }
.rw-stats-card:nth-child(5) .rw-stats-ic { background: linear-gradient(135deg, #0F3D5C, #1A5A82); }
.rw-stats-num { font-size: 22px; font-weight: 900; color: #0F3D5C; line-height: 1; letter-spacing: -.02em; }
.rw-stats-lbl { font-size: 11px; color: #64748B; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; margin-top: 2px; }

/* ===== BODY ===== */
.rw-body { padding: 56px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }

.rw-card {
    max-width: 820px; margin: 0 auto;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid #E2E8F0; position: relative;
    box-shadow: 0 24px 70px rgba(15,61,92,.1);
}
.rw-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #C9A227, #0F3D5C, #C9A227);
    background-size: 200% 100%; animation: rwGradLine 4s linear infinite;
}
@keyframes rwGradLine { to { background-position: 200% 0; } }

.rw-head {
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 32px 36px; position: relative; overflow: hidden;
}
.rw-head::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.rw-head-inner { position: relative; z-index: 1; }
.rw-head small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
.rw-head h3 { font-size: 24px; font-weight: 800; margin-bottom: 6px; letter-spacing: -.02em; color: #fff; }
.rw-head p { color: rgba(255,255,255,.8); font-size: 14px; display: flex; align-items: center; gap: 6px; }

/* ===== STEP INDICATOR ===== */
.rw-steps-nav {
    display: flex; gap: 8px; padding: 20px 36px 0;
    background: rgba(15,61,92,.02); border-bottom: 1px solid #E2E8F0;
}
body.rw-dark .rw-steps-nav { background: rgba(0,0,0,.2); border-bottom-color: #334155; }
.rw-step-ind {
    flex: 1; display: flex; align-items: center; gap: 8px;
    padding: 10px 0; position: relative; transition: .3s;
}
.rw-step-ind::after {
    content: ''; flex: 1; height: 2px; background: #E2E8F0;
    margin: 0 6px; border-radius: 2px; transition: background .4s;
}
body.rw-dark .rw-step-ind::after { background: #334155; }
.rw-step-ind:last-child::after { display: none; }
.rw-step-ind.done::after { background: linear-gradient(90deg, #10B981, #34D399); }
.rw-step-ind.active::after { background: linear-gradient(90deg, #C9A227, #E8C55A); }
.rw-step-num-i {
    width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
    background: #E2E8F0; color: #64748B; font-size: 12px; font-weight: 800;
    display: grid; place-items: center; transition: .3s;
}
body.rw-dark .rw-step-num-i { background: #334155; color: #94A3B8; }
.rw-step-ind.done .rw-step-num-i { background: linear-gradient(135deg, #10B981, #34D399); color: #fff; }
.rw-step-ind.active .rw-step-num-i { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; transform: scale(1.15); box-shadow: 0 4px 14px rgba(201,162,39,.4); }
.rw-step-lbl-i { font-size: 11.5px; font-weight: 700; color: #64748B; letter-spacing: .4px; display: none; }
body.rw-dark .rw-step-lbl-i { color: #94A3B8; }
.rw-step-ind.active .rw-step-lbl-i { color: #0F3D5C; }
body.rw-dark .rw-step-ind.active .rw-step-lbl-i { color: #E8C55A; }
@media (min-width: 700px) { .rw-step-lbl-i { display: block; } }
@media (max-width: 640px) { .rw-steps-nav { padding: 16px 20px 0; } }

.rw-progress {
    padding: 14px 36px 0; margin: 0; position: relative; z-index: 2;
}
.rw-progress-text { margin-top: 6px; }
.rw-progress-bar {
    height: 6px; background: #E2E8F0; border-radius: 3px; overflow: hidden;
}
.rw-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, #10B981, #34D399);
    transition: width .4s ease;
}
.rw-progress-text {
    font-size: 11px; color: #64748B; margin-top: 8px; text-align: right; font-weight: 600;
}

.rw-form-body { padding: 36px; }

.rw-segmented {
    display: grid; grid-template-columns: repeat(4, 1fr);
    background: #F7F9FC; border: 1px solid #E2E8F0;
    border-radius: 14px; padding: 4px; margin-bottom: 24px;
    position: relative;
}
.rw-seg {
    padding: 14px; border: none; background: transparent; cursor: pointer;
    font-family: inherit; font-size: 13px; font-weight: 700;
    color: #64748B; border-radius: 10px;
    transition: .3s ease;
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    position: relative; z-index: 1;
}
.rw-seg .rw-seg-ic { font-size: 22px; display: inline-flex; }
.rw-seg.active {
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40;
    box-shadow: 0 6px 18px rgba(201,162,39,.35);
}
@media (max-width: 640px) { .rw-segmented { grid-template-columns: repeat(2, 1fr); } }

.rw-field { position: relative; margin-bottom: 18px; }
.rw-field input, .rw-field select, .rw-field textarea {
    width: 100%; padding: 22px 18px 10px;
    border: 2px solid #E2E8F0; border-radius: 14px;
    font-size: 14px; font-family: inherit; outline: none;
    background: #fff; color: #1E293B; transition: .3s;
}
.rw-field textarea { resize: vertical; min-height: 90px; padding-top: 26px; }
.rw-field select { cursor: pointer; appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath fill='%230F3D5C' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 16px center; padding-right: 40px;
}
.rw-field input:focus, .rw-field select:focus, .rw-field textarea:focus {
    border-color: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.15);
}
.rw-field input.error, .rw-field select.error, .rw-field textarea.error {
    border-color: #EF4444; box-shadow: 0 0 0 4px rgba(239,68,68,.15);
}
.rw-field input.valid, .rw-field select.valid, .rw-field textarea.valid {
    border-color: #10B981; box-shadow: 0 0 0 4px rgba(16,185,129,.15);
}
.rw-field label {
    position: absolute; left: 18px; top: 18px;
    font-size: 13px; color: #64748B;
    pointer-events: none; transition: .25s ease;
    background: transparent; padding: 0 6px; font-weight: 600;
}
.rw-field input:focus ~ label,
.rw-field input:not(:placeholder-shown) ~ label,
.rw-field select:focus ~ label,
.rw-field.has-value label,
.rw-field textarea:focus ~ label,
.rw-field textarea:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 10.5px; color: #C9A227;
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
    background: #fff;
}
.rw-field-ic {
    position: absolute; right: 16px; top: 20px; font-size: 16px;
    color: #64748B; pointer-events: none; display: inline-flex;
}
.rw-field-msg {
    font-size: 11px; margin-top: 6px; padding-left: 18px;
    display: none; align-items: center; gap: 4px;
}
.rw-field-msg.show { display: flex; }
.rw-field-msg.error { color: #EF4444; }
.rw-field-msg.success { color: #10B981; }
.rw-field-msg svg { font-size: 12px; }

.rw-char-count {
    position: absolute; right: 16px; bottom: 12px;
    font-size: 11px; color: #64748B; font-weight: 600;
    background: rgba(255,255,255,.85); padding: 2px 6px; border-radius: 4px;
}
body.rw-dark .rw-char-count { background: rgba(15,23,42,.85); }

.rw-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .rw-row { grid-template-columns: 1fr; } }

.rw-conditional {
    max-height: 0; overflow: hidden; opacity: 0;
    transition: max-height .4s ease, opacity .3s ease, margin .3s;
}
.rw-conditional.active { max-height: 300px; opacity: 1; margin-bottom: 18px; }

.rw-submit {
    margin-top: 28px; width: 100%; padding: 18px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; box-shadow: 0 12px 36px rgba(201,162,39,.45);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.rw-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.rw-submit:hover::before { left: 150%; }
.rw-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(201,162,39,.65); }
.rw-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.rw-kbd {
    font-size: 10.5px; padding: 3px 8px; border-radius: 5px;
    background: rgba(15,61,92,.12); color: #092A40; font-weight: 800;
    letter-spacing: .4px; margin-left: auto;
}

.rw-secure {
    display: flex; align-items: center; gap: 10px; margin-top: 16px;
    font-size: 12px; color: #64748B;
}
.rw-secure-ic {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(16,185,129,.1); color: #10B981;
    display: grid; place-items: center; font-size: 12px;
}

.rw-autosave {
    display: flex; align-items: center; gap: 8px; margin-top: 12px;
    font-size: 11px; color: #64748B; font-weight: 600;
}
.rw-autosave-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #10B981; animation: rwPulse 2s ease infinite;
}

.rw-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600;
    display: flex; gap: 12px; align-items: center;
    animation: rwAlertIn .45s ease;
}
@keyframes rwAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.rw-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444; }

.rw-state { text-align: center; padding: 70px 40px; position: relative; overflow: hidden; }
.rw-state-ic {
    width: 110px; height: 110px; border-radius: 50%; margin: 0 auto 24px;
    display: grid; place-items: center; font-size: 52px;
    background: linear-gradient(135deg, #10B981, #34D399); color: #fff;
    box-shadow: 0 20px 55px rgba(16,185,129,.4);
    animation: rwSuccessPop .7s cubic-bezier(0.34, 1.56, 0.64, 1);
    position: relative; z-index: 1;
}
@keyframes rwSuccessPop {
    0% { transform: scale(0) rotate(-180deg); }
    60% { transform: scale(1.15) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}
.rw-state h3 { font-size: 28px; color: #092A40; margin-bottom: 12px; letter-spacing: -.02em; position: relative; z-index: 1; }
.rw-state p { color: #64748B; font-size: 16px; max-width: 480px; margin: 0 auto; line-height: 1.6; position: relative; z-index: 1; }
.rw-state-steps {
    margin-top: 32px; padding: 24px; background: #F7F9FC; border-radius: 16px;
    text-align: left; position: relative; z-index: 1; border: 1px solid #E2E8F0;
}
.rw-state-steps h4 { font-size: 14px; color: #092A40; margin-bottom: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
.rw-step {
    display: flex; gap: 12px; margin-bottom: 12px; align-items: flex-start;
}
.rw-step-num {
    width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A); color: #092A40;
    display: grid; place-items: center; font-size: 12px; font-weight: 800;
}
.rw-step-text { font-size: 13px; color: #64748B; line-height: 1.5; padding-top: 4px; }

.rw-confetti {
    position: absolute; width: 10px; height: 10px; opacity: 0;
    animation: rwConfettiFall 3s ease-out forwards;
}
@keyframes rwConfettiFall {
    0% { transform: translateY(-100px) rotate(0); opacity: 1; }
    100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
}

/* ===== INFO PANEL ===== */
.rw-info {
    max-width: 820px; margin: 40px auto 0; padding: 28px;
    background: linear-gradient(135deg, rgba(201,162,39,.08), rgba(15,61,92,.04));
    border: 1px solid rgba(201,162,39,.25); border-radius: 20px;
}
.rw-info h4 { font-size: 16px; color: #092A40; margin-bottom: 16px; font-weight: 800; display: flex; align-items: center; gap: 10px; }
.rw-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
.rw-info-item {
    padding: 16px; background: #fff; border-radius: 12px;
    border: 1px solid #E2E8F0; transition: .3s;
}
.rw-info-item:hover { border-color: rgba(201,162,39,.4); transform: translateY(-3px); box-shadow: 0 10px 24px rgba(15,61,92,.08); }
.rw-info-ic { font-size: 24px; margin-bottom: 8px; color: #C9A227; display: inline-flex; }
.rw-info-item h5 { font-size: 13px; color: #092A40; margin-bottom: 4px; font-weight: 700; }
.rw-info-item p { font-size: 12px; color: #64748B; line-height: 1.5; margin: 0; }

/* ===== FAQ ===== */
.rw-faq { max-width: 820px; margin: 32px auto 0; padding: 0 24px; }
.rw-faq h4 { font-size: 18px; color: #092A40; margin-bottom: 16px; font-weight: 800; text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px; }
.rw-faq-item {
    background: #fff; border: 1px solid #E2E8F0; border-radius: 12px;
    margin-bottom: 10px; overflow: hidden; transition: .3s;
}
.rw-faq-item:hover { border-color: rgba(201,162,39,.4); }
.rw-faq-q {
    padding: 16px 20px; cursor: pointer; font-size: 14px; font-weight: 700;
    color: #092A40; display: flex; justify-content: space-between; align-items: center;
    transition: .3s;
}
.rw-faq-q:hover { background: #F7F9FC; }
.rw-faq-q svg {
    font-size: 16px; color: #C9A227; transition: transform .3s; flex-shrink: 0;
}
.rw-faq-item.open .rw-faq-q svg { transform: rotate(180deg); }
.rw-faq-a {
    max-height: 0; overflow: hidden; transition: max-height .3s ease;
    padding: 0 20px;
}
.rw-faq-item.open .rw-faq-a {
    max-height: 200px; padding: 0 20px 16px;
}
.rw-faq-a p { font-size: 13px; color: #64748B; line-height: 1.6; margin: 0; }

/* ===== RELATED LINKS ===== */
.rw-links {
    max-width: 820px; margin: 30px auto 0; padding: 0 24px;
    display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;
}
.rw-link-item {
    display: flex; align-items: center; gap: 12px; padding: 14px 16px;
    border-radius: 14px; background: #fff; text-decoration: none;
    color: #0F3D5C; font-size: 13.5px; font-weight: 600;
    transition: .25s; border: 1px solid #E2E8F0;
    box-shadow: 0 4px 14px rgba(15,61,92,.05);
}
body.rw-dark .rw-link-item:hover { background: rgba(201,162,39,.08); }
.rw-link-item:hover { background: rgba(201,162,39,.08); border-color: rgba(201,162,39,.4); transform: translateX(4px); }
.rw-link-ic {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82); color: #fff;
    display: grid; place-items: center; font-size: 16px;
}
.rw-link-item span { flex: 1; }
.rw-link-arrow { color: #C9A227; display: inline-flex; flex-shrink: 0; }

/* ===== BACK TO TOP ===== */
.rw-back-top {
    position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px;
    border-radius: 50%; background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; display: grid; place-items: center;
    font-size: 22px; box-shadow: 0 10px 30px rgba(201,162,39,.4);
    cursor: pointer; border: none; z-index: 999;
    opacity: 0; pointer-events: none; transform: translateY(20px);
    transition: .4s cubic-bezier(.16,1,.3,1);
}
.rw-back-top.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.rw-back-top:hover { transform: translateY(-4px) scale(1.05); }

@media (max-width: 640px) {
    .rw-form-body { padding: 24px; }
    .rw-head { padding: 24px; }
    .rw-progress { padding: 0 24px; }
}

@media print {
    .rw-hero, .rw-dark-toggle, .rw-back-top, .rw-progress-global, .rw-faq, .rw-links, .rw-stats-row, .rw-info { display: none !important; }
    .rw-card { box-shadow: none; border: 1px solid #000; margin-top: 0; }
}
</style>

<div class="rw-progress-global"><div class="rw-progress-global-bar" id="rwProg"></div></div>

<!-- ===== HERO ===== -->
<section class="rw-hero">
    <div class="rw-hero-aurora"></div>
    <div class="rw-hero-grid"></div>
    <div class="container">
        <div class="rw-hero-inner">
            <span class="rw-hero-badge">
                <span class="rw-hero-badge-dot"></span>
                Registrasi Responden Akreditasi
            </span>
            <h1>Dukung <span class="rw-grad">Akreditasi Kampus</span><br>Sebagai Responden</h1>
            <p class="rw-hero-lead">
                Daftarkan diri Anda sebagai calon responden wawancara asesor.
                Partisipasi Anda sangat berarti bagi keberhasilan akreditasi.
            </p>
            <div class="rw-roles">
                <div class="rw-role-pill"><span><?= $ic['graduation'] ?></span> Alumni</div>
                <div class="rw-role-pill"><span><?= $ic['book_open'] ?></span> Mahasiswa</div>
                <div class="rw-role-pill"><span><?= $ic['user_tie'] ?></span> Dosen</div>
                <div class="rw-role-pill"><span><?= $ic['briefcase'] ?></span> Tendik</div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Row -->
<div class="rw-stats-row">
    <div class="rw-stats-card">
        <div class="rw-stats-ic"><?= $ic['graduation'] ?></div>
        <div>
            <div class="rw-stats-num" data-count="<?= $rwStats['Alumni'] ?>"><?= $rwStats['Alumni'] ?></div>
            <div class="rw-stats-lbl">Alumni</div>
        </div>
    </div>
    <div class="rw-stats-card">
        <div class="rw-stats-ic"><?= $ic['book_open'] ?></div>
        <div>
            <div class="rw-stats-num" data-count="<?= $rwStats['Mahasiswa'] ?>"><?= $rwStats['Mahasiswa'] ?></div>
            <div class="rw-stats-lbl">Mahasiswa</div>
        </div>
    </div>
    <div class="rw-stats-card">
        <div class="rw-stats-ic"><?= $ic['user_tie'] ?></div>
        <div>
            <div class="rw-stats-num" data-count="<?= $rwStats['Dosen'] ?>"><?= $rwStats['Dosen'] ?></div>
            <div class="rw-stats-lbl">Dosen</div>
        </div>
    </div>
    <div class="rw-stats-card">
        <div class="rw-stats-ic"><?= $ic['briefcase'] ?></div>
        <div>
            <div class="rw-stats-num" data-count="<?= $rwStats['Tendik'] ?>"><?= $rwStats['Tendik'] ?></div>
            <div class="rw-stats-lbl">Tendik</div>
        </div>
    </div>
    <div class="rw-stats-card">
        <div class="rw-stats-ic"><?= $ic['users'] ?></div>
        <div>
            <div class="rw-stats-num" data-count="<?= $rwStats['total'] ?>"><?= $rwStats['total'] ?></div>
            <div class="rw-stats-lbl">Total</div>
        </div>
    </div>
</div>

<!-- ===== BODY ===== -->
<section class="rw-body">
    <div class="container">
        <div class="rw-card">
            <?php if ($success): ?>
                <div class="rw-state" id="rwSuccessState">
                    <div class="rw-state-ic"><?= $ic['check_circle'] ?></div>
                    <h3>Registrasi Berhasil!</h3>
                    <p>Terima kasih telah mendaftar sebagai calon responden. Tim LPM akan menghubungi Anda via WhatsApp bila jadwal wawancara telah ditetapkan.</p>
                    
                    <div class="rw-state-steps">
                        <h4><?= $ic['list_checks'] ?> Langkah Selanjutnya:</h4>
                        <div class="rw-step">
                            <div class="rw-step-num">1</div>
                            <div class="rw-step-text">Tim LPM akan memverifikasi data Anda dalam 1-3 hari kerja.</div>
                        </div>
                        <div class="rw-step">
                            <div class="rw-step-num">2</div>
                            <div class="rw-step-text">Anda akan menerima konfirmasi via WhatsApp dengan detail jadwal.</div>
                        </div>
                        <div class="rw-step">
                            <div class="rw-step-num">3</div>
                            <div class="rw-step-text">Ikuti wawancara dengan asesor (online/offline) sesuai jadwal.</div>
                        </div>
                    </div>
                    
                    <a href="/publik/berita.php" class="btn btn-primary" style="margin-top:24px;padding:10px 22px;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#092A40;border-radius:50px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:8px;box-shadow:0 8px 22px rgba(201,162,39,.4);">Baca Berita Terbaru <?= $ic['arrow_right'] ?></a>
                </div>
            <?php else: ?>
                <div class="rw-head">
                    <div class="rw-head-inner">
                        <small><?= $ic['clipboard_list'] ?> Formulir Kesediaan</small>
                        <h3>Daftar Sebagai Responden Wawancara</h3>
                        <p><?= $ic['info'] ?> Pilih kategori Anda di bawah — kolom akan menyesuaikan otomatis.</p>
                    </div>
                </div>

                <div class="rw-steps-nav" id="rwStepsNav">
                    <div class="rw-step-ind active" data-step="1"><span class="rw-step-num-i">1</span><span class="rw-step-lbl-i">Kategori</span></div>
                    <div class="rw-step-ind" data-step="2"><span class="rw-step-num-i">2</span><span class="rw-step-lbl-i">Data Diri</span></div>
                    <div class="rw-step-ind" data-step="3"><span class="rw-step-num-i">3</span><span class="rw-step-lbl-i">Kontak</span></div>
                    <div class="rw-step-ind" data-step="4"><span class="rw-step-num-i">4</span><span class="rw-step-lbl-i">Ketersediaan</span></div>
                </div>

                <div class="rw-progress">
                    <div class="rw-progress-bar">
                        <div class="rw-progress-fill" id="rwProgressFill"></div>
                    </div>
                    <div class="rw-progress-text" id="rwProgressText">0% lengkap</div>
                </div>

                <div class="rw-form-body">
                    <?php if ($error): ?><div class="rw-alert danger"><?= $ic['alert_circle'] ?> <?= Security::e($error) ?></div><?php endif; ?>

                    <form method="POST" id="rwForm" novalidate>
                        <?= Security::csrfField() ?>

                        <div class="rw-segmented" id="rwSeg" data-section="1">
                            <button type="button" class="rw-seg active" data-tipe="Alumni">
                                <span class="rw-seg-ic"><?= $ic['graduation'] ?></span>Alumni
                            </button>
                            <button type="button" class="rw-seg" data-tipe="Mahasiswa">
                                <span class="rw-seg-ic"><?= $ic['book_open'] ?></span>Mahasiswa
                            </button>
                            <button type="button" class="rw-seg" data-tipe="Dosen">
                                <span class="rw-seg-ic"><?= $ic['user_tie'] ?></span>Dosen
                            </button>
                            <button type="button" class="rw-seg" data-tipe="Tendik">
                                <span class="rw-seg-ic"><?= $ic['briefcase'] ?></span>Tendik
                            </button>
                        </div>
                        <input type="hidden" name="tipe" id="rwTipe" value="Alumni">

                        <div class="rw-row" data-section="2">
                            <div class="rw-field">
                                <input type="text" name="nama" id="rwNama" placeholder=" " required autocomplete="name">
                                <label for="rwNama">Nama Lengkap *</label>
                                <span class="rw-field-ic"><?= $ic['user'] ?></span>
                                <div class="rw-field-msg" id="rwNamaMsg"></div>
                            </div>
                            <div class="rw-field">
                                <input type="text" name="identitas" id="rwId" placeholder=" ">
                                <label for="rwId">NIM / NIDN / NIP</label>
                                <span class="rw-field-ic"><?= $ic['id_card'] ?></span>
                            </div>
                        </div>

                        <div class="rw-conditional active" id="rwProdiWrap" data-section="2">
                            <div class="rw-field has-value">
                                <select name="id_prodi" id="rwProdi">
                                    <option value="0">— Pilih Prodi —</option>
                                    <?php foreach ($prodiList as $p): ?>
                                        <option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="rwProdi">Program Studi</label>
                            </div>
                        </div>

                        <div class="rw-conditional" id="rwUnitWrap" data-section="2">
                            <div class="rw-field has-value">
                                <select name="unit_kerja" id="rwUnit">
                                    <option>Perpustakaan</option>
                                    <option>TIPD / IT</option>
                                    <option>Laboratorium</option>
                                    <option>Sarpras</option>
                                    <option>BAAK / Administrasi</option>
                                    <option>Keuangan</option>
                                    <option>Humas</option>
                                    <option>Lainnya</option>
                                </select>
                                <label for="rwUnit">Unit Kerja</label>
                            </div>
                        </div>

                        <div class="rw-row" data-section="3">
                            <div class="rw-field">
                                <input type="text" name="no_wa" id="rwWa" placeholder=" " required autocomplete="tel">
                                <label for="rwWa">No. WhatsApp *</label>
                                <span class="rw-field-ic"><?= $ic['phone'] ?></span>
                                <div class="rw-field-msg" id="rwWaMsg"></div>
                            </div>
                            <div class="rw-field">
                                <input type="email" name="email" id="rwEmail" placeholder=" " autocomplete="email">
                                <label for="rwEmail">Email</label>
                                <span class="rw-field-ic"><?= $ic['mail'] ?></span>
                                <div class="rw-field-msg" id="rwEmailMsg"></div>
                            </div>
                        </div>

                        <div data-section="4">
                            <div class="rw-field has-value">
                                <select name="ketersediaan" id="rwKet">
                                    <option>Sangat Siap</option>
                                    <option>Siap</option>
                                    <option>Ragu-ragu</option>
                                    <option>Perlu Penjadwalan Ulang</option>
                                </select>
                                <label for="rwKet">Ketersediaan</label>
                            </div>

                            <div class="rw-field">
                                <textarea name="catatan" id="rwCat" placeholder=" " maxlength="500"></textarea>
                                <label for="rwCat">Catatan (Opsional)</label>
                                <span class="rw-char-count" id="rwCharCount">0/500</span>
                            </div>
                        </div>

                        <button type="submit" class="rw-submit" id="rwSubmit">
                            <?= $ic['mic'] ?> Daftar Sebagai Responden
                            <span class="rw-kbd">Ctrl + ↵</span>
                        </button>

                        <div class="rw-secure">
                            <span class="rw-secure-ic"><?= $ic['shield'] ?></span>
                            <span>Data Anda aman dan hanya digunakan untuk keperluan akreditasi.</span>
                        </div>
                        
                        <div class="rw-autosave">
                            <span class="rw-autosave-dot"></span>
                            <span><?= $ic['save'] ?> Form auto-save aktif — data tidak hilang saat refresh</span>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info Panel -->
        <div class="rw-info">
            <h4><?= $ic['info'] ?> Tentang Proses Akreditasi</h4>
            <div class="rw-info-grid">
                <div class="rw-info-item">
                    <div class="rw-info-ic"><?= $ic['target'] ?></div>
                    <h5>Tujuan Wawancara</h5>
                    <p>Asesor ingin memahami implementasi penjaminan mutu di unit Anda secara langsung.</p>
                </div>
                <div class="rw-info-item">
                    <div class="rw-info-ic"><?= $ic['clock'] ?></div>
                    <h5>Durasi</h5>
                    <p>Wawancara berlangsung 30-60 menit dengan format semi-terstruktur.</p>
                </div>
                <div class="rw-info-item">
                    <div class="rw-info-ic"><?= $ic['shield'] ?></div>
                    <h5>Kerahasiaan</h5>
                    <p>Identitas responden dijaga kerahasiaannya sesuai standar akreditasi.</p>
                </div>
                <div class="rw-info-item">
                    <div class="rw-info-ic"><?= $ic['calendar_days'] ?></div>
                    <h5>Kontak</h5>
                    <p>Tim LPM akan menghubungi via WhatsApp 3-7 hari sebelum jadwal.</p>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="rw-faq">
            <h4><?= $ic['help_circle'] ?> Pertanyaan yang Sering Diajukan</h4>
            <div class="rw-faq-item">
                <div class="rw-faq-q">Apakah saya wajib menjadi responden?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="rw-faq-a"><p>Tidak. Partisipasi bersifat sukarela. Namun, kontribusi Anda sangat berharga untuk keberhasilan akreditasi institusi.</p></div>
            </div>
            <div class="rw-faq-item">
                <div class="rw-faq-q">Bagaimana jika saya berhalangan hadir?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="rw-faq-a"><p>Anda dapat menginformasikan kepada tim LPM untuk penjadwalan ulang. Fleksibilitas waktu sangat kami hargai.</p></div>
            </div>
            <div class="rw-faq-item">
                <div class="rw-faq-q">Apakah ada persiapan khusus?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="rw-faq-a"><p>Tidak perlu persiapan khusus. Asesor akan bertanya tentang pengalaman dan persepsi Anda terhadap layanan unit.</p></div>
            </div>
            <div class="rw-faq-item">
                <div class="rw-faq-q">Berapa lama proses akreditasi ini?<span><?= $ic['chevron_down'] ?></span></div>
                <div class="rw-faq-a"><p>Proses akreditasi berlangsung 6-12 bulan. Wawancara responden biasanya dilakukan di bulan ke-2 atau ke-3.</p></div>
            </div>
        </div>

        <!-- Related Links -->
        <div class="rw-links">
            <a href="/publik/tracer.php" class="rw-link-item">
                <span class="rw-link-ic"><?= $ic['graduation'] ?></span>
                <span>Tracer Study Alumni</span>
                <span class="rw-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
            <a href="/publik/survei-pengguna.php" class="rw-link-item">
                <span class="rw-link-ic"><?= $ic['briefcase'] ?></span>
                <span>Survei Pengguna</span>
                <span class="rw-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
            <a href="/publik/akreditasi.php" class="rw-link-item">
                <span class="rw-link-ic"><?= $ic['award'] ?></span>
                <span>Status Akreditasi</span>
                <span class="rw-link-arrow"><?= $ic['arrow_right'] ?></span>
            </a>
        </div>
    </div>
</section>

<button class="rw-dark-toggle" id="rwDarkToggle" aria-label="Toggle dark mode"><?= $ic['moon'] ?></button>
<button class="rw-back-top" id="rwBackTop" aria-label="Kembali ke atas"><?= $ic['arrow_up'] ?></button>

<script>
(function () {
    'use strict';

    var ICON = {
        check: <?= json_encode($ic['check']) ?>,
        alert: <?= json_encode($ic['alert_circle']) ?>,
        loader: <?= json_encode($ic['loader']) ?>,
        mic: <?= json_encode($ic['mic']) ?>,
        moon: <?= json_encode($ic['moon']) ?>,
        sun: <?= json_encode($ic['sun']) ?>
    };

    /* ===== SCROLL PROGRESS & BACK TO TOP ===== */
    var prog = document.getElementById('rwProg');
    var backTop = document.getElementById('rwBackTop');
    window.addEventListener('scroll', function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        var p = max > 0 ? (window.scrollY / max) * 100 : 0;
        if (prog) prog.style.width = p + '%';
        if (backTop) backTop.classList.toggle('visible', window.scrollY > 500);
    }, { passive: true });
    if (backTop) backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ===== DARK MODE ===== */
    var darkBtn = document.getElementById('rwDarkToggle');
    var savedDark = localStorage.getItem('rwDark') === '1';
    if (savedDark) {
        document.body.classList.add('rw-dark');
        if (darkBtn) darkBtn.innerHTML = ICON.sun;
    }
    if (darkBtn) darkBtn.addEventListener('click', function () {
        var on = document.body.classList.toggle('rw-dark');
        localStorage.setItem('rwDark', on ? '1' : '0');
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

    /* ===== SEGMENTED CONTROL ===== */
    var seg = document.getElementById('rwSeg');
    var tipeInput = document.getElementById('rwTipe');
    var prodiWrap = document.getElementById('rwProdiWrap');
    var unitWrap = document.getElementById('rwUnitWrap');

    if (seg) {
        seg.addEventListener('click', function (e) {
            var btn = e.target.closest('.rw-seg');
            if (!btn) return;
            seg.querySelectorAll('.rw-seg').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var tipe = btn.dataset.tipe;
            tipeInput.value = tipe;

            if (tipe === 'Tendik') {
                prodiWrap.classList.remove('active');
                unitWrap.classList.add('active');
            } else {
                prodiWrap.classList.add('active');
                unitWrap.classList.remove('active');
            }
            updateProgress();
        });
    }

    /* ===== AUTO-SAVE & RESTORE ===== */
    var form = document.getElementById('rwForm');
    var STORAGE_KEY = 'rw_form_draft_v2';
    
    if (form) {
        var draft = localStorage.getItem(STORAGE_KEY);
        if (draft) {
            try {
                var data = JSON.parse(draft);
                if (data.tipe) {
                    tipeInput.value = data.tipe;
                    seg.querySelectorAll('.rw-seg').forEach(function (b) {
                        b.classList.toggle('active', b.dataset.tipe === data.tipe);
                    });
                    if (data.tipe === 'Tendik') {
                        prodiWrap.classList.remove('active');
                        unitWrap.classList.add('active');
                    } else {
                        prodiWrap.classList.add('active');
                        unitWrap.classList.remove('active');
                    }
                }
                ['nama', 'identitas', 'no_wa', 'email', 'catatan'].forEach(function (name) {
                    var el = form.querySelector('[name="' + name + '"]');
                    if (el && data[name]) el.value = data[name];
                });
                ['id_prodi', 'unit_kerja', 'ketersediaan'].forEach(function (name) {
                    var el = form.querySelector('[name="' + name + '"]');
                    if (el && data[name]) el.value = data[name];
                });
                updateProgress();
            } catch (e) {}
        }

        var saveTimeout;
        form.addEventListener('input', function () {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function () {
                var data = { tipe: tipeInput.value };
                ['nama', 'identitas', 'no_wa', 'email', 'catatan'].forEach(function (name) {
                    var el = form.querySelector('[name="' + name + '"]');
                    if (el) data[name] = el.value;
                });
                ['id_prodi', 'unit_kerja', 'ketersediaan'].forEach(function (name) {
                    var el = form.querySelector('[name="' + name + '"]');
                    if (el) data[name] = el.value;
                });
                localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            }, 500);
            updateProgress();
        });

        form.addEventListener('submit', function () {
            localStorage.removeItem(STORAGE_KEY);
        });
    }

    /* ===== PROGRESS BAR ===== */
    function updateProgress() {
        if (!form) return;
        var fields = ['nama', 'no_wa'];
        var filled = 0;
        fields.forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (el && el.value.trim()) filled++;
        });
        var pct = Math.round((filled / fields.length) * 100);
        document.getElementById('rwProgressFill').style.width = pct + '%';
        document.getElementById('rwProgressText').textContent = pct + '% lengkap';
    }
    updateProgress();

    /* ===== STEP INDICATOR (scroll-based) ===== */
    var steps = document.querySelectorAll('.rw-step-ind');
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

    /* ===== REAL-TIME VALIDATION ===== */
    var namaInput = document.getElementById('rwNama');
    var waInput = document.getElementById('rwWa');
    var emailInput = document.getElementById('rwEmail');
    var catInput = document.getElementById('rwCat');
    var charCount = document.getElementById('rwCharCount');

    if (namaInput) {
        namaInput.addEventListener('input', function () {
            var msg = document.getElementById('rwNamaMsg');
            if (this.value.trim().length < 3) {
                this.classList.add('error'); this.classList.remove('valid');
                msg.innerHTML = ICON.alert + ' Nama minimal 3 karakter';
                msg.classList.add('show', 'error'); msg.classList.remove('success');
            } else {
                this.classList.remove('error'); this.classList.add('valid');
                msg.innerHTML = ICON.check + ' Valid';
                msg.classList.add('show', 'success'); msg.classList.remove('error');
            }
        });
    }

    if (waInput) {
        waInput.addEventListener('input', function () {
            var msg = document.getElementById('rwWaMsg');
            var val = this.value.replace(/[^0-9+\-\s]/g, '');
            this.value = val;
            if (val.length > 0 && (val.length < 10 || val.length > 15)) {
                this.classList.add('error'); this.classList.remove('valid');
                msg.innerHTML = ICON.alert + ' Nomor harus 10-15 digit';
                msg.classList.add('show', 'error'); msg.classList.remove('success');
            } else if (val.length >= 10) {
                this.classList.remove('error'); this.classList.add('valid');
                msg.innerHTML = ICON.check + ' Valid';
                msg.classList.add('show', 'success'); msg.classList.remove('error');
            } else {
                this.classList.remove('error', 'valid');
                msg.classList.remove('show');
            }
        });
    }

    if (emailInput) {
        emailInput.addEventListener('input', function () {
            var msg = document.getElementById('rwEmailMsg');
            if (this.value && !this.value.includes('@')) {
                this.classList.add('error'); this.classList.remove('valid');
                msg.innerHTML = ICON.alert + ' Format email tidak valid';
                msg.classList.add('show', 'error'); msg.classList.remove('success');
            } else if (this.value) {
                this.classList.remove('error'); this.classList.add('valid');
                msg.innerHTML = ICON.check + ' Valid';
                msg.classList.add('show', 'success'); msg.classList.remove('error');
            } else {
                this.classList.remove('error', 'valid');
                msg.classList.remove('show');
            }
        });
    }

    if (catInput && charCount) {
        catInput.addEventListener('input', function () {
            charCount.textContent = this.value.length + '/500';
            if (this.value.length > 450) charCount.style.color = '#F59E0B';
            else charCount.style.color = '';
        });
    }

    /* ===== SUBMIT ===== */
    if (form) {
        form.addEventListener('submit', function (e) {
            var btn = document.getElementById('rwSubmit');
            if (btn && !btn.dataset.busy) {
                var nama = document.getElementById('rwNama').value.trim();
                var wa = document.getElementById('rwWa').value.trim();
                if (nama.length < 3 || wa.length < 10 || wa.length > 15) {
                    e.preventDefault();
                    alert('Mohon lengkapi data dengan benar.');
                    return;
                }
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.innerHTML = ICON.loader + ' Mendaftar...';
            }
        });

        // Keyboard Shortcut: Ctrl/Cmd + Enter
        form.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
    }

    /* ===== CONFETTI ON SUCCESS ===== */
    var successState = document.getElementById('rwSuccessState');
    if (successState) {
        var colors = ['#C9A227', '#E8C55A', '#0F3D5C', '#10B981', '#F59E0B'];
        for (var i = 0; i < 50; i++) {
            var conf = document.createElement('div');
            conf.className = 'rw-confetti';
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
    document.querySelectorAll('.rw-faq-item').forEach(function (item) {
        item.querySelector('.rw-faq-q').addEventListener('click', function () {
            item.classList.toggle('open');
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>