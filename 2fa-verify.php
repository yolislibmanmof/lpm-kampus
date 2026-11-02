<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/TotpAuth.php';

// Harus dalam status pending 2FA
if (empty($_SESSION['require_2fa'])) {
    header('Location: /login.php');
    exit;
}
if (isset($_SESSION['require_2fa_expires']) && $_SESSION['require_2fa_expires'] < time()) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: /login.php?expired=1');
    exit;
}

$userId = (int)Auth::id();
$error = '';
$mode = $_POST['mode'] ?? 'totp';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId > 0) {
    if ($mode === 'totp') {
        $code = trim($_POST['code'] ?? '');
        $secret = TotpAuth::getUserSecret($userId);
        if ($secret && TotpAuth::verifyCode($secret, $code)) {
            unset($_SESSION['require_2fa'], $_SESSION['require_2fa_expires']);
            session_regenerate_id(true);
            Logger::log('LOGIN', '2FA success (TOTP) user #' . $userId);
            header('Location: /sim/index.php');
            exit;
        }
        $error = 'Kode OTP tidak valid atau sudah kadaluarsa.';
    } else {
        $code = trim($_POST['recovery_code'] ?? '');
        if ($userId > 0 && TotpAuth::consumeRecoveryCode($userId, $code)) {
            unset($_SESSION['require_2fa'], $_SESSION['require_2fa_expires']);
            session_regenerate_id(true);
            Logger::log('LOGIN', '2FA success (recovery code) user #' . $userId);
            header('Location: /sim/index.php');
            exit;
        }
        $error = 'Kode recovery tidak valid atau sudah dipakai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Verifikasi 2FA | SIM-Mutu</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Plus Jakarta Sans', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #061D2E, #0F3D5C); color: #fff; padding: 20px; }
.card { background: #fff; color: #0F3D5C; border-radius: 24px; padding: 40px; max-width: 440px; width: 100%; box-shadow: 0 30px 80px rgba(0,0,0,.3); }
.logo { text-align: center; margin-bottom: 24px; font-size: 48px; }
h2 { text-align: center; font-size: 22px; margin-bottom: 8px; color: #0F3D5C; }
p.desc { text-align: center; color: #64748B; font-size: 13px; margin-bottom: 28px; line-height: 1.6; }
.field { margin-bottom: 16px; }
.field label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #0F3D5C; text-transform: uppercase; letter-spacing: .5px; }
.field input { width: 100%; padding: 14px 16px; border: 2px solid #E2E8F0; border-radius: 12px; font-size: 18px; font-family: monospace; text-align: center; letter-spacing: 8px; font-weight: 700; outline: none; transition: .3s; }
.field input:focus { border-color: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.15); }
.btn { width: 100%; padding: 14px; border-radius: 12px; border: none; background: linear-gradient(135deg, #0F3D5C, #1A5A82); color: #fff; font-size: 14px; font-weight: 800; cursor: pointer; transition: .3s; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(15,61,92,.3); }
.error { padding: 12px; background: #FEE2E2; color: #991B1B; border-radius: 10px; margin-bottom: 16px; font-size: 13px; text-align: center; border-left: 4px solid #EF4444; }
.tabs { display: flex; gap: 8px; margin-bottom: 20px; background: #F7F9FC; padding: 4px; border-radius: 10px; }
.tab { flex: 1; padding: 10px; text-align: center; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 700; color: #64748B; transition: .2s; border: none; background: none; }
.tab.active { background: #fff; color: #0F3D5C; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.panel { display: none; }
.panel.active { display: block; }
a { color: #C9A227; text-decoration: none; }
a:hover { text-decoration: underline; }
.back-link { text-align: center; margin-top: 16px; font-size: 12px; }
</style>
</head>
<body>
<div class="card">
    <div class="logo">🔐</div>
    <h2>Verifikasi Dua Faktor</h2>
    <p class="desc">Akun Anda dilindungi 2FA. Masukkan kode 6 digit dari aplikasi authenticator Anda.</p>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="tabs">
        <button type="button" class="tab <?= $mode === 'totp' ? 'active' : '' ?>" onclick="switchTab('totp', this)">📱 Kode OTP</button>
        <button type="button" class="tab <?= $mode === 'recovery' ? 'active' : '' ?>" onclick="switchTab('recovery', this)">🔑 Kode Recovery</button>
    </div>

    <div class="panel <?= $mode === 'totp' ? 'active' : '' ?>" id="panel-totp">
        <form method="POST">
            <input type="hidden" name="mode" value="totp">
            <div class="field">
                <label>Kode 6 Digit</label>
                <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus autocomplete="one-time-code">
            </div>
            <button type="submit" class="btn">✓ Verifikasi</button>
        </form>
    </div>

    <div class="panel <?= $mode === 'recovery' ? 'active' : '' ?>" id="panel-recovery">
        <form method="POST">
            <input type="hidden" name="mode" value="recovery">
            <div class="field">
                <label>Kode Recovery (XXXX-XXXX)</label>
                <input type="text" name="recovery_code" maxlength="9" style="letter-spacing:3px;" required autocomplete="off" placeholder="AB12-CD34">
            </div>
            <button type="submit" class="btn">✓ Gunakan Kode Recovery</button>
            <p style="text-align:center;font-size:11px;color:#64748B;margin-top:12px;">⚠️ Setiap kode recovery hanya bisa dipakai sekali.</p>
        </form>
    </div>

    <div class="back-link"><a href="/logout.php">← Batalkan & Keluar</a></div>
</div>
<script>
function switchTab(mode, btn) {
    document.querySelectorAll('.tab').forEach(function (t) { t.classList.remove('active'); });
    document.querySelectorAll('.panel').forEach(function (p) { p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('panel-' + mode).classList.add('active');
}
</script>
</body>
</html>