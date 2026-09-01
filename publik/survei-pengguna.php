<?php
$pageTitle = 'Survei Pengguna Lulusan';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();
$db = Database::getInstance();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$success = false; $error = '';

$ASPEK = [
    'etika' => 'Etika & integritas lulusan',
    'keahlian' => 'Keahlian sesuai bidang kerja',
    'bahasa' => 'Kemampuan bahasa asing',
    'teknologi' => 'Penguasaan teknologi / informatika',
    'komunikasi' => 'Kemampuan komunikasi',
    'kerjasama' => 'Kerja sama dalam tim',
    'pengembangan' => 'Potensi pengembangan diri',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $ok = true; $skor = [];
    foreach ($ASPEK as $k => $l) {
        $v = (int)($_POST['aspek_' . $k] ?? 0);
        if ($v < 1 || $v > 5) $ok = false;
        $skor[$k] = $v;
    }
    if (!$ok) { $error = 'Mohon beri penilaian bintang (1–5) pada semua aspek.'; }
    else {
        $db->prepare("INSERT INTO survei_pengguna (nama_instansi, nama_responden, jabatan, kontak, nama_alumni, id_prodi, tahun_lulus, aspek_etika, aspek_keahlian, aspek_bahasa, aspek_teknologi, aspek_komunikasi, aspek_kerjasama, aspek_pengembangan, komentar) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([
               trim($_POST['nama_instansi']), trim($_POST['nama_responden']), trim($_POST['jabatan']), trim($_POST['kontak']),
               trim($_POST['nama_alumni']), (int)($_POST['id_prodi'] ?? 0), trim($_POST['tahun_lulus']),
               $skor['etika'], $skor['keahlian'], $skor['bahasa'], $skor['teknologi'], $skor['komunikasi'], $skor['kerjasama'], $skor['pengembangan'],
               trim($_POST['komentar'])
           ]);
        Notifier::sendRole(1, '💼 Survei Pengguna Masuk', (trim($_POST['nama_instansi']) . ' menilai lulusan kami.'), '/sim/admin/survei-pengguna.php', '💼');
        $success = true;
    }
}
require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   SURVEI PENGGUNA ULTIMATE v8.0 — Glass • Progress • Aspect Cards
============================================================ */

/* ===== HERO ===== */
.sp-hero {
    min-height: 60vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 120px 0 60px;
    display: flex; align-items: center;
}
.sp-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: spShift 16s ease-in-out infinite;
}
@keyframes spShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.sp-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.sp-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; }

.sp-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.sp-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: spShimmer 3.5s ease-in-out infinite;
}
@keyframes spShimmer { to { left: 200%; } }
.sp-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: spPulse 2s ease infinite;
}
@keyframes spPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.sp-hero h1 {
    font-size: clamp(34px, 5vw, 62px); font-weight: 800;
    line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em;
}
.sp-hero h1 .sp-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: spTextShine 4s linear infinite; font-style: italic;
}
@keyframes spTextShine { to { background-position: 200% center; } }
.sp-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

.sp-trust { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
.sp-trust-item {
    padding: 7px 14px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.sp-trust-item span { color: #10B981; }

/* ===== BODY ===== */
.sp-body { padding: 60px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }

/* Glass Card */
.sp-card {
    max-width: 860px; margin: 0 auto;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid var(--border); position: relative;
    box-shadow: 0 24px 70px rgba(15,61,92,.1);
}
.sp-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--primary), var(--accent));
    background-size: 200% 100%; animation: spGradLine 4s linear infinite;
}
@keyframes spGradLine { to { background-position: 200% 0; } }

