<?php
$pageTitle = 'Pengaduan & Kritik';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $_SESSION['_pengaduan_time'] ??= 0;
    if (time() - $_SESSION['_pengaduan_time'] < 60) {
        $error = 'Mohon tunggu 1 menit sebelum mengirim pengaduan berikutnya.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $subjek = trim($_POST['subjek'] ?? '');
        $isi = trim($_POST['isi'] ?? '');
        if (!$nama || !$email || !$subjek || !$isi) {
            $error = 'Mohon lengkapi semua kolom dengan benar.';
        } else {
            $stmt = Database::getInstance()->prepare(
                "INSERT INTO pengaduan (nama, email, instansi, subjek, isi_pesan, ip_address)
                 VALUES (:nama, :email, :instansi, :subjek, :isi, :ip)"
            );
            $stmt->execute([
                ':nama' => $nama, ':email' => $email,
                ':instansi' => trim($_POST['instansi'] ?? ''),
                ':subjek' => $subjek, ':isi' => $isi,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            Notifier::sendRole(1, '📩 Pengaduan Publik Baru', $nama . ' — ' . $subjek, '/sim/admin/pengaduan.php', '📩');
            $success = 'Pengaduan Anda telah kami terima. Tim LPM akan menindaklanjuti segera.';
            $_SESSION['_pengaduan_time'] = time();
        }
    }
}

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   PENGADUAN ULTIMATE v8.0 — Glass Form • Floating Labels • Steps
============================================================ */

/* ===== HERO ===== */
.pg-hero {
    min-height: 78vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 130px 0 90px;
    display: flex; align-items: center;
}
.pg-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 15% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 85% 20%, rgba(26,90,130,.45), transparent 55%),
        radial-gradient(ellipse 60% 50% at 70% 80%, rgba(232,197,90,.18), transparent 55%),
        linear-gradient(160deg, #061D2E 0%, #0F3D5C 55%, #092A40 100%);
    animation: pgShift 16s ease-in-out infinite;
}
@keyframes pgShift {
    0%, 100% { filter: hue-rotate(0deg); transform: scale(1); }
    50% { filter: hue-rotate(12deg); transform: scale(1.04); }
}
.pg-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.pg-hero-inner { position: relative; z-index: 3; max-width: 900px; margin: 0 auto; text-align: center; }

.pg-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 28px; position: relative; overflow: hidden;
}
.pg-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: pgShimmer 3.5s ease-in-out infinite;
}
@keyframes pgShimmer { to { left: 200%; } }
.pg-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: pgPulse 2s ease infinite;
}
@keyframes pgPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.pg-hero h1 {
    font-size: clamp(40px, 6vw, 78px); font-weight: 800;
    line-height: 1.04; margin-bottom: 22px; letter-spacing: -.04em;
}
.pg-hero h1 .pg-grad {
    background: linear-gradient(120deg, #E8C55A 0%, #C9A227 25%, #F7E491 50%, #C9A227 75%, #E8C55A 100%);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: pgTextShine 4s linear infinite; font-style: italic;
}
@keyframes pgTextShine { to { background-position: 200% center; } }
.pg-hero-lead {
    font-size: clamp(16px, 1.5vw, 18px); color: rgba(255,255,255,.75);
    max-width: 660px; margin: 0 auto 44px; line-height: 1.65;
}

/* Floating icons */
.pg-float-ic {
    position: absolute; font-size: 36px; opacity: .15;
    animation: pgFloat 8s ease-in-out infinite;
    pointer-events: none; z-index: 1;
}
.pg-fi1 { top: 18%; left: 8%; animation-delay: 0s; }
.pg-fi2 { top: 28%; right: 10%; animation-delay: -2s; font-size: 42px; }
.pg-fi3 { bottom: 22%; left: 12%; animation-delay: -4s; font-size: 32px; }
.pg-fi4 { bottom: 18%; right: 8%; animation-delay: -6s; font-size: 40px; }
@keyframes pgFloat {
    0%, 100% { transform: translateY(0) rotate(0); }
    50% { transform: translateY(-16px) rotate(-6deg); }
}
@media (max-width: 992px) { .pg-float-ic { display: none; } }

/* Trust badges */
.pg-trust {
    display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 32px;
}
.pg-trust-item {
    padding: 8px 16px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.pg-trust-item span { color: #10B981; font-size: 14px; }

/* ===== BODY ===== */
.pg-body { padding: 80px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }
.pg-grid {
    display: grid; grid-template-columns: 1.4fr 1fr; gap: 40px; align-items: start;
}
@media (max-width: 992px) { .pg-grid { grid-template-columns: 1fr; } }

/* Glass Form */
.pg-form-wrap {
    background: #fff; border-radius: 28px; padding: 44px;
    border: 1px solid var(--border); position: relative; overflow: hidden;
    box-shadow: 0 20px 60px rgba(15,61,92,.08);
}
.pg-form-wrap::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--primary), var(--accent));
    background-size: 200% 100%; animation: pgGradLine 4s linear infinite;
}
@keyframes pgGradLine { to { background-position: 200% 0; } }
.pg-form-head { margin-bottom: 28px; }
.pg-form-head h3 { font-size: 24px; color: var(--primary-dark); margin-bottom: 6px; letter-spacing: -.02em; }
.pg-form-head p { color: var(--text-muted); font-size: 14px; }

