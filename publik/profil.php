<?php
$pageTitle = 'Profil & Legalitas';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();

$db = Database::getInstance();

function getSetting($db, $key, $default = '') {
    $st = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $st->execute([$key]);
    $r = $st->fetch();
    return $r ? $r['setting_value'] : $default;
}

$visi = getSetting($db, 'visi', 'Menjadi lembaga penjaminan mutu internal yang unggul dan terpercaya.');
$misiArr = array_filter(array_map('trim', explode("\n", getSetting($db, 'misi'))));

$tupoksiArr = [];
foreach (array_filter(array_map('trim', explode("\n", getSetting($db, 'tupoksi')))) as $line) {
    $p = array_map('trim', explode('|', $line));
    if (count($p) >= 2) $tupoksiArr[] = ['judul' => $p[0], 'icon' => $p[1] ?? '📌', 'desk' => $p[2] ?? ''];
}

$struktur = $db->query("SELECT * FROM struktur_org ORDER BY urutan ASC, id_struktur ASC")->fetchAll();
$legalitas = $db->query("SELECT * FROM legalitas ORDER BY urutan ASC, id_legalitas ASC")->fetchAll();
$kepalaLpm = $db->query("SELECT nama_lengkap, foto FROM users WHERE id_role = 2 LIMIT 1")->fetch();

// ===== KUMPULAN ICON SVG (GAYA MODERN & PROFESSIONAL) =====
$ic = [
    'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'trophy' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>',
    'search' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
    'layout_dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>',
    'target' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    'refresh_cw' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>',
    'clipboard_list' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>',
    'globe' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>',
    'handshake' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/></svg>',
    'rocket' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
    'settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.72l-.15.1a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l-.22-.38a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
    'trending_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
    'user' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>',
    'file_text' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>',
    'download' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>',
    'send' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
    'arrow_right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
    'flag' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/></svg>',
    'shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>',
    'eye' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
    'help_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
    'chevron_down' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
    'chevron_right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>',
];

// Helper mapping ikon database
$mapDB = ['📌'=>'flag','📋'=>'clipboard_list','🔍'=>'search','🎯'=>'target','📊'=>'layout_dashboard','🏆'=>'trophy','🔄'=>'refresh_cw','📈'=>'trending_up','⚙️'=>'settings','🎓'=>'award','🚀'=>'rocket','📜'=>'file_text'];

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   PROFIL STANDALONE ULTIMATE v11.0 — Self-Contained Premium
============================================================ */

.pr-wrap {
    --p: #0F3D5C; --pl: #1A5A82; --pd: #092A40;
    --a: #C9A227; --al: #E8C55A;
    --bg: #F7F9FC; --card: #FFFFFF;
    --td: #1E293B; --tm: #64748B;
    --bd: #E2E8F0;
    --r: 16px; --rl: 22px;
    --eo: cubic-bezier(.22, 1, .36, 1);
    --es: cubic-bezier(.34, 1.56, .64, 1);
    --tr: all .3s var(--eo);
}
.pr-wrap * { box-sizing: border-box; }

/* ===== SCROLL PROGRESS ===== */
.pr-progress {
    position: fixed; top: 0; left: 0; width: 100%; height: 4px; z-index: 1000;
    background: rgba(15,61,92,.05);
}
.pr-progress-bar {
    height: 100%; width: 0%; background: linear-gradient(90deg, #C9A227, #E8C55A);
    transition: width .1s linear;
}

/* ===== FLOATING TOC ===== */
.pr-toc {
    position: fixed; right: 24px; top: 50%; transform: translateY(-50%);
    display: flex; flex-direction: column; gap: 14px; z-index: 100;
    opacity: 0; pointer-events: none; transition: opacity .4s;
}
.pr-toc.visible { opacity: 1; pointer-events: auto; }
.pr-toc-link {
    width: 8px; height: 8px; border-radius: 50%;
    background: rgba(15,61,92,.25); position: relative;
    transition: .3s; display: block; text-decoration: none;
}
.pr-toc-link::before {
    content: attr(data-title); position: absolute; right: 20px; top: 50%; transform: translateY(-50%);
    background: #092A40; color: #fff; padding: 6px 12px; border-radius: 8px;
    font-size: 11px; font-weight: 600; white-space: nowrap;
    opacity: 0; pointer-events: none; transition: .3s;
    box-shadow: 0 8px 20px rgba(0,0,0,.15);
}
.pr-toc-link:hover::before { opacity: 1; right: 24px; }
.pr-toc-link.active { background: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.2); transform: scale(1.4); }
@media (max-width: 1200px) { .pr-toc { display: none; } }

