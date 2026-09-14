<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/lang-autotranslate.php';
lpm_autotranslate_start();
Security::sendHeaders();

$currentPage = basename($_SERVER['PHP_SELF']);
$brandUtama = Site::setting('brand_utama', 'LPM');
$brandAksen = Site::setting('brand_aksen', 'Kampus');
$favPath = Site::setting('favicon_path');
if ($favPath && file_exists(PATH_UPLOAD . $favPath)) {
    $favHref = '/uploads/' . $favPath;
} else {
    $initial = mb_substr($brandUtama, 0, 1);
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0%' stop-color='%230F3D5C'/><stop offset='100%' stop-color='%23C9A227'/></linearGradient></defs><rect width='64' height='64' rx='14' fill='url(%23g)'/><text x='32' y='44' font-family='Arial Black' font-size='34' font-weight='900' text-anchor='middle' fill='white'>$initial</text></svg>";
    $favHref = 'data:image/svg+xml;utf8,' . $svg;
}

// ===== MULTI-BAHASA TICKER ITEMS =====
$tickItems = [
    ['id' => '🎓 Tracer Study Alumni telah dibuka — isi data karier Anda',
     'en' => '🎓 Alumni Tracer Study is open — fill in your career data'],
    ['id' => '📅 Jadwal AMI Semester berjalan telah dirilis',
     'en' => '📅 Current Semester AMI Schedule has been released'],
    ['id' => '🏆 Selamat kepada Prodi peraih predikat Unggul',
     'en' => '🏆 Congratulations to Programs achieving Excellent status'],
    ['id' => '📄 Dokumen mutu publik dapat diunduh gratis',
     'en' => '📄 Public quality documents available for free download'],
    ['id' => '🤝 LPM terbuka untuk konsultasi penjaminan mutu',
     'en' => '🤝 LPM is open for quality assurance consultations'],
];
$currentLang = lang();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Lembaga Penjaminan Mutu — Sistem Informasi Penjaminan Mutu Kampus">
    <title><?= $pageTitle ?? t('home', 'Beranda') ?> | <?= Security::e($brandUtama) ?> <?= Security::e($brandAksen) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= $favHref ?>">
    <link rel="shortcut icon" href="<?= $favHref ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="alternate" hreflang="id" href="<?= currentLangUrl('id') ?>">
    <link rel="alternate" hreflang="en" href="<?= currentLangUrl('en') ?>">
    <link rel="alternate" hreflang="x-default" href="<?= currentLangUrl('id') ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* ============================================================
           HEADER ULTIMATE v8.0 — Ticker • Pulse Logo • Mega Menu • Lang Switcher
        ============================================================ */

        /* ===== ANNOUNCEMENT TICKER BAR ===== */
        .hd-topbar {
            position: fixed; top: 0; left: 0; right: 0; height: 40px; z-index: 1002;
            background: linear-gradient(90deg, #061D2E, #0F3D5C 50%, #061D2E);
            border-bottom: 1px solid rgba(201,162,39,.25);
            overflow: hidden; display: flex; align-items: center;
            transition: transform .45s var(--ease-out);
        }
        body.hd-scrolled .hd-topbar { transform: translateY(-100%); }
        .hd-topbar::before, .hd-topbar::after {
            content: ''; position: absolute; top: 0; bottom: 0; width: 90px; z-index: 2; pointer-events: none;
        }
        .hd-topbar::before { left: 0; background: linear-gradient(90deg, #061D2E, transparent); }
        .hd-topbar::after { right: 0; background: linear-gradient(270deg, #061D2E, transparent); }
        .hd-topbar-track {
            display: flex; gap: 64px; width: max-content;
            animation: hdTicker 38s linear infinite;
        }
        .hd-topbar:hover .hd-topbar-track { animation-play-state: paused; }
        .hd-topbar-item {
            color: rgba(255,255,255,.8); font-size: 12px; font-weight: 600;
            white-space: nowrap; display: flex; align-items: center; gap: 10px;
            text-decoration: none; transition: .25s;
        }
        .hd-topbar-item:hover { color: var(--accent-light); }
        .hd-topbar-item b { color: var(--accent-light); font-size: 9px; }
        @keyframes hdTicker { to { transform: translateX(-50%); } }

        .navbar { top: 40px !important; }
        body.hd-scrolled .navbar { top: 0 !important; }

        /* ===== LOGO PULSE RING ===== */
        .brand-logo { position: relative; }
        .brand-logo::after {
            content: ''; position: absolute; inset: 0; border-radius: 12px;
            animation: hdLogoPulse 3s ease infinite; pointer-events: none;
        }
        @keyframes hdLogoPulse {
            0% { box-shadow: 0 0 0 0 rgba(201,162,39,.5); }
            70% { box-shadow: 0 0 0 12px rgba(201,162,39,0); }
            100% { box-shadow: 0 0 0 0 rgba(201,162,39,0); }
        }

        /* ===== NAV LINKS: dot indicator aktif ===== */
        .nav-links a.active::before {
            content: ''; position: absolute; top: 6px; right: 8px;
            width: 5px; height: 5px; border-radius: 50%;
            background: var(--accent); box-shadow: 0 0 8px var(--accent);
        }

        /* ===== MEGA MENU LAYANAN (2 kolom) ===== */
        .nav-dd { position: relative; }
        .nav-dd > button {
            padding: 9px 16px; color: var(--text-dark); font-weight: 600; font-size: 14.5px;
            border-radius: 10px; transition: var(--transition); background: none; border: none;
            cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: 7px;
            position: relative;
        }
        .nav-dd > button::after {
            content: ''; position: absolute; left: 16px; right: 16px; bottom: 4px; height: 2px;
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
            border-radius: 2px; transform: scaleX(0); transform-origin: left; transition: transform .35s var(--ease-out);
        }
        .nav-dd > button:hover { color: var(--primary); }
        .nav-dd > button:hover::after, .nav-dd.open > button::after { transform: scaleX(1); }
        .nav-dd .caret { font-size: 9px; transition: transform .3s var(--ease-out); }
        .nav-dd.open .caret, .nav-dd:hover .caret { transform: rotate(180deg); }

        .nav-dd-menu {
            position: absolute; top: calc(100% + 14px); right: -120px; width: 620px;
            background: #fff; border-radius: 20px; box-shadow: 0 30px 80px rgba(15,61,92,.22);
            border: 1px solid var(--border); padding: 14px;
            opacity: 0; visibility: hidden; transform: translateY(14px) scale(.97);
            transform-origin: top right; transition: .3s var(--ease-out); z-index: 700;
            overflow: hidden;
        }
        .nav-dd-menu::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--primary), var(--accent));
            background-size: 200% 100%; animation: hdGradLine 4s linear infinite;
        }
        @keyframes hdGradLine { to { background-position: 200% 0; } }
        .nav-dd-menu::after { content: ''; position: absolute; top: -16px; right: 0; width: 100%; height: 16px; }
        .nav-dd:hover .nav-dd-menu, .nav-dd.open .nav-dd-menu { opacity: 1; visibility: visible; transform: none; }

        .hd-mm-head {
            padding: 10px 14px 12px; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px dashed var(--border); margin-bottom: 10px;
        }
        .hd-mm-head span { font-size: 10.5px; letter-spacing: 2.5px; color: var(--accent); font-weight: 800; }
        .hd-mm-head small { font-size: 11px; color: var(--text-muted); }
        .hd-mm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; }
        .hd-mm-grid a {
            display: flex; gap: 13px; align-items: flex-start; padding: 12px 14px; border-radius: 14px;
            text-decoration: none; color: var(--text-dark); transition: .22s;
            position: relative; overflow: hidden;
        }
        .hd-mm-grid a::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(90deg, rgba(201,162,39,.12), transparent);
            opacity: 0; transition: .25s;
        }
        .hd-mm-grid a:hover::before { opacity: 1; }
        .hd-mm-grid a:hover { transform: translateX(5px); }
        .hd-mm-ic {
            width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
            background: linear-gradient(135deg, rgba(15,61,92,.08), rgba(201,162,39,.14));
            display: grid; place-items: center; font-size: 17px;
            transition: .3s var(--ease-spring);
        }
        .hd-mm-grid a:hover .hd-mm-ic { transform: scale(1.12) rotate(-8deg); background: linear-gradient(135deg, var(--accent), var(--accent-light)); }
        .hd-mm-txt strong { display: block; font-size: 13.5px; font-weight: 700; }
        .hd-mm-txt small { display: block; color: var(--text-muted); font-weight: 500; font-size: 11.5px; margin-top: 2px; }
        .hd-mm-foot {
            margin-top: 10px; padding: 12px 14px; border-radius: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            text-decoration: none; color: #fff; transition: .3s;
        }
        .hd-mm-foot:hover { box-shadow: 0 10px 30px rgba(15,61,92,.4); transform: translateY(-2px); }
        .hd-mm-foot strong { font-size: 13.5px; display: block; }
        .hd-mm-foot small { font-size: 11.5px; opacity: .8; }
        @media (max-width: 1200px) {
            .nav-dd-menu { width: 320px; right: -60px; }
            .hd-mm-grid { grid-template-columns: 1fr; }
        }

        /* ===== LANGUAGE SWITCHER ===== */
        .lang-switcher {
            display: inline-flex; gap: 2px; padding: 3px;
            background: rgba(15,61,92,.06);
            border: 1px solid var(--border);
            border-radius: 50px;
        }
        .lang-btn {
            padding: 6px 14px; border-radius: 50px;
            font-size: 12px; font-weight: 700; letter-spacing: .5px;
            text-decoration: none; transition: .25s;
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--text-muted);
        }
        .lang-btn:hover { color: var(--primary); }
        .lang-btn.active {
            background: linear-gradient(135deg, #C9A227, #E8C55A);
            color: #092A40;
            box-shadow: 0 4px 12px rgba(201,162,39,.35);
        }

        /* ===== NAV CTA GLOW ===== */
        .nav-cta {
            padding: 10px 24px; font-size: 14px;
            box-shadow: 0 4px 16px rgba(15,61,92,.3);
            animation: hdCtaGlow 3.5s ease infinite;
        }
        @keyframes hdCtaGlow {
            0%, 100% { box-shadow: 0 4px 16px rgba(15,61,92,.3); }
            50% { box-shadow: 0 6px 26px rgba(201,162,39,.5); }
        }

        /* ===== NAVBAR RIGHT ACTIONS ===== */
        .nav-actions { display: flex; align-items: center; gap: 12px; }

        /* ===== HAMBURGER & DRAWER (stagger) ===== */
        .hamburger { display: none; background: none; border: none; cursor: pointer; width: 44px; height: 44px; border-radius: 12px; }
        .hamburger span { display: block; width: 22px; height: 2px; background: var(--primary); margin: 2.5px auto; border-radius: 2px; transition: .3s var(--ease-out); }
        .hamburger.active span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
        .mobile-drawer {
            position: fixed; top: 0; right: -320px; width: 300px; height: 100vh;
            background: #fff; z-index: 1200; box-shadow: var(--shadow-lg);
            transition: right .45s var(--ease-out); padding: 92px 26px 26px;
            display: flex; flex-direction: column; gap: 6px; overflow-y: auto;
        }
        .mobile-drawer.open { right: 0; }
        .mobile-drawer a { padding: 13px 16px; border-radius: 12px; text-decoration: none; color: var(--text-dark); font-weight: 600; transition: .25s; }
        .mobile-drawer.open a { animation: hdDrawerIn .5s var(--ease-out) backwards; }
        .mobile-drawer.open a:nth-child(1) { animation-delay: .05s; }
        .mobile-drawer.open a:nth-child(2) { animation-delay: .1s; }
        .mobile-drawer.open a:nth-child(3) { animation-delay: .15s; }
        .mobile-drawer.open a:nth-child(4) { animation-delay: .2s; }
        .mobile-drawer.open a:nth-child(5) { animation-delay: .25s; }
        .mobile-drawer.open a:nth-child(6) { animation-delay: .3s; }
        .mobile-drawer.open a:nth-child(7) { animation-delay: .35s; }
        .mobile-drawer.open a:nth-child(8) { animation-delay: .4s; }
        .mobile-drawer.open a:nth-child(9) { animation-delay: .45s; }
        .mobile-drawer.open a:nth-child(10) { animation-delay: .5s; }
        .mobile-drawer.open a:nth-child(11) { animation-delay: .55s; }
        .mobile-drawer.open a:nth-child(12) { animation-delay: .6s; }
        .mobile-drawer.open a:nth-child(13) { animation-delay: .65s; }
        @keyframes hdDrawerIn { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: none; } }
        .mobile-drawer a:hover, .mobile-drawer a.active { background: rgba(15, 61, 92, .07); color: var(--primary); transform: translateX(4px); }
        .mobile-drawer .btn { margin-top: 14px; }
        .drawer-overlay { position: fixed; inset: 0; background: rgba(9, 42, 64, .45); backdrop-filter: blur(3px); z-index: 1100; opacity: 0; visibility: hidden; transition: .35s; }
        .drawer-overlay.show { opacity: 1; visibility: visible; }
        .dw-label { padding: 14px 16px 6px; font-size: 11px; letter-spacing: 2px; color: var(--text-muted); font-weight: 800; }

        /* Mobile lang switcher */
        .dw-lang {
            display: flex; gap: 8px; padding: 12px 16px; margin-bottom: 8px;
            background: linear-gradient(135deg, rgba(201,162,39,.08), rgba(15,61,92,.04));
            border-radius: 12px; border: 1px solid rgba(201,162,39,.2);
        }
        .dw-lang a {
            flex: 1; text-align: center; padding: 8px !important;
            background: rgba(255,255,255,.5); border-radius: 8px; font-size: 13px;
        }
        .dw-lang a.active {
            background: linear-gradient(135deg, #C9A227, #E8C55A) !important;
            color: #092A40 !important; font-weight: 800;
            transform: none !important;
        }

        @media (max-width: 992px) {
            .hamburger { display: grid; place-items: center; }
            .hd-topbar { height: 34px; }
            .navbar { top: 34px !important; }
            .lang-switcher { display: none; } /* sembunyikan di desktop navbar, muncul di drawer */
        }

/* ===== NAVBAR RAPI v8.3 — anti wrap, sejajar ===== */
.navbar .container { display: flex; align-items: center; gap: 14px; }
.brand { flex-shrink: 0; }
.brand-text { white-space: nowrap; font-size: 17px; }
.nav-links { gap: 0; }
.nav-links > li > a,
.nav-dd > button {
    padding: 9px 11px; font-size: 13.5px; white-space: nowrap;
}
.nav-cta { white-space: nowrap; padding: 10px 18px; font-size: 13.5px; }
.nav-actions { flex-shrink: 0; display: flex; align-items: center; gap: 10px; }
.lang-switcher { flex-shrink: 0; }
.lang-btn { white-space: nowrap; padding: 6px 12px; font-size: 11.5px; letter-spacing: .5px; }

@media (max-width: 1360px) {
    .brand-text { font-size: 15px; }
    .nav-links > li > a, .nav-dd > button { padding: 9px 8px; font-size: 12.5px; }
    .nav-cta { padding: 9px 14px; font-size: 12.5px; }
    .lang-btn { padding: 6px 9px; font-size: 10.5px; }
}
    </style>
</head>
<body>

<!-- ===== ANNOUNCEMENT TICKER (Multi-bahasa) ===== -->
<div class="hd-topbar">
    <div class="hd-topbar-track">
        <?php for ($i = 0; $i < 2; $i++):
            foreach ($tickItems as $ti): ?>
                <a class="hd-topbar-item" href="/publik/berita.php"><b>✦</b> <?= $ti[$currentLang] ?? $ti['id'] ?></a>
        <?php endforeach; endfor; ?>
    </div>
</div>

<nav class="navbar">
    <div class="container">
        <?= Site::brand('publik') ?>
        <ul class="nav-links">
            <li><a href="/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"><?= t('home', 'Beranda') ?></a></li>
            <li><a href="/publik/profil.php" class="<?= $currentPage === 'profil.php' ? 'active' : '' ?>"><?= t('profile', 'Profil') ?></a></li>
            <li><a href="/publik/akreditasi.php" class="<?= $currentPage === 'akreditasi.php' ? 'active' : '' ?>"><?= t('accreditation', 'Akreditasi') ?></a></li>
            <li><a href="/publik/dokumen.php" class="<?= $currentPage === 'dokumen.php' ? 'active' : '' ?>"><?= t('documents', 'Dokumen') ?></a></li>
            <li><a href="/publik/berita.php" class="<?= $currentPage === 'berita.php' ? 'active' : '' ?>"><?= t('news', 'Berita') ?></a></li>
            <li><a href="/publik/pengaduan.php" class="<?= $currentPage === 'pengaduan.php' ? 'active' : '' ?>"><?= t('complaint', 'Pengaduan') ?></a></li>

            <!-- ===== MEGA MENU LAYANAN ===== -->
            <li class="nav-dd" id="navDd">
                <button type="button" aria-haspopup="true"><?= t('services', 'Layanan') ?> <span class="caret">▼</span></button>
                <div class="nav-dd-menu">
                    <div class="hd-mm-head">
                        <span><?= strtoupper(t('services_public', 'Layanan Publik')) ?></span>
                        <small><?= t('services_public_desc', 'Akses cepat & transparan') ?></small>
                    </div>
                    <div class="hd-mm-grid">
                        <a href="/publik/tracer.php"><span class="hd-mm-ic">🎓</span><span class="hd-mm-txt"><strong><?= t('tracer', 'Tracer Study Alumni') ?></strong><small><?= t('tracer_desc', 'Isi data karier Anda') ?></small></span></a>
                        <a href="/publik/registrasi-wawancara.php"><span class="hd-mm-ic">🎤</span><span class="hd-mm-txt"><strong><?= t('respondent', 'Responden Wawancara') ?></strong><small><?= t('respondent_desc', 'Daftar calon responden asesor') ?></small></span></a>
                        <a href="/publik/survei-pengguna.php"><span class="hd-mm-ic">💼</span><span class="hd-mm-txt"><strong><?= t('user_survey', 'Survei Pengguna Lulusan') ?></strong><small><?= t('user_survey_desc', 'Penilaian instansi / employer') ?></small></span></a>
                        <a href="/publik/dokumen.php"><span class="hd-mm-ic">📚</span><span class="hd-mm-txt"><strong><?= t('download_form', 'Unduh Formulir') ?></strong><small><?= t('download_form_desc', 'Dokumen mutu publik') ?></small></span></a>
                        <a href="/publik/berita.php?kategori=Agenda"><span class="hd-mm-ic">📅</span><span class="hd-mm-txt"><strong><?= t('ami_schedule', 'Jadwal AMI') ?></strong><small><?= t('ami_schedule_desc', 'Agenda audit terkini') ?></small></span></a>
                        <a href="/publik/pengaduan.php"><span class="hd-mm-ic">📨</span><span class="hd-mm-txt"><strong><?= t('complaint_full', 'Pengaduan & Kritik') ?></strong><small><?= t('complaint_desc', 'Sampaikan aspirasi Anda') ?></small></span></a>
                    </div>
                    <a href="/login.php" class="hd-mm-foot">
                        <span><strong>🔐 <?= t('portal_sim', 'Portal SIM-Mutu') ?></strong><small><?= t('portal_sim_desc', 'Masuk sistem internal bagi civitas akademika') ?></small></span>
                        <span style="font-size:20px;">→</span>
                    </a>
                </div>
            </li>

            <li><a href="/login.php" class="btn btn-primary nav-cta">🔐 SIM-Mutu</a></li>
        </ul>
        <div class="nav-actions">
            <?php require_once __DIR__ . '/lang-switcher.php'; ?>
            <button class="hamburger" id="hamburger" aria-label="Buka menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="mobile-drawer" id="mobileDrawer">
    <!-- Mobile Language Switcher -->
    <div class="dw-lang">
        <a href="<?= currentLangUrl('id') ?>" class="<?= $currentLang === 'id' ? 'active' : '' ?>">🇮🇩 Indonesia</a>
        <a href="<?= currentLangUrl('en') ?>" class="<?= $currentLang === 'en' ? 'active' : '' ?>">🇬🇧 English</a>
    </div>

    <a href="/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">🏠 <?= t('home', 'Beranda') ?></a>
    <a href="/publik/profil.php" class="<?= $currentPage === 'profil.php' ? 'active' : '' ?>">🏛️ <?= t('profile', 'Profil') ?></a>
    <a href="/publik/akreditasi.php" class="<?= $currentPage === 'akreditasi.php' ? 'active' : '' ?>">🏆 <?= t('accreditation', 'Akreditasi') ?></a>
    <a href="/publik/dokumen.php" class="<?= $currentPage === 'dokumen.php' ? 'active' : '' ?>">📚 <?= t('documents', 'Dokumen') ?></a>
    <a href="/publik/berita.php" class="<?= $currentPage === 'berita.php' ? 'active' : '' ?>">📰 <?= t('news', 'Berita') ?></a>
    <a href="/publik/pengaduan.php" class="<?= $currentPage === 'pengaduan.php' ? 'active' : '' ?>">📨 <?= t('complaint', 'Pengaduan') ?></a>
    <div class="dw-label"><?= strtoupper(t('services', 'Layanan')) ?></div>
    <a href="/publik/tracer.php">🎓 <?= t('tracer', 'Tracer Study Alumni') ?></a>
    <a href="/publik/registrasi-wawancara.php">🎤 <?= t('respondent', 'Responden Wawancara') ?></a>
    <a href="/publik/survei-pengguna.php">💼 <?= t('user_survey', 'Survei Pengguna Lulusan') ?></a>
    <a href="/publik/berita.php?kategori=Agenda">📅 <?= t('ami_schedule', 'Jadwal AMI') ?></a>
    <a href="/login.php" class="btn btn-primary">🔐 <?= t('portal_sim', 'Masuk SIM-Mutu') ?></a>
</div>

<script>
(function () {
    var h = document.getElementById('hamburger'),
        d = document.getElementById('mobileDrawer'),
        o = document.getElementById('drawerOverlay');
    function toggle(open) {
        h.classList.toggle('active', open);
        d.classList.toggle('open', open);
        o.classList.toggle('show', open);
    }
    h.addEventListener('click', function () { toggle(!d.classList.contains('open')); });
    o.addEventListener('click', function () { toggle(false); });
    d.querySelectorAll('a').forEach(function (a) {
        // Jangan tutup drawer saat klik link bahasa (biar user lihat perubahan)
        if (!a.classList.contains('dw-lang')) {
            a.addEventListener('click', function () { toggle(false); });
        }
    });

    /* Dropdown Layanan */
    var dd = document.getElementById('navDd');
    if (dd) {
        dd.querySelector('button').addEventListener('click', function (e) {
            e.stopPropagation();
            dd.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!dd.contains(e.target)) dd.classList.remove('open');
        });
    }

    /* Ticker bar collapse saat scroll */
    window.addEventListener('scroll', function () {
        document.body.classList.toggle('hd-scrolled', window.scrollY > 60);
    }, { passive: true });
})();
</script>