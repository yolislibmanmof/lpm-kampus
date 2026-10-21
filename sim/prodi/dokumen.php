<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([3]);
$db = Database::getInstance();

$search = trim($_GET['search'] ?? '');
$kategori = $_GET['kategori'] ?? 'all';

// Build query
$query = "SELECT d.*, k.nama_kategori FROM dokumen_mutu d 
          LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori 
          WHERE d.status = 'Aktif' AND (d.tipe_akses = 'publik' OR d.tipe_akses = 'internal')";
$params = [];

if ($kategori !== 'all') {
    $query .= " AND d.id_kategori = :kategori";
    $params[':kategori'] = (int)$kategori;
}

if ($search) {
    $query .= " AND (d.judul_dokumen LIKE :search OR d.kode_dokumen LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY d.kode_dokumen ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$dokumen = $stmt->fetchAll();

// Get kategori list
$kategoriList = $db->query("SELECT * FROM kategori_dokumen ORDER BY nama_kategori")->fetchAll();

// Count per kategori
$catCount = $db->query("SELECT id_kategori, COUNT(*) c FROM dokumen_mutu WHERE status='Aktif' GROUP BY id_kategori")->fetchAll();
$countMap = array_column($catCount, 'c', 'id_kategori');
$totalAll = count($dokumen);

$simTitle = 'Dokumen Mutu';
$activeMenu = 'dokumen';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
    .doc-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: var(--radius-lg);
        padding: 32px 36px;
        color: #fff;
        margin-bottom: 28px;
        position: relative;
        overflow: hidden;
    }
    .doc-header::before {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
        right: -80px;
        top: -100px;
    }
    .doc-header h2 {
        font-size: 24px;
        margin-bottom: 8px;
        position: relative;
        font-weight: 800;
    }
    .doc-header p {
        color: rgba(255,255,255,.85);
        position: relative;
        font-size: 14px;
    }
    
    /* DIPERBAIKI: Gunakan CSS variables, bukan #fff */
    .doc-search {
        background: var(--bg-card);
        border-radius: 16px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,.05);
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
        border: 2px solid var(--border);
    }
    .doc-search-input {
        flex: 1;
        min-width: 250px;
        position: relative;
    }
    .doc-search-input input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 2px solid var(--border);
        border-radius: 12px;
        font-size: 14px;
        transition: all 0.3s;
        background: var(--bg-card);
        color: var(--text-dark);
    }
    .doc-search-input input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 4px rgba(201,162,39,.15);
    }
    .doc-search-input .ic {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 18px;
        color: var(--text-muted);
    }
    
    .doc-pills {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }
    /* DIPERBAIKI: Gunakan CSS variables */
    .doc-pill {
        padding: 10px 20px;
        border-radius: 50px;
        background: var(--bg-card);
        border: 2px solid var(--border);
        color: var(--text-dark);
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .doc-pill:hover {
        border-color: var(--accent);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(201,162,39,.15);
    }
    .doc-pill.active {
        background: linear-gradient(135deg, var(--accent), var(--accent-light));
        border-color: transparent;
        color: var(--primary-dark);
    }
    .doc-pill .count {
        background: rgba(15,61,92,.1);
        padding: 2px 8px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 800;
    }
    .doc-pill.active .count {
        background: rgba(255,255,255,.3);
    }
    
    /* DIPERBAIKI: Gunakan CSS variables */
    .doc-card {
        background: var(--bg-card);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 16px;
        border: 2px solid var(--border);
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .doc-card:hover {
        border-color: var(--accent);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(201,162,39,.12);
    }
    .doc-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    .doc-icon.pdf { background: linear-gradient(135deg, #DC2626, #F87171); }
    .doc-icon.doc { background: linear-gradient(135deg, #2563EB, #60A5FA); }
    .doc-icon.xls { background: linear-gradient(135deg, #059669, #34D399); }
    .doc-icon.ppt { background: linear-gradient(135deg, #EA580C, #FB923C); }
    
    .doc-info {
        flex: 1;
        min-width: 0;
    }
    .doc-info h4 {
        font-size: 16px;
        margin-bottom: 4px;
        color: var(--text-dark);
        font-weight: 700;
    }
    .doc-info p {
        font-size: 13px;
        color: var(--text-muted);
        margin: 0;
    }
    .doc-meta {
        display: flex;
        gap: 16px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
    .doc-meta span {
        font-size: 12px;
        color: var(--text-muted);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .doc-meta .badge {
        background: var(--bg-light);
        padding: 4px 10px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 11px;
    }
    
    .doc-actions {
        display: flex;
        gap: 10px;
        flex-shrink: 0;
    }
    .doc-btn {
        padding: 10px 18px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .doc-btn-primary {
        background: linear-gradient(135deg, var(--accent), var(--accent-light));
        color: var(--primary-dark);
        box-shadow: 0 4px 12px rgba(201,162,39,.3);
    }
    .doc-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(201,162,39,.4);
    }
    /* DIPERBAIKI: Gunakan CSS variables */
    .doc-btn-outline {
        background: transparent;
        color: var(--primary);
        border: 2px solid var(--border);
    }
    .doc-btn-outline:hover {
        border-color: var(--accent);
        color: var(--accent);
    }
    
    /* DIPERBAIKI: Gunakan CSS variables */
    .doc-empty {
        text-align: center;
        padding: 60px 20px;
        background: var(--bg-card);
        border-radius: 16px;
        border: 2px dashed var(--border);
    }
    .doc-empty .ic {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    .doc-empty h3 {
        font-size: 20px;
        color: var(--text-dark);
        margin-bottom: 8px;
    }
    .doc-empty p {
        color: var(--text-muted);
        font-size: 14px;
    }

    /* DARK MODE OVERRIDES - PASTIKAN SEMUA TERLINDUNGI */
    body.dark .doc-search {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .doc-search-input input {
        background: #0D1526;
        border-color: var(--border);
        color: var(--text-dark);
    }
    body.dark .doc-pill {
        background: var(--bg-card);
        border-color: var(--border);
        color: var(--text-dark);
    }
    body.dark .doc-card {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .doc-empty {
        background: var(--bg-card);
        border-color: var(--border);
    }
    body.dark .doc-btn-outline {
        border-color: var(--border);
        color: var(--text-dark);
    }
</style>

<div class="doc-header">
    <h2> Dokumen Mutu Program Studi</h2>
    <p>Akses kebijakan, manual mutu, standar, dan formulir resmi untuk referensi penyusunan EDP & borang akreditasi.</p>
</div>

<div class="doc-search">
    <div class="doc-search-input">
        <span class="ic">🔍</span>
        <input type="text" id="searchInput" placeholder="Cari dokumen berdasarkan judul atau kode..." value="<?= Security::e($search) ?>">
    </div>
    <div style="font-size:14px;color:var(--text-muted);font-weight:600;">
        Total: <strong style="color:var(--accent);"><?= $totalAll ?></strong> dokumen
    </div>
</div>

<div class="doc-pills">
    <a href="?kategori=all&search=<?= urlencode($search) ?>" class="doc-pill <?= $kategori === 'all' ? 'active' : '' ?>">
        ️ Semua <span class="count"><?= $totalAll ?></span>
    </a>
    <?php foreach ($kategoriList as $kat): ?>
        <a href="?kategori=<?= $kat['id_kategori'] ?>&search=<?= urlencode($search) ?>" class="doc-pill <?= $kategori == $kat['id_kategori'] ? 'active' : '' ?>">
            <?= Security::e($kat['nama_kategori']) ?>
            <span class="count"><?= $countMap[$kat['id_kategori']] ?? 0 ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($dokumen)): ?>
    <div class="doc-empty">
        <div class="ic">📂</div>
        <h3>Tidak Ada Dokumen Ditemukan</h3>
        <p>Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
    </div>
<?php else: ?>
    <div id="docList">
        <?php foreach ($dokumen as $d): 
            $ext = strtolower(pathinfo($d['file_path'], PATHINFO_EXTENSION));
            $iconClass = '';
            $icon = '📄';
            if ($ext === 'pdf') { $iconClass = 'pdf'; $icon = ''; }
            elseif (in_array($ext, ['doc', 'docx'])) { $iconClass = 'doc'; $icon = '📘'; }
            elseif (in_array($ext, ['xls', 'xlsx'])) { $iconClass = 'xls'; $icon = '📗'; }
            elseif (in_array($ext, ['ppt', 'pptx'])) { $iconClass = 'ppt'; $icon = '📙'; }
        ?>
        <div class="doc-card" data-search="<?= strtolower(Security::e($d['judul_dokumen'] . ' ' . $d['kode_dokumen'])) ?>">
            <div class="doc-icon <?= $iconClass ?>"><?= $icon ?></div>
            <div class="doc-info">
                <h4><?= Security::e($d['judul_dokumen']) ?></h4>
                <p><?= Security::e($d['nama_kategori'] ?? '—') ?></p>
                <div class="doc-meta">
                    <span>📋 Kode: <strong><?= Security::e($d['kode_dokumen']) ?></strong></span>
                    <span>📦 Versi: <span class="badge">v<?= $d['versi'] ?></span></span>
                    <span>📅 <?= date('d M Y', strtotime($d['uploaded_at'])) ?></span>
                </div>
            </div>
            <div class="doc-actions">
                <a href="/download.php?id=<?= $d['id_dokumen'] ?>" class="doc-btn doc-btn-primary">
                    📥 Unduh
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
// Live search
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const cards = document.querySelectorAll('.doc-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            const searchData = card.getAttribute('data-search');
            if (searchData.includes(query)) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Update count
        const countEl = document.querySelector('.doc-search div:last-child strong');
        if (countEl) {
            countEl.textContent = visibleCount;
        }
    });
}

// Animate cards on load
window.addEventListener('load', function() {
    const cards = document.querySelectorAll('.doc-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 50);
    });
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>