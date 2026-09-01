<?php
$pageTitle = 'Responden Wawancara Akreditasi';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();
$db = Database::getInstance();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetchAll();
$success = false; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $nama = trim($_POST['nama'] ?? '');
    if (!$nama) { $error = 'Nama wajib diisi.'; }
    else {
        $db->prepare("INSERT INTO responden_akreditasi (tipe, nama, identitas, id_prodi, unit_kerja, no_wa, email, ketersediaan, catatan) VALUES (?,?,?,?,?,?,?,?,?)")
           ->execute([
               $_POST['tipe'], $nama, trim($_POST['identitas']),
               $_POST['tipe'] === 'Tendik' ? null : (int)($_POST['id_prodi'] ?? 0),
               $_POST['tipe'] === 'Tendik' ? trim($_POST['unit_kerja']) : null,
               trim($_POST['no_wa']), trim($_POST['email']), trim($_POST['ketersediaan']), trim($_POST['catatan'])
           ]);
        Notifier::sendRole(1, '🎤 Responden Terdaftar', $nama . ' (' . $_POST['tipe'] . ') mendaftar sebagai responden wawancara.', '/sim/admin/responden.php', '🎤');
        $success = true;
    }
}
require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   RESPONDEN WAWANCARA ULTIMATE v8.0 — Segmented Control • Conditional
============================================================ */

/* ===== HERO ===== */
.rw-hero {
    min-height: 60vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 120px 0 60px;
    display: flex; align-items: center;
}
.rw-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: rwShift 16s ease-in-out infinite;
}
@keyframes rwShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.rw-hero-grid {
    position: absolute; inset: 0; pointer-events: none; opacity: .3;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.rw-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; }

.rw-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.rw-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: rwShimmer 3.5s ease-in-out infinite;
}
@keyframes rwShimmer { to { left: 200%; } }
.rw-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: rwPulse 2s ease infinite;
}
@keyframes rwPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.rw-hero h1 {
    font-size: clamp(34px, 5vw, 62px); font-weight: 800;
    line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em;
}
.rw-hero h1 .rw-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: rwTextShine 4s linear infinite; font-style: italic;
}
@keyframes rwTextShine { to { background-position: 200% center; } }
.rw-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

/* Role pills */
.rw-roles {
    display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 32px;
}
.rw-role-pill {
    padding: 10px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.15);
    color: rgba(255,255,255,.9); font-size: 13px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 8px;
}

/* ===== BODY ===== */
.rw-body { padding: 60px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }

/* Glass Card */
.rw-card {
    max-width: 820px; margin: 0 auto;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid var(--border); position: relative;
    box-shadow: 0 24px 70px rgba(15,61,92,.1);
}
.rw-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--primary), var(--accent));
    background-size: 200% 100%; animation: rwGradLine 4s linear infinite;
}
@keyframes rwGradLine { to { background-position: 200% 0; } }

/* Header */
.rw-head {
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 32px 36px; position: relative; overflow: hidden;
}
.rw-head::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.rw-head-inner { position: relative; z-index: 1; }
.rw-head small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: block; margin-bottom: 6px; }
.rw-head h3 { font-size: 24px; font-weight: 800; margin-bottom: 6px; letter-spacing: -.02em; }
.rw-head p { color: rgba(255,255,255,.8); font-size: 14px; }

/* Form body */
.rw-form-body { padding: 36px; }

/* Segmented control */
.rw-segmented {
    display: grid; grid-template-columns: repeat(4, 1fr);
    background: var(--bg-light); border: 1px solid var(--border);
    border-radius: 14px; padding: 4px; margin-bottom: 24px;
    position: relative;
}
.rw-seg {
    padding: 14px; border: none; background: transparent; cursor: pointer;
    font-family: inherit; font-size: 13px; font-weight: 700;
    color: var(--text-muted); border-radius: 10px;
    transition: .3s var(--ease-out);
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    position: relative; z-index: 1;
}
.rw-seg .rw-seg-ic { font-size: 20px; }
.rw-seg.active {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark);
    box-shadow: 0 6px 18px rgba(201,162,39,.35);
}
@media (max-width: 640px) { .rw-segmented { grid-template-columns: repeat(2, 1fr); } }

