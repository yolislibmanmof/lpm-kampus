<?php
$pageTitle = 'EDOM — Evaluasi Dosen oleh Mahasiswa';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();
$db = Database::getInstance();

$EDOM_Q = [
    1  => 'Kejelasan penyampaian tujuan & materi pembelajaran',
    2  => 'Penguasaan materi dan kemampuan menjawab pertanyaan',
    3  => 'Penggunaan metode mengajar yang bervariasi & menarik',
    4  => 'Pemanfaatan media & teknologi pembelajaran',
    5  => 'Ketepatan waktu & konsistensi kehadiran mengajar',
    6  => 'Interaksi & kepedulian terhadap kesulitan mahasiswa',
    7  => 'Keadilan & transparansi penilaian/evaluasi',
    8  => 'Pemberian umpan balik (feedback) atas tugas',
    9  => 'Sikap, etika, dan keteladanan di dalam kelas',
    10 => 'Kemampuan memotivasi & menginspirasi mahasiswa',
];

$token = $_GET['token'] ?? '';
$kelas = null;
if ($token) {
    $st = $db->prepare("SELECT k.*, d.nama_dosen, per.nama periode_nama, per.status status_periode, p.nama_prodi
        FROM edom_kelas k
        JOIN edom_periode per ON k.id_periode = per.id_periode
        JOIN dosen d ON k.id_dosen = d.id_dosen
        LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
        WHERE k.token = ?");
    $st->execute([$token]);
    $kelas = $st->fetch();
}

$success = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kelas) {
    Security::verifyCsrf();
    if ($kelas['status_periode'] !== 'Terbuka') $error = 'Periode evaluasi ini sudah ditutup.';
    else {
        $nim = trim($_POST['nim'] ?? '');
        $chk = $db->prepare("SELECT id_jawaban FROM edom_jawaban WHERE id_kelas = ? AND nim = ?");
        $chk->execute([$kelas['id_kelas'], $nim]);
        if (!$nim) $error = 'NIM wajib diisi.';
        elseif ($chk->fetch()) $error = 'NIM ini sudah mengisi untuk kelas ini. Terima kasih! 🙏';
        else {
            $ok = true; $vals = [];
            for ($i = 1; $i <= 10; $i++) { $v = (int)($_POST['q' . $i] ?? 0); if ($v < 1 || $v > 5) $ok = false; $vals[] = $v; }
            if (!$ok) $error = 'Beri penilaian bintang 1–5 untuk semua aspek.';
            else {
                $db->prepare("INSERT INTO edom_jawaban (id_kelas, nim, q1,q2,q3,q4,q5,q6,q7,q8,q9,q10, komentar) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                   ->execute(array_merge([$kelas['id_kelas'], $nim], $vals, [trim($_POST['komentar'] ?? '')]));
                $success = true;
            }
        }
    }
}
require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   EDOM ULTIMATE v8.0 — Glass Form • Big Stars • Progress Tracker
============================================================ */

/* ===== HERO ===== */
.ed-hero {
    min-height: 60vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 120px 0 60px;
    display: flex; align-items: center;
}
.ed-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: edShift 16s ease-in-out infinite;
}
@keyframes edShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.ed-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.ed-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; }

.ed-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.ed-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: edShimmer 3.5s ease-in-out infinite;
}
@keyframes edShimmer { to { left: 200%; } }
.ed-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: edPulse 2s ease infinite;
}
@keyframes edPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.ed-hero h1 {
    font-size: clamp(34px, 5vw, 62px); font-weight: 800;
    line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em;
}
.ed-hero h1 .ed-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: edTextShine 4s linear infinite; font-style: italic;
}
@keyframes edTextShine { to { background-position: 200% center; } }
.ed-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

