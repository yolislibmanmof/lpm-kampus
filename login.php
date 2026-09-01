<?php
require_once 'config/config.php';
Security::sendHeaders();

if (Auth::check()) { header('Location: /sim/index.php'); exit; }

$error = ''; $info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $result = Auth::attempt($email, $password);
        if ($result['success']) { header('Location: /sim/index.php'); exit; }
        $error = $result['message'];
    }
}
if (isset($_GET['expired'])) $info = 'Sesi Anda telah berakhir. Silakan login kembali.';

$favPath = Site::setting('favicon_path');
if ($favPath && file_exists(PATH_UPLOAD . $favPath)) {
    $favHref = '/uploads/' . $favPath;
} else {
    $initial = mb_substr(Site::setting('brand_utama', 'LPM'), 0, 1);
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0%' stop-color='%230F3D5C'/><stop offset='100%' stop-color='%23C9A227'/></linearGradient></defs><rect width='64' height='64' rx='14' fill='url(%23g)'/><text x='32' y='44' font-family='Arial Black' font-size='34' font-weight='900' text-anchor='middle' fill='white'>$initial</text></svg>";
    $favHref = 'data:image/svg+xml;utf8,' . $svg;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIM-Mutu | <?= Security::e(Site::setting('brand_utama','LPM')) ?> <?= Security::e(Site::setting('brand_aksen','Kampus')) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= $favHref ?>">
    <link rel="shortcut icon" href="<?= $favHref ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .lg-wrap { min-height: 100vh; display: grid; grid-template-columns: 1.15fr 1fr; }
        .lg-left {
            position: relative; overflow: hidden; color: #fff; padding: 60px;
            background: linear-gradient(160deg, #061D2E, var(--primary));
            display: flex; align-items: center;
        }
        .lg-left::after { content: ''; position: absolute; width: 460px; height: 460px; border-radius: 50%; background: rgba(201,162,39,.12); bottom: -160px; left: -160px; animation: floatOrb 10s ease-in-out infinite; }
        #lgCanvas { position: absolute; inset: 0; }
        .lg-left-content { position: relative; z-index: 2; max-width: 580px; }
        .lg-left h1 { font-size: clamp(32px, 3.6vw, 46px); margin: 26px 0 14px; }
        .lg-gold { background: linear-gradient(135deg, var(--accent), var(--accent-light)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .lg-left > .lg-left-content > p { color: rgba(255,255,255,.75); font-size: 16px; margin-bottom: 34px; }
        .lg-feats { list-style: none; margin-bottom: 36px; }
        .lg-feats li { display: flex; gap: 14px; align-items: center; margin-bottom: 14px; font-size: 15px; color: rgba(255,255,255,.85); }
        .lg-feats .ic { min-width: 42px; height: 42px; border-radius: 12px; background: rgba(255,255,255,.09); border: 1px solid rgba(255,255,255,.14); display: grid; place-items: center; font-size: 18px; }
        .lg-chips { display: flex; gap: 10px; flex-wrap: wrap; }
        .lg-chips span { padding: 7px 18px; border-radius: 50px; background: rgba(201,162,39,.15); border: 1px solid rgba(201,162,39,.4); color: var(--accent-light); font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }

        .lg-right { display: grid; place-items: center; padding: 44px 24px; background: var(--bg-light); }
        .lg-box { width: 100%; max-width: 450px; animation: fadeUp .7s var(--ease-out); }
        .lg-back { display: inline-block; margin-bottom: 26px; color: var(--text-muted); text-decoration: none; font-size: 13.5px; font-weight: 600; transition: .25s; }
        .lg-back:hover { color: var(--primary); transform: translateX(-4px); }
        .lg-box h2 { font-size: 32px; color: var(--primary-dark); margin-bottom: 6px; }
        .lg-sub { color: var(--text-muted); margin-bottom: 30px; font-size: 15px; }
        .lg-field { position: relative; }
        .lg-field .fi { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 16px; pointer-events: none; }
        .lg-field input { padding-left: 47px; }
        .lg-eye { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 17px; opacity: .6; transition: .25s; }
        .lg-eye:hover { opacity: 1; transform: translateY(-50%) scale(1.15); }
        .lg-submit { width: 100%; justify-content: center; padding: 14px; font-size: 15.5px; }
        .lg-secure { display: flex; gap: 8px; align-items: center; justify-content: center; margin-top: 22px; font-size: 12.5px; color: var(--text-muted); }
        @media (max-width: 992px) { .lg-wrap { grid-template-columns: 1fr; } .lg-left { display: none; } }
    </style>
</head>
<body>
<div class="lg-wrap">
    <div class="lg-left">
        <canvas id="lgCanvas"></canvas>
        <div class="lg-left-content">
            <?= Site::brand('login') ?>
            <h1>Portal <span class="lg-gold">SIM-Mutu</span><br>Sistem Informasi Penjaminan Mutu</h1>
            <p>Satu pintu menuju seluruh layanan penjaminan mutu internal — dari evaluasi diri hingga verifikasi audit.</p>
            <ul class="lg-feats">
                <li><span class="ic">📊</span> Dasbor eksekutif & laporan RTM real-time</li>
                <li><span class="ic">🔍</span> E-Audit & verifikasi tindakan koreksi online</li>
                <li><span class="ic">☁️</span> Cloud borang akreditasi per program studi</li>
                <li><span class="ic">🛡️</span> Keamanan berlapis dengan kontrol akses (RBAC)</li>
            </ul>
            <div class="lg-chips">
                <span>Admin</span><span>Pimpinan</span><span>Kaprodi</span><span>Auditor</span>
            </div>
        </div>
    </div>

    <div class="lg-right">
        <div class="lg-box">
            <a href="/index.php" class="lg-back">← Kembali ke Website Publik</a>
            <h2>Selamat Datang 👋</h2>
            <p class="lg-sub">Masuk menggunakan akun institusi Anda</p>

            <?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>
            <?php if ($info): ?><div class="alert alert-success"><?= Security::e($info) ?></div><?php endif; ?>

            <form method="POST" autocomplete="off">
                <?= Security::csrfField() ?>
                <div class="form-group">
                    <label class="form-label">Email Institusi</label>
                    <div class="lg-field">
                        <span class="fi">✉️</span>
                        <input type="email" name="email" class="form-control" placeholder="nama@kampus.ac.id" required value="<?= Security::e($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="lg-field">
                        <span class="fi">🔐</span>
                        <input type="password" name="password" id="lgPass" class="form-control" placeholder="••••••••" required minlength="8">
                        <button type="button" class="lg-eye" id="lgEye" aria-label="Lihat password">👁️</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary lg-submit">🔐 Masuk ke SIM-Mutu</button>
            </form>

            <div class="lg-secure">🔒 Koneksi terenkripsi • Dilindungi CSRF & rate-limiting</div>
        </div>
    </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
(function () {
    var eye = document.getElementById('lgEye'), pass = document.getElementById('lgPass');
    if (eye) eye.addEventListener('click', function () {
        var show = pass.type === 'password';
        pass.type = show ? 'text' : 'password';
        eye.textContent = show ? '🙈' : '👁️';
    });
})();
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var c = document.getElementById('lgCanvas');
    if (!c) return;
    var ctx = c.getContext('2d'), W, H, pts = [], N = 42;
    function rs() { W = c.width = c.offsetWidth; H = c.height = c.offsetHeight; }
    rs(); window.addEventListener('resize', rs);
    for (var i = 0; i < N; i++) pts.push({ x: Math.random() * 900, y: Math.random() * 700, vx: (Math.random() - .5) * .4, vy: (Math.random() - .5) * .4, r: Math.random() * 1.7 + .5, o: Math.random() * .5 + .15 });
    (function step() {
        if (!document.hidden) {
            ctx.clearRect(0, 0, W, H);
            for (var a = 0; a < N; a++) {
                var p = pts[a];
                p.x += p.vx; p.y += p.vy;
                if (p.x < 0 || p.x > W) p.vx *= -1;
                if (p.y < 0 || p.y > H) p.vy *= -1;
                ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, 6.283);
                ctx.fillStyle = 'rgba(232,197,90,' + p.o + ')'; ctx.fill();
            }
            for (var x = 0; x < N; x++) for (var y = x + 1; y < N; y++) {
                var dx = pts[x].x - pts[y].x, dy = pts[x].y - pts[y].y, d = Math.sqrt(dx * dx + dy * dy);
                if (d < 105) {
                    ctx.strokeStyle = 'rgba(255,255,255,' + (.13 * (1 - d / 105)) + ')';
                    ctx.lineWidth = .6;
                    ctx.beginPath(); ctx.moveTo(pts[x].x, pts[x].y); ctx.lineTo(pts[y].x, pts[y].y); ctx.stroke();
                }
            }
        }
        requestAnimationFrame(step);
    })();
})();
</script>
</body>
</html>