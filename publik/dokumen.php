<?php
$pageTitle = 'Unduh Dokumen Publik';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();

$db = Database::getInstance();
$search = trim($_GET['search'] ?? '');
$kategori = $_GET['kategori'] ?? 'all';

$query = "SELECT d.*, k.nama_kategori FROM dokumen_mutu d
          LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori
          WHERE d.tipe_akses = 'publik' AND d.status = 'Aktif'";
$params = [];
if ($kategori !== 'all') { $query .= " AND d.id_kategori = :kategori"; $params[':kategori'] = (int)$kategori; }
if ($search) { $query .= " AND (d.judul_dokumen LIKE :s1 OR d.kode_dokumen LIKE :s2)"; $params[':s1'] = "%$search%"; $params[':s2'] = "%$search%"; }
$query .= " ORDER BY d.uploaded_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$dokumen = $stmt->fetchAll();

$kategoriList = $db->query("SELECT * FROM kategori_dokumen ORDER BY nama_kategori")->fetchAll();
$catCount = $db->query("SELECT id_kategori, COUNT(*) c FROM dokumen_mutu WHERE tipe_akses='publik' AND status='Aktif' GROUP BY id_kategori")->fetchAll();
$countMap = array_column($catCount, 'c', 'id_kategori');
$totalAll = array_sum($countMap);

function dkIcon($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) { 'pdf' => '📕', 'doc', 'docx' => '📘', 'xls', 'xlsx' => '📗', 'ppt', 'pptx' => '📙', default => '📄' };
}
function dkColor($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'pdf' => ['#DC2626', '#F87171'],
        'doc', 'docx' => ['#2563EB', '#60A5FA'],
        'xls', 'xlsx' => ['#059669', '#34D399'],
        'ppt', 'pptx' => ['#EA580C', '#FB923C'],
        default => ['#64748B', '#94A3B8']
    };
}

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   DOKUMEN STANDALONE ULTIMATE v10.0
============================================================ */
.dk-wrap {
    --p: #0F3D5C; --pl: #1A5A82; --pd: #092A40;
    --a: #C9A227; --al: #E8C55A;
    --bg: #F7F9FC; --card: #FFFFFF;
    --td: #1E293B; --tm: #64748B;
    --bd: #E2E8F0;
    --eo: cubic-bezier(.22, 1, .36, 1);
    --es: cubic-bezier(.34, 1.56, .64, 1);
}
.dk-wrap * { box-sizing: border-box; }

/* HERO */
.dk-hero {
    min-height: 78vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 130px 0 90px;
    display: flex; align-items: center;
}
.dk-hero::before {
    content: ''; position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 15% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 85% 20%, rgba(26,90,130,.45), transparent 55%),
        radial-gradient(ellipse 60% 50% at 70% 80%, rgba(232,197,90,.18), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: dkShift 16s ease-in-out infinite;
}
@keyframes dkShift { 0%,100% { filter: hue-rotate(0deg); transform: scale(1); } 50% { filter: hue-rotate(12deg); transform: scale(1.04); } }
.dk-hero::after {
    content: ''; position: absolute; inset: 0; pointer-events: none; opacity: .35;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.dk-hero-inner { position: relative; z-index: 3; max-width: 900px; margin: 0 auto; text-align: center; padding: 0 24px; }

.dk-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 28px; position: relative; overflow: hidden;
}
.dk-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.5), transparent);
    animation: dkShimmer 3.5s ease-in-out infinite;
}
@keyframes dkShimmer { to { left: 200%; } }
.dk-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #E8C55A; box-shadow: 0 0 14px #E8C55A;
    animation: dkPulse 2s ease infinite;
}
@keyframes dkPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.dk-hero h1 {
    font-size: clamp(40px, 6vw, 78px); font-weight: 800;
    line-height: 1.04; margin: 0 0 22px; letter-spacing: -.04em; color: #fff;
}
.dk-hero h1 .gr {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: dkShine 4s linear infinite; font-style: italic;
}
@keyframes dkShine { to { background-position: 200% center; } }
.dk-hero-lead { font-size: clamp(16px, 1.5vw, 18px); color: rgba(255,255,255,.75); max-width: 660px; margin: 0 auto 44px; line-height: 1.65; }

