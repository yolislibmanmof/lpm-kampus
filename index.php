<?php
$pageTitle = 'Beranda';
require_once 'includes/header-publik.php';

$db = Database::getInstance();

$berita = $db->query("SELECT * FROM berita WHERE is_published = 1 ORDER BY published_at DESC LIMIT 6")->fetchAll();
$stats = $db->query("
    SELECT COUNT(*) total,
           SUM(CASE WHEN peringkat IN ('Unggul','A') THEN 1 ELSE 0 END) unggul,
           SUM(CASE WHEN peringkat IN ('Baik Sekali','B') THEN 1 ELSE 0 END) baik_sekali
    FROM akreditasi WHERE tingkat = 'Prodi'
")->fetch();

$unggulList = $db->query("
    SELECT a.*, p.nama_prodi, f.nama_fakultas
    FROM akreditasi a
    LEFT JOIN prodi p ON a.id_prodi = p.id_prodi
    LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
    WHERE a.tingkat = 'Prodi' AND a.peringkat IN ('Unggul','A')
    ORDER BY p.nama_prodi ASC LIMIT 6
")->fetchAll();

$sliderImgs = array_values(array_filter(array_map('trim', explode("\n", Site::setting('hero_slider', '')))));
?>

<style>
/* ============================================================
   INDEX ULTIMATE PREMIUM v6.0 — International Class
   Aurora • Grid • Noise • 3D Tilt • Magnetic • Spotlight
============================================================ */

/* ===== GLOBAL TEXTURES ===== */
.ix-noise {
    position: fixed; inset: 0; z-index: 9991; pointer-events: none; opacity: .035;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23n)' opacity='0.8'/%3E%3C/svg%3E");
}
.ix-grid-bg {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.15) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
}

/* ===== HERO MEGA ===== */
.ix-hero {
    min-height: 100vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 120px 0 80px;
    display: flex; align-items: center;
}
.ix-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 80% 60% at 20% 30%, rgba(201,162,39,.25), transparent 50%),
        radial-gradient(ellipse 60% 80% at 80% 20%, rgba(26,90,130,.4), transparent 50%),
        radial-gradient(ellipse 70% 60% at 70% 80%, rgba(232,197,90,.15), transparent 50%),
        linear-gradient(160deg, #061D2E 0%, #0F3D5C 55%, #092A40 100%);
    animation: ixShift 18s ease-in-out infinite;
}
@keyframes ixShift {
    0%, 100% { filter: hue-rotate(0deg); transform: scale(1); }
    50% { filter: hue-rotate(15deg); transform: scale(1.05); }
}

.ix-hero-inner { position: relative; z-index: 3; max-width: 1000px; margin: 0 auto; text-align: center; }

/* Eyebrow badge dengan shimmer */
.ix-eyebrow {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 8px 18px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.12);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px;
    text-transform: uppercase; color: rgba(255,255,255,.85);
    margin-bottom: 32px; position: relative; overflow: hidden;
}
.ix-eyebrow::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.4), transparent);
    animation: ixShimmer 3s ease-in-out infinite;
}
@keyframes ixShimmer { to { left: 200%; } }
.ix-eyebrow-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--accent-light); box-shadow: 0 0 14px var(--accent-light);
    animation: ixPulseDot 2s ease infinite;
}
@keyframes ixPulseDot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .6; transform: scale(1.3); }
}

/* Headline dengan shimmer gradient */
.ix-hero h1 {
    font-size: clamp(40px, 6.5vw, 88px); font-weight: 800;
    line-height: 1.02; margin-bottom: 28px; letter-spacing: -.04em;
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
}
.ix-hero h1 .ix-word { display: inline-block; position: relative; }
.ix-hero h1 .ix-grad {
    background: linear-gradient(120deg, #E8C55A 0%, #C9A227 25%, #F7E491 50%, #C9A227 75%, #E8C55A 100%);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: ixTextShine 4s linear infinite;
    font-style: italic;
}
@keyframes ixTextShine { to { background-position: 200% center; } }

.ix-hero-lead {
    font-size: clamp(16px, 1.6vw, 19px); color: rgba(255,255,255,.72);
    max-width: 680px; margin: 0 auto 44px; line-height: 1.65;
}

/* CTA dengan glow */
.ix-hero-ctas { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 60px; }
.ix-btn-glow {
    padding: 16px 34px; border-radius: 50px; font-weight: 700; font-size: 15px;
    text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
    transition: .4s var(--ease-out); position: relative; overflow: hidden;
    border: none; cursor: pointer;
}
.ix-btn-primary {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); box-shadow: 0 10px 40px rgba(201,162,39,.45);
}
.ix-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 18px 50px rgba(201,162,39,.65); }
.ix-btn-ghost {
    background: rgba(255,255,255,.05); backdrop-filter: blur(12px);
    border: 1.5px solid rgba(255,255,255,.18); color: #fff;
}
.ix-btn-ghost:hover { background: rgba(255,255,255,.12); border-color: rgba(201,162,39,.6); transform: translateY(-3px); }

