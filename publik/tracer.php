<?php
$pageTitle = 'Tracer Study Alumni';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();
$db = Database::getInstance();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$success = false; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $nim = trim($_POST['nim'] ?? '');
    $chk = $db->prepare("SELECT id_alumni FROM tracer_alumni WHERE nim = ?");
    $chk->execute([$nim]);
    if (!$nim) { $error = 'NIM wajib diisi.'; }
    elseif ($chk->fetch()) { $error = 'NIM ini sudah pernah mengisi tracer study. Terima kasih! 🙏'; }
    else {
        $db->prepare("INSERT INTO tracer_alumni (nama, nim, id_prodi, tahun_lulus, email, no_wa, status_kerja, nama_instansi, jabatan, masa_tunggu_bulan, kesesuaian_bidang, kisaran_gaji, testimoni, siap_wawancara) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([
               trim($_POST['nama']), $nim, (int)($_POST['id_prodi'] ?? 0), trim($_POST['tahun_lulus']),
               trim($_POST['email']), trim($_POST['no_wa']), trim($_POST['status_kerja']),
               trim($_POST['nama_instansi']), trim($_POST['jabatan']), (int)($_POST['masa_tunggu_bulan'] ?? 0),
               trim($_POST['kesesuaian_bidang']), trim($_POST['kisaran_gaji']), trim($_POST['testimoni']),
               isset($_POST['siap_wawancara']) ? 1 : 0
           ]);
        if (isset($_POST['siap_wawancara'])) {
            $db->prepare("INSERT INTO responden_akreditasi (tipe, nama, identitas, id_prodi, no_wa, email, ketersediaan, catatan) VALUES ('Alumni',?,?,?,?,?,'Siap',?)")
               ->execute([trim($_POST['nama']), $nim, (int)$_POST['id_prodi'], trim($_POST['no_wa']), trim($_POST['email']), 'Otomatis dari Tracer Study ' . trim($_POST['tahun_lulus'])]);
        }
        Notifier::sendRole(1, '🎓 Tracer Study Masuk', trim($_POST['nama']) . ' (' . $nim . ') baru saja mengisi tracer study.', '/sim/admin/tracer.php', '🎓');
        $success = true;
    }
}
require_once __DIR__ . '/../includes/header-publik.php';
$tahunIni = (int)date('Y');
?>

<style>
/* ============================================================
   TRACER STUDY ULTIMATE v8.0 — Wizard Sections • Toggle • Conditional
============================================================ */

/* ===== HERO ===== */
.tr-hero {
    min-height: 60vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 120px 0 60px;
    display: flex; align-items: center;
}
.tr-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: trShift 16s ease-in-out infinite;
}
@keyframes trShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.tr-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.tr-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; }

.tr-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.tr-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: trShimmer 3.5s ease-in-out infinite;
}
@keyframes trShimmer { to { left: 200%; } }
.tr-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: trPulse 2s ease infinite;
}
@keyframes trPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.tr-hero h1 {
    font-size: clamp(34px, 5vw, 62px); font-weight: 800;
    line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em;
}
.tr-hero h1 .tr-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: trTextShine 4s linear infinite; font-style: italic;
}
@keyframes trTextShine { to { background-position: 200% center; } }
.tr-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

.tr-trust { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
.tr-trust-item {
    padding: 7px 14px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.tr-trust-item span { color: #10B981; }

/* ===== BODY ===== */
.tr-body { padding: 60px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }

/* Glass Card */
.tr-card {
    max-width: 880px; margin: 0 auto;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid var(--border); position: relative;
    box-shadow: 0 24px 70px rgba(15,61,92,.1);
}
.tr-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--primary), var(--accent));
    background-size: 200% 100%; animation: trGradLine 4s linear infinite;
}
@keyframes trGradLine { to { background-position: 200% 0; } }

/* Header banner */
.tr-head {
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 32px 36px;
    display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
    position: relative; overflow: hidden;
}
.tr-head::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.tr-head-ic {
    width: 68px; height: 68px; border-radius: 20px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); display: grid; place-items: center;
    font-size: 28px;
    box-shadow: 0 10px 30px rgba(201,162,39,.4);
    position: relative; z-index: 1;
}
.tr-head-info { flex: 1; min-width: 220px; position: relative; z-index: 1; }
.tr-head-info small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: block; margin-bottom: 4px; }
.tr-head-info h3 { font-size: 22px; font-weight: 800; margin-bottom: 4px; letter-spacing: -.02em; }
.tr-head-info p { color: rgba(255,255,255,.85); font-size: 14px; }
.tr-head-badge {
    padding: 6px 16px; border-radius: 50px;
    background: rgba(16,185,129,.18); border: 1px solid rgba(16,185,129,.35);
    color: #6EE7B7; font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase;
    position: relative; z-index: 1;
}

