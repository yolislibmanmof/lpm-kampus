<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
require_once dirname(__DIR__, 2) . '/core/ApiAuth.php';

$simTitle = 'Kelola API Tokens';
$activeMenu = 'apitokens';
$pesan = '';

// ===== GENERATE TOKEN BARU =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    Security::verifyCsrf();
    $action = $_POST['action'];

    if ($action === 'create') {
        $nama = trim($_POST['nama_aplikasi'] ?? '');
        $desc = trim($_POST['deskripsi'] ?? '');
        $days = max(30, (int)($_POST['days_valid'] ?? 365));
        $perms = array_filter($_POST['permissions'] ?? []);
        if (!$nama) {
            $pesan = '❌ Nama aplikasi wajib diisi.';
        } else {
            $tokenData = ApiAuth::generateToken(Auth::id(), $nama, $desc ?: null, $perms ?: null, $days);
            Logger::log('CREATE', "Membuat API token: $nama");
            $pesan = "✅ Token berhasil dibuat!<br><strong>Token:</strong> <code style='user-select:all;background:#FEF3C7;padding:4px 8px;border-radius:6px;font-size:12px;'>{$tokenData['token']}</code><br><small class='text-muted'>Simpan token ini sekarang. Anda tidak akan bisa melihatnya lagi.</small><br><small>Berlaku s.d. <strong>{$tokenData['expires_at']}</strong></small>";
        }
    } elseif ($action === 'revoke') {
        $id = (int)($_POST['id_token'] ?? 0);
        $db->prepare("UPDATE api_tokens SET is_active = 0 WHERE id_token = ? AND id_user = ?")
           ->execute([$id, Auth::id()]);
        Logger::log('DELETE', "Revoked API token #$id");
        $pesan = '✅ Token berhasil dinonaktifkan.';
    }
}

// ===== LOAD DATA =====
$tokens = $db->query("SELECT t.*, u.nama_lengkap FROM api_tokens t
                     JOIN users u ON t.id_user = u.id_user
                     ORDER BY t.created_at DESC")->fetchAll();

$webhooks = $db->query("SELECT * FROM webhook_secrets ORDER BY nama")->fetchAll();

$recentLogs = $db->query("SELECT l.*, t.nama_aplikasi FROM api_audit_log l
                          LEFT JOIN api_tokens t ON l.id_token = t.id_token
                          ORDER BY l.created_at DESC LIMIT 20")->fetchAll();

$availablePerms = [
    'dashboard.read' => 'Baca dashboard',
    'sync.read' => 'Baca status sinkronisasi',
    'sync.trigger' => 'Trigger sinkronisasi manual',
    'risk.read' => 'Baca risiko',
    'risk.write' => 'Tambah/edit risiko',
    '*' => 'Semua permission (super)'
];

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?>
    <div class="alert <?= strpos($pesan, '✅') === 0 ? 'alert-success' : 'alert-danger' ?>"><?= $pesan ?></div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
        <h2 style="margin:0;">🔑 Kelola API Tokens</h2>
        <p class="text-muted" style="margin:4px 0 0;font-size:13px;">Buat & kelola token akses API untuk mobile app dan integrasi eksternal.</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalCreate')">➕ Buat Token Baru</button>
</div>

<!-- STATISTIK -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
    <div class="stat-card"><div class="stat-icon blue">🔑</div><div><h3 style="font-size:24px;"><?= count($tokens) ?></h3><p class="text-muted">Total Token</p></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><h3 style="font-size:24px;"><?= count(array_filter($tokens, fn($t) => $t['is_active'])) ?></h3><p class="text-muted">Aktif</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">🔗</div><div><h3 style="font-size:24px;"><?= count($webhooks) ?></h3><p class="text-muted">Webhook Secrets</p></div></div>
</div>

<!-- DAFTAR TOKEN -->
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">📋 Daftar API Tokens</h3>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Aplikasi</th>
                    <th>Pemilik</th>
                    <th>Permissions</th>
                    <th>Terakhir Dipakai</th>
                    <th>Expired</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($tokens)): ?>
                <tr><td colspan="7" style="text-align:center;padding:30px;" class="text-muted">Belum ada token.</td></tr>
            <?php else: foreach ($tokens as $t):
                $perms = $t['permissions'] ? json_decode($t['permissions'], true) : [];
                $isExpired = $t['expires_at'] && strtotime($t['expires_at']) < time();
                $isActive = $t['is_active'] && !$isExpired;
            ?>
                <tr>
                    <td>
                        <strong><?= Security::e($t['nama_aplikasi']) ?></strong><br>
                        <small class="text-muted"><?= Security::e($t['deskripsi'] ?? '—') ?></small>
                    </td>
                    <td><small><?= Security::e($t['nama_lengkap']) ?></small></td>
                    <td>
                        <?php if (empty($perms)): ?>
                            <span class="text-muted" style="font-size:11px;">Semua</span>
                        <?php else: ?>
                            <?php foreach (array_slice($perms, 0, 3) as $p): ?>
                                <span class="badge badge-b" style="font-size:10px;"><?= Security::e($p) ?></span>
                            <?php endforeach; ?>
                            <?php if (count($perms) > 3): ?>
                                <span class="text-muted" style="font-size:11px;">+<?= count($perms) - 3 ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><small><?= $t['last_used'] ? date('d/m/y H:i', strtotime($t['last_used'])) : '—' ?></small></td>
                    <td><small><?= $t['expires_at'] ? date('d/m/Y', strtotime($t['expires_at'])) : '—' ?></small></td>
                    <td>
                        <?php if ($isActive): ?><span class="badge badge-success">Aktif</span>
                        <?php elseif ($isExpired): ?><span class="badge badge-warning">Expired</span>
                        <?php else: ?><span class="badge badge-danger">Revoked</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isActive): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Nonaktifkan token ini? Aksi tidak bisa dibatalkan.');">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="revoke">
                            <input type="hidden" name="id_token" value="<?= (int)$t['id_token'] ?>">
                            <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);border-color:var(--danger);">🔒 Revoke</button>
                        </form>
                        <?php else: ?><span class="text-muted" style="font-size:11px;">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- WEBHOOK SECRETS -->
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">🔗 Webhook Secrets</h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:16px;">Gunakan secret key ini untuk verifikasi HMAC signature saat mengirim event ke endpoint <code>/sim/api/webhook.php</code>.</p>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Nama Source</th><th>Secret Key</th><th>Terakhir Trigger</th></tr>
            </thead>
            <tbody>
            <?php foreach ($webhooks as $w): ?>
                <tr>
                    <td><strong><?= Security::e($w['nama']) ?></strong></td>
                    <td><code style="font-size:11px;user-select:all;background:var(--bg-light);padding:4px 8px;border-radius:4px;"><?= Security::e($w['secret_key']) ?></code></td>
                    <td><small><?= $w['last_triggered'] ? date('d/m/y H:i', strtotime($w['last_triggered'])) : '—' ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- AUDIT LOG -->