/* Header banner */
.sp-head {
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 32px 36px;
    display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
    position: relative; overflow: hidden;
}
.sp-head::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.sp-head-ic {
    width: 68px; height: 68px; border-radius: 20px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); display: grid; place-items: center;
    font-size: 28px;
    box-shadow: 0 10px 30px rgba(201,162,39,.4);
    position: relative; z-index: 1;
}
.sp-head-info { flex: 1; min-width: 220px; position: relative; z-index: 1; }
.sp-head-info small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: block; margin-bottom: 4px; }
.sp-head-info h3 { font-size: 22px; font-weight: 800; margin-bottom: 4px; letter-spacing: -.02em; }
.sp-head-info p { color: rgba(255,255,255,.85); font-size: 14px; }
.sp-head-badge {
    padding: 6px 16px; border-radius: 50px;
    background: rgba(16,185,129,.18); border: 1px solid rgba(16,185,129,.35);
    color: #6EE7B7; font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase;
    position: relative; z-index: 1;
}

/* Progress */
.sp-progress-wrap {
    padding: 18px 36px; background: rgba(247,249,252,.95); backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center; gap: 14px;
    position: sticky; top: 118px; z-index: 10;
}
body.hd-scrolled .sp-progress-wrap { top: 78px; }
.sp-progress-label { font-size: 13px; font-weight: 700; color: var(--primary-dark); }
.sp-progress-label span { color: var(--accent); }
.sp-progress-bar {
    flex: 1; max-width: 420px; height: 8px; background: var(--border);
    border-radius: 8px; overflow: hidden;
}
.sp-progress-fill {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, var(--accent), var(--accent-light));
    border-radius: 8px; transition: width .4s var(--ease-out);
    box-shadow: 0 0 14px rgba(201,162,39,.4);
}
.sp-progress-count {
    padding: 4px 12px; border-radius: 50px;
    background: rgba(201,162,39,.12); color: var(--accent);
    font-size: 12px; font-weight: 800; letter-spacing: .5px;
}

/* Form body */
.sp-form-body { padding: 36px; }

/* Section divider */
.sp-sec-title {
    display: flex; align-items: center; gap: 12px;
    margin: 28px 0 18px; padding-bottom: 14px;
    border-bottom: 2px dashed var(--border);
}
.sp-sec-title:first-child { margin-top: 0; }
.sp-sec-ic {
    width: 40px; height: 40px; border-radius: 12px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff; display: grid; place-items: center; font-size: 18px;
    box-shadow: 0 6px 18px rgba(15,61,92,.2);
}
.sp-sec-title h4 { font-size: 16px; color: var(--primary-dark); margin: 0; letter-spacing: -.01em; }
.sp-sec-title small { color: var(--text-muted); font-size: 12px; display: block; margin-top: 2px; }

/* Floating label fields */
.sp-field { position: relative; margin-bottom: 16px; }
.sp-field input, .sp-field select, .sp-field textarea {
    width: 100%; padding: 22px 18px 10px;
    border: 2px solid var(--border); border-radius: 14px;
    font-size: 14px; font-family: inherit; outline: none;
    background: #fff; color: var(--text-dark); transition: .3s;
}
.sp-field textarea { resize: vertical; min-height: 100px; padding-top: 26px; }
.sp-field select { cursor: pointer; appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath fill='%230F3D5C' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 16px center; padding-right: 40px;
}
.sp-field input:focus, .sp-field select:focus, .sp-field textarea:focus {
    border-color: var(--accent); box-shadow: 0 0 0 4px rgba(201,162,39,.15);
}
.sp-field label {
    position: absolute; left: 18px; top: 18px;
    font-size: 13px; color: var(--text-muted);
    pointer-events: none; transition: .25s var(--ease-out);
    background: #fff; padding: 0 6px; font-weight: 600;
}
.sp-field input:focus ~ label,
.sp-field input:not(:placeholder-shown) ~ label,
.sp-field select:focus ~ label,
.sp-field.has-value label,
.sp-field textarea:focus ~ label,
.sp-field textarea:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 10.5px; color: var(--accent);
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
}
.sp-field-ic {
    position: absolute; right: 16px; top: 20px; font-size: 16px;
    color: var(--text-muted); pointer-events: none;
}
.sp-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .sp-row { grid-template-columns: 1fr; } }

