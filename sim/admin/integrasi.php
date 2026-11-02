<?php
require_once __DIR__ . '/../../config/config.php';
Auth::requireRole([1]);
require_once __DIR__ . '/../../core/IntegrationHub.php';

$simTitle = 'Pusat Integrasi Data';
$activeMenu = 'integrasi';

// Handle Actions
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$response = null;

if ($action === 'test' && isset($_POST['kode'])) {
    $response = IntegrationHub::testConnection($_POST['kode']);
} elseif ($action === 'sync' && isset($_POST['kode']) && isset($_POST['jenis'])) {
    $response = IntegrationHub::runSync($_POST['kode'], $_POST['jenis'], [
        'mode' => $_POST['mode'] ?? 'full'
    ]);
} elseif ($action === 'save_config' && isset($_POST['kode'])) {
    $db = Database::getInstance();
    $stmt = $db->prepare("UPDATE api_integrasi SET base_url = :url, api_key = :key, api_secret = :secret, token = :token, is_active = :active, config_json = :config WHERE kode = :kode");
    $stmt->execute([
        ':url'     => $_POST['base_url'] ?? '',
        ':key'     => $_POST['api_key'] ?? '',
        ':secret'  => $_POST['api_secret'] ?? '',
        ':token'   => $_POST['token'] ?? '',
        ':active'  => isset($_POST['is_active']) ? 1 : 0,
        ':config'  => $_POST['config_json'] ?? null,
        ':kode'    => $_POST['kode']
    ]);
    $response = ['success' => true, 'message' => 'Konfigurasi berhasil disimpan'];
}

$stats = IntegrationHub::getStats();
$adapters = IntegrationHub::getAvailableAdapters();

// Ambil detail API
$db = Database::getInstance();
$apiList = $db->query("SELECT * FROM api_integrasi ORDER BY kode")->fetchAll();

require_once __DIR__ . '/../../includes/header-sim.php';
?>

