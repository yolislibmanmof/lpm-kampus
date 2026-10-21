<?php
$pageTitle = 'Berita & Agenda';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();

$db = Database::getInstance();
$slug = $_GET['slug'] ?? null;
$kategori = $_GET['kategori'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'new';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 7;
$offset = ($page - 1) * $limit;

$orderMap = ['new' => 'published_at DESC', 'old' => 'published_at ASC', 'az' => 'judul ASC'];
$order = $orderMap[$sort] ?? 'published_at DESC';

// ===== KUMPULAN ICON SVG (GAYA MODERN & PROFESSIONAL) =====
$ic = [
    'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>',
    'clock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'timer' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" x2="14" y1="2" y2="2"/><line x1="12" x2="15" y1="14" y2="11"/><circle cx="12" cy="14" r="8"/></svg>',
    'pen' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
    'link' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
    'check' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    'message_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>',
    'mail' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    'newspaper' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>',
    'arrow_left' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>',
    'arrow_right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
    'circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="0.5em" height="0.5em" viewBox="0 0 24 24" fill="currentColor" stroke="none"><circle cx="12" cy="12" r="6"/></svg>',
    'flame' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>',
    'calendar_days' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>',
    'megaphone' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
    'inbox' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>',
    'search' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
    'bookmark' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>',
    'bookmark_filled' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>',
    'text' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" x2="15" y1="20" y2="20"/><line x1="12" x2="12" y1="4" y2="20"/></svg>',
    'share_2' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>',
    'arrow_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>',
    'list' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>',
    'grid' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>',
    'sort' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 16-4 4-4-4"/><path d="M17 20V4"/><path d="m3 8 4-4 4 4"/><path d="M7 4v16"/></svg>',
    'x' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
    'printer' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>',
    'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
    'twitter' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>',
    'linkedin' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg>',
];

/* ========== DETAIL BERITA ========== */
if ($slug) {
    $stmt = $db->prepare("SELECT * FROM berita WHERE slug = :slug AND is_published = 1");
    $stmt->execute([':slug' => $slug]);
    $berita = $stmt->fetch();
    
    if (!$berita) { 
        header('Location: /publik/berita.php'); 
        exit; 
    }
    
    $pageTitle = $berita['judul'];
    $metaDesc = mb_strimwidth(strip_tags($berita['konten']), 0, 150, '...');
    
    $wordCount = str_word_count(strip_tags($berita['konten']));
    $readTime = max(1, ceil($wordCount / 200));

    // Berita terkait
    $related = $db->prepare("SELECT * FROM berita WHERE is_published = 1 AND slug != :slug ORDER BY published_at DESC LIMIT 3");
    $related->execute([':slug' => $slug]);
    $related = $related->fetchAll();

    // Navigasi sebelumnya / berikutnya
    $pnPrev = null; $pnNext = null;
    try {
        $st = $db->prepare("SELECT slug, judul FROM berita WHERE is_published = 1 AND published_at < :pa ORDER BY published_at DESC LIMIT 1");
        $st->execute([':pa' => $berita['published_at']]);
        $pnPrev = $st->fetch();
        $st = $db->prepare("SELECT slug, judul FROM berita WHERE is_published = 1 AND published_at > :pa ORDER BY published_at ASC LIMIT 1");
        $st->execute([':pa' => $berita['published_at']]);
        $pnNext = $st->fetch();
    } catch (Throwable $e) {}

    require_once __DIR__ . '/../includes/header-publik.php';
?>
<style>
/* ===== DETAIL BERITA: INTERNATIONAL CLASS ===== */
.bd-hero {
    min-height: 75vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 140px 0 80px;
    display: flex; align-items: flex-end;
}
.bd-hero.has-cover { align-items: flex-end; }
.bd-hero.has-cover::before {
    content: ''; position: absolute; inset: 0; z-index: 1;
    background: linear-gradient(to top, #061D2E 10%, rgba(6,29,46,0.8) 40%, rgba(6,29,46,0.4) 100%);
}
.bd-hero-bg {
    position: absolute; inset: 0; z-index: 0;
    background-size: cover; background-position: center;
    filter: brightness(0.7) saturate(1.2);
    transform: scale(1.05); transition: transform 10s ease;
}
.bd-hero:hover .bd-hero-bg { transform: scale(1); }
.bd-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background: radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
                radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
                linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: bdShift 16s ease-in-out infinite;
}
@keyframes bdShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.bd-hero-inner { position: relative; z-index: 3; max-width: 900px; margin: 0 auto; width: 100%; }
.bd-back {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 18px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.15);
    color: #fff; text-decoration: none; font-size: 13px; font-weight: 700;
    margin-bottom: 28px; transition: .3s;
}
.bd-back:hover { background: rgba(255,255,255,.15); transform: translateX(-4px); }
.bd-cat {
    display: inline-block; padding: 6px 16px; border-radius: 50px;
    background: rgba(201,162,39,.18); backdrop-filter: blur(10px);
    border: 1px solid rgba(201,162,39,.3);
    color: var(--accent-light, #F7E491); font-size: 11px; font-weight: 800;
    letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px;
}
.bd-hero h1 {
    font-size: clamp(36px, 6vw, 64px); font-weight: 800; color: #fff;
    line-height: 1.1; margin-bottom: 24px; letter-spacing: -.03em;
    text-shadow: 0 4px 20px rgba(0,0,0,.3);
}
.bd-hero-meta {
    display: flex; gap: 24px; flex-wrap: wrap; color: rgba(255,255,255,.8);
    font-size: 14px; font-weight: 500;
}
.bd-hero-meta span { display: inline-flex; align-items: center; gap: 8px; }
.bd-hero-meta .bd-dot { width: 4px; height: 4px; border-radius: 50%; background: var(--accent-light, #F7E491); }

.bd-read-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 3px;
    background: rgba(15,61,92,.1); z-index: 1001;
}
.bd-read-progress-bar {
    height: 100%; width: 0; 
    background: linear-gradient(90deg, var(--accent, #C9A227), var(--accent-light, #F7E491));
    box-shadow: 0 0 14px rgba(201,162,39,.5);
    transition: width .1s linear;
}

.bd-body-sec { padding: 80px 0; background: #fff; }
.bd-body {
    max-width: 780px; margin: 0 auto;
    font-size: 18px; line-height: 1.9; color: var(--text-dark, #1e293b);
    transition: font-size .3s ease;
}
.bd-body p { margin-bottom: 24px; }
.bd-body h2, .bd-body h3 { margin-top: 40px; margin-bottom: 16px; color: var(--primary-dark, #0F3D5C); scroll-margin-top: 100px; }
.bd-body strong { color: var(--primary-dark, #0F3D5C); font-weight: 700; }
.bd-body img { max-width: 100%; height: auto; border-radius: 16px; margin: 12px 0; }
.bd-body-drop::first-letter {
    font-size: 80px; font-weight: 900; float: left;
    line-height: .8; margin: 12px 16px 0 0;
    background: linear-gradient(135deg, var(--accent, #C9A227), var(--accent-light, #F7E491));
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}

.bd-toc {
    background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 16px;
    padding: 24px; margin-bottom: 40px; max-width: 780px; margin-left: auto; margin-right: auto;
}
.bd-toc h4 { font-size: 16px; color: var(--primary-dark, #0F3D5C); margin: 0 0 16px; display: flex; align-items: center; gap: 8px; font-weight: 800; }
.bd-toc ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; }
.bd-toc li a { color: var(--text-muted, #64748b); text-decoration: none; font-size: 14px; font-weight: 500; transition: .2s; display: block; padding: 4px 0; border-left: 2px solid transparent; padding-left: 12px; }
.bd-toc li a:hover { color: var(--accent, #C9A227); border-left-color: var(--accent, #C9A227); }
.bd-toc li.toc-h3 a { padding-left: 24px; font-size: 13px; }

/* Prev / Next */
.bd-pn { max-width: 780px; margin: 56px auto 0; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.bd-pn a {
    padding: 18px 20px; border-radius: 16px; border: 1px solid var(--border, #e2e8f0);
    background: #fff; text-decoration: none; transition: .3s;
    display: flex; flex-direction: column; gap: 6px;
}
.bd-pn a:hover { border-color: rgba(201,162,39,.5); transform: translateY(-3px); box-shadow: 0 12px 30px rgba(15,61,92,.1); }
.bd-pn small { font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: #C9A227; display: inline-flex; align-items: center; gap: 6px; }
.bd-pn span { font-size: 14px; font-weight: 700; color: #0F3D5C; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.bd-pn a.next { text-align: right; align-items: flex-end; }
.bd-pn .pn-gap { visibility: hidden; }
@media (max-width: 640px) { .bd-pn { grid-template-columns: 1fr; } .bd-pn a.next { text-align: left; align-items: flex-start; } }

.bd-tools-float {
    position: fixed; right: 24px; top: 50%; transform: translateY(-50%);
    display: flex; flex-direction: column; gap: 12px; z-index: 500;
}
.bd-tool-btn {
    width: 48px; height: 48px; border-radius: 14px;
    background: #fff; border: 1px solid var(--border, #e2e8f0);
    display: grid; place-items: center; font-size: 16px; font-weight: 800;
    color: var(--primary-dark, #0F3D5C);
    text-decoration: none; transition: .3s var(--ease-spring, cubic-bezier(0.34, 1.56, 0.64, 1));
    box-shadow: 0 6px 20px rgba(15,61,92,.08); cursor: pointer;
}
.bd-tool-btn:hover { transform: translateX(-6px) scale(1.1); box-shadow: 0 10px 30px rgba(15,61,92,.15); border-color: rgba(201,162,39,.5); }
.bd-tool-btn.active { background: linear-gradient(135deg, var(--accent, #C9A227), var(--accent-light, #F7E491)); color: #fff; border-color: transparent; }
@media (max-width: 1200px) { .bd-tools-float { display: none; } }

.bd-share-float {
    position: fixed; left: 24px; top: 50%; transform: translateY(-50%);
    display: flex; flex-direction: column; gap: 12px; z-index: 500;
}
.bd-sf-btn {
    width: 48px; height: 48px; border-radius: 14px;
    background: #fff; border: 1px solid var(--border, #e2e8f0);
    display: grid; place-items: center; font-size: 20px;
    text-decoration: none; transition: .3s var(--ease-spring, cubic-bezier(0.34, 1.56, 0.64, 1));
    box-shadow: 0 6px 20px rgba(15,61,92,.08); cursor: pointer; color: var(--primary-dark, #0F3D5C);
}
.bd-sf-btn:hover { transform: translateX(6px) scale(1.1); box-shadow: 0 10px 30px rgba(15,61,92,.15); border-color: rgba(201,162,39,.5); color: var(--accent, #C9A227); }
.bd-sf-btn.primary {
    background: linear-gradient(135deg, var(--accent, #C9A227), var(--accent-light, #F7E491));
    border-color: transparent; color: var(--primary-dark, #0F3D5C);
}
@media (max-width: 1200px) { .bd-share-float { display: none; } }

.bd-related { padding: 100px 0; background: linear-gradient(180deg, #F7F9FC, #fff); }
.bd-related-head { max-width: 720px; margin: 0 auto 40px; text-align: center; }
.bd-related-head h2 { font-size: clamp(28px, 3vw, 36px); font-weight: 800; color: var(--primary-dark, #0F3D5C); margin-bottom: 8px; letter-spacing: -.02em; }
.bd-related-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
.bd-related-card {
    background: #fff; border-radius: 24px; overflow: hidden;
    border: 1px solid var(--border, #e2e8f0); text-decoration: none;
    transition: .4s var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1)); display: flex; flex-direction: column;
    box-shadow: 0 6px 24px rgba(15,61,92,.06); color: inherit;
}
.bd-related-card:hover { transform: translateY(-8px); box-shadow: 0 24px 50px rgba(15,61,92,.14); border-color: rgba(201,162,39,.4); }
.bd-related-cover {
    height: 180px; position: relative; overflow: hidden;
    background: linear-gradient(135deg, var(--primary, #0F3D5C), var(--primary-light, #1A5A82));
    display: grid; place-items: center; font-size: 48px; color: rgba(255,255,255,.8);
}
.bd-related-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s; }
.bd-related-card:hover .bd-related-cover img { transform: scale(1.05); }
.bd-related-cover::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(400px circle at 30% 30%, rgba(201,162,39,.28), transparent 60%);
}
.bd-related-body { padding: 24px; flex: 1; display: flex; flex-direction: column; }
.bd-related-body .bd-cat {
    padding: 4px 12px; font-size: 10px; margin-bottom: 12px;
    background: rgba(15,61,92,.06); border-color: transparent;
    color: var(--primary, #0F3D5C); width: fit-content;
}
.bd-related-body h4 { font-size: 16px; color: var(--primary-dark, #0F3D5C); margin-bottom: 12px; line-height: 1.4; letter-spacing: -.01em; }
.bd-related-body small { color: var(--text-muted, #64748b); font-size: 12px; margin-top: auto; display: flex; align-items: center; gap: 6px; }

@media print {
    .sim-sidebar, .sim-topbar, .bd-share-float, .bd-tools-float, .bd-related, footer, .bd-back, .bd-read-progress, .bd-toc, .bd-pn { display: none !important; }
    .bd-hero { min-height: auto; padding: 40px 0; background: #fff !important; color: #000 !important; }
    .bd-hero-aurora, .bd-hero-bg { display: none; }
    .bd-hero h1 { color: #000 !important; font-size: 24pt; text-shadow: none; }
    .bd-hero-meta { color: #333 !important; }
    .bd-body { color: #000 !important; font-size: 12pt; line-height: 1.6; max-width: 100%; }
}
</style>

<div class="bd-read-progress"><div class="bd-read-progress-bar" id="bdProgress"></div></div>

<?php 
$hasCover = !empty($berita['cover']) && @file_exists(dirname(__DIR__) . '/uploads/' . $berita['cover']);
$currentUrl = rtrim(APP_URL ?? '', '/') . $_SERVER['REQUEST_URI'];
$shareText = urlencode($berita['judul'] . ' - ' . $currentUrl);
?>

<section class="bd-hero <?= $hasCover ? 'has-cover' : '' ?>">
    <?php if ($hasCover): ?>
        <div class="bd-hero-bg" style="background-image: url('/uploads/<?= Security::e($berita['cover']) ?>');"></div>
    <?php else: ?>
        <div class="bd-hero-aurora"></div>
    <?php endif; ?>
    
    <div class="container">
        <div class="bd-hero-inner">
            <a href="/publik/berita.php" class="bd-back"><?= $ic['arrow_left'] ?> Kembali ke Berita</a>
            <span class="bd-cat"><?= Security::e($berita['kategori']) ?></span>
            <h1><?= Security::e($berita['judul']) ?></h1>
            <div class="bd-hero-meta">
                <span><?= $ic['calendar'] ?> <?= date('d F Y', strtotime($berita['published_at'])) ?></span>
                <span class="bd-dot"></span>
                <span><?= $ic['clock'] ?> <?= date('H:i', strtotime($berita['published_at'])) ?> WITA</span>
                <span class="bd-dot"></span>
                <span><?= $ic['timer'] ?> <?= $readTime ?> menit baca</span>
                <span class="bd-dot"></span>
                <span><?= $ic['pen'] ?> Tim LPM</span>
            </div>
        </div>
    </div>
</section>

<div class="bd-share-float">
    <button class="bd-sf-btn primary" id="bdCopyLink" title="Salin tautan"><?= $ic['link'] ?></button>
    <a href="https://wa.me/?text=<?= $shareText ?>" target="_blank" rel="noopener" class="bd-sf-btn" title="WhatsApp"><?= $ic['message_circle'] ?></a>
    <a href="mailto:?subject=<?= urlencode($berita['judul']) ?>&body=<?= urlencode($currentUrl) ?>" class="bd-sf-btn" title="Email"><?= $ic['mail'] ?></a>
    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($currentUrl) ?>" target="_blank" rel="noopener" class="bd-sf-btn" title="Facebook"><?= $ic['facebook'] ?></a>
    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($currentUrl) ?>&text=<?= urlencode($berita['judul']) ?>" target="_blank" rel="noopener" class="bd-sf-btn" title="Twitter"><?= $ic['twitter'] ?></a>
    <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($currentUrl) ?>&title=<?= urlencode($berita['judul']) ?>" target="_blank" rel="noopener" class="bd-sf-btn" title="LinkedIn"><?= $ic['linkedin'] ?></a>
</div>

<div class="bd-tools-float">
    <button class="bd-tool-btn" id="bdFontUp" title="Perbesar Font">A+</button>
    <button class="bd-tool-btn" id="bdFontDown" title="Perkecil Font">A-</button>
    <button class="bd-tool-btn" id="bdBookmark" title="Simpan Artikel"><?= $ic['bookmark'] ?></button>
    <button class="bd-tool-btn" id="bdPrint" title="Cetak Artikel"><?= $ic['printer'] ?></button>
</div>

<section class="bd-body-sec">
    <div class="container">
        <div class="bd-body bd-body-drop" id="bdContent">
            <?= nl2br(Security::e($berita['konten'])) ?>
        </div>

        <div class="bd-pn">
            <?php if ($pnPrev): ?>
                <a href="?slug=<?= Security::e($pnPrev['slug']) ?>">
                    <small><?= $ic['arrow_left'] ?> Sebelumnya</small>
                    <span><?= Security::e($pnPrev['judul']) ?></span>
                </a>
            <?php else: ?><span class="pn-gap"></span><?php endif; ?>
            <?php if ($pnNext): ?>
                <a class="next" href="?slug=<?= Security::e($pnNext['slug']) ?>">
                    <small>Berikutnya <?= $ic['arrow_right'] ?></small>
                    <span><?= Security::e($pnNext['judul']) ?></span>
                </a>
            <?php else: ?><span class="pn-gap"></span><?php endif; ?>
        </div>
    </div>
</section>

<?php if (!empty($related)): ?>
<section class="bd-related">
    <div class="container">
        <div class="bd-related-head">
            <span class="pr-tag" style="display:inline-block;padding:6px 18px;border-radius:50px;background:rgba(201,162,39,.1);color:var(--accent, #C9A227);font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;margin-bottom:14px;border:1px solid rgba(201,162,39,.2);">Baca Juga</span>
            <h2>Berita Terkait</h2>
        </div>
        <div class="bd-related-grid">
            <?php foreach ($related as $r): 
                $rCover = !empty($r['cover']) && @file_exists(dirname(__DIR__) . '/uploads/' . $r['cover']) ? '/uploads/' . Security::e($r['cover']) : null;
            ?>
            <a href="?slug=<?= Security::e($r['slug']) ?>" class="bd-related-card">
                <div class="bd-related-cover">
                    <?php if ($rCover): ?>
                        <img src="<?= $rCover ?>" alt="<?= Security::e($r['judul']) ?>">
                    <?php else: ?>
                        <?= $ic['newspaper'] ?>
                    <?php endif; ?>
                </div>
                <div class="bd-related-body">
                    <span class="bd-cat"><?= Security::e($r['kategori']) ?></span>
                    <h4><?= Security::e($r['judul']) ?></h4>
                    <small><?= $ic['calendar'] ?> <?= date('d M Y', strtotime($r['published_at'])) ?></small>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
(function () {
    var bar = document.getElementById('bdProgress');
    if (bar) {
        window.addEventListener('scroll', function () {
            var h = document.documentElement;
            var max = h.scrollHeight - h.clientHeight;
            var p = max > 0 ? (h.scrollTop / max) * 100 : 0;
            bar.style.width = p + '%';
        }, { passive: true });
    }

    // Font Size Adjuster
    var content = document.getElementById('bdContent');
    var fontUp = document.getElementById('bdFontUp');
    var fontDown = document.getElementById('bdFontDown');
    var currentSize = parseFloat(localStorage.getItem('bdFontSize') || '18');
    if (content) content.style.fontSize = currentSize + 'px';

    if (fontUp) fontUp.addEventListener('click', function () {
        if (currentSize < 24) {
            currentSize += 2;
            if (content) content.style.fontSize = currentSize + 'px';
            localStorage.setItem('bdFontSize', currentSize);
        }
    });
    if (fontDown) fontDown.addEventListener('click', function () {
        if (currentSize > 14) {
            currentSize -= 2;
            if (content) content.style.fontSize = currentSize + 'px';
            localStorage.setItem('bdFontSize', currentSize);
        }
    });

    // Print
    var printBtn = document.getElementById('bdPrint');
    if (printBtn) printBtn.addEventListener('click', function () { window.print(); });

    // Bookmark (menyimpan objek {slug, judul})
    var bookmarkBtn = document.getElementById('bdBookmark');
    var currentSlug = <?= json_encode($slug) ?>;
    var artTitle = <?= json_encode($berita['judul']) ?>;
    function loadBookmarks() {
        var raw = [];
        try { raw = JSON.parse(localStorage.getItem('bdBookmarks') || '[]'); } catch (e) {}
        return raw.map(function (b) { return typeof b === 'string' ? { slug: b, judul: 'Artikel tersimpan' } : b; });
    }
    var bookmarks = loadBookmarks();
    function hasBm() { return bookmarks.some(function (b) { return b.slug === currentSlug; }); }
    function paintBm() {
        if (!bookmarkBtn) return;
        bookmarkBtn.innerHTML = hasBm() ? <?= json_encode($ic['bookmark_filled']) ?> : <?= json_encode($ic['bookmark']) ?>;
        bookmarkBtn.classList.toggle('active', hasBm());
    }
    paintBm();
    if (bookmarkBtn) bookmarkBtn.addEventListener('click', function () {
        if (hasBm()) bookmarks = bookmarks.filter(function (b) { return b.slug !== currentSlug; });
        else bookmarks.push({ slug: currentSlug, judul: artTitle });
        localStorage.setItem('bdBookmarks', JSON.stringify(bookmarks));
        paintBm();
    });

    // Dynamic TOC Generator
    if (content) {
        var headings = content.querySelectorAll('h2, h3');
        if (headings.length > 2) {
            var toc = document.createElement('div');
            toc.className = 'bd-toc';
            toc.innerHTML = '<h4><?= $ic['list'] ?> Daftar Isi</h4><ul></ul>';
            var ul = toc.querySelector('ul');
            headings.forEach(function (h, i) {
                var id = 'heading-' + i;
                h.id = id;
                var li = document.createElement('li');
                li.className = h.tagName === 'H3' ? 'toc-h3' : '';
                li.innerHTML = '<a href="#' + id + '">' + h.textContent + '</a>';
                ul.appendChild(li);
            });
            content.parentNode.insertBefore(toc, content);
        }
    }
})();
</script>

<?php
    require_once __DIR__ . '/../includes/footer-publik.php';
    exit; // PENTING: Menghentikan eksekusi di sini agar tidak masuk ke kode daftar berita
} 

/* ========== DAFTAR BERITA ========== */
try {
    $countQuery = "SELECT COUNT(*) as total FROM berita WHERE is_published = 1";
    $countParams = [];
    if ($kategori !== 'all') { 
        $countQuery .= " AND kategori = :kategori"; 
        $countParams[':kategori'] = $kategori; 
    }
    if ($search) {
        $countQuery .= " AND (judul LIKE :search OR konten LIKE :search2)";
        $countParams[':search'] = "%$search%";
        $countParams[':search2'] = "%$search%";
    }
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($countParams);
    $total = (int)$countStmt->fetch()['total'];
    
    $totalPages = max(1, (int)ceil($total / $limit));
    
    if ($page > $totalPages) {
        $redirectUrl = '?page=' . $totalPages;
        if ($kategori !== 'all') $redirectUrl .= '&kategori=' . urlencode($kategori);
        if ($search) $redirectUrl .= '&search=' . urlencode($search);
        if ($sort !== 'new') $redirectUrl .= '&sort=' . urlencode($sort);
        header('Location: ' . $redirectUrl);
        exit;
    }

    $offset = ($page - 1) * $limit;

    $dataQuery = "SELECT * FROM berita WHERE is_published = 1";
    $dataParams = [];
    if ($kategori !== 'all') { 
        $dataQuery .= " AND kategori = :kategori"; 
        $dataParams[':kategori'] = $kategori; 
    }
    if ($search) {
        $dataQuery .= " AND (judul LIKE :search OR konten LIKE :search2)";
        $dataParams[':search'] = "%$search%";
        $dataParams[':search2'] = "%$search%";
    }
    $dataQuery .= " ORDER BY $order LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($dataQuery);
    foreach ($dataParams as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $beritaList = $stmt->fetchAll();
    $featured = ($page == 1 && !empty($beritaList) && $sort === 'new' && $kategori === 'all' && !$search) ? array_shift($beritaList) : null;
    
} catch (PDOException $e) {
    $beritaList = [];
    $featured = null;
    $totalPages = 1;
}

// Statistik newsroom & hitungan kategori
$catCounts = []; $totalAll = 0; $thisMonth = 0;
try {
    $catCounts = array_column($db->query("SELECT kategori, COUNT(*) c FROM berita WHERE is_published = 1 GROUP BY kategori")->fetchAll(), 'c', 'kategori');
    $totalAll = array_sum($catCounts);
    $thisMonth = (int)$db->query("SELECT COUNT(*) FROM berita WHERE is_published = 1 AND YEAR(published_at) = YEAR(NOW()) AND MONTH(published_at) = MONTH(NOW())")->fetchColumn();
} catch (Throwable $e) {}

// Helper URL yang menjaga parameter
function bsUrl(array $over = []) {
    global $kategori, $search, $sort;
    $q = array_merge(['kategori' => $kategori, 'search' => $search, 'sort' => $sort], $over);
    if (($q['kategori'] ?? 'all') === 'all') unset($q['kategori']);
    if (empty($q['search'])) unset($q['search']);
    if (($q['sort'] ?? 'new') === 'new') unset($q['sort']);
    if (isset($q['page']) && $q['page'] <= 1) unset($q['page']);
    return $q ? '?' . http_build_query($q) : '?';
}

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ===== BERITA LIST: INTERNATIONAL CLASS ===== */
.bs-hero {
    min-height: 78vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 140px 0 100px;
    display: flex; align-items: center;
}
.bs-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background: radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
                radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
                radial-gradient(ellipse 60% 50% at 70% 80%, rgba(232,197,90,.18), transparent 55%),
                linear-gradient(160deg, #061D2E 0%, #0F3D5C 55%, #092A40 100%);
    animation: bsShift 16s ease-in-out infinite;
}
@keyframes bsShift { 0%,100% { filter: hue-rotate(0deg); transform: scale(1); } 50% { filter: hue-rotate(12deg); transform: scale(1.04); } }
.bs-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.bs-hero-inner { position: relative; z-index: 3; max-width: 900px; margin: 0 auto; text-align: center; }
.bs-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 28px; position: relative; overflow: hidden;
}
.bs-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: bsShimmer 3.5s ease-in-out infinite;
}
@keyframes bsShimmer { to { left: 200%; } }
.bs-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #EF4444; box-shadow: 0 0 14px #EF4444;
    animation: bsLivePulse 1.5s ease infinite;
}
@keyframes bsLivePulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.3); } }
.bs-hero h1 {
    font-size: clamp(40px, 6vw, 78px); font-weight: 800; color: #fff;
    line-height: 1.04; margin-bottom: 22px; letter-spacing: -.04em;
}
.bs-hero h1 .bs-grad {
    background: linear-gradient(120deg, #E8C55A 0%, #C9A227 25%, #F7E491 50%, #C9A227 75%, #E8C55A 100%);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: bsTextShine 4s linear infinite; font-style: italic;
}
@keyframes bsTextShine { to { background-position: 200% center; } }
.bs-hero-lead {
    font-size: clamp(16px, 1.5vw, 18px); color: rgba(255,255,255,.75);
    max-width: 660px; margin: 0 auto 32px; line-height: 1.65;
}

.bs-search-form { display: flex; gap: 12px; max-width: 600px; margin: 0 auto 32px; }
.bs-search-input-wrap {
    flex: 1; display: flex; align-items: center; gap: 12px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    padding: 4px 4px 4px 20px; color: #fff; transition: .3s;
}
.bs-search-input-wrap:focus-within { border-color: var(--accent-light, #F7E491); box-shadow: 0 0 0 4px rgba(201,162,39,.2); }
.bs-search-input-wrap svg { font-size: 18px; color: rgba(255,255,255,.6); }
.bs-search-input-wrap input {
    flex: 1; background: transparent; border: none; outline: none;
    color: #fff; font-size: 15px; padding: 10px 0;
}
.bs-search-input-wrap input::placeholder { color: rgba(255,255,255,.5); }
.bs-search-form button {
    padding: 12px 28px; border-radius: 50px; border: none;
    background: linear-gradient(135deg, var(--accent, #C9A227), var(--accent-light, #F7E491));
    color: var(--primary-dark, #0F3D5C); font-weight: 800; font-size: 14px;
    cursor: pointer; transition: .3s; box-shadow: 0 8px 20px rgba(201,162,39,.3);
}
.bs-search-form button:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(201,162,39,.5); }

/* Stats strip */
.bs-stats { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 34px; }
.bs-stat {
    min-width: 132px; padding: 13px 20px; border-radius: 18px;
    background: rgba(255,255,255,.07); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.14); text-align: center; transition: .3s;
}
.bs-stat:hover { transform: translateY(-3px); background: rgba(255,255,255,.12); }
.bs-stat b {
    display: block; font-size: 25px; font-weight: 800; line-height: 1;
    background: linear-gradient(180deg, #F7E491, #C9A227);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.bs-stat span { font-size: 10.5px; letter-spacing: 1.2px; text-transform: uppercase; color: rgba(255,255,255,.65); font-weight: 700; }

.bs-ticker {
    display: flex; gap: 40px; padding: 14px 0;
    background: rgba(255,255,255,.06); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.12); border-radius: 50px;
    overflow: hidden; max-width: 820px; margin: 28px auto 0; position: relative;
}
.bs-ticker::before {
    content: 'LIVE'; position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    padding: 4px 12px; border-radius: 50px;
    background: var(--danger, #EF4444); color: #fff; font-size: 10px; font-weight: 900;
    letter-spacing: 1.5px; z-index: 2;
    box-shadow: 0 0 0 0 rgba(239,68,68,.7); animation: bsLivePulse 2s ease infinite;
}
.bs-ticker-track { display: flex; gap: 40px; width: max-content; padding-left: 80px; animation: bsMarquee 45s linear infinite; }
.bs-ticker:hover .bs-ticker-track { animation-play-state: paused; }
.bs-ticker-item {
    color: rgba(255,255,255,.85); font-size: 13px; font-weight: 600; white-space: nowrap;
    display: flex; align-items: center; gap: 10px; text-decoration: none; transition: .25s;
}
.bs-ticker-item:hover { color: var(--accent-light, #F7E491); }
.bs-ticker-item b { color: var(--accent-light, #F7E491); display: inline-flex; }
@keyframes bsMarquee { to { transform: translateX(-50%); } }

.bs-body { padding: 60px 0 100px; background: #fff; }

/* Saved panel */
.bs-saved {
    max-width: 860px; margin: 0 auto 34px; background: #fff;
    border: 1px solid var(--border, #e2e8f0); border-radius: 18px; overflow: hidden;
    box-shadow: 0 6px 22px rgba(15,61,92,.07); display: none;
}
.bs-saved.show { display: block; animation: bsSaveIn .4s cubic-bezier(0.16,1,0.3,1); }
@keyframes bsSaveIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.bs-saved-head {
    padding: 14px 20px; display: flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, rgba(201,162,39,.08), rgba(15,61,92,.04));
    border-bottom: 1px solid var(--border, #e2e8f0);
    font-size: 14px; font-weight: 800; color: #0F3D5C;
}
.bs-saved-head > svg { color: #C9A227; }
.bs-saved-head .sv-count { margin-left: auto; font-size: 11px; color: #64748b; font-weight: 700; }
.bs-saved-list { display: flex; flex-direction: column; }
.bs-saved-item { display: flex; align-items: center; gap: 6px; border-bottom: 1px dashed #e2e8f0; }
.bs-saved-item:last-child { border-bottom: none; }
.bs-saved-item a {
    display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0;
    padding: 12px 20px; text-decoration: none; color: inherit; transition: .25s;
}
.bs-saved-item a:hover { background: rgba(201,162,39,.06); padding-left: 26px; }
.bs-saved-item .sv-ic { color: #C9A227; flex-shrink: 0; display: inline-flex; font-size: 15px; }
.bs-saved-item .sv-tx {
    flex: 1; min-width: 0; font-size: 13.5px; font-weight: 600; color: #0F3D5C;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.bs-saved-item .sv-rm {
    border: none; background: none; color: #94a3b8; cursor: pointer;
    font-size: 15px; display: inline-flex; padding: 6px; margin-right: 12px;
    border-radius: 8px; transition: .2s;
}
.bs-saved-item .sv-rm:hover { color: #EF4444; background: rgba(239,68,68,.08); }

/* Pills */
.bs-pills { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-bottom: 26px; }
.bs-pill {
    padding: 11px 24px; border-radius: 50px; font-weight: 700; font-size: 13.5px;
    text-decoration: none; transition: .3s var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1));
    border: 1.5px solid var(--border, #e2e8f0); color: var(--text-dark, #1e293b); background: #fff;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 2px 8px rgba(15,61,92,.04);
}
.bs-pill:hover { border-color: rgba(201,162,39,.5); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,61,92,.1); }
.bs-pill.active {
    background: linear-gradient(135deg, var(--accent, #C9A227), var(--accent-light, #F7E491));
    border-color: transparent; color: var(--primary-dark, #0F3D5C);
    box-shadow: 0 10px 28px rgba(201,162,39,.4);
}
.bs-pill-c { padding: 1px 9px; border-radius: 50px; font-size: 11px; font-weight: 800; background: rgba(15,61,92,.08); color: #0F3D5C; }
.bs-pill.active .bs-pill-c { background: rgba(255,255,255,.28); color: #0F3D5C; }

/* Toolbar */
.bs-toolbar {
    display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;
    margin: 0 auto 30px; max-width: 1160px;
    padding: 12px 16px; background: #fff; border: 1px solid var(--border, #e2e8f0);
    border-radius: 16px; box-shadow: 0 4px 16px rgba(15,61,92,.05);
}
.bs-tb-group { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.bs-tb-label { font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: #64748b; display: inline-flex; gap: 6px; align-items: center; }
.bs-tb-btn {
    padding: 8px 14px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff;
    color: #1e293b; font-size: 12.5px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px; transition: .3s; font-family: inherit;
}
.bs-tb-btn:hover { border-color: rgba(201,162,39,.5); transform: translateY(-2px); }
.bs-tb-btn.on { background: linear-gradient(135deg, #C9A227, #E8C55A); border-color: transparent; color: #0F3D5C; }
.bs-tb-select {
    padding: 8px 12px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff;
    font-size: 12.5px; font-weight: 700; color: #1e293b; font-family: inherit; cursor: pointer; outline: none;
}
.bs-tb-select:focus { border-color: rgba(201,162,39,.6); box-shadow: 0 0 0 3px rgba(201,162,39,.14); }
.bs-tb-result { font-size: 12px; font-weight: 700; color: #64748b; }
.bs-tb-result b { color: #0F3D5C; }

/* Featured */
.bs-featured {
    display: grid; grid-template-columns: 1.2fr 1fr; border-radius: 28px;
    overflow: hidden; margin-bottom: 40px; position: relative;
    background: #fff; border: 1px solid var(--border, #e2e8f0);
    box-shadow: 0 20px 60px rgba(15,61,92,.12);
    transition: .5s var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1));
    text-decoration: none; color: inherit;
}
.bs-featured:hover { transform: translateY(-6px); box-shadow: 0 30px 80px rgba(15,61,92,.2); }
.bs-f-cover {
    background: linear-gradient(135deg, var(--primary, #0F3D5C), var(--primary-light, #1A5A82));
    display: grid; place-items: center; font-size: 96px; min-height: 400px;
    position: relative; overflow: hidden; color: rgba(255,255,255,.8);
}
.bs-f-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s; }
.bs-featured:hover .bs-f-cover img { transform: scale(1.05); }
.bs-f-cover::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(500px circle at 30% 30%, rgba(201,162,39,.35), transparent 60%);
}
.bs-f-cover > span { position: relative; z-index: 1; transition: .5s var(--ease-spring, cubic-bezier(0.34, 1.56, 0.64, 1)); }
.bs-featured:hover .bs-f-cover > span { transform: scale(1.2) rotate(-6deg); }
.bs-f-badge {
    position: absolute; top: 24px; left: 24px;
    padding: 6px 16px; border-radius: 50px;
    background: rgba(255,255,255,.95); backdrop-filter: blur(10px);
    color: var(--primary-dark, #0F3D5C); font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase; z-index: 2;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 14px rgba(0,0,0,.15);
}
.bs-f-body { padding: 48px; display: flex; flex-direction: column; justify-content: center; }
.bs-f-body .bs-cat, .bs-card-body .bs-cat {
    display: inline-block; padding: 5px 14px; border-radius: 50px;
    background: rgba(15,61,92,.06); color: var(--primary, #0F3D5C);
    font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
    margin-bottom: 14px; width: fit-content;
}
.bs-f-body h2 {
    font-size: clamp(24px, 2.6vw, 32px); font-weight: 800; color: var(--primary-dark, #0F3D5C);
    margin-bottom: 14px; letter-spacing: -.02em; line-height: 1.2;
}
.bs-f-body p { color: var(--text-muted, #64748b); font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
.bs-f-foot { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; }
.bs-f-foot small { color: var(--text-muted, #64748b); font-size: 12.5px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.bs-f-cta {
    padding: 10px 22px; border-radius: 50px; font-size: 13px; font-weight: 800;
    background: linear-gradient(135deg, var(--accent, #C9A227), var(--accent-light, #F7E491));
    color: var(--primary-dark, #0F3D5C); text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px;
    transition: .3s; box-shadow: 0 6px 18px rgba(201,162,39,.35);
}
.bs-f-cta:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(201,162,39,.5); }
@media (max-width: 992px) { .bs-featured { grid-template-columns: 1fr; } .bs-f-cover { min-height: 280px; } .bs-f-body { padding: 32px; } }

/* Card grid */
.bs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
.bs-grid.list-view { grid-template-columns: 1fr; }
.bs-grid.list-view .bs-card { flex-direction: row; align-items: stretch; }
.bs-grid.list-view .bs-card-cover { width: 250px; min-height: 100%; height: auto; flex-shrink: 0; font-size: 44px; }
.bs-grid.list-view .bs-card-body { justify-content: center; }
.bs-grid.list-view .bs-card-body p { flex: 0 1 auto; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
@media (max-width: 760px) {
    .bs-grid.list-view .bs-card { flex-direction: column; }
    .bs-grid.list-view .bs-card-cover { width: 100%; height: 200px; min-height: 0; }
}
.bs-card {
    background: #fff; border-radius: 24px; overflow: hidden;
    border: 1px solid var(--border, #e2e8f0); transition: .4s var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1));
    display: flex; flex-direction: column;
    box-shadow: 0 6px 24px rgba(15,61,92,.06);
    text-decoration: none; color: inherit;
}
.bs-card:hover { transform: translateY(-8px); box-shadow: 0 24px 50px rgba(15,61,92,.14); border-color: rgba(201,162,39,.4); }
.bs-card-cover {
    height: 200px; position: relative; overflow: hidden;
    background: linear-gradient(135deg, var(--primary, #0F3D5C), var(--primary-light, #1A5A82));
    display: grid; place-items: center; font-size: 56px; color: rgba(255,255,255,.8);
}
.bs-card-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s; }
.bs-card:hover .bs-card-cover img { transform: scale(1.05); }
.bs-card-cover::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(400px circle at 30% 30%, rgba(201,162,39,.28), transparent 60%);
}
.bs-card-cover > span { position: relative; z-index: 1; transition: .5s var(--ease-spring, cubic-bezier(0.34, 1.56, 0.64, 1)); }
.bs-card:hover .bs-card-cover > span { transform: scale(1.2) rotate(-6deg); }
.bs-card-cover.agenda { background: linear-gradient(135deg, #C9A227, #E8C55A); }
.bs-card-cover.pengumuman { background: linear-gradient(135deg, #092A40, #1A5A82); }
.bs-card-body { padding: 24px; flex: 1; display: flex; flex-direction: column; }
.bs-card-body .bs-cat { padding: 4px 12px; font-size: 10px; margin-bottom: 12px; }
.bs-card-body h3 { font-size: 17px; color: var(--primary-dark, #0F3D5C); margin-bottom: 10px; letter-spacing: -.01em; line-height: 1.4; }
.bs-card-body p { color: var(--text-muted, #64748b); font-size: 14px; line-height: 1.6; margin-bottom: 16px; flex: 1; }
.bs-card-foot { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding-top: 16px; border-top: 1px dashed var(--border, #e2e8f0); flex-wrap: wrap; }
.bs-card-foot small { color: var(--text-muted, #64748b); font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.bs-read-badge {
    padding: 3px 10px; border-radius: 50px; background: rgba(201,162,39,.1);
    color: #C9A227; font-size: 10.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px;
}
.bs-card-link { color: var(--primary, #0F3D5C); font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; transition: .25s; }
.bs-card:hover .bs-card-link { color: var(--accent, #C9A227); gap: 10px; }

/* Empty */
.bs-empty {
    grid-column: 1 / -1; text-align: center; padding: 70px 20px;
    background: #fff; border-radius: 24px; border: 2px dashed var(--border, #e2e8f0);
}
.bs-empty-ico { font-size: 72px; margin-bottom: 20px; opacity: .5; animation: bsFloat 4s ease-in-out infinite; color: var(--text-muted, #64748b); display: flex; justify-content: center; }
@keyframes bsFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
.bs-empty h3 { font-size: 22px; color: var(--primary-dark, #0F3D5C); margin-bottom: 8px; }
.bs-empty p { color: var(--text-muted, #64748b); font-size: 14px; max-width: 460px; margin: 0 auto; line-height: 1.6; }

/* Pagination */
.bs-pagination { display: flex; justify-content: center; gap: 8px; margin-top: 56px; flex-wrap: wrap; }
.bs-pg {
    min-width: 42px; height: 42px; padding: 0 14px; border-radius: 12px;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; text-decoration: none;
    border: 1.5px solid var(--border, #e2e8f0); color: var(--text-dark, #1e293b); background: #fff;
    transition: .3s var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1));
}
.bs-pg:hover { border-color: rgba(201,162,39,.5); transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,61,92,.1); }
.bs-pg.active {
    background: linear-gradient(135deg, var(--accent, #C9A227), var(--accent-light, #F7E491));
    border-color: transparent; color: var(--primary-dark, #0F3D5C);
    box-shadow: 0 8px 22px rgba(201,162,39,.4);
}
.bs-pg-dots { padding: 0 10px; color: #64748b; display: flex; align-items: center; font-weight: 700; }

.bs-back-top {
    position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px;
    border-radius: 50%; background: linear-gradient(135deg, var(--accent, #C9A227), var(--accent-light, #F7E491));
    color: var(--primary-dark, #0F3D5C); display: grid; place-items: center;
    font-size: 24px; box-shadow: 0 10px 30px rgba(201,162,39,.4);
    cursor: pointer; border: none; z-index: 999;
    opacity: 0; pointer-events: none; transform: translateY(20px);
    transition: .4s var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1));
}
.bs-back-top.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.bs-back-top:hover { transform: translateY(-4px) scale(1.05); }

.bs-reveal { opacity: 0; transform: translateY(30px); transition: opacity .7s var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1)), transform .7s var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1)); }
.bs-reveal.bs-in { opacity: 1; transform: none; }
</style>

<section class="bs-hero">
    <div class="bs-hero-aurora"></div>
    <div class="bs-hero-grid"></div>
    <div class="container">
        <div class="bs-hero-inner">
            <span class="bs-hero-badge">
                <span class="bs-hero-badge-dot"></span>
                Live Newsroom
            </span>
            <h1>Berita & <span class="bs-grad">Agenda</span><br>LPM Terkini</h1>
            <p class="bs-hero-lead">
                Dokumentasi kegiatan, pelatihan auditor, jadwal AMI, dan seminar
                penjaminan mutu — selalu diperbarui.
            </p>
            
            <form method="GET" class="bs-search-form">
                <div class="bs-search-input-wrap">
                    <?= $ic['search'] ?>
                    <input type="text" name="search" placeholder="Cari berita atau agenda..." value="<?= Security::e($search) ?>">
                    <input type="hidden" name="kategori" value="<?= Security::e($kategori) ?>">
                    <?php if ($sort !== 'new'): ?><input type="hidden" name="sort" value="<?= Security::e($sort) ?>"><?php endif; ?>
                </div>
                <button type="submit">Cari</button>
            </form>

            <div class="bs-stats">
                <div class="bs-stat"><b><?= $totalAll ?></b><span>Total Artikel</span></div>
                <div class="bs-stat"><b><?= $catCounts['Berita'] ?? 0 ?></b><span>Berita</span></div>
                <div class="bs-stat"><b><?= $catCounts['Agenda'] ?? 0 ?></b><span>Agenda</span></div>
                <div class="bs-stat"><b><?= $catCounts['Pengumuman'] ?? 0 ?></b><span>Pengumuman</span></div>
                <div class="bs-stat"><b><?= $thisMonth ?></b><span>Bulan Ini</span></div>
            </div>

            <?php if (!empty($beritaList) || $featured): ?>
            <div class="bs-ticker">
                <div class="bs-ticker-track">
                    <?php
                    $tickers = array_merge($featured ? [$featured] : [], array_slice($beritaList, 0, 6));
                    for ($i = 0; $i < 2; $i++):
                        foreach ($tickers as $t): ?>
                        <a href="?slug=<?= Security::e($t['slug']) ?>" class="bs-ticker-item">
                            <b><?= $ic['circle'] ?></b> <?= Security::e(mb_strimwidth($t['judul'], 0, 70, '...')) ?>
                        </a>
                    <?php endforeach; endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="bs-body">
    <div class="container">

        <!-- Panel Artikel Tersimpan -->
        <div class="bs-saved" id="bsSaved">
            <div class="bs-saved-head"><?= $ic['bookmark'] ?> Artikel Tersimpan <span class="sv-count" id="bsSavedCount"></span></div>
            <div class="bs-saved-list" id="bsSavedList"></div>
        </div>

        <div class="bs-pills bs-reveal">
            <a href="<?= bsUrl(['kategori' => 'all', 'page' => null]) ?>" class="bs-pill <?= $kategori === 'all' ? 'active' : '' ?>"><?= $ic['newspaper'] ?> Semua <span class="bs-pill-c"><?= $totalAll ?></span></a>
            <a href="<?= bsUrl(['kategori' => 'Berita', 'page' => null]) ?>" class="bs-pill <?= $kategori === 'Berita' ? 'active' : '' ?>"><?= $ic['newspaper'] ?> Berita <span class="bs-pill-c"><?= $catCounts['Berita'] ?? 0 ?></span></a>
            <a href="<?= bsUrl(['kategori' => 'Agenda', 'page' => null]) ?>" class="bs-pill <?= $kategori === 'Agenda' ? 'active' : '' ?>"><?= $ic['calendar_days'] ?> Agenda <span class="bs-pill-c"><?= $catCounts['Agenda'] ?? 0 ?></span></a>
            <a href="<?= bsUrl(['kategori' => 'Pengumuman', 'page' => null]) ?>" class="bs-pill <?= $kategori === 'Pengumuman' ? 'active' : '' ?>"><?= $ic['megaphone'] ?> Pengumuman <span class="bs-pill-c"><?= $catCounts['Pengumuman'] ?? 0 ?></span></a>
        </div>

        <div class="bs-toolbar bs-reveal">
            <div class="bs-tb-group">
                <span class="bs-tb-label"><?= $ic['sort'] ?> Urutkan</span>
                <select class="bs-tb-select" id="bsSort">
                    <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="old" <?= $sort === 'old' ? 'selected' : '' ?>>Terlama</option>
                    <option value="az" <?= $sort === 'az' ? 'selected' : '' ?>>Judul A–Z</option>
                </select>
            </div>
            <div class="bs-tb-group">
                <span class="bs-tb-result">Menampilkan <b><?= count($beritaList) + ($featured ? 1 : 0) ?></b> dari <b><?= $total ?></b> artikel</span>
            </div>
            <div class="bs-tb-group">
                <span class="bs-tb-label"><?= $ic['grid'] ?> Tampilan</span>
                <button type="button" class="bs-tb-btn bs-view on" data-view="grid"><?= $ic['grid'] ?> Grid</button>
                <button type="button" class="bs-tb-btn bs-view" data-view="list"><?= $ic['list'] ?> List</button>
            </div>
        </div>

        <?php if ($featured): 
            $fCover = !empty($featured['cover']) && @file_exists(dirname(__DIR__) . '/uploads/' . $featured['cover']) ? '/uploads/' . Security::e($featured['cover']) : null;
            $fRead = max(1, ceil(str_word_count(strip_tags($featured['konten'])) / 200));
        ?>
        <a href="?slug=<?= Security::e($featured['slug']) ?>" class="bs-featured bs-reveal">
            <div class="bs-f-cover">
                <?php if ($fCover): ?>
                    <img src="<?= $fCover ?>" alt="<?= Security::e($featured['judul']) ?>">
                <?php else: ?>
                    <span><?= $ic['newspaper'] ?></span>
                <?php endif; ?>
                <span class="bs-f-badge"><?= $ic['flame'] ?> Sorotan Utama</span>
            </div>
            <div class="bs-f-body">
                <span class="bs-cat"><?= Security::e($featured['kategori']) ?></span>
                <h2><?= Security::e($featured['judul']) ?></h2>
                <p><?= Security::e(mb_strimwidth(strip_tags($featured['konten']), 0, 200, '...')) ?></p>
                <div class="bs-f-foot">
                    <small><?= $ic['calendar'] ?> <?= date('d M Y', strtotime($featured['published_at'])) ?> &nbsp;•&nbsp; <?= $ic['timer'] ?> <?= $fRead ?> mnt</small>
                    <span class="bs-f-cta">Baca Sorotan <?= $ic['arrow_right'] ?></span>
                </div>
            </div>
        </a>
        <?php endif; ?>

        <div class="bs-grid" id="bsGrid">
            <?php if (empty($beritaList)): ?>
                <div class="bs-empty bs-reveal">
                    <div class="bs-empty-ico"><?= $ic['inbox'] ?></div>
                    <h3><?= $featured ? 'Tidak ada artikel lain' : 'Belum ada berita' ?></h3>
                    <p><?= $featured
                        ? 'Sorotan utama di atas adalah satu-satunya artikel yang tampil untuk filter saat ini. Artikel berikutnya akan muncul di area ini.'
                        : 'Belum ada berita untuk kategori atau pencarian ini. Silakan pilih kategori lain atau kunjungi kembali nanti.' ?></p>
                </div>
            <?php endif; ?>
            
            <?php foreach ($beritaList as $b):
                $coverClass = '';
                $icon = $ic['newspaper'];
                if ($b['kategori'] === 'Agenda') { $coverClass = 'agenda'; $icon = $ic['calendar_days']; }
                elseif ($b['kategori'] === 'Pengumuman') { $coverClass = 'pengumuman'; $icon = $ic['megaphone']; }
                
                $coverUrl = !empty($b['cover']) && @file_exists(dirname(__DIR__) . '/uploads/' . $b['cover']) ? '/uploads/' . Security::e($b['cover']) : null;
                $bRead = max(1, ceil(str_word_count(strip_tags($b['konten'])) / 200));
            ?>
                <a href="?slug=<?= Security::e($b['slug']) ?>" class="bs-card bs-reveal">
                    <div class="bs-card-cover <?= $coverClass ?>">
                        <?php if ($coverUrl): ?>
                            <img src="<?= $coverUrl ?>" alt="<?= Security::e($b['judul']) ?>">
                        <?php else: ?>
                            <span><?= $icon ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="bs-card-body">
                        <span class="bs-cat"><?= Security::e($b['kategori']) ?></span>
                        <h3><?= Security::e($b['judul']) ?></h3>
                        <p><?= Security::e(mb_strimwidth(strip_tags($b['konten']), 0, 100, '...')) ?></p>
                        <div class="bs-card-foot">
                            <small><?= $ic['calendar'] ?> <?= date('d M Y', strtotime($b['published_at'])) ?></small>
                            <span class="bs-read-badge"><?= $ic['timer'] ?> <?= $bRead ?> mnt</span>
                            <span class="bs-card-link">Baca <?= $ic['arrow_right'] ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="bs-pagination bs-reveal">
            <?php if ($page > 1): ?>
                <a href="<?= bsUrl(['page' => $page - 1]) ?>" class="bs-pg"><?= $ic['arrow_left'] ?></a>
            <?php endif; ?>
            
            <?php 
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            if ($start > 1) echo '<span class="bs-pg-dots">…</span>';
            for ($i = $start; $i <= $end; $i++): 
            ?>
                <a href="<?= bsUrl(['page' => $i]) ?>" class="bs-pg <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($end < $totalPages) echo '<span class="bs-pg-dots">…</span>'; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?= bsUrl(['page' => $page + 1]) ?>" class="bs-pg"><?= $ic['arrow_right'] ?></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    var ICON = {
        bookmark: <?= json_encode($ic['bookmark']) ?>,
        x: <?= json_encode($ic['x']) ?>
    };
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) { en.target.classList.add('bs-in'); io.unobserve(en.target); }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.bs-reveal').forEach(function (el) { io.observe(el); });

    var backTop = document.getElementById('bsBackTop');
    if (backTop) {
        window.addEventListener('scroll', function () {
            backTop.classList.toggle('visible', window.scrollY > 500);
        });
        backTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ===== SORT ===== */
    var sortSel = document.getElementById('bsSort');
    if (sortSel) sortSel.addEventListener('change', function () {
        var u = new URL(location.href);
        u.searchParams.set('sort', this.value);
        u.searchParams.delete('page');
        location.href = u.toString();
    });

    /* ===== VIEW TOGGLE ===== */
    var grid = document.getElementById('bsGrid');
    var viewBtns = document.querySelectorAll('.bs-view');
    function applyView(v) {
        if (!grid) return;
        grid.classList.toggle('list-view', v === 'list');
        viewBtns.forEach(function (b) { b.classList.toggle('on', b.dataset.view === v); });
    }
    var savedView = 'grid';
    try { savedView = localStorage.getItem('bsView') || 'grid'; } catch (e) {}
    applyView(savedView);
    viewBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            applyView(this.dataset.view);
            try { localStorage.setItem('bsView', this.dataset.view); } catch (e) {}
        });
    });

    /* ===== SAVED ARTICLES PANEL ===== */
    function loadSaved() {
        var raw = [];
        try { raw = JSON.parse(localStorage.getItem('bdBookmarks') || '[]'); } catch (e) {}
        return raw.map(function (b) { return typeof b === 'string' ? { slug: b, judul: 'Artikel tersimpan' } : b; });
    }
    function renderSaved() {
        var wrap = document.getElementById('bsSaved');
        var list = document.getElementById('bsSavedList');
        var cnt = document.getElementById('bsSavedCount');
        if (!wrap || !list) return;
        var items = loadSaved();
        if (!items.length) { wrap.classList.remove('show'); return; }
        wrap.classList.add('show');
        if (cnt) cnt.textContent = items.length + ' artikel';
        list.innerHTML = items.map(function (it, idx) {
            return '<div class="bs-saved-item">' +
                '<a href="?slug=' + encodeURIComponent(it.slug) + '">' +
                    '<span class="sv-ic">' + ICON.bookmark + '</span>' +
                    '<span class="sv-tx">' + esc(it.judul) + '</span>' +
                '</a>' +
                '<button type="button" class="sv-rm" data-idx="' + idx + '" title="Hapus dari simpanan">' + ICON.x + '</button>' +
            '</div>';
        }).join('');
        list.querySelectorAll('.sv-rm').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var arr = loadSaved();
                arr.splice(parseInt(this.dataset.idx, 10), 1);
                localStorage.setItem('bdBookmarks', JSON.stringify(arr));
                renderSaved();
            });
        });
    }
    renderSaved();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>