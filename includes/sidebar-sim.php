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
?>

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
        <?php foreach ($currentRoleMenus as $menu): ?>
            <a href="<?= $menu['url'] ?>" class="<?= $activeMenu === $menu['key'] ? 'active' : '' ?>">
                <span class="ic"><?= $menu['icon'] ?></span><span class="lbl"><?= $menu['label'] ?></span>
            </a>
        <?php endforeach; ?>
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