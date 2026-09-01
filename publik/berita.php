<?php
$pageTitle = 'Berita & Agenda';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();

$db = Database::getInstance();
$slug = $_GET['slug'] ?? null;
$kategori = $_GET['kategori'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 7;
$offset = ($page - 1) * $limit;

/* ========== DETAIL BERITA ========== */
if ($slug) {
    $stmt = $db->prepare("SELECT * FROM berita WHERE slug = :slug AND is_published = 1");
    $stmt->execute([':slug' => $slug]);
    $berita = $stmt->fetch();
    if (!$berita) { header('Location: /publik/berita.php'); exit; }
    $pageTitle = $berita['judul'];

    // Berita terkait
    $related = $db->prepare("SELECT * FROM berita WHERE is_published = 1 AND slug != :slug ORDER BY published_at DESC LIMIT 3");
    $related->execute([':slug' => $slug]);
    $related = $related->fetchAll();

    require_once __DIR__ . '/../includes/header-publik.php';
?>
<style>
/* ===== DETAIL BERITA ===== */
.bd-hero {
    min-height: 70vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 130px 0 60px;
    display: flex; align-items: center;
}
.bd-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: bdShift 16s ease-in-out infinite;
}
@keyframes bdShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.bd-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.bd-hero-inner { position: relative; z-index: 3; max-width: 840px; margin: 0 auto; }
.bd-back {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 18px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.15);
    color: #fff; text-decoration: none; font-size: 13px; font-weight: 700;
    margin-bottom: 28px; transition: .3s;
}
.bd-back:hover { background: rgba(255,255,255,.15); transform: translateX(-3px); }
.bd-cat {
    display: inline-block; padding: 6px 16px; border-radius: 50px;
    background: rgba(201,162,39,.18); backdrop-filter: blur(10px);
    border: 1px solid rgba(201,162,39,.3);
    color: var(--accent-light); font-size: 11px; font-weight: 800;
    letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px;
}
.bd-hero h1 {
    font-size: clamp(32px, 5vw, 58px); font-weight: 800;
    line-height: 1.1; margin-bottom: 24px; letter-spacing: -.03em;
}
.bd-hero-meta {
    display: flex; gap: 24px; flex-wrap: wrap; color: rgba(255,255,255,.7);
    font-size: 13.5px; font-weight: 500;
}
.bd-hero-meta span { display: inline-flex; align-items: center; gap: 8px; }
.bd-hero-meta .bd-dot { width: 4px; height: 4px; border-radius: 50%; background: var(--accent-light); }

/* Reading Progress Bar */
.bd-read-progress {
    position: fixed; top: 40px; left: 0; right: 0; height: 3px;
    background: rgba(15,61,92,.1); z-index: 1001;
}
body.hd-scrolled .bd-read-progress { top: 0; }
.bd-read-progress-bar {
    height: 100%; width: 0; background: linear-gradient(90deg, var(--accent), var(--accent-light));
    box-shadow: 0 0 14px rgba(201,162,39,.5);
    transition: width .1s linear;
}

