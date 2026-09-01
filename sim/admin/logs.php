<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();

$aksi = $_GET['aksi'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$q = "SELECT * FROM activity_log WHERE 1=1";
$params = [];
if ($aksi !== 'all') { $q .= " AND aksi = :a"; $params[':a'] = $aksi; }
if ($search) { $q .= " AND (nama_user LIKE :s OR detail LIKE :s2)"; $params[':s'] = "%$search%"; $params[':s2'] = "%$search%"; }
$q .= " ORDER BY created_at DESC LIMIT 100";
$stmt = $db->prepare($q);
$stmt->execute($params);
$logs = $stmt->fetchAll();

function aksiIcon($a) {
    return match (true) {
        str_contains($a, 'LOGIN') => '🔐',
        str_contains($a, 'UPLOAD') => '📤',
        str_contains($a, 'DELETE') || str_contains($a, 'HAPUS') => '🗑️',
        str_contains($a, 'UPDATE') || str_contains($a, 'UBAH') => '✏️',
        str_contains($a, 'AUDIT') => '🔍',
        str_contains($a, 'KOREKSI') => '🛠️',
        default => '📌'
    };
}

$simTitle = 'Audit Trail';
$activeMenu = 'logs';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div class="card" style="margin-bottom:24px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;">
        <input type="text" name="search" class="form-control" style="max-width:320px;" placeholder="🔍 Cari user / detail..." value="<?= Security::e($search) ?>">
        <select name="aksi" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="all">Semua Aktivitas</option>
            <option value="LOGIN" <?= $aksi==='LOGIN'?'selected':'' ?>>Login</option>
            <option value="UPLOAD" <?= $aksi==='UPLOAD'?'selected':'' ?>>Upload</option>
            <option value="UPDATE" <?= $aksi==='UPDATE'?'selected':'' ?>>Update</option>
            <option value="DELETE" <?= $aksi==='DELETE'?'selected':'' ?>>Delete</option>
            <option value="AUDIT" <?= $aksi==='AUDIT'?'selected':'' ?>>Audit</option>
        </select>
        <button type="submit" class="btn btn-primary">Terapkan</button>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Detail</th><th>IP</th></tr></thead>
        <tbody>
        <?php if (empty($logs)): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;" class="text-muted">Belum ada aktivitas tercatat.</td></tr>
        <?php endif; ?>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td style="white-space:nowrap;"><?= date('d M Y, H:i', strtotime($l['created_at'])) ?></td>
                <td><strong><?= Security::e($l['nama_user']) ?></strong></td>
                <td><span class="badge badge-a"><?= aksiIcon($l['aksi']) ?> <?= Security::e($l['aksi']) ?></span></td>
                <td style="max-width:340px;font-size:13px;"><?= Security::e($l['detail']) ?></td>
                <td><small class="text-muted"><?= Security::e($l['ip_address']) ?></small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>