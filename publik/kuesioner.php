<?php
$pageTitle = 'Kuesioner Monev';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();

$db = Database::getInstance();
$token = $_GET['token'] ?? '';
$pesan = ''; $error = '';

$stmt = $db->prepare("SELECT * FROM kuesioner_periode WHERE token = ?");
$stmt->execute([$token]);
$periode = $stmt->fetch();

$sidik = sha1(($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
$sudahIsi = false;
if ($periode) {
    $c = $db->prepare("SELECT COUNT(*) n FROM kuesioner_jawaban WHERE id_periode = ? AND sidik_responden = ?");
    $c->execute([$periode['id_periode'], $sidik]);
    $sudahIsi = $c->fetch()['n'] > 0;
}

if ($periode && $periode['status'] === 'Aktif' && !$sudahIsi && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $skor = $_POST['skor'] ?? [];
    $valid = true;
    foreach ($skor as $v) { if (!in_array((int)$v, [1,2,3,4,5], true)) $valid = false; }
    if (empty($skor) || !$valid) {
        $error = 'Mohon beri penilaian (1–5 bintang) pada semua pertanyaan.';
    } else {
        foreach ($skor as $qid => $v) {
            $db->prepare("INSERT INTO kuesioner_jawaban (id_periode, id_pertanyaan, sidik_responden, skor) VALUES (?,?,?,?)")
               ->execute([$periode['id_periode'], (int)$qid, $sidik, (int)$v]);
        }
        $sudahIsi = true;
        $pesan = 'Terima kasih! Masukan Anda sangat berarti bagi peningkatan mutu kampus. 🎉';
    }
}

$questions = [];
if ($periode) {
    $q = $db->prepare("SELECT * FROM kuesioner_pertanyaan WHERE id_periode = ? ORDER BY id_pertanyaan");
    $q->execute([$periode['id_periode']]);
    $questions = $q->fetchAll();
}

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   KUESIONER MONEV ULTIMATE v8.0 — Glass • Progress • Aspect Colors
============================================================ */

/* ===== HERO ===== */
.kq-hero {
    min-height: 60vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 120px 0 60px;
    display: flex; align-items: center;
}
.kq-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 15% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 85% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: kqShift 16s ease-in-out infinite;
}
@keyframes kqShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.kq-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.kq-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; }

.kq-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.kq-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: kqShimmer 3.5s ease-in-out infinite;
}
@keyframes kqShimmer { to { left: 200%; } }
.kq-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: kqPulse 2s ease infinite;
}
@keyframes kqPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.kq-hero h1 {
    font-size: clamp(34px, 5vw, 62px); font-weight: 800;
    line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em;
}
.kq-hero h1 .kq-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: kqTextShine 4s linear infinite; font-style: italic;
}
@keyframes kqTextShine { to { background-position: 200% center; } }
.kq-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

.kq-trust { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
.kq-trust-item {
    padding: 7px 14px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.kq-trust-item span { color: #10B981; }

/* ===== BODY ===== */
.kq-body { padding: 60px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }

/* Glass Card */
.kq-card {
    max-width: 820px; margin: 0 auto;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid var(--border); position: relative;
    box-shadow: 0 24px 70px rgba(15,61,92,.1);
}
.kq-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--primary), var(--accent));
    background-size: 200% 100%; animation: kqGradLine 4s linear infinite;
}
@keyframes kqGradLine { to { background-position: 200% 0; } }

/* Periode banner */
.kq-per {
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 28px 36px;
    display: flex; justify-content: space-between; align-items: center;
    gap: 16px; flex-wrap: wrap; position: relative; overflow: hidden;
}
.kq-per::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.kq-per-info { position: relative; z-index: 1; }
.kq-per-info small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: block; margin-bottom: 4px; }
.kq-per-info h3 { font-size: 22px; font-weight: 800; margin-bottom: 4px; letter-spacing: -.02em; }
.kq-per-info p { color: rgba(255,255,255,.85); font-size: 13.5px; }
.kq-per-badge {
    padding: 7px 16px; border-radius: 50px;
    background: rgba(16,185,129,.18); border: 1px solid rgba(16,185,129,.35);
    color: #6EE7B7; font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase;
    position: relative; z-index: 1;
}

/* Progress sticky */
.kq-progress-wrap {
    padding: 18px 36px; background: rgba(247,249,252,.95); backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center; gap: 14px;
    position: sticky; top: 118px; z-index: 10;
}
body.hd-scrolled .kq-progress-wrap { top: 78px; }
.kq-progress-label { font-size: 13px; font-weight: 700; color: var(--primary-dark); }
.kq-progress-label span { color: var(--accent); }
.kq-progress-bar {
    flex: 1; max-width: 420px; height: 8px; background: var(--border);
    border-radius: 8px; overflow: hidden;
}
.kq-progress-fill {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, var(--accent), var(--accent-light));
    border-radius: 8px; transition: width .4s var(--ease-out);
    box-shadow: 0 0 14px rgba(201,162,39,.4);
}
.kq-progress-count {
    padding: 4px 12px; border-radius: 50px;
    background: rgba(201,162,39,.12); color: var(--accent);
    font-size: 12px; font-weight: 800; letter-spacing: .5px;
}