/* Form body */
.tr-form-body { padding: 36px; }

/* Section title dengan stepper */
.tr-sec-title {
    display: flex; align-items: center; gap: 14px;
    margin: 32px 0 20px; padding-bottom: 16px;
    border-bottom: 2px dashed var(--border);
}
.tr-sec-title:first-child { margin-top: 0; }
.tr-sec-ic {
    width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff; display: grid; place-items: center; font-size: 18px;
    box-shadow: 0 8px 20px rgba(15,61,92,.2);
}
.tr-sec-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); font-weight: 900; font-size: 13px;
    display: grid; place-items: center; margin-left: auto;
    box-shadow: 0 4px 12px rgba(201,162,39,.4);
}
.tr-sec-title h4 { font-size: 17px; color: var(--primary-dark); margin: 0; letter-spacing: -.01em; }
.tr-sec-title small { color: var(--text-muted); font-size: 12px; display: block; margin-top: 2px; }

/* Floating label fields */
.tr-field { position: relative; margin-bottom: 16px; }
.tr-field input, .tr-field select, .tr-field textarea {
    width: 100%; padding: 22px 18px 10px;
    border: 2px solid var(--border); border-radius: 14px;
    font-size: 14px; font-family: inherit; outline: none;
    background: #fff; color: var(--text-dark); transition: .3s;
}
.tr-field textarea { resize: vertical; min-height: 100px; padding-top: 26px; }
.tr-field select { cursor: pointer; appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath fill='%230F3D5C' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 16px center; padding-right: 40px;
}
.tr-field input:focus, .tr-field select:focus, .tr-field textarea:focus {
    border-color: var(--accent); box-shadow: 0 0 0 4px rgba(201,162,39,.15);
}
.tr-field label {
    position: absolute; left: 18px; top: 18px;
    font-size: 13px; color: var(--text-muted);
    pointer-events: none; transition: .25s var(--ease-out);
    background: #fff; padding: 0 6px; font-weight: 600;
}
.tr-field input:focus ~ label,
.tr-field input:not(:placeholder-shown) ~ label,
.tr-field select:focus ~ label,
.tr-field.has-value label,
.tr-field textarea:focus ~ label,
.tr-field textarea:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 10.5px; color: var(--accent);
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
}
.tr-field-ic {
    position: absolute; right: 16px; top: 20px; font-size: 16px;
    color: var(--text-muted); pointer-events: none;
}
.tr-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .tr-row { grid-template-columns: 1fr; } }

/* Conditional animation */
.tr-conditional {
    max-height: 1000px; overflow: hidden; opacity: 1;
    transition: max-height .5s var(--ease-out), opacity .3s ease;
}
.tr-conditional.hidden { max-height: 0; opacity: 0; pointer-events: none; }

