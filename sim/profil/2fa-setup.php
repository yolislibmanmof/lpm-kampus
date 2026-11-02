<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
if (!Auth::check()) {
    header('Location: ' . BASE_URL . '/sim/login.php');
    exit;
}
require_once dirname(__DIR__, 2) . '/core/TotpAuth.php';
$db = Database::getInstance();

$simTitle = 'Two-Factor Authentication';
$activeMenu = 'profil';
$user = Auth::user();
$pesan = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'enable') {
        $code = trim($_POST['code'] ?? '');
        $secret = $_POST['secret'] ?? '';
        if (TotpAuth::verifyCode($secret, $code)) {
            if (TotpAuth::enable2fa($user['id'], $secret)) {
                // Generate recovery codes
                $codes = TotpAuth::generateRecoveryCodes(10);
                $db->beginTransaction();
                $db->prepare("DELETE FROM totp_recovery_codes WHERE id_user = ?")->execute([$user['id']]);
                $ins = $db->prepare("INSERT INTO totp_recovery_codes (id_user, code_hash) VALUES (?, ?)");
                foreach ($codes as $c) {
                    $ins->execute([$user['id'], TotpAuth::hashRecoveryCode($c)]);
                }
                $db->commit();
                Logger::log('SECURITY', '2FA enabled for user #' . $user['id']);
                
                $_SESSION['recovery_codes_new'] = $codes;
                $pesan = '✅ 2FA berhasil diaktifkan!';
                header('Location: ' . $_SERVER['PHP_SELF'] . '?show_recovery=1');
                exit;
            }
        } else {
            $pesan = '❌ Kode verifikasi tidak valid. Coba lagi.';
        }
    } elseif ($action === 'disable') {
        if (TotpAuth::disable2fa($user['id'])) {
            Logger::log('SECURITY', '2FA disabled for user #' . $user['id']);
            $pesan = '✅ 2FA telah dinonaktifkan.';
        }
    } elseif ($action === 'regenerate_recovery') {
        $codes = TotpAuth::generateRecoveryCodes(10);
        $db->beginTransaction();
        $db->prepare("DELETE FROM totp_recovery_codes WHERE id_user = ?")->execute([$user['id']]);
        $ins = $db->prepare("INSERT INTO totp_recovery_codes (id_user, code_hash) VALUES (?, ?)");
        foreach ($codes as $c) {
            $ins->execute([$user['id'], TotpAuth::hashRecoveryCode($c)]);
        }
        $db->commit();
        $_SESSION['recovery_codes_new'] = $codes;
        header('Location: ' . $_SERVER['PHP_SELF'] . '?show_recovery=1');
        exit;
    }
}

$is2faEnabled = TotpAuth::is2faEnabled($user['id']);
$newSecret = !$is2faEnabled ? TotpAuth::generateSecret() : null;
$provisioningUri = $newSecret ? TotpAuth::getProvisioningUri($newSecret, $user['email']) : null;
$showRecovery = isset($_GET['show_recovery']) && isset($_SESSION['recovery_codes_new']);
$recoveryCodes = $showRecovery ? $_SESSION['recovery_codes_new'] : [];
$remainingRecovery = $is2faEnabled ? TotpAuth::getRemainingRecoveryCodes($user['id']) : 0;
if ($showRecovery) unset($_SESSION['recovery_codes_new']);

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?>
    <div class="alert <?= strpos($pesan, '✅') !== false ? 'alert-success' : 'alert-danger' ?>"><?= Security::e($pesan) ?></div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <div>
        <h2 style="margin:0;">🔐 Two-Factor Authentication</h2>
        <p class="text-muted" style="margin:4px 0 0;font-size:13px;">Lindungi akun Anda dengan verifikasi 2 langkah.</p>
    </div>
    <?php if ($is2faEnabled): ?>
        <span class="badge badge-success" style="padding:8px 16px;font-size:13px;">✓ Aktif</span>
    <?php else: ?>
        <span class="badge badge-warning" style="padding:8px 16px;font-size:13px;">⚠ Nonaktif</span>
    <?php endif; ?>
</div>