/* Floating labels */
.rw-field { position: relative; margin-bottom: 18px; }
.rw-field input, .rw-field select, .rw-field textarea {
    width: 100%; padding: 22px 18px 10px;
    border: 2px solid var(--border); border-radius: 14px;
    font-size: 14px; font-family: inherit; outline: none;
    background: #fff; color: var(--text-dark); transition: .3s;
}
.rw-field textarea { resize: vertical; min-height: 90px; padding-top: 26px; }
.rw-field select { cursor: pointer; appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath fill='%230F3D5C' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 16px center; padding-right: 40px;
}
.rw-field input:focus, .rw-field select:focus, .rw-field textarea:focus {
    border-color: var(--accent); box-shadow: 0 0 0 4px rgba(201,162,39,.15);
}
.rw-field label {
    position: absolute; left: 18px; top: 18px;
    font-size: 13px; color: var(--text-muted);
    pointer-events: none; transition: .25s var(--ease-out);
    background: #fff; padding: 0 6px; font-weight: 600;
}
.rw-field input:focus ~ label,
.rw-field input:not(:placeholder-shown) ~ label,
.rw-field select:focus ~ label,
.rw-field.has-value label,
.rw-field textarea:focus ~ label,
.rw-field textarea:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 10.5px; color: var(--accent);
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
}
.rw-field-ic {
    position: absolute; right: 16px; top: 20px; font-size: 16px;
    color: var(--text-muted); pointer-events: none;
}

/* Row grid */
.rw-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .rw-row { grid-template-columns: 1fr; } }

/* Conditional field animation */
.rw-conditional {
    max-height: 0; overflow: hidden; opacity: 0;
    transition: max-height .4s var(--ease-out), opacity .3s ease, margin .3s;
}
.rw-conditional.active { max-height: 300px; opacity: 1; margin-bottom: 18px; }

/* Submit */
.rw-submit {
    margin-top: 28px; width: 100%; padding: 18px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: var(--primary-dark); box-shadow: 0 12px 36px rgba(201,162,39,.45);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.rw-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.rw-submit:hover::before { left: 150%; }
.rw-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(201,162,39,.65); }

/* Secure note */
.rw-secure {
    display: flex; align-items: center; gap: 10px; margin-top: 16px;
    font-size: 12px; color: var(--text-muted);
}
.rw-secure-ic {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(16,185,129,.1); color: #10B981;
    display: grid; place-items: center; font-size: 12px;
}