/* Floating label inputs */
.pg-field { position: relative; margin-bottom: 20px; }
.pg-field input, .pg-field textarea {
    width: 100%; padding: 22px 18px 10px;
    border: 2px solid var(--border); border-radius: 14px;
    font-size: 15px; font-family: inherit; outline: none;
    background: #fff; color: var(--text-dark);
    transition: .3s;
}
.pg-field textarea { resize: vertical; min-height: 130px; padding-top: 26px; }
.pg-field input:focus, .pg-field textarea:focus {
    border-color: var(--accent); box-shadow: 0 0 0 4px rgba(201,162,39,.15);
}
.pg-field label {
    position: absolute; left: 18px; top: 18px;
    font-size: 14px; color: var(--text-muted);
    pointer-events: none; transition: .25s var(--ease-out);
    background: #fff; padding: 0 6px;
    font-weight: 600;
}
.pg-field input:focus ~ label,
.pg-field input:not(:placeholder-shown) ~ label,
.pg-field textarea:focus ~ label,
.pg-field textarea:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 11px; color: var(--accent);
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
}
.pg-field-ic {
    position: absolute; right: 16px; top: 18px; font-size: 18px;
    color: var(--text-muted); pointer-events: none;
}
.pg-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .pg-row { grid-template-columns: 1fr; } }

/* Submit button */
.pg-submit {
    width: 100%; padding: 16px; border-radius: 14px; border: none; cursor: pointer;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); font-weight: 800; font-size: 15px; font-family: inherit;
    transition: .3s; box-shadow: 0 10px 30px rgba(201,162,39,.4);
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
    position: relative; overflow: hidden;
}
.pg-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.pg-submit:hover::before { left: 150%; }
.pg-submit:hover { transform: translateY(-2px); box-shadow: 0 14px 38px rgba(201,162,39,.55); }

.pg-secure {
    display: flex; align-items: center; gap: 10px; margin-top: 16px;
    font-size: 12px; color: var(--text-muted);
}
.pg-secure-ic {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(16,185,129,.1); color: #10B981;
    display: grid; place-items: center; font-size: 12px;
}

