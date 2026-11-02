<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();

$tahunFilter = (int)($_GET['tahun'] ?? date('Y'));

try {
    $asalMap = [];
    foreach ($db->query("SELECT id_prodi, nama_prodi FROM prodi")->fetchAll() as $pp) {
        $asalMap[$pp['id_prodi']] = $pp['nama_prodi'];
    }

    $q = "SELECT pa.*, u.nama_lengkap auditor, u.id_prodi prodi_asal, p.nama_prodi prodi_audit, j.tahun_ami
        FROM penugasan_audit pa
        JOIN users u ON pa.id_auditor = u.id_user
        LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
        LEFT JOIN jadwal_ami j ON pa.id_jadwal = j.id_jadwal
        WHERE j.tahun_ami = ? OR j.tahun_ami IS NULL
        ORDER BY (u.id_prodi = pa.id_prodi AND u.id_prodi IS NOT NULL AND pa.id_prodi IS NOT NULL) DESC, 
                 pa.tanggal_audit DESC";
    
    $stmt = $db->prepare($q);
    $stmt->execute([$tahunFilter]);
    $tugas = $stmt->fetchAll();

    $nKonflik = 0;
    $nAman = 0;
    foreach ($tugas as $t) {
        if ($t['prodi_asal'] && $t['prodi_asal'] == $t['id_prodi']) {
            $nKonflik++;
        } else {
            $nAman++;
        }
    }
} catch (PDOException $e) {
    $tugas = []; $nKonflik = 0; $nAman = 0;
    $error = '❌ Gagal memuat data: ' . $e->getMessage();
}

$tahunList = [];
try {
    $tahunList = $db->query("SELECT DISTINCT tahun_ami FROM jadwal_ami WHERE tahun_ami IS NOT NULL ORDER BY tahun_ami DESC")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tahunList)) $tahunList = [date('Y')];
} catch (PDOException $e) {
    $tahunList = [date('Y')];
}

$simTitle = 'Cek Konflik Kepentingan';
$activeMenu = 'konflik';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    .row-konflik { background: rgba(239, 68, 68, 0.08) !important; transition: background 0.3s; }
    .row-konflik:hover { background: rgba(239, 68, 68, 0.12) !important; }
    .badge-konflik { background: rgba(239, 68, 68, 0.15); color: var(--danger); font-weight: 700; }
    .badge-aman { background: rgba(16, 185, 129, 0.15); color: var(--success); font-weight: 700; }
    .badge-tanya { background: rgba(245, 158, 11, 0.15); color: var(--warning); font-weight: 700; }
    
    @media print {
        .no-print { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    }
</style>

<?php if (!empty($error)): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div class="no-print" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card">
        <div class="stat-icon blue">📋</div>
        <div><h3 style="font-size:28px;"><?= count($tugas) ?></h3><p class="text-muted">Total Penugasan (<?= $tahunFilter ?>)</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $nKonflik > 0 ? 'red' : 'green' ?>"><?= $nKonflik > 0 ? '⚠️' : '✅' ?></div>
        <div><h3 style="font-size:28px;"><?= $nKonflik ?></h3><p class="text-muted">Konflik Terdeteksi</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">🛡️</div>
        <div><h3 style="font-size:28px;"><?= $nAman ?></h3><p class="text-muted">Penugasan Independen</p></div>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:20px;" class="no-print">
        <div>
            <h3 style="margin:0;">⚖️ Independensi Penugasan Audit</h3>
            <p class="text-muted" style="font-size:13px;margin:4px 0 0;">Sistem membandingkan <strong>prodi asal auditor</strong> dengan <strong>prodi yang diaudit</strong>. Auditor tidak boleh mengaudit prodi sendiri.</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <form method="GET" style="display:flex;gap:8px;">
                <select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
                    <?php foreach ($tahunList as $th): ?>
                        <option value="<?= $th ?>" <?= $tahunFilter == $th ? 'selected' : '' ?>>Tahun <?= $th ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <button onclick="window.print()" class="btn btn-outline" style="padding:8px 16px;font-size:13px;">🖨️ Cetak</button>
            <a href="/sim/admin/penugasan.php" class="btn btn-primary" style="padding:8px 16px;font-size:13px;">⚙️ Kelola Penugasan</a>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Auditor</th>
                    <th>Prodi Asal</th>
                    <th>Prodi Diaudit</th>
                    <th>Tahun AMI</th>
                    <th>Status Independensi</th>
                    <th class="no-print" style="width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($tugas)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;">
                        <div style="font-size:40px;margin-bottom:10px;">📭</div>
                        <p class="text-muted">Belum ada data penugasan audit untuk tahun ini.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($tugas as $t):
                    $konflik = $t['prodi_asal'] && $t['prodi_asal'] == $t['id_prodi'];
                ?>
                    <tr class="<?= $konflik ? 'row-konflik' : '' ?>">
                        <td>
                            <strong><?= Security::e($t['auditor']) ?></strong><br>
                            <small class="text-muted">ID: <?= (int)$t['id_auditor'] ?></small>
                        </td>
                        <td>
                            <?= $t['prodi_asal'] ? Security::e($asalMap[$t['prodi_asal']] ?? '—') : '<span class="text-muted">Belum diatur</span>' ?>
                        </td>
                        <td><strong><?= Security::e($t['prodi_audit'] ?? 'Umum / Lintas Prodi') ?></strong></td>
                        <td><?= Security::e((string)($t['tahun_ami'] ?? '—')) ?></td>
                        <td>
                            <?php if ($konflik): ?>
                                <span class="badge badge-konflik">⚠️ KONFLIK</span>
                            <?php elseif (!$t['prodi_asal']): ?>
                                <span class="badge badge-tanya">❓ Prodi Asal Kosong</span>
                            <?php else: ?>
                                <span class="badge badge-aman">✅ Independen</span>
                            <?php endif; ?>
                        </td>
                        <td class="no-print">
                            <?php if ($konflik): ?>
                                <a href="/sim/admin/penugasan.php?fix_auditor=<?= $t['id_auditor'] ?>" class="btn btn-sm btn-outline" style="color:var(--danger);border-color:var(--danger);">🔄 Ganti</a>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div style="background:var(--bg-light);border-radius:10px;padding:14px;margin-top:16px;display:flex;gap:10px;align-items:flex-start;" class="no-print">
        <span style="font-size:18px;">💡</span>
        <p class="text-muted" style="font-size:13px;margin:0;line-height:1.5;">
            <strong>Tips:</strong> Pastikan kolom <strong>"Prodi"</strong> pada akun auditor (Role 4) sudah diisi dengan benar di menu 👥 <a href="/sim/admin/users.php" style="color:var(--primary);text-decoration:underline;">Manajemen Pengguna</a>.
        </p>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>