/* Toggle Premium */
.tr-toggle {
    padding: 20px; border-radius: 16px; margin: 24px 0;
    background: linear-gradient(135deg, rgba(201,162,39,.06), rgba(15,61,92,.03));
    border: 1px solid rgba(201,162,39,.25);
    display: flex; align-items: center; gap: 16px;
    transition: .3s;
}
.tr-toggle.active {
    background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(16,185,129,.03));
    border-color: rgba(16,185,129,.4);
}
.tr-toggle-ic {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff; display: grid; place-items: center; font-size: 20px;
    box-shadow: 0 6px 18px rgba(15,61,92,.2);
    transition: .3s;
}
.tr-toggle.active .tr-toggle-ic {
    background: linear-gradient(135deg, #10B981, #34D399);
    box-shadow: 0 6px 18px rgba(16,185,129,.35);
}
.tr-toggle-text { flex: 1; }
.tr-toggle-text h5 { font-size: 14px; color: var(--primary-dark); margin: 0 0 2px; letter-spacing: -.01em; }
.tr-toggle-text p { font-size: 12.5px; color: var(--text-muted); margin: 0; line-height: 1.5; }

.tr-switch {
    position: relative; width: 54px; height: 30px; flex-shrink: 0;
}
.tr-switch input { opacity: 0; width: 0; height: 0; }
.tr-slider {
    position: absolute; cursor: pointer; inset: 0;
    background: #CBD5E1; border-radius: 30px;
    transition: .3s;
}
.tr-slider::before {
    content: ''; position: absolute;
    height: 22px; width: 22px; left: 4px; top: 4px;
    background: #fff; border-radius: 50%;
    transition: .3s var(--ease-spring);
    box-shadow: 0 2px 6px rgba(0,0,0,.2);
}
.tr-switch input:checked + .tr-slider {
    background: linear-gradient(135deg, #10B981, #34D399);
}
.tr-switch input:checked + .tr-slider::before {
    transform: translateX(24px);
}

/* Submit */
.tr-submit {
    margin-top: 28px; width: 100%; padding: 18px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); box-shadow: 0 12px 36px rgba(201,162,39,.45);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.tr-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.tr-submit:hover::before { left: 150%; }
.tr-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(201,162,39,.65); }

.tr-secure {
    display: flex; align-items: center; gap: 10px; margin-top: 16px;
    font-size: 12px; color: var(--text-muted);
}
.tr-secure-ic {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(16,185,129,.1); color: #10B981;
    display: grid; place-items: center; font-size: 12px;
}

/* Alert */
.tr-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600;
    display: flex; gap: 12px; align-items: center;
    animation: trAlertIn .45s var(--ease-out);
}
@keyframes trAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.tr-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid var(--danger); }