/* Success state */
.pg-success {
    text-align: center; padding: 40px 20px;
}
.pg-success-ic {
    width: 90px; height: 90px; border-radius: 50%; margin: 0 auto 24px;
    background: linear-gradient(135deg, #10B981, #34D399);
    display: grid; place-items: center; font-size: 42px; color: #fff;
    box-shadow: 0 15px 40px rgba(16,185,129,.4);
    animation: pgSuccessPop .6s var(--ease-spring);
}
@keyframes pgSuccessPop {
    0% { transform: scale(0); opacity: 0; }
    60% { transform: scale(1.15); }
    100% { transform: scale(1); opacity: 1; }
}
.pg-success h3 { font-size: 24px; color: var(--primary-dark); margin-bottom: 10px; letter-spacing: -.02em; }
.pg-success p { color: var(--text-muted); font-size: 15px; max-width: 420px; margin: 0 auto 24px; }

/* Sidebar */
.pg-side { display: flex; flex-direction: column; gap: 24px; }

/* Stepper card */
.pg-steps-card {
    background: #fff; border-radius: 24px; padding: 32px;
    border: 1px solid var(--border); box-shadow: 0 10px 40px rgba(15,61,92,.06);
}
.pg-steps-card h3 { font-size: 19px; color: var(--primary-dark); margin-bottom: 24px; letter-spacing: -.01em; }
.pg-step {
    display: flex; gap: 16px; position: relative; padding-bottom: 22px;
}
.pg-step:last-child { padding-bottom: 0; }
.pg-step:not(:last-child)::after {
    content: ''; position: absolute; left: 22px; top: 46px; bottom: 0;
    width: 2px; background: linear-gradient(to bottom, var(--accent), rgba(201,162,39,.1));
}
.pg-step-num {
    min-width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); font-weight: 900; font-size: 17px;
    display: grid; place-items: center;
    box-shadow: 0 8px 20px rgba(201,162,39,.35);
    position: relative; z-index: 1;
    transition: .4s var(--ease-spring);
}
.pg-step:hover .pg-step-num { transform: scale(1.1) rotate(-6deg); }
.pg-step-content h4 { font-size: 15px; color: var(--primary-dark); margin-bottom: 4px; letter-spacing: -.01em; }
.pg-step-content p { font-size: 13.5px; color: var(--text-muted); line-height: 1.55; }
.pg-step-content .pg-sla {
    display: inline-block; margin-top: 6px; padding: 2px 10px; border-radius: 50px;
    background: rgba(16,185,129,.1); color: #059669; font-size: 11px; font-weight: 700;
    letter-spacing: .5px;
}

/* FAQ card */
.pg-faq-card {
    background: #fff; border-radius: 24px; padding: 28px;
    border: 1px solid var(--border); box-shadow: 0 10px 40px rgba(15,61,92,.06);
}
.pg-faq-card h3 { font-size: 19px; color: var(--primary-dark); margin-bottom: 20px; letter-spacing: -.01em; }
.pg-faq { border: 1px solid var(--border); border-radius: 14px; margin-bottom: 10px; overflow: hidden; transition: .3s; }
.pg-faq:last-child { margin-bottom: 0; }
.pg-faq[open] { border-color: rgba(201,162,39,.4); box-shadow: 0 6px 18px rgba(201,162,39,.1); }
.pg-faq summary {
    padding: 16px 20px; cursor: pointer; font-weight: 700; font-size: 14px;
    color: var(--text-dark); list-style: none;
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    transition: .25s;
}
.pg-faq summary::-webkit-details-marker { display: none; }
.pg-faq summary:hover { color: var(--primary); }
.pg-faq summary .pg-faq-plus {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
    background: rgba(201,162,39,.12); color: var(--accent);
    display: grid; place-items: center; font-size: 16px; font-weight: 900;
    transition: .3s var(--ease-spring);
}
.pg-faq[open] summary .pg-faq-plus {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); transform: rotate(45deg);
}
.pg-faq p {
    padding: 0 20px 18px; font-size: 13.5px; color: var(--text-muted); line-height: 1.65;
    animation: pgFaqIn .3s var(--ease-out);
}
@keyframes pgFaqIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }

