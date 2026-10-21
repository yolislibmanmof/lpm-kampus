<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/core/Mailer.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

/* Ambil setting sebagai SCALAR — tidak ada array di bagian HTML */
try {
    $row = $db->query("SELECT * FROM mail_settings WHERE id = 1")->fetch();
    $row = is_array($row) ? $row : [];
} catch (Throwable $e) {
    $row = [];
}

$f_aktif = !empty($row['aktif']);
$f_host  = $row['smtp_host'] ?? 'smtp.gmail.com';
$f_port  = (int)($row['smtp_port'] ?? 587);
$f_user  = $row['smtp_user'] ?? '';
$f_pass  = $row['smtp_pass'] ?? '';
$f_from  = $row['smtp_from'] ?? '';
$f_name  = $row['smtp_name'] ?? 'SIM-Mutu LPM';
$f_to    = $row['kirim_ke'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= SIMPAN PENGATURAN =================
    if ($action === 'save') {
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $host  = trim($_POST['smtp_host'] ?? '');
        $port  = (int)($_POST['smtp_port'] ?? 587);
        $user  = trim($_POST['smtp_user'] ?? '');
        $pass  = $_POST['smtp_pass'] ?? ''; // JANGAN di-trim, App Password bisa punya spasi di akhir yang sengaja di-copy
        $from  = trim($_POST['smtp_from'] ?? '');
        $name  = trim($_POST['smtp_name'] ?? 'SIM-Mutu LPM');
        $to    = trim($_POST['kirim_ke'] ?? '');

        // Validasi dasar
        if ($aktif && (empty($host) || $port <= 0 || empty($user) || empty($from))) {
            $error = '❌ Host, Port, Username, dan Alamat Pengirim wajib diisi jika notifikasi diaktifkan.';
        } elseif ($aktif && !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $error = '❌ Format Alamat Pengirim tidak valid (contoh: lpm@kampus.ac.id).';
        } elseif ($to !== '' && !preg_match('/^[a-zA-Z0-9_,\s\.\-@]+$/', $to)) {
            $error = '❌ Format alamat email tujuan mengandung karakter tidak valid.';
        } else {
            try {
                $db->prepare("REPLACE INTO mail_settings (id, aktif, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_from, smtp_name, kirim_ke) VALUES (1,?,?,?,?,?,?,?,?)")
                   ->execute([$aktif, $host, $port, $user, $pass, $from, $name, $to]);
                
                Logger::log('UPDATE', 'Memperbarui konfigurasi SMTP');
                
                // Redirect untuk mencegah double submit (PRG Pattern)
                header('Location: /sim/admin/email.php?success=1');
                exit;
            } catch (PDOException $e) {
                $error = '❌ Gagal menyimpan pengaturan: ' . $e->getMessage();
            }
        }
    }

    // ================= UJI COBA EMAIL =================
    if ($action === 'test') {
        if (empty($f_to)) {
            $error = '❌ Isi dulu kolom "Kirim Notifikasi Ke" dan simpan pengaturan sebelum melakukan uji coba.';
        } else {
            try {
                $emails = array_map('trim', explode(',', $f_to));
                // Filter hanya email yang formatnya valid
                $validEmails = array_filter($emails, function($email) {
                    return filter_var($email, FILTER_VALIDATE_EMAIL);
                });

                if (empty($validEmails)) {
                    $error = '❌ Tidak ada alamat email yang valid di kolom "Kirim Notifikasi Ke".';
                } else {
                    $r = Mailer::send(
                        $validEmails, 
                        '✅ Uji Coba Email SIM-Mutu',
                        Mailer::template('Uji Coba Email', ['Selamat! Notifikasi email SIM-Mutu berfungsi dengan baik.'])
                    );
                    
                    if (!empty($r['success'])) {
                        $pesan = '✅ ' . $r['message'];
                        Logger::log('CREATE', 'Uji coba email berhasil dikirim ke: ' . implode(', ', $validEmails));
                    } else {
                        $error = '❌ Gagal mengirim: ' . ($r['message'] ?? 'Unknown error');
                    }
                }
            } catch (Throwable $e) {
                $error = '❌ Terjadi kesalahan sistem saat mengirim email: ' . $e->getMessage();
            }
        }
    }
}