/* Success state */
.tr-state { text-align: center; padding: 70px 40px; position: relative; overflow: hidden; }
.tr-state-ic {
    width: 110px; height: 110px; border-radius: 50%; margin: 0 auto 24px;
    display: grid; place-items: center; font-size: 52px;
    background: linear-gradient(135deg, #10B981, #34D399); color: #fff;
    box-shadow: 0 20px 55px rgba(16,185,129,.4);
    animation: trSuccessPop .7s var(--ease-spring);
    position: relative; z-index: 1;
}
@keyframes trSuccessPop {
    0% { transform: scale(0) rotate(-180deg); }
    60% { transform: scale(1.15) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}
.tr-state h3 { font-size: 28px; color: var(--primary-dark); margin-bottom: 12px; letter-spacing: -.02em; position: relative; z-index: 1; }
.tr-state p { color: var(--text-muted); font-size: 16px; max-width: 480px; margin: 0 auto; line-height: 1.6; position: relative; z-index: 1; }

.tr-confetti {
    position: absolute; width: 10px; height: 10px; opacity: 0;
    animation: trConfettiFall 3s ease-out forwards;
}
@keyframes trConfettiFall {
    0% { transform: translateY(-100px) rotate(0); opacity: 1; }
    100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
}

@media (max-width: 640px) {
    .tr-form-body { padding: 24px; }
    .tr-head { padding: 24px; }
}
</style>

<!-- ===== HERO ===== -->
<section class="tr-hero">
    <div class="tr-hero-aurora"></div>
    <div class="tr-hero-grid"></div>
    <div class="container">
        <div class="tr-hero-inner">
            <span class="tr-hero-badge">
                <span class="tr-hero-badge-dot"></span>
                Tracer Study Alumni
            </span>
            <h1>Jejak Langkah <span class="tr-grad">Alumni Kami</span></h1>
            <p class="tr-hero-lead">
                Kabar karier Anda adalah bahan berharga untuk peningkatan mutu kurikulum & akreditasi.
                Hanya ±3 menit saja.
            </p>
            <div class="tr-trust">
                <div class="tr-trust-item"><span>⚡</span> ±3 Menit</div>
                <div class="tr-trust-item"><span>🔒</span> Rahasia</div>
                <div class="tr-trust-item"><span>🎓</span> Wajib Akreditasi</div>
                <div class="tr-trust-item"><span>💛</span> Untuk Almamater</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== BODY ===== -->
<section class="tr-body">
    <div class="container">
        <div class="tr-card">
            <?php if ($success): ?>
                <div class="tr-state" id="trSuccessState">
                    <div class="tr-state-ic">🎉</div>
                    <h3>Terima Kasih, Alumni Hebat!</h3>
                    <p>Data Anda tercatat dan sangat berarti bagi almamater. Sampai jumpa di kegiatan alumni berikutnya! Sukses selalu untuk karier Anda. 💛</p>
                    <a href="/publik/berita.php" class="btn btn-primary" style="margin-top:24px;padding:10px 22px;">Baca Berita Terbaru →</a>
                </div>
            <?php else: ?>
                <!-- Header Banner -->
                <div class="tr-head">
                    <div class="tr-head-ic">🎓</div>
                    <div class="tr-head-info">
                        <small>Formulir Tracer Study</small>
                        <h3>Jejak Karier Alumni</h3>
                        <p>🔒 Data dijaga kerahasiaannya — hanya untuk penjaminan mutu & akreditasi.</p>
                    </div>
                    <span class="tr-head-badge">● Aktif</span>
                </div>

                <div class="tr-form-body">
                    <?php if ($error): ?><div class="tr-alert danger">⚠️ <?= Security::e($error) ?></div><?php endif; ?>

                    <form method="POST" id="trForm">
                        <?= Security::csrfField() ?>

                        <!-- SECTION 1: DATA DIRI -->
                        <div class="tr-sec-title">
                            <div class="tr-sec-ic">👤</div>
                            <div><h4>Data Diri Alumni</h4><small>Identitas dasar Anda sebagai alumni</small></div>
                            <span class="tr-sec-num">1</span>
                        </div>

                        <div class="tr-row">
                            <div class="tr-field">
                                <input type="text" name="nama" placeholder=" " required>
                                <label>Nama Lengkap *</label>
                                <span class="tr-field-ic">👤</span>
                            </div>
                            <div class="tr-field">
                                <input type="text" name="nim" placeholder=" " required>
                                <label>NIM *</label>
                                <span class="tr-field-ic">🆔</span>
                            </div>
                        </div>

                        <div class="tr-row">
                            <div class="tr-field has-value">
                                <select name="id_prodi" required>
                                    <option value="">— Pilih Prodi —</option>
                                    <?php foreach ($prodiList as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option><?php endforeach; ?>
                                </select>
                                <label>Program Studi *</label>
                            </div>
                            <div class="tr-field has-value">
                                <select name="tahun_lulus" required>
                                    <?php for ($y = $tahunIni; $y >= $tahunIni - 10; $y--): ?><option><?= $y ?></option><?php endfor; ?>
                                </select>
                                <label>Tahun Lulus *</label>
                            </div>
                        </div>

                        <div class="tr-row">
                            <div class="tr-field">
                                <input type="email" name="email" placeholder=" ">
                                <label>Email</label>
                                <span class="tr-field-ic">✉️</span>
                            </div>
                            <div class="tr-field">
                                <input type="text" name="no_wa" placeholder=" " required>
                                <label>No. WhatsApp *</label>
                                <span class="tr-field-ic">💬</span>
                            </div>
                        </div>

                        <!-- SECTION 2: STATUS KARIR -->
                        <div class="tr-sec-title">
                            <div class="tr-sec-ic">💼</div>
                            <div><h4>Status Karier Saat Ini</h4><small>Kondisi pekerjaan Anda setelah lulus</small></div>
                            <span class="tr-sec-num">2</span>
                        </div>

                        <div class="tr-field has-value">
                            <select name="status_kerja" id="trStatus" required>
                                <option>Bekerja</option>
                                <option>Wirausaha</option>
                                <option>Melanjutkan Studi</option>
                                <option>Belum Bekerja</option>
                            </select>
                            <label>Status Saat Ini *</label>
                        </div>

                        <!-- Conditional: Detail pekerjaan (disembunyikan jika Belum Bekerja) -->
                        <div class="tr-conditional" id="trWorkDetails">
                            <div class="tr-row">
                                <div class="tr-field">
                                    <input type="text" name="nama_instansi" placeholder=" ">
                                    <label>Nama Instansi / Usaha</label>
                                    <span class="tr-field-ic">🏢</span>
                                </div>
                                <div class="tr-field">
                                    <input type="text" name="jabatan" placeholder=" ">
                                    <label>Jabatan / Posisi</label>
                                    <span class="tr-field-ic">💼</span>
                                </div>
                            </div>

                            <div class="tr-row">
                                <div class="tr-field">
                                    <input type="number" name="masa_tunggu_bulan" value="0" min="0" placeholder=" ">
                                    <label>Masa Tunggu Kerja (bulan)</label>
                                    <span class="tr-field-ic">⏱️</span>
                                </div>
                                <div class="tr-field has-value">
                                    <select name="kesesuaian_bidang">
                                        <option>Sangat Sesuai</option>
                                        <option>Sesuai</option>
                                        <option>Kurang Sesuai</option>
                                        <option>Tidak Sesuai</option>
                                    </select>
                                    <label>Kesesuaian dengan Bidang Studi</label>
                                </div>
                            </div>

                            <div class="tr-field has-value">
                                <select name="kisaran_gaji">
                                    <option>&lt; 3 juta</option>
                                    <option>3–5 juta</option>
                                    <option>5–8 juta</option>
                                    <option>&gt; 8 juta</option>
                                </select>
                                <label>Kisaran Gaji</label>
                            </div>
                        </div>

                        <!-- SECTION 3: TESTIMONI -->
                        <div class="tr-sec-title">
                            <div class="tr-sec-ic">💬</div>
                            <div><h4>Testimoni untuk Kampus</h4><small>Cerita & saran untuk almamater</small></div>
                            <span class="tr-sec-num">3</span>
                        </div>

                        <div class="tr-field">
                            <textarea name="testimoni" placeholder=" "></textarea>
                            <label>Ceritakan pengalaman & saran Anda untuk kampus...</label>
                        </div>

                        <!-- SECTION 4: KESEDIAAN WAWANCARA -->
                        <div class="tr-sec-title">
                            <div class="tr-sec-ic">🎤</div>
                            <div><h4>Kesediaan Wawancara</h4><small>Opsional — dukungan untuk akreditasi</small></div>
                            <span class="tr-sec-num">4</span>
                        </div>

                        <label class="tr-toggle" id="trToggleWrap">
                            <div class="tr-toggle-ic">🙋</div>
                            <div class="tr-toggle-text">
                                <h5>Bersedia Diwawancara Asesor Akreditasi</h5>
                                <p>Jika dicentang, data Anda akan otomatis didaftarkan sebagai calon responden wawancara akreditasi.</p>
                            </div>
                            <div class="tr-switch">
                                <input type="checkbox" name="siap_wawancara" value="1" id="trWawancara">
                                <span class="tr-slider"></span>
                            </div>
                        </label>

                        <button type="submit" class="tr-submit" id="trSubmit">📨 Kirim Tracer Study Sekarang</button>

                        <div class="tr-secure">
                            <span class="tr-secure-ic">🔒</span>
                            <span>Data Anda terenkripsi dan hanya digunakan untuk penjaminan mutu serta akreditasi kampus.</span>
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
    var successState = document.getElementById('trSuccessState');
    if (successState) {
        var colors = ['#C9A227', '#E8C55A', '#0F3D5C', '#10B981', '#F59E0B'];
        for (var i = 0; i < 50; i++) {
            var conf = document.createElement('div');
            conf.className = 'tr-confetti';
            conf.style.left = Math.random() * 100 + '%';
            conf.style.top = '-20px';
            conf.style.background = colors[Math.floor(Math.random() * colors.length)];
            conf.style.borderRadius = Math.random() > .5 ? '50%' : '2px';
            conf.style.animationDelay = (Math.random() * 1.5) + 's';
            conf.style.animationDuration = (2 + Math.random() * 2) + 's';
            successState.appendChild(conf);
        }
    }

    /* Conditional: hide work details if "Belum Bekerja" */
    var status = document.getElementById('trStatus');
    var workDetails = document.getElementById('trWorkDetails');
    if (status && workDetails) {
        function toggleWork() {
            if (status.value === 'Belum Bekerja') {
                workDetails.classList.add('hidden');
            } else {
                workDetails.classList.remove('hidden');
            }
        }
        status.addEventListener('change', toggleWork);
        toggleWork();
    }

    /* Toggle visual feedback */
    var toggle = document.getElementById('trWawancara');
    var toggleWrap = document.getElementById('trToggleWrap');
    if (toggle && toggleWrap) {
        toggle.addEventListener('change', function () {
            if (toggle.checked) {
                toggleWrap.classList.add('active');
            } else {
                toggleWrap.classList.remove('active');
            }
        });
    }

    /* Submit feedback */
    var form = document.getElementById('trForm');
    if (form) {
        form.addEventListener('submit', function () {
            var btn = document.getElementById('trSubmit');
            if (btn && !btn.dataset.busy) {
                btn.dataset.busy = '1';
                btn.innerHTML = '⏳ Mengirim...';
            }
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>