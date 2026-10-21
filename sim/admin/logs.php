<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();

$aksi     = $_GET['aksi'] ?? 'all';
$search   = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 50;
$offset   = ($page - 1) * $limit;

/* ===== Export CSV (Bukti Audit) ===== */
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="audit_trail_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($output, ['Waktu', 'Pengguna', 'Aktivitas', 'Detail', 'IP Address'], ';');
    
    $where = "1=1"; $params = [];
    if ($aksi !== 'all') { $where .= " AND aksi = ?"; $params[] = $aksi; }
    if ($search) { $where .= " AND (nama_user LIKE ? OR detail LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if ($dateFrom) { $where .= " AND DATE(created_at) >= ?"; $params[] = $dateFrom; }
    if ($dateTo) { $where .= " AND DATE(created_at) <= ?"; $params[] = $dateTo; }
    
    $stmt = $db->prepare("SELECT * FROM activity_log WHERE $where ORDER BY created_at DESC");
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [$row['created_at'], $row['nama_user'], $row['aksi'], $row['detail'], $row['ip_address']], ';');
    }
    fclose($output);
    exit;
}

/* ===== Bangun Query & Paginasi ===== */
$where = "1=1"; $params = [];
if ($aksi !== 'all') { $where .= " AND aksi = ?"; $params[] = $aksi; }
if ($search) { $where .= " AND (nama_user LIKE ? OR detail LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($dateFrom) { $where .= " AND DATE(created_at) >= ?"; $params[] = $dateFrom; }
if ($dateTo) { $where .= " AND DATE(created_at) <= ?"; $params[] = $dateTo; }

$totalStmt = $db->prepare("SELECT COUNT(*) FROM activity_log WHERE $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$q = "SELECT * FROM activity_log WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($q);
$stmt->execute($params);
$logs = $stmt->fetchAll();

/* Helper: Badge berwarna berdasarkan jenis aktivitas */
function aksiBadge(string $a): string {
    $icon = '📌'; $bg = 'var(--bg-light)'; $color = 'var(--text-dark)';
    if (str_contains($a, 'LOGIN'))      { $icon = '🔐'; $bg = '#DBEAFE'; $color = '#1E40AF'; }
    elseif (str_contains($a, 'CREATE')) { $icon = '🆕'; $bg = '#D1FAE5'; $color = '#065F46'; }
    elseif (str_contains($a, 'UPLOAD')) { $icon = '📤'; $bg = '#D1FAE5'; $color = '#065F46'; }
    elseif (str_contains($a, 'DELETE')) { $icon = '🗑️'; $bg = '#FEE2E2'; $color = '#991B1B'; }
    elseif (str_contains($a, 'UPDATE')) { $icon = '✏️'; $bg = '#FEF3C7'; $color = '#92400E'; }
    elseif (str_contains($a, 'AUDIT'))  { $icon = '🔍'; $bg = '#E0E7FF'; $color = '#3730A3'; }
    
    return '<span class="badge" style="background:' . $bg . ';color:' . $color . ';font-weight:600;padding:4px 10px;">' . $icon . ' ' . htmlspecialchars($a) . '</span>';
}

$simTitle = 'Audit Trail';
$activeMenu = 'logs';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">🔍 Filter & Pencarian Log</h3>
    <form method="GET" style="display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr auto;gap:12px;align-items:end;">
        <div class="form-group" style="margin:0;"><label class="form-label">Cari User / Detail</label>
            <input type="text" name="search" class="form-control" placeholder="cth: Nama user atau kata kunci..." value="<?= Security::e($search) ?>"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Jenis Aktivitas</label>
            <select name="aksi" class="form-control">
                <option value="all">Semua Aktivitas</option>
                <option value="LOGIN" <?= $aksi==='LOGIN'?'selected':'' ?>>Login</option>
                <option value="CREATE" <?= $aksi==='CREATE'?'selected':'' ?>>Create</option>
                <option value="UPDATE" <?= $aksi==='UPDATE'?'selected':'' ?>>Update</option>
                <option value="DELETE" <?= $aksi==='DELETE'?'selected':'' ?>>Delete</option>
                <option value="UPLOAD" <?= $aksi==='UPLOAD'?'selected':'' ?>>Upload</option>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Dari Tanggal</label>
            <input type="date" name="date_from" class="form-control" value="<?= Security::e($dateFrom) ?>"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Sampai Tanggal</label>
            <input type="date" name="date_to" class="form-control" value="<?= Security::e($dateTo) ?>"></div>
        <button type="submit" class="btn btn-primary" style="height:42px;">🔍 Terapkan</button>
    </form>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <p class="text-muted" style="margin:0;font-size:13px;">Menampilkan <strong><?= count($logs) ?></strong> dari <strong><?= number_format($total) ?></strong> total log.</p>
    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>" class="btn btn-outline">📤 Export CSV (Bukti Audit)</a>
</div>

<div class="table-wrapper">
    <table>
        <thead><tr><th style="width:15%;">Waktu</th><th style="width:15%;">Pengguna</th><th style="width:15%;">Aktivitas</th><th>Detail</th><th style="width:10%;">IP</th></tr></thead>
        <tbody>
        <?php if (empty($logs)): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;" class="text-muted">Tidak ada aktivitas yang cocok dengan filter.</td></tr>
        <?php else: ?>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td style="white-space:nowrap;font-size:13px;"><?= date('d M Y, H:i', strtotime($l['created_at'])) ?></td>
                    <td><strong><?= Security::e($l['nama_user']) ?></strong></td>
                    <td><?= aksiBadge($l['aksi']) ?></td>
                    <td style="max-width:400px;font-size:13px;line-height:1.5;"><?= Security::e($l['detail']) ?></td>
                    <td><code style="font-size:11px;"><?= Security::e($l['ip_address']) ?></code></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div style="display:flex;justify-content:center;gap:8px;margin-top:24px;flex-wrap:wrap;">
    <?php 
    $queryParams = $_GET;
    unset($queryParams['page']);
    $baseUrl = '?' . http_build_query($queryParams) . '&page=';
    ?>
    <?php if ($page > 1): ?><a href="<?= $baseUrl . ($page - 1) ?>" class="btn btn-outline" style="padding:6px 14px;">← Sebelumnya</a><?php endif; ?>
    
    <?php 
    // Tampilkan maksimal 5 halaman di sekitar halaman saat ini
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    if ($start > 1) echo '<span style="padding:6px 10px;">...</span>';
    for ($i = $start; $i <= $end; $i++): 
    ?>
        <a href="<?= $baseUrl . $i ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" style="padding:6px 14px;min-width:40px;text-align:center;"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($end < $totalPages) echo '<span style="padding:6px 10px;">...</span>'; ?>
    
    <?php if ($page < $totalPages): ?><a href="<?= $baseUrl . ($page + 1) ?>" class="btn btn-outline" style="padding:6px 14px;">Selanjutnya →</a><?php endif; ?>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>