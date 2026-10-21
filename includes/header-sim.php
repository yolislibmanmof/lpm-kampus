<?php
if (!Auth::check()) { header('Location: /login.php'); exit; }

if (!function_exists('getRoleName')) {
    function getRoleName($roleId) {
        return match ($roleId) {
        1 => 'Admin LPM', 2 => 'Pimpinan', 3 => 'Kaprodi', 4 => 'Auditor', 5 => 'GPM Fakultas', default => 'User'
        };
    }
}
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) return 'Baru saja';
        if ($diff < 3600) return floor($diff / 60) . ' mnt lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        return floor($diff / 86400) . ' hari lalu';
    }
}

$simTitle = $simTitle ?? 'Dashboard';
$activeMenu = $activeMenu ?? '';
Security::sendHeaders();

// Favicon dinamis
$favPath = Site::setting('favicon_path');
if ($favPath && file_exists(PATH_UPLOAD . $favPath)) {
    $favHref = '/uploads/' . $favPath;
} else {
    $initial = mb_substr(Site::setting('brand_utama', 'LPM'), 0, 1);
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0%' stop-color='%230F3D5C'/><stop offset='100%' stop-color='%23C9A227'/></linearGradient></defs><rect width='64' height='64' rx='14' fill='url(%23g)'/><text x='32' y='44' font-family='Arial Black' font-size='34' font-weight='900' text-anchor='middle' fill='white'>$initial</text></svg>";
    $favHref = 'data:image/svg+xml;utf8,' . $svg;
}