/* Trust strip */
.ed-trust { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
.ed-trust-item {
    padding: 7px 14px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.ed-trust-item span { color: #10B981; }

/* ===== BODY ===== */
.ed-body { padding: 60px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }

/* Glass Card */
.ed-card {
    max-width: 820px; margin: 0 auto;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid var(--border); position: relative;
    box-shadow: 0 24px 70px rgba(15,61,92,.1);
}
.ed-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--primary), var(--accent));
    background-size: 200% 100%; animation: edGradLine 4s linear infinite;
}
@keyframes edGradLine { to { background-position: 200% 0; } }

/* Dosen info banner */
.ed-dosen {
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 32px 36px;
    display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
    position: relative; overflow: hidden;
}
.ed-dosen::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.ed-dosen-ava {
    width: 68px; height: 68px; border-radius: 20px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); display: grid; place-items: center;
    font-size: 28px; font-weight: 900;
    box-shadow: 0 10px 30px rgba(201,162,39,.4);
    position: relative; z-index: 1;
}
.ed-dosen-info { flex: 1; min-width: 220px; position: relative; z-index: 1; }
.ed-dosen-info small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: block; margin-bottom: 4px; }
.ed-dosen-info h3 { font-size: 22px; font-weight: 800; margin-bottom: 4px; letter-spacing: -.02em; }
.ed-dosen-info p { color: rgba(255,255,255,.85); font-size: 14px; }
.ed-dosen-per {
    padding: 6px 16px; border-radius: 50px;
    background: rgba(16,185,129,.18); border: 1px solid rgba(16,185,129,.35);
    color: #6EE7B7; font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase;
    position: relative; z-index: 1;
}

/* Progress tracker */
.ed-progress-wrap {
    padding: 20px 36px; background: var(--bg-light);
    border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center; gap: 14px;
    position: sticky; top: 118px; z-index: 10; backdrop-filter: blur(12px);
    background: rgba(247,249,252,.95);
}
body.hd-scrolled .ed-progress-wrap { top: 78px; }
.ed-progress-label { font-size: 13px; font-weight: 700; color: var(--primary-dark); }
.ed-progress-label span { color: var(--accent); }
.ed-progress-bar {
    flex: 1; max-width: 420px; height: 8px; background: var(--border);
    border-radius: 8px; overflow: hidden;
}
.ed-progress-fill {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, var(--accent), var(--accent-light));
    border-radius: 8px; transition: width .4s var(--ease-out);
    box-shadow: 0 0 14px rgba(201,162,39,.4);
}
.ed-progress-count {
    padding: 4px 12px; border-radius: 50px;
    background: rgba(201,162,39,.12); color: var(--accent);
    font-size: 12px; font-weight: 800; letter-spacing: .5px;
}

/* Form body */
.ed-form-body { padding: 36px; }