<div class="card">
    <h3 style="margin-bottom:16px;">📜 Audit Log API (20 Terakhir)</h3>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Waktu</th><th>Endpoint</th><th>Method</th><th>Aplikasi</th><th>Status</th><th>Waktu Respon</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recentLogs as $l): ?>
                <tr>
                    <td><small><?= date('d/m/y H:i:s', strtotime($l['created_at'])) ?></small></td>
                    <td><code><?= Security::e($l['endpoint']) ?></code></td>
                    <td><span class="badge badge-b"><?= $l['method'] ?></span></td>
                    <td><small><?= Security::e($l['nama_aplikasi'] ?? '—') ?></small></td>
                    <td>
                        <span class="badge <?= $l['response_status'] < 400 ? 'badge-success' : 'badge-danger' ?>">
                            <?= (int)$l['response_status'] ?>
                        </span>
                    </td>
                    <td><small><?= (int)$l['response_time_ms'] ?>ms</small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CREATE TOKEN -->
<div id="modalCreate" class="modal-overlay">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3>➕ Buat API Token Baru</h3>
            <button onclick="closeModal('modalCreate')" class="modal-close">×</button>
        </div>
        <form method="POST" style="padding:24px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Nama Aplikasi *</label>
                <input type="text" name="nama_aplikasi" class="form-control" required placeholder="Contoh: Mobile App LPM, SI-Kampus">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Deskripsi</label>
                <input type="text" name="deskripsi" class="form-control" placeholder="Opsional: keterangan tambahan">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Masa Berlaku (hari)</label>
                <select name="days_valid" class="form-control">
                    <option value="90">90 hari (3 bulan)</option>
                    <option value="180">180 hari (6 bulan)</option>
                    <option value="365" selected>365 hari (1 tahun)</option>
                    <option value="730">730 hari (2 tahun)</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Permissions (kosongkan = semua)</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <?php foreach ($availablePerms as $k => $v): ?>
                        <label style="display:flex;align-items:center;gap:6px;font-size:12px;padding:6px;background:var(--bg-light);border-radius:6px;cursor:pointer;">
                            <input type="checkbox" name="permissions[]" value="<?= $k ?>">
                            <span><strong><?= $k ?></strong><br><small class="text-muted"><?= $v ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalCreate')">Batal</button>
                <button type="submit" class="btn btn-primary">🔑 Generate Token</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
document.querySelectorAll('.modal-overlay').forEach(function(ov) {
    ov.addEventListener('click', function(e) { if (e.target === ov) ov.style.display = 'none'; });
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>