<style>
    .integ-hero { 
        background: linear-gradient(135deg, var(--primary), var(--primary-light)); 
        color: #fff; 
        padding: 32px; 
        border-radius: 16px; 
        margin-bottom: 24px; 
        position: relative; 
        overflow: hidden; 
    }
    .integ-hero::before { 
        content: ''; 
        position: absolute; 
        width: 400px; 
        height: 400px; 
        border-radius: 50%; 
        background: radial-gradient(circle, rgba(201,162,39,.2), transparent 70%); 
        right: -100px; 
        top: -150px; 
    }
    
    /* API Cards - Menggunakan CSS Variables */
    .api-card { 
        background: var(--bg-card); 
        border-radius: 14px; 
        padding: 20px; 
        border: 1px solid var(--border); 
        transition: .3s; 
        position: relative; 
        overflow: hidden; 
    }
    .api-card:hover { 
        transform: translateY(-4px); 
        box-shadow: 0 12px 30px rgba(15,61,92,.12); 
    }
    .api-card::before { 
        content: ''; 
        position: absolute; 
        top: 0; 
        left: 0; 
        right: 0; 
        height: 4px; 
        background: linear-gradient(90deg, var(--primary), var(--accent)); 
    }
    
    /* API Logo dengan gradient */
    .api-logo { 
        width: 56px; 
        height: 56px; 
        border-radius: 12px; 
        display: grid; 
        place-items: center; 
        font-size: 28px; 
        font-weight: 900; 
        color: #fff; 
    }
    .api-logo.pddikti { background: linear-gradient(135deg, #00A65A, #00894D); }
    .api-logo.sister { background: linear-gradient(135deg, #0066CC, #004C99); }
    .api-logo.sevima { background: linear-gradient(135deg, #FF6B35, #E55525); }
    .api-logo.sinta { background: linear-gradient(135deg, #9C27B0, #7B1FA2); }
    
    /* Status dot */
    .status-dot { 
        display: inline-block; 
        width: 10px; 
        height: 10px; 
        border-radius: 50%; 
        margin-right: 6px; 
    }
    .status-dot.active { 
        background: #10B981; 
        box-shadow: 0 0 8px #10B981; 
        animation: pulse 2s infinite; 
    }
    .status-dot.inactive { background: #9CA3AF; }
    @keyframes pulse { 
        0%, 100% { opacity: 1; } 
        50% { opacity: .5; } 
    }
    
    /* Sync buttons */
    .sync-btn { 
        padding: 8px 14px; 
        border-radius: 8px; 
        font-size: 12px; 
        font-weight: 700; 
        border: none; 
        cursor: pointer; 
        transition: .2s; 
    }
    .sync-btn:hover { transform: scale(1.05); }
    .sync-btn.primary { 
        background: var(--primary); 
        color: #fff; 
    }
    .sync-btn.gold { 
        background: linear-gradient(135deg, #C9A227, #E8C55A); 
        color: #092A40; 
    }
    .sync-btn.outline {
        background: var(--bg-light);
        color: var(--text-dark);
        border: 1px solid var(--border);
    }
    
    /* Log timeline */
    .log-timeline { 
        position: relative; 
        padding-left: 24px; 
    }
    .log-timeline::before { 
        content: ''; 
        position: absolute; 
        left: 8px; 
        top: 0; 
        bottom: 0; 
        width: 2px; 
        background: var(--border); 
    }
    .log-item { 
        position: relative; 
        padding-bottom: 16px; 
    }
    .log-item::before { 
        content: ''; 
        position: absolute; 
        left: -20px; 
        top: 4px; 
        width: 12px; 
        height: 12px; 
        border-radius: 50%; 
        border: 3px solid var(--bg-card); 
    }
    .log-item.success::before { 
        background: #10B981; 
        box-shadow: 0 0 0 2px #10B981; 
    }
    .log-item.failed::before { 
        background: #EF4444; 
        box-shadow: 0 0 0 2px #EF4444; 
    }
    .log-item.partial::before { 
        background: #F59E0B; 
        box-shadow: 0 0 0 2px #F59E0B; 
    }
    
    /* Modal overlay & content - Menggunakan CSS Variables */
    .modal-overlay { 
        position: fixed; 
        inset: 0; 
        background: rgba(9,42,64,.6); 
        backdrop-filter: blur(4px); 
        z-index: 2000; 
        display: none; 
        align-items: center; 
        justify-content: center; 
    }
    .modal-overlay.show { display: flex; }
    .modal-box { 
        background: var(--bg-card); 
        border-radius: 16px; 
        padding: 28px; 
        max-width: 600px; 
        width: 90%; 
        max-height: 85vh; 
        overflow-y: auto; 
        border: 1px solid var(--border);
    }
    .progress-ring { width: 80px; height: 80px; }
    
    /* Badge styles dengan CSS Variables */
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
        background: var(--bg-light);
        color: var(--text-muted);
    }
    .badge.bg-success {
        background: rgba(16,185,129,.15);
        color: #059669;
    }
    .badge.bg-danger {
        background: rgba(239,68,68,.15);
        color: #DC2626;
    }
    .badge.bg-warning {
        background: rgba(245,158,11,.15);
        color: #B45309;
    }
    
    /* Form elements */
    select, input[type="text"], input[type="url"], input[type="password"] {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--bg-card);
        color: var(--text-dark);
        font-size: 13px;
    }
    select:focus, input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(15,61,92,.1);
    }
    
    /* Label */
    label {
        display: block;
        font-weight: 700;
        margin-bottom: 6px;
        font-size: 13px;
        color: var(--text-dark);
    }
    
    /* Text muted */
    .text-muted {
        color: var(--text-muted);
    }
    
    /* Responsive grid */
    @media (max-width: 768px) {
        .integ-hero { padding: 20px; }
        .api-card { padding: 16px; }
    }
</style>

<div class="integ-hero">
    <div style="position:relative;">
        <h2 style="margin:0;font-size:28px;">🔗 Pusat Integrasi Data</h2>
        <p style="opacity:.85;margin:8px 0 0;max-width:640px;">Sinkronisasi otomatis dari PDDIKTI, SISTER, SIAKAD SEVIMA, dan SINTA. Data selalu terkini tanpa input manual.</p>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;position:relative;margin-top:16px;">
        <div style="padding:12px 20px;background:rgba(255,255,255,.12);border-radius:10px;">
            <small style="opacity:.8;">API Terhubung</small>
            <div style="font-size:22px;font-weight:800;"><?= count(array_filter($stats['apis'], fn($a) => $a['is_active'])) ?>/<?= count($stats['apis']) ?></div>
        </div>
        <div style="padding:12px 20px;background:rgba(255,255,255,.12);border-radius:10px;">
            <small style="opacity:.8;">Antrian Sync</small>
            <div style="font-size:22px;font-weight:800;"><?= $stats['pending_queue'] ?></div>
        </div>
        <div style="padding:12px 20px;background:rgba(255,255,255,.12);border-radius:10px;">
            <small style="opacity:.8;">Conflict Belum Resolved</small>
            <div style="font-size:22px;font-weight:800;color:<?= $stats['unresolved_conflicts'] > 0 ? '#FBBF24' : '#fff' ?>;"><?= $stats['unresolved_conflicts'] ?></div>
        </div>
    </div>
</div>

<?php if ($response): ?>
<div class="card" style="padding:16px;margin-bottom:20px;background:<?= $response['success'] ? 'rgba(16,185,129,.1)' : 'rgba(239,68,68,.1)' ?>;border:1px solid <?= $response['success'] ? '#10B981' : '#EF4444' ?>;border-radius:12px;">
    <?= $response['success'] ? '✅' : '❌' ?> <?= Security::e($response['message'] ?? 'Aksi berhasil') ?>
    <?php if (isset($response['result'])): ?>
        <div style="margin-top:10px;font-size:13px;">
            Total: <b><?= $response['result']['total'] ?></b> | 
            Sukses: <b style="color:#059669;"><?= $response['result']['success'] ?></b> | 
            Gagal: <b style="color:#DC2626;"><?= $response['result']['failed'] ?></b> | 
            Durasi: <b><?= $response['duration'] ?? 0 ?>s</b>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- GRID API CARDS -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;margin-bottom:24px;">
    <?php foreach ($apiList as $api): 
        $adapter = IntegrationHub::getAdapter($api['kode']);
        $supportedTypes = $adapter ? $adapter->getSupportedSyncTypes() : [];
        $configJson = $api['config_json'] ? json_decode($api['config_json'], true) : [];
    ?>
    <div class="api-card">
        <div style="display:flex;gap:14px;align-items:center;margin-bottom:16px;">
            <div class="api-logo <?= strtolower($api['kode']) ?>">
                <?= strtoupper(substr($api['kode'], 0, 2)) ?>
            </div>
            <div style="flex:1;">
                <h4 style="margin:0;font-size:16px;"><?= Security::e($api['nama']) ?></h4>
                <small style="color:var(--text-muted);">
                    <span class="status-dot <?= $api['is_active'] ? 'active' : 'inactive' ?>"></span>
                    <?= $api['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    <?php if ($api['last_test']): ?>
                        • Tes: <?= date('d/m H:i', strtotime($api['last_test'])) ?>
                    <?php endif; ?>
                </small>
            </div>
            <span class="badge" style="background:<?= $api['last_test_status'] === 'Sukses' ? 'rgba(16,185,129,.15)' : ($api['last_test_status'] === 'Gagal' ? 'rgba(239,68,68,.15)' : 'rgba(107,114,128,.15)') ?>;color:<?= $api['last_test_status'] === 'Sukses' ? '#059669' : ($api['last_test_status'] === 'Gagal' ? '#DC2626' : '#6B7280') ?>;">
                <?= $api['last_test_status'] ?>
            </span>
        </div>

        <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px;word-break:break-all;">
            🔗 <?= Security::e($api['base_url']) ?>
        </div>

        <!-- Tombol Aksi Cepat -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="test">
                <input type="hidden" name="kode" value="<?= $api['kode'] ?>">
                <button type="submit" class="sync-btn primary">🔌 Test Koneksi</button>
            </form>
            <button class="sync-btn gold" onclick="openSyncModal('<?= $api['kode'] ?>', '<?= Security::e($api['nama']) ?>')">⚡ Sync Manual</button>
            <button class="sync-btn" style="background:var(--bg-light);color:var(--text-dark);" onclick="openConfigModal('<?= $api['kode'] ?>')">⚙️ Konfigurasi</button>
        </div>

        <!-- Jenis Sync yang Didukung -->
        <?php if (!empty($supportedTypes)): ?>
        <details style="margin-top:10px;">
            <summary style="cursor:pointer;font-size:12px;font-weight:700;color:var(--primary);">📋 Lihat Jenis Sync (<?= count($supportedTypes) ?>)</summary>
            <div style="margin-top:8px;display:flex;flex-direction:column;gap:4px;">
                <?php foreach ($supportedTypes as $key => $label): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 10px;background:var(--bg-light);border-radius:6px;font-size:12px;">
                    <span><?= Security::e($label) ?></span>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="sync">
                        <input type="hidden" name="kode" value="<?= $api['kode'] ?>">
                        <input type="hidden" name="jenis" value="<?= $key ?>">
                        <button type="submit" style="background:none;border:none;color:var(--primary);cursor:pointer;font-weight:700;font-size:11px;">▶ Sync</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </details>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- LOG 24 JAM TERAKHIR -->
<div class="card" style="padding:24px;margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;">📜 Riwayat Sinkronisasi (24 Jam Terakhir)</h3>
        <a href="/sim/admin/sync-log.php" class="btn btn-outline btn-sm">Lihat Semua →</a>
    </div>
    <?php
    $recentLogs = $db->query("SELECT l.*, a.kode, a.nama FROM sync_log l JOIN api_integrasi a ON l.id_api = a.id_api WHERE l.waktu_mulai >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY l.waktu_mulai DESC LIMIT 10")->fetchAll();
    ?>
    <?php if (empty($recentLogs)): ?>
        <p class="text-muted" style="text-align:center;padding:20px;">Belum ada aktivitas sinkronisasi dalam 24 jam terakhir.</p>
    <?php else: ?>
        <div class="log-timeline">
            <?php foreach ($recentLogs as $log): 
                $statusClass = strtolower($log['status']);
            ?>
            <div class="log-item <?= $statusClass ?>">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                    <div style="flex:1;">
                        <strong style="font-size:13px;"><?= Security::e($log['nama']) ?> — <?= Security::e($log['jenis_sync']) ?></strong>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                            <span class="badge bg-<?= $statusClass === 'success' ? 'success' : ($statusClass === 'failed' ? 'danger' : 'warning') ?>" style="font-size:10px;"><?= $log['status'] ?></span>
                            • <?= (int)$log['data_berhasil'] ?>/<?= (int)$log['total_data'] ?> data
                            • <?= (int)$log['durasi_detik'] ?>s
                            <?php if ($log['id_user']): ?>
                                • oleh User #<?= $log['id_user'] ?>
                            <?php else: ?>
                                • oleh CRON
                            <?php endif; ?>
                        </div>
                    </div>
                    <small style="color:var(--text-muted);font-size:11px;white-space:nowrap;"><?= date('d/m H:i', strtotime($log['waktu_mulai'])) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL: SYNC MANUAL -->
<div class="modal-overlay" id="syncModal">
    <div class="modal-box">
        <h3 style="margin:0 0 16px;">⚡ Sinkronisasi Manual</h3>
        <form method="POST">
            <input type="hidden" name="action" value="sync">
            <input type="hidden" name="kode" id="syncKode">
            <p style="color:var(--text-muted);font-size:13px;">Sinkronisasi: <strong id="syncNama"></strong></p>
            
            <div style="margin-bottom:14px;">
                <label style="display:block;font-weight:700;margin-bottom:6px;font-size:13px;">Jenis Data</label>
                <select name="jenis" id="syncJenis" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);"></select>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-weight:700;margin-bottom:6px;font-size:13px;">Mode Sinkronisasi</label>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
                    <label style="padding:10px;border:1px solid var(--border);border-radius:8px;cursor:pointer;font-size:12px;">
                        <input type="radio" name="mode" value="full" checked> <b>Full</b><br><small class="text-muted">Semua data</small>
                    </label>
                    <label style="padding:10px;border:1px solid var(--border);border-radius:8px;cursor:pointer;font-size:12px;">
                        <input type="radio" name="mode" value="incremental"> <b>Inkremental</b><br><small class="text-muted">Hanya yang baru</small>
                    </label>
                    <label style="padding:10px;border:1px solid var(--border);border-radius:8px;cursor:pointer;font-size:12px;">
                        <input type="radio" name="mode" value="dry_run"> <b>Dry Run</b><br><small class="text-muted">Preview saja</small>
                    </label>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeSyncModal()">Batal</button>
                <button type="submit" class="btn btn-primary">🚀 Mulai Sync</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: KONFIGURASI API -->
<div class="modal-overlay" id="configModal">
    <div class="modal-box">
        <h3 style="margin:0 0 16px;">⚙️ Konfigurasi API</h3>
        <form method="POST" id="configForm">
            <input type="hidden" name="action" value="save_config">
            <input type="hidden" name="kode" id="configKode">
            
            <div style="margin-bottom:12px;">
                <label style="display:block;font-weight:700;margin-bottom:6px;font-size:13px;">Base URL</label>
                <input type="url" name="base_url" id="cfgBaseUrl" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);" required>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-weight:700;margin-bottom:6px;font-size:13px;">API Key</label>
                <input type="text" name="api_key" id="cfgApiKey" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);">
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-weight:700;margin-bottom:6px;font-size:13px;">API Secret</label>
                <input type="password" name="api_secret" id="cfgApiSecret" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);">
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-weight:700;margin-bottom:6px;font-size:13px;">Token (Bearer)</label>
                <input type="text" name="token" id="cfgToken" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_active" id="cfgActive"> <b>Aktifkan API ini</b>
                </label>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeConfigModal()">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
const apiData = <?= json_encode(array_map(fn($a) => [
    'kode' => $a['kode'],
    'nama' => $a['nama'],
    'base_url' => $a['base_url'],
    'api_key' => $a['api_key'],
    'api_secret' => $a['api_secret'],
    'token' => $a['token'],
    'is_active' => $a['is_active'],
    'types' => IntegrationHub::getAdapter($a['kode']) ? IntegrationHub::getAdapter($a['kode'])->getSupportedSyncTypes() : []
], $apiList)) ?>;

function openSyncModal(kode, nama) {
    document.getElementById('syncKode').value = kode;
    document.getElementById('syncNama').textContent = nama;
    const select = document.getElementById('syncJenis');
    select.innerHTML = '';
    const types = apiData.find(a => a.kode === kode)?.types || {};
    for (const [key, label] of Object.entries(types)) {
        select.innerHTML += `<option value="${key}">${label}</option>`;
    }
    document.getElementById('syncModal').classList.add('show');
}
function closeSyncModal() { document.getElementById('syncModal').classList.remove('show'); }

function openConfigModal(kode) {
    const data = apiData.find(a => a.kode === kode);
    if (!data) return;
    document.getElementById('configKode').value = kode;
    document.getElementById('cfgBaseUrl').value = data.base_url || '';
    document.getElementById('cfgApiKey').value = data.api_key || '';
    document.getElementById('cfgApiSecret').value = data.api_secret || '';
    document.getElementById('cfgToken').value = data.token || '';
    document.getElementById('cfgActive').checked = !!data.is_active;
    document.getElementById('configModal').classList.add('show');
}
function closeConfigModal() { document.getElementById('configModal').classList.remove('show'); }

// Tutup modal saat klik overlay
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer-sim.php'; ?>