/* Alert toast */
.pg-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600; animation: pgAlertIn .45s var(--ease-out);
    display: flex; gap: 12px; align-items: center;
}
@keyframes pgAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.pg-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid var(--danger); }

/* Reveal */
.pg-reveal { opacity: 0; transform: translateY(30px); transition: opacity .7s var(--ease-out), transform .7s var(--ease-out); }
.pg-reveal.pg-in { opacity: 1; transform: none; }
</style>

<!-- Floating icons di hero -->
<section class="pg-hero">
    <div class="pg-hero-aurora"></div>
    <div class="pg-hero-grid"></div>
    <div class="pg-float-ic pg-fi1">📨</div>
    <div class="pg-float-ic pg-fi2">💬</div>
    <div class="pg-float-ic pg-fi3">✉️</div>
    <div class="pg-float-ic pg-fi4">📝</div>
    <div class="container">
        <div class="pg-hero-inner">
            <span class="pg-hero-badge">
                <span class="pg-hero-badge-dot"></span>
                Layanan Aspirasi Publik
            </span>
            <h1>Pengaduan & <span class="pg-grad">Kritik Membangun</span></h1>
            <p class="pg-hero-lead">
                Masukan Anda adalah bahan bakar peningkatan mutu kampus kami.
                Identitas Anda kami jaga kerahasiaannya.
            </p>
            <div class="pg-trust">
                <div class="pg-trust-item"><span>✓</span> 100% Rahasia</div>
                <div class="pg-trust-item"><span>✓</span> Respon 1-3 Hari</div>
                <div class="pg-trust-item"><span>✓</span> Terenkripsi</div>
                <div class="pg-trust-item"><span>✓</span> Gratis</div>
            </div>
        </div>
    </div>
</section>