$notifCount = Notifier::unread(Auth::id());
$notifList = [];
try {
    $s = Database::getInstance()->prepare("SELECT * FROM notifications WHERE id_user = ? ORDER BY created_at DESC LIMIT 8");
    $s->execute([Auth::id()]);
    $notifList = $s->fetchAll();
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Security::e($simTitle) ?> | SIM-Mutu v4.0</title>
    <link rel="icon" type="image/x-icon" href="<?= $favHref ?>">
    <link rel="shortcut icon" href="<?= $favHref ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .sim-content { background-image: radial-gradient(rgba(15,61,92,.05) 1px, transparent 1px); background-size: 22px 22px; }
        .sim-topbar { position: sticky; top: 0; z-index: 60; background: rgba(247,249,252,.86); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); margin: -32px -36px 32px; padding: 18px 36px; border-bottom: 1px solid var(--border); }
        .sb-collapse { width: 42px; height: 42px; border-radius: 12px; border: 1px solid var(--border); background: #fff; cursor: pointer; font-size: 17px; transition: var(--transition); display: grid; place-items: center; }
        .sb-collapse:hover { background: var(--primary); color: #fff; transform: rotate(90deg); }
        .sb-clock { background: #fff; border: 1px solid var(--border); border-radius: 50px; padding: 9px 20px; font-size: 13px; font-weight: 700; color: var(--primary); box-shadow: var(--shadow-sm); font-variant-numeric: tabular-nums; }
        .sb-rolebadge { padding: 5px 14px; border-radius: 50px; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; background: linear-gradient(135deg, var(--accent), var(--accent-light)); color: var(--primary-dark); }
        .st-title { font-size: 24px; color: var(--primary-dark); }
        body.dark .st-title { color: var(--accent-light); }
        .table-wrapper tbody tr:nth-child(even) { background: rgba(15,61,92,.025); }
        .sim-content .card { border-radius: var(--radius-lg); }
        .sim-content .card h3 { display: flex; align-items: center; gap: 10px; }
        .stat-card h3 { color: var(--primary-dark); }
        .sim-sidebar { transition: width .35s var(--ease-out); }
        body.sim-collapsed .sim-sidebar { width: 86px; }
        body.sim-collapsed .sim-content { margin-left: 86px; }
        body.sim-collapsed .sb-txt, body.sim-collapsed .brand-text, body.sim-collapsed .sim-menu .lbl { display: none; }
        body.sim-collapsed .sim-menu a { justify-content: center; padding: 13px 0; margin: 3px 10px; }
        body.sim-collapsed .sim-menu a::before { left: 0; }
        body.sim-collapsed .sb-profile { justify-content: center; padding: 14px 0; }
        body.sim-collapsed .sb-ava { margin: 0; }
        body.sim-collapsed .sb-head { padding: 0 14px 16px; }
        body.sim-collapsed .sb-label { visibility: hidden; }
        .sb-profile { display: flex; gap: 12px; align-items: center; margin-top: 20px; padding: 14px; border-radius: 14px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.09); }
        .sb-ava { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, var(--accent), var(--accent-light)); color: var(--primary-dark); font-weight: 800; display: grid; place-items: center; font-size: 17px; }
        .sb-profile strong { display: block; font-size: 13.5px; color: #fff; line-height: 1.3; }
        .sb-profile small { color: rgba(255,255,255,.55); font-size: 11.5px; }
        .sb-label { padding: 18px 26px 8px; font-size: 10.5px; letter-spacing: 2px; color: rgba(255,255,255,.35); font-weight: 800; }
        .sb-ver { padding: 16px 26px; font-size: 11px; color: rgba(255,255,255,.3); }
        .sb-logout:hover { background: rgba(239,68,68,.15) !important; color: #FCA5A5 !important; }

        .nt-wrap { position: relative; }
        .nt-bell { position: relative; width: 44px; height: 44px; border-radius: 14px; border: 1px solid var(--border); background: #fff; cursor: pointer; font-size: 18px; transition: var(--transition); display: grid; place-items: center; }
        .nt-bell:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .nt-badge { position: absolute; top: -7px; right: -7px; min-width: 20px; height: 20px; padding: 0 5px; border-radius: 50px; background: var(--danger); color: #fff; font-size: 11px; font-weight: 800; display: grid; place-items: center; box-shadow: 0 0 0 3px #fff; animation: badgePulse 2s infinite; }
        @keyframes badgePulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.15); } }
        .nt-drop { position: absolute; right: 0; top: 54px; width: 340px; background: #fff; border-radius: 16px; box-shadow: var(--shadow-lg); border: 1px solid var(--border); opacity: 0; visibility: hidden; transform: translateY(10px); transition: .3s var(--ease-out); z-index: 300; overflow: hidden; max-height: 480px; overflow-y: auto; }
        .nt-wrap.open .nt-drop { opacity: 1; visibility: visible; transform: none; }
        .nt-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .nt-head a { color: var(--primary); font-size: 12px; font-weight: 700; text-decoration: none; }
        .nt-head a:hover { text-decoration: underline; }
        .nt-item { display: flex; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--border); text-decoration: none; color: inherit; transition: .2s; cursor: pointer; }
        .nt-item:last-child { border-bottom: none; }
        .nt-item:hover { background: var(--bg-light); transform: translateX(3px); }
        .nt-item.unread { background: rgba(201,162,39,.08); border-left: 3px solid var(--accent); }
        .nt-ic { font-size: 20px; flex-shrink: 0; }
        .nt-txt strong { font-size: 13.5px; display: block; color: var(--text-dark); }
        .nt-txt small { color: var(--text-muted); font-size: 12.5px; display: block; }
        .nt-txt em { color: var(--accent); font-style: normal; font-size: 11px; font-weight: 700; }
        .nt-empty { padding: 34px; text-align: center; color: var(--text-muted); font-size: 13.5px; }

        body.dark { --bg-light:#0B1220; --bg-card:#101A30; --text-dark:#E6EDF7; --text-muted:#8FA3BF; --border:#1E2A44; }
        body.dark .sim-content { background-image: radial-gradient(rgba(255,255,255,.045) 1px, transparent 1px); }
        body.dark .sim-topbar { background: rgba(11,18,32,.86); border-color: var(--border); }
        body.dark .card, body.dark .table-wrapper, body.dark .stat-card,
        body.dark .sb-collapse, body.dark .sb-clock, body.dark .nt-bell,
        body.dark .nt-drop, body.dark .form-control { background: var(--bg-card); color: var(--text-dark); border-color: var(--border); }
        body.dark .form-control { background: #0D1526; }
        body.dark .sb-clock, body.dark .stat-card h3 { color: var(--accent-light); }
        body.dark thead { background: linear-gradient(90deg, #0A1B2E, #12365B); }
        body.dark tbody tr { border-color: var(--border); }
        body.dark tbody tr:nth-child(even) { background: rgba(255,255,255,.02); }
        body.dark tbody tr:hover { background: rgba(201,162,39,.07); }
        body.dark h1, body.dark h2, body.dark h3, body.dark h4, body.dark strong, body.dark td { color: var(--text-dark); }
        body.dark p, body.dark small, body.dark .text-muted { color: var(--text-muted); }
        body.dark .badge-baik { background: #1E2A44; color: #CBD5E1; }
        body.dark .alert-success { background: rgba(16,185,129,.15); color: #6EE7B7; border-color: var(--success); }
        body.dark .alert-danger { background: rgba(239,68,68,.15); color: #FCA5A5; }
        body.dark .btn-outline { border-color: #3B82F6; color: #93C5FD; }
        body.dark .btn-outline:hover { background: #3B82F6; color: #fff; }
        body.dark .nt-item:hover { background: #0D1526; }
        body.dark .nt-item.unread { background: rgba(201,162,39,.1); }
        body.dark .nt-badge { box-shadow: 0 0 0 3px #0B1220; }
        body.dark .nt-head a { color: var(--accent-light); }
        body.dark ::-webkit-scrollbar-track { background: #0B1220; }

        .cmdk { position: fixed; inset: 0; background: rgba(4,10,20,.62); backdrop-filter: blur(5px); z-index: 4000; display: none; align-items: flex-start; justify-content: center; padding-top: 12vh; }
        .cmdk.open { display: flex; }
        .cmdk-box { width: min(560px, 92vw); background: var(--bg-card); border-radius: 18px; box-shadow: var(--shadow-lg); border: 1px solid var(--border); overflow: hidden; animation: cmdIn .25s var(--ease-out); }
        @keyframes cmdIn { from { opacity: 0; transform: scale(.96) translateY(12px); } }
        .cmdk-input { width: 100%; padding: 17px 22px; border: none; outline: none; font-size: 16px; font-family: inherit; background: transparent; color: var(--text-dark); border-bottom: 1px solid var(--border); }
        .cmdk-list { max-height: 320px; overflow-y: auto; }
        .cmdk-item { display: flex; gap: 12px; padding: 13px 22px; cursor: pointer; color: var(--text-dark); text-decoration: none; font-size: 14px; font-weight: 600; align-items: center; }
        .cmdk-item.sel, .cmdk-item:hover { background: rgba(201,162,39,.13); color: var(--accent); }
        .cmdk-hint { padding: 10px 22px; font-size: 11px; color: var(--text-muted); border-top: 1px solid var(--border); display: flex; gap: 16px; }
        .cmdk-hint b { color: var(--accent); }

        @media (max-width: 992px) {
            .sim-topbar { margin: -24px -18px 24px; padding: 14px 18px; }
            .sb-collapse, .sb-clock { display: none; }
            .nt-drop { width: 300px; right: -60px; }
        }

/* ==========================================
   GLOBAL DARK MODE OVERRIDE (v2.0 - MAXIMUM)
   Memaksa adaptasi untuk semua modul Prodi & Admin
   ========================================== */

body.dark {
    /* Perbaiki warna teks heading agar terbaca di background gelap */
    --text-heading: #E6EDF7; 
}

/* 1. Paksa Background Kartu & Form menjadi Gelap */
body.dark .card, 
body.dark .stat-card, 
body.dark .table-wrapper,
body.dark .form-card, 
body.dark .add-form-card, 
body.dark .kriteria-card, 
body.dark .upload-card, 
body.dark .input-card,
body.dark .edp-card,
body.dark .audit-card,
body.dark .finding-item,
body.dark .form-group,
body.dark .doc-card,
body.dark .contact-card,
body.dark .qa-btn,
body.dark .modal-content {
    background: var(--bg-card) !important;
    color: var(--text-dark) !important;
    border-color: var(--border) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,.2) !important;
}

/* 2. Paksa Teks Judul & Label agar Terang */
body.dark h1, body.dark h2, body.dark h3, body.dark h4, 
body.dark strong, body.dark b, 
body.dark .form-label, 
body.dark .task-title,
body.dark .dosen-name,
body.dark .contact-info strong {
    color: var(--text-heading) !important;
}

/* 3. Perbaiki Tabel agar Rapi di Dark Mode */
body.dark table th {
    background: rgba(255,255,255,0.05) !important;
    color: var(--text-muted) !important;
    border-color: var(--border) !important;
}
body.dark table td {
    border-color: var(--border) !important;
    color: var(--text-dark) !important;
}

/* 4. Perbaiki Input, Select, dan Textarea */
body.dark input.form-control, 
body.dark select.form-control, 
body.dark textarea.form-control {
    background: #0D1526 !important; /* Lebih gelap dari card */
    color: var(--text-dark) !important;
    border-color: var(--border) !important;
}
body.dark input.form-control:focus, 
body.dark select.form-control:focus, 
body.dark textarea.form-control:focus {
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px rgba(201,162,39,.2) !important;
}

/* 5. Perbaiki Tombol Outline & Badge */
body.dark .btn-outline {
    border-color: var(--border) !important;
    color: var(--text-dark) !important;
    background: transparent !important;
}
body.dark .btn-outline:hover {
    background: rgba(255,255,255,0.1) !important;
}
body.dark .badge-baik, body.dark .badge-a, body.dark .badge-b {
    background: rgba(255,255,255,0.1) !important;
    color: var(--text-dark) !important;
}

/* 6. Perbaiki Area Khusus (Dropzone, Alert, dll) */
body.dark .file-input-label,
body.dark .csv-drop {
    background: rgba(255,255,255,0.03) !important;
    border-color: var(--border) !important;
    color: var(--text-muted) !important;
}
body.dark .alert-success {
    background: rgba(16,185,129,.15) !important;
    color: #6EE7B7 !important;
    border-color: #10B981 !important;
}
body.dark .alert-danger {
    background: rgba(239,68,68,.15) !important;
    color: #FCA5A5 !important;
    border-color: #EF4444 !important;
}
    </style>
</head>
<body>
<script>if (localStorage.getItem('lpm-dark') === '1') document.body.classList.add('dark');</script>
<div class="sim-layout">
    <?php require_once __DIR__ . '/sidebar-sim.php'; ?>

    <div class="cmdk" id="cmdk">
        <div class="cmdk-box">
            <input type="text" class="cmdk-input" id="cmdkInput" placeholder="Ketik menu atau perintah...">
            <div class="cmdk-list" id="cmdkList"></div>
            <div class="cmdk-hint"><span><b>↑↓</b> navigasi</span><span><b>Enter</b> buka</span><span><b>Esc</b> tutup</span><span><b>Ctrl K</b> toggle</span></div>
        </div>
    </div>
    <?php
    $cmdItems = [];
    foreach ($currentRoleMenus as $m) $cmdItems[] = ['icon' => $m['icon'], 'label' => $m['label'], 'url' => $m['url'], 'action' => ''];
    $cmdItems[] = ['icon' => '🌙', 'label' => 'Ganti Mode Terang / Gelap', 'url' => '', 'action' => 'dark'];
    $cmdItems[] = ['icon' => '🖨️', 'label' => 'Cetak Halaman Ini', 'url' => '', 'action' => 'print'];
    $cmdItems[] = ['icon' => '🚪', 'label' => 'Keluar dari SIM-Mutu', 'url' => '', 'action' => 'logout'];
    ?>
    <script>window.CMD_ITEMS = <?= json_encode($cmdItems) ?>;</script>
    
    <!-- ===== PATCH LOGOUT: FORM POST GLOBAL + INTERCEPTOR ===== -->
    <form id="global-logout-form" action="/logout.php" method="POST" style="display:none;" autocomplete="off">
        <?= Security::csrfField() ?>
    </form>
    <script>
    (function () {
        'use strict';
        function lpmLogout(ev) {
            if (ev) ev.preventDefault();
            if (confirm('Apakah Anda yakin ingin keluar dari SIM-Mutu?')) {
                document.getElementById('global-logout-form').submit();
            }
        }
        document.addEventListener('click', function (e) {
            var el = e.target.closest('a[href*="logout.php"], [data-logout]');
            if (el) {
                e.preventDefault();
                e.stopImmediatePropagation();
                lpmLogout(e);
            }
        }, true);
        window.LPM_LOGOUT = lpmLogout;
    })();
    </script>
    <!-- ===== AKHIR PATCH LOGOUT ===== -->

    <main class="sim-content">
        <div class="sim-topbar">
            <div style="display:flex;align-items:center;gap:16px;">
                <button class="sb-collapse" id="sbCollapse" title="Ciutkan sidebar">⚙️</button>
                <div>
                    <h1 class="st-title"><?= Security::e($simTitle) ?></h1>
                    <p class="text-muted" style="font-size:13px;">SIM-Mutu / <?= Security::e($simTitle) ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:14px;">
                <span class="sb-clock" id="sbClock">—</span>
                <button class="sb-collapse" id="cmdkBtn" title="Command Palette (Ctrl+K)">⌨️</button>
                <button class="sb-collapse" id="dmToggle" title="Mode gelap/terang">🌙</button>

                <!-- ===== NOTIFICATION CENTER ===== -->
                <div class="nt-wrap" id="ntWrap">
                    <button class="nt-bell" id="ntBell" aria-label="Notifikasi" type="button">🔔
                        <?php if ($notifCount > 0): ?><span class="nt-badge" id="ntBadge"><?= $notifCount > 9 ? '9+' : $notifCount ?></span><?php endif; ?>
                    </button>
                    <div class="nt-drop">
                        <div class="nt-head">
                            <strong>🔔 Notifikasi</strong>
                            <div style="display:flex;gap:10px;align-items:center;">
                                <a href="#" id="ntSoundToggle" style="font-size:14px;text-decoration:none;" title="Nyalakan Suara Notifikasi">🔇</a>
                                <?php if ($notifCount > 0): ?><a href="#" id="ntMarkAllRead" style="color:var(--primary);font-size:12px;font-weight:700;text-decoration:none;">Tandai semua dibaca</a><?php endif; ?>
                            </div>
                        </div>
                        <div id="ntListContainer">
                            <?php if (empty($notifList)): ?>
                                <div class="nt-empty">🌙 Tidak ada notifikasi.<br>Semua tenang dan terkendali.</div>
                            <?php else: ?>
                                <?php foreach ($notifList as $n): ?>
                                <a class="nt-item <?= $n['is_read'] ? '' : 'unread' ?>" href="/sim/notif.php?action=read&id=<?= $n['id_notif'] ?>&goto=<?= urlencode($n['url']) ?>" data-id="<?= $n['id_notif'] ?>">
                                    <span class="nt-ic"><?= $n['icon'] ?></span>
                                    <span class="nt-txt">
                                        <strong><?= Security::e($n['judul']) ?></strong>
                                        <small><?= Security::e(mb_strimwidth($n['isi'], 0, 60, '...')) ?></small>
                                        <em><?= timeAgo($n['created_at']) ?></em>
                                    </span>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- ===== AKHIR NOTIFICATION CENTER ===== -->

                <span class="sb-rolebadge"><?= Security::e(getRoleName(Auth::role())) ?></span>
                <div style="width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,var(--primary),var(--accent));display:grid;place-items:center;color:#fff;font-weight:800;box-shadow:var(--shadow-md);">
                    <?= strtoupper(substr(Auth::user()['nama'], 0, 1)) ?>
                </div>
            </div>
        </div>

<!-- ===== SCRIPT FITUR UTAMA ===== -->
<script>
(function() {
    'use strict';

    // === CLOCK ===
    function updateClock() {
        const now = new Date();
        const el = document.getElementById('sbClock');
        if (el) el.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    }
    updateClock();
    setInterval(updateClock, 1000);

    // === SIDEBAR COLLAPSE ===
    const sbCollapseBtn = document.getElementById('sbCollapse');
    if (sbCollapseBtn) {
        if (localStorage.getItem('lpm-sidebar-collapsed') === '1') document.body.classList.add('sim-collapsed');
        sbCollapseBtn.addEventListener('click', function() {
            document.body.classList.toggle('sim-collapsed');
            localStorage.setItem('lpm-sidebar-collapsed', document.body.classList.contains('sim-collapsed') ? '1' : '0');
        });
    }

    // === DARK MODE TOGGLE (DIPERBAIKI) ===
    const dmToggle = document.getElementById('dmToggle');
    if (dmToggle) {
        dmToggle.textContent = document.body.classList.contains('dark') ? '☀️' : '🌙';
        dmToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('dark');
            const isDark = document.body.classList.contains('dark');
            localStorage.setItem('lpm-dark', isDark ? '1' : '0');
            dmToggle.textContent = isDark ? '☀️' : '🌙';
        });
    }

    // === COMMAND PALETTE ===
    const cmdk = document.getElementById('cmdk');
    const cmdkInput = document.getElementById('cmdkInput');
    const cmdkList = document.getElementById('cmdkList');
    const cmdkBtn = document.getElementById('cmdkBtn');

    function openCmdk() {
        if (cmdk) {
            cmdk.classList.add('open');
            if (cmdkInput) { cmdkInput.value = ''; cmdkInput.focus(); }
            renderCmdkList('');
        }
    }
    function closeCmdk() { if (cmdk) cmdk.classList.remove('open'); }

    function renderCmdkList(query) {
        if (!cmdkList) return;
        const q = query.toLowerCase();
        const items = (window.CMD_ITEMS || []).filter(m => m.label.toLowerCase().includes(q));
        cmdkList.innerHTML = items.map((m, i) =>
            `<a class="cmdk-item ${i === 0 ? 'sel' : ''}" data-url="${m.url || ''}" data-action="${m.action || ''}">
                <span>${m.icon}</span><span>${m.label}</span>
            </a>`
        ).join('');
    }


 
 +
 +-+
 
 if* (cmdkBtn) cmdkBtn.addEventListener('click', function(e) { e.preventDefault(); openCmdk(); });
    if (cmdk) cmdk.addEventListener('click', function(e) { if (e.target === cmdk) closeCmdk(); });
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); cmdk && cmdk.classList.contains('open') ? closeCmdk() : openCmdk(); }
        if (e.key === 'Escape') closeCmdk();
    });
    if (cmdkInput) {
        cmdkInput.addEventListener('input', function() { renderCmdkList(this.value); });
        cmdkInput.addEventListener('keydown', function(e) {
            const items = cmdkList.querySelectorAll('.cmdk-item');
            const sel = cmdkList.querySelector('.cmdk-item.sel');
            let idx = Array.from(items).indexOf(sel);
            if (e.key === 'ArrowDown') { e.preventDefault(); idx = Math.min(idx + 1, items.length - 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); idx = Math.max(idx - 1, 0); }
            else if (e.key === 'Enter' && sel) { e.preventDefault(); executeCmdkItem(sel); return; }
            else return;
            items.forEach(i => i.classList.remove('sel'));
            if (items[idx]) items[idx].classList.add('sel');
        });
    }
    if (cmdkList) {
        cmdkList.addEventListener('click', function(e) {
          + 
          +
           const item = e.target.closest('.cmdk-item');
         +
          
         i+
         -+
         item) executeCmdkItem(item);+-
-+
-+
--+
+

+     });
    }
    function executeCmdkItem(el) {
        const url = el.dataset.url;
        const action = el.dataset.action;
        closeCmdk();
        if (action === 'dark' && dmToggle) dmToggle.click();
        else if (action === 'print') window.print();
        else if (action === 'logout' && window.LPM_LOGOUT) window.LPM_LOGOUT();
        else if (url) window.location.href = url;
    }

    // === NOTIFICATION CENTER ===
    const ntWrap = document.getElementById('ntWrap');
    const ntBell = document.getElementById('ntBell');
    const ntBadge = document.getElementById('ntBadge');
    const ntListContainer = document.getElementById('ntListContainer');
    const ntSoundToggle = document.getElementById('ntSoundToggle');
    const ntMarkAllRead = document.getElementById('ntMarkAllRead');

    const popSound = new Audio("data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQQAAAAAAA==");
    let currentCount = parseInt(ntBadge ? ntBadge.textContent.replace('+', '') : 0);
    let isSoundEnabled = localStorage.getItem('lpm_notif_sound') === '1';

    if (ntSoundToggle) {
        ntSoundToggle.textContent = isSoundEnabled ? '🔊' : '🔇';
        ntSoundToggle.title = isSoundEna
        bled ? 'Matikan Suara Not
        *-
        i+kasi' : 'Nyalakan Suara Notifikasi';
 
 +   }

    if (ntBell) {
        ntBell.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            ntWrap.classList.toggle('open');
        });
    }

    document.addEventListener('click', function(e) {
        if (ntWrap && !ntWrap.contains(e.target)) {
            ntWrap.classList.remove('open');
        }
    });

    if (ntSoundToggle) {
        ntSoundToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            isSoundEnabled = !isSoundEnabled;
            localStorage.setItem('lpm_notif_sound', isSoundEnabled ? '1' : '0');
            ntSoundToggle.textContent = isSoundEnabled ? '🔊' : '🔇';
            ntSoundToggle.title = isSoundEnabled ? 'Matikan Suara Notifikasi' : 'Nyalakan Suara Notifikasi';
        });
    }

    if (ntMarkAllRead) {
        ntMarkAllRead.addEventListener('click', async function(e) {
        *-   e.preventDefault();+
            e.stopPropagation();
            try {
                await fetch('/sim/api/mark-all-read.php', { method: 'POST' });
                currentCount = 0;
                if (ntBadge) ntBadge.style.display = 'none';
                if (ntListContainer) ntListContainer.innerHTML = '<div class="nt-empty">🌙 Tidak ada notifikasi.<br>Semua tenang dan terkendali.</div>';
                if (ntMarkAllRead) ntMarkAllRead.remove();
            } catch (err) { console.error('Mark all read failed:', err); }
        });
    }

    async function fetchNotifications() {
        try {
            const response = await fetch('/sim/api/get-notifications.php');
            const data = await response.json();
            if (data.success) {
                if (data.count > currentCount && currentCount > 0 && isSoundEnabled) {
                    popSound.play().catch(function() {});
                }
                currentCount = data.count;
                if (ntBadge) {
                    if (currentCount > 0) {
                        ntBadge.textContent = currentCount > 9 ? '9+' : currentCount;
                        ntBadge.style.display = 'grid';
                    } else {
                        ntBadge.style.display = 'none';
                    }
                }
                if (ntListContainer) {
                    ntListContainer.i++erHTML = data.html || '<div class="nt-empty">🌙 Tidak ada notifikasi.<br>Semua tenang dan terkendali.</div>';
                }
            }
        } catch (error) { console.error('Notif fetch failed:', error); }
    }

    document.addEventListener('click', function(e) {
        const item = e.target.closest('.nt-item[data-id]');
        if (item && item.classList.contains('unread')) {
            item.classList.remove('unread');
            item.style.borderLeft = 'none';
            try {
                const formData = new FormData();
                formData.append('id', item.dataset.id);
                fetch('/sim/api/mark-as-read.php', { method: 'POST', body: formData });
                if (currentCount > 0) {
                    currentCount--;
                    if (ntBadge) {
                        ntBadge.textContent = currentCount > 9 ? '9+' : currentCount;
                        if (currentCount === 0) ntBadge.style.display = 'none';
                    }
                }
            } catch (err) { console.error('Mark read failed:', err); }
        }
    });

    fetchNotifications();
    setInterval(fetchNotifications, 30000);
})();
</script>