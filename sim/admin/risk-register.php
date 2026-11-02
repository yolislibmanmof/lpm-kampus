<?php
require_once __DIR__ . '/../../config/config.php';
Auth::requireRole([1, 2]); // Admin dan Pimpinan
require_once __DIR__ . '/../../core/RiskManager.php';

$simTitle = 'Manajemen Risiko (Risk Register)';
$activeMenu = 'risk';

// Handle POST Tambah Risiko
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_risk') {
    Security::verifyCsrf();
    $data = [
        'id_prodi' => !empty($_POST['id_prodi']) ? (int)$_POST['id_prodi'] : null,
        'id_user_pic' => (int)$_POST['id_user_pic'],
        'judul_risiko' => trim($_POST['judul_risiko']),
        'deskripsi' => trim($_POST['deskripsi']),
        'dampak' => (int)$_POST['dampak'],
        'probabilitas' => (int)$_POST['probabilitas'],
        'rencana_mitigasi' => trim($_POST['rencana_mitigasi']),
        'status' => $_POST['status'] ?? 'Teridentifikasi',
        'tanggal_identifikasi' => $_POST['tanggal_identifikasi'] ?: date('Y-m-d'),
        'tanggal_review' => $_POST['tanggal_review'] ?: null
    ];
    
    if (RiskManager::addRisk($data)) {
        $successMsg = "✅ Risiko berhasil ditambahkan ke dalam register.";
    } else {
        $errorMsg = "❌ Gagal menambahkan risiko.";
    }
}

// Filter Status
$statusFilter = $_GET['status'] ?? '';
$risks = RiskManager::getRisks(null, $statusFilter);
$stats = RiskManager::getStats();

// Ambil daftar user untuk dropdown PIC
$db = Database::getInstance();
$users = $db->query("SELECT id_user, nama_lengkap, id_prodi FROM users WHERE is_active = 1 ORDER BY nama_lengkap ASC")->fetchAll();
$prodiList = $db->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi ASC")->fetchAll();

// Heatmap Matrix
$heatmap = [];
for ($d = 5; $d >= 1; $d--) {
    for ($p = 1; $p <= 5; $p++) {
        $level = RiskManager::calculateLevel($d, $p);
        $count = 0;
        foreach ($risks as $r) {
            if ($r['dampak'] == $d && $r['probabilitas'] == $p && $r['status'] != 'Termitigasi') {
                $count++;
            }
        }
        $heatmap[$d][$p] = ['count' => $count, 'color' => $level['color'], 'bg' => $level['bg']];
    }
}

require_once __DIR__ . '/../../includes/header-sim.php';
?>

<?php if (!empty($successMsg)): ?><div class="alert alert-success"><?= $successMsg ?></div><?php endif; ?>
<?php if (!empty($errorMsg)): ?><div class="alert alert-danger"><?= $errorMsg ?></div><?php endif; ?>