/* Legend */
.sp-legend {
    padding: 18px; border-radius: 16px;
    background: linear-gradient(135deg, rgba(201,162,39,.06), rgba(15,61,92,.03));
    border: 1px solid rgba(201,162,39,.2); margin-bottom: 22px;
}
.sp-legend-title { font-size: 12px; font-weight: 800; color: var(--primary-dark); margin-bottom: 10px; letter-spacing: .5px; }
.sp-legend-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
.sp-lg-item {
    padding: 10px 6px; border-radius: 10px; background: #fff;
    border: 1px solid var(--border); text-align: center;
}
.sp-lg-stars { font-size: 14px; color: var(--accent); margin-bottom: 3px; }
.sp-lg-stars.dim { color: #D8DEE8; }
.sp-lg-label { font-size: 10px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; }
.sp-lg-item.s1 .sp-lg-label { color: #EF4444; }
.sp-lg-item.s2 .sp-lg-label { color: #F59E0B; }
.sp-lg-item.s3 .sp-lg-label { color: #64748B; }
.sp-lg-item.s4 .sp-lg-label { color: #10B981; }
.sp-lg-item.s5 .sp-lg-label { color: var(--accent); }

/* Aspect card */
.sp-aspect {
    padding: 20px; border-radius: 16px; margin-bottom: 14px;
    background: #fff; border: 1px solid var(--border);
    transition: .3s var(--ease-out);
}
.sp-aspect.answered {
    background: linear-gradient(135deg, rgba(16,185,129,.04), rgba(201,162,39,.03));
    border-color: rgba(16,185,129,.3);
}
.sp-aspect:hover { border-color: rgba(201,162,39,.4); box-shadow: 0 6px 20px rgba(15,61,92,.06); }
.sp-aspect-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
.sp-aspect-num {
    display: inline-block; padding: 3px 10px; border-radius: 8px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff; font-size: 11px; font-weight: 800; letter-spacing: 1px;
    min-width: 32px; text-align: center;
}
.sp-aspect.answered .sp-aspect-num { background: linear-gradient(135deg, #10B981, #34D399); }
.sp-aspect-title { font-weight: 600; font-size: 15px; color: var(--primary-dark); line-height: 1.4; flex: 1; }
.sp-aspect-score {
    padding: 4px 10px; border-radius: 50px;
    background: var(--bg-light); color: var(--text-muted);
    font-size: 11px; font-weight: 800; letter-spacing: .5px;
    transition: .3s;
}
.sp-aspect.answered .sp-aspect-score {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark);
}

/* Stars besar */
.sp-stars {
    display: flex; flex-direction: row-reverse; justify-content: flex-end;
    gap: 8px;
}
.sp-stars input { display: none; }
.sp-stars label {
    font-size: 34px; color: #D8DEE8; cursor: pointer;
    transition: .2s var(--ease-spring); line-height: 1;
}
.sp-stars label:hover,
.sp-stars label:hover ~ label,
.sp-stars input:checked ~ label {
    color: var(--accent);
    text-shadow: 0 0 20px rgba(201,162,39,.6);
}
.sp-stars label:hover { transform: scale(1.25) rotate(-5deg); }
.sp-stars input:checked ~ label { animation: spStarPop .5s var(--ease-spring); }
@keyframes spStarPop { 0% { transform: scale(1); } 50% { transform: scale(1.3); } 100% { transform: scale(1); } }

/* Submit */
.sp-submit {
    margin-top: 28px; width: 100%; padding: 18px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); box-shadow: 0 12px 36px rgba(201,162,39,.45);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.sp-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.sp-submit:hover::before { left: 150%; }
.sp-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(201,162,39,.65); }

.sp-secure {
    display: flex; align-items: center; gap: 10px; margin-top: 16px;
    font-size: 12px; color: var(--text-muted);
}
.sp-secure-ic {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(16,185,129,.1); color: #10B981;
    display: grid; place-items: center; font-size: 12px;
}

/* Alert */
.sp-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600;
    display: flex; gap: 12px; align-items: center;
    animation: spAlertIn .45s var(--ease-out);
}
@keyframes spAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.sp-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid var(--danger); }

/* Success */
.sp-state { text-align: center; padding: 70px 40px; position: relative; overflow: hidden; }
.sp-state-ic {
    width: 110px; height: 110px; border-radius: 50%; margin: 0 auto 24px;
    display: grid; place-items: center; font-size: 52px;
    background: linear-gradient(135deg, #10B981, #34D399); color: #fff;
    box-shadow: 0 20px 55px rgba(16,185,129,.4);
    animation: spSuccessPop .7s var(--ease-spring);
    position: relative; z-index: 1;
}
@keyframes spSuccessPop {
    0% { transform: scale(0) rotate(-180deg); }
    60% { transform: scale(1.15) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}
.sp-state h3 { font-size: 28px; color: var(--primary-dark); margin-bottom: 12px; letter-spacing: -.02em; position: relative; z-index: 1; }
.sp-state p { color: var(--text-muted); font-size: 16px; max-width: 480px; margin: 0 auto; line-height: 1.6; position: relative; z-index: 1; }

.sp-confetti {
    position: absolute; width: 10px; height: 10px; opacity: 0;
    animation: spConfettiFall 3s ease-out forwards;
}
@keyframes spConfettiFall {
    0% { transform: translateY(-100px) rotate(0); opacity: 1; }
    100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
}

@media (max-width: 640px) {
    .sp-form-body { padding: 24px; }
    .sp-head { padding: 24px; }
    .sp-stars label { font-size: 28px; }
    .sp-legend-grid { grid-template-columns: repeat(5, 1fr); gap: 3px; }
    .sp-lg-label { font-size: 8.5px; }
    .sp-progress-wrap { padding: 14px 20px; top: 118px; }
}
</style>

<!-- ===== HERO ===== -->
<section class="sp-hero">
    <div class="sp-hero-aurora"></div>
    <div class="sp-hero-grid"></div>
    <div class="container">
        <div class="sp-hero-inner">
            <span class="sp-hero-badge">
                <span class="sp-hero-badge-dot"></span>
                Survei Pengguna Lulusan
            </span>
            <h1>Penilaian <span class="sp-grad">Pengguna Lulusan</span></h1>
            <p class="sp-hero-lead">
                Bagi instansi/perusahaan yang mempekerjakan lulusan kami —
                masukan Anda membentuk kurikulum berikutnya yang lebih relevan.
            </p>
            <div class="sp-trust">
                <div class="sp-trust-item"><span>⚡</span> ±2 Menit</div>
                <div class="sp-trust-item"><span>🎯</span> 7 Aspek</div>
                <div class="sp-trust-item"><span>📊</span> Berdampak</div>
                <div class="sp-trust-item"><span>🔒</span> Rahasia</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== BODY ===== -->
<section class="sp-body">
    <div class="container">
        <div class="sp-card">
            <?php if ($success): ?>
                <div class="sp-state" id="spSuccessState">
                    <div class="sp-state-ic">🤝</div>
                    <h3>Terima Kasih atas Penilaian Anda!</h3>
                    <p>Masukan Anda menjadi bahan berharga untuk peningkatan mutu kurikulum dan layanan kami. Kami berkomitmen menghasilkan lulusan yang siap kerja dan relevan dengan kebutuhan industri.</p>
                    <a href="/publik/berita.php" class="btn btn-primary" style="margin-top:24px;padding:10px 22px;">Baca Berita Terbaru →</a>
                </div>
            <?php else: ?>
                <!-- Header Banner -->
                <div class="sp-head">
                    <div class="sp-head-ic">💼</div>
                    <div class="sp-head-info">
                        <small>Formulir Survei</small>
                        <h3>Penilaian Pengguna Lulusan</h3>
                        <p>Isi data instansi & beri penilaian bintang (1–5) untuk 7 aspek kompetensi lulusan.</p>
                    </div>
                    <span class="sp-head-badge">● Aktif</span>
                </div>

                <!-- Progress -->
                <div class="sp-progress-wrap">
                    <span class="sp-progress-label">Aspek dinilai: <span id="spCount">0</span> / 7</span>
                    <div class="sp-progress-bar"><div class="sp-progress-fill" id="spFill"></div></div>
                    <span class="sp-progress-count" id="spPercent">0%</span>
                </div>

                <div class="sp-form-body">
                    <?php if ($error): ?><div class="sp-alert danger">⚠️ <?= Security::e($error) ?></div><?php endif; ?>

                    <form method="POST" id="spForm">
                        <?= Security::csrfField() ?>

                        <!-- Section 1: Data Instansi -->
                        <div class="sp-sec-title">
                            <div class="sp-sec-ic">🏢</div>
                            <div><h4>Data Instansi & Responden</h4><small>Informasi perusahaan dan Anda sebagai penilai</small></div>
                        </div>

                        <div class="sp-row">
                            <div class="sp-field">
                                <input type="text" name="nama_instansi" placeholder=" " required>
                                <label>Nama Instansi / Perusahaan *</label>
                                <span class="sp-field-ic">🏢</span>
                            </div>
                            <div class="sp-field">
                                <input type="text" name="nama_responden" placeholder=" ">
                                <label>Nama Anda (HRD/Atasan)</label>
                                <span class="sp-field-ic">👤</span>
                            </div>
                        </div>

                        <div class="sp-row">
                            <div class="sp-field">
                                <input type="text" name="jabatan" placeholder=" ">
                                <label>Jabatan Anda</label>
                                <span class="sp-field-ic">💼</span>
                            </div>
                            <div class="sp-field">
                                <input type="text" name="kontak" placeholder=" ">
                                <label>Email / Telepon</label>
                                <span class="sp-field-ic">📞</span>
                            </div>
                        </div>

                        <!-- Section 2: Data Lulusan -->
                        <div class="sp-sec-title">
                            <div class="sp-sec-ic">🎓</div>
                            <div><h4>Data Lulusan yang Dinilai</h4><small>Identitas alumni yang Anda pekerjakan</small></div>
                        </div>

                        <div class="sp-row">
                            <div class="sp-field">
                                <input type="text" name="nama_alumni" placeholder=" " required>
                                <label>Nama Lulusan *</label>
                                <span class="sp-field-ic">👤</span>
                            </div>
                            <div class="sp-field has-value">
                                <select name="id_prodi">
                                    <option value="0">— Pilih Prodi —</option>
                                    <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
                                </select>
                                <label>Prodi Lulusan</label>
                            </div>
                        </div>

                        <div class="sp-field">
                            <input type="text" name="tahun_lulus" placeholder="cth: 2023">
                            <label>Tahun Lulus</label>
                            <span class="sp-field-ic">📅</span>
                        </div>

                        <!-- Section 3: Penilaian -->
                        <div class="sp-sec-title">
                            <div class="sp-sec-ic">⭐</div>
                            <div><h4>Penilaian Kinerja Lulusan</h4><small>Beri bintang 1–5 untuk 7 aspek kompetensi</small></div>
                        </div>

                        <!-- Legend -->
                        <div class="sp-legend">
                            <div class="sp-legend-title">📏 Panduan Penilaian</div>
                            <div class="sp-legend-grid">
                                <div class="sp-lg-item s1"><div class="sp-lg-stars">★</div><div class="sp-lg-label">Sangat Kurang</div></div>
                                <div class="sp-lg-item s2"><div class="sp-lg-stars">★★</div><div class="sp-lg-label">Kurang</div></div>
                                <div class="sp-lg-item s3"><div class="sp-lg-stars">★★★</div><div class="sp-lg-label">Cukup</div></div>
                                <div class="sp-lg-item s4"><div class="sp-lg-stars">★★★★</div><div class="sp-lg-label">Baik</div></div>
                                <div class="sp-lg-item s5"><div class="sp-lg-stars">★★★★★</div><div class="sp-lg-label">Sangat Baik</div></div>
                            </div>
                        </div>

                        <?php $i = 1; foreach ($ASPEK as $k => $label): ?>
                        <div class="sp-aspect" data-aspect="<?= $k ?>">
                            <div class="sp-aspect-head">
                                <span class="sp-aspect-num"><?= str_pad($i++, 2, '0', STR_PAD_LEFT) ?></span>
                                <span class="sp-aspect-title"><?= Security::e($label) ?></span>
                                <span class="sp-aspect-score" id="score_<?= $k ?>">—</span>
                            </div>
                            <div class="sp-stars">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                    <input type="radio" id="a<?= $k ?><?= $s ?>" name="aspek_<?= $k ?>" value="<?= $s ?>" data-aspect="<?= $k ?>" required>
                                    <label for="a<?= $k ?><?= $s ?>" title="<?= $s ?> bintang">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Section 4: Komentar -->
                        <div class="sp-sec-title">
                            <div class="sp-sec-ic">💬</div>
                            <div><h4>Komentar & Saran</h4><small>Masukan membangun untuk almamater</small></div>
                        </div>

                        <div class="sp-field">
                            <textarea name="komentar" placeholder=" "></textarea>
                            <label>Tuliskan saran untuk peningkatan mutu lulusan...</label>
                        </div>

                        <button type="submit" class="sp-submit" id="spSubmit">📨 Kirim Penilaian Sekarang</button>

                        <div class="sp-secure">
                            <span class="sp-secure-ic">🔒</span>
                            <span>Data Anda aman dan hanya digunakan untuk penjaminan mutu internal kampus.</span>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function () {
    /* Success confetti */
    var successState = document.getElementById('spSuccessState');
    if (successState) {
        var colors = ['#C9A227', '#E8C55A', '#0F3D5C', '#10B981', '#F59E0B'];
        for (var i = 0; i < 40; i++) {
            var conf = document.createElement('div');
            conf.className = 'sp-confetti';
            conf.style.left = Math.random() * 100 + '%';
            conf.style.top = '-20px';
            conf.style.background = colors[Math.floor(Math.random() * colors.length)];
            conf.style.borderRadius = Math.random() > .5 ? '50%' : '2px';
            conf.style.animationDelay = (Math.random() * 1.5) + 's';
            conf.style.animationDuration = (2 + Math.random() * 2) + 's';
            successState.appendChild(conf);
        }
    }

    /* Progress + score display */
    var form = document.getElementById('spForm');
    if (!form) return;

    var fill = document.getElementById('spFill');
    var count = document.getElementById('spCount');
    var percent = document.getElementById('spPercent');
    var total = 7;
    var aspekKeys = ['etika','keahlian','bahasa','teknologi','komunikasi','kerjasama','pengembangan'];

    function updateProgress() {
        var answered = 0;
        aspekKeys.forEach(function (k) {
            var checked = form.querySelector('input[name="aspek_' + k + '"]:checked');
            var card = form.querySelector('.sp-aspect[data-aspect="' + k + '"]');
            var scoreEl = document.getElementById('score_' + k);
            if (checked) {
                answered++;
                if (card) card.classList.add('answered');
                if (scoreEl) scoreEl.textContent = '★ ' + checked.value;
            } else {
                if (card) card.classList.remove('answered');
                if (scoreEl) scoreEl.textContent = '—';
            }
        });
        var p = Math.round((answered / total) * 100);
        if (fill) fill.style.width = p + '%';
        if (count) count.textContent = answered;
        if (percent) percent.textContent = p + '%';
    }

    form.addEventListener('change', updateProgress);
    updateProgress();

    form.addEventListener('submit', function () {
        var btn = document.getElementById('spSubmit');
        if (btn && !btn.dataset.busy) {
            btn.dataset.busy = '1';
            btn.innerHTML = '⏳ Mengirim...';
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>