/* Glass Stats Panel */
.ix-glass {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px;
    background: rgba(255,255,255,.1); backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,.15); border-radius: 24px;
    padding: 2px; max-width: 820px; margin: 0 auto; overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.4), inset 0 1px 0 rgba(255,255,255,.1);
}
.ix-gs {
    padding: 26px 20px; text-align: center;
    background: rgba(6,29,46,.6); backdrop-filter: blur(8px);
    transition: .4s; position: relative;
}
.ix-gs:first-child { border-radius: 22px 0 0 22px; }
.ix-gs:last-child { border-radius: 0 22px 22px 0; }
.ix-gs:hover { background: rgba(201,162,39,.12); }
.ix-gs-num {
    font-size: clamp(28px, 3.5vw, 42px); font-weight: 800;
    background: linear-gradient(180deg, #F7E491 0%, #C9A227 100%);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    line-height: 1; margin-bottom: 6px; letter-spacing: -.02em;
    display: block;
}
.ix-gs-label {
    font-size: 11px; color: rgba(255,255,255,.65);
    letter-spacing: 1.2px; text-transform: uppercase; font-weight: 600;
}
.ix-gs-spark {
    height: 18px; margin-top: 8px; opacity: .6;
}

/* Floating cards */
.ix-float-cards {
    position: absolute; inset: 0; pointer-events: none; z-index: 2;
}
.ix-fc {
    position: absolute; padding: 14px 18px; border-radius: 16px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; color: #fff;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 12px 40px rgba(0,0,0,.3);
    animation: ixFloating 8s ease-in-out infinite;
}
.ix-fc .ix-fc-ic {
    width: 32px; height: 32px; border-radius: 10px;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    display: grid; place-items: center; font-size: 14px;
}
.ix-fc small { display: block; font-weight: 500; color: rgba(255,255,255,.6); font-size: 10px; }
.ix-fc-1 { top: 18%; left: 6%; animation-delay: 0s; }
.ix-fc-2 { top: 28%; right: 6%; animation-delay: -2s; }
.ix-fc-3 { bottom: 22%; left: 8%; animation-delay: -4s; }
.ix-fc-4 { bottom: 16%; right: 8%; animation-delay: -6s; }
@keyframes ixFloating {
    0%, 100% { transform: translateY(0) rotate(0); }
    33% { transform: translateY(-12px) rotate(-1deg); }
    66% { transform: translateY(8px) rotate(1deg); }
}
@media (max-width: 992px) { .ix-float-cards { display: none; } }

/* Spotlight */
.ix-spotlight {
    position: absolute; inset: 0; z-index: 1; pointer-events: none;
    background: radial-gradient(600px circle at var(--mx, 50%) var(--my, 30%), rgba(201,162,39,.18), transparent 40%);
    transition: background .1s ease;
}

/* Scroll indicator */
.ix-scroll-hint {
    position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
    display: flex; flex-direction: column; align-items: center; gap: 12px;
    color: rgba(255,255,255,.5); font-size: 10px; letter-spacing: 3px;
    text-transform: uppercase; z-index: 3;
}
.ix-scroll-line {
    width: 1px; height: 50px; background: linear-gradient(to bottom, transparent, var(--accent-light), transparent);
    animation: ixScrollLine 2.5s ease-in-out infinite;
}
@keyframes ixScrollLine {
    0% { opacity: 0; transform: scaleY(0); transform-origin: top; }
    50% { opacity: 1; transform: scaleY(1); }
    100% { opacity: 0; transform: scaleY(0); transform-origin: bottom; }
}

/* ===== TRUST MARQUEE ===== */
.ix-trust {
    padding: 32px 0; background: #092A40; overflow: hidden; position: relative;
    border-top: 1px solid rgba(201,162,39,.1); border-bottom: 1px solid rgba(201,162,39,.1);
}
.ix-trust::before, .ix-trust::after {
    content: ''; position: absolute; top: 0; bottom: 0; width: 120px; z-index: 2;
    pointer-events: none;
}
.ix-trust::before { left: 0; background: linear-gradient(90deg, #092A40, transparent); }
.ix-trust::after { right: 0; background: linear-gradient(270deg, #092A40, transparent); }
.ix-trust-label {
    text-align: center; font-size: 10.5px; letter-spacing: 3px;
    color: rgba(255,255,255,.45); text-transform: uppercase; font-weight: 700;
    margin-bottom: 20px;
}
.ix-trust-track {
    display: flex; gap: 80px; width: max-content;
    animation: ixMarquee 35s linear infinite;
}
.ix-trust:hover .ix-trust-track { animation-play-state: paused; }
.ix-trust-item {
    color: rgba(255,255,255,.55); font-size: 14px; font-weight: 700;
    letter-spacing: 2px; text-transform: uppercase; white-space: nowrap;
    display: flex; align-items: center; gap: 10px;
    transition: .3s;
}
.ix-trust-item:hover { color: var(--accent-light); }
.ix-trust-item .ix-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); }
@keyframes ixMarquee { to { transform: translateX(-50%); } }

/* ===== STATS WALL (Interactive) ===== */
.ix-stats-wall {
    background: #fff; padding: 100px 0; position: relative;
}
.ix-sw-head {
    text-align: center; max-width: 720px; margin: 0 auto 72px;
}
.ix-sw-tag {
    display: inline-block; padding: 6px 18px; border-radius: 50px;
    background: rgba(201,162,39,.1); color: var(--accent);
    font-size: 11px; font-weight: 800; letter-spacing: 2px;
    text-transform: uppercase; margin-bottom: 18px;
    border: 1px solid rgba(201,162,39,.2);
}
.ix-sw-head h2 {
    font-size: clamp(32px, 4.5vw, 52px); font-weight: 800;
    color: var(--primary-dark); letter-spacing: -.02em; line-height: 1.1;
    margin-bottom: 16px;
}
.ix-sw-head p { color: var(--text-muted); font-size: 17px; }

.ix-sw-grid {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 2px;
    background: var(--border); border-radius: 28px; overflow: hidden;
    box-shadow: 0 20px 60px rgba(15,61,92,.08);
}
.ix-sw-item {
    background: #fff; padding: 44px 32px; position: relative;
    transition: .4s var(--ease-out); overflow: hidden;
}
.ix-sw-item::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(circle at 50% 0%, rgba(201,162,39,.08), transparent 60%);
    opacity: 0; transition: .5s;
}
.ix-sw-item:hover::after { opacity: 1; }
.ix-sw-item:hover { background: linear-gradient(180deg, #fff, #FFFBEB); }
.ix-sw-ico {
    width: 48px; height: 48px; border-radius: 12px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff; display: grid; place-items: center; font-size: 20px;
    margin-bottom: 20px;
    box-shadow: 0 6px 18px rgba(15,61,92,.2);
}
.ix-sw-num {
    font-size: clamp(40px, 5vw, 64px); font-weight: 800;
    color: var(--primary-dark); letter-spacing: -.03em; line-height: 1;
    margin-bottom: 8px;
    background: linear-gradient(180deg, var(--primary-dark), var(--primary));
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.ix-sw-num .ix-suffix { font-size: .5em; font-weight: 700; }
.ix-sw-label {
    font-size: 13px; color: var(--text-muted); letter-spacing: .5px;
    text-transform: uppercase; font-weight: 700; margin-bottom: 18px;
}
.ix-sw-trend {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 700;
    background: rgba(16,185,129,.1); color: #059669;
}
@media (max-width: 992px) { .ix-sw-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .ix-sw-grid { grid-template-columns: 1fr; } }

/* ===== BENTO TUPOKSI 3D ===== */
.ix-bento-sec { padding: 100px 0; background: linear-gradient(180deg, #fff, #F7F9FC); position: relative; }
.ix-bento {
    display: grid; grid-template-columns: repeat(6, 1fr);
    grid-auto-rows: minmax(180px, auto); gap: 20px;
    perspective: 1400px;
}
.ix-bn {
    position: relative; overflow: hidden;
    border-radius: 24px; padding: 32px;
    background: #fff; border: 1px solid var(--border);
    transition: transform .6s var(--ease-out), box-shadow .4s;
    display: flex; flex-direction: column; justify-content: space-between;
    transform-style: preserve-3d;
    box-shadow: 0 8px 30px rgba(15,61,92,.06);
}
.ix-bn::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(600px circle at var(--mx,50%) var(--my,50%), rgba(201,162,39,.12), transparent 40%);
    opacity: 0; transition: .5s;
}
.ix-bn:hover::before { opacity: 1; }
.ix-bn:hover { transform: translateY(-8px) rotateX(2deg) rotateY(-2deg); box-shadow: 0 30px 60px rgba(15,61,92,.15); border-color: rgba(201,162,39,.4); }

.ix-bn-hero {
    grid-column: span 3; grid-row: span 2;
    background: linear-gradient(135deg, #061D2E 0%, #0F3D5C 55%, #12365B 100%);
    color: #fff; padding: 48px; border: none; position: relative; overflow: hidden;
}
.ix-bn-hero::after {
    content: ''; position: absolute; bottom: -100px; right: -100px;
    width: 360px; height: 360px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
    animation: ixFloat 8s ease-in-out infinite;
}
@keyframes ixFloat { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.15); } }
.ix-bn-hero-eyebrow {
    display: inline-block; padding: 5px 14px; border-radius: 50px;
    background: rgba(201,162,39,.18); color: var(--accent-light);
    font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;
    margin-bottom: 20px; border: 1px solid rgba(201,162,39,.3);
    position: relative; z-index: 1;
}
.ix-bn-hero h3 {
    font-size: clamp(26px, 3vw, 38px); font-weight: 800;
    line-height: 1.1; margin-bottom: 16px; letter-spacing: -.02em;
    position: relative; z-index: 1;
}
.ix-bn-hero p { font-size: 16px; opacity: .85; line-height: 1.6; max-width: 440px; position: relative; z-index: 1; }
.ix-bn-hero .ix-bn-ic {
    width: 70px; height: 70px; border-radius: 18px;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); font-size: 32px;
    display: grid; place-items: center; margin-bottom: 20px;
    box-shadow: 0 10px 30px rgba(201,162,39,.4);
    position: relative; z-index: 1;
}

.ix-bn-sm { grid-column: span 3; }
.ix-bn-xs { grid-column: span 2; }

.ix-bn-num {
    position: absolute; top: 20px; right: 24px;
    font-size: 52px; font-weight: 900; color: rgba(15,61,92,.05);
    line-height: 1; letter-spacing: -.04em;
}
.ix-bn-hero .ix-bn-num { color: rgba(255,255,255,.08); font-size: 140px; }
.ix-bn-ic {
    width: 56px; height: 56px; border-radius: 14px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff; font-size: 24px; display: grid; place-items: center;
    margin-bottom: 16px;
    box-shadow: 0 8px 20px rgba(15,61,92,.2);
    transition: .4s var(--ease-spring);
}
.ix-bn:hover .ix-bn-ic { transform: scale(1.1) rotate(-8deg); }
.ix-bn h3 { font-size: 19px; color: var(--primary-dark); margin-bottom: 8px; letter-spacing: -.01em; }
.ix-bn p { font-size: 14px; color: var(--text-muted); line-height: 1.55; }

@media (max-width: 992px) {
    .ix-bento { grid-template-columns: repeat(2, 1fr); }
    .ix-bn-hero, .ix-bn-sm, .ix-bn-xs { grid-column: span 2; grid-row: span 1; }
}

/* ===== TIMELINE PPEPP PRESISI ===== */
.ix-tl {
    background: #061D2E; color: #fff; position: relative; overflow: hidden;
    padding: 88px 0 72px;
}
.ix-tl::before {
    content: ''; position: absolute; inset: 0; opacity: .2;
    background-image: radial-gradient(rgba(201,162,39,.2) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.ix-tl-inner { position: relative; z-index: 1; }
.ix-tl-head { text-align: center; max-width: 720px; margin: 0 auto 44px; padding: 0 24px; }
.ix-tl-head .ix-sw-tag { background: rgba(201,162,39,.18); color: var(--accent-light); border-color: rgba(201,162,39,.3); }
.ix-tl-head h2 { color: #fff; }
.ix-tl-head p { color: rgba(255,255,255,.7); }

/* Tombol kontrol — sejajar kanan container */
.ix-tl-controls {
    max-width: 1200px; margin: 0 auto 20px; padding: 0 24px;
    display: flex; justify-content: flex-end; gap: 10px;
}
.ix-tl-btn {
    width: 44px; height: 44px; border-radius: 50%;
    border: 1px solid rgba(255,255,255,.2); background: rgba(255,255,255,.06);
    color: #fff; cursor: pointer; font-size: 16px; transition: .3s;
    display: grid; place-items: center;
}
.ix-tl-btn:hover { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #092A40; border-color: transparent; transform: translateY(-2px); }

/* Track: kartu pertama PRESISI sejajar tepi container */
.ix-tl-track {
    display: flex; gap: 20px; overflow-x: auto;
    scroll-snap-type: x mandatory; scroll-behavior: smooth;
    padding: 8px 24px 24px;
    padding-left: max(calc((100vw - 1200px) / 2 + 24px), 24px);
    padding-right: max(calc((100vw - 1200px) / 2 + 24px), 24px);
    scrollbar-width: none;
}
.ix-tl-track::-webkit-scrollbar { display: none; }

.ix-tl-step {
    flex: 0 0 300px; scroll-snap-align: start;
    background: rgba(255,255,255,.05); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.12); border-radius: 20px;
    padding: 26px; position: relative; transition: .4s var(--ease-out);
}
.ix-tl-step:hover { background: rgba(201,162,39,.08); border-color: rgba(201,162,39,.4); transform: translateY(-6px); }
.ix-tl-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
.ix-tl-step-num {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; font-weight: 900; font-size: 20px;
    display: grid; place-items: center;
    box-shadow: 0 10px 26px rgba(201,162,39,.4);
}
.ix-tl-step-tag { font-size: 10px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,.45); }
.ix-tl-step-ic { font-size: 30px; display: block; margin-bottom: 12px; }
.ix-tl-step h3 { font-size: 19px; color: #fff; margin: 0 0 8px; letter-spacing: -.01em; }
.ix-tl-step p { font-size: 13.5px; color: rgba(255,255,255,.72); line-height: 1.6; margin: 0; }

/* Progress bar — lebar presisi container */
.ix-tl-progress {
    max-width: 1152px; width: calc(100% - 48px);
    margin: 28px auto 0; height: 4px;
    background: rgba(255,255,255,.1); border-radius: 4px; overflow: hidden;
}
.ix-tl-progress-bar {
    height: 100%; width: 20%;
    background: linear-gradient(90deg, #C9A227, #E8C55A);
    border-radius: 4px; transition: width .3s;
}

/* ===== SHOWCASE 3D CARDS ===== */
.ix-sc {
    padding: 100px 0; position: relative; overflow: hidden;
    background: linear-gradient(180deg, #061D2E 0%, #0F3D5C 50%, #092A40 100%);
    color: #fff;
}
.ix-sc::before {
    content: ''; position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 15% 20%, rgba(201,162,39,.25), transparent 50%),
        radial-gradient(ellipse 50% 40% at 85% 80%, rgba(26,90,130,.4), transparent 50%);
    animation: ixShift 20s ease-in-out infinite;
}
.ix-sc-inner { position: relative; z-index: 1; }
.ix-sc-head {
    display: flex; justify-content: space-between; align-items: flex-end;
    gap: 24px; flex-wrap: wrap; margin-bottom: 60px;
}
.ix-sc-head-text { max-width: 620px; }
.ix-sc-head h2 {
    font-size: clamp(32px, 4.5vw, 52px); font-weight: 800;
    margin-bottom: 14px; letter-spacing: -.02em; line-height: 1.08;
}
.ix-sc-head h2 .ix-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: ixTextShine 4s linear infinite;
}
.ix-sc-head p { color: rgba(255,255,255,.7); font-size: 17px; }

.ix-sc-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 22px;
    perspective: 1200px;
}
.ix-sc-card {
    background: rgba(255,255,255,.04); backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,.1); border-radius: 24px;
    padding: 32px 28px; position: relative; overflow: hidden;
    transition: transform .5s var(--ease-out), box-shadow .4s, border-color .4s;
    transform-style: preserve-3d;
}
.ix-sc-card::before {
    content: ''; position: absolute; top: -1px; left: -1px; right: -1px; height: 2px;
    background: linear-gradient(90deg, transparent, var(--accent), transparent);
    opacity: 0; transition: .5s;
}
.ix-sc-card:hover::before { opacity: 1; }
.ix-sc-card:hover {
    transform: translateY(-12px) rotateX(3deg);
    box-shadow: 0 40px 80px rgba(0,0,0,.4), 0 0 40px rgba(201,162,39,.2);
    border-color: rgba(201,162,39,.4);
}
.ix-sc-rank {
    display: inline-block; padding: 5px 14px; border-radius: 50px;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); font-size: 11px; font-weight: 900;
    letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 20px;
    box-shadow: 0 6px 18px rgba(201,162,39,.35);
}
.ix-sc-ring {
    width: 80px; height: 80px; margin: 0 auto 20px; position: relative;
}
.ix-sc-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.ix-sc-ring circle { fill: none; stroke-width: 6; }
.ix-sc-ring .track { stroke: rgba(255,255,255,.08); }
.ix-sc-ring .prog {
    stroke: url(#goldGrad); stroke-linecap: round;
    stroke-dasharray: 226; stroke-dashoffset: 56;
    transition: 1.2s var(--ease-out);
}
.ix-sc-ring .num {
    position: absolute; inset: 0; display: grid; place-items: center;
    font-size: 18px; font-weight: 900; color: var(--accent-light);
    letter-spacing: -.02em;
}
.ix-sc-card h4 {
    font-size: 18px; color: #fff; margin-bottom: 6px; text-align: center;
    letter-spacing: -.01em;
}
.ix-sc-card small { display: block; text-align: center; color: rgba(255,255,255,.5); font-size: 13px; }
.ix-sc-card .ix-sc-badge {
    position: absolute; top: 18px; right: 18px;
    font-size: 20px; filter: drop-shadow(0 4px 10px rgba(201,162,39,.5));
}

/* ===== LIVE NEWSROOM ===== */
.ix-news { padding: 100px 0; background: #fff; position: relative; }
.ix-news-ticker {
    display: flex; gap: 40px; padding: 14px 0;
    background: linear-gradient(90deg, var(--primary-dark), var(--primary));
    border-radius: 50px; overflow: hidden; margin-bottom: 56px;
    position: relative;
}
.ix-news-ticker::before {
    content: 'LIVE'; position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    padding: 4px 12px; border-radius: 50px;
    background: var(--danger); color: #fff; font-size: 10px; font-weight: 900;
    letter-spacing: 1.5px; z-index: 2;
    box-shadow: 0 0 0 0 rgba(239,68,68,.7); animation: ixLive 2s ease infinite;
}
@keyframes ixLive {
    0% { box-shadow: 0 0 0 0 rgba(239,68,68,.7); }
    100% { box-shadow: 0 0 0 14px rgba(239,68,68,0); }
}
.ix-news-ticker-track {
    display: flex; gap: 40px; width: max-content; padding-left: 80px;
    animation: ixMarquee 45s linear infinite;
}
.ix-news-ticker-item {
    color: #fff; font-size: 13px; font-weight: 600; white-space: nowrap;
    display: flex; align-items: center; gap: 12px;
}
.ix-news-ticker-item b { color: var(--accent-light); }

.ix-news-grid {
    display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 22px;
}
.ix-news-hero {
    grid-row: span 2; position: relative; overflow: hidden;
    border-radius: 28px; min-height: 500px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    padding: 40px; display: flex; flex-direction: column; justify-content: flex-end;
    color: #fff; transition: .5s var(--ease-out);
}
.ix-news-hero::before {
    content: ''; position: absolute; inset: 0;
    background:
        radial-gradient(600px circle at 30% 20%, rgba(201,162,39,.35), transparent 60%),
        linear-gradient(to top, rgba(6,29,46,.95) 0%, transparent 50%);
}
.ix-news-hero:hover { transform: translateY(-6px); box-shadow: 0 30px 70px rgba(15,61,92,.35); }
.ix-news-hero-inner { position: relative; z-index: 1; }
.ix-news-hero .ix-cat {
    display: inline-block; padding: 5px 14px; border-radius: 50px;
    background: rgba(255,255,255,.15); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.25);
    font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;
    margin-bottom: 16px;
}
.ix-news-hero h3 {
    font-size: clamp(24px, 2.8vw, 34px); margin-bottom: 12px;
    letter-spacing: -.02em; line-height: 1.15;
}
.ix-news-hero p { color: rgba(255,255,255,.8); margin-bottom: 20px; font-size: 15px; }
.ix-news-hero .ix-date { color: rgba(255,255,255,.6); font-size: 12px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }

.ix-news-sm {
    background: #fff; border-radius: 20px; overflow: hidden;
    border: 1px solid var(--border); transition: .4s var(--ease-out);
    display: flex; flex-direction: column;
    box-shadow: 0 4px 20px rgba(15,61,92,.05);
}
.ix-news-sm:hover { transform: translateY(-6px); box-shadow: 0 20px 50px rgba(15,61,92,.12); border-color: rgba(201,162,39,.4); }
.ix-news-sm-cover {
    height: 140px; position: relative; overflow: hidden;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    display: grid; place-items: center; font-size: 38px;
}
.ix-news-sm-cover.agenda { background: linear-gradient(135deg, #C9A227, #E8C55A); }
.ix-news-sm-cover.pengumuman { background: linear-gradient(135deg, #092A40, #1A5A82); }
.ix-news-sm-body { padding: 20px 22px; flex: 1; display: flex; flex-direction: column; }
.ix-news-sm-body .ix-cat {
    display: inline-block; padding: 3px 10px; border-radius: 50px;
    background: rgba(15,61,92,.08); color: var(--primary);
    font-size: 10px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
    margin-bottom: 10px; width: fit-content;
}
.ix-news-sm-body h4 { font-size: 15px; margin-bottom: 8px; color: var(--primary-dark); line-height: 1.35; letter-spacing: -.01em; }
.ix-news-sm-body .ix-date { color: var(--text-muted); font-size: 11px; font-weight: 600; margin-top: auto; }

@media (max-width: 992px) {
    .ix-news-grid { grid-template-columns: 1fr 1fr; }
    .ix-news-hero { grid-column: span 2; grid-row: auto; min-height: 360px; }
}
@media (max-width: 576px) {
    .ix-news-grid { grid-template-columns: 1fr; }
    .ix-news-hero { grid-column: auto; }
}

/* ===== QUOTE ===== */
.ix-quote-sec { padding: 100px 0; background: linear-gradient(180deg, #fff, #F7F9FC); }
.ix-quote {
    max-width: 1000px; margin: 0 auto; position: relative;
    background: linear-gradient(135deg, #061D2E, #0F3D5C); color: #fff;
    border-radius: 32px; padding: 72px 60px; overflow: hidden;
    box-shadow: 0 30px 80px rgba(15,61,92,.25);
}
.ix-quote::before {
    content: '"'; position: absolute; top: -60px; left: 30px;
    font-family: Georgia, serif; font-size: 320px;
    color: rgba(201,162,39,.12); line-height: 1;
}
.ix-quote::after {
    content: ''; position: absolute; bottom: -100px; right: -100px;
    width: 400px; height: 400px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.ix-quote-grid { display: grid; grid-template-columns: auto 1fr; gap: 48px; align-items: center; position: relative; z-index: 1; }
.ix-quote-ava {
    width: 160px; height: 160px; border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    display: grid; place-items: center; font-size: 64px; color: var(--primary-dark);
    flex-shrink: 0; position: relative;
    box-shadow: 0 0 0 6px rgba(255,255,255,.1), 0 0 0 12px rgba(201,162,39,.2), 0 20px 50px rgba(0,0,0,.4);
}
.ix-quote blockquote {
    font-size: clamp(20px, 2.2vw, 28px); line-height: 1.45;
    font-weight: 500; font-style: italic; margin-bottom: 24px;
    letter-spacing: -.01em;
}
.ix-quote cite {
    display: block; font-style: normal;
}
.ix-quote cite strong { display: block; color: var(--accent-light); font-size: 17px; font-weight: 800; }
.ix-quote cite small { color: rgba(255,255,255,.6); font-size: 13px; letter-spacing: .5px; }
@media (max-width: 768px) {
    .ix-quote { padding: 48px 32px; }
    .ix-quote-grid { grid-template-columns: 1fr; text-align: center; gap: 28px; }
    .ix-quote-ava { margin: 0 auto; width: 120px; height: 120px; font-size: 48px; }
}

/* ===== PARTNERS ===== */
.ix-partners { padding: 80px 0; background: #fff; overflow: hidden; }
.ix-partners-label { text-align: center; font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 40px; }
.ix-partners-track { display: flex; gap: 64px; width: max-content; animation: ixMarquee 30s linear infinite; }
.ix-partners-item {
    padding: 24px 36px; border-radius: 16px;
    background: #fff; border: 1px solid var(--border);
    font-weight: 800; color: var(--primary-dark); font-size: 16px;
    letter-spacing: -.01em; transition: .3s;
    display: flex; align-items: center; gap: 12px;
    min-width: 200px; justify-content: center;
}
.ix-partners-item:hover { border-color: var(--accent); transform: translateY(-4px); box-shadow: 0 12px 30px rgba(15,61,92,.1); }
.ix-partners-item span { font-size: 24px; }

/* ===== MEGA CTA ===== */
.ix-mega {
    padding: 120px 0; position: relative; overflow: hidden;
    background: #061D2E; color: #fff;
}
.ix-mega-canvas { position: absolute; inset: 0; z-index: 0; }
.ix-mega-inner { position: relative; z-index: 1; text-align: center; max-width: 760px; margin: 0 auto; }
.ix-mega h2 {
    font-size: clamp(36px, 5.5vw, 72px); font-weight: 800;
    line-height: 1.05; margin-bottom: 20px; letter-spacing: -.03em;
}
.ix-mega h2 .ix-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: ixTextShine 4s linear infinite;
    font-style: italic;
}
.ix-mega p { font-size: 18px; color: rgba(255,255,255,.75); margin-bottom: 44px; }
.ix-mega-ctas { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

/* ===== ANIMATIONS ===== */
@keyframes ixFadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: none; } }
.ix-reveal { opacity: 0; transform: translateY(40px); transition: opacity .8s var(--ease-out), transform .8s var(--ease-out); }
.ix-reveal.ix-in { opacity: 1; transform: none; }

@media (max-width: 768px) {
    .ix-glass { grid-template-columns: repeat(2, 1fr); }
    .ix-gs:first-child, .ix-gs:last-child { border-radius: 0; }
    .ix-gs:nth-child(1) { border-radius: 22px 0 0 0; }
    .ix-gs:nth-child(2) { border-radius: 0 22px 0 0; }
    .ix-gs:nth-child(3) { border-radius: 0 0 0 22px; }
    .ix-gs:nth-child(4) { border-radius: 0 0 22px 0; }
    .ix-hero { padding: 100px 0 60px; }
    .ix-sw-grid, .ix-stats-wall, .ix-bento-sec, .ix-tl, .ix-sc, .ix-news, .ix-quote-sec, .ix-partners, .ix-mega { padding: 72px 0; }
}
</style>

<!-- ===== GLOBAL TEXTURES ===== -->
<div class="ix-noise"></div>
<style>
/* ===== FOTO SLIDER FULL DI HERO ===== */
.ix-hero-bg { position: absolute; inset: 0; z-index: 0; }
.ix-hero-slide {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
    opacity: 0; transition: opacity 1.6s ease;
    animation: ixHeroZoom 12s ease-in-out infinite;
}
.ix-hero-slide.on { opacity: 1; }
@keyframes ixHeroZoom { 0%,100% { transform: scale(1); } 50% { transform: scale(1.08); } }
.ix-hero-shade {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(6,29,46,.78), rgba(6,29,46,.5) 45%, rgba(6,29,46,.88));
}
/* Strip 4 kartu: foto pindah ke hero, jadi latar gelap solid */
.ix-strip { background: linear-gradient(180deg, #061D2E, #092A40); }
.ix-strip-bg { display: none; }
</style>

<style>
/* ===== HERO PRESISI v2 — foto lebih terlihat, teks lebih ringkas ===== */
.ix-hero { padding: 110px 0 70px; }

/* Overlay lebih terang: foto menembus jelas, teks tetap terbaca */
.ix-hero-shade {
    background: linear-gradient(180deg,
        rgba(6,29,46,.58) 0%,
        rgba(6,29,46,.34) 45%,
        rgba(6,29,46,.66) 100%);
}

/* Judul satu ukuran lebih ringkas + bayangan agar kontras di atas foto */
.ix-hero h1 {
    font-size: clamp(34px, 4.2vw, 58px);
    text-shadow: 0 2px 26px rgba(6,29,46,.7);
}
.ix-hero-lead {
    font-size: clamp(15px, 1.4vw, 17px);
    max-width: 620px;
    text-shadow: 0 1px 14px rgba(6,29,46,.65);
}
.ix-hero-ctas { margin-bottom: 44px; }
.ix-eyebrow { margin-bottom: 22px; padding: 7px 16px; }

/* Panel statistik semi-transparan — foto terlihat menembusnya */
.ix-glass { background: rgba(255,255,255,.08); max-width: 760px; }
.ix-gs { background: rgba(6,29,46,.38); }
</style>

<!-- ===== HERO MEGA ===== -->
<section class="ix-hero" id="hero">
    <div class="ix-aurora"></div>
    <div class="ix-grid-bg"></div>
    <div class="ix-hero-bg">
        <?php foreach ($sliderImgs as $i => $img): ?>
            <div class="ix-hero-slide <?= $i === 0 ? 'on' : '' ?>" style="background-image:url('/uploads/<?= Security::e($img) ?>');"></div>
        <?php endforeach; ?>
        <div class="ix-hero-shade"></div>
    </div>
    <div class="ix-spotlight" id="heroSpot"></div>
    <div class="container">
        <div class="ix-hero-inner">
            <span class="ix-eyebrow">
                <span class="ix-eyebrow-dot"></span>
                Lembaga Penjaminan Mutu Internal
            </span>
            <h1>
                Menjaga Kualitas,<br>
                Meraih <span class="ix-grad">Keunggulan Akademik</span>
            </h1>
            <p class="ix-hero-lead">
                Kami membangun budaya mutu melalui siklus <strong>PPEPP</strong> — Penetapan, Pelaksanaan,
                Evaluasi, Pengendalian, dan Peningkatan — yang terintegrasi penuh dengan standar
                <strong>SN-Dikti, BAN-PT, dan LAM</strong>.
            </p>
            <div class="ix-hero-ctas">
                <a href="/publik/profil.php" class="ix-btn-glow ix-btn-primary">🏛️ Tentang Kami</a>
                <a href="/publik/akreditasi.php" class="ix-btn-glow ix-btn-ghost">🏆 Lihat Akreditasi →</a>
            </div>
            <div class="ix-glass">
                <div class="ix-gs">
                    <span class="ix-gs-num" data-count="<?= $stats['total'] ?? 0 ?>">0</span>
                    <span class="ix-gs-label">Program Studi</span>
                    <svg class="ix-gs-spark" viewBox="0 0 100 20" preserveAspectRatio="none">
                        <polyline fill="none" stroke="#C9A227" stroke-width="1.5" points="0,15 20,12 40,10 60,8 80,5 100,3"/>
                    </svg>
                </div>
                <div class="ix-gs">
                    <span class="ix-gs-num" data-count="<?= $stats['unggul'] ?? 0 ?>">0</span>
                    <span class="ix-gs-label">Unggul / A</span>
                    <svg class="ix-gs-spark" viewBox="0 0 100 20" preserveAspectRatio="none">
                        <polyline fill="none" stroke="#C9A227" stroke-width="1.5" points="0,18 20,15 40,10 60,8 80,4 100,2"/>
                    </svg>
                </div>
                <div class="ix-gs">
                    <span class="ix-gs-num" data-count="<?= $stats['baik_sekali'] ?? 0 ?>">0</span>
                    <span class="ix-gs-label">Baik Sekali</span>
                    <svg class="ix-gs-spark" viewBox="0 0 100 20" preserveAspectRatio="none">
                        <polyline fill="none" stroke="#C9A227" stroke-width="1.5" points="0,10 20,12 40,8 60,10 80,6 100,4"/>
                    </svg>
                </div>
                <div class="ix-gs">
                    <span class="ix-gs-num">100<span class="ix-suffix">%</span></span>
                    <span class="ix-gs-label">Standar SN-Dikti</span>
                    <svg class="ix-gs-spark" viewBox="0 0 100 20" preserveAspectRatio="none">
                        <polyline fill="none" stroke="#C9A227" stroke-width="1.5" points="0,5 20,5 40,5 60,5 80,5 100,5"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="ix-scroll-hint">
        <span>Scroll</span>
        <div class="ix-scroll-line"></div>
    </div>
</section>

<!-- ===== STRIP 4 KARTU + FOTO SLIDER ===== -->
<style>
.ix-strip { position: relative; overflow: hidden; padding: 72px 0; }
.ix-strip-bg { position: absolute; inset: 0; }
.ix-strip-slide {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
    opacity: 0; transition: opacity 1.4s ease;
    animation: ixStripZoom 9s ease-in-out infinite;
}
.ix-strip-slide.on { opacity: 1; }
@keyframes ixStripZoom { 0%,100% { transform: scale(1.05); } 50% { transform: scale(1.12); } }
.ix-strip-fallback { background: linear-gradient(135deg, #061D2E, #0F3D5C 55%, #12365B); }
.ix-strip-shade { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(6,29,46,.84), rgba(6,29,46,.68)); }
.ix-strip-grid { position: relative; z-index: 2; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.ix-strip-card {
    display: flex; align-items: center; gap: 14px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.16); border-radius: 18px;
    padding: 20px 22px; color: #fff; transition: .35s var(--ease-out);
}
.ix-strip-card:hover { transform: translateY(-6px); background: rgba(201,162,39,.14); border-color: rgba(201,162,39,.45); }
.ix-strip-ic {
    width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    display: grid; place-items: center; font-size: 20px;
    box-shadow: 0 8px 22px rgba(201,162,39,.35);
}
.ix-strip-card strong { display: block; font-size: 15px; letter-spacing: -.01em; }
.ix-strip-card small { color: rgba(255,255,255,.65); font-size: 12px; }
@media (max-width: 992px) { .ix-strip-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 576px) { .ix-strip-grid { grid-template-columns: 1fr; } }
</style>

<section class="ix-strip">
    <div class="ix-strip-bg">
        <?php if (!empty($sliderImgs)): foreach ($sliderImgs as $i => $img): ?>
            <div class="ix-strip-slide <?= $i === 0 ? 'on' : '' ?>" style="background-image:url('/uploads/<?= Security::e($img) ?>');"></div>
        <?php endforeach; else: ?>
            <div class="ix-strip-slide on ix-strip-fallback"></div>
        <?php endif; ?>
        <div class="ix-strip-shade"></div>
    </div>
    <div class="container">
        <div class="ix-strip-grid">
            <div class="ix-strip-card">
                <div class="ix-strip-ic">🏆</div>
                <div><strong>Akreditasi</strong><small>Unggul / A</small></div>
            </div>
            <div class="ix-strip-card">
                <div class="ix-strip-ic">📊</div>
                <div><strong>Dasbor Real-time</strong><small>PPEPP Dashboard</small></div>
            </div>
            <div class="ix-strip-card">
                <div class="ix-strip-ic">🎯</div>
                <div><strong>AMI Aktif</strong><small>100% Prodi</small></div>
            </div>
            <div class="ix-strip-card">
                <div class="ix-strip-ic">🔍</div>
                <div><strong>Auditor Tersertifikasi</strong><small>Tim Profesional</small></div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var slides = document.querySelectorAll('.ix-hero-slide');
    if (slides.length < 2) return;
    var i = 0;
    setInterval(function () {
        slides[i].classList.remove('on');
        i = (i + 1) % slides.length;
        slides[i].classList.add('on');
    }, 5000);
})();
</script>

<!-- ===== TRUST MARQUEE ===== -->
<div class="ix-trust">
    <div class="ix-trust-label">Terafiliasi & Diakui Oleh</div>
    <div class="ix-trust-track">
        <?php
        $trusts = ['BAN-PT', 'LAM INFOKOM', 'LAM SAINTEK', 'SN-DIKTI 2024', 'KEMENDIKBUDRISTEK', 'PDDIKTI', 'SISTER', 'ISO 21001'];
        for ($i = 0; $i < 2; $i++):
            foreach ($trusts as $t): ?>
            <div class="ix-trust-item"><span class="ix-dot"></span> <?= $t ?></div>
        <?php endforeach; endfor; ?>
    </div>
</div>

<!-- ===== STATS WALL ===== -->
<section class="ix-stats-wall">
    <div class="container">
        <div class="ix-sw-head ix-reveal">
            <span class="ix-sw-tag">Impact dalam Angka</span>
            <h2>Komitmen Kami Terukur,<br>Hasil Kami Nyata</h2>
            <p>Data real-time yang menunjukkan perjalanan penjaminan mutu kami.</p>
        </div>
        <div class="ix-sw-grid ix-reveal">
            <div class="ix-sw-item">
                <div class="ix-sw-ico">🏫</div>
                <div class="ix-sw-num" data-count="<?= $stats['total'] ?? 0 ?>">0</div>
                <div class="ix-sw-label">Program Studi</div>
                <span class="ix-sw-trend">▲ +2 Tahun ini</span>
            </div>
            <div class="ix-sw-item">
                <div class="ix-sw-ico" style="background:linear-gradient(135deg,#C9A227,#E8C55A);color:var(--primary-dark);">🏆</div>
                <div class="ix-sw-num" data-count="<?= $stats['unggul'] ?? 0 ?>">0</div>
                <div class="ix-sw-label">Akreditasi Unggul</div>
                <span class="ix-sw-trend">▲ Target tercapai</span>
            </div>
            <div class="ix-sw-item">
                <div class="ix-sw-ico">🎯</div>
                <div class="ix-sw-num">100<span class="ix-suffix">%</span></div>
                <div class="ix-sw-label">Prodi Ter-AMI</div>
                <span class="ix-sw-trend">● On track</span>
            </div>
            <div class="ix-sw-item">
                <div class="ix-sw-ico">👥</div>
                <div class="ix-sw-num" data-count="24">0</div>
                <div class="ix-sw-label">Auditor Aktif</div>
                <span class="ix-sw-trend">▲ Tersertifikasi</span>
            </div>
        </div>
    </div>
</section>

<!-- ===== BENTO TUPOKSI 3D ===== -->
<section class="ix-bento-sec">
    <div class="container">
        <div class="ix-sw-head ix-reveal">
            <span class="ix-sw-tag">Tupoksi Kami</span>
            <h2>Enam Pilar Penjaminan Mutu<br>yang <span style="color:var(--accent);">Saling Terintegrasi</span></h2>
            <p>Struktur kerja LPM dirancang untuk memastikan mutu akademik terjaga di setiap lini.</p>
        </div>
        <div class="ix-bento">
            <div class="ix-bn ix-bn-hero ix-reveal">
                <span class="ix-bn-num">01</span>
                <div class="ix-bn-hero-eyebrow">Pilar Utama</div>
                <div class="ix-bn-ic">📋</div>
                <h3>Menetapkan Standar Mutu</h3>
                <p>Menyusun kebijakan, manual, formulir, dan standar mutu turunan SN-Dikti sebagai acuan seluruh unit akademik. Fondasi budaya mutu kampus.</p>
            </div>
            <div class="ix-bn ix-bn-sm ix-reveal">
                <span class="ix-bn-num">02</span>
                <div class="ix-bn-ic">🔍</div>
                <h3>Mengawasi Pelaksanaan</h3>
                <p>Memantau kegiatan tridharma di setiap unit dan program studi secara berkala.</p>
            </div>
            <div class="ix-bn ix-bn-sm ix-reveal">
                <span class="ix-bn-num">03</span>
                <div class="ix-bn-ic">✅</div>
                <h3>Audit Mutu Internal</h3>
                <p>Evaluasi diri dan AMI untuk menilai kinerja fakultas & prodi secara independen.</p>
            </div>
            <div class="ix-bn ix-bn-xs ix-reveal">
                <span class="ix-bn-num">04</span>
                <div class="ix-bn-ic">🏆</div>
                <h3>Mengelola Akreditasi</h3>
                <p>Borang & data dukung BAN-PT / LAM.</p>
            </div>
            <div class="ix-bn ix-bn-xs ix-reveal">
                <span class="ix-bn-num">05</span>
                <div class="ix-bn-ic">📊</div>
                <h3>Monev Real-time</h3>
                <p>Dasbor capaian standar mutu.</p>
            </div>
            <div class="ix-bn ix-bn-xs ix-reveal">
                <span class="ix-bn-num">06</span>
                <div class="ix-bn-ic">🔄</div>
                <h3>Peningkatan</h3>
                <p>RTM menyempurnakan PPEPP.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== PPEPP TIMELINE (PRESISI) ===== -->
<section class="ix-tl">
    <div class="ix-tl-inner">
        <div class="ix-tl-head ix-reveal">
            <span class="ix-sw-tag">Cara Kami Bekerja</span>
            <h2>Siklus PPEPP Berjalan Tanpa Henti</h2>
            <p>Lima langkah penjaminan mutu yang berulang setiap tahun akademik, membentuk spiral peningkatan berkelanjutan.</p>
        </div>
        <div class="ix-tl-controls">
            <button class="ix-tl-btn" id="tlPrev" aria-label="Geser kiri">←</button>
            <button class="ix-tl-btn" id="tlNext" aria-label="Geser kanan">→</button>
        </div>
    </div>

    <div class="ix-tl-track" id="tlTrack">
        <?php
        $ppepp = [
            ['📋', 'Penetapan', 'Menyusun kebijakan, manual, dan standar mutu turunan SN-Dikti.', 'Tahun ke-1'],
            ['⚙️', 'Pelaksanaan', 'Implementasi standar dalam tridharma di seluruh unit & prodi.', 'Berjalan'],
            ['🔍', 'Evaluasi', 'Evaluasi diri dan Audit Mutu Internal (AMI) secara berkala.', 'Tahunan'],
            ['🎯', 'Pengendalian', 'Tindak lanjut temuan audit dan tindakan koreksi terukur.', 'Triwulanan'],
            ['📈', 'Peningkatan', 'Rapat Tinjauan Manajemen untuk menaikkan standar mutu.', 'RTM Tahunan'],
        ];
        foreach ($ppepp as $i => $s): ?>
        <div class="ix-tl-step">
            <div class="ix-tl-top">
                <div class="ix-tl-step-num">0<?= $i + 1 ?></div>
                <span class="ix-tl-step-tag"><?= $s[3] ?></span>
            </div>
            <span class="ix-tl-step-ic"><?= $s[0] ?></span>
            <h3><?= $s[1] ?></h3>
            <p><?= $s[2] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="ix-tl-progress">
        <div class="ix-tl-progress-bar" id="tlProgress"></div>
    </div>
</section>

<script>
(function () {
    var t = document.getElementById('tlTrack');
    if (!t) return;
    var step = 320;
    var prev = document.getElementById('tlPrev');
    var next = document.getElementById('tlNext');
    if (prev) prev.addEventListener('click', function () { t.scrollBy({ left: -step, behavior: 'smooth' }); });
    if (next) next.addEventListener('click', function () { t.scrollBy({ left: step, behavior: 'smooth' }); });
})();
</script>

<!-- ===== SHOWCASE AKREDITASI ===== -->
<section class="ix-sc">
    <svg width="0" height="0" style="position:absolute;">
        <defs>
            <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#E8C55A"/>
                <stop offset="100%" stop-color="#C9A227"/>
            </linearGradient>
        </defs>
    </svg>
    <div class="container ix-sc-inner">
        <div class="ix-sc-head ix-reveal">
            <div class="ix-sc-head-text">
                <span class="ix-sw-tag" style="background:rgba(201,162,39,.18);color:var(--accent-light);border-color:rgba(201,162,39,.3);">🏆 Showcase Unggulan</span>
                <h2>Program Studi <span class="ix-grad">Unggulan</span> Kami</h2>
                <p>Prodi yang telah meraih predikat <strong style="color:var(--accent-light);">Unggul / A</strong> — bukti konkret komitmen mutu berkelanjutan.</p>
            </div>
            <a href="/publik/akreditasi.php" class="ix-btn-glow ix-btn-primary">Lihat Semua →</a>
        </div>
        <div class="ix-sc-grid">
            <?php if (empty($unggulList)): ?>
                <div style="grid-column:1/-1;text-align:center;padding:60px;color:rgba(255,255,255,.6);">
                    <div style="font-size:64px;margin-bottom:16px;">🏅</div>
                    <p style="font-size:16px;">Belum ada prodi dengan predikat Unggul. Mari kita raih bersama!</p>
                </div>
            <?php else: foreach ($unggulList as $u): ?>
                <div class="ix-sc-card ix-reveal">
                    <span class="ix-sc-badge">🏆</span>
                    <span class="ix-sc-rank"><?= Security::e($u['peringkat']) ?></span>
                    <div class="ix-sc-ring">
                        <svg viewBox="0 0 80 80">
                            <circle class="track" cx="40" cy="40" r="36"/>
                            <circle class="prog" cx="40" cy="40" r="36"/>
                        </svg>
                        <div class="num">A+</div>
                    </div>
                    <h4><?= Security::e($u['nama_prodi'] ?? '—') ?></h4>
                    <small><?= Security::e($u['nama_fakultas'] ?? '') ?></small>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<!-- ===== LIVE NEWSROOM ===== -->
<section class="ix-news">
    <div class="container">
        <div class="ix-sw-head ix-reveal">
            <span class="ix-sw-tag">Live Newsroom</span>
            <h2>Berita, Agenda & Pengumuman</h2>
            <p>Informasi terkini seputar kegiatan penjaminan mutu LPM.</p>
        </div>

        <div class="ix-news-ticker">
            <div class="ix-news-ticker-track">
                <?php
                $tickers = array_slice($berita, 0, 6);
                for ($i = 0; $i < 2; $i++):
                    foreach ($tickers as $t): ?>
                    <div class="ix-news-ticker-item">
                        <b>●</b>
                        <?= Security::e(mb_strimwidth($t['judul'], 0, 70, '...')) ?>
                    </div>
                <?php endforeach; endfor; ?>
            </div>
        </div>

        <?php
        $hero_news = $berita[0] ?? null;
        $side_news = array_slice($berita, 1, 4);
        ?>
        <div class="ix-news-grid">
            <?php if ($hero_news): ?>
            <a href="/publik/berita.php?slug=<?= Security::e($hero_news['slug']) ?>" class="ix-news-hero" style="text-decoration:none;color:inherit;">
                <div class="ix-news-hero-inner">
                    <span class="ix-cat"><?= Security::e($hero_news['kategori']) ?></span>
                    <h3><?= Security::e($hero_news['judul']) ?></h3>
                    <p><?= Security::e(mb_strimwidth(strip_tags($hero_news['konten']), 0, 160, '...')) ?></p>
                    <div class="ix-date">📅 <?= date('d M Y', strtotime($hero_news['published_at'])) ?></div>
                </div>
            </a>
            <?php endif; ?>
            <?php foreach ($side_news as $n):
                $coverClass = '';
                if ($n['kategori'] === 'Agenda') $coverClass = 'agenda';
                elseif ($n['kategori'] === 'Pengumuman') $coverClass = 'pengumuman';
                $icon = $n['kategori'] === 'Agenda' ? '📅' : ($n['kategori'] === 'Pengumuman' ? '📢' : '📰');
            ?>
                <a href="/publik/berita.php?slug=<?= Security::e($n['slug']) ?>" class="ix-news-sm" style="text-decoration:none;color:inherit;">
                    <div class="ix-news-sm-cover <?= $coverClass ?>"><span><?= $icon ?></span></div>
                    <div class="ix-news-sm-body">
                        <span class="ix-cat"><?= Security::e($n['kategori']) ?></span>
                        <h4><?= Security::e($n['judul']) ?></h4>
                        <div class="ix-date">📅 <?= date('d M Y', strtotime($n['published_at'])) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== QUOTE ===== -->
<section class="ix-quote-sec">
    <div class="container">
        <div class="ix-quote ix-reveal">
            <div class="ix-quote-grid">
                <div class="ix-quote-ava">
                    <?php if (!empty($kepalaLpm['foto'])): ?>
                        <img src="/uploads/<?= Security::e($kepalaLpm['foto']) ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    <?php else: ?>👤<?php endif; ?>
                </div>
                <div>
                    <blockquote>
                        "Mutu bukan tujuan akhir, melainkan perjalanan tanpa henti. Setiap siklus PPEPP adalah
                        kesempatan untuk tumbuh lebih baik, lebih kuat, dan lebih bermartabat sebagai institusi
                        pendidikan tinggi yang dipercaya masyarakat."
                    </blockquote>
                    <cite>
                        <strong><?= Security::e($kepalaLpm['nama_lengkap'] ?? 'Kepala LPM') ?></strong>
                        <small>Kepala Lembaga Penjaminan Mutu</small>
                    </cite>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== PARTNERS ===== -->
<section class="ix-partners">
    <div class="ix-partners-label">Terafiliasi & Diakui Oleh</div>
    <div class="ix-partners-track">
        <?php
        $partners = [
            ['🏛️', 'BAN-PT'],
            ['🎓', 'LAM'],
            ['📊', 'PDDIKTI'],
            ['🌐', 'SISTER'],
            ['🏆', 'Kemendikbud'],
            ['📚', 'Kemdikbudristek'],
        ];
        for ($i = 0; $i < 2; $i++):
            foreach ($partners as $p): ?>
            <div class="ix-partners-item"><span><?= $p[0] ?></span> <?= $p[1] ?></div>
        <?php endforeach; endfor; ?>
    </div>
</section>

<!-- ===== MEGA CTA ===== -->
<section class="ix-mega">
    <canvas class="ix-mega-canvas" id="megaCanvas"></canvas>
    <div class="container">
        <div class="ix-mega-inner ix-reveal">
            <span class="ix-eyebrow" style="background:rgba(201,162,39,.15);border-color:rgba(201,162,39,.3);color:var(--accent-light);">
                <span class="ix-eyebrow-dot"></span> Mari Berkolaborasi
            </span>
            <h2 style="margin-top:20px;">
                Wujudkan <span class="ix-grad">Budaya Mutu</span><br>
                Bersama Kami
            </h2>
            <p>Masukan Anda adalah bahan bakar peningkatan mutu. Sampaikan aspirasi atau masuk ke portal SIM-Mutu bagi civitas akademika.</p>
            <div class="ix-mega-ctas">
                <a href="/publik/pengaduan.php" class="ix-btn-glow ix-btn-primary">📨 Sampaikan Masukan</a>
                <a href="/login.php" class="ix-btn-glow ix-btn-ghost">🔐 Masuk SIM-Mutu →</a>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    'use strict';

    /* ===== SPOTLIGHT CURSOR ===== */
    var hero = document.getElementById('hero');
    var spot = document.getElementById('heroSpot');
    if (hero && spot) {
        hero.addEventListener('mousemove', function (e) {
            var r = hero.getBoundingClientRect();
            spot.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            spot.style.setProperty('--my', (e.clientY - r.top) + 'px');
        });
    }

    /* ===== BENTO 3D TILT ===== */
    document.querySelectorAll('.ix-bn').forEach(function (el) {
        el.addEventListener('mousemove', function (e) {
            var r = el.getBoundingClientRect();
            var px = ((e.clientX - r.left) / r.width - .5) * 8;
            var py = ((e.clientY - r.top) / r.height - .5) * -8;
            el.style.transform = 'translateY(-8px) rotateX(' + py + 'deg) rotateY(' + px + 'deg)';
            el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            el.style.setProperty('--my', (e.clientY - r.top) + 'px');
        });
        el.addEventListener('mouseleave', function () { el.style.transform = ''; });
    });

    /* ===== SHOWCASE 3D TILT ===== */
    document.querySelectorAll('.ix-sc-card').forEach(function (el) {
        el.addEventListener('mousemove', function (e) {
            var r = el.getBoundingClientRect();
            var px = ((e.clientX - r.left) / r.width - .5) * 6;
            var py = ((e.clientY - r.top) / r.height - .5) * -6;
            el.style.transform = 'translateY(-12px) rotateX(' + py + 'deg) rotateY(' + px + 'deg)';
        });
        el.addEventListener('mouseleave', function () { el.style.transform = ''; });
    });

    /* ===== REVEAL ON SCROLL ===== */
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) { en.target.classList.add('ix-in'); io.unobserve(en.target); }
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.ix-reveal').forEach(function (el) { io.observe(el); });

    /* ===== COUNTER → di-handle oleh main.js global (section 7) ===== */
    /* Tidak perlu custom counter di sini agar konsisten & efisien */

    /* ===== TIMELINE SCROLL PROGRESS ===== */
    var tlTrack = document.getElementById('tlTrack');
    var tlProg = document.getElementById('tlProgress');
    if (tlTrack && tlProg) {
        tlTrack.addEventListener('scroll', function () {
            var max = tlTrack.scrollWidth - tlTrack.clientWidth;
            var p = max > 0 ? tlTrack.scrollLeft / max : 0;
            tlProg.style.width = (20 + p * 80) + '%';
        });
    }

    /* ===== MEGA CTA AURORA CANVAS ===== */
    var canvas = document.getElementById('megaCanvas');
    if (canvas) {
        var ctx = canvas.getContext('2d');
        var W, H, pts = [];
        function resize() { W = canvas.width = canvas.offsetWidth; H = canvas.height = canvas.offsetHeight; }
        resize();
        window.addEventListener('resize', resize);
        for (var i = 0; i < 6; i++) {
            pts.push({
                x: Math.random() * W, y: Math.random() * H,
                vx: (Math.random() - .5) * .6, vy: (Math.random() - .5) * .6,
                r: 180 + Math.random() * 120,
                h: [45, 30, 200, 45, 180, 35][i]
            });
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
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, 6.283);
                ctx.fill();
            });
            requestAnimationFrame(step);
        })();
    }
})();
</script>

<?php require_once 'includes/footer-publik.php'; ?>