<style>
    .risk-heatmap { display: grid; grid-template-columns: 50px repeat(5, 1fr); gap: 6px; margin: 20px 0; }
    .risk-cell { 
        padding: 16px 8px; border-radius: 8px; text-align: center; font-weight: 700; font-size: 14px; 
        display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 60px; 
        transition: .2s; border: 1px solid rgba(0,0,0,0.05);
    }
    body.dark .risk-cell { border-color: rgba(255,255,255,0.1); }
    .risk-cell:hover { transform: scale(1.05); z-index: 2; box-shadow: 0 4px 12px rgba(0,0,0,.15); }
    .risk-cell .count { font-size: 24px; line-height: 1; }
    .risk-cell .label { font-size: 11px; opacity: .8; font-weight: 500; }
    .axis-label { display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: var(--text-muted); }
    .axis-y { writing-mode: vertical-rl; transform: rotate(180deg); }
    
    .risk-badge { padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700; }
    .status-teridentifikasi { background: rgba(59,130,246,.15); color: #2563EB; }
    body.dark .status-teridentifikasi { color: #60A5FA; }
    
    .status-dalam { background: rgba(245,158,11,.15); color: #B45309; }
    body.dark .status-dalam { color: #FBBF24; }
    
    .status-termitigasi { background: rgba(16,185,129,.15); color: #059669; }
    body.dark .status-termitigasi { color: #34D399; }
    
    .status-diterima { background: rgba(107,114,128,.15); color: #4B5563; }
    body.dark .status-diterima { color: #9CA3AF; }

    /* Modal Styles (Matching Reference) */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(4,10,20,.7); backdrop-filter:blur(6px); z-index:5000; align-items:center; justify-content:center; padding:20px; }
    .modal-content { background:var(--bg-card); border-radius:20px; box-shadow:0 25px 60px rgba(0,0,0,.3); border:1px solid var(--border); max-height:90vh; overflow-y:auto; width: 100%; max-width: 650px; }
    .modal-header { display:flex; justify-content:space-between; align-items:center; padding:20px 24px; border-bottom:1px solid var(--border); position:sticky; top:0; background:var(--bg-card); border-radius:20px 20px 0 0; z-index: 10; }
    .modal-header h3 { margin:0; font-size:18px; color:var(--text-dark); }
    .modal-close { background:none; border:none; font-size:28px; cursor:pointer; color:var(--text-muted); line-height:1; }
    .modal-close:hover { color:var(--danger); }
    .form-group { margin-bottom: 14px; }
    .form-label { display: block; font-weight: 700; margin-bottom: 6px; font-size: 13px; color: var(--text-dark); }
    .form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-dark); font-size: 13px; }
    .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15,61,92,.1); }
</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;" class="no-print">
    <h2 style="margin:0;">⚠️ Manajemen Risiko (Risk Register)</h2>
    <button class="btn btn-primary" onclick="openModal('modalAddRisk')">➕ Tambah Risiko Baru</button>
</div>

<!-- STATISTIK -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
    <div class="card" style="padding:20px;border-left:4px solid var(--primary);">
        <small class="text-muted">Total Risiko Terdaftar</small>
        <h3 style="margin:8px 0 0;font-size:28px;"><?= $stats['total'] ?></h3>
    </div>
    <div class="card" style="padding:20px;border-left:4px solid var(--danger);">
        <small class="text-muted">Risiko Level Ekstrem</small>
        <h3 style="margin:8px 0 0;font-size:28px;color:var(--danger);"><?= $stats['ekstrem'] ?></h3>
    </div>
    <div class="card" style="padding:20px;border-left:4px solid var(--warning);">
        <small class="text-muted">Masih Dalam Proses Mitigasi</small>
        <h3 style="margin:8px 0 0;font-size:28px;color:var(--warning);"><?= $stats['aktif'] ?></h3>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;">
    <!-- HEATMAP 5x5 -->
    <div class="card" style="padding:24px;">
        <h3 style="margin-bottom:8px;">🗺️ Peta Panas Risiko</h3>
        <p class="text-muted" style="font-size:13px;margin-bottom:16px;">Matriks Dampak (Y) vs Probabilitas (X)</p>
        
        <div class="risk-heatmap">
            <div></div>
            <div class="axis-label" style="grid-column: 2 / span 5;">PROBABILITAS →</div>
            
            <?php for ($d = 5; $d >= 1; $d--): ?>
                <div class="axis-label axis-y">DAMPAK <?= $d ?></div>
                <?php for ($p = 1; $p <= 5; $p++): 
                    $cell = $heatmap[$d][$p];
                ?>
                <div class="risk-cell" style="background:<?= $cell['bg'] ?>; color:<?= $cell['color'] ?>;">
                    <?php if ($cell['count'] > 0): ?>
                        <span class="count"><?= $cell['count'] ?></span>
                    <?php else: ?>
                        <span class="label">-</span>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            <?php endfor; ?>
            
            <div></div>
            <?php for ($p = 1; $p <= 5; $p++): ?>
                <div class="axis-label"><?= $p ?></div>
            <?php endfor; ?>
        </div>
        
        <div style="display:flex;gap:12px;margin-top:16px;font-size:11px;flex-wrap:wrap;">
            <span style="display:flex;align-items:center;gap:4px;"><span style="width:12px;height:12px;background:#EF4444;border-radius:3px;"></span> Ekstrem (20-25)</span>
            <span style="display:flex;align-items:center;gap:4px;"><span style="width:12px;height:12px;background:#F59E0B;border-radius:3px;"></span> Tinggi (12-19)</span>
            <span style="display:flex;align-items:center;gap:4px;"><span style="width:12px;height:12px;background:#3B82F6;border-radius:3px;"></span> Sedang (5-11)</span>
            <span style="display:flex;align-items:center;gap:4px;"><span style="width:12px;height:12px;background:#10B981;border-radius:3px;"></span> Rendah (1-4)</span>
        </div>
    </div>

    <!-- TABEL RISIKO -->
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <h3 style="margin:0;">📋 Daftar Risiko & Mitigasi</h3>
            <form method="GET" style="display:flex;gap:8px;">
                <select name="status" class="form-control" style="width:auto;padding:6px 10px;font-size:12px;" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="Teridentifikasi" <?= $statusFilter==='Teridentifikasi'?'selected':'' ?>>Teridentifikasi</option>
                    <option value="Dalam Mitigasi" <?= $statusFilter==='Dalam Mitigasi'?'selected':'' ?>>Dalam Mitigasi</option>
                    <option value="Termitigasi" <?= $statusFilter==='Termitigasi'?'selected':'' ?>>Termitigasi</option>
                    <option value="Diterima (Risk Accepted)" <?= $statusFilter==='Diterima (Risk Accepted)'?'selected':'' ?>>Diterima</option>
                </select>
                <?php if($statusFilter): ?><a href="/sim/admin/risk-register.php" class="btn btn-sm btn-outline">Reset</a><?php endif; ?>
            </form>
        </div>
        <div style="overflow-x:auto;">
            <table class="table" style="font-size:13px;width:100%;margin:0;">
                <thead style="background:var(--bg-light);">
                    <tr>
                        <th style="padding:12px 16px;">Risiko</th>
                        <th style="padding:12px 16px;text-align:center;">D x P</th>
                        <th style="padding:12px 16px;">Level</th>
                        <th style="padding:12px 16px;">Rencana Mitigasi</th>
                        <th style="padding:12px 16px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($risks)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">Belum ada data risiko yang terdaftar.</td></tr>
                    <?php else: foreach ($risks as $r): 
                        $levelData = RiskManager::calculateLevel($r['dampak'], $r['probabilitas']);
                        $statusClass = 'status-' . strtolower(str_replace(' ', '-', explode('(', $r['status'])[0]));
                    ?>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:16px;">
                            <strong><?= Security::e($r['judul_risiko']) ?></strong><br>
                            <small class="text-muted"><?= $r['nama_prodi'] ?: '🏛️ Institusi' ?> • PIC: <?= Security::e($r['nama_pic']) ?></small>
                        </td>
                        <td style="padding:16px;text-align:center;">
                            <span style="font-weight:800;font-size:16px;"><?= $r['skor_risiko'] ?></span>
                            <br><small class="text-muted"><?= $r['dampak'] ?> × <?= $r['probabilitas'] ?></small>
                        </td>
                        <td style="padding:16px;">
                            <span class="risk-badge" style="background:<?= $levelData['bg'] ?>;color:<?= $levelData['color'] ?>;">
                                <?= $r['level_risiko'] ?>
                            </span>
                        </td>
                        <td style="padding:16px;max-width:250px;">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= Security::e($r['rencana_mitigasi']) ?>">
                                <?= Security::e($r['rencana_mitigasi']) ?>
                            </div>
                        </td>
                        <td style="padding:16px;">
                            <span class="risk-badge <?= $statusClass ?>"><?= $r['status'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== MODAL TAMBAH RISIKO ===== -->
<div id="modalAddRisk" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>➕ Tambah Risiko Baru</h3>
            <button onclick="closeModal('modalAddRisk')" class="modal-close">×</button>
        </div>
        <form method="POST" style="padding:24px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="add_risk">
            
            <div class="form-group">
                <label class="form-label">Judul Risiko *</label>
                <input type="text" name="judul_risiko" class="form-control" required placeholder="Contoh: Keterlambatan pengisian borang AMI">
            </div>
            
            <div class="form-group">
                <label class="form-label">Deskripsi Detail</label>
                <textarea name="deskripsi" class="form-control" rows="2" placeholder="Jelaskan penyebab dan potensi dampak secara spesifik..."></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group">
                    <label class="form-label">Tingkat</label>
                    <select name="id_prodi" class="form-control">
                        <option value="">🏛️ Institusi (Umum)</option>
                        <?php foreach ($prodiList as $p): ?>
                            <option value="<?= $p['id_prodi'] ?>"><?= Security::e($p['nama_prodi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Penanggung Jawab (PIC) *</label>
                    <select name="id_user_pic" class="form-control" required>
                        <option value="">— Pilih PIC —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id_user'] ?>"><?= Security::e($u['nama_lengkap']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                <div class="form-group">
                    <label class="form-label">Dampak (1-5) *</label>
                    <select name="dampak" class="form-control" required>
                        <?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= $i ?> - <?= ['Sangat Rendah','Rendah','Sedang','Tinggi','Sangat Tinggi'][$i-1] ?></option><?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Probabilitas (1-5) *</label>
                    <select name="probabilitas" class="form-control" required>
                        <?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= $i ?> - <?= ['Sangat Jarang','Jarang','Mungkin','Sering','Hampir Pasti'][$i-1] ?></option><?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Awal</label>
                    <select name="status" class="form-control">
                        <option>Teridentifikasi</option>
                        <option>Dalam Mitigasi</option>
                        <option>Diterima (Risk Accepted)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Rencana Mitigasi *</label>
                <textarea name="rencana_mitigasi" class="form-control" rows="3" required placeholder="Langkah konkret untuk mencegah atau menangani risiko ini..."></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group">
                    <label class="form-label">Tanggal Identifikasi</label>
                    <input type="date" name="tanggal_identifikasi" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Jadwal Review Ulang</label>
                    <input type="date" name="tanggal_review" class="form-control">
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAddRisk')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Risiko</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.style.display = 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer-sim.php'; ?>