<!-- Body -->
<section class="pg-body">
    <div class="container">
        <div class="pg-grid">
            <!-- Form -->
            <div class="pg-form-wrap pg-reveal">
                <div class="pg-form-head">
                    <h3>✍️ Formulir Pengaduan</h3>
                    <p>Isi dengan lengkap dan jujur. Setiap masukan akan kami telaah dengan serius.</p>
                </div>

                <?php if ($success): ?>
                    <div class="pg-success">
                        <div class="pg-success-ic">✓</div>
                        <h3>Pengaduan Terkirim!</h3>
                        <p>Terima kasih atas masukan Anda. Tim LPM akan menindaklanjuti dalam 1-3 hari kerja. Notifikasi akan dikirim ke email Anda.</p>
                        <a href="/publik/berita.php" class="btn btn-primary" style="padding:10px 22px;">Lihat Berita Terbaru →</a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="pg-alert danger">⚠️ <?= Security::e($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" id="pgForm">
                        <?= Security::csrfField() ?>
                        <div class="pg-row">
                            <div class="pg-field">
                                <input type="text" name="nama" id="pgNama" placeholder=" " required>
                                <label for="pgNama">Nama Lengkap *</label>
                                <span class="pg-field-ic">👤</span>
                            </div>
                            <div class="pg-field">
                                <input type="email" name="email" id="pgEmail" placeholder=" " required>
                                <label for="pgEmail">Email Aktif *</label>
                                <span class="pg-field-ic">✉️</span>
                            </div>
                        </div>
                        <div class="pg-field">
                            <input type="text" name="instansi" id="pgInstansi" placeholder=" ">
                            <label for="pgInstansi">Instansi (Opsional)</label>
                            <span class="pg-field-ic">🏢</span>
                        </div>
                        <div class="pg-field">
                            <input type="text" name="subjek" id="pgSubjek" placeholder=" " required>
                            <label for="pgSubjek">Subjek Pengaduan *</label>
                            <span class="pg-field-ic">📌</span>
                        </div>
                        <div class="pg-field">
                            <textarea name="isi" id="pgIsi" placeholder=" " required></textarea>
                            <label for="pgIsi">Uraian Lengkap *</label>
                        </div>
                        <button type="submit" class="pg-submit" id="pgSubmit">📨 Kirim Pengaduan Sekarang</button>
                        <div class="pg-secure">
                            <span class="pg-secure-ic">🔒</span>
                            <span>Data Anda terenkripsi dan hanya diakses oleh petugas LPM yang berwenang.</span>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="pg-side">
                <!-- Stepper -->
                <div class="pg-steps-card pg-reveal">
                    <h3>🧭 Alur Tindak Lanjut</h3>
                    <div class="pg-step">
                        <div class="pg-step-num">1</div>
                        <div class="pg-step-content">
                            <h4>Pengaduan Diterima</h4>
                            <p>Laporan masuk otomatis ke sistem LPM dan tercatat dengan nomor tiket.</p>
                            <span class="pg-sla">⚡ Instan</span>
                        </div>
                    </div>
                    <div class="pg-step">
                        <div class="pg-step-num">2</div>
                        <div class="pg-step-content">
                            <h4>Verifikasi & Telaah</h4>
                            <p>Petugas menelaah substansi laporan secara mendalam.</p>
                            <span class="pg-sla">⏱️ Maks 3 Hari</span>
                        </div>
                    </div>
                    <div class="pg-step">
                        <div class="pg-step-num">3</div>
                        <div class="pg-step-content">
                            <h4>Tindak Lanjut</h4>
                            <p>Laporan diteruskan ke unit terkait untuk ditindaklanjuti.</p>
                            <span class="pg-sla">🔄 Koordinasi</span>
                        </div>
                    </div>
                    <div class="pg-step">
                        <div class="pg-step-num">4</div>
                        <div class="pg-step-content">
                            <h4>Umpan Balik</h4>
                            <p>Status dan hasil tindak lanjut dikirim ke email pelapor.</p>
                            <span class="pg-sla">📧 Notifikasi</span>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="pg-faq-card pg-reveal">
                    <h3>❓ Pertanyaan Umum</h3>
                    <details class="pg-faq">
                        <summary>Apakah identitas saya dirahasiakan?<span class="pg-faq-plus">+</span></summary>
                        <p>Ya, 100% dirahasiakan. Identitas pelapor hanya dapat diakses oleh petugas LPM yang berwenang dan tidak akan dipublikasikan dalam bentuk apa pun.</p>
                    </details>
                    <details class="pg-faq">
                        <summary>Berapa lama tanggapan diberikan?<span class="pg-faq-plus">+</span></summary>
                        <p>Kami berupaya memberikan tanggapan awal dalam 1-3 hari kerja dan tindak lanjut final maksimal 14 hari kerja sejak laporan diterima.</p>
                    </details>
                    <details class="pg-faq">
                        <summary>Bisakah saya mengirim tanpa nama?<span class="pg-faq-plus">+</span></summary>
                        <p>Kolom nama wajib diisi untuk keperluan verifikasi, namun Anda dapat meminta anonimitas dalam isi laporan dan kami akan menghormatinya.</p>
                    </details>
                    <details class="pg-faq">
                        <summary>Apakah ada biaya untuk layanan ini?<span class="pg-faq-plus">+</span></summary>
                        <p>Tidak sama sekali. Layanan pengaduan LPM sepenuhnya gratis dan terbuka untuk seluruh pemangku kepentingan.</p>
                    </details>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    /* Reveal */
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) { en.target.classList.add('pg-in'); io.unobserve(en.target); }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.pg-reveal').forEach(function (el) { io.observe(el); });

    /* Submit button feedback */
    var form = document.getElementById('pgForm');
    if (form) {
        form.addEventListener('submit', function () {
            var btn = document.getElementById('pgSubmit');
            if (btn && !btn.dataset.busy) {
                btn.dataset.busy = '1';
                btn.innerHTML = '⏳ Mengirim...';
            }
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>