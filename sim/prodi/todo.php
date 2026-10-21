<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();
$pesan = '';

$me = $db->prepare("SELECT id_prodi FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$prodiId = $me->fetch()['id_prodi'] ?? null;

$simTitle = 'To-Do Akreditasi';
$activeMenu = 'todo';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

if (!$prodiId): ?>
    <div class="alert alert-danger">️ Akun Anda belum terhubung ke Program Studi. Hubungi Admin LPM.</div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; endif;

$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO tugas_akreditasi (id_prodi, judul, kategori, pic, tenggat) VALUES (?,?,?,?,?)")
           ->execute([$prodiId, trim($_POST['judul']), trim($_POST['kategori']), trim($_POST['pic']), $_POST['tenggat'] ?: null]);
        $pesan = '✅ Tugas berhasil ditambahkan.';
    }
    if ($action === 'status') {
        $db->prepare("UPDATE tugas_akreditasi SET status = ? WHERE id_tugas = ? AND id_prodi = ?")
           ->execute([$_POST['status'], (int)$_POST['id_tugas'], $prodiId]);
        $pesan = '✅ Status tugas diperbarui.';
    }
    if ($action === 'del') {
        $db->prepare("DELETE FROM tugas_akreditasi WHERE id_tugas = ? AND id_prodi = ?")->execute([(int)$_POST['id_tugas'], $prodiId]);
        $pesan = '✅ Tugas berhasil dihapus.';
    }
}

$list = $db->prepare("SELECT * FROM tugas_akreditasi WHERE id_prodi = ? ORDER BY FIELD(status, 'Belum Mulai', 'Berjalan', 'Selesai') ASC, tenggat ASC");
$list->execute([$prodiId]);
$list = $list->fetchAll();

$total = count($list);
$selesai = count(array_filter($list, fn($t) => $t['status'] === 'Selesai'));
$berjalan = count(array_filter($list, fn($t) => $t['status'] === 'Berjalan'));
$telat = count(array_filter($list, fn($t) => $t['status'] !== 'Selesai' && $t['tenggat'] && $t['tenggat'] < $today));
$progres = $total > 0 ? round($selesai / $total * 100) : 0;

// Kategori colors
$katColors = [
    'LED' => '#3B82F6', 'LKPS' => '#8B5CF6', 'SK & Dokumen' => '#64748B'
];
foreach (range(1, 9) as $i) $katColors["Borang K$i"] = '#F59E0B';
?>