<?php if ($showRecovery): ?>
<!-- ===== RECOVERY CODES ===== -->
<div class="card" style="border-left:4px solid var(--warning);">
    <h3 style="margin-bottom:8px;">🔑 Kode Recovery Anda</h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:16px;">
        <strong style="color:var(--danger);">PENTING:</strong> Simpan kode-kode ini di tempat aman. 
        Setiap kode hanya bisa dipakai <strong>sekali</strong> untuk login jika Anda kehilangan akses ke authenticator.
    </p>
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:16px;">
        <?php foreach ($recoveryCodes as $i => $c): ?>
            <div style="padding:12px;background:var(--bg-light);border-radius:8px;font-family:monospace;font-size:15px;font-weight:700;text-align:center;user-select:all;">
                <small style="color:var(--text-muted);font-weight:600;">#<?= $i + 1 ?></small><br>
                <?= Security::e($c) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <button onclick="copyRecovery()" class="btn btn-outline" style="padding:8px 16px;font-size:13px;">
        📋 Salin Semua Kode
    </button>
    <button onclick="window.print()" class="btn btn-outline" style="padding:8px 16px;font-size:13px;">
        🖨️ Cetak
    </button>
</div>
<script>
function copyRecovery() {
    const codes = <?= json_encode($recoveryCodes) ?>;
    navigator.clipboard.writeText(codes.join('\n')).then(() => {
        alert('Semua kode recovery telah disalin ke clipboard!');
    });
}
</script>
<?php endif; ?>

<?php if (!$is2faEnabled): ?>
<!-- ===== SETUP 2FA ===== -->
<div class="card">
    <h3 style="margin-bottom:16px;">📱 Aktifkan 2FA</h3>
    <div style="display:grid;grid-template-columns:200px 1fr;gap:24px;align-items:start;">
        <div style="text-align:center;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($provisioningUri) ?>" 
                 alt="QR Code" style="border-radius:12px;border:4px solid var(--bg-light);">
            <p class="text-muted" style="font-size:11px;margin-top:8px;">Scan dengan Google Authenticator / Authy</p>
        </div>
        <div>
            <p style="margin-bottom:16px;font-size:13px;line-height:1.6;">
                <strong>Langkah 1:</strong> Unduh aplikasi authenticator (Google Authenticator, Microsoft Authenticator, atau Authy) di HP Anda.<br>
                <strong>Langkah 2:</strong> Scan QR code di samping atau masukkan kode berikut secara manual:<br>
                <code style="background:var(--bg-light);padding:8px 12px;border-radius:6px;font-size:13px;display:inline-block;margin-top:6px;user-select:all;font-weight:700;"><?= Security::e($newSecret) ?></code>
            </p>
            
            <form method="POST">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="enable">
                <input type="hidden" name="secret" value="<?= Security::e($newSecret) ?>">
                <div class="form-group">
                    <label class="form-label">Langkah 3: Masukkan kode 6 digit dari aplikasi</label>
                    <input type="text" name="code" class="form-control" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autocomplete="one-time-code" style="max-width:240px;font-family:monospace;font-size:20px;letter-spacing:8px;text-align:center;font-weight:700;">
                </div>
                <button type="submit" class="btn btn-primary">✓ Verifikasi & Aktifkan</button>
            </form>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ===== 2FA AKTIF ===== -->
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:8px;">🛡️ Status 2FA</h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:16px;">
        Akun Anda dilindungi dengan verifikasi dua langkah. Setiap login akan meminta kode dari aplikasi authenticator.
    </p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px;">
        <div style="padding:16px;background:var(--bg-light);border-radius:12px;">
            <small class="text-muted">Kode Recovery Tersisa</small>
            <div style="font-size:24px;font-weight:800;margin-top:4px;"><?= $remainingRecovery ?>/10</div>
        </div>
        <div style="padding:16px;background:var(--bg-light);border-radius:12px;">
            <small class="text-muted">Teraktivasi Sejak</small>
            <div style="font-size:16px;font-weight:700;margin-top:4px;"><?= $user['totp_verified_at'] ? date('d M Y', strtotime($user['totp_verified_at'])) : '—' ?></div>
        </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <form method="POST" style="display:inline;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="regenerate_recovery">
            <button class="btn btn-outline" style="padding:8px 16px;font-size:13px;">🔄 Generate Ulang Recovery Codes</button>
        </form>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin menonaktifkan 2FA? Akun Anda akan kurang aman.');">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="disable">
            <button class="btn btn-outline" style="padding:8px 16px;font-size:13px;color:var(--danger);border-color:var(--danger);">🚫 Nonaktifkan 2FA</button>
        </form>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:12px;">💡 Tips Keamanan</h3>
    <ul style="font-size:13px;line-height:1.8;color:var(--text-muted);padding-left:20px;">
        <li>Jangan bagikan kode OTP atau recovery code kepada siapapun.</li>
        <li>Simpan recovery codes di tempat aman (offline, misal: dicetak).</li>
        <li>Gunakan aplikasi authenticator dengan backup cloud (Authy, Google Authenticator).</li>
        <li>Jika HP hilang, segera gunakan recovery code dan generate ulang.</li>
    </ul>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>