/* Body */
.bd-body-sec { padding: 80px 0; background: #fff; }
.bd-body {
    max-width: 780px; margin: 0 auto;
    font-size: 17px; line-height: 1.85; color: var(--text-dark);
}
.bd-body p { margin-bottom: 18px; }
.bd-body strong { color: var(--primary-dark); font-weight: 700; }
.bd-body-drop::first-letter {
    font-size: 72px; font-weight: 900; float: left;
    line-height: .9; margin: 8px 14px 0 0;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}

/* Share floating */
.bd-share-float {
    position: fixed; left: 24px; top: 50%; transform: translateY(-50%);
    display: flex; flex-direction: column; gap: 10px; z-index: 500;
}
.bd-sf-btn {
    width: 44px; height: 44px; border-radius: 12px;
    background: #fff; border: 1px solid var(--border);
    display: grid; place-items: center; font-size: 18px;
    text-decoration: none; transition: .3s var(--ease-spring);
    box-shadow: 0 6px 20px rgba(15,61,92,.08);
}
.bd-sf-btn:hover { transform: translateX(4px) scale(1.1); box-shadow: 0 10px 30px rgba(15,61,92,.15); border-color: rgba(201,162,39,.5); }
.bd-sf-btn.primary {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    border-color: transparent; color: var(--primary-dark);
}
@media (max-width: 1200px) { .bd-share-float { display: none; } }

/* Related */
.bd-related { padding: 80px 0; background: linear-gradient(180deg, #F7F9FC, #fff); }
.bd-related-head { max-width: 720px; margin: 0 auto 40px; text-align: center; }
.bd-related-head h2 { font-size: clamp(26px, 3vw, 36px); font-weight: 800; color: var(--primary-dark); margin-bottom: 8px; letter-spacing: -.02em; }
.bd-related-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 22px; }
.bd-related-card {
    background: #fff; border-radius: 20px; overflow: hidden;
    border: 1px solid var(--border); text-decoration: none;
    transition: .4s var(--ease-out); display: flex; flex-direction: column;
    box-shadow: 0 6px 24px rgba(15,61,92,.06);
}
.bd-related-card:hover { transform: translateY(-6px); box-shadow: 0 24px 50px rgba(15,61,92,.14); border-color: rgba(201,162,39,.4); }
.bd-related-cover {
    height: 150px; position: relative; overflow: hidden;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    display: grid; place-items: center; font-size: 42px;
}
.bd-related-cover::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(400px circle at 30% 30%, rgba(201,162,39,.28), transparent 60%);
}
.bd-related-body { padding: 20px 22px; flex: 1; display: flex; flex-direction: column; }
.bd-related-body .bd-cat {
    padding: 3px 10px; font-size: 10px; margin-bottom: 10px;
    background: rgba(15,61,92,.06); border-color: transparent;
    color: var(--primary); width: fit-content;
}
.bd-related-body h4 { font-size: 15px; color: var(--primary-dark); margin-bottom: 10px; line-height: 1.35; letter-spacing: -.01em; }
.bd-related-body small { color: var(--text-muted); font-size: 12px; margin-top: auto; }
</style>

<div class="bd-read-progress"><div class="bd-read-progress-bar" id="bdProgress"></div></div>

<!-- ===== HERO ===== -->
<section class="bd-hero">
    <div class="bd-hero-aurora"></div>
    <div class="bd-hero-grid"></div>
    <div class="container">
        <div class="bd-hero-inner">
            <a href="/publik/berita.php" class="bd-back">← Kembali ke Berita</a>
            <span class="bd-cat"><?= Security::e($berita['kategori']) ?></span>
            <h1><?= Security::e($berita['judul']) ?></h1>
            <div class="bd-hero-meta">
                <span>📅 <?= date('d F Y', strtotime($berita['published_at'])) ?></span>
                <span class="bd-dot"></span>
                <span>🕐 <?= date('H:i', strtotime($berita['published_at'])) ?> WITA</span>
                <span class="bd-dot"></span>
                <span>✍️ Tim LPM</span>
            </div>
        </div>
    </div>
</section>

<!-- Floating share -->
<div class="bd-share-float">
    <a href="#" class="bd-sf-btn primary" title="Salin tautan" onclick="navigator.clipboard.writeText(location.href);this.textContent='✓';return false;">🔗</a>
    <a href="https://wa.me/?text=<?= urlencode($berita['judul'] . ' ' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" target="_blank" class="bd-sf-btn" title="WhatsApp">💬</a>
    <a href="mailto:?subject=<?= urlencode($berita['judul']) ?>&body=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" class="bd-sf-btn" title="Email">✉️</a>
</div>

<!-- Body -->
<section class="bd-body-sec">
    <div class="container">
        <div class="bd-body bd-body-drop">
            <?= nl2br(Security::e($berita['konten'])) ?>
        </div>
    </div>
</section>

<!-- Related -->
<?php if (!empty($related)): ?>
<section class="bd-related">
    <div class="container">
        <div class="bd-related-head">
            <span class="pr-tag" style="display:inline-block;padding:6px 18px;border-radius:50px;background:rgba(201,162,39,.1);color:var(--accent);font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;margin-bottom:14px;border:1px solid rgba(201,162,39,.2);">Baca Juga</span>
            <h2>Berita Terkait</h2>
        </div>
        <div class="bd-related-grid">
            <?php foreach ($related as $r): ?>
            <a href="?slug=<?= Security::e($r['slug']) ?>" class="bd-related-card">
                <div class="bd-related-cover">📰</div>
                <div class="bd-related-body">
                    <span class="bd-cat"><?= Security::e($r['kategori']) ?></span>
                    <h4><?= Security::e($r['judul']) ?></h4>
                    <small>📅 <?= date('d M Y', strtotime($r['published_at'])) ?></small>
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
    if (!bar) return;
    window.addEventListener('scroll', function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        var p = max > 0 ? (h.scrollTop / max) * 100 : 0;
        bar.style.width = p + '%';
    }, { passive: true });
})();
</script>