<style>
    .todo-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg); padding: 32px 36px; color: #fff;
        margin-bottom: 28px; position: relative; overflow: hidden;
    }
    .todo-header::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
        right: -80px; top: -100px; animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .todo-header h2 { font-size: 24px; margin-bottom: 8px; position: relative; font-weight: 800; }
    .todo-header p { color: rgba(255,255,255,.85); position: relative; font-size: 14px; margin-bottom: 16px; }
    
    .prog-bar {
        background: rgba(255,255,255,.2); border-radius: 50px; height: 14px; overflow: hidden; position: relative;
    }
    .prog-fill {
        height: 100%; background: linear-gradient(90deg, var(--accent), var(--accent-light));
        border-radius: 50px; transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .prog-fill::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent);
        animation: shimmer 2s infinite;
    }
    @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
    
    .stat-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-top: 20px;
    }
    .stat-item {
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px); border-radius: 12px;
        padding: 16px; text-align: center; border: 1px solid rgba(255,255,255,.15);
    }
    .stat-item h4 { font-size: 28px; margin: 0 0 4px; font-weight: 800; }
    .stat-item small { font-size: 12px; opacity: .8; text-transform: uppercase; letter-spacing: 1px; }
    
    /* DIPERBAIKI: Gunakan CSS variables */
    .add-task-card {
        background: var(--bg-card);
        border-radius: 16px; padding: 24px; margin-bottom: 28px;
        box-shadow: 0 4px 12px rgba(0,0,0,.05); border: 2px solid var(--border);
    }
    .add-task-card h3 { margin: 0 0 16px; font-size: 18px; display: flex; align-items: center; gap: 10px; color: var(--text-dark); }
    
    .task-table th { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; }
    .task-table td { border-color: var(--border); }
    .task-title { font-weight: 700; color: var(--text-dark); font-size: 14px; }
    .kat-badge {
        display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;
        color: #fff; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-select {
        padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); font-size: 12px; font-weight: 600;
        background: var(--bg-card); color: var(--text-dark); cursor: pointer; transition: all 0.3s;
    }
    .status-select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(201,162,39,.15); }
    
    .overdue-badge {
        display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px;
        background: #FEE2E2; color: #991B1B; border-radius: 6px; font-size: 11px; font-weight: 700; margin-top: 4px;
    }

    /* DARK MODE OVERRIDES */
    body.dark .add-task-card {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .task-table th {
        background: rgba(255,255,255,0.05);
    }
    body.dark .status-select {
        background: #0D1526;
        border-color: var(--border);
        color: var(--text-dark);
    }
</style>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div class="todo-header">
    <h2>✅ Papan Tugas Persiapan Akreditasi</h2>
    <p>Petakan, pantau, dan selesaikan setiap langkah menuju akreditasi unggul.</p>
    <div class="prog-bar">
        <div class="prog-fill" style="width: <?= $progres ?>%;"></div>
    </div>
    <div class="stat-grid">
        <div class="stat-item"><h4><?= $total ?></h4><small>Total Tugas</small></div>
        <div class="stat-item"><h4><?= $selesai ?></h4><small>Selesai</small></div>
        <div class="stat-item"><h4><?= $berjalan ?></h4><small>Sedang Berjalan</small></div>
        <div class="stat-item" style="background: <?= $telat > 0 ? 'rgba(239,68,68,.2)' : 'rgba(255,255,255,.1)' ?>">
            <h4 style="color: <?= $telat > 0 ? '#FCA5A5' : '#fff' ?>"><?= $telat ?></h4>
            <small>Terlambat</small>
        </div>
    </div>
</div>

<div class="add-task-card">
    <h3>➕ Tambah Tugas Baru</h3>
    <form method="POST" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:16px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Uraian Tugas</label>
            <input type="text" name="judul" class="form-control" placeholder="cth: Finalisasi LED Kriteria 6" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Kategori</label>
            <select name="kategori" class="form-control">
                <option>LED</option><option>LKPS</option>
                <?php foreach (range(1, 9) as $i): ?><option>Borang K<?= $i ?></option><?php endforeach; ?>
                <option>SK & Dokumen</option><option>Lainnya</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">PIC</label>
            <input type="text" name="pic" class="form-control" placeholder="cth: Bu Ana">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tenggat Waktu</label>
            <input type="date" name="tenggat" class="form-control">
        </div>
        <button class="btn btn-gold" style="padding:12px 20px;">💾 Simpan</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">📋 Daftar Tugas Akreditasi</h3>
    <div class="table-wrapper">
        <table class="task-table">
            <thead>
                <tr>
                    <th style="width:35%">Uraian Tugas</th>
                    <th style="width:15%">Kategori</th>
                    <th style="width:15%">PIC</th>
                    <th style="width:15%">Tenggat</th>
                    <th style="width:15%">Status</th>
                    <th style="width:5%"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;">
                        <div style="font-size:48px;margin-bottom:12px;opacity:0.5;">📋</div>
                        <p class="text-muted">Belum ada tugas. Mulai petakan pekerjaan borang Anda!</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($list as $t):
                    $overdue = $t['status'] !== 'Selesai' && $t['tenggat'] && $t['tenggat'] < $today;
                    $katColor = $katColors[$t['kategori']] ?? '#64748B';
                ?>
                <tr style="<?= $t['status'] === 'Selesai' ? 'opacity:0.6;' : '' ?>">
                    <td>
                        <div class="task-title"><?= Security::e($t['judul']) ?></div>
                    </td>
                    <td>
                        <span class="kat-badge" style="background:<?= $katColor ?>"><?= Security::e($t['kategori']) ?></span>
                    </td>
                    <td><?= Security::e($t['pic']) ?: '<span class="text-muted">—</span>' ?></td>
                    <td>
                        <?= $t['tenggat'] ? date('d M Y', strtotime($t['tenggat'])) : '<span class="text-muted">Fleksibel</span>' ?>
                        <?php if ($overdue): ?>
                            <br><span class="overdue-badge">⏰ Terlambat</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="status">
                            <select name="status" class="status-select" onchange="this.form.submit()" style="color: <?= $t['status'] === 'Selesai' ? '#059669' : ($t['status'] === 'Berjalan' ? '#D97706' : '#64748B') ?>">
                                <?php foreach (['Belum Mulai','Berjalan','Selesai'] as $s): ?>
                                    <option <?= $t['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus tugas ini?');" style="margin:0;">
                            <input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="del">
                            <button class="btn btn-outline" style="padding:6px 10px;font-size:14px;color:var(--danger);border:none;background:transparent;" title="Hapus">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Animate progress bar on load
window.addEventListener('load', function() {
    const progFill = document.querySelector('.prog-fill');
    if (progFill) {
        const targetWidth = progFill.style.width;
        progFill.style.width = '0%';
        setTimeout(() => { progFill.style.width = targetWidth; }, 100);
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>