/* Form body */
.kq-form-body { padding: 36px; }

/* Question item */
.kq-q {
    padding: 22px 0; border-bottom: 1px dashed var(--border);
    transition: .3s;
}
.kq-q:last-of-type { border-bottom: none; }
.kq-q.answered {
    background: linear-gradient(90deg, rgba(16,185,129,.04), transparent);
    padding-left: 12px; margin-left: -12px; margin-right: -12px;
    padding-right: 12px; border-radius: 12px;
}
.kq-q-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 10px; flex-wrap: wrap; }
.kq-q-num {
    display: inline-block; padding: 3px 10px; border-radius: 8px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff; font-size: 11px; font-weight: 800; letter-spacing: 1px;
    min-width: 32px; text-align: center;
}
.kq-q.answered .kq-q-num { background: linear-gradient(135deg, #10B981, #34D399); }
.kq-aspek {
    padding: 4px 12px; border-radius: 50px;
    font-size: 10.5px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
    border: 1px solid transparent;
}
.kq-aspek.Pendidikan { background: rgba(16,185,129,.1); color: #059669; border-color: rgba(16,185,129,.25); }
.kq-aspek.Penelitian { background: rgba(59,130,246,.1); color: #1D4ED8; border-color: rgba(59,130,246,.25); }
.kq-aspek.Pengabdian { background: rgba(245,158,11,.1); color: #B45309; border-color: rgba(245,158,11,.25); }
.kq-aspek.Layanan { background: rgba(139,92,246,.1); color: #6D28D9; border-color: rgba(139,92,246,.25); }
.kq-aspek.Sarana { background: rgba(236,72,153,.1); color: #BE185D; border-color: rgba(236,72,153,.25); }
.kq-aspek-default { background: rgba(15,61,92,.06); color: var(--primary); }

.kq-q-text { font-weight: 600; font-size: 15px; color: var(--primary-dark); line-height: 1.5; }

/* Stars besar */
.kq-stars {
    display: flex; flex-direction: row-reverse; justify-content: flex-end;
    gap: 8px; margin-top: 14px;
}
.kq-stars input { display: none; }
.kq-stars label {
    font-size: 36px; color: #D8DEE8; cursor: pointer;
    transition: .2s var(--ease-spring); line-height: 1;
}
.kq-stars label:hover,
.kq-stars label:hover ~ label,
.kq-stars input:checked ~ label {
    color: var(--accent);
    text-shadow: 0 0 20px rgba(201,162,39,.6);
}
.kq-stars label:hover { transform: scale(1.25) rotate(-5deg); }
.kq-stars input:checked ~ label { animation: kqStarPop .5s var(--ease-spring); }
@keyframes kqStarPop { 0% { transform: scale(1); } 50% { transform: scale(1.3); } 100% { transform: scale(1); } }

/* Submit */
.kq-submit {
    margin-top: 28px; width: 100%; padding: 18px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); box-shadow: 0 12px 36px rgba(201,162,39,.45);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.kq-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.kq-submit:hover::before { left: 150%; }
.kq-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(201,162,39,.65); }

/* Alert */
.kq-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600;
    display: flex; gap: 12px; align-items: center;
    animation: kqAlertIn .45s var(--ease-out);
}
@keyframes kqAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.kq-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid var(--danger); }

/* States */
.kq-state { text-align: center; padding: 60px 40px; }
.kq-state-ic {
    width: 100px; height: 100px; border-radius: 50%; margin: 0 auto 24px;
    display: grid; place-items: center; font-size: 48px;
    box-shadow: 0 18px 50px rgba(0,0,0,.15);
}
.kq-state-ic.error { background: linear-gradient(135deg, #64748B, #94A3B8); color: #fff; }
.kq-state-ic.success {
    background: linear-gradient(135deg, #10B981, #34D399); color: #fff;
    animation: kqSuccessPop .7s var(--ease-spring);
}
@keyframes kqSuccessPop {
    0% { transform: scale(0) rotate(-180deg); }
    60% { transform: scale(1.15) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}
.kq-state h3 { font-size: 24px; color: var(--primary-dark); margin-bottom: 10px; letter-spacing: -.02em; }
.kq-state p { color: var(--text-muted); font-size: 15px; max-width: 440px; margin: 0 auto; line-height: 1.6; }

@media (max-width: 640px) {
    .kq-form-body { padding: 24px; }
    .kq-per { padding: 24px; }
    .kq-stars label { font-size: 30px; }
    .kq-progress-wrap { padding: 14px 20px; top: 118px; }
}
</style>

<!-- ===== HERO ===== -->
<section class="kq-hero">
    <div class="kq-hero-aurora"></div>
    <div class="kq-hero-grid"></div>
    <div class="container">
        <div class="kq-hero-inner">
            <span class="kq-hero-badge">
                <span class="kq-hero-badge-dot"></span>
                Kuesioner Monitoring & Evaluasi
            </span>
            <h1>Suara Anda, <span class="kq-grad">Mutu Kami</span></h1>
            <p class="kq-hero-lead">
                Penilaian singkat (±2 menit), anonim, dan berdampak langsung pada
                peningkatan layanan akademik kampus.
            </p>
            <div class="kq-trust">
                <div class="kq-trust-item"><span>🔒</span> Anonim</div>
                <div class="kq-trust-item"><span>⚡</span> ±2 Menit</div>
                <div class="kq-trust-item"><span>📊</span> Berdampak</div>
                <div class="kq-trust-item"><span>✓</span> Gratis</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== BODY ===== -->
<section class="kq-body">
    <div class="container">
        <div class="kq-card">
            <?php if (!$periode || $periode['status'] !== 'Aktif'): ?>
                <div class="kq-state">
                    <div class="kq-state-ic error">🔒</div>
                    <h3>Kuesioner Tidak Tersedia</h3>
                    <p>Tautan tidak valid atau periode kuesioner telah ditutup. Silakan hubungi pihak LPM untuk informasi lebih lanjut.</p>
                </div>
            <?php elseif ($sudahIsi): ?>
                <div class="kq-state">
                    <div class="kq-state-ic success">🎉</div>
                    <h3><?= $pesan ? Security::e($pesan) : 'Anda Sudah Mengisi' ?></h3>
                    <p>Setiap responden hanya dapat mengisi satu kali demi kevalidan data. Terima kasih atas partisipasi Anda!</p>
                    <a href="/publik/berita.php" class="btn btn-primary" style="margin-top:24px;padding:10px 22px;">Baca Berita Terbaru →</a>
                </div>
            <?php else: ?>
                <!-- Periode Banner -->
                <div class="kq-per">
                    <div class="kq-per-info">
                        <small>Periode Aktif</small>
                        <h3><?= Security::e($periode['nama_periode']) ?></h3>
                        <p>Responden: <strong><?= Security::e($periode['tipe_responden']) ?></strong> • <?= count($questions) ?> pernyataan</p>
                    </div>
                    <span class="kq-per-badge">● Aktif</span>
                </div>

                <!-- Progress -->
                <div class="kq-progress-wrap">
                    <span class="kq-progress-label">Progres: <span id="kqCount">0</span> / <?= count($questions) ?></span>
                    <div class="kq-progress-bar"><div class="kq-progress-fill" id="kqFill"></div></div>
                    <span class="kq-progress-count" id="kqPercent">0%</span>
                </div>

                <div class="kq-form-body">
                    <?php if ($error): ?><div class="kq-alert danger">⚠️ <?= Security::e($error) ?></div><?php endif; ?>

                    <form method="POST" id="kqForm">
                        <?= Security::csrfField() ?>
                        <?php foreach ($questions as $i => $q):
                            $aspek = $q['aspek'] ?? 'Lainnya';
                            $aspekClass = in_array($aspek, ['Pendidikan','Penelitian','Pengabdian','Layanan','Sarana']) ? $aspek : 'default';
                        ?>
                        <div class="kq-q" data-q="<?= $q['id_pertanyaan'] ?>">
                            <div class="kq-q-head">
                                <span class="kq-q-num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                                <span class="kq-aspek kq-aspek-<?= $aspekClass ?>"><?= Security::e($aspek) ?></span>
                            </div>
                            <p class="kq-q-text"><?= Security::e($q['pertanyaan']) ?></p>
                            <div class="kq-stars">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                    <input type="radio" id="q<?= $q['id_pertanyaan'] ?>_<?= $s ?>" name="skor[<?= $q['id_pertanyaan'] ?>]" value="<?= $s ?>" required>
                                    <label for="q<?= $q['id_pertanyaan'] ?>_<?= $s ?>" title="<?= $s ?> bintang">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <button type="submit" class="kq-submit" id="kqSubmit">📨 Kirim Penilaian Sekarang</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function () {
    var form = document.getElementById('kqForm');
    if (!form) return;

    var fill = document.getElementById('kqFill');
    var count = document.getElementById('kqCount');
    var percent = document.getElementById('kqPercent');
    var total = form.querySelectorAll('.kq-q').length;

    function updateProgress() {
        var answered = 0;
        form.querySelectorAll('.kq-q').forEach(function (q) {
            var name = q.querySelector('input[type="radio"]').name;
            var checked = form.querySelector('input[name="' + name + '"]:checked');
            if (checked) { answered++; q.classList.add('answered'); }
            else { q.classList.remove('answered'); }
        });
        var p = total > 0 ? Math.round((answered / total) * 100) : 0;
        if (fill) fill.style.width = p + '%';
        if (count) count.textContent = answered;
        if (percent) percent.textContent = p + '%';
    }

    form.addEventListener('change', updateProgress);
    updateProgress();

    form.addEventListener('submit', function () {
        var btn = document.getElementById('kqSubmit');
        if (btn && !btn.dataset.busy) {
            btn.dataset.busy = '1';
            btn.innerHTML = '⏳ Mengirim...';
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>