<?php
    require_once __DIR__ . '/../includes/footer-publik.php';
    exit;
}

/* ========== DAFTAR BERITA ========== */
$query = "SELECT * FROM berita WHERE is_published = 1";
$params = [];
if ($kategori !== 'all') { $query .= " AND kategori = :kategori"; $params[':kategori'] = $kategori; }

$countStmt = $db->prepare(str_replace("SELECT *", "SELECT COUNT(*) as total", $query));
$countStmt->execute($params);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

$query .= " ORDER BY published_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $val) $stmt->bindValue($key, $val);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$beritaList = $stmt->fetchAll();

$featured = ($page == 1 && !empty($beritaList)) ? array_shift($beritaList) : null;

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ===== BERITA LIST ===== */
.bs-hero {
    min-height: 78vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 130px 0 90px;
    display: flex; align-items: center;
}
.bs-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
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
@keyframes bsLivePulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50% { opacity: .6; transform: scale(1.3); }
}
.bs-hero h1 {
    font-size: clamp(40px, 6vw, 78px); font-weight: 800;
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

/* Ticker */
.bs-ticker {
    display: flex; gap: 40px; padding: 14px 0;
    background: rgba(255,255,255,.06); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.12); border-radius: 50px;
    overflow: hidden; max-width: 820px; margin: 0 auto;
    position: relative;
}
.bs-ticker::before {
    content: 'LIVE'; position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    padding: 4px 12px; border-radius: 50px;
    background: var(--danger); color: #fff; font-size: 10px; font-weight: 900;
    letter-spacing: 1.5px; z-index: 2;
    box-shadow: 0 0 0 0 rgba(239,68,68,.7); animation: bsLivePulse 2s ease infinite;
}
.bs-ticker-track {
    display: flex; gap: 40px; width: max-content; padding-left: 80px;
    animation: bsMarquee 45s linear infinite;
}
.bs-ticker:hover .bs-ticker-track { animation-play-state: paused; }
.bs-ticker-item {
    color: rgba(255,255,255,.85); font-size: 13px; font-weight: 600; white-space: nowrap;
    display: flex; align-items: center; gap: 10px; text-decoration: none; transition: .25s;
}
.bs-ticker-item:hover { color: var(--accent-light); }
.bs-ticker-item b { color: var(--accent-light); }
@keyframes bsMarquee { to { transform: translateX(-50%); } }

/* ===== PILLS ===== */
.bs-body { padding: 80px 0 100px; background: #fff; }
.bs-pills {
    display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;
    margin-bottom: 48px;
}
.bs-pill {
    padding: 11px 24px; border-radius: 50px; font-weight: 700; font-size: 13.5px;
    text-decoration: none; transition: .3s var(--ease-out);
    border: 1.5px solid var(--border); color: var(--text-dark); background: #fff;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 2px 8px rgba(15,61,92,.04);
}
.bs-pill:hover { border-color: rgba(201,162,39,.5); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,61,92,.1); }
.bs-pill.active {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    border-color: transparent; color: var(--primary-dark);
    box-shadow: 0 10px 28px rgba(201,162,39,.4);
}

/* Featured */
.bs-featured {
    display: grid; grid-template-columns: 1.2fr 1fr; border-radius: 28px;
    overflow: hidden; margin-bottom: 56px; position: relative;
    background: #fff; border: 1px solid var(--border);
    box-shadow: 0 20px 60px rgba(15,61,92,.12);
    transition: .5s var(--ease-out);
    text-decoration: none; color: inherit;
}
.bs-featured:hover { transform: translateY(-6px); box-shadow: 0 30px 80px rgba(15,61,92,.2); }
.bs-f-cover {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    display: grid; place-items: center; font-size: 96px; min-height: 400px;
    position: relative; overflow: hidden;
}
.bs-f-cover::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(500px circle at 30% 30%, rgba(201,162,39,.35), transparent 60%);
}
.bs-f-cover span { position: relative; z-index: 1; transition: .5s var(--ease-spring); }
.bs-featured:hover .bs-f-cover span { transform: scale(1.2) rotate(-6deg); }
.bs-f-badge {
    position: absolute; top: 24px; left: 24px;
    padding: 6px 16px; border-radius: 50px;
    background: rgba(255,255,255,.95); backdrop-filter: blur(10px);
    color: var(--primary-dark); font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase; z-index: 2;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 14px rgba(0,0,0,.15);
}
.bs-f-badge::before {
    content: '🔥'; font-size: 14px;
}
.bs-f-body { padding: 48px; display: flex; flex-direction: column; justify-content: center; }
.bs-f-body .bs-cat {
    display: inline-block; padding: 5px 14px; border-radius: 50px;
    background: rgba(15,61,92,.06); color: var(--primary);
    font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
    margin-bottom: 14px; width: fit-content;
}
.bs-f-body h2 {
    font-size: clamp(24px, 2.6vw, 32px); font-weight: 800; color: var(--primary-dark);
    margin-bottom: 14px; letter-spacing: -.02em; line-height: 1.2;
}
.bs-f-body p { color: var(--text-muted); font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
.bs-f-foot { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; }
.bs-f-foot small { color: var(--text-muted); font-size: 12.5px; font-weight: 600; }
.bs-f-cta {
    padding: 10px 22px; border-radius: 50px; font-size: 13px; font-weight: 800;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px;
    transition: .3s; box-shadow: 0 6px 18px rgba(201,162,39,.35);
}
.bs-f-cta:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(201,162,39,.5); }
@media (max-width: 992px) { .bs-featured { grid-template-columns: 1fr; } .bs-f-cover { min-height: 280px; } .bs-f-body { padding: 32px; } }