// Cek jika redirect dari save berhasil
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $pesan = '✅ Pengaturan email berhasil disimpan.';
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
        <form method="POST" id="formSave">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="save">
            <label style="display:flex;gap:10px;align-items:center;font-size:14px;margin-bottom:18px;cursor:pointer;">
                <input type="checkbox" name="aktif" value="1" <?= $f_aktif ? 'checked' : '' ?>> <strong>Aktifkan notifikasi email</strong>
            </label>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;">
                <div class="form-group"><label class="form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control" value="<?= Security::e($f_host) ?>" placeholder="smtp.gmail.com"></div>
                <div class="form-group"><label class="form-label">Port</label>
                    <input type="number" name="smtp_port" class="form-control" value="<?= Security::e($f_port) ?>" placeholder="587"></div>
                <div class="form-group"><label class="form-label">Username / Email</label>
                    <input type="text" name="smtp_user" class="form-control" value="<?= Security::e($f_user) ?>" placeholder="email@kampus.ac.id"></div>
                <div class="form-group"><label class="form-label">Password / App Password</label>
                    <div style="position:relative;">
                        <input type="password" name="smtp_pass" id="smtp_pass" class="form-control" value="<?= Security::e($f_pass) ?>" placeholder="16 karakter app password">
                        <button type="button" onclick="togglePass()" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;" title="Lihat Password">👁️</button>
                    </div>
                </div>
                <div class="form-group"><label class="form-label">Alamat Pengirim</label>
                    <input type="email" name="smtp_from" class="form-control" value="<?= Security::e($f_from) ?>" placeholder="lpm@kampus.ac.id"></div>
                <div class="form-group"><label class="form-label">Nama Pengirim</label>
                    <input type="text" name="smtp_name" class="form-control" value="<?= Security::e($f_name) ?>" placeholder="SIM-Mutu LPM"></div>
            </div>
            <div class="form-group"><label class="form-label">Kirim Notifikasi Ke <small class="text-muted">(pisahkan koma untuk banyak email)</small></label>
                <input type="text" name="kirim_ke" class="form-control" value="<?= Security::e($f_to) ?>" placeholder="lpm@kampus.ac.id, rektor@kampus.ac.id"></div>
            <button type="submit" class="btn btn-gold">💾 Simpan Pengaturan</button>
        </form>

        <form method="POST" style="margin-top:14px; border-top: 1px solid var(--border); padding-top: 14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="test">
            <button type="submit" class="btn btn-outline" <?= empty($f_to) ? 'disabled title="Simpan alamat tujuan terlebih dahulu"' : '' ?>>📨 Kirim Email Test</button>
            <small class="text-muted" style="margin-left:10px;">Menguji menggunakan pengaturan yang <strong>terakhir disimpan</strong>.</small>
        </form>
    </div>

    <div class="card" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;">
        <h3 style="color:#fff;margin-bottom:12px;">💡 Panduan Gmail / SMTP</h3>
        <div style="font-size:13.5px;opacity:.95;line-height:1.7;">
            <p><strong>Untuk Gmail:</strong></p>
            <ol style="padding-left:20px;margin-bottom:16px;">
                <li>Aktifkan <strong>2-Step Verification</strong> di akun Google.</li>
                <li>Buat <strong>App Password</strong> (16 karakter) di pengaturan keamanan.</li>
                <li>Host: <code style="background:rgba(255,255,255,.2);padding:2px 6px;border-radius:4px;">smtp.gmail.com</code></li>
                <li>Port: <code style="background:rgba(255,255,255,.2);padding:2px 6px;border-radius:4px;">587</code> (TLS) atau <code style="background:rgba(255,255,255,.2);padding:2px 6px;border-radius:4px;">465</code> (SSL).</li>
                <li>Username = email Gmail, Password = <strong>App Password</strong> (bukan password login biasa).</li>
            </ol>
            <p style="border-top:1px solid rgba(255,255,255,.3);padding-top:12px;margin-top:12px;">
                🔔 <strong>Fitur Otomatis:</strong><br>
                Email pengingat tenggat (H-90, H-30, H-7) akan terkirim otomatis ke alamat di atas jika diaktifkan.
            </p>
        </div>
    </div>
</div>

<script>
function togglePass() {
    var x = document.getElementById("smtp_pass");
    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>