/* Glass search */
.dk-search {
    display: flex; gap: 10px; max-width: 720px; margin: 0 auto;
    background: rgba(255,255,255,.08); backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    padding: 6px; box-shadow: 0 20px 60px rgba(0,0,0,.45);
}
.dk-search input {
    padding: 14px 22px; border: none; background: transparent;
    color: #fff; font-family: inherit; font-size: 14px; outline: none; flex: 1; min-width: 0;
}
.dk-search input::placeholder { color: rgba(255,255,255,.5); }
.dk-search button {
    padding: 14px 28px; border-radius: 50px; border: none; cursor: pointer;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; font-weight: 800; font-size: 14px; font-family: inherit;
    transition: .3s; box-shadow: 0 8px 24px rgba(201,162,39,.4);
    display: inline-flex; align-items: center; gap: 8px;
}
.dk-search button:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(201,162,39,.6); }
@media (max-width: 640px) { .dk-search { flex-direction: column; border-radius: 22px; } }

/* Floating stats */
.dk-hero-stats { display: flex; justify-content: center; gap: 40px; margin-top: 40px; flex-wrap: wrap; }
.dk-hs { text-align: center; }
.dk-hs-n {
    font-size: 32px; font-weight: 800; line-height: 1;
    background: linear-gradient(180deg, #F7E491, #C9A227);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    letter-spacing: -.02em; display: block; margin-bottom: 4px;
}
.dk-hs-l { font-size: 11px; color: rgba(255,255,255,.6); letter-spacing: 1.5px; text-transform: uppercase; font-weight: 600; }

/* BODY */
.dk-body { padding: 80px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }
.dk-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

/* Pills */
.dk-pills { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-bottom: 48px; }
.dk-pill {
    padding: 10px 22px; border-radius: 50px; font-weight: 700; font-size: 13.5px;
    text-decoration: none; transition: .3s var(--eo);
    border: 1.5px solid #E2E8F0; color: #1E293B; background: #fff;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 2px 8px rgba(15,61,92,.04);
}
.dk-pill:hover { border-color: rgba(201,162,39,.5); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,61,92,.1); }
.dk-pill.on {
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    border-color: transparent; color: #092A40;
    box-shadow: 0 10px 28px rgba(201,162,39,.4);
}
.dk-pill-c {
    padding: 2px 10px; border-radius: 50px; font-size: 11px; font-weight: 800;
    background: rgba(15,61,92,.08); color: #0F3D5C;
}
.dk-pill.on .dk-pill-c { background: rgba(255,255,255,.25); color: #092A40; }

/* Cards */
.dk-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 22px;
    perspective: 1400px;
}
.dk-card {
    background: #fff; border-radius: 22px; overflow: hidden;
    border: 1px solid #E2E8F0; position: relative;
    transition: transform .5s var(--eo), box-shadow .4s, border-color .4s;
    transform-style: preserve-3d;
    box-shadow: 0 6px 24px rgba(15,61,92,.06);
    display: flex; flex-direction: column;
}
.dk-card::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(500px circle at var(--mx,50%) var(--my,50%), rgba(201,162,39,.12), transparent 40%);
    opacity: 0; transition: .4s; pointer-events: none;
}
.dk-card:hover::before { opacity: 1; }
.dk-card:hover { transform: translateY(-8px) rotateX(2deg) rotateY(-2deg); box-shadow: 0 30px 60px rgba(15,61,92,.14); border-color: rgba(201,162,39,.4); }