/* Card grid */
.bs-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 22px;
}
.bs-card {
    background: #fff; border-radius: 22px; overflow: hidden;
    border: 1px solid var(--border); transition: .4s var(--ease-out);
    display: flex; flex-direction: column;
    box-shadow: 0 6px 24px rgba(15,61,92,.06);
    text-decoration: none; color: inherit;
}
.bs-card:hover { transform: translateY(-6px); box-shadow: 0 24px 50px rgba(15,61,92,.14); border-color: rgba(201,162,39,.4); }
.bs-card-cover {
    height: 180px; position: relative; overflow: hidden;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    display: grid; place-items: center; font-size: 48px;
}
.bs-card-cover::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(400px circle at 30% 30%, rgba(201,162,39,.28), transparent 60%);
}
.bs-card-cover span { position: relative; z-index: 1; transition: .5s var(--ease-spring); }
.bs-card:hover .bs-card-cover span { transform: scale(1.2) rotate(-6deg); }
.bs-card-cover.agenda { background: linear-gradient(135deg, #C9A227, #E8C55A); }
.bs-card-cover.pengumuman { background: linear-gradient(135deg, #092A40, #1A5A82); }
.bs-card-body { padding: 24px; flex: 1; display: flex; flex-direction: column; }
.bs-card-body .bs-cat {
    display: inline-block; padding: 3px 10px; border-radius: 50px;
    background: rgba(15,61,92,.06); color: var(--primary);
    font-size: 10px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
    margin-bottom: 12px; width: fit-content;
}
.bs-card-body h3 { font-size: 16px; color: var(--primary-dark); margin-bottom: 8px; letter-spacing: -.01em; line-height: 1.35; }
.bs-card-body p { color: var(--text-muted); font-size: 13.5px; line-height: 1.55; margin-bottom: 14px; flex: 1; }
.bs-card-foot { display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px dashed var(--border); }
.bs-card-foot small { color: var(--text-muted); font-size: 12px; font-weight: 600; }
.bs-card-link { color: var(--primary); font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; transition: .25s; }
.bs-card:hover .bs-card-link { color: var(--accent); gap: 10px; }

/* Empty */
.bs-empty {
    grid-column: 1 / -1; text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px; border: 2px dashed var(--border);
}
.bs-empty-ico { font-size: 72px; margin-bottom: 20px; opacity: .5; animation: bsFloat 4s ease-in-out infinite; }
@keyframes bsFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
.bs-empty h3 { font-size: 22px; color: var(--primary-dark); margin-bottom: 8px; }
.bs-empty p { color: var(--text-muted); font-size: 14px; }

/* Pagination */
.bs-pagination { display: flex; justify-content: center; gap: 8px; margin-top: 56px; flex-wrap: wrap; }
.bs-pg {
    min-width: 42px; height: 42px; padding: 0 14px; border-radius: 12px;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; text-decoration: none;
    border: 1.5px solid var(--border); color: var(--text-dark); background: #fff;
    transition: .3s var(--ease-out);
}
.bs-pg:hover { border-color: rgba(201,162,39,.5); transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,61,92,.1); }
.bs-pg.active {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    border-color: transparent; color: var(--primary-dark);
    box-shadow: 0 8px 22px rgba(201,162,39,.4);
}

/* Reveal */
.bs-reveal { opacity: 0; transform: translateY(30px); transition: opacity .7s var(--ease-out), transform .7s var(--ease-out); }
.bs-reveal.bs-in { opacity: 1; transform: none; }
</style>

<!-- ===== HERO ===== -->
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
            <?php if (!empty($beritaList) || $featured): ?>
            <div class="bs-ticker">
                <div class="bs-ticker-track">
                    <?php
                    $tickers = array_merge($featured ? [$featured] : [], array_slice($beritaList, 0, 6));
                    for ($i = 0; $i < 2; $i++):
                        foreach ($tickers as $t): ?>
                        <a href="?slug=<?= Security::e($t['slug']) ?>" class="bs-ticker-item">
                            <b>●</b> <?= Security::e(mb_strimwidth($t['judul'], 0, 70, '...')) ?>
                        </a>
                    <?php endforeach; endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== BODY ===== -->
<section class="bs-body">
    <div class="container">
        <!-- Pills -->
        <div class="bs-pills bs-reveal">
            <a href="?kategori=all" class="bs-pill <?= $kategori === 'all' ? 'active' : '' ?>">📰 Semua</a>
            <a href="?kategori=Berita" class="bs-pill <?= $kategori === 'Berita' ? 'active' : '' ?>">📰 Berita</a>
            <a href="?kategori=Agenda" class="bs-pill <?= $kategori === 'Agenda' ? 'active' : '' ?>">📅 Agenda</a>
            <a href="?kategori=Pengumuman" class="bs-pill <?= $kategori === 'Pengumuman' ? 'active' : '' ?>">📢 Pengumuman</a>
        </div>

        <!-- Featured -->
        <?php if ($featured): ?>
        <a href="?slug=<?= Security::e($featured['slug']) ?>" class="bs-featured bs-reveal">
            <div class="bs-f-cover">
                <span>📰</span>
                <span class="bs-f-badge">Sorotan Utama</span>
            </div>
            <div class="bs-f-body">
                <span class="bs-cat"><?= Security::e($featured['kategori']) ?></span>
                <h2><?= Security::e($featured['judul']) ?></h2>
                <p><?= Security::e(mb_strimwidth(strip_tags($featured['konten']), 0, 200, '...')) ?></p>
                <div class="bs-f-foot">
                    <small>📅 <?= date('d M Y', strtotime($featured['published_at'])) ?></small>
                    <span class="bs-f-cta">Baca Sorotan →</span>
                </div>
            </div>
        </a>
        <?php endif; ?>

        <!-- Grid -->
        <div class="bs-grid">
            <?php if (empty($beritaList) && !$featured): ?>
                <div class="bs-empty bs-reveal">
                    <div class="bs-empty-ico">📭</div>
                    <h3>Belum ada berita</h3>
                    <p>Belum ada berita untuk kategori ini. Silakan pilih kategori lain atau kunjungi kembali nanti.</p>
                </div>
            <?php endif; ?>
            <?php foreach ($beritaList as $b):
                $coverClass = '';
                if ($b['kategori'] === 'Agenda') $coverClass = 'agenda';
                elseif ($b['kategori'] === 'Pengumuman') $coverClass = 'pengumuman';
                $icon = $b['kategori'] === 'Agenda' ? '📅' : ($b['kategori'] === 'Pengumuman' ? '📢' : '📰');
            ?>
                <a href="?slug=<?= Security::e($b['slug']) ?>" class="bs-card bs-reveal">
                    <div class="bs-card-cover <?= $coverClass ?>"><span><?= $icon ?></span></div>
                    <div class="bs-card-body">
                        <span class="bs-cat"><?= Security::e($b['kategori']) ?></span>
                        <h3><?= Security::e($b['judul']) ?></h3>
                        <p><?= Security::e(mb_strimwidth(strip_tags($b['konten']), 0, 100, '...')) ?></p>
                        <div class="bs-card-foot">
                            <small>📅 <?= date('d M Y', strtotime($b['published_at'])) ?></small>
                            <span class="bs-card-link">Baca →</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="bs-pagination bs-reveal">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&kategori=<?= Security::e($kategori) ?>" class="bs-pg">←</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&kategori=<?= Security::e($kategori) ?>" class="bs-pg <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&kategori=<?= Security::e($kategori) ?>" class="bs-pg">→</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) { en.target.classList.add('bs-in'); io.unobserve(en.target); }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.bs-reveal').forEach(function (el) { io.observe(el); });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>