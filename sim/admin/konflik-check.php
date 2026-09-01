<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();

/* Peta nama prodi (untuk kolom Prodi Asal) */
$asalMap = [];
foreach ($db->query("SELECT id_prodi, nama_prodi FROM prodi")->fetchAll() as $pp) {
    $asalMap[$pp['id_prodi']] = $pp['nama_prodi'];
}

$tugas = $db->query("SELECT pa.*, u.nama_lengkap auditor, u.id_prodi prodi_asal, p.nama_prodi prodi_audit, j.tahun_ami
    FROM penugasan_audit pa
    JOIN users u ON pa.id_auditor = u.id_user
    LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
    LEFT JOIN jadwal_ami j ON pa.id_jadwal = j.id_jadwal
    ORDER BY pa.tanggal_audit DESC")->fetchAll();

$nKonflik = 0;
foreach ($tugas as $t) if ($t['prodi_asal'] && $t['prodi_asal'] == $t['id_prodi']) $nKonflik++;

$simTitle = 'Cek Konflik Kepentingan';
$activeMenu = 'konflik';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">📋</div><div><h3 style="font-size:28px;"><?= count($tugas) ?></h3><p class="text-muted">Total Penugasan</p></div></div>
    <div class="stat-card"><div class="stat-icon <?= $nKonflik > 0 ? 'red' : 'green' ?>"><?= $nKonflik > 0 ? '⚠️' : '✅' ?></div><div><h3 style="font-size:28px;"><?= $nKonflik ?></h3><p class="text-muted">Konflik Terdeteksi</p></div></div>
</div>

<div class="card">
    <h3 style="margin-bottom:6px;">⚖️ Independensi Penugasan Audit</h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:16px;">Sistem membandingkan <strong>prodi asal auditor</strong> dengan <strong>prodi yang diaudit</strong>. Prinsip: auditor tidak boleh mengaudit prodi sendiri.</p>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Auditor</th><th>Prodi Asal</th><th>Prodi Diaudit</th><th>Tahun</th><th>Independensi</th></tr></thead>
            <tbody>
            <?php if (empty($tugas)): ?><tr><td colspan="5" style="text-align:center;padding:30px;" class="text-muted">Belum ada penugasan audit.</td></tr><?php endif; ?>
            <?php foreach ($tugas as $t):
                $konflik = $t['prodi_asal'] && $t['prodi_asal'] == $t['id_prodi'];
            ?>
                <tr>
                    <td><strong><?= Security::e($t['auditor']) ?></strong></td>
                    <td><?= $t['prodi_asal'] ? Security::e($asalMap[$t['prodi_asal']] ?? '—') : '—' ?></td>
                    <td><?= Security::e($t['prodi_audit']) ?></td>
                    <td><?= Security::e((string)($t['tahun_ami'] ?? '—')) ?></td>
                    <td><?= $konflik
                        ? '<span class="badge" style="background:#FEE2E2;color:#991B1B;">⚠️ KONFLIK — ganti auditor!</span>'
                        : '<span class="badge badge-unggul">✅ Independen</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="font-size:12.5px;margin-top:14px;">💡 Agar deteksi akurat, isi <strong>prodi asal</strong> pada akun auditor (role 4) di 👥 Manajemen Pengguna — biasanya prodi tempat dosen tersebut bernaung.</p>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>