/* Legend premium */
.ed-legend-wrap {
    padding: 22px; border-radius: 18px;
    background: linear-gradient(135deg, rgba(201,162,39,.06), rgba(15,61,92,.03));
    border: 1px solid rgba(201,162,39,.2);
    margin-bottom: 28px;
}
.ed-legend-title { font-size: 13px; font-weight: 800; color: var(--primary-dark); margin-bottom: 12px; letter-spacing: .5px; }
.ed-legend {
    display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;
}
.ed-lg-item {
    padding: 12px 8px; border-radius: 12px;
    background: #fff; border: 1px solid var(--border);
    text-align: center; transition: .3s;
}
.ed-lg-item:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,61,92,.08); }
.ed-lg-stars { font-size: 16px; color: var(--accent); margin-bottom: 4px; }
.ed-lg-stars.dim { color: #D8DEE8; }
.ed-lg-label { font-size: 11px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; }
.ed-lg-item.s1 .ed-lg-label { color: #EF4444; }
.ed-lg-item.s2 .ed-lg-label { color: #F59E0B; }
.ed-lg-item.s3 .ed-lg-label { color: #64748B; }
.ed-lg-item.s4 .ed-lg-label { color: #10B981; }
.ed-lg-item.s5 .ed-lg-label { color: var(--accent); }
@media (max-width: 640px) { .ed-legend { grid-template-columns: repeat(5, 1fr); gap: 4px; } .ed-lg-item { padding: 8px 4px; } .ed-lg-label { font-size: 9px; } }

/* NIM field */
.ed-nim-field {
    position: relative; margin-bottom: 24px;
}
.ed-nim-field input {
    width: 100%; padding: 18px 18px 18px 48px;
    border: 2px solid var(--border); border-radius: 14px;
    font-size: 15px; font-family: inherit; outline: none;
    background: #fff; color: var(--text-dark); transition: .3s;
}
.ed-nim-field input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(201,162,39,.15); }
.ed-nim-field label {
    position: absolute; left: 48px; top: 18px;
    font-size: 14px; color: var(--text-muted);
    pointer-events: none; transition: .25s var(--ease-out);
    background: #fff; padding: 0 6px; font-weight: 600;
}
.ed-nim-field input:focus ~ label,
.ed-nim-field input:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 11px; color: var(--accent);
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
}
.ed-nim-field-ic {
    position: absolute; left: 16px; top: 18px; font-size: 18px; color: var(--text-muted);
}

/* Question item */
.ed-q {
    padding: 22px 0; border-bottom: 1px dashed var(--border);
    transition: .3s;
}
.ed-q:last-of-type { border-bottom: none; }
.ed-q.answered { background: linear-gradient(90deg, rgba(16,185,129,.03), transparent); padding-left: 12px; margin-left: -12px; margin-right: -12px; padding-right: 12px; border-radius: 12px; }
.ed-q-num {
    display: inline-block; padding: 3px 10px; border-radius: 8px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff; font-size: 11px; font-weight: 800; letter-spacing: 1px;
    margin-bottom: 8px; min-width: 32px; text-align: center;
}
.ed-q.answered .ed-q-num { background: linear-gradient(135deg, #10B981, #34D399); }
.ed-q-text { font-weight: 600; font-size: 15px; color: var(--primary-dark); line-height: 1.5; }

/* Stars besar */
.ed-stars {
    display: flex; flex-direction: row-reverse; justify-content: flex-end;
    gap: 8px; margin-top: 14px;
}
.ed-stars input { display: none; }
.ed-stars label {
    font-size: 38px; color: #D8DEE8; cursor: pointer;
    transition: .2s var(--ease-spring); line-height: 1;
    position: relative;
}
.ed-stars label:hover,
.ed-stars label:hover ~ label,
.ed-stars input:checked ~ label {
    color: var(--accent);
    text-shadow: 0 0 20px rgba(201,162,39,.6);
}
.ed-stars label:hover { transform: scale(1.25) rotate(-5deg); }
.ed-stars input:checked ~ label { animation: edStarPop .5s var(--ease-spring); }
@keyframes edStarPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

/* Komentar */
.ed-komentar {
    margin-top: 20px; position: relative;
}
.ed-komentar textarea {
    width: 100%; padding: 18px; border: 2px solid var(--border); border-radius: 14px;
    font-size: 14px; font-family: inherit; outline: none; resize: vertical;
    min-height: 100px; transition: .3s;
}
.ed-komentar textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(201,162,39,.15); }
.ed-komentar-label {
    font-size: 11px; font-weight: 800; letter-spacing: 1px;
    text-transform: uppercase; color: var(--accent);
    margin-bottom: 8px; display: block;
}

/* Submit */
.ed-submit {
    margin-top: 28px; width: 100%; padding: 18px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); box-shadow: 0 12px 36px rgba(201,162,39,.45);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.ed-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.ed-submit:hover::before { left: 150%; }
.ed-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(201,162,39,.65); }
.ed-submit:disabled { opacity: .6; cursor: not-allowed; }

/* Alert */
.ed-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600;
    display: flex; gap: 12px; align-items: center;
    animation: edAlertIn .45s var(--ease-out);
}
@keyframes edAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.ed-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid var(--danger); }

/* Empty / Success states */
.ed-state { text-align: center; padding: 60px 40px; }
.ed-state-ic {
    width: 100px; height: 100px; border-radius: 50%; margin: 0 auto 24px;
    display: grid; place-items: center; font-size: 48px;
    box-shadow: 0 18px 50px rgba(0,0,0,.15);
}
.ed-state-ic.error { background: linear-gradient(135deg, #EF4444, #F87171); color: #fff; }
.ed-state-ic.success {
    background: linear-gradient(135deg, #10B981, #34D399); color: #fff;
    animation: edSuccessPop .7s var(--ease-spring);
}
@keyframes edSuccessPop {
    0% { transform: scale(0) rotate(-180deg); }
    60% { transform: scale(1.15) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}
.ed-state h3 { font-size: 24px; color: var(--primary-dark); margin-bottom: 10px; letter-spacing: -.02em; }
.ed-state p { color: var(--text-muted); font-size: 15px; max-width: 440px; margin: 0 auto; line-height: 1.6; }

@media (max-width: 640px) {
    .ed-form-body { padding: 24px; }
    .ed-dosen { padding: 24px; }
    .ed-stars label { font-size: 32px; }
    .ed-progress-wrap { padding: 14px 20px; top: 118px; }
}
</style>

<!-- ===== HERO ===== -->
<section class="ed-hero">
    <div class="ed-hero-aurora"></div>
    <div class="ed-hero-grid"></div>
    <div class="container">
        <div class="ed-hero-inner">
            <span class="ed-hero-badge">
                <span class="ed-hero-badge-dot"></span>
                Evaluasi Dosen oleh Mahasiswa
            </span>
            <h1>Suara Anda, <span class="ed-grad">Peningkatan Mutu</span><br>Pembelajaran Kami</h1>
            <p class="ed-hero-lead">
                Penilaian Anda bersifat rahasia, objektif, dan berdampak langsung pada
                peningkatan kualitas pembelajaran di kampus.
            </p>
            <div class="ed-trust">
                <div class="ed-trust-item"><span>🔒</span> 100% Rahasia</div>
                <div class="ed-trust-item"><span>⚡</span> ±3 Menit</div>
                <div class="ed-trust-item"><span>🎯</span> Objektif</div>
                <div class="ed-trust-item"><span>📈</span> Berdampak</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== BODY ===== -->
<section class="ed-body">
    <div class="container">
        <div class="ed-card">
            <?php if (!$kelas): ?>
                <div class="ed-state">
                    <div class="ed-state-ic error">🔗</div>
                    <h3>Tautan Tidak Valid</h3>
                    <p>Pastikan Anda membuka tautan atau QR code resmi dari Kaprodi atau LPM. Tautan ini mungkin sudah kadaluarsa atau salah.</p>
                </div>
            <?php elseif ($success): ?>
                <div class="ed-state">
                    <div class="ed-state-ic success">🎉</div>
                    <h3>Terima Kasih atas Penilaian Anda!</h3>
                    <p>Masukan Anda telah tercatat dengan aman dan akan digunakan oleh dosen serta LPM untuk peningkatan mutu pembelajaran secara berkelanjutan.</p>
                    <a href="/publik/berita.php" class="btn btn-primary" style="margin-top:24px;padding:10px 22px;">Baca Berita Terbaru →</a>
                </div>
            <?php else: ?>
                <!-- Dosen Banner -->
                <div class="ed-dosen">
                    <div class="ed-dosen-ava"><?= strtoupper(mb_substr($kelas['nama_dosen'], 0, 1)) ?></div>
                    <div class="ed-dosen-info">
                        <small>Dosen Pengampu</small>
                        <h3><?= Security::e($kelas['nama_dosen']) ?></h3>
                        <p><?= Security::e($kelas['mata_kuliah']) ?> • <?= Security::e($kelas['nama_prodi'] ?? '—') ?></p>
                    </div>
                    <span class="ed-dosen-per">● Periode Terbuka</span>
                </div>

                <!-- Progress -->
                <div class="ed-progress-wrap">
                    <span class="ed-progress-label">Progres: <span id="edCount">0</span> / 10 aspek</span>
                    <div class="ed-progress-bar"><div class="ed-progress-fill" id="edFill"></div></div>
                    <span class="ed-progress-count" id="edPercent">0%</span>
                </div>

                <div class="ed-form-body">
                    <!-- Legend -->
                    <div class="ed-legend-wrap">
                        <div class="ed-legend-title">📏 Panduan Penilaian Bintang</div>
                        <div class="ed-legend">
                            <div class="ed-lg-item s1"><div class="ed-lg-stars">★</div><div class="ed-lg-label">Sangat Kurang</div></div>
                            <div class="ed-lg-item s2"><div class="ed-lg-stars">★★</div><div class="ed-lg-label">Kurang</div></div>
                            <div class="ed-lg-item s3"><div class="ed-lg-stars">★★★</div><div class="ed-lg-label">Cukup</div></div>
                            <div class="ed-lg-item s4"><div class="ed-lg-stars">★★★★</div><div class="ed-lg-label">Baik</div></div>
                            <div class="ed-lg-item s5"><div class="ed-lg-stars">★★★★★</div><div class="ed-lg-label">Sangat Baik</div></div>
                        </div>
                    </div>

                    <?php if ($error): ?><div class="ed-alert danger">⚠️ <?= Security::e($error) ?></div><?php endif; ?>

                    <form method="POST" id="edForm">
                        <?= Security::csrfField() ?>
                        <div class="ed-nim-field">
                            <span class="ed-nim-field-ic">🎓</span>
                            <input type="text" name="nim" id="edNim" placeholder=" " required>
                            <label for="edNim">NIM Anda *</label>
                        </div>

                        <?php foreach ($EDOM_Q as $i => $label): ?>
                        <div class="ed-q" data-q="<?= $i ?>">
                            <span class="ed-q-num"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></span>
                            <p class="ed-q-text"><?= Security::e($label) ?></p>
                            <div class="ed-stars">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                    <input type="radio" id="q<?= $i ?>_<?= $s ?>" name="q<?= $i ?>" value="<?= $s ?>" required>
                                    <label for="q<?= $i ?>_<?= $s ?>" title="<?= $s ?> bintang">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="ed-komentar">
                            <label class="ed-komentar-label">💬 Komentar & Saran (Opsional)</label>
                            <textarea name="komentar" placeholder="Tuliskan masukan membangun untuk dosen Anda..."></textarea>
                        </div>

                        <button type="submit" class="ed-submit" id="edSubmit">📨 Kirim Evaluasi Sekarang</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function () {
    var form = document.getElementById('edForm');
    if (!form) return;

    var fill = document.getElementById('edFill');
    var count = document.getElementById('edCount');
    var percent = document.getElementById('edPercent');
    var total = 10;

    function updateProgress() {
        var answered = 0;
        for (var i = 1; i <= total; i++) {
            var checked = form.querySelector('input[name="q' + i + '"]:checked');
            var q = form.querySelector('.ed-q[data-q="' + i + '"]');
            if (checked) { answered++; if (q) q.classList.add('answered'); }
            else { if (q) q.classList.remove('answered'); }
        }
        var p = Math.round((answered / total) * 100);
        if (fill) fill.style.width = p + '%';
        if (count) count.textContent = answered;
        if (percent) percent.textContent = p + '%';
    }

    form.addEventListener('change', updateProgress);
    updateProgress();

    form.addEventListener('submit', function () {
        var btn = document.getElementById('edSubmit');
        if (btn && !btn.dataset.busy) {
            btn.dataset.busy = '1';
            btn.innerHTML = '⏳ Mengirim...';
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>