/* Alert */
.rw-alert {
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600;
    display: flex; gap: 12px; align-items: center;
    animation: rwAlertIn .45s var(--ease-out);
}
@keyframes rwAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.rw-alert.danger { background: #FEE2E2; color: #991B1B; border-left: 4px solid var(--danger); }

/* Success state */
.rw-state { text-align: center; padding: 70px 40px; position: relative; overflow: hidden; }
.rw-state-ic {
    width: 110px; height: 110px; border-radius: 50%; margin: 0 auto 24px;
    display: grid; place-items: center; font-size: 52px;
    background: linear-gradient(135deg, #10B981, #34D399); color: #fff;
    box-shadow: 0 20px 55px rgba(16,185,129,.4);
    animation: rwSuccessPop .7s var(--ease-spring);
    position: relative; z-index: 1;
}
@keyframes rwSuccessPop {
    0% { transform: scale(0) rotate(-180deg); }
    60% { transform: scale(1.15) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}
.rw-state h3 { font-size: 28px; color: var(--primary-dark); margin-bottom: 12px; letter-spacing: -.02em; position: relative; z-index: 1; }
.rw-state p { color: var(--text-muted); font-size: 16px; max-width: 480px; margin: 0 auto; line-height: 1.6; position: relative; z-index: 1; }

/* Confetti */
.rw-confetti {
    position: absolute; width: 10px; height: 10px; opacity: 0;
    animation: rwConfettiFall 3s ease-out forwards;
}
@keyframes rwConfettiFall {
    0% { transform: translateY(-100px) rotate(0); opacity: 1; }
    100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
}

@media (max-width: 640px) {
    .rw-form-body { padding: 24px; }
    .rw-head { padding: 24px; }
}
</style>

<!-- ===== HERO ===== -->
<section class="rw-hero">
    <div class="rw-hero-aurora"></div>
    <div class="rw-hero-grid"></div>
    <div class="container">
        <div class="rw-hero-inner">
            <span class="rw-hero-badge">
                <span class="rw-hero-badge-dot"></span>
                Registrasi Responden Akreditasi
            </span>
            <h1>Dukung <span class="rw-grad">Akreditasi Kampus</span><br>Sebagai Responden</h1>
            <p class="rw-hero-lead">
                Daftarkan diri Anda sebagai calon responden wawancara asesor.
                Partisipasi Anda sangat berarti bagi keberhasilan akreditasi.
            </p>
            <div class="rw-roles">
                <div class="rw-role-pill">🎓 Alumni</div>
                <div class="rw-role-pill">📚 Mahasiswa</div>
                <div class="rw-role-pill">👨‍🏫 Dosen</div>
                <div class="rw-role-pill">💼 Tendik</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== BODY ===== -->
<section class="rw-body">
    <div class="container">
        <div class="rw-card">
            <?php if ($success): ?>
                <div class="rw-state" id="rwSuccessState">
                    <div class="rw-state-ic">🎉</div>
                    <h3>Registrasi Berhasil!</h3>
                    <p>Terima kasih telah mendaftar sebagai calon responden. Tim LPM akan menghubungi Anda via WhatsApp bila jadwal wawancara telah ditetapkan.</p>
                    <a href="/publik/berita.php" class="btn btn-primary" style="margin-top:24px;padding:10px 22px;">Baca Berita Terbaru →</a>
                </div>
            <?php else: ?>
                <!-- Head -->
                <div class="rw-head">
                    <div class="rw-head-inner">
                        <small>Formulir Kesediaan</small>
                        <h3>Daftar Sebagai Responden Wawancara</h3>
                        <p>Pilih kategori Anda di bawah — kolom akan menyesuaikan otomatis.</p>
                    </div>
                </div>

                <div class="rw-form-body">
                    <?php if ($error): ?><div class="rw-alert danger">⚠️ <?= Security::e($error) ?></div><?php endif; ?>

                    <form method="POST" id="rwForm">
                        <?= Security::csrfField() ?>

                        <!-- Segmented Control -->
                        <div class="rw-segmented" id="rwSeg">
                            <button type="button" class="rw-seg active" data-tipe="Alumni">
                                <span class="rw-seg-ic">🎓</span>Alumni
                            </button>
                            <button type="button" class="rw-seg" data-tipe="Mahasiswa">
                                <span class="rw-seg-ic">📚</span>Mahasiswa
                            </button>
                            <button type="button" class="rw-seg" data-tipe="Dosen">
                                <span class="rw-seg-ic">👨‍🏫</span>Dosen
                            </button>
                            <button type="button" class="rw-seg" data-tipe="Tendik">
                                <span class="rw-seg-ic">💼</span>Tendik
                            </button>
                        </div>
                        <input type="hidden" name="tipe" id="rwTipe" value="Alumni">

                        <div class="rw-row">
                            <div class="rw-field">
                                <input type="text" name="nama" id="rwNama" placeholder=" " required>
                                <label for="rwNama">Nama Lengkap *</label>
                                <span class="rw-field-ic">👤</span>
                            </div>
                            <div class="rw-field">
                                <input type="text" name="identitas" id="rwId" placeholder=" ">
                                <label for="rwId">NIM / NIDN / NIP</label>
                                <span class="rw-field-ic">🆔</span>
                            </div>
                        </div>

                        <!-- Conditional: Prodi -->
                        <div class="rw-conditional active" id="rwProdiWrap">
                            <div class="rw-field has-value">
                                <select name="id_prodi" id="rwProdi">
                                    <option value="0">— Pilih Prodi —</option>
                                    <?php foreach ($prodiList as $p): ?>
                                        <option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="rwProdi">Program Studi</label>
                            </div>
                        </div>

                        <!-- Conditional: Unit Kerja -->
                        <div class="rw-conditional" id="rwUnitWrap">
                            <div class="rw-field has-value">
                                <select name="unit_kerja" id="rwUnit">
                                    <option>Perpustakaan</option>
                                    <option>TIPD / IT</option>
                                    <option>Laboratorium</option>
                                    <option>Sarpras</option>
                                    <option>BAAK / Administrasi</option>
                                    <option>Keuangan</option>
                                    <option>Humas</option>
                                    <option>Lainnya</option>
                                </select>
                                <label for="rwUnit">Unit Kerja</label>
                            </div>
                        </div>

                        <div class="rw-row">
                            <div class="rw-field">
                                <input type="text" name="no_wa" id="rwWa" placeholder=" " required>
                                <label for="rwWa">No. WhatsApp *</label>
                                <span class="rw-field-ic">💬</span>
                            </div>
                            <div class="rw-field">
                                <input type="email" name="email" id="rwEmail" placeholder=" ">
                                <label for="rwEmail">Email</label>
                                <span class="rw-field-ic">✉️</span>
                            </div>
                        </div>

                        <div class="rw-field has-value">
                            <select name="ketersediaan" id="rwKet">
                                <option>Sangat Siap</option>
                                <option>Siap</option>
                                <option>Ragu-ragu</option>
                                <option>Perlu Penjadwalan Ulang</option>
                            </select>
                            <label for="rwKet">Ketersediaan</label>
                        </div>

                        <div class="rw-field">
                            <textarea name="catatan" id="rwCat" placeholder=" "></textarea>
                            <label for="rwCat">Catatan (Opsional)</label>
                        </div>

                        <button type="submit" class="rw-submit" id="rwSubmit">🎤 Daftar Sebagai Responden</button>

                        <div class="rw-secure">
                            <span class="rw-secure-ic">🔒</span>
                            <span>Data Anda aman dan hanya digunakan untuk keperluan akreditasi.</span>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function () {
    /* Segmented control */
    var seg = document.getElementById('rwSeg');
    var tipeInput = document.getElementById('rwTipe');
    var prodiWrap = document.getElementById('rwProdiWrap');
    var unitWrap = document.getElementById('rwUnitWrap');

    if (seg) {
        seg.addEventListener('click', function (e) {
            var btn = e.target.closest('.rw-seg');
            if (!btn) return;
            seg.querySelectorAll('.rw-seg').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var tipe = btn.dataset.tipe;
            tipeInput.value = tipe;

            if (tipe === 'Tendik') {
                prodiWrap.classList.remove('active');
                unitWrap.classList.add('active');
            } else {
                prodiWrap.classList.add('active');
                unitWrap.classList.remove('active');
            }
        });
    }

    /* Submit */
    var form = document.getElementById('rwForm');
    if (form) {
        form.addEventListener('submit', function () {
            var btn = document.getElementById('rwSubmit');
            if (btn && !btn.dataset.busy) {
                btn.dataset.busy = '1';
                btn.innerHTML = '⏳ Mendaftar...';
            }
        });
    }

    /* Confetti on success */
    var successState = document.getElementById('rwSuccessState');
    if (successState) {
        var colors = ['#C9A227', '#E8C55A', '#0F3D5C', '#10B981', '#F59E0B'];
        for (var i = 0; i < 40; i++) {
            var conf = document.createElement('div');
            conf.className = 'rw-confetti';
            conf.style.left = Math.random() * 100 + '%';
            conf.style.top = '-20px';
            conf.style.background = colors[Math.floor(Math.random() * colors.length)];
            conf.style.borderRadius = Math.random() > .5 ? '50%' : '2px';
            conf.style.animationDelay = (Math.random() * 1.5) + 's';
            conf.style.animationDuration = (2 + Math.random() * 2) + 's';
            successState.appendChild(conf);
        }
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>