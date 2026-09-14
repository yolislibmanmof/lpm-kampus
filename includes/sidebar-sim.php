<?php
$menus = [
    1 => [
        ['url' => '/sim/index.php', 'icon' => '📊', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['url' => '/sim/admin/users.php', 'icon' => '👥', 'label' => 'Manajemen Pengguna', 'key' => 'users'],
        ['url' => '/sim/admin/penugasan-gpm.php', 'icon' => '🏢', 'label' => 'Penugasan GPM', 'key' => 'gpm'],
        ['url' => '/sim/admin/auditor-kompetensi.php', 'icon' => '🎖️', 'label' => 'Kompetensi Auditor', 'key' => 'komp'],
        ['url' => '/sim/admin/dokumen.php', 'icon' => '📁', 'label' => 'Dokumen PPEPP', 'key' => 'dokumen'],
        ['url' => '/sim/admin/kendali-dokumen.php', 'icon' => '📑', 'label' => 'Kendali Dokumen', 'key' => 'kendok'],
        ['url' => '/sim/admin/matriks-monitor.php', 'icon' => '🗂️', 'label' => 'Monitor Kesiapan', 'key' => 'monmatriks'],
        ['url' => '/sim/admin/konflik-check.php', 'icon' => '⚖️', 'label' => 'Cek Konflik Kepentingan', 'key' => 'konflik'],
        ['url' => '/sim/admin/iku.php', 'icon' => '📊', 'label' => 'Dasbor IKU', 'key' => 'iku'],
        ['url' => '/sim/admin/jadwal.php', 'icon' => '📅', 'label' => 'Penjadwalan AMI', 'key' => 'jadwal'],
        ['url' => '/sim/admin/instrumen.php', 'icon' => '🧾', 'label' => 'Instrumen AMI', 'key' => 'instrumen'],
        ['url' => '/sim/admin/berita.php', 'icon' => '📰', 'label' => 'Berita & Agenda', 'key' => 'berita'],
        ['url' => '/sim/admin/pengaduan.php', 'icon' => '📩', 'label' => 'Pengaduan Publik', 'key' => 'pengaduan'],
        ['url' => '/sim/admin/laporan.php', 'icon' => '📈', 'label' => 'Laporan Mutu', 'key' => 'laporan'],
        ['url' => '/sim/laporan/pusat-laporan.php', 'icon' => '🖨️', 'label' => 'Pusat Laporan PDF', 'key' => 'puslap'],
        ['url' => '/sim/admin/kuesioner.php', 'icon' => '📱', 'label' => 'Kuesioner Monev', 'key' => 'kuesioner'],
        ['url' => '/sim/admin/tracer.php', 'icon' => '🎓', 'label' => 'Tracer Study', 'key' => 'tracer'],
        ['url' => '/sim/admin/responden.php', 'icon' => '🎤', 'label' => 'Responden Wawancara', 'key' => 'responden'],
        ['url' => '/sim/admin/survei-pengguna.php', 'icon' => '💼', 'label' => 'Survei Pengguna Lulusan', 'key' => 'surveipg'],
        ['url' => '/sim/admin/rtm-tl.php', 'icon' => '📑', 'label' => 'Tindak Lanjut RTM', 'key' => 'rtmtl'],
        ['url' => '/sim/admin/prodi.php', 'icon' => '🏫', 'label' => 'Prodi & Fakultas', 'key' => 'prodi'],
        ['url' => '/sim/admin/import.php', 'icon' => '📥', 'label' => 'Import Data PDDikti', 'key' => 'import'],
        ['url' => '/sim/admin/dosen-manual.php', 'icon' => '📇', 'label' => 'Kelola Dosen', 'key' => 'dosenman'],
        ['url' => '/sim/admin/profil.php', 'icon' => '🏛️', 'label' => 'Konten Profil Publik', 'key' => 'profil'],
        ['url' => '/sim/admin/slider.php', 'icon' => '🖼️', 'label' => 'Slider Beranda', 'key' => 'slider'],
        ['url' => '/sim/admin/akreditasi-intl.php', 'icon' => '🌍', 'label' => 'Akreditasi Internasional', 'key' => 'akrintl'],
        ['url' => '/sim/admin/logs.php', 'icon' => '📜', 'label' => 'Audit Trail', 'key' => 'logs'],
        ['url' => '/sim/admin/tenggat.php', 'icon' => '⏰', 'label' => 'Tenggat & Reminder', 'key' => 'tenggat'],
        ['url' => '/sim/admin/email.php', 'icon' => '✉️', 'label' => 'Pengaturan Email', 'key' => 'email'],
        ['url' => '/sim/panduan.php', 'icon' => '📘', 'label' => 'Buku Panduan', 'key' => 'panduan'],
    ],
    2 => [
        ['url' => '/sim/index.php', 'icon' => '📊', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['url' => '/sim/pimpinan/rtm.php', 'icon' => '📑', 'label' => 'Laporan RTM', 'key' => 'rtm'],
        ['url' => '/sim/admin/rtm-tl.php', 'icon' => '📑', 'label' => 'Tindak Lanjut RTM', 'key' => 'rtmtl'],
        ['url' => '/sim/pimpinan/akreditasi.php', 'icon' => '🏆', 'label' => 'Status Akreditasi', 'key' => 'akreditasi'],
        ['url' => '/sim/pimpinan/temuan.php', 'icon' => '⚠️', 'label' => 'Temuan Audit', 'key' => 'temuan'],
        ['url' => '/sim/admin/matriks-monitor.php', 'icon' => '🗂️', 'label' => 'Monitor Kesiapan', 'key' => 'monmatriks'],
        ['url' => '/sim/pimpinan/monev.php', 'icon' => '📈', 'label' => 'Monitoring Capaian', 'key' => 'monev'],
        ['url' => '/sim/pimpinan/iku.php', 'icon' => '📊', 'label' => 'Dasbor IKU', 'key' => 'iku'],
        ['url' => '/sim/pimpinan/rekomendasi.php', 'icon' => '🤖', 'label' => 'Rekomendasi AI', 'key' => 'rekom'],
        ['url' => '/sim/pimpinan/benchmark.php', 'icon' => '🌐', 'label' => 'Benchmark & Analitik', 'key' => 'bench'],
        ['url' => '/sim/admin/tracer.php', 'icon' => '🎓', 'label' => 'Tracer Study', 'key' => 'tracer'],
        ['url' => '/sim/admin/responden.php', 'icon' => '🎤', 'label' => 'Responden Wawancara', 'key' => 'responden'],
        ['url' => '/sim/admin/survei-pengguna.php', 'icon' => '💼', 'label' => 'Survei Pengguna Lulusan', 'key' => 'surveipg'],
        ['url' => '/sim/laporan/pusat-laporan.php', 'icon' => '🖨️', 'label' => 'Pusat Laporan PDF', 'key' => 'puslap'],
        ['url' => '/sim/instrumen/view.php', 'icon' => '🧾', 'label' => 'Instrumen AMI', 'key' => 'instrumen'],
        ['url' => '/sim/panduan.php', 'icon' => '📘', 'label' => 'Buku Panduan', 'key' => 'panduan'],
    ],
    3 => [
        ['url' => '/sim/index.php', 'icon' => '📊', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['url' => '/sim/prodi/edp.php', 'icon' => '📝', 'label' => 'Evaluasi Diri (EDP)', 'key' => 'edp'],
        ['url' => '/sim/prodi/borang.php', 'icon' => '☁️', 'label' => 'Cloud Borang', 'key' => 'borang'],
        ['url' => '/sim/prodi/matriks.php', 'icon' => '🗂️', 'label' => 'Matriks Bukti', 'key' => 'matriks'],
        ['url' => '/sim/prodi/edom.php', 'icon' => '📝', 'label' => 'EDOM', 'key' => 'edom'],
        ['url' => '/sim/prodi/dosen.php', 'icon' => '📇', 'label' => 'Kelola Dosen', 'key' => 'dosenprodi'],
        ['url' => '/sim/prodi/todo.php', 'icon' => '✅', 'label' => 'To-Do Akreditasi', 'key' => 'todo'],
        ['url' => '/sim/prodi/prestasi.php', 'icon' => '🏅', 'label' => 'Bank Prestasi', 'key' => 'prestasi'],
        ['url' => '/sim/prodi/tracer-prodi.php', 'icon' => '🎓', 'label' => 'Tracer Prodi', 'key' => 'tracerprodi'],
        ['url' => '/sim/prodi/laporan-kinerja.php', 'icon' => '📄', 'label' => 'Laporan Kinerja', 'key' => 'lapprodi'],
        ['url' => '/sim/prodi/kerjasama.php', 'icon' => '🤝', 'label' => 'Kerja Sama / MoU', 'key' => 'kerjasama'],
        ['url' => '/sim/prodi/statistik-mahasiswa.php', 'icon' => '📊', 'label' => 'Statistik Mahasiswa', 'key' => 'statmhs'],
        ['url' => '/sim/prodi/riwayat.php', 'icon' => '📜', 'label' => 'Riwayat Audit', 'key' => 'riwayat'],
        ['url' => '/sim/prodi/dokumen.php', 'icon' => '📄', 'label' => 'Dokumen Mutu', 'key' => 'dokumen'],
        ['url' => '/sim/instrumen/view.php', 'icon' => '🧾', 'label' => 'Instrumen AMI', 'key' => 'instrumen'],
        ['url' => '/sim/panduan.php', 'icon' => '📘', 'label' => 'Buku Panduan', 'key' => 'panduan'],
    ],
    4 => [
        ['url' => '/sim/index.php', 'icon' => '📊', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['url' => '/sim/auditor/e-audit.php', 'icon' => '🔍', 'label' => 'E-Audit (Tugas Saya)', 'key' => 'eaudit'],
        ['url' => '/sim/auditor/verifikasi.php', 'icon' => '✅', 'label' => 'Verifikasi Koreksi', 'key' => 'verifikasi'],
        ['url' => '/sim/auditor/riwayat.php', 'icon' => '📜', 'label' => 'Riwayat Audit', 'key' => 'riwayat'],
        ['url' => '/sim/instrumen/view.php', 'icon' => '🧾', 'label' => 'Instrumen AMI', 'key' => 'instrumen'],
        ['url' => '/sim/panduan.php', 'icon' => '📘', 'label' => 'Buku Panduan', 'key' => 'panduan'],
    ],
    5 => [
        ['url' => '/sim/index.php', 'icon' => '📊', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['url' => '/sim/instrumen/view.php', 'icon' => '🧾', 'label' => 'Instrumen AMI', 'key' => 'instrumen'],
        ['url' => '/sim/panduan.php', 'icon' => '📘', 'label' => 'Buku Panduan', 'key' => 'panduan'],
    ],
];
$currentRoleMenus = $menus[Auth::role()] ?? [];

// ===== PENGELOMPOKAN KHUSUS ADMIN LPM (role 1) =====
$menuGroups = (Auth::role() == 1) ? [
    ['icon' => '👥', 'label' => 'SDM & Pengguna',  'keys' => ['users', 'gpm', 'komp', 'dosenman', 'import']],
    ['icon' => '🏆', 'label' => 'Akreditasi',      'keys' => ['prodi', 'akrintl', 'monmatriks', 'konflik']],
    ['icon' => '🔄', 'label' => 'AMI & RTM',       'keys' => ['jadwal', 'instrumen', 'rtmtl']],
    ['icon' => '📊', 'label' => 'Monev & IKU',     'keys' => ['iku', 'kuesioner', 'tracer', 'responden', 'surveipg']],
    ['icon' => '📁', 'label' => 'Dokumen Mutu',    'keys' => ['dokumen', 'kendok']],
    ['icon' => '📣', 'label' => 'Konten Publik',   'keys' => ['berita', 'pengaduan', 'profil', 'slider']],
    ['icon' => '📈', 'label' => 'Laporan',         'keys' => ['laporan', 'puslap']],
    ['icon' => '⚙️', 'label' => 'Sistem',          'keys' => ['logs', 'tenggat', 'email', 'panduan']],
] : null;

$byKey = [];
foreach ($currentRoleMenus as $m) $byKey[$m['key']] = $m;
?>

<style>
    /* ===== ACCORDION GROUPS ===== */
    .sb-group { margin-bottom: 2px; }
    .sb-group > summary {
        display: flex; align-items: center; gap: 10px;
        padding: 11px 16px; margin: 0 10px; border-radius: 12px;
        cursor: pointer; list-style: none;
        color: rgba(255,255,255,.75); font-weight: 600; font-size: 13.5px;
        transition: .25s;
    }
    .sb-group > summary::-webkit-details-marker { display: none; }
    .sb-group > summary:hover { background: rgba(255,255,255,.06); color: #fff; }
    .sb-group > summary .ic { font-size: 16px; }
    .sb-group > summary .chev { margin-left: auto; font-size: 9px; opacity: .6; transition: transform .3s var(--ease-out); }
    .sb-group[open] > summary { color: #E8C55A; }
    .sb-group[open] > summary .chev { transform: rotate(90deg); }
    .sb-sub {
        display: flex; flex-direction: column; gap: 2px;
        margin: 2px 10px 8px 24px; padding: 2px 0 2px 10px;
        border-left: 1px solid rgba(255,255,255,.12);
    }
    .sb-sub a { padding: 9px 12px !important; font-size: 13px !important; border-radius: 10px !important; margin: 0 !important; }
    .sb-sub a .ic { font-size: 14px; }

    /* Mode sidebar ciut: kelompok jadi icon saja */
    body.sim-collapsed .sb-group > summary { justify-content: center; padding: 11px 0; margin: 0 10px; }
    body.sim-collapsed .sb-group > summary .lbl,
    body.sim-collapsed .sb-group > summary .chev { display: none; }
    body.sim-collapsed .sb-sub { margin-left: 10px; border-left: none; padding-left: 0; }
</style>

<aside class="sim-sidebar">
    <div class="sb-head" style="padding:0 20px 16px;border-bottom:1px solid rgba(255,255,255,.1);">
        <?= Site::brand('sim') ?>
        <div class="sb-profile">
            <div class="sb-ava"><?= strtoupper(substr(Auth::user()['nama'], 0, 1)) ?></div>
            <div class="sb-txt">
                <strong><?= Security::e(Auth::user()['nama']) ?></strong>
                <small><?= Security::e(Auth::user()['email']) ?></small>
            </div>
        </div>
    </div>

    <div class="sb-label">MENU UTAMA</div>
    <nav class="sim-menu" style="margin-top:0;">

        <!-- Dashboard selalu di atas -->
        <a href="/sim/index.php" class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
            <span class="ic">📊</span><span class="lbl">Dashboard</span>
        </a>

        <?php if ($menuGroups): ?>
            <!-- ===== ADMIN: MODE KELOMPOK (ACCORDION) ===== -->
            <?php foreach ($menuGroups as $g):
                $items = array_values(array_filter(array_map(fn($k) => $byKey[$k] ?? null, $g['keys'])));
                if (empty($items)) continue;
                $hasActive = in_array($activeMenu, array_column($items, 'key'));
            ?>
            <details class="sb-group" <?= $hasActive ? 'open' : '' ?>>
                <summary>
                    <span class="ic"><?= $g['icon'] ?></span>
                    <span class="lbl"><?= $g['label'] ?></span>
                    <span class="chev">▶</span>
                </summary>
                <div class="sb-sub">
                    <?php foreach ($items as $menu): ?>
                    <a href="<?= $menu['url'] ?>" class="<?= $activeMenu === $menu['key'] ? 'active' : '' ?>">
                        <span class="ic"><?= $menu['icon'] ?></span><span class="lbl"><?= $menu['label'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- ===== ROLE LAIN: TETAP FLAT ===== -->
            <?php foreach ($currentRoleMenus as $menu): if ($menu['key'] === 'dashboard') continue; ?>
                <a href="<?= $menu['url'] ?>" class="<?= $activeMenu === $menu['key'] ? 'active' : '' ?>">
                    <span class="ic"><?= $menu['icon'] ?></span><span class="lbl"><?= $menu['label'] ?></span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <div style="margin-top:30px;border-top:1px solid rgba(255,255,255,.1);">
        <div class="sb-label">AKUN</div>
        <nav class="sim-menu" style="margin-top:0;">
            <a href="/index.php" target="_blank"><span class="ic">🌐</span><span class="lbl">Website Publik</span></a>
            <a href="/logout.php" class="sb-logout"><span class="ic">🚪</span><span class="lbl">Keluar</span></a>
        </nav>
        <div class="sb-ver">SIM-Mutu v4.0 • © <?= date('Y') ?></div>
    </div>
</aside>