.dk-cover {
    height: 160px; position: relative; overflow: hidden;
    background: linear-gradient(135deg, #F7F9FC, #EEF3F8);
    display: grid; place-items: center;
}
.dk-cover::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, var(--c1, #0F3D5C), var(--c2, #1A5A82));
    opacity: .08;
}
.dk-cover-ic {
    width: 78px; height: 100px; border-radius: 8px;
    background: linear-gradient(135deg, var(--c1, #0F3D5C), var(--c2, #1A5A82));
    display: grid; place-items: center; font-size: 36px;
    box-shadow: 0 10px 30px rgba(0,0,0,.2);
    position: relative; transition: .5s var(--es);
}
.dk-card:hover .dk-cover-ic { transform: translateY(-6px) rotate(-4deg); }
.dk-cover-ext {
    position: absolute; bottom: 12px; right: 12px;
    padding: 4px 10px; border-radius: 6px;
    background: rgba(255,255,255,.95); backdrop-filter: blur(8px);
    font-size: 10px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
    color: var(--c1, #0F3D5C);
}
.dk-cat {
    position: absolute; top: 14px; left: 14px;
    padding: 5px 12px; border-radius: 50px;
    background: rgba(255,255,255,.95); backdrop-filter: blur(10px);
    color: #092A40; font-size: 10.5px; font-weight: 800;
    letter-spacing: .8px; text-transform: uppercase;
    box-shadow: 0 4px 14px rgba(0,0,0,.1);
}

.dk-body-card { padding: 24px; flex: 1; display: flex; flex-direction: column; }
.dk-code {
    display: inline-block; padding: 3px 10px; border-radius: 6px;
    background: rgba(15,61,92,.06); color: #0F3D5C;
    font-size: 11px; font-weight: 700; letter-spacing: .6px;
    margin-bottom: 10px; width: fit-content;
}
.dk-body-card h3 {
    font-size: 16px; color: #092A40; margin: 0 0 10px;
    letter-spacing: -.01em; line-height: 1.35;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
}
.dk-body-card p {
    font-size: 13.5px; color: #64748B; line-height: 1.55;
    margin: 0 0 16px; flex: 1;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
}

.dk-foot {
    padding: 14px 24px; border-top: 1px dashed #E2E8F0;
    display: flex; justify-content: space-between; align-items: center;
    background: rgba(15,61,92,.02);
}
.dk-meta { display: flex; flex-direction: column; gap: 2px; }
.dk-meta small { font-size: 10.5px; color: #64748B; letter-spacing: .5px; text-transform: uppercase; font-weight: 700; }
.dk-meta strong { font-size: 12.5px; color: #092A40; }

.dk-dl {
    padding: 10px 18px; border-radius: 50px; font-size: 12.5px; font-weight: 800;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; text-decoration: none;
    display: inline-flex; align-items: center; gap: 7px;
    transition: .3s; box-shadow: 0 6px 18px rgba(201,162,39,.35);
    position: relative; overflow: hidden;
}
.dk-dl::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.dk-dl:hover::before { left: 150%; }
.dk-dl:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(201,162,39,.5); }

/* Empty */
.dk-empty {
    grid-column: 1/-1; text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px; border: 2px dashed #E2E8F0;
    position: relative; overflow: hidden;
}
.dk-empty::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(600px circle at 50% 30%, rgba(201,162,39,.08), transparent 60%);
}
.dk-empty-ic { font-size: 72px; margin-bottom: 20px; opacity: .5; position: relative; animation: dkFloat 4s ease-in-out infinite; }
@keyframes dkFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
.dk-empty h3 { font-size: 22px; color: #092A40; margin: 0 0 8px; letter-spacing: -.01em; position: relative; }
.dk-empty p { color: #64748B; font-size: 14px; max-width: 420px; margin: 0 auto; position: relative; }

/* Note */
.dk-note {
    margin-top: 48px; padding: 22px 28px;
    background: linear-gradient(135deg, rgba(201,162,39,.08), rgba(15,61,92,.04));
    border: 1px solid rgba(201,162,39,.25); border-left: 5px solid #C9A227;
    border-radius: 18px;
    display: flex; gap: 18px; align-items: flex-start;
}
.dk-note-ic {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; display: grid; place-items: center; font-size: 20px;
    box-shadow: 0 6px 18px rgba(201,162,39,.35);
}
.dk-note h4 { font-size: 15px; color: #092A40; margin: 0 0 4px; }
.dk-note p { font-size: 14px; color: #64748B; line-height: 1.55; margin: 0; }
.dk-note strong { color: #C9A227; }

.dk-rev { opacity: 0; transform: translateY(30px); transition: opacity .7s var(--eo), transform .7s var(--eo); }
.dk-rev.in { opacity: 1; transform: none; }
</style>

<div class="dk-wrap">

<section class="dk-hero">
    <div class="dk-hero-inner">
        <span class="dk-badge"><span class="dk-badge-dot"></span> Pusat Dokumen Publik</span>
        <h1>Dokumen <span class="gr">Mutu</span><br>Akses Terbuka</h1>
        <p class="dk-hero-lead">Kebijakan, standar, manual, formulir, dan publikasi resmi LPM tersedia untuk diunduh secara transparan.</p>
        <form method="GET" class="dk-search">
            <input type="text" name="search" placeholder="🔍 Cari judul atau kode dokumen..." value="<?= Security::e($search) ?>">
            <input type="hidden" name="kategori" value="<?= Security::e($kategori) ?>">
            <button type="submit">🔍 Cari</button>
        </form>
        <div class="dk-hero-stats">
            <div class="dk-hs"><span class="dk-hs-n"><?= $totalAll ?></span><span class="dk-hs-l">Total Dokumen</span></div>
            <div class="dk-hs"><span class="dk-hs-n"><?= count($kategoriList) ?></span><span class="dk-hs-l">Kategori</span></div>
            <div class="dk-hs"><span class="dk-hs-n">24/7</span><span class="dk-hs-l">Akses Online</span></div>
            <div class="dk-hs"><span class="dk-hs-n">100%</span><span class="dk-hs-l">Gratis</span></div>
        </div>
    </div>
</section>

<section class="dk-body">
    <div class="dk-container">
        <div class="dk-pills dk-rev">
            <a href="?kategori=all&search=<?= urlencode($search) ?>" class="dk-pill <?= $kategori === 'all' ? 'on' : '' ?>">🗂️ Semua <span class="dk-pill-c"><?= $totalAll ?></span></a>
            <?php foreach ($kategoriList as $kat): ?>
                <a href="?kategori=<?= $kat['id_kategori'] ?>&search=<?= urlencode($search) ?>" class="dk-pill <?= $kategori == $kat['id_kategori'] ? 'on' : '' ?>">
                    <?= Security::e($kat['nama_kategori']) ?> <span class="dk-pill-c"><?= $countMap[$kat['id_kategori']] ?? 0 ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="dk-grid">
            <?php if (empty($dokumen)): ?>
                <div class="dk-empty dk-rev">
                    <div class="dk-empty-ic">📭</div>
                    <h3>Tidak ada dokumen ditemukan</h3>
                    <p>Coba ubah kata kunci atau pilih kategori lain.</p>
                </div>
            <?php endif; ?>
            <?php foreach ($dokumen as $doc):
                $colors = dkColor($doc['file_path']);
                $ext = strtoupper(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
            ?>
            <article class="dk-card dk-rev" style="--c1:<?= $colors[0] ?>;--c2:<?= $colors[1] ?>;">
                <div class="dk-cover">
                    <span class="dk-cat"><?= Security::e($doc['nama_kategori']) ?></span>
                    <div class="dk-cover-ic"><?= dkIcon($doc['file_path']) ?></div>
                    <span class="dk-cover-ext"><?= $ext ?></span>
                </div>
                <div class="dk-body-card">
                    <span class="dk-code"><?= Security::e($doc['kode_dokumen']) ?></span>
                    <h3><?= Security::e($doc['judul_dokumen']) ?></h3>
                    <p><?= Security::e(mb_strimwidth($doc['deskripsi'] ?? 'Dokumen resmi penjaminan mutu.', 0, 120, '...')) ?></p>
                </div>
                <div class="dk-foot">
                    <div class="dk-meta">
                        <small>📅 Diunggah</small>
                        <strong><?= date('d M Y', strtotime($doc['uploaded_at'])) ?> • v<?= $doc['versi'] ?></strong>
                    </div>
                    <a href="/download.php?id=<?= $doc['id_dokumen'] ?>&type=publik" class="dk-dl">📥 Unduh</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="dk-note dk-rev">
            <div class="dk-note-ic">💡</div>
            <div>
                <h4>Hak Akses & Versi Dokumen</h4>
                <p>Seluruh dokumen publik dapat diunduh bebas. Dokumen internal hanya tersedia bagi civitas akademika melalui portal <strong>SIM-Mutu</strong>.</p>
            </div>
        </div>
    </div>
</section>

</div>

<script>
(function () {
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.dk-rev').forEach(function (el) { io.observe(el); });

    document.querySelectorAll('.dk-card').forEach(function (el) {
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
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>