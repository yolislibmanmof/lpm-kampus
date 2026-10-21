<?php
$menus = [
    1 => [
        ['url' => '/sim/index.php', 'icon' => '📊', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['url' => '/sim/admin/users.php', 'icon' => '👥', 'label' => 'Manajemen Pengguna', 'key' => 'users'],
        ['url' => '/sim/admin/penugasan-gpm.php', 'icon' => '🏢', 'label' => 'Penugasan GPM', 'key' => 'gpm'],
        ['url' => '/sim/admin/auditor-kompetensi.php', 'icon' => '🎖️', 'label' => 'Kompetensi Auditor', 'key' => 'komp'],
        
        // --- BARU: Ditambahkan ke kelompok Dokumen Mutu / Akreditasi ---
        ['url' => '/sim/admin/akreditasi.php', 'icon' => '🏆', 'label' => 'Manajemen Akreditasi', 'key' => 'akreditasi'],
        ['url' => '/sim/admin/standar.php', 'icon' => '📐', 'label' => 'Standar & Instrumen AMI', 'key' => 'standar'],
        
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
        ['url' => '/sim/admin/mitra.php', 'icon' => '🏛️', 'label' => 'Mitra & Pengakuan', 'key' => 'mitra'],
        ['url' => '/sim/admin/milestone.php', 'icon' => '📜', 'label' => 'Milestone Lembaga', 'key' => 'milestone'],
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
// PERUBAHAN: Menambahkan 'akreditasi' ke grup Akreditasi, dan 'standar' ke grup AMI & RTM
$menuGroups = (Auth::role() == 1) ? [
    ['icon' => '👥', 'label' => 'SDM & Pengguna',  'keys' => ['users', 'gpm', 'komp', 'dosenman', 'import']],
    ['icon' => '🏆', 'label' => 'Akreditasi',      'keys' => ['prodi', 'akreditasi', 'akrintl', 'monmatriks', 'konflik']],
    ['icon' => '🔄', 'label' => 'AMI & RTM',       'keys' => ['standar', 'jadwal', 'instrumen', 'rtmtl']],
    ['icon' => '📊', 'label' => 'Monev & IKU',     'keys' => ['iku', 'kuesioner', 'tracer', 'responden', 'surveipg']],
    ['icon' => '📁', 'label' => 'Dokumen Mutu',    'keys' => ['dokumen', 'kendok']],
    ['icon' => '📣', 'label' => 'Konten Publik',   'keys' => ['berita', 'pengaduan', 'profil', 'slider', 'mitra', 'milestone']],
    ['icon' => '📈', 'label' => 'Laporan',         'keys' => ['laporan', 'puslap']],
    ['icon' => '⚙️', 'label' => 'Sistem',          'keys' => ['logs', 'tenggat', 'email', 'panduan']],
] : null;

$byKey = [];
foreach ($currentRoleMenus as $m) $byKey[$m['key']] = $m;

function sidebarIcon(string $icon): string
{
    $names = [
        '📊' => 'chart',
        '👥' => 'users',
        '🏢' => 'building',
        '🎖️' => 'medal',
        '🏆' => 'trophy',
        '📐' => 'ruler',
        '📁' => 'folder',
        '📑' => 'files',
        '🗂️' => 'layers',
        '⚖️' => 'scale',
        '📅' => 'calendar',
        '🧾' => 'receipt',
        '📰' => 'news',
        '📩' => 'mail',
        '📈' => 'trend',
        '🖨️' => 'printer',
        '📱' => 'phone',
        '🎓' => 'graduation',
        '🎤' => 'mic',
        '💼' => 'briefcase',
        '🏫' => 'school',
        '📥' => 'inbox',
        '📇' => 'contact',
        '🏛️' => 'landmark',
        '🖼️' => 'image',
        '📜' => 'scroll',
        '🌍' => 'globe',
        '🌐' => 'globe',
        '⏰' => 'clock',
        '✉️' => 'mail',
        '📘' => 'book',
        '⚠️' => 'alert',
        '🤖' => 'bot',
        '📝' => 'pen',
        '☁️' => 'cloud',
        '✅' => 'check',
        '🏅' => 'medal',
        '📄' => 'document',
        '🤝' => 'handshake',
        '🔍' => 'search',
        '🔄' => 'refresh',
        '📣' => 'megaphone',
        '⚙️' => 'gear',
        '🚪' => 'door',
    ];

    $paths = [
        'chart' => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 21v-4h6v4M8 7h2m4 0h2M8 11h2m4 0h2"/>',
        'medal' => '<circle cx="12" cy="8" r="6"/><path d="m8.2 13-1.4 8 5.2-3 5.2 3-1.4-8"/>',
        'trophy' => '<path d="M8 21h8m-4-4v4M7 4h10v5a5 5 0 0 1-10 0V4Z"/><path d="M7 6H4v2a4 4 0 0 0 4 4m9-6h3v2a4 4 0 0 1-4 4"/>',
        'ruler' => '<path d="m3 17 14-14 4 4L7 21z"/><path d="m14 6 4 4m-7-1 2 2m-5 1 2 2m-5 1 2 2"/>',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'files' => '<path d="M8 7V4a2 2 0 0 1 2-2h8l4 4v12a2 2 0 0 1-2 2h-3"/><path d="M18 6h4m-4-4v4M4 7h10a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/>',
        'layers' => '<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 17l9 5 9-5"/>',
        'scale' => '<path d="M12 3v18m-7 0h14M5 7h14M7 7l-4 7h8L7 7Zm10 0-4 7h8l-4-7Z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'receipt' => '<path d="M4 3h16v18l-3-2-3 2-3-2-3 2-4-2V3Z"/><path d="M8 8h8M8 12h8M8 16h4"/>',
        'news' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h5v5H7zm8 0h2m-2 4h2m-10 4h10"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'trend' => '<path d="M3 3v18h18"/><path d="m7 14 4-4 4 4 6-7"/><path d="M16 7h5v5"/>',
        'printer' => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/>',
        'phone' => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/>',
        'graduation' => '<path d="m2 10 10-5 10 5-10 5-10-5Z"/><path d="M6 12v5c4 3 8 3 12 0v-5m4-2v7"/>',
        'mic' => '<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0m-7 7v5m-4 0h8"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4h8v3M3 12h18m-11 0v2h4v-2"/>',
        'school' => '<path d="m3 9 9-6 9 6-9 6-9-6Z"/><path d="M6 11v6c4 3 8 3 12 0v-6m3-2v7"/>',
        'inbox' => '<path d="M4 4h16l2 11v4H2v-4L4 4Z"/><path d="M2 15h6l2 3h4l2-3h6"/>',
        'contact' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M6 16a3 3 0 0 1 6 0m3-6h3m-3 4h3"/>',
        'landmark' => '<path d="m3 9 9-6 9 6M4 10h16M5 10v9m5-9v9m4-9v9m5-9v9M3 21h18"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'scroll' => '<path d="M8 21h12a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H8a4 4 0 0 0 0 8h12M8 3v8m4-3h5m-5 4h5m-5 4h5"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20Z"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'book' => '<path d="M4 4h12a4 4 0 0 1 4 4v12H8a4 4 0 0 1-4-4V4Z"/><path d="M4 16a4 4 0 0 1 4-4h12M8 4v8"/>',
        'alert' => '<path d="m10.3 3.9-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3.1l-8-14a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4m0 4h.01"/>',
        'bot' => '<rect x="4" y="7" width="16" height="13" rx="3"/><path d="M12 3v4m-5 5h.01M17 12h.01M8 16h8"/>',
        'pen' => '<path d="m12 20 9-9-4-4-9 9-1 5 5-1Z"/><path d="m15 8 4 4M4 20h4"/>',
        'cloud' => '<path d="M20 16.2A4.5 4.5 0 0 0 18 7.5a6 6 0 0 0-11.5 1A4 4 0 0 0 7 16h13Z"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8m-8 4h8"/>',
        'handshake' => '<path d="m8 12 3 3a2 2 0 0 0 3-1l1-1m-7-5 3-2 4 2 3-1 4 4-5 5a2 2 0 0 1-3 0l-1 1a2 2 0 0 1-3 0l-1-1a2 2 0 0 1-3 0l-4-4 4-5 5-2"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'refresh' => '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M5.6 9A7 7 0 0 1 18 6l2 6M4 12l2 6a7 7 0 0 0 12.4-3"/>',
        'megaphone' => '<path d="m3 11 18-5v12L3 13v-2Z"/><path d="m7 14 2 7h4l-2-6m10-7v10"/>',
        'gear' => '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.6a8 8 0 0 1-1.7 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.7-1l-1.7.6-1.4-2.4 1.4-1.1a7 7 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.6a8 8 0 0 1 1.7-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.7 1l1.7-.6 1.4 2.4-1.4 1.1a7 7 0 0 1 0 2Z"/>',
        'door' => '<path d="M4 21h16M6 21V3h12v18m-5-9h.01"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    ];

    $name = $names[$icon] ?? 'grid';
    $body = $paths[$name] ?? $paths['grid'];

    return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}
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

    .sim-sidebar .ic {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .sim-sidebar .sidebar-icon {
        display: block;
        width: 18px;
        height: 18px;
        flex: none;
    }

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
            <span class="ic"><?= sidebarIcon('📊') ?></span><span class="lbl">Dashboard</span>
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
                    <span class="ic"><?= sidebarIcon($g['icon']) ?></span>
                    <span class="lbl"><?= $g['label'] ?></span>
                    <span class="chev">▶</span>
                </summary>
                <div class="sb-sub">
                    <?php foreach ($items as $menu): ?>
                    <a href="<?= $menu['url'] ?>" class="<?= $activeMenu === $menu['key'] ? 'active' : '' ?>">
                        <span class="ic"><?= sidebarIcon($menu['icon']) ?></span><span class="lbl"><?= $menu['label'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- ===== ROLE LAIN: TETAP FLAT ===== -->
            <?php foreach ($currentRoleMenus as $menu): if ($menu['key'] === 'dashboard') continue; ?>
                <a href="<?= $menu['url'] ?>" class="<?= $activeMenu === $menu['key'] ? 'active' : '' ?>">
                    <span class="ic"><?= sidebarIcon($menu['icon']) ?></span><span class="lbl"><?= $menu['label'] ?></span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <div style="margin-top:30px;border-top:1px solid rgba(255,255,255,.1);">
        <div class="sb-label">AKUN</div>
        <nav class="sim-menu" style="margin-top:0;">
            <a href="/index.php" target="_blank"><span class="ic"><?= sidebarIcon('🌐') ?></span><span class="lbl">Website Publik</span></a>
            <a href="/logout.php" class="sb-logout"><span class="ic"><?= sidebarIcon('🚪') ?></span><span class="lbl">Keluar</span></a>
        </nav>
        <div class="sb-ver">SIM-Mutu v4.0 • © <?= date('Y') ?></div>
    </div>
</aside>