/* === HERO === */
.pr-hero {
    min-height: 88vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 140px 0 100px;
    display: flex; align-items: center;
}
.pr-hero::before {
    content: ''; position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 15% 30%, rgba(201,162,39,.3), transparent 55%),
        radial-gradient(ellipse 60% 70% at 85% 20%, rgba(26,90,130,.5), transparent 55%),
        radial-gradient(ellipse 60% 50% at 70% 80%, rgba(232,197,90,.2), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: prShift 16s ease-in-out infinite;
}
@keyframes prShift {
    0%, 100% { filter: hue-rotate(0deg); transform: scale(1); }
    50% { filter: hue-rotate(12deg); transform: scale(1.04); }
}
.pr-hero::after {
    content: ''; position: absolute; inset: 0; pointer-events: none; opacity: .35;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.pr-hero-spot {
    position: absolute; inset: 0; z-index: 1; pointer-events: none;
    background: radial-gradient(600px circle at var(--mx,50%) var(--my,50%), rgba(201,162,39,.25), transparent 40%);
    transition: background .1s ease;
}
.pr-hero-inner { position: relative; z-index: 3; max-width: 1000px; margin: 0 auto; text-align: center; padding: 0 24px; }

.pr-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 28px; position: relative; overflow: hidden;
}
.pr-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.5), transparent);
    animation: prShimmer 3.5s ease-in-out infinite;
}
@keyframes prShimmer { to { left: 200%; } }
.pr-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #E8C55A; box-shadow: 0 0 14px #E8C55A;
    animation: prPulse 2s ease infinite;
}
@keyframes prPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.pr-hero h1 {
    font-size: clamp(40px, 6.5vw, 84px); font-weight: 800;
    line-height: 1.04; margin: 0 0 24px; letter-spacing: -.04em;
    color: #fff; font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
}
.pr-hero h1 .gr {
    background: linear-gradient(120deg, #E8C55A 0%, #C9A227 25%, #F7E491 50%, #C9A227 75%, #E8C55A 100%);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: prShine 4s linear infinite; font-style: italic;
}
@keyframes prShine { to { background-position: 200% center; } }
.pr-hero-lead {
    font-size: clamp(16px, 1.5vw, 18px); color: rgba(255,255,255,.75);
    max-width: 660px; margin: 0 auto 44px; line-height: 1.65;
}
.pr-hero-lead strong { color: #E8C55A; }

.pr-stats {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,.18); border-radius: 24px;
    padding: 2px; max-width: 780px; margin: 0 auto;
    box-shadow: 0 20px 60px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.1);
}
.pr-st {
    padding: 22px 16px; text-align: center;
    background: rgba(6,29,46,.55); backdrop-filter: blur(10px);
    transition: .3s;
}
.pr-st:first-child { border-radius: 22px 0 0 22px; }
.pr-st:last-child { border-radius: 0 22px 22px 0; }
.pr-st:hover { background: rgba(201,162,39,.14); }
.pr-st-n {
    font-size: clamp(26px, 3.4vw, 38px); font-weight: 800; line-height: 1;
    background: linear-gradient(180deg, #F7E491 0%, #C9A227 100%);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    letter-spacing: -.02em; display: block; margin-bottom: 4px;
}
.pr-st-l { font-size: 10.5px; color: rgba(255,255,255,.65); letter-spacing: 1.2px; text-transform: uppercase; font-weight: 600; }

.pr-fc {
    position: absolute; padding: 12px 18px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; color: #fff;
    display: inline-flex; align-items: center; gap: 10px;
    box-shadow: 0 12px 40px rgba(0,0,0,.35);
    animation: prFloat 7s ease-in-out infinite;
    z-index: 2; pointer-events: none;
}
.pr-fc span {
    width: 28px; height: 28px; border-radius: 8px;
    background: linear-gradient(135deg, #C9A227, #E8C55A); color: #092A40;
    display: grid; place-items: center; font-size: 14px;
}
.pr-fc1 { top: 22%; left: 6%; animation-delay: 0s; }
.pr-fc2 { top: 28%; right: 6%; animation-delay: -2s; }
.pr-fc3 { bottom: 24%; left: 8%; animation-delay: -4s; }
.pr-fc4 { bottom: 18%; right: 8%; animation-delay: -6s; }
@keyframes prFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
@media (max-width: 992px) { .pr-fc { display: none; } .pr-stats { grid-template-columns: repeat(2, 1fr); } }

/* === SECTIONS === */
.pr-sec { padding: 100px 0; position: relative; }
.pr-sec.light { background: #fff; }
.pr-sec.soft { background: linear-gradient(180deg, #fff, #F7F9FC); }
.pr-sec.dark { background: linear-gradient(180deg, #061D2E, #0F3D5C 55%, #092A40); color: #fff; }

.pr-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.pr-head { max-width: 720px; margin: 0 auto 60px; text-align: center; }
.pr-tag {
    display: inline-flex; align-items: center; gap: 8px; padding: 6px 18px; border-radius: 50px;
    background: rgba(201,162,39,.1); color: #C9A227;
    font-size: 11px; font-weight: 800; letter-spacing: 2px;
    text-transform: uppercase; margin-bottom: 18px;
    border: 1px solid rgba(201,162,39,.2);
}
.pr-sec.dark .pr-tag { background: rgba(201,162,39,.18); color: #E8C55A; border-color: rgba(201,162,39,.3); }
.pr-head h2 {
    font-size: clamp(32px, 4.5vw, 52px); font-weight: 800;
    color: #092A40; letter-spacing: -.02em; line-height: 1.1;
    margin: 0 0 16px;
}
.pr-sec.dark .pr-head h2 { color: #fff; }
.pr-head h2 .gr {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: prShine 4s linear infinite; font-style: italic;
}
.pr-head h2 em { color: #C9A227; font-style: normal; }
.pr-head p { color: #64748B; font-size: 17px; margin: 0; }
.pr-sec.dark .pr-head p { color: rgba(255,255,255,.7); }

/* === BENTO ABOUT === */
.pr-bento {
    display: grid; grid-template-columns: repeat(6, 1fr);
    grid-auto-rows: minmax(140px, auto); gap: 20px;
    perspective: 1400px;
}
.pr-bn {
    position: relative; overflow: hidden; border-radius: 22px;
    padding: 28px; background: #fff; border: 1px solid #E2E8F0;
    transition: transform .6s var(--eo), box-shadow .4s;
    box-shadow: 0 6px 24px rgba(15,61,92,.06);
    transform-style: preserve-3d;
}
.pr-bn::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(600px circle at var(--mx,50%) var(--my,50%), rgba(201,162,39,.12), transparent 40%);
    opacity: 0; transition: .4s;
}
.pr-bn:hover::before { opacity: 1; }
.pr-bn:hover { transform: translateY(-8px) rotateX(2deg) rotateY(-2deg); box-shadow: 0 30px 60px rgba(15,61,92,.14); border-color: rgba(201,162,39,.4); }

.pr-bn-h {
    grid-column: span 3; grid-row: span 2;
    background: linear-gradient(135deg, #061D2E, #0F3D5C 55%, #12365B);
    color: #fff; padding: 40px; border: none;
}
.pr-bn-h::after {
    content: ''; position: absolute; bottom: -80px; right: -80px;
    width: 300px; height: 300px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.28), transparent 70%);
    animation: prOrb 9s ease-in-out infinite;
}
@keyframes prOrb { 0%,100% { transform: scale(1); } 50% { transform: scale(1.18); } }
.pr-bn-h-ey {
    display: inline-block; padding: 5px 14px; border-radius: 50px;
    background: rgba(201,162,39,.18); color: #E8C55A;
    font-size: 10.5px; font-weight: 800; letter-spacing: 1.6px; text-transform: uppercase;
    margin-bottom: 18px; border: 1px solid rgba(201,162,39,.3);
    position: relative; z-index: 1;
}
.pr-bn-h h3 {
    font-size: clamp(22px, 2.4vw, 30px); font-weight: 800;
    line-height: 1.15; margin: 0 0 14px; letter-spacing: -.02em;
    position: relative; z-index: 1; color: #fff;
}
.pr-bn-h p { font-size: 15px; opacity: .85; line-height: 1.65; max-width: 420px; position: relative; z-index: 1; margin: 0; }
.pr-bn-h-drop {
    font-size: clamp(52px, 7vw, 88px); font-weight: 900;
    color: #E8C55A; float: left; line-height: .85;
    margin: 6px 16px 0 0; text-shadow: 0 4px 20px rgba(201,162,39,.4);
    position: relative; z-index: 1;
}

.pr-bn-sm { grid-column: span 3; }
.pr-bn-xs { grid-column: span 2; }
.pr-bn .pr-bn-n {
    position: absolute; top: 16px; right: 20px;
    font-size: 46px; font-weight: 900; color: rgba(15,61,92,.05);
    line-height: 1; letter-spacing: -.04em;
}
.pr-bn-h .pr-bn-n { color: rgba(255,255,255,.08); font-size: 130px; }
.pr-bn-ic {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    color: #fff; font-size: 22px; display: grid; place-items: center;
    margin-bottom: 14px;
    box-shadow: 0 8px 20px rgba(15,61,92,.22);
    transition: .4s var(--es);
}
.pr-bn:hover .pr-bn-ic { transform: scale(1.1) rotate(-8deg); }
.pr-bn h3 { font-size: 18px; color: #092A40; margin: 0 0 6px; letter-spacing: -.01em; }
.pr-bn p { font-size: 14px; color: #64748B; line-height: 1.55; margin: 0; }
.pr-bn.hl { background: linear-gradient(135deg, rgba(201,162,39,.08), rgba(15,61,92,.04)); border-color: rgba(201,162,39,.3); }
.pr-bn.hl h3 span { color: #C9A227; }

@media (max-width: 992px) {
    .pr-bento { grid-template-columns: repeat(2, 1fr); }
    .pr-bn-h, .pr-bn-sm, .pr-bn-xs { grid-column: span 2; grid-row: auto; }
}

/* === CORE VALUES === */
.pr-values-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; }
.pr-val {
    background: #fff; padding: 32px 24px; border-radius: 24px;
    border: 1px solid #E2E8F0; text-align: center;
    transition: .4s var(--eo); box-shadow: 0 4px 20px rgba(15,61,92,.04);
}
.pr-val:hover { transform: translateY(-8px); border-color: rgba(201,162,39,.4); box-shadow: 0 20px 40px rgba(15,61,92,.1); }
.pr-val-ic {
    width: 64px; height: 64px; margin: 0 auto 20px; border-radius: 18px;
    background: linear-gradient(135deg, rgba(201,162,39,.1), rgba(15,61,92,.05));
    color: #C9A227; display: grid; place-items: center; font-size: 28px;
    transition: .4s var(--es);
}
.pr-val:hover .pr-val-ic { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #092A40; transform: scale(1.1) rotate(-5deg); }
.pr-val h3 { font-size: 18px; color: #092A40; margin: 0 0 8px; }
.pr-val p { font-size: 14px; color: #64748B; line-height: 1.6; margin: 0; }

/* === VISI MISI GLASS === */
.pr-vm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; position: relative; z-index: 1; }
.pr-sec.dark::before {
    content: ''; position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 15% 30%, rgba(201,162,39,.22), transparent 55%),
        radial-gradient(ellipse 50% 50% at 85% 70%, rgba(26,90,130,.4), transparent 55%);
    animation: prShift 18s ease-in-out infinite;
}
.pr-vm {
    position: relative; overflow: hidden;
    background: rgba(255,255,255,.05); backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,.12); border-radius: 28px;
    padding: 44px 38px; transition: .5s var(--eo);
    transform-style: preserve-3d;
}
.pr-vm:hover { background: rgba(255,255,255,.08); border-color: rgba(201,162,39,.4); transform: translateY(-6px); }
.pr-vm::before {
    content: ''; position: absolute; top: -1px; left: -1px; right: -1px; height: 3px;
    background: linear-gradient(90deg, transparent, #C9A227, transparent);
}
.pr-vm-wm {
    position: absolute; top: -20px; right: 10px;
    font-size: 180px; font-weight: 900; color: rgba(201,162,39,.05);
    line-height: 1; pointer-events: none; letter-spacing: -.04em;
}
.pr-vm-ic {
    width: 64px; height: 64px; border-radius: 18px;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; font-size: 28px; display: grid; place-items: center;
    margin-bottom: 22px;
    box-shadow: 0 12px 32px rgba(201,162,39,.4);
}
.pr-vm h3 { font-size: 28px; color: #fff; margin: 0 0 16px; letter-spacing: -.02em; position: relative; z-index: 1; }
.pr-vm-txt {
    font-size: 17px; color: rgba(255,255,255,.85); line-height: 1.6;
    font-style: italic; position: relative; z-index: 1; margin: 0;
}
.pr-vm-list { list-style: none; padding: 0; margin: 0; position: relative; z-index: 1; }
.pr-vm-list li {
    display: flex; gap: 16px; align-items: flex-start;
    padding: 14px 0; border-bottom: 1px dashed rgba(255,255,255,.1);
    transition: .3s;
}
.pr-vm-list li:last-child { border-bottom: none; }
.pr-vm-list li:hover { transform: translateX(6px); }
.pr-vm-list .n {
    min-width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0;
    background: rgba(201,162,39,.18); color: #E8C55A;
    font-weight: 800; font-size: 13px; display: grid; place-items: center;
    border: 1px solid rgba(201,162,39,.3);
}
.pr-vm-list li span { color: rgba(255,255,255,.85); font-size: 15px; line-height: 1.55; }
@media (max-width: 992px) {
    .pr-vm-grid { grid-template-columns: 1fr; }
    .pr-vm { padding: 32px 24px; }
    .pr-vm-wm { font-size: 120px; }
}

/* === TIMELINE PPEPP === */
.pr-sec.light::before {
    content: ''; position: absolute; inset: 0; opacity: .3; pointer-events: none;
    background-image: radial-gradient(rgba(15,61,92,.08) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.pr-tl-wrap {
    position: relative; padding: 40px 0; z-index: 1;
    overflow-x: auto; scrollbar-width: none;
}
.pr-tl-wrap::-webkit-scrollbar { display: none; }
.pr-tl-line {
    position: absolute; top: 72px; left: 10%; right: 10%; height: 3px;
    background: linear-gradient(90deg, #0F3D5C, #C9A227, #0F3D5C);
    border-radius: 3px; z-index: 0;
}
.pr-tl-track {
    display: grid; grid-template-columns: repeat(5, 1fr);
    gap: 16px; position: relative; z-index: 1; min-width: 1100px;
}
.pr-tl-step { text-align: center; position: relative; padding: 0 10px; }
.pr-tl-step-n {
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; font-weight: 900; font-size: 22px;
    display: grid; place-items: center; margin: 0 auto 20px;
    box-shadow: 0 10px 28px rgba(201,162,39,.45);
    border: 5px solid #fff; position: relative;
    transition: .4s var(--es);
}
.pr-tl-step:hover .pr-tl-step-n { transform: scale(1.18) rotate(-8deg); }
.pr-tl-step-ic {
    width: 68px; height: 68px; border-radius: 18px; margin: 0 auto 18px;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    display: grid; place-items: center; font-size: 28px; color: #fff;
    box-shadow: 0 10px 24px rgba(15,61,92,.25);
    transition: .4s var(--es);
}
.pr-tl-step:hover .pr-tl-step-ic { transform: translateY(-5px); box-shadow: 0 16px 34px rgba(15,61,92,.35); }
.pr-tl-step h3 { font-size: 18px; color: #092A40; margin: 0 0 10px; letter-spacing: -.01em; }
.pr-tl-step p { font-size: 13px; color: #64748B; line-height: 1.55; max-width: 220px; margin: 0 auto; }

/* === STRUKTUR TREE === */
.pr-tree {
    display: flex; flex-direction: column; align-items: center;
    gap: 60px; position: relative; padding: 20px 0; z-index: 1;
}
.pr-tree::before {
    content: ''; position: absolute; top: 100px; bottom: 100px;
    left: 50%; width: 2px;
    background: linear-gradient(to bottom, #C9A227, transparent);
    transform: translateX(-50%);
}
.pr-tree-level {
    display: flex; gap: 24px; justify-content: center; flex-wrap: wrap;
    position: relative; z-index: 1;
}
.pr-tree-node {
    background: rgba(255,255,255,.06); backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,.14); border-radius: 22px;
    padding: 26px 22px; text-align: center;
    min-width: 220px; max-width: 260px;
    transition: .4s var(--eo); position: relative;
    box-shadow: 0 14px 40px rgba(0,0,0,.3);
}
.pr-tree-node:hover { background: rgba(201,162,39,.1); border-color: rgba(201,162,39,.45); transform: translateY(-6px); }
.pr-tree-ava {
    width: 90px; height: 90px; border-radius: 50%; margin: 0 auto 16px;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    display: grid; place-items: center; font-size: 36px; color: #092A40;
    position: relative; overflow: hidden;
    box-shadow: 0 0 0 4px rgba(255,255,255,.1), 0 0 0 8px rgba(201,162,39,.2), 0 10px 30px rgba(0,0,0,.3);
}
.pr-tree-ava img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.pr-tree-node h4 { font-size: 16px; color: #fff; margin: 0 0 4px; letter-spacing: -.01em; }
.pr-tree-node .jab {
    display: inline-block; padding: 4px 12px; border-radius: 50px;
    background: rgba(201,162,39,.2); color: #E8C55A;
    font-size: 11px; font-weight: 700; letter-spacing: .8px;
    margin: 8px 0 6px; border: 1px solid rgba(201,162,39,.3);
}
.pr-tree-node small { color: rgba(255,255,255,.55); font-size: 12px; }
.pr-tree-node.lead {
    background: linear-gradient(135deg, rgba(201,162,39,.15), rgba(201,162,39,.05));
    border-color: rgba(201,162,39,.5);
    box-shadow: 0 20px 50px rgba(201,162,39,.25), 0 0 0 1px rgba(201,162,39,.2);
    min-width: 260px;
}
.pr-tree-node.lead .pr-tree-ava { width: 110px; height: 110px; font-size: 44px; }
.pr-tree-node.lead h4 { font-size: 19px; }
.pr-tree-conn {
    position: relative; width: 100%; display: flex; justify-content: center;
    height: 40px;
}
.pr-tree-conn::before {
    content: ''; position: absolute; top: 0; left: 50%;
    width: 2px; height: 20px;
    background: linear-gradient(to bottom, #C9A227, rgba(201,162,39,.3));
    transform: translateX(-50%);
}
.pr-tree-conn::after {
    content: ''; position: absolute; top: 20px;
    left: 25%; right: 25%; height: 2px;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.4), transparent);
}
@media (max-width: 768px) {
    .pr-tree::before { display: none; }
    .pr-tree-level { flex-direction: column; align-items: center; }
    .pr-tree-conn { display: none; }
}

/* === TUPOKSI 3D === */
.pr-tf-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 22px; perspective: 1400px;
}
.pr-tf {
    background: #fff; border-radius: 22px; padding: 32px;
    border: 1px solid #E2E8F0; position: relative; overflow: hidden;
    transition: transform .6s var(--eo), box-shadow .4s, border-color .4s;
    transform-style: preserve-3d;
    box-shadow: 0 6px 24px rgba(15,61,92,.06);
}
.pr-tf::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #C9A227, #0F3D5C);
    transform: scaleX(0); transform-origin: left; transition: transform .5s var(--eo);
}
.pr-tf:hover::before { transform: scaleX(1); }
.pr-tf::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(500px circle at var(--mx,50%) var(--my,50%), rgba(201,162,39,.12), transparent 40%);
    opacity: 0; transition: .4s;
}
.pr-tf:hover::after { opacity: 1; }
.pr-tf:hover { transform: translateY(-8px) rotateX(2deg) rotateY(-2deg); box-shadow: 0 30px 60px rgba(15,61,92,.15); border-color: rgba(201,162,39,.4); }
.pr-tf-ic {
    width: 64px; height: 64px; border-radius: 18px; margin-bottom: 20px;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    color: #fff; font-size: 28px; display: grid; place-items: center;
    box-shadow: 0 10px 26px rgba(15,61,92,.25);
    transition: .4s var(--es); position: relative; z-index: 1;
}
.pr-tf:hover .pr-tf-ic { transform: scale(1.1) rotate(-8deg); }
.pr-tf h3 { font-size: 19px; color: #092A40; margin: 0 0 10px; letter-spacing: -.01em; position: relative; z-index: 1; }
.pr-tf p { font-size: 14.5px; color: #64748B; line-height: 1.6; position: relative; z-index: 1; margin: 0; }

/* === LEGALITAS === */
.pr-lg-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 22px;
}
.pr-lg {
    background: #fff; border-radius: 22px; padding: 28px;
    border: 1px solid #E2E8F0; position: relative; overflow: hidden;
    transition: .4s var(--eo);
    box-shadow: 0 6px 22px rgba(15,61,92,.06);
    display: flex; flex-direction: column;
}
.pr-lg:hover { transform: translateY(-6px); box-shadow: 0 24px 50px rgba(15,61,92,.14); border-color: rgba(201,162,39,.4); }
.pr-lg::before {
    content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 4px;
    background: linear-gradient(to bottom, #C9A227, #0F3D5C);
}
.pr-lg-year {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 50px;
    background: rgba(15,61,92,.06); color: #0F3D5C;
    font-size: 11px; font-weight: 800; letter-spacing: 1px;
    margin-bottom: 14px; width: fit-content;
}
.pr-lg-ic {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg, rgba(201,162,39,.12), rgba(15,61,92,.08));
    display: grid; place-items: center; font-size: 22px; margin-bottom: 14px; color: #C9A227;
    transition: .4s var(--es);
}
.pr-lg:hover .pr-lg-ic { transform: scale(1.12) rotate(-8deg); background: linear-gradient(135deg, #C9A227, #E8C55A); color: #092A40; }
.pr-lg h4 { font-size: 15px; color: #092A40; margin: 0 0 10px; letter-spacing: -.01em; line-height: 1.35; }
.pr-lg-num { font-size: 12px; color: #C9A227; font-weight: 700; letter-spacing: .5px; margin-bottom: 14px; }
.pr-lg-foot {
    margin-top: auto; padding-top: 16px; border-top: 1px dashed #E2E8F0;
    display: flex; justify-content: space-between; align-items: center;
}
.pr-lg-dl {
    padding: 8px 18px; border-radius: 50px; font-size: 12px; font-weight: 700;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
    transition: .3s; box-shadow: 0 6px 18px rgba(201,162,39,.3);
}
.pr-lg-dl:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(201,162,39,.45); }
.pr-lg-empty { color: #64748B; font-size: 12px; font-style: italic; }

/* === FAQ ACCORDION === */
.pr-faq-list { max-width: 860px; margin: 0 auto; display: flex; flex-direction: column; gap: 16px; }
.pr-faq-item {
    background: #fff; border: 1px solid #E2E8F0; border-radius: 18px;
    overflow: hidden; transition: .3s var(--eo);
    box-shadow: 0 4px 12px rgba(15,61,92,.04);
}
.pr-faq-item:hover { border-color: rgba(201,162,39,.3); }
.pr-faq-item.active { border-color: rgba(201,162,39,.5); box-shadow: 0 10px 30px rgba(201,162,39,.1); }
.pr-faq-q {
    width: 100%; padding: 22px 28px; background: none; border: none;
    display: flex; align-items: center; gap: 16px; text-align: left;
    font-size: 16px; font-weight: 700; color: #092A40; cursor: pointer;
}
.pr-faq-ic { color: #C9A227; flex-shrink: 0; display: flex; font-size: 22px; }
.pr-faq-q span:nth-child(2) { flex: 1; }
.pr-faq-arrow { color: #64748B; transition: transform .3s var(--eo); flex-shrink: 0; display: flex; font-size: 18px; }
.pr-faq-item.active .pr-faq-arrow { transform: rotate(180deg); color: #C9A227; }
.pr-faq-a {
    max-height: 0; overflow: hidden; transition: max-height .4s var(--eo), padding .4s var(--eo);
    padding: 0 28px;
}
.pr-faq-item.active .pr-faq-a { max-height: 200px; padding: 0 28px 24px; padding-left: 68px; }
.pr-faq-a p { color: #64748B; font-size: 14.5px; line-height: 1.65; margin: 0; }

/* === CTA === */
.pr-cta {
    padding: 100px 0; position: relative; overflow: hidden;
    background: #061D2E; color: #fff;
}
.pr-cta-canvas { position: absolute; inset: 0; z-index: 0; }
.pr-cta-inner { position: relative; z-index: 1; text-align: center; max-width: 760px; margin: 0 auto; padding: 0 24px; }
.pr-cta h2 {
    font-size: clamp(32px, 4.5vw, 60px); font-weight: 800;
    line-height: 1.08; margin: 20px 0 18px; letter-spacing: -.03em;
    color: #fff;
}
.pr-cta p { font-size: 17px; color: rgba(255,255,255,.75); margin-bottom: 38px; }
.pr-cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.pr-btn {
    padding: 15px 32px; border-radius: 50px; font-weight: 700; font-size: 15px;
    text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
    transition: .4s var(--eo); border: none; cursor: pointer;
}
.pr-btn-primary {
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; box-shadow: 0 10px 40px rgba(201,162,39,.45);
}
.pr-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 18px 50px rgba(201,162,39,.65); }
.pr-btn-ghost {
    background: rgba(255,255,255,.05); backdrop-filter: blur(12px);
    border: 1.5px solid rgba(255,255,255,.18); color: #fff;
}
.pr-btn-ghost:hover { background: rgba(255,255,255,.12); border-color: rgba(201,162,39,.6); transform: translateY(-3px); }

.pr-rev { opacity: 0; transform: translateY(40px); transition: opacity .8s var(--eo), transform .8s var(--eo); }
.pr-rev.in { opacity: 1; transform: none; }

.pr-empty { grid-column: 1/-1; text-align: center; padding: 60px; color: #64748B; }
.pr-empty.dark { color: rgba(255,255,255,.5); }
</style>

<div class="pr-progress"><div class="pr-progress-bar" id="prProg"></div></div>
<nav class="pr-toc" id="prToc">
    <a href="#tentang" class="pr-toc-link" data-title="Tentang"></a>
    <a href="#visi-misi" class="pr-toc-link" data-title="Visi & Misi"></a>
    <a href="#nilai-utama" class="pr-toc-link" data-title="Nilai Utama"></a>
    <a href="#ppepp" class="pr-toc-link" data-title="PPEPP"></a>
    <a href="#struktur" class="pr-toc-link" data-title="Struktur"></a>
    <a href="#tupoksi" class="pr-toc-link" data-title="Tupoksi"></a>
    <a href="#legalitas" class="pr-toc-link" data-title="Legalitas"></a>
    <a href="#milestone" class="pr-toc-link" data-title="Milestone"></a>
    <a href="#faq" class="pr-toc-link" data-title="FAQ"></a>
</nav>

<div class="pr-wrap">

<!-- ===== HERO ===== -->
<section class="pr-hero" id="hero">
    <div class="pr-hero-spot" id="prSpot"></div>

    <div class="pr-fc pr-fc1"><span><?= $ic['award'] ?></span> BAN-PT Accredited</div>
    <div class="pr-fc pr-fc2"><span><?= $ic['trophy'] ?></span> Unggul Target</div>
    <div class="pr-fc pr-fc3"><span><?= $ic['search'] ?></span> AMI Tahunan</div>
    <div class="pr-fc pr-fc4"><span><?= $ic['layout_dashboard'] ?></span> PPEPP Active</div>

    <div class="pr-hero-inner">
        <span class="pr-badge"><span class="pr-badge-dot"></span> Tentang Lembaga Penjaminan Mutu</span>
        <h1>Garda Terdepan<br><span class="gr">Budaya Mutu</span> Kampus</h1>
        <p class="pr-hero-lead">
            Memastikan kualitas akademik dan tata kelola perguruan tinggi memenuhi bahkan
            melampaui standar <strong>SN-Dikti, BAN-PT, dan LAM</strong>
            melalui siklus PPEPP yang berkelanjutan.
        </p>
        <div class="pr-stats">
            <div class="pr-st"><span class="pr-st-n" data-count="5">0</span><span class="pr-st-l">Divisi</span></div>
            <div class="pr-st"><span class="pr-st-n" data-count="24">0</span><span class="pr-st-l">Auditor Aktif</span></div>
            <div class="pr-st"><span class="pr-st-n">100<span style="font-size:.5em;">%</span></span><span class="pr-st-l">Prodi Ter-AMI</span></div>
            <div class="pr-st"><span class="pr-st-n" data-count="9">0</span><span class="pr-st-l">Standar Mutu</span></div>
        </div>
    </div>
</section>

<!-- ===== BENTO ABOUT ===== -->
<section class="pr-sec light" id="tentang">
    <div class="pr-container">
        <div class="pr-head pr-rev">
            <span class="pr-tag">Sekilas Tentang Kami</span>
            <h2>Membangun Fondasi<br>Mutu yang <em>Kokoh</em></h2>
            <p>LPM adalah unit non-struktural yang bertanggung jawab langsung kepada Rektor, menjalankan sistem penjaminan mutu internal secara menyeluruh.</p>
        </div>
        <div class="pr-bento">
            <div class="pr-bn pr-bn-h pr-rev">
                <span class="pr-bn-n">01</span>
                <div class="pr-bn-h-ey">Mandat Kami</div>
                <div><span class="pr-bn-h-drop">L</span>
                    <h3>embaga Penjaminan Mutu Internal hadir untuk memastikan seluruh layanan tridharma memenuhi standar nasional bahkan internasional.</h3>
                </div>
            </div>
            <div class="pr-bn pr-bn-sm hl pr-rev">
                <span class="pr-bn-n">02</span>
                <div class="pr-bn-ic"><?= $ic['target'] ?></div>
                <h3>Bertanggung Jawab kepada <span>Rektor</span></h3>
                <p>Fungsi pengawasan dan penjaminan mutu secara independen.</p>
            </div>
            <div class="pr-bn pr-bn-sm pr-rev">
                <span class="pr-bn-n">03</span>
                <div class="pr-bn-ic"><?= $ic['refresh_cw'] ?></div>
                <h3>Siklus <span style="color:#C9A227;">PPEPP</span></h3>
                <p>Penetapan, Pelaksanaan, Evaluasi, Pengendalian, Peningkatan.</p>
            </div>
            <div class="pr-bn pr-bn-xs pr-rev">
                <span class="pr-bn-n">04</span>
                <div class="pr-bn-ic"><?= $ic['clipboard_list'] ?></div>
                <h3>Standar SN-Dikti</h3>
                <p>Turunan 9 standar.</p>
            </div>
            <div class="pr-bn pr-bn-xs pr-rev">
                <span class="pr-bn-n">05</span>
                <div class="pr-bn-ic"><?= $ic['globe'] ?></div>
                <h3>Orientasi Global</h3>
                <p>Acuan internasional.</p>
            </div>
            <div class="pr-bn pr-bn-xs pr-rev">
                <span class="pr-bn-n">06</span>
                <div class="pr-bn-ic"><?= $ic['handshake'] ?></div>
                <h3>Kolaboratif</h3>
                <p>Bersama civitas.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== VISI MISI ===== -->
<section class="pr-sec dark" id="visi-misi">
    <div class="pr-container">
        <div class="pr-head pr-rev">
            <span class="pr-tag">Arah & Komitmen</span>
            <h2>Visi & <span class="gr">Misi</span> Kami</h2>
            <p>Panduan strategis LPM dalam membangun budaya mutu.</p>
        </div>
        <div class="pr-vm-grid">
            <div class="pr-vm pr-rev">
                <span class="pr-vm-wm">V</span>
                <div class="pr-vm-ic"><?= $ic['target'] ?></div>
                <h3>Visi</h3>
                <p class="pr-vm-txt"><?= Security::e($visi) ?></p>
            </div>
            <div class="pr-vm pr-rev">
                <span class="pr-vm-wm">M</span>
                <div class="pr-vm-ic" style="background:linear-gradient(135deg,#E8C55A,#C9A227);"><?= $ic['rocket'] ?></div>
                <h3>Misi</h3>
                <ul class="pr-vm-list">
                    <?php $i = 1; foreach ($misiArr as $m): ?>
                    <li><span class="n"><?= $i++ ?></span><span><?= Security::e($m) ?></span></li>
                    <?php endforeach; ?>
                    <?php if (empty($misiArr)): ?><li><span style="opacity:.6;">Misi belum diatur.</span></li><?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ===== CORE VALUES ===== -->
<section class="pr-sec soft" id="nilai-utama">
    <div class="pr-container">
        <div class="pr-head pr-rev">
            <span class="pr-tag">Core Values</span>
            <h2>Nilai-Nilai <em>Utama</em> Kami</h2>
            <p>Prinsip dasar yang menjadi landasan setiap langkah penjaminan mutu.</p>
        </div>
        <div class="pr-values-grid">
            <div class="pr-val pr-rev">
                <div class="pr-val-ic"><?= $ic['shield'] ?></div>
                <h3>Integritas</h3>
                <p>Menjunjung tinggi kejujuran dan objektivitas dalam setiap evaluasi dan audit.</p>
            </div>
            <div class="pr-val pr-rev">
                <div class="pr-val-ic"><?= $ic['award'] ?></div>
                <h3>Profesional</h3>
                <p>Bekerja dengan standar tinggi, kompeten, dan berorientasi pada hasil terbaik.</p>
            </div>
            <div class="pr-val pr-rev">
                <div class="pr-val-ic"><?= $ic['eye'] ?></div>
                <h3>Transparan</h3>
                <p>Terbuka dalam proses, akuntabel dalam pelaporan, dan dapat dipertanggungjawabkan.</p>
            </div>
            <div class="pr-val pr-rev">
                <div class="pr-val-ic"><?= $ic['handshake'] ?></div>
                <h3>Kolaboratif</h3>
                <p>Bersinergi dengan seluruh unit dan civitas akademika untuk mencapai mutu paripurna.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== TIMELINE PPEPP ===== -->
<section class="pr-sec light" id="ppepp">
    <div class="pr-container">
        <div class="pr-head pr-rev">
            <span class="pr-tag">Siklus Kerja</span>
            <h2>Penjaminan Mutu <em>Berkelanjutan</em></h2>
            <p>Alur kerja PPEPP yang membentuk spiral peningkatan.</p>
        </div>
        <div class="pr-tl-wrap">
            <div class="pr-tl-line"></div>
            <div class="pr-tl-track">
                <?php foreach ([
                    ['01', $ic['clipboard_list'], 'Penetapan Standar', 'Kebijakan & standar SN-Dikti.'],
                    ['02', $ic['settings'], 'Pelaksanaan', 'Implementasi di seluruh unit.'],
                    ['03', $ic['search'], 'Evaluasi & AMI', 'Audit mutu internal tahunan.'],
                    ['04', $ic['target'], 'Pengendalian', 'Tindak lanjut temuan.'],
                    ['05', $ic['trending_up'], 'Peningkatan', 'RTM & perbaikan.'],
                ] as $t): ?>
                <div class="pr-tl-step pr-rev">
                    <div class="pr-tl-step-n"><?= $t[0] ?></div>
                    <div class="pr-tl-step-ic"><?= $t[1] ?></div>
                    <h3><?= $t[2] ?></h3>
                    <p><?= $t[3] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ===== STRUKTUR TREE ===== -->
<section class="pr-sec dark" id="struktur">
    <div class="pr-container">
        <div class="pr-head pr-rev">
            <span class="pr-tag">Tim Kami</span>
            <h2>Struktur <span class="gr">Organisasi</span></h2>
            <p>Personalia LPM yang berdedikasi.</p>
        </div>
        <div class="pr-tree">
            <?php $lead = $struktur[0] ?? null; $team = array_slice($struktur, 1); ?>
            <?php if ($lead): ?>
            <div class="pr-tree-level">
                <div class="pr-tree-node lead pr-rev">
                    <div class="pr-tree-ava">
                        <?php if ($lead['foto']): ?><img src="/uploads/<?= Security::e($lead['foto']) ?>" alt=""><?php else: ?><?= $ic['user'] ?><?php endif; ?>
                    </div>
                    <h4><?= Security::e($lead['nama']) ?></h4>
                    <span class="jab"><?= Security::e($lead['jabatan']) ?></span>
                    <small><?= Security::e($lead['bidang']) ?></small>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($team)): ?>
            <div class="pr-tree-conn"></div>
            <div class="pr-tree-level">
                <?php foreach ($team as $p): ?>
                <div class="pr-tree-node pr-rev">
                    <div class="pr-tree-ava">
                        <?php if ($p['foto']): ?><img src="/uploads/<?= Security::e($p['foto']) ?>" alt=""><?php else: ?><?= $ic['user'] ?><?php endif; ?>
                    </div>
                    <h4><?= Security::e($p['nama']) ?></h4>
                    <span class="jab"><?= Security::e($p['jabatan']) ?></span>
                    <small><?= Security::e($p['bidang']) ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (empty($struktur)): ?><div class="pr-empty dark">Struktur belum ditambahkan.</div><?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== TUPOKSI ===== -->
<section class="pr-sec light" id="tupoksi">
    <div class="pr-container">
        <div class="pr-head pr-rev">
            <span class="pr-tag">Tupoksi</span>
            <h2>Tugas Pokok & <em>Fungsi</em></h2>
            <p>Enam tanggung jawab utama LPM.</p>
        </div>
        <div class="pr-tf-grid">
            <?php foreach ($tupoksiArr as $t): 
                $tIcon = $t['icon'] ?? '';
                $finalIcon = isset($mapDB[$tIcon]) ? $ic[$mapDB[$tIcon]] : (isset($ic[$tIcon]) ? $ic[$tIcon] : $ic['flag']);
            ?>
            <div class="pr-tf pr-rev">
                <div class="pr-tf-ic"><?= $finalIcon ?></div>
                <h3><?= Security::e($t['judul']) ?></h3>
                <p><?= Security::e($t['desk']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== LEGALITAS ===== -->
<section class="pr-sec soft" id="legalitas">
    <div class="pr-container">
        <div class="pr-head pr-rev">
            <span class="pr-tag">Legalitas</span>
            <h2>Dasar <em>Hukum</em> Pendirian</h2>
            <p>Dokumen resmi operasional LPM.</p>
        </div>
        <div class="pr-lg-grid">
            <?php if (empty($legalitas)): ?><div class="pr-empty">Belum ada dokumen legalitas.</div><?php endif; ?>
            <?php foreach ($legalitas as $l): ?>
            <div class="pr-lg pr-rev">
                <span class="pr-lg-year"><?= $ic['calendar'] ?> <?= Security::e($l['tahun']) ?></span>
                <div class="pr-lg-ic"><?= $ic['file_text'] ?></div>
                <h4><?= Security::e($l['tentang']) ?></h4>
                <div class="pr-lg-num">No. SK: <?= Security::e($l['nomor_sk']) ?></div>
                <div class="pr-lg-foot">
                    <?php if ($l['file_path']): ?>
                        <a href="/uploads/<?= Security::e($l['file_path']) ?>" target="_blank" class="pr-lg-dl"><?= $ic['download'] ?> Unduh PDF</a>
                    <?php else: ?><span class="pr-lg-empty">File belum tersedia</span><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
$milestones = [];
try { $milestones = $db->query("SELECT * FROM milestone ORDER BY urutan, tahun")->fetchAll(); } catch (Throwable $e) {}
?>
<?php if (!empty($milestones)): ?>
<section class="pr-sec light" id="milestone">
    <div class="pr-container">
        <div class="pr-head pr-rev">
            <span class="pr-tag">Perjalanan Kami</span>
            <h2>Milestone <em>Lembaga</em></h2>
            <p>Jejak langkah pengembangan penjaminan mutu dari masa ke masa.</p>
        </div>
        <div class="prx-tl">
            <?php foreach ($milestones as $i => $m): 
                $mIcon = $m['icon'] ?? '';
                $finalIcon = isset($mapDB[$mIcon]) ? $ic[$mapDB[$mIcon]] : (isset($ic[$mIcon]) ? $ic[$mIcon] : $ic['flag']);
            ?>
            <div class="prx-item <?= $i % 2 ? 'right' : 'left' ?> pr-rev">
                <div class="prx-dot"><span><?= $finalIcon ?></span></div>
                <div class="prx-card">
                    <span class="prx-year"><?= Security::e($m['tahun']) ?></span>
                    <span class="prx-cat"><?= Security::e($m['kategori']) ?></span>
                    <h3><?= Security::e($m['judul']) ?></h3>
                    <p><?= Security::e($m['deskripsi']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<style>
.prx-tl{position:relative;max-width:900px;margin:0 auto;padding:20px 0}
.prx-tl::before{content:'';position:absolute;top:0;bottom:0;left:50%;width:3px;transform:translateX(-50%);background:linear-gradient(180deg,#C9A227,#0F3D5C,#C9A227);border-radius:3px}
.prx-item{position:relative;width:50%;padding:0 44px 44px 0}
.prx-item.right{margin-left:50%;padding:0 0 44px 44px}
.prx-dot{position:absolute;top:6px;right:-27px;width:54px;height:54px;border-radius:50%;background:#fff;border:3px solid #C9A227;display:grid;place-items:center;font-size:22px;color:#C9A227;box-shadow:0 8px 22px rgba(201,162,39,.35);z-index:2}
.prx-item.right .prx-dot{right:auto;left:-27px}
.prx-card{background:#fff;border:1px solid #E2E8F0;border-radius:18px;padding:22px;box-shadow:0 10px 30px rgba(15,61,92,.08);transition:.3s}
.prx-card:hover{transform:translateY(-4px);box-shadow:0 18px 44px rgba(15,61,92,.14);border-color:rgba(201,162,39,.4)}
.prx-year{display:inline-block;padding:4px 12px;border-radius:50px;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#092A40;font-weight:900;font-size:12px;margin-bottom:8px}
.prx-cat{display:inline-block;padding:4px 10px;border-radius:50px;background:rgba(15,61,92,.08);color:#0F3D5C;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.8px;margin-left:6px}
.prx-card h3{font-size:17px;color:#092A40;margin:6px 0}
.prx-card p{font-size:13.5px;color:#64748B;line-height:1.6;margin:0}
@media(max-width:768px){
    .prx-tl::before{left:26px}
    .prx-item,.prx-item.right{width:100%;margin-left:0;padding:0 0 36px 66px}
    .prx-dot,.prx-item.right .prx-dot{left:0;right:auto}
}
</style>
<?php endif; ?>

<!-- ===== FAQ ===== -->
<section class="pr-sec light" id="faq">
    <div class="pr-container">
        <div class="pr-head pr-rev">
            <span class="pr-tag">FAQ</span>
            <h2>Pertanyaan <em>Umum</em></h2>
            <p>Jawaban atas pertanyaan yang sering diajukan seputar penjaminan mutu.</p>
        </div>
        <div class="pr-faq-list">
            <div class="pr-faq-item pr-rev">
                <button class="pr-faq-q">
                    <span class="pr-faq-ic"><?= $ic['help_circle'] ?></span>
                    <span>Apa itu SPMI dan mengapa penting bagi perguruan tinggi?</span>
                    <span class="pr-faq-arrow"><?= $ic['chevron_down'] ?></span>
                </button>
                <div class="pr-faq-a">
                    <p>Sistem Penjaminan Mutu Internal (SPMI) adalah kegiatan sistemik penjaminan mutu pendidikan tinggi oleh setiap perguruan tinggi secara otonom untuk mengendalikan dan meningkatkan penyelenggaraan pendidikan tinggi secara berencana dan berkelanjutan.</p>
                </div>
            </div>
            <div class="pr-faq-item pr-rev">
                <button class="pr-faq-q">
                    <span class="pr-faq-ic"><?= $ic['help_circle'] ?></span>
                    <span>Siapa saja yang terlibat dalam pelaksanaan Audit Mutu Internal (AMI)?</span>
                    <span class="pr-faq-arrow"><?= $ic['chevron_down'] ?></span>
                </button>
                <div class="pr-faq-a">
                    <p>AMI dilaksanakan oleh auditor internal yang telah tersertifikasi dan ditugaskan oleh LPM. Auditee mencakup seluruh unit kerja dan program studi di lingkungan perguruan tinggi untuk memastikan pemenuhan standar.</p>
                </div>
            </div>
            <div class="pr-faq-item pr-rev">
                <button class="pr-faq-q">
                    <span class="pr-faq-ic"><?= $ic['help_circle'] ?></span>
                    <span>Bagaimana perbedaan antara SPMI dan SPME (Akreditasi)?</span>
                    <span class="pr-faq-arrow"><?= $ic['chevron_down'] ?></span>
                </button>
                <div class="pr-faq-a">
                    <p>SPMI adalah penjaminan mutu yang dilakukan secara internal oleh perguruan tinggi (oleh LPM), sedangkan SPME (Sistem Penjaminan Mutu Eksternal) atau Akreditasi dilakukan oleh lembaga eksternal seperti BAN-PT atau LAM untuk menilai kelayakan dan peringkat program studi/institusi.</p>
                </div>
            </div>
            <div class="pr-faq-item pr-rev">
                <button class="pr-faq-q">
                    <span class="pr-faq-ic"><?= $ic['help_circle'] ?></span>
                    <span>Apa itu Rapat Tinjauan Manajemen (RTM)?</span>
                    <span class="pr-faq-arrow"><?= $ic['chevron_down'] ?></span>
                </button>
                <div class="pr-faq-a">
                    <p>RTM adalah forum evaluasi tingkat pimpinan yang dilaksanakan secara berkala (biasanya tahunan) untuk meninjau kinerja SPMI, mengevaluasi hasil AMI, dan menetapkan kebijakan peningkatan mutu untuk siklus berikutnya.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA ===== -->
<section class="pr-cta" id="cta">
    <canvas class="pr-cta-canvas" id="prCanvas"></canvas>
    <div class="pr-cta-inner pr-rev">
        <span class="pr-badge" style="background:rgba(201,162,39,.15);border-color:rgba(201,162,39,.3);"><span class="pr-badge-dot"></span> Mari Berkolaborasi</span>
        <h2>Bersama Wujudkan<br><span class="gr">Budaya Mutu</span></h2>
        <p>Masukan Anda adalah bahan bakar peningkatan mutu kampus.</p>
        <div class="pr-cta-btns">
            <a href="/publik/pengaduan.php" class="pr-btn pr-btn-primary"><?= $ic['send'] ?> Sampaikan Aspirasi</a>
            <a href="/publik/akreditasi.php" class="pr-btn pr-btn-ghost"><?= $ic['trophy'] ?> Lihat Akreditasi <?= $ic['arrow_right'] ?></a>
        </div>
    </div>
</section>

</div>

<script>
(function () {
    /* Spotlight */
    var hero = document.querySelector('.pr-hero');
    var spot = document.getElementById('prSpot');
    if (hero && spot) {
        hero.addEventListener('mousemove', function (e) {
            var r = hero.getBoundingClientRect();
            spot.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            spot.style.setProperty('--my', (e.clientY - r.top) + 'px');
        });
    }

    /* 3D tilt */
    document.querySelectorAll('.pr-bn, .pr-tf').forEach(function (el) {
        el.addEventListener('mousemove', function (e) {
            var r = el.getBoundingClientRect();
            var px = ((e.clientX - r.left) / r.width - .5) * 7;
            var py = ((e.clientY - r.top) / r.height - .5) * -7;
            el.style.transform = 'translateY(-8px) rotateX(' + py + 'deg) rotateY(' + px + 'deg)';
            el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            el.style.setProperty('--my', (e.clientY - r.top) + 'px');
        });
        el.addEventListener('mouseleave', function () { el.style.transform = ''; });
    });

    /* Reveal */
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.pr-rev').forEach(function (el) { io.observe(el); });

    /* Counter */
    var cio = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            var el = en.target, target = parseInt(el.getAttribute('data-count'), 10);
            if (isNaN(target)) return;
            var start = performance.now();
            (function tick(now) {
                var p = Math.min((now - start) / 1600, 1);
                el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
                if (p < 1) requestAnimationFrame(tick);
            })(start);
            cio.unobserve(el);
        });
    }, { threshold: 0.4 });
    document.querySelectorAll('[data-count]').forEach(function (el) { cio.observe(el); });

    /* Aurora canvas */
    var canvas = document.getElementById('prCanvas');
    if (canvas) {
        var ctx = canvas.getContext('2d');
        var W, H, pts = [];
        function resize() { W = canvas.width = canvas.offsetWidth; H = canvas.height = canvas.offsetHeight; }
        resize();
        window.addEventListener('resize', resize);
        for (var i = 0; i < 5; i++) {
            pts.push({ x: Math.random() * W, y: Math.random() * H, vx: (Math.random() - .5) * .6, vy: (Math.random() - .5) * .6, r: 180 + Math.random() * 120, h: [45, 30, 200, 45, 180][i] });
        }
        (function step() {
            ctx.clearRect(0, 0, W, H);
            ctx.globalCompositeOperation = 'lighter';
            pts.forEach(function (p) {
                p.x += p.vx; p.y += p.vy;
                if (p.x < 0 || p.x > W) p.vx *= -1;
                if (p.y < 0 || p.y > H) p.vy *= -1;
                var g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r);
                g.addColorStop(0, 'hsla(' + p.h + ', 80%, 55%, .18)');
                g.addColorStop(1, 'hsla(' + p.h + ', 80%, 55%, 0)');
                ctx.fillStyle = g;
                ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, 6.283); ctx.fill();
            });
            requestAnimationFrame(step);
        })();
    }

    /* FAQ Accordion */
    document.querySelectorAll('.pr-faq-q').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var item = this.closest('.pr-faq-item');
            item.classList.toggle('active');
        });
    });

    /* Scroll Progress & TOC */
    var progressBar = document.getElementById('prProg');
    var toc = document.getElementById('prToc');
    var sections = document.querySelectorAll('.pr-sec, .pr-hero, .pr-cta');

    window.addEventListener('scroll', function() {
        var scrollTop = window.scrollY;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        var scrollPercent = (scrollTop / docHeight) * 100;
        if(progressBar) progressBar.style.width = scrollPercent + '%';

        if (scrollTop > 400) {
            if(toc) toc.classList.add('visible');
        } else {
            if(toc) toc.classList.remove('visible');
        }
    });

    if (toc) {
        var tocLinks = toc.querySelectorAll('.pr-toc-link');
        var tocObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var id = entry.target.getAttribute('id');
                    tocLinks.forEach(function(link) {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === '#' + id) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        }, { rootMargin: '-40% 0px -50% 0px' });

        sections.forEach(function(sec) {
            if (sec.id) tocObserver.observe(sec);
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>