<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/core/Mailer.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

/* Ambil setting sebagai SCALAR — tidak ada array di bagian HTML */
$row = $db->query("SELECT * FROM mail_settings WHERE id = 1")->fetch();
$row = is_array($row) ? $row : [];
$f_aktif = !empty($row['aktif']);
$f_host  = $row['smtp_host'] ?? 'smtp.gmail.com';
$f_port  = $row['smtp_port'] ?? 587;
$f_user  = $row['smtp_user'] ?? '';
$f_pass  = $row['smtp_pass'] ?? '';
$f_from  = $row['smtp_from'] ?? '';
$f_name  = $row['smtp_name'] ?? 'SIM-Mutu LPM';
$f_to    = $row['kirim_ke'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    if (($_POST['action'] ?? '') === 'save') {
        $db->prepare("REPLACE INTO mail_settings (id, aktif, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_from, smtp_name, kirim_ke) VALUES (1,?,?,?,?,?,?,?,?)")
           ->execute([
               isset($_POST['aktif']) ? 1 : 0,
               trim($_POST['smtp_host']), (int)$_POST['smtp_port'], trim($_POST['smtp_user']), $_POST['smtp_pass'],
               trim($_POST['smtp_from']), trim($_POST['smtp_name']), trim($_POST['kirim_ke'])
           ]);
        $pesan = '✅ Pengaturan email tersimpan.';
        header('Location: /sim/admin/email.php');
        exit;
    }

    if (($_POST['action'] ?? '') === 'test') {
        if (!$f_to) {
            $error = '❌ Isi dulu kolom "Kirim Notifikasi Ke" lalu simpan.';
        } else {
            $r = Mailer::send(array_map('trim', explode(',', $f_to)), '✅ Email Test SIM-Mutu',
                Mailer::template('Uji Coba Email', ['Selamat! Notifikasi email SIM-Mutu berfungsi dengan baik.']));
            if ($r['success']) $pesan = '✅ ' . $r['message'];
            else $error = '❌ Gagal: ' . $r['message'];
        }
    }
}

$simTitle = 'Pengaturan Email';
$activeMenu = 'email';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px;">
    <div class="card">
        <h3 style="margin-bottom:20px;">✉️ Konfigurasi SMTP</h3>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="save">
            <label style="display:flex;gap:10px;align-items:center;font-size:14px;margin-bottom:18px;">
                <input type="checkbox" name="aktif" value="1" <?= $f_aktif ? 'checked' : '' ?>> Aktifkan notifikasi email
            </label>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;">
                <div class="form-group"><label class="form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control" value="<?= Security::e($f_host) ?>"></div>
                <div class="form-group"><label class="form-label">Port</label>
                    <input type="number" name="smtp_port" class="form-control" value="<?= Security::e($f_port) ?>"></div>
                <div class="form-group"><label class="form-label">Username / Email</label>
                    <input type="text" name="smtp_user" class="form-control" value="<?= Security::e($f_user) ?>"></div>
                <div class="form-group"><label class="form-label">Password / App Password</label>
                    <input type="password" name="smtp_pass" class="form-control" value="<?= Security::e($f_pass) ?>"></div>
                <div class="form-group"><label class="form-label">Alamat Pengirim</label>
                    <input type="text" name="smtp_from" class="form-control" value="<?= Security::e($f_from) ?>"></div>
                <div class="form-group"><label class="form-label">Nama Pengirim</label>
                    <input type="text" name="smtp_name" class="form-control" value="<?= Security::e($f_name) ?>"></div>
            </div>
            <div class="form-group"><label class="form-label">Kirim Notifikasi Ke (pisahkan koma untuk banyak email)</label>
                <input type="text" name="kirim_ke" class="form-control" value="<?= Security::e($f_to) ?>" placeholder="lpm@kampus.ac.id, rektor@kampus.ac.id"></div>
            <button class="btn btn-gold">💾 Simpan Pengaturan</button>
        </form>

        <form method="POST" style="margin-top:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="test">
            <button class="btn btn-outline">📨 Kirim Email Test</button>
            <small class="text-muted" style="margin-left:10px;">Test memakai pengaturan yang terakhir DISIMPAN.</small>
        </form>
    </div>

    <div class="card" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;">
        <h3 style="color:#fff;margin-bottom:12px;">💡 Panduan Gmail</h3>
        <p style="font-size:13.5px;opacity:.9;line-height:1.7;">
            1. Aktifkan <strong>2-Step Verification</strong> di akun Google.<br>
            2. Buat <strong>App Password</strong> (16 karakter).<br>
            3. Host: <code>smtp.gmail.com</code>, Port: <code>587</code>.<br>
            4. Username = email Gmail, Password = <strong>App Password</strong>.<br><br>
            🔔 Email otomatis terkirim pada reminder tenggat (H-90…H-0).
        </p>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>