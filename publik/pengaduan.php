<?php
$pageTitle = 'Pengaduan & Kritik';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    
    if (!empty($_POST['website_hp'])) {
        header('Location: /publik/pengaduan.php');
        exit;
    }

    $_SESSION['_pengaduan_time'] ??= 0;
    if (time() - $_SESSION['_pengaduan_time'] < 60) {
        $error = 'Mohon tunggu 1 menit sebelum mengirim pengaduan berikutnya.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $subjek = trim($_POST['subjek'] ?? '');
        $isi = trim($_POST['isi'] ?? '');
        $instansi = trim($_POST['instansi'] ?? '');
        $kategori = trim($_POST['kategori'] ?? 'Lainnya');
        $rating = (int)($_POST['rating'] ?? 0);
        $captchaAns = trim($_POST['captcha_ans'] ?? '');
        $captchaExp = $_SESSION['_captcha_exp'] ?? '';

        if (!$nama || !$email || !$subjek || !$isi) {
            $error = 'Mohon lengkapi semua kolom wajib (*) dengan benar.';
        } elseif (mb_strlen($isi) < 20) {
            $error = 'Uraian pengaduan terlalu singkat. Mohon jelaskan lebih detail (min. 20 karakter).';
        } elseif ($captchaAns !== $captchaExp) {
            $error = 'Jawaban verifikasi keamanan tidak tepat. Silakan coba lagi.';
        } else {
            try {
                $stmt = Database::getInstance()->prepare(
                    "INSERT INTO pengaduan (nama, email, instansi, subjek, isi_pesan, ip_address, kategori, rating)
                     VALUES (:nama, :email, :instansi, :subjek, :isi, :ip, :kategori, :rating)"
                );
                $stmt->execute([
                    ':nama' => $nama, ':email' => $email,
                    ':instansi' => $instansi,
                    ':subjek' => $subjek, ':isi' => $isi,
                    ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    ':kategori' => $kategori,
                    ':rating' => $rating
                ]);
                Notifier::sendRole(1, '📩 Pengaduan Publik Baru', $nama . ' — ' . $subjek, '/sim/admin/pengaduan.php', '📩');
                $success = 'Pengaduan Anda telah kami terima. Tim LPM akan menindaklanjuti segera.';
                $_SESSION['_pengaduan_time'] = time();
                unset($_SESSION['_captcha_exp']);
            } catch (PDOException $e) {
                try {
                    $stmt2 = Database::getInstance()->prepare(
                        "INSERT INTO pengaduan (nama, email, instansi, subjek, isi_pesan, ip_address)
                         VALUES (:nama, :email, :instansi, :subjek, :isi, :ip)"
                    );
                    $stmt2->execute([
                        ':nama' => $nama, ':email' => $email,
                        ':instansi' => $instansi,
                        ':subjek' => $subjek, ':isi' => $isi,
                        ':ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);
                    Notifier::sendRole(1, '📩 Pengaduan Publik Baru', $nama . ' — ' . $subjek, '/sim/admin/pengaduan.php', '📩');
                    $success = 'Pengaduan Anda telah kami terima. Tim LPM akan menindaklanjuti segera.';
                    $_SESSION['_pengaduan_time'] = time();
                    unset($_SESSION['_captcha_exp']);
                } catch (PDOException $e2) {
                    $error = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
                }
            }
        }
    }
}

if (empty($_SESSION['_captcha_exp']) || $success) {
    $a = rand(1, 9); $b = rand(1, 9);
    $_SESSION['_captcha_exp'] = (string)($a + $b);
    $_SESSION['_captcha_q'] = "$a + $b";
}

// ===== KUMPULAN ICON SVG (GAYA MODERN & PROFESSIONAL) =====
$ic = [
    'mail' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    'message_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>',
    'send' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
    'edit' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
    'edit_3' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/><path d="m15 5 3 3"/></svg>',
    'check' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    'alert_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>',
    'user' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'building' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>',
    'folder' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>',
    'tag' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>',
    'star' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'paperclip' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 17.98 8.8l-8.58 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>',
    'upload' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>',
    'shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>',
    'lock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'activity' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
    'compass' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>',
    'zap' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    'clock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'refresh_cw' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>',
    'link_2' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/></svg>',
    'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'file_text' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>',
    'newspaper' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>',
    'help_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
    'moon' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>',
    'sun' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>',
    'arrow_right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
    'arrow_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>',
    'x' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
    'file' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>',
    'image' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>',
    'file_pdf' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 13v4"/><path d="M9 13h1.5a1.5 1.5 0 0 1 0 3H9"/><path d="M17 13h-1.5v4H17"/><path d="M15.5 15H17"/></svg>',
    'loader' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:pgSpin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
];

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   PENGADUAN ULTIMATE v11.0 — Enhanced • Accessible • Professional
============================================================ */
@keyframes pgSpin { to { transform: rotate(360deg); } }

/* ===== SCROLL PROGRESS ===== */
.pg-progress { position: fixed; top: 0; left: 0; width: 100%; height: 4px; z-index: 1100; background: rgba(15,61,92,.06); }
.pg-progress-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #C9A227, #E8C55A); box-shadow: 0 0 12px rgba(201,162,39,.5); transition: width .1s linear; }

/* ===== DARK MODE ===== */
.pg-dark-toggle {
    position: fixed; bottom: 24px; left: 24px; z-index: 1000;
    width: 52px; height: 52px; border-radius: 50%; border: none; cursor: pointer;
    background: #fff; color: #0F3D5C;
    display: grid; place-items: center; font-size: 22px;
    box-shadow: 0 8px 24px rgba(15,61,92,.15);
    transition: .3s;
}
.pg-dark-toggle:hover { transform: scale(1.08) rotate(15deg); }
body.pg-dark { background: #0F172A; color: #E2E8F0; }
body.pg-dark .pg-body { background: linear-gradient(180deg, #0F172A, #1E293B); }
body.pg-dark .pg-form-wrap,
body.pg-dark .pg-steps-card,
body.pg-dark .pg-faq-card,
body.pg-dark .pg-links-card,
body.pg-dark .pg-tracker-card { background: #1E293B; border-color: #334155; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
body.pg-dark .pg-form-head h3,
body.pg-dark .pg-steps-card h3,
body.pg-dark .pg-faq-card h3,
body.pg-dark .pg-links-card h3,
body.pg-dark .pg-tracker-card h3,
body.pg-dark .pg-step-content h4,
body.pg-dark .pg-success h3,
body.pg-dark .pg-field input,
body.pg-dark .pg-field textarea,
body.pg-dark .pg-field select { color: #E2E8F0; }
body.pg-dark .pg-form-head p,
body.pg-dark .pg-step-content p,
body.pg-dark .pg-faq p,
body.pg-dark .pg-success p,
body.pg-dark .pg-secure,
body.pg-dark .pg-tracker-note,
body.pg-dark .pg-field label { color: #94A3B8; }
body.pg-dark .pg-field input,
body.pg-dark .pg-field textarea,
body.pg-dark .pg-field select {
    background: #0F172A; border-color: #334155; color: #E2E8F0;
}
body.pg-dark .pg-field input:focus ~ label,
body.pg-dark .pg-field input:not(:placeholder-shown) ~ label,
body.pg-dark .pg-field textarea:focus ~ label,
body.pg-dark .pg-field textarea:not(:placeholder-shown) ~ label,
body.pg-dark .pg-field select:focus ~ label { background: #1E293B; }
body.pg-dark .pg-field label { background: #1E293B; }
body.pg-dark .pg-faq { border-color: #334155; }
body.pg-dark .pg-faq summary { color: #E2E8F0; }
body.pg-dark .pg-faq[open] { border-color: rgba(201,162,39,.4); }
body.pg-dark svg { color: inherit; }

/* ===== HERO ===== */
.pg-hero {
    min-height: 78vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 130px 0 90px;
    display: flex; align-items: center;
}
.pg-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 15% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 85% 20%, rgba(26,90,130,.45), transparent 55%),
        radial-gradient(ellipse 60% 50% at 70% 80%, rgba(232,197,90,.18), transparent 55%),
        linear-gradient(160deg, #061D2E 0%, #0F3D5C 55%, #092A40 100%);
    animation: pgShift 16s ease-in-out infinite;
}
@keyframes pgShift { 0%, 100% { filter: hue-rotate(0deg); transform: scale(1); } 50% { filter: hue-rotate(12deg); transform: scale(1.04); } }
.pg-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.pg-hero-inner { position: relative; z-index: 3; max-width: 900px; margin: 0 auto; text-align: center; }

.pg-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 28px; position: relative; overflow: hidden;
}
.pg-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: pgShimmer 3.5s ease-in-out infinite;
}
@keyframes pgShimmer { to { left: 200%; } }
.pg-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: pgPulse 2s ease infinite;
}
@keyframes pgPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.pg-hero h1 { font-size: clamp(40px, 6vw, 78px); font-weight: 800; line-height: 1.04; margin-bottom: 22px; letter-spacing: -.04em; }
.pg-hero h1 .pg-grad {
    background: linear-gradient(120deg, #E8C55A 0%, #C9A227 25%, #F7E491 50%, #C9A227 75%, #E8C55A 100%);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: pgTextShine 4s linear infinite; font-style: italic;
}
@keyframes pgTextShine { to { background-position: 200% center; } }
.pg-hero-lead { font-size: clamp(16px, 1.5vw, 18px); color: rgba(255,255,255,.75); max-width: 660px; margin: 0 auto 44px; line-height: 1.65; }

.pg-float-ic {
    position: absolute; opacity: .12; animation: pgFloat 8s ease-in-out infinite;
    pointer-events: none; z-index: 1; color: #E8C55A; font-size: 48px;
}
.pg-fi1 { top: 18%; left: 8%; animation-delay: 0s; }
.pg-fi2 { top: 28%; right: 10%; animation-delay: -2s; font-size: 56px; }
.pg-fi3 { bottom: 22%; left: 12%; animation-delay: -4s; font-size: 44px; }
.pg-fi4 { bottom: 18%; right: 8%; animation-delay: -6s; font-size: 52px; }
@keyframes pgFloat { 0%, 100% { transform: translateY(0) rotate(0); } 50% { transform: translateY(-16px) rotate(-6deg); } }
@media (max-width: 992px) { .pg-float-ic { display: none; } }

.pg-trust { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 32px; }
.pg-trust-item {
    padding: 8px 16px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.pg-trust-item span { color: #10B981; font-size: 16px; display: inline-flex; }

/* ===== BODY ===== */
.pg-body { padding: 80px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }
.pg-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 40px; align-items: start; }
@media (max-width: 992px) { .pg-grid { grid-template-columns: 1fr; } }

.pg-form-wrap {
    background: #fff; border-radius: 28px; padding: 44px;
    border: 1px solid var(--border, #E2E8F0); position: relative; overflow: hidden;
    box-shadow: 0 20px 60px rgba(15,61,92,.08);
}
.pg-form-wrap::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #C9A227, #0F3D5C, #C9A227);
    background-size: 200% 100%; animation: pgGradLine 4s linear infinite;
}
@keyframes pgGradLine { to { background-position: 200% 0; } }
.pg-form-head { margin-bottom: 28px; }
.pg-form-head h3 { font-size: 24px; color: var(--primary-dark, #0F3D5C); margin-bottom: 6px; letter-spacing: -.02em; display: flex; align-items: center; gap: 10px; }
.pg-form-head p { color: var(--text-muted, #64748B); font-size: 14px; }

.pg-field { position: relative; margin-bottom: 20px; }
.pg-field input, .pg-field textarea, .pg-field select {
    width: 100%; padding: 22px 18px 10px;
    border: 2px solid var(--border, #E2E8F0); border-radius: 14px;
    font-size: 15px; font-family: inherit; outline: none;
    background: #fff; color: var(--text-dark, #1E293B);
    transition: .3s;
}
.pg-field textarea { resize: vertical; min-height: 130px; padding-top: 26px; }
.pg-field select { padding-top: 22px; appearance: none; cursor: pointer; }
.pg-field input:focus, .pg-field textarea:focus, .pg-field select:focus {
    border-color: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.15);
}
.pg-field input.valid, .pg-field textarea.valid { border-color: #10B981; }
.pg-field input.error, .pg-field textarea.error, .pg-field select.error {
    border-color: #EF4444; box-shadow: 0 0 0 4px rgba(239,68,68,.15);
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
}
.pg-field.shake { animation: shake 0.4s ease-in-out; }

.pg-field label {
    position: absolute; left: 18px; top: 18px;
    font-size: 14px; color: var(--text-muted, #64748B);
    pointer-events: none; transition: .25s cubic-bezier(.16,1,.3,1);
    background: #fff; padding: 0 6px; font-weight: 600;
}
.pg-field select ~ label,
.pg-field input:focus ~ label,
.pg-field input:not(:placeholder-shown) ~ label,
.pg-field textarea:focus ~ label,
.pg-field textarea:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 11px; color: #C9A227;
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
}
.pg-field-ic {
    position: absolute; right: 16px; top: 18px; font-size: 18px;
    color: var(--text-muted, #64748B); pointer-events: none;
    display: inline-flex;
}
.pg-field .pg-char-count {
    position: absolute; right: 16px; bottom: 12px;
    font-size: 11px; color: #64748B; font-weight: 600;
    background: rgba(255,255,255,.85); padding: 2px 6px; border-radius: 4px;
}
body.pg-dark .pg-field .pg-char-count { background: rgba(15,23,42,.85); }
.pg-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .pg-row { grid-template-columns: 1fr; } }

/* ===== RATING STARS ===== */
.pg-rating { display: flex; gap: 8px; align-items: center; padding: 10px 0; }
.pg-rating-star {
    font-size: 28px; color: #E2E8F0; cursor: pointer; transition: .2s;
    background: none; border: none; padding: 0; line-height: 1;
    display: inline-flex;
}
.pg-rating-star:hover, .pg-rating-star.active { color: #C9A227; transform: scale(1.15); }
.pg-rating-label { margin-left: 12px; font-size: 13px; color: #64748B; font-weight: 600; }
body.pg-dark .pg-rating-star { color: #475569; }
body.pg-dark .pg-rating-star.active { color: #E8C55A; }

/* ===== FILE UPLOAD ===== */
.pg-upload {
    border: 2px dashed var(--border, #E2E8F0); border-radius: 14px;
    padding: 24px; text-align: center; cursor: pointer; transition: .3s;
    background: rgba(15,61,92,.02); position: relative; overflow: hidden;
}
.pg-upload:hover { border-color: #C9A227; background: rgba(201,162,39,.05); }
.pg-upload.dragover { border-color: #C9A227; background: rgba(201,162,39,.1); }
.pg-upload-ic { font-size: 36px; color: #C9A227; margin-bottom: 10px; display: flex; justify-content: center; }
.pg-upload p { font-size: 13px; color: #64748B; margin: 4px 0; }
.pg-upload p strong { color: #0F3D5C; }
body.pg-dark .pg-upload p strong { color: #E2E8F0; }
.pg-upload input { display: none; }
.pg-upload-list { display: flex; flex-direction: column; gap: 8px; margin-top: 14px; }
.pg-upload-item {
    display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    background: #F7F9FC; border-radius: 10px; font-size: 13px;
    border: 1px solid #E2E8F0;
}
body.pg-dark .pg-upload-item { background: #0F172A; border-color: #334155; color: #E2E8F0; }
.pg-upload-item .pg-uf-ic { color: #C9A227; flex-shrink: 0; display: inline-flex; }
.pg-upload-item .pg-uf-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; color: #0F3D5C; }
body.pg-dark .pg-upload-item .pg-uf-name { color: #E2E8F0; }
.pg-upload-item .pg-uf-size { font-size: 11px; color: #64748B; flex-shrink: 0; }
.pg-upload-item .pg-uf-rm {
    background: none; border: none; cursor: pointer; color: #EF4444;
    font-size: 16px; padding: 0; line-height: 1; display: inline-flex;
}

/* ===== CAPTCHA ===== */
.pg-captcha {
    display: flex; gap: 12px; align-items: center;
    padding: 16px; background: #F7F9FC; border-radius: 14px;
    margin-bottom: 20px; border: 1px solid #E2E8F0;
}
body.pg-dark .pg-captcha { background: #0F172A; border-color: #334155; }
.pg-captcha-ic { color: #C9A227; font-size: 24px; display: inline-flex; flex-shrink: 0; }
.pg-captcha-q { flex: 1; font-size: 14px; color: #0F3D5C; font-weight: 700; }
body.pg-dark .pg-captcha-q { color: #E2E8F0; }
.pg-captcha input {
    width: 80px; padding: 10px 12px; border: 2px solid #E2E8F0; border-radius: 10px;
    font-size: 16px; font-weight: 800; text-align: center; outline: none;
    font-family: inherit; background: #fff; color: #0F3D5C;
}
body.pg-dark .pg-captcha input { background: #1E293B; border-color: #334155; color: #E2E8F0; }
.pg-captcha input:focus { border-color: #C9A227; box-shadow: 0 0 0 3px rgba(201,162,39,.15); }

.pg-submit {
    width: 100%; padding: 16px; border-radius: 14px; border: none; cursor: pointer;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; font-weight: 800; font-size: 15px; font-family: inherit;
    transition: .3s; box-shadow: 0 10px 30px rgba(201,162,39,.4);
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
    position: relative; overflow: hidden;
}
.pg-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.pg-submit:hover::before { left: 150%; }
.pg-submit:hover { transform: translateY(-2px); box-shadow: 0 14px 38px rgba(201,162,39,.55); }
.pg-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }
.pg-kbd {
    font-size: 10.5px; padding: 3px 8px; border-radius: 5px;
    background: rgba(15,61,92,.12); color: #0F3D5C; font-weight: 800;
    letter-spacing: .4px; margin-left: auto;
}

.pg-secure {
    display: flex; align-items: center; gap: 10px; margin-top: 16px;
    font-size: 12px; color: var(--text-muted, #64748B);
}
.pg-secure-ic {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(16,185,129,.1); color: #10B981;
    display: grid; place-items: center; font-size: 12px;
}

.pg-success { text-align: center; padding: 40px 20px; position: relative; overflow: hidden; }
.pg-success-ic {
    width: 90px; height: 90px; border-radius: 50%; margin: 0 auto 24px;
    background: linear-gradient(135deg, #10B981, #34D399);
    display: grid; place-items: center; font-size: 42px; color: #fff;
    box-shadow: 0 15px 40px rgba(16,185,129,.4);
    animation: pgSuccessPop .6s cubic-bezier(.34,1.56,.64,1);
    position: relative; z-index: 1;
}
@keyframes pgSuccessPop {
    0% { transform: scale(0); opacity: 0; }
    60% { transform: scale(1.15); }
    100% { transform: scale(1); opacity: 1; }
}
.pg-success h3 { font-size: 24px; color: var(--primary-dark, #0F3D5C); margin-bottom: 10px; letter-spacing: -.02em; position: relative; z-index: 1; }
.pg-success p { color: var(--text-muted, #64748B); font-size: 15px; max-width: 420px; margin: 0 auto 24px; position: relative; z-index: 1; line-height: 1.65; }
@keyframes pgConfettiFall {
    0% { transform: translateY(-100px) rotate(0); opacity: 1; }
    100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
}

/* ===== SIDE PANELS ===== */
.pg-side { display: flex; flex-direction: column; gap: 24px; }
.pg-steps-card, .pg-faq-card, .pg-links-card, .pg-tracker-card {
    background: #fff; border-radius: 24px; padding: 32px;
    border: 1px solid var(--border, #E2E8F0);
    box-shadow: 0 10px 40px rgba(15,61,92,.06);
}
.pg-steps-card h3, .pg-faq-card h3, .pg-links-card h3, .pg-tracker-card h3 {
    font-size: 19px; color: var(--primary-dark, #0F3D5C); margin-bottom: 24px; letter-spacing: -.01em;
    display: flex; align-items: center; gap: 10px;
}

.pg-step { display: flex; gap: 16px; position: relative; padding-bottom: 22px; }
.pg-step:last-child { padding-bottom: 0; }
.pg-step:not(:last-child)::after {
    content: ''; position: absolute; left: 22px; top: 46px; bottom: 0;
    width: 2px; background: linear-gradient(to bottom, #C9A227, rgba(201,162,39,.1));
}
.pg-step-num {
    min-width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; font-weight: 900; font-size: 17px;
    display: grid; place-items: center;
    box-shadow: 0 8px 20px rgba(201,162,39,.35);
    position: relative; z-index: 1; transition: .4s cubic-bezier(.34,1.56,.64,1);
}
.pg-step:hover .pg-step-num { transform: scale(1.1) rotate(-6deg); }
.pg-step-content h4 { font-size: 15px; color: var(--primary-dark, #0F3D5C); margin-bottom: 4px; letter-spacing: -.01em; }
.pg-step-content p { font-size: 13.5px; color: var(--text-muted, #64748B); line-height: 1.55; }
.pg-step-content .pg-sla {
    display: inline-flex; align-items: center; gap: 5px;
    margin-top: 6px; padding: 2px 10px; border-radius: 50px;
    background: rgba(16,185,129,.1); color: #059669; font-size: 11px; font-weight: 700;
    letter-spacing: .5px;
}
.pg-step-content .pg-sla svg { font-size: 11px; }

/* ===== STATUS TRACKER ===== */
.pg-tracker-card { padding: 28px; }
.pg-tracker-card h3 { margin-bottom: 20px; }
.pg-tracker-bar {
    display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
    position: relative;
}
.pg-trk-step {
    flex: 1; height: 6px; border-radius: 4px;
    background: #E2E8F0; position: relative;
    transition: background .6s;
}
.pg-trk-step.done { background: linear-gradient(90deg, #10B981, #34D399); }
.pg-trk-step.current { background: linear-gradient(90deg, #C9A227, #E8C55A); animation: pgTrkPulse 2s ease-in-out infinite; }
@keyframes pgTrkPulse { 0%,100% { opacity: 1; } 50% { opacity: .6; } }
.pg-tracker-labels { display: flex; justify-content: space-between; font-size: 10.5px; font-weight: 700; color: #64748B; letter-spacing: .6px; text-transform: uppercase; }
body.pg-dark .pg-trk-step { background: #334155; }
.pg-tracker-note { font-size: 12px; color: #64748B; line-height: 1.55; margin-top: 14px; padding: 10px 14px; background: rgba(201,162,39,.08); border-radius: 10px; border-left: 3px solid #C9A227; }

/* ===== LINKS CARD ===== */
.pg-links-list { display: flex; flex-direction: column; gap: 10px; }
.pg-link-item {
    display: flex; align-items: center; gap: 12px; padding: 12px 14px;
    border-radius: 12px; background: #F7F9FC; text-decoration: none;
    color: #0F3D5C; font-size: 13.5px; font-weight: 600;
    transition: .25s; border: 1px solid transparent;
}
body.pg-dark .pg-link-item { background: #0F172A; color: #E2E8F0; }
.pg-link-item:hover { background: rgba(201,162,39,.1); border-color: rgba(201,162,39,.3); transform: translateX(4px); }
.pg-link-ic {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    background: rgba(201,162,39,.15); color: #C9A227;
    display: grid; place-items: center; font-size: 16px;
}
.pg-link-item span { flex: 1; }
.pg-link-item .pg-arrow { color: #C9A227; display: inline-flex; }

/* ===== FAQ ===== */
.pg-faq-card { padding: 28px; }
.pg-faq-card h3 { margin-bottom: 20px; }
.pg-faq { border: 1px solid var(--border, #E2E8F0); border-radius: 14px; margin-bottom: 10px; overflow: hidden; transition: .3s; }
.pg-faq:last-child { margin-bottom: 0; }
.pg-faq[open] { border-color: rgba(201,162,39,.4); box-shadow: 0 6px 18px rgba(201,162,39,.1); }
.pg-faq summary {
    padding: 16px 20px; cursor: pointer; font-weight: 700; font-size: 14px;
    color: var(--text-dark, #1E293B); list-style: none;
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    transition: .25s;
}
.pg-faq summary::-webkit-details-marker { display: none; }
.pg-faq summary:hover { color: #0F3D5C; }
.pg-faq summary .pg-faq-plus {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
    background: rgba(201,162,39,.12); color: #C9A227;
    display: grid; place-items: center; font-size: 16px; font-weight: 900;
    transition: .3s cubic-bezier(.34,1.56,.64,1);
}
.pg-faq[open] summary .pg-faq-plus {
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; transform: rotate(45deg);
}
.pg-faq p {
    padding: 0 20px 18px; font-size: 13.5px; color: var(--text-muted, #64748B); line-height: 1.65;
    animation: pgFaqIn .3s cubic-bezier(.16,1,.3,1);
}
@keyframes pgFaqIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }

.pg-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600; animation: pgAlertIn .45s cubic-bezier(.16,1,.3,1);
    display: flex; gap: 12px; align-items: center;
}
@keyframes pgAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.pg-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444; }
body.pg-dark .pg-alert.danger { background: rgba(239,68,68,.15); color: #FCA5A5; }

/* ===== BACK TO TOP ===== */
.pg-back-top {
    position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px;
    border-radius: 50%; background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; display: grid; place-items: center;
    font-size: 22px; box-shadow: 0 10px 30px rgba(201,162,39,.4);
    cursor: pointer; border: none; z-index: 999;
    opacity: 0; pointer-events: none; transform: translateY(20px);
    transition: .4s cubic-bezier(.16,1,.3,1);
}
.pg-back-top.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.pg-back-top:hover { transform: translateY(-4px) scale(1.05); }

.pg-reveal { opacity: 0; transform: translateY(30px); transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
.pg-reveal.pg-in { opacity: 1; transform: none; }

@media print {
    .pg-hero, .pg-side, .pg-back-top, .pg-dark-toggle, .pg-progress { display: none !important; }
    .pg-body { padding: 20px 0; }
    .pg-form-wrap { box-shadow: none; border: 1px solid #000; }
}
</style>

<div class="pg-progress"><div class="pg-progress-bar" id="pgProg"></div></div>

<section class="pg-hero">
    <div class="pg-hero-aurora"></div>
    <div class="pg-hero-grid"></div>
    <div class="pg-float-ic pg-fi1"><?= $ic['mail'] ?></div>
    <div class="pg-float-ic pg-fi2"><?= $ic['message_circle'] ?></div>
    <div class="pg-float-ic pg-fi3"><?= $ic['send'] ?></div>
    <div class="pg-float-ic pg-fi4"><?= $ic['edit'] ?></div>
    <div class="container">
        <div class="pg-hero-inner">
            <span class="pg-hero-badge">
                <span class="pg-hero-badge-dot"></span>
                Layanan Aspirasi Publik
            </span>
            <h1>Pengaduan & <span class="pg-grad">Kritik Membangun</span></h1>
            <p class="pg-hero-lead">
                Masukan Anda adalah bahan bakar peningkatan mutu kampus kami.
                Identitas Anda kami jaga kerahasiaannya.
            </p>
            <div class="pg-trust">
                <div class="pg-trust-item"><span><?= $ic['check'] ?></span> 100% Rahasia</div>
                <div class="pg-trust-item"><span><?= $ic['check'] ?></span> Respon 1-3 Hari</div>
                <div class="pg-trust-item"><span><?= $ic['check'] ?></span> Terenkripsi</div>
                <div class="pg-trust-item"><span><?= $ic['check'] ?></span> Gratis</div>
            </div>
        </div>
    </div>
</section>

<section class="pg-body">
    <div class="container">
        <div class="pg-grid">
            <div class="pg-form-wrap pg-reveal">
                <div class="pg-form-head">
                    <h3><?= $ic['edit_3'] ?> Formulir Pengaduan</h3>
                    <p>Isi dengan lengkap dan jujur. Setiap masukan akan kami telaah dengan serius.</p>
                </div>

                <?php if ($success): ?>
                    <div class="pg-success" id="pgSuccessState">
                        <div class="pg-success-ic"><?= $ic['check'] ?></div>
                        <h3>Pengaduan Terkirim!</h3>
                        <p>Terima kasih atas masukan Anda. Tim LPM akan menindaklanjuti dalam 1-3 hari kerja. Notifikasi akan dikirim ke email Anda.</p>
                        <a href="/publik/berita.php" class="btn btn-primary" style="padding:10px 22px;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#0F3D5C;border-radius:50px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:8px;box-shadow:0 8px 22px rgba(201,162,39,.4);">Lihat Berita Terbaru <?= $ic['arrow_right'] ?></a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="pg-alert danger"><?= $ic['alert_circle'] ?> <?= Security::e($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" id="pgForm" enctype="multipart/form-data">
                        <?= Security::csrfField() ?>
                        <input type="text" name="website_hp" style="display:none !important;" tabindex="-1" autocomplete="off">
                        
                        <div class="pg-row">
                            <div class="pg-field">
                                <input type="text" name="nama" id="pgNama" placeholder=" " required autocomplete="name">
                                <label for="pgNama">Nama Lengkap *</label>
                                <span class="pg-field-ic"><?= $ic['user'] ?></span>
                            </div>
                            <div class="pg-field">
                                <input type="email" name="email" id="pgEmail" placeholder=" " required autocomplete="email">
                                <label for="pgEmail">Email Aktif *</label>
                                <span class="pg-field-ic"><?= $ic['mail'] ?></span>
                            </div>
                        </div>

                        <div class="pg-row">
                            <div class="pg-field">
                                <input type="text" name="instansi" id="pgInstansi" placeholder=" " autocomplete="organization">
                                <label for="pgInstansi">Instansi (Opsional)</label>
                                <span class="pg-field-ic"><?= $ic['building'] ?></span>
                            </div>
                            <div class="pg-field">
                                <select name="kategori" id="pgKategori" required>
                                    <option value="" disabled selected></option>
                                    <option value="Akademik">Akademik</option>
                                    <option value="Fasilitas">Fasilitas</option>
                                    <option value="Pelayanan">Pelayanan</option>
                                    <option value="Keuangan">Keuangan</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                                <label for="pgKategori">Kategori Pengaduan *</label>
                                <span class="pg-field-ic"><?= $ic['folder'] ?></span>
                            </div>
                        </div>

                        <div class="pg-field">
                            <input type="text" name="subjek" id="pgSubjek" placeholder=" " required maxlength="120">
                            <label for="pgSubjek">Subjek Pengaduan *</label>
                            <span class="pg-field-ic"><?= $ic['tag'] ?></span>
                        </div>

                        <div class="pg-field">
                            <textarea name="isi" id="pgIsi" placeholder=" " required maxlength="2000"></textarea>
                            <label for="pgIsi">Uraian Lengkap * (min. 20 karakter)</label>
                        </div>

                        <div class="pg-field" style="margin-bottom:20px;">
                            <label for="pgRating" style="position:static;transform:none;font-size:13px;color:#0F3D5C;font-weight:700;margin-bottom:8px;display:block;letter-spacing:.4px;">
                                <?= $ic['star'] ?> Seberapa puas Anda dengan layanan kami saat ini?
                            </label>
                            <div class="pg-rating" id="pgRating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" class="pg-rating-star" data-val="<?= $i ?>" aria-label="<?= $i ?> bintang"><?= $ic['star'] ?></button>
                                <?php endfor; ?>
                                <span class="pg-rating-label" id="pgRatingLabel">Belum dinilai</span>
                                <input type="hidden" name="rating" id="pgRatingVal" value="0">
                            </div>
                        </div>

                        <div class="pg-field" style="margin-bottom:20px;">
                            <label style="position:static;transform:none;font-size:13px;color:#0F3D5C;font-weight:700;margin-bottom:8px;display:block;letter-spacing:.4px;">
                                <?= $ic['paperclip'] ?> Lampiran Pendukung (Opsional, maks 5MB per file)
                            </label>
                            <div class="pg-upload" id="pgUpload">
                                <input type="file" id="pgFileInput" multiple accept="image/*,application/pdf,.doc,.docx">
                                <div class="pg-upload-ic"><?= $ic['upload'] ?></div>
                                <p><strong>Klik untuk memilih</strong> atau seret file ke sini</p>
                                <p style="font-size:11.5px;opacity:.7;">JPG, PNG, PDF, DOC (maks 5MB per file)</p>
                                <div class="pg-upload-list" id="pgUploadList"></div>
                            </div>
                        </div>

                        <div class="pg-captcha">
                            <span class="pg-captcha-ic"><?= $ic['shield'] ?></span>
                            <span class="pg-captcha-q">Verifikasi: Berapakah <?= $_SESSION['_captcha_q'] ?> ?</span>
                            <input type="text" name="captcha_ans" required placeholder="?" autocomplete="off">
                        </div>

                        <button type="submit" class="pg-submit" id="pgSubmit">
                            <?= $ic['send'] ?> Kirim Pengaduan Sekarang
                            <span class="pg-kbd">Ctrl + ↵</span>
                        </button>
                        <div class="pg-secure">
                            <span class="pg-secure-ic"><?= $ic['lock'] ?></span>
                            <span>Data Anda terenkripsi dan hanya diakses oleh petugas LPM yang berwenang.</span>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="pg-side">
                <div class="pg-tracker-card pg-reveal">
                    <h3><?= $ic['activity'] ?> Status Alur Tindak Lanjut</h3>
                    <div class="pg-tracker-bar">
                        <div class="pg-trk-step done"></div>
                        <div class="pg-trk-step current"></div>
                        <div class="pg-trk-step"></div>
                        <div class="pg-trk-step"></div>
                    </div>
                    <div class="pg-tracker-labels">
                        <span>Terima</span>
                        <span>Telaah</span>
                        <span>Tindak</span>
                        <span>Selesai</span>
                    </div>
                    <div class="pg-tracker-note">
                        <strong style="color:#C9A227;">Fase saat ini:</strong> Menunggu pengaduan baru dari Anda untuk memulai proses.
                    </div>
                </div>

                <div class="pg-steps-card pg-reveal">
                    <h3><?= $ic['compass'] ?> Alur Tindak Lanjut</h3>
                    <div class="pg-step">
                        <div class="pg-step-num">1</div>
                        <div class="pg-step-content">
                            <h4>Pengaduan Diterima</h4>
                            <p>Laporan masuk otomatis ke sistem LPM dan tercatat dengan nomor tiket.</p>
                            <span class="pg-sla"><?= $ic['zap'] ?> Instan</span>
                        </div>
                    </div>
                    <div class="pg-step">
                        <div class="pg-step-num">2</div>
                        <div class="pg-step-content">
                            <h4>Verifikasi & Telaah</h4>
                            <p>Petugas menelaah substansi laporan secara mendalam.</p>
                            <span class="pg-sla"><?= $ic['clock'] ?> Maks 3 Hari</span>
                        </div>
                    </div>
                    <div class="pg-step">
                        <div class="pg-step-num">3</div>
                        <div class="pg-step-content">
                            <h4>Tindak Lanjut</h4>
                            <p>Laporan diteruskan ke unit terkait untuk ditindaklanjuti.</p>
                            <span class="pg-sla"><?= $ic['refresh_cw'] ?> Koordinasi</span>
                        </div>
                    </div>
                    <div class="pg-step">
                        <div class="pg-step-num">4</div>
                        <div class="pg-step-content">
                            <h4>Umpan Balik</h4>
                            <p>Status dan hasil tindak lanjut dikirim ke email pelapor.</p>
                            <span class="pg-sla"><?= $ic['mail'] ?> Notifikasi</span>
                        </div>
                    </div>
                </div>

                <div class="pg-links-card pg-reveal">
                    <h3><?= $ic['link_2'] ?> Tautan Terkait</h3>
                    <div class="pg-links-list">
                        <a href="/publik/akreditasi.php" class="pg-link-item">
                            <span class="pg-link-ic"><?= $ic['award'] ?></span>
                            <span>Status Akreditasi Prodi</span>
                            <span class="pg-arrow"><?= $ic['arrow_right'] ?></span>
                        </a>
                        <a href="/publik/dokumen.php" class="pg-link-item">
                            <span class="pg-link-ic"><?= $ic['file_text'] ?></span>
                            <span>Dokumen SPMI & Kebijakan</span>
                            <span class="pg-arrow"><?= $ic['arrow_right'] ?></span>
                        </a>
                        <a href="/publik/berita.php" class="pg-link-item">
                            <span class="pg-link-ic"><?= $ic['newspaper'] ?></span>
                            <span>Berita & Pengumuman LPM</span>
                            <span class="pg-arrow"><?= $ic['arrow_right'] ?></span>
                        </a>
                        <a href="mailto:lpm@kampus.ac.id" class="pg-link-item">
                            <span class="pg-link-ic"><?= $ic['mail'] ?></span>
                            <span>Email Langsung: lpm@kampus.ac.id</span>
                            <span class="pg-arrow"><?= $ic['arrow_right'] ?></span>
                        </a>
                    </div>
                </div>

                <div class="pg-faq-card pg-reveal">
                    <h3><?= $ic['help_circle'] ?> Pertanyaan Umum</h3>
                    <details class="pg-faq">
                        <summary>Apakah identitas saya dirahasiakan?<span class="pg-faq-plus">+</span></summary>
                        <p>Ya, 100% dirahasiakan. Identitas pelapor hanya dapat diakses oleh petugas LPM yang berwenang dan tidak akan dipublikasikan dalam bentuk apa pun.</p>
                    </details>
                    <details class="pg-faq">
                        <summary>Berapa lama tanggapan diberikan?<span class="pg-faq-plus">+</span></summary>
                        <p>Kami berupaya memberikan tanggapan awal dalam 1-3 hari kerja dan tindak lanjut final maksimal 14 hari kerja sejak laporan diterima.</p>
                    </details>
                    <details class="pg-faq">
                        <summary>Bisakah saya mengirim tanpa nama?<span class="pg-faq-plus">+</span></summary>
                        <p>Kolom nama wajib diisi untuk keperluan verifikasi, namun Anda dapat meminta anonimitas dalam isi laporan dan kami akan menghormatinya.</p>
                    </details>
                    <details class="pg-faq">
                        <summary>Apakah ada biaya untuk layanan ini?<span class="pg-faq-plus">+</span></summary>
                        <p>Tidak sama sekali. Layanan pengaduan LPM sepenuhnya gratis dan terbuka untuk seluruh pemangku kepentingan.</p>
                    </details>
                    <details class="pg-faq">
                        <summary>File apa saja yang dapat dilampirkan?<span class="pg-faq-plus">+</span></summary>
                        <p>Anda dapat melampirkan foto, dokumen PDF, atau file Word (DOC/DOCX) sebagai bukti pendukung, maksimal 5MB per file.</p>
                    </details>
                </div>
            </div>
        </div>
    </div>
</section>

<button class="pg-dark-toggle" id="pgDarkToggle" aria-label="Toggle dark mode"><?= $ic['moon'] ?></button>


<script>
(function () {
    'use strict';

    var ICON = {
        check: <?= json_encode($ic['check']) ?>,
        star: <?= json_encode($ic['star']) ?>,
        x: <?= json_encode($ic['x']) ?>,
        file: <?= json_encode($ic['file']) ?>,
        image: <?= json_encode($ic['image']) ?>,
        file_pdf: <?= json_encode($ic['file_pdf']) ?>,
        moon: <?= json_encode($ic['moon']) ?>,
        sun: <?= json_encode($ic['sun']) ?>
    };

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) { en.target.classList.add('pg-in'); io.unobserve(en.target); }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.pg-reveal').forEach(function (el) { io.observe(el); });

    var prog = document.getElementById('pgProg');
    var backTop = document.getElementById('pgBackTop');
    window.addEventListener('scroll', function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        var p = max > 0 ? (window.scrollY / max) * 100 : 0;
        if (prog) prog.style.width = p + '%';
        if (backTop) backTop.classList.toggle('visible', window.scrollY > 500);
    }, { passive: true });
    if (backTop) backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    var darkBtn = document.getElementById('pgDarkToggle');
    var savedDark = localStorage.getItem('pgDark') === '1';
    if (savedDark) {
        document.body.classList.add('pg-dark');
        if (darkBtn) darkBtn.innerHTML = ICON.sun;
    }
    if (darkBtn) darkBtn.addEventListener('click', function () {
        var on = document.body.classList.toggle('pg-dark');
        localStorage.setItem('pgDark', on ? '1' : '0');
        this.innerHTML = on ? ICON.sun : ICON.moon;
    });

    var form = document.getElementById('pgForm');
    if (form) {
        var STORAGE_KEY = 'pg_form_draft_v2';
        
        try {
            var draft = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
            ['nama', 'email', 'instansi', 'subjek', 'isi', 'kategori'].forEach(function (name) {
                if (draft[name]) {
                    var el = form.querySelector('[name="' + name + '"]');
                    if (el) el.value = draft[name];
                }
            });
            if (draft.rating) {
                setRating(parseInt(draft.rating, 10));
            }
        } catch (e) {}

        var charCountIsi = document.createElement('div');
        charCountIsi.className = 'pg-char-count';
        var isiField = form.querySelector('[name="isi"]');
        if (isiField) {
            isiField.parentElement.style.position = 'relative';
            isiField.parentElement.appendChild(charCountIsi);
        }

        var charCountSubj = document.createElement('div');
        charCountSubj.className = 'pg-char-count';
        var subjField = form.querySelector('[name="subjek"]');
        if (subjField) {
            subjField.parentElement.style.position = 'relative';
            subjField.parentElement.appendChild(charCountSubj);
        }

        function saveDraft() {
            var d = {};
            ['nama', 'email', 'instansi', 'subjek', 'isi', 'kategori'].forEach(function (name) {
                var el = form.querySelector('[name="' + name + '"]');
                if (el) d[name] = el.value;
            });
            d.rating = document.getElementById('pgRatingVal').value;
            localStorage.setItem(STORAGE_KEY, JSON.stringify(d));
        }

        form.addEventListener('input', function (e) {
            saveDraft();

            if (e.target.name === 'isi') {
                var len = e.target.value.length;
                charCountIsi.textContent = len + '/2000';
                if (len < 20) charCountIsi.style.color = '#EF4444';
                else if (len > 1800) charCountIsi.style.color = '#F59E0B';
                else charCountIsi.style.color = '#10B981';
            }

            if (e.target.name === 'subjek') {
                var len = e.target.value.length;
                charCountSubj.textContent = len + '/120';
                if (len > 110) charCountSubj.style.color = '#F59E0B';
                else charCountSubj.style.color = '#64748B';
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
        });

        if (isiField) { isiField.dispatchEvent(new Event('input')); }
        if (subjField) { subjField.dispatchEvent(new Event('input')); }

        var stars = form.querySelectorAll('.pg-rating-star');
        var ratingLabel = document.getElementById('pgRatingLabel');
        var ratingInput = document.getElementById('pgRatingVal');
        var ratingTexts = ['', 'Sangat Tidak Puas', 'Tidak Puas', 'Cukup', 'Puas', 'Sangat Puas'];
        
        function setRating(val) {
            stars.forEach(function (s, i) {
                s.classList.toggle('active', i < val);
            });
            if (ratingLabel) ratingLabel.textContent = ratingTexts[val] || 'Belum dinilai';
            if (ratingInput) ratingInput.value = val;
        }
        stars.forEach(function (s) {
            s.addEventListener('click', function () {
                var v = parseInt(this.getAttribute('data-val'), 10);
                var current = parseInt(ratingInput.value, 10);
                setRating(v === current ? 0 : v);
                saveDraft();
            });
        });

        var uploadArea = document.getElementById('pgUpload');
        var fileInput = document.getElementById('pgFileInput');
        var uploadList = document.getElementById('pgUploadList');
        var uploadedFiles = [];

        function formatSize(bytes) {
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return bytes + ' B';
        }
        function iconForFile(file) {
            if (file.type.startsWith('image/')) return ICON.image;
            if (file.type === 'application/pdf') return ICON.file_pdf;
            return ICON.file;
        }
        function renderFiles() {
            uploadList.innerHTML = '';
            uploadedFiles.forEach(function (f, i) {
                var item = document.createElement('div');
                item.className = 'pg-upload-item';
                item.innerHTML =
                    '<span class="pg-uf-ic">' + iconForFile(f) + '</span>' +
                    '<span class="pg-uf-name">' + f.name + '</span>' +
                    '<span class="pg-uf-size">' + formatSize(f.size) + '</span>' +
                    '<button type="button" class="pg-uf-rm" data-idx="' + i + '" aria-label="Hapus">' + ICON.x + '</button>';
                uploadList.appendChild(item);
            });
        }
        function handleFiles(files) {
            for (var i = 0; i < files.length; i++) {
                var f = files[i];
                if (f.size > 5 * 1024 * 1024) {
                    alert('File "' + f.name + '" melebihi 5MB.');
                    continue;
                }
                if (uploadedFiles.length >= 5) {
                    alert('Maksimal 5 file lampiran.');
                    break;
                }
                uploadedFiles.push(f);
            }
            renderFiles();
            var dt = new DataTransfer();
            uploadedFiles.forEach(function (f) { dt.items.add(f); });
            fileInput.files = dt.files;
        }
        if (uploadArea && fileInput) {
            uploadArea.addEventListener('click', function (e) {
                if (!e.target.closest('.pg-uf-rm')) fileInput.click();
            });
            uploadArea.addEventListener('dragover', function (e) {
                e.preventDefault(); uploadArea.classList.add('dragover');
            });
            uploadArea.addEventListener('dragleave', function () {
                uploadArea.classList.remove('dragover');
            });
            uploadArea.addEventListener('drop', function (e) {
                e.preventDefault(); uploadArea.classList.remove('dragover');
                handleFiles(e.dataTransfer.files);
            });
            fileInput.addEventListener('change', function () { handleFiles(this.files); });
            uploadList.addEventListener('click', function (e) {
                var btn = e.target.closest('.pg-uf-rm');
                if (btn) {
                    var idx = parseInt(btn.getAttribute('data-idx'), 10);
                    uploadedFiles.splice(idx, 1);
                    renderFiles();
                    var dt = new DataTransfer();
                    uploadedFiles.forEach(function (f) { dt.items.add(f); });
                    fileInput.files = dt.files;
                }
            });
        }

        form.addEventListener('submit', function (e) {
            var firstInvalid = null;
            var isValid = true;

            form.querySelectorAll('[required]').forEach(function (el) {
                if (el.type === 'hidden' || el.name === 'website_hp') return;
                if (!el.value.trim()) {
                    isValid = false;
                    el.classList.add('error');
                    if (!firstInvalid) firstInvalid = el;
                } else {
                    el.classList.remove('error');
                }
            });

            var emailEl = form.querySelector('[name="email"]');
            if (emailEl && emailEl.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value)) {
                isValid = false;
                emailEl.classList.add('error');
                if (!firstInvalid) firstInvalid = emailEl;
            }

            var isiEl = form.querySelector('[name="isi"]');
            if (isiEl && isiEl.value.trim().length < 20) {
                isValid = false;
                isiEl.classList.add('error');
                if (!firstInvalid) firstInvalid = isiEl;
            }

            var catEl = form.querySelector('[name="kategori"]');
            if (catEl && !catEl.value) {
                isValid = false;
                catEl.classList.add('error');
                if (!firstInvalid) firstInvalid = catEl;
            }

            if (!isValid) {
                e.preventDefault();
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.parentElement.classList.add('shake');
                    setTimeout(function () { firstInvalid.parentElement.classList.remove('shake'); }, 500);
                    firstInvalid.focus();
                }
                return;
            }

            localStorage.removeItem(STORAGE_KEY);
            
            var btn = document.getElementById('pgSubmit');
            if (btn && !btn.dataset.busy) {
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.innerHTML = '<?= $ic['loader'] ?> Mengirim...';
            }
        });

        form.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
    }

    var successState = document.getElementById('pgSuccessState');
    if (successState) {
        var colors = ['#C9A227', '#E8C55A', '#10B981', '#3B82F6', '#F59E0B'];
        for (var i = 0; i < 60; i++) {
            var conf = document.createElement('div');
            conf.style.cssText = 'position:absolute;width:10px;height:10px;opacity:0;animation:pgConfettiFall 3s ease-out forwards;pointer-events:none;';
            conf.style.left = Math.random() * 100 + '%';
            conf.style.top = '-20px';
            conf.style.background = colors[Math.floor(Math.random() * colors.length)];
            conf.style.borderRadius = Math.random() > .5 ? '50%' : '2px';
            conf.style.animationDelay = (Math.random() * 1.5) + 's';
            conf.style.animationDuration = (2 + Math.random() * 2) + 's';
            successState.appendChild(conf);
        }
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>